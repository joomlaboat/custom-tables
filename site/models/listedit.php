<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\DataTypes\Tree;

jimport('joomla.application.component.model');

class CustomTablesModelListEdit extends JModel
{
	var $imagefolder="images/esoptimages";
	
    function __construct()
    {
		parent::__construct();
		
		$jinput = JFactory::getApplication()->input;
		$array = $jinput->get('cid',array(),'array');

		$this->setId((int)$array[0]);
    }

	function setId($tree_id)
	{
		// Set id and wipe data

		$this->_id	= $tree_id;
		$this->_data	= null;
	}

	function getData()
	{
		$row = $this->getTable();
		$row->load( $this->_id );
		return $row;
	}

	function store()
	{
		$jinput = JFactory::getApplication()->input;
		
	    $optionname=strtolower(trim(preg_replace("/[^a-zA-Z0-9]/", "", JFactory::getApplication()->input->get('optionname','','STRING'))));
		$title=ucwords(strtolower(trim(JFactory::getApplication()->input->get('title','','STRING'))));
		
		JFactory::getApplication()->input->set('optionname',$optionname);
		JFactory::getApplication()->input->set('title',$title);
	
		//save image if needed
		$fieldname='imagefile';
		$value=0;
		$imagemethods=new CustomTablesImageMethods;
		$tree_id=JFactory::getApplication()->input->get('id',0,'INT');
		$imagefolder=JPATH_SITE.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'esoptimages';
		
		$imageparams='';
		
		
		if($tree_id==0)
			$file = $jinput->input->getVar($fieldname, '', 'files', 'array');

			$filename=$file['name'];
			if($filename!='')
			{
				$imageparams=$jinput->get('imageparams','','string');
				
				if(strlen($imageparams)==0)
					$imageparams=Tree::getHeritageInfo(JFactory::getApplication()->input->get('parentid',0,'INT'), 'imageparams');
			
				$value=$imagemethods->UploadSingleImage(0, $fieldname,$imagefolder,$imageparams,'-options');
			}
			
		else
		{
			
			$ExistingImage=Tree::isRecordExist($tree_id,'id', 'image', '#__customtables_options');
			$file = $jinput->getVar($fieldname, '', 'files', 'array');
			
			$filename=$file['name'];
			if($filename=='')
			{
				if($jinput->getCmd('image_delete')=='true')
				{
					if($ExistingImage>0)
					$imagemethods->DeleteExistingSingleImage($ExistingImage,$imagefolder,$imageparams,'-options',$fieldname);
									
					$savequery[]=''.$fieldname.'='.$value;
				
				}
			}
			else
			{
				$imageparams=$jinput->getString('imageparams');
				if(strlen($imageparams)==0)
					$imageparams=Tree::getHeritageInfo($jinput->get('parentid',0,'INT'), 'imageparams');
					
				$value=$imagemethods->UploadSingleImage($ExistingImage, $fieldname,$imagefolder,$imageparams,'-options');
			}
		}	
		if($value!=0)
			$jinput->set('image',$value);
			
			
		$row = $this->getTable();
		// consume the post data with allow_html
		
		$data = $jinput->get( 'jform',array(),'ARRAY');
		
		
		$post = array();

		if (!$row->bind($data))
		{
			return false;
		}

		// Make sure the  record is valid
		if (!$row->check())
		{
			return false;
		}

		// Store
		if (!$row->store())
		{
			return false;
		}
		$tree_id=$row->id;
		//set FamilyTree
		$row = $this->getTable();
		// Make sure the  record is valid
		$row->load( $tree_id );
	
		// Store
		$row->familytree='-'.Tree::getFamilyTree($tree_id,0).'-';
		$familytreestr=Tree::getFamilyTreeString($tree_id,0);
		if($familytreestr!='')
			$row->familytreestr=','.$familytreestr.'.'.$row->optionname.'.';
		else
			$row->familytreestr=','.$row->optionname.'.';
		
		
		if (!$row->store())
		{
			return false;
		}


		return true;
	}

	function delete()
	{
		$jinput = JFactory::getApplication()->input;
		
		$cids = $jinput->get->post('cid',array(),'array');
		$row = $this->getTable();

		if (count( $cids ))
		{
			foreach($cids as $cid)
			{
				if (!$row->delete( $cid ))
				{
					return false;
				}
			}
		}
		return true;
	}
}
