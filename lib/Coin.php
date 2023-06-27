<?php

namespace WinnerApp;

use DateInterval;
use DateTime;
use Exception;

class CoinService
{
    const DB_PRICE_TABLE = 'coin_prices';    
    const FLOAT_PRECISION = 9;
    
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
                $chartArray[$coinRow['coin_symbol']][$intvlKey] = $coinRow;
            }
        }

        /// Fill in empty values
        foreach ($wantedIntervals as $intvlKey => $intvlArray) {
            foreach ($chartArray as $coinSymbol => $pricesArray) {
                if (!array_key_exists($intvlKey, $pricesArray)) {
                    $chartArray[$coinSymbol][$intvlKey] = null;
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

            $chartArray[$coinSymbol] = array_merge(array(0 => $coinRow), $chartArray[$coinSymbol]);
        }

        /// Fill current values with zeros if not found
        foreach ($chartArray as $coinSymbol => $pricesArray) {
            if (!array_key_exists(0, $pricesArray)) {
                $results[$coinSymbol] = array_merge(array(0 => null), $chartArray[$coinSymbol]);
            }
        }

        /// At this point we should have the full chart with prices

        /// Calculate the differences from now
        if (false)
        foreach ($chartArray as $coinSymbol => $pricesArray) {
            /// This means we don't have the latest price
            if (!array_key_exists(0, $pricesArray)) {
                continue;
            }

            $priceNowRow = $pricesArray[0];
            $bidPriceNow = $priceNowRow['bid_price'];
            $askPriceNow = $priceNowRow['ask_price'];

            /// Loop through the rest of the prices and do the calculations
            foreach ($pricesArray as $priceTimeText => $priceRow) {
                var_dump("priceTimeText -> $priceTimeText");
                var_dump($priceRow);
                if ($priceTimeText == 0 || empty($priceRow)) {
                    continue;
                }

                $bidPriceRow = $priceRow['bid_price'];
                $askPriceRow = $priceRow['ask_price'];

                $prc = CoinService::FLOAT_PRECISION;
                $bidDiff = bcsub($bidPriceNow, $bidPriceRow, $prc);

                $bidPercentage = 0;
                if ($bidPriceRow > 0) {                    
                    $bidPercentage = bcmul(bcdiv($bidDiff, $bidPriceRow, $prc), 100, $prc);
                }

                $askDiff = bcsub($askPriceNow, $askPriceRow, $prc);
                $askPercentage = 0;
                if ($askPriceRow > 0) {
                    $askPercentage = bcmul(bcdiv($askDiff, $askPriceRow, $prc), 100, $prc);
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
}
