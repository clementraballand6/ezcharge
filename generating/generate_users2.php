<?php


require __DIR__ . '/../vendor/autoload.php';

$mysql = new mysqli('localhost', 'root', 'pwsio', 'EzCharge_correct');
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

$terminal_ids = array();
for ($i = 0; $i < 50; $i++) {
    $station = array();
    $station['lat'] = rand(4400000, 4800000) / 100000;
    $station['lon'] = rand(-8000, 560000) / 100000;

    $stationId = insert('station', $station);

    // Une station à 2 à 4 bornes
    $maxTerminals = rand(2, 4);
    for ($v = 0; $v < $maxTerminals; $v++) {

        $type = rand(1, 3);

        if ($type == 1) {
            $power = 3;
        } elseif ($type == 2) {
            $power = 24;
        } else {
            $power = 50;
        }

        $terminal = array();
        $terminal['id_station'] = $stationId;
        $terminal['power'] = $power;

        // On stock les ids
        $terminal_ids[] = insert('terminal', $terminal);
    }
}

$min_terminal_ids = min($terminal_ids);
$max_terminal_ids = max($terminal_ids);

for ($i = 0; $i < 30; $i++) {
    $user = array();
    $user['lastname'] = $faker->lastName;
    $user['firstname'] = $faker->firstName;
    $user['city'] = $faker->city;
    $user['postal_code'] = str_replace(" ", "", Address::postcode());
    $user_id = insert("user", $user);

    // 1/8  des utilisateurs ont 5 à 10 contracts
    if (rand(0, 7) == 7) {
        $max = rand(5, 10);
        for ($v = 0; $v < $max; $v++) {
            $contract = array();
            $contract['id_user'] = $user_id;
            $contract['plate'] = rand(10, 99) . '-' . strtoupper($faker->randomLetter . $faker->randomLetter . $faker->randomLetter) . '-' . rand(10, 99);
            $contract['created_at'] = $faker->dateTimeBetween("-8 months", "-7 months")->format("Y-m-d");
            $c_type = rand(1, 2);
            $contract_id = insert('contract', $contract);

            $contract_type = array();
            if ($c_type == 1) {
                $contract_type['id'] = $contract_id;
                $contract_type['remaining'] = rand(558, 9863);
                insert('prepaid', $contract_type);
            } else {
                $contract_type['id'] = $contract_id;
                $contract_type['start'] = $faker->dateTimeBetween("-6 months", "-3 months")->format("Y-m-d");
                $contract_type['end'] = $faker->dateTimeBetween("-1 months", "+3 months")->format("Y-m-d");
                insert('subscription', $contract_type);
            }
            $max2 = rand(19, 40);
            for ($k = 0; $k < $max2; $k++) {
                var_dump("user_terminal");
                $charge = array();
                $charge['id_contract'] = $contract_id;
                $charge['id_terminal'] = rand($min_terminal_ids, $max_terminal_ids);
                $charge['charged_amount'] = rand(12, 300);
                $date = $faker->dateTimeBetween("-2 months", "now");
                $charge['created_at'] = $date->format("Y-m-d H:i:s");
                insert('charge', $charge);
            }
        }

        // 7/8 des utilisateurs ont 1 contrat
    } else {
        $contract = array();
        $contract['id_user'] = $user_id;
        $contract['plate'] = rand(10, 99) . '-' . strtoupper($faker->randomLetter . $faker->randomLetter . $faker->randomLetter) . '-' . rand(10, 99);
        $contract['created_at'] = $faker->dateTimeBetween("-8 months", "-7 months")->format("Y-m-d");
        $c_type = rand(1, 2);
        $contract_id = insert('contract', $contract);

        $contract_type = array();
        if ($c_type == 1) {
            $contract_type['id'] = $contract_id;
            $contract_type['remaining'] = rand(558, 9863);
            $contract_id = insert('prepaid', $contract_type);
        } else {
            $contract_type['id'] = $contract_id;
            $contract_type['start'] = $faker->dateTimeBetween("-6 months", "-3 months")->format("Y-m-d");
            $contract_type['end'] = $faker->dateTimeBetween("-1 months", "+3 months")->format("Y-m-d");
            $contract_id = insert('subscription', $contract_type);
        }
        $max2 = rand(19, 40);
        for ($k = 0; $k < $max2; $k++) {
            $charge = array();
            $charge['id_contract'] = $contract_id;
            $charge['id_terminal'] = rand($min_terminal_ids, $max_terminal_ids);
            $charge['charged_amount'] = rand(12, 300);
            $date = $faker->dateTimeBetween("-2 months", "now");
            $charge['created_at'] = $date->format("Y-m-d H:i:s");
            insert('charge', $charge);
        }
    }
}
