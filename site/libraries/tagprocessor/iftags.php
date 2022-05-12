<?php
/**
 * CustomTables Joomla! 3.x Native Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @copyright (C) 2018-2022 Ivan Komlev
 * @license GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die('Restricted access');

class tagProcessor_If
{
    public static function process(&$ct,&$htmlresult,&$row)
    {
		$options=array();
        $fList=JoomlaBasicMisc::getListToReplace('if',$options,$htmlresult,'{}');

        $i=0;

        foreach($fList as $fItem)
        {
			tagProcessor_If::parseIfStatements($options[$i],$ct,$htmlresult,$row);
			$i++;
        }

		//outdated - obsolete, use Twig if statements instead. Example: {% if record.published == 1 %} ... {% endif %}
		if(isset($row) and is_array($row) and isset($row['listing_published']))
		{
			//Row Publish Status IF,IFNOT statments
			tagProcessor_If::IFStatment('[_if_published]','[_endif_published]',$htmlresult,!$row['listing_published']==1);
			tagProcessor_If::IFStatment('[_ifnot_published]','[_endifnot_published]',$htmlresult,$row['listing_published']==1);
		}
		else
		{
			tagProcessor_If::IFStatment('[_if_published]','[_endif_published]',$htmlresult,false);
			tagProcessor_If::IFStatment('[_ifnot_published]','[_endifnot_published]',$htmlresult,true);
		}

		$user = JFactory::getUser();
		$currentuserid=(int)$user->get('id');

		tagProcessor_If::IFUserTypeStatment($htmlresult,$user,$currentuserid);

	}

    protected static function processValue(&$ct,&$row,$value)
    {
        tagProcessor_General::process($ct,$value,$row);
        tagProcessor_Page::process($ct,$value);
        tagProcessor_Item::process($ct,$row,$value,'');
        tagProcessor_Value::processValues($ct,$row,$value,'[]');

        return $value;
    }

    protected static function parseIfStatements($statement,&$ct,&$htmlresult,&$row)
    {
        $options=array();
        $fList=JoomlaBasicMisc::getListToReplaceAdvanced('{if:'.$statement.'}','{endif}',$options,$htmlresult,'{if:');

        $i=0;

        foreach($fList as $fItem)
        {

            $content=$options[$i];

            $statement_items=tagProcessor_If::ExplodeSmartParams($statement); //"and" and "or" as separators
			$isTrues=array();//false;

				foreach($statement_items as $item)
				{
					if($item[0]=='or' or $item[0]=='and')
					{
                        $equation=$item[1];
						$opr=tagProcessor_If::getOpr($equation);

                        if($opr!='')
                        {
                            $pair=JoomlaBasicMisc::csv_explode($opr, $equation, '"', false);//true
                        }
                        else
                        {
                            //this to process bullean values. use example {if:[paid]}<b>Paid</b>{endif} TODO: or {if:paid}<b>Paid</b>{endif}, {if:paid} exuals to {if:[_value:paid]}
                            $opr='!=';
                            $pair=array($item[1],'0');//boolean
                        }

						$processed_value1=tagProcessor_If::processValue($ct,$row,$pair[0]);
                        $processed_value2=tagProcessor_If::processValue($ct,$row,$pair[1]);

						$isTrues[]=[$item[0],tagProcessor_If::doMath($processed_value1,$opr,$processed_value2)];

					}
				}

				$isTrue=tagProcessor_If::doANDORs($isTrues);

                if($isTrue)
                    $htmlresult=str_replace($fItem,$content,$htmlresult);
                else
                    $htmlresult=str_replace($fItem,'',$htmlresult);


            $i++;

        }
    }
  
    protected static function doANDORs($isTrues)
	{

		$true_count=0;

		foreach($isTrues as $t)
		{
			if($t[0]=='and')
			{
				if($t[1])
					$true_count++;
			}
			elseif($t[0]=='or')
			{
				if($t[1])
					return true; //if at least one value is true - retrun true
			}
			else
				return false; //wrong parameter, only "or" and "and" accepted
		}


		if($true_count==count($isTrues)) //if all true then true
			return true;

		return false;

	}

	protected static function doMath($value1,$operation,$value2)
	{
        $value1=str_replace('"','',$value1);
        $value2=str_replace('"','',$value2);

        if(is_numeric($value1))
            $value1=(float)$value1;
        elseif(strpos($value1,',')!==false)
            $value1=explode(',',$value1);

        if(is_numeric($value2))
            $value2=(float)$value2;
        elseif(strpos($value2,',')!==false)
            $value2=explode(',',$value2);

        if(is_array($value1) and !is_array($value2))
        {
            //at least one true
            foreach($value1 as $val1)
            {

                if(tagProcessor_If::ifCompare($val1,$value2,$operation))
                    return true;
            }
        }
        elseif(!is_array($value1) and is_array($value2))
        {
            //at least one true
            foreach($value2 as $val2)
            {

                if(tagProcessor_If::ifCompare($value1,$val2,$operation))
                    return true;
            }
        }
        elseif(is_array($value1) and is_array($value2))
        {
            //at least one true
            foreach($value1 as $val1)
            {
                foreach($value2 as $val2)
                {

                    if(tagProcessor_If::ifCompare($val1,$val2,$operation))
                        return true;
                }
            }
        }
        else
            return tagProcessor_If::ifCompare($value1,$value2,$operation);


        return false;
	}

    protected static function ifCompare($value1,$value2,$operation)
    {
                            if($operation=='>')
							{
								if($value1>$value2)
									return true;
							}
							elseif($operation=='<')
							{
								if($value1<$value2)
									return true;
							}
							elseif($operation=='=' or $operation=='==')
							{
								if($value1==$value2)
									return true;
							}
                            elseif($operation=='!=')
							{
								if($value1!=$value2)
									return true;
							}
							elseif($operation=='>=')
							{
								if($value1>=$value2)
									return true;
							}
							elseif($operation=='<=')
							{
								if($value1<=$value2)
									return true;
							}

							return false;
    }

    public static function ExplodeSmartParams($param)
	{
		$items=array();
		$a=	explode(' and ',$param);
		foreach($a as $b)
		{
			$c=explode(' or ',$b);
			if(count($c)==1)
			{
				$items[]=array('and', $b);
			}
			else
			{
				foreach($c as $d)
				{
					$items[]=array('or', $d);
				}
			}
		}
		return $items;
	}//function ExplodeSmartParams($param)

    protected static function getOpr($str)
	{
		$opr='';

		if(strpos($str,'<=')!==false)
			$opr='<=';
		elseif(strpos($str,'>=')!==false)
			$opr='>=';
		elseif(strpos($str,'!=')!==false)
			$opr='!=';
		elseif(strpos($str,'==')!==false)
			$opr='==';
		elseif(strpos($str,'=')!==false)
			$opr='=';
		elseif(strpos($str,'<')!==false)
			$opr='<';
		elseif(strpos($str,'>')!==false)
			$opr='>';
		return $opr;
	}

    //---------------------- old
    public static function IFUserTypeStatment(&$htmlresult,&$user,$currentuserid)
	{
		$options=array();
		$fList=JoomlaBasicMisc::getListToReplace('_if_usertype',$options,$htmlresult,'[]');

		if($currentuserid==0 or count($user->groups)==0)
		{
			$i=0;
			foreach($fList as $fItem)
			{
				$check_user_type=$options[$i];
				tagProcessor_If::IFStatment('[_if_usertype:'.$check_user_type.']','[_endif_usertype:'.$check_user_type.']',$htmlresult,true);
				tagProcessor_If::IFStatment('[_ifnot_usertype:'.$check_user_type.']','[_endifnot_usertype:'.$check_user_type.']',$htmlresult,false);

				$i++;
			}
		}
		else
		{
			$usertypes=array_keys($user->groups);

			$i=0;
			foreach($fList as $fItem)
			{
				$check_user_type=$options[$i];
				$isEmpty=!in_array($check_user_type,$usertypes);

				tagProcessor_If::IFStatment('[_if_usertype:'.$check_user_type.']','[_endif_usertype:'.$check_user_type.']',$htmlresult,$isEmpty);
				tagProcessor_If::IFStatment('[_ifnot_usertype:'.$check_user_type.']','[_endifnot_usertype:'.$check_user_type.']',$htmlresult,!$isEmpty);

				$i++;
			}
		}
	}

	public static function IFStatment($ifname,$endifname,&$htmlresult,$isEmpty)
	{

		if($isEmpty)
		{
			do{
				$textlength=strlen($htmlresult);

				$startif_=strpos($htmlresult,$ifname);

				if($startif_===false)
					break;

				if(!($startif_===false))
				{
					$endif_=strpos($htmlresult,$endifname);
					if(!($endif_===false))
					{
						$p=$endif_+strlen($endifname);

						$htmlresult=substr($htmlresult,0,$startif_).substr($htmlresult,$p);
					}
				}

			}while(1==1);//$textlengthnew!=$textlength);
		}
		else
		{
			$htmlresult=str_replace($ifname,'',$htmlresult);
			$htmlresult=str_replace($endifname,'',$htmlresult);

		}
	}
}
