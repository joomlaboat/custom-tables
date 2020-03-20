<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
class CTValue
{

    public static function getValue($id,&$es,&$esfield,&$savequery,$prefix,$establename,$LanguageList,&$fieldstosave)
    {

        $db = JFactory::getDBO();
        $esfieldname=$esfield['fieldname'];
        $comesfieldname=$prefix.'es_'.$esfieldname;
        $jinput=JFactory::getApplication()->input;
        $typeparams=$esfield['typeparams'];
        $value_found=false;


        switch($esfield['type'])
			{
				case 'records':

					$value_found=CTValue::get_record_type_value($savequery,$typeparams,$prefix,$esfieldname);

					break;
				case 'sqljoin':

					$value=$jinput->getInt($comesfieldname,null);
					if(isset($value))
					{
						if($value==0)
							$savequery[]='es_'.$esfieldname.'=NULL';
						else
							$savequery[]='es_'.$esfieldname.'='.(int)$value;

                        $value_found=true;
					}

					break;
				case 'radio':
						$value=$jinput->getCmd($comesfieldname,null);

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
                        }
					break;

				case 'googlemapcoordinates':
						$value=$jinput->getString($comesfieldname,null);

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
                        }
					break;

                case 'color':

                        $value=JFactory::getApplication()->input->getString($comesfieldname,'');
    //                    echo '$value='.$value.'<br/>';
                                                
                        if(strpos($value,'rgb')!==false)
                        {
                            $parts=str_replace('rgba(','',$value);
                            $parts=str_replace('rgb(','',$parts);
                            $parts=str_replace(')','',$parts);
                            $values=explode(',',$parts);
                            
                            if(count($values)>=3)
                            {
                                $r=CTValue::toHex((int)$values[0]);
                                $g=CTValue::toHex((int)$values[1]);
                                $b=CTValue::toHex((int)$values[2]);
                                $value=$r.$g.$b;
                            }
                            
                            if(count($values)==4)
                            {
                                $a=255*(float)$values[3];
                                $value.=CTValue::toHex($a);
                            }
                                                        
                        }
                        else
                            $value=JFactory::getApplication()->input->get($comesfieldname,'','ALNUM');

                        
  //                      echo '$value='.$value.'<br/>';
//die;
						if(isset($value))
                        {
                            $value=strtolower($value);
                            $value=str_replace('#','',$value);
                            if(ctype_xdigit($value) or $value=='')
                            {
                                $value_found=true;
                                $savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
                            }
                        }
					break;

				case 'alias':
						$value=$jinput->getString($comesfieldname,null);

						if(isset($value))
                        {
                            $value_found=CTValue::get_alias_type_value($id,$establename,$savequery,$prefix,$esfieldname);
                        }
					break;

