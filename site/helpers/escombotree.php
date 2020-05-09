<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class JHTMLESComboTree
{
        static function render($prefix,$establename, $esfieldname, $optionname, $langpostfix, $value,$cssstyle="",$onchange="",$where="",$innerjoin=false,$isRequired=false,$requirementdepth=0)
        {
				$jinput = JFactory::getApplication()->input;

				require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'combotreeloader.php');

				$MyESDynCombo=new ESDynamicComboTree();
				$MyESDynCombo->initialize($establename,$esfieldname,$optionname,$prefix);
				$MyESDynCombo->cssstyle=$cssstyle;
				$MyESDynCombo->onchange=$onchange;
				$MyESDynCombo->innerjoin=$innerjoin;
				$MyESDynCombo->langpostfix=$langpostfix;
				$MyESDynCombo->isRequired=$isRequired;
				$MyESDynCombo->requirementdepth=$requirementdepth;

				$MyESDynCombo->where=$where;

						$filterwhere='';
						$filterwherearr=array();

						$urlwhere='';
						$urlwherearr=array();

				//Set current value (count only firet one in case multi-value provided)
				$value_arr=explode(',',$value);
				if(count($value_arr)>0)
				{
						if(count($value_arr)<2)
								$value_arr[1]='';
						$i=1;
						$option_arr=explode('.',$value_arr[1]);
						$parent_arr=explode('.',$optionname);
						if(count($option_arr)>count($parent_arr))
						{

								for($p=count($parent_arr);$p<count($option_arr);$p++)
								{
										$opt=$option_arr[$p];
										if($opt=='')
											break;

                                                                                $jinput->set($MyESDynCombo->ObjectName.'_'.$i, $opt);
										$i++;
								}
						}
				}

				$html_=
				'<div id="'.$MyESDynCombo->ObjectName.'" name="'.$MyESDynCombo->ObjectName.'">'
				.$MyESDynCombo->renderComboBox($filterwhere, $urlwhere, $filterwherearr, $urlwherearr, ($requirementdepth==1 ? true : false ),$value)
				.'</div>';


				return $html_;
        }

}
