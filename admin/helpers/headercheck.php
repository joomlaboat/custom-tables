<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component
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

class customtablesHeaderCheck
{/*
	function js_loaded($script_name)
	{
		// UIkit check point
		if (strpos($script_name,'uikit') !== false)
		{
			$app            	= Factory::getApplication();
			$getTemplateName  	= $app->getTemplate('template')->template;
			
			if (strpos($getTemplateName,'yoo') !== false)
			{
				return true;
			}
		}
		
		$document 	= Factory::getDocument();
		$head_data 	= $document->getHeadData();
		foreach (array_keys($head_data['scripts']) as $script)
		{
			if (stristr($script, $script_name))
			{
				return true;
			}
		}

		return false;
	}
	*/
    /*
    function css_loaded($script_name)
    {
        // UIkit check point
        if (strpos($script_name,'uikit') !== false)
        {
            $app            	= Factory::getApplication();
            $getTemplateName  	= $app->getTemplate('template')->template;

            if (strpos($getTemplateName,'yoo') !== false)
            {
                return true;
            }
        }

        $document 	= Factory::getDocument();
        $head_data 	= $document->getHeadData();

        foreach (array_keys($head_data['styleSheets']) as $script)
        {
            if (stristr($script, $script_name))
            {
                return true;
            }
        }

        return false;
    }
    */
}
