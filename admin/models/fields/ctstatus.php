<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

namespace Joomla\CMS\Form\Field;

\defined('JPATH_PLATFORM') or die;

class CTStatusField extends PredefinedlistField
{
	public $type = 'CTStatus';

	protected $predefinedOptions = array(
		-2   => 'JTRASHED',
		0   => 'JUNPUBLISHED',
		1   => 'JPUBLISHED',
	);
}
