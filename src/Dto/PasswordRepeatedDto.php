<?php declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordRepeatedDto
{
    /**
     * @Assert\NotBlank(message="constraints.password.empty")
     * @Assert\Length(
     *     min=User::MIN_PASSWORD_LENGTH,
     *     minMessage="constraints.password.min",
     *     max=User::MAX_PASSWORD_LENGTH,
     *     maxMessage="constraints.password.max",
     *     allowEmptyString = false
     * )
     */
    public string $password;

    /**
     * @SerializedName("password_confirmation");
     * @Assert\Expression(
     *     "this.password == value",
     *     message="constraints.password.not_match"
     * )
     */
    public string $passwordConfirmation;
}
