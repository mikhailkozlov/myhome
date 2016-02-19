<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->group(['prefix' => 'api/v1'],function() use ($app) {
    resource('power', 'PowerController');
    resource('energy', 'EnergyController');
});


function resource($uri, $controller)
{
	//$verbs = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE');
	global $app;
	$app->get($uri, 'App\Http\Controllers\\'.$controller.'@index');
	$app->get($uri.'/create', 'App\Http\Controllers\\'.$controller.'@create');
	$app->post($uri, 'App\Http\Controllers\\'.$controller.'@store');
	$app->get($uri.'/{id}', 'App\Http\Controllers\\'.$controller.'@show');
	$app->get($uri.'/{id}/edit', 'App\Http\Controllers\\'.$controller.'@edit');
	$app->put($uri.'/{id}', 'App\Http\Controllers\\'.$controller.'@update');
	$app->patch($uri.'/{id}', 'App\Http\Controllers\\'.$controller.'@update');
	$app->delete($uri.'/{id}', 'App\Http\Controllers\\'.$controller.'@destroy');
}
