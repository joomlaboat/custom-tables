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

class SaveFieldQuerySet
{
	var $ct;
    var $db;
	var $field;
	var $row;
	var $isCopy;
	
	function __construct(&$ct,&$row,$isCopy=false)
	{
		$this->ct = $ct;
		$this->db = Factory::getDBO();
		$this->row = $row;
		$this->isCopy = $isCopy;
	}

	function getSaveFieldSet(&$fieldrow)
    {
		$this->field = new Field($this->ct,$fieldrow,$this->row);
		
		$query = $this->getSaveFieldSetType();
		
		//Process default value
		if($this->field->defaultvalue != "" and ($query == null or $this->row[$this->field->realfieldname] == null or $this->row[$this->field->realfieldname] == ''))
		{
			$twig = new TwigProcessor($this->ct, $this->field->defaultvalue);
			$value = $twig->process($this->row);
			
			$this->row[$this->field->realfieldname] = $value;
			return $this->field->realfieldname.'='.$this->db->quote($value);
		}
		else
			return $query;
	}
		
	protected function getSaveFieldSetType()
    {
		$listing_id = $this->row[$this->ct->Table->realidfieldname];
		
        switch($this->field->type)
		{
				case 'records':
					$value = $this->get_record_type_value();
					$this->row[$this->field->realfieldname] = $value;
					return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));

				case 'sqljoin':

					$value=$this->ct->Env->jinput->getInt($this->field->comesfieldname,null);
					if(isset($value))
					{
						$this->row[$this->field->realfieldname] = $value;
						
						if($value==0)
							return $this->field->realfieldname.'=NULL';
						else
							return $this->field->realfieldname.'='.(int)$value;
					}

					break;
				case 'radio':
						$value=$this->ct->Env->jinput->getCmd($this->field->comesfieldname,null);

						if(isset($value))
						{
							$this->row[$this->field->realfieldname] = $value;
 							return $this->field->realfieldname.'='.$this->db->Quote($value);
						}
						
					break;

