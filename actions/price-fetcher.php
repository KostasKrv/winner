<?php

/**
 * This script should fetch all prices available from binance
 * and then store them in the db. 
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
        echo 'Starting bookPrices -> ' . date('Y/m/d H:i:s') . '<br />';
        $insertSets = array();
        $bookPrices = Config::getInstance()->binanceApi()->bookPrices();

        echo 'Ending bookPrices -> ' . date('Y/m/d H:i:s') . '<br />';

        $_dateToFormat = 'Y-m-d H:i:s.v';
        $_now = new \DateTime();
        //$_timezone = new \DateTimeZone('Europe/Athens');
        //$_now->setTimezone($_timezone);

        foreach ($bookPrices as $symbol => $symbolData) {
            if (!Utils::shouldProcessSymbol(strtoupper($symbol))) {
                continue;
            }

            echo $symbol . ' -> ' . var_export($symbolData, true) . '<br />';

            $inserted = Utils::insertCoinPriceIntoDb($symbol, floatval($symbolData['bid']), floatval($symbolData['ask']), $_now);

            if ($inserted === true) {
                $insertSets[$symbol] = 1;
            }
        }        
    } catch (\Exception $e) {
        var_dump($e);
    }

    echo '<h3>Inserted ' . ($c = count($insertSets)) . ' coin prices</h3>';
    echo '<h3>Skipped ' . (count($bookPrices) - $c) . ' coin prices</h3>';

    Utils::deleteLockFile();
    ?>

    <script>
        setTimeout(function() {
            location.reload();
        }, 15 * 1000);
    </script>

</body>

</html>