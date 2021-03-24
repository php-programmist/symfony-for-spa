<?php


namespace App\Exception\User;


use App\Model\Security\PasswordResetRequest;
use Exception;
use Throwable;

class PasswordResetException extends Exception
{
    /**
     * @var PasswordResetRequest
     */
    private $resetRequest;

    public function __construct(
        PasswordResetRequest $resetRequest,
        $message = "",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->resetRequest = $resetRequest;
    }

    /**
     * @return PasswordResetRequest
     */
    public function getResetRequest(): PasswordResetRequest
    {
        return $this->resetRequest;
    }
}