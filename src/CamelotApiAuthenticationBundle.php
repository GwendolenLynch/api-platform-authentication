<?php

declare(strict_types=1);

namespace Camelot\Api\Authentication;

use Camelot\Api\Authentication\Entity\RefreshToken;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class CamelotApiAuthenticationBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('user_class')->isRequired()->end()
                ->scalarNode('user_repository_class')->isRequired()->end()
                ->scalarNode('token_path')->defaultValue('/api/auth/token')->end()
                ->scalarNode('refresh_token_path')->defaultValue('/api/auth/refresh')->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();
        $services->defaults()
            ->autowire()
            ->autoconfigure()
        ;

        $services->set(OpenApi\JwtDecorator::class)
            ->decorate('api_platform.openapi.factory')
            ->arg('$decorated', service('.inner'))
            ->arg('$tokenPath', $config['token_path'])
            ->arg('$refreshTokenPath', $config['refresh_token_path'])
        ;

        $services->set(Command\UserTokenLoginCommand::class)
            ->arg('$repository', service($config['user_repository_class']))
            ->arg('$env', param('kernel.environment'))
        ;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // prepend
        $builder->prependExtensionConfig('gesdinet_jwt_refresh_token', [
            'refresh_token_class' => RefreshToken::class,
            'token_parameter_name' => 'refresh_token',
        ]);

        // append
        $container->extension('security', [
            'firewalls' => [
                'login' => [
                    'pattern' => '^/api/auth/token',
                    'stateless' => true,
                    'json_login' => [
                        'check_path' => '/api/auth/token',
                        'username_path' => 'email',
                        'password_path' => 'password',
                        'success_handler' => 'lexik_jwt_authentication.handler.authentication_success',
                        'failure_handler' => 'lexik_jwt_authentication.handler.authentication_failure',
                    ],
                ],
                'api' => [
                    'pattern' => '^/api/.+',
                    'stateless' => true,
                    'entry_point' => 'jwt',
                    'jwt' => null,
                    'refresh_jwt' => [
                        'check_path' => '/api/auth/refresh',
                    ],
                ],
            ],
        ]);
    }
}
