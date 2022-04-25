<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Email;
use CustomTables\Fields;
use CustomTables\Layouts;
use CustomTables\DataTypes\Tree;
use CustomTables\CustomPHP\CleanExecute;
use CustomTables\TwigProcessor;
use CustomTables\SaveFieldQuerySet;

use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Factory;

jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');

$site_libpath=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
require_once($site_libpath.'layout.php');

$libpath=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR;
require_once($libpath.'valuetags.php');

class CustomTablesModelEditItem extends JModelLegacy
{
	var $ct;
	var $itemaddedtext;
	var $onrecordaddsendemailto;
	var $onrecordaddsendemail;
	var $sendemailcondition;
	var $emailsentstatusfield;

	var $layout_catalog;
	var $layout_details;
	var $listing_id;

	var $isAutorized;
	var $guestcanaddnew;
	var $pagelayout;
	var $msg_itemissaved;

	var $userGroup;
	var $edit_userGroup;
	var $publish_userGroup;
	var $delete_userGroup;
	var $add_userGroup;

	var $useridfield;
	var $useridfield_uniqueusers;
	var $isUserAdministrator;
	
	var $params;

	var $BlockExternalVars;
	var $advancedtagprocessor;
	
	var $row;

	function __construct()
	{
		$this->ct = new CT;
		parent::__construct();
	}
	
	function getParam_safe($param)
	{
		return $this->params->get($param);
	}
	
	function setFrmt($frmt)
	{
		$this->ct->Env->frmt=$frmt;
	}

	function load($params,$BlockExternalVars=false)
	{
		if(isset($params))
			$this->params = $params;
		else
		{
			$app = JFactory::getApplication();
			$this->params = $app->getParams();
		}
		
		$this->ct->Env->menu_params = $this->params;

		$jinput=JFactory::getApplication()->input;

		if($this->getParam_safe( 'customitemid' )!='')
		{
			$forceitemid = $this->getParam_safe( 'customitemid' );
			
			//Find Itemid by alias
			if(((int)$forceitemid)>0)
				$this->ct->Env->Itemid=$forceitemid;
			else
			{
				if($forceitemid!=0)
					$this->ct->Env->Itemid=(int)JoomlaBasicMisc::FindItemidbyAlias($forceitemid);//Accepts menu Itemid and alias
				else
					$this->ct->Env->Itemid=$this->ct->Env->jinput->getInt('Itemid');
			}
		}
		else
			$this->ct->Env->Itemid = $this->ct->Env->jinput->getInt('Itemid');

		$this->BlockExternalVars=$BlockExternalVars;

		$this->useridfield=$this->getParam_safe('useridfield');
		$this->useridfield_unique=false;
		
		$this->edit_userGroup=(int)$this->getParam_safe( 'editusergroups' );
		$this->publish_userGroup=(int)$this->getParam_safe( 'publishusergroups' );
		if($this->publish_userGroup==0)
			$this->publish_userGroup=$this->edit_userGroup;

		$this->delete_userGroup=(int)$this->getParam_safe( 'deleteusergroups' );
		if($this->delete_userGroup==0)
			$this->delete_userGroup=$this->edit_userGroup;

		$this->add_userGroup=(int)$this->getParam_safe( 'addusergroups' );
		if($this->add_userGroup==0)
			$this->add_userGroup=$this->add_userGroup;


		$tablename_or_id_not_sanitized = $this->getParam_safe( 'establename' );
		
		if($tablename_or_id_not_sanitized == null or $tablename_or_id_not_sanitized == '')
			$tablename_or_id_not_sanitized = $this->getParam_safe( 'tableid' ); //Used in back-end
		
		$this->ct->getTable($tablename_or_id_not_sanitized, $this->useridfield);

		if($this->ct->Table->tablename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected (148).', 'error');
			return false;
		}

		$this->applybuttontitle=$this->getParam_safe( 'applybuttontitle' );
		$this->guestcanaddnew=$this->getParam_safe( 'guestcanaddnew' );

		if($this->params->get( 'msgitemissaved' ))
			$this->msg_itemissaved=$this->getParam_safe( 'msgitemissaved' );
		else
			$this->msg_itemissaved=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED' );

		$this->onrecordaddsendemailto=$this->getParam_safe('onrecordaddsendemailto');
		$this->onrecordsavesendemailto=$this->getParam_safe('onrecordsavesendemailto');

		$this->onrecordaddsendemail=(int)$this->getParam_safe('onrecordaddsendemail');
		$this->sendemailcondition=$this->getParam_safe('sendemailcondition');
		$this->emailsentstatusfield=$this->getParam_safe('emailsentstatusfield');

		$this->findUserIDField();

		if($this->ct->Env->jinput->getInt('moduleid'))
		{
			
		}
		else
		{
			if($this->getParam_safe('eseditlayout')!='')
			{
				$Layouts = new Layouts($this->ct);
				$this->pagelayout = $Layouts->getLayout($this->getParam_safe('eseditlayout'));
			}
			else
				$this->pagelayout='';
		}

		$this->layout_catalog='';
		$this->layout_details='';

		$this->onrecordaddsendemaillayout=$this->getParam_safe('onrecordaddsendemaillayout');

		if($this->getParam_safe('listingid')!=0)
		{
			if($this->listing_id==0)
				$this->listing_id=$this->getParam_safe('listingid');
			
			$this->processCustomListingID();
		}
		elseif(!$BlockExternalVars and $jinput->getInt('listing_id',0)!=0)
		{
			$this->listing_id = $jinput->getCmd('listing_id',0);
			$this->processCustomListingID();
		}
			
		if($this->listing_id==0 and $this->useridfield_uniqueusers and $this->useridfield!='')
		{
			//try to find record by userid
			$this->listing_id=$this->findRecordByUserID();
		}
		
		if(isset($this->row))
			$this->getSpecificVersionIfSet();
		else
		{
			//default record values
			$this->row = ['listing_id' => 0,'listing_published' =>0];
		}
		
		return true;
	}

	function findRecordByUserID()
	{
		$db = JFactory::getDBO();
		$wheres=array();
		
		if($this->ct->Table->published_field_found)
			$wheres[]='published=1';
			
		$wheres_user=$this->UserIDField_BuildWheres($this->useridfield);

		$wheres=array_merge($wheres,$wheres_user);
		
		$query='SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename.' WHERE '.implode(' AND ',$wheres).' LIMIT 1';

		$db->setQuery($query);
		$rows=$db->loadAssocList();
		
		if(count($rows)<1)
		{
			$a=array();//for compatibility
			return $a;
		}

		$this->row=$rows[0];

		return $this->row[$this->ct->Table->realidfieldname];
	}

