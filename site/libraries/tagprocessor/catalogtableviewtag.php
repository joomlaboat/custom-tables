<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'tables.php');
require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'administrator'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'misc.php');

require_once('render_html.php');
require_once('render_xlsx.php');
require_once('render_csv.php');
require_once('render_json.php');
require_once('render_xml.php');
require_once('render_image.php');

class tagProcessor_CatalogTableView
{
    use render_html;
	use render_xlsx;
	use render_csv;
    use render_json;
    use render_xml;
	use render_image;

    public static function process(&$Model,&$pagelayout,&$SearchResult,$new_replaceitecode)
    {
        $vlu='';

        //$allowcontentplugins=$Model->params->get( 'allowcontentplugins' );

        //Catalog Table View
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('catalogtable',$options,$pagelayout,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$pair=JoomlaBasicMisc::csv_explode(';', $options[$i], '"', true);
			$fields=$pair[0];

			if($Model->frmt=='csv')
			{
				$vlu=self::get_CatalogTable_CSV($Model,$fields,$SearchResult);
                $pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}
            elseif($Model->frmt=='json')
			{
				$vlu=self::get_CatalogTable_JSON($Model,$fields,$SearchResult);
                $pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}
            elseif($Model->frmt=='xml')
			{
				$vlu=self::get_CatalogTable_XML($Model,$fields,$SearchResult);
                $pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}
			elseif($Model->frmt=='xlsx')
			{
				self::get_CatalogTable_XLSX($fields);
			}
			else
			{
				if(isset($pair[1]))
					$class=$pair[1];
				else
					$class='';

				$vlu=self::get_CatalogTable_HTML($Model,$fields,$class,$SearchResult);
				$pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}

			$i++;
		}
        return $vlu;
    }
}
