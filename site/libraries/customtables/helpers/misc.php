<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @author Ivan komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @license GNU/GPL
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

use CustomTables\Fields;

class JoomlaBasicMisc
{
	static public function array_insert(&$array, $insert, $position = -1)
	{
	    $position = ($position == -1) ? (count($array)) : $position ;
	    if($position != (count($array))) {
	    $ta = $array;
	    for($i = $position; $i < (count($array)); $i++) {
               if(!isset($array[$i])) {
                    die("Invalid array: All keys must be numerical and in sequence.");
               }
               $tmp[$i+1] = $array[$i];
               unset($ta[$i]);
	    }
	    $ta[$position] = $insert;
	    $array = $ta + $tmp;

	    } else {
	         $array[$position] = $insert;
	    }
	    ksort($array);
	    return true;
	}
	
	//https://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size
	public static function file_upload_max_size() {
		static $max_size = -1;

		if ($max_size < 0) {
			// Start with post_max_size.
			$post_max_size = JoomlaBasicMisc::parse_size(ini_get('post_max_size'));
			if ($post_max_size > 0) {
				$max_size = $post_max_size;
			}

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = JoomlaBasicMisc::parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size;
	}


	protected static function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else
			return round($size);
	}
	
	// Snippet from PHP Share: http://www.phpshare.org
	public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
	}


	public static function generateRandomString($length = 32)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++)
		{
		    $randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}



	public static function suggest_TempFileName()
	{
		$output_dir=$file = DIRECTORY_SEPARATOR .
            trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR;

		$random_name=JoomlaBasicMisc::generateRandomString(32);

		do
		{
			$file=$output_dir.$random_name;
			if(!file_exists($file))
				return $file;

		}while(1==1);


	}

	public static function CreateUniqueID($categoryCode, $CreateDate)
	{

		$db =& JFactory::getDBO();

		$n=1;
		do
		{
			$DateText=floor(date("ym",$CreateDate));
			$ID=$DateText.$categoryCode.$n;


			$query =' SELECT listing_id FROM listing WHERE esrecat_id="'.$ID.'"' ;

			$db->setQuery( $query );
			$db->execute();

			$n++;
		}while($db->getNumRows()>0);
		return $ID;
	}

	public static function isUserAdmin()
	{
		$user = JFactory::getUser();


			//if ($user->authorise( 'com_customtables', 'edit', 'content', 'all' )) {
			if ($user->authorise('core.edit', 'com_content'))
			{
			  //Editing permitted
			  return true;
			} else {
			  //Editing not permitted
			  return false;
			}

	}

	public static function SendNotification($Subject,$Message)
	{
		$mainframe =& JFactory::getApplication('site');

		$MailFrom 	= $mainframe->getCfg('mailfrom');
		$FromName 	= $mainframe->getCfg('fromname');
		$mail = JFactory::getMailer();

        $mail->IsHTML(false);
        $mail->addRecipient($MailFrom);
        $mail->setSender( array($MailFrom,$FromName) );
        $mail->setSubject($Subject);

        $mail->setBody($Message);

        $sent = $mail->Send();


	}

	public static function getURLQueryOption($urlstr, $opt)
	{

		$params = array();

		$query=explode('&',$urlstr);

		$newquery=array();

		for($q=0;$q<count($query);$q++)
		{
			$p=strpos($query[$q],$opt.'=');
			if($p!==false)
			{
				$parts=explode('=',$query[$q]);
				if(count($parts)>1)
					return $parts[1];
				else
					return '';
			}
		}
		return '';
	}

	public static function deleteURLQueryOption($urlstr, $opt_)
	{
		$link='';
		$newquery=array();
		$opt=$opt_.'=';
		
		$parts=explode('?',$urlstr);
		
		if(isset($parts[1]))
		{
			$link=$parts[0];
			$query=explode('&',$parts[1]);
		}
		else
			$query=explode('&',$parts[0]);

		foreach($query as $q)
		{
			if(strpos($q,$opt)===false)
				$newquery[]=$q;
		}
		
		if(count($newquery)==0)
			return $link;	
		
		if($link=='')
			return implode('&',$newquery);
		
		return $link.'?'.implode('&',$newquery);
	}

	public static function ExplodeURLQuery($urlstr)
	{

		$p=strpos($urlstr,'?');

		if($p===false)
			return array();

		$urlstr=substr($urlstr,$p+1);

		return explode('&',$urlstr);


	}

	public static function curPageURL() {
		
		$WebsiteRoot = str_replace(JURI::root(true),'',JURI::root(false));
		$RequestURL = $_SERVER["REQUEST_URI"];
		
		if($WebsiteRoot!='' and $WebsiteRoot[strlen($WebsiteRoot) - 1] == '/')
		{
			if($RequestURL!='' and $RequestURL[0] == '/')
			{
				//Delete $WebsiteRoot end /
				$WebsiteRoot = substr($WebsiteRoot,0,strlen($WebsiteRoot) -1 );
			}
		}
			
		return $WebsiteRoot.$RequestURL;
	}

	public static function curPageHost() {
		$pageHost = 'http';
		if ($_SERVER["HTTPS"] == "on") {$pageHost .= "s";}
		$pageHost .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageHost .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageHost .= $_SERVER["SERVER_NAME"];
		}
		return $pageHost;
	}

	public static function getFirstImage($content)
	{

		preg_match_all('/<img[^>]+>/i',$content, $result);
		if(count($result[0])==0)
			return '';



		$img_tag=$result[0][0];

		$img = array();
		preg_match_all('/(src|alt)=("[^"]*")/i',$img_tag, $img, PREG_SET_ORDER);

		$image=JoomlaBasicMisc::getSrcParam($img);


		if($image=='')
		{
			$img = array();
			preg_match_all("/(src|alt)=('[^']*')/i",$img_tag, $img, PREG_SET_ORDER);
			$image=JoomlaBasicMisc::getSrcParam($img);

			if($image=='')
				return '';

			$image=str_replace("'",'',$image);
		}
		else
		{
			$image=str_replace('"','',$image);
		}


		return $image;

	}

	public static function simple_trimtext($text, $words, $strip_html = true)
	{
		if($strip_html)
			$text = strip_tags($text);


		preg_match('/([^\\s]*(?>\\s+|$)){0,'.$words.'}/', $text, $matches);
		$desc=$matches[0];

		return $desc;
	}

	public static function chars_trimtext($text, $count,$cleanbraces=false,$cleanquotes=false)
	{
		if($count==0)
			return "";

		$desc=strip_tags($text);
		$desc=trim($desc);
		$desc=str_replace("/n","",$desc);
		$desc=str_replace("/r","",$desc);

		if(strlen($desc)>$count and $count!=1)
			$desc=substr($desc,0,$count);

		if($cleanbraces)
			$desc = preg_replace('!{.*?}!s', '', $desc);

		if($cleanquotes)
		{
			$desc=str_replace('"','',$desc);
			$desc=str_replace('\'','',$desc);
		}

		$desc=trim($desc);

		return $desc;
	}

	public static function words_trimtext($text, $count,$cleanbraces=false,$cleanquotes=false)
	{
		if($count==0)
			return "";


		$desc=strip_tags($text);

		if($count!=1)
			preg_match('/([^\\s]*(?>\\s+|$)){0,'.$count.'}/', $desc, $matches);

		$desc=trim($matches[0]);
		$desc=str_replace("/n","",$desc);
		$desc=str_replace("/r","",$desc);

		$desc=str_replace("+","_",$desc);



		if($cleanbraces)
			$desc = preg_replace('!{.*?}!s', '', $desc);

		if($cleanquotes)
		{
			$desc=str_replace('"','',$desc);
			$desc=str_replace('\'','',$desc);
		}

		$desc=trim(preg_replace('/\s\s+/', ' ', $desc));

		return $desc;

	}//if(JoomlaBasicMisc::layoutsettings->wordcount==0)




	public static function getSrcParam($img)
	{
		foreach($img as $i)
		{
			if($i[1]=='src' or $i[1]=='SRC')
				return $i[2];

		}

	}

	public static function trim_text($input, $length, $ellipses = true, $strip_html = true)
	{
		if ($strip_html)
				$input = strip_tags($input);

		$input=str_replace("&nbsp;","",$input);
		$input=trim($input);

		//return $input;
		$v=phpversion();
		$vera=explode('.',$v);
		$version=$vera[0];

		if($version==5)
		{

			if (iconv_strlen($input,'UTF-8') <= $length)
				return trim($input);

			$input=str_replace("&","*****",$input);

			$last_space = iconv_strrpos(iconv_substr($input, 0, $length,'UTF-8'), ' ','UTF-8');

			$trimmed_text = iconv_substr($input, 0, $last_space,'UTF-8');
		}
		elseif($version==5)
		{
			if (strlen($input) <= $length)
				return trim($input);

			$input=str_replace("&","*****",$input);

			$last_space = strrpos(iconv_substr($input, 0, $length,'UTF-8'), ' ','UTF-8');

			$trimmed_text = substr($input, 0, $last_space,'UTF-8');
		}

		$trimmed_text=str_replace("*****","&",$trimmed_text);

		if ($ellipses)
			$trimmed_text .= '...';



		return trim($trimmed_text);
	}

	public static function getListToReplace($par,&$options,&$text,$qtype,$separator=':',$quote_char='"')
	{
		$fList=array();
		$l=strlen($par)+2;

		$offset=0;
		do{
			if($offset>=strlen($text))
				break;

			$ps=strpos($text, $qtype[0].$par.$separator, $offset);
			if($ps===false)
				break;


			if($ps+$l>=strlen($text))
				break;

			$quote_open=false;

			$ps1=$ps+$l;
			$count=0;
			do{

				$count++;
				if($count>1000)
				{
					echo 'count>1000';
					die;
				}

				if($quote_char=='')
					$peq=false;
				else
				{
					do
					{
						$peq=strpos($text, $quote_char, $ps1);

						if($peq>0 and $text[$peq-1]=='\\')
						{
							// ignore quote in this case
							$ps1++;

						}
						else
							break;

					}while(1==1);
				}

				$pe=strpos($text, $qtype[1], $ps1);

				if($pe===false)
					break;

				if($peq!==false and $peq<$pe)
				{
					//quote before the end character

					if(!$quote_open)
						$quote_open=true;
					else
						$quote_open=false;

					$ps1=$peq+1;
				}
				else
				{
					if(!$quote_open)
						break;

					$ps1=$pe+1;

				}
			}while(1==1);



		if($pe===false)
			break;

		$notestr=substr($text,$ps,$pe-$ps+1);

			$options[]=trim(substr($text,$ps+$l,$pe-$ps-$l));
			$fList[]=$notestr;


		$offset=$ps+$l;


		}while(!($pe===false));

		//for these with no parameters
		$ps=strpos($text, $qtype[0].$par.$qtype[1]);
		if(!($ps===false))
		{
			$options[]='';
			$fList[]=$qtype[0].$par.$qtype[1];
		}

		return $fList;
	}



	public static function getListToReplaceAdvanced($begining_tag,$ending_tag,&$options,&$text,$sub_begining_tag='')
	{
		$fList=array();
		$l=strlen($begining_tag);//+1;

		$skip_count=0;
		
		$offset=0;
		do{
			if($offset>=strlen($text))
				break;

			$ps=strpos($text, $begining_tag, $offset);
			if($ps===false)
				break;

			if($ps+$l>=strlen($text))
				break;

			$quote_open=false;

			$ps1=$ps+$l;
			$count=0;
			do{

				$count++;
				if($count>1000)
				{
					echo 'too many quotes.';
					die;
				}

				$peq=strpos($text, '"', $ps1);
				$pe=strpos($text, $ending_tag, $ps1);
				
				if(!$quote_open and $sub_begining_tag!='')// this part to all sub-entries, example:  {if:[a]=1} Hello {if:[b]=1} Sir {endif}. How do you do?{endif}
				{
					$sub_bt=strpos($text, $sub_begining_tag, $ps1);
					if($sub_bt!==false and $sub_bt<$pe and ($peq===false or $peq>$sub_bt))
							$skip_count++;//sub entry found. Increase skip count
				}

				if($pe===false)
					break;

				if($peq!==false and $peq<$pe)
				{
					//quote before the end character

					if(!$quote_open)
						$quote_open=true;
					else
						$quote_open=false;

					$ps1=$peq+1;
				}
				else
				{
				
					if(!$quote_open)
					{
						if($skip_count==0)//this is to skip sub entries
							break;
						
						$skip_count-=1;
					}

					$ps1=$pe+1;

				}
			}while(1==1);



		if($pe===false)
			break;

		$notestr=substr($text,$ps,$pe-$ps+strlen($ending_tag));
		$options[]=substr($text,$ps+$l,$pe-$ps-$l);
		$fList[]=$notestr;

		$offset=$ps+$l;

		}while(!($pe===false));

		//for these with no parameters
		$ps=strpos($text, $begining_tag.$ending_tag);
		if(!($ps===false))
		{
			$options[]='';
			$fList[]=$begining_tag.$ending_tag;
		}

		return $fList;
	}





	public static function getMenuParam($param, $Itemid,$rawparams='')
    {

		if($rawparams=='')
		{
			$db = JFactory::getDBO();

			$query = 'SELECT params FROM #__menu WHERE id='.(int)$Itemid.' LIMIT 1';

			$db->setQuery($query);

			$rows= $db->loadObjectList();

			if(count($rows)==0)
				return '';

			$row=$rows[0];

			$rawparams=$row->params;

		}



			if(strlen($rawparams)<8)
				return '';

			$rawparams=substr($rawparams,1,strlen($rawparams)-2);

			$paramslist=JoomlaBasicMisc::csv_explode(',', $rawparams,'"', true);

			foreach($paramslist as $pl)
		    {
				$pair=JoomlaBasicMisc::csv_explode(':', $pl,'"', false);

				if($pair[0]==$param)
					return $pair[1];
			}

		return '';

	}//function getMenuParam($param, $Itemid,$rawparams='')


	public static function csv_explode(string $delim, string $str, string $enclose='"', bool $preserve = false)
	{
		//$delim=','
		
		$resArr = array();
		$n = 0;
		$expEncArr = explode($enclose, $str);
		foreach($expEncArr as $EncItem)
		{
			if($n++%2){
				array_push($resArr, array_pop($resArr) . ($preserve?$enclose:'') . $EncItem.($preserve?$enclose:''));
			}else{
				$expDelArr = explode($delim, $EncItem);
				array_push($resArr, array_pop($resArr) . array_shift($expDelArr));
			    $resArr = array_merge($resArr, $expDelArr);
			}
		}
	return $resArr;
	}


	//-- only for "records" field type;
	public static function processValue($field,&$model,&$row,$langpostfix)
	{
		$htmlresult='';
		$p=strpos($field,'->');
		if(!($p===false))
		{
			$recursivefieldslist=substr($field,$p+2);
			$field=substr($field,0,$p);
		}
		else
			$recursivefieldslist=null;

		//get options
		$options='';
		$p=strpos($field,'(');

		if($p===false)
		{
		}
		else
		{
			$e=strpos($field,'(',$p);
			if($e===false)
				return 'syntax error';

			$options=substr($field,$p+1,$e-$p-1);
			$field=substr($field,0,$p);
		}

		//getting filed row (we need field typeparams, to render formated value)
		if($field=='_id' or $field=='_published')
		{
			$htmlresult=$row[str_replace('_','',$field)];
		}
		else
		{
			$fieldrow=Fields::getFieldAsocByName_($field,$model->ct->Table->fields);
			if(count($fieldrow)>0)
			{
				if(isset($recursivefieldslist))
				{
					$typeparams_=explode(',',$fieldrow['typeparams']);
					$typeparams_[1]=$recursivefieldslist;
					$typeparams=implode(',',$typeparams_);
				}
				else
					$typeparams=$fieldrow['typeparams'];


				$getGalleryRows=array();
				$getFileBoxRows=array();

				if($fieldrow['type']=="multilangstring" or $fieldrow['type']=="multilangtext")
					$real_fields=$fieldrow['realfieldname'].$langpostfix;
				else
					$real_fields=$fieldrow['realfieldname'];

				$options_list=explode(',',$options);
				$v=tagProcessor_Value::getValueByType($model,
					$row[$real_fields],
					$field,
					$fieldrow['type'],
					$typeparams,
					$options_list,
					$getGalleryRows,
					$getFileBoxRows,
					$row['listing_id'],
					$row,
					$fieldrow['id']);

				$htmlresult=$v;
			}
			else
			{
				$htmlresult='Field "'.$field.'" not found.';
			}
		}//if($field=='_id' or $field=='_published')
		return $htmlresult;

	}//processValue()


	public static function getGroupIdByTitle($grouptitle)
	{
		$db = JFactory::getDbo();

		// Build the database query to get the rules for the asset.
		$query	= $db->getQuery(true);
		$query->select('id');
		$query->from('#__usergroups');
		$query->where('title='.$db->quote(trim($grouptitle)));
		$query->limit(1);

		// Execute the query and load the rules from the result.
		$db->setQuery($query);

		$rows= $db->loadObjectList();
		if(count($rows)<1)
			return '';

		$row=$rows[0];


		return $row->id;
	}

	public static function makeNewFileName($filename,$format)
    {
		//Use translation if needed
		$parts=explode('.',$filename);
		$filename_array=array();
		
		$filename_array[] = JoomlaBasicMisc::JTextExtended($parts[0]);
		if(count($parts)>1)
		{
			for($i=1;$i<count($parts);$i++)
				$filename_array[]=$parts[$i];
		}
		
		$filename=implode('.',$filename_array);
		
        // Remove anything which isn't a word, whitespace, number
		// or any of the following caracters -_~,;[]().
		// If you don't need to handle multi-byte characters
		// you can use preg_replace rather than mb_ereg_replace
		// Thanks @Łukasz Rysiak!
		if(function_exists('mb_ereg_replace'))
		{
			$filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
			// Remove any runs of periods (thanks falstro!)
			$filename = mb_ereg_replace("([\.]{2,})", '', $filename);
		}
		else
		{
			$filename = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
			// Remove any runs of periods (thanks falstro!)
			$filename = preg_replace("([\.]{2,})", '', $filename);
		}

		if($format!='')
			$filename.='.'.$format;

        return $filename;
    }



    public static function strip_tags_content($text, $tags = '', $invert = FALSE)
    {
		//$tags - list of tags. Example: <b><span>

        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);

        if(is_array($tags) AND count($tags) > 0)
        {
            if($invert == FALSE)
            {
                return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            }
            else
            {
                return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
            }
        }
        elseif($invert == FALSE)
        {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }
        return $text;
	/*
	 *Usage example: 
$text = '<b>example</b> text with <div>tags</div>'; 

Result for strip_tags($text): 
example text with tags 

Result for strip_tags_content($text): 
text with 

Result for strip_tags_content($text, '<b>'): 
<b>example</b> text with 

Result for strip_tags_content($text, '<b>', TRUE); 
text with <div>tags</div> 
	 */
    }



	public static function slugify($text)
	{
		//or use
		//JFilterOutput::stringURLSafe($this->alias);

		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);


		//ini_set('mbstring.substitute_character', "none");
		//$text= mb_convert_encoding($text, 'UTF-8', 'UTF-8');
		// transliterate

		//$text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
		if(function_exists('iconv'))
			$text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
			
		//$text = iconv('utf-8', 'us-ascii//IGNORE//TRANSLIT', $text);
		//$text = iconv('utf-8', 'ISO-8859-1//TRANSLIT', $text);




		// trim
		$text = trim($text, '-');



		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text))
			return '';

		return $text;
	}

    public static function JTextExtended($text,$value = null)
    {
		if(is_null($value))
			$new_text = JText::_($text);
		else
			$new_text = JText::sprintf($text,$value);

        if($new_text==$text)
        {
            $parts=explode('_',$text);
            if(count($parts)>1)
            {
                $type=$parts[0];
                if($type=='PLG' and count($parts)>2)
                {
                    $extension=strtolower($parts[0].'_'.$parts[1].'_'.$parts[2]);
                }
                else
                    $extension=strtolower($parts[0].'_'.$parts[1]);

                $lang = JFactory::getLanguage();
                $lang->load($extension,JPATH_BASE);

				if(is_null($value))
					return JText::_($text);
				else
					return JText::sprintf($text,$value);
			}
            else
                return $text;
        }
        else
            return $new_text;

    }

	public static function FindItemidbyAlias($alias)
	{
			$db = JFactory::getDBO();
			$query = 'SELECT id FROM #__menu WHERE published=1 AND alias='.$db->Quote($alias);

			$db->setQuery( $query );
			$recs = $db->loadAssocList( );
			if(!$recs) return 0;
			if (count($recs)<1) return 0;

			$r=$recs[0];
			return $r['id'];
	}

	public static function FindMenuItemRowByAlias($alias)
	{
			$db = JFactory::getDBO();
			$query = 'SELECT * FROM #__menu WHERE published=1 AND alias='.$db->Quote($alias);

			$db->setQuery( $query );

			$recs = $db->loadAssocList( );
			if(!$recs) return 0;
			if (count($recs)<1) return 0;

			return $recs[0];
	}
	
	
	public static function checkUserGroupAccess($thegroup=0)
	{
		if($thegroup==0)
			return false;

		$user = JFactory::getUser();
		$isAdmin = $user->get('isRoot');
		if($isAdmin)
			return true;
		
		$usergroups = JAccess::getGroupsByUser($user->id);

		if(in_array($thegroup,$usergroups))
			return true;

		return false;
	}
	
	
	
public static function prepareSearchFilter(&$_params)
{
	if(isset($_params['filter']) and $_params['filter']!="")
	{
		$jinput=JFactory::getApplication()->input;
		
		$f=str_replace('$now','{now}',$_params['filter']);
		$f=str_replace('$year',date('Y'),$f);
			

		$newf=array();
		$p=explode(' ',$f);
		foreach($p as $a)
		{
			if(strpos($a,'$get_')!==false)
			{
				$z=explode('$get_',$a);
				$sp=explode('|',$z[1]);//$get_param|
				$v=(string)preg_replace('/[^A-Z0-9_\.,-]/i', '', $jinput->getString($sp[0]));
				$newf[]=str_replace('$get_'.$sp[0],$v,$a);
			}
			else
				$newf[]=$a;
		}
		$f=implode(' ',$newf);

		$_params['filter']=str_replace('|',',',$f);
	}
}


}
