<!-- proposal performance START -->
<div id="prop-performance" class="prop-section">
	<table style="width:60%;">
		<caption>Estimated System Contribution*:</caption>
		<tbody class="tabled">
			<tr class="dark">
				<td>1st Year Solar Energy Production</td>
				<td align="right"><?php echo number_format($f->production); ?> kWh</td>
			</tr>
			<tr class="light">
				<td>Overall Annual Energy Consumed</td>
				<td align="right"><?php echo $job->job_kwh_load>0 ? number_format($job->job_kwh_load)." kWh" : "n / a"; ?></td>
			</tr>
			<tr>
				<td class="big darker round-l">System Contribution</td>
				<td class="big darker round-r" align="right"><?php echo $job->job_kwh_load>0 ? (round($f->production / $job->job_kwh_load * 10000)/100)."%" : "n / a"; ?></td>
			</tr>
		</tbody>
	</table>
	<div style="margin:0 0 0 60%; position:relative; <?php echo $job->job_kwh_load>0 ? "" : "display:none;"; ?>">
		<div class="vis vis-pie" style="position:absolute; left:70px; top:-108px;">
			<table>
				<caption>Energy Distribution</caption>
				<thead>
					<tr>
						<td></td>
						<th>kWh</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th>Energy from Utility</th>
						<td><?php echo $job->job_kwh_load - $f->production; ?></td>
					</tr>
					<tr>
						<th>Energy from Solar</th>
						<td><?php echo $f->production; ?></td>
					</tr>	
				</tbody>
			</table>
		</div>
	</div>
	<?php echo $install_params; ?>
	<br /><br />
	<table>
		<caption>Estimated System Output Analysis*:</caption>
		<thead>
			<tr>
				<th align="left">Month</th>
				<th align="right">Solar Radiation (kWh/m<span class="super">2</span>/day)</th>
				<th align="right">AC Energy Usage (kWh)</th>
				<th align="right">AC Energy Output (kWh)</th>
				<th align="right">% System Contribution</th>
				<th align="right">Energy Output Value ($)</th>
			</tr>
		</thead>
		<tbody>
			<tr class="dark">
				<td>Jan</td>
				<td align="right"><?php echo $pro_pvwatts[0][0]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_1) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][0]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][0]/$job->job_bill_1)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][0]; ?></td>
			</tr>
			<tr class="light">
				<td>Feb</td>
				<td align="right"><?php echo $pro_pvwatts[0][1]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_2) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][1]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][1]/$job->job_bill_2)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][1]; ?></td>
			</tr>
			<tr class="dark">
				<td>Mar</td>
				<td align="right"><?php echo $pro_pvwatts[0][2]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_3) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][2]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][2]/$job->job_bill_3)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][2]; ?></td>
			</tr>
			<tr class="light">
				<td>Apr</td>
				<td align="right"><?php echo $pro_pvwatts[0][3]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_4) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][3]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][3]/$job->job_bill_4)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][3]; ?></td>
			</tr>
			<tr class="dark">
				<td>May</td>
				<td align="right"><?php echo $pro_pvwatts[0][4]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_5) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][4]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][4]/$job->job_bill_5)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][4]; ?></td>
			</tr>
			<tr class="light">
				<td>Jun</td>
				<td align="right"><?php echo $pro_pvwatts[0][5]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_6) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][5]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][5]/$job->job_bill_6)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][5]; ?></td>
			</tr>
			<tr class="dark">
				<td>Jul</td>
				<td align="right"><?php echo $pro_pvwatts[0][6]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_7) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][6]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][6]/$job->job_bill_7)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][6]; ?></td>
			</tr>
			<tr class="light">
				<td>Aug</td>
				<td align="right"><?php echo $pro_pvwatts[0][7]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_8) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][7]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][7]/$job->job_bill_8)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][7]; ?></td>
			</tr>
			<tr class="dark">
				<td>Sep</td>
				<td align="right"><?php echo $pro_pvwatts[0][8]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_9) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][8]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][8]/$job->job_bill_9)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][8]; ?></td>
			</tr>
			<tr class="light">
				<td>Oct</td>
				<td align="right"><?php echo $pro_pvwatts[0][9]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_10) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][9]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][9]/$job->job_bill_10)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][9]; ?></td>
			</tr>
			<tr class="dark">
				<td>Nov</td>
				<td align="right"><?php echo $pro_pvwatts[0][10]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_11) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][10]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][10]/$job->job_bill_11)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][10]; ?></td>
			</tr>
			<tr class="light">
				<td>Dec</td>
				<td align="right"><?php echo $pro_pvwatts[0][11]; ?></td>
				<td align="right"><?php echo $draw_bill ? number_format($job->job_bill_12) : "-"; ?></td>
				<td align="right"><?php echo number_format($pro_pvwatts[1][11]); ?></td>
				<td align="right"><?php echo $draw_bill ? round(10000*$pro_pvwatts[1][11]/$job->job_bill_12)/100 : "-"; ?></td>
				<td align="right"><?php echo $pro_pvwatts[2][11]; ?></td>
			</tr>
			<tr>
				<td class="big darker round-l">Year</td>
				<td align="right" class="big darker"><?php echo $pro_pvwatts[0][12]; ?> <span style="font-size:12px;">kWh/m<span class="super">2</span>/day</span></td>
				<td align="right" class="big darker"><?php echo $draw_bill ? number_format($job_bill_total)." kWh" : "-"; ?></td>
				<td align="right" class="big darker"><?php echo number_format($pro_pvwatts[1][12]); ?> kWh</td>
				<td align="right" class="big darker"><?php echo $draw_bill ? (round(10000*$pro_pvwatts[1][12]/$job_bill_total)/100)."%" : "-"; ?></td>
				<td align="right" class="big darker round-r">$<?php echo number_format($pro_pvwatts[2][12]); ?></td>
			</tr>
		</tbody>
	</table>
	<br />
	<table>
		<tfoot>
			<tr>
				<td class="cell-foot" colspan="4">* Data as calculated by <a href="http://rredc.nrel.gov/solar/calculators/PVWATTS/version1/" target="_blank">PVWATTS</a> from the National Renewable Energy Laboratory.</td>
			</tr>
		</tfoot>
	</table>
	<br /><br />
	<div class="vis vis-area performance-graphs bill-comparison">
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
	<?php if($draw_bill) echo "<br />"; ?>
	<div class="vis vis-bar-performance performance-graphs">
		<table>	
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
		<br />
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
</div>
<!-- proposal performance END -->