<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\DataTypes\Tree;
use CustomTables\Inputbox;
use \JoomlaBasicMisc;
use \JHTMLCTTime;
use \tagProcessor_Value;

use \CT_FieldTypeTag_file;
use \CT_FieldTypeTag_image;
use \CT_FieldTypeTag_imagegallery;
use \CT_FieldTypeTag_filebox;
use \CT_FieldTypeTag_sqljoin;
use \CT_FieldTypeTag_records;
use \CT_FieldTypeTag_log;
use \CT_FieldTypeTag_ct;

use Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;

use \JHTML;

$types_path=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR;
require_once($types_path.'_type_ct.php');
require_once($types_path.'_type_file.php');
require_once($types_path.'_type_filebox.php');
require_once($types_path.'_type_gallery.php');
require_once($types_path.'_type_image.php');
require_once($types_path.'_type_log.php');
require_once($types_path.'_type_records.php');
require_once($types_path.'_type_sqljoin.php');

class Value
{
	var $ct;
	var $field;
	
	function __construct(&$ct)
	{
		$this->ct = $ct;
	}

	function renderValue(&$fieldrow,&$row,$option_list)
	{
		$this->field = new Field($this->ct,$fieldrow,$row);
		
		$rfn = $this->field->realfieldname;
		$rowValue = isset($row[$rfn]) ? $row[$rfn] : null;
			
		switch($this->field->type)
		{
			case 'int':
			case 'viewcount':
				$thousand_sep = $option_list[0] ?? ($this->field->params[0] ?? '');
				return number_format ( (int)$rowValue, 0, '',$thousand_sep);

			case 'float':
				$decimals = $option_list[0] != '' ? (int)$option_list[0] : ($this->field->params[0] != '' ? (int)$this->field->params[0] : 2);
				$decimals_sep = $option_list[1] ?? '.';
				$thousand_sep = $option_list[2] ?? '';
				return number_format ( (float)$rowValue, $decimals,$decimals_sep,$thousand_sep);
				
			case 'ordering':
				return $this->orderingProcess($rowValue, $row);

			case 'id':
			case 'md5':
			case 'phponadd':
			case 'phponchange':
			case 'phponview':
			case 'googlemapcoordinates':
			case 'alias':
			case 'radio':
			case 'server':
			case 'email':
			case 'url':
				return $rowValue;
				
			case 'multilangstring':
			case 'multilangtext':
				return $this->multilang($row, $option_list);
				
    		case 'string':
			case 'text':
				return Value::TextFunctions($rowValue,$option_list);
	
			case 'color':
				return $this->colorProcess($rowValue,$option_list);

			case 'file':
			
				return CT_FieldTypeTag_file::process($rowValue,$this->field,$option_list,$row['listing_id']);
			
			case 'image':
				$imagesrc='';
				$imagetag='';

				CT_FieldTypeTag_image::getImageSRClayoutview($option_list,$rowValue,$this->field->params,$imagesrc,$imagetag);

				return $imagetag;
				
			case 'signature':
				
				CT_FieldTypeTag_image::getImageSRClayoutview($option_list,$rowValue,$this->field->params,$imagesrc,$imagetag);
				
				$conf = Factory::getConfig();
				$sitename = $conf->get('config.sitename');

				$ImageFolder_ = \CustomTablesImageMethods::getImageFolder($this->field->params);
	
				$ImageFolderWeb=str_replace(DIRECTORY_SEPARATOR,'/',$ImageFolder_);
				$ImageFolder=str_replace('/',DIRECTORY_SEPARATOR,$ImageFolder_);

				$imagesrc='';
				$imagetag='';
				
				$format = $this->field->params[3] ?? 'png';
					
				if($format == 'jpeg')
					$format = 'jpg';
		
				$imagefileweb = URI::root(false).$ImageFolderWeb.'/'.$rowValue.'.'.$format;
				$imagefile=$ImageFolder.DIRECTORY_SEPARATOR.$rowValue.'.'.$format;
				
				if(file_exists(JPATH_SITE.DIRECTORY_SEPARATOR.$imagefile))
				{
					$width = $this->field->params[0] ?? 300;
					$height = $this->field->params[1] ?? 150;
					
					$imagetag='<img src="'.$imagefileweb.'" width="'.$width.'" height="'.$height.'" alt="'.$sitename.'" title="'.$sitename.'" />';
					//$imagesrc=$imagefileweb;
					return $imagetag;
				}
				return null;
				
			
			case 'article':
			case 'multilangarticle':
				return $this->articleProcess($rowValue, $option_list);
				
			case 'imagegallery':

				if($option_list[0]=='_count')
					return count($getGalleryRows);

				$imagesrclist='';
				$imagetaglist='';

				CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows,$option_list[0],
					$row['listing_id'],$this->field->fieldname,$this->field->params,$imagesrclist,$imagetaglist,$this->ct->Table->tableid);
						
				return $imagetaglist;

			case 'filebox':

				$FileBoxRows=CT_FieldTypeTag_filebox::getFileBoxRows($this->ct->Table->tablename,$this->field->fieldname,$row['listing_id']);

				if($option_list[0]=='_count')
					return count($FileBoxRows);

				return CT_FieldTypeTag_filebox::process($FileBoxRows, $this->field, $row['listing_id'],$option_list);
    		
			case 'customtables':
				return $this->listProcess($rowValue, $option_list);

			case 'records':
				return CT_FieldTypeTag_records::resolveRecordType($this->ct,$rowValue, $this->field->params, $option_list);

			case 'sqljoin':
				return CT_FieldTypeTag_sqljoin::resolveSQLJoinType($this->ct,$rowValue, $this->field->params, $option_list);

			case 'user':
			case 'userid':
				return JHTML::_('ESUserView.render',$rowValue,$option_list[0]);

			case 'usergroup':
				return tagProcessor_Value::showUserGroup($rowValue);

			case 'usergroups':
				return tagProcessor_Value::showUserGroups($rowValue);

			case 'filelink':
				$processor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php';
				require_once($processor_file);
					
				return CT_FieldTypeTag_file::process($rowValue,','.$this->field->params,
					$option_list, $row['listing_id'], $this->field->id,$this->ct->Table->tableid); // "," is to be compatible with file field type params. Becuse first parameter is max file size there

			case 'log':
				return CT_FieldTypeTag_log::getLogVersionLinks($this->ct,$rowValue,$row);

			case 'checkbox':
				if((bool)(int)$rowValue)
					return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YES');
				else
					return JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NO');

			case 'lastviewtime':
				return $this->dataProcess($rowValue, $option_list);

			case 'date':
				return $this->dataProcess($rowValue, $option_list);
                    
			case 'time':
				require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'cttime.php');
				$seconds=JHTMLCTTime::ticks2Seconds($rowValue,$this->field->params);
				return JHTMLCTTime::seconds2FormatedTime($seconds,$option_list[0]);

