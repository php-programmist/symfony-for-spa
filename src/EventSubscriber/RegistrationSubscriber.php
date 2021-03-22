<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

class RegistrationSubscriber implements EventSubscriberInterface
{
    private AuthenticationSuccessHandler $authenticationSuccessHandler;

    public function __construct(AuthenticationSuccessHandler $authenticationSuccessHandler)
    {

        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            ViewEvent::class => [
                ['onViewEvent', 17]
            ]
        ];
    }

    public function onViewEvent(ViewEvent $event): void
    {
        $user = $event->getControllerResult();
        $request = $event->getRequest();
        if ($user instanceof User
            && $request->attributes->get('_route') === 'api_users_post_collection'
            && $user->getId() !== null
        ) {
            $response = $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);
            if (null !== $response) {
                $response->setStatusCode(Response::HTTP_CREATED);
                $event->setResponse($response);
                $event->stopPropagation();
            }
        }
    }
}
