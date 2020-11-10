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



JTable::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'tables');

$site_libpath=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
$admin_libpath=JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
require_once($admin_libpath.'customtablesmisc.php');
require_once($admin_libpath.'misc.php');
require_once($admin_libpath.'imagemethods.php');
require_once($admin_libpath.'languages.php');
require_once($admin_libpath.'tables.php');
require_once($admin_libpath.'fields.php');
require_once($admin_libpath.'layouts.php');
require_once($site_libpath.'layout.php');
require_once($site_libpath.'logs.php');

$libpath=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR;
require_once($libpath.'valuetags.php');


require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'filtering.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'save.php');

class CustomTablesModelEditItem extends JModelLegacy {

	var $es;

	var $LangMisc;
	var $establename;
	var $estableid;
	var $tabletitle;
	var $establedescription;
	var $tablename;//mysql table name
	var $tablecustomphp;
	var $tablerow;

	var $itemaddedtext;
	var $onrecordaddsendemailto;
	var $onrecordaddsendemail;
	var $sendemailcondition;
	var $emailsentstatusfield;


	var $layout_catalog;
	var $layout_details;
	var $id;
	var $LanguageList;
	var $langpostfix;
	var $esfields;
	var $showlines;
	//var $oklink;

	var $isAutorized;
	var $guestcanaddnew;
	var $pagelayout;
	var $msg_itemissaved;

	var $userGroup;
	var $edit_userGroup;
	var $publish_userGroup;
	var $delete_userGroup;
	var $add_userGroup;

	var $current_url;
	var $encoded_current_url;
	var $userid;
	var $useridfield;
	var $useridfield_uniqueusers;
	var $isUserAdministrator;
	var $print;
	var $frmt;
	var $params;
	var $Itemid;
	var $BlockExternalVars;
	var $advancedtagprocessor;
	
	var $row;

	function __construct()
	{
		parent::__construct();
		$this->advancedtagprocessor=false;
		
		$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
		if(file_exists($phptagprocessor))
		{
			//require_once($phptagprocessor);
			$this->advancedtagprocessor=true;
		}

		$jinput=JFactory::getApplication()->input;

		$this->current_url=JoomlaBasicMisc::curPageURL();
		$this->encoded_current_url=base64_encode($this->current_url);

		$user = JFactory::getUser();
		$this->userid=$user->id;

		$this->isUserAdministrator=JoomlaBasicMisc::isUserAdmin($this->userid);

		$this->print=(bool)$jinput->getInt('print',0);
		$this->frmt=$jinput->getCmd('frmt','html');

	}


	function load($params,$BlockExternalVars=false)
	{
		$app = JFactory::getApplication();
		if(isset($params))
			$this->params=$params;
		else
			$this->params=$app->getParams();
		
		$jinput=JFactory::getApplication()->input;
		$this->Itemid=$jinput->getInt('Itemid',0);

		if((int)$this->params->get( 'customitemid' )!=0)
		$this->Itemid=(int)$this->params->get( 'customitemid' );
		
		$this->BlockExternalVars=$BlockExternalVars;

		$this->useridfield='';
		$this->useridfield_unique=false;
		

		//if((int)$this->params->get( 'customitemid' )!=0)
		//$this->Itemid=(int)$this->params->get( 'customitemid' );

		$this->es= new CustomTablesMisc;


		$this->LangMisc	= new ESLanguages;
		$this->LanguageList=$this->LangMisc->getLanguageList();
		$this->langpostfix=$this->LangMisc->getLangPostfix();



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


		$this->establename=$this->params->get( 'establename' );

		if($this->establename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected.', 'error');
			return false;
		}


		$this->applybuttontitle=$this->params->get( 'applybuttontitle' );

		$this->guestcanaddnew=$this->params->get( 'guestcanaddnew' );

		$this->showlines=false;

		$this->submitbuttons=$this->params->get( 'submitbuttons' );


		$this->msg_itemissaved=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED' );



		if($this->params->get( 'msgitemissaved' ))
		$this->msg_itemissaved=$this->params->get( 'msgitemissaved' );

		//if(!$BlockExternalVars and $this->oklink!='')
			//JFactory::getApplication()->input->set('returnto',base64_encode ($this->oklink));


		$this->tablerow=ESTables::getTableRowByNameAssoc($this->establename);
		$this->estableid=$this->tablerow['id'];

		$this->onrecordaddsendemailto=$this->params->get('onrecordaddsendemailto');
		$this->onrecordsavesendemailto=$this->params->get('onrecordsavesendemailto');

		$this->onrecordaddsendemail=(int)$this->params->get('onrecordaddsendemail');
		$this->sendemailcondition=$this->params->get('sendemailcondition');
		$this->emailsentstatusfield=$this->params->get('emailsentstatusfield');

		$this->tabletitle=$this->tablerow['tabletitle'.$this->langpostfix];
		$this->establedescription=$this->tablerow['description'.$this->langpostfix];
		$this->tablename='#__customtables_table_'.$this->establename;
		$this->tablecustomphp=$this->tablerow['customphp'];

		$this->findUserIDField();

		if($this->params->get('eseditlayout')!='')
		{
			$layouttype=0;
			
			$this->pagelayout=ESLayouts::getLayout($this->params->get('eseditlayout'),$layouttype);
		}
		else
			$this->pagelayout='';

		$this->layout_catalog='';
		$this->layout_details='';

		$this->onrecordaddsendemaillayout=$this->params->get('onrecordaddsendemaillayout');


		//	Fields
		$this->esfields= ESFields::getFields($this->estableid);


		if($this->params->get('listingid')!=0)
		{
			$this->id=$this->processCustomListingID();
		}
		elseif(!$BlockExternalVars and $jinput->getInt('listing_id',0)!=0)
		{
			$this->id=$jinput->getInt('listing_id',0);
			$this->id=$this->processCustomListingID();
		}
			
		if($this->id==0 and $this->useridfield_uniqueusers and $this->useridfield!='')
		{
			//try to find record by userid
			$this->id=$this->findRecordByUserID();
		}
		
		if(isset($this->row))
			$this->getSpecificVersionIfSet();
		
		return true;
	}

