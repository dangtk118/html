<?php

$installer = $this;

$installer->startSetup();

// insert fahasa_events bat ma deal 24
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2481', 'Bắt ma deal ngày 24/08/2018 9:00 - 10:00', 'Bắt ma deal ngày 24/08/2018 khung giờ 9:00 - 10:00', '2018-08-24 09:00:00', '2018-08-24 10:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2482', 'Bắt ma deal ngày 24/08/2018 12:00 - 13:00', 'Bắt ma deal ngày 24/08/2018 khung giờ 12:00 - 13:00', '2018-08-24 12:00:00', '2018-08-24 13:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2483', 'Bắt ma deal ngày 24/08/2018 21:00 - 22:00', 'Bắt ma deal ngày 24/08/2018 khung giờ 21:00 - 22:00', '2018-08-24 21:00:00', '2018-08-24 22:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");

// insert fahasa_event_gift catchghost-2481
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2481', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");

// insert fahasa_event_gift catchghost-2482
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2482', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");

// insert fahasa_event_gift catchghost-2483
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2483', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");


// insert fahasa_events bat ma deal 25
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2581', 'Bắt ma deal ngày 25/08/2018 9:00 - 10:00', 'Bắt ma deal ngày 25/08/2018 khung giờ 9:00 - 10:00', '2018-08-25 09:00:00', '2018-08-25 10:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2582', 'Bắt ma deal ngày 25/08/2018 12:00 - 13:00', 'Bắt ma deal ngày 25/08/2018 khung giờ 12:00 - 13:00', '2018-08-25 12:00:00', '2018-08-25 13:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2583', 'Bắt ma deal ngày 25/08/2018 21:00 - 22:00', 'Bắt ma deal ngày 25/08/2018 khung giờ 21:00 - 22:00', '2018-08-25 21:00:00', '2018-08-25 22:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");

// insert fahasa_event_gift catchghost-2581
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2581', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");

// insert fahasa_event_gift catchghost-2582
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2582', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");

// insert fahasa_event_gift catchghost-2583
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2583', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");


// insert fahasa_events bat ma deal 26
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2681', 'Bắt ma deal ngày 26/08/2018 9:00 - 10:00', 'Bắt ma deal ngày 26/08/2018 khung giờ 9:00 - 10:00', '2018-08-26 09:00:00', '2018-08-26 10:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2682', 'Bắt ma deal ngày 26/08/2018 12:00 - 13:00', 'Bắt ma deal ngày 26/08/2018 khung giờ 12:00 - 13:00', '2018-08-26 12:00:00', '2018-08-26 13:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('catchghost-2683', 'Bắt ma deal ngày 26/08/2018 21:00 - 22:00', 'Bắt ma deal ngày 26/08/2018 khung giờ 21:00 - 22:00', '2018-08-26 21:00:00', '2018-08-26 22:00:00', 1, '', 'random_gift', 'all', now(), 'admin');");

// insert fahasa_event_gift catchghost-2681
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2681', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");

// insert fahasa_event_gift catchghost-2682
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2682', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");

// insert fahasa_event_gift catchghost-2683
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_1', 'COUPON GIẢM THÊM 8%', 'admin', 'rule', '648', 703);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_2', 'COUPON GIẢM THÊM 10%', 'admin', 'rule', '649', 200);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_3', 'COUPPON 15%', 'admin', 'rule', '650', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_4', 'SỔ TAY CÁNH CỤT Ú', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_5', 'BỘ POSTCARD KỲ BÍ', 'admin', 'gift', '1', 50);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_6', 'NOTEBOOK NHẬT KÝ DIỆT MA', 'admin', 'gift', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_7', '1 lần FREESHIP', 'admin', 'freeship', '1', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('catchghost-2683', 'catchghost_gift_8', 'KHUNG ẢNH SẮC MÀU', 'admin', 'gift', '1', 15);");

$installer->endSetup();
