<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
defined('_JEXEC') or die;

use CustomTables\common;
use CustomTables\CT;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;

class CustomtablesViewListoflayouts extends HtmlView
{
	var CT $ct;

	function display($tpl = null)
	{
		$this->ct = new CT([], true);

		if ($this->getLayout() !== 'modal') {
			// Include helper submenu
			CustomtablesHelper::addSubmenu('listoflayouts');
		}

		// Assign data to the view
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->listOrder = common::escape($this->state->get('list.ordering'));
		$this->listDirn = common::escape($this->state->get('list.direction')) ?? '';

		// get global action permissions
		$this->canDo = ContentHelper::getActions('com_customtables', 'listoflayouts');
		$this->canCreate = $this->canDo->get('layouts.create');
		$this->canEdit = $this->canDo->get('layouts.edit');
		$this->canState = $this->canDo->get('layouts.edit.state');
		$this->canDelete = $this->canDo->get('layouts.delete');
		$this->isEmptyState = count($this->items ?? 0) == 0;

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal') {
			if (CUSTOMTABLES_JOOMLA_MIN_4) {
				$this->addToolbar_4();
			} else {
				$this->addToolbar_3();
				$this->sidebar = JHtmlSidebar::render();
			}
		}

		// Check for errors.
		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		if (CUSTOMTABLES_JOOMLA_MIN_4)
			parent::display('quatro');
		else
			parent::display($tpl);
	}

	protected function addToolbar_4()
	{
		// Get the toolbar object instance
		$toolbar = Toolbar::getInstance('toolbar');

		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFLAYOUTS'), 'joomla');

		if ($this->canCreate)
			$toolbar->addNew('layouts.add');

		$dropdown = $toolbar->dropdownButton('status-group')
			->text('JTOOLBAR_CHANGE_STATUS')
			->toggleSplit(false)
			->icon('icon-ellipsis-h')
			->buttonClass('btn btn-action')
			->listCheck(true);

		$childBar = $dropdown->getChildToolbar();

		if ($this->canState) {
			$childBar->publish('listoflayouts.publish')->listCheck(true);
			$childBar->unpublish('listoflayouts.unpublish')->listCheck(true);
		}

		if ($this->canDo->get('core.admin')) {
			$childBar->checkin('listoflayouts.checkin');
		}

		if (($this->canState && $this->canDelete)) {
			if ($this->state->get('filter.published') != ContentComponent::CONDITION_TRASHED) {
				$childBar->trash('listoflayouts.trash')->listCheck(true);
			}

			if (!$this->isEmptyState && $this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->canDelete) {
				$toolbar->delete('listoflayouts.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}
	}

	protected function addToolBar_3()
	{
		ToolbarHelper::title(common::translate('COM_CUSTOMTABLES_LISTOFLAYOUTS'), 'joomla');

		if ($this->canCreate) {
			ToolbarHelper::addNew('layouts.add');
		}

		// Only load if there are items
		if (CustomtablesHelper::checkArray($this->items)) {
			if ($this->canEdit) {
				ToolbarHelper::editList('layouts.edit');
			}

			if ($this->canState) {
				ToolbarHelper::publishList('listoflayouts.publish');
				ToolbarHelper::unpublishList('listoflayouts.unpublish');
			}

			if ($this->canDo->get('core.admin')) {
				ToolbarHelper::checkin('listoflayouts.checkin');
			}

			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete)) {
				ToolbarHelper::deleteList('', 'listoflayouts.delete', 'JTOOLBAR_EMPTY_TRASH');
			} elseif ($this->canState && $this->canDelete) {
				ToolbarHelper::trash('listoflayouts.trash');
			}
		}

		JHtmlSidebar::setAction('index.php?option=com_customtables&view=listoflayouts');
	}

	/**
	 * @throws Exception
	 *
	 * @since 3.0.0
	 */
	function isTwig($row): array
	{
		$errors = [];

		$original_ct_tags_q = ['currenturl', 'currentuserid', 'currentusertype', 'date', 'gobackbutton', 'description', 'format', 'Itemid', 'returnto',
			'server', 'tabledescription', 'tabletitle', 'table', 'today', 'user', 'websiteroot', 'layout', 'if', 'headtag', 'metakeywords', 'metadescription',
			'pagetitle', 'php', 'php_a', 'php_b', 'php_c', 'catalogtable', 'catalog', 'recordlist', 'page', 'add', 'count', 'navigation', 'batchtoolbar',
			'checkbox', 'pagination', 'print', 'recordcount', 'search', 'searchbutton', 'button', 'buttons', 'captcha', 'id', 'published', 'link', 'linknoreturn',
			'number', 'toolbar', 'cart', 'createuser', 'resolve', '_value', 'sqljoin', 'attachment'];

		$original_ct_tags_s = ['_if', '_endif', '_value', '_edit'];

		$twig_tags = [
			'fields.list', 'fields.count', 'fields.json',
			'user.name', 'user.username', 'user.email', 'user.id',
			'user.lastvisitdate', 'user.registerdate', 'user.usergroups', 'user.customfield',

			'url.link', 'url.format', 'url.itemid', 'url.getint', 'url.getstring', 'url.getuint', 'url.getword', 'url.getfloat',
			'url.getalnum', 'url.getcmd', 'url.getstringandencode', 'url.getstringanddecode', 'url.base64', 'url.root', 'url.set', 'url.server',

			'html.add', 'html.recordcount', 'html.checkboxcount', 'html.print', 'html.goback', 'html.navigation', 'html.batch', 'html.search',
			'html.searchbutton', 'html.searchreset', 'html.toolbar', 'html.pagination', 'html.orderby', 'html.limit', 'html.button', 'html.captcha',
			'html.message', 'html.recordlist', 'html.importcsv', 'html.tablehead', 'html.base64encode',

			'document.setpagetitle', 'document.setheadtag', 'document.script', 'document.style', 'document.jslibrary', 'document.setmetakeywords',
			'document.setmetadescription', 'document.layout', 'document.languagepostfix', 'document.attachment', 'document.sitename',
			'document.set', 'document.get', 'document.config',

			'record.id', 'record.number', 'record.published', 'record.advancedjoin',
			'record.link', 'record.count', 'record.avg', 'record.min', 'record.max', 'record.sum', 'record.', 'record.',
			'record.joincount', 'record.joinavg', 'record.joinmin', 'record.joinmax', 'record.joinvalue', 'record.jointable',
			'record.advancedjoin', 'record.missingfields', 'record.missingfieldslist', 'record.islast',

			'tables.getvalue', 'tables.getrecord', 'tables.getrecords',

			'table.records', 'table.recordstotal', 'table.recordpagestart', 'table.recordsperpage', 'table.title', 'table.description',
			'table.name', 'table.id', 'table.fields',

			'document.config'];

		$twig_catalog_tags = ['html.add', 'html.batch', 'html.recordcount', 'html.checkboxcount', 'html.batch', 'html.search', 'html.searchbutton', 'html.searchreset',
			'html.pagination', 'html.orderby', 'html.limit', 'html.recordlist', 'html.importcsv'];

		$ct = new CT([], true);
		$ct->getTable($row->tableid);

		// ------------------------ CT Original
		$original_ct_matches = 0;

		foreach ($original_ct_tags_s as $tag) {
			if (str_contains($row->layoutcode, '[' . $tag . ':'))
				$original_ct_matches += 1;

			if (str_contains($row->layoutcode, '[' . $tag . ']'))
				$original_ct_matches += 1;
		}

		foreach ($original_ct_tags_q as $tag) {
			if (str_contains($row->layoutcode, '{' . $tag . ':'))
				$original_ct_matches += 1;

			if (strpos($row->layoutcode, '{' . $tag . '}') !== false)
				$original_ct_matches += 1;
		}

		if ($ct->Table !== null) {
			foreach ($ct->Table->fields as $field) {
				$fieldName = $field['fieldname'];

				if (str_contains($row->layoutcode, '*' . $fieldName . '*'))
					$original_ct_matches += 1;

				if (str_contains($row->layoutcode, '|' . $fieldName . '|'))
					$original_ct_matches += 1;

				if (str_contains($row->layoutcode, '[' . $fieldName . ':'))
					$original_ct_matches += 1;

				if (str_contains($row->layoutcode, '[' . $fieldName . ']'))
					$original_ct_matches += 1;
			}
		}

		// ------------------------ Twig
		$twig_matches = 0;
		$twigTagFound = false;

		if (in_array((int)$row->layouttype, [CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM, CUSTOMTABLES_LAYOUT_TYPE_DETAILS, CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM, CUSTOMTABLES_LAYOUT_TYPE_EMAIL])) {
			if (str_contains($row->layoutcode, '{% block record %}'))
				$errors [] = 'Remove {% block record %} tag';
		}

		foreach ($twig_tags as $tag) {
			if (str_contains($row->layoutcode, '{{ ' . $tag . '('))
				$twigTagFound = true;

			if (str_contains($row->layoutcode, '{{ ' . $tag . ' }}'))
				$twigTagFound = true;

			if ($twigTagFound) {
				$twig_matches += 1;
				$twigTagFound = false;

				if (in_array((int)$row->layouttype, [CUSTOMTABLES_LAYOUT_TYPE_EDIT_FORM, CUSTOMTABLES_LAYOUT_TYPE_DETAILS, CUSTOMTABLES_LAYOUT_TYPE_CATALOG_ITEM, CUSTOMTABLES_LAYOUT_TYPE_EMAIL])) {
					//Edit for or single item type layout
					if (in_array($tag, $twig_catalog_tags))
						$errors [] = 'Remove {{ ' . $tag . ' }} tag';
				}
			}
		}

		if ($ct->Table !== null) {
			foreach ($ct->Table->fields as $field) {
				$fieldName = $field['fieldname'];

				if (str_contains($row->layoutcode, '{{ ' . $fieldName . '('))
					$twig_matches += 1;

				if (str_contains($row->layoutcode, '{{ ' . $fieldName . ' }}'))
					$twig_matches += 1;

				if (str_contains($row->layoutcode, '{{ ' . $fieldName . '.'))
					$twig_matches += 1;
			}
		}

		return ['original' => $original_ct_matches, 'twig' => $twig_matches, 'errors' => $errors];
	}
}
