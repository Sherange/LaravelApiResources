<?php

use Faker\Generator as Faker;

$factory->define(App\People::class, function (Faker $faker) {
    return [
        'first_name' => $factory->firstNameMale,
        'last_name' => $factory->lastName,
        'twitter' => $factory->paragraph
    ];
});
