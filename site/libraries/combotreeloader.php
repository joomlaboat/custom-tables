<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

	$independat=false;

	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

	if(!defined('_JEXEC'))
	{

		//Indipendat
		define( '_JEXEC', 1 );


			$path=dirname(__FILE__);
			$path_p=strrpos($path,DIRECTORY_SEPARATOR);
			$path=substr($path,0,$path_p);
			$path_p=strrpos($path,DIRECTORY_SEPARATOR);
			$path=substr($path,0,$path_p);
			$path_p=strrpos($path,DIRECTORY_SEPARATOR);
			$path=substr($path,0,$path_p);

			define('JPATH_BASE', $path);
			define('JPATH_SITE', $path);

			require_once ( JPATH_BASE .DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'defines.php' );
			require_once ( JPATH_BASE .DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'framework.php' );

			JDEBUG ? $_PROFILER->mark( 'afterLoad' ) : null;

			// CREATE THE APPLICATION

				// Instantiate the application.
				$app = JFactory::getApplication('site');

				// Initialise the application.
				$app->initialise();
		$independat=true;
	}
	else
	{
		$independat=false;


	}




if($independat)
{


	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'combotreeloader.php');

	$establename=JFactory::getApplication()->input->get('establename','','CMD');
	$esfieldname=JFactory::getApplication()->input->get('esfieldname','','CMD');
	$optionname=JFactory::getApplication()->input->getCmd('optionname');

	$MyESDynCombo=new ESDynamicComboTree();
	$MyESDynCombo->initialize($establename,$esfieldname,$optionname,JFactory::getApplication()->input->getString('prefix'));
	$MyESDynCombo->langpostfix=JFactory::getApplication()->input->getCmd('langpostfix','');
	$MyESDynCombo->cssstyle=JFactory::getApplication()->input->getString('cssstyle');
	$MyESDynCombo->onchange=JFactory::getApplication()->input->getString('onchange');
	$MyESDynCombo->innerjoin=JFactory::getApplication()->input->getInt('innerjoin');
	$MyESDynCombo->isRequired=JFactory::getApplication()->input->get('isrequired',0,'INT');
	$MyESDynCombo->requirementdepth=JFactory::getApplication()->input->getInt('requirementdepth');
	$MyESDynCombo->where=JFactory::getApplication()->input->getString('where');
	$MyESDynCombo->parentname='';


	$filterwhere='';
	$filterwherearr=array();
	$urlwhere='';
	$urlwherearr=array();

	$html_=$MyESDynCombo->renderComboBox($filterwhere, $urlwhere, $filterwherearr, $urlwherearr);



	echo $html_;



}
else
{

	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'customtablesmisc.php');
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
	$MyESDynCombo=new ESDynamicComboTree();
	$MyESDynCombo->parentname='';
}


	JHTML::script('combotree16.js', 'components/com_customtables/js/customtables/');

class ESDynamicComboTree
{

var $es;
var $LangMisc;
var $ObjectName;
var $langpostfix;
var $establename;
var $esfieldname;
var $listingtable;
var $optionname;
var $cssstyle='';
var $onchange='';
var $innerjoin;
var $where;
var $parentname;
var $prefix;
var $isRequired;
var $requirementdepth;

function __construct()
{


}

function initialize($tablename,$fieldname,$optionname,$prefix)
{
		$this->requirementdepth=0;
		$this->prefix=$prefix;
		$this->es= new CustomTablesMisc;
		$this->LangMisc	= new ESLanguages;
		$this->langpostfix=$this->LangMisc->getLangPostfix();
		$this->establename=$tablename;
		$this->esfieldname=$fieldname;
		$this->optionname=$optionname;
		$this->listingtable='#__customtables_table_'.$this->establename;
		$this->ObjectName=$this->prefix.'escombotree_'.$this->establename.'_'.$this->esfieldname;
}

function getInstrWhereAdv($object_name,$temp_parent, &$filterwhere, &$urlwhere, &$filterwherearr,&$urlwherearr,$field)
{
    if(strlen(JFactory::getApplication()->input->getString($object_name, '' ))>0)
    {

        $filterwherearr[]='INSTR('.$this->listingtable.'.es_'.$field.', ",'.$temp_parent.'.")';
        $urlwherearr[]=$object_name.'='.JFactory::getApplication()->input->getCmd( $object_name, '' );
    }
    if(count($filterwherearr)>0)
    {
        $filterwhere = ' '.implode(" AND ",$filterwherearr);
        $urlwhere = ' '.implode("&",$urlwherearr);

    }
}

function CleanLink($newparams, $deletewhat)
{
		$i=0;
		do
		{
		    if(!(strpos($newparams[$i],$deletewhat)===false))
		    {
			unset($newparams[$i]);
			$newparams=array_values($newparams);
			if(count($newparams)==0) return $newparams;

			$i=0;

		    }
		    else
			$i++;

		}while($i<count($newparams));

		return $newparams;
}


function renderSelectBox($objectname, $rows, $urlwhere, $optionalOptions, $simpleList=false,$value='')
{
    if(count($rows)==1)
    {
	  if($rows[0]->tempid=="na") //optionname
	  {
        return "";
	  }

    }elseif(count($rows)==0)
	    return "";

    $optionalarr=$this->CleanLink(explode("&",$urlwhere), $objectname);
    $optional=implode("&", $optionalarr);




	if($value=='')
		$value=JFactory::getApplication()->input->getCmd( $objectname, '' );



    $result='';

	$WebsiteRoot=JURI::root(true);
	$WebsiteRoot=str_replace("/components/com_customtables/libraries/","",$WebsiteRoot);
	$WebsiteRoot=str_replace("/components/com_customtables/libraries","",$WebsiteRoot);

	if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
		$WebsiteRoot.='/';

	if($simpleList)
		$onChange='';
	else
		$onChange=' onChange="comboSERefreshMe('
		.'\''.$WebsiteRoot.'\', '
		.'this, '
		.'\''.$objectname.'\', '
		.'\''.$this->ObjectName.'\', '
		.'\''.$optional.'\', '
		.'\''.$this->establename.'\', '
		.'\''.$this->esfieldname.'\', '
		.'\''.$this->optionname.'\', '
		.'\''.$this->innerjoin.'\', '
		.'\''.urlencode($this->cssstyle).'\', '
		.'\''.$this->parentname.'\', '
		.'\''.urlencode($this->where).'\', '
		.'\''.$this->langpostfix.'\', '
		.'\''.urlencode($this->onchange).'\', '
		.'\''.urlencode($this->prefix).'\', '
		.'\''.((int)$this->isRequired).'\', '
		.'\''.((int)$this->requirementdepth).'\'); '
		.' " ';

	$result.='<select name="'.$objectname.'" id="'.$objectname.'" style="'.$this->cssstyle.'"'.$onChange.' '.$optionalOptions.' >';


	if($this->isRequired)
		$result.='<option value="" '.($value=="" ? ' SELECTED ':'').'>- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_SELECT' ).'</option>';
	else
		$result.='<option value="" '.($value=="" ? ' SELECTED ':'').'>- '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ALL' ).'</option>';


