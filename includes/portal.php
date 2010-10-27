<?php
// GET THE PROPOSAL DATA AND BUILD IT
//////////////////////////////////////////////////////////////////////////////////
// make the calcs and convert to object
require_once($EINSTEIN_LOC."includes/es-object.class.php");
require_once($EINSTEIN_LOC."includes/es-calcs.php");
$f = new Object();
foreach(estimate($pro) as $k=>$v) if(!is_numeric($k)) $f->push($k,$v);
// get the office info
$m->getRow("es_offices",$pro->pro_officeID);
$off = $m->lastData();
// get the rep info
$m->getRow("es_reps",$pro->pro_repID);
$rep = $m->lastData();
// get the customer info
$m->getRow("es_customers",$pro->pro_customerID);
$cus = $m->lastData();
// get the jobs info
$job_table = $pro->pro_published ? "es_jobs_s" : "es_jobs";
$m->getRow($job_table,$pro->pro_jobID);
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
// $cover_letter = "Dear ".$job_title.",<br /><br />".$cover_letter;
// $cover_letter .= ($pro->pro_incentive==1) ? "<br /><br />This system will generate a recurring revenue of approximately $".$incentive_revenue." annually for ".$pro->pro_incentive_yrs." years.": "";
// $cover_letter .= "<br /><br />Yours truly,<br />".$rep->rep_name_first." ".$rep->rep_name_last.", <em>".$rep->rep_title."</em><br /><br />";
// $cover_letter .= "<a href='".$LHS_LOC."' target='_blank'><strong>Lighthouse</strong>solar</a><br />";
// $cover_letter .= $off->off_city.", ".$off->off_state." ".$off->off_zip."<br />";
// $cover_letter .= "<a href='mailto:".$rep->rep_email."'>".$rep->rep_email."</a> (e)<br />";
// $cover_letter .= $rep->rep_phone!="" ? $rep->rep_phone." (p)" : $off->off_phone." (p)";
// overwrite incentive for financials
$pro->pro_incentive_rate = $pro->pro_incentive==1 ? $pro->pro_incentive_rate : 0;
// get the zones
$zoneIDs = explode(",",substr($pro->pro_zones,0,-1));
$pro_num_zones = count($zoneIDs);
$pro_num_modules = 0;
$zones = array();
$zone_table = $pro->pro_published ? "es_zones_s" : "es_zones";
foreach($zoneIDs as $zoneID) {
	$m->getRow($zone_table,$zoneID);
	$zones[] = $m->lastData();
	$pro_num_modules += $m->lastData()->zon_num_modules;
}
// pv watts
$pro_pvwatts = array(array_fill(0,13,0),array_fill(0,13,0),array_fill(0,13,0));
// modules
$module_qntys = array();
$module_descs = array();
$module_prices = array();
// racking
$racking_qntys = array();
$racking_descs = array();
$racking_prices = array();
// mounting
$mounting_qntys = array();
$mounting_descs = array();
$mounting_prices = array();
// rebate - above total
$rebate_types_bbl = array();
$rebate_descs_bbl = array();
$rebate_prices_bbl = array();
// rebates - below total
$rebate_types_abl = array();
$rebate_descs_abl = array();
$rebate_prices_abl = array();
// layout images on names
$layout_html = "";
$print_layout_html = "";
// install params
$install_params = "";
// loop dat shit!
$row_color = array("light","dark"); $c = 0;
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
	$m->getRow("es_modules",$zone->zon_module,"mod_model_num");
	$module_descs[] = $m->lastData()->mod_desc;
	$module_prices[] = $zone->zon_module_price;
	// racking
	$racking_qntys[] = $zone->zon_racking_length;
	$m->getRow("es_racking",$zone->zon_racking,"rac_model_num");
	$racking_descs[] = $m->lastData()->rac_desc;
	$racking_prices[] = $zone->zon_racking_price;
	// mounting
	$mounting_qntys[] = $zone->zon_num_connections;
	$m->getRow("es_mounting_methods",$zone->zon_mounting_method,"met_value");
	$mounting_descs[] = $m->lastData()->met_desc;
	$mounting_prices[] = $zone->zon_connection_price;
	// rebate
	$rebate_types_bbl[] = "@ $".(floor($zone->zon_rebate / $zone->zon_size / 10)/100)." / Watt";
	$rebate_descs_bbl[] = $zone->zon_rebate_desc;
	$rebate_prices_bbl[] = $zone->zon_rebate;
	// build the layout text
	if($m->getRow("es_uploads",$zone->zon_layout)) {
		$i_url = $EINSTEIN_URI.$m->lastData()->up_root.$m->lastData()->up_handle."/".$m->lastData()->up_handle."_sized_800.jpg";
		$ip_url = $EINSTEIN_URI.$m->lastData()->up_root.$m->lastData()->up_handle."/".$m->lastData()->up_handle."_sized_tall.jpg";
		if(file_exists($ip_url)) $have_ip = TRUE;
		else {
			$have_ip = FALSE;
			$ip_url = $i_url;
		}
		$layout_html .= "<span class='caption'>".$zone->zon_name." – ".$zone->zon_size."kW System Layout</span><img src='".$i_url."' width='650' class='layout-img' alt='Zone Layout' /><br /><br />";
		$print_layout_html .= "<div style='page-break-before:always;' class='fake-break'></div>";
		$print_layout_html .= '<div class="proposal-head">
								<table style="width:664px;">
									<tr>
										<td style="padding:0 0 6px 0; vertical-align:bottom;">
											<h1 class="page-head">
												<span style="font-weight:bold;">System Layout</span> '.$job->job_name.' &ndash; '.$f->size.'kW
											</h1>
										</td>
										<td style="padding:0 0 4px 0;" align="right"><img src="gfx/logo-black.png" alt="small logo" /></td>
									</tr>
								</table>
							</div>
							<div class="page proposal-page">
								<table>
									<tbody>
										<tr>
											<td style="padding:0;">
												<p class="prop-header-txt">
													<span class="prepared">PV Proposal #'.$pro->ID.' Prepared for:</span>
													<br />
													'.$job_html.'
												</p>
											</td>
											<td style="padding:0; float:right;">
												<p class="prop-header-txt" style="text-align:right;">
													<span class="prepared">Prepared by:</span><br />
													'.$rep->rep_name_first." ".$rep->rep_name_last.'<br />
													'.$rep->rep_email.' (e)<br />
													'.($rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone).' (p)<br />
													'.$off->off_city.", ".$off->off_state.'
												</p>
											</td>
										</tr>
									</tbody>
								</table>
								<br /><br />';
		$print_layout_html .= "<table>
								<tbody>
									<tr>
										<td style='padding:0;' colspan='2'>
											<span class='caption'>".$zone->zon_name." – ".$zone->zon_size."kW System Layout</span>
											<img src='".$ip_url."' width='".($have_ip ? "" : 650)."' class='layout-img' alt='Zone Layout' /><br /><br />
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
	$install_params .= "<table>";
	$install_params .= "<caption>".$zone->zon_name." Installation Parameters:</caption>";
	$install_params .= "<tbody class='tabled'>";
	$install_params .= "<tr class='dark'>";
	$install_params .= "<td>DC Array Size</td>";
	$install_params .= "<td align='right'>".$zone->zon_size." kW</td>";
	$install_params .= "</tr>";
	$install_params .= "<tr class='light'>";
	$install_params .= "<td>1st Year Solar Energy Production</td>";
	$install_params .= "<td align='right'>".number_format($zone->zon_production)." kWh</td>";
	$install_params .= "</tr>";
	$install_params .= "<tr class='dark'>";
	$install_params .= "<td>Tilt</td>";
	$install_params .= "<td align='right'>".$zone_tilt."º</td>";
	$install_params .= "</tr>";
	$install_params .= "<tr class='light'>";
	$install_params .= "<td>Azimuth</td>";
	$install_params .= "<td align='right'>".$zone->zon_azimuth."º</td>";
	$install_params .= "</tr>";
	if($pro_num_zones > 1) {
		$install_params .= "<tr>";
		$install_params .= "<td class='big darker round-l'>Portion of total System Energy Production</td>";
		$install_params .= "<td align='right' class='big darker round-r'>".(round($zone->zon_production / $f->production * 10000)/100)."%</td>";
		$install_params .= "</tr>";
	}
	$install_params .= "</tbody>";
	$install_params .= "</table>";
}
// inverters
$inverters = explode(",",substr($pro->pro_inverter,0,-1));
$inverter_qntys = array();
$inverter_descs = array();
$inverter_prices = array();
$total_inverter_price = 0;
foreach($inverters as $in) {
	if(strpos($in,"_g_")!==FALSE) $in = substr($in,0,strpos($in,"_g_"));
	$m->getRow("es_inverters",$in,"inv_model_num");
	$inverter_qntys[] = 1;
	$inverter_descs[] = $m->lastData()->inv_desc;
	$inverter_price = $m->lastData()->inv_price*(1 + $off->off_inventory_up*0.01)*(1 + $off->off_inventory_margin*0.01);
	$inverter_prices[] = $inverter_price;
	$total_inverter_price += $inverter_price;
}
// monitors
$monitors = explode(",",substr($pro->pro_data_monitors,0,-1));
$monitor_types = explode(",",substr($pro->pro_data_monitor_types,0,-1));
$monitor_qntys = array();
$monitor_descs = array();
$monitor_prices = array();
for($i=0;$i<count($monitors);$i++) {
	if($m->getRow("es_data_monitoring",$monitors[$i],"dat_model_num")) {
		$monitor_qntys[] = 1;
		switch($monitor_types[$i]) {
			case 1 :
				$monitor_descs[] = "FREE ".$m->lastData()->dat_desc;
				$monitor_prices[] = 0;
				break;
			case 0 :
				$monitor_descs[] = $m->lastData()->dat_desc;
				$monitor_prices[] = $m->lastData()->dat_price*(1 + $off->off_inventory_up*0.01)*(1 + $off->off_inventory_margin*0.01);
				break;
		}
	}
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
// add racking if duplicate
for($i=0;$i<count($racking_descs);$i++) {
	for($j=0;$j<count($racking_descs);$j++) {
		if($racking_descs[$j]==$racking_descs[$i] && $i!=$j && $racking_descs[$i]!=NULL && $racking_descs[$j]!=NULL) {
			$racking_qntys[$i] += $racking_qntys[$j];
			$racking_prices[$i] += $racking_prices[$j];
			$racking_descs[$j] = NULL;
			$racking_qntys[$j] = NULL;
			$racking_prices[$j] = NULL;
		}
	}
}
$racking_descs = array_values(array_filter($racking_descs,"strlen"));
$racking_qntys = array_values(array_filter($racking_qntys,"strlen"));
$racking_prices = array_values(array_filter($racking_prices,"strlen"));
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
// add monitor if duplicate
for($i=0;$i<count($monitor_descs);$i++) {
	for($j=0;$j<count($monitor_descs);$j++) {
		if($monitor_descs[$j]==$monitor_descs[$i] && $i!=$j && $monitor_descs[$i]!=NULL && $monitor_descs[$j]!=NULL) {
			$monitor_qntys[$i] += $monitor_qntys[$j];
			$monitor_prices[$i] += $monitor_prices[$j];
			$monitor_descs[$j] = NULL;
			$monitor_qntys[$j] = NULL;
			$monitor_prices[$j] = NULL;
		}
	}
}
$monitor_descs = array_values(array_filter($monitor_descs,"strlen"));
$monitor_qntys = array_values(array_filter($monitor_qntys,"strlen"));
$monitor_prices = array_values(array_filter($monitor_prices,"strlen"));
// parse additional rebates
$add_rebate_types = explode(",",substr($pro->pro_rebate_type,0,-1));
$add_rebate_descs = explode(",",substr($pro->pro_rebate_desc,0,-1));
$add_rebate_amnts = explode(",",substr($pro->pro_rebate_amnt,0,-1));
$add_rebate_order = explode(",",substr($pro->pro_rebate_display_weight,0,-1));
for($i=0;$i<count($add_rebate_types);$i++) {
	if($add_rebate_amnts[$i]!="") {
		switch($add_rebate_types[$i]) {
			case 0 :
				$rt = "@ $".$add_rebate_amnts[$i]." / Watt";
				$rp = $add_rebate_amnts[$i]*$f->size*1000;
				break;
			case 1 :
				$rt = "@ ".$add_rebate_amnts[$i]."% System Price";
				$rp = $add_rebate_amnts[$i]*$f->price*0.01;
				break;
			case 2 :
				$rt = "@ Fixed Amount";
				$rp = $add_rebate_amnts[$i];
				break;
		}
		if($add_rebate_order[$i]==0) {
			$rebate_types_bbl[] = $rt;
			$rebate_prices_bbl[] = $rp;
			$rebate_descs_bbl[] = $add_rebate_descs[$i];
		} else {
			$rebate_types_abl[] = $rt;
			$rebate_prices_abl[] = $rp;
			$rebate_descs_abl[] = $add_rebate_descs[$i];
		}
	}
}
// show tax credit info?
$use_credit = $f->credit!=0 ? TRUE : FALSE;
if($use_credit) {
	$rebate_types_abl[] = "";
	$rebate_prices_abl[] = $f->credit;
	$rebate_descs_abl[] = "30% Federal Tax Credit*";
}
// components lines
$components_html = "";
$c = 0;
for($i=0;$i<count($module_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$module_qntys[$i]."</td><td class='ex'></td><td>".$module_descs[$i]."</td><td align='right'>$".number_format($module_prices[$i])."</td></tr>";
	$c++;
}
for($i=0;$i<count($racking_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".number_format($racking_qntys[$i])." (ft.)</td><td class='ex'></td><td>".$racking_descs[$i]."</td><td align='right'>$".number_format($racking_prices[$i])."</td></tr>";
	$c++;
}
for($i=0;$i<count($mounting_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".number_format($mounting_qntys[$i])."</td><td class='ex'></td><td>".$mounting_descs[$i]."</td><td align='right'>$".number_format($mounting_prices[$i])."</td></tr>";
	$c++;
}
for($i=0;$i<count($inverter_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$inverter_qntys[$i]."</td><td class='ex'></td><td>".$inverter_descs[$i]."</td><td align='right'>$".number_format($inverter_prices[$i])."</td></tr>";
	$c++;
}
if($f->misc_materials!=0) {
	$misc_desc = "";
	if($pro->pro_conduit_out!=0 || $pro->pro_conduit_in!=0 || $pro->pro_conduit_under!=0) $misc_desc .= "Conduit, Wire, Misc. Electrical Supplies, ";
	if($pro->pro_misc_materials!=0 && $pro->pro_misc_materials_desc!="") $misc_desc .= $pro->pro_misc_materials_desc.", ";
	else if($pro->pro_misc_materials!=0) $misc_desc .= "Misc. Materials, ";
	$misc_desc = substr($misc_desc,0,-2);
	if($misc_desc=="") $misc_desc = "Misc. Materials";
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>&nbsp;</td><td>&nbsp;</td><td>".$misc_desc."</td><td align='right'>$".number_format($f->misc_materials)."</td></tr>";
	$c++;
}
for($i=0;$i<count($monitor_qntys);$i++) {
	$components_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$monitor_qntys[$i]."</td><td class='ex'></td><td>".$monitor_descs[$i]."</td><td align='right'>$".number_format($monitor_prices[$i])."</td></tr>";
	$c++;
}
$components_html .= "<tr><td colspan='3' class='big darker round-l'>Materials Total</td><td align='right' class='big darker round-r'>$".number_format($f->comp_total)."</td></tr>";
// labor lines
$labor_html = "";
$c = 0;
$labor_html .= "<tr class='dark'><td>PV System – Installation Labor</td><td align='right'>$".number_format($f->install_labor)."</td></tr>";
$c++;
$labor_html .= "<tr><td class='big darker round-l'>Labor Total</td><td align='right' class='big darker round-r'>$".number_format($f->install_labor)."</td></tr>";
// fees lines
$fees_html = "";
$c = 0;
if($f->permit!=0) {
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Permit Fees</td><td align='right'>$".number_format($f->permit)."</td></tr>";
	$c++;
}
if($pro->pro_engin_fee!=0) {
	$engin_up = $pro->pro_engin_fee*$off->off_sub_up*0.01;
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Engineering Fees</td><td align='right'>$".number_format($pro->pro_engin_fee+$engin_up)."</td></tr>";
	$c++;
}
if($pro->pro_extra_fee!=0) {
	$extra_up = $pro->pro_extra_fee*$off->off_sub_up*0.01;
	if($pro->pro_extra_desc!="") $pro->pro_extra_desc = "(".$pro->pro_extra_desc.")";
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Extra Fees ".$pro->pro_extra_desc."</td><td align='right'>$".number_format($pro->pro_extra_fee+$extra_up)."</td></tr>";
	$c++;
}
if($f->equip!=0) {
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Equipment Rental Fees</td><td align='right'>$".number_format($f->equip)."</td></tr>";
	$c++;
}
if($pro->pro_inspection!=0) {
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Design / Inspection / Commissioning Fees</td><td align='right'>$".number_format($pro->pro_inspection)."</td></tr>";
	$c++;
}
for($i=0;$i<count($rebate_descs_bbl);$i++) {
	if($rebate_prices_bbl[$i]!=0) {
		if($rebate_descs_bbl[$i]=="") $rebate_descs_bbl[$i] = "Rebate";
		$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$rebate_descs_bbl[$i]." (".$rebate_types_bbl[$i].")</td><td class='red-txt' align='right'>-&nbsp&nbsp$".number_format($rebate_prices_bbl[$i])."</td></tr>";
		$c++;
	}
}
if($pro->pro_discount!=0) {
	if($pro->pro_discount_desc!="") $pro->pro_discount_desc = "(".$pro->pro_discount_desc.")";
	$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Discount ".$pro->pro_discount_desc."</td><td class='red-txt' align='right'>-&nbsp&nbsp$".number_format($pro->pro_discount)."</td></tr>";
	$c++;
}
$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Subtotal</td><td align='right'>$".number_format($f->subtotal)."</td></tr>";
$c++;
$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>Sales Tax</td><td align='right'>$".number_format($f->tax)."</td></tr>";
$c++;
$fees_html .= "<tr><td class='big darker round-l'>Total System Out of Pocket Cost (@ $".$f->ppw_cus_net." / Watt)</td><td class='big darker round-r' align='right'>$".number_format($f->cus_price)."</td></tr>";
// rebates after out of pocket
$c = 1;
for($i=0;$i<count($rebate_descs_abl);$i++) {
	if($rebate_prices_abl[$i]!=0) {
		if($rebate_descs_abl[$i]=="") $rebate_descs_abl[$i] = "Rebate";
		if($rebate_types_abl[$i]!="") $rebate_types_abl[$i] = "(".$rebate_types_abl[$i].")";
		$fees_html .= "<tr class='".$row_color[($c+1)%2]."'><td>".$rebate_descs_abl[$i]." ".$rebate_types_abl[$i]."</td><td class='red-txt' align='right'>-&nbsp&nbsp$".number_format($rebate_prices_abl[$i])."</td></tr>";
		$c++;
	}
}
// final cost
if(count($rebate_descs_abl)>0) $fees_html .= "<tr><td class='".$row_color[($c+1)%2]." round-l'>Final Cost To You</td><td align='right' class='".$row_color[($c+1)%2]." round-r' style='font-weight:bold;'>$".number_format($f->cus_after_credit)."</td></tr>";
// materials
$materials_html = "";
$print_materials_html = "";
foreach($module_descs as $module_desc) {
	if($m->getRow("es_modules",$module_desc,"mod_desc")) {
		$module = $m->lastData();
		if($module->mod_cutsheet_uri!="") {
			$materials_html .= "<tr><td colspan='2'>&nbsp;</td></tr>
								<tr>
									<td style='padding:0; width:180px;'>
										<a href='".$module->mod_cutsheet_uri."'><img src='".$module->mod_cutsheet_t_uri."' width='154' height='200' alt='Cutsheet Thumbnail' /></a>
									</td>
									<td style='padding:0; vertical-align:top;'>
										<h2 class='spec-head'>".$module_desc."</h2>
										<img src='gfx/pdf_icon.jpg' width='30' height='10' alt='PDF Icon' /> <a href='".$module->mod_cutsheet_uri."'>Download Technical Data Sheet</a>
										<br /><br />
										<img src='gfx/ul-ce_logos.jpg' width='130' height='50' alt='UL and CE Logos' />
									</td>
							  </tr>";
		}
		if($module->mod_print_cutsheet_uri!="") {
			$print_uris = explode(",",$module->mod_print_cutsheet_uri);
			foreach($print_uris as $uri) {
				$print_materials_html .= "<div style='page-break-before:always;' class='fake-break'></div>
										<div class='proposal-head'>
											<table style='width:664px;'>
												<tr>
													<td style='padding:0 0 6px 0; vertical-align:bottom;'>
														<h1 class='page-head'>
															<span style='font-weight:bold;'>Materials</span> ".$job->job_name." &ndash; ".$f->size."kW
														</h1>
													</td>
													<td style='padding:0 0 4px 0;' align='right'>
														<img src='gfx/logo-black.png' alt='small logo' />
													</td>
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
	if($m->getRow("es_inverters",$inverter_desc,"inv_desc")) {
		$inverter = $m->lastData();
		if($inverter->inv_cutsheet_uri!="") {
			$materials_html .= "<tr><td colspan='2'>&nbsp;</td></tr>
								<tr>
									<td style='padding:0; width:180px;'>
										<a href='".$inverter->inv_cutsheet_uri."'><img src='".$inverter->inv_cutsheet_t_uri."' width='154' height='200' alt='Cutsheet Thumbnail' /></a>
									</td>
									<td style='padding:0; vertical-align:top;'>
										<h2 class='spec-head'>".$inverter_desc."</h2>
										<img src='gfx/pdf_icon.jpg' width='30' height='10' alt='PDF Icon' /> <a href='".$inverter->inv_cutsheet_uri."'>Download Technical Data Sheet</a>
										<br /><br />
										<img src='gfx/ul-ce_logos.jpg' width='130' height='50' alt='UL and CE Logos' />
									</td>
							  </tr>";
		}
		if($inverter->inv_print_cutsheet_uri!="") {
			$print_uris = explode(",",$inverter->inv_print_cutsheet_uri);
			foreach($print_uris as $uri) {
				$print_materials_html .= "<div style='page-break-before:always;' class='fake-break'></div>
										<div class='proposal-head'>
											<table style='width:664px;'>
												<tr>
													<td style='padding:0 0 6px 0; vertical-align:bottom;'>
														<h1 class='page-head'>
															<span style='font-weight:bold;'>Materials</span> ".$job->job_name." &ndash; ".$f->size."kW
														</h1>
													</td>
													<td style='padding:0 0 4px 0;' align='right'>
														<img src='gfx/logo-black.png' alt='small logo' />
													</td>
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