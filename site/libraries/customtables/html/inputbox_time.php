<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

use DateInterval;
use DateTimeImmutable;
use Exception;

if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

class InputBox_Time extends BaseInputBox
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
		try {
			$from = self::durationToSeconds($params[0]);

			/*
			if (isset($params[1]))
				$to = self::durationToSeconds($params[1]);
			else
				$to = 3600 * 24;//24 hours

			if (isset($params[2]))
				$step = self::durationToSeconds($params[2]);
			else
				$step = 3600;//1 hour
			*/

			if (isset($params[3]))
				$ticks = self::durationToSeconds($params[3]);
			else
				$ticks = 1;//1 second

			if (isset($params[4]))
				$offset = self::durationToSeconds($params[4]);
			else
				$offset = 0;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		return (int)$number_of_ticks * $ticks + $from - $offset;
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

	function render_time(?string $value, ?string $defaultValue): string
	{
		if ($value === null) {
			$value = common::inputGetCmd($this->ct->Env->field_prefix . $this->field->fieldname);

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
		$options = array(array('id' => '', 'name' => '- ' . common::translate('COM_CUSTOMTABLES_SELECT')));

		try {
			$from = self::durationToSeconds($this->field->params[0]);

			if (isset($params[1]))
				$to = self::durationToSeconds($params[1]);
			else
				$to = 3600 * 24;//24 hours

			if (isset($params[2]))
				$step = self::durationToSeconds($params[2]);
			else
				$step = 3600;//1 hour

			if (isset($params[3]))
				$ticks = self::durationToSeconds($params[3]);
			else
				$ticks = 1;//1 second

			if (isset($params[4]))
				$offset = self::durationToSeconds($params[4]);
			else
				$offset = 0;
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		$format = '';
		if (isset($option_list[2]))
			$format = $option_list[2];

		for ($i = $from; $i < $to + $step; $i += $step) {
			$tick = floor((($i) - $from + $offset) / $ticks);
			$options[] = (object)(array('id' => strval($tick), 'name' => self::seconds2FormattedTime($i, $format)));
		}
		return $this->renderSelect(strval($value ?? ''), $options);
	}

	public static function seconds2FormattedTime($seconds, $format = ''): string
	{
		date_default_timezone_set('UTC');

		if ($format != '')
			return date($format, $seconds);
		else
			return date('H:i:s', $seconds);
	}
}