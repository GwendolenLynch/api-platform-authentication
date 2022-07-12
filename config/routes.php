<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('camelot_api_authentication_token', '/api/auth/token')
        ->methods([Request::METHOD_POST])
    ;
    $routes->add('camelot_api_authentication_refresh_token', '/api/auth/refresh')
        ->methods([Request::METHOD_POST])
    ;
};
