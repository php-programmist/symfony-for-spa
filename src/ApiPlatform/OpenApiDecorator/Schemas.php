<?php


namespace App\ApiPlatform\OpenApiDecorator;


use ArrayObject;

class Schemas
{
    /**
     * @param ArrayObject|null $schemas
     */
    public static function addCredentialsSchema(?ArrayObject $schemas): void
    {
        $schemas['Credentials'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'youremail@example.com',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'password',
                ],
            ],
        ]);
    }


    /**
     * @param ArrayObject|null $schemas
     */
    public static function addRefreshTokenSchema(?ArrayObject $schemas): void
    {
        $schemas['RefreshCredentials'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'refresh_token' => [
                    'type' => 'string',
                    'example' => 'xxx00a7a9e970f9bbe076e05743e00648908c38366c551a8cdf524ba424fc3e520988f6320a54989bbe85931ffe1bfcc63e33fd8b45d58564039943bfbd8dxxx',
                ],
            ],
        ]);
    }

    /**
     * @param ArrayObject|null $schemas
     */
    public static function addTokenSchema(?ArrayObject $schemas): void
    {
        $schemas['Token'] = new ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'refresh_token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);
    }
}