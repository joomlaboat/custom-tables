<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die();

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Exception;

class InputBox_time extends BaseInputBox
{
    function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
    {
        parent::__construct($ct, $field, $row, $option_list, $attributes);
    }

    /**
     * @throws Exception
     * @since 3.2.0
     */
    public static function ticks2Seconds($number_of_ticks, array $params): int
    {
        $parameters = self::getParameters($params);
        return (int)$number_of_ticks * $parameters['ticks'] + $parameters['from'] - $parameters['offset'];
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    protected static function getParameters(array $params): array
    {
        try {
            $from = self::durationToSeconds($params[0]); // Min (From) Time. Example: 0h

            if (isset($params[1]))
                $to = self::durationToSeconds($params[1]);
            else
                $to = 3600 * 24;//24 hours

            if (isset($params[2]))
                $step = self::durationToSeconds($params[2]);
            else
                $step = 3600;//1 hour

            if (isset($params[3]))
                $ticks = self::durationToSeconds($params[3]); // Save Ticks. Example: 1s;
            else
                $ticks = 1; // 1 second

            if (isset($params[4]))
                $offset = self::durationToSeconds($params[4]); // Save Tick Offset. Example: 0
            else
                $offset = 0;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return ['from' => $from, 'to' => $to, 'step' => $step, 'ticks' => $ticks, 'offset' => $offset];
    }

    /**
     * @throws Exception
     * @since 3.2.0
     */
    protected static function durationToSeconds(string $duration): int
    {
        if ($duration == '' or $duration == '0')
            $duration = '0h';

        try {
            $interval = new DateInterval('PT' . strtoupper($duration));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $reference = new DateTimeImmutable;
        $endTime = $reference->add($interval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

    /**
     * @throws Exception
     * @since 3.2.0
     */
    public static function seconds2Ticks($number_of_seconds, array $params): int
    {
        $parameters = self::getParameters($params);

        // Calculate the ticks based on the given seconds, min time, and offsets.
        $calculated_ticks = (($number_of_seconds - $parameters['from'] + $parameters['offset']) / $parameters['ticks']);

        return (int)$calculated_ticks;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function formattedTime2Seconds($formatted_time, $format = ''): int
    {
        common::default_timezone_set();

        if ($format === '') {
            // Guess the format if not provided
            $format = self::guessTimeFormat($formatted_time);
            if ($format === null) {
                return -1; // Unable to guess format, return -1 for invalid input
            }
        }

        try {
            $time = DateTime::createFromFormat($format, $formatted_time);
            if ($time === false) {
                //throw new Exception('Invalid formatted time or format.');
                return -1;
            }

            $startOfDay = new DateTime('00:00:00');
            $seconds = $time->getTimestamp() - $startOfDay->getTimestamp();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return (int)$seconds;
    }

    /**
     * @throws Exception
     * @since 3.2.2
     */
    public static function guessTimeFormat($time_string): ?string
    {
        // Commonly used time formats to check against
        $formats = [
            'H:i:s', 'H:i', 'H', 'h:i:s A', 'h:i A', 'h A',
            'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d H', 'Y-m-d',
            'm/d/Y H:i:s', 'm/d/Y H:i', 'm/d/Y H', 'm/d/Y',
            // Add more formats as needed based on your data
        ];

        foreach ($formats as $format) {
            $parsed_time = DateTime::createFromFormat($format, $time_string);
            if ($parsed_time !== false && $parsed_time->format($format) === $time_string) {
                return $format;
            }
        }
        return null; // Return null if no format matches
    }

    function render(?string $value, ?string $defaultValue): string
    {
        if ($value === null) {
            $value = common::inputGetCmd($this->ct->Table->fieldPrefix . $this->field->fieldname);

            if ($value === null)
                $value = $defaultValue;
        }

        self::selectBoxAddCSSClass($this->attributes, $this->ct->Env->version);

        try {
            return $this->do_render((int)$value);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @throws Exception
     * @since 3.2.0
     */
    protected function do_render(int $value): string
    {
        $options = [];
        $parameters = self::getParameters($this->field->params);

        $format = ($this->option_list[2] ?? '') === '' ? 'H:i:s' : $this->option_list[2];

        for ($i = $parameters['from']; $i < $parameters['to'] + $parameters['step']; $i += $parameters['step']) {
            $tick = floor((($i) - $parameters['from'] + $parameters['offset']) / $parameters['ticks']);
            $options[] = (object)(array('id' => strval($tick), 'name' => self::seconds2FormattedTime($i, $format)));
        }
        return $this->renderSelect(strval($value ?? ''), $options);
    }

    public static function seconds2FormattedTime(int $seconds, ?string $format = null): string
    {
        return common::formatDate((string)$seconds, $format);
    }
}