<?php
$installer = $this;
$installer->startSetup();
$installer->run("
create table fhs_varnish_cache_reload_timestamp(
    cache_id varchar(64) primary key,
    last_reload_timestamp timestamp default 0,
    cache_type varchar(128)
);
insert into fhs_varnish_cache_reload_timestamp values ('turpentine_esi_blocks', now(), 'varnish');
insert into fhs_varnish_cache_reload_timestamp values ('turpentine_pages', now(), 'varnish');
");

$installer->endSetup();

