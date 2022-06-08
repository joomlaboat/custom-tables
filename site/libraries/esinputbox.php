<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;
use CustomTables\Inputbox;
use CustomTables\TwigProcessor;
use Joomla\CMS\Factory;

class ESInputBox
{
    var string $requiredlabel = '';
    var CustomTables\CT $ct;
    var $jinput;

    function __construct(CustomTables\CT &$ct)
    {
        $this->ct = $ct;
        $this->jinput = Factory::getApplication()->input;
        $this->requiredlabel = 'COM_CUSTOMTABLES_REQUIREDLABEL';
    }

    function renderFieldBox(array &$fieldrow, ?array &$row, array $option_list): string
    {
        $Inputbox = new Inputbox($this->ct, $fieldrow, $option_list, false);

        $realFieldName = $fieldrow['realfieldname'];

        if ($this->ct->Env->frmt == 'json') {
            //This is the field options for JSON output

            $shortFieldObject = Fields::shortFieldObject($fieldrow, ($row[$realFieldName] ?? null), $option_list);

            if ($fieldrow['type'] == 'sqljoin') {
                $typeparams = JoomlaBasicMisc::csv_explode(',', $fieldrow['typeparams'], '"', false);

                if (isset($option_list[2]) and $option_list[2] != '')
                    $typeparams[2] = $option_list[2];//Overwrites field type filter parameter.

                $typeparams[6] = 'json'; // to get the Object instead of the HTML element.

                $attributes_ = '';
                $value = '';
                $place_holder = '';
                $class = '';

                $list_of_values = JHTML::_('ESSQLJoin.render',
                    $typeparams,
                    $value,
                    false,
                    $this->ct->Languages->Postfix,
                    $this->ct->Env->field_input_prefix . $fieldrow['fieldname'],
                    $place_holder,
                    $class,
                    $attributes_);

                $shortFieldObject['value_options'] = $list_of_values;
            }

            return $shortFieldObject;
        }

        $value = '';

        if ($this->ct->isRecordNull($row)) {
            $value = $this->ct->Env->jinput->getString($realFieldName);
            if ($value == '')
                $value = $Inputbox->getWhereParameter($realFieldName);

            if ($value == '') {
                $value = $fieldrow['defaultvalue'];

                //Process default value, not processing PHP tag
                if ($value != '') {
                    if ($this->ct->Env->legacysupport) {
                        tagProcessor_General::process($this->ct, $value, $row, '', 1);
                        tagProcessor_Item::process($this->ct, $row, $value, '', '', 0);
                        tagProcessor_If::process($this->ct, $value, $row, '', 0);
                        tagProcessor_Page::process($this->ct, $value);
                        tagProcessor_Value::processValues($this->ct, $row, $value, '[]');
                    }

                    $twig = new TwigProcessor($this->ct, $value);
                    $value = $twig->process($row);

                    if ($value != '') {
                        if ($this->ct->Params->allowContentPlugins)
                            JoomlaBasicMisc::applyContentPlugins($htmlresult);

                        if ($fieldrow['type'] == 'alias') {
                            $listing_id = $row[$this->ct->Table->realidfieldname] ?? 0;
                            $value = $this->ct->Table->prepare_alias_type_value($listing_id, $value, $fieldrow['realfieldname']);
                        }
                    }
                }
            }
        } else {
            if ($fieldrow['type'] != 'multilangstring' and $fieldrow['type'] != 'multilangtext' and $fieldrow['type'] != 'multilangarticle') {
                $value = $row[$realFieldName] ?? null;
            }
        }

        return $Inputbox->render($value, $row);
    }
}
