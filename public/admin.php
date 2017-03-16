<?php

/***************************************************************************************************************
 *                                                                                                             *
 *                                                                                                             *
 *                                           >   ADMIN ROUTES                                                  *
 *                                                                                                             *
 *                                                                                                             *
 ***************************************************************************************************************/

use Utils\Utils as Utils;

// Define path for the admin template
const USER_PATH = 'admin';

/*********************************
 *             HOME              *
 *********************************/
$app->get('/home', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');
    // Redirect to users table (admin's 'home' is the users list)
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/users");
    exit();
})->setName('home');

/*********************************
 *             NEW_USER          *
 *********************************/
$app->get('/new_user', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/new_user.html', [
        'infos' => $_SESSION['infos'],
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('new_user');

/*********************************
 *             ADD_USER      *
 *********************************/
$app->get('/add_user', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');
    // add the user with url params and retrieve inserted id in u_id
    $u_id = Utils::addUser($request->getParams());

    // redirect to the created contract
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/user/" . $u_id);
    exit();
})->setName('add_user');

/*********************************
 *             CONTRACTS         *
 *********************************/
$app->get('/contracts', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/contracts.html', [
        'infos' => $_SESSION['infos'],
        'contracts' => Utils::allContracts(),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('contracts');

/*********************************
 *             NEW_CONTRACT      *
 *********************************/
$app->get('/new_contract/{id}/{lastname}/{firstname}', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/new_contract.html', [
        'infos' => $_SESSION['infos'],
        'id' => $args['id'],
        'lastname' => $args['lastname'],
        'firstname' => $args['firstname'],
        'contracts' => Utils::allContracts(),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('new_contract');

/*********************************
 *             ADD_CONTRACT      *
 *********************************/
$app->get('/add_contract', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');
    // add the contract with url params and retrieve id in c_id
    $c_id = Utils::addContract($request->getParams());

    // redirect to the created contract
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/contract/" . $c_id);
    exit();
})->setName('add_contract');

/*********************************
 *         CONTRACT_SAVE         *
 *********************************/
$app->get('/contract_save', function ($request, $response, $args) {
    $params = $request->getParams();
    // save the contract with url params
    Utils::saveContract($params);

    // redirect to the edited contract's edit page
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/edit_contract/" . $params['id']);
    exit();
})->setName('contract_save');

/*********************************
 *          EDIT_CONTRACT        *
 *********************************/
$app->get('/edit_contract/{id}', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');

    // Check if contract has been updated, then reset the var
    $updated = false;
    if(isset($_SESSION['contract_updated'])){
        if($_SESSION['contract_updated']){
            $updated = true;
            $_SESSION['contract_updated'] = false;
        }
    }

    // retrieve contract and user infos to inject in the template
    $contract = Utils::contractInfos($args['id']);
    $user = Utils::userInfo($contract['user_id']);

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/edit_contract.html', [
        'infos' => $_SESSION['infos'],
        'contract' => $contract,
        'contract_updated' => $updated,
        'user' => $user,
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('edit_contract');

/*********************************
 *         USER_CONTRACTS        *
 *********************************/
$app->get('/user_contracts/{id}', function ($request, $response, $args) {
    Utils::checkAuth();

    // retrieve user contracts
    $contracts = Utils::contracts($args['id']);

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/user_contracts.html', [
        'user' => Utils::userInfo($args['id']),
        'infos' => $_SESSION['infos'],
        'contracts' => $contracts,
        'hasSubscription' => Utils::closestContractDate($contracts),
        'hasPrepaid' => Utils::hasPrepaidContract($contracts),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('user_contracts');

/*********************************
 *             STATIONS          *
 *********************************/
$app->get('/stations/{id}', function ($request, $response, $args) {
    // return JSON for ajax call in charge table (for station modal)
    return $response->getBody()->write(Utils::stationsOfContract($args['id']));
})->setName('contracts');

/*********************************
 *             DELETE_USER       *
 *********************************/
$app->get('/delete_user/{id}', function ($request, $response, $args) {
    // delete wanted user
    Utils::deleteUser($args['id']);

    // redirect to the route the call came from (passed in url param ?from)
    header("Location: " . $request->getParams()['from']);
    exit();
})->setName('delete_user');

/*********************************
 *             DELETE_CONTRACT       *
 *********************************/
$app->get('/delete_contract/{id}', function ($request, $response, $args) {
    // delete wanted contact
    Utils::deleteContract($args['id']);

    // redirect to the route the call came from (passed in url param ?from)
    header("Location: " . $request->getParams()['from']);
    exit();
})->setName('delete_user');

/*********************************
 *             CONTRACT          *
 *********************************/
$app->get('/contract/{id}', function ($request, $response, $args) {
    Utils::checkAuthFor('admin');

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/contract.html', [
        'user' => Utils::infosOfContract($args['id']),
        'infos' => $_SESSION['infos'],
        'contract_id' => $args['id'],
        'contract_type' => Utils::contractInfos($args['id'])['type_id'] == 1 ? 'du contrat prepayé' : 'de l\'abonnement',
        'plate' => Utils::contractInfos($args['id'])['plate'],
        'terminals' => Utils::terminals($args['id']),
        'terminals_chart' => Utils::terminalsChartData($args['id']),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('contract');

/*********************************
 *             USERS             *
 *********************************/
$app->get('/users', function ($request, $response, $args) {

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/users.html', [
        'infos' => $_SESSION['infos'],
        'users' => Utils::allUsers(),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('users');

/*********************************
 *             USER              *
 *********************************/
$app->get('/user/{id}', function ($request, $response, $args) {
    // Check if user has been updated, then reset the var
    $updated = false;
    if(isset($_SESSION['user_updated'])){
        if($_SESSION['user_updated']){
            $updated = true;
            $_SESSION['user_updated'] = false;
        }
    }

    // Inject useful variables into twig template
    return $this->view->render($response, USER_PATH . '/user.html', [
        'infos' => $_SESSION['infos'],
        'user_updated' => $updated,
        'user' => Utils::userInfo($args['id']),
        'route' => $request->getAttribute('route')->getName()
    ]);
})->setName('user');

/*********************************
 *             USER_SAVE         *
 *********************************/
$app->get('/user_save', function ($request, $response, $args) {
    $params = $request->getParams();
    // save the edited user's infos
    Utils::saveUser($params);

    // then redirect back to the edited user's page
    header("Location: " . $_SERVER['SCRIPT_NAME'] . "/user/" . $params['id']);
    exit();
})->setName('user_save');
