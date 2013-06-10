<?php
class QodyPage extends QodyOwnable
{
	var $m_page_parent		= '';
	var $m_page_title 		= '';
	var $m_menu_title 		= '';
	var $m_capability 		= 1;
	var $m_menu_slug 		= '';
	var $m_icon_url 		= '';
	var $m_menu_position	= null;
	var $m_page_hook		= '';
	
	var $m_data_type = '';
	
	function __construct()
	{
		$fields = array();
		
		// Standard WP
		$fields['admin_init'] = 'AdminInit,WhenOnPage';
		
		$this->LoadActionHooks( $fields );
		
		// has to take priority so posttypes can latch to it
		add_action( 'admin_menu', array( $this, 'CreatePage' ), $this->m_priority );
		
		parent::__construct();
	}
	
	function AdminInit()
	{
		add_action( 'admin_head-'.$this->m_page_hook, array( $this, 'LoadMetaboxes' ) );
	}
	
	// Run when we are on this page in the admin area
	function WhenOnPage()
	{
		// if we are on any admin screen
		if( !is_admin() )
			return false;
		
		// if it's not the right post type
		if( $_GET['page'] != $this->m_menu_slug )
			return false;
		
		$this->EnqueueStyle( $this->AssetPrefix().'post_edit' );
		$this->EnqueueScript( $this->AssetPrefix().'post_edit' );
		$this->FW()->EnqueueScript( 'bootstrap-everything' );
		
		$this->FW()->EnqueueStyle( 'qody_global' );
		$this->EnqueueStyle( 'nicer-settings' );
		
		//add_action( 'admin_print_styles', array( $this, 'LoadAdminBootstrapLast' ), 10000 );
		add_action( 'admin_print_styles', array( $this, 'LoadAdminBootstrapLast' ) );
		
		if( $this->m_menu_slug == $this->GetPre().'-home.php' && !$this->PassApiCheck() )
		{
			$this->FW()->Helper('postman')->Add( 'bad', 'You must first enter a valid O.I.N Key to unlock that plugin!' );
			$url = $this->FW()->Page( 'unlock' )->AdminUrl();

			header( "Location: ".$url );
			exit;
		}
		
		return true;
	}
	
	function PrintButtonSet( $fields, $group_slug = '.button_set_content', $field_to_fill = '' )
	{ ?>
	<div class="btn-group" data-toggle="buttons-radio">
		<?php
		foreach( $fields as $key => $value )
		{
			$filling_js = '';
			
			if( $field_to_fill )
			{
				$filling_js = "jQuery( '$field_to_fill' ).val( '$key' );";
			} ?>
		<button type="button" class="btn btn-small btn-info <?php echo $value ? 'active' : ''; ?>" onClick="CustomGroupToggle( '#<?php echo $key; ?>', '<?php echo $group_slug; ?>' ); <?php echo $filling_js; ?>"><?php echo ucwords( str_replace( '_', ' ', $key ) ); ?></button>
		<?php
		} ?>
	</div>
	<?php
	}
	
	// TODO: remove this from Melody -> Products and use PrintButtonSet instead
	function PrintExtraFeatureButtons( $extras, $label = 'optional features' )
	{ ?>
	<div class="control-group">
		<label class="control-label" style="font-weight:normal;"><?php echo $label; ?></label>
		<div class="controls">
			<div class="btn-group" data-toggle="buttons-checkbox">
				<?php
				foreach( $extras as $key => $value )
				{ ?>
				<button type="button" class="btn btn-small btn-info <?php echo $value ? 'active' : ''; ?>" onClick="jQuery('#<?php echo $key; ?>').slideToggle();"><?php echo ucwords( str_replace( '_', ' ', $key ) ); ?></button>
				<?php
				} ?>
			</div>
		</div>
	</div>
	<?php
	}
	
	function LoadAdminBootstrapLast()
	{
		$this->EnqueueStyle( 'restricted-bootstrap' );
	}
	
	function get_post_custom( $post_id )
	{
		if( $this->m_data_type )
			return $this->DataType( $this->m_data_type )->get_post_custom( $post_id );
			
		return parent::get_post_custom( $post_id );
	}
	
	function get_the_title( $post_id )
	{
		return $this->GetDataType()->get_the_title( $post_id );
	}
	
	function SetDataType( $slug )
	{
		$this->m_data_type = $slug;
	}
	
