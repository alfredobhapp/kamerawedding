<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/src/Response.php';
require_once __DIR__ . '/../app/src/Db.php';
require_once __DIR__ . '/../app/src/Router.php';
require_once __DIR__ . '/../app/src/Upload.php';

$config = require __DIR__ . '/../app/config.php';
$db = Db::connect($config['db']);
$router = new Router($db, $config);

$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$router->dispatch($method, $route);
