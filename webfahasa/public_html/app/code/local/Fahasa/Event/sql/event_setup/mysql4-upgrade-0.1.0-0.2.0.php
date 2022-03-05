<?php

$installer = $this;

$installer->startSetup();

// insert fahasa_events
$installer->run("insert into fahasa_events (event_id, name, content, date_begin, date_end, active, event_password, type, channel, created_at, created_by) values('birthdaycake', 'Birthday Cake', 'Chương trình cắt bánh sinh nhật Fahasa 2018', '2018-08-01 00:00:00', '2018-08-07 23:59:59', 1, '', 'random_gift', 'web', now(), 'theanh');");

// edit fahasa_event_gift
$installer->run("ALTER TABLE fahasa_event_gift ADD value varchar(255);");
$installer->run("ALTER TABLE fahasa_event_gift ADD type varchar(255);");
$installer->run("ALTER TABLE fahasa_event_gift ADD times_use varchar(255);");

// insert fahasa_event_gift
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_1', '5K F-Point - Tặng bạn phần quà nhỏ xinh để mừng sinh nhật cho FAHASA nè!', 'theanh', 'fpoint', '5000', 1467);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_2', '24K F-Point - Bạn đã khám phá Daily Deals FAHASA giảm duy nhất trong 24H?', 'theanh', 'fpoint', '24000', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_3', '35K F-Point - Bật mí là FAHASA sách cực hay giá cực giảm mỗi thứ 3 & thứ 5!', 'theanh', 'fpoint', '35000', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_4', '42K F-Point - Cắt bánh mừng FAHASA 42 tuổi!', 'theanh', 'fpoint', '42000', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_5', '68K F-Point - Sinh nhật 6 - 8 FAHASA cung Sư Tử đó nha!', 'theanh', 'fpoint', '68000', 2);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_6', '103K F-Point - Mừng nhà sách thứ 103 FAHASA!', 'theanh', 'fpoint', '103000', 1);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_7', '1 Lần Freeship - Tặng bạn vì FAHASA thích giao hàng tốc độ!', 'theanh', 'freeship', '1', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_8', 'Mega Code 8% - Thêm 8% nhiệt huyết gom đầy sách hay!', 'theanh', 'rule', '771', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_9', 'Super Code 12% - Thêm 12% đam mê với sách FAHASA!', 'theanh', 'rule', '772', 5);");
$installer->run("insert into fahasa_event_gift (event_id, name, description, created_by, type, value, times_use) values('birthdaycake', 'birthdaycake_gift_10', 'VIP Code 15% - Thêm 15% yêu thương cho giỏ hàng FAHASA!', 'theanh', 'rule', '773', 3);");

$installer->endSetup();
