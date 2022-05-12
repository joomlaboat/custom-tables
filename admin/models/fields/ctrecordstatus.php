<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace Joomla\CMS\Form\Field;

\defined('JPATH_PLATFORM') or die;

class CTRecordStatusField extends PredefinedlistField
{
	public $type = 'CTRecordStatus';

	protected $predefinedOptions = array(
		0   => 'JUNPUBLISHED',
		1   => 'JPUBLISHED',
	);
}
