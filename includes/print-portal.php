<?php
// GET THE PROPOSAL DATA AND BUILD IT
//////////////////////////////////////////////////////////////////////////////////
// make the calcs and convert to object
require_once($EINSTEIN_LOC."includes/es-object.class.php");
require_once($EINSTEIN_LOC."includes/es-calcs.php");
$f = new Object();
foreach(estimate($pro) as $k=>$v) if(!is_numeric($k)) $f->push($k,$v);
// get the office info
$m->getRow('es_offices',$pro->pro_officeID);
$off = $m->lastData();
// get the rep info
$m->getRow('es_reps',$pro->pro_repID);
$rep = $m->lastData();
// get the customer info
$m->getRow('es_customers',$pro->pro_customerID);
$cus = $m->lastData();
// get the jobs info
$m->getRow('es_jobs',$pro->pro_jobID);
$job = $m->lastData();
// for avatar
$customer_name = $cus->cus_name_first." ".$cus->cus_name_last;
if(strlen($customer_name)>17) $customer_name = substr($customer_name,0,14)."...";
$customer_contact = "";
if($cus->cus_email1!="") $customer_contact = $cus->cus_email1;
else if($cus->cus_email2!="") $customer_contact = $cus->cus_email2;
else if($cus->cus_phone1!="") $customer_contact = $cus->cus_phone1;
else if($cus->cus_phone2!="") $customer_contact = $cus->cus_phone2;
else if($cus->cus_phone_mobile!="") $customer_contact = $cus->cus_phone_mobile;
// general customer info
$customer_phone = $cus->cus_phone1!="" ? $cus->cus_phone1 : $cus->cus_phone2;
$customer_phone = $customer_phone!="" ? $customer_phone : $cus->cus_phone_mobile;
$customer_email = $cus->cus_email1!="" ? $cus->cus_email1 : $cus->cus_email2;
// overall contacts
$customer_title = $cus->cus_company!="" ? $cus->cus_company : $cus->cus_name_first." ".$cus->cus_name_last;
$job_title = $job->job_company!="" ? $job->job_company : ($job->job_contact!="" ? $job->job_contact : $customer_title);
// job
$job_html = $job_title."<br />";
$job_html .= $job->job_phone!="" ? $job->job_phone."<br />" : $customer_phone."<br />"; 		
$job_html .= $job->job_address1."<br />";
$job_html .= ($job->job_address2!="") ? $job->job_address2."<br />" : "";
$job_html .= $job->job_city.", ";
$job_html .= $job->job_state." ";
$job_html .= $job->job_zip."<br />";
$job_name = (strlen($job->job_name)>20) ? substr($job->job_name,0,17)."..." : $job->job_name;
$job_summary_short =  "<strong>".$job_name." – ".$f->size."kW</strong>";
$job_summary =  "<strong>".$job->job_name." – ".$f->size."kW</strong>";
// incentive
$incentive_revenue = number_format($pro->pro_incentive_rate*$f->production);
// cover letter
$cover_letter = str_replace("\n","<br />",$pro->pro_cover_letter);
$cover_letter = str_replace('"','&quot;',$cover_letter);
$cover_letter = str_replace("'","&#39;",$cover_letter);
$cover_letter = str_replace("#amp;","&",$cover_letter);
$cover_letter = "Dear ".$job_title.",<br /><br />".$cover_letter;
$cover_letter .= ($pro->pro_incentive==1) ? "<br /><br />This system will generate a recurring revenue of approximately $".$incentive_revenue." annually for ".$pro->pro_incentive_yrs." years.": "";
$cover_letter .= "<br /><br />Yours truly,<br />".$rep->rep_name_first." ".$rep->rep_name_last.", <em>".$rep->rep_title."</em><br /><br />";
$cover_letter .= "<a href='".$LHS_LOC."' target='_blank'><strong>Lighthouse</strong>solar</a><br />";
$cover_letter .= $off->off_city.", ".$off->off_state." ".$off->off_zip."<br />";
$cover_letter .= $rep->rep_email." (e)<br />";
$cover_letter .= $rep->rep_phone!="" ? $rep->rep_phone." (p)" : $off->off_phone." (p)";
// overwrite incentive for financials
$pro->pro_incentive_rate = $pro->pro_incentive==1 ? $pro->pro_incentive_rate : 0;
// get the zones
$zoneIDs = explode(",",substr($pro->pro_zones,0,-1));
$pro_num_zones = count($zoneIDs);
$pro_num_modules = 0;
$zones = array();
foreach($zoneIDs as $zoneID) {
	$m->getRow('es_zones',$zoneID);
	$zones[] = $m->lastData();
	$pro_num_modules += $m->lastData()->zon_num_modules;
}
// pv watts
$pro_pvwatts = array(array_fill(0,13,0),array_fill(0,13,0),array_fill(0,13,0));
// modules
$module_qntys = array();
$module_descs = array();
$module_prices = array();
// mounting
$mounting_qntys = array();
$mounting_descs = array();
$mounting_prices = array();
// rebate
$rebate_qntys = array();
$rebate_descs = array();
$rebate_prices = array();
// connections
$connections_desc = "";
$connections_price = 0;
// layout images on names
$layout_html = "";
// install params
$install_params = "";
// loop dat shit!
$row_color = array('light','dark'); $c = 0;
foreach($zones as $zone) {
	// find this zones contribution
	$wc = $zone->zon_num_modules / $pro_num_modules;
	// built performance data
	list($r1,$e1,$v1) = explode(",",$zone->zon_pvwatts_m1);
	list($r2,$e2,$v2) = explode(",",$zone->zon_pvwatts_m2);
	list($r3,$e3,$v3) = explode(",",$zone->zon_pvwatts_m3);
	list($r4,$e4,$v4) = explode(",",$zone->zon_pvwatts_m4);
	list($r5,$e5,$v5) = explode(",",$zone->zon_pvwatts_m5);
	list($r6,$e6,$v6) = explode(",",$zone->zon_pvwatts_m6);
	list($r7,$e7,$v7) = explode(",",$zone->zon_pvwatts_m7);
	list($r8,$e8,$v8) = explode(",",$zone->zon_pvwatts_m8);
	list($r9,$e9,$v9) = explode(",",$zone->zon_pvwatts_m9);
	list($r10,$e10,$v10) = explode(",",$zone->zon_pvwatts_m10);
	list($r11,$e11,$v11) = explode(",",$zone->zon_pvwatts_m11);
	list($r12,$e12,$v12) = explode(",",$zone->zon_pvwatts_m12);
	list($rt,$et,$vt) = explode(",",$zone->zon_pvwatts_mt);
	$pro_pvwatts[0][0] += round($r1*$wc*100)/100;
	$pro_pvwatts[0][1] += round($r2*$wc*100)/100;
	$pro_pvwatts[0][2] += round($r3*$wc*100)/100;
	$pro_pvwatts[0][3] += round($r4*$wc*100)/100;
	$pro_pvwatts[0][4] += round($r5*$wc*100)/100;
	$pro_pvwatts[0][5] += round($r6*$wc*100)/100;
	$pro_pvwatts[0][6] += round($r7*$wc*100)/100;
	$pro_pvwatts[0][7] += round($r8*$wc*100)/100;
	$pro_pvwatts[0][8] += round($r9*$wc*100)/100;
	$pro_pvwatts[0][9] += round($r10*$wc*100)/100;
	$pro_pvwatts[0][10] += round($r11*$wc*100)/100;
	$pro_pvwatts[0][11] += round($r12*$wc*100)/100;
	$pro_pvwatts[0][12] += round($rt*$wc*100)/100;
	$pro_pvwatts[1][0] += $e1;
	$pro_pvwatts[1][1] += $e2;
	$pro_pvwatts[1][2] += $e3;
	$pro_pvwatts[1][3] += $e4;
	$pro_pvwatts[1][4] += $e5;
	$pro_pvwatts[1][5] += $e6;
	$pro_pvwatts[1][6] += $e7;
	$pro_pvwatts[1][7] += $e8;
	$pro_pvwatts[1][8] += $e9;
	$pro_pvwatts[1][9] += $e10;
	$pro_pvwatts[1][10] += $e11;
	$pro_pvwatts[1][11] += $e12;
	$pro_pvwatts[1][12] += $et;
	$pro_pvwatts[2][0] += $v1;
	$pro_pvwatts[2][1] += $v2;
	$pro_pvwatts[2][2] += $v3;
	$pro_pvwatts[2][3] += $v4;
	$pro_pvwatts[2][4] += $v5;
	$pro_pvwatts[2][5] += $v6;
	$pro_pvwatts[2][6] += $v7;
	$pro_pvwatts[2][7] += $v8;
	$pro_pvwatts[2][8] += $v9;
	$pro_pvwatts[2][9] += $v10;
	$pro_pvwatts[2][10] += $v11;
	$pro_pvwatts[2][11] += $v12;
	$pro_pvwatts[2][12] += $vt;
	// modules
	$module_qntys[] = $zone->zon_num_modules;
	$m->getRow('es_modules',$zone->zon_module,'mod_model_num');
	$module_descs[] = $m->lastData()->mod_desc;
	$module_prices[] = $zone->zon_module_price;
	// mounting
	$mounting_qntys[] = $zone->zon_racking_length;
	$m->getRow('es_racking',$zone->zon_racking,'rac_model_num');
	$mounting_descs[] = $m->lastData()->rac_desc;
	$mounting_prices[] = $zone->zon_racking_price;
	// rebate
	$rebate_types[] = "@ $".(round($zone->zon_rebate / $zone->zon_size / 10)/100)." / Watt";
	$rebate_descs[] = $zone->zon_rebate_desc;
	$rebate_prices[] = $zone->zon_rebate;
	// add to misc materials
	$connections_price += $zone->zon_connection_price;
	if($zone->zon_connection_price>0) $connections_desc = "Mounting Materials";
	// build the layout text
	if($m->getRow('es_uploads',$zone->zon_layout)) {
		$i_url = $EINSTEIN_URI.$m->lastData()->up_root.$m->lastData()->up_handle."/".$m->lastData()->up_handle."_sized_800.jpg";
		$layout_html .= "<div style='page-break-before:always;' class='fake-break'></div><div class='proposal-head'>
									<table cellspacing='0' cellpadding='0' style='width:662px;'>
										<tr>
											<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'>".$job->job_name." &ndash; ".$f->size."kW</span> System Layout</h1></td>
											<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td>
										</tr>
									</table>
								</div>
						<div class='page proposal-page'>
						<table cellspacing='0' cellpadding='0'>
							<tbody>
								<tr>
									<td style='padding:0;'>
										<p style='font-size:9pt;'>
											<span style='font-size:11pt;'>PV Proposal #".$pro->ID." prepared for:</span>
											<br />
											".$job_html."
										</p>
									</td>
									<td style='padding:0; float:right;'>
										<p style='font-size:9pt; text-align:right;'>
											<span style='font-size:11pt;'>prepared by:</span><br />
											".$rep->rep_name_first." ".$rep->rep_name_last."<br />
											".$rep->rep_email." (e)<br />
											".($rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone)." (p)<br />
											".$off->off_city.", 
											".$off->off_state."
										</p>
									</td>
								</tr>
							</tbody>
						</table>
						<br /><br />
						<table cellspacing='0' cellpadding='0'>
							<tbody>
								<tr>
									<td style='padding:0;' colspan='2'>
										<span class='caption'>".$zone->zon_name." – ".$zone->zon_size."kW System Layout</span><br /><img src='".$i_url."' width='650' style='padding-top:10pt;' alt='Zone Layout' /><br /><br />
									</td>
								</tr>
								<tr><td style='padding:0;' colspan='1'>&nbsp;</td></tr>
								<tr>
									<td style='padding:0;' colspan='2'>
										I have reviewed and I approve the location and layout of the solar system installation as proposed.
									</td>
								</tr>
								<tr><td style='padding:0;' colspan='1'>&nbsp;</td></tr>
								<tr><td style='padding:0;' colspan='1'>&nbsp;</td></tr>
								<tr>
									<td style='padding:0; border-top:1px dotted #222222;' colspan='1'>
										Signature / Date
									</td>
								</tr>
							</tbody>
						</table>
					</div>";
	}
	// get the tilt
	if($zone->zon_tilt!="custom") {
		if(preg_match('|\({1}(.*?)\){1}|',$zone->zon_tilt, $tm)) $zone_tilt = $tm[1];
	} else $zone_tilt = $zone->zon_custom_tilt;
	// install params
	$install_params .= "<br /><br />";
	$install_params .= "<span class='caption'>".$zone->zon_name." Installation Parameters:</span>";
	$install_params .= "<br />";
	$install_params .= "<table cellspacing='0' cellpadding='0' style='margin-top:10pt;'>";
	$install_params .= "<thead>";
	$install_params .= "<tr>";
	$install_params .= "<th scope='col' align='left'>DC Array Size</th>";
	$install_params .= "<th scope='col' align='right'>Annual Production</th>";
	$install_params .= "<th scope='col' align='right'>Tilt</th>";
	$install_params .= "<th scope='col' align='right'>Azimuth</th>";
	$install_params .= "</tr>";
	$install_params .= "</thead>";
	$install_params .= "<tbody>";
	$install_params .= "<tr class='".$row_color[($c+1)%2]."'>";
	$install_params .= "<td>".$zone->zon_size." kW</td>";
	$install_params .= "<td align='right'>".number_format($zone->zon_production)." kWh</td>";
	$install_params .= "<td align='right'>".$zone_tilt."º</td>";
	$install_params .= "<td align='right'>".$zone->zon_azimuth."º</td>";
	$install_params .= "</tr>";
	$install_params .= "</tbody>";
	$install_params .= "</table>";
}
// inverters
$inverters = explode(",",substr($pro->pro_inverter,0,-1));
$inverter_qntys = array();
$inverter_descs = array();
$total_inverter_price = 0;
foreach($inverters as $in) {
	if(strpos($in,"_g_")!==FALSE) $in = substr($in,0,strpos($in,"_g_"));
	$m->getRow('es_inverters',$in,'inv_model_num');
	$inverter_qntys[] = 1;
	$inverter_descs[] = $m->lastData()->inv_desc;
	$inverter_prices[] = $m->lastData()->inv_price + $m->lastData()->inv_price*$off->off_inventory_up*0.01;
	$total_inverter_price += $m->lastData()->inv_price + $m->lastData()->inv_price*$off->off_inventory_up*0.01;
}
// add modules if duplicate
for($i=0;$i<count($module_descs);$i++) {
	for($j=0;$j<count($module_descs);$j++) {
		if($module_descs[$j]==$module_descs[$i] && $i!=$j && $module_descs[$i]!=NULL && $module_descs[$j]!=NULL) {
			$module_qntys[$i] += $module_qntys[$j];
			$module_prices[$i] += $module_prices[$j];
			$module_descs[$j] = NULL;
			$module_qntys[$j] = NULL;
			$module_prices[$j] = NULL;
		}
	}
}
$module_descs = array_values(array_filter($module_descs,"strlen"));
$module_qntys = array_values(array_filter($module_qntys,"strlen"));
$module_prices = array_values(array_filter($module_prices,"strlen"));
// add mounting if duplicate
for($i=0;$i<count($mounting_descs);$i++) {
	for($j=0;$j<count($mounting_descs);$j++) {
		if($mounting_descs[$j]==$mounting_descs[$i] && $i!=$j && $mounting_descs[$i]!=NULL && $mounting_descs[$j]!=NULL) {
			$mounting_qntys[$i] += $mounting_qntys[$j];
			$mounting_prices[$i] += $mounting_prices[$j];
			$mounting_descs[$j] = NULL;
			$mounting_qntys[$j] = NULL;
			$mounting_prices[$j] = NULL;
		}
	}
}
$mounting_descs = array_values(array_filter($mounting_descs,"strlen"));
$mounting_qntys = array_values(array_filter($mounting_qntys,"strlen"));
$mounting_prices = array_values(array_filter($mounting_prices,"strlen"));
// add inverter if duplicate
for($i=0;$i<count($inverter_descs);$i++) {
	for($j=0;$j<count($inverter_descs);$j++) {
		if($inverter_descs[$j]==$inverter_descs[$i] && $i!=$j && $inverter_descs[$i]!=NULL && $inverter_descs[$j]!=NULL) {
			$inverter_qntys[$i] += $inverter_qntys[$j];
			$inverter_prices[$i] += $inverter_prices[$j];
			$inverter_descs[$j] = NULL;
			$inverter_qntys[$j] = NULL;
			$inverter_prices[$j] = NULL;
		}
	}
}
$inverter_descs = array_values(array_filter($inverter_descs,"strlen"));
$inverter_qntys = array_values(array_filter($inverter_qntys,"strlen"));
$inverter_prices = array_values(array_filter($inverter_prices,"strlen"));
// parse additional rebates
$add_rebate_types = explode(",",substr($pro->pro_rebate_type,0,-1));
$add_rebate_descs = explode(",",substr($pro->pro_rebate_desc,0,-1));
$add_rebate_amnts = explode(",",substr($pro->pro_rebate_amnt,0,-1));
for($i=0;$i<count($add_rebate_types);$i++) {
	if($add_rebate_amnts[$i]!="") {
		switch($add_rebate_types[$i]) {
			case 0 :
				$rebate_types[] = "@ $".$add_rebate_amnts[$i]." / Watt";
				$rebate_prices[] = $add_rebate_amnts[$i]*$f->size*1000;
				break;
			case 1 :
				$rebate_types[] = "@ ".$add_rebate_amnts[$i]."% System Price";
				$rebate_prices[] = $add_rebate_amnts[$i]*$f->price_nf*0.01;
				break;
			case 2 :
				$rebate_types[] = "@ Fixed Amount";
				$rebate_prices[] = $add_rebate_amnts[$i];
				break;
		}
		$rebate_descs[] = $add_rebate_descs[$i];
	}
}
// components lines
$components_html = "";
$c = 0;
for($i=0;$i<count($module_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$module_qntys[$i]."</td><td class='cell-indent'>".$module_descs[$i]."</td><td align='right'>".number_format($module_prices[$i])."</td></tr>";
	$c++;
}
for($i=0;$i<count($mounting_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$mounting_qntys[$i]." (ft.)</td><td class='cell-indent'>".$mounting_descs[$i]."</td><td align='right'>".number_format($mounting_prices[$i])."</td></tr>";
	$c++;
}
for($i=0;$i<count($inverter_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$inverter_qntys[$i]."</td><td class='cell-indent'>".$inverter_descs[$i]."</td><td align='right'>".number_format($inverter_prices[$i])."</td></tr>";
	$c++;
}
if($connections_price+$f->misc_materials>0) {
	$misc_desc = "";
	if($pro->pro_conduit_out!=0 || $pro->pro_conduit_in!=0 || $pro->pro_conduit_under!=0) $misc_desc .= "Conduit, ";
	$misc_desc .= $connections_desc.", ";
	if($pro->pro_misc_materials>0 && $pro->pro_misc_materials_desc!="") $misc_desc .= $pro->pro_misc_materials_desc.", ";
	else if($pro->pro_misc_materials>0) $misc_desc .= "Other Materials, ";
	$misc_desc = substr($misc_desc,0,-2);
	if($misc_desc=="") $misc_desc = "Misc. Materials";
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>&nbsp;</td><td class='cell-indent'>".$misc_desc."</td><td align='right'>".number_format($connections_price+$f->misc_materials)."</td></tr>";
	$c++;
}
$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>1</td><td class='cell-indent'>FREE LightGauge Data Monitoring System</td><td align='right'>0</td></tr>";
$c++;
$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td colspan='2' class='cell-total'>Materials Total</td><td align='right' class='cell-total'>$".number_format($f->comp_total)."</td></tr>";
// labor lines
$labor_html = "";
$c = 0;
$labor_html .= "<tr class='".$row_color[($c+1)%2]."'><td>PV System – Installation Labor</td><td align='right'>".$f->install_labor."</td></tr>";
$c++;
$labor_html .= "<tr class='".$row_color[($c+1)%2]."'><td class='cell-total'>Labor Total</td><td align='right' class='cell-total'>$".$f->install_labor."</td></tr>";
// fees lines
$fees_html = "";
$c = 0;
if($f->permit!=0) {
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Permit Fees</td><td align='right'>".$f->permit."</td></tr>";
	$c++;
}
if($pro->pro_engin_fee!=0) {
	$engin_up = $pro->pro_engin_fee*$off->off_sub_up*0.01;
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Engineering Fees</td><td align='right'>".number_format($pro->pro_engin_fee+$engin_up)."</td></tr>";
	$c++;
}
if($pro->pro_extra_fee!=0) {
	$extra_up = $pro->pro_extra_fee*$off->off_sub_up*0.01;
	if($pro->pro_extra_desc!="") $pro->pro_extra_desc = "(".$pro->pro_extra_desc.")";
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Extra Fees ".$pro->pro_extra_desc."</td><td align='right'>".number_format($pro->pro_extra_fee+$extra_up)."</td></tr>";
	$c++;
}
if($f->equip!=0) {
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Equipment Rental Fees</td><td align='right'>".$f->equip."</td></tr>";
	$c++;
}
if($pro->pro_inspection!=0) {
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Design / Inspection / Commissioning Fees</td><td align='right'>".number_format($pro->pro_inspection)."</td></tr>";
	$c++;
}
for($i=0;$i<count($rebate_descs);$i++) {
	if($rebate_prices[$i]!=0) {
		if($rebate_descs[$i]=="") $rebate_descs[$i] = "Rebate";
		$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td class='cell-credit'>".$rebate_descs[$i]." (".$rebate_types[$i].")</td><td class='cell-credit' align='right'>-".number_format($rebate_prices[$i])."</td></tr>";
		$c++;
	}
}
if($pro->pro_discount!=0) {
	if($pro->pro_discount_desc!="") $pro->pro_discount_desc = "(".$pro->pro_discount_desc.")";
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td class='cell-credit'>Discount ".$pro->pro_discount_desc."</td><td class='cell-credit' align='right'>-".number_format($pro->pro_discount)."</td></tr>";
	$c++;
}
$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Subtotal</td><td align='right'>".$f->subtotal."</td></tr>";
$c++;
$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Sales Tax</td><td align='right'>".$f->tax."</td></tr>";
$c++;
$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td class='cell-rebate cell-emphasis'>Total System Out of Pocket Cost (@ $".$f->ppw_cus_net." / Watt)</td><td class='cell-rebate cell-emphasis' align='right'>$".$f->cus_price."</td></tr>";
// show tax credit info?
$use_credit = ($f->credit!=0) ? 1 : 0;
if($use_credit) {
	$c = 0;
	$fees_html .= "<tr><td colspan='2'>&nbsp;</td></tr>";
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td class='cell-credit'>30% Federal Tax Credit</td><td class='cell-credit' align='right'>-".$f->credit."</td></tr>";
	$c++;
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td class='cell-total'>Final Cost To You*</td><td class='cell-total' align='right'>$".$f->cus_after_credit."</td></tr>";
}
// materials
$materials_html = "";
foreach($module_descs as $module_desc) {
	if($m->getRow('es_modules',$module_desc,'mod_desc')) {
		$module = $m->lastData();
		if($module->mod_print_cutsheet_uri!="") {
			$print_uris = explode(",",$module->mod_print_cutsheet_uri);
			foreach($print_uris as $uri) {
				$materials_html .= "<div style='page-break-before:always;' class='fake-break'></div>
										<div class='proposal-head'>
											<table cellspacing='0' cellpadding='0' style='width:662px;'>
												<tr>
													<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'>".$job->job_name." &ndash; ".$f->size."kW</span> Materials</h1></td>
													<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td>
												</tr>
											</table>
										</div>
									<div class='page proposal-page'>
										<img src='".$uri."' alt='".$module_desc."' />
									</div>";
			}
		}
	}
}
foreach($inverter_descs as $inverter_desc) {
	if($m->getRow('es_inverters',$inverter_desc,'inv_desc')) {
		$inverter = $m->lastData();
		if($inverter->inv_print_cutsheet_uri!="") {
			$print_uris = explode(",",$inverter->inv_print_cutsheet_uri);
			foreach($print_uris as $uri) {
				$materials_html .= "<div style='page-break-before:always;' class='fake-break'></div><div class='proposal-head'>
											<table cellspacing='0' cellpadding='0' style='width:662px;'>
												<tr>
													<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'>".$job->job_name." &ndash; ".$f->size."kW</span> Materials</h1></td>
													<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td>
												</tr>
											</table>
										</div>
									<div class='page proposal-page'>
										<img src='".$uri."' alt='".$inverter_desc."' />
									</div>";
			}
		}
	}
}
// check sum for performance area graph
$job_bill_total = $job->job_bill_1+$job->job_bill_2+$job->job_bill_3+$job->job_bill_4+$job->job_bill_5+$job->job_bill_6+$job->job_bill_7+$job->job_bill_8+$job->job_bill_9+$job->job_bill_10+$job->job_bill_11+$job->job_bill_12;
$draw_bill = $job_bill_total>0 ? 1 : 0;
//////////////////////////////////////////////////////////////////////////////////
?>