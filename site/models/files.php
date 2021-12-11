<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

jimport('joomla.application.component.model');

class CustomTablesModelFiles extends JModelLegacy
{
	var $ct;
	var $tableid;
	var $fieldid;
	var $fieldrow;
	var $security;
	var $key;

	function __construct()
	{
		$path = JPATH_COMPONENT_SITE . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'customtables' . DIRECTORY_SEPARATOR;
		require_once($path.'loader.php');
		CTLoader();
		
		$this->ct = new CT;

		parent::__construct();

		$app		= JFactory::getApplication();
		$this->params=$app->getParams();

		$jinput=JFactory::getApplication()->input;
		$id= $jinput->getInt('listing_id', 0);

		$this->tableid = $jinput->getInt('tableid',0);
		$this->fieldid = $jinput->getInt('fieldid',0);
		
		
		$this->security = $jinput->getCmd('security', 'd');
		$this->key = $jinput->getCmd('key','');

		if($id==0 or $this->fieldid==0)
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');

			$this->_id=0;
			return false;
		}

		$this->load($id);

	}

	function load($id)
	{
		if($id==0)
			return false;

		$jinput=JFactory::getApplication()->input;

		$this->tableid = $jinput->getInt('tableid',0);
		$this->ct->getTable($this->tableid, null);
				
		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected (79).', 'error');
			return;
		}

		$this->setId($id);

		foreach($this->ct->Table->fields as $f)
		{
			if($f['id']==$this->fieldid)
			{
				$this->fieldrow=$f;
				break;
			}
		}
	}

	function setId($id)
	{
		$this->_id	= $id;
		$this->_data	= null;
	}



	function & getData()
	{
		if($this->_id==0)
		{
			$row=array();
			return $row;
		}

		$db = JFactory::getDBO();

		$query = 'SELECT *, id AS listing_id ';
		$query.=' FROM '.$this->ct->Table->realtablename.' WHERE id='.(int)$this->_id.' LIMIT 1';

		$db->setQuery($query);

		$rows=$db->loadAssocList();

		if(count($rows)<1)
		{
			$a=array();
			return $a;
		}

		$row=$rows[0];
		return $row;
	}


	function getTypeFieldName($type)
	{
		foreach($this->ct->Table->fields as $ESField)
		{
				if($ESField['type']==$type)
					return $ESField['realfieldname'];
		}

		return '';
	}
}
