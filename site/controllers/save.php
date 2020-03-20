<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @version 1.6.1
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/



// no direct access
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
$jinput = JFactory::getApplication()->input;

$task=$jinput->getCmd( 'task');



switch ($task)
{

	case 'save' :
		if(CustomTablesSave($task,$this))
			parent::display();

		break;
	
	case 'saveandcontinue' :
		if(CustomTablesSave($task,$this))
			parent::display();

		break;

	case 'saveascopy' :
		if(CustomTablesSave($task,$this))
			parent::display();

		break;

	case 'cancel':

		$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT_CANCELED');
		$link 	= $returnto=base64_decode (JFactory::getApplication()->input->get('returnto','','BASE64'));

		$this->setRedirect($link, $msg);

	break;

	case 'delete':
		if(CustomTablesDelete($task,$this))
			parent::display();

		break;

	default:
		parent::display();
}

function CustomTablesDelete($task,&$this_)
{
		$model = $this_->getModel('edititem');
		if (!$model->CheckAuthorization(3)) //3 is to delete
		{

			// not authorized
			if ($clean == 1)
			{
				JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');
				return;
			}
			else
			{
				$link = $WebsiteRoot . 'index.php?option=com_users&view=login&return=' . $encodedreturnto;
				$this_->setRedirect($link,JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
			}

			die ;// not authorized
		}
		else
		{
			require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
			$jinput = JFactory::getApplication()->input;
			$encodedreturnto = base64_encode(JoomlaBasicMisc::curPageURL());
			$returnto = $jinput->get('returnto', '', 'BASE64');
			$decodedreturnto = base64_decode($returnto);
			$WebsiteRoot = JURI::root(true);

			if ($returnto != '')
			{
				$link = $decodedreturnto;
				if (strpos($link, 'http:') === false and strpos($link, 'https:') === false) $link.= $WebsiteRoot . $link;
			}
			else
				$link = $WebsiteRoot . 'index.php?Itemid=' . $Itemid;
			
			$app = JFactory::getApplication();
			$params = $app->getParams();
			$model = $this_->getModel('catalog');
			$model->load($params, false);
			if ($model->delete())
			{
				if ($clean == 1)
				{
					echo 'deleted';
					die ;
				}
				else
					$this_->setRedirect($link,JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_DELETED'));
			}
			else
			{
				if ($clean == 1)
				{
					echo 'error';
					die ;
				}
				else
					$this_->setRedirect($link,JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORDS_NOT_DELETED'));
			}
		} //if(!$model->CheckAuthorization())
}

function CustomTablesSave($task,&$this_)
{
	$jinput = JFactory::getApplication()->input;
	$returnto=$jinput->get('returnto','','BASE64');
	$link 	= base64_decode ($returnto);

	$WebsiteRoot=JURI::root(true);
	if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
		$WebsiteRoot.='/';


	$jinput->set('task','');

	$model = $this_->getModel('edititem');
	$app		= JFactory::getApplication();
	$params=$app->getParams();


	if(!$model->load($params))
	{
	}
	elseif(!$model->CheckAuthorization(1))
	{
		$link =$WebsiteRoot.'index.php?option=com_users&view=login&return='.base64_encode(JoomlaBasicMisc::curPageURL());

		$this_->setRedirect($link,JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
	}
	else
	{
		$msg_='';
		$isOk=true;

		if($task=='saveascopy')
			$isOk=$model->copy($msg_, $link);
		else
			$isOk=$model->store($msg_,$link);

		if($task=='saveandcontinue')
		{
			$link=JoomlaBasicMisc::deleteURLQueryOption($link, 'listing_id');

			if(strpos($link,"?")===false)
				$link.='?';
			else
				$link.='&';
			
			$link.='listing_id='.$jinput->getInt('listing_id');
			
			//stay on the same page if "saveandcontinue"
			//return;
		}
			
		if($isOk)
		{

			if($model->msg_itemissaved=='-')
				$msg='';
			elseif($msg_=='-')
				$msg='';
			elseif($msg_!='')
				$msg=$msg_;
			elseif($model->msg_itemissaved=='')
				$msg=JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_SAVED');
			else
				$msg=$model->msg_itemissaved;

			$site_libpath=JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR;
			require_once($site_libpath.'layout.php');

			$LayoutProc=new LayoutProcessor;
			$LayoutProc->Model=$model;
			$LayoutProc->layout=$msg;
			$msg=$LayoutProc->fillLayout(array(),null,array(),'[]',true);


			if(JFactory::getApplication()->input->get('clean',0,'INT')==1)
			{
				echo 'saved';
				die;
			}
			elseif($link!='')
			{
				$link=str_replace('$get_listing_id',JFactory::getApplication()->input->get('listing_id',0,'INT'),$link);



				if(strpos($link,'tmpl=component')===false)
				{
					if($msg!='')
					{


						$this_->setRedirect($link, $msg);
					}
					else
						$this_->setRedirect($link);


				}//if(strpos($link,'template=component')===false)
				else
				{
					$this_->setRedirect($link);
				}//if(strpos($link,'template=component')===false)

			}//if($link!='')
			else
			{
				if(JFactory::getApplication()->input->get('submitbutton','','CMD')=='nextprint')
				{
				    $link 	= $WebsiteRoot.'index.php?option=com_customtables&view=details'
												.'&Itemid='.JFactory::getApplication()->input->get('Itemid',0,'INT')
												.'&listing_id='.JFactory::getApplication()->input->get('listing_id',0,'INT')
												.'&tmpl=component'
												.'&print=1'
												;

					$status = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

					echo  '<p style="text-align:center;">
						<input type="button" class="button" value="'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_PRINT').'"
					onClick=\'window.open("'.$link.'","win2","'.$status.'"); return false; \'>
					</p>';

					JFactory::getApplication()->input->get('view','details');


					return true;



				}//if(JFactory::getApplication()->input->get('submitbutton','','CMD')=='nextprint')
				else
				{
					$link 	= $WebsiteRoot.'index.php?option=com_customtables&view=catalog&Itemid='.JFactory::getApplication()->input->get('Itemid',0,'INT');


					
						if($msg!='')
							$this_->setRedirect($link, $msg);
						else
							$this_->setRedirect($link);
					

				}//if(JFactory::getApplication()->input->get('submitbutton','','CMD')=='nextprint')
			}////if($link!='')
		}//if($isOk)
		else
		{
			if($msg_=='COM_CUSTOMTABLES_INCORRECT_CAPTCHA')
			{
				echo '<p style="padding:5px;width:100%;background-color:red;color:white;font-size:24px;font-weight:bold;text-align:center;">'.$msg_.'</p>
				<script type="text/javascript">
setTimeout("history.go(-1)", 3000);
</script>';

			}
			else
			{
				if($link!='')
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_NOT_SAVED');
					$this_->setRedirect($link, $msg,'error');
				}
				else
				{
					echo '<p class="error">'.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_RECORD_NOT_SAVED').'</p>';
				}
			}

		}//if($isOk)
	}//if(!$model->CheckAuthorization())

}