                case 'string':
						$value=$jinput->getString($comesfieldname,null);

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
                        }
					break;

				case 'multilangstring':

					$firstlanguage=true;
					foreach($LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
						$postfix='_'.$lang->sef;

						$value=$jinput->getString($comesfieldname.$postfix);

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.$postfix.'='.$db->Quote($value);
                        }
					}
					break;


				case 'text':

					$value_= JComponentHelper::filterText($jinput->post->get($comesfieldname, null, 'raw'));

					if(isset($value_))
					{
						$value=stripslashes($value_);

                        $value_found=true;
						$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
					}

					break;

				case 'multilangtext':

					$firstlanguage=true;
					foreach($LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
						$postfix='_'.$lang->sef;

						$value_= JComponentHelper::filterText($jinput->post->get($comesfieldname.$postfix, null, 'raw'));

						if(isset($value_))
						{
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.$postfix.'='.$db->Quote($value_);
						}
					}
					break;

				case 'int':
						$value=$jinput->getInt($comesfieldname,null);

						if(isset($value)) // always check with isset(). null doesnt work as 0 is null somehow in PHP
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.(int)$value;
                        }

					break;

				case 'user':
						$value=$jinput->getVar($comesfieldname);

						if(isset($value))
                        {
                            $value=$jinput->getInt($comesfieldname);
                            if($value==0)
                            {
                                $savequery[]='es_'.$esfieldname.'=null';
                            }
                            else
                                $savequery[]='es_'.$esfieldname.'='.(int)$value;

                            $value_found=true;
                        }


					break;

                case 'userid':

                    	$value=$jinput->getInt($comesfieldname,null);

						if(isset($value))
                        {
							if($value!=0)
                            {
                                $savequery[]='es_'.$esfieldname.'='.(int)$value;
                                $value_found=true;
                            }
                        }

					break;

				case 'usergroup':
						$value=$jinput->getInt($comesfieldname,null);

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.(int)$value;
                        }
					break;

				case 'usergroups':
					$value_found=CTValue::get_usergroups_type_value($savequery,$typeparams,$prefix,$esfieldname);
					break;

                case 'language':

                    $value_found=CTValue::get_customtables_type_language($savequery,$typeparams,$prefix,$esfieldname);
					break;

				case 'filelink':
						$value=$jinput->getString($comesfieldname,null);
						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
                        }
					break;



				case 'float':
						$value=$jinput->get($comesfieldname,null,'FLOAT');

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.(float)$value;
                        }
					break;

				case 'image':

                    $image_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_image.php';
					require_once($image_type_file);
                    $value_found=CT_FieldTypeTag_image::get_image_type_value($id,$es,$savequery,$typeparams,$prefix,$esfieldname,$establename);

					break;

				case 'file':

                    $file_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
					require_once($file_type_file);

					$value_found=CT_FieldTypeTag_file::get_file_type_value($id,$es,$savequery,$typeparams,$prefix,$esfieldname,$establename);
					break;

				case 'article':
						$value=$jinput->getInt($comesfieldname,null);

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.'='.$value;
                        }
					break;

				case 'multilangarticle':
					$firstlanguage=true;
					foreach($LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$value=$jinput->getInt($comesfieldname.$postfix,null);

						if(isset($value))
                        {
                            $value_found=true;
							$savequery[]='es_'.$esfieldname.$postfix.'='.$value;
                        }
					}
					break;

				case 'customtables':
                    $value_found=CTValue::get_customtables_type_value($es,$savequery,$typeparams,$prefix,$esfieldname,$establename);

					break;

				case 'email':
						$value=trim($jinput->getString($comesfieldname,null));
						if(isset($value))
						{
							if(CTValue::checkEmail($value))
								$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
							else
								$savequery[]='es_'.$esfieldname.'=""';

                            $value_found=true;
						}
					break;

				case 'checkbox':
                    $value=$jinput->getCmd($comesfieldname);
                    
                    if($value!=null)
                    {
                        if((int)$value==1 or $value=='on')
                            $value=1;
                        else
                            $value=0;
                        
                        $savequery[]='es_'.$esfieldname.'='.$value;
                        $value_found=true;
                    }
                    else
                    {
                        $value=$jinput->getCmd($comesfieldname.'_off');
                        if($value!=null)
                        {
                            $savequery[]='es_'.$esfieldname.'='.(int)$value;
                            $value_found=true;
                        }
                    }
                    break;

				case 'date':
						$value=JFactory::getApplication()->input->getString($comesfieldname,null);
						if(isset($value))
                        {
							$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
                            $value_found=true;
                        }
                        
                        
                        
                        

					break;
                
                case 'time':
						$value=JFactory::getApplication()->input->getString($comesfieldname,null);
						if(isset($value))
                        {
							$savequery[]='es_'.$esfieldname.'='.(int)$value;
                            $value_found=true;
                        } 

					break;

			}//switch($esfield['type'])

    return $value_found;

}

static public function toHex($n) {
    echo 'n='.$n.'<br/>';
    $n = intval($n);
    if (!$n)
        return '00';

    $n = max(0, min($n, 255)); // make sure the $n is not bigger than 255 and not less than 0
    $index1 = (int) ($n - ($n % 16)) / 16;
    $index2 = (int) $n % 16;

    return substr("0123456789ABCDEF", $index1, 1) 
        . substr("0123456789ABCDEF", $index2, 1);
}

