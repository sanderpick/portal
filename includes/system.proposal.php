<!-- proposal system START -->
<div id="prop-system" class="prop-section">
	<div class="system-graphs">
		<span class="caption">Cost Breakdown:</span>
		<div class="pie-back">
			<div style="width:50%; float:left; padding:0 0 0 80px;">
				<div class="vis vis-pie">
					<table>
						<caption>Cost Breakdown:</caption>
						<thead>
							<tr>
								<td></td>
								<th>$</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<th>Labor</th>
								<td><?php echo $f->install_labor; ?></td>
							</tr>
							<tr>
								<th>Taxes &amp; Fees</th>
								<td><?php echo $f->tax + $f->fees_total; ?></td>
							</tr>
							<tr>
								<th>Materials</th>
								<td><?php echo $f->comp_total; ?></td>
							</tr>	
						</tbody>
					</table>
				</div>
			</div>
			<div style="margin:0 0 0 50%; width:50%; padding:0 0 0 80px;">
				<div class="vis vis-pie">
					<table>
						<caption>Cost Breakdown:</caption>
						<thead>
							<tr>
								<td></td>
								<th>$</th>
							</tr>
						</thead>
						<tbody>
							<?php if($use_credit) { ?>
							<tr>
								<th>Your Cost</th>
								<td><?php echo $f->cus_after_credit; ?></td>
							</tr>
							<tr>
								<th>30% Federal Tax Credit</th>
								<td><?php echo $f->credit; ?></td>
							</tr>
							<?php } else { ?>	
							<tr>
								<th>Your Cost</th>
								<td><?php echo $f->cus_price; ?></td>
							</tr>
							<?php } ?>
							<tr>
								<th>Rebates &amp; Discounts</th>
								<td><?php echo $f->credits_total; ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="clear"></div>
		</div>
		<br />
	</div>
	<table>
		<caption>Materials Detail:</caption>
		<tbody class="tabled">
			<?php echo $components_html; ?>
		</tbody>
	</table>
	<br /><br />
	<table>
		<caption>Labor Detail:</caption>
		<tbody class="tabled">
			<?php echo $labor_html; ?>
		</tbody>
	</table>
	<br /><br />
	<table>
		<caption>Credits and Fees Detail:</caption>
		<tbody class="tabled">
			<?php echo $fees_html; ?>
		</tbody>
	</table>
	<?php if($use_credit) { ?>
	<br />
	<table>
		<tfoot>
			<tr>
				<td class="cell-foot" colspan="2">* Please consult with a tax professional when claiming.</td>
			</tr>
		</tfoot>
	</table>
	<?php } ?>
</div>
<!-- proposal system END -->