<?php


namespace App\Model\MessageBus\Handler;


use App\Exception\Storage\JsonDecodeException;
use App\Exception\Storage\StorageKeyNotExistException;
use App\Model\MessageBus\Message\AbstractSendEmailMessage;
use App\Service\EmailManager;
use App\Service\StorageManager;
use Exception;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendEmailMessageHandler implements MessageHandlerInterface
{
    /**
     * @var EmailManager
     */
    private EmailManager $emailManager;
    /**
     * @var StorageManager
     */
    private StorageManager $storageManager;

    /**
     * SendEmailMessageHandler constructor.
     * @param EmailManager $emailManager
     * @param StorageManager $storageManager
     */
    public function __construct(EmailManager $emailManager, StorageManager $storageManager)
    {
        $this->emailManager = $emailManager;
        $this->storageManager = $storageManager;
    }

    /**
     * @param AbstractSendEmailMessage $message
     * @throws Exception
     */
    public function __invoke(AbstractSendEmailMessage $message)
    {
        try {
            $this->storageManager->loadData($message);
        } catch (JsonDecodeException | StorageKeyNotExistException $ex) {
            throw new UnrecoverableMessageHandlingException(
                "Ошибка при обработке сообщения (данные находятся в {$message->getStorageKey()}): {$ex->getMessage()}"
            );
        }

        $email = $message->getEmail();

        if ($email === null) {
            throw new UnrecoverableMessageHandlingException('В сообщении нет данных об отправляемом письме');
        }
        $this->emailManager->send($email, false);

        $this->storageManager->deleteData($message);
    }
}