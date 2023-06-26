<?php

namespace WinnerApp;

use DateInterval;
use DateTime;
use Exception;

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

    const DB_PRICE_TABLE = 'coin_prices';
    static function fetchLastInsertedPrices()
    {
        global $GLOBAL_CONFIG;
        $_DB = $GLOBAL_CONFIG->db();
        $selectQuery = "SELECT DISTINCT(coin_symbol),bid_price,ask_price,price_date FROM `" . Utils::DB_PRICE_TABLE . "` ORDER BY price_date DESC";

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

    /**
     * Insert a coin price into the db
     *
     * @param string $symbol The symbol of the coin
     * @param float $bidPriceFloatVal The highest price a buyer is willing to pay
     * @param float $askPriceFloatVal The minimum price a seller is willing to accept
     * @param DateTime $date The date of the price
     *      
     * @return string | boolean
     */
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

            $_DB->insert(Utils::DB_PRICE_TABLE, $_set);
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
        $mainException = null;

        try {
            $startOfApi = new \DateTime();

            $insertSets = $skippedArray = $errorSets = array();
            $bookPrices = Config::getInstance()->binanceApi()->bookPrices();

            $_now = $endOfApi = new \DateTime();

            foreach ($bookPrices as $symbol => $symbolData) {

                $bidPriceFloat = floatval($symbolData['bid']);
                $askPriceFloat = floatval($symbolData['ask']);
                $symbol = strtoupper($symbol);

                if (!Utils::shouldProcessSymbol($symbol, $bidPriceFloat, $askPriceFloat)) {
                    $skippedArray[$symbol] = $symbolData;
                    continue;
                }

                /// Inserted is an error or a boolean
                $inserted = Utils::insertCoinPriceIntoDb($symbol, $bidPriceFloat, $askPriceFloat, $_now);

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
        $coins = array(
            '1INCHUSDT',
            'ACAUSDT',
        );

        $wantedIntervals = array(
            0 => '1 MINUTE',
            //'1 minute' => '1 MINUTE',
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
        foreach ($wantedIntervals as $i => $k) {
            $time = new DateTime();
            $time->sub(DateInterval::createFromDateString($k));
            $wantedIntervals[$i] = array($k, $time);
        }

        $results = array();
        foreach ($coins as $coinSymbol) {
            foreach ($wantedIntervals as $intvlKey => $intvlArray) {
                $result = Utils::findCoinPriceAtGivenTime($coinSymbol, $intvlArray[1]);
                $results[$coinSymbol][$intvlKey] = $result;
            }
        }

        return $results;
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
            . " FROM " . Utils::DB_PRICE_TABLE . " cp"
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

    const PKG_BOOTSTRAP = 'bootstrap';
    const PKG_CHARTS = 'charts';
    const PKG_FONTAWESOME = 'font-awesome';

    const PKG_DEFAULTS = array(
        Utils::PKG_BOOTSTRAP,
        Utils::PKG_FONTAWESOME,
    );

    public static function htmlHeader($title = '', $INCLUDETHESE = array())
    {
        $INCLUDETHESE = array_merge(Utils::PKG_DEFAULTS, $INCLUDETHESE);
?>
        <!doctype html>
        <html lang="en">

        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Le winner<?php if (!empty($title)) : ?> | <?php echo $title ?><?php endif ?></title>

            <?php if (in_array(Utils::PKG_BOOTSTRAP, $INCLUDETHESE)) : ?>
                <!-- Bootstrap -->
                <link href="/public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
                <?php /* <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous"> */ ?>
            <?php endif; ?>

            <?php if (in_array(Utils::PKG_FONTAWESOME, $INCLUDETHESE)) : ?>
                <!-- Bootstrap -->
                <link href="/public/font-awesome/css/bootstrap.min.css" rel="stylesheet">
                <?php /* <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous"> */ ?>
            <?php endif; ?>
        </head>

        <body class="bg-body-tertiary">
        <?php
    } /// Function htmlHeader


    static function htmlFooter($INCLUDETHESE = array())
    {
        $INCLUDETHESE = array_merge(Utils::PKG_DEFAULTS, $INCLUDETHESE);
        ?>
            <!-- From htmlFooterUtil -->
            <diV id="htmlFooterFromFunction">
                <script type="text/javascript" src="/public/jquery/jquery.min.js"></script>

                <?php if (in_array(Utils::PKG_BOOTSTRAP, $INCLUDETHESE)) : ?>
                    <script type="text/javascript" src="/public/bootstrap/js/bootstrap.min.js"></script>
                <?php endif; ?>

                <?php if (in_array(Utils::PKG_CHARTS, $INCLUDETHESE)) : ?>
                    <script type="text/javascript" src="/public/bootstrap/js/bootstrap.min.js"></script>
                <?php endif; ?>

                <?php if (in_array(Utils::PKG_FONTAWESOME, $INCLUDETHESE)) : ?>
                <!-- Bootstrap -->
                <link href="/public/font-awesome/css/bootstrap.min.css" rel="stylesheet">
                <?php /* <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous"> */ ?>
            <?php endif; ?>
            </diV>
        </body>

        </html>
<?php
    } /// Function htmlFooter
}
