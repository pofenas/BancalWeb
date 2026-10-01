<?php

use CodeIgniter\Router\RouteCollection;


/** @var RouteCollection $routes */

$routes->get('api/accion', 'Api::accion');
$routes->get('/', 'Panel::index');
$routes->get('panel', 'Panel::index');
$routes->get('panel/data/(:segment)', 'Panel::todo/$1');
$routes->post('panel/create', 'Panel::create');
$routes->post('panel/update/(:num)', 'Panel::update/$1');
$routes->post('panel/delete/(:num)', 'Panel::delete/$1');
$routes->put('api/status/(:segment)/(:num)', 'Api::status/$1/$2');
$routes->get('api/login/(:segment)', 'Api::login/$1');
$routes->get('api/getSchedule/(:segment)', 'Api::getSchedule/$1');