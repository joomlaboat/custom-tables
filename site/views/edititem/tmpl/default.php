<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2020. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'edittags.php');

jimport('joomla.html.html.bootstrap');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');
JHtml::_('behavior.calendar');
JHtml::_('bootstrap.popover');

$document = JFactory::getDocument();
$document->addScript(JURI::root(true).'/components/com_customtables/js/edit_227.js?v=2.2.3');
$document->addScript(JURI::root(true).'/components/com_customtables/js/esmulti.js?v=2.2.3');

if (!$this->Model->BlockExternalVars and $this->Model->params->get( 'show_page_heading', 1 ) ) : ?>
<div class="page-header<?php echo $this->escape($this->Model->params->get('pageclass_sfx')); ?>">
	<h2 itemprop="headline">
		<?php echo JoomlaBasicMisc::JTextExtended($this->Model->params->get( 'page_title' ));
/*
			if(JFactory::getApplication()->input->get('listing_id',0,'INT')!=0)
				echo ' - '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT' );
			else
				echo ' - '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ADD' );
			*/
		 ?>
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

<fieldset>
<?php
	//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
	//$calendars=array();

	if(isset($this->row['listing_id']))
		$listing_id=(int)$this->row['listing_id'];
	else
		$listing_id=0;
						
	require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
	$LayoutProc=new LayoutProcessor;
	$LayoutProc->Model=$this->Model;
	$LayoutProc->layout=$this->Model->pagelayout;
						
	//Better to run tag processor before rendering form edit elements because of IF statments that can exclude the part of the layout that contains form fields.
	$this->Model->pagelayout=$LayoutProc->fillLayout($this->row,null,'','||',false,true);
						
	tagProcessor_Edit::process($this->Model,$this->Model->pagelayout,$this->row,'comes_');
	
	if($this->params->get( 'allowcontentplugins' )==1)
		LayoutProcessor::applyContentPlugins($this->Model->pagelayout);

	echo $this->Model->pagelayout;

	$returnto='';

	if(JFactory::getApplication()->input->get('returnto','','BASE64'))
		$returnto=base64_decode(JFactory::getApplication()->input->get('returnto','','BASE64'));
	elseif($this->params->get( 'returnto' ))
		$returnto=$this->params->get( 'returnto' );

	//if($this->Model->id!=0 and $returnto!='')
	//$returnto=str_replace('{id}',$this->Model->id,$returnto);//it should be done in layout processing, probably does

	$encoded_returnto=base64_encode ($returnto);

	if($listing_id==0)
	{
		$this->params = JComponentHelper::getParams( 'com_customtables' );
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

    <input type="hidden" name="submitbutton" value="<?php echo $this->Model->submitbuttons; ?>" />
	<?php echo JHtml::_('form.token'); ?>
	</fieldset>
</form>
