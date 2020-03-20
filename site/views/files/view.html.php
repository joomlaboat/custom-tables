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
//jimport('joomla.html.pane');

jimport( 'joomla.application.component.view'); //Important to get menu parameters
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'fieldtypes'.DIRECTORY_SEPARATOR.'_type_file.php');

class CustomTablesViewFiles extends JViewLegacy
{

 var $Model;
 var $row;

	function display($tpl = null)
	{

		$this->Model = $this->getModel();

  $this->row = $this->get('Data');



  $filepath=$this->getFilePath();
  

  if($filepath=='')
  {
   JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
  }

  $key=$this->Model->key;

  $test_key=CT_FieldTypeTag_file::makeTheKey($filepath,$this->Model->security,$this->Model->_id,$this->Model->esfieldid,$this->Model->estableid);

 if($key==$test_key)
    $this->render_file_output($this->row,$filepath);
 else
	JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_DOWNLOAD_LINK_IS_EXPIRED'), 'error');
 
	}


 function render_file_output($row,$filepath)
 {
	$jinput=JFactory::getApplication()->input;
	$savefile=$jinput->getInt('savefile',0);
	
 	$jinput = JFactory::getApplication()->input;
	
	if(strlen($filepath)>8 and substr($filepath,0,8)=='/images/')
		$file=JPATH_SITE.str_replace('/',DIRECTORY_SEPARATOR,$filepath);
	else
		$file=str_replace('/',DIRECTORY_SEPARATOR,$filepath);

   if(!file_exists($file))
   {
    JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_FOUND'), 'error');
    return;
   }

   $content=file_get_contents($file);

   $parts=explode('/',$file);
   $filename=end($parts);
   $fileextension=end($filename);

   $content=$this->doCustomPHP($content,$row);

   if (ob_get_contents()) ob_end_clean();

   $mt=mime_content_type($file);
   
    @header('Content-Type: '.$mt);
	@header("Pragma: public");
	@header("Expires: 0");
	@header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	@header("Cache-Control: public");
	@header("Content-Description: File Transfer");
	/*header("Content-type: application/octet-stream");*/
	
	@header("Content-Transfer-Encoding: binary");
   
	if($savefile)
		@header("Content-Disposition: attachment; filename=\"".$filename."\"");
   
   //header("Content-Length: ".filesize($file));
   //ob_end_flush();
   echo $content;

   die;
 }


 function doCustomPHP($content,&$row)
	{

  $admin_libpath=JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
  require_once($admin_libpath.'misc.php');

  $servertagprocessor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'servertags.php';

		if(!file_exists($servertagprocessor_file))
			return $content;

  $TypeParams=$this->Model->fieldrow['typeparams'];
  $param_parts=JoomlaBasicMisc::csv_explode(',', $TypeParams, '"', false);
  if(!isset($param_parts[4]))
   return $content;

  $customphpfile=$param_parts[4];


  if($customphpfile!='')
		{

   $parts=explode('/',$customphpfile); //just a security check
			if(count($parts)>1)
				return $content;

			$file=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'customphp'.DIRECTORY_SEPARATOR.$customphpfile;
			if(file_exists($file))
			{
				require_once($file);
				$function_name='CTProcessFile_'.str_replace('.php','',$customphpfile);

				if(function_exists ($function_name))
				{
      return call_user_func($function_name,$content,$row,$this->Model->estableid,$this->Model->esfieldid);

				}

			}
		}
		return $content;

	}





 function getFilePath()
 {
	if($this->Model->fieldrow['type']=='filelink')
		$TypeParams=','.$this->Model->fieldrow['typeparams']; //file link field type parameters have folder path as second parameter
	else
		$TypeParams=$this->Model->fieldrow['typeparams'];
	
  if(!isset($this->row['es_'.$this->Model->fieldrow['fieldname']]))
   return '';

  $rowValue=$this->row['es_'.$this->Model->fieldrow['fieldname']];

  return CT_FieldTypeTag_file::getFileFolder($TypeParams).'/'.$rowValue;

 }

 /*
 function getRealFilePath()
 {
  $TypeParams=$this->Model->fieldrow['typeparams'];


  $rowValue=$this->row['es_'.$this->Model->fieldrow['fieldname']];

  $pair=explode(',',$TypeParams);
		//$pair[1] - the folder
		//$options[0] - how to process

		if(isset($pair[1]))
            $filepath=$pair[1].'/'.$rowValue;
		else
            $filepath='images/esfiles/'.$rowValue;

   if($filepath[0]!='/');
    $filepath='/'.$filepath;

  return $filepath;
 }
 */

}



?>
