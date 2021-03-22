<?php


namespace App\Event\User;


use App\Entity\User;
use App\Event\GetUserInterface;

class UserInvitedReferralEvent extends AbstractUserEvent implements GetUserInterface
{
    private User $referral;

    /**
     * @return User
     */
    public function getReferral(): User
    {
        return $this->referral;
    }

    /**
     * @param User $referral
     * @return UserInvitedReferralEvent
     */
    public function setReferral(User $referral): UserInvitedReferralEvent
    {
        $this->referral = $referral;
        return $this;
    }
}