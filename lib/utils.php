
<?php
function startsWith($haystack, $needle)
{
    return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}
function endsWith($haystack, $needle)
{
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

global $_lockFilename;
$_lockFilename = __DIR__ . '/lock_file.lock';
function ensureOneProccess()
{
    global $_lockFilename;
    if (file_exists($_lockFilename)) {
        echo 'Halted. Another process is running!<br />';
        exit;
    }

    file_put_contents($_lockFilename, date('Y-m-d HH:mm:ii:ss'));
}

function deleteLockFile()
{
    global $_lockFilename;
    if (file_exists($_lockFilename)) {
        unlink($_lockFilename);
    }
}

function shouldProcessSymbol($symbol)
{
    if (!endsWith($symbol, 'USDT')) {
        return false;
    }

    if (endsWith($symbol, 'UPUSDT')) {
        return false;
    }

    if (endsWith($symbol, 'DOWNUSDT')) {
        return false;
    }

    return true;
};

function fetchLastInsertedPrices()
{
    global $_DB;
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

function insertCoinPriceIntoDb($symbol, $bidPriceFloatVal, $askPriceFloatVal, $date)
{
    global $_DB;
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
    } catch (Delight\Db\Throwable\IntegrityConstraintViolationException $e) {
        /// Skipped because same price inside minute                
        return false;
    } catch (Exception $e) {
        return false;
    }

    return true;
}
