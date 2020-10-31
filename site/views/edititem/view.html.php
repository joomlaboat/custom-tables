<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
jimport('joomla.html.pane');

jimport( 'joomla.application.component.view'); //Important to get menu parameters
class CustomTablesViewEditItem extends JViewLegacy {
    var $catid=0;
	function display($tpl = null)
	{

	    $document = JFactory::getDocument();
		$document->addCustomTag('<link src="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" >');

		$app		= JFactory::getApplication();
		$params=$app->getParams();


		$this->assignRef('params',$params);

	    //========= User info
		$user = JFactory::getUser();
		$userid = (int)$user->get('id');
		$this->assignRef('userid',$userid);

		//------ end user info

		$Model = $this->getModel();
        $Model->load($params);

        if(!$Model->CheckAuthorization(1))
    	{
    		//not authorized
            JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
    		return false;
        }



        if(!isset($Model->esfields) or !is_array($Model->esfields))
            return false;

		$this->assignRef('Model',$Model);

		$langpostfix=$Model->langpostfix;
		$this->assignRef('langpostfix',$langpostfix);


		


		//	Fields
		$this->assignRef('esfields',$Model->esfields);
/*

		// Rocord
		$db = JFactory::getDBO();
		$row=array(); //Default is empty

		if($Model->id!=0)
		{
		    // Load record

		    $query = 'SELECT *, id AS listing_id,published AS listing_published FROM #__customtables_table_'.$Model->establename.' WHERE id='.$Model->id.' LIMIT 1';
		    $db->setQuery( $query );
		    $rows = $db->loadAssocList();
		    if(count($rows)==0)
		    {
    			//Record not found
                $Model->id=0;
		    }
		    else
		    {
			$row=$rows[0]; //Record found

			//get specific Version if set
			$version= JFactory::getApplication()->input->get('version',0,'INT');
			if($version!=0)
			{
			    //get log field
			    $log_field=$this->getTypeFieldName('log');;
			    if($log_field!='')
			    {
			    	$new_row= $this->getVersionData($row,$log_field,$version);
				if(count($new_row)>0)
				{
				    $row=$this->makeEmptyRecord($Model->id,$new_row['published']);

				    //Copy values
				    foreach($this->Model->esfields as $ESField)
					$row['es_'.$ESField['fieldname']]=$new_row['es_'.$ESField['fieldname']];


				}
			    }
			}
		    }
		}
*/
		//$this->assignRef('row',$row);
		$this->assignRef('row',$Model->row);

		$WebsiteRoot=JURI::root(true);
		if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
			$WebsiteRoot.='/';

		$this->formLink=$WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'.($this->Model->Itemid!=0 ? '&amp;Itemid='.$this->Model->Itemid : '');//.'&amp;lang='.$lang;
		$this->formName='eseditForm';
		$this->formClass='form-validate form-horizontal well';
		$this->formAttribute=' onsubmit="return checkRequiredFields();"';
		


		parent::display($tpl);
	}

	


}//class