				case 'googlemapcoordinates':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);

						if(isset($value))
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.$this->db->Quote($value);
						}

					break;

                case 'color':

                    $value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);
						
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
                            $value=$this->ct->Env->jinput->get($this->field->comesfieldname,'','ALNUM');

                     
                        $value=strtolower($value);
                        $value=str_replace('#','',$value);
                        if(ctype_xdigit($value) or $value=='')
						{
							$this->row[$this->field->realfieldname] = $value;
                            return $this->field->realfieldname.'='.$this->db->Quote($value);
						}
                    }
					break;

				case 'alias':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);

						if(isset($value))
						{
							$value = $this->get_alias_type_value($listing_id);
							$this->row[$this->field->realfieldname] = $value;
                            return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));
						}
					break;

                case 'string':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);

						if(isset($value))
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.$this->db->Quote($value);
						}
					break;

				case 'multilangstring':

					$firstlanguage=true;
					$sets = [];
					foreach($this->Languages->LanguageList as $lang)
					{
						if($firstlanguage)
						{
							$postfix='';
							$firstlanguage=false;
						}
						else
							$postfix='_'.$lang->sef;

						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname.$postfix);

						if(isset($value))
						{
							$this->row[$this->field->realfieldname.$postfix] = $value;
							$sets[] = $this->field->realfieldname.$postfix.'='.$this->db->Quote($value);
						}
					}
					
					return (count($sets) > 0 ? $sets : null);

				case 'text':

					$value= ComponentHelper::filterText($this->ct->Env->jinput->post->get($this->field->comesfieldname, null, 'raw'));

					if(isset($value))
					{
						$this->row[$this->field->realfieldname] = $value;
						return $this->field->realfieldname.'='.$this->db->Quote(stripslashes($value));
					}

					break;

				case 'multilangtext':

					$sets = [];
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
						
						$value= ComponentHelper::filterText($this->ct->Env->jinput->post->get($this->field->comesfieldname.$postfix, null, 'raw'));

						if(isset($value))
						{
							$this->row[$this->field->realfieldname.$postfix] = $value;
							$sets[] = $this->field->realfieldname.$postfix.'='.$this->db->Quote($value);
						}
					}
					
					return (count($sets) > 0 ? $sets : null);

				case 'ordering':
						$value=$this->ct->Env->jinput->getInt($this->field->comesfieldname,null);

						if(isset($value)) // always check with isset(). null doesnt work as 0 is null somehow in PHP
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.(int)$value;
						}

					break;

				case 'int':
						$value=$this->ct->Env->jinput->getInt($this->field->comesfieldname,null);

						if(isset($value)) // always check with isset(). null doesnt work as 0 is null somehow in PHP
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.(int)$value;
						}

					break;

				case 'user':
						$value=$this->ct->Env->jinput->getVar($this->field->comesfieldname);

						if(isset($value))
                        {
                            $value=$this->ct->Env->jinput->getInt($this->field->comesfieldname);
							$this->row[$this->field->realfieldname] = $value;
							
							if($value == null)
                                return $this->field->realfieldname.'=null';
                            else
                                return $this->field->realfieldname.'='.(int)$value;
                        }

					break;

                case 'userid':

                    	$value=$this->ct->Env->jinput->getInt($this->field->comesfieldname,null);

						if(isset($value) and $value!=0)
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.(int)$value;
						}
						elseif($this->row[$this->ct->Table->realidfieldname] == 0 or $this->row[$this->ct->Table->realidfieldname] == '' or $this->isCopy)
						{
							$user = JFactory::getUser();
							$value = ($user->id!=0 ? $user->id : 0); 
							
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.(int)$value;
						}
                        
					break;

				case 'usergroup':
						$value=$this->ct->Env->jinput->getInt($this->field->comesfieldname,null);

						if(isset($value))
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.(int)$value;
						}

					break;

				case 'usergroups':
					$value = $this->get_usergroups_type_value();
					$this->row[$this->field->realfieldname] = $value;
					return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));

                case 'language':
					$value = $this->get_customtables_type_language();
					$this->row[$this->field->realfieldname] = $value;
					return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));

				case 'filelink':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);
						if(isset($value))
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.$this->db->Quote($value);
						}

					break;

				case 'float':
						$value=$this->ct->Env->jinput->get($this->field->comesfieldname,null,'FLOAT');

						if(isset($value))
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.(float)$value;
						}
						
					break;

				case 'image':

                    $image_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_image.php';
					require_once($image_type_file);
					
					$value = CT_FieldTypeTag_image::get_image_type_value($this->field, $listing_id);
					$this->row[$this->field->realfieldname] = $value;
					return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));

				case 'file':

                    $file_type_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
					require_once($file_type_file);
					
					$value = CT_FieldTypeTag_file::get_file_type_value($this->field, $listing_id);
					$this->row[$this->field->realfieldname] = $value;
					return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));
					
				case 'signature':
				
					$value = $this->get_customtables_type_signature();
					$this->row[$this->field->realfieldname] = $value;
					return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));

				case 'article':
						$value=$this->ct->Env->jinput->getInt($this->field->comesfieldname,null);

						if(isset($value))
						{
							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.$value;
						}

					break;

				case 'multilangarticle':
				
					$sets = [];
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

						$value = $this->ct->Env->jinput->getInt($this->field->comesfieldname.$postfix,null);

						if(isset($value))
						{
							$this->row[$this->field->realfieldname.$postfix] = $value;
							$sets[] = $this->field->realfieldname.$postfix.'='.(int)$value;
						}
					}
					
					return (count($sets) > 0 ? $sets : null);

				case 'customtables':
				
					$value = $this->get_customtables_type_value();
					$this->row[$this->field->realfieldname] = $value;
					return ($value == null ? null : $this->field->realfieldname.'='.$this->db->Quote($value));

				case 'email':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname);
						if(isset($value))
						{
							$value = trim($value);
							if(Email::checkEmail($value))
							{
								$this->row[$this->field->realfieldname] = $value;
								return $this->field->realfieldname.'='.$this->db->Quote($value);
							}
							else
							{
								$this->row[$this->field->realfieldname] = null;
								return $this->field->realfieldname.'='.$this->db->Quote("");//PostgreSQL compatible
							}
						}
					break;

				case 'url':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname);
						if(isset($value))
						{
							$value = trim($value);
							
							if (filter_var($value, FILTER_VALIDATE_URL))
							{
								$this->row[$this->field->realfieldname] = $value;
								return $this->field->realfieldname.'='.$this->db->Quote($value);
							}
							else
							{
								$this->row[$this->field->realfieldname] = null;
								return $this->field->realfieldname.'='.$this->db->Quote("");//PostgreSQL compatible
							}
						}
					break;

				case 'checkbox':
                    $value=$this->ct->Env->jinput->getCmd($this->field->comesfieldname);
                    
                    if($value!=null)
                    {
                        if((int)$value==1 or $value=='on')
                            $value=1;
                        else
                            $value=0;
                        
						$this->row[$this->field->realfieldname] = (int)$value;
                        return $this->field->realfieldname.'='.(int)$value;
                    }
                    else
                    {
                        $value=$this->ct->Env->jinput->getCmd($this->field->comesfieldname.'_off');
                        if($value!=null)
						{
							if((int)$value == 1)
							{
								$this->row[$this->field->realfieldname] = 0;
								return $this->field->realfieldname.'=0';
							}
							else
							{
								$this->row[$this->field->realfieldname] = 1;
								return $this->field->realfieldname.'=1';
							}
						}
                    }
                    break;

				case 'date':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);
						if(isset($value))
						{
							if($value == '' or $value == '0000-00-00')
							{
								$this->row[$this->field->realfieldname] = null;
								return $this->field->realfieldname.'=NULL';
							}
							else
							{
								$this->row[$this->field->realfieldname] = $value;
								return $this->field->realfieldname.'='.$this->db->Quote($value);
							}
						}

					break;
                
                case 'time':
						$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);
						if(isset($value))
                        {
							if($value=='')
							{
								$this->row[$this->field->realfieldname] = null;
								return $this->field->realfieldname.'=NULL';
							}
							else
							{
								$this->row[$this->field->realfieldname] = (int)$value;
								return $this->field->realfieldname.'='.(int)$value;
							}
                        } 

					break;
					
					
				case 'creationtime':
					if($this->row[$this->ct->Table->realidfieldname] == 0 or $this->row[$this->ct->Table->realidfieldname] == '' or $this->isCopy)
					{
						$value = gmdate( 'Y-m-d H:i:s');
						$this->row[$this->field->realfieldname] = $value;
						return $this->field->realfieldname.'='.$this->db->Quote($value);
					}
					break;

				case 'changetime':
						$value = gmdate( 'Y-m-d H:i:s');
						$this->row[$this->field->realfieldname] = $value;
						return $this->field->realfieldname.'='.$this->db->Quote($value);
					break;

				case 'server':

						if(count($this->field->params) == 0)
							$value=$this->getUserIP(); //Try to get client real IP
						else
							$value=$jinput->server->get($this->field->params[0],'','STRING');

						$this->row[$this->field->realfieldname] = $value;
						return $this->field->realfieldname.'='.$this->db->Quote($value);
					break;

				case 'id':
					//get max id
					if($this->row[$this->ct->Table->realidfieldname] == 0 or $this->row[$this->ct->Table->realidfieldname] == '' or $this->isCopy)
					{
						$minid=(int)$this->fields->params[0];

						$query='SELECT MAX('.$realfieldname.') AS maxid FROM '.$this->ct->Table->realtablename.' LIMIT 1';
						$this->db->setQuery( $query );
						$rows=$this->db->loadObjectList();
						if(count($rows)!=0)
						{
							$value=(int)($rows[0]->maxid)+1;
							if($value < $minid)
								$value = $minid;

							$this->row[$this->field->realfieldname] = $value;
							return $this->field->realfieldname.'='.$this->db->Quote($value);
						}
					}
					break;
				
				case 'md5':
				
					$vlu = '';
					$fields = explode(',',$this->field->params[0]);
					foreach($fields as $f1)
					{
						if($f1 != $this->field->fieldname)
						{
							//to make sure that field exists
							foreach($this->ct->Table->fields as $f2)
							{
								if($f2['fieldname']==$f1)
									$vlu.=$this->row[$f2['realfieldname']];
							}
						}
					}

					if($vlu!='')
					{
						$value = md5($vlu);
						$this->row[$this->field->realfieldname] = $value;
						return $this->field->realfieldname.'='.$this->db->Quote($value);
					}
					
					break;
			}
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

	public function Try2CreateUserAccount(&$ct,$field)
	{
		$uid=(int)$ct->Table->record[$field['realfieldname']];
	
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

		if(count($this->field->params)<3)
		{
			Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('User field name parameters count is less than 3.','error' ));
			return false;
		}
	
		//Try to create user
		$new_parts=array();

		foreach($this->field->params as $part)
		{
			tagProcessor_General::process($ct,$part,$ct->Table->record,'',1);
			tagProcessor_Item::process($ct,$ct->Table->record,$part,'','',0);
			tagProcessor_If::process($ct,$part,$ct->Table->record,'',0);
			tagProcessor_Page::process($ct,$part);
			tagProcessor_Value::processValues($ct,$ct->Table->record,$part,'[]');

			$new_parts[]=$part;
		}
	
		$user_groups=$new_parts[0];
		$user_name=$new_parts[1];
		$user_email=$new_parts[2];
	
		if($user_groups=='')
		{
			Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('User group field not set.' ));
			return false;
		}
		elseif($user_name=='')
		{
			Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('User name field not set.' ));
			return false;
		}
		elseif($user_email=='')
		{
			Factory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('User email field not set.' ));
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
				CTUser::UpdateUserField($ct->Table->realtablename, $ct->Table->realidfieldname,$field['realfieldname'],$existing_user_id,$ct->Table->record['listing_id']);
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
			CTUser::CreateUser($ct->Table->realtablename, $ct->Table->realidfieldname,$user_email,$user_name,$user_groups,$ct->Table->record['listing_id'],$field['realfieldname'],$this->realtablename);

		return true;
	}

	protected function get_customtables_type_signature()
	{
		$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);
	
		if(isset($value))
		{
			$ImageFolder = \CustomTablesImageMethods::getImageFolder($this->fields->params);
	
			$format = $this->field->params[3] ?? 'png';
		
			if($format == 'svg-db')
			{
				return $value;
			}
			else
			{
				if($format == 'jpeg')
					$format = 'jpg';

				//Get new file name and avoid possible duplicate

				$i=0;
				do
				{
					$ImageID=date("YmdHis").($i>0 ? $i : '');
					//there is possible error, check all possible ext
					$image_file = JPATH_SITE.DIRECTORY_SEPARATOR.$ImageFolder.DIRECTORY_SEPARATOR.$ImageID.'.'.$format;
					$i++;
				}while(file_exists($image_file));
		
				$parts = explode(';base64,',$value);

				$deceded_binary = base64_decode($parts[1]);
				file_put_contents($image_file, $deceded_binary);
			
				return $ImageID;
			}
		}
		return null;
	}
	
	protected function get_customtables_type_language()
	{
		$value=$this->ct->Env->jinput->getCmd($this->field->comesfieldname,null);

		if(isset($value))
			return value;

		return null;
	}

	protected function get_customtables_type_value()
	{
		$value='';

		$optionname=$this->field->params[0];

		if($this->field->params[1]=='multi')
		{
			$value=$this->getMultiString($optionname);

			if($value!=null)
			{
				if($value!='')
					return ','.$value.',';
				else
					return '';
			}
		}
		elseif($this->field->params[1]=='single')
		{
			$value=$this->getComboString($optionname);

			if($value!=null)
			{
				if($value!='')
					return ','.$value.',';
				else
					return '';
			}
		}
        return null;
    }

	protected function get_usergroups_type_value()
	{
		switch($this->field->params[0])
		{
			case 'single';
				$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);
				if(isset($value))
					return ','.$value.',';
				
				break;
			case 'multi';
				$valuearray = $this->ct->Env->jinput->post->get( $this->field->comesfieldname, null, 'array' );
				if(isset($valuearray))
					return ','.implode(',',$valuearray).',';

				break;
			case 'multibox';
				$valuearray = $this->ct->Env->jinput->post->get( $this->field->comesfieldname, null, 'array' );
				if(isset($valuearray))
					return ','.implode(',',$valuearray).',';

				break;
			case 'radio';
				$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);
				if(isset($value))
					return ','.$value.',';

				break;
			case 'checkbox';
				$valuearray = $this->ct->Env->jinput->post->get( $this->field->comesfieldname, null, 'array' );
				if(isset($valuearray))
					return ','.implode(',',$valuearray).',';

				break;
		}
        return null;
    }

	public function get_alias_type_value($listing_id)
	{
		$value=$this->ct->Env->jinput->getString($this->field->comesfieldname);
		if(!isset($value))
			return null;
    
		$value=$this->prepare_alias_type_value($listing_id,$value);
		if($value=='')
			return null;

		return $value;
	}

	public function prepare_alias_type_value($listing_id,$value)
	{
		$value=JoomlaBasicMisc::slugify($value);

		if($value=='')
			return '';

		if(!$this->checkIfAliasExists($listing_id,$value,$this->field->realfieldname))
			return $value;

		$val=$this->splitStringToStringAndNumber($value);

		$value_new=$val[0];
		$i=$val[1];

		do
		{
			if($this->checkIfAliasExists($listing_id,$value_new,$realfieldname))
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
		$query = 'SELECT count('.$this->ct->Table->realidfieldname.') AS c FROM '.$this->ct->Table->realtablename.' WHERE '
			.$this->ct->Table->realidfieldname.'!='.(int)$exclude_id.' AND '.$realfieldname.'='.$this->db->quote($value).' LIMIT 1';
		
		$this->db->setQuery( $query );

		$rows = $this->db->loadObjectList();
		if(count($rows)==0)
			return false;

		$c=(int)$rows[0]->c;

		if($c>0)
			return true;

		return false;
	}

	protected function get_record_type_value()
	{
		if(count($this->field->params)>2)
		{
			$esr_selector=$this->field->params[2];
			$selectorpair=explode(':',$esr_selector);

			switch($selectorpair[0])
			{
				case 'single';

				$value=$this->ct->Env->jinput->getString($this->field->comesfieldname,null);

				if(isset($value))
					return (int)$value;

				break;

				case 'multi';
					$valuearray = $this->ct->Env->jinput->post->get($this->field->comesfieldname, null, 'array' );

					if(isset($valuearray))
						return $this->getCleanRecordValue($valuearray);
                                    
				break;

				case 'multibox';
					$valuearray = $this->ct->Env->jinput->post->get($this->field->comesfieldname,null, 'array' );

					if(isset($valuearray))
					{
						$clean_value=$this->getCleanRecordValue($valuearray);
						return $clean_value;
					}
					break;

				case 'radio';
					$valuearray = $this->ct->Env->jinput->post->get($this->field->comesfieldname, null, 'array' );

					if(isset($valuearray))
						return $this->getCleanRecordValue($valuearray);

					break;

				case 'checkbox';
					$valuearray = $this->ct->Env->jinput->post->get($this->field->comesfieldname, null, 'array' );

					if(isset($valuearray))
						return $this->getCleanRecordValue($valuearray);

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

	protected function getMultiString($parent)
	{
		$prefix = $this->field->prefix.'multi_'.$this->ct->Table->tablename.'_'.$this->field->fieldname;
		
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
				$value=$this->ct->Env->jinput->getString($prefix.'_'.$row->id,null);
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

	protected function getComboString($parent)
	{
		$prefix = $this->field->prefix.'combotree_'.$this->tablename.'_'.$this->field->fieldname;
		
		$i=1;
		$result=array();
		$v='';
		$set=false;
		do
		{

			$value=$this->ct->Env->jinput->getCmd($prefix.'_'.$i);
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

    function runUpdateQuery(&$savequery,$listing_id)
    {
		if(count($savequery)>0)
		{
			$query='UPDATE '.$this->ct->Table->realtablename.' SET '.implode(', ',$savequery).' WHERE '.$this->ct->Table->realidfieldname.'='.$this->db->quote($listing_id);
			$this->db->setQuery( $query );
			$this->db->execute();
		}
    }
}
