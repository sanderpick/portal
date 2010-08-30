<!-- proposal header START -->
<div id="prop-header">
	<table>
		<tbody>
			<tr>
				<td style="padding:0;">
					<p style="font-size:10px;">
						<span style="font-size:14px;">PV Proposal #<?php echo $pro->ID; ?> prepared for:</span><br />
						<?php echo $job_html; ?>
					</p>
				</td>
				<td style="padding:0; float:right;">
					<p style="font-size:10px; text-align:right;">
						<span style="font-size:14px;">prepared by:</span><br />
						<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br />
						<a href="mailto:<?php echo $rep->rep_email; ?>"><?php echo $rep->rep_email; ?></a> (e)<br />
						<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br />
						<?php echo $off->off_city.", ".$off->off_state; ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	<br /><br />
</div>
<!-- proposal header END -->