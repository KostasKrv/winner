<?php

namespace WinnerApp;

require_once(__DIR__ . '/../lib/config.php');

$cl = new SegmentInserter();
$cl->action();
$cl->toJsonResponse();