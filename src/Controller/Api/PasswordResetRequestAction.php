<?php


namespace App\Controller\Api;


use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use App\Dto\ResetEmailDto;
use App\Exception\User\PasswordResetException;
use App\Service\UserManager;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PasswordResetRequestAction
{
    /**
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param UserManager $manager
     * @return JsonResponse
     * @throws NonUniqueResultException
     * @throws PasswordResetException
     */
    public function __invoke(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserManager $manager
    ): JsonResponse {
        /** @var ResetEmailDto $data */
        $data = $serializer->deserialize($request->getContent(), ResetEmailDto::class, 'json');
        $violations = $validator->validate($data);
        if (0 !== \count($violations)) {
            throw new ValidationException($violations);
        }
        $user = $manager->findByEmailOrFail($data->email);
        $manager->sendPasswordResetRequest($user);

        return new JsonResponse([
            'status' => true,
            'message' => sprintf('На указанный Вами адрес %s отправлено письмо, содержащее ссылку для сброса пароля',
                $data->email)
        ]);
    }
}