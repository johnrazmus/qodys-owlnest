<?php
class qodyHelper_FrameworkTime extends QodyHelper
{
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		
		parent::__construct();
	}
	
	function SecondsPerDay()
	{
		$seconds_in_day = 60 * 60 * 24;
		
		return $seconds_in_day;
	}
	
	function CalculateDay( $date, $strtotime = true )
	{
		$seconds_in_day = $this->SecondsPerDay();
		
		$the_day = (int)(($strtotime ? strtotime($date) : $date) / $seconds_in_day) * $seconds_in_day;
		
		return $the_day;
	}
	
	function GetToday()
	{
		$seconds_in_day = $this->SecondsPerDay();
		
		$today = (int)($this->time() / $seconds_in_day) * $seconds_in_day;
		
		return $today;
	}
	
	function GetYesterday()
	{
		$seconds_in_day = $this->SecondsPerDay();
		$today = $this->GetToday();
		
		$yesterday = $today - ($seconds_in_day * 1);
		
		return $yesterday;
	}
	
	function GetTimeOfWord( $word )
	{
		if( is_numeric($word) )
			$word = 'second';
			
		$second = 1;
		$minute = 60;
		$hour = $minute * 60;
		$day = $hour * 24;
		$week = $day * 7;
		$month = $day * 30;
		$year = $month * 12;
		
		return ${$word};
	}
	
	// localize the time
	function Localize( $time )
	{
		if( !is_numeric( $time ) )
			$time = strtotime( $time );
			
		$good_time = date_i18n( "U", $time );
		
		return $good_time;
	}
	
	// isn't very accurate yet
	function Get()
	{
		$good_time = date_i18n( "U" );
		
		return $good_time;
	}
	
	function Time()
	{
		return current_time( 'timestamp' );
	}
}
?>