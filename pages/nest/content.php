<?php
$qody_plugins = $this->Overseer()->GetQodyPlugins();

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

$installed_plugins = get_plugins();
$installable_plugins = $this->GetInstallableOwls();

if( !is_writable( $this->GetAsset( 'ajax', 'install_plugin', 'dir' ) ) )
{
	$this->Log( "Plugin install script is not readable by your current server/permission setup", 'error' );
}

if( !is_writable( $this->GetAsset( 'ajax', 'activate_plugin', 'dir' ) ) )
{
	$this->Log( "Plugin activation script is not readable by your current server/permission setup", 'error' );
}
?>

<style>
#side-sortables.empty-container {
	border: none;
	height: 350px;
}
</style>

<div class="wrap qody_only_area">
	
	<div class="page-header">
		<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br></div>
		<h2><?php echo $this->m_page_title; ?> <small><?php echo $this->Helper('tools')->Clean( $page_title ); ?></small></h2>
	</div>
	
	<?php $this->Helper('postman')->DisplayMessages(); ?>

	<form action="<?php echo $this->GetAsset( 'forms', 'save', 'url' ); ?>" method="post" id="">
		
		<input type="hidden" id="check_url" value="<?php echo $this->GetAsset( 'ajax', 'version_check', 'url' ); ?>" />
		<input type="hidden" id="should_check" value="<?php echo $this->ShouldCheckForUpdates() ? 'yes' : 'no'; ?>" />
		
		<input type="hidden" id="install_script_url" value="<?php echo $this->GetAsset( 'ajax', 'install_plugin', 'url' ); ?>" />
		<input type="hidden" id="activate_script_url" value="<?php echo $this->GetAsset( 'ajax', 'activate_plugin', 'url' ); ?>" />
		
		<div id="poststuff" class="metabox-holder">
			<div id="post-body">
				<div id="post-body-content">
					<div id="normal-sortables" class="meta-box-sortables ui-sortable">
						
						<div class="row-fluid">
							<div class="span12">
								
								<table class="table table-bordered">
									<thead>
										<tr>
											<th>&nbsp;</th>
											<th>Owl In Charge</th>
											<th>Plugin Name</th>
											<th>Install</th>
											<th>Activate</th>
										</tr>
									</thead>
									<tbody>
										<?php
										foreach( $installable_plugins as $key => $value )
										{
											//$data = get_plugin_data( WP_PLUGIN_DIR.'/'.$value['plugin_url'], false );
											
											//if( $data['Name'] )
											if( file_exists( WP_PLUGIN_DIR.'/'.$value['plugin_url'] ) )
												$installed = true;
											else
												$installed = false;
											
											if( $installed && is_plugin_active( $value['plugin_url'] ) )
												$activated = true;
											else
												$activated = false;
											
											$show_activate_button = 'none'; ?>
										<tr>
											<td style="width:80px;">
												<img style="width:80px;" src="<?php echo $value['image_url']; ?>" />
											</td>
											<td style="vertical-align:middle;">
												<a target="_new" href="<?php echo $value['owl_url'] ? $value['owl_url'] : '#'; ?>" style="font-size:18px; font-weight:bold;"><?php echo $value['owl_name']; ?></a>
											</td>
											<td style="vertical-align:middle;">
												<span class="plugin_name"><?php echo $value['plugin_name']; ?></span>
											</td>
											<td style="vertical-align:middle; width:150px; text-align:center;" class="install_status_container">
												<?php
												if( $installed )
												{ ?>
												<span class="label label-success">complete</span>
												<?php										
												}
												else
												{ ?>
												<div class="btn-group">
													<button class="btn dropdown-toggle" data-toggle="dropdown">Click to Install <span class="caret"></span></button>
													<ul class="dropdown-menu">
														<li><a href="#" class="plugin_install" rel="<?php echo $value['download_url']; ?>" alt="<?php echo $value['plugin_url']; ?>">1-click Auto Install</a></li>
														<li class="divider"></li>
														<li><a target="_blank" href="<?php echo $value['download_url']; ?>">Manual Download</a></li>
													</ul>
												</div>
												<?php
												} ?>
											</td>
											<td style="vertical-align:middle; width:150px; text-align:center;">
												<?php
												if( $activated )
												{ ?>
												<span class="label label-success">complete</span>
												<?php										
												}
												else if( $installed )
												{
													$show_activate_button = 'inline-block';
												}
												else
												{
													$show_activate_button = 'none';
												} ?>
												<button class="btn plugin_activate" rel="<?php echo $value['plugin_url']; ?>" style="display:<?php echo $show_activate_button; ?>;">Activate Plugin</button>
											</td>
										</tr>
										<?php
										} ?>
										
									</tbody>
								</table>
								
							</div>
						</div>
					</div>
					
				</div>
			</div>
		</div>
	</form>
</div>
		





