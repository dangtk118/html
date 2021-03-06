
const $JSP_LOADING = $jq("#jsp-loading");
const $JSP_LOADED = $jq("#jsp-loaded");
const $JSP_LOGIN = $jq("#jsp-login");
const $PIECES = $jq("#jsp-picture");
const $JSP_INPUT_CODE_PIECE = $jq("#jsp-input-code-piece");

const URL_LOAD_PLAYER = "/event/jigsawpuzzle/loadplayer";
const URL_APPLY_CODE = "/event/jigsawpuzzle/applycode";
const URL_LOAD_HISTORY = "/event/jigsawpuzzle/loadhistory";
const URL_TRADE_PIECE = "/event/jigsawpuzzle/tradepiece";
const URL_LOAD_FPOINT = "/event/jigsawpuzzle/loadfpoint";
const URL_TRADE_FPOINT = "/event/jigsawpuzzle/tradefpoint";

const URL_CHECK_MISSIONS = "/event/jigsawpuzzle/checkmissions";

const TRADE_PIECE_RATIO = 200;
const TRADE_PIECE_MAX_PIECES = 5;

const TRADE_FPOINTS_RATIO = 1000; //// 1000 fpoints for one piece
const TRADE_FPOINTS_MAX_PIECES = 5;

let my_player_data = null;
let trade_piece_data = {
    count: 1,
    row: 0,
    col: 0
};

initializeGame();

function initializeGame() {
    $JSP_LOADING.show();
    $JSP_LOADED.hide();

    getPlayerData(function (result, error_type) {
        $JSP_LOADING.hide();
        
        if (result) {
            $JSP_LOADED.show();
            checkMissions();
            runMissionSwiper();
            return;
        }
        
        switch(error_type){
            case 'no_login':
                $JSP_LOGIN.show();
                break;
            case 'no_active_game':
                $jq("#jsp-no-game").show();
                break;
            default:
                alert("System Error: " + error_type);
        }
    });
}

/*
 * Effects
 */
const EFFECT_PIECE_TIMEOUT = 10000;
const EFFECT_PIECE_STAR_COUNT = 100;
const EFFECT_PIECE_STAR_SPAKLE = 20;

function createNewPieceStar(body, size, color) {

    let $new_star = $jq("<div class='star'></div>").css({
        top: (Math.random() * 100) + '%',
        left: (Math.random() * 100) + '%',
        webkitAnimationDelay: (Math.random() * EFFECT_PIECE_STAR_SPAKLE) + 's',
        mozAnimationDelay: (Math.random() * EFFECT_PIECE_STAR_SPAKLE) + 's',
        color: color,
        'margin-left': '-15px',
        'margin-top': '-15px'
    }).addClass(size).appendTo(body);
};

function createNewPieceEffect($piece) {

    let $body = $piece.find(".jsp-piece-stars");
    for (var i = 0; i < EFFECT_PIECE_STAR_COUNT; i++) {
        let size = 'small';

        if (i % 2 === 0) {
            size = 'small';
        } else if (i % 3 === 0) {
            size = 'medium';
        } else {
            size = 'large';
        }

        let color = randomizePieceEffectColor();
        createNewPieceStar($body, size, color);
    }

    $piece.addClass('flickering-piece');

    setTimeout(function () {
        $piece.removeClass('flickering-piece');
        $body.empty();
    }, EFFECT_PIECE_TIMEOUT);
}

function randomizePieceEffectColor() {
    var x = Math.round(0xffffff * Math.random()).toString(16);
    var y = (6 - x.length);
    var z = "000000";
    var z1 = z.substring(0, y);
    var color = "#" + z1 + x;

    return color;
}

/*
 *  Game UI/UX functions
 */
/// Trade Piece
function openTradePiecePanel(to_close, row, col) {

    $jsp_trade_piece_panel = $jq("#jsp-trade-piece-panel");

    if ($jsp_trade_piece_panel.is(":visible")) {
        to_close = true;
    }

    if (to_close) {
        $jsp_trade_piece_panel.hide();
    } else {
        let piece_data;
        if (my_player_data['pieces'] && my_player_data['pieces'][row] && my_player_data['pieces'][row][col]) {
            piece_data = my_player_data['pieces'][row][col];
        }

        if (!piece_data) {
            openAlertPanel("Game L???i", "Kh??ng m??? ???????c h???p tho???i.");
        } else {
            setTradePiecePanel(piece_data, row, col);
            $jsp_trade_piece_panel.show();
            openTradeFpointPanel(true);
            openHistoryPanel(true);
        }
    }
}

