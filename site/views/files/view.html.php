 <?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');
//jimport('joomla.html.pane');

jimport( 'joomla.application.component.view'); //Important to get menu parameters
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
		$test_key=CT_FieldTypeTag_file::makeTheKey($filepath,$this->Model->security,$this->Model->_id,$this->Model->esfieldid,$this->Model->ct->Table->tableid);

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

		$parts = explode('/',$file);
		$filename = end($parts);
		$filename_parts = explode('.',$filename);
		$fileextension=end($filename_parts);

		$content=$this->doCustomPHP($content,$row);

		if (ob_get_contents()) ob_end_clean();

		$mt=mime_content_type($file);
   
		@header('Content-Type: '.$mt);
		@header("Pragma: public");
		@header("Expires: 0");
		@header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		@header("Cache-Control: public");
		@header("Content-Description: File Transfer");
		@header("Content-Transfer-Encoding: binary");
		@header("Content-Disposition: attachment; filename=\"".$filename."\"");
   
		echo $content;

		die ;//clean exit
	}

	function doCustomPHP($content,&$row)
	{
		$servertagprocessor_file=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'servertags.php';

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
					return call_user_func($function_name,$content,$row,$this->Model->ct->Table->tableid,$this->Model->esfieldid);
			}
		}
		return $content;
	}

	function getFilePath()
	{
		if(!isset($this->Model->fieldrow))
			return '';
	 
		if($this->Model->fieldrow['type']=='filelink')
			$TypeParams=','.$this->Model->fieldrow['typeparams']; //file link field type parameters have folder path as second parameter
		else
			$TypeParams=$this->Model->fieldrow['typeparams'];
	
		if(!isset($this->row[$this->Model->fieldrow['realfieldname']]))
			return '';

		$rowValue=$this->row[$this->Model->fieldrow['realfieldname']];

		return CT_FieldTypeTag_file::getFileFolder($TypeParams).'/'.$rowValue;
	}
}
