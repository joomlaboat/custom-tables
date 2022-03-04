<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

	$jinput = JFactory::getApplication()->input;
		
	$model = $this->getModel('edititem');
	$model->params=JFactory::getApplication()->getParams();;
	$model->listing_id = $jinput->getCmd('listing_id');
	
	if(!$model->CheckAuthorization())
	{
		//not authorized
		$link =JRoute::_('index.php?option=com_users&view=login&return='.base64_encode(JoomlaBasicMisc::curPageURL()));
		
		$this->setRedirect($link,JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_YOU_MUST_LOGIN_FIRST'));
	}
	else
	{
		switch(JFactory::getApplication()->input->getCmd( 'task' ))
		{
			case 'add' :
				$model = $this->getModel('editphotos');
				$model->load();

				if ($model->add())
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE_ADDED' );
				}
				else
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE_NOT_ADDED');
				}
				
				$establename=JFactory::getApplication()->input->getCmd( 'establename');
				$galleryname=JFactory::getApplication()->input->get('galleryname','','CMD');
				$listing_id=JFactory::getApplication()->input->get('listing_id',0,'INT');
				$returnto=JFactory::getApplication()->input->get('returnto','','BASE64');
				$Itemid=JFactory::getApplication()->input->get('Itemid',0,'INT');

				$link 	= 'index.php?option=com_customtables&view=editphotos'
					.'&establename='.$establename
					.'&galleryname='.$galleryname
					.'&listing_id='.$listing_id
					.'&returnto='.$returnto
					.'&Itemid='.$Itemid;
				
				$this->setRedirect($link, $msg);
			break;

			case 'delete' :
				$model = $this->getModel('editphotos');
				$model->load();

				if ($model->delete())
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE_DELETED' );
				}
				else
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE_NOT_DELETED');
				}
			
				$establename=JFactory::getApplication()->input->getCmd( 'establename');
				$galleryname=JFactory::getApplication()->input->get('galleryname','','CMD');
				$listing_id=JFactory::getApplication()->input->get('listing_id',0,'INT');
				$returnto=JFactory::getApplication()->input->get('returnto','','BASE64');
				$Itemid=JFactory::getApplication()->input->get('Itemid',0,'INT');

				$link 	= 'index.php?option=com_customtables&view=editphotos'
											.'&establename='.$establename
											.'&galleryname='.$galleryname
											.'&listing_id='.$listing_id
											.'&returnto='.$returnto
											.'&Itemid='.$Itemid;

				$this->setRedirect($link, $msg);
				break;

			case 'saveorder' :
				$model = $this->getModel('editphotos');
				$model->load();

				if ($model->reorder())
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE_ORDER_SAVED' );
				}
				else
				{
					$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_IMAGE_ORDER_NOT_SAVED');
				}

				$returnto=JFactory::getApplication()->input->get('returnto','','BASE64');

				$link 	= $returnto=base64_decode (JFactory::getApplication()->input->get('returnto','','BASE64'));
				$this->setRedirect($link, $msg);
				break;

			case 'cancel' :
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT_CANCELED' );
				$link 	= $returnto=base64_decode (JFactory::getApplication()->input->get('returnto','','BASE64'));
				$this->setRedirect($link, $msg);
				break;
			default:
				parent::display();
		}
	}
