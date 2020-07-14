<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

defined('_JEXEC') or die('Restricted access');
 
function render_multilangtext(&$row,&$LanguageList,&$esfield,$RequiredLabel,$width,$prefix,$cssclass)
{
    $result='';
						
							$firstlanguage=true;
							foreach($LanguageList as $lang)
							{
								if($firstlanguage)
								{
									$postfix='';
									$firstlanguage=false;
								}
								else
									$postfix='_'.$lang->sef;
								
								$fieldname=$esfield['fieldname'].$postfix;
								
								if(count($row)==0)
									$value=JFactory::getApplication()->input->get('es_'.$fieldname,'','STRING');
								else
									$value=$row['es_'.$fieldname];
								
								$result.=($esfield['isrequired'] ? ' '.$RequiredLabel : '');
								
								
								$result.='<div id="'.$fieldname.'_div" class="multilangtext">';
                                
								if($esfield['typeparams']=='rich')
								{
                                        $result.='<span class="language_label_rich">'.$lang->caption.'</span>';
										$editor = JFactory::getEditor();
										$result.=$editor->display($prefix.$fieldname,$value, ($width>0 ? $width : '500'), '300', '60', '5');
								}
								else
								{
								
										$result.='<textarea filter="raw" name="'.$prefix.$fieldname.'" '
											.'id="'.$prefix.$fieldname.'" '
											.'class="'.$cssclass.' '.($esfield['isrequired'] ? 'required' : '').'">'.$value.'</textarea>
                                            
                                            <span class="language_label">'.$lang->caption.'</span>
                                            ';
										$result.=($esfield['isrequired'] ? ' '.$RequiredLabel : '');
								}
								
								
								$result.= '</div>';
                                
                                ////</td>
								//</tr>';
								
							}
							//$result.='</table>';
    return $result;                            
}
