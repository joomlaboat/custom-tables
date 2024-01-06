<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2024. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
	die('Restricted access');
}

use CustomTables\database;
use CustomTables\MySQLWhereClause;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use CustomTables\CT;

$theme = 'eclipse';

if (defined('_JEXEC')) {
	$document = Factory::getDocument();

	$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/ajax.js"></script>');

	$version_object = new Version;
	$version = (int)$version_object->getShortVersion();

	$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_common.js"></script>');

	if ($version < 4)
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams.js"></script>');
	else
		$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/typeparams_j4.js"></script>');

	$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/layoutwizard.js"></script>');
	$document->addCustomTag('<script src="' . CUSTOMTABLES_MEDIA_WEBPATH . 'js/layouteditor.js"></script>');
	$document->addCustomTag('<link href="' . CUSTOMTABLES_MEDIA_WEBPATH . 'css/layouteditor.css" rel="stylesheet">');

	$document->addCustomTag('<link rel="stylesheet" href="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/lib/codemirror.css">');
	$document->addCustomTag('<link rel="stylesheet" href="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/show-hint.css">');

	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/lib/codemirror.js"></script>');
	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/addon/mode/overlay.js"></script>');

	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/show-hint.js"></script>');
	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/xml-hint.js"></script>');
	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/html-hint.js"></script>');
	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/mode/xml/xml.js"></script>');
	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/mode/javascript/javascript.js"></script>');
	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/mode/css/css.js"></script>');
	$document->addCustomTag('<script src="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/mode/htmlmixed/htmlmixed.js"></script>');
	$document->addCustomTag('<link rel="stylesheet" href="' . Uri::root(true) . '/components/com_customtables/libraries/codemirror/theme/' . $theme . '.css">');

	if ($version >= 4)
		$document->addCustomTag('<link rel="stylesheet" href="' . Uri::root(true) . '/media/system/css/fields/switcher.css">');
}

function renderEditor($textareacode, $textareaid, $typeboxid, $textareatabid, &$onPageLoads)
{
	$ct = new CT;

	$index = count($onPageLoads);
	$result = '<div class="customlayoutform layouteditorbox">' . $textareacode . '</div><div id="' . $textareatabid . '"></div>';

	$code = '
		joomlaVersion =' . $ct->Env->version . ';
		
		text_areas.push(["' . $textareaid . '",' . $index . ']);
        codemirror_editors[' . $index . '] = CodeMirror.fromTextArea(document.getElementById("' . $textareaid . '"), {
          mode: "layouteditor",
	   lineNumbers: true,
        lineWrapping: true,
		theme: "eclipse",
          extraKeys: {"Ctrl-Space": "autocomplete"}

        });
	      var charWidth' . $index . ' = codemirror_editors[' . $index . '].defaultCharWidth(), basePadding = 4;
      codemirror_editors[' . $index . '].on("renderLine", function(cm, line, elt) {
        var off = CodeMirror.countColumn(line.text, null, cm.getOption("tabSize")) * charWidth' . $index . ';
        elt.style.textIndent = "-" + off + "px";
        elt.style.paddingLeft = (basePadding + off) + "px";
      });

		loadTagParams("' . $typeboxid . '","' . $textareatabid . '","Joomla");
		
	';
	if (count($onPageLoads) == 0) {
		$languages = getKnownLanguages();

		$code .= '
			
			languages=[' . $languages . '];
			
			';


		if ($ct->Env->advancedTagProcessor) {
			$code .= '
			proversion=true;
	';
		}

		$code .= '
				loadFields("jform_tableid","fieldWizardBox","Joomla");
				loadLayout(' . $ct->Env->version . ');
				
				addExtraEvents();
			';

	}

	$onPageLoads[] = $code;
	return $result;
}

function getKnownLanguages(): string
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

function render_onPageLoads($onPageLoads, $version)
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

	$result_js = '
	

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

	if (defined('_JEXEC')) {
		$document = Factory::getDocument();
		$document->addCustomTag('<script>' . $result_js . '</script>');
	}
	return $result;

}