	function processCustomListingID()
	{
		$db = JFactory::getDBO();

		if(is_numeric($this->listing_id) or (strpos($this->listing_id,'=')===false and strpos($this->listing_id,'<')===false and strpos($this->listing_id,'>')===false))
		{
			$query = 'SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename
				.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($this->listing_id).' LIMIT 1';
				
			$db->setQuery($query);
			$rows=$db->loadAssocList();
			if(count($rows)<1)
				return -1;

			$this->row=$rows[0];

			return $this->listing_id;
		}

		$filter=$this->listing_id;
		if($filter=='')
			return 0;

		$LayoutProc=new LayoutProcessor($this->ct);
		$LayoutProc->layout=$filter;
		$filter=$LayoutProc->fillLayout(array(),null,'[]',true);

		//TODO
		Factory::getApplication()->enqueueMessage('Filtering not done.','error');

		$PathValue=array();
		$paramwhere=$filtering->getWhereExpression($filter,$PathValue);

		if($paramwhere!='')
			$wherearr[]=' ('.$paramwhere.' )';

		if($this->ct->Table->published_field_found)
			$wherearr[]='published=1';

		if(count($wherearr)>0)
			$where = ' WHERE '.implode(" AND ",$wherearr);

		$query = 'SELECT '.$this->ct->Table->realidfieldname.' AS listing_id FROM '.$this->ct->Table->realtablename.' '.$where;

		$query.=' ORDER BY '.$this->ct->Table->realidfieldname.' DESC'; //show last
		$query.=' LIMIT 1';

		$db->setQuery($query);
		$rows=$db->loadAssocList();

		if(count($rows)<1)
		{
			$this->row=array();;
			return 0;
		}

		$this->row=$rows[0];
	}
	
	function getSpecificVersionIfSet()
	{
		//get specific Version if set
		$version= JFactory::getApplication()->input->get('version',0,'INT');
		if($version!=0)
		{
		    //get log field
		    $log_field=$this->getTypeFieldName('log');;
		    if($log_field!='')
		    {
		    	$new_row= $this->getVersionData($this->row,$log_field,$version);
				if(count($new_row)>0)
				{
				    $this->row=$this->makeEmptyRecord($this->listing_id,$new_row['listing_published']);

				    //Copy values
				    foreach($this->ct->Table->fields as $ESField)
						$this->row[$ESField['realfieldname']]=$new_row[$ESField['realfieldname']];
				}
		    }
		}
	}
	
	function makeEmptyRecord($listing_id,$published)
	{
	    $row=array();
	    $row['listing_id'] = $listing_id;
	    
		if($this->ct->Table->published_field_found)
			$row['published']=$published;
		
	    $row['listing_published']=$published;

	    foreach($this->ct->Table->fields as $ESField)
			$row[$ESField['realfieldname']]='';
			
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

	function getVersionData(&$row,$log_field,$version)
	{
		$creation_time_field=$this->getTypeFieldName('changetime');

		$versions=explode(';',$row[$log_field]);
		if($version<=count($versions))
		{
			$data_editor=explode(',',$versions[$version-2]);
			$data_content=explode(',',$versions[$version-1]);

			if($data_content[3]!='')
			{
				//record versions stored in database table text field as base64 encoded json object
				$obj=json_decode(base64_decode($data_content[3]),true);
				$new_row=$obj[0];
						
				if($this->ct->Table->published_field_found)
					$new_row['published']=$row['published'];
							
				$new_row[$this->ct->Table->realidfieldname]=$row[$this->ct->Table->realidfieldname];
				$new_row['listing_id']=$row['listing_id'];
				$new_row[$log_field]=$row[$log_field];

				if($creation_time_field)
				{
					$timestamp = date('Y-m-d H:i:s', (int)$data_editor[0]);
					$new_row[$creation_time_field]=$timestamp ;
				}
				return $new_row;
			}
		}
		return array();
	}

	function CheckAuthorization($action=1)
	{
		if($action==5) //force edit
		{
			$action=1;
		}
		else
		{
			if($action==1 and $this->listing_id==0)
				$action=4; //add new
		}

		$menuparams = JoomlaBasicMisc::getMenuParams($this->ct->Env->Itemid);
		
		$guestcanaddnew = $menuparams->guestcanaddnew ?? 0;

		if($guestcanaddnew==1)
			return true;

		if($guestcanaddnew==-1 and $this->listing_id==0)
		{
			$this->isAutorized=false;
			return false;
		}

		//check is authorized or not
		$user = JFactory::getUser();
		$this->userid = (int)$user->get('id');

		$this->edit_userGroup=(int)$this->params->get( 'editusergroups' );
		$this->publish_userGroup=(int)$this->params->get( 'publishusergroups' );

		if($this->publish_userGroup==0)
			$this->publish_userGroup=$this->edit_userGroup;

		$this->delete_userGroup=(int)$this->params->get( 'deleteusergroups' );
		if($this->delete_userGroup==0)
			$this->delete_userGroup=$this->edit_userGroup;

		$this->add_userGroup=(int)$this->params->get( 'addusergroups' );
		if($this->add_userGroup==0)
			$this->add_userGroup=$this->add_userGroup;

		if($action==1)
			$this->userGroup = $this->edit_userGroup;
		if($action==2)
			$this->userGroup = $this->publish_userGroup;
		if($action==3)
			$this->userGroup = $this->delete_userGroup;
		if($action==4)
			$this->userGroup = $this->add_userGroup;

		$this->isAutorized=false;

		if($this->userid==0)
		{
			$this->isAutorized=false;
			$this->isUserAdministrator=false;
			return false;
		}

		if($this->ct->Env->isUserAdministrator)
		{
			//Administrator has access to anything
			$this->isAutorized=true;
			return true;
		}

		if($this->listing_id==0 or $this->useridfield=='')
		{
			$this->isAutorized=JoomlaBasicMisc::checkUserGroupAccess($this->userGroup);
			return $this->isAutorized;
		}

		$theAnswerIs=false;

		if($this->useridfield!='')
			$theAnswerIs=$this->checkIfItemBelongsToUser($this->useridfield);

		if($theAnswerIs==false)
		{
			$this->isAutorized=JoomlaBasicMisc::checkUserGroupAccess($this->userGroup);
			return $this->isAutorized;
		}

		return true;
	}
	
	function CheckAuthorizationACL($access)
	{
		$this->isAutorized=false;
		$this->isUserAdministrator=false;
		
		if($access=='core.edit' and $this->listing_id==0)
			$access='core.create'; //add new

		$user = JFactory::getUser();

		if ($user->authorise($access, 'com_customtables')) 
		{
			$this->isAutorized=true;
			return true;
		}
		
		if($access!='core.edit')
			return false;

		if($this->useridfield!='')
		{
			if($this->checkIfItemBelongsToUser($this->useridfield))
			{
				if ($user->authorise('core.edit.own', 'com_customtables')) 
				{
					$this->isAutorized=true;
					return true;
				}
			}
		}
		return false;
	}

	function findUserIDField()
	{
		if($this->useridfield!='')
		{
			$useridfields=array();
			$statement_items=tagProcessor_If::ExplodeSmartParams($this->useridfield); //"and" and "or" as separators
			
			foreach($statement_items as $item)
			{
				if($item[0]=='or' or $item[0]=='and')
				{
					$field=$item[1];
					if(strpos($field,'.')===false)
					{
						//Current table field name
						//find selected field
						foreach($this->ct->Table->fields as $esfield)
						{
							if($esfield['fieldname']==$field and ($esfield['type']=='userid' or $esfield['type']=='user'))
							{
								$useridfields[]=[$item[0],$item[1]];
								
								//Following applys to current table fields only and to only one (the last one in the statement)
								$params=$esfield['typeparams'];
								$parts=JoomlaBasicMisc::csv_explode(',', $params, '"', false);

								$this->useridfield_uniqueusers=false;
								if(isset($parts[4]) and $parts[4]=='unique')
									$this->useridfield_uniqueusers=true;
									
								break;
							}
						}
					}
					else
					{
						//Table join
						//parents(children).user
						$useridfields[]=[$item[0],$item[1]];
					}
				}
			}
			
			$useridfields_str='';
			$index=0;
			foreach($useridfields as $field)
			{
				if($index==0)
					$useridfields_str.=$field[1];
				else
					$useridfields_str.=' '.$field[0].' '.$field[1];
				
				$index+=1;
			}
			
			$this->useridfield=$useridfields_str;

			return $this->useridfield;
		}

		return '';
	}

	function UserIDField_BuildWheres($useridfield)
	{
		$wheres=array();
		
		$statement_items=tagProcessor_If::ExplodeSmartParams($useridfield); //"and" and "or" as separators
		
		$wheres_owner=array();

		foreach($statement_items as $item)
		{
			$field=$item[1];
			if(strpos($field,'.')===false)
			{
				//example: user
				//check if the record belong to the current user
				$user_field_row=Fields::FieldRowByName($field,$this->ct->Table->fields);
				$wheres_owner[]=[$item[0],$user_field_row['realfieldname'].'='.$this->ct->Env->userid];
				//$wheres_owner[]=[$item[0],'c.'.$user_field_row['realfieldname'].'='.$this->ct->Env->userid];
			}
			else
			{
				//example: parents(children).user
				$statement_parts=explode('.',$field);
				if(count($statement_parts)!=2)
				{
					JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has a syntax error. Error is about "." character - only one is permited. Correct example: parent(children).user'), 'error');
					return false;
				}
				
				$table_parts=explode('(',$statement_parts[0]);
				if(count($table_parts)!=2)
				{
					JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has a syntax error. Error is about "(" character. Correct example: parent(children).user'), 'error');
					return false;
				}
				
				$parent_tablename=$table_parts[0];
				$parent_join_field=str_replace(')','',$table_parts[1]);
				$parent_user_field=$statement_parts[1];
				
				$parent_table_row=ESTables::getTableRowByName($parent_tablename);

				if(!is_object($parent_table_row))
				{
					JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Table "'.$parent_tablename.'" not found.'), 'error');
					return false;
				}
				
				$parent_table_fields=Fields::getFields($parent_table_row->id);
				
				$parent_join_field_row=Fields::FieldRowByName($parent_join_field,$parent_table_fields);
				
				if(count($parent_join_field_row)==0)
				{
					JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Join field "'.$parent_join_field.'" not found.'), 'error');
					return false;
				}
				
				if($parent_join_field_row['type']!='sqljoin' and $parent_join_field_row['type']!='records')
				{
					JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Wrong join field type "'.$parent_join_field_row['type'].'". Accepted types: "sqljoin" and "records" .'), 'error');
					return false;
				}
				
				//User field
				
				$parent_user_field_row=Fields::FieldRowByName($parent_user_field,$parent_table_fields);

				if(count($parent_user_field_row)==0)
				{
					JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: User field "'.$parent_user_field.'" not found.'), 'error');
					return false;
				}
				
				if($parent_user_field_row['type']!='userid' and $parent_user_field_row['type']!='user')
				{
					JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('Menu Item - "UserID Field name" parameter has an error: Wrong user field type "'.$parent_join_field_row['type'].'". Accepted types: "userid" and "user" .'), 'error');
					return false;
				}

				$parent_wheres=array();
				
				$parent_wheres[]='p.'.$parent_user_field_row['realfieldname'].'='.$this->userid;
				
				if($parent_join_field_row['type']=='sqljoin')
					$parent_wheres[]='p.'.$parent_join_field_row['realfieldname'].'=c.listing_id';
				elseif($parent_join_field_row['type']=='records')
					$parent_wheres[]='INSTR(p.'.$parent_join_field_row['realfieldname'].',CONCAT(",",c.'.$this->ct->Table->realidfieldname.',","))';
				else
					return false;
				
				
				$q='(SELECT p.'.$parent_table_row->realidfieldname.' FROM '.$parent_table_row->realtablename.' AS p WHERE '.implode(' AND ',$parent_wheres).' LIMIT 1) IS NOT NULL';
				
				$wheres_owner[]=[$item[0],$q];
			}
		}
		
		$wheres_owner_str='';
		$index=0;
		foreach($wheres_owner as $field)
		{
			if($index==0)
				$wheres_owner_str.=$field[1];
			else
				$wheres_owner_str.=' '.strtoupper($field[0]).' '.$field[1];
		
			$index+=1;
		}
		
		$db = JFactory::getDBO();
		
		if($this->listing_id != '' and $this->listing_id != 0)
			$wheres[]=$this->ct->Table->realidfieldname.'='.$db->quote($this->listing_id);
		
		if($wheres_owner_str!='')
			$wheres[]='('.$wheres_owner_str.')';
			
		return $wheres;
	}
	
