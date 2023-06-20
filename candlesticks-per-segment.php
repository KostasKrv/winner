<?php

/**
 * This script should fetch all prices available from binance
 * and then store them in the db
 * 
 * https://github.com/binance-exchange/php-binance-api
 */

require_once 'config.php';
//ensureOneProccess();
global $_BINANCE_API;
?>
<html>

<head>
    <title>Le winner</title>
</head>

<body>
    <?php

    try {
        function redirectToSymbol($symbol)
        {
            global $haltAfterThis;
            if (!empty($haltAfterThis)) {
                $htmlToReturn = 'Halt because you said only one';
                return $htmlToReturn;
            }

            $paramName = 'symbol';
            $paramValue = $symbol;

            // get the current URL
            $currentUrl = $_SERVER['REQUEST_URI'];

            // remove any previous value of the parameter from the current URL
            $newUrl = preg_replace('/([?&])' . $paramName . '=[^&]+(&|$)/', '$1', $currentUrl);

            // append the new parameter to the URL
            $newUrl = (strpos($newUrl, '?') !== false) ? $newUrl . '&' : $newUrl . '?';
            $newUrl .= $paramName . '=' . $paramValue;

            $newUrl = str_replace(array('?&', '&&'), array('?', ''), $newUrl);

            $htmlToReturn = '<p>Now redirect to ' . $newUrl . '</p>';
            $htmlToReturn .=
                '<script>'
                . ' setTimeout(function() {'
                . '     location.href = "' . $newUrl . '";'
                . ' }, 50);'
                . '</script>';

            return $htmlToReturn;
        }


        $symbolFromGet = filter_input(INPUT_GET, 'symbol');
        $haltAfterThis = filter_input(INPUT_GET, 'halt');
        $dbTableName = 'coin_price_segments';
        $dateFromFormat = 'U.u';
        $dateToFormat = 'Y-m-d H:i:s.v';
        $_timezone = new DateTimeZone('Europe/Athens');
        $html = '';


        //$symbols = array('LINKUSDT', "ETHUSDT"); ///TODO

        $symbolDbRows = $_DB->select('SELECT DISTINCT(cp.coin_symbol) FROM coin_prices cp');

        /// Delete the old ones
        $before = new DateTime();
        $before->setTimezone($_timezone);
        $before->sub(new DateInterval('P15D'));

        $doneSymbols = array();
        $remainingSymbols = array();
        $currentSymbolProccessed = false;
        $nextSymbol = null;

        foreach ($symbolDbRows as $symbolRow) :
            $symbol = $symbolRow['coin_symbol'];
            $isThisSymbol = $symbol == $symbolFromGet;

            /// If empty symbol was given redirect to the first one
            if (empty($symbolFromGet)) {
                $remainingSymbols[] = $symbol;
                if (empty($nextSymbol)) {
                    $nextSymbol = $symbol;
                }
                continue;
            }

            if (!$isThisSymbol && !$currentSymbolProccessed) {
                $doneSymbols[] = $symbol;
                continue;
            }

            /// If the symbol is proccessed then decide the next one and add to remaining
            if ($currentSymbolProccessed) {
                $remainingSymbols[] = $symbol;
                if (empty($nextSymbol)) {
                    $nextSymbol = $symbol;
                }
                continue;
            }

            $insertSets = array();

            //Periods: 1m,3m,5m,15m,30m,1h,2h,4h,6h,8h,12h,1d,3d,1w,1M
            $ticks = $_BINANCE_API->candlesticks($symbol, "5m");

            if (0) {
                echo '<pre>';
                var_dump($ticks);
                echo '</pre>';
            }

            $totalTicks = count($ticks);
            $index = 0;

            foreach ($ticks as $tickTime => $tick) :
                $index++;
                /*
[1635516000000]=>
  array(13) {
    ["open"]=>
    string(10) "0.00827100"
    ["high"]=>
    string(10) "0.00832900"
    ["low"]=>
    string(10) "0.00827100"
    ["close"]=>
    string(10) "0.00829300"
    ["volume"]=>
    string(11) "35.99663131"
    ["openTime"]=>
    int(1635516000000)
    ["closeTime"]=>
    int(1635516899999)
    ["assetVolume"]=>
    string(13) "4334.45700000"
    ["baseVolume"]=>
    string(11) "35.99663131"
    ["trades"]=>
    int(3315)
    ["assetBuyVolume"]=>
    string(13) "2557.97300000"
    ["takerBuyVolume"]=>
    string(11) "21.24492037"
    ["ignored"]=>
    string(1) "0"
  }
  */

                try {

                    $_od = $tick['openTime'] / 1000 . '.000';
                    $_oT = DateTime::createFromFormat($dateFromFormat, $_od);
                    $_oT->setTimezone($_timezone);

                    $_set = [
                        'coin_symbol' => $symbol,
                        'low' => floatval($tick['low']),
                        'high' => floatval($tick['high']),
                        'open' => floatval($tick['open']),
                        'close' => floatval($tick['close']),
                        'open_time' => $_oT->format($dateToFormat),
                    ];

                    $_isLast = $index === $totalTicks;

                    if ($_isLast) :
                        ///Remove the last one so to update
                        $_DB->delete(
                            $dbTableName,
                            [
                                // where
                                'coin_symbol' => $_set['coin_symbol'],
                                'open_time' => $_set['open_time'],
                            ]
                        );
                    endif;

                    $_DB->insert($dbTableName, $_set);
                    $insertSets[] = 1;

                    $_DB->exec(
                        "DELETE FROM $dbTableName WHERE open_time < ?",
                        array($before->format($dateToFormat))
                    );
                    $currentSymbolProccessed = true;
                } catch (Exception $e) {
                    $a = 'one_per_segment';

                    if (strpos($e->getMessage(), $a) !== false) {
                        //echo 'Skipped inserting an old value again<br/>';
                    } else {
                        //var_dump($e);
                        var_dump($e->getMessage());
                        var_dump($_set);
                    }
                }
            endforeach;
            $html .= '<h5>Inserted ' . (count($insertSets)) . ' coin prices for ' . $symbol . '</h5>';
        endforeach;
    } catch (Exception $e) {
        $html .= $e->getMessage();
    }

    deleteLockFile();

    if (empty($nextSymbol)) {
        $html .= '<h2>Finished processing of symbols</h2>';
    } else {
        $html .= redirectToSymbol($nextSymbol);
    }

    ?>
    <script>
        <?php if (!empty($symbolFromGet)) : ?>
            // Update the title of the document
            const head = document.head;
            const title = head.querySelector('title');
            title.textContent = '<?php echo $symbolFromGet; ?> . Le Winner';
        <?php endif ?>
    </script>

    <div class="results-container">
        <div class="">
            <h4>ToDo <?php echo count($remainingSymbols) ?></h4>
            <pre><?php echo json_encode($remainingSymbols, JSON_PRETTY_PRINT); ?></pre>
        </div>
        <div class="">
            <h4>Current</h4>
            <pre><?php echo (empty($symbolFromGet) ? '-' : $symbolFromGet); ?></pre>
            <div><?php echo $html; ?></div>
        </div>
        <div class="">
            <h4>Done <?php echo count($doneSymbols) ?></h4>
            <pre><?php echo json_encode($doneSymbols, JSON_PRETTY_PRINT); ?></pre>
        </div>
    </div>

    <style type="text/css">
        * {
            font-family: monospace;
        }

        .results-container {
            display: flex;
            flex-direction: row;
            justify-content: space-around;

        }

        .results-container>div {
            width: 100%;
            border: 1px solid #606060;
            margin: 10px;
            padding: 10px;
            border-radius: 3px;
            background-color: #fbfbfa;
        }
    </style>
</body>

</html>