const $JSP_TRADE_PIECE_INPUT = $jq("#jsp-trade-piece-input");
$JSP_TRADE_PIECE_INPUT.bind('keyup mouseup', function () {

    let _value = parseInt($JSP_TRADE_PIECE_INPUT.val());
    _value = _value < 0 ? 0 : _value;
    _value = _value > $JSP_TRADE_PIECE_INPUT.attr('max') ? $JSP_TRADE_PIECE_INPUT.attr('max') : _value;
    $JSP_TRADE_PIECE_INPUT.val(_value);

    let _total = _value * TRADE_PIECE_RATIO;
    let _text = _value + " x " + TRADE_PIECE_RATIO + " = " + _total + " F-point";
    $jq("#jsp-trade-piece-result").text(_text);

    trade_piece_data['count'] = _value;
});

function setTradePiecePanel(piece_data, row, col) {

    $jq("#jsp-trade-piece-panel").find("img").attr('src', piece_data['img_open']);
    $jq("#jsp-trade-piece-count").text("B???n ??ang c?? "
            + piece_data['count'] + " m???nh gh??p.");

    let max = piece_data['count'] > TRADE_PIECE_MAX_PIECES ? TRADE_PIECE_MAX_PIECES : piece_data['count'];

    $JSP_TRADE_PIECE_INPUT.val("1");
    $JSP_TRADE_PIECE_INPUT.attr({
        'value': 1,
        'min': 1,
        'max': max
    });

    $JSP_TRADE_PIECE_INPUT.trigger('keyup');

    trade_piece_data['row'] = row;
    trade_piece_data['col'] = col;
}

//// Trade Fpoint
function openTradeFpointPanel(to_close) {
    $jsp_trade_fpoint_panel = $jq("#jsp-trade-fpoint-panel");

    if ($jsp_trade_fpoint_panel.is(":visible")) {
        to_close = true;
    }

    if (to_close) {
        $jsp_trade_fpoint_panel.hide();
    } else {
        let $btn = $jq("#jsp-button-open-trade-fpoint");
        $btn.attr("disabled", true);
        
        loadFpoint(function (result, fpoint) {
            $btn.attr("disabled", false);
            
            if (result) {
                setTradeFpointPanel(fpoint);
                $jsp_trade_fpoint_panel.show();
                openTradePiecePanel(true);
                openHistoryPanel(true);
            } else {
                openAlertPanel("Game L???i", "Kh??ng t???i ???????c d??? li???u !");
            }
        });
    }
}

const $JSP_TRADE_FPOINT_INPUT = $jq("#jsp-trade-fpoint-input");
$JSP_TRADE_FPOINT_INPUT.bind('keyup mouseup', function () {
    let _value = parseInt($JSP_TRADE_FPOINT_INPUT.val());
    _value = _value < 0 ? 0 : _value;
    _value = _value > $JSP_TRADE_FPOINT_INPUT.attr('max') ? $JSP_TRADE_FPOINT_INPUT.attr('max') : _value;
    $JSP_TRADE_FPOINT_INPUT.val(_value);

    let _total = _value * TRADE_FPOINTS_RATIO;
    let _text = _value + " x " + TRADE_FPOINTS_RATIO + " = " + _total + " F-point";
    $jq("#jsp-trade-fpoint-result").text(_text);

});

function setTradeFpointPanel(fpoint) {

    $jq("#jsp-trade-fpoint-info").text("B???n ??ang c?? " + formatNumber(fpoint) + " F-Point.");

    let possible_pieces = Math.round(fpoint / TRADE_FPOINTS_RATIO);
    let max = possible_pieces > TRADE_FPOINTS_MAX_PIECES ? TRADE_FPOINTS_MAX_PIECES : possible_pieces;

    $JSP_TRADE_FPOINT_INPUT.val("1");
    $JSP_TRADE_FPOINT_INPUT.attr({
        'value': 1,
        'min': 1,
        'max': max
    });

    $JSP_TRADE_FPOINT_INPUT.trigger('keyup');
}

//// History Panel
function openHistoryPanel(to_close) {
    $jsp_history_panel = $jq("#jsp-history-panel");

    if ($jsp_history_panel.is(":visible")) {
        to_close = true;
    }

    if (to_close) {
        $jsp_history_panel.hide();
    } else {
        openTradePiecePanel(true);
        openTradeFpointPanel(true);

        let $btn = $jq("#jsp-button-open-history");
        $btn.attr("disabled", true);

        openWaitingPanel();
        getHistoryLogs(function (result) {
            openWaitingPanel(true);
            $jsp_history_panel.show();
            $btn.attr("disabled", false);
        });
    }
}

