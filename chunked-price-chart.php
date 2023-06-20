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


class chartCreator {
    static function getSelectChunk($intervalNewest, $intervalOlder) {
        $dynamicName = 'from_' . $intervalOlder . '_to_' . $intervalNewest;

        $templateString = "
            SELECT
            c.coin_symbol AS coin_symbol,
            MAX(c.high) AS max_in_{$dynamicName},
            MIN(c.low) AS min_in_{$dynamicName}
            FROM
                coin_1_hour AS c
            WHERE
                c.openTime < CURRENT_TIMESTAMP () - INTERVAL {$intervalNewest} HOUR
            AND c.openTime >= CURRENT_TIMESTAMP () - INTERVAL {$intervalOlder} HOUR
            GROUP BY
            c.coin_symbol";

            return array(
                $dynamicName,
                $templateString
            );
    }

    static function getPriceChartSqlTemplate($chunks) {
        $selectedFields = array();
        $joins = array();

        $s_joins = $s_selected = '';
        $orderBy;

        foreach($chunks as $chunkArr) {
            $idName = $chunkArr[0];
            $query = $chunkArr[1];

            if (empty($orderBy)) {
                $orderBy = 'buy_price_diff_max_' . $idName;
            }

            $joins[$idName] = "
                LEFT JOIN ({$query}) {$idName} ON (
                    {$idName}.coin_symbol = cp.coin_symbol
                )";

            $selectedFields[$idName] = "
                {$idName}.max_in_{$idName} AS max_in_{$idName},
                {$idName}.min_in_{$idName} AS min_in_{$idName},

                (-1 * FORMAT(
                        (({$idName}.max_in_{$idName} - cp.ask_price) / {$idName}.max_in_{$idName} * 100)
                        , 2
                    )) AS buy_price_diff_max_{$idName},
                (-1 * FORMAT(
                        (({$idName}.max_in_{$idName} - cp.bid_price) / {$idName}.max_in_{$idName} * 100)
                        , 2
                    )) AS sell_price_diff_max_{$idName}";
        }

        if (!empty($selectedFields)) {
            $_tmp = array_reverse($selectedFields);
            $_tmp[] = '';

            $s_selected = implode(',',  array_reverse($_tmp));            
        }
        
        $s_joins = implode('', $joins);

        $tpl = "
            SELECT
                cp.coin_symbol AS coin_symbol,
                cp.bid_price AS bid_price,
                cp.ask_price AS ask_price,
                cp.price_date AS price_date
                {$s_selected}
            FROM
                (SELECT * FROM coin_prices tcp WHERE tcp.bid_price > 0) cp
                {$s_joins}    
            ORDER BY
                {$orderBy}, cp.coin_symbol ASC
        ";

        return $tpl;
    }


}


$ch = array(
    chartCreator::getSelectChunk(0, 4),
    chartCreator::getSelectChunk(4, 12),
    chartCreator::getSelectChunk(12, 24),
    chartCreator::getSelectChunk(24, 36),
    chartCreator::getSelectChunk(36, 48),
    chartCreator::getSelectChunk(36, 48),
);

//var_dump($ch);

$ss = chartCreator::getPriceChartSqlTemplate($ch);
var_dump($ss);
    ?>

    <script>
        /*setTimeout(function() {
            location.reload();
        }, 15 * 1000);*/
    </script>

</body>

</html>
