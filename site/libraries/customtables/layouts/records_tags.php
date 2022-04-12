<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;
 
// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Tables;
use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\SearchInputBox;

use \JoomlaBasicMisc;
use \ESTables;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;

class Twig_Records_Tags
{
	var $ct;

	function __construct(&$ct)
	{
		$this->ct = $ct;
	}
	
	function count()//wizard ok
	{
		//{{ records.count }}
		
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ records.count }} - Table not loaded.', 'error');
			return '';
		}
		
		return $this->ct->Table->recordcount;
	}
		
	function list($layoutname = '')//wizard ok
	{
		//Example {{ records.list("InvoicesPage") }}
		
		$layouts = new Layouts($this->ct);
		
		$pagelayout = $layouts->getLayout($layoutname,false);//It is safier to process layout after rendering the table
		if($layouts->tableid == null)
		{
			Factory::getApplication()->enqueueMessage('{{ records.record("'.$layoutname.'") }} - Layout "'.$layoutname.' not found.', 'error');
			return '';
		}
		
		$number = 0;
		$twig = new TwigProcessor($this->ct, '{% autoescape false %}'.$pagelayout.'{% endautoescape %}');
		
		$htmlresult = '';
		
		foreach($this->ct->Records as $row)
		{
			$row['_number'] = $number;
			$htmlresult .= $twig->process($row);
			$number++;
		}
		
		//return $htmlresult;		
		return new \Twig\Markup($htmlresult, 'UTF-8' );
	}
}