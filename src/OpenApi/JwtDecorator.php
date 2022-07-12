<?php

declare(strict_types=1);

namespace Camelot\Api\Authentication\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class JwtDecorator implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;
    private string $tokenPath;
    private string $refreshTokenPath;

    public function __construct(OpenApiFactoryInterface $decorated, string $tokenPath, string $refreshTokenPath)
    {
        $this->decorated = $decorated;
        $this->tokenPath = $tokenPath;
        $this->refreshTokenPath = $refreshTokenPath;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $this->addSchema($openApi);
        $this->addAuthToken($openApi);
        $this->addAuthTokenRefresh($openApi);

        return $openApi;
    }

    private function addSchema(OpenApi $openApi): void
    {
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['Token'] = new ArrayObject(
            [
                'type' => 'object',
                'properties' => [
                    'token' => [
                        'type' => 'string',
                        'readOnly' => false,
                    ],
                    'refresh_token' => [
                        'type' => 'string',
                        'readOnly' => true,
                    ],
                ],
            ]
        );

        $schemas['RefreshToken'] = new ArrayObject(
            [
                'type' => 'object',
                'properties' => [
                    'refresh_token' => [
                        'type' => 'string',
                        'readOnly' => false,
                    ],
                ],
            ]
        );

        $schemas['Credentials'] = new ArrayObject(
            [
                'type' => 'object',
                'properties' => [
                    'email' => [
                        'type' => 'string',
                        'example' => 'jane.doe@example.com',
                    ],
                    'password' => [
                        'type' => 'string',
                        'example' => 'k1tt3ns',
                    ],
                ],
            ]
        );
    }

    private function addAuthToken(OpenApi $openApi): void
    {
        $requestBody = new Model\RequestBody(
            description: 'Request an authenticated JSON Web Token (JWT) using an existing credentials.',
            content: new ArrayObject(['application/json' => ['schema' => ['$ref' => '#/components/schemas/Credentials']]]),
        );
        $responses = [
            '200' => [
                'description' => 'An authenticated JSON Web Token (JWT).',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Token']]],
            ],
        ];

        $pathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postCredentialsItem',
                tags: ['Token'],
                responses: $responses,
                summary: 'Request an authenticated JSON Web Token (JWT) using an existing credentials.',
                requestBody: $requestBody,
            ),
        );

        $openApi->getPaths()->addPath($this->tokenPath, $pathItem);
    }

    private function addAuthTokenRefresh(OpenApi $openApi): void
    {
        $requestBody = new Model\RequestBody(
            description: 'Request an new authenticated JSON Web Token (JWT) using a refresh token.',
            content: new ArrayObject(['application/json' => ['schema' => ['$ref' => '#/components/schemas/RefreshToken']]]),
        );
        $responses = [
            '200' => [
                'description' => 'An authenticated JSON Web Token (JWT) with a refreshed expiry.',
                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Token']]],
            ],
        ];

        $pathItem = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postTokenItem',
                tags: ['Token'],
                responses: $responses,
                summary: 'Request an new authenticated JSON Web Token (JWT) using a refresh token.',
                requestBody: $requestBody,
            ),
        );

        $openApi->getPaths()->addPath($this->refreshTokenPath, $pathItem);
    }
}
