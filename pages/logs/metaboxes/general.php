<?php
$logs = $this->GetLogs();
?>

<?php
if( $logs )
{ ?>
<div class="row-fluid" style="margin-bottom:10px;">
	<div class="span12">
		<button class="btn pull-right">clear all <?php echo count( $logs ); ?> logs</button>
	</div>
</div>

<input type="hidden" name="plugin_global" value="<?php echo str_replace( '-', '_', $this->GetOverseer()->Owner()->m_plugin_slug ); ?>" />
<?php
} ?>

<table class="table table-striped table-bordered table-condensed">
	<thead>
		<tr>
			<th>Time</th>
			<th>Data</th>
			<th>Type</th>
		</tr>
	</thead>
	<tbody>
	<?php
	if( $logs )
	{
		$timeNow = $this->time();
		foreach( $logs as $key => $value )
		{
			if( $value['type'] == 'error' )
				$color = '#cc0000';
			else if( $value['type'] == 'success' )
				$color = '#009900';
			else
				$color = '';
			 ?>
		<tr>
			<td><?php echo $this->Helper('tools')->NumberTimeToStringTime( $timeNow - $value['date'] ); ?> ago</td>
			<td style="color:<?php echo $color; ?>"><?php echo $value['data']; ?></td>
			<td><?php echo $value['type']; ?></td>
		</tr>
		<?php
		}
	}
	else
	{ ?>
		<tr>
			<td colspan="3" style="text-align:center;">no logs to show yet</td>
		</tr>
	<?php
	}?>
	</tbody>
</table>