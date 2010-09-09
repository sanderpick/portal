<!-- proposal environmental START -->
<div id="prop-environmental" class="prop-section">
	<table>
		<caption>Environmental Offset Parameters:</caption>
		<tbody class="tabled">
			<tr class="dark">
				<td>1st Year Solar Energy Production</td>
				<td align="right"><?php echo number_format($f->production); ?> kWh</td>
			</tr>
			<tr class="light">
				<td>Overall Annual Energy Consumed</td>
				<td align="right"><?php echo $job->job_kwh_load>0 ? number_format($job->job_kwh_load)." kWh" : "n / a"; ?></td>
			</tr>
			<tr class="dark">
				<td>System Contribution</td>
				<td align="right"><?php echo $job->job_kwh_load>0 ? (round($f->production / $job->job_kwh_load * 10000)/100)."%" : "n / a"; ?></td>
			</tr>
			<tr>
				<td class="big darker round-l">Estimated Annual Carbon Offset</td>
				<td align="right" class="big darker round-r"><?php echo number_format($f->production*1.71); ?> lbs CO<span class="sub">2</span>*</td>
			</tr>
		</tbody>
	</table>
	<br /><br />
	<table>
		<tbody>
			<tr>
				<td style="padding:0;">
					<div class="left cell-round" style="background:#eee url(img/trees.jpg) center no-repeat;"><p><em>save</em> Trees</p></div>
					<div class="right cell-round" style="background:#eee url(img/oil.jpg) center no-repeat;"><p>help <em>avoid</em> Oil</p></div>
					<div class="cell-round" style="margin:0 222px; background:#eee url(img/cars.jpg) center no-repeat;"><p><em>reduce</em> Pollution</p></div>
					<div class="clear"></div>
				</td>
			</tr>
		</tbody>
	</table>
	<br />
	<table>
		<tfoot>
			<tr>
				<td class="cell-foot">
					* These figures are based on data obtained from the National Renewable Energy Laboratory (NREL).
				</td>
			</tr>
		</tfoot>
	</table>
</div>
<!-- proposal environmental END -->