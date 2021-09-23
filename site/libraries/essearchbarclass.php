<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

defined('_JEXEC') or die('Restricted access');

use CustomTables\CT;
use CustomTables\Fields;

class ESSearchBarClass
{
	var $ct;
	
	var $isLoader;

	var $orientation;
	var $moduleid;
	var $modulename;
	var $esTable;
	var $TableID;
	var $esinputbox;
	var $Fields;
	var $startindex;
	var $raw_fieldlist;
	var $fieldlist;
	var $fieldlist_style;
	var $fieldlist_option;

	var $Itemid;

	var $where_arr;
	var $wherelist_arr;

	var $floatstyle;
	var $alignto;

	var $wherelist_arr_start;

	function Loadme_Step1($isLoader,&$params)
	{
		$jinput=JFactory::getApplication()->input;

		$this->isLoader=$isLoader;

		//get establename
		$this->modulename='essearchbar_'.$this->moduleid;
		
		$this->ct = new CT;
		$this->ct->getTable($this->params->get( 'establename' ), $this->params->get('useridfield'));
		if($this->ct->Table->tablename=='')
		{
			echo 'Table not selected';
			die ;
		}

		$this->esinputbox= new ESSerachInputBox;
		$this->esinputbox->Model = $this;
		$this->esinputbox->modulename=$this->modulename;

		//get field list
		if($jinput->getString('fieldlist'))
			$this->raw_fieldlist=trim($jinput->getString('fieldlist'));
		else
			$this->raw_fieldlist=trim($params->get('fieldlist'));

		$this->raw_fieldlist=str_replace("\n",'',$this->raw_fieldlist);
		$this->raw_fieldlist=str_replace("\r",'',$this->raw_fieldlist);

		$this->getCleanFieldList($this->raw_fieldlist);

		$row=array();		//-----------------
		$this->startindex=$jinput->getInt('index',1);

		if($this->orientation=='horizontal')
		{
			//float:left;
			$this->floatstyle='style="text-align:left;max-width:200px;padding-right:5px;border:none;float:left;position:static;"';
			$this->alignto='left';
		}
		elseif($this->orientation=='vertical')
		{
			$this->floatstyle='';
			$this->alignto='center';
		}
		else
		{
			$this->floatstyle='';
			$this->alignto='';
		}
	}//function Loadme($isLoader)

