<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

$layout=JFactory::getApplication()->input->get('layout','','CMD');


	switch(JFactory::getApplication()->input->get('task','','CMD'))
	{
		case 'edit':

			JFactory::getApplication()->input->set('view', 'listedit');
			JFactory::getApplication()->input->set('layout', 'form'  );

			parent::display();

		break;

		case 'save':

			$model = $this->getModel('listedit');

			$link 	= 'index.php?option=com_customtables&view=list&Itemid='.JFactory::getApplication()->input->get('Itemid',0,'INT');
			if ($model->store())
			{
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OPTION_SAVED' );
				$this->setRedirect($link, $msg);
			}
			else
			{
				$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OPTION_NOT_SAVED');
				$this->setRedirect($link, $msg,'error');
			}

		break;

		case 'cancel':

			$link 	= 'index.php?option=com_customtables&view=list&Itemid='.JFactory::getApplication()->input->get('Itemid',0,'INT');

			$msg = '';

			$this->setRedirect($link, $msg);


		break;

		case 'remove':


			$link 	= 'index.php?option=com_customtables&view=list&Itemid='.JFactory::getApplication()->input->get('Itemid',0,'INT');

			// Check for request forgeries
			JSession::checkToken() or jexit( 'COM_CUSTOMTABLES_INVALID_TOKEN' );

			// Get some variables from the request

			$cid	= JFactory::getApplication()->input->post->get('cid',array(),'array');
			JArrayHelper::toInteger($cid);

			if (!count($cid)) {
				$this->setRedirect( $link, JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_OPTIONS_NOT_SELECTED') );
				return false;
			}

			$model = $this->getModel( 'List' );
			if ($n = $model->delete($cid)) {
				$msg = JText::sprintf( '% COM_CUSTOMTABLES_OPTIONS_DELETED', $n );
			} else {
				$msg = $model->getError();
			}
			$this->setRedirect( $link, $msg );

		break;

		default:

			JFactory::getApplication()->input->set('view', 'list');
			parent::display();

		break;
	}
