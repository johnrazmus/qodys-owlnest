<?php
if( !class_exists('qodyHelper_UpdaterObject') )
{
	global $already_checked_group;
	
	class qodyHelper_FrameworkUpdater extends QodyHelper
	{
		var $m_already_checked = array();
		
		function __construct()
		{
			$this->SetOwner( func_get_args() );
			
			parent::__construct();
		}
	
		function SetupChecker( $current_version, $plugin_slug  )  
		{  
			$new_checker = new qodyHelper_UpdaterObject( $current_version, $plugin_slug, $this );
		}	
	}

	class qodyHelper_UpdaterObject
	{
		public $current_version;
		public $update_path = 'http://qody.co/api/';
		public $plugin_slug;
		public $slug;
		public $m_owner;
		
		function __construct( $current_version, $plugin_slug, $owner )
		{
			$this->m_owner = $owner;
			
			// Set the class public variables  
			$this->current_version = $current_version;  
			//$this->update_path = $update_path;  
			$this->plugin_slug = $plugin_slug;  
			list ($t1, $t2) = explode('/', $plugin_slug);  
			$this->slug = str_replace('.php', '', $t2);  
	 
			// define the alternative API for updating checking  
			add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
			//add_filter( 'load-update-core.php', array( $this, 'check_update' ) );
			//add_filter('update_option__transient_update_plugins', array($this, 'check_update'));  // old version
			
			//add_filter('site_transient_update_plugins', array(&$this,'check_update')); //WP 3.0+
			//add_filter('transient_update_plugins', array(&$this,'check_update')); //WP 2.8+
			//add_filter( 'wp_version_check', array( $this, 'check_update' ) );
			
			// Define the alternative response for information checking  
			add_filter('plugins_api', array($this, 'check_info'), 10, 3);  
		}
		
		/** 
		 * Add our self-hosted autoupdate plugin to the filter transient 
		 * 
		 * @param $transient 
		 * @return object $ transient 
		 */  
		public function check_update( $transient )  
		{
			global $already_checked_group;
			
			if( !$transient->checked )
			{  
				return $transient;  
			}
			//wp_mail( 'testing@qody.co', 'testing', print_r( $already_checked_group, true ) );
			//wp_mail( 'testing@qody.co', 'testing', print_r($transient, true ) );
			if( $already_checked_group[ $this->plugin_slug ] )
				return $transient;
			
			$already_checked_group[ $this->plugin_slug ] = true;
			
			$this->m_owner->Overseer()->Log( "Checked for new updates of ".$this->plugin_slug, 'success' );
			
			// Get the remote version  
			$remote_version = $this->getRemote_version();  
			
			// If a newer version is available, add the update  
			if (version_compare($this->current_version, $remote_version, '<'))
			{  
				$this->m_owner->Overseer()->Log( "Found new update for ".$this->plugin_slug." from version ".$this->current_version." to version ".$remote_version, 'success' );
			
				$obj = new stdClass();  
				$obj->slug = $this->slug;  
				$obj->new_version = $remote_version;  
				$obj->url = $this->update_path;  
				$obj->package = $this->update_path.'?a=download&p='.$this->plugin_slug;  
				$transient->response[$this->plugin_slug] = $obj;  
			}  
			 
			return $transient;  
		}  
	  
		/** 
		 * Add our self-hosted description to the filter 
		 * 
		 * @param boolean $false 
		 * @param array $action 
		 * @param object $arg 
		 * @return bool|object 
		 */  
		public function check_info($false, $action, $arg)  
		{
			if ($arg->slug === $this->slug) {  
				$information = $this->getRemote_information();  
				return $information;  
			}  
			return false;  
		}  
	  
		/** 
		 * Return the remote version 
		 * @return string $remote_version 
		 */  
		public function getRemote_version()  
		{  
			$request = wp_remote_get( $this->update_path.'?a=version&p='.$this->plugin_slug );  
			
			if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
				return $request['body'];  
			}  
			return false;  
		}  
	  
		/** 
		 * Get information about the remote version 
		 * @return bool|object 
		 */  
		public function getRemote_information()  
		{  
			$request = wp_remote_get( $this->update_path.'?a=info&p='.$this->plugin_slug );
			
			if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
				return unserialize($request['body']);  
			}  
			return false;  
		}  
	  
		/** 
		 * Return the status of the plugin licensing 
		 * @return boolean $remote_license 
		 */  
		public function getRemote_license()  
		{
			$request = wp_remote_post($this->update_path, array('body' => array('action' => 'license')));  
			if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
				return $request['body'];  
			}  
			return false;  
		}
	}
}

?>