<?php

/***************************************************************************************************************
 *                                                                                                             *
 *                                                                                                             *
 *                                           >   ROUTES FUNCTIONS                                              *
 *                                                                                                             *
 *                                                                                                             *
 ***************************************************************************************************************/

namespace Utils;

use Faker\Provider\cs_CZ\DateTime;
use RedBeanPHP\Facade as R;

class Utils
{
    /**
     * get all contracts
     * @return array
     */
    public static function allContracts()
    {

        return R::getAll("select u.id as user_id, u.firstname, u.lastname, u.city, u.postal_code, u.login, u.mail, u.phone, c.id as contract_id, c.created_at, c.type_id, c.plate from contracts c, users u where c.user_id = u.id order by u.lastname, u.firstname");
    }

    /**
     * get all users
     * @return array
     */
    public static function allUsers()
    {
        return R::getAll("select * from users");
    }

    /**
     * increment connection count
     * @param $id
     */
    public static function incrementConnection($id)
    {
        R::exec("update users set connection_number = connection_number + 1 where id = $id");
    }

    /**
     * reset hints
     */
    public static function reactivateHints()
    {
        $_SESSION['show_hints'] = [];
        $_SESSION['infos']['connection_number'] = 0;
        R::exec("update users set connection_number = 1 where id = " . $_SESSION['infos']['id']);
    }

    /**
     * add a contract
     * @param array $params contract infos
     * @return int $id inserted id
     */
    public static function addContract($params)
    {
        R::exec("insert into contracts values (null, '" . $params['type'] . "','" . $params['start'] . "','" . $params['end'] . "','" . $params['user_id'] . "','" . $params['kwh'] . "','" . $params['plate'] . "')");
        return R::getInsertID();
    }

    /**
     * add an user
     * @param $params
     * @return mixed
     */
    public static function addUser($params)
    {
        R::exec("insert into users values (null, '" . $params['lastname'] . "','" . $params['firstname'] . "','" . $params['city'] . "','" . $params['postal_code'] . "','" . $params['password'] . "','" . $params['login'] .  "','" . $params['phone'] .  "','" . $params['mail'] . "', 0)");
        return R::getInsertID();
    }

    /**
     * get infos of a user
     * @param int $id user's id
     * @return mixed
     */
    public static function userInfo($id)
    {
        return R::getAll("select * from users where id = ?", [$id])[0];
    }

