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

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'edittags.php');

jimport('joomla.html.html.bootstrap');

$document = JFactory::getDocument();

$document->addScript(JURI::root(true).'/components/com_customtables/js/base64.js');
$document->addScript(JURI::root(true).'/components/com_customtables/js/edit_234.js');
$document->addScript(JURI::root(true).'/components/com_customtables/js/esmulti.js');

$document->addCustomTag('<link href="'.JURI::root(true).'/components/com_customtables/css/style.css" type="text/css" rel="stylesheet" >');
$document->addCustomTag('<link rel="stylesheet" href="'.JURI::root(true).'/media/system/css/fields/switcher.css">');

if (!$this->BlockExternalVars and $this->ct->Env->menu_params->get( 'show_page_heading', 1 ) ) : ?>
<div class="page-header<?php echo $this->escape($this->ct->Env->menu_params->get('pageclass_sfx')); ?>">
	<h2 itemprop="headline">
		<?php echo JoomlaBasicMisc::JTextExtended($this->ct->Env->menu_params->get( 'page_title' )); ?>
	</h2>
</div>
<?php endif;

//------------------------------------------------------------------------
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

?>
<form action="<?php echo $this->formLink; ?>" method="post" name="<?php echo $this->formName; ?>" id="<?php echo $this->formName; ?>" class="<?php echo $this->formClass; ?>"<?php echo $this->formAttribute; ?>>


<?php

	echo ($this->ct->Env->version < 4 ? '<fieldset>' : '<fieldset class="options-form">');

	//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
	//$calendars=array();

	if(isset($this->row['listing_id']))
		$listing_id=(int)$this->row['listing_id'];
	else
		$listing_id=0;
						
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
	$LayoutProc=new LayoutProcessor($this->ct,$this->pagelayout);

	//Better to run tag processor before rendering form edit elements because of IF statments that can exclude the part of the layout that contains form fields.
	$this->pagelayout = $LayoutProc->fillLayout($this->row,null,'||',false,true);

	$this->ct->isEditForm = true; //This changes inputbox prefix
	tagProcessor_Edit::process($this->ct,$this->pagelayout,$this->row);
	
	$twig = new TwigProcessor($this->ct, $this->pagelayout);
	$this->pagelayout = $twig->process($this->row);
	
	if($this->params->get( 'allowcontentplugins' )==1)
		LayoutProcessor::applyContentPlugins($this->pagelayout);

	echo $this->pagelayout;

	$returnto='';

	if(JFactory::getApplication()->input->get('returnto','','BASE64'))
		$returnto=base64_decode(JFactory::getApplication()->input->get('returnto','','BASE64'));
	elseif($this->params->get( 'returnto' ))
		$returnto=$this->params->get( 'returnto' );

	$encoded_returnto=base64_encode ($returnto);

	if($listing_id==0)
	{
		$publishstatus=$this->params->get( 'publishstatus' );
		echo '<input type="hidden" name="published" value="'.(int)$publishstatus.'" />';
	}

	?>
	<input type="hidden" name="task" id="task" value="save" />
	<input type="hidden" name="returnto" id="returnto" value="<?php echo $encoded_returnto; ?>" />
	<input type="hidden" name="listing_id" id="listing_id" value="<?php echo $listing_id; ?>" />
	<?php if(JFactory::getApplication()->input->get('tmpl','','CMD')!='') : ?>
	<input type="hidden" name="tmpl" value="<?php echo JFactory::getApplication()->input->getCmd('tmpl',''); ?>" />
	<?php endif; ?>

	<?php echo JHtml::_('form.token'); ?>
	</fieldset>
</form>
