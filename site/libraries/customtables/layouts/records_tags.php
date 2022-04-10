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
	
	function count()
	{
		//{{ records.count }}
		
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.count }} - Table not loaded.', 'error');
			return '';
		}
		
		return $ct->Table->recordcount;
	}
		
	protected function id_list()
	{
		if($this->ct->Env->frmt == 'csv')
			return '';	
			
		if(!isset($this->ct->Table))
		{
			Factory::getApplication()->enqueueMessage('{{ record.list }} - Table not loaded.', 'error');
			return '';
		}
		
		if(!isset($this->ct->Records))
		{
			Factory::getApplication()->enqueueMessage('{{ record.list }} - Records not loaded.', 'error');
			return '';
		}
		
		if($this->ct->Table->recordlist == null)
			$this->ct->getRecordList();
		
		return implode(',',$this->ct->Table->recordlist);
	}
	
	function list($layoutname = '')
	{
		//Example {{ records.list("InvoicesPage") }}
		
		if($layoutname == '')
			return $this->id_list();
		
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
	
	
	function htmltable(array $fields = [])
	{
		// {{ records.htmltable([['column_1_title','column_1_value'],['column_1_title','column_1_value']]) }}
		if(count($fields) == 0)
		{
			Factory::getApplication()->enqueueMessage('{{ records.htmltable([]) }} - List of fields not set', 'error');
			return '';
		}
		
		print_r($fields);
		
		return 'LOL';
		$layouts = new Layouts($this->ct);
		
		$pagelayout = $layouts->getLayout($layoutname,false);//It is safier to process layout after rendering the table
		if($layouts->tableid == null)
		{
			Factory::getApplication()->enqueueMessage('{{ records.htmltable("'.$layoutname.'","'.$filter.'","'.$orderby.'") }} - Layout "'.$layoutname.' not found.', 'error');
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