<?php


namespace App\Exception\User;


use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Throwable;

class PasswordResetException extends BadRequestException
{
    public function __construct(
        $message = 'Сброс пароля уже запрошен. Вы можете запрашивать сброс пароля не чаще одного раза в 2 часа. Если вам не пришло письмо о сбросе пароля, обратитесь к администратору сайта.',
        $code = 400,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}