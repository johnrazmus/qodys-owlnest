<?php
if( !class_exists('qodyHelper_CronObject') )
{
	class qodyHelper_FrameworkCron extends QodyHelper
	{
		var $m_new_interval_seconds;
		var $m_new_interval_slug;
		
		function __construct()
		{
			$this->SetOwner( func_get_args() );
			
			parent::__construct();
		}
		
		// $recurrence can be an array of a new interval (slug, seconds), or one of the wordpress defaults
		// $recurrence - hourly, twicedaily, daily
		function SetupCron( $data )  
		{
			if( !$data['timestamp'] )
				$data['timestamp'] = $this->Helper('time')->Time();
			
			if( !$data['caller'] )
				$data['caller'] = $this;
			
			$new_cron = new qodyHelper_CronObject( $data );
		}
		
		function CreateNewInterval( $seconds, $schedule_slug )
		{
			$this->m_new_interval_seconds = $seconds;
			$this->m_new_interval_slug = $schedule_slug;
			
			add_filter( 'cron_schedules', array( $this, 'add_to_cron_schedules' ) );		
		}
		
		function add_to_cron_schedules( $all_schedules = array() )
		{
			$fields = array();
			$fields['interval'] = $this->m_new_interval_seconds;
			$fields['display'] = 'Qody custom: '.str_replace( '_', ' ', $this->m_new_interval_slug );
			
			$all_schedules[ $this->m_new_interval_slug ] = $fields;
			
			return $all_schedules;
		}
		
		function UnscheduleCron( $action_hook )
		{			
			wp_clear_scheduled_hook( $action_hook ); 
		}
	}

	class qodyHelper_CronObject
	{
		var $m_timestamp;
		var $m_recurrence;
		var $m_action_name;
		var $m_args;
		var $m_caller;
		
		function __construct( $data )
		{
			$this->m_caller = $data['caller'];
			
			$this->m_timestamp = $data['timestamp'] ? $data['timestamp'] : $this->m_caller->Helper('time')->Time();
			$this->m_action_name = $data['action_name'];
			$this->m_args = $data['function_args'] ? $data['function_args'] : array();
			
			$this->m_recurrence = $data['recurrence'];
			
			//$this->UnscheduleIt();
			$this->ScheduleIt();
		}
		
		function UnscheduleIt()
		{
			//$timestamp = wp_next_scheduled( $this->m_hook, $this->m_args );
    		//wp_unschedule_event( $timestamp, $this->m_hook, $this->m_args );
			
			wp_clear_scheduled_hook( $this->m_action_name ); 
		}
		
		function GetSchedule()
		{
			$data = wp_get_schedule( $this->m_action_name, $this->m_args );
			
			return $data;
		}
		
		function ScheduleIt()
		{
			$next_to_run = wp_next_scheduled( $this->m_action_name, $this->m_args );
			$difference = $next_to_run - $this->m_caller->Helper('time')->Time();
			
			$next_to_run_formatted = $this->m_caller->Helper('tools')->NumberTimeToStringTime( $difference );
			
			// prevent duplicates
			if( $next_to_run )
				return;
			
			$this->m_caller->Log( "Scheduled a new Wordpress cron job <strong>".$this->m_action_name."</strong>", 'success' );
			
			wp_schedule_event( $this->m_timestamp, $this->m_recurrence, $this->m_action_name, $this->m_args );
		}
		
	}
}

?>