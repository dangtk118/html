<?php

class Sonassi_FastSearchIndex_AdminController extends Mage_Adminhtml_Controller_Action
{

    public function refreshSearchAction()
    {
      // fetch write database connection that is used in Mage_Core module
      $write = Mage::getSingleton('core/resource')->getConnection('core_write');
      
      // now $write is an instance of Zend_Db_Adapter_Abstract
      $write->query("UPDATE `fhs_index_process` SET status = 'working', started_at = NOW() WHERE indexer_code = 'catalogsearch_fulltext'");
      
      $allStores = Mage::app()->getStores();

      //$nameAttributeId = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product','name')->getAttributeId();
      //$descAttributeId = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product','description')->getAttributeId();

      $query = "TRUNCATE `fhs_catalogsearch_fulltext`;";
      $query .= "TRUNCATE fahasa_bookstore_catalogsearch_fulltext;";
      foreach ($allStores as $_eachStoreId => $val) {
        if ($_eachStoreId < 5) {
          $query .= "INSERT IGNORE INTO fhs_catalogsearch_fulltext (product_id, store_id, data_index, name, author, sku)
          SELECT DISTINCT ca_ent.entity_id as product_id, ".$_eachStoreId." as store_id, 
          lower(CONCAT(
                  ifnull(ca_tag.value,''), ' ; ',
                  ifnull(ca_category.value,''), ' ; ',
                  ifnull(ca_manufacturer_ov.value,''), ' ; ',
                  ifnull(ca_publisher.value,''), ' ; ',
                  HTML_UnEncode(if(ca_desc.value IS NOT NULL OR category_main = 'Văn phòng phẩm - Dụng Cụ Học Sinh', ca_desc.value, ''))
          )) as data_index,
          replace(replace(replace(lower(ca_name.value), '?', ' '), '!', ' '), '-', ' ') as name,
          lower(ca_author.value) as author,
          lower(ca_ent.sku) as sku
        
          FROM `fhs_catalog_product_entity` ca_ent
        
          LEFT JOIN `fhs_catalog_product_entity_varchar` ca_author ON ca_author.entity_id = ca_ent.entity_id AND ca_author.attribute_id = 141
          LEFT JOIN `fhs_catalog_product_entity_varchar` ca_publisher ON ca_publisher.entity_id = ca_ent.entity_id AND ca_publisher.attribute_id = 142
          LEFT JOIN `fhs_catalog_product_entity_int` ca_manufacturer ON ca_manufacturer.entity_id = ca_ent.entity_id AND ca_manufacturer.attribute_id = 81
          LEFT JOIN `fhs_catalog_product_entity_varchar` ca_name ON ca_name.entity_id = ca_ent.entity_id AND ca_name.attribute_id = 71
          LEFT JOIN `fhs_catalog_product_entity_text` ca_desc ON ca_desc.entity_id = ca_ent.entity_id AND ca_desc.attribute_id = 72
          LEFT JOIN `fhs_eav_attribute_option_value` ca_manufacturer_ov ON ca_manufacturer_ov.option_id = ca_manufacturer.value
          LEFT JOIN `fhs_catalog_product_entity_int` visibility ON visibility.attribute_id = 102 AND visibility.entity_id = ca_ent.entity_id
          LEFT JOIN (SELECT catProd.product_id product_id, GROUP_CONCAT(catName.value SEPARATOR ' , ') value FROM `fhs_catalog_category_product` catProd JOIN `fhs_catalog_category_entity_varchar` catName on catProd.category_id = catName.entity_id and catName.attribute_id = 41 group by catProd.product_id, catName.store_id) ca_category ON ca_category.product_id = ca_ent.entity_id LEFT JOIN (select GROUP_CONCAT(fhs_tag.name SEPARATOR ' , ') value, fhs_tag_relation.product_id product_id from fhs_tag JOIN fhs_tag_relation on fhs_tag.tag_id = fhs_tag_relation.tag_id group by fhs_tag_relation.product_id) ca_tag ON ca_tag.product_id = ca_ent.entity_id
          WHERE visibility.value IS NULL OR visibility.value = 4
          ;";
        }
        }
        $query .= "INSERT
                        IGNORE
                INTO
                        fahasa_bookstore_catalogsearch_fulltext (data_index, sku, name, author)
                select
                        distinct lower(concat( ifnull(category , ''), ' ; ', ifnull(category2, ''), ' ; ', ifnull(publisherName, '') )) as data_index,
                        SKU as sku,
                        replace(replace(replace(Name, '_', ' '), '!', ' '),
                        '?',
                        ' ') as name,
                        authors as author
                from
                        fahasa_stock fs
                JOIN fahasa_bookstore_stock_vanilla bsv ON
                        bsv.MABH = fs.SKU
                where
                        length(SKU) > 5
                        AND TMDT + SAP_CO_HANG + SAP_CO_HANG_HA_NOI + NXB_Tre + GIGOTOYS + Don_Hang_Si + NCC_Thuong_Mai_Dien_Tu + Tiki + Lazada + Vnshop + Thuong_Mai_Dien_Tu_1 + Thuong_Mai_Dien_Tu_2 + Fahasa_Cam_Ranh + Fahasa_Ba_Ria + Fahasa_Tien_Giang + Quang_Nam + FAHASA_Thanh_Hoa + Fahasa_Hai_Phong + Hai_Dang_Vung_Tau + Fahasa_Long_Bien + Fahasa_KonTum + Kho_TTam_Sach_Nguyen_Hue + Fahasa_Pham_Van_Dong + Fahasa_Phu_Lam + Fahasa_Go_Vap + Da_Nang + Fahasa_Tan_Phu + Fahasa_Quang_Binh + Fahasa_Dat_Mui + Ly_Thai_To + Phan_Rang + Kho_TTam_Sach_Gia_Dinh + Buon_Ma_Thuot + Fahasa_Thanh_Khe + Tan_Dinh + Fahasa_Hoa_Binh + Fahasa_Lagi + Fahasa_Nam_Dinh + Fahasa_Ha_Noi + Thuan_An + Fahasa_Cai_Lay + Phu_Nhuan + Fahasa_Thai_Binh + Fahasa_Ca_Mau + Can_Tho + Fahasa_Viet_Tri + Fahasa_Ninh_Kieu + Fahasa_Soc_Trang + Lac_Xuan + Thu_Duc + Thuong_mai_dien_tu_Ha_Noi + FAHASA_Vinh_Phuc + Fahasa_Quan_9 + FAHASA_Ha_Dong + Fahasa_Ben_Tre + Fahasa_Cao_Lanh + Kho_TT_Sach_FAHASA_Ha_Noi + Fahasa_Tay_Ninh + Thuong_Mai_Dien_Tu + Fahasa_Long_An + Fahasa_Long_Binh_Tan + FAHASA_Hau_Giang + Tan_Binh + Ly_Thuong_Kiet + Fahasa_Binh_Phuoc + Fahasa_Tan_Hiep + Fahasa_My_Tho + Fahasa_Dak_LaK + Fahasa_Kien_Giang + Nha_Trang + Fahasa_Soc_Trang_2 + Nguyen_Hue + Fahasa_My_Khe + Fahasa_Hanh_Thong_Tay + Fahasa_Sa_Dec + Fahasa_Quang_Tri + FAHASA_Dong_Da + Fahasa_Tra_Vinh + Kho_TT_Sach_Phu_Nhuan + FAHASA_Bac_Giang + Fahasa_Ninh_Binh + FAHASA_Ngo_Quyen + Fahasa_Phu_Yen + Bien_Hoa + Cu_Chi + Phan_Thiet + Fahasa_Lotte + Fahasa_Vinh_Long + Fahasa_Bac_Ninh + Quang_Ngai + Binh_Tan + Fahasa_Bao_Loc + Fahasa_Da_Lat + Kho_TTam_Sach_Xuan_Thu + Fahasa_Hong_Ha + Fahasa_An_Giang + Fahasa_Thot_Not + Binh_Thuan + Khanh_Hoa + Di_An + Fahasa_Long_Xuyen + Vung_Tau + Xuan_Thu + Quy_Nhon + Fahasa_Gia_Lai + Fahasa_Ha_Tinh + Fahasa_Van_Phuc + Fahasa_Song_La + Fahasa_Lai_Thieu + Fahasa_Bac_Lieu + Cay_Go + Fahasa_Lam_Son + Dong_Nai + Kho_Cong_ty_774_Truong_Chinh + NS_Fahasa_Hai_Chau + Fahasa_Ha_Nam + Fahasa_Long_Binh_DN + Fahasa_Tan_Thanh + FAHASA_Hue + Fahasa_Tay_Do + Binh_Dinh + Sai_Gon + Fahasa_Binh_Duong > 0
                group by
                        SKU
                ORDER BY
                        fs.CreateDate desc;";
        // now $write is an instance of Zend_Db_Adapter_Abstract
        //Build index for search cms page index - Thang Pham
        $query .= "TRUNCATE fhs_cms_search_fulltext;
                SET group_concat_max_len=524280;
                INSERT IGNORE INTO fhs_cms_search_fulltext (pageUrl, data_index, type, page_id)
                SELECT a.pageUrl, GROUP_CONCAT(a.searchString separator ' , ') as searchString, GROUP_CONCAT(a.type) as type, a.id  
                    FROM 
                    (SELECT id, type, pageUrl, CONCAT(name,  ',', keyword) AS searchString 
                      FROM fhs_page_keyword_url
                      WHERE pageUrl IS NOT NULL and pageUrl != '') a 
                GROUP BY a.pageUrl;";
      
      $write->query($query);
      
      $write->query("UPDATE `fhs_index_process` SET status = 'pending', ended_at = NOW() WHERE indexer_code = 'catalogsearch_fulltext'");
      
      Mage::getSingleton('core/session')->addSuccess(Mage::helper('index')->__('%s index was rebuilt.', 'Catalog Search Index'));
      $this->_redirect('adminhtml/process/list/');
    }
    
