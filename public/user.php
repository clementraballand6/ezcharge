<?php

/***************************************************************************************************************
 *                                                                                                             *
 *                                                                                                             *
 *                                           >   USER ROUTES                                                   *
 *                                                                                                             *
 *                                                                                                             *
 ***************************************************************************************************************/

use Utils\Utils as Utils;

// Define path for the user template
const USER_PATH = 'user';

/*********************************
 *             HOME              *
 *********************************/
$app->get('/home', function ($request, $response, $args) {
    Utils::checkAuth();

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/index.html', [
        'infos' => $_SESSION['infos'],
        'contracts' => $_SESSION['contracts'],
        'show_hints' => Utils::areHintsDisplayed($request),
        'route' => $request->getAttribute('route')->getName(),
        'closestContract' => Utils::closestContractDate($_SESSION['contracts']),
        'minRemaining' => Utils::minRemaining($_SESSION['contracts'])
    ]);
})->setName('home');

/*********************************
 *             CONTRACTS         *
 *********************************/
$app->get('/contracts', function ($request, $response, $args) {
    Utils::checkAuth();

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/contracts.html', [
        'infos' => $_SESSION['infos'],
        'contracts' => $_SESSION['contracts'],
        'show_hints' => Utils::areHintsDisplayed($request),
        'hasSubscription' => Utils::closestContractDate($_SESSION['contracts']),
        'hasPrepaid' => Utils::hasPrepaidContract($_SESSION['contracts']),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('contracts');

/*********************************
 *             STATIONS          *
 *********************************/
$app->get('/stations/{id}', function ($request, $response, $args) {
    return $response->getBody()->write(Utils::stationsOfContract($args['id']));
})->setName('contracts');

/*********************************
 *        REACTIVATE HINTS       *
 *********************************/
$app->get('/reactivate_hints', function ($request, $response, $args) {
    Utils::reactivateHints();

    header("Location: " . $request->getParams()['from']);
    exit();
})->setName('reactivate_hints');

/*********************************
 *             CONTRACT          *
 *********************************/
$app->get('/contract/{id}', function ($request, $response, $args) {
    Utils::checkAuth();

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/contract.html', [
        'infos' => $_SESSION['infos'],
        'contract_id' => $args['id'],
        'show_hints' => Utils::areHintsDisplayed($request),
        'contract_type' => Utils::contractInfos($args['id'])['type_id'] == 1 ? 'le contrat prepayé' : 'l\'abonnement',
        'plate' => Utils::contractInfos($args['id'])['plate'],
        'terminals' => Utils::terminals($args['id']),
        'terminals_chart' => Utils::terminalsChartData($args['id']),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('contract');

/*********************************
 *             REFRESH           *
 *********************************/
$app->get('/refresh', function ($request, $response, $args) {
    $params = [
        'login' => $_SESSION['infos']['login'],
        'password' => $_SESSION['infos']['password']
    ];
    // Re auth user to refresh contracts (see Utils::auth())
    Utils::auth($params);
    // Redirect back to contract
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/contracts");
    exit();
})->setName('refresh');

/*********************************
 *             INFOS             *
 *********************************/
$app->get('/infos', function ($request, $response, $args) {

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/infos.html', [
        'infos' => $_SESSION['infos'],
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('infos');
