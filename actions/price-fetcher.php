<?php

/**
 * This script should fetch all prices available from binance
 * and then store them in the db. 
 * 
 */

namespace WinnerApp;

require_once __DIR__ . '/../lib/config.php';


?>
<html>

<head>
    <title>Le winner</title>
    <?php Utils::htmlHeader(); ?>
</head>

<body>
    <?php
    $results = Utils::fetchAndInsertPricesIntoDb();

    ?>
    <h3><a href="#inserted-section">Inserted <?php echo ($totalInserted = count($results['insertedArray'])) ?></a></h3>
    <h3><a href="#skipped-section">Skipped <?php echo (count($results['apiPricesArray']) - $totalInserted) ?></a></h3>
    <h3><a href="#error-section">Error <?php echo count($results['errorArray']) ?></a></h3>
    <p>
        StartOfApi: <?php echo ($results['startOfApiDateTime'])->format('Y/m/d H:i:s.v'); ?>
        <br />
        EndOfApi: <?php echo ($results['endOfApiDateTime'])->format('Y/m/d H:i:s.v'); ?>
    </p>

    <?php if (!empty($results['mainExceptionString'])) : ?>
        <h5>MainException <?php echo $results['mainExceptionString'] ?></h5>
    <?php endif; ?>    

    <h3 id="error-section">Errorneous prices</h3>
    <pre><?php var_dump($results['errorArray']); ?></pre>

    <h3 id="inserted-section">Inserted prices</h3>
    <pre><?php var_dump($results['insertedArray']); ?></pre>

    <h3 id="skipped-section">Skipped prices</h3>
    <pre><?php var_dump($results['skippedArray']); ?></pre>    

    <script>
        setTimeout(function() {
            location.reload();
        }, 15 * 1000);
    </script>

    <?php Utils::htmlFooter(); ?>

</body>

</html>