    /**
     * save user
     * @param array $user user to save
     */
    public static function saveUser($user)
    {
        $_SESSION['user_updated'] = 1;
        R::exec("update users
        set lastname = ?, firstname = ?, city = ?, postal_code = ?, phone = ?, mail = ?
        where id = ?", [$user['lastname'], $user['firstname'], $user['city'], $user['postal_code'], $user['phone'], $user['mail'], $user['id']]);
    }

    /**
     * save contract
     * @param array $contract contract to save
     */
    public static function saveContract($contract)
    {
        $_SESSION['contract_updated'] = 1;
        R::exec("update contracts
        set start = ?, end = ?, remaining = ?, plate = ?
        where id = ?", [$contract['start'], $contract['end'], $contract['remaining'], $contract['plate'], $contract['id']]);
    }

    /**
     * get user's infos of the contract
     * @param $id
     * @return array $contractInfo user info of the contract
     */
    public static function infosOfContract($id)
    {
        return R::getAll("select u.id as id, u.lastname, u.firstname, c.start, c.end, c.remaining, c.id as contract_id, c.plate, c.type_id, c.created_at from contracts c, users u where c.user_id = u.id and c.id = ?", [$id])[0];
    }

    /**
     * delete user and all its related data
     * @param $id
     */
    public static function deleteUser($id)
    {
        R::getAll("delete from users where users.id = ?", [$id]);
        $contracts = R::getAll("select id from contracts where contracts.user_id = ?", [$id]);
        foreach ($contracts as $c => $v) {
            R::getAll("delete from user_terminal where contract_id = ?", [$v['id']]);
        }
        R::getAll("delete from contracts where contracts.user_id = ?", [$id]);
        R::getAll("delete from users where users.id = ?", [$id]);
    }

    /**
     * delete a contract and its related data
     * @param $id
     */
    public static function deleteContract($id)
    {
        R::getAll("delete from contracts where contracts.id = ?", [$id]);
        R::getAll("delete from user_terminal where contract_id = ?", [$id]);
    }

    /**
     * get JSON of the stations of a contract
     * @param $id
     * @return JSON
     */
    public static function stationsOfContract($id)
    {
        $stations = R::getAll("select station.id as id, terminal.power as power, station.name as name, station.lat as lat, station.lon as lon from station, terminal where station.id = terminal.station_id and station.id IN (select station.id from user_terminal, terminal, station where terminal_id = terminal.id and terminal.station_id = station.id and contract_id = ?)", [$id]);

        $res = array();

        for ($i = 0; $i < sizeof($stations); $i++) {
            if (!array_key_exists($stations[$i]['id'], $res)) {
                $res[$stations[$i]['id']]['name'] = $stations[$i]['name'];
                $res[$stations[$i]['id']]['lat'] = $stations[$i]['lat'];
                $res[$stations[$i]['id']]['lon'] = $stations[$i]['lon'];
                $res[$stations[$i]['id']]['power_types'] = [
                    "3" => 0,
                    "24" => 0,
                    "50" => 0
                ];
                $res[$stations[$i]['id']]['power_types'][$stations[$i]['power']]++;
            } else {
                $res[$stations[$i]['id']]['power_types'][$stations[$i]['power']]++;
            }
        }

        return json_encode($res);
    }

    /**
     * get all contracts or contracts of a user
     * @param null $id user id
     * @return array
     */
    public static function contracts($id = null)
    {
        if (is_null($id)) {
            $id = $_SESSION['infos']['id'];
        }
        return R::getAll("select * from contracts where user_id = ?", [$id]);
    }

    /**
     * get contract's infos
     * @param $id contract's id
     * @return mixed
     */
    public static function contractInfos($id)
    {
        return R::getAll("select * from contracts where id = ?", [$id])[0];
    }

    /**
     * get the terminals of a contract
     * @param $id contract's id
     * @return array
     */
    public static function terminals($id)
    {
        return R::getAll("select * from user_terminal, terminal, station where terminal_id = terminal.id and terminal.station_id = station.id and contract_id = ? order by user_terminal.date desc", [$id]);
    }

    /**
     * get formatted terminals data of a contract for Morris chart
     * @param $id contract's id
     * @return array
     */
    public static function terminalsChartData($id)
    {
        $terminals = self::terminals($id);
        $res = array();
        for ($i = 0; $i < sizeof($terminals); $i++) {
            $res[$i]['date'] = $terminals[$i]['date'] . " " . $terminals[$i]['time'];
            $res[$i]['total_power'] = $terminals[$i]['total_power'];
            $res[$i]['id'] = $terminals[$i]['id'];
        }

        return $res;
    }

    /**
     * get the subscription contract with the closest date
     * @param $contracts
     * @return bool|mixed
     */
    public static function closestContractDate($contracts)
    {
        if (!self::hasPeriodContract($contracts)) {
            return false;
        }

        $now = new \DateTime();
        $now = $now->getTimestamp();
        $closest = [
            'diff' => INF,
            'contract' => 0
        ];
        for ($i = 0; $i < sizeof($contracts); $i++) {
            if ($contracts[$i]['type_id'] == 2) {
                $date = new \DateTime($contracts[$i]['end']);
                $diff = $date->getTimestamp() - $now;
                if ($diff < $closest['diff'] && $diff > 0) {
                    $closest['diff'] = $diff;
                    $closest['contract'] = $contracts[$i];
                }
            }
        }
        return $closest['contract'];
    }

    /**
     * get the prepaid contract with the least Kw/H remaining
     * @param $contracts
     * @return bool|mixed
     */
    public static function minRemaining($contracts)
    {
        if (!self::hasPrepaidContract($contracts)) {
            return false;
        }

        $min = array();
        $min['remaining'] = INF;

        for ($i = 0; $i < sizeof($contracts); $i++) {
            if ($contracts[$i]['type_id'] == 1) {
                if ($contracts[$i]['remaining'] < $min['remaining']) {
                    $min['remaining'] = $contracts[$i]['remaining'];
                    $min['contract'] = $contracts[$i];
                }
            }
        }

        return $min['contract'];
    }

    /**
     * find if user has subscription contracts
     * @param $contracts
     * @return bool
     */
    public static function hasPeriodContract($contracts)
    {
        for ($i = 0; $i < sizeof($contracts); $i++) {
            if ($contracts[$i]['type_id'] == 2) {
                return true;
            }
        }
        return false;
    }

    /**
     * find if user has prepaid contracts
     * @param $contracts
     * @return bool
     */
    public static function hasPrepaidContract($contracts)
    {
        for ($i = 0; $i < sizeof($contracts); $i++) {
            if ($contracts[$i]['type_id'] == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * return if user has hints
     * @param $request
     * @return bool
     */
    public static function areHintsDisplayed($request)
    {
        $showHints = !isset($_SESSION['show_hints'][$request->getAttribute('route')->getName()]) && $_SESSION['infos']['connection_number'] == 0;
        if(!isset($_SESSION['show_hints'][$request->getAttribute('route')->getName()])){
            $_SESSION['show_hints'][$request->getAttribute('route')->getName()] = 0;
        }
        return $showHints;
    }

    /**
     * handle user authentification
     * @param $params
     */
    public static function auth($params)
    {
        if (isset($params['login']) && isset($params['password'])) {
            if ($params['login'] == '' || $params['password'] == '') {
                header("Location: " . $_SERVER['SCRIPT_NAME'] . "/login?err=1");
                die();
            }

            $user = R::getAll("select * from users where login = ?", [$params['login']]);

            if (empty($user)) {
                header("Location: " . $_SERVER['SCRIPT_NAME'] . "/login?err=1");
            }

            if (sha1($params['password']) == $user[0]['password']) {
                $_SESSION['infos'] = $user[0];
                if (!$user[0]['is_admin']) {
                    $_SESSION['contracts'] = self::contracts();
                    $_SESSION['show_hints'] = [];
                    self::incrementConnection($user[0]['id']);
                }
                return;
            }

            die();
        }
        header("Location: " . $_SERVER['SCRIPT_NAME'] . "/login");
        die();
    }

    /**
     * check is current user is logged
     */
    public static function checkAuth()
    {
        if (!isset($_SESSION['infos']['login'])) {
            // If current user isn't logged then redirect to login page
            header("Location: " . $_SERVER['SCRIPT_NAME'] . "/login");
            exit();
        }
    }

    /**
     * check if current user has the asked rights
     * @param $rights
     */
    public static function checkAuthFor($rights)
    {
        self::checkAuth();

        // If current page requires admin account and current account is only user then redirect to home
        if ($rights == "admin" && !$_SESSION['infos']['is_admin']) {
            // IT SHOULD NEVER EXEC THIS PART (because of route inclusion)
            header("Location: " . $_SERVER['SCRIPT_NAME'] . "/home");
            exit();
        }
    }

}