    public function refreshCatProdAction()
    {
      // fetch write database connection that is used in Mage_Core module
      $write = Mage::getSingleton('core/resource')->getConnection('core_write');
      
      // now $write is an instance of Zend_Db_Adapter_Abstract
      $res = $write->query("SHOW INDEX FROM catalog_category_product_index_idx WHERE key_name = 'catalog_cat_product_idx'");
      $isNo = $res->fetchAll();
        
      if(count($isNo)) 
        $write->query("DROP INDEX catalog_cat_product_idx ON catalog_category_product_index_idx;");
      
      $write->query("CREATE TABLE `new_catalog_category_product_index_idx` LIKE `catalog_category_product_index_idx`;
      RENAME TABLE `catalog_category_product_index_idx` TO `old_catalog_category_product_index_idx`, `new_catalog_category_product_index_idx` TO `catalog_category_product_index_idx`;
      DROP TABLE `old_catalog_category_product_index_idx`;");
      
      $write->query("CREATE INDEX catalog_cat_product_idx ON catalog_category_product_index_idx(product_id,category_id,store_id); ");
      
      $indexProcess = Mage::getSingleton('index/indexer')->getProcessByCode('catalog_category_product');
      if ($indexProcess) {
          $indexProcess->reindexAll();
      }
      
      Mage::getSingleton('core/session')->addSuccess(Mage::helper('index')->__('%s index was rebuilt.', 'Category Products Index'));
      $this->_redirect('adminhtml/process/list/');
    }
    
}
