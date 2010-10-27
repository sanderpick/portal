<?php
// build the financials
require_once("analysis.classes.php");
//$analysis = new SolarAnalysis(5.50,6992,0.0098,20369,0.066,0.12,30,10000,0,0,0.30,3876);
$analysis = new SolarAnalysis(
	$f->size, // system size
	$f->production, // system production
	0.0098, // system derate
	$f->cus_after_credit, // system cost
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
$analysis->total_profit -= $analysis->sys_inverter_half_life;
// compile data
$analysis->finish();
?>
<!-- proposal financials START -->
<div id="prop-financials" class="prop-section">
	<table>
		<caption>System Specs:</caption>
		<tbody class="tabled">
			<tr class="dark">
				<td>System Size</td>
				<td align="right" style="font-weight:bold;"><?php echo $f->size; ?> kW</td>
			</tr>
			<tr class="light">
				<td>1st Year Solar Energy Production</td>
				<td align="right"><?php echo number_format($f->production); ?> kWh</td>
			</tr>
			<tr class="dark">
				<td>Overall Annual Energy Consumed</td>
				<td align="right"><?php echo number_format($job->job_kwh_load); ?> kWh</td>
			</tr>
			<tr class="light">
				<td>Current Utility Rate</td>
				<td align="right" class="lighter">$<?php echo $zones[0]->zon_erate/100; ?> / kWh</td>
			</tr>
			<tr class="dark">
				<td>Levelized Value of Solar Energy<span class="super">1</span></td>
				<td align="right" class="lighter">$<?php echo round(1000*$analysis->lcoe_solar_energy)/1000; ?> / kWh</td>
			</tr>
			<tr>
				<td class="big darker round-l">System Cost or Investment*</td>
				<td align="right" class="big darker round-r">$<?php echo number_format($f->cus_after_credit); ?></td>
			</tr>
		</tbody>
	</table>
	<br /><br />
	<table>
		<caption>Financial Summary:</caption>
		<tbody class="tabled">
			<tr class="dark">
				<td>System Payback Period</td>
				<td align="right" style="font-weight:bold;"><?php echo $analysis->payback_yrs; ?> Years</td>
			</tr>
			<tr class="light">
				<td>30 Year Yield on Investment (tax-free IRR)<span class="super">2,3</span></td>
				<td align="right"><?php echo round($analysis->irr_post_tax*10000)/100; ?>%</td>
			</tr>
			<!-- <tr class="dark">
				<td>30 Year Yield on Investment (pre-tax IRR)<span class="super">2,&dagger;</span></td>
				<td align="right" class="lighter"><php echo round($analysis->irr_pre_tax*10000)/100; ?>%</td>
			</tr> -->
			<tr class="dark">
				<td>30 Year Cumulative Electric Cost WITHOUT Solar</td>
				<td align="right">$<?php echo number_format($analysis->total_elec_bill_no_solar); ?></td>
			</tr>
			<tr class="light">
				<td>30 Year Cumulative Electric Cost WITH Solar<span class="super">&dagger;</span></td>
				<td align="right">-&nbsp;&nbsp;$<?php echo number_format($analysis->total_elec_bill_solar); ?></td>
			</tr>
			<tr class="dark">
				<td>30 Year Solar Savings</td>
				<td align="right">=&nbsp;&nbsp;$<?php echo number_format($analysis->elec_savings); ?></td>
			</tr>
			<?php $bc = "light"; if($analysis->total_sorec_rev!=0) { ?>
			<tr class="<?php echo $bc; ?>">
				<td>SREC Revenue</td>
				<td align="right" class="green-txt">+&nbsp;&nbsp;$<?php echo number_format($analysis->total_sorec_rev); ?></td>
			</tr>
			<?php $bc = $bc=="dark" ? "light" : "dark"; } ?>
			<tr class="<?php echo $bc; $bc = $bc=="dark" ? "light" : "dark"; ?>">
				<td>System Cost or Investment*</td>
				<td align="right" class="red-txt">-&nbsp;&nbsp;$<?php echo number_format($analysis->sys_cost); ?></td>
			</tr>
			<tr>
				<td class="big darker round-l">Total Profit from Solar Investment</td>
				<td align="right" class="big darker round-r"><strong>$<?php echo number_format($analysis->elec_savings + $analysis->total_sorec_rev - $analysis->sys_cost); ?></strong></td>
			</tr>
		</tbody>
	</table>
	<div id="financials-table">
		<br /><br />
		<table>
			<caption>30 Year Financial Summary:</caption>
			<thead>
				<tr>
					<th align="left">Year</th>
					<th align="right">Utility Rate</th>
					<th align="right">Electric Bill <br />WITHOUT Solar</th>
					<th align="right">Electric Bill <br />WITH Solar</th>
					<?php if($analysis->total_sorec_rev!=0) { ?><th align="right">SREC</th><?php } ?>
					<th align="right">Annual Savings</th>
					<th align="right">Payback on Solar Investment <br />(includes inverter replacement)</th>
				</tr>
			</thead>
			<tbody>
				<tr class="dark">
					<td>0</td>
					<td align="right">&ndash;</td>
					<td align="right">&ndash;</td>
					<td align="right">&ndash;</td>
					<?php if($analysis->total_sorec_rev!=0) { ?><td align="right">&ndash;</td><?php } ?>
					<td align="right">&ndash;</td>
					<td align="right" style="color:red;">-&nbsp;&nbsp;$<?php echo number_format($analysis->sys_cost); ?></td>
				</tr>
				<?php
					$rows = ""; $bc = "light";
					for($n=0;$n<$analysis->sys_life;$n++) {
						if($analysis->annual_data[$n]->cashflow < 0) {
							$cashflow = "-&nbsp;&nbsp;$".number_format(-$analysis->annual_data[$n]->cashflow);
							$c = "green";
						} else {
							$cashflow = "$".number_format($analysis->annual_data[$n]->cashflow);
							$c = "red";
						}
						$sorec_rev = $analysis->annual_data[$n]->sorec_rev < 0 ? number_format(-$analysis->annual_data[$n]->sorec_rev) : number_format($analysis->annual_data[$n]->sorec_rev);
						$c = $analysis->annual_data[$n]->cashflow > 0 ? "green" : "red";
						$rows .= '<tr class="'.$bc.'">';
						$rows .= 	'<td>'.($n+1).'</td>';
						$rows .= 	'<td align="right">$'.(round(1000*$analysis->annual_data[$n]->utility)/1000).'</td>';
						$rows .= 	'<td align="right">$'.number_format($analysis->annual_data[$n]->elec_bill_no_solar).'</td>';
						$rows .= 	'<td align="right">$'.number_format($analysis->annual_data[$n]->elec_bill_solar).'</td>';
						$rows .= 	$analysis->total_sorec_rev!=0 ? '<td align="right">$'.$sorec_rev.'</td>' : '';
						$rows .= 	'<td align="right">$'.number_format($analysis->annual_data[$n]->total_solar_value).'</td>';
						$rows .= 	'<td align="right" style="color:'.$c.'">'.$cashflow.'</td>';
						$rows .= '</tr>';
						$bc = $bc=="dark" ? "light" : "dark";
					}
					echo $rows;
				?>
				<tr>
					<td class="big darker round-l">Total</td>
					<td align="right" class="big darker">&nbsp;</td>
					<td align="right" class="big darker">$<?php echo number_format($analysis->total_elec_bill_no_solar); ?></td>
					<td align="right" class="big darker">$<?php echo number_format($analysis->total_elec_bill_solar); ?></td>
					<?php if($analysis->total_sorec_rev!=0) { ?>
					<td align="right" class="big darker">$<?php echo number_format($analysis->total_sorec_rev); ?></td>
					<?php } ?>
					<td align="right" class="big darker">$<?php echo number_format($analysis->total_savings); ?></td>
					<td align="right" class="big darker round-r">&nbsp;</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="vis vis-bar-financial financials-graphs">
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
		<br />
		<table>
			<caption>– Cumulative Cashflow –</caption>
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
					<th>Cumulative Cashflow (pre-payback)</th>
					<td><?php echo round($analysis->annual_data[0]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[1]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[2]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[3]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[4]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[5]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[6]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[7]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[8]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[9]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[10]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[11]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[12]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[13]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[14]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[15]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[16]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[17]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[18]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[19]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[20]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[21]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[22]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[23]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[24]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[25]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[26]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[27]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[28]->cashflow); ?></td>
					<td><?php echo round($analysis->annual_data[29]->cashflow); ?></td>
				</tr>
				<tr>
					<th>Cumulative Cashflow (post-payback)</th>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
					<td>0</td>
				</tr>
			</tbody>
		</table>
	</div>
	<br />
	<table>
		<tfoot>
			<tr>
				<td class="cell-foot">
					1) &quot;Levelized Value of Solar Energy&quot; is the approximation of the average $/kWh value of energy produced from the quoted system. The system net cost (in the installation year), plus any O&M costs, is divided by the amount of energy produced by the system over its life-cycle. This calculation is not adjusted for the time-value of money.
					<br /><br />
					<?php if($use_credit) { ?>
						* This figure represents the total system cost after applying the 30% Fed Tax Credit.<br /><br />
					<?php } ?>
					2) &quot;Internal Rate of Return (IRR)&quot; is the rate of return (annual compounded) that the cash flows bring based upon the amount of capital invested upon installation. If you financed your system 100%, IRR does not apply since you did not invest your capital.
					<br /><br />
					3) &quot;tax-free IRR&quot;: Since the yield is based off of energy cost savings from Solar PV, the investment is inherently tax-free. For comparison purposes, this could be considered the post-tax return. Returns on other types of investments are often quoted as a pre-tax return. Therefore, in order to do an "apples to apples" comparison of your solar investment to other investment options, you will have to take into account your tax bracket percentage.
					<br /><br />
					&dagger; This figure includes an inverter replacement at year 15.
				</td>
			</tr>
		</tfoot>
	</table>
</div>
<!-- proposal financials END -->