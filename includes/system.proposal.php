<!-- proposal system START -->
<div id="prop-system" class="prop-section">
	<table>
		<caption>Materials Detail:</caption>
		<thead>
			<tr>
				<th align="left">qty.</th>
				<th class="cell-indent" align="left">component</th>
				<th align="right">price ($)</th>
			</tr>
		</thead>
		<tbody>
			<?php echo $components_html; ?>
		</tbody>
	</table>
	<br /><br />
	<table>
		<caption>Labor Detail:</caption>
		<thead>
			<tr>
				<th align="left">description</th>
				<th align="right">price ($)</th>
			</tr>
		</thead>
		<tbody>
			<?php echo $labor_html; ?>
		</tbody>
	</table>
	<br /><br />
	<table>
		<caption>Credits and Fees:</caption>
		<thead>
			<tr>
				<th align="left">item</th>
				<th align="right">price ($)</th>
			</tr>
		</thead>
		<tbody>
			<?php echo $fees_html; ?>
		</tbody>
		<tfoot>
			<tr>
				<td class="cell-foot" colspan="3">* This figure represents your <em>estimated</em> final cost after the 30% Federal Tax Credit. Please consult with a tax professional when claiming.</td>
			</tr>
		</tfoot>
	</table>
</div>
<!-- proposal system END -->