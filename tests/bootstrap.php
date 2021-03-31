<?php

use Symfony\Component\Dotenv\Dotenv;

passthru(sprintf(
    'php "%s/../bin/console" doctrine:database:create --env=test --if-not-exists --no-interaction',
    __DIR__
));
echo "Resetting test database...";
passthru(sprintf(
    'php "%s/../bin/console" doctrine:schema:drop --env=test --force --no-interaction',
    __DIR__
));
passthru(sprintf(
    'php "%s/../bin/console" doctrine:schema:update --env=test --force --no-interaction',
    __DIR__
));
echo " Done" . PHP_EOL . PHP_EOL;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}