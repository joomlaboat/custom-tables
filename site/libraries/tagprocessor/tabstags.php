<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;

class tagProcessor_Tabs
{
	public static function process(&$htmlresult)
    {
		$version_object = new Version;
		$version = (int)$version_object->getShortVersion();
		
		if($version < 4)
			tagProcessor_Tabs::process_3($htmlresult);
		else
			tagProcessor_Tabs::process_4($htmlresult);
	}
	
	public static function process_4(&$htmlresult)
    {
			$options=array();
            $fList=JoomlaBasicMisc::getListToReplace('tab',$options,$htmlresult,'{}');

            $i=0;
            $objname='CTtab';

            foreach($fList as $fItem)
            {
                $option=$options[$i];
                $name=JoomlaBasicMisc::slugify($option);

                $tab='';
                if($i==0)
					$tab=HTMLHelper::_('uitab.startTabSet', $objname, ['active' => $name, 'recall' => true, 'breakpoint' => 768]);
                else
					$tab=HTMLHelper::_('uitab.endTab');

				$tab.=HTMLHelper::_('uitab.addTab', $objname, $name, $option);

                $htmlresult=str_replace($fItem,$tab,$htmlresult);
				$i++;
            }

            $endtab=HTMLHelper::_('uitab.endTab');
			$endtab.=HTMLHelper::_('uitab.endTabSet');
            
            $htmlresult=str_replace('{/tabs}',$endtab,$htmlresult);
	}
	
    public static function process_3(&$htmlresult)
    {
			$options=array();
            $fList=JoomlaBasicMisc::getListToReplace('tab',$options,$htmlresult,'{}');

            if(count($fList))
            {
                $document = JFactory::getDocument();
        		$document->addCustomTag('
                <script>
                jQuery(function($){
                var tabs$ = $(".nav-tabs a");

$( window ).on("hashchange", function() {
    var hash = window.location.hash, // get current hash
        menu_item$ = tabs$.filter(\'[href="\' + hash + \'"]\'); // get the menu element

    menu_item$.tab("show"); // call bootstrap to show the tab
}).trigger("hashchange");



  var hash = window.location.hash;
  hash && $(\'ul.nav a[href="\' + hash + \'"]\').tab(\'show\');

  $(\'.nav-tabs a\').click(function (e) {
    $(this).tab(\'show\');
    var scrollmem = $(\'body\').scrollTop() || $(\'html\').scrollTop();
    window.location.hash = this.hash;
    $(\'html,body\').scrollTop(scrollmem);
  });

});
                </script>
                ');

                //JHtml::_('behavior.tabstate');

            }


            $i=0;

            $objname='CTtab';

            foreach($fList as $fItem)
            {
                $option=$options[$i];
                $name=JoomlaBasicMisc::slugify($option);

                $tab='';
                if($i==0)
                {
                    //first tab
                    $tab=JHtml::_('bootstrap.startTabSet', $objname, array('active' => $name));
                    //,'class' =>'CTTAB'
                }
                else
                {
                    //tabs between first and last
                    $tab=JHtml::_('bootstrap.endTab'); //close previouse tab
                }



                $tab.=JHtml::_('bootstrap.addTab', $objname, $name, $option); //open new tab
                $htmlresult=str_replace($fItem,$tab,$htmlresult);
				$i++;
            }

            $endtab=JHtml::_('bootstrap.endTab'); //close previouse tab
            $endtab.=JHtml::_('bootstrap.endTabSet');
            $htmlresult=str_replace('{/tabs}',$endtab,$htmlresult);
	}

}
