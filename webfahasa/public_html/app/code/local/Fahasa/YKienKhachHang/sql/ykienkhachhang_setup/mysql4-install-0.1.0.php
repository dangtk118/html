<?php
$installer = $this;
$installer->startSetup();
$installer->run("
create table y_kien_khach_hang(
    id int not null auto_increment, 
    customer_email varchar(64),
    order_id  varchar(64),
    chat_luong_sp int,
    chat_luong_sp_note text null,
    thoi_gian_giao_hang int,
    thoi_gian_giao_hang_note text null,
    thai_do_nv_giao_hang int,
    thai_do_nv_giao_hang_note text null,
    nv_giao_hang_lien_he_truoc_khi_giao int,
    cham_soc_khach_hang int ,
    cham_soc_khach_hang_note text null,
    coupon text null,
    date datetime,
    primary key(id));
");

$installer->endSetup();