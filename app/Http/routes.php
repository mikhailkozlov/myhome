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

$app->group(['prefix' => 'api/v1'], function () use ($app) {
    resource('power', 'PowerController', 'api.power');
    resource('energy', 'EnergyController', 'api.energy');
});

/**
 * Build Controller resource
 *
 * @param $uri
 * @param $controller
 * @param null $prefix
 */
function resource($uri, $controller, $prefix = null)
{
    //$verbs = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE');
    global $app;

    // default prefix
    if (is_null($prefix)) {
        $prefix = rtrim(strtolower($controller), 'controller');
    }


    $app->get($uri, [
        'as'   => $prefix . '.index',
        'uses' => 'App\Http\Controllers\\' . $controller . '@index'
    ]);

    $app->get($uri . '/create', [
        'as'   => $prefix . '.create',
        'uses' => 'App\Http\Controllers\\' . $controller . '@create'
    ]);
    $app->post($uri, [
            'as'   => $prefix . '.store',
            'uses' => 'App\Http\Controllers\\' . $controller . '@store'
        ]
    );
    $app->get($uri . '/{id}', [
        'as'   => $prefix . '.show',
        'uses' => 'App\Http\Controllers\\' . $controller . '@show'
    ]);
    $app->get($uri . '/{id}/edit', [
        'as'   => $prefix . '.edit',
        'uses' => 'App\Http\Controllers\\' . $controller . '@edit'
    ]);
    $app->put($uri . '/{id}', [
        'as'   => $prefix . '.update',
        'uses' => 'App\Http\Controllers\\' . $controller . '@update'
    ]);
    $app->patch($uri . '/{id}', [
        'as'   => $prefix . '.update',
        'uses' => 'App\Http\Controllers\\' . $controller . '@update'
    ]);
    $app->delete($uri . '/{id}', [
        'as'   => $prefix . '.destroy',
        'uses' => 'App\Http\Controllers\\' . $controller . '@destroy'
    ]);
}
