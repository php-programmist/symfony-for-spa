<?php


namespace App\Helper;


use Carbon\Carbon;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use IntlDateFormatter;

class DateTimeHelper
{
    const FORMAT_FULL = 'd MMMM y';
    const FORMAT_FROM_FULL = 'd MMMM y';
    const FORMAT_FROM_MONTH = 'd MMMM';
    const FORMAT_FROM_DAY = 'd';
    const FORMAT_FROM_NONE = '';
    const FORMAT_TIME = 'HH:mm';
    const FORMAT_FROM_TIME = 'HH:mm - ';
    const FORMAT_MONTH_NAME_FULL = 'LLLL';
    const DEFAULT_TIME_ZONE = 'Europe/Moscow';
    const BUSINESS_HOURS_FROM = 8;
    const BUSINESS_HOURS_TO = 21;

    const DATE_DELIMITER = '–';

    /**
     * @param DateTimeInterface|null $fromDate
     * @param DateTimeInterface|null $toDate
     * @param string|null $format
     * @param string|null $delimiter
     * @return string
     */
    public static function getPeriodTitle(
        ?DateTimeInterface $fromDate,
        ?DateTimeInterface $toDate,
        string $format = null,
        string $delimiter = null
    ) {
        if ($fromDate === null || $toDate === null) {
            $date = $fromDate ?? $toDate;
            if ($date === null) {
                $result = '';
            } else {
                $result = self::format($date, $format ?? self::FORMAT_FULL);
            }
        } else {
            if ($toDate->format('y') > $fromDate->format('y')) {
                $fromFormat = self::FORMAT_FROM_FULL;
            } elseif ($toDate->format('m') > $fromDate->format('m')) {
                $fromFormat = self::FORMAT_FROM_MONTH;
            } elseif ($toDate->format('d') > $fromDate->format('d')) {
                $fromFormat = self::FORMAT_FROM_DAY;
            } else {
                $fromFormat = self::FORMAT_FROM_NONE;
            }
            $fromFormat = $format ?? $fromFormat;
            if (!empty($fromFormat)) {
                $fromFormat = $fromFormat . ' ' . ($delimiter ?? self::DATE_DELIMITER) . ' ';
            }
            $result = self::format($fromDate, $fromFormat) . self::format($toDate, $format ?? self::FORMAT_FULL);
        }
        return $result;
    }

    /**
     * Полное имя месяца в именительном падеже
     * @param DateTimeInterface $date
     * @return string
     */
    public static function monthName(DateTimeInterface $date): string
    {
        return self::format($date, self::FORMAT_MONTH_NAME_FULL);
    }

    /**
     * @param DateTimeInterface $date
     * @param string $format
     * @param string|null $timezone
     * @return string
     */
    public static function format(DateTimeInterface $date, string $format, string $timezone = null): string
    {
        if (!empty($format)) {
            /** @noinspection PhpUndefinedClassInspection */
            $IntlDateFormatter = new IntlDateFormatter(
                'ru_RU',
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $timezone,
                null,
                $format
            );
            $result = $IntlDateFormatter->format($date);
            //Если не получилось, то делаем обычное форматирование
            if ($result === false) {
                $result = $date->format('d.m.Y');
            }
        } else {
            $result = '';
        }
        return $result;
    }

    /**
     * @param DateTimeInterface|null $fromTime
     * @param DateTimeInterface|null $toTime
     * @return string
     */
    public static function getTimeTitle(?DateTimeInterface $fromTime, ?DateTimeInterface $toTime)
    {
        if ($fromTime === null || $toTime === null) {
            $time = ($fromTime ?? $toTime);
            if ($time === null) {
                $result = '';
            } else {
                $result = self::format($time, self::FORMAT_TIME);
            }
        } else {
            if ($fromTime->format('H:i') === $toTime->format('H:i')) {
                $fromFormat = self::FORMAT_FROM_NONE;
            } else {
                $fromFormat = self::FORMAT_FROM_TIME;
            }
            $result = self::format($fromTime, $fromFormat) . self::format($toTime, self::FORMAT_TIME);
        }
        return $result;
    }

    /**
     * @param DateTimeInterface|null $toTime
     * @param DateTimeInterface|null $fromTime
     * @return int
     */
    public static function getDiffInSeconds(?DateTimeInterface $toTime, ?DateTimeInterface $fromTime = null): int
    {
        if (null === $toTime) {
            return 0;
        }
        if (null === $fromTime) {
            $fromTime = Carbon::now(self::DEFAULT_TIME_ZONE);
        }
        return $toTime->getTimestamp() - $fromTime->getTimestamp();
    }

