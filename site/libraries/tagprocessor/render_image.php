<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/


// no direct access
defined('_JEXEC') or die('Restricted access');

trait render_image
{

    protected static function get_CatalogTable_singleline_IMAGE(&$ct,&$pagelayout,$allowcontentplugins)
	{
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_imagegenerator'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'include.php');
		require_once(JPATH_SITE.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_imagegenerator'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'misc.php');

		if (ob_get_contents()) ob_end_clean();

		$IG=new IG();

		$IG->filename = JoomlaBasicMisc::makeNewFileName($ct->Env->menu_params->get('page_title'),'');
		$IG->setImageGeneratorProfileFromText($pagelayout);

		$image_width=$IG->width;
		$image_height=$IG->height;
		if (ob_get_contents()) ob_end_clean();

		$obj=null;

		//set canvas width
		$IG->width=$image_width*3+10;
		//set canvas height
		$IG->height=$image_height*ceil(count($ct->Records)/3)+10;

		$obj=$IG->render(false,$obj);

		$IG->width=$image_width;
		$IG->height=$image_height;



		$x_offset=5;
		$y_offset=5;
		$c=0;

		foreach($ct->Records as $row)
		{
			$vlu=$this->RenderResultLine($row,false,$allowcontentplugins);
			$IG->setInstructions($vlu,true);
			$obj=$IG->render(false,$obj,$x_offset,$y_offset);
			//break;
			$x_offset+=$image_width;
			$c++;
			if($c>=3)
			{
				$c=0;
				$x_offset=5;
				$y_offset+=$image_height;
			}
		}
		
		$IG->setInstructions('',false);
		$obj=$IG->render(true,$obj);

        die;//clean exit
    }
}
