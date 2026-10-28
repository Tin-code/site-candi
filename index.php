<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);

require ROOT_PATH . '/vendor/autoload.php';

use App\Core\Env;
use App\Core\Session;
use App\Core\View;
use App\Core\Request;
use App\Core\Router;

// 1. Environnement
Env::load(ROOT_PATH . '/.env');

// 2. Session
Session::start();

// 3. Vue — chemin vers les templates
View::setPath(ROOT_PATH . '/app/Views');

// 4. Requête
$request = new Request();

// 5. Router
$router = new Router();
require ROOT_PATH . '/config/routes.php';

// 6. Dispatch
$router->dispatch($request);
