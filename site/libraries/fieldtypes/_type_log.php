<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CT_FieldTypeTag_log
{
	public static function getLogVersionLinks(&$ct,$rowValue,&$row)
	{
		$current_json_data_size=CT_FieldTypeTag_log::getVersionDataSize($ct,$row);

		$url=JoomlaBasicMisc::curPageURL();
		$new_url=JoomlaBasicMisc::deleteURLQueryOption($url, 'version');

		$result='';
		$versions=explode(';',$rowValue);

		$jinput = JFactory::getApplication()->input;
		$version = JFactory::getApplication()->input->get('version',0,'INT');

		$version_date='';
		$version_author='';
		$version_size=0;

		//get creation date
		foreach($ct->Table->fields as $ESField)
		{
			if($ESField['type']=='creationtime')
			{
				$version_date=strtotime($row[$ct->Env->field_prefix.$ESField['fieldname']]);
				break;
			}
		}

		//get original author
		foreach($ct->Table->fields as $ESField)
		{
			if($ESField['type']=='userid')
			{
				$version_author=JHTML::_('ESUserView.render',$row[$ct->Env->field_prefix.$ESField['fieldname']],'');
				break;
			}
		}

		if(count($versions)>1)
		{
			$result.='<ol>';
			$i=0;
			foreach($versions as $v)
			{
				$i++;
				$data=explode(',',$v);


					$result.='<li>';

					$str='';
					if($version_date!='')
					{
						$str=date( 'Y-m-d H:m:s', $version_date).' - '.$version_author;


						if(isset($data[3]))
						{
							$decoded_data_rows=json_decode(base64_decode($data[3]),true);

							if($decoded_data_rows == null)
							{
								//Log data is too long (longer than 65,535 bytes)
								//JSON record is corrupted
								//Update to 5.4.5
								$current_version_size = 0;
							}
							else
							{
								$decoded_data_row = $decoded_data_rows[0];
								$current_version_size=CT_FieldTypeTag_log::getVersionDataSize($ct,$decoded_data_row);
							}
						}
						else
							$current_version_size=$current_json_data_size;

						if($current_version_size>$version_size)
							$str.=' <span style="color:#00aa00">+'.($current_version_size-$version_size).'</span>';
						elseif($current_version_size<$version_size)
							$str.=' <span style="color:#aa0000">-'.($version_size-$current_version_size).'</span>';
					}
					else
						$str=$version_author;

					if($str=='')
						$str='Original Version';

					if($i==count($versions))
					{
						if($version==0)
							$result.='<b>'.$str.'</b>';
						else
							$result.='<a href="'.$new_url.'" target="_blank">'.$str.'</a>';
					}
					else
					{
						if($data[3]!='')
						{
							if(strpos($new_url,'?')===false)
								$link=$new_url.'?version='.$i;
							else
								$link=$new_url.'&version='.$i;

							if($version==$i)
								$result.='<b>'.$str.'</b>';
							else
								$result.='<a href="'.$link.'" target="_blank">'.$str.'</a>';
						}
						else
							$result.=$str;


					}

					$result.='</li>';

					$version_date=$data[0];

					if(isset($data[1])) //last comma is empty so no element number 1
					{
						$version_author=JHTML::_('ESUserView.render',$data[1],'');

						if(isset($data[3])) //last comma is empty so no element number 1
						{

							$decoded_data_rows=json_decode(base64_decode($data[3]),true);
							if($decoded_data_rows == null)
							{
								//Log data is too long (longer than 65,535 bytes)
								//JSON record is corrupted
								//Update to 5.4.5
								$version_size = 0;
							}
							else
							{
								$decoded_data_row=$decoded_data_rows[0];
								$version_size=CT_FieldTypeTag_log::getVersionDataSize($ct,$decoded_data_row);
							}
						}
						else
							$version_size=0;

					}
			}
			$result.='</ol>';
		}

		return $result;

	}


	public static function getVersionDataSize(&$ct,$decoded_data_row)
	{
		$version_size=0;

		foreach($ct->Table->fields as $ESField)
		{
			if($ESField['type']!='log' and $ESField['type']!='dummy')
			{
				$field_name=$ct->Env->field_prefix.$ESField['fieldname'];
				if(isset($decoded_data_row[$field_name]))
					$version_size+=strlen($decoded_data_row[$field_name]);
			}
		}

		return $version_size;
	}
}
