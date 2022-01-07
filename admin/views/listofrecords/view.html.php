<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
\defined('_JEXEC') or die;

use CustomTables\Fields;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

use CustomTables\CT;

/**
 * Customtables View class for the Listoffields
 */
class CustomtablesViewListofrecords extends JViewLegacy
{
	/**
	 * Listoffields view display method
	 * @return void
	 */
	var $ct;
	var $tablefields;
	

	function display($tpl = null)
	{
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listofrecords');
		}
		
		//$this->ct = new CT;
		$model = $this->getModel();
		$this->ct = $model->ct;
		
		$app = JFactory::getApplication();
		
		$this->tablefields=array();
		
		$this->state = $this->get('State');
		
		if($this->ct->Table->tableid == 0)
			return;
		
		$this->tablefields=Fields::getFields($this->ct->Table->tableid);

		$this->items = $this->get('Items');
		
		$this->pagination = $this->get('Pagination');
		$this->user = JFactory::getUser();
		
		if($this->ct->Env->version >= 4)
		{
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		}
		
		$this->listOrder = $this->escape($this->state->get('list.ordering'));
		$this->listDirn = $this->escape($this->state->get('list.direction'));
		
		// get global action permissions
		$this->canDo = ContentHelper::getActions('com_customtables', 'tables');
		$this->canEdit = $this->canDo->get('tables.edit');
		$this->canState = $this->canDo->get('tables.edit');
		$this->canCreate = $this->canDo->get('tables.edit');
		$this->canDelete = $this->canDo->get('tables.edit');
		
		$this->isEmptyState = $this->get('IsEmptyState');

		if ($this->getLayout()!== 'modal')
		{
			if($this->ct->Env->version < 4)
			{
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			}
			else
				$this->addToolbar_4();
				
		}

		// Display the template
		if($this->ct->Env->version < 4)
			parent::display($tpl);
		else
			parent::display('quatro');

		// Set the document
		$this->setDocument();
	}

	/**
	 * Setting the toolbar
	 */
	 
	protected function addToolbar_4()
	{
		$user  = Factory::getUser();

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		if($this->ct->Table->tableid!=0)
		{
			JToolBarHelper::title('Custom Tables - Table "'.$this->ct->Table->tabletitle.'" - '.JText::_('COM_CUSTOMTABLES_LISTOFRECORDS'), 'joomla');
		}
		else
		{
			JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFRECORDS'), 'joomla');
			return;
		}

		
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listofrecords&tableid='.$this->ct->Table->tableid);
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/records');

		if ($this->canCreate)
			$toolbar->addNew('records.add');

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();
			
		if ($this->canState)
		{
			$childBar->publish('listofrecords.publish')->listCheck(true);
			$childBar->unpublish('listofrecords.unpublish')->listCheck(true);
		}
		
		if(($this->canState && $this->canDelete))
		{
			if (!$this->isEmptyState && $this->canDelete)
			{
				$childBar->delete('listofrecords.delete')
					->text('JTOOLBAR_DELETE')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}
	}
	 
	protected function addToolBar_3()
	{
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		$app = JFactory::getApplication();

		if($this->ct->Table->tableid!=0)
		{
			JToolBarHelper::title('Custom Tables - Table "'.$this->ct->Table->tabletitle.'" - '.JText::_('COM_CUSTOMTABLES_LISTOFRECORDS'), 'joomla');
		}
		else
			JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');


		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listofrecords&tableid='.$this->ct->Table->tableid);
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/records');

		if ($this->canCreate)
		{
			JToolBarHelper::addNew('records.add');
		}

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items))
		{
			if ($this->canEdit)
			{
				JToolBarHelper::editList('records.edit');
			}

			if ($this->canState)
			{
				JToolBarHelper::publishList('listofrecords.publish');
				JToolBarHelper::unpublishList('listofrecords.unpublish');
			}

			if ($this->canDelete)
			{
				JToolbarHelper::deleteList('', 'listofrecords.delete', 'JTOOLBAR_DELETE');
			}
		}

		if ($this->canState)
		{
			// Build the active state filter options.
			$options        = array();
			$options[]      = JHtml::_('select.option', '1', 'COM_CUSTOMTABLES_PUBLISHED');
			$options[]      = JHtml::_('select.option', '0', 'COM_CUSTOMTABLES_UNPUBLISHED');
			$options[]      = JHtml::_('select.option', '*', 'COM_CUSTOMTABLES_ALL');

			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.published'), true)
			);
			// only load if batch allowed
		}

		// Set Tableid Selection
		/*
		$CTTable = JFormHelper::loadFieldType('CTTable', false);
		$CTTableOptions=$CTTable->getOptions(false); // works only if you set your field getOptions on public!!

		JHtmlSidebar::addFilter(
		JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'),
		'filter_tableid',
		JHtml::_('select.options', $CTTableOptions, 'value', 'text', $this->state->get('filter.tableid'))
		);
		*/
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_LISTOFRECORDS'));
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 50)
		{
			// use the helper htmlEscape method instead and shorten the string
			return CustomtablesHelper::htmlEscape($var, $this->_charset, true);
		}
		// use the helper htmlEscape method instead.
		return CustomtablesHelper::htmlEscape($var, $this->_charset);
	}
	
	protected function processRecord($row,$detailsLayout)
	{
		$jinput=JFactory::getApplication()->input;

		$paramsArray=array();
		$paramsArray['establename']=$this->ct->Table->tablename;
		$paramsArray['listingid']=$row['id'];
		$paramsArray['custom_where']='';
		
		if($paramsArray==null)
				return '';

		$_params= new JRegistry;
		$_params->loadArray($paramsArray);

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'details.php');

		$config=array();
		$model = JModelLegacy::getInstance('Details', 'CustomTablesModel', $config);
		$model->load($_params,$paramsArray['listingid'],true,$paramsArray['custom_where']);

		$model->ct->LayoutProc->layout=$detailsLayout;

		if(count($row)>0)
		{
			$model->ct->LayoutProc->toolbar_array=array();
			$result=$model->ct->LayoutProc->fillLayout($row);
			return $result;
		}
		else
			return 'CustomTables Record Not Found';
	}
}
