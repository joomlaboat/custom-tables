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
	$count=0;
	$db = JFactory::getDBO();
	$layoutname=$layout_row->layoutname;

    $result='<div id="layouteditor_modal_content_box">';


	$w1='('.$db->quoteName('type').'="sqljoin" OR '.$db->quoteName('type').'="records")';
	$w2a='INSTR(SUBSTRING_INDEX(typeparams,",",2),"layout:'.$layoutname.'")';
	$w2b='INSTR(SUBSTRING_INDEX(typeparams,",",2),"tablelesslayout:'.$layoutname.'")';
	$w2='('.$w2a.' OR '.$w2b.')';
	$wF='f.published=1 AND f.tableid=t.id AND '.$w1.' AND '.$w2;
    $rows=_getTablesThatUseThisLayout($layout_row->layoutname,$wF);

    if(count($rows)>0)
    {
		$count+=count($rows);
        $result.='<h3>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLES_WITH_TABLEJOIN_FIELDS', true).'</h3>';
        $result.=_renderTableList($rows);
    }
	
	$w2a='INSTR(SUBSTRING_INDEX(defaultvalue,",",2),"layout:'.$layoutname.'")';
	$w2b='INSTR(SUBSTRING_INDEX(defaultvalue,",",2),"tablelesslayout:'.$layoutname.'")';
	$wF='('.$w2a.' OR '.$w2b.')';
	$rows=_getTablesThatUseThisLayout($layout_row->layoutname,$wF);

    if(count($rows)>0)
    {
		$count+=count($rows);
        $result.='<h3>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_TABLES_DEFAULTVALUE', true).'</h3>';
        $result.=_renderTableList($rows);
    }
	
	$menus=_getMenuItemsThatUseThisLayout($layout_row->layoutname);

    if(count($menus)>0)
    {
		$count+=count($menus);
        $result.='<h3>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_MENUS', true).'</h3>';
		$result.=_renderMenuList($menus);
        
    }

	$layouts=_getLayoutsThatUseThisLayout($layout_row->layoutname);
    if(count($layouts)>0)
    {
		$count+=count($layouts);
        $result.='<h3>'.JText::_('COM_CUSTOMTABLES_DASHBOARD_LISTOFLAYOUTS', true).'</h3>';
		$result.=_renderLayoutList($layouts);
        
    }
	
	if($count==0)
        $result.='<p>'.JText::_('COM_CUSTOMTABLES_LAYOUTS_THIS_LAYOUT_IS_NOT_IN_USE', true).'</p>';

	$result.='</div>';

    return $result;
}

function _renderLayoutList($layouts)
{
    $result='<ul style="list-style-type:none;margin:0px;">';
	
    foreach($layouts as $layout)
    {
		$link='/administrator/index.php?option=com_customtables&view=layouts&layout=edit&id='.$layout['id'];
		$result.='<li><a href="'.$link.'" target="_blank">'.$layout['layoutname'].'</a></li>';
	}
		
	$result.='</ul>';
    return $result;
}

function _renderMenuList($menus)
{
    $result='<ul style="list-style-type:none;margin:0px;">';
	
    foreach($menus as $menu)
    {
		$link='/administrator/index.php?option=com_menus&view=item&client_id=0&layout=edit&id='.$menu['id'];
		$result.='<li><a href="'.$link.'" target="_blank">'.$menu['title'].'</a></li>';
	}
		
	$result.='</ul>';
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
        <td><ul style="list-style-type:none;margin:0px;">';
		
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
	$fields='(SELECT GROUP_CONCAT(CONCAT(f.id,",",fieldname),";") FROM #__customtables_fields AS f WHERE '.$wF.' ORDER BY fieldname) AS fields';

	
	$w='(SELECT tableid FROM #__customtables_fields AS f WHERE '.$wF.' LIMIT 1) IS NOT NULL';
    $query = 'SELECT id AS tableid, tabletitle,tablename, '.$fields.' FROM #__customtables_tables AS t WHERE '.$w.' ORDER BY tablename';

	$db->setQuery( $query );
	if (!$db->query())    die ( $db->stderr());
	$rows=$db->loadAssocList();

    return $rows;
}

function _getMenuItemsThatUseThisLayout($layoutname)
{
    $db = JFactory::getDBO();

	$wheres=array();
	$wheres[]='published=1';
	$wheres[]='INSTR(link,"index.php?option=com_customtables&view=")';
	
	$layout_params=['escataloglayout','esitemlayout','esdetailslayout','eseditlayout','onrecordaddsendemaillayout','cataloglayout'];
	$w=array();
	foreach($layout_params as $l)
	{
		$toSearch='"'.$l.'":"'.$layoutname.'"';
		$w[]='INSTR(params,'.$db->quote($toSearch).')';
	}
	$wheres[]='('.implode(' OR ',$w).')';
	
	$query = 'SELECT id,title FROM #__menu WHERE '.implode(' AND ',$wheres);
	
	$db->setQuery( $query );
	if (!$db->query())    echo ( $db->stderr());
	return $db->loadAssocList();
}

function _getLayoutsThatUseThisLayout($layoutname)
{
    $db = JFactory::getDBO();
	
	$wheres=array();
	$wheres[]='published=1';
	
	$layout_params=['layout:'.$layoutname,'tablelesslayout:'.$layoutname,','.$layoutname.',',':'.$layoutname];
	$w=array();
	foreach($layout_params as $l)
	{
		$w[]='INSTR(layoutcode,'.$db->quote($l).')';
	}
	$wheres[]='('.implode(' OR ',$w).')';
	
	
	$query = 'SELECT id,layoutname FROM #__customtables_layouts WHERE '.implode(' AND ',$wheres);
	
	
	$db->setQuery( $query );
	if (!$db->query())    echo ( $db->stderr());
	return $db->loadAssocList();
}
