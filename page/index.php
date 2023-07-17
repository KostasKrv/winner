<?php

namespace WinnerApp;

require_once(__DIR__ . '/../lib/config.php');

Utils::htmlHeader($title = "Home");

$links = array(
    /*array(
        'title' => 'Fetch orders',
        'desciption' => 'Some placeholder content in a paragraph.',
        'link' => 'page/order-fetcher',
    ),*/
    array(
        'title' => 'Fetch prices',
        'desciption' => 'Populate the current coin prices into the db.',
        'link' => 'page/price-fetcher',
        'icon' => 'fa-solid fa-cloud-arrow-down',
        'iconColor' => 'text-success-emphasis'
    ),
    array(
        'title' => 'Insert segments',
        'desciption' => 'Populate the segments per hour into the db.',
        'link' => 'page/insert-segments',
        'icon' => 'fa-solid fa-chart-simple',
        'iconColor' => 'text-success-emphasis'
    ),
    array(
        'title' => 'Show current status',
        'desciption' => 'Populate the current coin prices into the db.',
        'link' => 'page/prices-from-db',
        'icon' => 'fa-solid fa-chart-line',
        'iconColor' => 'text-primary'
    ),    
);
?>
<div class="page-price-fetcher">

    <div class="d-flex flex-column flex-md-row p-4 gap-4 py-md-5 align-items-center justify-content-center">
        <div class="list-group">
            <?php foreach ($links as $linkArr) : ?>
                <a href="<?php echo $linkArr['link'] ?>" target="_blank" class="list-group-item list-group-item-action d-flex gap-3 py-3" aria-current="true">
                    <i class="<?php echo $linkArr['icon'] ?> <?php echo $linkArr['iconColor'] ?> fa-2x"></i>
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