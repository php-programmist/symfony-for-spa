<?php


namespace App\EventSubscriber;


use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;

class UserMeSubscriber implements EventSubscriberInterface
{
    /**
     * @var Security
     */
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['resolveMe', EventPriorities::PRE_READ],
        ];
    }

    public function resolveMe(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ('api_users_me_collection' !== $request->attributes->get('_route')) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request->attributes->set('id', $user->getId());
        $request->attributes->set('_route', 'api_users_get_item');
        $request->attributes->set('_controller', 'api_platform.action.get_item');
        $request->attributes->set('_api_collection_operation_name', '');
        $request->attributes->set('_api_item_operation_name', 'get');
    }
}