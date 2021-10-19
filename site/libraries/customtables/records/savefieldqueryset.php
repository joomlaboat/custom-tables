<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Factory;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\User\UserHelper;

use CT_FieldTypeTag_image;
use CT_FieldTypeTag_file;
use CustomTables\DataTypes\Tree;
use CustomTables\Email;

use tagProcessor_General;
use tagProcessor_Item;
use tagProcessor_If;
use tagProcessor_Page;
use tagProcessor_Value;

use \JoomlaBasicMisc;
use CustomTables\CTUser;
use \LayoutProcessor;

trait SaveFieldQuerySet
{
    var $fieldname;
	var $realfieldname;
	var $comesfieldname;
	var $typeparams;
	var $jinput;
	var $prefix;
	var $db;

	function getSaveFieldSet($id,&$esfield)
    {
        $this->fieldname=$esfield['fieldname'];
		$this->realfieldname=$esfield['realfieldname'];
		
        $this->comesfieldname=$this->prefix.$this->fieldname;
        $this->jinput=Factory::getApplication()->input;
        $this->typeparams=$esfield['typeparams'];

        switch($esfield['type'])
		{
				case 'records':
					return $this->get_record_type_value();

				case 'sqljoin':

					$value=$this->jinput->getInt($this->comesfieldname,null);
					if(isset($value))
					{
						if($value==0)
							return $this->realfieldname.'=NULL';
						else
							return $this->realfieldname.'='.(int)$value;
					}

					break;
				case 'radio':
						$value=$this->jinput->getCmd($this->comesfieldname,null);

						if(isset($value))
 							return $this->realfieldname.'='.$this->db->Quote($value);
					break;

				case 'googlemapcoordinates':
						$value=$this->jinput->getString($this->comesfieldname,null);

						if(isset($value))
							return $this->realfieldname.'='.$this->db->Quote($value);

					break;

                case 'color':

                    $value=$this->jinput->getString($this->comesfieldname,null);
						
					if(isset($value))
                    {
                        if(strpos($value,'rgb')!==false)
                        {
                            $parts=str_replace('rgba(','',$value);
                            $parts=str_replace('rgb(','',$parts);
                            $parts=str_replace(')','',$parts);
                            $values=explode(',',$parts);
                            
                            if(count($values)>=3)
                            {
                                $r=$this->toHex((int)$values[0]);
                                $g=$this->toHex((int)$values[1]);
                                $b=$this->toHex((int)$values[2]);
                                $value=$r.$g.$b;
                            }
                            
                            if(count($values)==4)
                            {
                                $a=255*(float)$values[3];
                                $value.=$this->toHex($a);
                            }

                        }
                        else
                            $value=$this->jinput->get($this->comesfieldname,'','ALNUM');

                     
                        $value=strtolower($value);
                        $value=str_replace('#','',$value);
                        if(ctype_xdigit($value) or $value=='')
                            return $this->realfieldname.'='.$this->db->Quote($value);
                    }
					break;

				case 'alias':
						$value=$this->jinput->getString($this->comesfieldname,null);

						if(isset($value))
                            return $this->get_alias_type_value($id);
					break;

                case 'string':
						$value=$this->jinput->getString($this->comesfieldname,null);

						if(isset($value))
							return $this->realfieldname.'='.$this->db->Quote($value);
					break;

				case 'multilangstring':

					$firstlanguage=true;
					$sets = array();
					foreach($this->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
						$postfix='_'.$lang->sef;

						$value=$this->jinput->getString($this->comesfieldname.$postfix);

						if(isset($value))
							$sets[]=$this->realfieldname.$postfix.'='.$this->db->Quote($value);
					}
					
					if(count($sets)>0)
						return implode(',',$sets);
					break;


				case 'text':

					$value_= ComponentHelper::filterText($this->jinput->post->get($this->comesfieldname, null, 'raw'));

					if(isset($value_))
						return $this->realfieldname.'='.$this->db->Quote(stripslashes($value_));

					break;

				case 'multilangtext':

					$firstlanguage=true;
					foreach($this->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
						$postfix='_'.$lang->sef;

						$value_= ComponentHelper::filterText($this->jinput->post->get($this->comesfieldname.$postfix, null, 'raw'));

						if(isset($value_))
							return $this->realfieldname.$postfix.'='.$this->db->Quote($value_);

					}
					break;

				case 'int':
						$value=$this->jinput->getInt($this->comesfieldname,null);

						if(isset($value)) // always check with isset(). null doesnt work as 0 is null somehow in PHP
							return $this->realfieldname.'='.(int)$value;


					break;

				case 'user':
						$value=$this->jinput->getVar($this->comesfieldname);

						if(isset($value))
                        {
                            $value=$this->jinput->getInt($this->comesfieldname);
							if($value == null)
                                return $this->realfieldname.'=null';
                            else
                                return $this->realfieldname.'='.(int)$value;

                        }

					break;

                case 'userid':

                    	$value=$this->jinput->getInt($this->comesfieldname,null);

						if(isset($value))
                        {
							if($value!=0)
                                return $this->realfieldname.'='.(int)$value;
                        }

					break;

				case 'usergroup':
						$value=$this->jinput->getInt($this->comesfieldname,null);

						if(isset($value))
							return $this->realfieldname.'='.(int)$value;

					break;

				case 'usergroups':
					return $this->get_usergroups_type_value();

                case 'language':
                    return $this->get_customtables_type_language();

				case 'filelink':
						$value=$this->jinput->getString($this->comesfieldname,null);
						if(isset($value))
							return $this->realfieldname.'='.$this->db->Quote($value);

					break;



				case 'float':
						$value=$this->jinput->get($this->comesfieldname,null,'FLOAT');

						if(isset($value))
							return $this->realfieldname.'='.(float)$value;
					break;

				case 'image':

                    $image_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_image.php';
					require_once($image_type_file);

                    return CT_FieldTypeTag_image::get_image_type_value($this, $id);

				case 'file':

                    $file_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
					require_once($file_type_file);
					return CT_FieldTypeTag_file::get_file_type_value($this, $id);

				case 'article':
						$value=$this->jinput->getInt($this->comesfieldname,null);

						if(isset($value))
							return $this->realfieldname.'='.$value;

					break;

				case 'multilangarticle':
					$firstlanguage=true;
					foreach($this->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$value=$this->jinput->getInt($this->comesfieldname.$postfix,null);

						if(isset($value))
							return $this->realfieldname.$postfix.'='.$value;
					}
					break;

				case 'customtables':
                    return $this->get_customtables_type_value();

					break;

				case 'email':
						$value=$this->jinput->getString($this->comesfieldname);
						if(isset($value))
						{
							$value = trim($value);
							if(Email::checkEmail($value))
								return $this->realfieldname.'='.$this->db->Quote($value);
							else
								return $this->realfieldname.'='.$this->db->Quote("");//PostgreSQL compatible
						}
					break;

				case 'url':
						$value=$this->jinput->getString($this->comesfieldname);
						if(isset($value))
						{
							$value = trim($value);
							
							if (filter_var($value, FILTER_VALIDATE_URL))
								return $this->realfieldname.'='.$this->db->Quote($value);
							else
								return $this->realfieldname.'='.$this->db->Quote("");//PostgreSQL compatible
						}
					break;

				case 'checkbox':
                    $value=$this->jinput->getCmd($this->comesfieldname);
                    
                    if($value!=null)
                    {
                        if((int)$value==1 or $value=='on')
                            $value=1;
                        else
                            $value=0;
                        
                        return $this->realfieldname.'='.(int)$value;
                    }
                    else
                    {
                        $value=$this->jinput->getCmd($this->comesfieldname.'_off');
                        if($value!=null)
							return $this->realfieldname.'=0';
                    }
                    break;

				case 'date':
						$value=$this->jinput->getString($this->comesfieldname,null);
						if(isset($value))
							return $this->realfieldname.'='.$this->db->Quote($value);

					break;
                
                case 'time':
						$value=$this->jinput->getString($this->comesfieldname,null);
						if(isset($value))
                        {
							if($value=='')
								return $this->realfieldname.'=NULL';
							else
								return $this->realfieldname.'='.(int)$value;
                        } 

					break;

			}//switch($esfield['type'])

    return null;

}

