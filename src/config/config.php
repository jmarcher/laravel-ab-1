<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection used to store the A/B testing information.
    |
    */

    'connection' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Experiments
    |--------------------------------------------------------------------------
    |
    | A list of experiment identifiers.
    |
    | Example: ['big-logo', 'small-buttons']
    |
    */

    'experiments' => [],

    /*
    |--------------------------------------------------------------------------
    | Goals
    |--------------------------------------------------------------------------
    |
    | A list of goals. This list can contain urls, route names or custom goals.
    |
    | Example: ['pricing/order', 'signup']
    |
    */

    'goals' => [],

    /*
    |--------------------------------------------------------------------------
    | AB Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of minutes that you wish the session
    | to be allowed to remain idle before it expires.
    |
    */

    'lifetime' => env('AB_LIFETIME', 60),

    /*
    |--------------------------------------------------------------------------
    | AB Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the cookie used to identify a session
    | instance by ID. The name specified here will get used every time a
    | new session cookie is created by the framework for every driver.
    |
    */

    'cookie' => env('AB_SESSION_COOKIE', 'ab'),

);
