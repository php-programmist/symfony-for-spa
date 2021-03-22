<?php


namespace App\Event\User;


use App\Dto\ChangeEmailDto;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class NewEmailRequestedEvent extends Event
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var ChangeEmailDto
     */
    private $newEmailDto;

    /**
     * NewEmailRequestedEvent constructor.
     * @param User $user
     * @param ChangeEmailDto $newEmailDto
     */
    public function __construct(User $user, ChangeEmailDto $newEmailDto)
    {
        $this->user = $user;
        $this->newEmailDto = $newEmailDto;
    }


    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return ChangeEmailDto
     */
    public function getNewEmailDto(): ChangeEmailDto
    {
        return $this->newEmailDto;
    }
}