	function GetPerPage()
	{
		$per_page = $_GET['per_page'];
		
		if( !$per_page )
			$per_page = $_COOKIE[ $this->m_page_hook.'perpage' ] ? $_COOKIE[ $this->m_page_hook.'perpage' ] : 10;
		
		return $per_page;
	}
	
	function GetCurrentPage()
	{
		return $_GET['current_page'] ? $_GET['current_page'] : 1;
	}
	
	function PrintPerPage()
	{
		$per_page = $this->GetPerPage();
		?>
	<div class="alignleft">
		<select class="widefat" style="width:auto;" onchange="ChangeItemsPerPage(this.value);">
			<option value="">- Per Page -</option>
			<?php
			for( $i = 5; $i < 100; $i += 5 )
			{ ?>
			<option <?php echo $i == $per_page ? 'selected="selected"' : ''; ?> value="<?php echo $i; ?>"><?php echo $i; ?> per page</option>
			<?php
			} ?>
			<?php
			for( $i = 100; $i < 1000; $i += 100 )
			{ ?>
			<option <?php echo $i == $per_page ? 'selected="selected"' : ''; ?> value="<?php echo $i; ?>"><?php echo $i; ?> per page</option>
			<?php
			} ?>
			<?php
			for( $i = 1000; $i < 10000; $i += 1000 )
			{ ?>
			<option <?php echo $i == $per_page ? 'selected="selected"' : ''; ?> value="<?php echo $i; ?>"><?php echo $i; ?> per page</option>
			<?php
			} ?>
		</select>
						
		<script type="text/javascript">
		function ChangeItemsPerPage( per_page_value )
		{
			if( per_page_value != "" )
			{
				document.cookie = "<?php echo $this->m_page_hook; ?>perpage=" + per_page_value + "; expires=<?php echo date( "r", strtotime("+1 month") ); ?>; path=/";
				window.location = "<?php echo $this->AdminUrl( array_merge( $_GET, array( 'current_page' => 1, 'per_page' => '' ) ) ); ?>";
			}
		}
		</script>
	</div>
		<?php
	}
	
	function PrintPaging( $total_items )
	{
		global $iter_min, $iter_max;
		
		if( !is_numeric( $total_items ) )
			$total_items = count( $total_items );
		
		$per_page = $this->GetPerPage();
		$current_page = $this->GetCurrentPage();
		
		$iter_min = ($current_page - 1) * $per_page + 1;
		$iter_max = $current_page * $per_page;
		
		$total_pages = ceil( $total_items / $per_page );
		?>
	<div class="pull-right pagination pagination-right">
								
		<ul>
			<li class="<?php echo $current_page == 1 ? 'disabled' : ''; ?>">
				<a href="<?php echo $current_page == 1 ? '#' : $this->AdminUrl( array_merge( $_GET, array( 'current_page' => 1 ) ) ); ?>">&laquo;</a>
			</li>
			<li class="<?php echo $current_page == 1 ? 'disabled' : ''; ?>">
				<a href="<?php echo $current_page == 1 ? '#' : $this->AdminUrl( array_merge( $_GET, array( 'current_page' => $current_page - 1 ) ) ); ?>">Prev</a>
			</li>
			<?php
			$fields = array();
			
			for( $i = 1; $i <= 2; $i++ )
			{
				if( $current_page - $i > 0 )
					$fields[] = $current_page - $i;
			}
			
			$fields = array_reverse( $fields );
			
			$fields[] = $current_page;
			
			for( $i = 1; $i <= 5; $i++ )
			{
				if( $current_page + $i <= $total_pages && count($fields) < 5 )
					$fields[] = $current_page + $i;
			}
			
			if( $fields )
			{
				foreach( $fields as $key => $value )
				{ ?>
			<li class="<?php echo $value == $current_page ? 'active' : ''; ?>">
				<a href="<?php echo $this->AdminUrl( array_merge( $_GET, array( 'current_page' => $value ) ) ); ?>">
					<?php echo $value; ?>
				</a>
			</li>
				<?php											
				}
			}
			?>
			<li class="<?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
				<a href="<?php echo $current_page >= $total_pages ? '#' : $this->AdminUrl( array_merge( $_GET, array( 'current_page' => $current_page + 1 ) ) ); ?>">Next</a>
			</li>
			<li class="<?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
				<a href="<?php echo $current_page >= $total_pages ? '#' : $this->AdminUrl( array_merge( $_GET, array( 'current_page' => $total_pages ) ) ); ?>">&raquo;</a>
			</li>
		</ul>
	</div>
	<div class="displaying-num pull-right" style="line-height:30px;">
		Displaying <?php echo $iter_min; ?> - <?php echo $current_page >= $total_pages ? $total_items : $iter_max; ?> of <?php echo $total_items; ?>
	</div>
		<?php		
	}
	
