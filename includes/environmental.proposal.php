<!-- proposal environmental START -->
<div id="prop-environmental" class="prop-section">
	<table>
		<caption>Environmental Impacts:</caption>
		<tbody>
			<tr class="dark">
				<td class="cell-rebate cell-emphasis" colspan="2">Estimated Annual Carbon Offset</td>
				<td class="cell-rebate cell-emphasis" colspan="1" align="right"><?php echo number_format($f->production*1.71); ?> lbs. of Carbon Dioxide*</td>
			</tr>
		</tbody>
	</table>
	<br /><br />
	<table>
		<caption>System Offset Parameters:</caption>
		<thead>
			<tr>
				<th align="left">Year 1 Energy Produced</th>
				<th align="left">Annual Energy Consumed</th>
				<th align="right">System Contribution</th>
				<th align="right">Annual Carbon Offset</th>
			</tr>
		</thead>
		<tbody>
			<tr class="dark">
				<td><?php echo number_format($f->production); ?> kWh</td>
				<td align="left"><?php echo $job->job_kwh_load>0 ? number_format($job->job_kwh_load)." kWh" : "n / a"; ?></td>
				<td align="right"><?php echo $job->job_kwh_load>0 ? (round($f->production / $job->job_kwh_load * 10000)/100)."%" : "n / a"; ?></td>
				<td align="right"><?php echo number_format($f->production*1.71); ?> lbs CO<span class="sub">2</span></td>
			</tr>
			<tr class="light">
				<td colspan="4">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="4" style="padding:0;">
					<div class="left cell-round" style="background:#eee url(img/trees.jpg) center no-repeat;"><p><em>save</em> Trees</p></div>
					<div class="right cell-round" style="background:#eee url(img/oil.jpg) center no-repeat;"><p>help <em>avoid</em> Oil</p></div>
					<div class="cell-round" style="margin:0 222px; background:#eee url(img/cars.jpg) center no-repeat;"><p><em>reduce</em> Pollution</p></div>
					<div class="clear"></div>
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td class="cell-foot" colspan="4">
					* These figures are based on data obtained from the National Renewable Energy Laboratory (NREL).
				</td>
			</tr>
		</tfoot>
	</table>
</div>
<!-- proposal environmental END -->