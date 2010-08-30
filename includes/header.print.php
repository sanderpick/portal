<!-- print header START -->
<div class="proposal-head">
	<table style="width:662px;">
		<tr>
			<td style="padding:0 0 6px 0; vertical-align:bottom;"><h1 class="page-head"><span style="font-weight:bold;"><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> <?php echo $current_section; ?></h1></td>
			<td style="padding:0 0 4px 0;" align="right"><img src="gfx/logo-black.png" alt="small logo" /></td>
		</tr>
	</table>
</div>
<div class="page proposal-page">
	<table>
		<tbody>
			<tr>
				<td style="padding:0;">
					<p style="font-size:9pt;">
						<span style="font-size:11pt;">PV Proposal #<?php echo $pro->ID; ?> prepared for:</span>
						<br />
						<?php echo $job_html; ?>
					</p>
				</td>
				<td style="padding:0; float:right;">
					<p style="font-size:9pt; text-align:right;">
						<span style="font-size:11pt;">prepared by:</span><br />
						<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br />
						<?php echo $rep->rep_email; ?> (e)<br />
						<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br />
						<?php echo $off->off_city; ?>, 
						<?php echo $off->off_state; ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	<br /><br />
<!-- print header END (less </div>) -->