	function renderJavascriptNeeded_Step2()
	{
		$jinput=JFactory::getApplication()->input;

		$WebsiteRoot=JURI::root(true);
		$WebsiteRoot=str_replace('/components/com_customtables/libraries/','',$WebsiteRoot);
		$WebsiteRoot=str_replace('/components/com_customtables/libraries','',$WebsiteRoot);

		if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
			$WebsiteRoot.='/';

		if(!$this->isLoader)
		{


			$qLink='modules/mod_essearchbar/mod_essearchbar.php?'
			.'&fieldlist='.urlencode($this->raw_fieldlist)
			.'&orientation='.$this->orientation
			.'&Itemid='.$this->Itemid
			.'&moduleid='.$this->moduleid
			;
		echo '

	<script language="javascript">

		var '.$this->modulename.'_fieldcount='.count($this->fieldlist).';
		function '.$this->modulename.'_onChange(index,value,fieldname,where,wherelist,langpostfix)
		{
				//reload fields
				var newindex=index+1;


				var q="'.$WebsiteRoot.$qLink.'&index="+newindex+"&value="+value+"&fieldname="+fieldname+"&where="+where+"&wherelist="+wherelist+"&langpostfix="+langpostfix;

		        if (window.XMLHttpRequest)
		        {
		            // code for IE7+, Firefox, Chrome, Opera, Safari
		            objXml =new XMLHttpRequest();
		        }
				else if (window.ActiveXObject)
				{
				    // code for IE6, IE5
				    objXml =new ActiveXObject("Microsoft.XMLHTTP");
				}

				objXml.open("GET", q, true);
				objXml.onreadystatechange=function()
				{
					if (objXml.readyState==4)
					{
						if(index<'.$this->modulename.'_fieldcount)
						{
							var obj=document.getElementById("'.$this->modulename.'_"+newindex);

							obj.innerHTML=objXml.responseText;

						}

					}
				}
				objXml.send(null);
		}
		function setWhereList()
		{
			var value="";
			var t="";
			var i=0;
			';
			$count=0;
			foreach($this->fieldlist as $fld)
			{
			echo '
			t="";';

				$count++;
				if($fld['type']=='customtables')
				{
						$typeparams=explode(',',$fld['typeparams']);
						$parent=$typeparams[0];
					echo '

			i=1;
			do{

				vo=document.getElementById("combotree_'.$this->ct->Table->tablename.'_'.$fld['fieldname'].'_"+i);
				if(vo == null)
					break;

				if(vo.value=="")
					break;

				if(t!="")
					t+=".";
				t+=vo.value;
				i++;
			}while(1);

			if(t!="")
			{
				if(value!="")
					value+=" and ";
				value+="'.$fld['fieldname'].'='.$parent.'."+t;

			}
			';

				}
				elseif($fld['type']=='user' or $fld['type']=='userid')
				{
					ESSearchBarClass::getJSForFieldType_user($this->modulename,$count,$fld['fieldname']);
				}
				elseif($fld['type']=='records')
				{
					ESSearchBarClass::getJSForFieldType_records($this->modulename,$count,$fld['fieldname'],$fld['typeparams']);
				}
				elseif($fld['type']=='sqljoin')
				{
					ESSearchBarClass::getJSForFieldType_sqljoin($this->modulename,$count,$fld['fieldname']);
				}
				elseif($fld['type']=='range')
				{
					echo '

			t=document.getElementById("'.$this->modulename.'_'.$count.'_obj").value;
			if(t!="" && t!="-")
			{

				if(value!="")
					value+=" and ";

				value+="'.$fld['fieldname'].'="+t;
			}
';

				}
				elseif($fld['type']=='checkbox')
				{
					if($fld['essb_option']=='true')
					{
						echo '

			t=document.getElementById("'.$this->modulename.'_'.$count.'_obj").checked;

			if(value!="")
				value+=" and ";


			if(t)
				value+="'.$fld['fieldname'].'="+t;

';
					}
					elseif($fld['essb_option']=='false')
					{
						echo '

			t=document.getElementById("'.$this->modulename.'_'.$count.'_obj").checked;

			if(value!="")
				value+=" and ";


			if(!t)
				value+="'.$fld['fieldname'].'="+t;

';
					}
					elseif($fld['essb_option']=='any')
					{
						echo '

			t=document.getElementById("'.$this->modulename.'_'.$count.'_obj").value;

			if(t!="")
			{
				if(value!="")
					value+=" and ";

				value+="'.$fld['fieldname'].'="+t;
			}
';
					}
					else
					{
						echo '

			t=document.getElementById("'.$this->modulename.'_'.$count.'_obj").checked;

			if(value!="")
				value+=" and ";

			value+="'.$fld['fieldname'].'="+t;

';
					}

				}
				else
				{
					echo '

			t=document.getElementById("'.$this->modulename.'_'.$count.'_obj").value;
			if(t!="")
			{
				if(value!="")
					value+=" and ";

				value+="'.$fld['fieldname'].'="+t;
			}
';

				}


			}

			echo '

			document.getElementById("where").value=escape(Base64.encode(value));

			return true;
		}

	</script>
	';

		if($jinput->get('where','','BASE64'))
		{
			$decodedurl=urldecode($jinput->get('where','','BASE64'));

			$this->wherelist_arr_start=explode(' and ',base64_decode($decodedurl));
		}
		else
			$this->wherelist_arr_start=array();

		$this->wherelist_arr=array();
		$this->where_arr=array();


		if($this->orientation!='clean')
			echo '<div style="display:table;float:none;">';
		else
			echo '<div>';

		echo '<form action="" method="get" onSubmit="return setWhereList();" >';

		if($this->orientation!='clean')
			echo '<div style="float:left;position: relative;padding:0px;display:block;">';
		else
			echo '<div>';
	}
	else
	{
		if($jinput->getString('where'))
		{
			$this->where_arr=explode(' AND ',$jinput->('where'));
		}
		else
			$this->where_arr=array();

		$wherelist=$jinput->getString('wherelist');

		if($wherelist!='')
		{
			$this->wherelist_arr=explode(';',$wherelist);
		}
		else
			$this->wherelist_arr=array();

		$value=$jinput->getCmd('value');
		$fieldname=$jinput->getCmd('fieldname','');
		$fieldraw=$this->getFieldRow($fieldname);

		$where=$this->getWhereByFieldType($value, $fieldraw);

		if($where!='')
			$this->where_arr[]=$where;

		if($value!='')
			$this->wherelist_arr[]=$fieldname.'*'.$value;
	}


	}//renderJavascriptNeeded()

