<?php
/**
 * CustomTables Joomla! 3.0 Native Component
 * @version 1.6.1
 * @author Ivan Komlev< <support@joomlaboat.com>
 * @link http://www.joomlaboat.com
 * @GNU General Public License
 **/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class JHTMLCTTime
{


        static public function render($control_name, $value,$class, $attribute='',$typeparams,$option_list)
        {
                $options=array(array('id'=>'','name'=>'- '.JText ::_( 'COM_CUSTOMTABLES_SELECT' )));
                
                
                $from=JHTMLCTTime::durationToSeconds($typeparams[0]);
                
                if(isset($typeparams[1]))
                        $to=JHTMLCTTime::durationToSeconds($typeparams[1]);
                else
                        $to=3600*24;//24 hours
                        
                if(isset($typeparams[2]))
                        $step=JHTMLCTTime::durationToSeconds($typeparams[2]);
                else
                        $step=3600;//1 hour
                        
                if(isset($typeparams[3]))
                        $ticks=JHTMLCTTime::durationToSeconds($typeparams[3]);
                else
                        $ticks=1;//1 second
                        
                if(isset($typeparams[4]))
                        $offset=JHTMLCTTime::durationToSeconds($typeparams[4]);
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

        /*
        static public function seconds2hms($seconds)
        {
                $t = round($seconds);
                return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
        }
        */
        
        static public function ticks2Seconds($number_of_ticks,$typeparams)
        {
                $from=JHTMLCTTime::durationToSeconds($typeparams[0]);
                
                    if(isset($typeparams[1]))
                            $to=JHTMLCTTime::durationToSeconds($typeparams[1]);
                    else
                            $to=3600*24;//24 hours
                        
                    if(isset($typeparams[2]))
                            $step=JHTMLCTTime::durationToSeconds($typeparams[2]);
                    else
                            $step=3600;//1 hour
                        
                    if(isset($typeparams[3]))
                        $ticks=JHTMLCTTime::durationToSeconds($typeparams[3]);
                    else
                        $ticks=1;//1 second
                        
                    if(isset($typeparams[4]))
                        $offset=JHTMLCTTime::durationToSeconds($typeparams[4]);
                    else
                        $offset='0';
                
                
                    $seconds=(int)$number_of_ticks*$ticks+$from-$offset;
                    
                    return $seconds;    
        }
        
        static public function seconds2FormatedTime($seconds,$format)
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
