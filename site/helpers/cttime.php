<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class JHTMLCTTime
{
        static public function render($control_name, $value,$class, string $attribute,array $params, array $option_list)
        {
                $options=array(array('id'=>'','name'=>'- '.JText ::_( 'COM_CUSTOMTABLES_SELECT' )));
                
                
                $from=JHTMLCTTime::durationToSeconds($params[0]);
                
                if(isset($params[1]))
                        $to=JHTMLCTTime::durationToSeconds($params[1]);
                else
                        $to=3600*24;//24 hours
                        
                if(isset($params[2]))
                        $step=JHTMLCTTime::durationToSeconds($params[2]);
                else
                        $step=3600;//1 hour
                        
                if(isset($params[3]))
                        $ticks=JHTMLCTTime::durationToSeconds($params[3]);
                else
                        $ticks=1;//1 second
                        
                if(isset($params[4]))
                        $offset=JHTMLCTTime::durationToSeconds($params[4]);
                else
                        $offset=0;

                $format='';
                if(isset($option_list[2]))
                        $format=$option_list[2];
                
                for($i=$from;$i<=$to;$i+=$step)
                {
                        $tick=floor((($i)-$from+$offset)/$ticks);
                        
                        $options[]=array('id'=>$tick,'name'=>JHTMLCTTime::seconds2FormatedTime($i,$format));
                }
                
                $cssclass='';
                if($class!='')
                        $cssclass='class="'.$class.'" ';

		return JHTML::_('select.genericlist', $options, $control_name, $cssclass.$attribute.' ', 'id', 'name', $value,$control_name);

        }
       
        static public function ticks2Seconds($number_of_ticks,array $params)
        {
                $from=JHTMLCTTime::durationToSeconds($params[0]);
                
                    if(isset($params[1]))
                            $to=JHTMLCTTime::durationToSeconds($params[1]);
                    else
                            $to=3600*24;//24 hours
                        
                    if(isset($params[2]))
                            $step=JHTMLCTTime::durationToSeconds($params[2]);
                    else
                            $step=3600;//1 hour
                        
                    if(isset($params[3]))
                        $ticks=JHTMLCTTime::durationToSeconds($params[3]);
                    else
                        $ticks=1;//1 second
                        
                    if(isset($params[4]))
                        $offset=JHTMLCTTime::durationToSeconds($params[4]);
                    else
                        $offset='0';
                
                
                    $seconds=(int)$number_of_ticks*$ticks+$from-$offset;
                    
                    return $seconds;    
        }
        
        static public function seconds2FormatedTime($seconds,$format='')
        {
            date_default_timezone_set('UTC');
                
            if($format!='')
				return date($format, $seconds);
			else
                return date('H:i:s', $seconds);
        }

        static public function durationToSeconds($duration)
        {
            if($duration=='' or $duration=='0')
				$duration='0h';
                
            $interval = new DateInterval('PT' . strtoupper($duration));
                
            $reference = new DateTimeImmutable;
            $endTime = $reference->add($interval);

            return $endTime->getTimestamp() - $reference->getTimestamp();
        }
}
