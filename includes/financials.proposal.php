<?php
// build the financials
require_once("analysis.classes.php");
// $analysis = new SolarAnalysis(5.50,6992,0.0098,20369,0.066,0.12,30,10000,0,0,0.30,3876);
$analysis = new SolarAnalysis(
	$f->size, // system size
	$f->production, // system production
	0.0098, // system derate
	$f->cus_after_credit_nf, // system cost
	0.066, // system rate increase
	$zones[0]->zon_erate/100, // system utility price
	30, // system term (years)
	$job->job_kwh_load, // system energy usage
	$pro->pro_incentive_rate, // system sorec rate
	$pro->pro_incentive_yrs, // system sorec term (years)
 	0.30, // system tax bracket
	$total_inverter_price // system inverter price
);
// build annual data
for($i=0;$i<$analysis->sys_life;$i++) {
	$sy = new SolarYear($analysis,$i);
	$analysis->total_output += $sy->output;
	$analysis->total_savings += $sy->solar_savings;
	$analysis->total_elec_bill_no_solar += $sy->elec_bill_no_solar;
	$analysis->total_elec_bill_solar += $sy->elec_bill_solar;
	$analysis->total_sorec_rev -= $sy->sorec_rev;
	$analysis->total_profit = $sy->cashflow;
	$analysis->payback_yrs += $sy->simple_payback;
	$analysis->annual_data[] = $sy;
}
// compile data
$analysis->finish();
?>
<!-- proposal financials START -->
<div id="prop-financials" class="prop-section">
	<table>
		<caption>Financial Analysis:</caption>
		<thead>
			<tr>
				<th style="vertical-align:bottom;" align="left">System<br />Size</th>
				<th style="vertical-align:bottom;" class="cell-small-indent" align="left">1st Year<br />Energy<br />Production</th>
				<th style="vertical-align:bottom;" class="cell-small-indent" align="left">Annual<br />Energy<br />Consumed</th>
				<th style="vertical-align:bottom;" class="cell-small-indent" align="left">Net Cost or<br />Investment*</th>
				<th style="vertical-align:bottom;" class="cell-small-indent" align="left">Utility<br />Rate</th>
				<th style="vertical-align:bottom;" class="cell-small-indent" align="right">Levelized<span class="super">1</span><br />Value of<br />Solar Energy</th>
			</tr>
		</thead>
		<tbody>
			<tr class="dark">
				<td><?php echo $f->size; ?> kW</td>
				<td align="left"><?php echo number_format($f->production); ?> kWh</td>
				<td align="left"><?php echo number_format($job->job_kwh_load); ?> kWh</td>
				<td align="left">$<?php echo $f->cus_after_credit; ?></td>
				<td align="left">$<?php echo $zones[0]->zon_erate/100; ?> / kWh</td>
				<td align="right">$<?php echo round(1000*$analysis->lcoe_solar_energy)/1000; ?> / kWh</td>
			</tr>
		</tbody>
	</table>
	<br /><br />
	<div id="financials-table">
		<table>
			<caption>30 Year Summary:</caption>
			<thead>
				<tr>
					<th align="left">Year</th>
					<th align="right">Electric Bill<br />WITHOUT Solar</th>
					<th align="right">Electric Bill<br />WITH Solar</th>
					<th align="right">Annual Savings</th>
					<th align="right">Payback on Solar Investment<br />(includes inverter replacement)</th>
				</tr>
			</thead>
			<tbody>
				<tr class="dark">
					<td>0</td>
					<td align="right">-</td>
					<td align="right">-</td>
					<td align="right">-</td>
					<td align="right" style="color:red;">$-<?php echo number_format($analysis->sys_cost); ?></td>
				</tr>
				<?php
					$rows = ""; $bc = "light";
					for($n=0;$n<$analysis->sys_life;$n++) {
						$c = $analysis->annual_data[$n]->cashflow > 0 ? "green" : "red";
						$rows .= '<tr class="'.$bc.'">';
						$rows .= 	'<td>'.($n+1).'</td>';
						$rows .= 	'<td align="right">$'.number_format($analysis->annual_data[$n]->elec_bill_no_solar).'</td>';
						$rows .= 	'<td align="right">$'.number_format($analysis->annual_data[$n]->elec_bill_solar).'</td>';
						$rows .= 	'<td align="right">$'.number_format($analysis->annual_data[$n]->cum_savings_solar).'</td>';
						$rows .= 	'<td align="right" style="color:'.$c.'">$'.number_format($analysis->annual_data[$n]->cashflow).'</td>';
						$rows .= '</tr>';
						$bc = $bc=="dark" ? "light" : "dark";
					}
					echo $rows;
				?>
				<tr class="light">
					<td class="cell-rebate cell-emphasis">Total</td>
					<td align="right" class="cell-rebate cell-emphasis">$<?php echo number_format($analysis->total_elec_bill_no_solar); ?></td>
					<td align="right" class="cell-rebate cell-emphasis">$<?php echo number_format($analysis->total_elec_bill_solar); ?></td>
					<td align="right" class="cell-rebate cell-emphasis">$<?php echo number_format($analysis->total_savings); ?></td>
					<td align="right" class="cell-rebate cell-emphasis">&nbsp;</td>
				</tr>
			</tbody>
		</table>
		<br /><br />
	</div>
	<div class="vis vis-bar-financial financials-graphs">
		<span class="caption">30 Year Summary:</span>
		<br /><br />
		<table>
			<caption>– Annual Electricity Bill Comparison –</caption>
			<thead>
				<tr>
					<td></td>
					<th>1</th>
					<th>2</th>
					<th>3</th>
					<th>4</th>
					<th>5</th>
					<th>6</th>
					<th>7</th>
					<th>8</th>
					<th>9</th>
					<th>10</th>
					<th>11</th>
					<th>12</th>
					<th>13</th>
					<th>14</th>
					<th>15</th>
					<th>16</th>
					<th>17</th>
					<th>18</th>
					<th>19</th>
					<th>20</th>
					<th>21</th>
					<th>22</th>
					<th>23</th>
					<th>24</th>
					<th>25</th>
					<th>26</th>
					<th>27</th>
					<th>28</th>
					<th>29</th>
					<th>30</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>Pre-Solar</th>
					<td><?php echo round($analysis->annual_data[0]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[1]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[2]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[3]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[4]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[5]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[6]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[7]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[8]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[9]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[10]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[11]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[12]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[13]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[14]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[15]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[16]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[17]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[18]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[19]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[20]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[21]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[22]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[23]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[24]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[25]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[26]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[27]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[28]->elec_bill_no_solar); ?></td>
					<td><?php echo round($analysis->annual_data[29]->elec_bill_no_solar); ?></td>
				</tr>
				<tr>
					<th>Post-Solar</th>
					<td><?php echo round($analysis->annual_data[0]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[1]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[2]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[3]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[4]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[5]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[6]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[7]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[8]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[9]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[10]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[11]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[12]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[13]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[14]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[15]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[16]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[17]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[18]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[19]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[20]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[21]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[22]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[23]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[24]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[25]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[26]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[27]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[28]->elec_bill_solar); ?></td>
					<td><?php echo round($analysis->annual_data[29]->elec_bill_solar); ?></td>
				</tr>
			</tbody>
		</table>
		<br /><br />
		<table>
			<caption>– Cumulative Utility Savings from Solar Energy –</caption>
			<thead>
				<tr>
					<td></td>
					<th>1</th>
					<th>2</th>
					<th>3</th>
					<th>4</th>
					<th>5</th>
					<th>6</th>
					<th>7</th>
					<th>8</th>
					<th>9</th>
					<th>10</th>
					<th>11</th>
					<th>12</th>
					<th>13</th>
					<th>14</th>
					<th>15</th>
					<th>16</th>
					<th>17</th>
					<th>18</th>
					<th>19</th>
					<th>20</th>
					<th>21</th>
					<th>22</th>
					<th>23</th>
					<th>24</th>
					<th>25</th>
					<th>26</th>
					<th>27</th>
					<th>28</th>
					<th>29</th>
					<th>30</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<th>Cumulative Utility Savings</th>
					<td><?php echo round($analysis->annual_data[0]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[1]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[2]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[3]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[4]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[5]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[6]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[7]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[8]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[9]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[10]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[11]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[12]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[13]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[14]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[15]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[16]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[17]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[18]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[19]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[20]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[21]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[22]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[23]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[24]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[25]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[26]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[27]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[28]->cum_savings_solar); ?></td>
					<td><?php echo round($analysis->annual_data[29]->cum_savings_solar); ?></td>
				</tr>
				<!-- <tr>
					<th>Solar Investment (w/ Inverter Replacement and SOREC if applicable)</th>
					<td><php echo round($analysis->annual_data[0]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[1]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[2]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[3]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[4]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[5]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[6]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[7]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[8]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[9]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[10]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[11]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[12]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[13]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[14]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[15]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[16]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[17]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[18]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[19]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[20]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[21]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[22]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[23]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[24]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[25]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[26]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[27]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[28]->simple_payback_sorec); ?></td>
					<td><php echo round($analysis->annual_data[29]->simple_payback_sorec); ?></td>
				</tr> -->
			</tbody>
		</table>
		<br /><br />
	</div>
	<table>
		<caption>Financial Summary:</caption>
		<tbody>
			<tr class="dark">
				<td style="font-weight:bold;" colspan="4">30 Year Cumulative Electric Cost WITHOUT Solar</td>
				<td style="font-weight:bold;" colspan="2" align="right">$<?php echo number_format($analysis->total_elec_bill_no_solar); ?></td>
			</tr>
			<tr class="light">
				<td style="font-weight:bold;" colspan="4">30 Year Cumulative Electric Cost WITH Solar</td>
				<td style="font-weight:bold;" colspan="2" align="right">$<?php echo number_format($analysis->total_elec_bill_solar); ?></td>
			</tr>
			<tr><td colspan="6">&nbsp;</td></tr>
			<tr class="dark">
				<td style="font-weight:bold;" colspan="4">Your total energy cost savings from solar over 30 years</td>
				<td style="font-weight:bold;" colspan="2" align="right">$<?php echo number_format($analysis->elec_savings); ?></td>
			</tr>
			<?php $bc = "light"; if($analysis->total_sorec_rev!=0) { ?>
			<tr class="<?php echo $bc; ?>">
				<td style="font-weight:bold;" colspan="4">SREC Revenue</td>
				<td style="font-weight:bold;" colspan="2" align="right">$<?php echo number_format($analysis->total_sorec_rev); ?></td>
			</tr>
			<?php $bc = $bc=="dark" ? "light" : "dark"; } ?>
			<tr class="<?php echo $bc; $bc = $bc=="dark" ? "light" : "dark"; ?>">
				<td style="font-weight:bold;" colspan="4">Total System Cost</td>
				<td style="font-weight:bold;" colspan="2" align="right">$-<?php echo number_format($analysis->sys_cost); ?></td>
			</tr>
			<tr class="<?php echo $bc; ?>">
				<td class="cell-rebate cell-emphasis" colspan="4">Total Profit from Solar Investment</td>
				<td class="cell-rebate cell-emphasis" colspan="2" align="right"><strong>$<?php echo number_format($analysis->total_profit); ?></strong></td>
			</tr>
			<tr><td colspan="6">&nbsp;</td></tr>
			<tr class="dark">
				<td style="font-weight:bold;" colspan="4">Your Simple Cash Payback</td>
				<td style="font-weight:bold;" colspan="2" align="right"><?php echo $analysis->payback_yrs; ?> Years</td>
			</tr>
			<tr><td colspan="6">&nbsp;</td></tr>
			<tr class="dark">
				<td style="font-weight:bold;" colspan="4">30 Year Yield on Investment (post-tax IRR)<span class="super">2,&spades;</span></td>
				<td style="font-weight:bold;" colspan="2" align="right"><?php echo round($analysis->irr_post_tax*10000)/100; ?>%</td>
			</tr>
			<tr class="light">
				<td style="font-weight:bold;" colspan="4">30 Year Yield on Investment (pre-tax IRR)<span class="super">2,&dagger;</span></td>
				<td style="font-weight:bold;" colspan="2" align="right"><?php echo round($analysis->irr_pre_tax*10000)/100; ?>%</td>
			</tr>
		</tbody>
	</table>
	<table>
		<tfoot>
			<tr>
				<td class="cell-foot">
					<?php if($use_credit) { ?>
						* This figure represents the total system cost after applying the 30% Fed Tax Credit.<br /><br />
					<?php } ?>
					1) &quot;Levelized Value of Solar Energy&quot; is the approximation of the average $/kWh value of energy produced from the quoted system. The system net cost (in the installation year), plus any O&M costs, is divided by the amount of energy produced by the system over its life-cycle. This calculation is not adjusted for the time-value of money.
					<br /><br />
					<!-- 2) &quot;Average Annual Utility Savings&quot; is the average annual utility bill savings expected across the system life. This takes into account utility rate inflation and any expected degradation in system performance. This estimate has not assumed any changes in the amount or timing in your building`s energy use.
					<br /><br /> -->
					2) &quot;Internal Rate of Return (IRR)&quot; is the rate of return (annual compounded) that the cash flows bring based upon the amount of capital invested upon installation. If you financed your system 100%, IRR does not apply since you did not invest your capital.
					<br /><br />
					&spades; &quot;post-tax IRR&quot;: Since the yield is based off of energy cost savings from solar PV the investment is inherently tax free. Therefore we classify the return as a post-tax return.
					<br /><br />
					&dagger; &quot;pre-tax IRR&quot;: Returns on other types of investments are often quoted as a "pre-tax" percentage. Therefore in order to compare your solar system to other investment options we have determined the "pre-tax" return on investment.
					<!-- 3) &quot;Total Life-Cycle Payback&quot; is the rate of return % the invested Net Cost (in the installation year) yields over the systems expected life. This calculation is not adjusted for the time-value of money. -->
				</td>
			</tr>
		</tfoot>
	</table>
</div>
<!-- proposal financials END -->