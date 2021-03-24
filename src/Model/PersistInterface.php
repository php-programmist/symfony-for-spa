<?php


namespace App\Model;


use Doctrine\ORM\EntityManagerInterface;

interface PersistInterface
{
    /**
     * @return int
     */
    public function getStorageTtl(): int;

    /**
     * @return string
     */
    public function getStorageKey(): string;

    /**
     * @param array $data
     * @param EntityManagerInterface $entityManager
     * @return mixed
     */
    public function setStorageData(array $data, EntityManagerInterface $entityManager);

    /**
     * @return array
     */
    public function getStorageData(): array;
}