<?php declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ResetEmailDto
{
    /**
     * @Assert\Email(message="constraints.email.incorrect")
     * @Assert\NotBlank(message="constraints.email.blank")
     */
    public string $email;
}
