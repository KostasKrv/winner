<?php

/**
 * This script should fetch all prices available from binance
 * and then store them in the db. 
 
 * 
 * 
 */
namespace WinnerApp;
require_once __DIR__ . '/../lib/config.php';

Utils::ensureOneProccess();
?>
<html>

<head>
    <title>Le winner</title>
</head>

<body>
    <?php

    try {
        echo 'Starting bookPrices->' . date('Y/m/d H:i:s') . '<br />';
        $insertSets = array();
        $bookPrices = Config::getInstance()->binanceApi()->bookPrices();

        echo 'Ending bookPrices->' . date('Y/m/d H:i:s') . '<br />';

        $_dateToFormat = 'Y-m-d H:i:s.v';
        $_now = new \DateTime();
        //$_timezone = new \DateTimeZone('Europe/Athens');
        //$_now->setTimezone($_timezone);

        foreach ($bookPrices as $symbol => $symbolData) {            
            if (!shouldProcessSymbol(strtoupper($symbol))) {
                continue;
            }

            echo $symbol . '<br />';
            var_dump($symbolData);
            echo '<br />';

            //$inserted = insertCoinPriceIntoDb($symbol, floatval($symbolData['bid']), floatval($symbolData['ask']), $_now);

            try {

                $_dateToFormat = 'Y-m-d H:i:s.v';
                //$_now = $date;
                $_now = new \DateTime();
                $_timezone = new \DateTimeZone('Europe/Athens');
                $_now->setTimezone($_timezone);
        
                $_set = [
                    'coin_symbol' => $symbol,
                    'bid_price' => floatval($symbolData['bid']),
                    'ask_price' => floatval($symbolData['ask']),
                    'price_date' => $_now->format($_dateToFormat),
                    'price_date_minute' => $_now->format('YmdHi'),
                ];
                
                echo $symbol . ':' . date('Y/m/d H:i:s') . '<br />';

                $_DB->insert('coin_prices', $_set);
                $insertSets[$symbol] = 1;
                
                
            } catch (\Delight\Db\Throwable\IntegrityConstraintViolationException $e) {
                /// Skipped because same price inside minute                
                //return false;
            } catch (\Exception $e) {
                //return false;
            }


            /*if ($inserted === true) {
                $insertSets[$symbol] = 1;
            }*/
        }

        /*$_DB->exec(
            "DELETE FROM coin_prices WHERE price_date < ?",
            array($before->format($_dateToFormat))
        );*/
    } catch (\Exception $e) {
        var_dump($e);
    }

    echo '<h3>Inserted ' . ($c = count($insertSets)) . ' coin prices</h3>';
    echo '<h3>Skipped ' . (count($bookPrices) - $c) . ' coin prices</h3>';
    /*
echo '<pre>';
var_dump($insertSets);
echo '</pre>';
*/


    //$ticks = $_BINANCE_API->candlesticks("BNBBTC", "5m");
    //print_r($ticks);
    /*echo '<pre>';
var_dump($ticks);
echo '</pre>';*/

    Utils::deleteLockFile();

    ?>

    <script>
        setTimeout(function() {
            location.reload();
        }, 15 * 1000);
    </script>

</body>

</html>