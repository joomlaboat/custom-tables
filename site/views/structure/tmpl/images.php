<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\DataTypes\Tree;
    
    JHTML::stylesheet("default.css", JURI::root(true)."/components/com_customtables/views/catalog/tmpl/");


	$params = JComponentHelper::getParams( 'com_customtables' );
  
		$catalogresult='<table width="100%" align="center">';
		
        $tr=0;
		$number_of_columns=$this->Model->columns;
		$content_width=100;
		$column_width=floor($content_width/$number_of_columns);
		
		$imagemethods=new CustomTablesImageMethods;
		
		//------------------
		
		$prefix='_esthumb';
		$imageparams='';
		if($this->Model->image_prefix=='_original')
						$prefix='_original';
		else
		{
				if(count($this->rows)>0)
				{
					$row=$this->rows[0];
					$imageparams=Tree::getHeritageInfo($row[parentid], 'imageparams');
				
					$cleanOptions=$imagemethods->getCustomImageOptions($imageparams);
				
				
					if(count($cleanOptions)>0)
					{
						foreach($cleanOptions as $imgSize)
						{
								if($this->Model->image_prefix==$imgSize[0])
										$prefix=$imgSize[0];
						}
					}
				}
		}
				
        foreach($this->rows as $row)
        {
				if($tr==0)
						$catalogresult.='<tr>';
		
								
				$imagefile_='images/esoptimages/'.$prefix.'_'.$row[image];
					
				if(file_exists($imagefile_.'.jpg'))
						$imagefile=$imagefile_.'.jpg';
				elseif(file_exists($imagefile_.'.png'))
						$imagefile=$imagefile_.'.png';
				elseif(file_exists($imagefile_.'.webp'))
						$imagefile=$imagefile_.'.webp';
				else
						$imagefile='';
		
				if($imagefile!='')
				{
						$catalogresult.='<td width="'.$column_width.'%" valign="top" align="center">';

						if($this->Model->esfieldname!='')
						{
								$aLink='index.php?option=com_customtables&view=catalog&';
								
								if($params->get( 'layout' )!='')
										$aLink.='layout='.$params->get( 'layout' ).'&';
								
								
								if($params->get( 'itemid' )!='')
										$aLink.='Itemid='.$params->get( 'itemid' ).'&';
								else
										$aLink.='Itemid='.JFactory::getApplication()->input->getInt('Itemid',  0).'&';
								
								$aLink.='&establename='.$this->Model->establename;
								$aLink.='&filter='.$this->Model->esfieldname.urlencode('=').$this->Model->optionname;
								
								if($row['optionname']!='')
										$aLink.='.'.$row['optionname'];
								

								
								$catalogresult.='<a href="'.$aLink.'"><img src="'.$imagefile.'" border="0" /></a>';
						}
						else
							$catalogresult.='<img src="'.$imagefile.'" border="0" />';
        
						$catalogresult.='</td>';
				
						$tr++;
						
						if($tr==$number_of_columns)
						{
								$catalogresult.='</tr>';
						
								if($this->Model->row_break)
										$catalogresult.='<tr><td colspan="'.$number_of_columns.'"><hr /></td></tr>';
						
						
								$tr	=0;
						}
				}
							
				
				
        }
		
       
	  
       $catalogresult.='</tbody>
        
    </table>';
		
		
       
        $mainframe = JFactory::getApplication('site');
            
        $o = new stdClass();
        $o->text = $catalogresult;
        $o->created_by_alias = 0;
            
        $params = $mainframe->getParams('com_content');
		
        JPluginHelper::importPlugin('content');
        $dispatcher = JDispatcher::getInstance();
		
		
        $results = $dispatcher->trigger('onPrepareContent', array (&$o, $params, 0));
		
        echo $o->text;
        
 
