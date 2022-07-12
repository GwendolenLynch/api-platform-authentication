<?php

declare(strict_types=1);

use Camelot\Api\Authentication\Tests\Fixtures\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $security): void {
    $security->enableAuthenticatorManager(true);

    $security->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('auto')
        ->cost(4)
        ->timeCost(3)
        ->memoryCost(10)
    ;

    $security->provider('test_user_provider')->entity(['class' => User::class, 'property' => 'email']);

    $security->firewall('login')->provider('test_user_provider');
    $security->firewall('api')->provider('test_user_provider');

    $security->accessControl(['path' => '^/api$', 'roles' => ['PUBLIC_ACCESS']]);
    $security->accessControl(['path' => '^/api/docs', 'roles' => ['PUBLIC_ACCESS']]);
    $security->accessControl(['path' => '^/api/', 'roles' => ['IS_AUTHENTICATED_FULLY']]);
    $security->accessControl(['path' => '^/api/auth/(login|token|refresh)', 'roles' => ['PUBLIC_ACCESS']]);
    $security->accessControl(['path' => '^/', 'roles' => ['PUBLIC_ACCESS']]);
};