	function checkIfItemBelongsToUser($useridfield)
	{
		$db = JFactory::getDBO();
		$wheres=$this->UserIDField_BuildWheres($useridfield);
		
		$query='SELECT c.'.$this->ct->Table->realidfieldname.' FROM '.$this->ct->Table->realtablename.' AS c WHERE '.implode(' AND ',$wheres).' LIMIT 1';

		$db->setQuery( $query );
		$db->execute();

		if($db->getNumRows()==1)
		{
			$this->isAutorized=true;
			return true;
		}

		$this->isAutorized=false;
		return false;
	}

	function makeDescription($vlu)
	{
		//came from Category Block
		return strip_tags($vlu);
	}

	function getCustomTablesBranch($optionname,$startfrom, $langpostfix, $defaultvalue)
	{
		$optionid=0;
		$filter_rootparent=Tree::getOptionIdFull($optionname);

		if($optionname)
		{
		    $available_categories=Tree::getChildren($optionid,$filter_rootparent,1, $langpostfix,$optionname);

		    $db = JFactory::getDBO();
		    $query = ' SELECT optionname, id, title_'.$langpostfix.' AS title FROM #__customtables_options WHERE ';
		    $query.= ' id='.$filter_rootparent.' LIMIT 1';

		    $db->setQuery( $query );

		    $rpname= $db->loadObjectList();

			if($startfrom==0)
			{
			if(count($rpname)==1)
				JoomlaBasicMisc::array_insert(
			    $available_categories,
			    array(
						"id" => $filter_rootparent,
						"name" => strtoupper($rpname[0]->title),
						"fullpath" => strtoupper($rpname[0]->optionname)

						),0);
			}
		}
		else
		{
		    $available_categories=Tree::getChildren($optionid,0,1, $langpostfix,'');
		}
		if($defaultvalue)
			JoomlaBasicMisc::array_insert(
			    $available_categories,
			    array(
						"id" => 0,
						"name" => $defaultvalue,
						"fullpath" => ''

						),0);

		if($startfrom==0)
		JoomlaBasicMisc::array_insert($available_categories,
								array(	"id" => 0,
										"name" => JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ROOT'),
										"fullpath" => ''),
									count($available_categories));

		return $available_categories;
	}

	function convertESParam2Array($par)
	{
		$newpar=array();
		$a=explode(',',$par);
		foreach($a as $b)
		{
			$c=trim($b);
			if(strlen($c)>0)
				$newpar[]=$c;
		}
		return $newpar;
	}

