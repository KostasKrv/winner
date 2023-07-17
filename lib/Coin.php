<?php

namespace WinnerApp;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

require_once(__DIR__ . '/config.php');

class CoinService
{
    const DB_PRICE_TABLE = 'coin_prices';
    const FLOAT_PRECISION = 20;

    static function shouldProcessSymbol($symbol, $bidPrice, $askPrice)
    {
        /// Zero prices check
        if (empty($bidPrice) || empty($askPrice)) {
            return false;
        }

        if (!Utils::endsWith($symbol, 'USDT')) {
            return false;
        }

        if (Utils::endsWith($symbol, 'UPUSDT')) {
            return false;
        }

        if (Utils::endsWith($symbol, 'DOWNUSDT')) {
            return false;
        }

        return true;
    }

    static function insertCoinPriceIntoDb($symbol, $bidPriceFloatVal, $askPriceFloatVal, $date)
    {
        $_DB = Config::getInstance()->db();

        try {
            $_dateToFormat = 'Y-m-d H:i:s.v';
            $_now = $date;

            $_set = [
                'coin_symbol' => $symbol,
                'bid_price' => $bidPriceFloatVal,
                'ask_price' => $askPriceFloatVal,
                'price_date' => $_now->format($_dateToFormat),
                'price_date_minute' => $_now->format('YmdHi'),
            ];

            $_DB->insert(CoinService::DB_PRICE_TABLE, $_set);
        } catch (\Delight\Db\Throwable\IntegrityConstraintViolationException $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    static function fetchAndInsertPricesIntoDb()
    {
        Utils::ensureOneProccess();

        $CONFIG = Config::getInstance();
        $mainException = null;

        try {
            $startOfApi = new \DateTime();

            $insertSets = $skippedArray = $errorSets = array();
            $bookPrices = $CONFIG->binanceApi()->bookPrices();

            $_now = $endOfApi = new \DateTime();

            foreach ($bookPrices as $symbol => $symbolData) {

                $bidPriceFloat = floatval($symbolData['bid']);
                $askPriceFloat = floatval($symbolData['ask']);
                $symbol = strtoupper($symbol);

                if (!CoinService::shouldProcessSymbol($symbol, $bidPriceFloat, $askPriceFloat)) {
                    $skippedArray[$symbol] = $symbolData;
                    continue;
                }

                /// Inserted is an error or a boolean
                $inserted = CoinService::insertCoinPriceIntoDb($symbol, $bidPriceFloat, $askPriceFloat, $_now);

                if ($inserted === true) {
                    $insertSets[$symbol] = $symbolData;
                } else {
                    $errorSets[$symbol] = $inserted;
                }
            }
        } catch (Exception $e) {
            $mainException = $e->getMessage();
        }
        Utils::deleteLockFile();

        return array(
            'insertedArray' => $insertSets,
            'errorArray' => $errorSets,
            'skippedArray' => $skippedArray,
            'apiPricesArray' => $bookPrices,
            'mainExceptionString' => $mainException,
            'startOfApiDateTime' => $startOfApi,
            'endOfApiDateTime' => $endOfApi,
        );
    }

    static function findCoinPricesChart()
    {
        $wantedIntervals = array(
            '1 minute' => '1 MINUTE',
            '2 minutes' => '2 MINUTES',
            '4 minutes' => '4 MINUTES',
            '5 minutes' => '5 MINUTES',
            '10 minutes' => '10 MINUTES',
            '15 minutes' => '15 MINUTES',
            '30 minutes' => '20 MINUTES',
            '1 hour' => '1 HOUR',
            '2 hours' => '2 HOURS',
            '3 hours' => '3 HOURS',
            '6 hours' => '6 HOURS',
            '8 hours' => '8 HOURS',
            '16 hours' => '16 HOURS',
            '1 day' => '1 DAY',
            '32 hours' => '32 HOURS',
            '2 days' => '2 DAYS',
            '3 days' => '3 DAYS',
        );

        $NOW = new DateTime();

        /// Fill intervals
        $_dateToFormat = 'YmdHi';
        foreach ($wantedIntervals as $i => $k) {
            $time = new DateTime();
            $time->sub(DateInterval::createFromDateString($k));
            $timeInt = intval($time->format($_dateToFormat)); /// Do in int to avoid many conversions later
            $wantedIntervals[$i] = array(
                'key' => $k,
                'timestampMinuteInt' => $timeInt
            );
        }

        $chartArray = array();

        /// Fetch the prices from the db. There may be empty for some coins
        foreach ($wantedIntervals as $intvlKey => $intvlArray) {
            $resultsRowArray = CoinService::findCoinPricesAtOnce($intvlArray['timestampMinuteInt']);
            foreach ((array) $resultsRowArray as $coinRow) {
                $chartArray[$coinRow['coin_symbol']][$intvlArray['timestampMinuteInt']] = array_merge(array('time' => $intvlKey), $coinRow);
            }
        }

        /// Fill in empty values
        foreach ($wantedIntervals as $intvlKey => $intvlArray) {
            foreach ($chartArray as $coinSymbol => $pricesArray) {
                if (!array_key_exists($intvlArray['timestampMinuteInt'], $pricesArray)) {
                    $chartArray[$coinSymbol][$intvlArray['timestampMinuteInt']] = null;
                }
            }
        }

        /// Now fetch the current prices
        $currentPricesArray = CoinService::findLatestCoinPricesAtOnce();
        foreach ((array) $currentPricesArray as $coinRow) {
            $coinSymbol = $coinRow['coin_symbol'];
            if (array_key_exists(0, $chartArray[$coinSymbol])) {
                continue;
            }

            $chartArray[$coinSymbol][$NOW->format($_dateToFormat)] = array_merge(
                array('time' => 'now'),
                $coinRow
            );
        }

        /// Fill current values with zeros if not found
        foreach ($chartArray as $coinSymbol => $pricesArray) {
            if (!array_key_exists($NOW->format($_dateToFormat), $pricesArray)) {
                $results[$coinSymbol] = array_merge(array($NOW->format($_dateToFormat) => null), $chartArray[$coinSymbol]);
            }
        }

        /// Fill current values with zeros if not found
        foreach ($chartArray as $coinSymbol => $pricesArray) {
            krsort($pricesArray, SORT_DESC);
            $chartArray[$coinSymbol] = $pricesArray;
        }

        //return $chartArray;

        /// At this point we should have the full chart with prices

        /// Calculate the price differences/percentage from now
        $NOW_KEY = $NOW->format($_dateToFormat);
        $PRECISION = CoinService::FLOAT_PRECISION;

        foreach ($chartArray as $coinSymbol => $pricesArray) {

            /// This means we don't have the latest price
            if (!array_key_exists($NOW_KEY, $pricesArray)) {
                continue;
            }

            $priceNowRow = $pricesArray[$NOW_KEY];
            $bidPriceNow = $priceNowRow['bid_price'];
            $askPriceNow = $priceNowRow['ask_price'];

            /// Loop through the rest of the prices and do the calculations
            foreach ($pricesArray as $priceTimeText => &$priceRow) {
                if (empty($priceRow)) {
                    continue;
                }

                $priceRow['bid_price'] = Utils::toFloat($priceRow['bid_price']);
                $priceRow['ask_price'] = Utils::toFloat($priceRow['ask_price']);

                $bidPriceRow = $priceRow['bid_price'];
                $askPriceRow = $priceRow['ask_price'];


                $bidDiff = bcsub($bidPriceNow, $bidPriceRow, $PRECISION);

                $bidPercentage = 0;
                if ($bidPriceRow > 0) {
                    $bidPercentage = bcmul(bcdiv($bidDiff, $bidPriceRow, $PRECISION), 100, 2);
                }
                $askDiff = Utils::toFloat(bcsub($askPriceNow, $askPriceRow, $PRECISION));
                $askPercentage = 0;
                if ($askPriceRow > 0) {
                    $askPercentage = bcmul(bcdiv($askDiff, $askPriceRow, $PRECISION), 100, 2);
                }

                $chartArray[$coinSymbol][$priceTimeText]['bid_difference'] = Utils::toFloat($bidDiff);
                $chartArray[$coinSymbol][$priceTimeText]['bid_percentage'] = Utils::toFloat($bidPercentage);
                $chartArray[$coinSymbol][$priceTimeText]['ask_difference'] = Utils::toFloat($askDiff);
                $chartArray[$coinSymbol][$priceTimeText]['ask_percentage'] = Utils::toFloat($askPercentage);
            }
        }

        return $chartArray;
    }

    static function findLatestCoinPricesAtOnce()
    {
        $GLOBAL_CONFIG = Config::getInstance();
        $_DB = $GLOBAL_CONFIG->db();

        $thresholdInMinutesInt = 1;
        $wantedDateTime = new DateTime();

        if ($wantedDateTime instanceof DateTime) {
            $_wantedDateTimeMinuteInt = intval($wantedDateTime->format('YmdHi'));
        } else {
            $_wantedDateTimeMinuteInt = $wantedDateTime;
        }

        $_oldestPriceMinuteInt = $_wantedDateTimeMinuteInt - $thresholdInMinutesInt;

        $selectQuery = ""
            . " SELECT DISTINCT(coin_symbol), bid_price, ask_price, price_date, price_date_minute"
            . " FROM " . CoinService::DB_PRICE_TABLE . " cp"
            . " WHERE"
            . " cp.price_date_minute >= $_oldestPriceMinuteInt"
            . " ORDER BY ABS(CURRENT_DATE - cp.price_date) DESC, coin_symbol, cp.price_date ASC";

        //echo $selectQuery;

        $result = $_DB->select(
            $selectQuery,
            array()
        );

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    static function findCoinPricesAtOnce($wantedDateTime, $thresholdInMinutesInt = 1)
    {
        global $GLOBAL_CONFIG;
        $_DB = $GLOBAL_CONFIG->db();

        if ($wantedDateTime instanceof DateTime) {
            $_wantedDateTimeMinuteInt = intval($wantedDateTime->format('YmdHi'));
        } else {
            $_wantedDateTimeMinuteInt = $wantedDateTime;
        }

        $_oldestPriceMinuteInt = $_wantedDateTimeMinuteInt - $thresholdInMinutesInt;
        $_newestPriceMinuteInt = $_wantedDateTimeMinuteInt + $thresholdInMinutesInt;

        /// eg select * from coin_prices cp where cp.coin_symbol = '1INCHUSDT' AND cp.price_date_minute > 202306221536 AND cp.price_date_minute < 202306221556 ORDER BY ABS(202306221540 - cp.price_date_minute) LIMIT 1
        $selectQuery = ""
            . " SELECT DISTINCT(coin_symbol), bid_price, ask_price, price_date, price_date_minute"
            . " FROM " . CoinService::DB_PRICE_TABLE . " cp"
            . " WHERE"
            . " cp.price_date_minute > $_oldestPriceMinuteInt"
            . " AND"
            . " cp.price_date_minute < $_newestPriceMinuteInt"
            . " ORDER BY ABS($_wantedDateTimeMinuteInt - cp.price_date_minute), coin_symbol, cp.price_date ASC";

        //echo $selectQuery;

        $result = $_DB->select(
            $selectQuery,
            array(
                //$_oldestPriceMinuteInt,
                //$_newestPriceMinuteInt,
                //$_wantedDateTimeMinuteInt,
            )
        );

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    static function findCoinPriceAtGivenTime($coinSymbolString, $wantedDateTime, $thresholdInMinutesInt = 1)
    {
        global $GLOBAL_CONFIG;
        $_DB = $GLOBAL_CONFIG->db();

        if ($wantedDateTime instanceof DateTime) {
            $_wantedDateTimeMinuteInt = intval($wantedDateTime->format('YmdHi'));
        } else {
            $_wantedDateTimeMinuteInt = $wantedDateTime;
        }

        $_oldestPriceMinuteInt = $_wantedDateTimeMinuteInt - $thresholdInMinutesInt;
        $_newestPriceMinuteInt = $_wantedDateTimeMinuteInt + $thresholdInMinutesInt;

        /// eg select * from coin_prices cp where cp.coin_symbol = '1INCHUSDT' AND cp.price_date_minute > 202306221536 AND cp.price_date_minute < 202306221556 ORDER BY ABS(202306221540 - cp.price_date_minute) LIMIT 1
        $selectQuery = ""
            . " SELECT *"
            . " FROM " . CoinService::DB_PRICE_TABLE . " cp"
            . " WHERE cp.coin_symbol = ?"
            . " AND cp.price_date_minute > ?"
            . " AND cp.price_date_minute < ?"
            . " ORDER BY ABS(? - cp.price_date_minute), cp.price_date ASC"
            . " LIMIT 1";

        $result = $_DB->selectRow(
            $selectQuery,
            array(
                $coinSymbolString,
                $_oldestPriceMinuteInt,
                $_newestPriceMinuteInt,
                $_wantedDateTimeMinuteInt,
            )
        );

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    static function deleteDump()
    {
        /*$_DB->exec(
            "DELETE FROM coin_prices WHERE price_date < ?",
            array($before->format($_dateToFormat))
        );*/
    }

    static function fetchSymbolsToProcess()
    {
        $GLOBAL_CONFIG = Config::getInstance();
        $_DB = $GLOBAL_CONFIG->db();

        $symbolDbRows = $_DB->select('SELECT DISTINCT(cp.coin_symbol) FROM coin_prices cp');

        $symbols = array();
        foreach ($symbolDbRows as $symbolRow) {
            $symbols[] = $symbolRow['coin_symbol'];
        }

        return $symbols;
    }
}

class SegmentInserter extends JsonResponse
{
    const PARAM_COIN_SYMBOL = 'coin_symbol';

    function __construct()
    {
    }

    function action()
    {
        $symbolFromPost = filter_input(INPUT_POST, SegmentInserter::PARAM_COIN_SYMBOL);

        $this->processSymbol($symbolFromPost);
        $this->toJsonResponse();
        //$this->processSymbol('MATICUSDT');
    }

    function processSymbol($symbol)
    {
        $CONFIG = Config::getInstance();

        $_DB = $CONFIG->db();

        $dbTableName = 'coin_price_segments';
        $dateToFormat = 'Y-m-d H:i:s.v';
        $dateFromFormat = 'U.u';
        $_timezone = new DateTimeZone('Europe/Athens');

        //Periods: 1m,3m,5m,15m,30m,1h,2h,4h,6h,8h,12h,1d,3d,1w,1M
        $segmentType = "1h";        
        
        $ticks = $CONFIG->binanceApi()->candlesticks(
            $symbol,
            $segmentType,
            $limit = 24 * 2,
            //$startTime * = $endTime,
            //$endTime,
        );

        //var_dump($ticks);exit;
        foreach ($ticks as $tickTime => $tick) :
            /*
                [1635516000000]=> array(13) { 
                    ["open"]=> string(10) "0.00827100"
                    ["high"]=>string(10) "0.00832900"
                    ["low"]=>string(10) "0.00827100"
                    ["close"]=>string(10) "0.00829300"
                    ["volume"]=>string(11) "35.99663131"
                    ["openTime"]=>int(1635516000000)
                    ["closeTime"]=>int(1635516899999)
                    ["assetVolume"]=>string(13) "4334.45700000"
                    ["baseVolume"]=>string(11) "35.99663131"
                    ["trades"]=>int(3315)
                    ["assetBuyVolume"]=>string(13) "2557.97300000"
                    ["takerBuyVolume"]=>string(11) "21.24492037"
                    ["ignored"]=>string(1) "0"
                }
            */

            try {
                $_od = ($tick['openTime'] / 1000);
                $_oT = DateTime::createFromFormat($dateFromFormat, number_format($_od, 6, '.', ''));
                $_oT->setTimezone($_timezone);

                $_cd = ($tick['closeTime'] / 1000);
                $_cT = DateTime::createFromFormat($dateFromFormat, number_format($_cd, 6, '.', ''));
                $_cT->setTimezone($_timezone);

                $_set = [
                    'coin_symbol' => $symbol,
                    'low' => floatval($tick['low']),
                    'high' => floatval($tick['high']),
                    'open' => floatval($tick['open']),
                    'close' => floatval($tick['close']),
                    'open_time' => $ot = $_oT->format($dateToFormat),
                    'close_time' => $ct = $_cT->format($dateToFormat),
                    'segment_type' => $segmentType
                ];

                $_DB->insert($dbTableName, $_set);
                $this->dataArray[$symbol]["$ot-$ct"] = $_set;
            } catch (Exception $e) {
                $a = 'one_per_segment';

                if (strpos($e->getMessage(), $a) !== false) {                    
                    $this->dataArray[] = 'Skipped: ' . $e->getMessage();
                } else {
                    $this->errorsArray[] = $e->getMessage() . '. Stack: ' . var_export(json_encode($e, JSON_PRETTY_PRINT), true);
                }
            }
        endforeach;
    }
}
