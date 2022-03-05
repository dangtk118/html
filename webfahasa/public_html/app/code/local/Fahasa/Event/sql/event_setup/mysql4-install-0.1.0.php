<?php

$installer = $this;
$installer->startSetup();

$installer->run('
insert into fahasa_events (event_id , name , content, date_begin, date_end, active , event_password , type, channel , created_at, created_by)
values("worldcup_match1","ban ket 1 worldcup 2018","Du doan doi thang tran dau ban ket 1 worldcup 2018", "2018-07-09 09:00:00", "2018-07-10 23:00:00", 1 , "", "choose", "web", now(), "admin");

insert into fahasa_events (event_id , name , content, date_begin, date_end, active , event_password , type, channel , created_at, created_by)
values("worldcup_match2","ban ket 2 worldcup 2018","Du doan doi thang tran dau ban ket 2 worldcup 2018", "2018-07-11 09:00:00", "2018-07-11 23:00:00", 1 , "", "choose", "web", now(), "admin");

insert into fahasa_events (event_id , name , content, date_begin, date_end, active , event_password , type, channel , created_at, created_by)
values("worldcup_match3","ban ket 3 worldcup 2018","Du doan doi thang tran dau tranh hang 3 worldcup 2018", "2018-07-12 09:00:00", "2018-07-13 23:00:00", 1 , "", "choose", "web", now(), "admin");

insert into fahasa_events (event_id , name , content, date_begin, date_end, active , event_password , type, channel , created_at, created_by)
values("worldcup_match4","ban ket 4 worldcup 2018","Du doan doi thang tran dau chung ket worldcup 2018", "2018-07-14 09:00:00", "2018-07-23 23:00:00", 1 , "", "choose", "web", now(), "admin");
     
ALTER TABLE fahasa_user_event_log ADD coupon_code varchar(255);

ALTER TABLE fahasa_user_event_log DROP INDEX `UC_Person`;
ALTER TABLE fahasa_user_event_log  ADD UNIQUE KEY `UC_Person` (`event_id`,`email`,`attend_code`);
');

$installer->endSetup();
