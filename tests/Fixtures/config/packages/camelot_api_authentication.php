<?php

use Camelot\Api\Authentication\Tests\Fixtures\Entity\User;
use Camelot\Api\Authentication\Tests\Fixtures\Repository\UserRepository;
use Symfony\Config\CamelotApiAuthenticationConfig;

return static function (CamelotApiAuthenticationConfig $config) {
    $config
        ->userClass(User::class)
        ->userRepositoryClass(UserRepository::class)
    ;
};

