<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage integrity/fields.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
namespace CustomTables\Integrity;
 
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;

use \Joomla\CMS\Factory;

use \ESTables;

class IntegrityFieldType_Gallery extends \CustomTables\IntegrityChecks
{
	public static function checkGallery($gallery_table_name,&$languages,$tablename,$fieldname)
	{
	}
}