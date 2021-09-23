<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;

use CustomTables\Layouts;

class tagProcessor_General
{
    public static function process(&$Model,&$pagelayout,&$row,$recordlist,$number)
    {

        tagProcessor_General::TableInfo($Model,$pagelayout);
        $pagelayout=str_replace('{today}',date( 'Y-m-d', time() ),$pagelayout);

        tagProcessor_General::getDate($Model,$pagelayout);
        tagProcessor_General::getUser($Model,$pagelayout,$row,$recordlist,$number);
        tagProcessor_General::userid($Model,$pagelayout);
        tagProcessor_General::Itemid($Model,$pagelayout);
        tagProcessor_General::CurrentURL($Model,$pagelayout);
        tagProcessor_General::ReturnTo($Model,$pagelayout);
        tagProcessor_General::WebsiteRoot($Model,$pagelayout);
        tagProcessor_General::getGoBackButton($Model,$pagelayout);

		Layouts::processLayoutTag($pagelayout);
    }

    
    protected static function WebsiteRoot(&$Model,&$htmlresult)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('websiteroot',$options,$htmlresult,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
            $option=explode(',',$options[$i]);
            
            if($option[0]=='includehost')
                $WebsiteRoot=JURI::root(false);
            else
                $WebsiteRoot=JURI::root(true);
                
            $notrailingslash=false;
            if(isset($option[1]) and $option[1]=='notrailingslash')
                $notrailingslash=true;

            if($notrailingslash)
            {
                $l=strlen($WebsiteRoot);
                if($WebsiteRoot!='' and $WebsiteRoot[$l-1]=='/')
                    $WebsiteRoot=substr($WebsiteRoot,0,$l-1);//delete trailing slash
            }
            else
            {
                if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
                	$WebsiteRoot.='/';    
            }
			
            $htmlresult=str_replace($fItem,$WebsiteRoot,$htmlresult);
			$i++;
		}

	}

    protected static function TableInfo(&$Model,&$pagelayout)
    {
        tagProcessor_General::tableDesc($Model,$pagelayout,'table');
        tagProcessor_General::tableDesc($Model,$pagelayout,'tabletitle','title');
        tagProcessor_General::tableDesc($Model,$pagelayout,'description','description');
        tagProcessor_General::tableDesc($Model,$pagelayout,'tabledescription','description');

    }
    protected static function tableDesc(&$Model,&$pagelayout,$tag,$default='')
    {
        $options=array();
		$fList=JoomlaBasicMisc::getListToReplace($tag,$options,$pagelayout,'{}');
		$i=0;
		foreach($fList as $fItem)
		{
            $vlu='';

            $opts=explode(',',$options[$i]);
            $extraopt='';
            if($default=='')
            {
                $task=$opts[0];

                if(isset($opts[1]))
                    $extraopt=$opts[1];
            }
            else
            {
                $extraopt=$opts[0];
                $task=$default;
            }

            if($task=='id')
                $vlu=$Model->ct->Table->tablerow['id'];
            elseif($task=='title')
                $vlu=$Model->ct->Table->tablerow['tabletitle'.$Model->ct->Languages->Postfix];
            elseif($task=='description')
                $vlu=$Model->ct->Table->tablerow['description'.$Model->ct->Languages->Postfix];
			elseif($task=='fields')
                $vlu=json_encode(Fields::shortFieldObjects($Model->ct->Table->fields));

            if($extraopt=='box')
            {
                JFactory::getApplication()->enqueueMessage($vlu,'notice');//, 'error'
                $pagelayout=str_replace($fItem,'',$pagelayout);
            }
            else
                $pagelayout=str_replace($fItem,$vlu,$pagelayout);

			$i++;
		}
    }

    public static function CurrentURL(&$Model,&$pagelayout)
	{
		$jinput = JFactory::getApplication()->input;

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('currenturl',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$optpair=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);//explode(',',$options[$i]);
			$value='';
            
            if(isset($optpair[1]) and $optpair[1]!='')
            {
                switch($optpair[0])
                {
                    case '':
                        $value=strip_tags($jinput->getString($optpair[1],''));
                    case 'int':
                        $value=$jinput->getInt($optpair[1],0);
                        break;
                    case 'integer'://legacy
                        $value=$jinput->getInt($optpair[1],0);
                        break;
                    case 'uint':
                        $value=$jinput->get($optpair[1],0,'UINT');
                        break;
                    case 'float':
                        $value=$jinput->getFloat($optpair[1],0);
                        break;
                    case 'string':
                        $value=strip_tags($jinput->getString($optpair[1],''));
                        break;
                    case 'word':
                        $value=$jinput->get($optpair[1],'','WORD');
                        break;
                    case 'alnum':
                        $value=$jinput->get($optpair[1],'','ALNUM');
                        break;
                    case 'cmd':
                        break;
                        $value=$jinput->getCmd($optpair[1],'');
                        break;
                    case 'base64decode':
                        $value=strip_tags(base64_decode($jinput->get($optpair[1],'','BASE64')));
                        break;
                    case 'base64':
                        $value=base64_encode(strip_tags($jinput->getString($optpair[1],'')));
                        break;
                    case 'base64encode':
                        $value=base64_encode(strip_tags($jinput->getString($optpair[1],'')));
                        break;
                    case 'set':
                        if(isset($optpair[2]))
                            $jinput->set($optpair[1],$optpair[2]);
                        else
                            $jinput->set($optpair[1],'');
                        
                        $value='';
                        break;
                    default:
                        $value='Query unknown output type.';
                    break;
                }
            }
            else
            {
                switch($optpair[0])
                {
                    case '':
                        $value=$Model->ct->Env->current_url;
                        break;
                    case 'base64':
                        $value=base64_encode($Model->ct->Env->current_url);
                        break;
                    case 'base64encode':
                        $value=base64_encode($Model->ct->Env->current_url);
                        break;
                    default:
                        $value='Output type not selected.';
                    break;
                }
            }
            

			$pagelayout=str_replace($fItem,$value,$pagelayout);
			$i++;
		}
	}

    protected static function ReturnTo(&$Model,&$pagelayout)
	{
		//Depricated. Use 	{currenturl:base64} instead
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('returnto',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$pagelayout=str_replace($fItem,$Model->ct->Env->encoded_current_url,$pagelayout);
			$i++;
		}
	}

    protected static function Itemid(&$Model,&$pagelayout)
	{

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('itemid',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$vlu=$Model->Itemid;
			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}



    protected static function getUser(&$Model,&$pagelayout,&$row,$recordlist,$number)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('user',$options,$pagelayout,'{}');
        $user = JFactory::getUser();
		$i=0;
		foreach($fList as $fItem)
		{
            $opts=JoomlaBasicMisc::csv_explode(',', $options[$i],'"',false);

			if(isset($opts[1]))
            {
				$id_value=$opts[1];

                tagProcessor_Value::processValues($Model,$row,$id_value,'[]');
                tagProcessor_Item::process(false,$Model,$row,$id_value,'',array(),$recordlist,$number);
                tagProcessor_General::process($Model,$id_value,$row,$recordlist,$number);
                tagProcessor_Page::process($Model,$id_value);
                $id=(int)$id_value;
            }
			else
            {

                $id=(int)$user->get('id');
            }

			if($id!=0)
			{

				$user_row=tagProcessor_General::getUserRowByID($id);

				switch($opts[0])
				{
					case 'name':
						$vlu=$user_row->name;
						break;

					case 'username':
						$vlu=$user_row->username;
						break;

					case 'email':
						$vlu=$user_row->email;
						break;

					case 'id':
						$vlu=$id;
						break;

					case 'lastvisitDate':
						$vlu=$user_row->lastvisitDate;

						if($vlu=='0000-00-00 00:00:00')
							$vlu='Never';


						break;

					case 'registerDate':
						$vlu=$user_row->registerDate;

						if($vlu=='0000-00-00 00:00:00')
							$vlu='Never';

						break;

                    case 'usergroupsid':
						$vlu=implode(',',array_keys($user->groups));

						break;

                    case 'usergroups':

                        $db = JFactory::getDBO();

                        $groups = JAccess::getGroupsByUser($id);
                        $groupid_list		= '(' . implode(',', $groups) . ')';
                        $query  = $db->getQuery(true);
                        $query->select('title');
                        $query->from('#__usergroups');
                        $query->where('id IN ' .$groupid_list);
                        $db->setQuery($query);
                        $rows	= $db->loadRowList();
                        $grouplist	= array();
                        foreach($rows as $group)
                           $grouplist[]=$group[0];

                        $vlu=implode(',',$grouplist);

						break;



					default:
						$vlu='';
						break;
				}
			}
			else
				$vlu='';

			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    protected static function userid(&$Model,&$pagelayout)
	{
        $user = JFactory::getUser();
		$currentuserid=(int)$user->get('id');
        if($currentuserid!=0 and count($user->groups)>0)
		{
			$pagelayout=str_replace('{currentusertype}',implode(',',array_keys($user->groups)),$pagelayout);
		}
        else
        {
            $pagelayout=str_replace('{currentusertype}','0',$pagelayout);
        }


		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('currentuserid',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$pagelayout=str_replace($fItem,$currentuserid,$pagelayout);
			$i++;
		}
	}


    protected static function getDate(&$Model,&$pagelayout)
	{

  
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('date',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			if($options[$i]!='')
				$vlu = date($options[$i]);//,$phpdate );
			else
				$vlu = JHTML::date('now');


			$pagelayout=str_replace($fItem,$vlu,$pagelayout);
			$i++;
		}
	}

    protected static function TableTitle(&$Model,&$pagelayout,$establetitle)
    {

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('tabletitle',$options,$pagelayout,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
			$pagelayout=str_replace($fItem,$establetitle,$pagelayout);
			$i++;
		}
	}




	public static function getUserRowByID($id)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT * FROM #__users WHERE id='.(int)$id.' LIMIT 1';

		$db->setQuery($query);
        $rows=$db->loadObjectList();

		if(count($rows)==0)
			return array();

		return $rows[0];
	}

	public static function getGoBackButton(&$Model,&$layout_code)
	{
        $jinput = JFactory::getApplication()->input;
        $returnto =base64_decode(JFactory::getApplication()->input->get('returnto','','BASE64'));

		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('gobackbutton',$options,$layout_code,'{}');

		$i=0;

		foreach($fList as $fItem)
		{
				$opt='';

				$title=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_GO_BACK');
				$pair=explode(',',$options[$i]);

				if($pair[0]=='')
					$image_icon='components/com_customtables/images/arrow_rtl.png';
				else
					$image_icon=$pair[0];

				if(isset($pair[1]) and $pair[1]!='')
					$opt=$pair[1];
				if(isset($pair[2]) and $pair[2]!='')
				{
					if($pair[2]=='-')
						$title='';
					else
						$title=$pair[2];

				}

                if(isset($pair[3]) and $pair[3]!='')
                    $returnto=$pair[3];

				if($Model->ct->Env->print==1)
                    $gobackbutton='';
                else
                    $gobackbutton=tagProcessor_General::renderGoBackButton($returnto,$title);

				$layout_code=str_replace($fItem,$gobackbutton,$layout_code);
				$i++;
		}
	}

	protected static function renderGoBackButton($returnto,$title)
	{
		$gobackbutton='';
		if ($returnto=='')
		{
			$gobackbutton='';
		}
		else
		{
			$gobackbutton='<a href="'.$returnto.'" class="ct_goback"><div>'.$title.'</div></a>';
		}
		return $gobackbutton;
	}


}
