source /opt/rh/rh-php70/enable

echo `date` ": Run Update Catalog Cache"

cd /home/webfahasa/public_html/shell/
php catalog_reloadCache.php

echo `date` ": DONE Update Catalog Cache"
