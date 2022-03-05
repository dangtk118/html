<?php
$installer = $this;
$installer->startSetup();
$installer->run("
create table fhs_phieudangky
(phieu_id int not null auto_increment, 
name text,  
phone text, 
email text, 
wherefrom text,
date datetime, 
primary key(phieu_id));
");

$installer->endSetup();

	 