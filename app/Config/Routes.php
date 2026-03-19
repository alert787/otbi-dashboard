<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('po', 'PO::index');
$routes->get('po/data', 'PO::getData');

$routes->get('pocsv', 'POCSV::index');
$routes->get('pocsv/data', 'POCSV::getData');
