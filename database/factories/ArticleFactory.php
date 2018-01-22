<?php

use Faker\Generator as Faker;

$factory->define(App\Article::class, function (Faker $faker) {
    return [
        'author_id' => $factory->randomDigit,
        'title' => $factory->title
    ];
});
