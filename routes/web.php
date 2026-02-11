<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $base_url = \URL::to('/');
    return redirect('' . $base_url . '/docs/api#/');
});
