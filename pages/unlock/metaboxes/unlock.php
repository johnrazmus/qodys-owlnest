<?php
$license_key = $this->Overseer()->GetLicenseKey();
$key_passed = $this->Overseer()->VerifyLicense();

$plugin_variable_name = str_replace( '-', '_', $this->Overseer()->Owner()->m_plugin_slug );
?>

<input type="hidden" name="plugin_global_variable" value="<?php echo $plugin_variable_name; ?>" />

<div class="row-fluid">
	<div class="span12">
		
		<div class="well" style="margin:5% 10%;">
			<div class="row-fluid">
				
				<div class="span4">
					<img style="width:200px;" src="<?php echo $this->GetOwlImage(); ?>" />
				</div>
				<div class="span8">
					
					<?php
					if( $key_passed )
					{ ?>
					<h1 style="color:#090; margin-top:50px;"><?php echo $this->Owner()->GetOwlName(); ?> is clocked in!</h1>
					
					<div style="padding-bottom:10px; font-size:14px;">
						<p>To clock out & change the O.I.N, click the button below.</p> 
					</div>
					
					<label class="control-label" for="oin_input" style="line-height:26px; margin:0px 5px 0px 0px;">O.I.N</label>
					
					<input type="text" name="license_key" readonly id="oin_input" class="input-xlarge api_key" value="<?php echo $license_key; ?>" placeholder="Enter O.I.N key">
					<button class="btn btn-primary">Clock Out</button>
					
					<input type="hidden" name="action" value="clock_out" />
					<?php
					}
					else
					{ ?>
					<h1><?php echo $this->Owner()->GetOwlName(); ?> must clock in!</h1>
					
					<div style="padding-bottom:10px; font-size:14px;">
						<p>Before you can use this plugin, you must have already hired <strong><?php echo $this->Owner()->GetOwlName(); ?></strong> 
						to manage it for you! If you haven't yet purchased access to this plugin, 
						<a target="_blank" href="<?php echo $this->Owner()->Owner()->m_owl_buy_url; ?>">click here to buy it</a>.</p>
						
						<p>If you've already hired <?php echo $this->Owner()->GetOwlName(); ?>, enter an <strong>Owl Identification Number (O.I.N)</strong> 
						below to get started. 
						<a target="_blank" href="http://nexus.qody.co/oin-keys/" style="font-weight:strong;">Click here to manage your O.I.N keys</a>.</p> 
					</div>
					
					<label class="control-label" for="oin_input" style="line-height:26px; margin:0px 5px 0px 0px;">O.I.N</label>
					
					<input type="text" name="license_key" id="oin_input" class="input-xlarge" placeholder="Enter O.I.N key">
					<button class="btn btn-primary">Clock In</button>
					<?php
					} ?>
					
				</div>
				
			</div>			
		</div>
		
	</div>
</div>