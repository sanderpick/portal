<!-- proposal performance START -->
<div id="prop-performance" class="prop-section">
	<table>
		<caption>Estimated System Contribution*:</caption>
		<thead>
			<tr>
				<th align="left">Year 1 Energy Produced</th>
				<th align="left">Annual Energy Consumed</th>
				<th align="right">System Contribution</th>
			</tr>
		</thead>
		<tbody>
			<tr class="dark">
				<td class="cell-rebate cell-emphasis"><?php echo number_format($f->production); ?> kWh</td>
				<td class="cell-rebate cell-emphasis" align="left"><?php echo $job->job_kwh_load>0 ? number_format($job->job_kwh_load)." kWh" : "n / a"; ?></td>
				<td class="cell-rebate cell-emphasis" align="right"><?php echo $job->job_kwh_load>0 ? (round($f->production / $job->job_kwh_load * 10000)/100)."%" : "n / a"; ?></td>
			</tr>
		</tbody>
	</table>
	<?php echo $install_params; ?>
	<br /><br />
	<table>
		<caption>Estimated System Output Analysis*:</caption>
		<thead>
			<tr>
				<th align="left">Month</th>
				<th align="left">AC Energy Usage (kWh)</th>
				<th align="right">Solar Radiation (kWh/m<span class="super">2</span>/day)</th>
				<th align="right">AC Energy Output (kWh)</th>
				<th align="right">Energy Output Value ($)</th>
			</tr>
		</thead>
		<tbody>
			<tr class="dark">
				<td>Jan</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_1) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][0]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][0]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][0]; ?></td>
			</tr>
			<tr class="light">
				<td>Feb</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_2) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][1]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][1]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][1]; ?></td>
			</tr>
			<tr class="dark">
				<td>Mar</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_3) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][2]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][2]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][2]; ?></td>
			</tr>
			<tr class="light">
				<td>Apr</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_4) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][3]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][3]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][3]; ?></td>
			</tr>
			<tr class="dark">
				<td>May</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_5) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][4]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][4]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][4]; ?></td>
			</tr>
			<tr class="light">
				<td>Jun</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_6) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][5]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][5]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][5]; ?></td>
			</tr>
			<tr class="dark">
				<td>Jul</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_7) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][6]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][6]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][6]; ?></td>
			</tr>
			<tr class="light">
				<td>Aug</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_8) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][7]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][7]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][7]; ?></td>
			</tr>
			<tr class="dark">
				<td>Sep</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_9) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][8]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][8]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][8]; ?></td>
			</tr>
			<tr class="light">
				<td>Oct</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_10) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][9]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][9]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][9]; ?></td>
			</tr>
			<tr class="dark">
				<td>Nov</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_11) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][10]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][10]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][10]; ?></td>
			</tr>
			<tr class="light">
				<td>Dec</td>
				<td><?php echo $draw_bill ? number_format($job->job_bill_12) : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[0][11]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[1][11]; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][11]; ?></td>
			</tr>
			<tr class="dark">
				<td class="cell-rebate cell-emphasis">Year</td>
				<td class="cell-rebate cell-emphasis"><?php echo $draw_bill ? number_format($job_bill_total)." kWh" : "-"; ?></td>
				<td align="right" class="cell-rebate cell-emphasis"><?php echo $pro_pvwatts[0][12]; ?> <span style="font-size:12px;">kWh/m<span class="super">2</span>/day</span></td>
				<td align="right" class="cell-rebate cell-emphasis"><?php echo number_format($pro_pvwatts[1][12]); ?> kWh</td>
				<td align="right" class="cell-rebate cell-emphasis">$<?php echo number_format($pro_pvwatts[2][12]); ?></td>
			</tr>
		</tbody>
	</table>
	<br /><br />
	<div class="vis vis-area performance-graphs">
		<span class='caption'>Estimated System Output Analysis*:</span>
		<?php if($draw_bill) echo "<br /><br />"; ?>
		<table>
			<caption>– Electricity Usage Comparison (estimate) –</caption>
			<thead>
				<tr>
					<td></td>
					<th>Jan</th>
					<th>Feb</th>
					<th>Mar</th>
					<th>Apr</th>
					<th>May</th>
					<th>Jun</th>
					<th>Jul</th>
					<th>Aug</th>
					<th>Sep</th>
					<th>Oct</th>
					<th>Nov</th>
					<th>Dec</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>Electricity from Utility</th>
					<td><?php echo $job->job_bill_1; ?></td>
					<td><?php echo $job->job_bill_2; ?></td>
					<td><?php echo $job->job_bill_3; ?></td>
					<td><?php echo $job->job_bill_4; ?></td>
					<td><?php echo $job->job_bill_5; ?></td>
					<td><?php echo $job->job_bill_6; ?></td>
					<td><?php echo $job->job_bill_7; ?></td>
					<td><?php echo $job->job_bill_8; ?></td>
					<td><?php echo $job->job_bill_9; ?></td>
					<td><?php echo $job->job_bill_10; ?></td>
					<td><?php echo $job->job_bill_11; ?></td>
					<td><?php echo $job->job_bill_12; ?></td>
				</tr>
				<tr>
					<th>Electricity from Solar</th>
					<td><?php echo $pro_pvwatts[1][0]; ?></td>
					<td><?php echo $pro_pvwatts[1][1]; ?></td>
					<td><?php echo $pro_pvwatts[1][2]; ?></td>
					<td><?php echo $pro_pvwatts[1][3]; ?></td>
					<td><?php echo $pro_pvwatts[1][4]; ?></td>
					<td><?php echo $pro_pvwatts[1][5]; ?></td>
					<td><?php echo $pro_pvwatts[1][6]; ?></td>
					<td><?php echo $pro_pvwatts[1][7]; ?></td>
					<td><?php echo $pro_pvwatts[1][8]; ?></td>
					<td><?php echo $pro_pvwatts[1][9]; ?></td>
					<td><?php echo $pro_pvwatts[1][10]; ?></td>
					<td><?php echo $pro_pvwatts[1][11]; ?></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="vis vis-bar-performance performance-graphs">
		<table>
			<br />
			<caption>– Available Energy Value –</caption>
			<thead>
				<tr>
					<td></td>
					<th>Jan</th>
					<th>Feb</th>
					<th>Mar</th>
					<th>Apr</th>
					<th>May</th>
					<th>Jun</th>
					<th>Jul</th>
					<th>Aug</th>
					<th>Sep</th>
					<th>Oct</th>
					<th>Nov</th>
					<th>Dec</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th></th>
					<td><?php echo $pro_pvwatts[2][0]; ?></td>
					<td><?php echo $pro_pvwatts[2][1]; ?></td>
					<td><?php echo $pro_pvwatts[2][2]; ?></td>
					<td><?php echo $pro_pvwatts[2][3]; ?></td>
					<td><?php echo $pro_pvwatts[2][4]; ?></td>
					<td><?php echo $pro_pvwatts[2][5]; ?></td>
					<td><?php echo $pro_pvwatts[2][6]; ?></td>
					<td><?php echo $pro_pvwatts[2][7]; ?></td>
					<td><?php echo $pro_pvwatts[2][8]; ?></td>
					<td><?php echo $pro_pvwatts[2][9]; ?></td>
					<td><?php echo $pro_pvwatts[2][10]; ?></td>
					<td><?php echo $pro_pvwatts[2][11]; ?></td>
				</tr>
			</tbody>
		</table>
		<br /><br />
		<table>
			<caption>– Available Solar Radiation –</caption>
			<thead>
				<tr>
					<td></td>
					<th>Jan</th>
					<th>Feb</th>
					<th>Mar</th>
					<th>Apr</th>
					<th>May</th>
					<th>Jun</th>
					<th>Jul</th>
					<th>Aug</th>
					<th>Sep</th>
					<th>Oct</th>
					<th>Nov</th>
					<th>Dec</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th></th>
					<td><?php echo $pro_pvwatts[0][0]; ?></td>
					<td><?php echo $pro_pvwatts[0][1]; ?></td>
					<td><?php echo $pro_pvwatts[0][2]; ?></td>
					<td><?php echo $pro_pvwatts[0][3]; ?></td>
					<td><?php echo $pro_pvwatts[0][4]; ?></td>
					<td><?php echo $pro_pvwatts[0][5]; ?></td>
					<td><?php echo $pro_pvwatts[0][6]; ?></td>
					<td><?php echo $pro_pvwatts[0][7]; ?></td>
					<td><?php echo $pro_pvwatts[0][8]; ?></td>
					<td><?php echo $pro_pvwatts[0][9]; ?></td>
					<td><?php echo $pro_pvwatts[0][10]; ?></td>
					<td><?php echo $pro_pvwatts[0][11]; ?></td>
				</tr>
			</tbody>
		</table>
	</div>
	<table>
		<tfoot>
			<tr>
				<td class="cell-foot" colspan="4">* Data as calculated by <a href="http://rredc.nrel.gov/solar/calculators/PVWATTS/version1/" target="_blank">PVWATTS</a> from the National Renewable Energy Laboratory.</td>
			</tr>
		</tfoot>
	</table>
</div>
<!-- proposal performance END -->