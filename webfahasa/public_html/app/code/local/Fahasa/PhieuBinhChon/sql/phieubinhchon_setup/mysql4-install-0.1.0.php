<?php
$installer = $this;
$installer->startSetup();
$installer->run("
create table fhs_phieubinhchon
(phieu_id int not null auto_increment, 
name text, 
cmnd_id text, 
job text, 
address text, 
phone text, 
email text, 
date datetime, 
book_name text, 
author text, 
publisher text, 
number text, 
primary key(phieu_id));
");

$installer->endSetup();

	 