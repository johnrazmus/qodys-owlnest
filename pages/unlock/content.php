<?php
$license_key = $this->Overseer()->GetLicenseKey();
$key_passed = $this->Overseer()->VerifyLicense();

$plugin_variable_name = str_replace( '-', '_', $this->Overseer()->Owner()->m_plugin_slug );
?>

<div class="wrap qody_only_area">
	
	<div class="page-header">
		<div id="icon-edit-pages" class="icon32 icon32-posts-page"><br></div>
		<h2>Enter your O.I.N Key to enable Qody's Owls <small><?php echo $this->Helper('tools')->Clean( $page_title ); ?></small></h2>
	</div>
	
	<?php $this->Helper('postman')->DisplayMessages(); ?>
	
	<style>
	.fade_it {
	   opacity:0.5;
	   transition: opacity .25s ease-in-out;
	   -moz-transition: opacity .25s ease-in-out;
	   -webkit-transition: opacity .25s ease-in-out;
	   }
	
	   .fade_it:hover {
		  opacity: 1.0;
		  }
	</style>
	
	<div id="poststuff" class="metabox-holder">
		<div id="post-body">
			<div id="post-body-content">
				
				<form action="<?php echo $this->GetAsset( 'forms', 'unlock', 'url' ); ?>" method="post" class="form-inline">
					
					<div class="row-fluid">
						
						<div class="span1"></div>
						<div class="span10">
							
							<div class="well" style="padding-bottom:19px;">
								
								<div class="row-fluid">
									<div class="span3">
										<img src="https://qody.s3.amazonaws.com/owls/qody/qody.png" />
									</div>
									<div class="span6">
										
										<?php
										if( $key_passed )
										{ ?>
										<h1 style="color:#090; margin-top:10px; font-size:34px;">Owls are clocked in!</h1>
										
										<div style="padding-bottom:10px; font-size:14px;">
											<p>To clock out & change the O.I.N Key, click the button below.</p> 
										</div>
										
										<label class="control-label" for="">O.I.N Key</label>
										<div class="row-fluid">
											<div class="span8">
												<input type="text" class="span12" name="license_key" id="oin_input" placeholder="ex. 3NTcyMyAxMjkwMj..." value="<?php echo $license_key; ?>">
											</div>
											<div class="span4">
												<button class="btn btn-danger">Clock Out</button>
											</div>
										</div>
										<input type="hidden" name="action" value="clock_out" />
										<?php
										}
										else
										{ ?>
										<h1><?php echo $this->Owner()->GetOwlName(); ?> must clock in!</h1>
										
										<div style="padding-bottom:10px; font-size:14px;">
											<p>Before you can use any of Qody's plugins or features, you must enter your unique O.I.N key. This 
											can be found on your O.I.N key management page 
											<a target="_blank" href="http://qody.co/my-owls/oin-keys/">here in your dashboard.</a></p>
											
											<p>If you're not yet a member of Qody's Nexus, 
											<a target="_blank" href="http://qody.co/owl/nexus-membership">learn about why it's awesome here.</a></p>
										</div>
										
										<label class="control-label" for="">O.I.N Key</label><br>
										<div class="row-fluid">
											<div class="span8">
												<input type="text" class="span12" name="license_key" id="oin_input" placeholder="ex. 3NTcyMyAxMjkwMj...">
											</div>
											<div class="span4">
												<button class="btn btn-primary">Clock In</button>
											</div>
										</div>
										<?php
										} ?>
										
									</div>
									<div class="span3">
										<img class="fade_it" src="https://qody.s3.amazonaws.com/owls/qody/<?php echo $key_passed ? 'un' : ''; ?>lock.png" />
									</div>
								</div>
								
							</div>
							
						</div>
						
					</div>
				
				</form>
				
			</div>
		</div>
	</div>
			
</div>