function openAlertPanel(title, body, to_close) {
    $jsp_alert_panel = $jq("#jsp-alert-panel");

    if ($jsp_alert_panel.is(":visible")) {
        to_close = true;
    }

    if (to_close) {
        $jsp_alert_panel.hide();
    } else {
        $jq("#jsp-alert-header").text(title);
        $jq("#jsp-alert-body").text(body);

        $jsp_alert_panel.show();
    }
}

function openWaitingPanel(to_close) {
    $jsp_waiting_panel = $jq("#jsp-waiting-panel");

    if ($jsp_waiting_panel.is(":visible")) {
        to_close = true;
    }

    if (to_close) {
        $jsp_waiting_panel.hide();
    } else {
        $jsp_waiting_panel.show();
    }
}

function showMessageBox(msg, timeout = 5000) {

    let $box = $jq("#jsp-message-box");
    $box.show();
    $box.text(msg);
    setTimeout(function () {
        $box.hide();
    }, timeout);
}

function inputPieceCode(e) {
    let value = $JSP_INPUT_CODE_PIECE.val();
    if (!value) {
        openAlertPanel("Th??ng B??o", "Xin vui l??ng nh???p m??.");
        return;
    }

    //// Apply Code
    openWaitingPanel();
    let $btn = $jq("#jsp-input-code-button");
    $btn.attr("disabled", true);

    applyPieceCode(value, function (result, error_type) {
        openWaitingPanel(true);
        if (result) {
            $JSP_INPUT_CODE_PIECE.val("");
            showMessageBox("B???n nh???n ???????c m???t m???nh gh??p m???i !")
        } else {
            switch (error_type) {
                case 'used_code':
                    openAlertPanel("Th??ng B??o", "M?? ???? ???????c s??? d???ng !");
                    break;
                case 'fail_to_add_piece':
                    openAlertPanel("Th??ng B??o", "L???i h??? th???ng. Kh??ng th??? th??m m???nh gh??p.");
                    break;
                default:
                    openAlertPanel("Th??ng B??o", "M?? kh??ng h???p l??? !");
            }
        }

        $btn.attr("disabled", false);
    });
}

function displayPicture() {
    $PIECES.empty();
    let pieces = my_player_data['pieces'];

    for (let i = 0; i < pieces.length; i++) {
        let $row = $jq("<div class='row' style='margin:0px !important'></div>");

        for (let y = 0; y < pieces[i].length; y++) {
            let piece = pieces[i][y];

            let $piece;
            if (piece['count'] >= 1) {
                $piece = $jq("<div class='col-xs-4 col-md-4 jsp-piece jsp-piece-open'><img src='"
                        + piece['img_open'] + "'><div class='jsp-piece-count'>"
                        + piece['count'] + "</div><div class='jsp-piece-stars'></div></div>");

                $piece.click(function () {
                    openTradePiecePanel(false, i, y);
                });

                $piece.hover(function () {
                    $jq(this).addClass('hovering-piece');
                }, function () {
                    $jq(this).removeClass('hovering-piece');
                });

            } else {
                $piece = $jq("<div class='col-xs-4 col-md-4 jsp-piece'><img src='"
                        + piece['img_hidden'] + "'><div class='jsp-piece-stars'></div></div>");
            }
            $row.append($piece);
        }
        $PIECES.append($row);
    }
}

function toCopyPieceCode(target, code) {
    $JSP_INPUT_CODE_PIECE.val(code);
    //inputTemp.select();
    //document.execCommand("copy");
    openAlertPanel("Th??ng B??o", "???? copy m??: " + $JSP_INPUT_CODE_PIECE.val());
    openHistoryPanel(true);
}

function updatePlayerData(player_data) {
    for (let i = 0; i < my_player_data['pieces'].length; i++) {
        for (let y = 0; y < my_player_data['pieces'][i].length; y++) {
            if (player_data['pieces'] && player_data['pieces'][i]
                    && player_data['pieces'][i][y]) {

                my_player_data['pieces'][i][y]['count'] = player_data['pieces'][i][y]['count'];
            }
        }
    }
}

/*
 * Mission Panel
 */
const INITIAL_PANEL_ID = "jsp-mission-panel-login";
let $all_mission_panels = $jq(".swiper-slide");
let $mission_panels = $jq(".jsp-mission-panel");
selectMissionPanel(INITIAL_PANEL_ID);

