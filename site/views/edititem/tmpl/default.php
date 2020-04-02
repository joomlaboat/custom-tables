<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @version 1.2.6
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'esinputbox.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tagprocessor'.DIRECTORY_SEPARATOR.'edittags.php');

require_once('includes'.DIRECTORY_SEPARATOR.'editmisc.php');

jimport('joomla.html.html.bootstrap');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidator');
JHtml::_('behavior.calendar');
JHtml::_('bootstrap.popover');

if (!$this->Model->BlockExternalVars and $this->Model->params->get( 'show_page_heading', 1 ) ) : ?>
<div class="page-header<?php echo $this->escape($this->Model->params->get('pageclass_sfx')); ?>">
	<h2 itemprop="headline">
		<?php echo JoomlaBasicMisc::JTextExtended($this->Model->params->get( 'page_title' ));

			if(JFactory::getApplication()->input->get('listing_id',0,'INT')!=0)
				echo ' - '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_EDIT' );
			else
				echo ' - '.JoomlaBasicMisc::JTextExtended('COM_CUSTOMTABLES_ADD' );
		 ?>
	</h2>
</div>
<?php endif;


$fieldstosave='';

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

	$document = JFactory::getDocument();
	$document->addScript(JURI::root(true).'/components/com_customtables/js/edit_113.js?v=1.2.6');
	$document->addScript(JURI::root(true).'/components/com_customtables/js/esmulti.js?v=1.2.6');

	$esinputbox = new ESInputBox;
	$esinputbox->es=$this->Model->es;
	$esinputbox->LanguageList=$this->Model->LanguageList;
	$esinputbox->langpostfix=$this->Model->langpostfix;
	$esinputbox->establename=$this->Model->establename;
	$esinputbox->estableid=$this->Model->estableid;
	$esinputbox->requiredlabel=$this->params->get( 'requiredlabel' );






	//$lang=JFactory::getApplication()->input->getInt('lang',0);

	$WebsiteRoot=JURI::root(true);
	if($WebsiteRoot=='' or $WebsiteRoot[strlen($WebsiteRoot)-1]!='/') //Root must have slash / in the end
		$WebsiteRoot.='/';

	$theLink=$WebsiteRoot.'index.php?option=com_customtables&amp;view=edititem'.($this->Model->Itemid!=0 ? '&amp;Itemid='.$this->Model->Itemid : '');//.'&amp;lang='.$lang;

	//</form> enctype="multipart/form-data">
?>

<form action="<?php echo $theLink; ?>" method="post" onsubmit="return checkRequiredFields();" name="eseditForm" id="eseditForm" class="form-validate form-horizontal well">



<fieldset>


<?php


					//Calendars of the child should be built again, because when Dom was ready they didn't exist yet.
					$calendars=array();


						if(isset($this->row['id']))
							$listing_id=(int)$this->row['id'];
						else
							$listing_id=0;
						
						require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'layout.php');
						$LayoutProc=new LayoutProcessor;
						$LayoutProc->Model=$this->Model;
						$LayoutProc->layout=$this->Model->pagelayout;
						$this->Model->pagelayout=$LayoutProc->fillLayout($this->row,null,'','||',false,true);
						
						tagProcessor_Edit::process($this->Model,$this->Model->pagelayout,$listing_id);
						

						$replaceitecode=JoomlaBasicMisc::generateRandomString();
						$items_to_replace=array();
						renderFields($this->row,$this->Model,$this->Model->langpostfix,0,$esinputbox,$calendars,'',$fieldstosave,$replaceitecode,$items_to_replace);

						
						//$LayoutProc->layout=$this->Model->pagelayout;
						//$this->Model->pagelayout=$LayoutProc->fillLayout($this->row,null,'','||',false,true);

						foreach($items_to_replace as $item)
							$this->Model->pagelayout=str_replace($item[0],$item[1],$this->Model->pagelayout);

						if($this->params->get( 'allowcontentplugins' )==1)
							LayoutProcessor::applyContentPlugins($this->Model->pagelayout);

						echo $this->Model->pagelayout;


		$returnto='';

		if(JFactory::getApplication()->input->get('returnto','','BASE64'))
			$returnto=base64_decode(JFactory::getApplication()->input->get('returnto','','BASE64'));
		elseif($this->params->get( 'returnto' ))
			$returnto=$this->params->get( 'returnto' );

		if($this->Model->id!=0)
		{
			if($returnto!='')
				$returnto=str_replace('{id}',$this->Model->id,$returnto);
		}

	$encoded_returnto=base64_encode ($returnto);

	if(!isset($this->row['id']) or $this->row['id']==0)
	{
		$this->params = JComponentHelper::getParams( 'com_customtables' );
		$publishstatus=$this->params->get( 'publishstatus' );
		echo '<input type="hidden" name="published" value="'.(int)$publishstatus.'" />';
	}

	?>
	<input type="hidden" name="task" id="task" value="save" />
	<input type="hidden" name="returnto" id="returnto" value="<?php echo $encoded_returnto; ?>" />
	<input type="hidden" name="listing_id" id="listing_id" value="<?php echo $this->Model->id; ?>" />
	<?php if(JFactory::getApplication()->input->get('tmpl','','CMD')!='') : ?>
	<input type="hidden" name="tmpl" value="<?php echo JFactory::getApplication()->input->getCmd('tmpl',''); ?>" />
	<?php endif; ?>

    <input type="hidden" name="submitbutton" value="<?php echo $this->Model->submitbuttons; ?>" />
	</fieldset>
</form>

	<?php
	$document->addScript(JURI::root(true)."/media/system/js/mootools-more.js");
