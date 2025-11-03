<?php
require_once '../core/Autoloader.php';
Core\Autoloader::register();

$controller = new Controllers\VisitorController();
$controller->form();
?>