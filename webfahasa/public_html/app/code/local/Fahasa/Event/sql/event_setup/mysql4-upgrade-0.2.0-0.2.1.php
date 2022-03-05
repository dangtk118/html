<?php

$installer = $this;

$installer->startSetup();

// edit fhs_almostcart_gift
$installer->run("ALTER TABLE fhs_almostcart_gift ADD cms_block varchar(255);");
$installer->run("ALTER TABLE fhs_almostcart_gift ADD view_page varchar(255);");

// insert data
$installer->run("insert into fhs_almostcart_gift(campaign_id , start_date , end_date , min_cart_value , insert_by , max_cart_value , description , item_order , alternate_name , status, cms_block,view_page)
values(1, '2018-08-15 00:00:00', '2018-08-21 23:59:59','0','theanh','249000','Tổng giỏ hàng từ 0 - 249.000đ',0,'Bão quà dậy sóng nhập mã là nhận tổng thanh toán giỏ hàng từ 0 đến 249.000đ', 1,'almostcart_gift_rank_1','checkout');");
$installer->run("insert into fhs_almostcart_gift(campaign_id , start_date , end_date , min_cart_value , insert_by , max_cart_value , description , item_order , alternate_name , status, cms_block,view_page)
values(1, '2018-08-15 00:00:00', '2018-08-21 23:59:59','250000','theanh','499000','Tổng giỏ hàng từ 250000 - 499.000đ',0,'Bão quà dậy sóng nhập mã là nhận tổng thanh toán giỏ hàng từ 250000 đến 499.000đ', 1,'almostcart_gift_rank_2','checkout');");
$installer->run("insert into fhs_almostcart_gift(campaign_id , start_date , end_date , min_cart_value , insert_by , description , item_order , alternate_name , status, cms_block,view_page)
values(1, '2018-08-15 00:00:00', '2018-08-21 23:59:59','500000','theanh','Tổng giỏ hàng từ 500.000đ trở lên',0,'Bão quà dậy sóng nhập mã là nhận tổng thanh toán giỏ hàng từ 500.000đ trở lên', 1,'almostcart_gift_rank_3','checkout');");

$installer->endSetup();
