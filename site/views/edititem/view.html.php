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


		$db = JFactory::getDBO();


		//	Fields
		$this->assignRef('esfields',$Model->esfields);


		// Rocord

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

		$this->assignRef('row',$row);

		// Compute selected asset permissions.
		//$asset	= 'com_content.article.'.$Model->id;
		//JFactory::getApplication()->input->set('asset',$asset);


		parent::display($tpl);
	}

	function makeEmptyRecord($id,$published)
	{
	    $row=array();
	    $row['id']=$id;
	    $row['published']=$published;


	    foreach($this->Model->esfields as $ESField)
		$row['es_'.$ESField['fieldname']]='';


	    return $row;
	}



	function getTypeFieldName($type)
	{
		foreach($this->Model->esfields as $ESField)
		{
				if($ESField['type']==$type)
					return 'es_'.$ESField['fieldname'];

		}

		return '';
	}

	function getVersionData(&$row,$log_field,$version)
	{
		$creation_time_field=$this->getTypeFieldName('changetime');

		$versions=explode(';',$row[$log_field]);
		if($version<=count($versions))
		{
					$data_editor=explode(',',$versions[$version-2]);
					$data_content=explode(',',$versions[$version-1]);

					if($data_content[3]!='')
					{
                        //record versions stored in database table text field as base64 encoded json object
						$obj=json_decode(base64_decode($data_content[3]),true);
						$new_row=$obj[0];
						$new_row['published']=$row['published'];
						$new_row['id']=$row['id'];
						$new_row['listing_id']=$row['id'];
						$new_row[$log_field]=$row[$log_field];


						if($creation_time_field)
						{
							$timestamp = date('Y-m-d H:i:s', (int)$data_editor[0]);
							$new_row[$creation_time_field]=$timestamp ;
						}


						return $new_row;
					}
		}

		return array();
	}


}//class
