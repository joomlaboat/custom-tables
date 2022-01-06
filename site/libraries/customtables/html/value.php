<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
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
	
	function __construct(&$ct)
	{
		$this->ct = $ct;
	}

	function renderValue(&$field,&$row,$option_list)
	{
		$rfn = $field['realfieldname'];
		$rowValue = isset($row[$rfn]) ? $row[$rfn] : null;
		
		$TypeParams = $field['typeparams'];
		$type_params = JoomlaBasicMisc::csv_explode(',',$TypeParams,'"',false);
		
		switch($field['type'])
		{
			case 'int':
			case 'viewcount':
				$thousand_sep = $option_list[0] ?? ($type_params[0] ?? '');
				return number_format ( (int)$rowValue, 0, '',$thousand_sep);

			case 'float':

				$decimals = $option_list[0] != '' ? (int)$option_list[0] : ($type_params[0] != '' ? (int)$type_params[0] : 2);
				$decimals_sep = $option_list[1] ?? '.';
				$thousand_sep = $option_list[2] ?? '';
				return number_format ( (float)$rowValue, $decimals,$decimals_sep,$thousand_sep);

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
				return $this->multilang($field, $row, $option_list);
				
    		case 'string':
			case 'text':
				return Value::TextFunctions($rowValue,$option_list);
	
			case 'color':
				return $this->colorProcess($rowValue,$option_list);

			case 'file':
				return CT_FieldTypeTag_file::process($rowValue,$TypeParams,$option_list,$row['listing_id'],$field['id'],$this->ct->Table->tableid);
			
			case 'image':
				$imagesrc='';
				$imagetag='';

				CT_FieldTypeTag_image::getImageSRClayoutview($option_list,$rowValue,$TypeParams,$imagesrc,$imagetag);

				return $imagetag;
			
			case 'article':
			case 'multilangarticle':
				return $this->articleProcess($rowValue, $option_list);
				
			case 'imagegallery':

				if($option_list[0]=='_count')
					return count($getGalleryRows);

				$imagesrclist='';
				$imagetaglist='';

				CT_FieldTypeTag_imagegallery::getImageGallerySRC($getGalleryRows,$option_list[0],
					$row['listing_id'],$field['fieldname'],$TypeParams,$imagesrclist,$imagetaglist,$this->ct->Table->tableid);
						
				return $imagetaglist;

			case 'filebox':

				if($option_list[0]=='_count')
					return count($getFileBoxRows);

				return CT_FieldTypeTag_filebox::process($this->ct->Table->tableid,$getFileBoxRows, $row['listing_id'], $field['fieldname'],
					$TypeParams,$option_list,$field['id'],'');
    		
			case 'customtables':
				return $this->listProcess($rowValue, $type_params, $option_list);

			case 'records':
				return CT_FieldTypeTag_records::resolveRecordType($this->ct,$rowValue, $TypeParams, $option_list);

			case 'sqljoin':
				return CT_FieldTypeTag_sqljoin::resolveSQLJoinType($this->ct,$rowValue, $TypeParams, $option_list);

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
					
				return CT_FieldTypeTag_file::process($rowValue,','.$TypeParams,
					$option_list, $row['listing_id'], $field['id'],$this->ct->Table->tableid); // "," is to be compatible with file field type params. Becuse first parameter is max file size there

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
				$seconds=JHTMLCTTime::ticks2Seconds($rowValue,$type_params);
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
	
	protected function multilang(array $field, array $row, array &$option_list)
	{
		$specific_lang = $option_list[4] ?? '';
		
		$fieldtype = $field['type'];
		
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
                
   		$fieldname=$field['realfieldname'].$postfix;
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
                        
		$article=tagProcessor_Value::getArticle((int)$rowValue,$article_field);

		if(isset($option_list[1]))
        {
			$opts=str_replace(':',',',$option_list[1]);
			return Value::TextFunctions($article,explode(',',$opts));
        }
		else 
			return $article;
	}
	
	protected function listProcess($rowValue, array $type_params, array &$option_list)
	{
		if(count($option_list)>1 and $option_list[0]!="")
		{
			if($option_list[0]=='group')
			{
				$rootparent=$type_params[0];

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
				return implode(',',Tree::getMultyValueTitles($rowValue,$this->ct->Languages->Postfix,1, ' - ',$type_params));
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