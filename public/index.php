<?php

/***************************************************************************************************************
 *                                                                                                             *
 *                                                                                                             *
 *                                           >   CORE SCRIPT                                                   *
 *                                                                                                             *
 *                                                                                                             *
 ***************************************************************************************************************/

session_start();

// Deps
require_once '../vendor/autoload.php';
require '../utils/Utils.php';

// Uses
use RedBeanPHP\Facade as R;
use Utils\Utils as Utils;

// include db config
$db_config = include '../config.php';

// Db connection
R::setup("mysql:host=localhost;dbname=" . $db_config['dbname'], $db_config['user'], $db_config['password']);

// Slim config setup
$app = new \Slim\App([
    'settings' => [
        'displayErrorDetails' => true,
        // Fix a dirtyyyyy slim bug (don't know why, stackoverflow's solution)
        'addContentLengthHeader' => false
    ]
]);

// Get container
$container = $app->getContainer();

// Register twig's component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('html');

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

/************************************************
 *                 COMMON ROUTES                *
 ************************************************/

/*********************************
 * LOGIN                         *
 * render login page             *
 *********************************/
$app->get('/login', function ($request, $response, $args) {
    // if user is already logged, then redirect to home
    if(isset($_SESSION['infos'])){
        header("Location: " . $_SERVER['SCRIPT_NAME'] . "/home");
        exit();
    }

    // render view with useful variables
    // if user fail to login, Utils::auth() method redirect to login?err=1, if err is set, template shows error message
    //          because if 'err' injected var is true, template shows the warning msg.
    return $this->view->render($response, 'login.html', [
        'err' => isset($request->getParams()['err'])
    ]);
})->setName('login');

/*********************************
 * LOG                           *
 * try to log the user           *
 *********************************/
$app->post('/log', function ($request, $response, $args) {
    $params = $request->getParams();
    // handle user auth
    Utils::auth($params);

    // if script can reach this code, then nothing bad happened, so redirect to home
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/home");
    exit();
})->setName('log');

/*********************************
 * LOGOUT                        *
 * log out the user              *
 *********************************/
$app->get('/logout', function ($request, $response, $args) {
    session_destroy();
    // redirect to login page after destroying session
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/login");
    exit();
})->setName('logout');

$app->get('/', function ($request, $response, $args) {
    // redirect to home route
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/home");
    exit();
})->setName('logout');

// Include correct routes for the connected user => admin or 'normal' user (customer)
if (isset($_SESSION['infos'])) {
    if ($_SESSION['infos']['is_admin']) {
        include 'admin.php';
    } else {
        include 'user.php';
    }
}

// Finally run slim
$app->run();
