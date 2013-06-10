<?php
class qodyHelper_FrameworkExtraFeaturedImage extends QodyHelper
{
	function __construct( $args = array() )
	{
		$this->SetOwner( func_get_args() );
		
		add_action( 'admin_init', array( $this, 'WhenUploading' ) );
		
		parent::__construct();
	}
	
	function RegisterScripts()
	{
		$this->RegisterScript( 'extra-featured-image', $this->GetAsset( 'js', 'extra-featured-image', 'url' ), array( 'jquery' ) );
		
		parent::RegisterScripts();
	}
	
	function WhenUploading()
	{
		global $post, $typenow, $pagenow;
		
		if( $pagenow != 'media-upload.php' )
			return;
		
		$this->EnqueueScript( 'extra-featured-image' );
	}
	
	function AddNewFeaturedImage( $fields )
	{
		
		
		return new QodyExtraImagesStructure( $fields );
	}
}

if( !class_exists('QodyExtraImagesStructure') )
{
	class QodyExtraImagesStructure
	{
		var $label = 'Custom image';
		var $id = 1;
		var $post_type = 'post';
		var $priority = 'low';
		var $set_text = 'Set custom image';
		var $m_whitelisted_post_types = array();
		
		function __construct( $fields )
		{
			$this->register( $fields );
		}
		
		function register( $fields = array() )
		{
			if( $fields['post_type'] )
				$this->post_type = $fields['post_type'];
			
			if( $fields['label'] )
				$this->label = $fields['label'];
			
			if( $fields['set_text'] )
				$this->set_text = $fields['set_text'];
			
			if( $fields['id'] )
				$this->id = $fields['id'];
			
			if( $fields['whitelisted_post_types'] )
				$this->m_whitelisted_post_types = $fields['whitelisted_post_types'];
			
			// add theme support if not already added
			if (!current_theme_supports('post-thumbnails'))
			{
				add_theme_support( 'post-thumbnails' );
			}
			
			//$this->EnqueueScript('extra-featured-image');
			
			add_filter('attachment_fields_to_edit', array($this, 'add_attachment_field'), null, 2);
			add_action("wp_ajax_set-qody-{$this->post_type}-{$this->id}-thumbnail", array($this, 'set_thumbnail'));
		}
	
		public function thumbnail_meta_box()
		{
			global $post;
			
			$thumbnail_id = get_post_meta($post->ID, "qody_{$this->post_type}_{$this->id}_thumbnail_id", true);
			
			echo $this->post_thumbnail_html($thumbnail_id);
		}
		
		function ShowExtraThumbnailControl()
		{
			global $post;
			
			$custom = get_post_custom( $post->ID );
			
			$thumbnail_id = get_post_meta( $post->ID, "qody_{$this->post_type}_{$this->id}_thumbnail_id", true );
			
			echo $this->post_thumbnail_html($thumbnail_id);
		}
		
		function post_thumbnail_html( $thumbnail_id = NULL )
		{
			global $content_width, $_wp_additional_image_sizes, $post_ID;
			
			$set_thumbnail_link = sprintf('<p class="hide-if-no-js"><a title="%1$s" href="%2$s" id="set-qody-%3$s-%4$s-thumbnail" class="thickbox featured-%3$s-%4$s">%%s</a></p>', esc_attr__( $this->set_text ), get_upload_iframe_src('image'), $this->post_type, $this->id);
			$content = sprintf($set_thumbnail_link, esc_html__( $this->set_text ));
	
			if( $thumbnail_id && get_post($thumbnail_id) )
			{
				$old_content_width = $content_width;
				
				$content_width = 266;
				
				$thumbnail_html = wp_get_attachment_image($thumbnail_id, 'full');
				
				/*if( !isset($_wp_additional_image_sizes["qody-{$this->post_type}-{$this->id}-thumbnail"]) )
					$thumbnail_html = wp_get_attachment_image($thumbnail_id, array($content_width, $content_width));
				else
					$thumbnail_html = wp_get_attachment_image($thumbnail_id, "qody-{$this->post_type}-{$this->id}-thumbnail");*/
					
				if( !empty($thumbnail_html) )
				{
					$ajax_nonce = wp_create_nonce("set_post_thumbnail-qody-{$this->post_type}-{$this->id}-{$post_ID}");
					$content = sprintf($set_thumbnail_link, $thumbnail_html);
					
					$content .= sprintf('<p class="hide-if-no-js"><a href="#" id="remove-qody-%1$s-%2$s-thumbnail" onclick="QodyThumbRemoveThumbnail(\'%2$s\', \'%1$s\', \'%4$s\');return false;">%3$s</a></p>', $this->post_type, $this->id, esc_html__( "Remove {$this->label}" ), $ajax_nonce);
				}
				
				$content_width = $old_content_width;
			}
			
			$data = '<div id="post-'.$this->id.'"><div class="inside">'.$content.'</div></div>';
		
			$data = $content;
	
			return $data;
		}
		
		function set_thumbnail()
		{
			global $post_ID; // have to do this so get_upload_iframe_src() can grab it
			
			$post_ID = intval($_POST['post_id']);
			
			if( !current_user_can('edit_post', $post_ID) )
				die('-1');
				
			$thumbnail_id = intval($_POST['thumbnail_id']);
	
			check_ajax_referer("set_post_thumbnail-qody-{$this->post_type}-{$this->id}-{$post_ID}");
	
			if( $thumbnail_id == '-1' )
			{
				delete_post_meta($post_ID, "qody_{$this->post_type}_{$this->id}_thumbnail_id");
				die($this->post_thumbnail_html(NULL));
			}
			
			if( $thumbnail_id && get_post($thumbnail_id) )
			{
				$thumbnail_html = wp_get_attachment_image($thumbnail_id, 'thumbnail');
				
				if( !empty($thumbnail_html) )
				{
					update_post_meta($post_ID, "qody_{$this->post_type}_{$this->id}_thumbnail_id", $thumbnail_id);
					die($this->post_thumbnail_html($thumbnail_id));
				}
			}
	
			die('0');
		}
		
		static function get_post_thumbnail_id( $post_type, $id, $post_id )
		{
			return get_post_meta($post_id, "{$post_type}_{$id}_thumbnail_id", true);
		}
		
		static function get_the_post_thumbnail( $post_type, $thumb_id, $post_id = NULL, $size = 'post-thumbnail', $attr = '' , $link_to_original = false )
		{
			global $id;
			
			$post_id = (NULL === $post_id) ? $id : $post_id;
			
			$post_thumbnail_id = self::get_post_thumbnail_id($post_type, $thumb_id, $post_id);
			
			$size = apply_filters("qody_{$post_type}_{$post_id}_thumbnail_size", $size);
			
			if( $post_thumbnail_id )
			{
				do_action("begin_fetch_multi_qody_{$post_type}_thumbnail_html", $post_id, $post_thumbnail_id, $size); // for "Just In Time" filtering of all of wp_get_attachment_image()'s filters
				
				$html = wp_get_attachment_image( $post_thumbnail_id, $size, false, $attr );
				
				do_action("end_fetch_multi_qody_{$post_type}_thumbnail_html", $post_id, $post_thumbnail_id, $size);
			}
			else
			{
				$html = '';
			}
	
			if( $link_to_original )
			{
				$html = sprintf('<a href="%s">%s</a>', wp_get_attachment_url($post_thumbnail_id), $html);
			}
	
			return apply_filters("qody_{$post_type}_{$thumb_id}_thumbnail_html", $html, $post_id, $post_thumbnail_id, $size, $attr);
		}
		
		static function the_post_thumbnail( $post_type, $thumb_id, $post_id = null, $size = 'post-thumbnail', $attr = '', $link_to_original = false )
		{
			echo self::get_the_post_thumbnail($post_type, $thumb_id, $post_id, $size, $attr, $link_to_original);
		}
		
		function add_attachment_field($form_fields, $post)
		{
			$calling_post_id = 0;
			
			if( isset($_GET['post_id']) )
				$calling_post_id = absint($_GET['post_id']);
			elseif( isset($_POST) && count($_POST) ) // Like for async-upload where $_GET['post_id'] isn't set
				$calling_post_id = $post->post_parent;
				
			// check the post type to see if link needs to be added
			$calling_post = get_post($calling_post_id);
			
			if( !in_array( $calling_post->post_type, $this->m_whitelisted_post_types ) )
				return $form_fields;
				
			/*if ($calling_post && $calling_post->post_type != $this->post_type)
			{
				return $form_fields;
			}*/
			
			$ajax_nonce = wp_create_nonce("set_post_thumbnail-qody-{$this->post_type}-{$this->id}-{$calling_post_id}");
			$link = sprintf('<a id="qody-%4$s-%1$s-thumbnail-%2$s" class="%1$s-thumbnail" href="#" onclick="QodyThumbSetThumbnail(\'%2$s\', \'%1$s\', \'%4$s\', \'%5$s\');return false;">Set as %3$s</a>', $this->id, $post->ID, $this->label, $this->post_type, $ajax_nonce);
			
			$link .= "<style>.media-item .describe .qody-".$this->post_type."-".$this->id."-thumbnail th { padding-top: 0; }</style>";
			
			$form_fields["qody-{$this->post_type}-{$this->id}-thumbnail"] = array(
				'label' => $this->label,
				'input' => 'html',
				'html' => $link);
			return $form_fields;
		}
	}
}
?>