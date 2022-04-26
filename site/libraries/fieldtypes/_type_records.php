<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class CT_FieldTypeTag_records
{
    public static function resolveRecordType(&$ct,$rowValue, array $typeparams, array $options)
	{
		$sortbyfield='';

		if(count($typeparams)<1)
			$result.='table not specified';

		if(count($typeparams)<2)
			$result.='field or layout not specified';

		if(count($typeparams)<3)
			$result.='selector not specified';

		$esr_table=$typeparams[0];

		if($options[0]!='')
		{
			$esr_field=$options[0];

			if(isset($options[1]))
			{
				$sortbyfield=$options[1];
			}

		}
		else
			$esr_field=$typeparams[1];

		$esr_selector=$typeparams[2];

		if(count($typeparams)>3)
			$esr_filter=$typeparams[3];
		else
			$esr_filter='';

		if($sortbyfield=='' and isset($typeparams[5]))
			$sortbyfield=$typeparams[5];

		//this is important because it has been selected some how.
		$esr_filter='';

		return JHTML::_('ESRecordsView.render',$rowValue,$esr_table,$esr_field,$esr_selector,$esr_filter,$ct->Languages->Postfix,$sortbyfield);
	}
}
