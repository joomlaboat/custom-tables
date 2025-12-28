<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x/6.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// No direct access to this file
defined('_JEXEC') or die();

use Joomla\CMS\Factory;

class LayoutEditor
{
	var string $theme;

	function __construct()
	{
		$this->theme = 'eclipse';

		if (defined('_JEXEC')) {
			$document = Factory::getApplication()->getDocument();
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_common.js"></script>');

			if (!CUSTOMTABLES_JOOMLA_MIN_4)
				$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams.js"></script>');
			else
				$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_j4.js"></script>');

			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/layoutwizard.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/layouteditor.js"></script>');
			$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/edit.js"></script>');
			$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/layouteditor.css" rel="stylesheet">');

			$document->addCustomTag('<link rel="stylesheet" href="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/lib/codemirror.css">');
			$document->addCustomTag('<link rel="stylesheet" href="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/addon/hint/show-hint.css">');

			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/lib/codemirror.js"></script>');
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/addon/mode/overlay.js"></script>');

			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/addon/hint/show-hint.js"></script>');
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/addon/hint/xml-hint.js"></script>');
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/addon/hint/html-hint.js"></script>');
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/mode/xml/xml.js"></script>');
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/mode/javascript/javascript.js"></script>');
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/mode/css/css.js"></script>');
			$document->addCustomTag('<script src="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/mode/htmlmixed/htmlmixed.js"></script>');
			$document->addCustomTag('<link rel="stylesheet" href="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/theme/' . $this->theme . '.css">');
			$document->addCustomTag('<link rel="stylesheet" href="' . common::UriRoot(true) . '/components/com_customtables/libraries/codemirror/theme/material-darker.css">');

			if (CUSTOMTABLES_JOOMLA_MIN_4)
				$document->addCustomTag('<link rel="stylesheet" href="' . common::UriRoot(true) . '/media/system/css/fields/switcher.css">');
		}
	}

	public function renderEditor(string $textAreaCode, string $textAreaId, string $typeBoxId, string $textAreaTabId, array &$onPageLoads, string $mode = 'layouteditor'): string
	{
		$ct = new CT([], true);
		$index = count($onPageLoads);
		$result = '<div class="customlayoutform layouteditorbox">' . $textAreaCode . '</div><div id="' . $textAreaTabId . '"></div>';
		$code = '';

		if (count($onPageLoads) == 0) {

			if (CUSTOMTABLES_JOOMLA_MIN_4)
				$code .= PHP_EOL . 'joomlaVersion = 4;//layouteditor.php:80' . PHP_EOL;
			else
				$code .= PHP_EOL . 'joomlaVersion = 3;//layouteditor.php:82' . PHP_EOL;

			$languages = $this->getKnownLanguages();
			$code .= PHP_EOL . 'languages=[' . $languages . '];' . PHP_EOL;

			$custom_fields = $this->getKnownCustomFields();
			$code .= PHP_EOL . 'custom_fields=[' . $custom_fields . '];' . PHP_EOL;

			if ($ct->Env->advancedTagProcessor)
				$code .= PHP_EOL . 'proversion=true;' . PHP_EOL;
		}

		$code .= '
        
        // Detect if dark mode is enabled
        if(window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches)
            codemirror_theme = "material-darker";
        
		text_areas.push(["' . $textAreaId . '",' . $index . ']);
        codemirror_editors[' . $index . '] = CodeMirror.fromTextArea(document.getElementById("' . $textAreaId . '"), {
            mode: "' . $mode . '",
            lineNumbers: true,
            lineWrapping: true,
            theme: codemirror_theme,
            extraKeys: {"Ctrl-Space": "autocomplete"}
        });
        
        var charWidth' . $index . ' = codemirror_editors[' . $index . '].defaultCharWidth(), basePadding = 4;
        codemirror_editors[' . $index . '].on("renderLine", function(cm, line, elt) {
            var off = CodeMirror.countColumn(line.text, null, cm.getOption("tabSize")) * charWidth' . $index . ';
            elt.style.textIndent = "-" + off + "px";
            elt.style.paddingLeft = (basePadding + off) + "px";
        });

		loadTagParams("' . $typeBoxId . '","' . $textAreaTabId . '","Joomla");
';
		if (count($onPageLoads) == 0) {
			$code .= PHP_EOL . 'loadFields("jform_tableid","fieldWizardBox","Joomla");'
				. PHP_EOL . 'loadLayout(' . (CUSTOMTABLES_JOOMLA_MIN_4 ? '4.0' : '3.0') . ');'
				. PHP_EOL . 'addExtraEvents();' . PHP_EOL;
		}
		$onPageLoads[] = $code;
		return $result;
	}

	protected function getKnownLanguages(): string
	{
		$list = array();

		if (defined('_JEXEC')) {

			$whereClause = new MySQLWhereClause();
			$rows = database::loadObjectList('#__languages', ['sef', 'title_native'], $whereClause, 'sef');

			foreach ($rows as $row)
				$list[] = '["' . $row->sef . '","' . $row->title_native . '"]';
		} elseif (defined('_JEXEC')) {
			$list[] = '[en,"English"]';
		}

		return implode(',', $list);
	}

	private function getKnownCustomFields(): string
	{
		$list = array();

		if (defined('_JEXEC')) {

			$whereClause = new MySQLWhereClause();
			$rows = database::loadObjectList('#__fields', ['context', 'title', 'name'], $whereClause, 'name');

			foreach ($rows as $row)
				$list[] = '["' . $row->context . '","' . $row->title . '","' . $row->name . '"]';
		} elseif (defined('_JEXEC')) {
			return '';
		}

		return implode(',', $list);
	}

	public function render_onPageLoads($onPageLoads): string
	{
		$result = '
<div id="layouteditor_Modal" class="layouteditor_modal">

  <!-- Modal content -->
  <div class="layouteditor_modal-content" id="layouteditor_modalbox">
    <span class="layouteditor_close">&times;</span>
	<div id="layouteditor_modal_content_box">
	</div>
  </div>

</div>
		';

		if (CUSTOMTABLES_JOOMLA_MIN_4)
			$version = 4;
		else
			$version = 3;

		$result_js = '
		
	if (typeof window.CTEditHelper === "undefined") {
		window.CTEditHelper = new CustomTablesEdit("Joomla",' . (explode('.', CUSTOMTABLES_JOOMLA_VERSION)[0]) . ',null,"' . common::UriRoot(false, true) . '");
	}
		
	joomlaVersion =' . $version . ';
	define_cmLayoutEditor();

	let text_areas=[];
    window.onload = function()
	{
		//changeBackIcon();
		loadTypes_silent("Joomla");

	' . implode('', $onPageLoads) . '
		adjustEditorHeight();
    };
	
	setTimeout(addTabExtraEvents, 500);
    ';

		$document = Factory::getApplication()->getDocument();
		$document->addCustomTag('<script>' . $result_js . '</script>');

		return $result;
	}
}