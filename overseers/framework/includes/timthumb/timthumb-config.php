<?php
define( 'DEBUG_ON', false );
define( 'DEBUG_LEVEL', 1 );

// this is so images show up on MU sites
if( $_GET['blog_id'] )
	define( 'CUSTOM_MU_TIMTHUMB_BASE', 'wp-content/blogs.dir/'.$_GET['blog_id'].'/' ); // this needs to be dynamic

// this is so it doesn't cache the files inside the plugin itself
define( 'FILE_CACHE_DIRECTORY', '' );

$ALLOWED_SITES = array (
		'flickr.com',
		'staticflickr.com',
		'picasa.com',
		'img.youtube.com',
		'upload.wikimedia.org',
		'photobucket.com',
		'imgur.com',
		'imageshack.us',
		'tinypic.com',
		'qody.co'
	);
?>