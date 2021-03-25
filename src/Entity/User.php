<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Model\Email\EmailAddress;
use App\Model\Uuid;
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
 * @ORM\Table(indexes={
 *     @ORM\Index(name="IDX_CREATED_AT", columns={"created_at"}),
 *     @ORM\Index(name="IDX_UPDATED_AT", columns={"updated_at"}),
 * })
 * @UniqueEntity(fields={"email"}, message="constraints.email.exists")
 * @ApiResource(
 *     collectionOperations={
 *          "get" ={
 *              "security"="is_granted('ROLE_ADMIN')"
 *          },
 *          "post"={
 *             "denormalization_context"={"groups"={"user:create"}}
 *          },
 *          "me"={
 *             "method"="GET",
 *             "path" = "/users/me",
 *             "pagination_enabled"=false
 *          }
 *     },
 *     itemOperations={
 *         "get" ={
 *              "security"="is_granted('ROLE_ADMIN') or user == object"
 *         },
 *         "email_confirm" = {
 *             "method"="GET",
 *             "path"="/users/{id}/email/confirm/{token}",
 *             "controller"=\App\Controller\Api\ConfirmEmailAction::class
 *         }
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
     * @Groups({"user:read"})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="guid", unique=true)
     */
    private string $uuid;

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

    /**
     * @Gedmo\Mapping\Annotation\Timestampable(on="create")
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"user:read"})
     */
    protected DateTime $createdAt;

    /**
     * @Gedmo\Mapping\Annotation\Timestampable(on="update")
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     * @Groups({"user:read"})
     */
    protected DateTime $updatedAt;

    /**
     * Подтвердил ли пользователь свой Email?
     * @ORM\Column(type="boolean", options={"default": false})
     * @Groups({"user:read"})
     */
    private bool $emailConfirmed = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $firstName = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $lastName = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $phone = null;

    public function __construct()
    {
        $this->uuid = Uuid::create();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
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

    /**
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->emailConfirmed;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return User
     */
    public function setCreatedAt(DateTime $createdAt): User
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return bool
     */
    public function isEmailConfirmed(): bool
    {
        return $this->emailConfirmed;
    }

    /**
     * @param bool $emailConfirmed
     * @return $this
     */
    public function setEmailConfirmed(bool $emailConfirmed): self
    {
        $this->emailConfirmed = $emailConfirmed;
        return $this;
    }

    /**
     * @return User
     */
    public function setConfirmed(): self
    {
        $this->setEmailConfirmed(true);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     * @return $this
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     * @return $this
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * @return $this
     */
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getFIO(): string
    {
        return trim($this->getFirstName() . ' ' . $this->getLastName());
    }

    /**
     * @return EmailAddress
     */
    public function getEmailAddress(): EmailAddress
    {
        return (new EmailAddress())
            ->setEmail($this->getEmail())
            ->setTitle($this->getFIO());
    }

    /**
     * @return array
     */
    public function getEmailSubstitutions(): array
    {
        return [
            '$user.email' => $this->getEmail(),
            '$user.first_name' => $this->getFirstName(),
            '$user.last_name' => $this->getLastName(),
            '$user.fio' => $this->getFIO(),
        ];
    }
}
