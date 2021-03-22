<?php


namespace App\Event\User;


use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractUserEvent extends Event
{
    private User $user;

    /**
     * RegisterEvent constructor.
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}