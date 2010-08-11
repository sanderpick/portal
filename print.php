<?php
#——————————————————————————————–—————————————————————–––––––––– HOST
$host = gethostbyaddr(gethostbyname($_SERVER["SERVER_NAME"]));
preg_match('/\.([a-z,A-Z]{2,6})$/',$host,$tld);
switch($tld[1]) {
	case "ld" : $EINSTEIN_LOC = "../estimator/"; break; // local
	default : $EINSTEIN_LOC = "../einstein.cleanenergysolutionsinc.com/"; break;
}
$LHS_LOC = "http://lighthousesolar.com";
#——————————————————————————————–—————————————————————– BEGIN SESSION
require_once($EINSTEIN_LOC."includes/es-manager.class.php");
$m = new EstimatorManager();
$e = FALSE;
// ge the pro key
$pro_key = (isset($_GET['pro_key'])) ? $_GET['pro_key'] : FALSE;
if(!$pro_key) $e = "Sorry, you must have a valid key to view a proposal.";
else {
	// get the pro
	if(!$m->getRow("es_proposals",$pro_key,"pro_key")) { $e = "Sorry, your proposal key is invalid or expired."; echo $e; }
	else {
		$pro = $m->lastData();
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
				$i_url = $EINSTEIN_LOC.$m->lastData()->up_root.$m->lastData()->up_handle."/".$m->lastData()->up_handle."_sized_800.jpg";
				$layout_html .= "<div style='page-break-before:always;' class='fake-break'></div><div class='proposal-head'> \
											<table cellspacing='0' cellpadding='0' style='width:662px;'> \
												<tr> \
													<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'>".$job->job_name." &ndash; ".$f->size."kW</span> System Layout</h1></td> \
													<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
												</tr> \
											</table> \
										</div> \
								<div class='page proposal-page'> \
								<table cellspacing='0' cellpadding='0'> \
									<tbody> \
										<tr> \
											<td style='padding:0;'> \
												<p style='font-size:9pt;'> \
													<span style='font-size:11pt;'>PV Proposal #".$pro->ID." prepared for:</span> \
													<br /> \
													".$job_html." \
												</p> \
											</td> \
											<td style='padding:0; float:right;'> \
												<p style='font-size:9pt; text-align:right;'> \
													<span style='font-size:11pt;'>prepared by:</span><br /> \
													".$rep->rep_name_first." ".$rep->rep_name_last."<br /> \
													".$rep->rep_email." (e)<br /> \
													".($rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone)." (p)<br /> \
													".$off->off_city.",  \
													".$off->off_state." \
												</p> \
											</td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<tbody> \
										<tr> \
											<td style='padding:0;' colspan='2'> \
												<span class='caption'>".$zone->zon_name." – ".$zone->zon_size."kW System Layout</span><br /><img src='".$i_url."' width='650' style='padding-top:10pt;' alt='Zone Layout' /><br /><br /> \
											</td> \
										</tr> \
										<tr><td style='padding:0;' colspan='1'>&nbsp;</td></tr> \
										<tr> \
											<td style='padding:0;' colspan='2'> \
												I have reviewed and I approve the location and layout of the solar system installation as proposed. \
											</td> \
										</tr> \
										<tr><td style='padding:0;' colspan='1'>&nbsp;</td></tr> \
										<tr><td style='padding:0;' colspan='1'>&nbsp;</td></tr> \
										<tr> \
											<td style='padding:0; border-top:1px dotted #222222;' colspan='1'> \
												Signature / Date \
											</td> \
										</tr> \
									</tbody> \
								</table> \
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
			$install_params .= "<table cellspacing='0' cellpadding='0' style='padding-top:10pt;'>";
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
						$materials_html .= "<div style='page-break-before:always;' class='fake-break'></div> \
												<div class='proposal-head'> \
													<table cellspacing='0' cellpadding='0' style='width:662px;'> \
														<tr> \
															<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'>".$job->job_name." &ndash; ".$f->size."kW</span> Materials</h1></td> \
															<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
														</tr> \
													</table> \
												</div> \
											<div class='page proposal-page'> \
												<img src='".$uri."' alt='".$module_desc."' /> \
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
						$materials_html .= "<div style='page-break-before:always;' class='fake-break'></div><div class='proposal-head'> \
													<table cellspacing='0' cellpadding='0' style='width:662px;'> \
														<tr> \
															<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'>".$job->job_name." &ndash; ".$f->size."kW</span> Materials</h1></td> \
															<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
														</tr> \
													</table> \
												</div> \
											<div class='page proposal-page'> \
												<img src='".$uri."' alt='".$inverter_desc."' /> \
											</div>";
					}
				}
			}
		}
		// check sum for performance area graph
		$job_bill_total = $job->job_bill_1+$job->job_bill_2+$job->job_bill_3+$job->job_bill_4+$job->job_bill_5+$job->job_bill_6+$job->job_bill_7+$job->job_bill_8+$job->job_bill_9+$job->job_bill_10+$job->job_bill_11+$job->job_bill_12;
		$draw_bill = $job_bill_total>0 ? 1 : 0;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="description" content="" />
	<title><?php echo str_replace(" ","_",$job_title)."-LHS-".$f->size."kW-PV_Proposal_#".$pro->ID; ?></title>
	<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<link href="css/print-style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="css/visualize/visualize.css" rel="stylesheet" type="text/css" />
	<link href="css/visualize/visualize-skin.css" rel="stylesheet" type="text/css" />
	<link href="css/print.css" rel="stylesheet" type="text/css" media="print" />
	<script type="text/javascript" src="js/EnhanceJS/enhance.js"></script>
	<script type="text/javascript" src="js/excanvas.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
	<script type="text/javascript" src="js/irr.v2.js"></script>
	<script type="text/javascript" src="js/visualize.jQuery.js"></script>
	<script type="text/javascript">
		// get the financials -- sys_size, sys_output, sys_derate, sys_cost, sys_inc, sys_utility, sys_life, sys_maintenance, sys_usage, sys_sorec, sys_inverter
		var finances = new IRR(<?php echo $f->size; ?>,<?php echo $f->production; ?>,0.0098,<?php echo $f->cus_after_credit_nf; ?>,0.067,<?php echo $zones[0]->zon_erate/100; ?>,30,0.005,<?php echo $job->job_kwh_load; ?>,<?php echo $pro->pro_incentive_rate; ?>,<?php echo $pro->pro_incentive_yrs; ?>,<?php echo $total_inverter_price; ?>);
		//––––––––––––––––––––––––––––––––– CONTENT
		var overview = "<div class='proposal-head'> \
							<table cellspacing='0' cellpadding='0' style='width:662px;'> \
								<tr> \
									<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> Overview</h1></td> \
									<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
								</tr> \
							</table> \
						</div> \
						<div class='page proposal-page'> \
						<table cellspacing='0' cellpadding='0'> \
							<tbody> \
								<tr> \
									<td style='padding:0;'> \
										<p style='font-size:9pt;'> \
											<span style='font-size:11pt;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
											<br /> \
											<?php echo $job_html; ?> \
										</p> \
									</td> \
									<td style='padding:0; float:right;'> \
										<p style='font-size:9pt; text-align:right;'> \
											<span style='font-size:11pt;'>prepared by:</span><br /> \
											<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
											<?php echo $rep->rep_email; ?> (e)<br /> \
											<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
											<?php echo $off->off_city; ?>,  \
											<?php echo $off->off_state; ?> \
										</p> \
									</td> \
								</tr> \
							</tbody> \
						</table> \
						<br /><br /> \
						<p style='width:650px; line-height:14pt;'><?php echo $cover_letter; ?></p> \
					</div>";
		var system = "<div class='proposal-head'> \
						<table cellspacing='0' cellpadding='0' style='width:662px;'> \
							<tr> \
								<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> System Details</h1></td> \
								<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
							</tr> \
						</table> \
					</div> \
					<div class='page proposal-page'> \
						<table cellspacing='0' cellpadding='0'> \
							<tbody> \
								<tr> \
									<td style='padding:0;'> \
										<p style='font-size:9pt;'> \
											<span style='font-size:11pt;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
											<br /> \
											<?php echo $job_html; ?> \
										</p> \
									</td> \
									<td style='padding:0; float:right;'> \
										<p style='font-size:9pt; text-align:right;'> \
											<span style='font-size:11pt;'>prepared by:</span><br /> \
											<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
											<?php echo $rep->rep_email; ?> (e)<br /> \
											<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
											<?php echo $off->off_city; ?>,  \
											<?php echo $off->off_state; ?> \
										</p> \
									</td> \
								</tr> \
							</tbody> \
						</table> \
						<br /><br /> \
						<table cellspacing='0' cellpadding='0'> \
							<caption>Materials Detail:</caption> \
							<thead> \
								<tr> \
									<th scope='col' align='left'>qty.</th> \
									<th class='cell-indent' scope='col' align='left'>component</th> \
									<th scope='col' align='right'>price ($)</th> \
								</tr> \
							</thead> \
							<tbody> \
								<?php echo $components_html; ?> \
							</tbody> \
						</table> \
						<br /><br /> \
						<table cellspacing='0' cellpadding='0'> \
							<caption>Labor Detail:</caption> \
							<thead> \
								<tr> \
									<th scope='col' align='left'>description</th> \
									<th scope='col' align='right'>price ($)</th> \
								</tr> \
							</thead> \
							<tbody> \
								<?php echo $labor_html; ?> \
							</tbody> \
						</table> \
						<br /><br /> \
						<table cellspacing='0' cellpadding='0'> \
							<caption>Credits and Fees:</caption> \
							<thead> \
								<tr> \
									<th scope='col' align='left'>item</th> \
									<th scope='col' align='right'>price ($)</th> \
								</tr> \
							</thead> \
							<tbody> \
								<?php echo $fees_html; ?> \
							</tbody> \
							<tfoot> \
								<tr> \
									<td class='cell-foot' colspan='3'>* This figure represents your <em>estimated</em> final cost after the 30% Federal Tax Credit. Please consult with a tax professional when claiming.</td> \
								</tr> \
							</tfoot> \
						</table> \
					</div>";
		var performance = "<div class='proposal-head'> \
								<table cellspacing='0' cellpadding='0' style='width:662px;'> \
									<tr> \
										<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> System Performance</h1></td> \
										<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
									</tr> \
								</table> \
							</div> \
							<div class='page proposal-page'> \
								<table cellspacing='0' cellpadding='0'> \
									<tbody> \
										<tr> \
											<td style='padding:0;'> \
												<p style='font-size:9pt;'> \
													<span style='font-size:11pt;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
													<br /> \
													<?php echo $job_html; ?> \
												</p> \
											</td> \
											<td style='padding:0; float:right;'> \
												<p style='font-size:9pt; text-align:right;'> \
													<span style='font-size:11pt;'>prepared by:</span><br /> \
													<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
													<?php echo $rep->rep_email; ?> (e)<br /> \
													<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
													<?php echo $off->off_city; ?>,  \
													<?php echo $off->off_state; ?> \
												</p> \
											</td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<caption>Estimated System Contribution*:</caption> \
									<thead> \
										<tr> \
											<th scope='col' align='left'>Year 1 Energy Produced</th> \
											<th scope='col' align='left'>Annual Energy Consumed</th> \
											<th scope='col' align='right'>System Contribution</th> \
										</tr> \
									</thead> \
									<tbody> \
										<tr class='dark'> \
											<td class='cell-rebate cell-emphasis'><?php echo number_format($f->production); ?> kWh</td> \
											<td class='cell-rebate cell-emphasis' align='left'><?php echo $job->job_kwh_load>0 ? number_format($job->job_kwh_load).' kWh' : 'n / a'; ?></td> \
											<td class='cell-rebate cell-emphasis' align='right'><?php echo $job->job_kwh_load>0 ? (round($f->production / $job->job_kwh_load * 10000)/100).'%' : 'n / a'; ?></td> \
										</tr> \
									</tbody> \
								</table> \
								<?php echo $install_params; ?> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<caption>Estimated System Output Analysis*:</caption> \
									<thead> \
										<tr> \
											<th scope='col' align='left'>Month</th> \
											<th scope='col' align='left'>AC Energy Usage (kWh)</th> \
											<th scope='col' align='right'>Solar Radiation (kWh/m<span class='super'>2</span>/day)</th> \
											<th scope='col' align='right'>AC Energy Output (kWh)</th> \
											<th scope='col' align='right'>Energy Output Value ($)</th> \
										</tr> \
									</thead> \
									<tbody> \
										<tr class='dark'> \
											<td>Jan</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_1) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][0]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][0]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][0]; ?></td> \
										</tr> \
										<tr class='light'> \
											<td>Feb</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_2) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][1]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][1]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][1]; ?></td> \
										</tr> \
										<tr class='dark'> \
											<td>Mar</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_3) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][2]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][2]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][2]; ?></td> \
										</tr> \
										<tr class='light'> \
											<td>Apr</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_4) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][3]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][3]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][3]; ?></td> \
										</tr> \
										<tr class='dark'> \
											<td>May</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_5) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][4]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][4]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][4]; ?></td> \
										</tr> \
										<tr class='light'> \
											<td>Jun</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_6) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][5]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][5]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][5]; ?></td> \
										</tr> \
										<tr class='dark'> \
											<td>Jul</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_7) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][6]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][6]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][6]; ?></td> \
										</tr> \
										<tr class='light'> \
											<td>Aug</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_8) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][7]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][7]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][7]; ?></td> \
										</tr> \
										<tr class='dark'> \
											<td>Sep</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_9) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][8]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][8]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][8]; ?></td> \
										</tr> \
										<tr class='light'> \
											<td>Oct</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_10) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][9]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][9]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][9]; ?></td> \
										</tr> \
										<tr class='dark'> \
											<td>Nov</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_11) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][10]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][10]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][10]; ?></td> \
										</tr> \
										<tr class='light'> \
											<td>Dec</td> \
											<td><?php echo $draw_bill ? number_format($job->job_bill_12) : '-'; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[0][11]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[1][11]; ?></td> \
											<td align='right'><?php echo $pro_pvwatts[2][11]; ?></td> \
										</tr> \
										<tr class='dark'> \
											<td class='cell-rebate cell-emphasis'>Year</td> \
											<td class='cell-rebate cell-emphasis'><?php echo $draw_bill ? number_format($job_bill_total).' kWh' : '-'; ?></td> \
											<td align='right' class='cell-rebate cell-emphasis'><?php echo $pro_pvwatts[0][12]; ?> <span style='font-size:12px;'>kWh/m<span class='super'>2</span>/day</span></td> \
											<td align='right' class='cell-rebate cell-emphasis'><?php echo number_format($pro_pvwatts[1][12]); ?> kWh</td> \
											<td align='right' class='cell-rebate cell-emphasis'>$<?php echo number_format($pro_pvwatts[2][12]); ?></td> \
										</tr> \
									</tbody> \
								</table> \
								<table> \
									<tfoot> \
										<tr> \
											<td class='cell-foot' colspan='4'>* Data as calculated by <a href='http://rredc.nrel.gov/solar/calculators/PVWATTS/version1/' target='_blank'>PVWATTS</a> developed by the National Renewable Energy Laboratory.</td> \
										</tr> \
									</tfoot> \
								</table> \
							</div>";
			var graphs = "<div class='proposal-head'> \
								<table cellspacing='0' cellpadding='0' style='width:662px;'> \
									<tr> \
										<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> System Performance</h1></td> \
										<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
									</tr> \
								</table> \
							</div> \
							<div class='page proposal-page'> \
								<table cellspacing='0' cellpadding='0'> \
									<tbody> \
										<tr> \
											<td style='padding:0;'> \
												<p style='font-size:9pt;'> \
													<span style='font-size:11pt;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
													<br /> \
													<?php echo $job_html; ?> \
												</p> \
											</td> \
											<td style='padding:0; float:right;'> \
												<p style='font-size:9pt; text-align:right;'> \
													<span style='font-size:11pt;'>prepared by:</span><br /> \
													<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
													<?php echo $rep->rep_email; ?> (e)<br /> \
													<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
													<?php echo $off->off_city; ?>,  \
													<?php echo $off->off_state; ?> \
												</p> \
											</td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<caption>Estimated System Output Analysis*:</caption> \
								</table> \
								<div id='visualize-area'> \
									<table> \
										<caption>– Electricity Usage Comparison (estimate) –</caption> \
										<thead> \
											<tr> \
												<td></td> \
												<th scope='col'>Jan</th> \
												<th scope='col'>Feb</th> \
												<th scope='col'>Mar</th> \
												<th scope='col'>Apr</th> \
												<th scope='col'>May</th> \
												<th scope='col'>Jun</th> \
												<th scope='col'>Jul</th> \
												<th scope='col'>Aug</th> \
												<th scope='col'>Sep</th> \
												<th scope='col'>Oct</th> \
												<th scope='col'>Nov</th> \
												<th scope='col'>Dec</th> \
											</tr> \
										</thead> \
										<tbody> \
											<tr> \
												<th scope='row'>Electricity from Utility</th> \
												<td><?php echo $job->job_bill_1; ?></td> \
												<td><?php echo $job->job_bill_2; ?></td> \
												<td><?php echo $job->job_bill_3; ?></td> \
												<td><?php echo $job->job_bill_4; ?></td> \
												<td><?php echo $job->job_bill_5; ?></td> \
												<td><?php echo $job->job_bill_6; ?></td> \
												<td><?php echo $job->job_bill_7; ?></td> \
												<td><?php echo $job->job_bill_8; ?></td> \
												<td><?php echo $job->job_bill_9; ?></td> \
												<td><?php echo $job->job_bill_10; ?></td> \
												<td><?php echo $job->job_bill_11; ?></td> \
												<td><?php echo $job->job_bill_12; ?></td> \
											</tr> \
											<tr> \
												<th scope='row'>Electricity from Solar</th> \
												<td><?php echo $pro_pvwatts[1][0]; ?></td> \
												<td><?php echo $pro_pvwatts[1][1]; ?></td> \
												<td><?php echo $pro_pvwatts[1][2]; ?></td> \
												<td><?php echo $pro_pvwatts[1][3]; ?></td> \
												<td><?php echo $pro_pvwatts[1][4]; ?></td> \
												<td><?php echo $pro_pvwatts[1][5]; ?></td> \
												<td><?php echo $pro_pvwatts[1][6]; ?></td> \
												<td><?php echo $pro_pvwatts[1][7]; ?></td> \
												<td><?php echo $pro_pvwatts[1][8]; ?></td> \
												<td><?php echo $pro_pvwatts[1][9]; ?></td> \
												<td><?php echo $pro_pvwatts[1][10]; ?></td> \
												<td><?php echo $pro_pvwatts[1][11]; ?></td> \
											</tr> \
										</tbody> \
									</table> \
								</div> \
								<div id='visualize-bar'> \
									<?php echo $draw_bill>0 ? '<br /><br />' : ''; ?> \
									<table> \
										<caption>– Available Energy Value –</caption> \
										<thead> \
											<tr> \
												<td></td> \
												<th scope='col'>Jan</th> \
												<th scope='col'>Feb</th> \
												<th scope='col'>Mar</th> \
												<th scope='col'>Apr</th> \
												<th scope='col'>May</th> \
												<th scope='col'>Jun</th> \
												<th scope='col'>Jul</th> \
												<th scope='col'>Aug</th> \
												<th scope='col'>Sep</th> \
												<th scope='col'>Oct</th> \
												<th scope='col'>Nov</th> \
												<th scope='col'>Dec</th> \
											</tr> \
										</thead> \
										<tbody> \
											<tr> \
												<th scope='row'></th> \
												<td><?php echo $pro_pvwatts[2][0]; ?></td> \
												<td><?php echo $pro_pvwatts[2][1]; ?></td> \
												<td><?php echo $pro_pvwatts[2][2]; ?></td> \
												<td><?php echo $pro_pvwatts[2][3]; ?></td> \
												<td><?php echo $pro_pvwatts[2][4]; ?></td> \
												<td><?php echo $pro_pvwatts[2][5]; ?></td> \
												<td><?php echo $pro_pvwatts[2][6]; ?></td> \
												<td><?php echo $pro_pvwatts[2][7]; ?></td> \
												<td><?php echo $pro_pvwatts[2][8]; ?></td> \
												<td><?php echo $pro_pvwatts[2][9]; ?></td> \
												<td><?php echo $pro_pvwatts[2][10]; ?></td> \
												<td><?php echo $pro_pvwatts[2][11]; ?></td> \
											</tr> \
										</tbody> \
									</table> \
									<br /><br /> \
									<table> \
										<caption>– Available Solar Radiation –</caption> \
										<thead> \
											<tr> \
												<td></td> \
												<th scope='col'>Jan</th> \
												<th scope='col'>Feb</th> \
												<th scope='col'>Mar</th> \
												<th scope='col'>Apr</th> \
												<th scope='col'>May</th> \
												<th scope='col'>Jun</th> \
												<th scope='col'>Jul</th> \
												<th scope='col'>Aug</th> \
												<th scope='col'>Sep</th> \
												<th scope='col'>Oct</th> \
												<th scope='col'>Nov</th> \
												<th scope='col'>Dec</th> \
											</tr> \
										</thead> \
										<tbody> \
											<tr> \
												<th scope='row'></th> \
												<td><?php echo $pro_pvwatts[0][0]; ?></td> \
												<td><?php echo $pro_pvwatts[0][1]; ?></td> \
												<td><?php echo $pro_pvwatts[0][2]; ?></td> \
												<td><?php echo $pro_pvwatts[0][3]; ?></td> \
												<td><?php echo $pro_pvwatts[0][4]; ?></td> \
												<td><?php echo $pro_pvwatts[0][5]; ?></td> \
												<td><?php echo $pro_pvwatts[0][6]; ?></td> \
												<td><?php echo $pro_pvwatts[0][7]; ?></td> \
												<td><?php echo $pro_pvwatts[0][8]; ?></td> \
												<td><?php echo $pro_pvwatts[0][9]; ?></td> \
												<td><?php echo $pro_pvwatts[0][10]; ?></td> \
												<td><?php echo $pro_pvwatts[0][11]; ?></td> \
											</tr> \
										</tbody> \
									</table> \
								</div> \
								<table> \
									<tfoot> \
										<tr> \
											<td class='cell-foot' colspan='4'>* Data as calculated by <a href='http://rredc.nrel.gov/solar/calculators/PVWATTS/version1/' target='_blank'>PVWATTS</a> developed by the National Renewable Energy Laboratory.</td> \
										</tr> \
									</tfoot> \
								</table> \
							</div>";
		var layout = "<?php echo $layout_html; ?>";
		var use_credit = <?php echo $use_credit; ?>;
		var financial = "<div class='proposal-head'> \
								<table cellspacing='0' cellpadding='0' style='width:662px;'> \
									<tr> \
										<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> Financial Details</h1></td> \
										<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
									</tr> \
								</table> \
							</div> \
							<div class='page proposal-page'> \
								<table cellspacing='0' cellpadding='0'> \
									<tbody> \
										<tr> \
											<td style='padding:0;'> \
												<p style='font-size:9pt;'> \
													<span style='font-size:11pt;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
													<br /> \
													<?php echo $job_html; ?> \
												</p> \
											</td> \
											<td style='padding:0; float:right;'> \
												<p style='font-size:9pt; text-align:right;'> \
													<span style='font-size:11pt;'>prepared by:</span><br /> \
													<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
													<?php echo $rep->rep_email; ?> (e)<br /> \
													<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
													<?php echo $off->off_city; ?>,  \
													<?php echo $off->off_state; ?> \
												</p> \
											</td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<caption>Financial Analysis:</caption> \
									<thead> \
										<tr> \
											<th scope='col' style='vertical-align:bottom;' align='left'>System<br />Size</th> \
											<th scope='col' style='vertical-align:bottom;' class='cell-small-indent' align='left'>1st Year<br />Energy<br />Production</th> \
											<th scope='col' style='vertical-align:bottom;' class='cell-small-indent' align='left'>Annual<br />Energy<br />Consumed</th> \
											<th scope='col' style='vertical-align:bottom;' class='cell-small-indent' align='left'>Net Cost or<br />Investment*</th> \
											<th scope='col' style='vertical-align:bottom;' class='cell-small-indent' align='left'>Utility<br />Rate</th> \
											<th scope='col' style='vertical-align:bottom;' class='cell-small-indent' align='right'>Levelized<span class='super'>1</span><br />Cost of<br />Solar Energy</th> \
											<th scope='col' style='vertical-align:bottom;' class='cell-small-indent' align='right'>Average<span class='super'>2</span><br />Annual<br />Utility<br />Savings</th> \
											<th scope='col' style='vertical-align:bottom;' class='cell-small-indent' align='right'>Average<br />Monthly<br />Utility<br />Savings</th> \
										</tr> \
									</thead> \
									<tbody> \
										<tr class='dark'> \
											<td><?php echo $f->size; ?> kW</td> \
											<td align='left'><?php echo number_format($f->production); ?> kWh</td> \
											<td align='left'><?php echo number_format($job->job_kwh_load); ?> kWh</td> \
											<td align='left'>$<?php echo $f->cus_after_credit; ?></td> \
											<td align='left'>$<?php echo $zones[0]->zon_erate/100; ?> / kWh</td> \
											<td align='right'>$"+Math.round(1000*finances.lcoe_solar_energy)/1000+" / kWh</td> \
											<td align='right'>$"+addCommas(Math.round(finances.avg_year_savings))+"</td> \
											<td align='right'>$"+addCommas(Math.round(finances.avg_month_savings))+"</td> \
										</tr> \
										<br /><br /> \
										<tr class='dark'> \
											<td style='font-weight:bold;' colspan='5'>Your total energy cost savings from solar over 30 years</td> \
											<td style='font-weight:bold;' colspan='3' align='right'>$"+addCommas(Math.round(finances.elec_savings))+"</td> \
										</tr> \
										<tr class='light'> \
											<td style='font-weight:bold;' colspan='5'>Your 30 year IRR<span class='super'>3</span> (tax free) of your Solar Investment</td> \
											<td style='font-weight:bold;' colspan='3' align='right'>"+Math.round(finances.irr_tax_free*10000)/100+"%</td> \
										</tr> \
										<tr class='dark'> \
											<td class='cell-rebate cell-emphasis' colspan='5'>Total Life-Cycle Payback<span class='super'>4</span> of your Solar Investment</td> \
											<td class='cell-rebate cell-emphasis' colspan='3' align='right'><strong>"+Math.round(finances.total_life_cycle_payback*10000)/100+"%</strong></td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table> \
									<tfoot> \
										<tr> \
											<td class='cell-foot'>";
		financial += (use_credit==1) ? "* This figure represents the total system cost after applying the 30% Fed Tax Credit.<br /><br />" : "";
		financial += "1) &quot;Levelized Cost of Solar Energy&quot; is the approximation of the average $/kWh value of energy produced from the quoted system. The system net cost (in the installation year), plus any O&M costs, is divided by the amount of energy produced by the system over its life-cycle. This calculation is not adjusted for the time-value of money. \
							<br /><br /> \
							2) &quot;Average Annual Utility Savings&quot; is the average annual utility bill savings expected across the system life. This takes into account utility rate inflation and any expected degradation in system performance. This estimate has not assumed any changes in the amount or timing in your building`s energy use. \
							<br /><br /> \
							3) &quot;Internal Rate of Return (IRR)&quot; is the rate of return (annual compounded) that the cash flows bring based upon the amount of capital invested upon installation. If you financed your system 100%, IRR does not apply since you did not invest your capital. \
							<br /><br /> \
							4) &quot;Total Life-Cycle Payback&quot; is the rate of return % the invested Net Cost (in the installation year) yields over the systems expected life. This calculation is not adjusted for the time-value of money. \
						</tr> \
					</tfoot> \
				</table></div>";
		var financial_graphs = "<div class='proposal-head'> \
								<table cellspacing='0' cellpadding='0' style='width:662px;'> \
									<tr> \
										<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> Financial Details</h1></td> \
										<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
									</tr> \
								</table> \
							</div> \
							<div class='page proposal-page'> \
								<table cellspacing='0' cellpadding='0'> \
									<tbody> \
										<tr> \
											<td style='padding:0;'> \
												<p style='font-size:9pt;'> \
													<span style='font-size:11pt;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
													<br /> \
													<?php echo $job_html; ?> \
												</p> \
											</td> \
											<td style='padding:0; float:right;'> \
												<p style='font-size:9pt; text-align:right;'> \
													<span style='font-size:11pt;'>prepared by:</span><br /> \
													<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
													<?php echo $rep->rep_email; ?> (e)<br /> \
													<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
													<?php echo $off->off_city; ?>,  \
													<?php echo $off->off_state; ?> \
												</p> \
											</td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<caption>Financial Analysis:</caption> \
								</table> \
								<div id='visualize-line'> \
									<table> \
										<caption>– Cumulative Electric Expenses Comparison –</caption> \
										<thead> \
											<tr> \
												<td></td> \
												<th scope='col'>1</th> \
												<th scope='col'>2</th> \
												<th scope='col'>3</th> \
												<th scope='col'>4</th> \
												<th scope='col'>5</th> \
												<th scope='col'>6</th> \
												<th scope='col'>7</th> \
												<th scope='col'>8</th> \
												<th scope='col'>9</th> \
												<th scope='col'>10</th> \
												<th scope='col'>11</th> \
												<th scope='col'>12</th> \
												<th scope='col'>13</th> \
												<th scope='col'>14</th> \
												<th scope='col'>15</th> \
												<th scope='col'>16</th> \
												<th scope='col'>17</th> \
												<th scope='col'>18</th> \
												<th scope='col'>19</th> \
												<th scope='col'>20</th> \
												<th scope='col'>21</th> \
												<th scope='col'>22</th> \
												<th scope='col'>23</th> \
												<th scope='col'>24</th> \
												<th scope='col'>25</th> \
												<th scope='col'>26</th> \
												<th scope='col'>27</th> \
												<th scope='col'>28</th> \
												<th scope='col'>29</th> \
												<th scope='col'>30</th> \
											</tr> \
										</thead> \
										<tbody> \
											<tr> \
												<th scope='row'>Cumulative Electricity Cost</th> \
												<td>"+Math.round(finances.cashflows[0].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[1].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[2].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[3].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[4].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[5].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[6].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[7].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[8].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[9].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[10].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[11].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[12].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[13].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[14].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[15].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[16].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[17].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[18].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[19].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[20].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[21].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[22].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[23].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[24].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[25].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[26].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[27].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[28].cum_savings_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[29].cum_savings_solar)+"</td> \
											</tr> \
											<tr> \
												<th scope='row'>Solar Investment (w/ Inverter Replacement and SOREC if applicable)</th> \
												<td>"+Math.round(finances.cashflows[0].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[1].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[2].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[3].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[4].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[5].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[6].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[7].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[8].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[9].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[10].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[11].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[12].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[13].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[14].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[15].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[16].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[17].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[18].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[19].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[20].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[21].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[22].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[23].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[24].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[25].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[26].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[27].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[28].simple_payback_sorec)+"</td> \
												<td>"+Math.round(finances.cashflows[29].simple_payback_sorec)+"</td> \
											</tr> \
										</tbody> \
									</table> \
									<br /><br /> \
									<table> \
										<caption>– Annual Electricity Bill Comparison –</caption> \
										<thead> \
											<tr> \
												<td></td> \
												<th scope='col'>1</th> \
												<th scope='col'>2</th> \
												<th scope='col'>3</th> \
												<th scope='col'>4</th> \
												<th scope='col'>5</th> \
												<th scope='col'>6</th> \
												<th scope='col'>7</th> \
												<th scope='col'>8</th> \
												<th scope='col'>9</th> \
												<th scope='col'>10</th> \
												<th scope='col'>11</th> \
												<th scope='col'>12</th> \
												<th scope='col'>13</th> \
												<th scope='col'>14</th> \
												<th scope='col'>15</th> \
												<th scope='col'>16</th> \
												<th scope='col'>17</th> \
												<th scope='col'>18</th> \
												<th scope='col'>19</th> \
												<th scope='col'>20</th> \
												<th scope='col'>21</th> \
												<th scope='col'>22</th> \
												<th scope='col'>23</th> \
												<th scope='col'>24</th> \
												<th scope='col'>25</th> \
												<th scope='col'>26</th> \
												<th scope='col'>27</th> \
												<th scope='col'>28</th> \
												<th scope='col'>29</th> \
												<th scope='col'>30</th> \
											</tr> \
										</thead> \
										<tbody> \
											<tr> \
												<th scope='row'>Pre-Solar</th> \
												<td>"+Math.round(finances.cashflows[0].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[1].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[2].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[3].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[4].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[5].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[6].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[7].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[8].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[9].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[10].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[11].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[12].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[13].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[14].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[15].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[16].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[17].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[18].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[19].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[20].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[21].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[22].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[23].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[24].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[25].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[26].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[27].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[28].elec_bill_no_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[29].elec_bill_no_solar)+"</td> \
											</tr> \
											<tr> \
												<th scope='row'>Post-Solar</th> \
												<td>"+Math.round(finances.cashflows[0].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[1].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[2].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[3].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[4].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[5].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[6].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[7].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[8].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[9].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[10].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[11].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[12].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[13].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[14].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[15].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[16].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[17].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[18].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[19].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[20].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[21].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[22].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[23].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[24].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[25].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[26].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[27].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[28].elec_bill_solar)+"</td> \
												<td>"+Math.round(finances.cashflows[29].elec_bill_solar)+"</td> \
											</tr> \
										</tbody> \
									</table> \
								</div> \
							</div>";
		var environmental = "<div class='proposal-head'> \
								<table cellspacing='0' cellpadding='0' style='width:662px;'> \
									<tr> \
										<td style='padding:0 0 6px 0; vertical-align:bottom;'><h1 class='page-head'><span style='font-weight:bold;'><?php echo $job->job_name; ?> &ndash; <?php echo $f->size; ?>kW</span> Environmental Details</h1></td> \
										<td style='padding:0 0 4px 0;' align='right'><img src='gfx/logo-black.png' alt='small logo' /></td> \
									</tr> \
								</table> \
							</div> \
							<div class='page proposal-page'> \
								<table cellspacing='0' cellpadding='0'> \
									<tbody> \
										<tr> \
											<td style='padding:0;'> \
												<p style='font-size:9pt;'> \
													<span style='font-size:11pt;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
													<br /> \
													<?php echo $job_html; ?> \
												</p> \
											</td> \
											<td style='padding:0; float:right;'> \
												<p style='font-size:9pt; text-align:right;'> \
													<span style='font-size:11pt;'>prepared by:</span><br /> \
													<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
													<?php echo $rep->rep_email; ?> (e)<br /> \
													<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
													<?php echo $off->off_city; ?>,  \
													<?php echo $off->off_state; ?> \
												</p> \
											</td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<caption>Environmental Impacts:</caption> \
									<tbody> \
										<tr class='dark'> \
											<td class='cell-rebate cell-emphasis' colspan='2'>Estimated Annual Carbon Offset</td> \
											<td class='cell-rebate cell-emphasis' colspan='1' align='right'><?php echo number_format($f->production*1.71); ?> lbs. of Carbon Dioxide*</td> \
										</tr> \
									</tbody> \
								</table> \
								<br /><br /> \
								<table cellspacing='0' cellpadding='0'> \
									<caption>System Offset Parameters:</caption> \
									<thead> \
										<tr> \
											<th scope='col' align='left'>Year 1 Energy Produced</th> \
											<th scope='col' align='left'>Annual Energy Consumed</th> \
											<th scope='col' align='right'>System Contribution</th> \
											<th scope='col' align='right'>Annual Carbon Offset</th> \
										</tr> \
									</thead> \
									<tbody> \
										<tr class='dark'> \
											<td><?php echo number_format($f->production); ?> kWh</td> \
											<td align='left'><?php echo $job->job_kwh_load>0 ? number_format($job->job_kwh_load).' kWh' : 'n / a'; ?></td> \
											<td align='right'><?php echo $job->job_kwh_load>0 ? (round($f->production / $job->job_kwh_load * 10000)/100).'%' : 'n / a'; ?></td> \
											<td align='right'><?php echo number_format($f->production*1.71); ?> lbs CO<span class='sub'>2</span></td> \
										</tr> \
										<tr class='light'> \
											<td colspan='4'>&nbsp;</td> \
										</tr> \
										<tr> \
											<td colspan='4' style='padding:0;'> \
												<div class='left cell-round' style='background:#eee url(img/trees.jpg) center no-repeat;'><p><em>save</em> Trees</p></div> \
												<div class='right cell-round' style='background:#eee url(img/oil.jpg) center no-repeat;'><p>help <em>avoid</em> Oil</p></div> \
												<div class='cell-round' style='margin:0 222px; background:#eee url(img/cars.jpg) center no-repeat;'><p><em>reduce</em> Pollution</p></div> \
												<div class='clear'></div> \
											</td> \
										</tr> \
									</tbody> \
									<tfoot> \
										<tr> \
											<td class='cell-foot' colspan='4'> \
												* These figures are based on data obtained from the National Renewable Energy Laboratory (NREL). \
											</td> \
										</tr> \
									</tfoot> \
								</table> \
							</div>";
		var materials = "<?php echo $materials_html; ?>";
		// for commas
		function addCommas(nStr) {
			nStr += '';
			x = nStr.split('.');
			x1 = x[0];
			x2 = x.length>1 ? '.'+x[1] : '';
			var rgx = /(\d+)(\d{3})/;
			while(rgx.test(x1)) x1 = x1.replace(rgx,'$1'+','+'$2');
			return x1+x2;
		}
		$(function() {
			$("#proposal-content").append(overview+"<div style='page-break-before:always;' class='fake-break'></div>"+system
				+"<div style='page-break-before:always;' class='fake-break'></div>"+performance
				+"<div style='page-break-before:always;' class='fake-break'></div>"+graphs
				+layout+"<div style='page-break-before:always;' class='fake-break'></div>"+financial
				+"<div style='page-break-before:always;' class='fake-break'></div>"+financial_graphs
				+"<div style='page-break-before:always;' class='fake-break'></div>"+environmental+materials);
			// visualize tables
			$("#visualize-area table, #visualize-bar table").each(function(i) {
				var type, colors, key, post, pre;
				switch(i) {
					case 0 : type = "area"; colors = ["#ff0000","#b1cd49"]; key = true; pre = ""; post = " kWh";
						if(<?php echo $draw_bill; ?>==1) $(this).visualize({ type:type,colors:colors,appendKey:key,width:"558px",height:"100px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
						break;
					case 1 : type = "bar"; colors = ["#5880c0"]; key = false; pre = "$"; post = "";
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"558px",height:"60px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
						break;
					case 2 : type = "bar"; colors = ["#eae854"]; key = false; pre = ""; post = "";
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"558px",height:"60px",yLabelPre:pre,yLabelPost:post,xTitle:"– kWh/m<span class='super'>2</span>/day over First Year –" });
						break;
				}
			});
			$("#visualize-line table").each(function(i) {
				var type, colors;
				switch(i) {
					case 0 : type = "line"; break;
					case 1 : type = "bar"; break;
				}
				$(this).visualize({ type:type,colors:["#ff0000","#b1cd49"],width:"558px",height:"170px",yLabelPre:"$",xTitle:"– System Term (years) –" });
			});
		});			
	</script>
</head>
<body>
	<div id="wrap">		
		<div id="proposal-content"></div>
	</div>
</body>
</html>
<?php
	}
}
?>