	protected function toHex($n)
	{
		$n = intval($n);
		if (!$n)
			return '00';

		$n = max(0, min($n, 255)); // make sure the $n is not bigger than 255 and not less than 0
		$index1 = (int) ($n - ($n % 16)) / 16;
		$index2 = (int) $n % 16;

		return substr("0123456789ABCDEF", $index1, 1) 
			. substr("0123456789ABCDEF", $index2, 1);
	}

public function Try2CreateUserAccount(&$Model,$field,$row)
{
    $uid=(int)$row[$field['realfieldname']];
	
    if($uid!=0)
    {
        $user = Factory::getUser($uid);
        $email=$user->email.'';
        if($email!='')
		{
			Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS','error' ));
            return false; //all good, user already assigned.
		}

    }

    $params=$field['typeparams'];
    $parts=JoomlaBasicMisc::csv_explode(',', $params, '"', false);

    if(count($parts)<3)
	{
		Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('User field name parameters count is less than 3.','error' ));
        return false;
	}
	
    //Try to create user
    $new_parts=array();
    foreach($parts as $part)
    {
        tagProcessor_General::process($Model,$part,$row,'',1);
    	tagProcessor_Item::process(false,$Model,$row,$part,'','',0);
    	tagProcessor_If::process($Model,$part,$row,'',0);
    	tagProcessor_Page::process($Model,$part);
    	tagProcessor_Value::processValues($Model,$row,$part,'[]');
        //if($part=="")
            //return false; //if any of the parameters empty then break;

        $new_parts[]=$part;
    }

