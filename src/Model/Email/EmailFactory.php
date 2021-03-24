<?php


namespace App\Model\Email;


use App\Exception\Storage\JsonDecodeException;

class EmailFactory
{

    /**
     * @param array $data
     * @return AbstractEmail
     * @throws JsonDecodeException
     */
    public static function createFromJsonData(array $data): AbstractEmail
    {
        $alias = $data[AbstractEmail::SERIALIZE_KEY_ALIAS] ?? '';

        if (empty($alias)) {
            throw new JsonDecodeException('Не найдена информация о типе письма в сериализованных данных');
        }

        switch ($alias) {
            case CustomUserEmail::ALIAS:
                $result = new CustomUserEmail();
                break;
            default:
                throw new JsonDecodeException("Неизвестный тип письма ({$alias}) в сериализованных данных.");
        }

        $result->jsonDeserialize($data);

        return $result;
    }
}