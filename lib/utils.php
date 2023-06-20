<?php

namespace WinnerApp;

class Utils
{
    static function startsWith($haystack, $needle)
    {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }

    static  function endsWith($haystack, $needle)
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    static function ensureOneProccess()
    {
        global $GLOBAL_CONFIG;
        $fileName = $GLOBAL_CONFIG->lockFile();
        if (file_exists($fileName)) {
            echo 'Halted. Another process is running!<br />';
            exit;
        }

        file_put_contents($fileName, date('Y-m-d HH:mm:ii:ss'));
    }

    static function deleteLockFile()
    {
        global $GLOBAL_CONFIG;
        $fileName = $GLOBAL_CONFIG->lockFile();
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    static function shouldProcessSymbol($symbol)
    {
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

    static function fetchLastInsertedPrices()
    {
        global $GLOBAL_CONFIG;
        $_DB = $GLOBAL_CONFIG->db();
        $selectQuery = "SELECT DISTINCT(coin_symbol),bid_price,ask_price,price_date FROM `coin_prices` ORDER BY price_date DESC";

        $results = $_DB->select($selectQuery);

        if (empty($results)) {
            return array();
        }
        $prices = array();
        foreach ($results as $resIndex => $result) {
            $prices[$result["coin_symbol"]] = $result;
        }

        return $prices;
    }

    static function insertCoinPriceIntoDb($symbol, $bidPriceFloatVal, $askPriceFloatVal, $date)
    {
        global $GLOBAL_CONFIG;
        $_DB = $GLOBAL_CONFIG->db();
        try {

            $_dateToFormat = 'Y-m-d H:i:s.v';
            $_now = $date;
            /*$_now = new DateTime();
        $_timezone = new DateTimeZone('Europe/Athens');
        $_now->setTimezone($_timezone);*/

            $_set = [
                'coin_symbol' => $symbol,
                'bid_price' => $bidPriceFloatVal,
                'ask_price' => $askPriceFloatVal,
                'price_date' => $_now->format($_dateToFormat),
                'price_date_minute' => $_now->format('YmdHi'),
            ];

            $_DB->insert('coin_prices', $_set);
        } catch (\Delight\Db\Throwable\IntegrityConstraintViolationException $e) {
            /// Skipped because same price inside minute                
            return false;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    static function deleteDump(){
        /*$_DB->exec(
            "DELETE FROM coin_prices WHERE price_date < ?",
            array($before->format($_dateToFormat))
        );*/
    }
}
