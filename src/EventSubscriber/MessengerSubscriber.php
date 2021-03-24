<?php


namespace App\EventSubscriber;


use App\Model\PersistInterface;
use App\Service\StorageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

class MessengerSubscriber implements EventSubscriberInterface
{
    private StorageManager $storageManager;

    /**
     * MessengerSubscriber constructor.
     * @param StorageManager $storageManager
     */
    public function __construct(StorageManager $storageManager)
    {
        $this->storageManager = $storageManager;
    }


    public static function getSubscribedEvents(): array
    {
        return [
            SendMessageToTransportsEvent::class => 'onSendMessage',
            WorkerMessageHandledEvent::class => 'omMessageHandled',
        ];
    }

    /**
     * @param WorkerMessageHandledEvent $event
     */
    public function omMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if ($message instanceof PersistInterface) {
            $this->storageManager->deleteData($message);
        }
    }

    /**
     * @param SendMessageToTransportsEvent $event
     * @throws \JsonException
     */
    public function onSendMessage(SendMessageToTransportsEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if ($message instanceof PersistInterface) {
            $this->storageManager->saveData($message);
        }
    }
}