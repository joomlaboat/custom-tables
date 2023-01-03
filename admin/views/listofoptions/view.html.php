<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @subpackage view.html.php
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
use CustomTables\DataTypes\Tree;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;

\defined('_JEXEC') or die;

jimport('joomla.application.component.view');

class CustomTablesViewListOfOptions extends JViewLegacy
{
    var $languages;
    var CT $ct;

    function display($tpl = null)
    {
        $this->ct = new CT;

        // Set toolbar items for the page
        CustomtablesHelper::addSubmenu('Options');

        $this->addToolBar();
        $this->sidebar = JHtmlSidebar::render();

        $this->languages = $this->ct->Languages->LanguageList;

        $document = Factory::getDocument();
        $document->setTitle(Text::_('View List Items'));

        $input = Factory::getApplication()->input;

        $this->limitstart = $input->getInt('limitstart', '0');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->lists = $this->_getViewLists();
        $this->user = Factory::getUser();

        // Ensure ampersands and double quotes are encoded in item titles
        foreach ($this->items as $i => $item) {
            $treename = $item->treename;
            $treename = JFilterOutput::ampReplace($treename);
            $treename = str_replace('"', '&quot;', $treename);
            $this->items[$i]->treename = $treename;
        }

        JHTML::_('behavior.tooltip');
        $this->isselectable = true;

        parent::display($tpl);
    }

    protected function addToolBar()
    {
        JToolBarHelper::title(Text::_('Custom Tables - List'), 'menu.png');
        JToolBarHelper::addNew('options.add');
        JToolBarHelper::editList('options.edit');
        JToolBarHelper::custom('listofoptions.copy', 'copy.png', 'copy_f2.png', 'Copy', true);
        JToolBarHelper::deleteList('', 'listofoptions.delete');
    }

    function &_getViewLists()
    {
        $mainframe = Factory::getApplication();
        $db = Factory::getDBO();

        $context = 'com_customtables.listofoptions.';

        $filter_order = $mainframe->getUserStateFromRequest($context . "filter_order", 'filter_order', 'optionname', 'cmd');
        $filter_order_Dir = $mainframe->getUserStateFromRequest($context . "filter_order_Dir", 'filter_order_Dir', 'ASC', 'word');

        if ($filter_order != 'id' and $filter_order != 'optionname')
            $filter_order = 'id';

        $filter_rootparent = $mainframe->getUserStateFromRequest($context . "filter_rootparent", 'filter_rootparent', '', 'int');

        $levellimit = $mainframe->getUserStateFromRequest($context . "levellimit", 'levellimit', 10, 'int');
        $search = $mainframe->getUserStateFromRequest($context . "search", 'search', '', 'string');
        $search = StringHelper::strtolower($search);

        // level limit filter
        $lists['levellist'] = JHTML::_('select.integerlist', 1, 20, 1, 'levellimit', 'size="1" onchange="document.adminForm.submit();"', $levellimit);


        // Category List
        $javascript = 'onchange="document.adminForm.submit();"';

        $available_rootparents = Tree::getAllRootParents();
        $lists['rootparent'] = JHTML::_('select.genericlist', $available_rootparents, 'filter_rootparent', $javascript, 'id', 'optionname', $filter_rootparent);

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;


        $lists['order'] = $filter_order;

        // search filter
        $lists['search'] = $search;

        return $lists;
    }
}
