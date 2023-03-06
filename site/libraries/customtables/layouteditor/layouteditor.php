<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023 Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// No direct access to this file
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use CustomTables\CT;

$theme = 'eclipse';
$document = Factory::getDocument();

$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/ajax.js"></script>');

$version_object = new Version;
$version = (int)$version_object->getShortVersion();

$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/typeparams_common.js"></script>');

$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/typeparams_common.js"></script>');
if ($version < 4)
    $document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/typeparams.js"></script>');
else
    $document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/typeparams_j4.js"></script>');

$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/layoutwizard.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/js/layouteditor.js"></script>');
$document->addCustomTag('<link href="' . JURI::root(true) . '/components/com_customtables/libraries/customtables/media/css/layouteditor.css" rel="stylesheet">');

$document->addCustomTag('<link rel="stylesheet" href="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/lib/codemirror.css">');
$document->addCustomTag('<link rel="stylesheet" href="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/show-hint.css">');

$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/lib/codemirror.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/addon/mode/overlay.js"></script>');

$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/show-hint.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/xml-hint.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/addon/hint/html-hint.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/mode/xml/xml.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/mode/javascript/javascript.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/mode/css/css.js"></script>');
$document->addCustomTag('<script src="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/mode/htmlmixed/htmlmixed.js"></script>');
$document->addCustomTag('<link rel="stylesheet" href="' . JURI::root(true) . '/components/com_customtables/libraries/codemirror/theme/' . $theme . '.css">');

if ($version >= 4)
    $document->addCustomTag('<link rel="stylesheet" href="' . JURI::root(true) . '/media/system/css/fields/switcher.css">');

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

		loadTagParams("' . $typeboxid . '","' . $textareatabid . '");
		
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
				loadFields("jform_tableid","fieldWizardBox");
				loadLayout(' . $ct->Env->version . ');
				
				addExtraEvents();
			';

    }

    $onPageLoads[] = $code;
    return $result;
}

function getKnownLanguages()
{
    $db = Factory::getDbo();
    $db->setQuery('SELECT sef, title_native FROM #__languages ORDER BY sef');

    $list = array();
    $rows = $db->loadObjectList();
    foreach ($rows as $row) {
        $list[] = '["' . $row->sef . '","' . $row->title_native . '"]';
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
	<script>

	joomlaVersion =' . $version . ';
	define_cmLayoutEditor();

	let text_areas=[];
    window.onload = function()
	{
		//changeBackIcon();
		loadTypes_silent("ct_processMessageBox");

	' . implode('', $onPageLoads) . '
		adjustEditorHeight();

    };
	
	setTimeout(addTabExtraEvents, 500);

    </script>';

    $document = Factory::getDocument();
    $document->addCustomTag($result_js);

    return $result;

}
