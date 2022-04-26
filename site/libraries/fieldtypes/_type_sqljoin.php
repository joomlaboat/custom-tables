<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CT_FieldTypeTag_sqljoin
{
    public static function resolveSQLJoinType(&$ct,$rowValue, $typeparams, $option_list)
	{
        if(count($typeparams)<1)
			$result.='table not specified';

		if(count($typeparams)<2)
			$result.='field or layout not specified';

		$esr_table=$typeparams[0];

		if(isset($option_list[0]) and $option_list[0]!='')
			$esr_field=$option_list[0];
		else
			$esr_field=$typeparams[1];

		if(count($typeparams)>2)
        {
			$esr_filter=$typeparams[2];
        }
		else
			$esr_filter='';

		//this is important because it has been selected some how.
		$esr_filter='';

		return JHTML::_('ESSQLJoinView.render',$rowValue,$esr_table,$esr_field,$esr_filter,$ct->Languages->Postfix,'');

	}
}
