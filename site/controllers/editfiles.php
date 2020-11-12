<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
	
	$jinput = JFactory::getApplication()->input;
		
	$model = $this->getModel('edititem');
	$model->params=JFactory::getApplication()->getParams();;
	$model->id = $jinput->getInt('listing_id');

								if(!$model->CheckAuthorization(5))
								{
									//not authorized
									JFactory::getApplication()->enqueueMessage(JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'), 'error');


									$link =JRoute::_('index.php?option=com_users&view=login&return='.base64_encode(JoomlaBasicMisc::curPageURL()));
									$this->setRedirect($link,JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_NOT_AUTHORIZED'));
									return;
								}
								else
								{
									switch(JFactory::getApplication()->input->getCmd( 'task' ))
									{

									case 'add' :

										$model = $this->getModel('editfiles');

										if ($model->add())
										{
											$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_ADDED' );
										}
										else
										{
											$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_ADDED');
										}

										$fileboxname=JFactory::getApplication()->input->getCmd( 'fileboxname');
										$listing_id=JFactory::getApplication()->input->get('listing_id',0,'INT');
										$returnto=JFactory::getApplication()->input->get('returnto','','BASE64');
										$Itemid=JFactory::getApplication()->input->get('Itemid',0,'INT');

										$link 	= 'index.php?option=com_customtables&view=editfiles'

											.'&fileboxname='.$fileboxname
											.'&listing_id='.$listing_id
											.'&returnto='.$returnto
											.'&Itemid='.$Itemid;

										$this->setRedirect($link, $msg);

										break;

									case 'delete' :

										$model = $this->getModel('editfiles');

										if ($model->delete())
										{
												$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_DELETED' );
										}
										else
										{
											$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_NOT_DELETED');
										}
										//$establename=JFactory::getApplication()->input->getCmd( 'establename');
										$fileboxname=JFactory::getApplication()->input->getCmd( 'fileboxname');
										$listing_id=JFactory::getApplication()->input->get('listing_id',0,'INT');
										$returnto=JFactory::getApplication()->input->get('returnto','','BASE64');
										$Itemid=JFactory::getApplication()->input->get('Itemid',0,'INT');

										$link 	= 'index.php?option=com_customtables&view=editfiles'

											.'&fileboxname='.$fileboxname
											.'&listing_id='.$listing_id
											.'&returnto='.$returnto
											.'&Itemid='.$Itemid;

										$this->setRedirect($link, $msg);

										break;

									case 'saveorder' :

										$model = $this->getModel('editfiles');


										if ($model->reorder())
										{
												$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_ORDER_SAVED' );
										}
										else
										{
											$msg = JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_FILE_ORDER_NOT_SAVED');
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
								}//switch(JFactory::getApplication()->input->get('task','','CMD'))
