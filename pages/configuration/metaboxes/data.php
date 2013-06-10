
<fieldset>
	<legend>PRO Features Unlock</legend>
	
	<?php $nextItem = 'membership_key'; ?>
	<?php $nextValue = $this->get_option( $nextItem ); ?>
	<div class="control-group">
		<label class="control-label" for="<?php echo $nextItem; ?>">Nexus Membership Key</label>
		<div class="controls">
			<div class="input-append">
				<input type="text" class="span3" id="<?php echo $nextItem; ?>" name="<?php echo $nextItem; ?>" value="<?php echo $nextValue; ?>">
				<span class="add-on">
					<i class="icon-briefcase"></i>
				</span>
				
			</div>
			<span class="help-inline">
				<a target="_blank" href="http://qody.co/membership-perks/">get key here</a>
			</span>
			<span class="help-block">
				By being a member of Qody's Nexus, you'll receive a unique secret key used here to unlock the 
				PRO features of all Qody plugins installed on this site.
			</span>
		</div>
	</div>

</fieldset>
