<?php

use CustomTables\database;

/**
 * CustomTables Joomla! 3.x/4.x/5.x Native Component and WordPress 6.x Plugin
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2023. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

//This function is too old and used to get the where parameter out of module select boxes

//TODO: Review the use of this class
/*
class Alpha
{
	function getAlphaWhere($alpha,&$wherearr)
	{
		$alpha = common::inputGetString('alpha','')
			
				if($this->ct->Params->blockExternalVars)
						return;

				$esfieldtype=common::inputGetCmd('esfieldtype','');
				$esfieldname=common::inputGetCmd('esfieldname','');

				if($esfieldtype!='customtables')
				{
						$fName=$esfieldname;
						if(!(strpos($esfieldname,'multi')===false))
							$fName.=$this->ct->Languages->Postfix;

						$wherearr[]='SUBSTRING(es_'.$fName.',1,1)="'.$alpha.'"';
				}
				else
				{
						$parentid=Tree::getOptionIdFull(common::inputGetString('optionname',''));


						$query = 'SELECT familytreestr, optionname '
								.' FROM #__customtables_options'
								.' WHERE INSTR(familytree,"-'.$parentid.'-") AND SUBSTRING(title'.$this->ct->Languages->Postfix.',1,1)="'.
								common::inputGetString('alpha','').'"'
								.' ';

						$rows = database::loadAssocList($query);

						$wherelist=array();
						foreach($rows as $row)
						{

								if($row['familytreestr'])
										$a=$row['familytreestr'].'.'.$row['optionname'];
								else
										$a=$row['optionname'];

								if(!in_array($a,$wherelist))
										$wherelist[]=$a;
						}

						$wherearr_=array();
						foreach($wherelist as $row)
						{

								$wherearr_[]='instr(es_'.common::inputGetCmd('esfieldname','').',"'.$row.'")';
						}
						$wherearr[]=' ('.implode(' OR ',$wherearr_).')';
				}

		}
}
*/