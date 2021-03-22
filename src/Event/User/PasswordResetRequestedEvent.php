<?php


namespace App\Event\User;


use App\Model\Security\PasswordResetRequest;

class PasswordResetRequestedEvent
{
    /**
     * @var PasswordResetRequest
     */
    private $resetRequest;
    /**
     * @var bool
     */
    private $sendEmail;

    /**
     * PasswordResetRequestedEvent constructor.
     * @param PasswordResetRequest $resetRequest
     * @param bool $sendEmail
     */
    public function __construct(PasswordResetRequest $resetRequest, bool $sendEmail)
    {
        $this->resetRequest = $resetRequest;
        $this->sendEmail = $sendEmail;
    }

    /**
     * @return PasswordResetRequest
     */
    public function getResetRequest(): PasswordResetRequest
    {
        return $this->resetRequest;
    }

    /**
     * @return bool
     */
    public function isSendEmail(): bool
    {
        return $this->sendEmail;
    }
}