	function check_captcha()
	{
		$options=array();
		$captcha=JoomlaBasicMisc::getListToReplace('captcha',$options,$this->pagelayout,'{}');

		if(count($captcha)==0)
			return true;

		$config = JFactory::getConfig()->get('captcha');
		$captcha = JCaptcha::getInstance($config);
		try
		{
			$completed = $captcha->CheckAnswer(null);//null because nothing should be provided

			if ($completed === false)
			    return false;

		}catch (Exception $e)
		{
			return false;
		}
		return true;
	}

	function copy(&$msg,&$link)
	{
		$jinput = JFactory::getApplication()->input;
		$listing_id = $jinput->getCmd('listing_id',0);

		$db = JFactory::getDBO();

		$query='SELECT MAX('.$this->ct->Table->realidfieldname.') AS maxid FROM '.$this->ct->Table->realtablename.' LIMIT 1';
		$db->setQuery( $query );
		$rows=$db->loadObjectList();
		if(count($rows)==0)
			$msg='Table not found or something wrong.';

		$new_id=(int)($rows[0]->maxid)+1;

		if($db->serverType == 'postgresql')
			$query='DROP TABLE IF EXISTS ct_tmp';
		else
			$query='DROP TEMPORARY TABLE IF EXISTS ct_tmp';

		$db->setQuery( $query );
		$db->execute();

		if($db->serverType == 'postgresql')
		{
			$query = 'CREATE TEMPORARY TABLE ct_tmp AS TABLE '.$this->ct->Table->realtablename.' WITH NO DATA';
			
			$db->setQuery( $query );
			$db->execute();
			
			$query = 'INSERT INTO ct_tmp (SELECT * FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.' = '.$db->quote($listing_id).')';
			
			$db->setQuery( $query );
			$db->execute();
		}
		else
		{
			$query='CREATE TEMPORARY TABLE ct_tmp SELECT * FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.' = '.$db->quote($listing_id);
			$db->setQuery( $query );
			$db->execute();
		}

		$sets=array();
		$sets[]=$this->ct->Table->realidfieldname.'='.$db->quote($new_id);
		
		$query='UPDATE ct_tmp SET '.implode(',',$sets).' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id);
		$db->setQuery( $query );
		$db->execute();

		$query='INSERT INTO '.$this->ct->Table->realtablename.' SELECT * FROM ct_tmp WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($new_id);
		$db->setQuery( $query );
		$db->execute();

		$jinput->set('listing_id',$new_id);
		$jinput->set('old_listing_id',$listing_id);
		$this->listing_id = $new_id;

		if($db->serverType == 'postgresql')
		{
			$query='DROP TABLE IF EXISTS ct_tmp';
			$db->setQuery( $query );
			$db->execute();
		}
		else
		{
			$query='DROP TEMPORARY TABLE IF EXISTS ct_tmp';
			$db->setQuery( $query );
			$db->execute();
		}

