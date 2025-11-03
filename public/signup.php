<?php
require_once '../core/Controller.php';
require_once '../controllers/AuthController.php';

$controller = new Controllers\AuthController();
$controller->signup();
?>