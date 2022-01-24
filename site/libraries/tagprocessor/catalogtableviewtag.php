<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

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

    public static function process(&$ct,&$pagelayout,$new_replaceitecode)
    {
        $vlu='';

        //Catalog Table View
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('catalogtable',$options,$pagelayout,'{}');

		$i=0;
		foreach($fList as $fItem)
		{
			$pair=JoomlaBasicMisc::csv_explode(';', $options[$i], '"', true);
			$fields=$pair[0];

			if($ct->Env->frmt=='csv')
			{
				$vlu=self::get_CatalogTable_CSV($ct,$fields);
                $pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}
            elseif($ct->Env->frmt=='json')
			{
				$vlu=self::get_CatalogTable_JSON($ct,$fields);
                $pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}
            elseif($ct->Env->frmt=='xml')
			{
				$vlu=self::get_CatalogTable_XML($ct,$fields);
                $pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}
			elseif($ct->Env->frmt=='xlsx')
			{
				self::get_CatalogTable_XLSX($fields);
			}
			else
			{
				$class='';
				$dragdrop='';
				
				if(isset($pair[1]))
				{
					$parts = explode(',',$pair[1]);
					if($parts[0] != '')
						$class=$parts[0];
					
					if(isset($parts[1]))
						$dragdrop = $parts[1] == 'dragdrop';
				}

				$vlu=self::get_CatalogTable_HTML($ct, $fields,$class, $dragdrop);
				$pagelayout=str_replace($fItem,$new_replaceitecode,$pagelayout);
			}

			$i++;
		}
        return $vlu;
    }
}
