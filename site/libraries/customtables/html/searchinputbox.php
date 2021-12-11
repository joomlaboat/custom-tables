<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

namespace CustomTables;

defined('_JEXEC') or die('Restricted access');

use CustomTables\DataTypes\Tree;
use \JoomlaBasicMisc;

use \Joomla\CMS\Factory;
use \JHTML;

JHTML::addIncludePath(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers');

class SearchInputBox
{
	var $ct;
	var $modulename;
	
	function __construct(&$ct, $modulename)
	{
		$this->ct = $ct;
		$this->modulename = $modulename;
	}

	function renderFieldBox($prefix,$objname,&$esfield,$cssclass,$index,$where,$innerjoin,$wherelist,$default_Action,$field_title=null)
	{
		if(!isset($esfield['fieldtitle'.$this->ct->Languages->Postfix]))
		{
			Factory::getApplication()->enqueueMessage(
				JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ERROR_LANGFIELDNOTFOUND' ), 'Error');
			
			return '';	
		}

		$place_holder=$esfield['fieldtitle'.$this->ct->Languages->Postfix];
	
		if($field_title==null)
			$field_title=$esfield['fieldtitle'.$this->ct->Languages->Postfix];

		$result='';

		$value=Factory::getApplication()->input->getCmd($prefix.$objname);
		
		if($value=='')
		{
			if(isset($esfield['fields']) and count($esfield['fields'])>0)
				$where_name = implode(';',$esfield['fields']);
			else
				$where_name = $esfield['fieldname'];
				
			$value=$this->getWhereParameter($where_name);
		}

		$objname_=$prefix.$objname;
		
		if($this->ct->Env->version < 4)
			$default_class='inputbox';
		else
			$default_class='form-control';

		switch($esfield['type'])
		{
						case 'int':
							$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'"
							value="'.$value.'" placeholder="'.$field_title.'"'
							.' onkeypress="es_SearchBoxKeyPress(event)"  />';


							break;

						case 'float':
							$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" value="'.$value.'"
							value="'.$value.'" placeholder="'.$field_title.'"'
							.' onkeypress="es_SearchBoxKeyPress(event)" />';

							break;

						case 'string':
							$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
							.' value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"')
							.' placeholder="'.$field_title.'"'
							.' onkeypress="es_SearchBoxKeyPress(event)"'
							.' />';

							break;

						case 'phponchange':
							$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
							.' value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"')
							.' placeholder="'.$field_title.'"'
							.' onkeypress="es_SearchBoxKeyPress(event)"'
							.' />';

							break;

						case 'phponadd':
							$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
							.' value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"')
							.' placeholder="'.$field_title.'"'
							.' onkeypress="es_SearchBoxKeyPress(event)"'
							.' />';

							break;

						case 'multilangstring':

								$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
								.' value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"')
								.' placeholder="'.$field_title.'"'
								.' onkeypress="es_SearchBoxKeyPress(event)"'
								.' />';

							break;

						case 'text':
								$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
								.' value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"')
								.' placeholder="'.$field_title.'"'
								.' onkeypress="es_SearchBoxKeyPress(event)"'
								.' />';


							break;


						case 'multilangtext':

								$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
								.' value="'.$value.'" '.((int)$esfield['typeparams']>0 ? 'maxlength="'.(int)$esfield['typeparams'].'"' : 'maxlength="255"')
								.' placeholder="'.$field_title.'" onkeypress="es_SearchBoxKeyPress(event)" />';

							break;

						case 'checkbox':
								$result.=$this->getCheckBox($esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass);
								break;

						case 'range':
								$result.=$this->getRangeBox($esfield,$index,$where,$wherelist,$objname_,$value, $cssclass);
								break;

						case 'customtables':
								$result.=$this->getCustomTablesBox($prefix,$innerjoin,$esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass,$place_holder);
								break;

						case 'userid':
							$result.=$this->getUserBox($esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass);
							break;

						case 'user':
							$result.=$this->getUserBox($esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass);
							break;

						case 'usergroup':
							$result.=$this->getUserGroupBox($esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass);
							break;

						case 'usergroups':
							$result.=$this->getUserGroupBox($esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass);
							break;

						case 'records':
							$result.=$this->getRecordsBox($esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass);
							break;

						case 'sqljoin':
							$result.=$this->getTableJoinBox($esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass);
						break;

						case 'email';
								$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
								.' placeholder="'.$field_title.'"'
								.' onkeypress="es_SearchBoxKeyPress(event)"'
								.' value="'.$value.'" maxlength="255" />';
								
						break;

						case 'url';
								$result.='<input type="text" name="'.$objname_.'" id="'.$objname_.'" class="'.$cssclass.' '.$default_class.'" '
								.' placeholder="'.$field_title.'"'
								.' onkeypress="es_SearchBoxKeyPress(event)"'
								.' value="'.$value.'" maxlength="1024" />';
						break;

						case 'date';
								$result.=JHTML::calendar($value, $objname_, $objname_);

						break;

		}
		return $result;
	}

	function getTableJoinBox(&$esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass)
	{
		$result='';

		$typeparams=JoomlaBasicMisc::csv_explode(',',$esfield['typeparams'],'"',false);

		if($default_Action!='' and $default_Action!=' ')
			$onchange=$default_Action;
		else
			$onchange=' onkeypress="es_SearchBoxKeyPress(event)"';

		if(is_array($value))
			$value=implode(',',$value);

		if($this->ct->Env->version < 4)
			$default_class='inputbox';
		else
			$default_class='form-control';

		$typeparams=JoomlaBasicMisc::csv_explode(',',$esfield['typeparams'],'"',false);
		$result.='<div class="'.$cssclass.'">'.JHTML::_('ESSQLJoin.render',$typeparams,$value,true,$this->ct->Languages->Postfix,$objname_,
				$esfield['fieldtitle'].$this->ct->Languages->Postfix,
			  ' '.$default_class.' es_class_sqljoin', $onchange,true).'</div>';

		return $result;
	}

	function getRecordsBox(&$esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass)
	{
		$result='';

		$typeparams=JoomlaBasicMisc::csv_explode(',',$esfield['typeparams'],'"',false);

							if(count($typeparams)<1)
								$result.='table not specified';

							if(count($typeparams)<2)
								$result.='field or layout not specified';

							if(count($typeparams)<3)
								$result.='selector not specified';

							$esr_table=$typeparams[0];
							$esr_field=$typeparams[1];
							$esr_selector=$typeparams[2];


							if($wherelist!='')
								$esr_filter=$wherelist;
							elseif(count($typeparams)>3)
								$esr_filter=$typeparams[3];
							else
								$esr_filter='';

							$dynamic_filter='';

							$sortbyfield='';
							if(isset($typeparams[5]))
								$sortbyfield=$typeparams[5];

							$v=array();
							$v[]=$index;
							$v[]='this.value';
							$v[]='"'.$esfield['fieldname'].'"';
							$v[]='"'.urlencode($where).'"';
							$v[]='"'.urlencode($wherelist).'"';
							$v[]='"'.$this->ct->Languages->Postfix.'"';

							if($default_Action!='' and $default_Action!=' ')
								$onchange=$default_Action;
							else
								$onchange=' onkeypress="es_SearchBoxKeyPress(event)"';

							if(is_array($value))
								$value=implode(',',$value);

							$real_selector=$esr_selector;

							$real_selector='single';

							$result.=JHTML::_('ESRecords.render',$typeparams,$objname_, $value,$esr_table,$esr_field,$real_selector,$esr_filter,'',
 $cssclass, $onchange,$dynamic_filter,$sortbyfield,$this->ct->Languages->Postfix,$esfield['fieldtitle'].$this->ct->Languages->Postfix);

							return $result;

	}

	function getCustomTablesBox($prefix,$innerjoin,&$esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass,$place_holder='')
	{
		$result='';
		$typeparams=explode(',',$esfield['typeparams']);
							$optionname=$typeparams[0];

							$parentid=Tree::getOptionIdFull($optionname);

							if($default_Action!='')
							{
								$onchange=$default_Action;
								$requirementdepth=1;
							}
							else
							{
								$onchange=$this->modulename.'_onChange('
									.$index.','
									.'me.value,'
									.'\''.$esfield['fieldname'].'\','
									.'\''.urlencode($where).'\','
									.'\''.urlencode($wherelist).'\','
									.'\''.$this->ct->Languages->Postfix.'\''
									.')';
								$requirementdepth=0;
							}

							$result.=JHTML::_('ESComboTree.render',
											  $prefix,
											  $this->ct->Table->tablename,
											  $esfield['fieldname'],
											  $optionname,
											  $this->ct->Languages->Postfix,
											  $value,
											  $cssclass,
											  $onchange,
											  $where,
											  $innerjoin,false,$requirementdepth,
											  $place_holder,
											  '',
											  ''
											);

		return $result;
	}

	function getCheckBox(&$esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass)
	{
		$result='';
		
		if($this->ct->Env->version < 4)
			$default_class='inputbox';
		else
			$default_class='form-control';
		
								if($esfield['essb_option']=='any')
								{
									if($esfield['essb_option2']!='')
										$translations=JoomlaBasicMisc::csv_explode(',', $esfield['essb_option2'], '"', false);
									else
										$translations=array('Any','Checked','Unchecked');

										$onchange=' onChange="'.$this->modulename.'_onChange('
										.$index.','
										.'this.value,'
										.'\''.$esfield['fieldname'].'\','
										.'\''.urlencode($where).'\','
										.'\''.urlencode($wherelist).'\','
										.'\''.$this->ct->Languages->Postfix.'\''
										.')"';


										$result.='
									<select id="'.$objname_.'" name="'.$objname_.'" '.$onchange.' class="'.$cssclass.' '.$default_class.'" >
										<option value="" '.($value=='' ? 'SELECTED' : '').'>'.$translations[0].'</option>
										<option value="true" '.($value=='true' ? 'SELECTED' : '').'>'.$translations[1].'</option>
										<option value="false" '.($value=='false' ? 'SELECTED' : '').'>'.$translations[2].'</option>
									</select>
									';


								}
								else
								{

									if($default_Action!='')
										$onAction=$default_Action;
									else
									{
										$onAction=' onChange="'.$this->modulename.'_onChange('
										.$index.','
										.'this.value,'
										.'\''.$esfield['fieldname'].'\','
										.'\''.urlencode($where).'\','
										.'\''.urlencode($wherelist).'\','
										.'\''.$this->ct->Languages->Postfix.'\''
										.')"';
									}

									$result.='<input type="checkbox" class="'.$cssclass.' '.$default_class.'" '
										.' id="'.$objname_.'" '
										.' name="'.$objname_.'" '
										.($value=='on' ? ' checked="checked" ' : '')
										.' onkeypress="es_SearchBoxKeyPress(event)"'
										.' '.$onAction.' >';


								}

		return $result;
	}


	function getRangeBox(&$esfield,$index,$where,$wherelist,$objname_,$value, $cssclass)
	{
		$jinput=Factory::getApplication()->input;
		$result='';
		
		if($this->ct->Env->version < 4)
			$default_class='inputbox';
		else
			$default_class='form-control';

		$value_min='';
		$value_max='';

								if($esfield['typeparams']=='date')
									$d='-to-';
								if($esfield['typeparams']=='float')
									$d='-';

								$values=explode($d,$value);
								$value_min=$values[0];

								if(isset($values[1]))
									$value_max=$values[1];

								if($value_min=='')
									$value_min=$jinput->getString($objname_.'_min');

								if($value_max=='')
									$value_max=$jinput->getString($objname_.'_max');

								//header function
								$document = Factory::getDocument();
								$js='
	function Update'.$objname_.'Values()
	{
		var o=document.getElementById("'.$objname_.'");
		var v_min=document.getElementById("'.$objname_.'_min").value
		var v_max=document.getElementById("'.$objname_.'_max").value;
		o.value=v_min+"'.$d.'"+v_max;

		//'.$this->modulename.'_onChange('.$index.',v_min+"'.$d.'"+v_max,"'.$esfield['fieldname'].'","'.urlencode($where).'","'.urlencode($wherelist).'");
	}
';

								$document->addScriptDeclaration($js);
								//end of header function

								$attribs='onChange="Update'.$objname_.'Values()" class="'.$default_class.'" ';


								$result.='<input type="hidden"'
										.' id="'.$objname_.'" '
										.' name="'.$objname_.'" '
										.' value="'.$value_min.$d.$value_max.'" '
										.' onkeypress="es_SearchBoxKeyPress(event)"'
										.' />';

								$result.='<table class="es_class_min_range_table" border="0" cellpadding="0" cellspacing="0" class="'.$cssclass.'" ><tbody><tr><td valign="middle">';

								//From
								if($esfield['typeparams']=='date')
								{
									$result.=JHTML::calendar($value_min, $objname_.'_min', $objname_.'_min','%Y-%m-%d',$attribs);
								}
								else
								{
									$result.='<input type="text"'
										.' id="'.$objname_.'_min" '
										.' name="'.$objname_.'_min" '
										. 'value="'.$value_min.'" '
										.' onkeypress="es_SearchBoxKeyPress(event)" '

										.str_replace('class="','class="es_class_min_range ',$attribs)
										.' >';
								}

								$result.='</td><td width="20" align="center">-</td><td align="left" width="140" valign="middle">';

								//To
								if($esfield['typeparams']=='date')
								{

									$result.=JHTML::calendar($value_max, $objname_.'_max', $objname_.'_max','%Y-%m-%d',$attribs);
								}
								else
								{
									$result.='<input type="text"'
										.' id="'.$objname_.'_max" '
										.' name="'.$objname_.'_max" '
										. 'value="'.$value_max.'" '
										.' onkeypress="es_SearchBoxKeyPress(event)" '

										.str_replace('class="','class="es_class_min_range ',$attribs)
										.' >';
								}

								$result.='</td></tr></tbody></table>';

		return $result;
	}

	function getUserGroupBox(&$esfield,$default_Action,$index,$where,$wherelist,$objname_,$value,$cssclass)
	{
		$result='';

		$mysqljoin='#__customtables_table_'.$this->ct->Table->tablename.' ON #__customtables_table_'.$this->ct->Table->tablename.'.es_'.$esfield['fieldname'].'=#__usergroups.id';

		$usergroup=$esfield['typeparams'];
		
		if($this->ct->Env->version < 4)
			$cssclass='class="inputbox '.$cssclass.'" ';
		else
			$cssclass='class="form-control '.$cssclass.'" ';
			

		$user =  Factory::getUser();

		if($default_Action!='')
		{
			$onchange=$default_Action;
		}
		else
		{
			$onchange=' onChange=   "'.$this->modulename.'_onChange('
									.$index.','
									.'this.value,'
									.'\''.$esfield['fieldname'].'\','
									.'\''.urlencode($where).'\','
									.'\''.urlencode($wherelist).'\','
									.'\''.$this->ct->Languages->Postfix.'\''
									.')"';
		}

		if($user->id!=0)
			$result=JHTML::_('ESUserGroup.render',$objname_, $value, '', $cssclass, $onchange,$where, $mysqljoin);

		return $result;
	}

	function getUserBox(&$esfield,$default_Action,$index,$where,$wherelist,$objname_,$value, $cssclass)
	{
		$result='';

		$mysqljoin='#__customtables_table_'.$this->ct->Table->tablename.' ON #__customtables_table_'.$this->ct->Table->tablename.'.es_'.$esfield['fieldname'].'=#__users.id';

		$usergroup=$esfield['typeparams'];
		
		$user =  Factory::getUser();

		if($default_Action!='')
		{
			$onchange=$default_Action;
		}
		else
		{
			$onchange=' onChange=   "'.$this->modulename.'_onChange('
									.$index.','
									.'this.value,'
									.'\''.$esfield['fieldname'].'\','
									.'\''.urlencode($where).'\','
									.'\''.urlencode($wherelist).'\','
									.'\''.$this->ct->Languages->Postfix.'\''
									.')"';
		}

		if($this->ct->Env->version < 4)
			$default_class='inputbox';
		else
			$default_class='form-control';

		if($user->id!=0)
			$result=JHTML::_('ESUser.render',$objname_, $value, '', 'class="'.$cssclass.' '.$default_class.'" ', $usergroup, $onchange,$where, $mysqljoin);


		return $result;
	}

	protected function getWhereParameter($field)
	{
		$f=str_replace($this->ct->Env->field_prefix,'',$field);//legacy support

		$list=$this->getWhereParameters();

		foreach($list as $l)
		{
			$p=explode('=',$l);
			$fld_name=str_replace('_t_','',$p[0]);
			$fld_name=str_replace('_r_','',$fld_name); //range

			if($fld_name==$f and isset($p[1]))
				return $p[1];

		}
		return '';
	}

	protected function getWhereParameters()
	{
		$value=Factory::getApplication()->input->getString('where');
		$value=str_replace('update','',$value);
		$value=str_replace('select','',$value);
		$value=str_replace('drop','',$value);
		$value=str_replace('grant','',$value);
		$value=str_replace('user','',$value);

		$b=base64_decode($value);
		$b=str_replace(' or ',' and ',$b);
		$b=str_replace(' OR ',' and ',$b);
		$b=str_replace(' AND ',' and ',$b);
		$list=explode(' and ',$b);
		return $list;
	}
}
