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

    static function toFloat($num, $precision = 7)
    {
        return round((float)filter_var($num, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION), $precision);
    }

    static function deleteLockFileIfOldEnough()
    {
        $CONFIG = Config::getInstance();

        $fileName = $CONFIG->lockFile();
        if (!file_exists($fileName)) {
            return;
        }
        
        $creationTime = filectime($fileName);
        $currentTime = time();

        if ($currentTime - $creationTime > 120) { // 120 seconds = 2 minutes
            Utils::deleteLockFile();
        }
    }

    static function ensureOneProccess()
    {
        Utils::deleteLockFileIfOldEnough();

        $CONFIG = Config::getInstance();
        $fileName = $CONFIG->lockFile();

        if (file_exists($fileName)) {
            echo 'Halted. Another process is running!<br />';
            exit;
        }

        file_put_contents($fileName, date('Y-m-d HH:mm:ii:ss'));
    }

    static function deleteLockFile()
    {
        $CONFIG = Config::getInstance();
        $fileName = $CONFIG->lockFile();

        if (file_exists($fileName)) {
            unlink($fileName);
        }
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

            <link rel="apple-touch-icon" sizes="180x180" href="/public/favicon/apple-touch-icon.png">
            <link rel="icon" type="image/png" sizes="32x32" href="/public/favicon/favicon-32x32.png">
            <link rel="icon" type="image/png" sizes="16x16" href="/public/favicon/favicon-16x16.png">
            <link rel="manifest" href="/public/favicon/site.webmanifest">
            
            <?php if (in_array(Utils::PKG_BOOTSTRAP, $INCLUDETHESE)) : ?>
                <link href="/vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
            <?php endif; ?>
            <?php if (in_array(Utils::PKG_FONTAWESOME, $INCLUDETHESE)) : ?>
                <link href="/vendor/components/font-awesome/css/all.min.css" rel="stylesheet">
            <?php endif; ?>

            <script type="text/javascript" src="/vendor/components/jquery/jquery.min.js"></script>
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
                <?php if (in_array(Utils::PKG_BOOTSTRAP, $INCLUDETHESE)) : ?>
                    <script type="text/javascript" src="/vendor/twbs/dist/js/bootstrap.min.js"></script>
                <?php endif; ?>

                <?php if (in_array(Utils::PKG_CHARTS, $INCLUDETHESE)) : ?>
                    <script type="text/javascript" src="/vendor/twbs/dist/js/bootstrap.min.js"></script>
                <?php endif; ?>
            </diV>
        </body>

        </html>
<?php
    } /// Function htmlFooter
}
