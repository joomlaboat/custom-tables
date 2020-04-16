<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @subpackage administrator/components/com_customtables/views/layouts/tmpl/dependencies.php
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

function renderDependencies($layout_row)
{

	$db = JFactory::getDBO();
	$layoutname=$layout_row->layoutname;

    $result='';


	$w1='('.$db->quoteName('type').'="sqljoin" OR '.$db->quoteName('type').'="records")';
	$w2a='INSTR(SUBSTRING_INDEX(typeparams,",",2),"layout:'.$layoutname.'")';
	$w2b='INSTR(SUBSTRING_INDEX(typeparams,",",2),"tablelesslayout:'.$layoutname.'")';
	$w2='('.$w2a.' OR '.$w2b.')';
	$wF='f.published=1 AND f.tableid=t.id AND '.$w1.' AND '.$w2;
    $rows=_getTablesThatUseThisLayout($layout_row->layoutname,$w2);

    if(count($rows)>0)
    {
        $result.='<h3>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLES_TYPEPARAM_THAT_USE_THIS_LAYOUT', true).'</h3>';
        $result.=_renderTableList($rows);
    }
	
	
	$w2a='INSTR(SUBSTRING_INDEX(defaultvalue,",",2),"layout:'.$layoutname.'")';
	$w2b='INSTR(SUBSTRING_INDEX(defaultvalue,",",2),"tablelesslayout:'.$layoutname.'")';
	$wF='('.$w2a.' OR '.$w2b.')';
	$rows=_getTablesThatUseThisLayout($layout_row->layoutname,$wF);

    if(count($rows)>0)
    {
        $result.='<h3>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLES_DEFAULTVALUE_THAT_USE_THIS_LAYOUT', true).'</h3>';
        $result.=_renderTableList($rows);
    }


/*
    $result.='<hr/>';
    $rows=_getMenuItemsThatUseThisLayout($table_id);
    if(count($rows)==0)
        $result.='<h4>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_NO_MENUITEMS_THAT_USE_THIS_LAYOUT', true).'</h4>';
    else
    {
        $result.='<h3>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_MENU_THAT_USE_THIS_LAYOUT', true).'</h3>';
        $result.=_renderMenuList($rows);
    }
*/

    return $result;
}

function _renderTableList($rows)
{
    $result='
        <table class="table table-striped">
			<thead>
                <th>'.JText::_('COM_CUSTOMTABLES_TABLES_TABLENAME', true).'</th>
                <th>'.JText::_('COM_CUSTOMTABLES_TABLES_TABLETITLE', true).'</th>
                <th>'.JText::_('COM_CUSTOMTABLES_LISTOFFIELDS', true).'</th>
            </thead>
			<tbody>
            ';

    foreach($rows as $row)
    {
		
		
        $result.='<tr>
        <td><a href="/administrator/index.php?option=com_customtables&view=listoffields&tableid='.$row['tableid'].'" target="_blank">'.$row['tablename'].'</a></td>
		<td><a href="/administrator/index.php?option=com_customtables&view=listoffields&tableid='.$row['tableid'].'" target="_blank">'.$row['tabletitle'].'</a></td>
        <td><ul style="list-style-type:none;">';
		
		$fields=explode(';',$row['fields']);
		
		foreach($fields as $field)
		{
			if($field!="")
			{
				$pair=explode(',',$field);
				$result.='<li><a href="/administrator/index.php?option=com_customtables&view=listoffields&task=fields.edit&tableid='.$row['tableid'].'&id='.$pair[0].'" target="_blank">'.$pair[1].'</a></li>';
			}
		}
		
		$result.='</ul></td></tr>';

    }

    $result.='</tbody>
            <tfoot></tfoot>
		</table>
    ';

    return $result;
}

function _getTablesThatUseThisLayout($layoutname,$wF)
{
    $db = JFactory::getDBO();

    //$wF='f.published=1 AND f.tableid=t.id AND '.$w1.' AND '.$w2;
	//$w2='(INSTR(typeparams,"layout:'.$layoutname.'") OR INSTR(typeparams,"tablelesslayout:'.$layoutname.'"))';
	$fields='(SELECT GROUP_CONCAT(CONCAT(f.id,",",fieldname),";") FROM #__customtables_fields AS f WHERE '.$wF.' ORDER BY fieldname) AS fields';
	
	
	$w='(SELECT tableid FROM #__customtables_fields AS f WHERE '.$wF.' LIMIT 1) IS NOT NULL';
    $query = 'SELECT id AS tableid, tabletitle,tablename, '.$fields.' FROM #__customtables_tables AS t WHERE '.$w.' ORDER BY tablename';

	$db->setQuery( $query );
	if (!$db->query())    die ( $db->stderr());
	$rows=$db->loadAssocList();

    return $rows;
}
/*
function _getTablesThatDependOnThisTable($tablename)
{
    $db = JFactory::getDBO();

    $select_tablename='(SELECT tabletitle FROM #__customtables_tables AS t WHERE t.id=f.tableid LIMIT 1)';

    $query = 'SELECT id, tableid,fieldtitle,typeparams,'.$select_tablename.' AS tabletitle FROM #__customtables_fields AS f WHERE typeparams LIKE "'.$tablename.',%" ORDER BY tabletitle';

	$db->setQuery( $query );
	if (!$db->query())    die ( $db->stderr($tablename));
	$rows=$db->loadAssocList();

    return $rows;
}
*/