<?php
/**
 * Custom Tables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://joomlaboat.com
 * @license GNU/GPL
 **/

namespace CustomTables;
 
// no direct access
defined('_JEXEC') or die('Restricted access');

//This function is too old and used to get the where parameter out of module select boxes

class Alpha
{
	function getAlphaWhere($alpha,&$wherearr)
	{
		$alpha = $jinput->get('alpha','','STRING')
			
				if($this->blockExternalVars)
						return;

				$jinput = JFactory::getApplication()->input;
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
						$db = JFactory::getDBO();

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
