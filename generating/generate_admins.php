<?php


require __DIR__ . '/vendor/autoload.php';

$mysql = new mysqli('localhost', 'root', 'pwsio', 'EzCharge');
$mysql->set_charset("utf8");

function insert($table, $params)
{

    global $mysql;
    $f = implode(",", array_keys($params));
    $p = implode('","', $params);
    $q = "INSERT INTO $table ($f) VALUES (\"$p\")";
    var_dump($q);
    var_dump($mysql);
    $mysql->query($q);
    return $mysql->insert_id;

}

use Faker\Provider\fr_FR\Address;

$faker = Faker\Factory::create('fr_FR');

for ($i = 0; $i < 10; $i++) {
    $user = array();
    $user['lastname'] = $faker->lastName;
    $user['firstname'] = $faker->firstName;
    $user['password'] = sha1($faker->numberBetween(1000, 9999));
    $user['login'] = $faker->userName;
    $user['is_admin'] = 1;
    $user['connection_number'] = 2;
    $user_id = insert("users", $user);
}
