<?php


namespace App\Controller\Api;


use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use App\Dto\PasswordRepeatedDto;
use App\Entity\User;
use App\Exception\User\PasswordResetTokenNotValidException;
use App\Service\UserManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PasswordResetConfirmAction
{
    /**
     * @param User $data
     * @param string $token
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UserManager $manager
     * @return JsonResponse
     * @throws PasswordResetTokenNotValidException
     */
    public function __invoke(
        User $data,
        string $token,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserManager $manager
    ): JsonResponse {
        $resetRequest = $manager->fetchPasswordResetRequest($data, $token);
        if (!$resetRequest->isValid()) {
            throw new PasswordResetTokenNotValidException('Параметры для сброса пароля не найдены или уже недействительны');
        }

        /** @var PasswordRepeatedDto $data */
        $data = $serializer->deserialize($request->getContent(), PasswordRepeatedDto::class, 'json');
        $violations = $validator->validate($data);
        if (0 !== count($violations)) {
            throw new ValidationException($violations);
        }
        $manager->resetPassword($resetRequest, $data->password);

        return new JsonResponse(['status' => true, 'message' => 'Вы успешно изменили пароль']);
    }
}