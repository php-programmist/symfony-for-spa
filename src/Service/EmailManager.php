<?php


namespace App\Service;


use App\Entity\User;
use App\Exception\User\UserNotFoundException;
use App\Model\Email\AbstractEmail;
use App\Model\Email\CustomUserEmail;
use App\Model\Email\EmailAddress;
use App\Model\Mailer\UnioneMailer;
use App\Model\MessageBus\Message\SendEmailMessage;
use App\Model\MessageBus\Message\SendHighPriorityEmailMessage;
use App\Model\Security\PasswordResetRequest;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\StringLoaderExtension;

class EmailManager
{
    private MessageBusInterface $messageBus;
    private RouterInterface $router;
    private UserManager $userManager;
    private EntityManagerInterface $entityManager;
    private Environment $twig;
    private MailerInterface $mailer;
    private string $siteName;
    private string $siteUrl;
    private string $senderEmail;
    private string $senderTitle;

    /**
     * @param MessageBusInterface $messageBus
     * @param RouterInterface $router
     * @param UserManager $userManager
     * @param EntityManagerInterface $entityManager
     * @param Environment $twig
     * @param MailerInterface $mailer
     * @param string $siteName
     * @param string $siteUrl
     * @param string $senderEmail
     * @param string $senderTitle
     */
    public function __construct(
        MessageBusInterface $messageBus,
        RouterInterface $router,
        UserManager $userManager,
        EntityManagerInterface $entityManager,
        Environment $twig,
        MailerInterface $mailer,
        string $siteName,
        string $siteUrl,
        string $senderEmail,
        string $senderTitle
    ) {
        $this->messageBus = $messageBus;
        $this->router = $router;
        $this->userManager = $userManager;
        $this->siteName = $siteName;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        $this->twig->addExtension(new StringLoaderExtension());
        $this->mailer = $mailer;
        $this->siteUrl = $siteUrl;
        $this->senderEmail = $senderEmail;
        $this->senderTitle = $senderTitle;
    }

    /**
     * @param AbstractEmail $email
     * @param bool $async
     * @throws TransportExceptionInterface
     */
    public function send(AbstractEmail $email, bool $async = true): void
    {
        if ($async) {
            $stamps = [];
            $delay = $email->getDelay();
            if ($delay > 0) {
                $stamps[] = new DelayStamp($delay);
            }
            if ($email->isHighPriority()) {
                $message = new SendHighPriorityEmailMessage($email);
            } else {
                $message = new SendEmailMessage($email);
            }
            $this->messageBus->dispatch($message, $stamps);
        } else {
            $this->mailer->send($email->getMailerEmail($this));
        }
    }

    /**
     * @param string $route
     * @param array $params
     * @return RouterInterface
     */
    public function getRoute(string $route, array $params = []): string
    {
        return $this->router->generate($route, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function getUser(int $id): User
    {
        return $this->userManager->findOrFail($id);
    }

    /**
     * @return array
     */
    public function getBasePlaceholders(): array
    {
        return [
            '$site.name' => $this->siteName,
            '$site.url' => $this->siteUrl,
        ];
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return EmailAddress
     */
    public function getSenderAddress(): EmailAddress
    {
        return (new EmailAddress())
            ->setEmail($this->senderEmail)
            ->setTitle($this->senderTitle);
    }

    /**
     * @param string $template
     * @param array $templateParams
     * @param array $placeholders
     * @return string
     */
    public function render(string $template, array $templateParams = [], array $placeholders = []): string
    {
        try {
            $result = $this->twig->render($template, $templateParams);
            if (!$this->mailer instanceof UnioneMailer) {
                $result = $this->applySubstitutions($result, $placeholders);
            }
        } catch (LoaderError | RuntimeError | SyntaxError $ex) {
            throw new LogicException('Произошла ошибка при формировании шаблона письма: ' . $ex->getMessage() . $ex->getTraceAsString());
        }
        return $result;
    }

    /**
     * @param string $data
     * @param array $placeholders
     * @return string
     */
    private function applySubstitutions(string $data, array $placeholders): string
    {
        return str_replace(array_keys($placeholders), array_values($placeholders), $data);
    }

    /**
     * @param User $user
     * @throws TransportExceptionInterface
     */
    public function sendConfirmRegistrationEmail(User $user): void
    {
        $email = (new CustomUserEmail())
            ->setUser($user)
            ->setTemplate('_emails/email-confirm.html.twig')
            ->setSubject('Подтвердите Ваш Email')
            ->setHighPriority(true)
            ->setSubstitutions([
                '$confirm.url' => $this->getRoute(
                    'email_confirm',
                    [
                        'uuid' => $user->getUuid()
                    ]
                )
            ]);
        $this->send($email);
    }

    /**
     * @param PasswordResetRequest $request
     * @param bool $async
     * @throws TransportExceptionInterface
     */
    public function sendResetPasswordEmail(PasswordResetRequest $request, bool $async = true): void
    {
        $email = (new CustomUserEmail())
            ->setTemplate('_emails/password-reset.html.twig')
            ->setSubject('Сброс пароля')
            ->setUser($request->getUser())
            ->setHighPriority(true)
            ->setSubstitutions(
                [
                    '$reset.url' => $this->getRoute(
                        'password_reset', [
                            'id' => $request->getUser()->getId(),
                            'token' => $request->getResetToken(),
                        ]
                    ),
                ]
            );

        $this->send($email, $async);
    }

}
