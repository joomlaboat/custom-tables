<?php
/**
 * CustomTables Joomla! 3.x/4.x Native Component
 * @package Custom Tables
 * @author Ivan komlev <support@joomlaboat.com>
 * @link https://www.joomlaboat.com
 * @copyright Copyright (C) 2018-2022. All Rights Reserved
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

namespace CustomTables;

// no direct access
if (!defined('_JEXEC') and !defined('WPINC')) {
    die('Restricted access');
}

use Joomla\CMS\Factory;

//This function is too old and used to get the where parameter out of module select boxes

//TODO: Review the use of this class
/*
class Alpha
{
	function getAlphaWhere($alpha,&$wherearr)
	{
        $jinput=Factory::getApplication()->input;
		$alpha = $jinput->get('alpha','','STRING')
			
				if($this->ct->Params->blockExternalVars)
						return;

				$jinput = Factory::getApplication()->input;
				$esfieldtype=$jinput->get('esfieldtype','','CMD');
				$esfieldname=$jinput->get('esfieldname','','CMD');

				if($esfieldtype!='customtables')
				{
						$fName=$esfieldname;
						if(!(strpos($esfieldname,'multi')===false))
							$fName.=$this->ct->Languages->Postfix;

						$wherearr[]='SUBSTRING(es_'.$fName.',1,1)="'.$alpha.'"';
				}
				else
				{
						$db = Factory::getDBO();

						$parentid=Tree::getOptionIdFull($jinput->get('optionname','','STRING'));


						$query = 'SELECT familytreestr, optionname '
								.' FROM #__customtables_options'
								.' WHERE INSTR(familytree,"-'.$parentid.'-") AND SUBSTRING(title'.$this->ct->Languages->Postfix.',1,1)="'.
								$jinput->get('alpha','','STRING').'"'
								.' ';

						$db->setQuery( $query );
						
						$rows=$db->loadAssocList();

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

								$wherearr_[]='instr(es_'.$jinput->getCMD('esfieldname','').',"'.$row.'")';
						}
						$wherearr[]=' ('.implode(' OR ',$wherearr_).')';
				}

		}
}
*/