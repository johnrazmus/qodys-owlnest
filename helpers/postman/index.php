<?php
class qodyHelper_FrameworkPostman extends QodyHelper
{
	var $m_display_count = 0;
	
	function __construct()
	{
		$this->SetOwner( func_get_args() );
		
		parent::__construct();
		
		// Prevents the notifications from being removed when called multiple times in a page load (themed page content)
		add_action( 'shutdown', array( $this, 'ClearMessageConditional' ) );
	}
	
	function GetPostfix()
	{
		$user_data = $this->GetUserData();
		
		$slug = '';
		
		if( $user_data['ID'] )
			$slug = '_'.$user_data['ID'];
		
		return $slug;
	}
	
	function GetDisplayCount()
	{
		$count = $this->get_option( 'qody_message_count'.$this->GetPostfix() );
		
		return $count;
	}
	
	function StoreDisplay()
	{
		$count = $this->get_option( 'qody_message_count'.$this->GetPostfix() );
		
		$this->update_option( 'qody_message_count'.$this->GetPostfix(), $count + 1 );
	}
	
	function ResetDisplayCount()
	{
		$this->update_option( 'qody_message_count'.$this->GetPostfix(), 0 );
	}
	
	function ClearMessageConditional()
	{
		if( $this->GetDisplayCount() > 0 )
			$this->RawDeleteMessage();
	}
	
	function RawGetMessage()
	{
		$qody_message = $this->get_option( 'qody_message'.$this->GetPostfix() );
		
		return $qody_message;
	}
	
	function RawUpdateMessage( $messages )
	{
		$this->update_option( 'qody_message'.$this->GetPostfix(), $messages );
	}
	
	function RawDeleteMessage()
	{
		$this->delete_option( 'qody_message'.$this->GetPostfix() );
		
		$this->ResetDisplayCount();
	}
	
	function Add( $type, $message )
	{
		$data = array();
		
		$data[ ($type == 'bad' ? 'errors' : 'results') ][] = $message;
		
		$this->SetMessage( $data );
	}
	
	function SetMessage( $new_message )
	{
		$current_messages = $this->Helper('tools')->DecodeResponse( $this->RawGetMessage() );
		
		if( !is_array( $new_message ) )
			$new_message = $this->Helper('tools')->DecodeResponse( $new_message );

		if( $new_message['results'] )
		{
			foreach( $new_message['results'] as $key => $value )
				$current_messages['results'][] = $value;
			
			$current_messages['results'] = array_unique( $current_messages['results'] );
		}
		
		if( $new_message['errors'] )
		{
			foreach( $new_message['errors'] as $key => $value )
				$current_messages['errors'][] = $value;
			
			$current_messages['errors'] = array_unique( $current_messages['errors'] );
		}
		
		$current_messages = $this->Helper('tools')->EncodeResponse( $current_messages );
		
		$this->RawUpdateMessage( $current_messages );
	}

	function DisplayMessages( $return = false, $preserve = true )
	{
		$message = $this->RawGetMessage();
		$message = $this->Helper('tools')->DecodeResponse( $message );
		
		$content = $this->MessagesStyles();
		
		if( $message )
		{
			if( isset( $message['errors'] ) )
			{
				foreach( $message['errors'] as $key => $value )
				{
					$content .= <<< CONT
			<div class="qody_message error_message"><p><strong>{$value}</strong></p></div>
CONT;
				}
			}
			
			if( isset( $message['results'] ) )
			{
				foreach( $message['results'] as $key => $value )
				{
					$content .= <<< CONT
			<div class="qody_message success_message"><p><strong>{$value}</strong></p></div>
CONT;
				}
			}
			
			$this->StoreDisplay();
			
			if( !$preserve )
				$this->RawDeleteMessage();
		}
		
		if( $return )
			return $content;
		
		echo $content;
	}
	
	function MessagesStyles()
	{
		$content = <<< CONT
	<style>
	/* message styles */
	.qody_message {position:relative;color:#565656;border:1px solid #f2eda1;background:#fefbd0 url("https://qody.s3.amazonaws.com/framework_plugin/images/notifications/notify_bg.png") 0 0 repeat-x;margin-bottom:10px;-moz-border-radius:3px;-webkit-border-radius:3px;border-radius:3px;}
	.qody_message p {padding:10px 10px 10px 35px;margin:0 !important;line-height:140%;background:url("https://qody.s3.amazonaws.com/framework_plugin/images/notifications/warning.png") 10px 50% no-repeat;}
	.qody_message.success_message {background-color:#f3fed0;border:1px solid #def2a1;}
	.qody_message.success_message p {background-image:url("https://qody.s3.amazonaws.com/framework_plugin/images/notifications/success.png");}
	.qody_message.error_message {background-color:#feeaea;border:1px solid #fadadb;}
	.qody_message.error_message p {background-image:url("https://qody.s3.amazonaws.com/framework_plugin/images/notifications/error.png");}
	.qody_message.information {background-color:#eaf8fe;border:1px solid #cde6f5;}
	.qody_message.information p {background-image:url("https://qody.s3.amazonaws.com/framework_plugin/images/notifications/info.png");}
	.qody_message.tip {border:1px solid #fdd845;background-color:#fff6bf;}
	.qody_message.tip p {background-image:url("https://qody.s3.amazonaws.com/framework_plugin/images/notifications/tooltip.png");}
	.qody_message.closeable {cursor:pointer;}
	.qody_message.closeable p {padding-right:15px;}
	.qody_message div.close {position:absolute;top:1px;right:4px;font:bold 13px Arial;/*text-indent:-999em;width:24px;height:24px;background:url("https://qody.s3.amazonaws.com/framework_plugin/images/notifications/close.png") 0 0 no-repeat;*/}
	</style>
CONT;
	
		return $content;
	}
}
?>