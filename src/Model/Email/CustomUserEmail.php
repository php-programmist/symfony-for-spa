<?php


namespace App\Model\Email;


use App\Exception\Storage\JsonDecodeException;
use App\Exception\User\UserNotFoundException;
use App\Service\EmailManager;

class CustomUserEmail extends AbstractToUserEmail
{
    public const ALIAS = 'custom.user.email';

    protected ?string $template;

    /**
     * @inheritDoc
     */
    public function getAlias(): string
    {
        return self::ALIAS;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                'template' => $this->getTemplate(),
                'templateParams' => $this->getTemplateParams(),
                'subject' => $this->subject,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * @param string|null $template
     * @return CustomUserEmail
     */
    public function setTemplate(?string $template): CustomUserEmail
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string|null $subject
     * @return CustomUserEmail
     */
    public function setSubject(?string $subject): CustomUserEmail
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param array $data
     * @return AbstractEmail
     * @throws JsonDecodeException
     */
    public function jsonDeserialize(array $data): AbstractEmail
    {
        parent::jsonDeserialize($data);

        $this
            ->setTemplate($data['template'])
            ->setTemplateParams($data['templateParams'])
            ->setSubject($data['subject']);
        return $this;
    }

    /**
     * @param EmailManager $manager
     * @return array
     * @throws UserNotFoundException
     */
    protected function getPlaceholders(EmailManager $manager): array
    {
        if (null === $this->getUserId()) {
            return $this->getSubstitutions();
        }
        return parent::getPlaceholders($manager);
    }
}