	static protected function getJSForFieldType_user($modulename,$count,$fieldname)
	{
		echo '

			tObject=document.getElementById("'.$modulename.'_'.$count.'_obj");

			if(tObject)
			{
				t=tObject.value;

				if(t!=0)
				{
					if(value!="")
						value+=" and ";

					value+="'.$fieldname.'="+t;
				}
			}
';
	}


	static protected function getJSForFieldType_records($modulename,$count,$fieldname,$typeparams_str)
	{
		$typeparams=explode(',',$typeparams_str);
					$esr_selector=$typeparams[2];

						echo '
						var obj_records=document.getElementById("'.$modulename.'_'.$count.'_obj");
						t=obj_records.value;
						';
			echo '


			if(t!="" && t!="-")
			{

				if(value!="")
					value+=" and ";

				value+="'.$fieldname.'="+t;

			}
';
	}

	static protected function getJSForFieldType_sqljoin($modulename,$count,$fieldname)
	{
		echo '
						var obj_records=document.getElementById("'.$modulename.'_'.$count.'_obj");
						t=obj_records.value;
						';
			echo '


			if(t!="" && t!="0")
			{

				if(value!="")
					value+=" and ";

				value+="'.$fieldname.'="+t;

			}
';
	}

	public function renderFields($parent,&$count,$level)
	{
		$outputresult='';
		$fieldindex=0;

		foreach($this->fieldlist as $fld)
		{
			if($fld['parentid']==$parent)
			{
				$count++;

				if(!$this->isLoader)
				{
				//process start "where" value
					if($count>1)
					{

						$fld_start=$this->fieldlist[$count-2];


						foreach($this->wherelist_arr_start as $wherelist_a)
						{
							$pair=explode('=',$wherelist_a);

							if($pair[0]==$fld_start['fieldname'])
							{

								$where=$this->getWhereByFieldType($pair[1], $fld_start,$this->ct->Languages->Postfix);

								if($where!='')
								{
									$this->where_arr[]=$where;
									$this->wherelist_arr[]=$wherelist_a;
								}
								else
								{


								}

								break;
							}
						}//foreach
					}//if


						$outputresult.='<!-- Open '.$this->modulename.'_'.$count.' -->';

						if($this->orientation!='clean')
							$outputresult.='<div style="float:left;position:static;padding:0px;border:none;text-align:'.$this->alignto.';" id="'.$this->modulename.'_'.$count.'">';
						else
							$outputresult.='<div id="'.$this->modulename.'_'.$count.'">';
				}
				else
				{
					if($count>$this->startindex)
					{
						$outputresult.='<!-- Open '.$this->modulename.'_'.$count.' -->';

						if($this->orientation!='clean')
							$outputresult.='<div style="float:left;position:static;padding:0px;border:none;text-align:'.$this->alignto.';" id="'.$this->modulename.'_'.$count.'">';
						else
							$outputresult.='<div id="'.$this->modulename.'_'.$count.'">';
					}
				}


				$where=implode(' AND ',$this->where_arr);

				$wherelist=implode(' and ',$this->wherelist_arr);

				if($count>=$this->startindex)
				{
					$objname=$this->modulename.'_'.$count.'_obj';



					if($fld['type']=='checkbox')
					{

						$childs=$this->renderFields($fld['id'],$count,$level+1);

						if($childs!='')
							$customAction=
							'onClick="

							if(this.checked==1)
								document.getElementById(\''.$objname.'_child\').style.display=\'block\';
							else
								document.getElementById(\''.$objname.'_child\').style.display=\'none\';
							"';
						else
							$customAction='';

						if($this->orientation!='clean')
							$outputresult.='<div style="float:left;position:static;padding: 0 5px 0 0;none;">';
						else
							$outputresult.='<div>';


						$outputresult_temp=$this->esinputbox->renderFieldBox(array(),'',$objname,$fld,
											'',
											$count,$where,true,$wherelist,$customAction);


						if($outputresult_temp!='')
						{
							if($fld['essb_option']=='any')
							{
								if($fld['fieldtitle'.$this->ct->Languages->Postfix]!='')
									$outputresult.='<span>'.$fld['fieldtitle'.$this->ct->Languages->Postfix].':</span>';

								if($this->orientation!='clean')
									$outputresult.='<br/>';

								$outputresult.=$outputresult_temp;
							}
							else
							{
								if($this->orientation!='clean')
									$outputresult.='<br/>'.$outputresult_temp;
								else
									$outputresult.=$outputresult_temp;

								$outputresult.='<span>'.$fld['fieldtitle'.$this->ct->Languages->Postfix].':</span>';
							}

						}//if($outputresult_temp!='')


						//child
						if($childs!='')
						{
							$value=$jinput->getCmd($objname);

							if($value=='on')
								$display='block';
							else
								$display='none';

							if($this->orientation!='clean')
								$outputresult.='<div id="'.$objname.'_child" style="margin-left:20px;border:none;solid;display:'.$display.';">'.$childs.'</div>';
							else
								$outputresult.='<div id="'.$objname.'_child">'.$childs.'</div>';
						}
					}
					else
					{
						$outputresult_temp=$this->esinputbox->renderFieldBox(array(),'',$objname,$fld,
										($fld['essb_style']!='' ? $fld['essb_style'] : 'margin:3px;padding:0px;'),
										$count,$where,true,$wherelist,'');

						if($this->orientation!='clean')
						{
							if($fld['type']=='range')
								$outputresult.='<div style="text-align:left;padding-right:5px;border:none;float:left;position:static;">';
							else
								$outputresult.='<div '.$this->floatstyle.'>';
						}
						else
						{
								$outputresult.='<div>';
						}

						if($outputresult_temp!='')
						{
							if($fld['fieldtitle'.$this->ct->Languages->Postfix]!='')
							{
								$outputresult.='<span>'.$fld['fieldtitle'.$this->ct->Languages->Postfix].':</span>';
								if($this->orientation!='clean')
									$outputresult.='<br/>';
							}

							$outputresult.=$outputresult_temp;
						}

					}

					$outputresult.='</div>';
				}
			}//if($fld[parent]==$parent)

			$fieldindex++;
		}//foreach($fieldlist as $fld)

		return $outputresult;
	}

	function getWhereByFieldType($value, &$fieldrow)
	{
		$db = JFactory::getDBO();

		if($value=='')
			return '';

		switch($fieldrow['type'])
		{
			case 'user' :
				return $fieldrow['realfieldname'].'=='.(int)$value;
				break;

			case 'userid' :
				return $fieldrow['realfieldname'].'=='.(int)$value;
				break;

			case 'checkbox' :


				if($fieldrow['essb_option']=='true')
				{
					//This will work out situations when "All or Checked" is needed
					if($value=='true')
						return $fieldrow['realfieldname'];
				}
				elseif($fieldrow['essb_option']=='false')
				{
					//This will work out situations when "Unchecked or All" is needed
					if($value!='true')
						return '!'.$fieldrow['realfieldname'];
				}
				elseif($fieldrow['essb_option']=='any')
				{
					//This will work out situations when "Checked or Unchecked or All" is needed
					if($value=='true')
						return $fieldrow['realfieldname'];

					if($value=='false')
						return '!'.$fieldrow['realfieldname'];
				}
				else
				{
					if($value=='true')
						return $fieldrow['realfieldname'];
					else
						return '!'.$fieldrow['realfieldname'];
				}

			break;


			case 'string' :
				return 'INSTR('.$fieldrow['realfieldname'].', '.$db->quote($value).')';

			case 'phponadd' :
				return 'INSTR('.$fieldrow['realfieldname'].', '.$db->quote($value).')';

			case 'phponchange' :
				return 'INSTR('.$fieldrow['realfieldname'].', '.$db->quote($value).')';

			case 'multilangstring' :
				return 'INSTR('.$fieldrow['realfieldname'].$this->ct->Languages->Postfix.', '.$db->quote($value).')';

			break;

			case 'customtables' :
				$typeparams=explode(',',$fieldrow['typeparams']);
				if($typeparams[0]!='')
					return 'INSTR('.$fieldrow['realfieldname'].', '.$db->quote(','.$value.'.').')';

			break;

			case 'records' :

				return 'INSTR('.$fieldrow['realfieldname'].', '.$db->quote($value).',)';
				break;

			case 'sqljoin' :

				return $fieldrow['realfieldname'].'='.(int)$value;
				break;
		}
		return '';
	}

	function getFieldRow($fieldname)
	{
		foreach($this->fieldlist as $fld)
		{
			if($fld['fieldname']==$fieldname)
				return $fld;
		}
		return array();
	}


	function getCleanFieldList($fieldlist_str)
	{
		$cleanfieldlist=array();
		$fieldlist=JoomlaBasicMisc::csv_explode(',', $fieldlist_str, $enclose='"', true);

		foreach($fieldlist as $fieldnameraw_)
		{
			$fieldnamerawpair=JoomlaBasicMisc::csv_explode(':', $fieldnameraw_, $enclose='"', false);

			$fieldnameraw=$fieldnamerawpair[0];

			if(isset($fieldnamerawpair[1]) and $fieldnamerawpair[1]!='')
				$fieldnamerawstyle=$fieldnamerawpair[1];
			else
				$fieldnamerawstyle='';

			if(isset($fieldnamerawpair[2]) and $fieldnamerawpair[2]!='')
				$fieldnamerawoption=$fieldnamerawpair[2];
			else
				$fieldnamerawoption='';


			if(isset($fieldnamerawpair[3]) and $fieldnamerawpair[3]!='')
				$second_option=$fieldnamerawpair[3];
			else
				$second_option='';

			//Example: price_r_price_t_Precio
			$fieldnamearr=explode('_t_',$fieldnameraw);
			$fieldname=strtolower(trim(preg_replace('/[^a-zA-Z,\-_]/', '', $fieldnamearr[0])));

			if(strpos($fieldname,'_r_')===false)
			{
				//normal fields
				foreach($this->ct->Table->fields  as $fld)
				{

					if($fld['fieldname']==$fieldname)
					{
						if(isset($fieldnamearr[1]))
							$fld['fieldtitle'.$this->ct->Languages->Postfix]=$fieldnamearr[1];

						if($fld['type']=='checkbox')
						{
							if($fieldnamerawoption=='true')
								$fld['fieldtitle'.$this->ct->Languages->Postfix]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ONLY' ).' '.$fld['fieldtitle'.$this->ct->Languages->Postfix];
							elseif($fieldnamerawoption=='false')
								$fld['fieldtitle'.$this->ct->Languages->Postfix]=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_INCLUDING' ).' '.$fld['fieldtitle'.$this->ct->Languages->Postfix];
						}

						$fld['essb_style']=$fieldnamerawstyle;
						$fld['essb_option']=$fieldnamerawoption;
						$fld['essb_option2']=$second_option;

						$cleanfieldlist[]=$fld;
					}
				}

			}
			else
			{
				$new_field_type='';

				//This is for range search only
				$isOk=true;
				$fRange=explode('_r_',$fieldname);

				if(isset($fRange[0]))
				{
					$found=false;
					$currentfld=array();

					$fFld=$fRange[0];
					foreach($this->ct->Table->fields as $fld)
					{
						if($fld['fieldname']==$fFld)
						{
							if( $fld['type']=='int' or $fld['type']=='float')
							{
								$found=true;
								$currentfld=$fld;
								$new_field_type='float';
								break;
							}

							if($fld['type']=='date')
							{
								$found=true;
								$currentfld=$fld;
								$new_field_type='date';
								break;
							}

						}
					}
					if(!$found)
						$isOk=false;

					if(isset($fRange[1]) and $fRange[1]!='')
					{
						$found=false;
						$currentfld=array();
						$fFld=$fRange[1];

						foreach($this->ct->Table->ields as $fld)
						{
							if($fld['fieldname']==$fFld and ($fld['type']=='int' or $fld['type']=='float' or $fld['type']=='date'))
							{
								//cannot change field type, because it has been set in first option. first-option_r_second-option
								$found=true;
								$currentfld=$fld;
								break;
							}
						}
						if(!$found)
							$isOk=false;

					}//if(isset($fRange[1]))
				}

				if($isOk)
				{

					$fieldtitle='Field Title';
					if(isset($fieldnamearr[1]))
						$fieldtitle=$fieldnamearr[1];

					if(count($fieldnamearr)==2)
					{
						$encodedtitle=base64_encode($fieldnamearr[1]);
						$fieldname.='_t_'.str_replace('=','_',$encodedtitle);
					}


					$f=array(
								'id'=>$currentfld['id'],
								'fieldname' => $fieldname,
								'fieldtitle'.$this->ct->Languages->Postfix=>$fieldtitle,
								'typeparams' => $new_field_type,
								'type'=>'range',
								'parentid'=>$currentfld['parentid'],
								'essb_style'=>$fieldnamerawstyle,
								'essb_option'=>$fieldnamerawoption,
								'essb_option2'=>$second_option
								);
					$cleanfieldlist[]=$f;

				}//if($isOk)

			}//if(strpos($fieldname,'_r_')===false)

		}//foreach($fieldlist as $fieldnamerow)
		$this->fieldlist=$cleanfieldlist;
	}
}//class
