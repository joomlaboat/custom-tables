<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author JoomlaBoat.com <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/
 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Tables View class
 */
class CustomtablesViewDocumentation extends JViewLegacy
{
	/**
	 * display method of View
	 * @return void
	 */
	var $internal_use=false;
	
	public function display($tpl = null)
	{
		$this->internal_use=true;

		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			CustomtablesHelper::addSubmenu('documentation');
			$this->addToolBar();
			$this->sidebar = JHtmlSidebar::render();
		}
		
		
		$internal_use=true;
		
		// Set the document
		$this->setDocument();
		
		/*

		// Display the template
		*/
		parent::display($tpl);

		
	}
	
	protected function addToolBar()
	{
	
		JToolBarHelper::title(JText::_('COM_CUSTOMTABLES_DOCUMENTATION'), 'joomla');
		JHtmlSidebar::setAction('index.php?option=com_customtables&view=documentation');
		
	}

	protected function setDocument()
	{
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_('COM_CUSTOMTABLES_DOCUMENTATION'));
		$this->document->addStyleSheet(JURI::root(true)."/administrator/components/com_customtables/css/fieldtypes.css", (CustomtablesHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
		
		
		$script='
		<script>
			function readmoreOpenClose(itemid)
			{
			    var obj=document.getElementById(itemid);
				var c=obj.className;
				if(c.indexOf("ct_readmoreOpen")!=-1)
					c=c.replace("ct_readmoreOpen","ct_readmoreClose");
				else if(c.indexOf("ct_readmoreClosed")!=-1)
					c=c.replace("ct_readmoreClosed","ct_readmoreOpen");
				else if(c.indexOf("ct_readmoreClose")!=-1)
					c=c.replace("ct_readmoreClose","ct_readmoreOpen");
				
					
				
				
				obj.className=c;
			}
		</script>
		';
		
		$this->document->addCustomTag($script);
		
	}
	
	function getFieldTypes()
	{
		$xml=$this->getXMLData('fieldtypes_220.xml');
		if(count($xml)==0 or !isset($xml->type))
			return '';

		return $this->renderFieldTypes($xml->type);
	}
	
	function getLayoutTags()
	{
		$xml=$this->getXMLData('tags_220.xml');

		if(count($xml)==0)
			return '';
		
		return $this->renderLayoutTagSets($xml->tagset);
	}
	
	function renderLayoutTagSets(&$tagsets)
	{
		$result='';
		
		foreach($tagsets as $tagset)
		{
			$tagset_att=$tagset->attributes();
			
			$is4Pro=(bool)(int)$tagset_att->proversion;
			$class='ct_doc_tagset_free';
			if($is4Pro)
				$class='ct_doc_tagset_pro';
			
			$result.='<div class="'.$class.'">';
			
			
			$result.='<h3>'.$tagset_att->label.'';
			if($is4Pro)
				$result.='<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">'.JText::_('COM_CUSTOMTABLES_AVAILABLE').'</a></div>';
			
			$result.='</h3>';
			
			
				
			$result.='<p>'.$tagset_att->description.'</p><hr/>';
			
			$result.=$this->renderTags($tagset->tag);

			$result.='</div>';
		}
		
		return $result;
	}
	
	function renderTags(&$tags)
	{
		$result='';
		
		foreach($tags as $tag)
		{
			$tag_att=$tag->attributes();
			
			$is4Pro=(bool)(int)$tag_att->proversion;
			$hidedefaultexample=(bool)(int)$tag_att->hidedefaultexample;
			$isDepricated=(bool)(int)$tag_att->depricated;
			
			$separator=':';
			if(!empty($tag_att->separator))
				$separator=$tag_att->separator;
			
			if(!$isDepricated)
			{
				$class='ct_doc_free';
				if($is4Pro)
					$class='ct_doc_pro';
					
					
				
				if($this->internal_use)
				{
					$result.='<div class="'.$class.' ct_readmoreClosed" id="ctDocTag_'.$tag_att->name.'">';
					$result.='<a name="'.$tag_att->name.'"></a><h4 onClick="readmoreOpenClose(\'ctDocTag_'.$tag_att->name.'\')">{'.$tag_att->name.'} - <span>'.$tag_att->label.'</span>';
				}
				else
				{
					$result.='<div class="'.$class.'" id="ctDocTag_'.$tag_att->name.'">';
					$result.='<a name="'.$tag_att->name.'"></a><h4>{'.$tag_att->name.'} - <span>'.$tag_att->label.'</span>';
				}
			
				if($is4Pro)
					$result.='<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">'.JText::_('COM_CUSTOMTABLES_AVAILABLE').'</a></div>';
			
				$result.='</h4>';
				
				$result.='<p>'.$tag_att->description.'</p>';
			
				if(!empty($tag->params) and count($tag->params)>0)
				{
					$content=$this->renderParameters($tag->params,$tag_att->name,$separator,'{','}',$hidedefaultexample);
					if($content!='')
						$result.='<h5>'.JText::_('COM_CUSTOMTABLES_PARAMS').':</h5>'.$content;
						
					$content=null;
				}
				$result.='</div>';
			}
		}
		
	
		return $result;
	}
	
	

	function renderFieldTypes(&$types)
	{
		$result='';
		
		foreach($types as $type)
		{
			$type_att=$type->attributes();
			
			$is4Pro=(bool)(int)$type_att->proversion;
			$hidedefaultexample=(bool)(int)$type_att->hidedefaultexample;
			$isDepricated=(bool)(int)$type_att->depricated;

			if(!$isDepricated)
			{
			
				$class='ct_doc_free';
				if($is4Pro)
					$class='ct_doc_pro';
					
				if($this->internal_use)
				{
					$result.='<div class="'.$class.' ct_readmoreClosed" id="ctDocType_'.$type_att->ct_name.'">';
					$result.='<h4 onClick="readmoreOpenClose(\'ctDocType_'.$type_att->ct_name.'\')">'.$type_att->ct_name.' - <span>'.$type_att->label.'</span>';
				}
				else
				{
					$result.='<div class="'.$class.'" id="ctDocType_'.$type_att->ct_name.'">';
					$result.='<h4>'.$type_att->ct_name.' - <span>'.$type_att->label.'</span>';
				}
			
				
				if($is4Pro)
					$result.='<div class="ct_doc_pro_label"><a href="https://joomlaboat.com/custom-tables#buy-extension" target="_blank">'.JText::_('COM_CUSTOMTABLES_AVAILABLE').'</a></div>';
			
				$result.='</h4>';
			
				
				$result.='<p>'.$type_att->description.'</p>';
			
				if(!empty($type->params) and count($type->params)>0)
				{
					$content=$this->renderParameters($type->params,'','','','',$hidedefaultexample);
					if($content!='')
						$result.='<h5>'.JText::_('COM_CUSTOMTABLES_FIELDTYPEPARAMS').':</h5>'.$content;
						
					$content=null;
				}
				
				
				if(!empty($type->editparams))
				{
					foreach($type->editparams as $p)
					{
						$params=$p->params;
						$result.='<h5>'.JText::_('COM_CUSTOMTABLES_EDITRECPARAMS').':</h5>'.$this->renderParameters($params,'<i>'.JText::_('COM_CUSTOMTABLES_FIELDNAME').'</i>',':','[',']',$hidedefaultexample);
						break;
					}
				
				}
			
				if(!empty($type->valueparams))
				{
					foreach($type->valueparams as $p)
					{
						$params=$p->params;
						$result.='<h5>'.JText::_('COM_CUSTOMTABLES_VALUEPARAMS').':</h5>'.$this->renderParameters($params,'<i>'.JText::_('COM_CUSTOMTABLES_FIELDNAME').'</i>',':','[',']',$hidedefaultexample);
						break;
					}
					
				}

				$result.='</div>';
			}
		}
		
		return $result;
	}
	
	function renderParameters($params_,$tag_name,$separator,$opening_char,$closing_char,$hidedefaultexample)
	{
		if(count($params_)==0) return '';
		
		$result='';
		$params=$params_->param;
		$example_values=array();
		$example_values_count=0;
		foreach($params as $param)
		{
			$param_att=$param->attributes();
				
			if(count($param_att)!=0)
			{
					$result.='
						<li><h6>'.$param_att->label.' ('.$param_att->type.')</h6>';
					$result.='<p>'.$param_att->description.'</p>';
					
				if(!empty($param_att->type))
				{
					$value_example='';
					$result.=$this->renderParamType($param,$param_att,$value_example);
					
					$example_values[]=$value_example;
					
					if($value_example!='')
						$example_values_count++;
				}
				
				$result.='</li>';
			}
		}
		
		if($result=='')
			return '';
		
		
		if(count($example_values)>0)
		{
			if($tag_name=='')
			{
				if(!(bool)(int)$hidedefaultexample)
					$result.='<p>'.JText::_('COM_CUSTOMTABLES_EXAMPLE').': <pre class="ct_doc_pre">'.$opening_char.$tag_name.$separator.implode(',',$this->cleanParams($example_values)).$closing_char.'</pre></p>';
			}
			else
			{
				if(!(bool)(int)$hidedefaultexample)
					$result.='<p>'.JText::_('COM_CUSTOMTABLES_EXAMPLE').($example_values_count>0 ? ' 1' : '').': <pre class="ct_doc_pre">'.$opening_char.$tag_name.$closing_char.'</pre></p>';
				
				if($example_values_count>0)
					$result.='<p>'.JText::_('COM_CUSTOMTABLES_EXAMPLE').((bool)(int)$hidedefaultexample ? '' : ' 2').': <pre class="ct_doc_pre">'.$opening_char.$tag_name.$separator.implode(',',$this->cleanParams($example_values)).$closing_char.'</pre></p>';
			}
		}
		
		return '<ol>'.$result.'</ol>';
	}
	
	function prepareExample($param)
	{
		$chars=array(',',':','{','}','[',']',' ');

		
		$found=false;
				
				foreach($chars as $c)
				{
					if(strpos($param,$c)!==false)
					{
						$found=true;
						break;
					}
				}
				
				if($found)
					return '"'.$param.'"';
				
				return $param;
					
	}
	
	function cleanParams($params)
	{
		$new_params=array();
		$count=0;
		
		foreach($params as $param_)
		{
			$count++;
			$param=trim($param_);
			if($param!='')
			{
				for($i=1;$i<$count;$i++)
					$new_params[]='';
						
				
				$param=str_replace('<','&lt;',$param);
				$param=str_replace('>','&gt;',$param);
				$new_params[]=$param;
					
				$count=0;
			}
			
		}
		return $new_params;
	}
	
	function renderParamType(&$param,&$param_att,&$value_example)
	{
		$result='';
		
		if(!empty($param_att->example))
		{
						if((bool)(int)$param_att->examplenoquotes)
							$value_example=$param_att->example;
						else
							$value_example=$this->prepareExample($param_att->example);
						
		}		
		
		switch($param_att->type)
		{

				
				case 'number':
					$result.='<ul class="ct_doc_param_options">
					<li><b>'.JText::_('COM_CUSTOMTABLES_DEFAULT').'</b>: '.$param_att->default.'</li>
					';
					
					if(!empty($param_att->min))
						$result.='<li><b>'.JText::_('COM_CUSTOMTABLES_MIN').'</b>: '.$param_att->min.'</li>';
						
					if(!empty($param_att->max))
						$result.='<li><b>'.JText::_('COM_CUSTOMTABLES_MAX').'</b>: '.$param_att->max.'</li>';
					
					$result.='</ul>';
					
					if(!empty($param_att->example))
						$value_example=$param_att->example;
					else
						$value_example=$param_att->min;
					
					break;
				
				case 'radio':
					$options=explode(',',$param_att->options);
					$value_example='';
					
					$result.='<p>'.JText::_('COM_CUSTOMTABLES_OPTIONS').':</p><ul class="ct_doc_param_options">';
					foreach($options as $option)
					{
						$parts=explode('|',$option);
						$result.='<li><b>'.$parts[0].'</b>: '.$parts[1].'</li>';

						if($value_example=='')
							$value_example=$parts[0];
					}

					$result.='</ul>';
					
					
					break;
				
				case 'list':
					$options=$param->option;
					$value_example='';
					
					if(!empty($param_att->example))
					{
						if((bool)(int)$param_att->examplenoquotes)
							$value_example=$param_att->example;
						else
							$value_example=$this->prepareExample($param_att->example);
						
					}
					
					$result.='<p>'.JText::_('COM_CUSTOMTABLES_OPTIONS').':</p><ul class="ct_doc_param_options">';
					foreach($options as $option)
					{
						$option_att=$option->attributes();
						
						if($option_att->value==$option_att->label)
							$result.='<li><b>'.$option_att->value.'</b>';
						else
							$result.='<li><b>'.$option_att->value.'</b>: '.$option_att->label;
						
						if(!empty($param_att->description))
							$result.='<p>'.$option_att->description.'</p>';
						
						$result.='</li>';
						
						if($value_example=='')
							$value_example=$option_att->value;
					}

					$result.='</ul>';
					
					
					break;
		}
		
		return $result;
;
	}
	
	
	function reIndexArray($arrays)
	{
		$array=array();
		$i=0;
	    foreach($arrays as $k => $item)
		{
			$array[$i]=$item;
		    unset($arrays[$k]);
		    $i++;

		}
		return $array;
    }
	
	function getXMLData($file)
    {
        $xml_content=file_get_contents(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_customtables'.DIRECTORY_SEPARATOR.'xml'.DIRECTORY_SEPARATOR.$file);
        if($xml_content!='')
		{
			$xml=simplexml_load_string($xml_content) or die('Cannot load or parse "'.$file.'" file.');
		}
		return $xml;
        
    }
	
}
