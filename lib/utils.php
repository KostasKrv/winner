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
        global $GLOBAL_CONFIG;
        $_DB = $GLOBAL_CONFIG->db();
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

            $_DB->insert('coin_prices', $_set);
        } catch (\Delight\Db\Throwable\IntegrityConstraintViolationException $e) {
            return $e->getMessage();
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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

    static function deleteDump()
    {
        /*$_DB->exec(
            "DELETE FROM coin_prices WHERE price_date < ?",
            array($before->format($_dateToFormat))
        );*/
    }

    public static function htmlHeader($title = '', $includeBootstrap = true)
    { ?>
        <!doctype html>
        <html lang="en">

        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Le winner<?php if (!empty($title)) : ?> | <?php echo $title ?><?php endif ?></title>

            <?php if ($includeBootstrap === true) : ?>
                <!-- Bootstrap -->
                <link href="/public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
                <?php /* <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous"> */ ?>
            <?php endif; ?>
        </head>

        <body class="bg-body-tertiary">
        <?php
    } /// Function htmlHeader

    static function htmlFooter($includeBootstrap = true)
    { ?>
            <!-- From htmlFooterUtil -->
            <diV id="htmlFooterFromFunction">
                <?php if ($includeBootstrap === true) : ?>
                    <script type="text/javascript" src="/public/bootstrap/js/bootstrap.min.js"></script>
                <?php endif; ?>
            </diV>

        </body>

        </html>
        <?php
    } /// Function htmlFooter
}
