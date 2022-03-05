<?php

class Fahasa_Event_Helper_Jigsawpuzzle extends Mage_Core_Helper_Abstract {
    
    /* Trade pieces for fpoints */
    const TRADE_PIECE_RATIO = 200; /// one piece for 200 fpoints
    const TRADE_PIECE_MAX_PIECES = 5;
    const TRADE_PIECE_ACTION_TYPE = "";
    const TRADE_PIECE_ACTION_LOG = "accure fpoint";
    
    /* trade fpoints for pieces  */
    const TRADE_FPOINTS_RATIO = 1000; //// 1000 fpoints for one piece
    const TRADE_FPOINTS_MAX_PIECES = 5;
    const TRADE_FPOINTS_ACTION_LOG = "pay order f-point";
    const FPOINT_UPDATED_BY = "Game Jigsaw Puzzle";
    
    const GENERATE_PERMITED_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const GENERATE_RETRY = 10;
    
    const HISTORY_ACTION_TYPE = array(
        'trade_piece' => 'trade_piece',
        'trade_fpoint' => 'trade_fpoint',
        'mission_login' => 'mission_login',
        'mission_share_game' => 'mission_share_game',
        'mission_share_registration' => 'mission_share_registration',
        'mission_review' => 'mission_review',
        'mission_refer' => 'mission_refer'
    );
    
    const MISSION_LIMITS = array(
        'mission_share_game' => 2,
        'mission_share_registration' => 1,
        'mission_review' => 100,
        'mission_refer' => 100,
        'mission_review_codes' => 2,
        'mission_refer_codes' => 5
    );
    
    public function loadPlayerData() {
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$customer->getEmail()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        /// 2. Load active game
        $connection_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $query_game = "SELECT * FROM fhs_jigsawpuzzle_game game WHERE game.is_active=1 LIMIT 1;";
        $game = $connection_read->fetchRow($query_game);
        if (!$game || !$game['id']) {
            return array(
                'result'=> false,
                'error_type' => 'no_active_game'
            );
        }
        
        /// Store Game Id into session
        $session->setJigsawPuzzleId($game['id']);
        
        /// 2. Check if player ever play the game, 
        /// Yes, load data . No -> load default and save into db;
        $query_player = "SELECT * FROM fhs_jigsawpuzzle_player player WHERE game_id=:game_id AND player.customer_id=:customer_id LIMIT 1;";
        $query_binding = array(
            "game_id" => $game['id'],
            "customer_id" => $customer->getId(),
        );
        
        $player = $connection_read->fetchRow($query_player, $query_binding);
        
        $result = array(
            'result' => true,
            'player' => array(
                'id' => $customer->getId(),
                'firstname' => $customer->getData('firstname'),
                'lastname' => $customer->getData('lastname'),
                'refer_code' => $customer->getData('refer_code'),
                'default_player_data' => $game['default_player_data']
            )
        );
        
        if($player){
            $result['player']['player_data'] = $player['player_data'];
        }else{
            
            $query_insert = "INSERT INTO fhs_jigsawpuzzle_player (game_id, customer_id, player_data) "
                    . "VALUES(:game_id, :customer_id, :player_data); ";
            
            $query_binding = array(
                "game_id" => $game['id'],
                "customer_id" => $customer->getId(),
                "player_data" => $game['default_player_data']
            );
            
            $connection_write->query($query_insert, $query_binding);
            
            $result['player']['player_data'] = $game['default_player_data'];
        }
        
        return $result;
    }
    
