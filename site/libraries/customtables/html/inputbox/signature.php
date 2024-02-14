<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('ABSPATH')) {
	die('Restricted access');
}

class InputBox_signature extends BaseInputBox
{
	function __construct(CT &$ct, Field $field, ?array $row, array $option_list = [], array $attributes = [])
	{
		parent::__construct($ct, $field, $row, $option_list, $attributes);
	}

	function render(?string $value, ?string $defaultValue): string
	{
		$elementId = $this->attributes['id'];

		$width = (($this->field->params !== null and count($this->field->params) > 0) ? $this->field->params[0] ?? 300 : 300);
		$height = (($this->field->params !== null and count($this->field->params) > 1) ? $this->field->params[1] ?? 150 : 150);
		$format = (($this->field->params !== null and count($this->field->params) > 3) ? $this->field->params[3] ?? 'svg' : 'svg');
		if ($format == 'svg-db')
			$format = 'svg';

		//https://github.com/szimek/signature_pad/blob/gh-pages/js/app.js
		//https://stackoverflow.com/questions/46514484/send-signature-pad-to-php-post-method
		//		class="wrapper"
		$attributes = $this->attributes;
		$attributes['id'] = $elementId . '_canvas';
		$attributes['name'] = null;
		self::addCSSClass($attributes, 'uneditable-input');
		$attributes['style'] = 'background-color: #ffffff;padding:0;width:' . $width . 'px;height:' . $height . 'px;';

		$result = '
<div class="ctSignature_flexrow" style="width:' . $width . 'px;height:' . $height . 'px;padding:0;">
	<div style="position:relative;display: flex;padding:0;">
		<canvas ' . self::attributes2String($attributes) . ' ></canvas>
		<div class="ctSignature_clear"><button type="button" class="close" id="' . $elementId . '_clear">×</button></div>';
		$result .= '
	</div>
</div>

<input type="text" style="display:none;" name="' . $elementId . '" id="' . $elementId . '" value="" '
			. 'data-type="' . $this->field->type . '" '
			. 'data-label="' . $this->field->title . '" '
			. 'data-valuerule="' . str_replace('"', '&quot;', $this->field->valuerule) . '" '
			. 'data-valuerulecaption="' . str_replace('"', '&quot;', $this->field->valuerulecaption) . '" >';

		$ctInputbox_signature = $elementId . '",' . ((int)$width) . ',' . ((int)$height) . ',"' . $format;
		$result .= '
<script>
	ctInputbox_signature("' . $ctInputbox_signature . '")
</script>';
		return $result;
	}
}