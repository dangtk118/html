update fhs_core_config_data set value = 0
where `path` = 'netcore/general/enable'
or `path` = 'chin_media/general/enable'
or `path` = 'accesstrade/general/enable';
