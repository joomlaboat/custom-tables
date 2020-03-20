<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
		function renderFields(&$row,&$Model,$langpostfix,$parentid,&$esinputbox,&$calendars,$style='',&$fieldstosave,$replaceitecode,&$items_to_replace)
		{
			$fieldstosave=array();
				$calendars=array();
				$result='';

				$firstItem=true;

					//custom layout
					if(!isset($Model->esfields) or !is_array($Model->esfields))
					{
						return false;
					}

					for($f=0;$f<count($Model->esfields);$f++ )
					{
						$esfield=$Model->esfields[$f];

						$options=array();

						$entries=JoomlaBasicMisc::getListToReplace($esfield['fieldname'],$options,$Model->pagelayout,'[]');

						if(count($entries)>0)
						{
							$i=0;
							for($i;$i<count($entries);$i++)
							{

								$pair=JoomlaBasicMisc::csv_explode(',',$options[$i],'"',false);
								// $pair[0] - CSS Class
								// $pair[1] - Optional Parameter
								$class=$pair[0];
								$attribute='';
								
								if(isset($pair[1]))
									$attribute=$pair[1];
								

								if(strpos($class,':')!==false)//its a style, chanage it to attribute
								{
									if($attribute!='')
										$attribute.=' ';

									$attribute.='style="'.$class.'"';
									$class='';
								}

								$result=renderField($row,$Model,$langpostfix,-1,$esinputbox,$calendars,$esfield,$class,$attribute,$fieldstosave,$pair);

								$new_replaceitecode=$replaceitecode.str_pad(count($items_to_replace), 9, '0', STR_PAD_LEFT).str_pad($i, 4, '0', STR_PAD_LEFT);

								$items_to_replace[]=array($new_replaceitecode,$result);
								$Model->pagelayout=str_replace($entries[$i],$new_replaceitecode,$Model->pagelayout);
							}

							$fieldstosave[]=$esfield['fieldname'];

						}




						//if(!(strpos($Model->pagelayout,'['.$esfield[fieldname].']')===false))
						//{




						//}

					}//for($f=0;$f<count($Model->esfields);$f++ )

				//}//if($Model->pagelayout=='')


		}




		function renderField(&$row,&$Model,$langpostfix,$parentid,&$esinputbox,&$calendars,&$esfield, $class='',$attributes='',&$fieldstosave,$option_list)
		{


			$result='';

			$fieldBox='';
				if($esfield['parentid']==$parentid or $parentid==-1)
				{
					$realFieldName='es_'.$esfield['fieldname'];



						if($esfield['type']=='date')
							$calendars[]='es_'.$esfield['fieldname'];

						$fieldBox='';

						if($esfield['type']!='dummy')
								$fieldBox=$esinputbox->renderFieldBox($Model,'com',$esfield,$row, $class,$attributes,$option_list);

						$result.=$fieldBox;

				}//if($esfield[parentid]==$parentid)

			return $result;

		}

		function renderChildBox(&$Model,&$firstItem,&$esfield,$langpostfix,&$row,&$esinputbox,&$realFieldName,$style='',&$fieldstosave)
		{

				$result='';
				if($Model->showlines and !$firstItem and $Model->pagelayout=='')
				$result.='<tr><td colspan="2"><hr/></td></tr>';



						$firstItem=false;

						if($Model->pagelayout=='')
						{
								$result.='<tr><td align="left" style="'.$style.'" >';

								if(!$esfield['hidden'])
										$result.='<label style="text-align:left;" for="es_'.$esfield['fieldname'].'"><b>'.$esfield['fieldtitle'.$langpostfix].'</b>:</label>';

								$result.='</td><td>';
						}

						$calendars_=array();


						$fieldBox='';

						if($fieldBox!='')
						{

							$calendars=null;
							$fieldBoxEmpty=renderFields($blackRow,$Model,$langpostfix,$esfield['id'],$esinputbox,$calendars,'',$fieldstosave);


							//child
								$result.='
						<input type="hidden"'
							.' id="es_'.$esfield['fieldname'].'_data" '
							.' name="es_'.$esfield['fieldname'].'_data"'
							.' value="'.base64_encode($fieldBoxEmpty).'" />

						<!--<script language="javascript"-->

						<input type="checkbox"'
										.' id="comes_'.$esfield['fieldname'].'" '
										.' name="comes_'.$esfield['fieldname'].'" '
										.' onClick=\'
										var o=document.getElementById("comdiv_'.$esfield['fieldname'].'");
										if(this.checked)
										{
											o.style.display="block";
											var b=document.getElementById("es_'.$esfield['fieldname'].'_data").value;
											o.innerHTML=Base64.decode(b);

							';

							//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
							foreach($calendars_ as $calendar)
							{
								$result.='
											Calendar.setup({
        inputField     :    "com'.$calendar.'",     // id of the input field
        ifFormat       :    "%Y-%m-%d",      // format of the input field
        button         :    "com'.$calendar.'_img",  // trigger for the calendar (button ID)
        align          :    "Tl",           // alignment (defaults to "Bl")
        singleClick    :    true
    });
							';
							}

								$result.='			}
										else
										{
											o.innerHTML="";
											o.style.display="none";
										}
										\''
										.($row[$realFieldName] ? ' checked="checked" ' : '')
										.'/>';


						$result.='
						<div id="comdiv_'.$esfield['fieldname'].'" style="display:'.($row[$realFieldName] ? 'block;' : 'none').';">
						<!-- child -->
						<fieldset class="adminform" style="padding: 10px;">
						<table class="admintable" cellspacing="1">
								'.$fieldBox.'
						</table>
						</fieldset>
						</div>
						';

			}//if($children!='')


						else
						{
								$fieldBox=$esinputbox->renderFieldBox($Model,'com',$esfield,$row,$style,'');
								if($fieldBox!='')
								{
										$result.=$fieldBox;
								}

						}//if($children!='')

						if($Model->pagelayout=='')
								$result.='</td></tr>';


			return $result;

		}

		function renderChildGroup(&$Model,&$firstItem,&$esfield,$langpostfix,&$row,&$esinputbox,&$realFieldName,$style='',&$fieldstosave)
		{
			$result='';
			if($Model->showlines and !$firstItem and $Model->pagelayout=='')
				$result.='<tr><td colspan="2"><hr/></td></tr>';

							$firstItem=false;

						$result.='
					<tr>
					<td colspan="2" align="left"  >

					<input type="hidden" id="es_'.$esfield['fieldname'].'" name="es_'.$esfield['fieldname'].'" value="1" />';


					$result.='<h3 style="text-align:left;" for="es_'.$esfield['fieldname'].'"><b>'.$esfield['fieldtitle'.$langpostfix].'</b>:</h3><hr>';



						$calendars_=array();
						$fieldBox=renderFields($row,$Model,$langpostfix,$esfield['id'],$esinputbox,$calendars_,$style,$fieldstosave);


						if($fieldBox!='')
						{
							$result.=$fieldBox;

						}//if($children!='')
						$result.='
					</td>
					</tr>';

			return $result;

		}