		return $this->store($msg,$link,true,$new_id);

	}

	function store(&$msg,&$link,$isCopy=false, $listing_id = '')
	{
		$jinput = JFactory::getApplication()->input;

		//IP Filter
		$USER_IP=$this->getUserIP();

		$IP_Black_List=array();

		if(in_array($USER_IP,$IP_Black_List))
			return true;

		if(!$this->check_captcha())
		{
			$msg='Incorrect Captcha';
			return false;
		}

		$isDebug=$jinput->getInt( 'debug',0);

		if($listing_id == '')
			$listing_id = $this->params->get('listingid');

		if($listing_id == '')
			$listing_id = $jinput->getCmd('listing_id', 0); //TODO : this inconsistancy must be fixed

		$fieldstosave=$this->getFieldsToSave(); //will Read page Layout to find fields to save

		$msg='';
		$savequery=array();
		$db = JFactory::getDBO();

		$user_email='';
		$user_name='';

		//	Fields
		$prefix='comes_';
		$this->ct->Table->prefix = $prefix;
		
		$row_old = [];
		
		if($listing_id != '')
			$row_old = $this->ct->Table->loadRecord($listing_id);
		else
			$row_old[$this->ct->Table->realidfieldname] = '';

		$phponchangefound=false;
		$phponaddfound=false;

		//$default_fields_to_apply=array();
		
		$savefield = new SaveFieldQuerySet($this->ct,$row_old,$isCopy);

		foreach($this->ct->Table->fields as $esfield)
		{
			$realfieldname=$esfield['realfieldname'];

			$value_found=false;
			if(in_array($esfield['fieldname'],$fieldstosave))
			{
				$saveFieldSet = $savefield->getSaveFieldSet($esfield);
				
				if($saveFieldSet != null)
				{
					if(is_array($saveFieldSet))
						$savequery = array_merge($savequery,$saveFieldSet);
					else
						$savequery[] = $saveFieldSet;
				}
				//else
					//$default_fields_to_apply[]=array($esfield['fieldname'],$esfield['defaultvalue'],$esfield['type'],$esfield['realfieldname']);
			}
			
			if($esfield['type'] == 'phponadd' and ($listing_id == 0 or $listing_id == '' or $isCopy))
				$phponaddfound=true;
			
			if($esfield['type'] == 'phponchange')
				$phponchangefound=true;

			if($this->params->get('emailfield')!='' and $this->params->get('emailfield')==$fieldname)
				$user_email=$jinput->getString($prefix.$fieldname);

			if($this->params->get('fullnamefield')!='' and $this->params->get('fullnamefield')==$fieldname)
				$user_name=$jinput->getString($prefix.$fieldname);
		}

		$row_old=array();
		$listing_id_temp = 0;
		
		$isitnewrecords = false;
		if($listing_id == 0 or $listing_id == '')
		{
			$isitnewrecords	=true;

			$publishstatus = $this->params->get( 'publishstatus' );
			if(is_null($publishstatus))
				$publishstatus = $jinput->getInt('published');
			else
				$publishstatus = (int)$publishstatus;

			if($this->ct->Table->tablerow['published_field_found'])
				$savequery[]='published='.$publishstatus;
			
			$listing_id_temp = ESTables::insertRecords($this->ct->Table->realtablename,$this->ct->Table->realidfieldname,$savequery);
		}

		else
		{
			//get old row
			//$query='SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id).' LIMIT 1';
			//$db->setQuery( $query );
			//$rows = $db->loadAssocList();
			//if(count($rows)!=0)
				//$row_old=$rows[0];
			
			$this->updateLog($listing_id);			
			$savefield->runUpdateQuery($savequery,$listing_id);
		}

		if(count($savequery)<1)
		{
			JFactory::getApplication()->enqueueMessage('Nothing to save', 'Warning');
			return false;
		}
		
		if(($listing_id==0 or $listing_id == '') and $listing_id_temp!=0)
		{
			$row = $this->ct->Table->loadRecord($listing_id_temp);
			//$query='SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id_temp).' LIMIT 1';
			//$db->setQuery( $query );

			//$rows = $db->loadAssocList();
			if($row != null)
			{
				$row=$rows[0];
				JFactory::getApplication()->input->set('listing_id',$row['listing_id']);

				if($phponaddfound)
					$this->doPHPonAdd($row);

				if($phponchangefound)
					$this->doPHPonChange($row);
					
				//$this->updateDefaultValues($row);

				$listing_id=$row['listing_id'];
			}
			$this->ct->Table->saveLog($listing_id,1);
		}
		else
		{
			$this->ct->Table->saveLog($listing_id,2);

			$row = $this->ct->Table->loadRecord($listing_id);
			//$query='SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id).' LIMIT 1';
			//$db->setQuery( $query );

			//$rows = $db->loadAssocList();

			if($row != null)
			{
				$row=$rows[0];
				JFactory::getApplication()->input->set('listing_id',$row['listing_id']);

				if($phponchangefound or $this->ct->Table->tablerow['customphp']!='')
					$this->doPHPonChange($row);
						
				if($phponaddfound and $isCopy)
					$this->doPHPonAdd($row);
						
				//$this->updateDefaultValues($row);
			}
		}

		//update MD5s
		//$this->updateMD5($listing_id);
		//$this->ct->Table->processDefaultValues($default_fields_to_apply,$this->ct,$row);

		if($this->onrecordsavesendemailto!='' or $this->onrecordaddsendemailto!='')
		{
			if($this->onrecordaddsendemail==3)
			{
				//check conditions
				if($this->checkSendEmailConditions($listing_id,$this->sendemailcondition))
				{
					//Send email conditions met
					$this->sendEmailIfAddressSet($listing_id);//,$new_username,$new_password);
				}
			}
			else
			{
				if($isitnewrecords or $isCopy)
				{
					//New record
					if($this->onrecordaddsendemail==1 or $this->onrecordaddsendemail==2)
						$this->sendEmailIfAddressSet($listing_id);//,$new_username,$new_password);
				}
				else
				{
					//Old record
					if($this->onrecordaddsendemail==2)
					{
						$this->sendEmailIfAddressSet($listing_id);//,$new_username,$new_password);
					}
				}
			}
		}

		//Prepare "Accept Return To" Link
		$art_link=$this->PrepareAcceptReturnToLink(JFactory::getApplication()->input->get( 'returnto','','BASE64' ));
		if($art_link!='')
			$link=$art_link;

		$link=str_replace('*new*',$row['listing_id'],$link);

		//Refresh menu if needed
		$msg= $this->itemaddedtext;

		if($this->ct->Env->advancedtagprocessor)
			CleanExecute::executeCustomPHPfile($this->ct->Table->tablerow['customphp'],$row,$row_old);

		if($isDebug)
		{
			die('Debug mode.');//debug mode
		}	
		
		$jinput->set('listing_id',$listing_id);

		return true;
	}

	function sendEmailIfAddressSet($listing_id)//,$new_username,$new_password)
	{
		$status=0;
		if($this->onrecordaddsendemailto!='')
			$status=$this->sendEmailNote($listing_id,$this->onrecordaddsendemailto);//,$new_username,$new_password);
		else
			$status=$this->sendEmailNote($listing_id,$this->onrecordsavesendemailto);//,$new_username,$new_password);
		
		if($this->emailsentstatusfield!='')
		{
			foreach($this->ct->Table->fields as $esfield)
			{
				$fieldname=$esfield['fieldname'];
				if($this->emailsentstatusfield==$fieldname)
				{
					$db = JFactory::getDBO();
					$query='UPDATE '.$this->ct->Table->realtablename.' SET es_'.$fieldname.'='.(int)$status.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id);

					$db->setQuery( $query );
					$db->execute();	
					return;
				}
			}
		}
	}

	function checkSendEmailConditions($listing_id,$condition)
	{
		if($condition=='')
			return true; //if no conditions
			
		$this->ct->Table->record = $this->getListingRowByID($listing_id);
		$parsed_condition=$this->parseRowLayoutContent($condition,true);
		
		$parsed_condition = '('.$parsed_condition.' ? 1 : 0)';

		$error = '';
		$value = CleanExecute::execute($parsed_condition,$error);
		
		if($error!='')
		{
			Factory::getApplication()->enqueueMessage($error,'error');
			return false;
		}

		if((int)$value==1)
			return true;

		return false;

	}

	/*
	function updateMD5($listing_id)
	{
		$savequery=array();
		foreach($this->ct->Table->fields as $esfield)
		{
				if($esfield['type']=='md5')
				{
						$fieldstocount=explode(',',str_replace('"','',$esfield['typeparams']));//only field names, nothing else
						
						$flds=array();
						foreach($fieldstocount as $f)
						{
							//to make sure that field exists
							foreach($this->ct->Table->fields as $esfield_)
							{
								if($esfield_['fieldname']==$f and $esfield['fieldname']!=$f)
									$flds[]='COALESCE('.$esfield_['realfieldname'].')';
							}
						}

						if(count($flds)>1)
							$savequery[]=$esfield['realfieldname'].'=md5(CONCAT_WS('.implode(',',$flds).'))';
				}
		}

		$this->ct->Table->runUpdateQuery($savequery,$listing_id);
	}
	*/
/*
	function updateDefaultValues($row)
	{
		$default_fields_to_apply=array();

		foreach($this->ct->Table->fields as $esfield)
		{
			$fieldname=$esfield['fieldname'];
			if($esfield['defaultvalue']!='' and $row[$esfield['realfieldname']]=='')
				$default_fields_to_apply[]=array($fieldname,$esfield['defaultvalue'],$esfield['type'],$esfield['realfieldname']);
		}

        $this->ct->Table->processDefaultValues($default_fields_to_apply,$this->ct,$row);
	}
*/
	function updateLog($listing_id)
	{
		if($listing_id==0 or $listing_id == '')
			return;

		$db = JFactory::getDBO();

		//saves previous version of the record
		//get data
		$fields_to_save=array();
		foreach($this->ct->Table->fields as $esfield)
		{
			if($esfield['type']=='multilangstring' or $esfield['type']=='multilangtext')
			{
				$firstlanguage=true;

				foreach($this->ct->Languages->LanguageList as $lang)
				{
					if($firstlanguage)
					{
						$postfix='';
						$firstlanguage=false;
					}
					else
						$postfix='_'.$lang->sef;

					$fields_to_save[]=$esfield['realfieldname'].$postfix;
				}
			}
			elseif($esfield['type']!='log' and $esfield['type']!='dummy')
				$fields_to_save[]=$esfield['realfieldname'];

		}
		
		//get data
		$query = 'SELECT '.implode(',',$fields_to_save).' FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id).' LIMIT 1';

	 	$db->setQuery($query);

		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return;

		$data=base64_encode(json_encode($rows));

		$savequery=array();
		foreach($this->ct->Table->fields as $esfield)
		{
				if($esfield['type']=='log')
				{
					$user = JFactory::getUser();
					$userid = (int)$user->get('id');
					$value=time().','.$userid.','.$this->getUserIP().','.$data.';';
					$savequery[]=$esfield['realfieldname'].'=CONCAT('.$esfield['realfieldname'].',"'.$value.'")';
				}
		}

		if(count($savequery)>0)
			$this->ct->Table->runUpdateQuery($savequery,$listing_id);
	}

