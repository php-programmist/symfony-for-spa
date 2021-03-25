<?php


namespace App\Event\User;


use App\Model\Security\PasswordResetRequest;

class PasswordResetRequestedEvent
{
    private PasswordResetRequest $resetRequest;

    /**
     * @param PasswordResetRequest $resetRequest
     */
    public function __construct(PasswordResetRequest $resetRequest)
    {
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