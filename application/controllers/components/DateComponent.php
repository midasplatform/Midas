<?php
class DateComponent extends AppComponent
{ 
  //format the date (ex: 5 days ago)
  static public function ago($timestamp,$only_time=false)
    {
    if(!is_numeric($timestamp))
      {
      $timestamp=strtotime($timestamp);
      if($timestamp==false)
        {
        return "";
        }
      }
     $difference = time() - $timestamp;
     $periods = array("second", "minute", "hour", "day", "week", "month", "years", "decade");
     $periodsFr = array("seconde", "minute", "heure", "jour", "semaine", "mois", "année", "décades");
     $lengths = array("60","60","24","7","4.35","12","10");
     for($j = 0; $difference >= $lengths[$j]; $j++)
     $difference /= $lengths[$j];
     $difference = round($difference);
     if($difference != 1)
       {
       $periods[$j].= "s";
       if($periodsFr[$j]!='mois') $periodsFr[$j].= "s";
       }
      
     if($only_time)
       {
       return $difference.' '.$periodsFr[$j];
       }
     if(Zend_Registry::get('configGlobal')->application->lang=='fr')
       {
       $text = "Il y a $difference $periodsFr[$j]";
       }
     else
       {
       $text = "$difference $periods[$j] ago";
       }
     return $text;
    }
    
} // end class
?>