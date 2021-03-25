<?php


namespace App\Tests\Functional;


use App\Model\Mailer\TestMailer;

class UserTest extends BaseApiTestCase
{
    public function testSuccessRegistration(): void
    {
        TestMailer::startCatch();
        $json = $this->sendPOST('/users', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'email' => self::TEST_USER_EMAIL,
                'password' => self::TEST_USER_PASSWORD,
            ],
        ], 201);

        self::assertArrayHasKey('token', $json);
        $mails = TestMailer::getSentEmailsTo(self::TEST_USER_EMAIL);
        self::assertEquals(1, TestMailer::getSentEmailsCount());
        self::assertEquals('Подтвердите Ваш Email', $mails[0]->getSubject());
    }

    /**
     * @dataProvider registrationDataProvider
     * @param string $email
     * @param string $password
     * @param string $detail
     */
    public function testRegistrationValidationErrors(string $email, string $password, string $detail): void
    {

        $json = $this->sendPOST('/users', [
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

    public function testMe(): void
    {
        $user = $this->createUser();
        $token = $this->getToken();

        $json = $this->sendGET('/users/me', [
            'headers' => [
                'accept' => 'application/json'
            ],
            'auth_bearer' => $token
        ], 200);

        self::assertEquals($user->getEmail(), $json['email']);
        self::assertFalse($json['emailConfirmed']);
    }

    public function testEmailConfirm(): void
    {
        $user = $this->createUser();

        $json = $this->sendGET(
            sprintf('/users/%d/email/confirm/%s', $user->getId(), $user->getUuid()),
            [],
            200
        );
        self::assertTrue($json['status']);

        //Проверяем, что почта подтверждена
        $accessToken = $this->getToken();
        $json = $this->sendGET('/users/me', [
            'headers' => [
                'accept' => 'application/json'
            ],
            'auth_bearer' => $accessToken
        ], 200);

        self::assertTrue($json['emailConfirmed']);
    }

    public function testPasswordReset(): void
    {
        $user = $this->createUser();
        $email = $user->getEmail();
        TestMailer::startCatch();

        $json = $this->sendPOST(
            '/users/password-reset/request',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json'
                ],
                'json' => [
                    'email' => $email,
                ],
            ],
            200
        );
        self::assertTrue($json['status']);

        $mails = TestMailer::getSentEmailsTo(self::TEST_USER_EMAIL);
        self::assertEquals(1, TestMailer::getSentEmailsCount());
        self::assertEquals('Сброс пароля', $mails[0]->getSubject());
    }
}