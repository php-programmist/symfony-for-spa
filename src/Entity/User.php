<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="constraints.email.exists")
 * @ApiResource(
 *     collectionOperations={
 *          "post"={
 *             "denormalization_context"={"groups"={"user:create"}}
 *           },
 *     },
 *     itemOperations={
 *         "get"
 *     },
 *     normalizationContext={"groups"={"user:read"}}
 * )
 */
class User implements UserInterface
{
    private const FAILED_LOGIN_DELAYS = [
        1 => 0,
        2 => 0,
        3 => 5 * 60,
        4 => 30 * 60,
        5 => 180 * 60,
    ];

    public const MIN_PASSWORD_LENGTH = 6;
    public const MAX_PASSWORD_LENGTH = 4096;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @Assert\Email(
     *     message="constraints.email.incorrect",
     *     mode=Email::VALIDATION_MODE_STRICT
     * )
     * @Assert\NotBlank(message="constraints.email.blank")
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"user:read","user:create"})
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private string $password;

    /**
     * @Groups("user:create")
     * @SerializedName("password")
     * @Assert\NotBlank(message="constraints.password.empty")
     * @Assert\Length(
     *     min=User::MIN_PASSWORD_LENGTH,
     *     minMessage="constraints.password.min",
     *     max=User::MAX_PASSWORD_LENGTH,
     *     maxMessage="constraints.password.max",
     *     allowEmptyString = false
     * )
     */
    private ?string $plainPassword = null;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $failedLoginCount = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $lastLoginAttempt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $lastLogin;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    /**
     * @return int
     */
    public function getFailedLoginCount(): int
    {
        return $this->failedLoginCount;
    }

    /**
     * @param int $failedLoginCount
     * @return $this
     */
    public function setFailedLoginCount(int $failedLoginCount): self
    {
        $this->failedLoginCount = $failedLoginCount;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLoginAttempt(): ?DateTime
    {
        return $this->lastLoginAttempt;
    }

    /**
     * @param DateTime|null $lastLoginAttempt
     */
    public function setLastLoginAttempt(?DateTime $lastLoginAttempt): void
    {
        $this->lastLoginAttempt = $lastLoginAttempt;
    }

    /**
     * @return DateTime|null
     */
    public function getLastLogin(): ?DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param DateTime|null $lastLogin
     * @return $this
     */
    public function setLastLogin(?DateTime $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function setAuthenticationSuccessData(): void
    {
        $this->setLastLogin(new DateTime());
        $this->setFailedLoginCount(0);
        $this->setLastLoginAttempt(null);
    }

    public function setAuthenticationFailureData(): void
    {
        $this->failedLoginCount++;
        $this->setLastLoginAttempt(new DateTime());
    }

    /**
     * @return int - задержка в секундах до следующей возможной попытки аутентификации
     */
    public function getLoginBlockDelay(): int
    {
        if (array_key_exists($this->failedLoginCount, self::FAILED_LOGIN_DELAYS)) {
            $delay = self::FAILED_LOGIN_DELAYS[$this->failedLoginCount];
        } else {
            $maxCount = array_key_last(self::FAILED_LOGIN_DELAYS);
            $maxDelay = self::FAILED_LOGIN_DELAYS[$maxCount];
            $delay = $maxDelay * (2 ** ($this->failedLoginCount - $maxCount));
        }

        return $delay;

    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     * @return $this
     */
    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }
}
