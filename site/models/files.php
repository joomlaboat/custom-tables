<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');
//jimport('joomla.application.component.controller');

//JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fields.php');
/*
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

require_once (JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');



require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'filtering.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layouts.php');
*/
class CustomTablesModelFiles extends JModelLegacy
{

	var $esTable;
	var $establename;

	var $estableid;
	var $tablerow;
	var $esfields;
	var $Itemid;

	var $esfieldid;
	var $fieldrow;
	var $security;
	var $key;

	function __construct()
	{
		parent::__construct();

		$app		= JFactory::getApplication();
		$this->params=$app->getParams();

		$jinput=JFactory::getApplication()->input;
		$id= $jinput->getInt('listing_id', 0);

		$this->Itemid=$jinput->getInt('Itemid',0);
		$this->estableid=$jinput->getInt('tableid',0);
		$this->esfieldid = JFactory::getApplication()->input->getInt('fieldid', 0);
		$this->security = JFactory::getApplication()->input->getCmd('security', 'd');
		$this->key = JFactory::getApplication()->input->getCmd('key','');

		if($id==0 or $this->esfieldid==0 or $this->estableid==0)
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

		$this->esTable=new ESTables;


		$this->setId($id);

		$this->tablerow = ESTables::getTableRowByIDAssoc($this->estableid);

		if(!isset($this->tablerow['id']))
		{
			JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
			return;
		}

		$this->establename=$this->tablerow['tablename'];

		//	Fields
		$this->esfields = ESFields::getFields($this->estableid);

		foreach($this->esfields as $f)
		{
			if($f['id']==$this->esfieldid)
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

		$tablename='#__customtables_table_'.$this->establename;

		$query = 'SELECT *, id AS listing_id ';
		$query.=' FROM '.$tablename.' WHERE id='.(int)$this->_id.' LIMIT 1';

		$db->setQuery($query);

		if (!$db->query())    die( $db->stderr());

		$rows=$db->loadAssocList();

		if(count($rows)<1)
		{
			$a=array();
			return $a;
		}

		$row=$rows[0];
		$row['listing_id']=$row['id'];

		return $row;
	}


	function getTypeFieldName($type)
	{
		foreach($this->esfields as $ESField)
		{
				if($ESField['type']==$type)
					return $ESField['realfieldname'];
		}

		return '';
	}
}
