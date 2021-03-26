<?php


namespace App\Exception\User;


use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PasswordResetTokenNotValidException extends NotFoundHttpException
{

}