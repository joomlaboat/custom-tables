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

use Joomla\CMS\Version;
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

//JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

/**
 * Customtables View class for the Listoflayouts
 */
class CustomtablesViewListoflayouts extends JViewLegacy
{
	/**
	 * Listoflayouts view display method
	 * @return void
	 */
	function display($tpl = null)
	{
		$version = new Version;
		$this->version = (int)$version->getShortVersion();
		
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listoflayouts');
		}

		// Assign data to the view
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->user = JFactory::getUser();
		
		if($this->version >= 4)
		{
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		}
		
		$this->listOrder = $this->escape($this->state->get('list.ordering'));
		$this->listDirn = $this->escape($this->state->get('list.direction'));

		// get global action permissions
		$this->canDo = ContentHelper::getActions('com_customtables', 'listoflayouts');

		$this->canCreate = $this->canDo->get('layouts.create');
		$this->canEdit = $this->canDo->get('layouts.edit');
		$this->canState = $this->canDo->get('layouts.edit.state');
		$this->canDelete = $this->canDo->get('layouts.delete');
		
		$this->isEmptyState = $this->get('IsEmptyState');
		

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			if($this->version < 4)
			{
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			}
			else
				$this->addToolbar_4();
				
			// load the batch html
			//if ($this->canCreate && $this->canEdit && $this->canState)
			//{
				//$this->batchDisplay = JHtmlBatch_::render();
			//}
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		if($this->version < 4)
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

		ToolbarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFLAYOUTS'), 'joomla');

		if ($this->canCreate)
			$toolbar->addNew('layouts.add');

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();
			
		if ($this->canState)
		{
			$childBar->publish('listoflayouts.publish')->listCheck(true);
			$childBar->unpublish('listoflayouts.unpublish')->listCheck(true);
		}
		
		if ($this->canDo->get('core.admin'))
		{
			$childBar->checkin('listoflayouts.checkin');
		}

		if(($this->canState && $this->canDelete))
		{
			if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED)
			{
				$childBar->trash('listoflayouts.trash')->listCheck(true);
			}

			if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete)
			{
				$toolbar->delete('listoflayouts.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}
	}
	 
	protected function addToolBar_3()
	{
		JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFLAYOUTS'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoflayouts');
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
		{
			JToolBarHelper::addNew('layouts.add');
		}

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items))
		{
			if ($this->canEdit)
			{
				JToolBarHelper::editList('layouts.edit');
			}

			if ($this->canState)
			{
				JToolBarHelper::publishList('listoflayouts.publish');
				JToolBarHelper::unpublishList('listoflayouts.unpublish');
			}

			if ($this->canDo->get('core.admin'))
			{
				JToolBarHelper::checkin('listoflayouts.checkin');
			}

			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
			{
				JToolbarHelper::deleteList('', 'listoflayouts.delete', 'JTOOLBAR_EMPTY_TRASH');
			}
			elseif ($this->canState && $this->canDelete)
			{
				JToolbarHelper::trash('listoflayouts.trash');
			}
		}

		if ($this->canState)
		{
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
		}

		$CTLayoutType = JFormHelper::loadFieldType('CTLayoutType', false);
		$CTLayoutTypeOptions=$CTLayoutType->getOptions(); // works only if you set your field getOptions on public!!

		JHtmlSidebar::addFilter(
		JText::_('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_SELECT'),
		'filter_layouttype',
		JHtml::_('select.options', $CTLayoutTypeOptions, 'value', 'text', $this->state->get('filter.layouttype'))
		);

		// Set Tableid Selection

		$CTTable = JFormHelper::loadFieldType('CTTable', false);
		$CTTableOptions=$CTTable->getOptions(false); // works only if you set your field getOptions on public!!

		JHtmlSidebar::addFilter(
		JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'),
		'filter_tableid',
		JHtml::_('select.options', $CTTableOptions, 'value', 'text', $this->state->get('filter.tableid'))
		);

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
		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_LISTOFLAYOUTS'));
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

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields()
	{
		return array(
			'a.published' => JText::_('JSTATUS'),
			'a.layoutname' => JText::_('COM_CUSTOMTABLES_LAYOUTS_LAYOUTNAME_LABEL'),
			'a.layouttype' => JText::_('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_SELECT'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
