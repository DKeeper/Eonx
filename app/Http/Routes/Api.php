<?php
declare(strict_types=1);

/** @var \Laravel\Lumen\Routing\Router $router */

// MailChimp group
$router->group(['prefix' => 'mailchimp', 'namespace' => 'MailChimp'], static function () use ($router) {
    // Lists group
    $router->group(['prefix' => 'lists'], static function () use ($router) {
        $router->post('/', 'ListsController@create');
        $router->get('/{listId}', 'ListsController@show');
        $router->patch('/{listId}', 'ListsController@update');
        $router->delete('/{listId}', 'ListsController@remove');
    });
    // Members group
    $router->group(['prefix' => 'lists/{listId}'], static function () use ($router) {
        $router->get('/members', 'MembersController@show');
        $router->get('/members/{email}', 'MembersController@showMember');
        $router->post('/members', 'MembersController@create');
        $router->delete('/members/{email}', 'MembersController@remove');
        $router->patch('/members/{email}', 'MembersController@update');
    });
});
