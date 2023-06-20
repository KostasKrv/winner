<?php

namespace WinnerApp;

set_time_limit(60 * 50);
date_default_timezone_set('Europe/Athens');

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/utils.php');

global $GLOBAL_CONFIG;
class Config
{
    private $DB;
    public function db(){
        return $this->DB;
    }

    private $BINANCE_API;
    public function binanceApi(){
        return $this->BINANCE_API;
    }

    private $LOCKFILE = __DIR__ . '/lock_file.lock';
    public function lockFile(){
        return $this->LOCKFILE;
    }

    function __construct()
    {
        $this->initDB();
        $this->initBinanceApi();
    }

    ///https://github.com/binance-exchange/php-binance-api
    private function initBinanceApi()
    {
        $binancePK = getenv('BINANCE_PUBLIC_KEY');
        $binanceSK = getenv('BINANCE_SECRET_KEY');
        
        $this->BINANCE_API = new \Binance\API($binancePK, $binanceSK);
    }

    private function initDB()
    {
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
        $dataSource->setUsername($dbUser);
        $dataSource->setPassword($dbPass);

        //global $_DB;
        $this->DB = \Delight\Db\PdoDatabase::fromDataSource($dataSource);
    }

    public static function getInstance(){
        global $GLOBAL_CONFIG;
        return $GLOBAL_CONFIG;
    }
}

$GLOBAL_CONFIG = new Config();
