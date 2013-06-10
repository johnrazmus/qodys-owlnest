<?php
$url = "http://plugins.qody.co/v/";

$real_key 			= $_GET['p'];
$plugin_name 		= str_replace( '\\', '', $_GET['n'] );
$current_version 	= $_GET['cv'];
$nest_url 			= $_GET['nest'];

$fields = array();
$fields['p'] = $real_key;

$url .= '?'.http_build_query( $fields );

if( $fh = fopen($url, "r") )
{
	$data = null;
	
	while (!feof($fh)) {
		$version .= fread($fh, 1024);
	}
	
	if( !empty($version) )
	{
		if( $version != $_GET['cv'] && $version != -1 )
		{ ?>
		<div class="update-message">
			There is a new version of <?php echo $plugin_name; ?> available. 
			<a href="http://plugins.qody.co/download/?p=<?php echo $real_key; ?>" title="Download Latest Version" target="_blank">
				Download version <?php echo $version; ?></a> 
			or 
			<a href="<?php echo $nest_url; ?>?p=<?php echo $real_key; ?>" title="Auto-Update" target="_blank">
				update automatically</a>.
			
		</div>
		<?php
		}
	}
	else
	{
		echo 'failed to retreive version number remotely';
	}
}
else
{
	echo 'failed to open update check script';
}?>