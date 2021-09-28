<?php

defined('JPATH_PLATFORM') or die;

function CTLoader($inclide_utilities = false)
{
	$path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
	
	$path_integrity = $path . 'integrity' . DIRECTORY_SEPARATOR;

	require_once($path_integrity.'integrity.php');
	require_once($path_integrity.'fields.php');
	require_once($path_integrity.'options.php');
	require_once($path_integrity.'coretables.php');
	require_once($path_integrity.'tables.php');
	
	$path_helpers = $path . 'helpers' . DIRECTORY_SEPARATOR;
	
	//require_once($path_helpers.'customtablesmisc.php');
	//require_once($path_helpers.'fields.php');
	require_once($path_helpers.'imagemethods.php');
	//require_once($path_helpers.'languages.php');
	require_once($path_helpers.'misc.php');
	require_once($path_helpers.'tables.php');
	require_once($path_helpers.'compareimages.php');
	require_once($path_helpers.'findsimilarimage.php');
	//require_once($path_helpers.'layouts.php');
	require_once($path_helpers.'types.php');
	
	if($inclide_utilities)
	{
		$path_utilities = $path . 'utilities' . DIRECTORY_SEPARATOR;
		require_once($path_utilities.'importtables.php');
		require_once($path_utilities.'exporttables.php');
	}
	
	$path_datatypes = $path . 'ct' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'ct.php');
	require_once($path_datatypes.'environment.php');
	
	$path_datatypes = $path . 'datatypes' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'datatypes.php');
	require_once($path_datatypes.'filemethods.php');
	require_once($path_datatypes.'tree.php');
	
	$path_datatypes = $path . 'layouts' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'layouts.php');
	
	
	$path_datatypes = $path . 'logs' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'logs.php');
	
	//$path_datatypes = $path . 'customphp' . DIRECTORY_SEPARATOR;
	//require_once($path_datatypes.'customphp.php');
	
	
	
	
	$path_datatypes = $path . 'table' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'table.php');
	
	$path_datatypes = $path . 'tables' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'tables.php');
	
	$path_datatypes = $path . 'fields' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'fields.php');
	
	$path_datatypes = $path . 'languages' . DIRECTORY_SEPARATOR;
	require_once($path_datatypes.'languages.php');
	
	//$path_datatypes = $path . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
	//require_once($path_datatypes.'Logs.php');
	

}
