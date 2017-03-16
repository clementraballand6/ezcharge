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

$terminal_ids = array();
for ($i = 0; $i < 200; $i++) {
    $station = array();
    $station['lat'] = rand(4400000, 4800000) / 100000;
    $station['lon'] = rand(-8000, 560000) / 100000;

    if (rand(1, 2) == 1) {
        $station['name'] = $faker->word(1);
    } else {
        $station['name'] = $faker->word(2);
    }

    $stationId = insert('station', $station);
    $max = rand(2, 4);
    for ($v = 0; $v < $max; $v++) {

        $type = rand(1, 3);

        if ($type == 1) {
            $power = 3;
        } elseif ($type == 2) {
            $power = 24;
        } else {
            $power = 50;
        }

        $terminal = array();
        $terminal['station_id'] = $stationId;
        $terminal['power'] = $power;

        $terminal_ids[] = insert('terminal', $terminal);
    }
}

$min_terminal_ids = min($terminal_ids);
$max_terminal_ids = max($terminal_ids);

for ($i = 0; $i < 30; $i++) {
    $user = array();
    $user['lastname'] = $faker->lastName;
    $user['firstname'] = $faker->firstName;
    $user['phone'] = $faker->phoneNumber;
    $user['password'] = sha1($faker->numberBetween(1000, 9999));
    $user['mail'] = $faker->email;
    $user['city'] = $faker->city;
    $user['login'] = $faker->userName;
    $user['connection_number'] = 0;
    $user['postal_code'] = str_replace(" ", "", Address::postcode());
    $user_id = insert("users", $user);

    if (rand(0, 7) == 7) {
        $max = rand(5, 10);
        for ($v = 0; $v < $max; $v++) {
            $c_type = rand(1, 2);
            $contract = array();
            $contract['user_id'] = $user_id;
            $contract['type_id'] = $c_type;
            $contract['plate'] = rand(10, 99) . '-' . strtoupper($faker->randomLetter . $faker->randomLetter . $faker->randomLetter) . '-' . rand(10, 99);
            if ($c_type == 1) {
                $contract['created_at'] = $faker->dateTimeBetween("-6 months", "-3 months")->format("Y-m-d");
                $contract['remaining'] = rand(558, 9863);
            } else {
                $contract['start'] = $faker->dateTimeBetween("-6 months", "-3 months")->format("Y-m-d");
                $contract['created_at'] = $faker->dateTimeBetween("-8 months", "-7 months")->format("Y-m-d");
                $contract['end'] = $faker->dateTimeBetween("-1 months", "+3 months")->format("Y-m-d");
            }
            $contract_id = insert('contracts', $contract);
            $max2 = rand(19, 40);
            for ($k = 0; $k < $max2; $k++) {
                var_dump("user_terminal");
                $charge = array();
                $charge['contract_id'] = $contract_id;
                $charge['total_power'] = rand(12, 300);
                $charge['terminal_id'] = rand($min_terminal_ids, $max_terminal_ids);
                $date = $faker->dateTimeBetween("-2 months", "now");
                $charge['date'] = $date->format("Y-m-d");
                $charge['time'] = $date->format("H:m:s");
                insert('user_terminal', $charge);
            }
        }
    } else {
        $c_type = rand(1, 2);
        $contract = array();
        $contract['user_id'] = $user_id;
        $contract['type_id'] = $c_type;
        $contract['plate'] = rand(10, 99) . '-' . strtoupper($faker->randomLetter . $faker->randomLetter . $faker->randomLetter) . '-' . rand(10, 99);
        if ($c_type == 1) {
            $contract['remaining'] = rand(558, 9863);
            $contract['created_at'] = $faker->dateTimeBetween("-6 months", "-3 months")->format("Y-m-d");
        } else {
            $contract['start'] = $faker->dateTimeBetween("-6 months", "-3 months")->format("Y-m-d");
            $contract['created_at'] = $faker->dateTimeBetween("-8 months", "-7 months")->format("Y-m-d");
            $contract['end'] = $faker->dateTimeBetween("-1 months", "+3 months")->format("Y-m-d");
        }
        $contract_id = insert('contracts', $contract);
        $max = rand(19, 40);
        for ($k = 0; $k < $max; $k++) {
            var_dump("user_terminal");
            $charge = array();
            $charge['contract_id'] = $contract_id;
            $charge['total_power'] = rand(12, 300);
            $charge['terminal_id'] = rand($min_terminal_ids, $max_terminal_ids);
            $date = $faker->dateTimeBetween("-2 months", "now");
            $charge['date'] = $date->format("Y-m-d");
            $charge['time'] = $date->format("H:m:s");
            insert('user_terminal', $charge);
        }
    }
}
