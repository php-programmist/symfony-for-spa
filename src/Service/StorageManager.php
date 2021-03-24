<?php


namespace App\Service;


use App\Exception\Storage\JsonDecodeException;
use App\Exception\Storage\StorageKeyNotExistException;
use App\Model\PersistInterface;
use Doctrine\ORM\EntityManagerInterface;
use Redis;

class StorageManager
{
    private Redis $redis;
    private EntityManagerInterface $entityManager;

    /**
     * StorageManager constructor.
     * @param Redis $redis
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(Redis $redis, EntityManagerInterface $entityManager)
    {
        $this->redis = $redis;
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $key
     * @return array
     * @throws JsonDecodeException
     * @throws StorageKeyNotExistException|\JsonException
     */
    protected function getData(string $key): array
    {
        $data = $this->redis->get($key);

        if (empty($data)) {
            throw new StorageKeyNotExistException("Не найден ключ ({$key}) в хранилище.");
        }

        $result = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        if ($result === false) {
            throw new JsonDecodeException("Ошибка при декодировании данных ($data)");
        }
        return $result;
    }

    /**
     * @param PersistInterface $persistable
     * @throws \JsonException
     */
    public function saveData(PersistInterface $persistable): void
    {
        $this->redis->setex(
            $persistable->getStorageKey(),
            $persistable->getStorageTtl(),
            json_encode($persistable->getStorageData(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * @param PersistInterface $persistable
     */
    public function deleteData(PersistInterface $persistable): void
    {
        $this->redis->del($persistable->getStorageKey());
    }

    /**
     * @param PersistInterface $persistable
     * @throws JsonDecodeException
     * @throws StorageKeyNotExistException|\JsonException
     */
    public function loadData(PersistInterface $persistable): void
    {
        $data = $this->getData($persistable->getStorageKey());
        if (!is_array($data)) {
            throw new JsonDecodeException('Полученные из хранилища данные не являются массивом');
        }
        $persistable->setStorageData($data, $this->entityManager);
    }
}