	function findRecordByUserID()
	{
		$db = JFactory::getDBO();
		$wherearr=array();
		$wherearr[]='published=1';
		$wherearr[]='es_'.$this->useridfield.'='.$this->userid;
		$where = ' WHERE '.implode(" AND ",$wherearr);
		$query = 'SELECT * FROM '.$this->tablename.' '.$where;
		$query.=' LIMIT 1';

		$db->setQuery($query);

		if (!$db->query())    die( $db->stderr());

		$rows=$db->loadAssocList();

		if(count($rows)<1)
		{
			$a=array();//for compatibility
			return $a;
		}

		$this->row=$rows[0];
		return $row['id'];

	}

	function processCustomListingID()
	{
		$db = JFactory::getDBO();

		if($this->id==0)
			$this->id=$this->params->get('listingid');

		if(is_numeric($this->id))
		{
			$query = 'SELECT * FROM '.$this->tablename.' WHERE id='.(int)$this->id.' LIMIT 1';
			$db->setQuery($query);
			$rows=$db->loadAssocList();
			if(count($rows)<1)
				return -1;

			$this->row=$rows[0];
			$this->row['listing_id']=$this->id;
			return $this->id;
		}


		$filter=$this->id;
		if($filter=='')
			return 0;

		$LayoutProc=new LayoutProcessor;
		$LayoutProc->Model=$this;
		$LayoutProc->layout=$filter;
		$filter=$LayoutProc->fillLayout(array(),null,array(),'[]',true);

				$filtering=new ESFiltering;
				$filtering->langpostfix=$this->langpostfix;
				$filtering->es=$this->es;
				$filtering->esfields=$this->esfields;

				$PathValue=array();
				$paramwhere=$filtering->getWhereExpression($filter,$PathValue);

				if($paramwhere!='')
						$wherearr[]=' ('.$paramwhere.' )';

			$wherearr[]='published=1';

			if(count($wherearr)>0)
				$where = ' WHERE '.implode(" AND ",$wherearr);

			$query = 'SELECT id FROM '.$this->tablename.' '.$where;

			$query.=' ORDER BY id DESC'; //show last
			$query.=' LIMIT 1';


		$db->setQuery($query);

		
		$rows=$db->loadAssocList();


		if(count($rows)<1)
		{
			$this->row=array();;
			return 0;
		}

		$this->row=$rows[0];
		return $row['id'];
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
				    $this->row=$this->makeEmptyRecord($this->id,$new_row['published']);

				    //Copy values
				    foreach($this->esfields as $ESField)
						$this->row['es_'.$ESField['fieldname']]=$new_row['es_'.$ESField['fieldname']];
				}
		    }
		}
	}
	
	function makeEmptyRecord($id,$published)
	{
	    $row=array();
	    $row['id']=$id;
	    $row['published']=$published;


	    foreach($this->Model->esfields as $ESField)
		$row['es_'.$ESField['fieldname']]='';


	    return $row;
	}



	function getTypeFieldName($type)
	{
		foreach($this->Model->esfields as $ESField)
		{
				if($ESField['type']==$type)
					return 'es_'.$ESField['fieldname'];

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
						$new_row['published']=$row['published'];
						$new_row['id']=$row['id'];
						$new_row['listing_id']=$row['id'];
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
			if($action==1 and $this->id==0)
				$action=4; //add new
		}

		$Itemid=JFactory::getApplication()->input->getInt("Itemid");
		$guestcanaddnew=JoomlaBasicMisc::getMenuParam('guestcanaddnew', $Itemid);


		if($guestcanaddnew==1)
			return true;

		if($guestcanaddnew==-1 and $this->id==0)
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



		// --- This can be replaced by tableid

		if($this->establename=='')
			$this->establename=$this->params->get( 'establename' ); //-- use this only

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

		$this->isUserAdministrator=JoomlaBasicMisc::isUserAdmin($this->userid);
		if($this->isUserAdministrator)
		{
			//Administrator has access to anything

			$this->isAutorized=true;
			return true;
		}


		if($this->id==0 or $this->useridfield=='')
		{
			$this->isAutorized=JoomlaBasicMisc::checkUserGroupAccess($this->userGroup);
			return $this->isAutorized;
		}

		$theAnswerIs=false;

		if($this->useridfield!='')
			$theAnswerIs=$this->checkIfItemBelongsToUser($this->useridfield);

		if($theAnswerIs==false)
		{
			$this->isAutorized=JoomlaBasicMisc::checkUserGroupAccess($this->publish_userGroup);
			return $this->isAutorized;
		}

		return true;
	}

	function findUserIDField()
	{
		if($this->Itemid==0)
		{
			$Itemid=JFactory::getApplication()->input->getInt("Itemid");
			$useridfield=JoomlaBasicMisc::getMenuParam('useridfield', $Itemid);
			if($this->establename=='')
				$this->establename=JoomlaBasicMisc::getMenuParam('establename', $Itemid);
		}
		else
		{
			$useridfield=$this->params->get('useridfield');
		}

		if($this->establename=='')
		{
			JFactory::getApplication()->enqueueMessage('Table not selected..', 'error');
			return '';
		}



		$this->tablename='#__customtables_table_'.$this->establename;

		$this->tablerow=ESTables::getTableRowByNameAssoc($this->establename);

		$this->estableid=$this->tablerow['id'];
		$this->esfields= ESFields::getFields($this->estableid);


		if($useridfield!='')
		{
			//find selected field
			foreach($this->esfields as $esfield)
			{
				if($esfield['fieldname']==$useridfield and ($esfield['type']=='userid' or $esfield['type']=='user'))
				{

					$this->useridfield=$esfield['fieldname'];

					$params=$esfield['typeparams'];
					$parts=JoomlaBasicMisc::csv_explode(',', $params, '"', false);

					$this->useridfield_uniqueusers=false;
					if(isset($parts[4]) and $parts[4]=='unique')
						$this->useridfield_uniqueusers=true;

					return $this->useridfield;
				}
			}
		}

		return '';
	}

	function checkIfItemBelongsToUser($useridfield)
	{
		//check if the item belong to the user
		$db = JFactory::getDBO();

		$query='SELECT id FROM '.$this->tablename.' WHERE id='.$this->id.' AND es_'.$useridfield.'='.$this->userid.' LIMIT 1';

		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

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
		$filter_rootparent=$this->es->getOptionIdFull($optionname);

		if($optionname)
		{
		    $available_categories=$this->getAllChild($optionid,$filter_rootparent,1, $langpostfix,$optionname);

		    $db = JFactory::getDBO();
		    $query = ' SELECT optionname, id, title_'.$langpostfix.' AS title FROM #__customtables_options WHERE ';
		    $query.= ' id='.$filter_rootparent.' LIMIT 1';

		    $db->setQuery( $query );

		    $rpname= $db->loadObjectList();

			if($startfrom==0)
			{
			if(count($rpname)==1)
				$this->array_insert(
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
		    $available_categories=$this->getAllChild($optionid,0,1, $langpostfix,'');
		}
		if($defaultvalue)
			$this->es->array_insert(
			    $available_categories,
			    array(
						"id" => 0,
						"name" => $defaultvalue,
						"fullpath" => ''

						),0);



		if($startfrom==0)
		$this->array_insert($available_categories,
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
		//INSERT INTO ow94h_customtables_table_years (published, es_year) SELECT published, es_year FROM ow94h_customtables_table_years WHERE id = 2018

		$jinput = JFactory::getApplication()->input;
		$id=$jinput->get('listing_id',0,'INT');

		$db = JFactory::getDBO();

		$query='SELECT MAX(id) AS maxid FROM '.$this->tablename.' LIMIT 1';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());
		$rows=$db->loadObjectList();
		if(count($rows)==0)
		{
				$msg='Table not found or something wrong.';
		}

		$new_id=(int)($rows[0]->maxid)+1;

		$query='DROP TEMPORARY TABLE IF EXISTS tmp';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		$query='CREATE TEMPORARY TABLE tmp SELECT * FROM '.$this->tablename.' WHERE id = '.$id.';';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		$sets=array();
		$sets[]='id='.$new_id;
		
		/*
		foreach($this->esfields as $esfield)
		{
			//update "userid" type fields to assign new author to copied record.
			if($esfield['type']=='userid')
			{
				$user = JFactory::getUser();
					
				if($user->id!=0)
					$sets[]='es_'.$esfield['fieldname'].'='.$user->id;
			}
			
			//update creation date if needed
			if($esfield['type']=='creationtime')
				$sets[]='es_'.$esfield['fieldname'].'='.$db->Quote(gmdate( 'Y-m-d H:i:s'));
				
				
				//$this->doPHPonAdd($row);
		}
		*/

		$query='UPDATE tmp SET '.implode(',',$sets).' WHERE id = '.$id.';';
		$db->setQuery( $query );
		$db->execute();

		$query='INSERT INTO '.$this->tablename.' SELECT * FROM tmp WHERE id = '.$new_id.';';
		$db->setQuery( $query );
		$db->execute();

		$jinput->set('listing_id',$new_id);
		$jinput->set('old_listing_id',$id);
		
		

		$query='DROP TEMPORARY TABLE IF EXISTS tmp';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());

		return $this->store($msg,$link,true);

	}

	function store(&$msg,&$link,$isCopy=false)
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

		$id=(int)$this->params->get('listingid');

		if($id==0)
			$id= $jinput->getInt('listing_id', 0); //TODO : this inconsistancy must be fixed

		$fieldstosave=$this->getFieldsToSave(); //will Read page Layout to find fields to save

		$msg='';
		$savequery=array();
		$db = JFactory::getDBO();

		$user_email='';
		$user_name='';



		//	Fields
		$prefix='comes_';

		$phponchangefound=false;
		$phponaddfound=false;

		$create_new_user=null;

		$default_fields_to_apply=array();

		foreach($this->esfields as $esfield)
		{
			$fieldname=$esfield['fieldname'];

			$value_found=false;
			if(in_array($fieldname,$fieldstosave))
			{
				$value_found=CTValue::getValue($id,$this->es,$esfield,$savequery,$prefix,$this->establename,$this->LanguageList,$fieldstosave);
				if(!$value_found)
					$default_fields_to_apply[]=array($fieldname,$esfield['defaultvalue'],$esfield['type']);
			}

			switch($esfield['type'])
			{
				case 'creationtime':
					if($id==0 or $isCopy)
						$savequery[]='es_'.$fieldname.'='.$db->Quote(gmdate( 'Y-m-d H:i:s'));
					break;

				case 'changetime':
						$savequery[]='es_'.$fieldname.'='.$db->Quote(gmdate( 'Y-m-d H:i:s'));
					break;

				case 'phponadd':
					if($id==0 or $isCopy)
					{
						$phponaddfound=true;
					}
					break;

				case 'phponchange':
						$phponchangefound=true;
					break;


				case 'server':
						$typeparams=$esfield['typeparams'];
						if($typeparams=='')
							$value=$this->getUserIP(); //Try to get client real IP
						else
							$value=$jinput->server->get($typeparams,'','STRING');

						$savequery[]='es_'.$fieldname.'='.$db->Quote($value);
					break;

				case 'userid':

					if(($id==0 or $isCopy) and !$value_found)//set new userid if record added or copied
					{
						$user = JFactory::getUser();

						if($user->id!=0)
							$savequery[]='es_'.$fieldname.'='.$user->id;
						else
							$savequery[]='es_'.$fieldname.'=0';
					}

					break;
				case 'user':
					$create_new_user=$esfield;
					break;

				case 'id':
					//get max id
					if($id==0 or $isCopy)
					{
						$minid=(int)$typeparams;


						$query='SELECT MAX(es_'.$fieldname.') AS maxid FROM '.$this->tablename.' LIMIT 1';
						$db->setQuery( $query );
						if (!$db->query())    die( $db->stderr());
						$rows=$db->loadObjectList();
						if(count($rows)!=0)
						{
							$i=(int)($rows[0]->maxid)+1;
							if($i<$minid)
								$i=$minid;

							$savequery[]='es_'.$fieldname.'='.$i;
						}
					}
					break;
			}

			if($this->params->get('emailfield')!='' and $this->params->get('emailfield')==$fieldname)
				$user_email=$jinput->getString($prefix.$fieldname);

			if($this->params->get('fullnamefield')!='' and $this->params->get('fullnamefield')==$fieldname)
				$user_name=$jinput->getString($prefix.$fieldname);


		}//foreach($this->esfields as $esfield)






		$row_old=array();

		if($id==0)
		{
			$isitnewrecords	=true;
			if($this->params->get('eseditlayout')!='')
				$publishstatus=1; //Pubished by default
			else
				$publishstatus=(int)$this->params->get( 'publishstatus' );

			$savequery[]='published='.$publishstatus;

			$id_temp=$this->getAvailableID($this->tablename);
			$savequery[]='id='.$id_temp;

			$query='INSERT '.$this->tablename.' SET '.implode(', ',$savequery);
		}

		else
		{
			//get old row
			$query='SELECT *, id AS listing_id FROM '.$this->tablename.' WHERE id='.$id.' LIMIT 1';
			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());
			$rows = $db->loadAssocList();
			if(count($rows)!=0)
				$row_old=$rows[0];

			$this->updateLog($id);
			$query='UPDATE '.$this->tablename.' SET '.implode(', ',$savequery).' WHERE id='.$id;
		}

		if(count($savequery)<1)
		{
			JFactory::getApplication()->enqueueMessage('Nothing to save', 'Warning');
			return false;
		}

		$db->setQuery( $query );
		$db->execute();

		if($id==0)
		{
			$query='SELECT *, id AS listing_id FROM '.$this->tablename.' ORDER BY id DESC LIMIT 1';
			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());
			$rows = $db->loadAssocList();
			if(count($rows)!=0)
			{
				$row=$rows[0];
				JFactory::getApplication()->input->set('listing_id',$row['listing_id']);

				if($phponaddfound)
					$this->doPHPonAdd($row);

				if($phponchangefound)
					$this->doPHPonChange($row);
					
				$this->updateDefaultValues($row);

				$listing_id=$row['listing_id'];
			}


			ESLogs::save($this->estableid,$listing_id,1);

		}
		else
		{
				$listing_id=$id;
				ESLogs::save($this->estableid,$listing_id,2);


				$query='SELECT *, id AS listing_id FROM '.$this->tablename.' WHERE id='.$id.' LIMIT 1';
				$db->setQuery( $query );
				if (!$db->query())    die( $db->stderr());
				$rows = $db->loadAssocList();

				if(count($rows)!=0)
				{
					$row=$rows[0];
					JFactory::getApplication()->input->set('listing_id',$row['listing_id']);

					if($phponchangefound or $this->tablecustomphp!='')
						$this->doPHPonChange($row);
						
					if($phponaddfound and $isCopy)
						$this->doPHPonAdd($row);
						
					$this->updateDefaultValues($row);

				}
		}

		//update MD5s
		$this->updateMD5($listing_id);
		CTValue::processDefaultValues($default_fields_to_apply,$this,$row);

		if($create_new_user!=null and (int)$row['published']==1)
		{
			CTValue::Try2CreateUserAccount($this,$create_new_user,$row);

		}

		//Send email note if applicable
		
		$new_username='';
		$new_password='';
		
		if($this->onrecordaddsendemail==3 and ($this->onrecordsavesendemailto!='' or $this->onrecordaddsendemailto!=''))
		{
			//check conditions
			
				if($this->checkSendEmailConditions($listing_id,$this->sendemailcondition))
				{
					//Send email conditions met
					$this->sendEmailIfAddressSet($listing_id,$new_username,$new_password);
				}
		}
		else
		{
			if($id==0 or $isCopy)
			{
				//New record
				if($this->onrecordaddsendemail==1 or $this->onrecordaddsendemail==2)
					$this->sendEmailIfAddressSet($listing_id,$new_username,$new_password);

			}
			else
			{
				//Old record
				if($this->onrecordaddsendemail==2)
				{
					$this->sendEmailIfAddressSet($listing_id,$new_username,$new_password);
				}

			}
		}


		//Prepare "Accept Return To" Link
		$art_link=$this->PrepareAcceptReturnToLink(JFactory::getApplication()->input->get( 'returnto','','BASE64' ));
		if($art_link!='')
			$link=$art_link;

		$link=str_replace('*new*',$row['id'],$link);

		//Refresh menu if needed
		$msg= $this->itemaddedtext;

		if($this->tablecustomphp!='')
			$this->doCustomPHP($row,$row_old);

		if($isDebug)
		{
			echo 'Debug mode.';
			die ;//debug mode
		}	
		$jinput->setVar('listing_id',$listing_id);
			
		return true;

	}

	function getAvailableID($establename)
    {
        $db = JFactory::getDBO();
        $query='SELECT MAX(id) AS maxid FROM '.$establename.' LIMIT 1';

		$db->setQuery( $query );
		
		
		//if (!$db->query())    die( $db->stderr());
		$rows=$db->loadObjectList();
		if(count($rows)!=0)
		{
            $i=(double)($rows[0]->maxid)+1;
        }
        else
            $i=1;

        return $i;
    }


	function Refresh($save_log=1)
	{
		$ids_str=JFactory::getApplication()->input->getString('ids', '');

		if($ids_str!='')
		{
			$ok=true;
			$ids_=explode(',',$ids_str);
			foreach($ids_ as $id)
			{
				if((int)$id!=0)
				{
					$id=(int)$id;
					$isok=$this->RefreshSingleRecord($id,$save_log);
					if(!$isok)
						$ok=false;
				}
			}
			return 	$ok;
		}

		$id= JFactory::getApplication()->input->getInt('listing_id', 0);

		if($id==0)
			return false;

		return $this->RefreshSingleRecord($id,$save_log);
	}

	protected function RefreshSingleRecord($id,$save_log)
	{

			$db = JFactory::getDBO();
			$query='SELECT *, id AS listing_id FROM '.$this->tablename.' WHERE id='.$id.' LIMIT 1';
			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());
			$rows = $db->loadAssocList();
			if(count($rows)==0)
				return false;

			$row=$rows[0];
			JFactory::getApplication()->input->set('listing_id',$id);

			$this->doPHPonChange($row);

			//update MD5s
			$this->updateMD5($id);

			if($save_log==1)
				ESLogs::save($this->estableid,(int)$id,10);

			$this->updateDefaultValues($row);


			if($this->tablecustomphp!='')
				$this->doCustomPHP($row, $row);



		$create_new_user=null;

		foreach($this->esfields as $esfield)
		{
			if($esfield['type']=='user')
			{
				$create_new_user=$esfield;
				break;
			}
		}

		if($create_new_user!=null and (int)$row['published']==1)
			CTValue::Try2CreateUserAccount($this,$create_new_user,$row);

		
		
		//Send email note if applicable
		
		if($this->onrecordaddsendemail==3 and ($this->onrecordsavesendemailto!='' or $this->onrecordaddsendemailto!=''))
		{
			
			//check conditions
			if($this->checkSendEmailConditions($id,$this->sendemailcondition))
			{
				//Send email conditions met
				$this->sendEmailIfAddressSet($id,$new_username,$new_password);
			}
		}
		
		return true;
	}


	function sendEmailIfAddressSet($listing_id,$new_username,$new_password)
	{
		$status=0;
		if($this->onrecordaddsendemailto!='')
			$status=$this->sendEmailNote($listing_id,$this->onrecordaddsendemailto,$new_username,$new_password);
		else
			$status=$this->sendEmailNote($listing_id,$this->onrecordsavesendemailto,$new_username,$new_password);
		
		if($this->emailsentstatusfield!='')
		{
			foreach($this->esfields as $esfield)
			{
				$fieldname=$esfield['fieldname'];
				if($this->emailsentstatusfield==$fieldname)
				{
					$db = JFactory::getDBO();
					$query='UPDATE '.$this->tablename.' SET es_'.$fieldname.'='.(int)$status.' WHERE id='.$listing_id;

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
			
		$row=$this->getListingRowByID($listing_id);
		$parsed_condition=$this->parseRowLayoutContent($row,$condition,true);
		$thescript='return '.$parsed_condition.';';
		$value=eval($thescript);

		if($value==1)
			return true;

		return false;

	}


	function updateMD5($id)
	{
		$savequery=array();
		foreach($this->esfields as $esfield)
		{

				if($esfield['type']=='md5')
				{
						$fieldstocount=explode(',',str_replace('"','',$esfield['typeparams']));//only field names, nothing else
						
						$flds=array();
						foreach($fieldstocount as $f)
						{
							//to make sure that field exists
							foreach($this->esfields as $esfield_)
							{
								if($esfield_['fieldname']==$f and $esfield['fieldname']!=$f)
									$flds[]='COALESCE(es_'.$f.')';
							}
						}

						if(count($flds)>1)
							$savequery[]='es_'.$esfield['fieldname'].'=md5(CONCAT_WS('.implode(',',$flds).'))';
						//else
							//$savequery[]='es_'.$esfield['fieldname'].'=md5('.implode(',',$flds).')';

				}
		}

        CTValue::runQueries($this,$savequery,$id);
	}

	function updateDefaultValues($row)
	{
		$default_fields_to_apply=array();

		foreach($this->esfields as $esfield)
		{
			$fieldname=$esfield['fieldname'];
			if($esfield['defaultvalue']!='' and $row['es_'.$fieldname]=='')
				$default_fields_to_apply[]=array($fieldname,$esfield['defaultvalue'],$esfield['type']);

		}

        CTValue::processDefaultValues($default_fields_to_apply,$this,$row);
	}


	function updateLog($id)
	{
		if($id==0)
			return;

		$db = JFactory::getDBO();

		//saves previous version of the record
		//get data
		$fields_to_save=array();
		foreach($this->esfields as $esfield)
		{
			if($esfield['type']=='multilangstring' or $esfield['type']=='multilangtext')
			{
				$firstlanguage=true;

				foreach($this->LangMisc->LanguageList as $lang)
				{
					if($firstlanguage)
					{
						$postfix='';
						$firstlanguage=false;
					}
					else
						$postfix='_'.$lang->sef;

					$fields_to_save[]='es_'.$esfield['fieldname'].$postfix;
				}
			}
			elseif($esfield['type']!='log' and $esfield['type']!='dummy')
				$fields_to_save[]='es_'.$esfield['fieldname'];

		}
		
		//get data
		$query = 'SELECT '.implode(',',$fields_to_save).' FROM #__customtables_table_'.$this->establename.' WHERE id='.$id.' LIMIT 1';

	 	$db->setQuery($query);
		if (!$db->query())	die( $db->stderr());
		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return;

		$data=base64_encode(json_encode($rows));

		$savequery=array();
		foreach($this->esfields as $esfield)
		{

				if($esfield['type']=='log')
				{
					$user = JFactory::getUser();
					$userid = (int)$user->get('id');
					$value=time().','.$userid.','.$this->getUserIP().','.$data.';';
					$savequery[]='es_'.$esfield['fieldname'].'=CONCAT(es_'.$esfield['fieldname'].',"'.$value.'")';
				}
		}

		if(count($savequery)>0)
		{
			$db = JFactory::getDBO();
			$query='UPDATE '.$this->tablename.' SET '.implode(', ',$savequery).' WHERE id='.$id;

			$db->setQuery( $query );
			$db->execute();
		}
	}

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
					foreach($this->LanguageList as $lang)
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
					$value = JComponentHelper::filterText(JFactory::getApplication()->input->post->get($prefix.$fieldname, '', 'raw'));
					break;

				case 'multilangtext':

					$firstlanguage=true;
					foreach($this->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$value_ = JComponentHelper::filterText(JFactory::getApplication()->input->post->get($prefix.$fieldname.$postfix, '', 'raw'));

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
					foreach($this->LanguageList as $lang)
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
							$value=$this->getMultiString($optionname, $prefix.'multi_'.$this->establename.'_'.$fieldname);
						elseif($typeparams_arr[1]=='single')
							$value=$this->getComboString($optionname, $prefix.'combotree_'.$this->establename.'_'.$fieldname);

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

		return eval($v);
	}



	function PrepareAcceptReturnToLink($artlink)
	{
		if($artlink=='')
			return '';

		$artlink=base64_decode ($artlink);


		if($artlink=='')
			return '';

		$mainframe = JFactory::getApplication('site');

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

		$LayoutProc=new LayoutProcessor;


		$LayoutProc->layout=$artlink;
		$LayoutProc->Model=$this;


		$db = JFactory::getDBO();
		$query = 'SELECT *, id AS listing_id FROM #__customtables_table_'.$this->establename.' ORDER BY id DESC LIMIT 1';
	 	$db->setQuery($query);
		if (!$db->query())	die( $db->stderr());
		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return '';

		$row=$rows[0];

		$processed_link=$LayoutProc->fillLayout($row,"",array(),'[]',true);

		return $processed_link;

	}

	function doPHPonAdd(&$row)
	{
		$id=$row['listing_id'];
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

		$LayoutProc=new LayoutProcessor;
		$LayoutProc->Model=$this;

		$savequery='';
		$db = JFactory::getDBO();

		foreach($this->esfields as $esfield)
		{
			$fieldname=$esfield['fieldname'];
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
						
						$thescript='return '.$this->applyContentPlugins($LayoutProc->fillLayout($row,'',array(),'[]',true)).';';
						
						$es=$this->es;
						
						try
						{
							$value=@eval($thescript);
						}
						catch (Exception $e)
						{
							echo $thescript;
						}
						
						$row['es_'.$fieldname]=$value;

						$savequery='es_'.$fieldname.'='.$db->quote($value);
						$query='UPDATE '.$this->tablename.' SET '.$savequery.' WHERE id='.$id;

						$db->setQuery( $query );
						$db->execute();

			}//if($esfield['type']=='phponadd')
		}//foreach($this->esfields as $esfield)



	}

	function doPHPonChange(&$row)
	{
		$id=$row['listing_id'];
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');

		$LayoutProc=new LayoutProcessor;
		$LayoutProc->Model=$this;//ok

		$db = JFactory::getDBO();

		foreach($this->esfields as $esfield)
		{
			$fieldname=$esfield['fieldname'];

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

						$thescript='return '.$this->applyContentPlugins($LayoutProc->fillLayout($row,'',array(),'[]',true)).';';
				
						$es=$this->es;
						
						try
						{
							$value=@eval($thescript);
						}
						catch (Exception $e)
						{
							echo $thescript;
						}
						
						$row['es_'.$fieldname]=$value;

						$savequery='es_'.$fieldname.'='.$db->quote($value);
						$query='UPDATE '.$this->tablename.' SET '.$savequery.' WHERE id='.$id;

						$db->setQuery( $query );
						$db->execute();
			}//if($esfield['type']=='phponchange')
		}//foreach($this->esfields as $esfield)


	}

	function applyContentPlugins($pagelayout)
	{

				$o = new stdClass();
				$o->text=$pagelayout;
				$o->created_by_alias = 0;

				$dispatcher	= JDispatcher::getInstance();

				JPluginHelper::importPlugin('content');

				$r = $dispatcher->trigger('onContentPrepare', array ('com_content.article', &$o, &$this->params_, 0));

		return $o->text;
	}



	function getListingRowByID($listing_id)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT *, id AS listing_id FROM #__customtables_table_'.$this->establename.' WHERE id='.$listing_id.' LIMIT 1';
	 	$db->setQuery($query);
		if (!$db->query())	die( $db->stderr());
		$rows = $db->loadAssocList();
		if(count($rows)!=1)
			return false;

		return $rows[0];
	}

	function parseRowLayoutContent(&$row,$content,$applyContentPlagins=true)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
		$LayoutProc=new LayoutProcessor;
		$LayoutProc->Model=$this;
		$LayoutProc->advancedtagprocessor=$this->advancedtagprocessor;
		$LayoutProc->layout=$content;
		$content=$LayoutProc->fillLayout($row,'','');
		if($applyContentPlagins)
			$LayoutProc->applyContentPlugins($content);
		
		return $content;
	}

	function sendEmailNote($listing_id,$emails,$new_username,$new_password)
	{
		$mainframe = JFactory::getApplication('site');
		$row=$this->getListingRowByID($listing_id);
		//Prepare Email List

		$emails_raw=JoomlaBasicMisc::csv_explode(',', $emails, '"', true);

		$emails=array();
		foreach($emails_raw as $SendToEmail)
		{
			$EmailPair=JoomlaBasicMisc::csv_explode(':', trim($SendToEmail), '"', false);
			
			$EmailTo=$this->parseRowLayoutContent($row,trim($EmailPair[0]),false);
			$Subject='Record added to "'.$this->tabletitle.'"';


			if(isset($EmailPair[1]))
			{
				if($EmailPair[1]!='')
				{
					$Subject=$this->parseRowLayoutContent($row,$EmailPair[1],true);
				}
			}

			if($EmailTo!='')
				$emails[]=array('email' => $EmailTo, 'subject' => $Subject);
		}

		//-----------
		$layouttype=0;
		$message_layout_content=ESLayouts::getLayout($this->onrecordaddsendemaillayout,$layouttype);
		$note=$this->parseRowLayoutContent($row,$message_layout_content,true);


		$MailFrom 	= $mainframe->getCfg('mailfrom');
		$FromName 	= $mainframe->getCfg('fromname');

		$note=str_replace('[_username]',$new_username,$note);
		$note=str_replace('[_password]',$new_password,$note);
		
		$status=0;

		foreach($emails as $SendToEmail)
		{
			//$EmailTo='ivankomlev@gmail.com';

			$Subject=$SendToEmail['subject'];

			$mail = JFactory::getMailer();

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
					$vlu='<p>File not found.</p>';


				$note_final=str_replace($fItem,'',$note);
				$i++;
			}

			$mail->IsHTML(true);
			$mail->addRecipient($EmailTo);
			$mail->setSender( array($MailFrom,$FromName) );
			$mail->setSubject( $Subject);
			$mail->setBody( $note_final );
			
			foreach($this->esfields as $esfield)
			{
				if($esfield['type']=='file')
				{

					$filename='images/esfiles/'.$row['es_'.$esfield['fieldname']];
					if(file_exists($filename))
							$mail->addAttachment($filename);

				}

			}

			$sent = $mail->Send();

			if ( $sent !== true ) {
				//echo 'Something went wrong. Email not sent.';
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

	function setPublishStatus($status)
	{
		$ids_str=JFactory::getApplication()->input->getString('ids', '');
		if($ids_str!='')
		{
			$ok=true;
			$ids_=explode(',',$ids_str);
			foreach($ids_ as $id)
			{
				if((int)$id!=0)
				{
					$id=(int)$id;
					$isok=$this->setPublishStatusSingleRecord($id,$status);
					if(!$isok)
						$ok=false;
				}
			}
			return 	$ok;
		}

		$id= $this->id;
		if($id==0)
			return false;

		return $this->setPublishStatusSingleRecord($id,$status);
	}

	protected function setPublishStatusSingleRecord($id,$status)
	{
		$db = JFactory::getDBO();
		$t=$this->establename;
		if(strpos('#__',$t)===false)
			$t='#__customtables_table_'.$t;

		$query = 'UPDATE '.$t.' SET published='.$status.' WHERE id='.(int)$id;

	 	$db->setQuery($query);
		$db->execute();	

		if($status==1)
			ESLogs::save($this->estableid,(int)$id,3);
		else
			ESLogs::save($this->estableid,(int)$id,4);

		$this->RefreshSingleRecord((int)$id,0);

		return true;
	}

	function doCustomPHP(&$row=array(),&$row_old=array())
	{
		$servertagprocessor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'servertags.php';

		if(!file_exists($servertagprocessor_file))
			return;

		if($this->tablecustomphp!='')
		{
			$parts=explode('/',$this->tablecustomphp); //just a security check
			if(count($parts)>1)
				return;

			$file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'customphp'.DIRECTORY_SEPARATOR.$this->tablecustomphp;
			if(file_exists($file))
			{
				require_once($file);
				$function_name='CTCustom_'.str_replace('.php','',$this->tablecustomphp);

				if(function_exists ($function_name))
				{
					call_user_func($function_name,$row,$row_old);
					return true;
				}

				$function_name='ESCustom_'.str_replace('.php','',$this->tablecustomphp);
				if(function_exists ($function_name))
				{
					call_user_func($function_name,$row,$row_old);
					return true;
				}
			}
		}
		return false;

	}







	function getFieldsToSave()
	{
		$fields=array();


		foreach($this->esfields as $esfield)
		{
			$fn=$esfield['fieldname'];
			$fn_str=array();
			$fn_str[]='['.$fn.':';
			$fn_str[]='['.$fn.']';

			$fn_str[]='"comes_'.$fn.'"';
			$fn_str[]="'comes_".$fn."'";
			
			$fn_str[]='[_edit:'.$fn.':';



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
	
	
	//--------------
	function delete()
		{
			$jinput = JFactory::getApplication()->input;

			$ids_str=$jinput->getString('ids','');
			if($ids_str!='')
			{
				$ok=true;
				$ids_=explode(',',$ids_str);
				foreach($ids_ as $id)
				{
					if((int)$id!=0)
					{
						$id=(int)$id;
						$isok=$this->deleteSingleRecord($id);
						if(!$isok)
						{
							$ok=false;
						}
					}
				}
				return 	$ok;
			}

			if(!$jinput->getInt('listing_id',0))
				return false;

			$id=$jinput->getInt('listing_id',0);
			if($id==0)
				return false;

			return $this->deleteSingleRecord($id);
		}

		protected function deleteSingleRecord($objectid)
		{
						$db = JFactory::getDBO();

						//delete images if exist
						require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'imagemethods.php');
						$imagemethods=new CustomTablesImageMethods;

						$query='SELECT * FROM #__customtables_table_'.$this->establename.' WHERE id='.$objectid;

						$db->setQuery($query);
						//if (!$db->query())   die ;
						$rows=$db->loadAssocList();


						if(count($rows)==0)
						{

								return false;
						}

						$row=$rows[0];


						foreach($this->esfields as $esfield)
						{
								if($esfield['type']=='image')
								{
										//delete single image
										$imagemethods->DeleteExistingSingleImage(
												$row['es_'.$esfield['fieldname']],
												$this->imagefolder,
												$esfield['typeparams'],
												$this->establename,
												$esfield['fieldname']
										);
								}
								elseif($esfield['type']=='imagegallery')
								{
										//delete gallery images if exist
										$galleryname=$esfield['fieldname'];
										$phototablename='#__customtables_gallery_'.$this->establename.'_'.$galleryname;

										$query = 'SELECT photoid FROM '.$phototablename.' WHERE listingid='.$objectid;
										$db->setQuery($query);
										//if (!$db->query())    die ;
										$photorows=$db->loadObjectList();

										foreach($photorows as $photorow)
										{
												$imagemethods->DeleteExistingGalleryImage(
														$this->imagefolder,
														$this->imagegalleryprefix,
														$this->estableid,
														$galleryname,
														$photorow->photoid,
														$esfield['typeparams'],
														true
												);
										}//foreach($photorows as $photorow)

								}//elseif($esfield[type]=='imagegallery')
						}//foreach($this->esfields as $esfield)

						$query='DELETE FROM #__customtables_table_'.$this->establename.' WHERE id='.$objectid;
						$db->setQuery($query);
						$db->execute();

						ESLogs::save($this->estableid,$objectid,5);

						$new_row=array();
						$this->doCustomPHP($new_row,$row);

						return true;

		}


}