static public function Try2CreateUserAccount($Model,$field,$row)
{
    $useridfieldname=$field['fieldname'];

    $uid=(int)$row['es_'.$useridfieldname];
    if($uid!=0)
    {
        $user = JFactory::getUser($uid);
        $email=$user->email.'';
        if($email!='')
            return 0; //all good, user already assigned.

    }

    $params=$field['typeparams'];
    $parts=JoomlaBasicMisc::csv_explode(',', $params, '"', false);
    if(count($parts)!=3)
        return false;

    //Try to create user
    $new_parts=array();
    foreach($parts as $part)
    {
        tagProcessor_General::process($Model,$part,$row,'',1);
    	tagProcessor_Item::process(false,$Model,$row,$part,'',array(),'',0);
    	tagProcessor_If::process($Model,$part,$row,'',0);
    	tagProcessor_Page::process($Model,$part);
    	tagProcessor_Value::processValues($Model,$row,$part,'[]');
        if($part=="")
            return false; //if any of the parameters empty then break;

        $new_parts[]=$part;
    }

    $user_groups=$new_parts[0];
    $user_name=$new_parts[1];
    $user_email=$new_parts[2];

    $unique_users=false;
    if(isset($new_parts[4]) and $new_parts[4]=='unique')
        $unique_users=true;


    require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'createuser.php');
    $existing_user_id=CustomTablesCreateUser::CheckIfEmailExist($user_email,$existing_user,$existing_name);
    if($existing_user_id)
	{
        if(!$unique_users) //allow not unique record per users
        {
            CTValue::UpdateUserField($Model->establename,$useridfieldname,$existing_user_id,$row['id']);
            JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_USER_UPDATED' ));
        }
        else
        {
            JFactory::getApplication()->enqueueMessage(
            JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_USER_WITH_EMAIL' )
                .' "'.$user_email.'" '
                .JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS' ), 'Error');
        }

	}
    else
        CTValue::CreateUser($user_email,$user_name,$user_groups,$row['id'],$useridfieldname,$Model->establename);

    return;
}

    static protected function UpdateUserField($tablename,$useridfieldname,$existing_user_id,$listing_id)
    {
        $db = JFactory::getDBO();
		$query = 'UPDATE #__customtables_table_'.$tablename.' SET es_'.$useridfieldname.'='.$existing_user_id.' WHERE id='.$listing_id.' LIMIT 1';
		$db->setQuery( $query );
		if (!$db->query())    die( $db->stderr());


    }

    static protected function CreateUser($email,$name,$usergroups,$listing_id,$useridfieldname,$tablename)
	{
		$msg='';
		$password=strtolower(JUserHelper::genRandomPassword());


		$new_password=$password;



		$realuserid=0;

		$articleid=0;
		$msg='';
		$realuserid=CustomTablesCreateUser::CreateUserAccount($name,$email,$password,$email,$usergroups,$msg,$articleid);

		if($realuserid!=0)
		{
                CTValue::UpdateUserField($tablename,$useridfieldname,$realuserid,$listing_id);
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_USER_CREATE_PSW_SENT' ));
		}
		else
		{

				$msg=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_USER_NOTCREATED');
				if(count($msg_warning)>0)
					$msg.='<br/><ul><li>'.implode('</li><li>',$msg_warning).'</li></ul>';

				JFactory::getApplication()->enqueueMessage($msg, 'error');
		}

	}

static public function get_customtables_type_language(&$savequery,$typeparams,$prefix,$esfieldname)
{
    $value_found=false;

    $db = JFactory::getDBO();
    $comesfieldname=$prefix.'es_'.$esfieldname;
    $jinput=JFactory::getApplication()->input;
    $value=$jinput->getCmd($comesfieldname,null);

	if(isset($value))
    {
        $value_found=true;
		$savequery[]='es_'.$esfieldname.'='.$db->Quote($value);
    }

    return $value_found;
}

