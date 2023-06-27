<?php

namespace WinnerApp;

require_once(__DIR__ . '/../lib/config.php');

$results = CoinService::findCoinPricesChart();
?>

<?php Utils::htmlHeader($title = "Price fetch"); ?>
<div class="container">
    <main>
        <div class="page-findCoinPricesChart">
            <pre>
                <?php echo json_encode($results, JSON_PRETTY_PRINT); ?>
            </pre>
        </div>
    </main>
</div>
<?php Utils::htmlFooter(); ?>