    /**
     * @param string $interval - example: '+1 hour' or 'tomorrow'
     * @param string $timeZone - example: 'Europe/Moscow'
     * @param bool $businessHours - if true, date will be in Business Hours interval
     * @return DateTimeInterface
     * @throws Exception
     */
    public static function getDateByInterval(
        string $interval,
        string $timeZone,
        bool $businessHours = false
    ): DateTimeInterface {
        $date = Carbon::now($timeZone);
        $interval = DateInterval::createFromDateString($interval);

        $date->add($interval);
        if ($businessHours) {
            $hour = (int)$date->format('H');
            if ($hour >= self::BUSINESS_HOURS_TO || $hour < self::BUSINESS_HOURS_FROM) {
                return self::getNextHour(self::BUSINESS_HOURS_FROM, $timeZone, $date);
            }
        }

        return $date;
    }

    /**
     * @param int $hour
     * @param string $timeZone
     * @param DateTimeInterface|null $fromDate
     * @return DateTimeInterface
     * @throws Exception
     */
    public static function getNextHour(
        int $hour,
        string $timeZone,
        ?DateTimeInterface $fromDate = null
    ): DateTimeInterface {
        if (null === $fromDate) {
            $fromDate = Carbon::now($timeZone);
        }

        if ($hour < $fromDate->format('H')) {
            try {
                $fromDate->add(new DateInterval('P1D'));
            } catch (Exception $e) {
            }
        }
        $fromDate->setTime($hour, 0);
        return $fromDate;
    }

    /**
     * @param DateTimeInterface|null $firstDate
     * @param DateTimeInterface|null $secondDate
     * @return bool
     */
    public static function isDatesSame(?DateTimeInterface $firstDate, ?DateTimeInterface $secondDate): bool
    {
        return
            ($firstDate === null ? $firstDate : $firstDate->format('Y-m-d'))
            ===
            ($secondDate === null ? $secondDate : $secondDate->format('Y-m-d'));
    }

    /**
     * @param DateTimeInterface|null $firstDate
     * @param DateTimeInterface|null $secondDate
     * @return bool
     */
    public static function isTimesSame(?DateTimeInterface $firstDate, ?DateTimeInterface $secondDate): bool
    {
        return
            ($firstDate === null ? $firstDate : $firstDate->format('H:i:s'))
            ===
            ($secondDate === null ? $secondDate : $secondDate->format('H:i:s'));
    }

    /**
     * @param DateTimeInterface|null $firstDate
     * @param DateTimeInterface|null $secondDate
     * @return bool
     */
    public static function isSequentialDay(?DateTimeInterface $firstDate, ?DateTimeInterface $secondDate): bool
    {
        $interval = new DateInterval('P1D');
        if (null === $firstDate || null === $secondDate) {
            return false;
        }
        $firstDateNextDay = DateTime::createFromFormat(
            DateTimeInterface::ATOM,
            $firstDate->format(DateTimeInterface::ATOM)
        );
        $firstDateNextDay->add($interval);
        return self::isDatesSame($firstDateNextDay, $secondDate);
    }

    /**
     * Возвращает количество миллисекунд до указанных даты и времени
     * @param DateTimeInterface $endDate
     * @param DateTimeInterface|null $endTime
     * @param DateTimeZone $timezone
     * @return int
     * @throws Exception
     */
    public static function getDelayToEnd(
        DateTimeInterface $endDate,
        ?DateTimeInterface $endTime,
        DateTimeZone $timezone
    ): int {
        /** @noinspection PhpUnhandledExceptionInspection */
        $endDateTime = new DateTime($endDate->format('Y-m-d'), $timezone);
        if ($endTime !== null) {
            $endDateTime->modify($endTime->format('H:i:s'));
        } else {
            $endDateTime->setTime(0, 0, 0);
        }
        return self::getDiffInSeconds($endDateTime) * 1000;
    }

    /**
     * Возвращает количество секунд до начала указанного часа
     * @param int $hour
     * @param string $timeZone
     * @return int
     * @throws Exception
     */
    public static function getSecondsToNextHour(int $hour, string $timeZone)
    {
        $userTimezone = new DateTimeZone(self::DEFAULT_TIME_ZONE);
        $fromTime = new DateTime('NOW', $userTimezone);
        $toTime = self::getNextHour($hour, $timeZone);
        return self::getDiffInSeconds($toTime, $fromTime);
    }


    /**
     * @param DateTimeInterface $period
     * @return bool
     */
    public static function isPeriodPassed(DateTimeInterface $period): bool
    {
        return $period < new DateTime();
    }


    /**
     * @return DateTime
     */
    public static function nowMidnight(): DateTime
    {
        return (new DateTime())->setTime(0, 0, 0);
    }


    /**
     * @param string $interval - example: '+1 hour' or 'tomorrow'
     * @param string $timeZone - example: 'Europe/Moscow'
     * @param bool $businessHours - if true, date will be in Business Hours interval
     * @return int - задержка до начала бизнес-часов в миллисекундах
     * @throws Exception
     */
    public static function getDelayByInterval(
        string $interval,
        string $timeZone,
        bool $businessHours = false
    ): int {
        $dateTime = self::getDateByInterval($interval, $timeZone, $businessHours);
        $delay = self::getDiffInSeconds($dateTime);
        if ($delay < 0) {
            return 0;
        }
        return $delay * 1000;
    }
}