    public function applyCode($code){
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$session->getJigsawPuzzleId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        /// 2. Load History that has this code
        $query_select = "SELECT * FROM fhs_jigsawpuzzle_player player "
            ." JOIN fhs_jigsawpuzzle_game game ON player.game_id = game.id AND game.id=:game_id AND game.is_active=1 "
            ." JOIN fhs_jigsawpuzzle_history history ON player.customer_id = history.player_id AND history.game_id=:game_id "
            ." WHERE player.customer_id=:customer_id AND history.piece_code=:piece_code;";
        
        $query_binding = array(
            "game_id" => $session->getJigsawPuzzleId(),
            "customer_id" => $customer->getId(),
            "piece_code" => $code
        );
        
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query_result = $connection->fetchRow($query_select, $query_binding);
        
        $result = array(
            'result' => false
        );
        
        /// 3. If there is a un-applied code, apply it
        if($query_result){
            $query_result['piece_code_applied'] = (int)$query_result['piece_code_applied'];
            if($query_result['piece_code_applied']){
                $result['error_type'] = 'used_code';
                return $result;
            }
            
            /// 4. Add a piece to player data
            $connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
            
            $added_result = $this->addRandomPlayerPiece($connection_write, $session->getJigsawPuzzleId()
                    , $customer->getId(), $query_result['player_data'], $query_result);
            
            if(!$added_result){
                $result['error_type'] = "fail_to_add_piece";
                return $result;
            }
            
            /// There is a piece code that is not applied ! We apply the code now
            $query_update = "UPDATE fhs_jigsawpuzzle_history SET piece_code_applied=1 "
                    . "WHERE game_id=:game_id AND player_id=:customer_id AND piece_code=:piece_code;";
            
            $query_binding = array(
                "game_id" => $session->getJigsawPuzzleId(),
                "customer_id" => $customer->getId(),
                "piece_code" => $code
            );
            
            $connection_write->query($query_update, $query_binding);
            
            //// Update player_data
            $this->updatePlayerData($connection_write, $session->getJigsawPuzzleId()
                , $customer->getId(), $added_result['player_data']);
            
            $result['result'] = true;
            $result['image_url'] = $added_result['image_url'];
            $result['player_data'] = $added_result['player_data'];
            $result['position_row'] = $added_result['position_row'];
            $result['position_col'] = $added_result['position_col'];
            
        }else{
            $result['error_type'] = "not_valid";
        }
        
        return $result;
    }
    
    public function loadHistory(){
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$session->getJigsawPuzzleId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        /// 2. Load History
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        $query_select = "SELECT piece_code, DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') as 'formated_created_at', 
            details, piece_code_applied FROM fhs_jigsawpuzzle_history WHERE game_id=:game_id 
            AND player_id=:player_id ORDER BY created_at DESC;";
        
        $query_binding = array(
            "game_id" => $session->getJigsawPuzzleId(),
            "player_id" => $customer->getId(),
        );
        
        $query_result = $connection->fetchAll($query_select, $query_binding);
        
        $result = array(
            'result' => true,
            'logs' => $query_result
        );
        
        return $result;
    }
    
    public function loadFpoint(){
         /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        /// 2. Load History
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        $query_select = "SELECT fpoint FROM fhs_customer_entity WHERE email=:email AND entity_id=:customer_id;";
        
        $query_binding = array(
            "customer_id" => $customer->getId(),
            "email" => $customer->getEmail()
        );
        
        $query_result = $connection->fetchRow($query_select, $query_binding);
        
        $result = array(
            'result' => true,
            'fpoint' => $query_result['fpoint']
        );
        
        return $result;
    }
    
