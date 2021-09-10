<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
\defined('_JEXEC') or die;
// import Joomla view library

//jimport('joomla.application.component.view');
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
// import Joomla view library
//jimport('joomla.application.component.view');

/**
 * Customtables View class for the Listoftables
 */
class CustomtablesViewListoftables extends JViewLegacy
{
	/**
	 * Listoftables view display method
	 * @return void
	 */
	var $advanced_options;
	var $languages;

	function display($tpl = null)
	{
		$version = new Version;
		$this->version = (int)$version->getShortVersion();
		
		$phptagprocessor=JPATH_SITE.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'content'.DIRECTORY_SEPARATOR.'customtables'.DIRECTORY_SEPARATOR.'protagprocessor'.DIRECTORY_SEPARATOR.'phptags.php';
		
		if(file_exists($phptagprocessor))
			$this->advanced_options=true;
		else
			$this->advanced_options=false;


		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listoftables');
		}
		
		if($this->version >= 4)
		{
			$this->filterForm    = $this->get('FilterForm');
			$this->activeFilters = $this->get('ActiveFilters');
		}
		
		// Assign data to the view
		$this->items = $this->get('Items');

		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->user = JFactory::getUser();
		$this->listOrder = $this->escape($this->state->get('list.ordering'));
		$this->listDirn = $this->escape($this->state->get('list.direction'));
		$this->saveOrder = $this->listOrder == 'ordering';
		// get global action permissions

		$this->canDo = ContentHelper::getActions('com_customtables', 'tables');
		$this->canCreate = $this->canDo->get('tables.create');
		$this->canEdit = $this->canDo->get('tables.edit');
		$this->canState = $this->canDo->get('tables.edit.state');
		$this->canDelete = $this->canDo->get('tables.delete');
		
		$this->isEmptyState = $this->get('IsEmptyState');
		//$this->canBatch = false;//$this->canDo->get('core.batch');

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

		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'languages.php');
		$LangMisc	= new ESLanguages;
		$this->languages=$LangMisc->getLanguageList();

		// Display the template
		if($this->version < 4)
			parent::display($tpl);
		else
			parent::display('quatro');


		// Set the document
		$this->setDocument();
	}


	protected function addToolbar_4()
	{
		$canDo = $this->canDo;
		$user  = Factory::getUser();

		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(Text::_('COM_CUSTOMTABLES_LISTOFTABLES'), 'joomla');

		if ($this->canCreate)
			$toolbar->addNew('tables.add');

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();
			
		if ($this->canState)
		{
			$childBar->publish('listoftables.publish')->listCheck(true);
			$childBar->unpublish('listoftables.unpublish')->listCheck(true);
		}
		
		if ($this->canDo->get('core.admin'))
		{
			$childBar->checkin('listoftables.checkin');
		}

		if(($this->canState && $this->canDelete))
		{
			if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED)
			{
				$childBar->trash('listoftables.trash')->listCheck(true);
			}

			if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete)
			{
				$toolbar->delete('listoftables.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}
	}

	protected function addToolBar_3()
	{
		JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFTABLES'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoftables');
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
		{
			JToolBarHelper::addNew('tables.add');
		}

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items))
		{
			if ($this->canEdit)
			{
				JToolBarHelper::editList('tables.edit');
			}

			if ($this->canState)
			{
				JToolBarHelper::publishList('listoftables.publish');
				JToolBarHelper::unpublishList('listoftables.unpublish');
			}

			if ($this->canDo->get('core.admin'))
			{
				JToolBarHelper::checkin('listoftables.checkin');
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
				JToolbarHelper::deleteList('', 'listoftables.delete', 'JTOOLBAR_EMPTY_TRASH');
			}
			elseif ($this->canState && $this->canDelete)
			{
				JToolbarHelper::trash('listoftables.trash');
			}
		}

		if($this->advanced_options)
			JToolBarHelper::custom('tables.export','download.png','','Export');

		// set help url for this view if found
		/*
		$help_url = CustomtablesHelper::getHelpUrl('listoftables');
		if (CustomtablesHelper::checkString($help_url))
		{
				JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
		}
		*/

		// add the options comp button
		/*
		if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
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

		// Set Tableid Selection
		$this->categoryidOptions = $this->getCategorySelections();

		if ($this->categoryidOptions)
		{
			// Tableid Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_CUSTOMTABLES_LAYOUTS_CATEGORY_LABEL').' -',
				'filter_category',
				JHtml::_('select.options', $this->categoryidOptions, 'value', 'text', $this->state->get('filter.category'))
			);

			/*
			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Tableid Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_CUSTOMTABLES_LAYOUTS_CATEGORY_LABEL').' -',
					'batch[category]',
					JHtml::_('select.options', $this->categoryidOptions, 'value', 'text')
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
		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_LISTOFTABLES'));
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
					//'a.sorting' => JText::_('JGRID_HEADING_ORDERING'),
		return array(
			'a.published' => JText::_('JSTATUS'),
			'a.tablename' => JText::_('COM_CUSTOMTABLES_TABLES_TABLENAME_LABEL'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}

	protected function getCategorySelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		//$query = $db->getQuery(true);

		// Select the text.
		//$query->select($db->quoteName(array('id','categoryname')));
		//$query->from('#__customtables_categories');
		//$query->order($db->quoteName('categoryname') . ' ASC');

		$query='SELECT id,categoryname FROM #__customtables_categories ORDER BY categoryname ASC';

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
				$_filter[] = JHtml::_('select.option', $result->id, $result->categoryname);
			}
			return $_filter;	
		}
		return false;
	}

	protected function getNumberOfRecords($realtablename,$realidfield)
	{
		$db = JFactory::getDBO();
		$query='SELECT COUNT('.$realidfield.') AS count FROM '.$realtablename.' LIMIT 1';
		
		$db->setQuery( $query );
        $rows=$db->loadObjectList();
		return $rows[0]->count;
	}
}
