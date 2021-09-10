<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
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