    public function tradePiece($num_pieces, $row, $col){
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$customer->getEmail()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        /// 2. limit number of pieces
        $num_pieces = (int)$num_pieces;
        $num_pieces = $num_pieces < 0 ? 0: $num_pieces;
        $num_pieces = $num_pieces > self::TRADE_FPOINTS_MAX_PIECES ? self::TRADE_FPOINTS_MAX_PIECES: $num_pieces;
        
        $connection_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $result = array(
            'result' => false
        );
        
        /*
         *  Start queries
         */
        try {
            $connection_write->beginTransaction();
            /// 3. Remove pieces from player data
            $sql_select_player = "SELECT * FROM fhs_jigsawpuzzle_player player "
                    . " JOIN fhs_jigsawpuzzle_game game ON player.game_id=game.id AND game.is_active=1 "
                    . " WHERE game_id=:game_id AND customer_id=:customer_id LIMIT 1;";
            
            $sql_binding_player = array(
                "game_id" => $session->getJigsawPuzzleId(),
                "customer_id" => $customer->getId()
            );
            
            $result_player = $connection_read->fetchRow($sql_select_player, $sql_binding_player);
            if(!$result_player){
                $result['error_type'] = "substract_player_piece";
                return $result;
            }
            
            $new_player_data = $this->subtractPlayerPiece($num_pieces, $row, $col, $result_player['player_data']);
            if(!$new_player_data){
                $result['error_type'] = "substract_player_piece";
                return $result;
            }
            
            $num_pieces = $new_player_data['removed_count'];
            /// 4. Calculate Fpoints
            $fpoint = $num_pieces * self::TRADE_PIECE_RATIO;

            //// 5. Update player_data
            $this->updatePlayerData($connection_write, $session->getJigsawPuzzleId()
                , $customer->getId(), $new_player_data['player_data']);
            
            //// 6. Add Fpoints
            $details = "Đổi Mảnh: " . $num_pieces . " mảnh ghép lấy " . $fpoint . " Fpoint.";
            $description_fpoint = "Game Jigsaw Puzzle: " . $details;
            
            $this->addSubstractFpoints(true, $customer, $connection_read
                , $connection_write, $fpoint, $description_fpoint);
            
            /// 7. Add History Log: one log
            $action_type = self::HISTORY_ACTION_TYPE['trade_piece'];
            $this->addHistoryLogs($connection_write, $session->getJigsawPuzzleId(), $customer->getId() 
                , $action_type, array(null), $fpoint, $details, $num_pieces, null);
            
            $connection_write->commit();
            
            $result['result'] = true;
            $result['player_data'] = $new_player_data['player_data'];
            
        } catch (Exception $e) {
            $connection_write->rollback();
            $result['error_type'] = "system_error";
        }
        
        return $result;
    }
    
    public function tradeFpoint($num_pieces){
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$session->getJigsawPuzzleId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        /// 2. limit number of pieces
        $num_pieces = (int)$num_pieces;
        $num_pieces = $num_pieces < 0 ? 0: $num_pieces;
        $num_pieces = $num_pieces > self::TRADE_FPOINTS_MAX_PIECES ? self::TRADE_FPOINTS_MAX_PIECES: $num_pieces;
        
        if($num_pieces <= 0){
            return array(
                'result'=> false,
                'error_type' => 'not_enough_fpoint'
            );
        }
        
        /// 3. Calculate Fpoints
        $fpoint = $num_pieces * self::TRADE_FPOINTS_RATIO;
        
        $connection_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $result = array(
            'result' => false
        );
        
        try {
            $connection_write->beginTransaction();
            //// 4. Generate Codes
            $generated_codes = array();
            for($i=0; $i < $num_pieces; $i++){
                $code = $this->generatePieceCode($connection_read, $session->getJigsawPuzzleId(), $customer->getId());
                
                if($code){
                    $generated_codes[] = $code;
                }
            }
            
            if(count($generated_codes) != $num_pieces){
                $result['error_type'] = 'code_generation_failed';
                return $result;
            }
            
            //// 5. Subtract Fpoints
            $details = "Đổi F-point: " . self::TRADE_FPOINTS_RATIO . " F-point cho 1 mảnh ghép.";
            $description_fpoint = "Game Jigsaw Puzzle: " . $details;
            
            $result_substracted = $this->addSubstractFpoints(false, $customer, $connection_read
                , $connection_write, $fpoint, $description_fpoint);
            
            if(!$result_substracted){
                $result['error_type'] = 'not_enough_fpoint';
                return $result;
            }
            
            /// 7. Add History Log: one log
            $action_type = self::HISTORY_ACTION_TYPE['trade_fpoint'];
            $this->addHistoryLogs($connection_write, $session->getJigsawPuzzleId(), $customer->getId() 
                , $action_type, $generated_codes, $fpoint, $details, $num_pieces, null);
            
            $connection_write->commit();
            $result['result'] = true;
            $result['fpoint'] = $fpoint;
            $result['num_pieces'] = $num_pieces;
            
        } catch (Exception $e) {
            $connection_write->rollback();
            $result['error_type'] = "system_error: " . $e;
        }
        
        return $result;
    }
    