function changeMissionPanel(e) {
    let $this = $jq(e);
    let id = $this.attr('data-panel');
    selectMissionPanel(id);
}

function selectMissionPanel(cur_panel_id) {
    $all_mission_panels.removeClass('swiper-slide-selected');
    $all_mission_panels.each(function (index, e) {
        let $e = $jq(e);
        let panel_id = $e.attr('data-panel');

        if (cur_panel_id == panel_id) {
            $e.addClass('swiper-slide-selected');
        }
    });

    $mission_panels.each(function (index, panel) {
        $this = $jq(panel);
        let id = $this.attr('id');

        if (id == cur_panel_id) {
            $this.show();
        } else {
            $this.hide();
        }
    });
}

/*
 *  Game API
 */
function getPlayerData(callback) {
    $jq.ajax({
        url: URL_LOAD_PLAYER,
        contentType: "application/json",
        method: 'post',
        success: function (data) {
            if (!data['result']) {
                return callback(false, data['error_type']);
            }
            
            if (data['player']) {
                $jq("#jsp-player-lastname").text(data['player']['lastname']);
                $jq("#jsp-player-firstname").text(data['player']['firstname']);
                $jq("#jsp-input-refer-code").val(data['player']['refer_code']);
                
                let _player_data = JSON.parse(data['player']['player_data']);
                my_player_data = JSON.parse(data['player']['default_player_data']);
                
                updatePlayerData(_player_data);
                /// Display Picture
                displayPicture();
                
                /// Pre-load picture pieces
                $picture_preload = $jq("#jsp-picture-preload");
                for (let i = 0; i < my_player_data['pieces'].length; i++) {
                    for (let y = 0; y < my_player_data['pieces'][i].length; y++) {
                        $picture_preload.append("<img class='lazyload' data-src='" + my_player_data['pieces'][i][y]['img_hidden'] + "'/>");
                        $picture_preload.append("<img class='lazyload' data-src='" + my_player_data['pieces'][i][y]['img_open'] + "'/>");
                    }
                }
            }

            callback(true);
        }
    });
}

function applyPieceCode(code, callback) {

    $jq.ajax({
        url: URL_APPLY_CODE,
        method: 'post',
        data: {
            code: code
        },
        success: function (data) {
            if (data["result"]) {

                let _player_data = JSON.parse(data['player_data']);
                updatePlayerData(_player_data);
                displayPicture()

                let position_row = data["position_row"];
                let position_col = data["position_col"];

                if ($PIECES) {
                    $row = $PIECES.children().eq(position_row);
                    if ($row) {
                        $piece = $row.children().eq(position_col);
                        if ($piece) {
                            createNewPieceEffect($piece);
                        }
                    }
                }
                
                return callback(true);
            }
            return callback(false, data['error_type']);
        }
    });
}

function getHistoryLogs(callback) {

    $jq.ajax({
        url: URL_LOAD_HISTORY,
        method: 'post',
        success: function (data) {
            
            if (data['result']) {
                $history_logs = $jq("#jsp-history-logs");
                $history_logs.empty();

                let log, $li;
                let unused_codes = 0;
                
                for (let i = 0; i < data['logs'].length; i++) {
                    log = data['logs'][i];
                    $li = $jq("<li><div>[" + log['formated_created_at'] + "]: " + log['details'] + "</div></li>");
                    
                    if (log['piece_code']) {
                        if(log['piece_code_applied']=="1"){
                            $li.append($jq("<div>[M?? M???nh Gh??p]: <b>" + log['piece_code'] 
                                    + "</b> <b style='color:#e40000'>(???? S??? D???ng)</b></div>"));                            
                        }else{
                            $li.append($jq("<div>[M?? M???nh Gh??p]: <b>" + log['piece_code']
                                    + "</b> <a onclick=\"toCopyPieceCode(this, '"
                                    + log['piece_code'] + "')\">(COPY M??)</a></div>"));
                            unused_codes++;
                        }
                    }

                    $history_logs.append($li);
                }
                
                $jq("#jsp-unused-pieces").text("M???nh gh??p ch??a s??? d???ng: " + unused_codes);
                return callback(true);
            }

            return callback(false);
        }
    });
}

