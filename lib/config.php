<?php

namespace WinnerApp;

require_once(__DIR__ . '/../vendor/autoload.php');

require_once(__DIR__ . '/Utils.php');
require_once(__DIR__ . '/Coin.php');
require_once(__DIR__ . '/JsonResponse.php');

global $GLOBAL_CONFIG;
class Config
{
    function __construct()
    {   
        set_time_limit(60 * 50);
        date_default_timezone_set('Europe/Athens');

        $this->initDB();
        $this->initBinanceApi();
        //$this->enableCatchFatalErrorsHandler();
    }

    /// As seen in https://spencermortensen.com/articles/php-error-handling/
    private function enableCatchFatalErrorsHandler()
    {
        /// Step 1
        $onError = function ($level, $message, $file, $line) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        };

        try {
            set_error_handler($onError);
        } catch (\Throwable $throwable) {
            Config::logError('Throwable: ' . $throwable->getMessage() . "\n");
        } finally {
            restore_error_handler();
        }

        /// Step 2
        $onShutdown = function () {
            $error = error_get_last();

            if ($error === null) {
                return;
            }

            $exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);

            Config::logError('Error: ' . $exception->getMessage() . "\n");
        };

        register_shutdown_function($onShutdown);
        error_reporting(1);

        /// Step 3
        $onException = function ($exception) {
            Config::logError("Exception: " . $exception->getMessage() . "\n");
        };

        set_exception_handler($onException);
    }

    public static function logError($exceptionString)
    {
        echo $exceptionString;
    }

    private $DB;
    public function db()
    {
        return $this->DB;
    }

    private $BINANCE_API;
    public function binanceApi()
    {
        return $this->BINANCE_API;
    }

    private $LOCKFILE = __DIR__ . '/lock_file.lock';
    public function lockFile()
    {
        return $this->LOCKFILE;
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

    public static function getInstance()
    {
        global $GLOBAL_CONFIG;
        return $GLOBAL_CONFIG;
    }
}

$GLOBAL_CONFIG = new Config();