    public function updatePlayerData($connection_write, $game_id, $customer_id, $new_player_data){
        $query_update = "UPDATE fhs_jigsawpuzzle_player SET player_data=:player_data 
            WHERE game_id=:game_id AND customer_id=:customer_id;";
        
        $query_binding = array(
            "game_id" => $game_id,
            "customer_id" => $customer_id,
            "player_data" => $new_player_data
        );
        
        $connection_write->query($query_update, $query_binding);
    }
    
    public function addSubstractFpoints($to_add, $customer, $connection_read, $connection_write, $fpoint, $description){
        /// 1. Select Fpoint
        $sql_select_fpoint = "SELECT fpoint FROM fhs_customer_entity WHERE entity_id=:customer_id AND email=:email LIMIT 1;";
        $sql_select_fpoint_binding = array(
            'customer_id' => $customer->getId(),
            'email' => $customer->getEmail()
        );
        
        $result_fpoint = $connection_read->fetchRow($sql_select_fpoint, $sql_select_fpoint_binding);
        if(!$result_fpoint){
            return false;
        }
        
        $amountBefore = (int)$result_fpoint['fpoint'];
        
        if($to_add){
            $amountAfter = (int)$amountBefore + (int)$fpoint;
            $value = (int)$fpoint;
            $action = self::TRADE_PIECE_ACTION_LOG;
        }else{
            $amountAfter = (int)$amountBefore - (int)$fpoint;
            if($amountAfter < 0){
                return false;
            }
            
            $value = -(int)$fpoint;
            $action = self::TRADE_FPOINTS_ACTION_LOG;
        }
        
        /// 5. Add Fpoint log
        $sql_insert_fpoint_log = "INSERT INTO fhs_purchase_action_log(account, customer_id, action, value, "
            . "amountAfter, updateBy, lastUpdated, description, amountBefore, type) "
            . "VALUES(:account, :customer_id, :action, :value, :amountAfter, :updateBy, NOW(), :description, :amountBefore, :type);";
        
        $sql_insert_fpoint_log_binding = array(
            'account' => $customer->getEmail(),
            'customer_id' => $customer->getId(),
            'action' => $action,
            'value' => $value,
            'amountAfter' => $amountAfter,
            'updateBy' => self::FPOINT_UPDATED_BY,
            'description' => $description,
            'amountBefore' => $amountBefore,
            'type' => 'fpoint'
        );
        
        $connection_write->query($sql_insert_fpoint_log, $sql_insert_fpoint_log_binding);
        
        /// 2. Substract fpoint
        $sql_substract_fpoint = "UPDATE fhs_customer_entity SET fpoint = fpoint + :amount WHERE entity_id=:customer_id; ";
        $sql_substract_fpoint_binding = array(
            'amount' => $value,
            'customer_id' => $customer->getId()
        );
        
        $connection_write->query($sql_substract_fpoint, $sql_substract_fpoint_binding);
        
        return true;
    }
    
    public function addHistoryLogs($connection_write, $game_id, $customer_id, $action_type, $piece_codes
            , $fpoint, $details, $num_pieces, $mission_entity_id){
        
        $logs = array();
        foreach($piece_codes as $code){
            $logs[] = array(
                'game_id' => $game_id,
                'player_id' => $customer_id,
                'action_type' => $action_type,
                'piece_code' => $code,
                'fpoint' => $fpoint,
                'details' => $details,
                'piece_code_applied' => 0,
                'num_pieces' => $num_pieces,
                'mission_entity_id' => $mission_entity_id
            );
        }
        
        $connection_write->insertMultiple('fhs_jigsawpuzzle_history', $logs);
    }
    
