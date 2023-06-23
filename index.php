<?php

namespace WinnerApp;

require_once(__DIR__ . '/lib/config.php');

?>

<?php Utils::htmlHeader($title = "Home"); ?>

<?php $links = array(
    array(
        'title' => 'Fetch orders',
        'desciption' => 'Some placeholder content in a paragraph.',
        'link' => 'actions/order-fetcher',
    ),
    array(
        'title' => 'Fetch prices',
        'desciption' => 'Populate the current coin prices into the db.',
        'link' => 'actions/price-fetcher',
    ),
    array(
        'title' => 'Show prices with statuses',
        'desciption' => 'Populate the current coin prices into the db.',
        'link' => 'actions/prices-from-db',
    ),
);
?>

<div class="page-price-fetcher">

    <div class="d-flex flex-column flex-md-row p-4 gap-4 py-md-5 align-items-center justify-content-center">
        <div class="list-group">
            <?php foreach ($links as $linkArr) : ?>
                <a href="<?php echo $linkArr['link'] ?>" target="_blank" class="list-group-item list-group-item-action d-flex gap-3 py-3" aria-current="true">
                    <img src="https://github.com/twbs.png" alt="twbs" width="32" height="32" class="rounded-circle flex-shrink-0">
                    <div class="d-flex gap-2 w-100 justify-content-between">
                        <div>
                            <h6 class="mb-0"><?php echo $linkArr['title'] ?></h6>
                            <p class="mb-0 opacity-75"><?php echo $linkArr['desciption'] ?></p>
                        </div>
                        <small class="opacity-50 text-nowrap"></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php Utils::htmlFooter(); ?>