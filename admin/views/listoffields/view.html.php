<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Customtables View class for the Listoffields
 */
class CustomtablesViewListoffields extends JViewLegacy
{
	/**
	 * Listoffields view display method
	 * @return void
	 */

	var $tableid;
	var $tablename;
	var $tabletitle;
	var $languages;

	function display($tpl = null)
	{
		$app = JFactory::getApplication();


		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listoffields');
		}

		// Assign data to the view
		//$model = $this->model;
		//$this->tableid = $this->tableid+100;

		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->user = JFactory::getUser();
		$this->listOrder = $this->escape($this->state->get('list.ordering'));
		$this->listDirn = $this->escape($this->state->get('list.direction'));
		$this->saveOrder = $this->listOrder == 'ordering';
		// get global action permissions
		$this->canDo = CustomtablesHelper::getActions('fields');
		$this->canEdit = $this->canDo->get('core.edit');
		$this->canState = $this->canDo->get('core.edit.state');
		$this->canCreate = $this->canDo->get('core.create');
		$this->canDelete = $this->canDo->get('core.delete');
		$this->canBatch = $this->canDo->get('core.batch');

		// We don't need toolbar in the modal window.
		$this->tableid=$app->input->getInt('tableid',0);
		$this->customtablename='';

		if($this->tableid!=0)
		{
			require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
			$table=ESTables::getTableRowByID($this->tableid);
			if(!is_object($table) and $table==0)
			{
				JFactory::getApplication()->enqueueMessage('Table not found', 'error');
				$this->tableid=0;
			}
			else
			{
				$this->tablename=$table->tablename;
				$this->tabletitle=$table->tabletitle;
				$this->customtablename=$table->customtablename;
			}
		}

		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
			// load the batch html
			if ($this->canCreate && $this->canEdit && $this->canState)
			{
				$this->batchDisplay = JHtmlBatch_::render();
			}
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
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}

	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		$app = JFactory::getApplication();

		if($this->tableid!=0)
		{
			require_once(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');

			JToolBarHelper::title('Table "'.$this->tabletitle.'" - '.JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');
		}
		else
			JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'), 'joomla');


		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoffields&tableid='.$this->tableid);
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
		{
			JToolBarHelper::addNew('fields.add');
		}

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items))
		{
			if ($this->canEdit)
			{
				JToolBarHelper::editList('fields.edit');
			}

			if ($this->canState)
			{
				JToolBarHelper::publishList('listoffields.publish');
				JToolBarHelper::unpublishList('listoffields.unpublish');
				//JToolBarHelper::archiveList('listoffields.archive');

				if ($this->canDo->get('core.admin'))
				{
					JToolBarHelper::checkin('listoffields.checkin');
				}
			}

			// Add a batch button
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

			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
			{
				JToolbarHelper::deleteList('', 'listoffields.delete', 'JTOOLBAR_EMPTY_TRASH');
			}
			elseif ($this->canState && $this->canDelete)
			{
				JToolbarHelper::trash('listoffields.trash');
			}
		}

		// set help url for this view if found
		$help_url = CustomtablesHelper::getHelpUrl('listoffields');
		if (CustomtablesHelper::checkString($help_url))
		{
				JToolbarHelper::help('COM_CUSTOMTABLES_HELP_MANAGER', false, $help_url);
		}

		// add the options comp button
		if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
		{
			JToolBarHelper::preferences('com_customtables');
		}

		if ($this->canState)
		{
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
			// only load if batch allowed
			if ($this->canBatch)
			{
				JHtmlBatch_::addListSelection(
					JText::_('COM_CUSTOMTABLES_KEEP_ORIGINAL_STATE'),
					'batch[published]',
					JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
				);
			}
		}

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);

		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			JHtmlBatch_::addListSelection(
				JText::_('COM_CUSTOMTABLES_KEEP_ORIGINAL_ACCESS'),
				'batch[access]',
				JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text')
			);
		}

		// Set Type Selection
		$this->typeOptions = $this->getTheTypeSelections();
		if ($this->typeOptions)
		{
			// Type Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_CUSTOMTABLES_FIELDS_TYPE_LABEL').' -',
				'filter_type',
				JHtml::_('select.options', $this->typeOptions, 'value', 'text', $this->state->get('filter.type'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Type Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_CUSTOMTABLES_FIELDS_TYPE_LABEL').' -',
					'batch[type]',
					JHtml::_('select.options', $this->typeOptions, 'value', 'text')
				);
			}
		}

		// Set Tableid Selection
		$this->tableidOptions = $this->getTheTableidSelections();


		if ($this->tableidOptions)
		{
			// Tableid Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_CUSTOMTABLES_FIELDS_TABLE_LABEL').' -',
				'filter_tableid',
				JHtml::_('select.options', $this->tableidOptions, 'value', 'text', $this->state->get('filter.tableid'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Tableid Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_CUSTOMTABLES_FIELDS_TABLE_LABEL').' -',
					'batch[tableid]',
					JHtml::_('select.options', $this->tableidOptions, 'value', 'text')
				);
			}
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
		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_LISTOFFIELDS'));
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
			'a.sorting' => JText::_('JGRID_HEADING_ORDERING'),
			'a.published' => JText::_('JSTATUS'),
			'a.fieldtitle' => JText::_('COM_CUSTOMTABLES_FIELDS_FIELDTITLE_LABEL'),
			'a.type' => JText::_('COM_CUSTOMTABLES_FIELDS_TYPE_LABEL'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}

	protected function getTheTypeSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('type'));
		$query->from($db->quoteName('#__customtables_fields'));
		$query->order($db->quoteName('type') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$_filter = array();
			foreach ($results as $type)
			{
				// Translate the type selection
				$text = $model->selectionTranslation($type,'type');
				// Now add the type and its text to the options array
				$_filter[] = JHtml::_('select.option', $type, JText::_($text));
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