    $user_groups=$new_parts[0];
    $user_name=$new_parts[1];
    $user_email=$new_parts[2];
	
	if($user_groups=='' or $user_name=='' or $user_email=='')
	{
		Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('User group field, user name and user email fields not set.' ));
		return false;
	}

    $unique_users=false;
    if(isset($new_parts[4]) and $new_parts[4]=='unique')
        $unique_users=true;
		
	$existing_user_id=CTUser::CheckIfEmailExist($user_email,$existing_user,$existing_name);
	
    if($existing_user_id)
	{
        if(!$unique_users) //allow not unique record per users
        {
            CTUser::UpdateUserField($Model->ct->Table->realtablename, $Model->ct->Table->realidfieldname,$field['realfieldname'],$existing_user_id,$row['listing_id']);
            Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_USER_UPDATED' ));
        }
        else
        {
            Factory::getApplication()->enqueueMessage(
            JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_USER_WITH_EMAIL' )
                .' "'.$user_email.'" '
                .JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_ALREADY_EXISTS' ), 'Error');
        }

	}
    else
        CTUser::CreateUser($Model->ct->Table->realtablename, $Model->ct->Table->realidfieldname,$user_email,$user_name,$user_groups,$row['listing_id'],$field['realfieldname'],$this->realtablename);

    return true;
}

	protected function get_customtables_type_language()
	{
		$value=$this->jinput->getCmd($this->comesfieldname,null);

		if(isset($value))
			return $this->realfieldname.'='.$this->db->Quote($value);

		return null;
	}

	protected function get_customtables_type_value()
	{
		$value='';

		$typeparams_arr=explode(',',$this->typeparams);
		$optionname=$typeparams_arr[0];

		if($typeparams_arr[1]=='multi')
		{
			$value=$this->getMultiString($optionname, $this->prefix.'multi_'.$this->tablename.'_'.$this->fieldname);

			if($value!=null)
			{
				if($value!='')
					return $this->realfieldname.'='.$this->db->Quote(','.$value.',');
				else
					return $this->realfieldname.'=""';
			}
		}
		elseif($typeparams_arr[1]=='single')
		{
			$value=$this->getComboString($optionname, $this->prefix.'combotree_'.$this->tablename.'_'.$this->fieldname);

			if($value!=null)
			{
				if($value!='')
					return $this->realfieldname.'='.$this->db->Quote(','.$value.',');
				else
					return $this->realfieldname.'=""';
			}
		}
        return null;
    }

	protected function get_usergroups_type_value()
	{
		switch($this->typeparams)
		{
			case 'single';

								$value=$this->jinput->getString($this->comesfieldname,null);

								if(isset($value))
									return $this->realfieldname.'='.$this->db->Quote(','.$value.',');


								break;

							case 'multi';
									$valuearray = $this->jinput->post->get( $this->comesfieldname, null, 'array' );

									if(isset($valuearray))
										return $this->realfieldname.'='.$this->db->Quote(','.implode(',',$valuearray).',');

								break;

							case 'multibox';
									$valuearray = $this->jinput->post->get( $this->comesfieldname, null, 'array' );

									if(isset($valuearray))
										return $this->realfieldname.'='.$this->db->Quote(','.implode(',',$valuearray).',');

								break;


							case 'radio';

									$value=$this->jinput->getString($this->comesfieldname,null);

									if(isset($value))
										return $this->realfieldname.'='.$this->db->Quote(','.$value.',');

								break;

							case 'checkbox';
								$valuearray = $this->jinput->post->get( $this->comesfieldname, null, 'array' );

								if(isset($valuearray))
									return $this->realfieldname.'='.$this->db->Quote(','.implode(',',$valuearray).',');

								break;
						}

        return false;
    }



	public function get_alias_type_value($id)
	{
		$value=$this->jinput->getString($this->comesfieldname);
		if(!isset($value))
			return null;
    
		$value=$this->prepare_alias_type_value($id,$value,$this->realfieldname);
		if($value=='')
			return null;

		return $this->realfieldname.'='.$this->db->quote($value);
	}

	public function prepare_alias_type_value($id,$value,$realfieldname)
	{
		$value=JoomlaBasicMisc::slugify($value);

		if($value=='')
			return '';

		if(!$this->checkIfAliasExists($id,$value,$realfieldname))
			return $value;

		$val=$this->splitStringToStringAndNumber($value);

		$value_new=$val[0];
		$i=$val[1];

		do
		{
			if($this->checkIfAliasExists($id,$value_new,$realfieldname))
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

protected function splitStringToStringAndNumber($string)
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

protected function checkIfAliasExists($exclude_id,$value,$realfieldname)
{
    $query = 'SELECT count('.$this->realidfieldname.') AS c FROM '.$this->realtablename.' WHERE '
		.$this->realidfieldname.'!='.(int)$exclude_id.' AND '.$realfieldname.'='.$this->db->quote($value).' LIMIT 1';
		
	$this->db->setQuery( $query );

	$rows = $this->db->loadObjectList();
    if(count($rows)==0)
        return false;

    $row=$rows[0];
    $c=(int)$row->c;

    if($c>0)
        return true;

    return false;
}

protected function get_record_type_value()
{
    $typeparamsarray=explode(',',$this->typeparams);
					if(count($typeparamsarray)>2)
					{
						$esr_selector=$typeparamsarray[2];
						$selectorpair=explode(':',$esr_selector);

						switch($selectorpair[0])
						{
							case 'single';

									$value=$this->jinput->getString($this->comesfieldname,null);

									if(isset($value))
										return $this->realfieldname.'='.(int)$value;

								break;

							case 'multi';
									$valuearray = $this->jinput->post->get($this->comesfieldname, null, 'array' );

									if(isset($valuearray))
										return $this->realfieldname.'='.$this->db->Quote($this->getCleanRecordValue($valuearray));
                                    
								break;

							case 'multibox';
									$valuearray = $this->jinput->post->get($this->comesfieldname,null, 'array' );

									if(isset($valuearray))
                                    {
                                        $clean_value=$this->getCleanRecordValue($valuearray);
										return $this->realfieldname.'='.$this->db->Quote($clean_value);
                                    }
								break;


							case 'radio';

									$valuearray = $this->jinput->post->get($this->comesfieldname, null, 'array' );

									if(isset($valuearray))
										return $this->realfieldname.'='.$this->db->Quote($this->getCleanRecordValue($valuearray));

								break;

							case 'checkbox';
									$valuearray = $this->jinput->post->get($this->comesfieldname, null, 'array' );

									if(isset($valuearray))
										return $this->realfieldname.'='.$this->db->Quote($this->getCleanRecordValue($valuearray));

								break;
						}

					}
        return null;
    }


    protected function getCleanRecordValue($array)
	{
		$values=array();
		foreach($array as $a)
		{
			if((int)$a!=0)
				$values[]=(int)$a;
		}
		return ','.implode(',',$values).',';
	}

	protected function getMultiString($parent, $prefix)
	{
		$parentid=Tree::getOptionIdFull($parent);
		$a=$this->getMultiSelector($parentid,$parent, $prefix);
		if($a==null)
			return null;

		if(count($a)==0)
			return '';
		else
			return implode(',',$a);

	}

    protected function getMultiSelector($parentid,$parentname, $prefix)
	{
		$set=false;
		$resilt_list=array();

		$rows=$this->getList($parentid);
		if(count($rows)<1)
			return $resilt_list;

		$count=count($rows);
		foreach($rows as $row)
		{
			if(strlen($parentname)==0)
				$ChildList=$this->getMultiSelector($row->id,$row->optionname,$prefix);
			else
				$ChildList=$this->getMultiSelector($row->id,$parentname.'.'.$row->optionname,$prefix);

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
				$value=$this->jinput->getString($prefix.'_'.$row->id,null);
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

	protected function getComboString($parent, $prefix)
	{
		$i=1;
		$result=array();
		$v='';
		$set=false;
		do
		{

			$value=$this->jinput->getCmd($prefix.'_'.$i);
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


    protected function getList($parentid)
	{
		$query = 'SELECT id, optionname FROM #__customtables_options WHERE parentid='.(int)$parentid;
	 	$this->db->setQuery($query);
        return $this->db->loadObjectList();
	}

    function processDefaultValue(&$Model,$htmlresult,$type,&$row)
    {
        tagProcessor_General::process($Model,$htmlresult,$row,'',1);
		tagProcessor_Item::process(false,$Model,$row,$htmlresult,'','',0);
		tagProcessor_If::process($Model,$htmlresult,$row,'',0);
		tagProcessor_Page::process($Model,$htmlresult);
		tagProcessor_Value::processValues($Model,$row,$htmlresult,'[]');

        if($htmlresult!='')
        {
            LayoutProcessor::applyContentPlugins($htmlresult);

            if($type=='alias')
			{
                $htmlresult=$this->prepare_alias_type_value(
					$row['listing_id'],
					$htmlresult,
					$this->realfieldname);
			}
            return $this->realfieldname.'='.$this->db->quote($htmlresult);
        }
		return null;
    }

    public function processDefaultValues($default_fields_to_apply,&$Model,&$row)
    {
        $savequery=array();
        
        foreach($default_fields_to_apply as $d)
		{
            $fieldname=$d[0];
            $value=$d[1];
            $type=$d[2];
			$this->realfieldname=$d[3];
			

            $r=$row[$this->realfieldname];
            if($r==null or $r=='' or $r==0)
            {
                $this->processDefaultValue($Model,$value,$type,$row,$savequery,$this->realfieldname);
            }
		}

        $this->runUpdateQuery($savequery,$row['listing_id']);
    }

    public function runUpdateQuery(&$savequery,$id)
    {
    	if(count($savequery)>0)
		{
			$query='UPDATE '.$this->realtablename.' SET '.implode(', ',$savequery).' WHERE '.$this->realidfieldname.'='.$id;

			$this->db->setQuery( $query );
			$this->db->execute();
		}
    }
}
