<?php

use Faker\Generator as Faker;

$factory->define(App\Comment::class, function (Faker $faker) {
    return [
        'article_id' => $factory->randomDigit,
        'author_id' => $factory->randomDigit,
        'body' => $factory->paragraph

    ];
});