/*
	function CheckValueRule($prefix,$fieldname, $fieldtype, $typeparams)
	{
		$valuearray=array();
		$value='';

		switch($fieldtype)
			{
				case 'records':

					$typeparamsarray=explode(',',$typeparams);
					if(count($typeparamsarray)>2)
					{
						$esr_selector=$typeparamsarray[2];
						$selectorpair=explode(':',$esr_selector);

						switch($selectorpair[0])
						{
							case 'single';
									$value=JFactory::getApplication()->input->getString($prefix.$fieldname);
								break;

							case 'multi';
									$valuearray = JFactory::getApplication()->input->get( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;
							case 'multibox';
									$valuearray = JFactory::getApplication()->input->get( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;

							case 'radio';
									$value=JFactory::getApplication()->input->getString($prefix.$fieldname);
								break;

							case 'checkbox';
									$valuearray = JFactory::getApplication()->input->get( $prefix.$fieldname, array(), 'post', 'array' );
									$value='"'.implode('","',$valuearray).'"';
								break;
						}

					}

					break;
				case 'radio':
						$value=JFactory::getApplication()->input->getString($prefix.$fieldname);
					break;

				case 'googlemapcoordinates':
						$value=JFactory::getApplication()->input->getString($prefix.$fieldname);
					break;

				case 'string':
						$value=JFactory::getApplication()->input->getString($prefix.$fieldname);
					break;

				case 'multilangstring':

					$firstlanguage=true;
					foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$valuearray[]=JFactory::getApplication()->input->getString($prefix.$fieldname.$postfix);

					}
					$value='"'.implode('","',$valuearray).'"';
					break;


				case 'text':
					$value = ComponentHelper::filterText(JFactory::getApplication()->input->post->get($prefix.$fieldname, '', 'raw'));
					break;

				case 'multilangtext':

					$firstlanguage=true;
					foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$value_ = ComponentHelper::filterText(JFactory::getApplication()->input->post->get($prefix.$fieldname.$postfix, '', 'raw'));

						$valuearray[]=$value_;

					}
					$value='"'.implode('","',$valuearray).'"';
					break;

				case 'int':
						$value=JFactory::getApplication()->input->getInt($prefix.$fieldname,0);
					break;

				case 'user':
						$value=(int)JFactory::getApplication()->input->getInt($prefix.$fieldname,0);
					break;

				case 'float':
						$value=JFactory::getApplication()->input->get($prefix.$fieldname,0,'FLOAT');
					break;


				case 'article':
						$value=JFactory::getApplication()->input->getInt($prefix.$fieldname,0);
					break;

				case 'multilangarticle':

					$firstlanguage=true;
					foreach($this->ct->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$valuearray[]=JFactory::getApplication()->input->getInt($prefix.$fieldname.$postfix,0);

					}
					$value='"'.implode('","',$valuearray).'"';
					break;

				case 'customtables':

						$typeparams_arr=explode(',',$typeparams);
						$optionname=$typeparams_arr[0];

						if($typeparams_arr[1]=='multi')
							$value=$this->getMultiString($optionname, $prefix.'multi_'.$this->ct->Table->tablename.'_'.$fieldname);
						elseif($typeparams_arr[1]=='single')
							$value=$this->getComboString($optionname, $prefix.'combotree_'.$this->ct->Table->tablename.'_'.$fieldname);

					break;

				case 'email':
						$value=JFactory::getApplication()->input->getString($prefix.$fieldname);
					break;

				case 'checkbox':
						$value=JFactory::getApplication()->input->getCmd($prefix.$fieldname);
					break;

				case 'date':
						$value=JFactory::getApplication()->input->getString($prefix.$fieldname);
					break;
			}

		if($value=='')
			$value='""';
			
		return;
	}
	*/

	function PrepareAcceptReturnToLink($artlink)
	{
		if($artlink=='')
			return '';

		$artlink=base64_decode ($artlink);

		if($artlink=='')
			return '';

		$mainframe = JFactory::getApplication('site');

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

		$LayoutProc=new LayoutProcessor($this->ct);
		$LayoutProc->layout=$artlink;

		$db = JFactory::getDBO();
		
		$query = 'SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename.' ORDER BY '.$this->ct->Table->realidfieldname.' DESC LIMIT 1';
	 	$db->setQuery($query);

		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return '';

		$row=$rows[0];

		$processed_link=$LayoutProc->fillLayout($row,"",'[]',true);

		return $processed_link;
	}

	function doPHPonAdd(&$row)
	{
		$listing_id = $row['listing_id'];
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

		$LayoutProc=new LayoutProcessor($this->ct);
		$LayoutProc->Model=$this;

		$savequery='';
		$db = JFactory::getDBO();

		foreach($this->ct->Table->fields as $esfield)
		{
			$realfieldname=$esfield['realfieldname'];
			$typeparams=$esfield['typeparams'];

			if($esfield['type']=='phponadd')
			{
						$parts=JoomlaBasicMisc::csv_explode(',', $typeparams, '"', false);
						
						if(count($parts)==1 and strpos($esfield['typeparams'],'"')!==false and strpos($esfield['typeparams'],'****quote****')===false )
							$thescript=$esfield['typeparams'];//to support older version when type params field could countain php script only. Also ****quote****  wasn't supported
						else
						{
							$thescript=$parts[0];
							$thescript=str_replace('****quote****','"',$thescript);
							$thescript=str_replace('****apos****',"'",$thescript);
						}
						
						$LayoutProc->layout=$thescript;
						
						$thescript='return '.LayoutProcessor::applyContentPlugins($LayoutProc->fillLayout($row,'','[]',true)).';';

						$error = '';
						$value = CleanExecute::execute($thescript,$error);
		
						if($error!='')
						{
							Factory::getApplication()->enqueueMessage($error,'error');
							return false;
						}
						
						$row[$realfieldname]=$value;

						$savequery=$realfieldname.'='.$db->quote($value);
						$query='UPDATE '.$this->ct->Table->realtablename.' SET '.$savequery.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id);

						$db->setQuery( $query );
						$db->execute();

			}
		}



	}

	function doPHPonChange(&$row)
	{
		$listing_id=$row['listing_id'];
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

		$LayoutProc=new LayoutProcessor($this->ct);

		$db = JFactory::getDBO();

		foreach($this->ct->Table->fields as $esfield)
		{
			$realfieldname=$esfield['realfieldname'];

			if($esfield['type']=='phponchange')
			{
						$parts=JoomlaBasicMisc::csv_explode(',', $esfield['typeparams'], '"', false);
						
						if(count($parts)==1 and strpos($esfield['typeparams'],'"')!==false and strpos($esfield['typeparams'],'****quote****')===false )
						{
							$thescript=$esfield['typeparams'];//to support older version when type params field could countain php script only. Also ****quote****  wasn't supported
						}
						else
						{
							$thescript=$parts[0];
							$thescript=str_replace('****quote****','"',$thescript);
							$thescript=str_replace('****apos****',"'",$thescript);
						}
						
						$LayoutProc->layout=$thescript;

						$htmlresult = $LayoutProc->fillLayout($row,'','[]',true);
						$thescript='return '.LayoutProcessor::applyContentPlugins($htmlresult).';';
				
						$error = '';
						$value = CleanExecute::execute($thescript,$error);
		
						if($error!='')
						{
							Factory::getApplication()->enqueueMessage($error,'error');
							return false;
						}
						
						$row[$realfieldname]=$value;

						$savequery=$realfieldname.'='.$db->quote($value);
						$query='UPDATE '.$this->ct->Table->realtablename.' SET '.$savequery.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id);

						$db->setQuery( $query );
						$db->execute();
			}//if($esfield['type']=='phponchange')
		}
	}

	function getListingRowByID($listing_id)
	{
		$db = JFactory::getDBO();
		
		$query = 'SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id).' LIMIT 1';
	 	$db->setQuery($query);

		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return false;

		return $rows[0];
	}

	function parseRowLayoutContent($content,$applyContentPlagins=true)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

		$LayoutProc=new LayoutProcessor($this->ct);
		$LayoutProc->layout=$content;
		$content=$LayoutProc->fillLayout($this->ct->Table->record);
		if($applyContentPlagins)
			LayoutProcessor::applyContentPlugins($content);
		
		$twig = new TwigProcessor($this->ct, '{% autoescape false %}'.$content.'{% endautoescape %}');
		$content = $twig->process($this->ct->Table->record);
		
		return $content;
	}

	function sendEmailNote($listing_id,$emails)
	{
		$mainframe = JFactory::getApplication('site');
		$this->ct->Table->record = $this->getListingRowByID($listing_id);
		
		//Prepare Email List
		$emails_raw=JoomlaBasicMisc::csv_explode(',', $emails, '"', true);

		$emails=array();
		foreach($emails_raw as $SendToEmail)
		{
			$EmailPair=JoomlaBasicMisc::csv_explode(':', trim($SendToEmail), '"', false);
			
			$EmailTo=$this->parseRowLayoutContent(trim($EmailPair[0]),false);
			
			if(isset($EmailPair[1]) and $EmailPair[1]!='')
				$Subject=$this->parseRowLayoutContent($EmailPair[1],true);
			else
				$Subject='Record added to "'.$this->ct->Table->tabletitle.'"';
			
			if($EmailTo!='')
				$emails[]=array('email' => $EmailTo, 'subject' => $Subject);
		}
		
		$Layouts = new Layouts($this->ct);
		$message_layout_content = $Layouts->getLayout($this->onrecordaddsendemaillayout);
		
		$note=$this->parseRowLayoutContent($message_layout_content,true);
		
		$MailFrom 	= $mainframe->getCfg('mailfrom');
		$FromName 	= $mainframe->getCfg('fromname');

		$status=0;

		foreach($emails as $SendToEmail)
		{
			$EmailTo=$SendToEmail['email'];
			$Subject=$SendToEmail['subject'];

			$options=array();
			$fList=JoomlaBasicMisc::getListToReplace('attachment',$options,$note,'{}');
			$i=0;
			$note_final=$note;
			foreach($fList as $fItem)
			{
				$filename=$options[$i];
				if(file_exists($filename))
				{
					$mail->addAttachment($filename);
					$vlu='';
				}
				else
					$vlu='<p>File not found. Code: 21098s</p>';

				$note_final=str_replace($fItem,'',$note);
				$i++;
			}
			
			$attachments=[];
			foreach($this->ct->Table->fields as $esfield)
			{
				if($esfield['type']=='file')
				{

					$filename='images/esfiles/'.$this->ct->Table->record[$esfield['realfieldname']];
					if(file_exists($filename))
							$attachments[] = $filename;
				}
			}
			
			$sent = Email::sendEmail($EmailTo,$Subject,$note_final,$isHTML = true,$attachments);

			if ( $sent !== true ) {
				//Something went wrong. Email not sent.
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_SENDING_EMAIL').': '.$EmailTo.' ('.$Subject.')', 'error');
				$status=0;
			}
			else{
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EMAIL_SENT_TO').': '.$EmailTo.' ('.$Subject.')');
				$status=1;
			}
		}
		
		return $status;
	}

	function Refresh($save_log=1)
	{
		$listing_ids_str=JFactory::getApplication()->input->getString('ids', '');

		if($listing_ids_str!='')
		{
			$listing_ids_=explode(',',$listing_ids_str);
			foreach($listing_ids_ as $listing_id)
			{
				if($listing_id != '')
				{
					$listing_id = preg_replace("/[^a-zA-Z_0-9-]/", "", $listing_id);
					if($this->RefreshSingleRecord($listing_id,$save_log) == -1)
						return -count($listing_ids_); //negative value means that there is an error
				}
			}
			return count($listing_ids_);
		}

		$listing_id = JFactory::getApplication()->input->getCmd('listing_id', 0);

		if($listing_id == 0 or $listing_id == '')
			return 0;

		return $this->RefreshSingleRecord($listing_id,$save_log);
	}
	
	protected function RefreshSingleRecord($listing_id,$save_log)
	{
		$db = JFactory::getDBO();
		
		$query='SELECT '.$this->ct->Table->tablerow['query_selects'].' FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id).' LIMIT 1';
		$db->setQuery( $query );

		$rows = $db->loadAssocList();
		if(count($rows)==0)
			return -1;

		$row=$rows[0];
		JFactory::getApplication()->input->set('listing_id',$listing_id);

		$this->doPHPonChange($row);

		//update MD5s
		$this->updateMD5($listing_id);

		if($save_log==1)
			$this->ct->Table->saveLog($listing_id,10);

		$this->updateDefaultValues($row);

		if($this->ct->Env->advancedtagprocessor)
			CleanExecute::executeCustomPHPfile($this->ct->Table->tablerow['customphp'],$row,$row);

		//Send email note if applicable
		if($this->onrecordaddsendemail==3 and ($this->onrecordsavesendemailto!='' or $this->onrecordaddsendemailto!=''))
		{
			//check conditions
			if($this->checkSendEmailConditions($listing_id,$this->sendemailcondition))
			{
				//Send email conditions met
				$this->sendEmailIfAddressSet($listing_id);//,$new_username,$new_password);
			}
		}
		
		return 1;
	}
	
	function setPublishStatus($status)
	{
		$listing_ids_str=JFactory::getApplication()->input->getString('ids', '');
		if($listing_ids_str!='')
		{
			$listing_ids_=explode(',',$listing_ids_str);
			foreach($listing_ids_ as $listing_id)
			{
				if($listing_id != '')
				{
					$listing_id = preg_replace("/[^a-zA-Z_0-9-]/", "", $listing_id);
					if($this->setPublishStatusSingleRecord($listing_id,$status) == -1)
						return -count($listing_ids_); //negative value means that there is an error
				}
			}
			return count($listing_ids_);
		}

		$listing_id = $this->listing_id;
		if($listing_id == '' or $listing_id == 0)
			return 0;

		return $this->setPublishStatusSingleRecord($listing_id,$status);
	}

	public function setPublishStatusSingleRecord($listing_id,$status)
	{
		if(!$this->ct->Table->published_field_found)
			return -1;
		
		$db = JFactory::getDBO();

		$query = 'UPDATE '.$this->ct->Table->realtablename.' SET published='.(int)$status.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id);
		
	 	$db->setQuery($query);
		$db->execute();	

		if($status==1)
			$this->ct->Table->saveLog($listing_id,3);
		else
			$this->ct->Table->saveLog($listing_id,4);

		$this->RefreshSingleRecord($listing_id,0);

		return 1;
	}

	function getFieldsToSave()
	{
		$fields=array();
		
		$backgroundFieldTypes = ['creationtime','changetime','server','id','md5'];

		foreach($this->ct->Table->fields as $esfield)
		{
			if(in_array($esfield['type'],$backgroundFieldTypes))
			{
				$fields[]=$esfield['fieldname'];
			}
			else
			{
				$fn=$esfield['fieldname'];
				
				$fn_str=array();
				$fn_str[]='['.$fn.':';
				$fn_str[]='['.$fn.']';

				$fn_str[]='"comes_'.$fn.'"';
				$fn_str[]="'comes_".$fn."'";
			
				$fn_str[]='[_edit:'.$fn.':';
				$fn_str[]=$fn.'.edit';

				$found=false;
				foreach($fn_str as $s)
				{
					if(strpos($this->pagelayout,$s)!==false)
					{
						$found=true;
						break;
					}
				}

				if($found)
					$fields[]=$fn;
			}
		}
		return $fields;
	}

	function getUserIP()
	{
		if( array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
		{
			if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')>0)
			{
			    $addr = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
				return trim($addr[0]);
			}
			else
			{
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		}
		else
		{
			return $_SERVER['REMOTE_ADDR'];
		}
	}
	
	function delete()
	{
		$jinput = JFactory::getApplication()->input;

		$listing_ids_str=$jinput->getString('ids','');
		if($listing_ids_str!='')
		{
			$ok=true;
			$listing_ids_=explode(',',$listing_ids_str);
			foreach($listing_ids_ as $listing_id)
			{
				if($listing_id != '')
				{
					$listing_id = preg_replace("/[^a-zA-Z_0-9-]/", "", $listing_id);
					if($this->deleteSingleRecord($listing_id) == -1)
						return -count($listing_ids_); //negative value means that there is an error
				}
			}
			return count($listing_ids_);
		}

		$listing_id = $jinput->getCmd('listing_id',0);
		if($listing_id == '' or $listing_id == 0)
			return 0;

		return $this->deleteSingleRecord($listing_id);
	}

	public function deleteSingleRecord($listing_id)
	{
		$db = JFactory::getDBO();

		//delete images if exist
		$imagemethods=new CustomTablesImageMethods;

		$query='SELECT * FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id);

		$db->setQuery($query);
		$rows=$db->loadAssocList();

		if(count($rows)==0)
			return -1;

		$row=$rows[0];

		foreach($this->ct->Table->fields as $esfield)
		{
			$field = new Field($ct,$esfield,$row);
			
			if($field->type=='image')
			{
				$ImageFolder_=CustomTablesImageMethods::getImageFolder($field->params);

				//delete single image
				$imagemethods->DeleteExistingSingleImage(
					$row[$field->realfieldname],
					$ImageFolder_,
					$field->params[0],
					$this->ct->Table->realtablename,
					$field->realfieldname,
					$this->ct->Table->realidfieldname
				);
			}
			elseif($field->type=='imagegallery')
			{
				$ImageFolder_=CustomTablesImageMethods::getImageFolder($field->params);
				
				//delete gallery images if exist
				$galleryname=$field->fieldname;
				$phototablename='#__customtables_gallery_'.$this->ct->Table->tablename.'_'.$galleryname;

				$query = 'SELECT photoid FROM '.$phototablename.' WHERE listingid='.$db->quote($listing_id);
				$db->setQuery($query);
				
				$photorows=$db->loadObjectList();

				foreach($photorows as $photorow)
				{
					$imagemethods->DeleteExistingGalleryImage(
						$ImageFolder_,
						$this->imagegalleryprefix,
						$this->ct->Table->tableid,
						$galleryname,
						$photorow->photoid,
						$field->params[0],
						true
					);
				}//foreach($photorows as $photorow)

			}
		}

		$query='DELETE FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($listing_id);
		$db->setQuery($query);
		$db->execute();

		$this->ct->Table->saveLog($listing_id,5);

		$new_row=array();

		if($this->ct->Env->advancedtagprocessor)
			CleanExecute::executeCustomPHPfile($this->ct->Table->tablerow['customphp'],$new_row,$row);
			
		return 1;
	}
	
	public function copyContent($from, $to)
	{
		$db = JFactory::getDBO();
		
		//Copy value from one cell to another (drag and drop functionality)
		$from_parts = explode('_',$from);
		$to_parts = explode('_',$to);
		
		$from_listing_id = $from_parts[0];
		$to_listing_id = $to_parts[0];
		
		$from_field = Fields::FieldRowByName($from_parts[1],$this->ct->Table->fields);
		$to_field = Fields::FieldRowByName($to_parts[1],$this->ct->Table->fields);
		
		if(!isset($from_field['type']))
			die(json_encode(['error' => 'From field not found.']));
		
		if(!isset($to_field['type']))
			die(json_encode(['error' => 'To field not found.']));
		
		$from_fieldtype = $from_field['type'];
		$to_fieldtype = $to_field['type'];
		
		//$from_query_value = '(SELECT '.$from_field['realfieldname'].' FROM '.$this->ct->Table->realtablename.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($from_listing_id).' LIMIT 1)';
		
		$from_row = $this->ct->Table->loadRecord($from_listing_id);
		$to_row = $this->ct->Table->loadRecord($to_listing_id);
		
		$f = $from_field['type'];
		$t = $to_field['type'];
		
		$ok = true;
		
		if($f != $t)
		{
			switch($t)
			{
				case 'string':
					if(!($f == 'email' or $f == 'int' or $f == 'float' or $f == 'text'))
						$ok = false;
				break;
				
				default:
					$ok = false;
			}
		}
		
		if(!$ok)
			die(json_encode(['error' => 'Target and distanation field types do not match.']));
		
		$new_value = '';
		
		switch($to_field['type'])
		{
			case 'sqljoin':
				if($to_row[$to_field['realfieldname']] !== '')
					die(json_encode(['error' => 'Target field type is the Table Join. Multiple values not allowed.']));
				
			case 'customtables':
				if($to_row[$to_field['realfieldname']] !== '')
					die(json_encode(['error' => 'Target field type is a Tree. Multiple values not allowed.']));
			
			case 'email':
			
				if($to_row[$to_field['realfieldname']] !== '')
					die(json_encode(['error' => 'Target field type is an Email. Multiple values not allowed.']));
				
				$new_value = $from_row[$from_field['realfieldname']];
			
			case 'string':

				if(strpos($to_row[$to_field['realfieldname']],$from_row[$from_field['realfieldname']]) !== false)
					die(json_encode(['error' => 'Target field already contains this value.']));
					
				$new_value = $to_row[$to_field['realfieldname']];
				if($new_value !='' )
					$new_value .=',';
				
				$new_value .= $from_row[$from_field['realfieldname']];
				break;
				
			case 'records':
			
				$new_items = [''];
				$to_items = explode(',',$to_row[$to_field['realfieldname']]);
				
				foreach($to_items as $item)
				{
					if($item != '' and !in_array($item,$new_items))
						$new_items[] = $item;
				}
				
				$from_items = explode(',',$from_row[$from_field['realfieldname']]);
				
				foreach($from_items as $item)
				{
					if($item != '' and !in_array($item,$new_items))
						$new_items[] = $item;
				}
				
				$new_items[] = '';
			
				if(count($new_items) == count($to_items))
					die(json_encode(['error' => 'Target field already contains this value(s).']));
				
				$new_value = implode(',',$new_items);
			
				break;
		}
		
		if($new_value != '')
		{
			$query='UPDATE '.$this->ct->Table->realtablename
					.' SET '.$to_field['realfieldname'].'= '.$db->quote($new_value)
					.' WHERE '.$this->ct->Table->realidfieldname.'='.$db->quote($to_listing_id);
				
			$db->setQuery($query);
			$db->execute();
			return true;
		}

		return false;
	}
}
