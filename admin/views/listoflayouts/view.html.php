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
		$this->saveOrder = $this->listOrder == 'ordering';

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

			// Add a batch button
			/*
			if ($this->canBatch && $this->canCreate && $this->canEdit && $this->canState)
			{
				// Get the toolbar object instance
				$bar = JToolBar::getInstance('toolbar');
				// set the batch button name
				$title = JText::_('JTOOLBAR_BATCH');
				// Instantiate a new JLayoutFile instance and render the batch button
				$layout = new JLayoutFile('joomla.toolbar.batch');
				// add the button to the page
				$dhtml = $layout->render(array('title' => $title));
				$bar->appendButton('Custom', $dhtml, 'batch');
			}
			*/

			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
			{
				JToolbarHelper::deleteList('', 'listoflayouts.delete', 'JTOOLBAR_EMPTY_TRASH');
			}
			elseif ($this->canState && $this->canDelete)
			{
				JToolbarHelper::trash('listoflayouts.trash');
			}
		}

		// set help url for this view if found
		//$help_url = CustomtablesHelper::getHelpUrl('listoflayouts');
		//if (CustomtablesHelper::checkString($help_url))
		//{
			//	JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
		//}

		// add the options comp button
		/*
		if ($this->canDo['core.admin'] || $this->canDo['core.options'])
		{
			JToolBarHelper::preferences('com_customtables');
		}
		*/

		if ($this->canState)
		{
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
			
			// only load if batch allowed
			/*
			if ($this->canBatch)
			{
				JHtmlBatch_::addListSelection(
					JText::_('COM_CUSTOMTABLES_KEEP_ORIGINAL_STATE'),
					'batch[published]',
					JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
				);
			}
			*/
		}

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);

		/*
		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			JHtmlBatch_::addListSelection(
				JText::_('COM_CUSTOMTABLES_KEEP_ORIGINAL_ACCESS'),
				'batch[access]',
				JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text')
			);
		}
		*/

		// Set Layouttype Selection
		$this->layouttypeOptions = $this->getTheLayouttypeSelections();
		if ($this->layouttypeOptions)
		{
			// Layouttype Filter
			JHtmlSidebar::addFilter(
				JText::_('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_SELECT'),
				'filter_layouttype',
				JHtml::_('select.options', $this->layouttypeOptions, 'value', 'text', $this->state->get('filter.layouttype'))
			);

			/*
			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Layouttype Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_CUSTOMTABLES_LAYOUTS_LAYOUTTYPE_LABEL').' -',
					'batch[layouttype]',
					JHtml::_('select.options', $this->layouttypeOptions, 'value', 'text')
				);
			}
			*/
		}

		// Set Tableid Selection
		$this->tableidOptions = $this->getTheTableidSelections();

		if ($this->tableidOptions)
		{
			// Tableid Filter
			JHtmlSidebar::addFilter(
				JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_SELECT'),
				'filter_tableid',
				JHtml::_('select.options', $this->tableidOptions, 'value', 'text', $this->state->get('filter.tableid'))
			);

			/*
			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Tableid Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLEID_LABEL').' -',
					'batch[tableid]',
					JHtml::_('select.options', $this->tableidOptions, 'value', 'text')
				);
			}
			*/
		}
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
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}

	protected function getTheLayouttypeSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('layouttype'));
		$query->from($db->quoteName('#__customtables_layouts'));
		$query->order($db->quoteName('layouttype') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$_filter = array();
			foreach ($results as $layouttype)
			{
				// Translate the layouttype selection
				if((int)$layouttype!=0)
				{
					$text = $model->selectionTranslation($layouttype,'layouttype');
					// Now add the layouttype and its text to the options array
					$_filter[] = JHtml::_('select.option', $layouttype, JText::_($text));
				}
			}
			return $_filter;
		}
		return false;
	}

	protected function getTheTableidSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName(array('id','tabletitle')));
		$query->from('#__customtables_tables');
		$query->order($db->quoteName('tabletitle') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadObjectList();

		if ($results)
		{
			//$results = array_unique($results);
			$_filter = array();
			foreach ($results as $result)
			{
				// Now add the tableid and its text to the options array
				$_filter[] = JHtml::_('select.option', $result->id, $result->tabletitle);
			}
			return $_filter;
		}
		return false;
	}
}
