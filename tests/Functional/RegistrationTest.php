<?php


namespace App\Tests\Functional;


use App\Model\Mailer\TestMailer;

class RegistrationTest extends BaseApiTestCase
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
}