static public function get_customtables_type_value(&$es,&$savequery,$typeparams,$prefix,$esfieldname,$establename)
{
    $value_found=false;
    $comesfieldname=$prefix.'es_'.$esfieldname;
    $db = JFactory::getDBO();
    $value='';

    				$typeparams_arr=explode(',',$typeparams);
					$optionname=$typeparams_arr[0];

					if($typeparams_arr[1]=='multi')
					{
							$value=CTValue::getMultiString($es,$optionname, $prefix.'esmulti_'.$establename.'_'.$esfieldname);

							if($value!=null)
							{
								if($value!='')
									$savequery[]='es_'.$esfieldname.'='.$db->Quote(','.$value.',');
								else
									$savequery[]='es_'.$esfieldname.'=""';

                                $value_found=true;
							}

					}
					elseif($typeparams_arr[1]=='single')
					{

							$value=CTValue::getComboString($optionname, $prefix.'escombotree_'.$establename.'_'.$esfieldname);


							if($value!=null)
							{

								if($value!='')
									$savequery[]='es_'.$esfieldname.'='.$db->Quote(','.$value.',');
								else
									$savequery[]='es_'.$esfieldname.'=""';

                                $value_found=true;
							}
					}

					// commas characters here are for the compatibility purpose, to let same algoritms search in multi value strings as well as in single value
        return $value_found;
    }





static public function get_usergroups_type_value(&$savequery,$typeparams,$prefix,$esfieldname)
{
        $value_found=false;
        $comesfieldname=$prefix.'es_'.$esfieldname;
        $db = JFactory::getDBO();

                        switch($typeparams)
						{
							case 'single';

								$value=JFactory::getApplication()->input->getString($comesfieldname,null);

								if(isset($value))
                                {
                                    $value_found=true;
									$savequery[]='es_'.$esfieldname.'='.$db->Quote(','.$value.',');
                                }

								break;

							case 'multi';
									$valuearray = JFactory::getApplication()->input->post->get( $comesfieldname, null, 'array' );

									if(isset($valuearray))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.$db->Quote(','.implode(',',$valuearray).',');
                                    }
								break;

							case 'multibox';
									$valuearray = JFactory::getApplication()->input->post->get( $comesfieldname, null, 'array' );


									if(isset($valuearray))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.$db->Quote(','.implode(',',$valuearray).',');
                                    }
								break;


							case 'radio';

									$value=JFactory::getApplication()->input->getString($comesfieldname,null);

									if(isset($value))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.$db->Quote(','.$value.',');
                                    }
								break;

							case 'checkbox';
								$valuearray = JFactory::getApplication()->input->post->get( $comesfieldname, null, 'array' );

								if(isset($valuearray))
                                {
                                    $value_found=true;
									$savequery[]='es_'.$esfieldname.'='.$db->Quote(','.implode(',',$valuearray).',');
                                }

								break;
						}

        return $value_found;
    }


static public function get_alias_type_value($id,$establename,&$savequery,$prefix,$esfieldname)
{
    $comesfieldname=$prefix.'es_'.$esfieldname;

    $value=JFactory::getApplication()->input->getString($comesfieldname);
    if(!isset($value))
        return false;
    
    $value=CTValue::prepare_alias_type_value($id,$establename,$esfieldname,$value);
    if($value=='')
        return false;

    $db = JFactory::getDBO();
    $savequery[]='es_'.$esfieldname.'='.$db->quote($value);
    return true;
}

static public function prepare_alias_type_value($id,$establename,$esfieldname,$value)
{
    $value=JoomlaBasicMisc::slugify($value);

    if($value=='')
        return '';

    $mysqltablename='#__customtables_table_'.$establename;
    $mysqlfieldname='es_'.$esfieldname;

    if(!CTValue::checkIfAliasExists($id,$mysqltablename,$mysqlfieldname,$value))
        return $value;

    $val=CTValue::splitStringToStringAndNumber($value);

	$value_new=$val[0];
    $i=$val[1];

	do
	{
		if(CTValue::checkIfAliasExists($id,$mysqltablename,$mysqlfieldname,$value_new))
		{
			//increase index
			$i++;
			$value_new=$val[0].'-'.$i;
		}
		else
			break;

	}while(1==1);

    return $value_new;
}

