<?php

namespace WinnerApp;

require_once(__DIR__ . '/../lib/config.php');

$results = Utils::fetchAndInsertPricesIntoDb();
?>

<?php Utils::htmlHeader($title = "Price fetch"); ?>
<div class="container">
    <main>
        <div class="page-price-fetcher">
            <a href="#inserted-section"><span class="badge bg-secondary">Inserted <?php echo ($totalInserted = count($results['insertedArray'])) ?></span></a>
            <a href="#skipped-section"><span class="badge bg-secondary">Skipped <?php echo (count($results['apiPricesArray']) - $totalInserted) ?></span></a>
            <a href="#error-section"><span class="badge bg-secondary">Error <?php echo count($results['errorArray']) ?></span></a>
            <p>
                StartOfApi: <?php echo ($results['startOfApiDateTime'])->format('Y/m/d H:i:s.v'); ?>
                <br />
                EndOfApi: <?php echo ($results['endOfApiDateTime'])->format('Y/m/d H:i:s.v'); ?>
            </p>

            <?php if (!empty($results['mainExceptionString'])) : ?>
                <h5>MainException <?php echo $results['mainExceptionString'] ?></h5>
            <?php endif; ?>

            <h4 id="error-section">Errorneous prices</h4>
            <pre class="fs-6 text-bg-dark p-3"><?php var_dump($results['errorArray']); ?></pre>

            <h4 id="inserted-section">Inserted prices</h4>
            <pre class="fs-6 text-bg-dark p-3"><?php var_dump($results['insertedArray']); ?></pre>

            <h4 id="skipped-section">Skipped prices</h4>
            <pre class="fs-6 text-bg-dark p-3"><?php var_dump($results['skippedArray']); ?></pre>

            <script>
                setTimeout(function() {
                    location.reload();
                }, 15 * 1000);
            </script>
        </div>
    </main>
</div>
<?php Utils::htmlFooter(); ?>