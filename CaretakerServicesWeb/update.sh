cp -r assets public 
php bin/console asset-map:compile
php bin/console c:c
chmod -R 777 var/
chmod -R 777 vendor/