    public function addRandomPlayerPiece($connection_write, $game_id, $customer_id, $player_data, $game_data){
        $result = null;
        /// 1. Randomly select a piece
        $player_data_decoded = json_decode($player_data, true);
        $pieces_data = $player_data_decoded["pieces"];
        if(!$pieces_data){
            return null;
        }
        
        /// Game Data
        $exlude_special_code = false;
        if($game_data['current_completed_player'] >= $game_data['max_completed_player']){
            $exlude_special_code = true;
        }
        
        $pos = $this->generateRandomPiece($pieces_data, $exlude_special_code);
        if(!$pos){
            return null;
        }
        
        $random_row = $pos['row'];
        $random_col = $pos['col'];
        $row_total = count($pieces_data);
        $col_total = count($pieces_data[0]);
        
        /// 2. Increase count of that piece
        $piece = $player_data_decoded["pieces"][$random_row][$random_col];
        if($piece){
            $piece['count'] = (int)$piece['count'] + 1;
        }
        
        $player_data_decoded["pieces"][$random_row][$random_col] = $piece;
        
        /// Check if image is full
        $total_pieces = (int)$row_total*(int)$col_total;
        $current_pieces = 0;
        
        for($i=0; $i < $row_total; $i++){
            for($y=0; $y < $col_total; $y++){
                $p = $player_data_decoded["pieces"][$i][$y];
                if($p && $p['count'] >= 1){
                    $current_pieces++;
                }
            }
        }
        
        if($current_pieces >= $total_pieces){
            $sql_picture = "UPDATE fhs_jigsawpuzzle_player player 
JOIN fhs_jigsawpuzzle_game game On player.game_id = game.id 
SET player.is_full_picture=1, player.game_completed_date=NOW(), game.current_completed_player = game.current_completed_player + 1
WHERE player.game_id=:game_id AND player.customer_id=:customer_id AND player.is_full_picture = 0;";
            
            $sql_binding = array(
                'game_id' => $game_id,
                'customer_id' => $customer_id
            );
            
            $connection_write->query($sql_picture, $sql_binding);
        }
        
        $result = array(
            'player_data' => json_encode($player_data_decoded),
            'position_row' => $random_row,
            'position_col' => $random_col
        );
        
        return $result; 
    }
    
    public function subtractPlayerPiece($num_pieces, $row, $col, $player_data){
        /// 1. Randomly select a piece
        $player_data_decoded = json_decode($player_data, true);
        
        if($player_data_decoded['pieces'] && $player_data_decoded['pieces'][$row] 
                && $player_data_decoded['pieces'][$row][$col]){
            
            $piece = $player_data_decoded["pieces"][$row][$col];
        }
        
        /// 2. Decrease count of that piece
        $result = null;
        
        if($piece && $piece['count'] > 0){
            $old_count = (int)$piece['count'];
            $piece['count'] = $old_count - (int)$num_pieces;
            $piece['count'] = $piece['count'] < 0 ? 0 : $piece['count'];
            $player_data_decoded["pieces"][$row][$col] = $piece;
            
            $result = array(
                'player_data' => json_encode($player_data_decoded),
                'removed_count' => (int)($old_count - $piece['count'])
            );
        }
        
        return $result; 
    }
    
    /*
     *  Code Generation
     */
    function generateRandomPiece($pieces_data, $exlude_last_piece = false){
        if(!$pieces_data[0]){
            return null;
        }
        
        $pos = array(
            'row' => 0,
            'col' => 0,
        );
        
        $row_total = count($pieces_data);
        $col_total = count($pieces_data[0]);
        
        if(!$exlude_last_piece){
            $pos['row'] = mt_rand(0, $row_total-1);
            $pos['col'] = mt_rand(0, $col_total-1);
            //Mage::log("Normal Random", null, "buffet.log");
            return $pos;
        }
        
        /// find the position of last piece (can be of any piece that count =  0)
        $last_row = 0;
        $last_col = 0;
        for($i=0; $i < $row_total; $i++){
            for($y=0; $y < $col_total; $y++){
                $piece = $pieces_data[$i][$y];
                if((int)$piece['count'] == 0){
                    $last_row = $i;
                    $last_col = $y;
                    break;
                }
            }
        }
        
        do {
            $pos['row'] = mt_rand(0, $row_total-1);
            $pos['col'] = mt_rand(0, $col_total-1);
        }while($pos['row'] == $last_row && $pos['col']==$last_col);
        
        //Mage::log("Normal Random with Exlusion: " . $last_row . " - ". $last_col, null, "buffet.log");
        
        return $pos;
    }
    
