<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use \Joomla\CMS\Factory;

use CustomTables\CT;
use CustomTables\TwigProcessor;

class CT_FieldTypeTag_sqljoin
{
	//New function
	public static function resolveSQLJoinTypeValue(&$field, $layoutcode, $listing_id, array $options)
	{
		$db = Factory::getDBO();
		
		$ct = new CT;
		$ct->getTable($field->params[0]);

		if(isset($field->params[6]))
			$selector=$field->params[6];
		else
			$selector='dropdown';	
		
		$row  = $ct->Table->loadRecord($listing_id);
		
		$twig = new TwigProcessor($ct, '{% autoescape false %}'.$layoutcode.'{% endautoescape %}');
		return $twig->process($row);
	}

	//Old function
    public static function resolveSQLJoinType(&$ct,$listing_id, $typeparams, $option_list)
	{
        if(count($typeparams)<1)
			return 'table not specified';

		if(count($typeparams)<2)
			return 'field or layout not specified';

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
		
		//Old method - slow
		$result = JHTML::_('ESSQLJoinView.render',$listing_id,$esr_table,$esr_field,$esr_filter,$ct->Languages->Postfix,'');

		//New method - fast and secure
		$join_ct = new CT;
		$join_ct->getTable($typeparams[0]);
		$row  = $join_ct->Table->loadRecord($listing_id);

		$twig = new TwigProcessor($join_ct, '{% autoescape false %}'.$result.'{% endautoescape %}');
		return $twig->process($row);
	}
}
