<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Carbon\Carbon;

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Concert::class, function (Faker\Generator $faker) {
    return [
        'title' => 'Example Band',
        'subtitle' => 'with the fake openers',
        'date' => Carbon::parse('+2 weeks'),
        'ticket_price' => 2000,
        'venue' => 'the example theatre',
        'venue_address' => '123 example lane',
        'city' => 'Fakeville',
        'state' => 'ON',
        'zip' => '17916',
        'additional_information' => 'some sample additional information'
    ];
});

/*
koristi se od verzije 5.3
$factory->state(App\Concert::class, 'published', function($faker){
    return [
        'published_at' => Carbon::parse('-1 week')
        ];
});*/

$factory->define(App\Ticket::class, function (Faker\Generator $faker) {
    return [
        //kreira se pomocu callback funkcije zato sto u slucaju da se koncert prosled i kao argument ne dolazi do poziva ove funkcije (ne kreira se novi koncert)
        'concert_id' => function () {
            return factory(App\Concert::class)->create()->id;
        }
    ];
});