    $count=0;
     foreach($rows as $row)
     {

	  $result.='<option value="'.$row->tempid.'" '.($value==$row->tempid ? ' SELECTED ':'').'>'.$row->optiontitle;
      if($this->innerjoin)$result.=' ('.$row->listingcount.')';
      $result.='</option>
	  ';
	  $count++;
     }

     $result.='</select>';

      return $result;
}

function getOptionList($parentname, $langpostfix)
{
    $parentid=$this->es->getOptionIdFull($parentname);
    $db = JFactory::getDBO();

    $query = 'SELECT '
				.' id AS optionid, '
				.' optionname AS tempid, '
				.' title'.$this->langpostfix.' AS optiontitle '

	  .' FROM #__customtables_options '
	  .' WHERE parentid='.$parentid.' ';


     $query.=' ORDER BY ordering, optiontitle';


	$db->setQuery($query);
    if (!$db->query())    die( $db->stderr());
		return $db->loadObjectList();
}
function getOptionListWhere($parentname, $langpostfix,$filterwhere, $listingfield)
{

    $parentid=$this->es->getOptionIdFull($parentname);
    $db = JFactory::getDBO();

    $query = 'SELECT '
				.' #__customtables_options.id AS optionid, '
				.' optionname AS tempid, '
				.' #__customtables_options.title'.$this->langpostfix.' AS optiontitle, '
				.' COUNT('.$this->listingtable.'.id) AS listingcount
	  FROM #__customtables_options
	  INNER JOIN '.$this->listingtable.' ON INSTR('.$this->listingtable.'.es_'.$listingfield.',concat(",'.$parentname.'.",optionname,"."))
      ';

    $where=array();

	$where[]='#__customtables_options.published';
	$where[]='#__customtables_options.parentid='.$parentid;


	if($this->where!='')
		$where[]=$this->where;

	if($filterwhere!='')
		$where[]=$filterwhere;

    $query.= ' WHERE '.implode(' AND ',$where).' GROUP BY optionid ORDER BY ordering, optiontitle';



	$db->setQuery($query);
        if (!$db->query())    die( $db->stderr());

	return $db->loadObjectList();
}



function renderComboBox(&$filterwhere, &$urlwhere, &$filterwherearr,&$urlwherearr, $simpleList=false,$value='')
{
	$result='';

	$i=1;

	$temp_parent=$this->optionname;
	$this->parentname=$temp_parent;
	do
	{
		if($this->innerjoin)
			$rows=$this->getOptionListWhere($temp_parent, $this->langpostfix,$filterwhere,$this->esfieldname);
		else
			$rows=$this->getOptionList($temp_parent, $this->langpostfix);

		$object_name=$this->ObjectName.'_'.$i;

		$values=explode('.',$value);

		if(count($values)>0)
		{
			$value=$values[count($values)-1];
			if($value==',' and count($values)>1)
				$value=$values[count($values)-2];

		}

		if($result!='')
			$result.='<br/>';

		if($i<=$this->requirementdepth and $this->isRequired)
		{

			$result.=$this->renderSelectBox($object_name, $rows,$urlwhere,'class="inputbox required"',$simpleList,$value);
		}
		else
			$result.=$this->renderSelectBox($object_name, $rows,$urlwhere,'class="inputbox"',$simpleList,$value);


		if(JFactory::getApplication()->input->getCmd($object_name))
		{
			$temp_parent.='.'.JFactory::getApplication()->input->getCmd($object_name);
			$this->parentname=$temp_parent;

			$this->getInstrWhereAdv($object_name,$temp_parent, $filterwhere, $urlwhere, $filterwherearr,$urlwherearr,$this->esfieldname) ;
		}
		else
			break;

		$i++;

	}while(JFactory::getApplication()->input->getCmd($object_name));
	return $result;
}

}
