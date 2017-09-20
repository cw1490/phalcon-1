<?php

/**
 * @name    routes.php
 * @author  joe@xxtime.com
 * @link    https://docs.phalconphp.com/zh/3.2/routing
 */
use Phalcon\Mvc\Router;


$router = new Router(false);
$router->removeExtraSlashes(true);


// 通用路由
$router->add(
    '/:controller/:action/:params',
    [
        'namespace'  => 'MyApp\Controllers',
        'controller' => 1,
        'action'     => 2,
        'params'     => 3
    ]
);

$router->add(
    '/:controller',
    [
        'namespace'  => 'MyApp\Controllers',
        'controller' => 1
    ]
);


// 多应用路由 Matches "/123/news/show/1"
$router->add(
    "/([0-9]+)/:controller/:action/:params",
    [
        'namespace'  => 'MyApp\Controllers',
        'app'        => 1,
        'controller' => 2,
        'action'     => 3,
        'params'     => 4
    ]
);

$router->add(
    "/([0-9]+)/:controller",
    [
        'namespace'  => 'MyApp\Controllers',
        'app'        => 1,
        'controller' => 2
    ]
);


// 接口路由
$router->add(
    '/api/:controller/:action/:params',
    [
        'namespace'  => 'MyApp\Controllers\Api',
        'controller' => 1,
        'action'     => 2,
        'params'     => 3
    ]
);

$router->add(
    '/api/:controller',
    [
        'namespace'  => 'MyApp\Controllers\Api',
        'controller' => 1
    ]
);

$router->add(
    '/api',
    [
        'namespace'  => 'MyApp\Controllers\Api',
        'controller' => 'Index'
    ]
);


// 公用方法路由
$router->add(
    "/login",
    [
        'namespace'  => 'MyApp\Controllers',
        'controller' => 'public',
        'action'     => 'login'
    ]
);

$router->add(
    "/logout",
    [
        'namespace'  => 'MyApp\Controllers',
        'controller' => 'public',
        'action'     => 'logout'
    ]
);


// Not Found
$router->notFound(
    [
        'namespace'  => 'MyApp\Controllers',
        'controller' => 'public',
        'action'     => 'show404',
    ]
);


return $router;