function tradePiecesToFpoints() {
    openWaitingPanel();
    openTradePiecePanel(true);
    
    $jq.ajax({
        url: URL_TRADE_PIECE,
        method: 'post',
        data: trade_piece_data,
        success: function (data) {
            openWaitingPanel(false);
            
            if (data['result']) {
                let _player_data = JSON.parse(data['player_data']);
                updatePlayerData(_player_data);
                displayPicture();
                return;
            }
            
            if(data['error_type']){
                switch(data['error_type']){
                    case 'substract_player_piece':
                        alert("System Error !");
                    break;
                    default:
                }
            }
        }
    })
}

function loadFpoint(callback) {
    openWaitingPanel();

    $jq.ajax({
        url: URL_LOAD_FPOINT,
        method: 'post',
        success: function (data) {
            openWaitingPanel(false);
            if (data['result']) {
                return callback(true, data['fpoint']);
            }

            return callback(false);
        }
    });
}

function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
}

function tradeFpointsForPieces() {
    openWaitingPanel();
    openTradeFpointPanel(true);
    
    let num_pieces = $JSP_TRADE_FPOINT_INPUT.val();
    $jq.ajax({
        url: URL_TRADE_FPOINT,
        method: 'post',
        data: {
            num_pieces: num_pieces
        },
        success: function (data) {
            openWaitingPanel(false);

            if (data['result']) {
                let _fpoint = num_pieces * TRADE_FPOINTS_RATIO;
                let _msg = "B???n ???? ?????i " + _fpoint + " F-point cho " + num_pieces + " m??. T??m m?? trong ph???n 'M?? M???nh Gh??p C???a B???n'.";
                openAlertPanel("Th??ng B??o", _msg);
            } else {
                switch (data['error_type']) {
                    case 'code_generation_failed':
                        openAlertPanel("Th??ng B??o", "Kh??ng t???o m?? ???????c");
                        break;
                    case 'not_enough_fpoint':
                        openAlertPanel("Th??ng B??o", "Kh??ng ????? F-point !");
                        break;
                    default:
                        openAlertPanel("Th??ng B??o", "L???i h??? th???ng");
                }
            }
        }
    });
}

/*
 *  Missions
 */

function checkMissions(){
    
    let $mission_check_loader = $jq("#jsp-mission-checking");
    $mission_check_loader.show();
    
    $jq.ajax({
        url: URL_CHECK_MISSIONS,
        contentType: "application/json",
        method: 'post',
        success: function (data) {
            $mission_check_loader.hide();
            if(data['result']){
                return;
            }
            
            switch(data['error_type']){
                case 'mission_login_failed':
                    alert("System Error: mission login");
                    break;
                case 'mission_refer_failed':
                    alert("System Error: mission refer");
                    break;
                case 'mission_review_failed':
                    alert("System Error: mission review");
                    break;
                default:
                    alert("System Error: " + data['error_type']);
            }
        }
    });
}

function copyReferCode(target){
    let copyTextInput = document.getElementById("jsp-input-refer-code");
    copyTextInput.select();
    document.execCommand("copy");
    alert("???? copy m??:  " + copyTextInput.value);    
}

function runMissionSwiper(){
    new Swiper('#jsp-mission-panels .swiper-container', {
        slidesPerView: 4,
        slidesPerGroup: 4,
        spaceBetween: 10,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        longSwipesMs: 800
    });
}

$jq(window).bind('fbInit',function(){

    const URL_SHARE_GAME = "/event/jigsawpuzzle/sharegame";
    const URL_SHARE_REGISTRATION = "/event/jigsawpuzzle/shareregistration";
    
    document.getElementById('share-game-button-1').onclick = clickToShareGame;
    document.getElementById('share-game-button-2').onclick = clickToShareGame;
    
    function clickToShareGame() {
        
        FB.ui({
            method: 'share',
            href: SHARE_GAME_URL,
        }, function (response) {
            if (response && !response.error_message) {
                $jq.ajax({
                    url: URL_SHARE_GAME,
                    method: 'post',
                    success: function (data) {
                        if(data && data['result']){
                            alert("B???n ???? chia s??? game th??nh c??ng !");
                        }
                    }
                });
            }
        });
    }
    
    document.getElementById('share-registration-button').onclick = clickToShareRegistration;
    
    function clickToShareRegistration(){
        
        FB.ui({
            method: 'share',
            href: SHARE_LINK_REGISTRATION,
        }, function (response) {
            if (response && !response.error_message) {
                $jq.ajax({
                    url: URL_SHARE_REGISTRATION,
                    method: 'post',
                    success: function (data) {
                        if(data && data['result']){
                            alert("B???n ???? chia s??? link ????ng k?? th??nh c??ng !");
                        }
                    }
                });
            }
        });
    }
});