<?php
require_once '../core/Controller.php';
require_once '../controllers/DashboardController.php';

$controller = new Controllers\DashboardController();
$controller->index();
?>