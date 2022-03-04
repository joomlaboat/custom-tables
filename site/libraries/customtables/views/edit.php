<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2021. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access

defined('_JEXEC') or die('Restricted access');

use CustomTables\TwigProcessor;

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR
	.'tagprocessor'.DIRECTORY_SEPARATOR.'edittags.php');

function CTViewEdit(&$ct, $row, &$pagelayout, $BlockExternalVars,$formLink,$formName)
{
	jimport('joomla.html.html.bootstrap');

	$ct->loadJSAndCSS();

	if (!$BlockExternalVars and $ct->Env->menu_params->get( 'show_page_heading', 1 ) )
	{
		echo '<div class="page-header'.LayoutProcessor::htmlEscape($ct->Env->menu_params->get('pageclass_sfx')).'"><h2 itemprop="headline">'
			.JoomlaBasicMisc::JTextExtended($ct->Env->menu_params->get( 'page_title' )).'</h2></div>';
	}

	if(isset($row['listing_id']))
		$listing_id=(int)$row['listing_id'];
	else
		$listing_id=0;

	echo '<form action="'.$formLink.'" method="post" name="'.$formName.'" id="'.$formName.'" class="form-validate form-horizontal well" '
		.'data-tableid="'.$ct->Table->tableid.'" data-recordid="'.$listing_id.'" '
		.'data-version='.$ct->Env->version.'>';

	echo ($ct->Env->version < 4 ? '<fieldset>' : '<fieldset class="options-form">');

	//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.

	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
	$LayoutProc=new LayoutProcessor($ct,$pagelayout);

	//Better to run tag processor before rendering form edit elements because of IF statments that can exclude the part of the layout that contains form fields.
	$pagelayout = $LayoutProc->fillLayout($row,null,'||',false,true);

	$ct->isEditForm = true; //This changes inputbox prefix
	tagProcessor_Edit::process($ct,$pagelayout,$row);
	
	$twig = new TwigProcessor($ct, $pagelayout);
	$pagelayout = $twig->process($row);
	
	if((int)$ct->Env->menu_params->get( 'allowcontentplugins' )==1)
		LayoutProcessor::applyContentPlugins($pagelayout);

	echo $pagelayout;

	$returnto='';

	if($ct->Env->jinput->get('returnto','','BASE64'))
		$returnto=base64_decode($ct->Env->jinput->get('returnto','','BASE64'));
	elseif($ct->Env->menu_params->get( 'returnto' ))
		$returnto=$ct->Env->menu_params->get( 'returnto' );

	$encoded_returnto=base64_encode ($returnto);

	if($listing_id==0)
	{
		$publishstatus=$ct->Env->menu_params->get( 'publishstatus' );
		echo '<input type="hidden" name="published" value="'.(int)$publishstatus.'" />';
	}

	echo '
	<input type="hidden" name="task" id="task" value="save" />
	<input type="hidden" name="returnto" id="returnto" value="'.$encoded_returnto.'" />
	<input type="hidden" name="listing_id" id="listing_id" value="'.$listing_id.'" />'
	.($ct->Env->jinput->getCmd('tmpl','') != '' ? '<input type="hidden" name="tmpl" value="'.$ct->Env->jinput->getCmd('tmpl','').'" />' : '')
	.JHtml::_('form.token')
	.'</fieldset>
</form>';

	if($ct->Env->isModal)
		die;
}


//Unused
function CTViewEdit_Script()
{
	$script = '

window.setInterval(function(){var r;try{r=window.XMLHttpRequest?new XMLHttpRequest():new ActiveXObject("Microsoft.XMLHTTP")}catch(e){}if(r){r.open("GET","/index.php?option=com_ajax&format=json",true);r.send(null)}},840000);

jQuery(function($) {
			 $(\'.hasTip\').each(function() {
				var title = $(this).attr(\'title\');
				alert(title);
				if (title) {
					var parts = title.split(\'::\', 2);
					var mtelement = document.id(this);
					mtelement.store(\'tip:title\', parts[0]);
					mtelement.store(\'tip:text\', parts[1]);
				}
			});
			var JTooltips = new Tips($(\'.hasTip\').get(), {"maxTitleChars": 50,"fixed": false});
		});

jQuery(document).ready(function(){
	jQuery(\'.hasTooltip\').tooltip({"html": true,"container": "body"});
});

jQuery.noConflict()
  </script>
  <script type="text/javascript">
    (function() {
      Joomla.JText.load({"JLIB_FORM_FIELD_INVALID":"Invalid field:&#160"});
    })();
';
}