    function generateString($input_length, $strength = 16) {
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = self::GENERATE_PERMITED_CHARS[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
        
        return $random_string;
    }
    
    function generatePieceCode($connection_read, $game_id, $customer_id){
        
        $sql_select_fpoint = "SELECT * FROM fhs_jigsawpuzzle_history WHERE game_id=:game_id AND player_id=:player_id;";
        $sql_select_fpoint_binding = array(
            "game_id" => $game_id,
            "player_id" => $customer_id
        );
        
        $result_logs = $connection_read->fetchRow($sql_select_fpoint, $sql_select_fpoint_binding);
        
        $old_codes = array();
        foreach($result_logs as $log){
            $old_codes[] = $log['piece_code'];
        }
        
        $piece_code = null;
        $input_length = strlen(self::GENERATE_PERMITED_CHARS);
        
        for($i=0; $i < self::GENERATE_RETRY; $i){
            $piece_code = $this->generateString($input_length, 10);
            if(!in_array($piece_code, $old_codes)){
                break;
            }
        }
        
        return $piece_code;
    }
    
    /*
     *  Missions
     */
    public function checkMissions(){
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$session->getJigsawPuzzleId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        /// 2. Load active game
        $connection_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        /// Mission login
        $mission_login_result = $this->checkMissionLogin($connection_read, $connection_write
                , $session->getJigsawPuzzleId(), $customer->getId());
        
        if(!$mission_login_result){
            return array(
                'result'=> false,
                'error_type' => 'mission_login_failed'
            );
        }
        
        /// Mission Refer
        $mission_refer_result = $this->checkMissionRefer($connection_read, $connection_write
                , $session->getJigsawPuzzleId(), $customer->getId());
        
        if(!$mission_refer_result){
            return array(
                'result'=> false,
                'error_type' => 'mission_refer_failed'
            );
        }
        
        /// Mission Review
        $mission_review_result = $this->checkMissionReview($connection_read, $connection_write
                , $session->getJigsawPuzzleId(), $customer->getId());
        
        if(!$mission_review_result){
            return array(
                'result'=> false,
                'error_type' => 'mission_review_failed'
            );
        }
        
        return array(
            'result'=> true
        );
    }
    
    public function checkMissionLogin($connection_read, $connection_write, $game_id, $player_id){
        // Conditions: a) logged in b) has a log in histry with action_type='mission_login'
        $query_check = "SELECT * FROM fhs_jigsawpuzzle_history "
            ." WHERE action_type=:action_type AND CURDATE() = DATE(created_at) AND game_id=:game_id AND player_id=:player_id;";
        
        $query_binding = array(
            'action_type' => self::HISTORY_ACTION_TYPE['mission_login'],
            'game_id' => $game_id,
            'player_id' => $player_id
        );
        
        $result_check = $connection_read->fetchRow($query_check, $query_binding);
        
        if(!$result_check){
            $generated_code = $this->generatePieceCode($connection_read, $game_id, $player_id);
            if(!$generated_code){
                return false;
            }
            
            $details = "Nhiệm Vụ Đăng Nhập: thưởng 1 mảnh ghép.";
            
            $this->addHistoryLogs($connection_write, $game_id, $player_id, self::HISTORY_ACTION_TYPE['mission_login'], array($generated_code)
            , 0, $details, 0, null);
        }
        
        return true;
    }
    
    public function doMissionShareGame(){
        /// Conditions: MAX 2 per day
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$session->getJigsawPuzzleId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        $game_id = $session->getJigsawPuzzleId();
        // Conditions: a) logged in b) has a log in histry with action_type share game
        $query_check = "SELECT * FROM fhs_jigsawpuzzle_history "
            ." WHERE action_type=:action_type AND CURDATE() = DATE(created_at) AND game_id=:game_id AND player_id=:player_id;";
        
        $query_binding = array(
            'action_type' => self::HISTORY_ACTION_TYPE['mission_share_game'],
            'game_id' => $game_id,
            'player_id' => $customer->getId()
        );
        
        $connection_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $logs_result = $connection_read->fetchAll($query_check, $query_binding);
        $logs_count = count($logs_result);
        
        $result = array(
            'result' => false
        );
        
        if($logs_count < self::MISSION_LIMITS['mission_share_game']){
            $generated_code = $this->generatePieceCode($connection_read, $game_id, $customer->getId());
            if(!$generated_code){
                $result['error_type'] = "no_generated_code";
                return $result;
            }
            
            $details = "Nhiệm Vụ Chia Sẽ Game: thưởng 1 mảnh ghép.";
            
            $connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $this->addHistoryLogs($connection_write, $game_id, $customer->getId(), self::HISTORY_ACTION_TYPE['mission_share_game']
                    , array($generated_code), 0, $details, 0, null);
            
        }
        
        $result['result'] = true;
        return $result;
    }
    
    public function doMissionShareRegistration(){
        /// Conditions: MAX 1 per day
        /// 1. Check if player login, load session
        $session = Mage::getSingleton('customer/session');
        $customer = $session->getCustomer();
        if (!$customer || !$session->getJigsawPuzzleId()) {
            return array(
                'result'=> false,
                'error_type' => 'no_login'
            );
        }
        
        $game_id = $session->getJigsawPuzzleId();
        // Conditions: a) logged in b) has a log in histry with action_type share registration
        $query_check = "SELECT * FROM fhs_jigsawpuzzle_history "
            ." WHERE action_type=:action_type AND CURDATE() = DATE(created_at) AND game_id=:game_id AND player_id=:player_id;";
        
        $query_binding = array(
            'action_type' => self::HISTORY_ACTION_TYPE['mission_share_registration'],
            'game_id' => $game_id,
            'player_id' => $customer->getId()
        );
        
        $connection_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $logs_result = $connection_read->fetchAll($query_check, $query_binding);
        $logs_count = count($logs_result);
        
        $result = array(
            'result' => false
        );
        
        if($logs_count < self::MISSION_LIMITS['mission_share_registration']){
            $generated_code = $this->generatePieceCode($connection_read, $game_id, $customer->getId());
            if(!$generated_code){
                $result['error_type'] = "no_generated_code";
                return $result;
            }
            
            $details = "Nhiệm Vụ Chia Sẽ Link Đăng Ký: thưởng 1 mảnh ghép.";
            
            $connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $this->addHistoryLogs($connection_write, $game_id, $customer->getId(), self::HISTORY_ACTION_TYPE['mission_share_registration']
                    , array($generated_code), 0, $details, 0, null);
            
        }
        
        $result['result'] = true;
        return $result;
    }
    
    public function checkMissionRefer($connection_read, $connection_write, $game_id, $player_id){
        
        /// 1. list all orders by this customer that has refer code used
        $sql_orders = "SELECT ce.refer_code, fo.entity_id FROM fhs_sales_flat_order fo "
            ." JOIN fhs_jigsawpuzzle_game jg ON jg.id = :game_id AND jg.is_active=1 "
            ." JOIN fhs_customer_entity ce ON fo.coupon_code = ce.refer_code "
            ." WHERE ce.entity_id = :customer_id AND fo.status='complete' "
            ." AND (fo.created_at between jg.start_date and jg.end_date) ;";
        
        $sql_orders_bindings = array(
            'game_id' => $game_id,
            'customer_id' => $player_id,
        );
        
        $sql_orders_results = $connection_read->fetchAll($sql_orders, $sql_orders_bindings);
        /// If there is no approved reviews, don't do any thing
        if(count($sql_orders_results) <= 0){
            return true;
        }
        
        /// 2. Check if these orders are added to game history logs
        $order_ids = array();
        foreach($sql_orders_results as $order){
            $order_ids[] = $order['entity_id']; 
        }
        
        $order_ids_str = implode(",", $order_ids);
        $action_type = self::HISTORY_ACTION_TYPE['mission_refer'];
        
        $sql_history = "select mission_entity_id from fhs_jigsawpuzzle_history 
            where action_type=:action_type and game_id=:game_id 
            and player_id=:player_id and mission_entity_id in (". $order_ids_str . ");"; 
        
        $sql_history_bindings = array(
            'action_type' => $action_type,
            'game_id' => $game_id,
            'player_id' => $player_id
        );
        
        $all_order_logs = $connection_read->fetchAll($sql_history, $sql_history_bindings);
        $logged_order_ids = array();
        foreach($all_order_logs as $log){ 
            $logged_order_ids[] = $log['mission_entity_id']; 
        }
        
        /// 3. Calculate diff order ids
        $diff_order_ids = array_diff($order_ids, $logged_order_ids);
        
        foreach($diff_order_ids as $order_id){
            $details = "Nhiệm Vụ Mã Giới Thiệu: thưởng " 
                    . self::MISSION_LIMITS['mission_refer_codes'] 
                    . " mảnh ghép cho đơn hàng (ID: " . $order_id . ").";
            
            /// 4. For each diff order, generate # of codes
            $generated_codes = array(); 
            for($i=0; $i < self::MISSION_LIMITS['mission_refer_codes']; $i++){
                $code = $this->generatePieceCode($connection_read, $game_id, $player_id); 
                
                if($code){
                    $generated_codes[] = $code; 
                }
            }
            
            if(count($generated_codes) != self::MISSION_LIMITS['mission_refer_codes'] ){
                return false;
            }
            
            $this->addHistoryLogs($connection_write, $game_id, $player_id, $action_type, $generated_codes
                , 0, $details, 0, $order_id);
        }
        
        return true;
    }
    
    public function checkMissionReview($connection_read, $connection_write, $game_id, $player_id){
        
        /// 1. list all reviews by this customer within game periods, that are approved
        $query_reviews = "SELECT entity_pk_value FROM fhs_review r 
            JOIN fhs_review_detail rd ON rd.review_id = r.review_id 
            JOIN fhs_jigsawpuzzle_game jg ON jg.id = :game_id AND jg.is_active=1 
            WHERE r.status_id = 1 AND (r.created_at between jg.start_date AND jg.end_date)
            AND customer_id=:customer_id LIMIT ". self::MISSION_LIMITS['mission_review'] . " ;";
        
        $query_bindings = array(
            'game_id' => $game_id,
            'customer_id' => $player_id
        );
        
        $all_players_approved_reviews = $connection_read->fetchAll($query_reviews, $query_bindings);
        /// If there is no approved reviews, don't do any thing
        if(count($all_players_approved_reviews) <= 0){
            return true;
        }
        
        /// 2. Now, check if these reviews, by this customer, are added to game history logs
        $reviewed_product_ids = array();
        foreach($all_players_approved_reviews as $review){
            $reviewed_product_ids[] = $review['entity_pk_value'];
        }
        
        $reviewed_product_ids_str = implode(",", $reviewed_product_ids);
        
        $action_type = self::HISTORY_ACTION_TYPE['mission_review'];
        
        $sql_history = "select mission_entity_id from fhs_jigsawpuzzle_history 
            where action_type=:action_type and game_id=:game_id 
            and player_id=:player_id and mission_entity_id in (". $reviewed_product_ids_str . ");"; 
        
        $sql_bindings = array(
            'action_type' => $action_type,
            'game_id' => $game_id,
            'player_id' => $player_id
        );
        
        $all_review_logs = $connection_read->fetchAll($sql_history, $sql_bindings);
        $logged_product_ids = array();
        foreach($all_review_logs as $log){ 
            $logged_product_ids[] = $log['mission_entity_id']; 
        }
        
        /// 3. Calculate product ids
        $diff_product_ids = array_diff($reviewed_product_ids, $logged_product_ids);
        
        /// 4. For each diff products, generate a code, add to log
        foreach($diff_product_ids as $prod_id){
            /// 4. For each diff order, generate # of codes
            $generated_codes = array(); 
            for($i=0; $i < self::MISSION_LIMITS['mission_review_codes']; $i++){
                $code = $this->generatePieceCode($connection_read, $game_id, $player_id); 
                if($code){
                    $generated_codes[] = $code; 
                }
            }
            
            if(count($generated_codes) != self::MISSION_LIMITS['mission_review_codes'] ){
                return false;
            }
            
            $details = "Nhiệm Vụ Review: thưởng "
                . self::MISSION_LIMITS['mission_review_codes']
                . " mảnh ghép cho việc review sản phẩm (ID: " . $prod_id . ");";
            
            $this->addHistoryLogs($connection_write, $game_id, $player_id, $action_type, $generated_codes
                , 0, $details, 0, $prod_id);
        }
        
        return true;
    }

}

