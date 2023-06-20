<?php
set_time_limit(60 * 50);
require_once('vendor/autoload.php');
require_once('utils.php');

/// Db section
$dbHost = getenv('DB_HOSTNAME');
$dbName = getenv('DB_NAME');
$dbPort = getenv('DB_PORT');
$dbUser = getenv('DB_USERNAME');
$dbPass = getenv('DB_PASSWORD');

$dataSource = new \Delight\Db\PdoDataSource('mysql');
$dataSource->setHostname($dbHost);
$dataSource->setPort($dbPort);
$dataSource->setDatabaseName($dbName);
$dataSource->setCharset('utf8mb4');
$dataSource->setUsername($dbUser );
$dataSource->setPassword($dbPass);

global $_DB;
$_DB = \Delight\Db\PdoDatabase::fromDataSource($dataSource);

/// Binance api
$binancePK = getenv('BINANCE_PUBLIC_KEY');
$binanceSK = getenv('BINANCE_SECRET_KEY');

global $_BINANCE_API;
$_BINANCE_API = new Binance\API($binancePK, $binanceSK);