	function LoadFancyEditor()
	{
		wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'media-upload'));
		wp_enqueue_style('thickbox');
		
		add_action("admin_head", array( $this, 'myplugin_load_tiny_mce' ) );
	}
	
	function myplugin_load_tiny_mce() {
	
		wp_tiny_mce( false ); // true gives you a stripped down version of the editor
	
	}
	
	function CreatePage()
	{
		global $submenu;
		
		if( $this->m_page_parent )
			$this->add_submenu_page();
		else
			$this->add_menu_page();
	}
	
	function GetDataType()
	{
		return $this->DataType( $this->m_data_type );
	}
	
	function SavePage()
	{
		$response = array();

		if( $_POST )
		{
			// saving it like a fake post
			if( $this->m_data_type )
			{
				$post_id = $_POST['post_id'] ? $_POST['post_id'] : $this->GetDataType()->wp_insert_post();
				
				//do_action( 'qody_save_post', $post_id, $post );
				
				foreach( $_POST as $key => $value )
				{
					if( strpos( $key, 'field_' ) === false )
					{
						$fields = array();
						$fields['ID'] = $post_id;
						$fields[ $key ] = $value;
						
						$this->GetDataType()->wp_insert_post( $fields );
						
						continue;
					}
						
					$key = str_replace( 'field_', '', $key );
					
					$this->GetDataType()->update_datatype_meta( $post_id, $key, $value );
				}
				
				//do_action( 'qody_after_save_post', $post_id, $post );
			}
			else
			{
				foreach( $_POST as $key => $value )
				{
					$this->update_option( $key, $value );
				}
			}
			
			$response['results'][] = 'Settings have been saved';
		}
		else
		{
			$response['errors'][] = 'Any unexpected error occured; please try again';
		}
		
		$this->Helper('postman')->SetMessage( $response );
	}
	
	function AdminUrl( $fields_or_content = '', $id = '' )
	{
		if( is_array( $fields_or_content ) )
		{
			$fields = $fields_or_content;
		}
		else
		{
			$fields = array();
			$fields['content'] = $fields_or_content;
			$fields['id'] = $id;
		}
		
		$fields['page'] = $this->m_menu_slug;
		
		return admin_url( 'admin.php?'.http_build_query( $fields ) );
	}
	
	function SetSlug( $slug )
	{
		$pre = $this->Owner() ? $this->Owner()->m_pre : '';
		
		$this->m_menu_slug = $pre.'-'.$slug.'.php';
	}
	function SetParent( $slug )
	{
		$pre = $this->Owner() ? $this->Owner()->m_pre : '';
		
		$this->m_page_parent = $pre.'-'.$slug.'.php';
	}
	
	function SetTitle( $title )
	{
		$this->m_page_title = $title;
		$this->m_menu_title = $title;
	}
	
	function ContentFunction()
	{
		global $qodys_framework;
		
		$content_file = $this->m_asset_folder.'/content.php';
		
		if( file_exists( $content_file ) )	
			include( $content_file );
		else
			echo 'content file not found';
	}
	
	function AddMetabox( $file_slug, $title, $position = 'normal', $type_slug = '', $priority = 'low', $output_directly = false )
	{
		$type_slug = $this->m_page_hook;
		
		parent::AddMetabox( $file_slug, $title, $position , $type_slug, $priority, $output_directly );
	}
	
	function do_meta_boxes( $context )
	{
		do_meta_boxes( $this->m_page_hook, $context, $this );
	}
	
	function add_menu_page()
	{
		$this->m_page_hook = add_menu_page
		(
			$this->m_page_title,
			$this->m_menu_title,
			1,
			$this->m_menu_slug,
			array( $this, 'ContentFunction' ),
			$this->m_icon_url,
			$this->m_menu_position
		);
		
		//$this->ItemDebug( $this->m_icon_url );
	}
	
	function add_submenu_page()
	{
		$this->m_page_hook = add_submenu_page
		(
			$this->m_page_parent,
			$this->m_page_title,
			$this->m_menu_title,
			1,
			$this->m_menu_slug,
			array( $this, 'ContentFunction' )
		);
	}
	

}
?>