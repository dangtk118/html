<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Sphinx Search Ultimate
 * @version   2.3.2
 * @revision  886
 * @copyright Copyright (C) 2014 Mirasvit (http://mirasvit.com/)
 */


if (!@class_exists('SphinxClient')) {
    include Mage::getBaseDir().DS.'lib'.DS.'Sphinx'.DS.'sphinxapi.php';
}

/**
 * ÐÐ»Ð°ÑÑ ÑÐµÐ°Ð»Ð¸Ð·ÑÐµÑ Ð¼ÐµÑÐ¾Ð´Ñ Ð´Ð»Ñ:
 *     Ð¾ÑÐ¿ÑÐ°Ð²ÐºÐ° Ð·Ð°Ð¿ÑÐ¾ÑÐ¾Ð² Ð½Ð° Ð¿Ð¾Ð¸ÑÐº
 *     ÑÐ±Ð¾ÑÐºÐ° ÑÐ°Ð¹Ð»Ð° ÐºÐ¾Ð½ÑÐ¸Ð³ÑÑÐ°ÑÐ¸Ð¸
 * ÐÐ°Ð·Ð°Ð²ÑÐ¹ ÐºÐ»Ð°ÑÑ Ð´Ð»Ñ ÑÐ°Ð±Ð¾ÑÑ Ð² ÑÐµÐ¶Ð¸Ð¼Ðµ Search Sphinx (on another server)
 *
 * @category Mirasvit
 * @package  Mirasvit_SearchSphinx
 */
class Mirasvit_SearchSphinx_Model_Engine_Sphinx extends Mirasvit_SearchIndex_Model_Engine
{
    const PAGE_SIZE                 = 1000;

    protected $_config              = null;
    protected $_sphinxFilepath      = null;
    protected $_configFilepath      = null;
    protected $_sphinxCfgTpl        = null;
    protected $_sphinxSectionCfgTpl = null;

    protected $_spxHost             = null;
    protected $_spxPort             = null;

    protected $_matchMode           = null;

    protected $_io                  = null;

    public function __construct()
    {
        $this->_config              = Mage::getSingleton('searchsphinx/config');
        $this->_sphinxFilepath      = Mage::getBaseDir('var').DS.'sphinx';
        $this->_configFilepath      = $this->_sphinxFilepath.DS.'sphinx.conf';

        $this->setSphinxCfgTpl();

        $this->_spxHost             = Mage::getStoreConfig('searchsphinx/general/external_host');
        $this->_spxPort             = Mage::getStoreConfig('searchsphinx/general/external_port');
        $this->_basePath            = Mage::getStoreConfig('searchsphinx/general/external_path');

        $this->_matchMode           = Mage::getStoreConfig('searchsphinx/advanced/match_mode', 0);

        $this->_io                  = Mage::helper('searchsphinx/io');

        return $this;
    }

    /**
     * ÐÐ±Ð²ÐµÑÑÐºÐ° Ð´Ð»Ñ ÑÑÐ½ÐºÑÐ¸Ð¸ _query
     *
     * @param  string  $queryText Ð¿Ð¾Ð¸ÑÐºÐ¾Ð²ÑÐ¹ Ð·Ð°Ð¿ÑÐ¾Ñ (Ð² Ð¾ÑÐ¸Ð³Ð¸Ð½Ð°Ð»ÑÐ½Ð¾Ð¼ Ð²Ð¸Ð´Ðµ)
     * @param  integer $store     ÐÐ ÑÐµÐºÑÑÐµÐ³Ð¾ Ð¼Ð°Ð³Ð°Ð·Ð¸Ð½Ð°
     * @param  object  $index     Ð¸Ð½Ð´ÐµÐºÑ Ð¿Ð¾ ÐºÐ¾ÑÐ¾ÑÐ¾Ð¼Ñ Ð½ÑÐ¶Ð½Ð¾ Ð¿ÑÐ¾Ð²ÐµÑÑÐ¸ Ð¿Ð¾Ð¸ÑÐº
     *
     * @return array Ð¼Ð°ÑÐ¸Ð² ÐÐ ÐµÐ»ÐµÐ¼ÐµÐ½ÑÐ¾Ð², Ð³Ð´Ðµ ÐÐ - ÐºÐ»ÑÑ, ÑÐµÐ»ÐµÐ²Ð°Ð½ÑÐ½Ð¾ÑÑÑ Ð·Ð½Ð°ÑÐµÐ½Ð¸Ðµ
     */
    public function query($queryText, $store, $index)
    {
        if ($store) {
            $store = array($store);
        }

        $queryText = Normalizer::normalize(mb_strtolower($queryText, 'UTF-8'));
        $indexCode  = $index->getCode();
        $res = null;
        // If the input string has utf8 character, we use utf8 match
        if ($indexCode == 'mage_catalog_product' && !mb_check_encoding($queryText, 'ASCII') && strpos($queryText, "pokémon") === false) {  
            $res = $this->_query($queryText, $store, $index, 1, true);   
            if (count($res) < 20)
            {
                $resAscii = $this->_query($queryText, $store, $index);
                //append
                $res = $res + $resAscii;
            }
        }
        else {
            // else we fallback to ascii match
            $res = $this->_query($queryText, $store, $index);
        }
                
        return $res;
    }
    
    /**
     * Query cms pages that match queryText.
     * @param type $queryText
     * @param type $type (optinal) (the "type" column in "fhs_page_keyword_url" table)
     * 
     * Return a list of (id, weight) pairs. Id is from the id column in "fhs_page_keyword_url" table
     */
    public function queryFhsCms($queryText)
    {
        $client = new SphinxClient();
        $client->setLimits(0 , self::PAGE_SIZE, $this->_config->getResultLimit());
        $client->SetMatchMode(SPH_MATCH_EXTENDED2);
        $client->setRankingMode(SPH_RANK_SPH04);
        $client->setMaxQueryTime(2000); //2 seconds        
        $client->setServer($this->_spxHost, $this->_spxPort);
        $queryText = $client->EscapeString(trim($queryText));
        
        
        // Try searching with decreasing strict word occurences
        $quorum = array("/0.99", "/0.89", "/0.74", "/0.6", "/1"); 
        $result = [];
        foreach ($quorum as $q) {
            $sphinxQuery_ = "\"".$queryText."\"".$q;        
            $result = $client->query($sphinxQuery_, "cms_page");
            Mage::log("queryFhsCms: $sphinxQuery_,", null, 'searchkey.log');
            Mage::log(print_r($result, true), null, 'searchkey.log');	
            if (count($result) > 0) {
                break;
            }
        }                  
        return $result;
    }

    /**
     * ÐÑÐ¿ÑÐ°Ð²Ð»ÑÐµÑ Ð¿Ð¾Ð´Ð³Ð¾ÑÐ¾Ð²Ð»ÐµÐ½Ð½ÑÐ¹ Ð·Ð°Ð¿ÑÐ¾Ñ Ð½Ð° ÑÑÐ¸Ð½ÐºÑ, Ð¸ Ð¿ÑÐµÐ¾Ð±ÑÐ°Ð·ÑÐµÑ Ð¾ÑÐ²ÐµÑ Ð² Ð½ÑÐ¶Ð½ÑÐ¹ Ð²Ð¸Ð´
     *
     * @param  string  $query Ð¿Ð¾Ð¸ÑÐºÐ¾Ð²ÑÐ¹ Ð·Ð°Ð¿ÑÐ¾Ñ (Ð² Ð¾ÑÐ¸Ð³Ð¸Ð½Ð°Ð»ÑÐ½Ð¾Ð¼ Ð²Ð¸Ð´Ðµ)
     * @param  integer $storeId    ÐÐ ÑÐµÐºÑÑÐµÐ³Ð¾ Ð¼Ð°Ð³Ð°Ð·Ð¸Ð½Ð°
     * @param  string  $indexCode  ÐÐ¾Ð´ Ð¸Ð½Ð´ÐµÐºÑÐ°  Ð¿Ð¾ ÐºÐ¾ÑÐ¾ÑÐ¾Ð¼Ñ Ð½ÑÐ¶Ð½Ð¾ Ð¿ÑÐ¾Ð²ÐµÑÑÐ¸ Ð¿Ð¾Ð¸ÑÐº (mage_catalog_product ...)
     * @param  string  $primaryKey Primary Key Ð¸Ð½Ð´ÐµÐºÑÐ° (entity_id, category_id, post_id ...)
     * @param  array   $attributes ÐÐ°ÑÐ¸Ð² Ð°ÑÑÐ¸Ð±ÑÑÐ¾Ð² Ñ Ð²ÐµÑÐ°Ð¼Ð¸
     * @param  integer $offset     Ð¡ÑÑÐ°Ð½Ð¸ÑÐ°
     *
     * @return array Ð¼Ð°ÑÐ¸Ð² ÐÐ ÐµÐ»ÐµÐ¼ÐµÐ½ÑÐ¾Ð², Ð³Ð´Ðµ ÐÐ - ÐºÐ»ÑÑ, ÑÐµÐ»ÐµÐ²Ð°Ð½ÑÐ½Ð¾ÑÑÑ Ð·Ð½Ð°ÑÐµÐ½Ð¸Ðµ
     */
    protected function _query($query, $storeId, $index, $offset = 1, $utf8Match = false) {        
        $uid = Mage::helper('mstcore/debug')->start();
        
        $indexCode  = $index->getCode() . ($utf8Match ? '_utf8' : '');
        $primaryKey = $index->getPrimaryKey();
        $attributes = $index->getAttributes();

        $client = new SphinxClient();
        $client->setMaxQueryTime(2000); //2 seconds
        $client->setLimits(($offset - 1) * self::PAGE_SIZE, self::PAGE_SIZE, $this->_config->getResultLimit());
        $client->SetMatchMode(SPH_MATCH_EXTENDED2);
        $client->setRankingMode(SPH_RANK_SPH04);     
        $client->setServer($this->_spxHost, $this->_spxPort);
        if (!array_key_exists($attributes, 'data_index')) {
            $attributes['data_index'] = '8';
        }
        $client->SetFieldWeights($attributes);

        if ($storeId) {
            $client->SetFilter('store_id', $storeId);
        }

        $sphinxQuery = $this->_buildQuery($query, $storeId);

        if (!$sphinxQuery) {
            return array();
        }
        
        $result = [];
        $sphinxQuery = $client->EscapeString(trim($sphinxQuery));
        // Try searching with decreasing strict word occurences
        $quorum = array("/0.99", "/0.89", "/0.74", "/0.6", "/1"); 
        foreach ($quorum as $q) {
            $sphinxQuery_ = "\"".$sphinxQuery."\"".$q;        
            $sphinxResult = $client->query($sphinxQuery_, $indexCode);
            $result = $this->_processSphinxSearchResult($sphinxResult, $storeId, $index, $offset);
            if (count($result) > 0) {
                break;
            }
        }
        
        arsort($result);
        $result = array_slice($result, 0, 1000, true);        

        $result = $this->_normalize($result);
        Mage::helper('mstcore/debug')->end($uid, $result);     
        return $result;
    }
    
    protected function _processSphinxSearchResult($sphinxResult, $storeId, $index, $offset) {
        $primaryKey = $index->getPrimaryKey();
        $entityIds = array();
        if ($sphinxResult === false) {
            //Mage::throwException($client->GetLastError()."\nQuery: ".$query);
        } elseif ($sphinxResult['total'] > 0) {
            
            foreach ($sphinxResult['matches'] as $data) {
                $entityIds[$data['attrs'][strtolower($primaryKey)]] = $data['weight'];
            }

            if ($sphinxResult['total'] > $offset * self::PAGE_SIZE
                && $offset * self::PAGE_SIZE < $this->_config->getResultLimit()) {
                $newIds = $this->_query($query, $storeId, $index, $offset + 1);
                foreach ($newIds as $key => $value) {
                   $entityIds[$key] = $value;
                }
            }
        }       
        return $entityIds;
    }

    /**
     * Ð¡ÑÑÐ¾Ð¸Ñ Ð·Ð°Ð¿ÑÐ¾Ñ Ðº ÑÑÐ¸Ð½ÐºÑÑ
     * ÐÐ°Ð¿ÑÐ¾Ñ ÑÐ¾ÑÑÐ¾Ð¸Ñ Ð¸Ð· ÑÐµÐºÑÐ¸Ð¹ (..) & (..) & ..
     *
     * @param  string  $query   Ð¿Ð¾Ð»ÑÐ·Ð¾Ð²Ð°ÑÐµÐ»ÑÑÐºÐ¸Ð¹ Ð·Ð°Ð¿ÑÐ¾Ñ
     * @param  integer $storeId
     *
     * @return string
     */
    protected function _buildQuery($query, $storeId)
    {
        if ($this->_matchMode != SPH_MATCH_EXTENDED) {
            return $query;
        }

        // Extended query syntax
        if (substr($query, 0, 1) == '=') {
            return substr($query, 1);
        }

        // Search by field
        if (substr($query, 0, 1) == '@') {
            return $query;
        }

        $arQuery = Mage::helper('searchsphinx/query')->buildQuery($query, $storeId, true);

        if (!is_array($arQuery)) {
            return false;
        }

        $result = array();
        foreach ($arQuery as $key => $array) {
            if ($key == 'not like') {
                $result[] = '-'.$this->_buildWhere($key, $array);
            } else {

                $result[] = $this->_buildWhere($key, $array);
            }
        }
        if (count($result)) {
            $query = '(' . join(' & ', $result) . ')';
        }

        return $query;
    }

    /**
     * Ð¡ÑÑÐ¾Ð¸Ñ ÑÐµÐºÑÐ¸Ð¸ Ð·Ð°Ð¿ÑÐ¾ÑÐ°
     *
     * @param  string $type  ÑÐ¸Ð¿ ÑÐµÐºÑÐ¸Ð¸ AND/OR
     * @param  array  $array ÑÐ»Ð¾Ð²Ð° Ð´Ð»Ñ ÑÐµÐºÑÐ¸Ð¸
     *
     * @return string
     */
    protected function _buildWhere($type, $array)
    {
        if (!is_array($array)) {
            if (substr($array, 0, 1) == ' ') {
                return '('.$this->escapeSphinxQL($array).')';
            } else {
                return '("*'.$this->escapeSphinxQL($array).'*")';
            }

        }

        foreach ($array as $key => $subarray) {
            if ($key == 'or') {
                $array[$key] = $this->_buildWhere($type, $subarray);
                if (is_array($array[$key])) {
                    $array = '('.implode(' | ', $array[$key]).')';
                }
            } elseif ($key == 'and') {
                $array[$key] = $this->_buildWhere($type, $subarray);
                if (is_array($array[$key])) {
                    $array = '('.implode(' & ', $array[$key]).')';
                }
            } else {
                $array[$key] = $this->_buildWhere($type, $subarray);
            }
        }

        return $array;

    }

    protected function escapeSphinxQL($string)
    {
        $from = array ('.', ' ', '\\', '(',')','|','-','!','@','~','"','&', '/', '^', '$', '=', "'");
        $to   = array ('', ' ', '\\\\', '\(','\)','\|','\-','\!','\@','\~','\"', '\&', '\/', '\^', '\$', '\=', "\'");

        return str_replace($from, $to, $string);
    }

    /**
     * Ð¡Ð¾Ð±Ð¸ÑÐ°ÐµÑ Ð¸ ÑÐ¾ÑÑÐ°Ð½ÑÐµÑ ÐºÐ¾Ð½ÑÐ¸Ð³ ÑÐ°Ð¹Ð» Ð´Ð»Ñ ÑÐ°Ð±Ð¾ÑÑ ÑÑÐ¸Ð½ÐºÑÐ° (sphinx.conf)
     * Ð¤Ð°Ð¹Ð» ÑÐ¾ÑÑÐ°Ð½ÑÐµÑÑÑÑ Ð² ../var/sphinx/sphinx.conf
     * Ð¨Ð°Ð±Ð»Ð¾Ð½ ÐºÐ¾Ð½ÑÐ¸Ð³Ð° Ð½Ð°ÑÐ¾Ð´Ð¸ÑÑÑÑ Ð² ÑÐ°ÑÑÐ¸ÑÐµÐ½Ð¸Ð¸ etc/config/sphinx.conf
     *
     * @return string Ð¿Ð¾Ð»Ð½ÑÐ¹ Ð¿ÑÑÑ Ðº ÑÐ°Ð¹Ð»Ñ
     */
    public function makeConfigFile()
    {
        if (!$this->_io->directoryExists($this->_sphinxFilepath)) {
            $this->_io->mkdir($this->_sphinxFilepath);
        }

        $data = array(
            'time'      => date('d.m.Y H:i:s'),
            'host'      => $this->_spxHost,
            'port'      => $this->_spxPort,
            'logdir'    => $this->_basePath,
            'sphinxdir' => $this->_basePath,
        );

        $formater = new Varien_Filter_Template();
        $formater->setVariables($data);
        $config   = $formater->filter(file_get_contents($this->_sphinxCfgTpl));

        $indexes = Mage::helper('searchindex/index')->getIndexes();
        $sections = array();
        foreach ($indexes as $index) {
            $indexer = $index->getIndexer();
            $sections[$index->getCode()] = $this->_getSectionConfig($index->getCode(), $indexer);
        }
        $config .= implode(PHP_EOL, $sections);
        // $config  .= PHP_EOL.$this->_getSectionConfig($index->getCode(), $indexer);

        if ($this->_io->isWriteable($this->_configFilepath)) {
            $this->_io->write($this->_configFilepath, $config);
        } else {
            if ($this->_io->fileExists($this->_configFilepath)) {
                Mage::throwException(sprintf("File %s does not writeable", $this->_configFilepath));
            } else {
                Mage::throwException(sprintf("Directory %s does not writeable", $this->_sphinxFilepath));
            }
        }

        return $this->_configFilepath;
    }

    /**
     * Ð¡Ð¾Ð±Ð¸ÑÐ°ÐµÑ ÑÐµÐºÑÐ¸Ñ Ð´Ð»Ñ ÐºÐ¾Ð½ÑÐ¸Ð³ ÑÐ°Ð¹Ð»Ð°
     * ÐÐ°Ð¶Ð´ÑÐ¹ Ð¸Ð½Ð´ÐµÐºÑ Ð¸Ð¼ÐµÐµÑ ÑÐ²Ð¾Ñ ÑÐµÐºÑÐ¸Ñ
     * Ð¡ÐµÐºÑÐ¸Ñ ÑÐ¾ÑÑÐ¾Ð¸Ñ source (Ð¾ÑÐºÑÐ´Ð°-ÑÑÐ¾ Ð±ÑÐ°ÑÑ) Ð¸ index (ÐºÑÐ´Ð° ÑÑÐ¾ Ð¿Ð¸ÑÐ°ÑÑ Ð¸ ÐºÐ°Ðº ÐµÐ³Ð¾ Ð¸Ð½Ð´ÐµÐºÑÐ¸ÑÐ¾Ð²Ð°ÑÑ)
     * Ð¨Ð°Ð±Ð»Ð¾Ð½ ÑÐµÐºÑÐ¸Ð¸ Ð½Ð°ÑÐ¾Ð´Ð¸ÑÑÑÑ Ð² ÑÐ°ÑÑÐ¸ÑÐµÐ½Ð¸Ð¸ etc/config/section.conf
     *
     * @param  string $name    Ð½Ð°Ð·Ð²Ð°Ð½Ð¸Ðµ (ÐºÐ¾Ð´ Ð¸Ð½Ð´ÐµÐºÑÐ°)
     * @param  object $indexer ÐÐ½Ð´ÐµÐºÑÐ°ÑÐ¾Ñ! Ð¸Ð½Ð´ÐµÐºÑÐ°
     *
     * @return string Ð³Ð¾ÑÐ¾Ð²Ð°Ñ ÑÐµÐºÑÐ¸Ñ
     */
    protected function _getSectionConfig($name, $indexer)
    {
        $sqlHost = Mage::getConfig()->getNode('global/resources/default_setup/connection/host');
        $sqlPort = 3306;

        if (count(explode(':', $sqlHost)) == 2) {
            $arr = explode(':', $sqlHost);
            $sqlHost = $arr[0];
            $sqlPort = $arr[1];
        }

        $data = array(
            'name'             => $name,
            'sql_host'         => $sqlHost,
            'sql_port'         => $sqlPort,
            'sql_user'         => Mage::getConfig()->getNode('global/resources/default_setup/connection/username'),
            'sql_pass'         => Mage::getConfig()->getNode('global/resources/default_setup/connection/password'),
            'sql_db'           => Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname'),
            'sql_query_pre'    => $this->_getSqlQueryPre($indexer),
            'sql_query'        => $this->_getSqlQuery($indexer),
            'sql_query_delta'  => $this->_getSqlQueryDelta($indexer),
            'sql_attr_uint'    => $indexer->getPrimaryKey(),
            'min_word_len'     => Mage::getStoreConfig(Mage_CatalogSearch_Model_Query::XML_PATH_MIN_QUERY_LENGTH),
            'index_path'       => $this->_basePath.DS.$name,
            'delta_index_path' => $this->_basePath.DS.$name.'_delta',
        );

        foreach ($data as $key => $value) {
            $data[$key] = str_replace('#', '\#', $value);
        }

        $formater = new Varien_Filter_Template();
        $formater->setVariables($data);
        $config   = $formater->filter(file_get_contents($this->_sphinxSectionCfgTpl));

        return $config;
    }

    /**
     * ÐÐ¾Ð·Ð²ÑÐ°ÑÐ°ÐµÑ Ð½Ð°ÑÐ°Ð»ÑÐ½ÑÐ¹ sql Ð·Ð°Ð¿ÑÐ¾Ñ (ÑÑÑÐ°Ð½Ð¾Ð²Ð¸ÑÑ ÑÑÐ°ÑÑÑ Ð² updated = 0)
     *
     * @param  object $indexer
     *
     * @return string
     */
    protected function _getSqlQueryPre($indexer)
    {
        $table = $indexer->getTableName();

        $sql = 'UPDATE '.$table.' SET updated=0';

        return $sql;
    }

    /**
     * ÐÐ¾Ð·Ð²ÑÐ°ÑÐ°ÐµÑ sql Ð·Ð°Ð¿ÑÐ¾Ñ, Ð²ÑÐ¿Ð¾Ð»Ð½ÑÑ ÐºÐ¾ÑÐ¾ÑÑÐ¹ ÑÑÐ¸Ð½ÐºÑ Ð¿Ð¾Ð»ÑÑÐ°ÐµÑ Ð²ÑÐµ! Ð¸Ð½Ð´ÐµÐºÑÐ¸ÑÑÐµÐ¼ÑÐµ Ð´Ð°Ð½Ð½ÑÐµ
     *
     * @param  object $indexer
     *
     * @return string
     */
    protected function _getSqlQuery($indexer)
    {
        $table = $indexer->getTableName();

        $sql = 'SELECT CONCAT('.$indexer->getPrimaryKey().', store_id) AS id, '.$table.'.product_id, '.$table.'.store_id, '.$table.'.name, '.$table.'.author, '.$table.'.sku, '.$table.'.data_index '.' FROM '.$table;

        return $sql;
    }

    /**
     * ÐÐ¾Ð·Ð²ÑÐ°ÑÐ°ÐµÑ sql Ð·Ð°Ð¿ÑÐ¾Ñ, Ð½Ð° Ð²ÑÐ±Ð¾ÑÐºÑ Ð²ÑÐµÑ ÐµÐ»ÐµÐ¼ÐµÐ½ÑÐ¾Ð² Ð´Ð»Ñ Ð´ÐµÐ»ÑÐ°-ÑÐµÐ¸Ð½Ð´ÐµÐºÑÐ° (Ð¾Ð±Ð½Ð¾Ð²Ð»ÐµÐ½Ð½ÑÑ ÑÐ»ÐµÐ¼ÐµÐ½ÑÐ¾Ð²)
     *
     * @param  object $indexer
     *
     * @return string
     */
    protected function _getSqlQueryDelta($indexer)
    {
        $sql = $this->_getSqlQuery($indexer);
        $sql .= ' WHERE updated = 1';

        return $sql;
    }

    /**
     * Define path for sphinx config files
     *
     * @return bool
     */
    private function setSphinxCfgTpl()
    {
        $cfgDir = $this->getSphinxVersion();
        $this->_sphinxCfgTpl        = Mage::getModuleDir('etc', 'Mirasvit_SearchSphinx').DS.'conf'.DS.$cfgDir.DS.'sphinx.conf';
        $this->_sphinxSectionCfgTpl = Mage::getModuleDir('etc', 'Mirasvit_SearchSphinx').DS.'conf'.DS.$cfgDir.DS.'section.conf';

        return true;
    }

    /**
     * Define sphinx version
     *
     * @return string $version
     */
    private function getSphinxVersion()
    {
        //We always use 2.2
        return $version = '2.2';
        //comment the original below code;
//        $version = '2.0';
//        $cmd     = 'searchd --help';
//        $exec    = $this->_exec($cmd);
//        $res     = preg_match('/Sphinx[\s]?([\d.]*)([\s\w\d.-]*)?/i', $exec['data'], $match);
//        if ($res === 1 && ($match[1] != '' || null != $match[1])) {
//            if ($match[1] > 2.1) {
//                $version = '2.2';
//            }
//        }
//
//        return $version;
    }
    public function _query_bookstore($queryText) {
        $client = new SphinxClient();
        $client->setLimits(0 , self::PAGE_SIZE, $this->_config->getResultLimit());
        $client->SetMatchMode(SPH_MATCH_EXTENDED2);
        $client->setRankingMode(SPH_RANK_SPH04);
        $client->setMaxQueryTime(2000); //1 seconds        
        $client->setServer($this->_spxHost, $this->_spxPort);
        $queryText = $client->EscapeString(trim($queryText));
        
        
        // Try searching with decreasing strict word occurences
        $quorum = array("/0.99", "/0.89", "/0.74", "/0.6", "/1"); 
        $result = [];
        foreach ($quorum as $q) {
            $sphinxQuery_ = "\"".$queryText."\"".$q;        
            $result = $client->query($sphinxQuery_, "bookstore");
            if (count($result['matches']) > 5) {
                break;
            }
        }                  
                
        arsort($result);
        return $result;
    }
}
