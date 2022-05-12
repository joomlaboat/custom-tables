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

class CTStatusField extends PredefinedlistField
{
	public $type = 'CTStatus';

	protected $predefinedOptions = array(
		-2   => 'JTRASHED',
		0   => 'JUNPUBLISHED',
		1   => 'JPUBLISHED',
	);
}
