<?php


namespace App\Tests\Functional;


class RegistrationTest extends BaseApiTestCase
{
    public function testSuccessRegistration(): void
    {
        $json = $this->sendPOST('/api/users', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => 'test@example.com',
                'password' => '$3CR3T',
            ],
        ], 201);

        self::assertArrayHasKey('token', $json);
    }

    /**
     * @dataProvider registrationDataProvider
     * @param string $email
     * @param string $password
     * @param string $detail
     */
    public function testRegistrationValidationErrors(string $email, string $password, string $detail): void
    {

        $json = $this->sendPOST('/api/users', [
            'headers' => [
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ],
            'json' => [
                'email' => $email,
                'password' => $password,
            ],
        ], 422);

        self::assertArrayHasKey('detail', $json);
        self::assertEquals($detail, $json['detail']);
    }

    public function registrationDataProvider(): array
    {
        return [
            [
                'email' => '',
                'password' => '$3CR3T',
                'detail' => 'email: Email не может быть пустым'
            ],
            [
                'email' => 'not-email',
                'password' => '$3CR3T',
                'detail' => 'email: Некорректный email'
            ],
            [
                'email' => 'test@example.com',
                'password' => '',
                'detail' => "password: Необходимо указать пароль\npassword: Пароль должен быть не менее 6 символов"
            ],
            [
                'email' => 'test@example.com',
                'password' => 'short',
                'detail' => 'password: Пароль должен быть не менее 6 символов'
            ],
        ];
    }
}