php bin/console d:d:c --if-not-exists
php bin/console d:s:u --force
php bin/console d:f:l --append
php bin/console lexik:jwt:generate-keypair --overwrite
php bin/console c:c
chmod -R 777 var/
chmod -R 777 vendor/