			case 'creationtime':
				return $this->timeProcess($rowValue, $option_list);

			case 'changetime':
				return $this->timeProcess($rowValue, $option_list);
						
			case 'id':
				return $rowValue;
		}
		return null;
	}
	
	protected function multilang(array &$row, array &$option_list)
	{
		$specific_lang = $option_list[4] ?? '';
		
		$fieldtype = $this->field->type;
		
		if($fieldtype=='multilangstring')
			$fieldtype='string';
		elseif($fieldtype=='multilangtext')
			$fieldtype='text';
                
		$postfix='';
		if($specific_lang!='')
		{
			$i=0;
			foreach($this->ct->Languages->LanguageList as $l)
			{
				if($l->sef==$specific_lang)
                {
					if($i==0)
						$postfix='';//first language in the list
                    else
						$postfix='_'.$specific_lang;
                            
					break;
                }
                $i++;
            }
        }
        else
            $postfix=$this->ct->Languages->Postfix; //front-end default language
                
   		$fieldname=$this->field->realfieldname.$postfix;
		if(isset($row[$fieldname]))
			$rowValue = $row[$fieldname];
		else
			$rowValue = null;
		
		return Value::TextFunctions($rowValue,$option_list);
	}
	
	protected function colorProcess($value, array &$option_list)
	{
		if($value=='')
			$value='000000';

        if($option_list[0]=="rgba")
		{
			$colors=array();
            if(strlen($value)>=6)
			{
				$colors[]=hexdec(substr($value, 0,2));
				$colors[]=hexdec(substr($value, 2,2));
				$colors[]=hexdec(substr($value, 4,2));
			}

			if(strlen($value)==8)
			{
				$a=hexdec(substr($value, 6,2));
				$colors[]=round($a/255,2);
			}                        

			if(strlen($value)==8)
				return 'rgba('.implode(',',$colors).')';
			else
				return 'rgb('.implode(',',$colors).')';
		}
		else
			return "#".$value;
	}
	
	protected function articleProcess($rowValue, array &$option_list)
	{
		if(isset($option_list[0]) and $option_list[0]!='')
			$article_field=$option_list[0];
		else
			$article_field='title';
                        
		$article=$this->getArticle((int)$rowValue,$article_field);

		if(isset($option_list[1]))
        {
			$opts=str_replace(':',',',$option_list[1]);
			return Value::TextFunctions($article,explode(',',$opts));
        }
		else 
			return $article;
	}
	
	protected function getArticle($articleid,$field)
	{
    	// get database handle
		$db = Factory::getDBO();
		$query='SELECT '.$field.' FROM #__content WHERE id='.(int)$articleid.' LIMIT 1';
		$db->setQuery($query);

		$rows=$db->loadAssocList();

		if(count($rows)!=1)
			return ""; //return nothing if article not found

		$row=$rows[0];
		return $row[$field];
	}
	
	protected function listProcess($rowValue, array &$option_list)
	{
		if(count($option_list)>1 and $option_list[0]!="")
		{
			if($option_list[0]=='group')
			{
				$rootparent=$this->field->params[0];

				$orientation=0;// horizontal
				if(isset($option_list[1]) and $option_list[1]=='vertical')
					$orientation=1;// vertical

				$grouparray=CT_FieldTypeTag_ct::groupCustomTablesParents($this->ct,$rowValue,$rootparent);

				//Build structure
				$vlu = '<table border="0"><tbody>';

				if($orientation==0)
					$vlu.='<tr>';

				foreach($grouparray as $fgroup)
				{
					if($orientation==1)
						$vlu.='<tr>';

					$vlu.='<td valign="top" align="left"><h3>'.$fgroup[0].'</h3><ul>';

					for($i=1; $i<count($fgroup);$i++)
					    $vlu.='<li>'.$fgroup[$i].'</li>';

					$vlu.='<ul></td><td width="20"></td>';

					if($orientation==1)
						$vlu.='</tr>';
				}

				if($orientation==0)
					$vlu.='</tr>';

				$vlu.='</tbody></table>';

				return $vlu;
			}
			elseif($option_list[0]=='list')
			{
				if($rowValue!='')
				{
					$vlus=explode(',',$rowValue);
					$vlus = array_filter($vlus);

					sort ($vlus);

					$temp_index=0;
					$vlu=Tree::BuildULHtmlList($vlus,$temp_index,$this->ct->Languages->Postfix);

					return $vlu;
				}
			}
		}
		else
		{
			if($rowValue!='')
				return implode(',',Tree::getMultyValueTitles($rowValue,$this->ct->Languages->Postfix,1, ' - ',$this->field->params));
		}
		return '';
	}
	
	protected function dataProcess($rowValue, array &$option_list)
	{	
		if($rowValue=='' or $rowValue=='0000-00-00' or $rowValue=='0000-00-00 00:00:00')
			return '';

		$phpdate =strtotime( $rowValue);

		if($option_list[0]!='')
		{
			if($option_list[0]=='timestamp')
				return  $phpdate;

			return date($option_list[0], $phpdate);
		}
		else
			return JHTML::date($phpdate );
	}
	
	protected function orderingProcess($value, &$row)
	{
		$orderby_pair = explode(' ',$this->ct->Ordering->orderby);
			
		if($orderby_pair[0] == $this->field->realfieldname)
			$iconClass = '';
		else
			$iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::_('tooltipText', 'COM_CUSTOMTABLES_FIELD_ORDERING_DISABLED');

		$result ='
			<span class="sortable-handler'.$iconClass.'">
				<i class="icon-menu"></i>
			</span>';
		
		if($orderby_pair[0] == $this->field->realfieldname)
		{
			$result .='<input type="text" style="display:none" name="order[]" size="5" value="'.$value.'" class="width-20 text-area-order " />';
			$result .='<input type="checkbox" style="display:none" name="cid[]" value="'.$row[$this->ct->Table->realidfieldname].'" class="width-20 text-area-order " />';
			
			$this->ct->LayoutVariables['ordering_field_type_found'] = true;
		}
			
		return $result;
	}
	
	protected function timeProcess($value, array &$option_list)
	{
		$phpdate = strtotime($value);
		if($option_list[0]!='')
		{
			if($option_list[0]=='timestamp')
				return  $phpdate;

			return date($option_list[0],$phpdate );
		}
		else
		{
			if($value == '0000-00-00 00:00:00')
				return '';
			
			return JHTML::date($phpdate );
		}
	}

	public static function TextFunctions($content,$parameters)
	{
        if(count($parameters)==0)
            return $content;
        
    				switch($parameters[0])
					{
						case "chars" :

							if(isset($parameters[1]))
								$count=(int)$parameters[1];
							else
								$count=-1;

							if(isset($parameters[2]) and $parameters[2]=='true')
								$cleanbraces=true;
							else
								$cleanbraces=false;

							if(isset($parameters[3]) and $parameters[3]=='true')
								$cleanquotes=true;
							else
								$cleanquotes=false;

							return JoomlaBasicMisc::chars_trimtext($content, $count, $cleanbraces, $cleanquotes);
							break;

						case "words" :

							if(isset($parameters[1]))
								$count=(int)$parameters[1];
							else
								$count=-1;

							if(isset($parameters[2]) and $parameters[2]=='true')
								$cleanbraces=true;
							else
								$cleanbraces=false;

							if(isset($parameters[3]) and $parameters[3]=='true')
								$cleanquotes=true;
							else
								$cleanquotes=false;

							return JoomlaBasicMisc::words_trimtext($content, $count, $cleanbraces, $cleanquotes);
							break;

						case "firstimage" :

							return JoomlaBasicMisc::getFirstImage($content);

							break;


						default:

							return $content;


						break;
					}

		return $content;

	}

}