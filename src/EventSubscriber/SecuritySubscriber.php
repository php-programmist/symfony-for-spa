<?php


namespace App\EventSubscriber;


use App\Event\User\PasswordResetRequestedEvent;
use App\Event\User\RegisterEvent;
use App\Service\EmailManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SecuritySubscriber implements EventSubscriberInterface
{
    private EmailManager $emailManager;

    /**
     * SecuritySubscriber constructor.
     * @param EmailManager $emailManager
     */
    public function __construct(
        EmailManager $emailManager
    ) {
        $this->emailManager = $emailManager;
    }

    /**
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            RegisterEvent::class => 'onRegister',
            PasswordResetRequestedEvent::class => 'onPasswordResetRequested',
        ];
    }

    /**
     * @param PasswordResetRequestedEvent $event
     * @throws TransportExceptionInterface
     */
    public function onPasswordResetRequested(PasswordResetRequestedEvent $event): void
    {
        $request = $event->getResetRequest();
        $this->emailManager->sendResetPasswordEmail($request);

    }

    /**
     * @param RegisterEvent $event
     * @throws TransportExceptionInterface
     */
    public function onRegister(RegisterEvent $event): void
    {
        $this->emailManager->sendConfirmRegistrationEmail($event->getUser());
    }
}