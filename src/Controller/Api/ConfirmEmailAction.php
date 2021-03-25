<?php


namespace App\Controller\Api;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConfirmEmailAction
{
    /**
     * @param User $data
     * @param string $token
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    public function __invoke(User $data, string $token, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($data->getUuid() !== $token) {
            throw new BadRequestException('Некорректный токен для подтверждения Email');
        }
        $data->setEmailConfirmed(true);
        $entityManager->flush();

        return new JsonResponse(['status' => true, 'message' => 'Email успешно подтвержден']);
    }
}