static protected function splitStringToStringAndNumber($string)
{
    if($string=='')
        return array('',0);

    $pair=explode('-',$string);
    $l=count($pair);

    if($l==1)
        return array($string,0);

    $c=end($pair);
    if(is_numeric($c))
    {
        unset($pair[$l-1]);
        $pair=array_values($pair);
        $val=array(implode('-',$pair),intval($c));
    }
    else
        return array($string,0);

    return $val;
}

static protected function checkIfAliasExists($exclude_id,$mysqltablename,$mysqlfieldname,$value)
{
    $db = JFactory::getDBO();

    $query = 'SELECT count(id) AS c FROM '.$mysqltablename.' WHERE id!='.$exclude_id.' AND '.$mysqlfieldname.'='.$db->quote($value).' LIMIT 1';
	$db->setQuery( $query );
	if (!$db->query())    die ( $db->stderr());

	$rows = $db->loadObjectList();
    if(count($rows)==0)
        return false;

    $row=$rows[0];
    $c=(int)$row->c;

    if($c>0)
        return true;

    return false;
}

static public function get_record_type_value(&$savequery,$typeparams,$prefix,$esfieldname)
{
    $value_found=false;

    $comesfieldname=$prefix.'es_'.$esfieldname;

    $db = JFactory::getDBO();
    $typeparamsarray=explode(',',$typeparams);
					if(count($typeparamsarray)>2)
					{
						$esr_selector=$typeparamsarray[2];
						$selectorpair=explode(':',$esr_selector);

						switch($selectorpair[0])
						{
							case 'single';

									$value=JFactory::getApplication()->input->getString($comesfieldname,null);

									if(isset($value))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.(int)$value;
                                    }

								break;

							case 'multi';
									$valuearray = JFactory::getApplication()->input->post->get( $comesfieldname, null, 'array' );

									if(isset($valuearray))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.$db->Quote(CTValue::getCleanRecordValue($valuearray));
                                    }
								break;

							case 'multibox';
									$valuearray = JFactory::getApplication()->input->post->get( $comesfieldname,null, 'array' );

									if(isset($valuearray))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.$db->Quote(CTValue::getCleanRecordValue($valuearray));
                                    }

								break;


							case 'radio';

									$valuearray = JFactory::getApplication()->input->post->get( $comesfieldname, null, 'array' );

									if(isset($valuearray))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.$db->Quote(CTValue::getCleanRecordValue($valuearray));
                                    }

								break;

							case 'checkbox';
									$valuearray = JFactory::getApplication()->input->post->get( $comesfieldname, null, 'array' );

									if(isset($valuearray))
                                    {
                                        $value_found=true;
										$savequery[]='es_'.$esfieldname.'='.$db->Quote(CTValue::getCleanRecordValue($valuearray));
                                    }

								break;
						}

					}
        return $value_found;
    }


    static public function getCleanRecordValue($array)
	{
		$values=array();
		foreach($array as $a)
		{
			if((int)$a!=0)
				$values[]=(int)$a;
		}
		return ','.implode(',',$values).',';
	}

    static public function checkEmail($email)
	{
		if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/",  $email))
        {
            if(CTValue::domain_exists($email))
                return true;
            else
                return false;
		}
		return false;
	}

    static public function domain_exists($email, $record = 'MX')
    {
    	$pair = explode('@', $email);
        if(count($pair)==1)
            return false;

    	return checkdnsrr(end($pair), $record);
    }

	static public function getMultiString(&$es,$parent, $prefix)
	{
		$parentid=$es->getOptionIdFull($parent);
		$a=CTValue::getMultiSelector($parentid,$parent, $prefix);
		if($a==null)
			return null;

		if(count($a)==0)
			return '';
		else
			return implode(',',$a);

	}

    static public function getMultiSelector($parentid,$parentname, $prefix)
	{
		$set=false;
		$resilt_list=array();

		$rows=CTValue::getList($parentid);
		if(count($rows)<1)
			return $resilt_list;

		$count=count($rows);
		foreach($rows as $row)
		{
			if(strlen($parentname)==0)
				$ChildList=CTValue::getMultiSelector($row->id,$row->optionname,$prefix);
			else
				$ChildList=CTValue::getMultiSelector($row->id,$parentname.'.'.$row->optionname,$prefix);

			if($ChildList!=null)
				$count_child=count($ChildList);
			else
				$count_child=0;

			if($count_child>0)
			{
					$resilt_list=array_merge($resilt_list,$ChildList);
			}
			else
			{
				$value=JFactory::getApplication()->input->getString($prefix.'_'.$row->id,null);
				if(isset($value))
				{
					$set=true;

					if(strlen($parentname)==0)
						$resilt_list[]=$row->optionname.'.';
					else
						$resilt_list[]=$parentname.'.'.$row->optionname.'.';
				}
			}
		}

		if(!$set)
			return null;

		return $resilt_list;
	}

	static public function getComboString($parent, $prefix)
	{
		$i=1;
		$result=array();
		$v='';
		$set=false;
		do
		{

			$value=JFactory::getApplication()->input->getCmd($prefix.'_'.$i);
			if(isset($value))
			{
				if($value!='')
				{
					$result[]=$value;
					$i++;
				}
				$set=true;
			}
			else
				break;


		}while($v!='');

		if(count($result)==0)
		{
			if($set)
				return '';
			else
				return null;
		}
		else
			return $parent.'.'.implode('.',$result).'.';

		// the format of the string is: ",[optionname1].[optionname2].[optionname..n].,
		// example: ,geo.usa.newyork.,
		// last "." dot is to let search by parents
		// php example: getpos(",geo.usa.",$string)
		// mysql example: instr($string, ",geo.usa.")

	}


    static public function getList($parentid)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT id, optionname FROM #__customtables_options WHERE parentid='.(int)$parentid;
	 	$db->setQuery($query);
		if (!$db->query())	die($db->stderr());
            return $db->loadObjectList();
	}

    static protected function processDefaultValue($esfieldname,&$Model,$htmlresult,$type,&$row,&$savequery)
    {
        $db = JFactory::getDBO();

        tagProcessor_General::process($Model,$htmlresult,$row,'',1);
		tagProcessor_Item::process(false,$Model,$row,$htmlresult,'',array(),'',0);
		tagProcessor_If::process($Model,$htmlresult,$row,'',0);
		tagProcessor_Page::process($Model,$htmlresult);
		tagProcessor_Value::processValues($Model,$row,$htmlresult,'[]');

        if($htmlresult!='')
        {
            LayoutProcessor::applyContentPlugins($htmlresult);

            if($type=='alias')
                $htmlresult=CTValue::prepare_alias_type_value($row['id'],$Model->establename,$esfieldname,$htmlresult);

            $savequery[]='es_'.$esfieldname.'='.$db->quote($htmlresult);
        }
    }

    static public function processDefaultValues($default_fields_to_apply,&$Model,&$row)
    {
        $savequery=array();
        
        foreach($default_fields_to_apply as $d)
		{
            $fieldname=$d[0];
            $value=$d[1];
            $type=$d[2];

            $r=$row['es_'.$fieldname];
            if($r==null or $r=='' or $r==0)
            {
                CTValue::processDefaultValue($fieldname,$Model,$value,$type,$row,$savequery);

            }
		}

        CTValue::runQueries($Model,$savequery,$row['id']);

    }

    static public function runQueries(&$Model,&$savequery,$id)
    {

    	if(count($savequery)>0)
		{
			$db = JFactory::getDBO();
			$query='UPDATE #__customtables_table_'.$Model->establename.' SET '.implode(', ',$savequery).' WHERE id='.$id;

			$db->setQuery( $query );
			if (!$db->query())    die( $db->stderr());
		}
    }

}
