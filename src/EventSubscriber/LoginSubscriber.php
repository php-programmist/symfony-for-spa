<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\UserManager;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Throwable;

class LoginSubscriber implements EventSubscriberInterface
{
    private UserManager $userManager;

    /**
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailureEvent',
            RequestEvent::class => [
                ['onKernelRequest', 9]
            ]
        ];
    }

    public function onAuthenticationFailureEvent(AuthenticationFailureEvent $event): void
    {
        if ($event->getException()->getPrevious() instanceof UsernameNotFoundException) {
            return;
        }
        try {
            $email = $event->getException()->getToken()->getUser();
            $this->userManager->setAuthenticationFailure($email);
        } catch (Throwable $e) {
        }

    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $this->userManager->setAuthenticationSuccess($user);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        if ($request->attributes->get('_route') === 'authentication_token') {
            $response = $this->userManager->getLoginBlockResponse($event->getRequest()->getContent());
            if (null !== $response) {
                $event->setResponse($response);
            }
        }
    }
}
