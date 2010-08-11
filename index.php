<?php
#——————————————————————————————–—————————————————————–––––––––– HOST
$host = gethostbyaddr(gethostbyname($_SERVER["SERVER_NAME"]));
preg_match('/\.([a-z,A-Z]{2,6})$/',$host,$tld);
switch($tld[1]) {
	case "ld" : $EINSTEIN_LOC = "../estimator/"; break; // local
	default : $EINSTEIN_LOC = "../einstein.cleanenergysolutionsinc.com/"; break;
}
$LHS_LOC = "http://lighthousesolar.com";
$BINDER_LOC = "http://tools.lighthousesolar.com/files/pdf/Lighthousesolar_Sales_Web.pdf";
#——————————————————————————————–—————————————————————– BEGIN SESSION
require_once($EINSTEIN_LOC."includes/es-manager.class.php");
$m = new EstimatorManager();
$e = FALSE;
// ge the pro key
$pro_key = (isset($_GET['pro_key'])) ? $_GET['pro_key'] : FALSE;
if(!$pro_key) { $e = "Sorry, you must have a valid key to view a proposal."; echo $e; }
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
		$m->getRow("es_offices",$pro->pro_officeID);
		$off = $m->lastData();
		// get the rep info
		$m->getRow("es_reps",$pro->pro_repID);
		$rep = $m->lastData();
		// get the customer info
		$m->getRow("es_customers",$pro->pro_customerID);
		$cus = $m->lastData();
		// get the jobs info
		$m->getRow("es_jobs",$pro->pro_jobID);
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
		$cover_letter .= "<a href='mailto:".$rep->rep_email."'>".$rep->rep_email."</a> (e)<br />";
		$cover_letter .= $rep->rep_phone!="" ? $rep->rep_phone." (p)" : $off->off_phone." (p)";
		// overwrite incentive for financials
		$pro->pro_incentive_rate = $pro->pro_incentive==1 ? $pro->pro_incentive_rate : 0;
		// get the zones
		$zoneIDs = explode(",",substr($pro->pro_zones,0,-1));
		$pro_num_zones = count($zoneIDs);
		$pro_num_modules = 0;
		$zones = array();
		foreach($zoneIDs as $zoneID) {
			$m->getRow("es_zones",$zoneID);
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
		$rebate_types = array();
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
			// mounting
			$mounting_qntys[] = $zone->zon_racking_length;
			$m->getRow("es_racking",$zone->zon_racking,"rac_model_num");
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
			if($m->getRow("es_uploads",$zone->zon_layout)) {
				$i_url = $EINSTEIN_LOC.$m->lastData()->up_root.$m->lastData()->up_handle."/".$m->lastData()->up_handle."_sized_800.jpg";
				$layout_html .= "<span class='caption'>".$zone->zon_name." – ".$zone->zon_size."kW System Layout</span><br /><img src='".$i_url."' width='650' style='padding-top:10px;' alt='Zone Layout' /><br /><br />";
			}
			// get the tilt
			if($zone->zon_tilt!="custom") {
				if(preg_match('|\({1}(.*?)\){1}|',$zone->zon_tilt, $tm)) $zone_tilt = $tm[1];
			} else $zone_tilt = $zone->zon_custom_tilt;
			// install params
			$install_params .= "<br /><br />";
			$install_params .= "<span class='caption'>".$zone->zon_name." Installation Parameters:</span>";
			$install_params .= "<br />";
			$install_params .= "<table cellspacing='0' cellpadding='0' style='padding-top:10px;'>";
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
			$m->getRow("es_inverters",$in,"inv_model_num");
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
			if($m->getRow("es_modules",$module_desc,"mod_desc")) {
				$module = $m->lastData();
				if($module->mod_cutsheet_uri!="") {
					$materials_html .= "<tr> \
											<tr> \
												<td colspan='2' style='padding:0;'> \
													<h2 class='spec-head'>".$module_desc."</h2> \
												</td> \
											</tr> \
											<td style='padding:0; width:180px;'> \
												<a href='".$module->mod_cutsheet_uri."'><img src='".$module->mod_cutsheet_t_uri."' width='154' height='200' alt='Cutsheet Thumbnail' /></a> \
											</td> \
											<td style='padding:0; vertical-align:top;'> \
												<p class='specs'> \
													<img src='gfx/pdf_icon.jpg' width='30' height='10' alt='PDF Icon' /> <a href='".$module->mod_cutsheet_uri."'>Download Technical Data Sheet</a> \
													<br /><br /> \
													<img src='gfx/ul-ce_logos.jpg' width='130' height='50' alt='UL and CE Logos' /> \
												</p> \
											</td> \
									  </tr> \
									  <tr> \
											<td colspan='2'>&nbsp;</td> \
									  </tr>";
				}
			}
		}
		foreach($inverter_descs as $inverter_desc) {
			if($m->getRow("es_inverters",$inverter_desc,"inv_desc")) {
				$inverter = $m->lastData();
				if($inverter->inv_cutsheet_uri!="") {
					$materials_html .= "<tr> \
											<tr> \
												<td colspan='2' style='padding:0;'> \
													<h2 class='spec-head'>".$inverter_desc."</h2> \
												</td> \
											</tr> \
											<td style='padding:0; width:180px;'> \
												<a href='".$inverter->inv_cutsheet_uri."'><img src='".$inverter->inv_cutsheet_t_uri."' width='154' height='200' alt='Cutsheet Thumbnail' /></a> \
											</td> \
											<td style='padding:0; vertical-align:top;'> \
												<p class='specs'> \
													<img src='gfx/pdf_icon.jpg' width='30' height='10' alt='PDF Icon' /> <a href='".$inverter->inv_cutsheet_uri."'>Download Technical Data Sheet</a> \
													<br /><br /> \
													<img src='gfx/ul-ce_logos.jpg' width='130' height='50' alt='UL and CE Logos' /> \
												</p> \
											</td> \
									  </tr> \
									  <tr> \
											<td colspan='2'>&nbsp;</td> \
									  </tr>";
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
	<title>My Solar Portal - Lighthouse Solar / <?php echo $off->off_city.", ".$off->off_state; ?></title>
	<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<link href="css/style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="css/custom-theme/jquery-ui-1.8.custom.css" rel="stylesheet" type="text/css" />
	<link href="css/li-scroller.css" rel="stylesheet" type="text/css" />
	<link href="css/contracts.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="css/visualize/visualize.css" rel="stylesheet" type="text/css" />
	<link href="css/visualize/visualize-skin.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="js/EnhanceJS/enhance.js"></script>
	<script type="text/javascript" src="js/excanvas.js"></script>
	<script type="text/javascript" src="js/gradient.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.custom.min.js"></script>
	<script type="text/javascript" src="js/irr.v2.js"></script>
	<script type="text/javascript" src="js/jquery.li-scroller.1.0.js"></script>
	<script type="text/javascript" src="js/visualize.jQuery.js"></script>
	<script type="text/javascript" src="js/date.format.js"></script>
	<script type="text/javascript">
		$(function() {
			// extend utils
			$.extend({
				tsToDate:function(ts) {
					var regex=/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/;
					var parts=ts.replace(regex,"$1 $2 $3 $4 $5 $6").split(' ');
					var d = new Date(parts[0],parts[1]-1,parts[2],parts[3],parts[4],parts[5]);
					// convert utc to local time
					var time = d.getTime();
					var tz = $.getTimeZone();
					var l = new Date(time+(3600000*tz));
					return l.format("shortDate")+" "+l.format("longTime");
				},
				getTimeZone:function() {
					var rightNow = new Date();
					var jan1 = new Date(rightNow.getFullYear(), 0, 1, 0, 0, 0, 0);  // jan 1st
					var june1 = new Date(rightNow.getFullYear(), 6, 1, 0, 0, 0, 0); // june 1st
					var temp = jan1.toGMTString();
					var jan2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
					temp = june1.toGMTString();
					var june2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
					var std_time_offset = (jan1 - jan2) / (1000 * 60 * 60);
					var daylight_time_offset = (june1 - june2) / (1000 * 60 * 60);
					var dst;
					if (std_time_offset==daylight_time_offset) dst = false; // daylight savings time is NOT observed
					else { // positive is southern, negative is northern hemisphere
						var hemisphere = std_time_offset - daylight_time_offset;
						if(hemisphere>=0) std_time_offset = daylight_time_offset;
						dst = true; // daylight savings time is observed
					}
					return dst ? std_time_offset+1 : std_time_offset;
				}
			});
			// get user info
			var System = {
				init:function() {
					this.browser = this.searchString(this.dataBrowser()) || "An unknown browser";
					this.version = this.searchVersion(navigator.userAgent)
						|| this.searchVersion(navigator.appVersion)
						|| "an unknown version";
					this.OS = this.searchString(this.dataOS()) || "an unknown OS";
				},
				searchString:function(data) {
					for(var i=0;i<data.length;i++) {
						var dataString = data[i].string;
						var dataProp = data[i].prop;
						this.versionSearchString = data[i].versionSearch || data[i].identity;
						if(dataString) if(dataString.indexOf(data[i].subString) != -1) return data[i].identity;
						else if(dataProp) return data[i].identity;
					}
				},
				searchVersion:function(dataString) {
					var index = dataString.indexOf(this.versionSearchString);
					if(index==-1) return;
					return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
				},
				dataBrowser:function() { 
					return [
						{ string:navigator.userAgent, subString:"Chrome", identity:"Chrome" },
						{ string:navigator.userAgent, subString:"OmniWeb", versionSearch:"OmniWeb/", identity:"OmniWeb" },
						{ string:navigator.vendor, subString:"Apple", identity:"Safari", versionSearch:"Version" },
						{ string:navigator.userAgent, subString:"Opera", identity:"Opera" },
						{ string:navigator.vendor, subString:"iCab", identity:"iCab" },
						{ string:navigator.vendor, subString:"KDE", identity:"Konqueror" },
						{ string:navigator.userAgent, subString:"Firefox", identity:"Firefox" },
						{ string:navigator.vendor, subString:"Camino", identity:"Camino" },
						{ string:navigator.userAgent, subString:"Netscape", identity:"Netscape" },
						{ string:navigator.userAgent, subString:"MSIE", identity:"Explorer", versionSearch:"MSIE" },
						{ string:navigator.userAgent, subString:"Gecko", identity:"Mozilla", versionSearch:"rv" },
						{ string:navigator.userAgent, subString:"Mozilla", identity:"Netscape", versionSearch:"Mozilla" }
					];
				},
				dataOS:function() {
					return [
						{ string:navigator.platform, subString:"Win", identity:"Windows" },
						{ string:navigator.platform, subString:"Mac", identity:"Mac" },
						{ string:navigator.userAgent, subString:"iPhone", identity:"iPhone/iPod" },
						{ string:navigator.platform, subString:"Linux", identity:"Linux" }
					];
				}
			};
			System.init();
			if(System.browser=="Chrome" || System.browser=="Safari" || (System.OS=="Win" && System.browser=="Firefox")) {
				var glassTop = 229;
				var binderGlassTop = 89;
				var glassInc = 28;
				$("#proposal-menu-over").css("top",glassTop+"px");
				$("#binder-menu-over").css("top",binderGlassTop+"px");
			} else if(System.browser=="Opera") {
				var glassTop = 219;
				var binderGlassTop = 79;
				var glassInc = 26;
				$("#proposal-menu-over").css("top",glassTop+"px");
				$("#binder-menu-over").css("top",binderGlassTop+"px");
			} else {
				var glassTop = 226;
				var binderGlassTop = 86;
				var glassInc = 27;
			}
			// ––––––––––––––––––––––––––––––––– init ui elements
			// main tabs
			$("#menu").tabs({ selected:1 });
			// dialog box
			var pro_valid = <?php echo $pro->pro_delivered; ?>;
			$("#check-out-window").dialog({
				autoOpen:false,
				width:710,
				height:520,
				modal:true,
				buttons:{
					"I Accept":function() { 
						$("#check-out-step").html("<h3 class='check-out-head'>STEP 2 OF 2: Meet with your Sales Rep.</h3>");
						$("#check-out-window").empty().html(step_two);
						//$("#choose-contract").remove();
						controlButton(true,0);
						controlButton(true,1);
						controlButton(true,2);
						controlButton(false,3);
						controlButton(false,4);
						
						if(pro_valid==1) approve();
					},
					// "I Accept":function() {
					// 						controlButton(false,0);
					// 						controlButton(true,1);
					// 						controlButton(false,2);
					// 						var contract_butts = "<div id='choose-contract'> \
					// 								<input type='radio' id='radio1' name='radio' checked='checked' /><label for='radio1'>Buy Now Agreement</label> \
					// 								<input type='radio' id='radio2' name='radio' /><label for='radio2'>PPA Agreement</label> \
					// 							</div>";
					// 						$("#check-out-step").html("<h3 class='check-out-head'>STEP 2 OF 3: Please select and review your Solar System contract.</h3>");
					// 						$("#check-out-window").empty().html(step_two);
					// 						//$(".ui-dialog-buttonpane").append(contract_butts);
					// 						$("#choose-contract").buttonset();
					// 						$("#choose-contract label").live("click",function(){
					// 							switch($(this).attr("for")) {
					// 								case "radio1" :
					// 									$("#contract-holder").empty().load("contracts/LHS-Buy_Now-Contract.htm");
					// 									break;
					// 								case "radio2" :
					// 									$("#contract-holder").empty().load("contracts/LHS-PPA-GenD08682.htm");
					// 									break;
					// 							}
					// 						});
					// 						$("#contract-holder").load("contracts/LHS-Buy_Now-Contract.htm");
					// 					},
					"< Back":function() {
						controlButton(true,0);
						controlButton(false,1);
						controlButton(true,2);
						$("#check-out-step").html("<h3 class='check-out-head'>STEP 1 OF 2: Please review your Solar System placement.</h3>");
						$("#check-out-window").empty().html(step_one);
						//$("#choose-contract").remove();
					},
					"Cancel":function() {
						$("#check-out-window").empty();
						//$("#choose-contract").remove();
						$(this).dialog("close"); 
					},
					"Done":function() {
						$("#check-out-window").empty();
						//$("#choose-contract").remove();
						$(this).dialog("close"); 
						if(pro_valid==1) {
							$("#check-out").hide();
							$("#proposal-pending").fadeIn("slow");
						}
					}
				}
			});
			// check out button
			$("#check-out").click(function() {
				$("#check-out-window").dialog("open");
				initCheckout();
				return false;
			});
			$("#check-out, ul#icons li").hover(
				function() { $(this).addClass("ui-state-hover"); }, 
				function() { $(this).removeClass("ui-state-hover"); }
			);
			// news ticker
			$("ul#ticker").liScroll({travelocity: 0.025});
			// menu control
			$(".list-item").click(function() {
				// set the data
				$("#data").data("proposal-section",$(this).text());
				// set the header text
				setProposalHead();
				// set the content text
				setProposalContent(eval(this.title));
				// move menu over
				var y = glassTop + parseInt(this.id.substring(1))*glassInc;
				$("#proposal-menu-over").animate({ "top":y+"px" },"fast");
			});
			$(".binder-list-item").click(function() {
				// set the data
				$("#data").data("binder-section",$(this).text());
				// set the header text
				setBinderHead();
				// set the content text
				setBinderContent(eval(this.title));
				// move menu over
				var y = binderGlassTop + parseInt(this.id.substring(1))*glassInc;
				$("#binder-menu-over").animate({ "top":y+"px" },"fast");
			});
			// get the financials -- sys_size, sys_output, sys_derate, sys_cost, sys_inc, sys_utility, sys_life, sys_maintenance, sys_usage, sys_sorec, sys_inverter
			var finances = new IRR(<?php echo $f->size; ?>,<?php echo $f->production; ?>,0.0098,<?php echo $f->cus_after_credit_nf; ?>,0.067,<?php echo $zones[0]->zon_erate/100; ?>,30,0.005,<?php echo $job->job_kwh_load; ?>,<?php echo $pro->pro_incentive_rate; ?>,<?php echo $pro->pro_incentive_yrs; ?>,<?php echo $total_inverter_price; ?>);
			//var finances = new IRR(6.60,9361,0.0098,13153.70,0.0387,0.185,30,0.005,11550,0.045,20,3763.70);
			//––––––––––––––––––––––––––––––––– CONTENT
			var overview = "<table cellspacing='0' cellpadding='0'> \
						<tbody> \
							<tr> \
								<td style='padding:0;'> \
									<p style='font-size:10px;'> \
										<span style='font-size:14px;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
										<br /> \
										<?php echo $job_html; ?> \
									</p> \
								</td> \
								<td style='padding:0; float:right;'> \
									<p style='font-size:10px; text-align:right;'> \
										<span style='font-size:14px;'>prepared by:</span><br /> \
										<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
										<a href='mailto:<?php echo $rep->rep_email; ?>'><?php echo $rep->rep_email; ?></a> (e)<br /> \
										<?php echo $rep->rep_phone!="" ? $rep->rep_phone : $off->off_phone; ?> (p)<br /> \
										<?php echo $off->off_city; ?>,  \
										<?php echo $off->off_state; ?> \
									</p> \
								</td> \
							</tr> \
						</tbody> \
					</table> \
					<br /><br /> \
					<p><?php echo $cover_letter; ?></p> \
					<!--<div class='overview-pics'> \
						<img src='img/overview1.jpg' width='180' alt='system photo 1' /> \
						<img src='img/overview2.jpg' width='180' alt='system photo 2' /> \
						<img src='img/overview3.jpg' width='180' alt='system photo 3' /> \
					</div>-->";
			var system = "<table cellspacing='0' cellpadding='0'> \
						<tbody> \
							<tr> \
								<td style='padding:0;'> \
									<p style='font-size:10px;'> \
										<span style='font-size:14px;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
										<br /> \
										<?php echo $job_html; ?> \
									</p> \
								</td> \
								<td style='padding:0; float:right;'> \
									<p style='font-size:10px; text-align:right;'> \
										<span style='font-size:14px;'>prepared by:</span><br /> \
										<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
										<a href='mailto:<?php echo $rep->rep_email; ?>'><?php echo $rep->rep_email; ?></a> (e)<br /> \
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
					</table>";
			var performance = "<table cellspacing='0' cellpadding='0'> \
						<tbody> \
							<tr> \
								<td style='padding:0;'> \
									<p style='font-size:10px;'> \
										<span style='font-size:14px;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
										<br /> \
										<?php echo $job_html; ?> \
									</p> \
								</td> \
								<td style='padding:0; float:right;'> \
									<p style='font-size:10px; text-align:right;'> \
										<span style='font-size:14px;'>prepared by:</span><br /> \
										<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
										<a href='mailto:<?php echo $rep->rep_email; ?>'><?php echo $rep->rep_email; ?></a> (e)<br /> \
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
					<div id='visualize-area'> \
						<?php echo $draw_bill>0 ? '<br /><br />' : ''; ?> \
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
						<br /><br /> \
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
					</table>";
			var layout = "<table cellspacing='0' cellpadding='0'> \
						<tbody> \
							<tr> \
								<td style='padding:0;'> \
									<p style='font-size:10px;'> \
										<span style='font-size:14px;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
										<br /> \
										<?php echo $job_html; ?> \
									</p> \
								</td> \
								<td style='padding:0; float:right;'> \
									<p style='font-size:10px; text-align:right;'> \
										<span style='font-size:14px;'>prepared by:</span><br /> \
										<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
										<a href='mailto:<?php echo $rep->rep_email; ?>'><?php echo $rep->rep_email; ?></a> (e)<br /> \
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
						<tbody> \
							<tr> \
								<td style='padding:0;' colspan='2'> \
									<?php echo $layout_html; ?> \
								</td> \
							</tr> \
						</tbody> \
					</table>";
			var use_credit = <?php echo $use_credit; ?>;
			var financial = "<table cellspacing='0' cellpadding='0'> \
						<tbody> \
							<tr> \
								<td style='padding:0;'> \
									<p style='font-size:10px;'> \
										<span style='font-size:14px;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
										<br /> \
										<?php echo $job_html; ?> \
									</p> \
								</td> \
								<td style='padding:0; float:right;'> \
									<p style='font-size:10px; text-align:right;'> \
										<span style='font-size:14px;'>prepared by:</span><br /> \
										<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
										<a href='mailto:<?php echo $rep->rep_email; ?>'><?php echo $rep->rep_email; ?></a> (e)<br /> \
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
							<tr class='light'> \
								<td colspan='8'>&nbsp;</td> \
							</tr> \
							<tr class='light'> \
								<td colspan='8'>&nbsp;</td> \
							</tr> \
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
					<div id='visualize-line'> \
						<br /><br /> \
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
					</table>";
			var environmental = "<table cellspacing='0' cellpadding='0'> \
						<tbody> \
							<tr> \
								<td style='padding:0;'> \
									<p style='font-size:10px;'> \
										<span style='font-size:14px;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
										<br /> \
										<?php echo $job_html; ?> \
									</p> \
								</td> \
								<td style='padding:0; float:right;'> \
									<p style='font-size:10px; text-align:right;'> \
										<span style='font-size:14px;'>prepared by:</span><br /> \
										<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
										<a href='mailto:<?php echo $rep->rep_email; ?>'><?php echo $rep->rep_email; ?></a> (e)<br /> \
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
					</table>";
			var materials = "<table cellspacing='0' cellpadding='0'> \
						<tbody> \
							<tr> \
								<td style='padding:0;'> \
									<p style='font-size:10px;'> \
										<span style='font-size:14px;'>PV Proposal #<?php echo $pro->ID; ?> prepared for:</span> \
										<br /> \
										<?php echo $job_html; ?> \
									</p> \
								</td> \
								<td style='padding:0; float:right;'> \
									<p style='font-size:10px; text-align:right;'> \
										<span style='font-size:14px;'>prepared by:</span><br /> \
										<?php echo $rep->rep_name_first." ".$rep->rep_name_last; ?><br /> \
										<a href='mailto:<?php echo $rep->rep_email; ?>'><?php echo $rep->rep_email; ?></a> (e)<br /> \
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
						<caption>System Components:</caption> \
						<tbody> \
							<?php echo $materials_html; ?> \
						</tbody> \
					</table>";
			var step_one = "<table cellspacing='0' cellpadding='0'> \
								<tbody> \
									<tr> \
										<td style='padding:0;' colspan='2'> \
											<?php echo $layout_html; ?> \
										</td> \
									</tr> \
									<tr> \
										<td colspan='2'>&nbsp;</td> \
									</tr> \
									<tr> \
										<td style='padding:0;' colspan='2'> \
											<p style='width:650px;'><strong>By clicking \"I Accept\" below, you acknowledge that you have reviewed and approve the location and layout of the solar system installation as proposed in the picture above.</strong</p> \
											<br /> \
										</td> \
									</tr> \
								</tbody> \
							</table>";
			// var step_two = "<table cellspacing='0' cellpadding='0'> \
			// 								<tbody> \
			// 									<!--<tr> \
			// 										<td style='padding:0;'> \
			// 											<div id='choose-contract'> \
			// 												<input type='radio' id='radio1' name='radio' /><label for='radio1'>Buy Now Agreement</label> \
			// 												<input type='radio' id='radio2' name='radio' checked='checked' /><label for='radio2'>PPA Agreement</label> \
			// 											</div> \
			// 										</td> \
			// 									</tr> \
			// 									<tr> \
			// 										<td>&nbsp;</td> \
			// 									</tr>--> \
			// 									<tr> \
			// 										<td style='padding:0;'> \
			// 											<div id='contract-holder' style='border:none; width:630px;'></div> \
			// 										</td> \
			// 									</tr> \
			// 								</tbody> \
			// 							</table>";
			var step_two = "<table cellspacing='0' cellpadding='0'> \
								<tbody> \
									<tr> \
										<td style='padding:0;'> \
											<p style='margin:0 20px 0 0; font-size:16px; line-height:18px;'>Thank you. Your Sales Rep will contact you within 24 hours.</p> \
										</td> \
									</tr> \
								</tbody> \
							</table>";
			var whysolarnow = "<table width='100%' border='0'><tr><td><img src='img/thelighthouseway/page1.jpg' /></td><td><p><strong><span style='font-size: 112%'>Why</span></strong> solar?</p><p>We believe that a commitment to developing a variety of renewable energy sources including solar are essential to solving America’s energy problems. Solar energy can be the most cost effective source of renewable energy for powering your home or business.<br><br></p><p><strong>The Benefits of Solar</strong> are clear:<br><br></p><blockquote><blockquote><h3><span style='color: #86CB00;'>Clean.</span></h3><p>Emissions from generating electricity are toxic to our environment as well as the single largest contributor to global warming. According to NREL, the average residential solar energy system saves roughly 100 tons of CO2; the equivalent of planting 67 trees.<br><br></p><h3><span style='color: #86CB00;'>Economical.</span></h3><p>Investing in a solar system is a hedge against rising energy prices and allows you to fix the cost of your energy in today’s dollars.<br><br></p><h3><span style='color: #86CB00;'>Homegrown.</span></h3><p>Energy independence is a critical priority for our country. Solar energy lets us make the energy we need here in America while creating jobs and fueling economic growth.<br><br></p></blockquote></blockquote><p><strong><span style='font-size: 112%'>Why</span></strong> now?</p><p>Rising energy prices, global warming and dependence on oil are just a few reasons why solar energy is the right choice today.<br><br></p><p>Solar energy allows for home and business owners to make an immediate contribution to positive change. Unlike other sources of power, solar energy systems can be installed at almost any location bringing the source of energy directly to the customer.<br><br></p><p><strong>Solar Energy</strong> makes economic sense:<br></p><p>Economies of scale in product supply and improved methods of installation have contributed to lower pricing in solar energy systems. Additionally, the advent of grid-tied systems that do not rely on battery storage have also made solar more affordable. These changes, coupled with rising energy costs, are reasons why solar energy makes economic sense.</p></td></tr></table>";
	
			var whylighthousesolar = "<table width='100%' border='0'><tr><td><p><strong><span style='font-size: 112%'>One Company.</span></strong> <span style='color: #86CB00;'><strong>One Solution.</strong></span></p><p><br><br>Our mission at <strong>Lighthouse</strong>solar is to facilitate the use of clean, free and unlimited solar energy. Our full-service approach provides everything you will need to enable the power of the sun to begin working for you. All of our services, from custom design, installation, finance and real-time data monitoring are provided directly by our company and according to the highest standards of service.<br><br></p><p><strong><span style='font-size: 110%'>Full-service Provider</span></strong> with a personal touch</p><p>As a full-service provider, we make every effort to satisfy our customers. Our Technical Sales Engineers will work with you to determine your energy requirements and capabilities. They will continue managing your project until your system is fully commissioned and producing clean energy. With one point of contact, you will receive the personal service and accountability you deserve. When you choose <strong>Lighthouse</strong>solar, you can expect that your experience will reflect the <strong>Lighthouse</strong>solar way of customer satisfaction.<br><br></p><p><strong><span style='font-size: 110%'>Custom Design</span></strong> and a fixed price</p><p>When you engage <strong>Lighthouse</strong>solar to provide you with a bid on a solar energy system you can trust that your bid will be accurate. We stand behind the integrity of our estimates by offering one fixed price with no hidden costs, so you will rest easy knowing that there will be no upward price adjustments to what you were originally promised.<br><br></p><p><strong><span style='font-size: 110%'>Professional Installation</span></strong> perfected by experience</p><p>Since 2006, we have been successfully installing solar energy systems with thousands of installations to date. Our reputation for quality service has allowed us to grow throughout the country and continue to add to our base of customers. Keeping our customers happy by providing professional and courteous service is one of the reasons for our success. Our installation teams are our own company-trained employees, many of which have been with us since the company began. We understand that your home or business is not a construction zone and we will respect your schedule and leave our work area clean.<br><br></p><p><strong><span style='font-size: 110%'>Reliable</span></strong> technology</p><p>We go to great lengths to understand, compare and determine which technologies will benefit our customers most. We only utilize the most reliable products that are backed by strong manufacturer warranties.<br><br></p><p><strong><span style='font-size: 110%'>Lifetime Workmanship</span></strong> warranty</p><p>Lighthousesolar guarantees its workmanship for the lifetime of every system installed. If there is a problem with our workmanship or if your system does not perform as expected based on the estimate we provided, we will repair, replace or refund as required.</p></td><td><img src='img/thelighthouseway/page2.jpg'></td></tr></table>";
			
			var electric = "<table width='100%' border='0'><tr><td><img src='img/thelighthouseway/page3.jpg'></td></tr><tr><td><p><strong>What is a Grid-Tied Solar Electric System?</strong></p><p>The main difference between a “grid-tied” solar electric system and an “off-grid” system is a bank of batteries for energy storage.  Grid-tied systems use the utility company as an energy storage device instead of a large and heavy battery system.   When energy is produced by your solar array, it is transferred through wiring to the inverter where it is turned into energy that can be used by the electrical devices in your home.  If there are any of these electrical devices using energy while the solar system is producing, that energy will first go to those devices to be consumed.  If any extra energy is being produced that your home is not consuming, that energy will be fed into the electrical grid and measured by what is called a net-meter.  On cloudy days, at night, or any other time when additional energy is required and the solar system can’t meet the demand, additional energy is drawn from the electrical grid to supply the electrical loads in your home.  The solar electric system has a “give and take” relationship with the electrical grid that is constantly being monitored by the net-meter.<br><br></p><p><strong>What is a Net-Meter?</strong></p><p>The net-meter keeps track of how much energy your solar electric system puts into the grid vs. how much energy your home draws from the grid.  When the solar system is providing more energy than your home needs, your net-meter “spins backward,” counting how much energy you are putting into the grid.  When your home needs energy and the solar system is not producing the required amount, the energy will come from the electrical grid and your net-meter will “spin forward,” counting how much energy you are taking out of the grid.  The goal of a solar electric system is to provide a “net-zero” number on your meter, meaning your system provided exactly enough energy to power your home year round.  If the system does not provide all of the energy your home needs, you will receive a bill from your utility company for any extra energy they supplied you.  If your system provides more energy than your home needs, your utility company may provide you several options for paying or crediting you for your excess energy. A <strong>Lighthouse</strong>solar representative can advise you of your utility’s policies.<br><br></p><p><strong>What Incentives are Available?</strong></p><p><em>Utility and State Rebates</em></p><p>Additional rebates and grants may be available in your location. A <strong>Lighthouse</strong>solar representative will discuss the rebates, grants or tax credits that are available in your area.<br><br></p><p><em>Federal Tax Credit Incentive</em></p><p>The American Recovery and Reinvestment Act (ARRA) has extended and expanded the Investment Tax Credit (ITC) so that solar investors can recover up to 30 percent of eligible system costs. The Treasury Department has initiated a Federal energy grant that can, in some cases, be used in lieu of the Investment Tax Credit. Consult your tax professional or call <strong>Lighthouse</strong>solar for more information.</p></td></tr></table>";
			
			var thermal = "<table width='100%' border='0'><tr><td><img src='img/thelighthouseway/page4.jpg'></td></tr><tr><td><p><strong>What is a Solar Thermal System?</strong></p><p>Solar thermal systems harness the sun’s energy and turn it into heat, otherwise known as thermal energy. The most common forms of solar thermal systems are low and medium temperature applications used on a residential scale for pool heating, domestic hot water heating, and space heating.<br><br></p><p>The basic operation of a solar thermal system works on the principles of thermodynamics and heat transfer. Heat is collected from the sun and transferred to a different location where heat is desired. Individual systems are engineered to properly collect and transfer energy at maximum efficiency to the location where the heat is needed.<br><br></p><p>The two main types of solar thermal systems are passive and active. Passive systems are generally used in warm climates where there is no threat of freezing. These types of systems typically incorporate an integrated tank on the collector or utilize what is called “thermo-siphoning” which operates on the principle that hotter, less dense fluids tend to rise. Active systems are used in climates that have the possibility of freezing. In active systems, a pump is used to circulate a heat transfer fluid through a solar thermal collector, which transfers the heat to a storage tank located in a conditioned space where there is no threat of freezing.<br><br></p><p>Solar thermal collectors have a much higher efficiency as compared to a solar electric (PV) collector. Typical efficiencies range from around 40-80 percent depending on the type of collector and system.<br><br></p><p><strong>Can I Net-Meter a Solar Thermal System?</strong></p><p>Solar thermal systems produce hot water and not gas, there is no measurable return to the utility company and therefore no net-metering available. Because of this, the best paybacks from a solar thermal system are derived when a system is designed to offset a percentage of the total energy needed to heat water and not over-produce. The best way to measure savings is to compare previous gas bills to bills after a thermal system is installed, or install a production monitoring system.<br><br></p><p><strong>What Incentives are Available?</strong></p><p><em>Utility and State Rebates</em></p><p>Additional rebates and grants may be available in your location. A <strong>Lighthouse</strong>solar representative will discuss the rebates, grants or tax credits that are available in your area.<br><br></p><p><em>Federal Tax Credit Incentive</em></p><p>The American Recovery and Reinvestment Act (ARRA) has extended and expanded the Investment Tax Credit (ITC) so that solar investors can recover up to 30 percent of eligible system costs. The Treasury Department has initiated a Federal energy grant that can, in some cases, be used in lieu of the Investment Tax Credit. Consult your tax professional or call <strong>Lighthouse</strong>solar for more information.</p></td></tr></table>";
			
			var residential = "<table width='100%' border='0'><tr><td>“Sally and I are impressed with your professionalism, neatness and attention to detail. You can put us on your satisfied customers list.”<br><span style='color: #86CB00;'><h3>-Sally &amp; Harry Hempy</h3>Jamestown, Colorado</span><br><span style='font-size: 80%'>System Size - 9.66kW</span></td><td><img src='img/thelighthouseway/page5a.jpg'></td></tr><tr><td>“I am extremely satisfied with the system <strong>Lighthouse</strong>solar put on my home. From start to finish they were always professional, prompt, and organized.”<br><span style='color: #86CB00;'><h3>-Kim Kelley</h3>Carbondale, Colorado</span><br><span style='font-size: 80%'>System Size - 8.6kW</span></td><td><img src='img/thelighthouseway/page5b.jpg'></td></tr><tr><td>“With <strong>Lighthouse</strong>solar, I was able to install my solar system at a great price. The staff was always friendly and extremely knowledgeable about the product.”<br><span style='color: #86CB00;'><h3>-Paul Snyder</h3>Westminster, Colorado<br></span><span style='font-size: 80%'>System Size - 7.2kW</span></td><td><img src='img/thelighthouseway/page5c.jpg'></td></tr><tr><td>“I received excellent service from <strong>Lighthouse</strong>solar. They created a beautiful solar patio cover that is the talk of the neighborhood. Thanks Lighthouse!”<br><span style='color: #86CB00;'><h3>-Arnold Vento</h3>Austin, Texas</span><br><span style='font-size: 80%'>System Size - 3.04kW</span></td><td><img src='img/thelighthouseway/page5d.jpg'></td></tr><tr><td>“<strong>Lighthouse</strong>solar is a company committed to making a difference by providing alternative energy solutions at an affordable price. Our system was installed by their friendly, knowledgeable staff in three days without disrupting our household. The installation looks clean and professional.”<br><span style='color: #86CB00;'><h3>-Greg Owings</h3> Carbondale, Colorado</span><br><span style='font-size: 80%'>System Size - 5.78 kW</span></td><td><img src='img/thelighthouseway/page5e.jpg'></td></tr><tr><td>“With all of the growth in the energy industry, we had a lot of potential prospects for installers. <strong>Lighthouse</strong>solar was the clear choice. Their professionalism and reliability is unmatched. We are extremely happy with the system they put on our property.”<br><span style='color: #86CB00;'><h3>-Chief Justice James Bacon</h3>New Paltz, New York</span><br><span style='font-size: 80%'>System Size - 8.1kW Ground Mount</span></td><td><img src='img/thelighthouseway/page5f.jpg'></td></tr></table>";
			
			var commercial = "<table width='100%' border='0'><tr><td><img src='img/thelighthouseway/page6a.jpg' /></td><td>“<strong>Lighthouse</strong>solar was very easy to work with. They were always knowledgeable and professional during the entire process. I had them put a system on my house as well as the apartment building I own.”<br /><span style='color: #86CB00;'><h3>-Chuck Semple</h3>Brouham Apartments, Denver, Colorado</span><br /><span style='font-size: 80%'>System Size - 9.99kW</span></td></tr><tr><td><img src='img/thelighthouseway/page6b.jpg' /></td><td><span style='font-size: 85%'>“All of the Lighthouse representatives we came in contact with over a 6-month period were enthusiastic, professional and patient. They guided us through the maze of regulations and rebates and developed a program that fully satisfied our needs. We sincerely appreciate the relationship we have begun with <strong>Lighthouse</strong>solar and look forward to working with them in the future as they manage our installation.”</span><br /><span style='color: #86CB00; font-size: 80%'><h3>-Mike Freece</h3>Boulder Country Club, Boulder, Colorado</span><span style='font-size: 80%'><br />System Size - 99.75kW <strong>Lighthouse</strong>finance PPA</span></td></tr><tr><td><img src='img/thelighthouseway/page6c.jpg' /></td><td>“Movement Climbing + Fitness is very grateful to have the opportunity to work with <strong>Lighthouse</strong>solar in order to become carbon neutral! As the first climbing and fitness facility in the country to have a full PV System installed, we are proud to help start what we believe can be a strong and responsible move towards environmental awareness in big commercial buildings.”<br /><span style='color: #86CB00;'><h3>-Anne-Worley Moelter</h3>Movement Climbing Gym, Boulder, Colorado</span><br /><span style='font-size: 80%'>System Size - 99.9kW Lighthousefinance PPA</span></td></tr><tr><td><img src='img/thelighthouseway/page6d.jpg' /></td><td>“<strong>Lighthouse</strong>solar did an amazing job on this groundbreaking project- installing panels in just three days!”<br /><span style='color: #86CB00;'><h3>-Anna Anami</h3>904 West, Austin, Texas</span><br /><span style='font-size: 80%'>System Size - 70kW</span></td></tr><tr><td><img src='img/thelighthouseway/page6e.jpg' /></td><td>“The city of Boulder is constantly looking for ways to utilize renewable energy. We couldn’t have picked a better company to install our panels. Not only did <strong>Lighthouse</strong>solar provide great products, but their customer service was impeccable. We couldn’t be happier with our Park Central Building’s system.”<br /><span style='color: #86CB00;'><h3>-Kevin Afflerbaugh</h3>City of Boulder, Boulder, Colorado</span><br /><span style='font-size: 80%'>System Size - 9.975kW</span></td></tr><tr><td><img src='img/thelighthouseway/page6f.jpg' /></td><td>“With many solar contractors from which to choose, we went with <strong>Lighthouse</strong>solar because of the professional proposal and customer service that we received. We had a difficult problem of integrating the solar onto our roof and they worked very hard to solve all of the issues. The outcome is a terrific looking solar array that has offset our demand drastically.”<br /><span style='color: #86CB00;'><h3>-Molly Casey</h3>Sola Management Group, Austin, Texas</span><br /><span style='font-size: 80%'>System Size - 23.1kW</span></td></tr></table>";
			
			var installation = "<table width='100%' border='0'><tr><td>The following chart of events describes what you can expect from <strong>Lighthouse</strong>solar for your installation project. Please note that each project will have its own unique timeline. These estimates below give you a general idea of how our process works at <strong>Lighthouse</strong>solar.</td></tr><tr><td><p><img src='img/thelighthouseway/page7.jpg' /></p></td></tr></table>";
			
			var monitoring = "<table width='100%' border='0'><tr><td><h3><strong>Light</strong>gauge</h3><br /><strong>Light</strong>gauge is a powerful tool offered by <strong>Lighthouse</strong>solar for its customers to monitor the production of their photovoltaic systems and the energy consumption of their home or business. Customers are able to access the data either through their own web portal or without an internet connection through a local area network.<br><br /><strong>Light</strong>gauge updates every second for real-time monitoring and can store thirty years worth of data in its solid state memory. <strong>Light</strong>gauge can be configured to monitor specific individual loads, such as a hot tub, water heater, machinery, a sub-panel or other electrical load that may be of interest. Specific loads can be tracked individually to get an accurate view of the total energy usage.<br><br /><strong>Light</strong>gauge installs in minutes in most cases and requires no maintenance or subscriptions. By making the electricity consumed in your home or business visible on your computer screen, the abstract understanding of electrical usage becomes tangible and manageable.</td><td><img src='img/thelighthouseway/page8a.jpg' /></td></tr><tr><td colspan='2'><h3><strong>Real-time</strong> data monitoring:</h3></td></tr><tr><td colspan='2'><img src='img/thelighthouseway/page8b.jpg' /></td></tr></table>";
			
			//––––––––––––––––––––––––––––––––– FUNCTIONS
			// changes the proposal header
			function setProposalHead() {
				var text = "<span style='font-weight:bold;'>"+$("#data").data("proposal")+"</span> "+$("#data").data("proposal-section");
				$("#proposal-head-text").html(text);
			}
			// changes the proposal content
			function setProposalContent(s) {
				// dump the content
				$("#proposal-page").html(s);
				// visualize tables
				$("#visualize-area table, #visualize-bar table").each(function(i) {
					var type, colors, key, post, pre;
					switch(i) {
						case 0 : type = "area"; colors = ["#ff0000","#b1cd49"]; key = true; pre = ""; post = " kWh";
							if(<?php echo $draw_bill; ?>==1) $(this).visualize({ type:type,colors:colors,appendKey:key,width:"558px",height:"200px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
							break;
						case 1 : type = "bar"; colors = ["#5880c0"]; key = false; pre = "$"; post = "";
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"558px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
							break;
						case 2 : type = "bar"; colors = ["#eae854"]; key = false; pre = ""; post = "";
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"558px",yLabelPre:pre,yLabelPost:post,xTitle:"– kWh/m<span class='super'>2</span>/day over First Year –" });
							break;
					}
				});
				$("#visualize-line table").each(function(i) {
					var type, colors;
					switch(i) {
						case 0 : type = "line"; break;
						case 1 : type = "bar"; break;
					}
					$(this).visualize({ type:type,colors:["#ff0000","#b1cd49"],width:"558px",height:"200px",yLabelPre:"$",xTitle:"– System Term (years) –" });
				});
			}
			// changes the binder header
			function setBinderHead() {
				var text = "<span style='font-weight:bold;'>Be part of the solution.</span> "+$("#data").data("binder-section");
				$("#binder-head-text").html(text);
			}
			// changes the binder content
			function setBinderContent(s) {
				// dump the content
				$("#binder-page").html(s);
			}
			// sets up the dialog box on open
			function initCheckout() {
				controlButton(false,0);
				controlButton(true,1);
				controlButton(false,2);
				controlButton(true,3);
				controlButton(true,4);
				$("#check-out-step").html("<h3 class='check-out-head'>STEP 1 OF 2: Please review your Solar System placement.</h3>");
				$("#check-out-window").html(step_one);
			}
			// hides or shows a dialog button
			function controlButton(h,n) {
				$(".ui-dialog .ui-dialog-buttonpane button").each(function(i) {
					if(i==n) (h) ? $(this).hide() : $(this).show();
				});
			}
			// approve
			function approve() {
				$.ajax({
		  			type:"POST",
		  			url:"<?php echo $EINSTEIN_LOC; ?>includes/es-submit.php",
		  			data:"pro_key=<?php echo $pro_key; ?>&es_do=approveProposal",
		  			success:function() {  },
					error:function(e) { console.log(e['responseText']); }
		 		});
			}
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
			//––––––––––––––––––––––––––––––––– START
			// init the proposal
			$("#data").data("proposal","<?php echo $job_summary ?>");
			$("#data").data("proposal-section",$("#proposal-t-of-c > li:first").text());
			setProposalHead();
			setProposalContent(overview);
			// init the binder
			$("#data").data("binder-section",$("#binder-t-of-c > li:first").text());
			setBinderHead();
			setBinderContent(whysolarnow);
			// check status
			var approved = <?php echo $pro->pro_approved; ?>;
			if(approved) {
				var approved_date = $.tsToDate("<?php echo $pro->pro_approved_date; ?>");
				$("#check-out").hide();
				$("#approved").text("Approved "+approved_date).show();
			}
		});
	</script>
	<style type="text/css">
		.ui-widget-content { }
		.ui-widget-overlay {
			background:url("gfx/modal.png") repeat scroll 50% 50% #666666;
			opacity:0.5;
		}
		.ui-tabs { background:none; border:none; }
		.ui-tabs .ui-tabs-nav { background:none; border:none; padding:1em 1em 0; }
		.ui-tabs .ui-tabs-nav li { box-shadow:0 0 10px #ccc; -moz-box-shadow:0 0 10px #ccc; -khtml-box-shadow:0 0 10px #ccc; -webkit-box-shadow:0 0 10px #ccc; }
		.ui-tabs .ui-tabs-panel { 
			background:white; border:1px solid #ccc; border-radius:10px; -moz-border-radius:10px; -khtml-border-radius:10px; -webkit-border-radius:10px; height:500px; min-height:500px; padding:20px; 
			box-shadow:0 0 10px #ccc; -moz-box-shadow:0 0 10px #ccc; -khtml-box-shadow:0 0 10px #ccc; -webkit-box-shadow:0 0 10px #ccc;
		}
		.ui-dialog { box-shadow:0 0 10px #ccc; -moz-box-shadow:0 0 10px #ccc; -khtml-box-shadow:0 0 10px #ccc; -webkit-box-shadow:0 0 10px #ccc; }
		.ui-selectmenu-custom { background:#f4f4f4; }
		.ui-selectmenu-menu li a, .ui-selectmenu-status { font-weight:bold; }
		.ui-state-default-blue {
			background:url("css/custom-theme/images/ui-bg_highlight-soft_100_5980bf_1x100.png") repeat-x scroll 50% 50% #5980bf;
			border:1px solid #5980bf;
			font-weight:normal;
		}
		.ui-state-default-blue:hover {
			background:url("css/custom-theme/images/ui-bg_inset-soft_100_9fb1da_1x100.png") repeat-x scroll 50% 50% #9fb1da;
			border:1px solid #9fb1da;
		}
		#check-out .ui-icon {
			background-image:url("css/custom-theme/images/ui-icons_ffffff_256x240.png");
		}
		.ui-dialog { height:520px; padding:0; }
		.ui-dialog .ui-dialog-buttonpane { margin:0; background:none; }
		.ui-dialog .ui-dialog-buttonpane button { padding:0.2em 0.6em; }
		.ui-dialog .ui-dialog-title { width:100%; font-size:18px; font-weight:normal; color:#b1cd49; }
		.ui-dialog .ui-dialog-titlebar { padding:0.5em 1em; }
		.ui-dialog .ui-dialog-titlebar-close { margin:-42px 3px 0; }
		.ui-dialog .ui-dialog-content { padding:0 0 0 30px; }
		.ui-dialog .ui-buttonset { color:#555; font-size:12px; }
	</style>
</head>
<body>
	<div id="data"></div>
	<div id="topper" class="gradient 5880c0 ffffff vertical">
		<div id="header">
			<div id="header-left">
				<a href="<?php echo $LHS_LOC; ?>" target="_blank"><img src="gfx/logo-black.png" width="269" height="51" alt="lighthouse solar logo" /></a>
			</div>
			<div id="header-right">
				<a href="<?php echo $LHS_LOC; ?>" target="_blank"><img src="gfx/truck.png" width="175" height="56" alt="truck mascot" /></a>
			</div>
			<div id="header-mid">
				<h4>Be part of the solution<span class="super-small">&trade;</span></h4>
			</div>
			<div class="clear"></div>
		</div>
		<div class="green-line"></div>
	</div>
	<div class="clear"></div>
	<div id="wrap">
		<div id="menu">
			<ul>
				<li><a href="#tabs-1">The <strong>Lighthouse</strong>solar Way</a></li>
				<!--<li style="float:right;"><a href="#tabs-2">My Rep</a></li>
				<li style="float:right; opacity:.5;"><a href="#tabs-3">My <strong>Light</strong>gauge</a></li>
				<li style="float:right; opacity:.5;"><a href="#tabs-4">My Gallery</a></li>-->
				<li style="float:right;"><a href="#tabs-2">My Proposal</a></li>
			</ul>
			<div id="tabs-1">
				<div id="binder-left">
					<div id="binder-menu">
						<h1 class="list">Table Of Contents:</h1>
						<div id="binder-menu-over"><img src="gfx/glass.png" width="225" height="30" alt="glass over"></div>
						<ul id="binder-t-of-c" class="list-container">
							<li id="i0" title="whysolarnow" class="binder-list-item"><strong>Why Solar</strong> now?</li>
							<li id="i1" title="whylighthousesolar" class="binder-list-item"><strong>Why Lighthouse</strong>solar?</li>
							<li id="i2" title="electric" class="binder-list-item"><strong>Solar</strong> 101: Solar Electric</li>
							<li id="i3" title="thermal" class="binder-list-item"><strong>Solar</strong> 101: Solar Thermal</li>
                            <li id="i4" title="residential" class="binder-list-item"><strong>Residential</strong> Portfolio</li>
                            <li id="i5" title="commercial" class="binder-list-item"><strong>Commercial</strong> Portfolio</li>
                            <li id="i6" title="installation" class="binder-list-item"><strong>The Installation</strong> Process</li>
                            <li id="i7" title="monitoring" class="binder-list-item"><strong>Data</strong> Monitoring</li>
						</ul>
						<span class="printer"><br />Download PDF version <a href="<?php echo $BINDER_LOC; ?>" target="_blank">here</a>.</span>
					</div>
				</div>
				<div id="binder-right">
					<div id="binder-content">
						<div id="binder-head">
							<img src="gfx/tiny-logo.png" width="84" height="16" alt="small logo" />
							<h1 id="binder-head-text" class="page-head"></h1>
						</div>
						<div id="binder-page" class="page"></div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
			<!--<div id="tabs-2"><span style='color:#555;'>coming soon...</span></div>
			<div id="tabs-3"><span style='color:#555;'>coming soon...</span></div>
			<div id="tabs-4"><span style='color:#555;'>coming soon...</span></div>-->
			<div id="tabs-2">
				<div id="proposal-left">
					<div id="persona">
						<!-- <div id="persona-left">
								<img src="img/bruce_wayne-avitar.jpg" width="40" height="40" alt="your avitar" />
						</div> -->
						<div id="persona-right">
							<h2 class="persona-head"><?php echo $customer_name; ?></h2>
							<h2 class="persona-sub"><?php echo $customer_contact; ?></h2>
						</div>
					</div>
					<div id="proposal-menu">
						<h1 class="select">My Solar System Proposal</h1>
						<span name="choose-proposal" id="choose-proposal">
							<?php echo $job_summary_short; ?>
						</span>
						<br /><br /><br />
						<h1 class="list">Table Of Contents:</h1>
						<div id="proposal-menu-over"><img src="gfx/glass.png" width="225" height="30" alt="glass over"></div>
						<ul id="proposal-t-of-c" class="list-container">
							<li id="i0" title="overview" class="list-item">Overview</li>
							<li id="i1" title="system" class="list-item">System Details</li>
							<li id="i2" title="performance" class="list-item">System Performance</li>
							<li id="i3" title="layout" class="list-item">System Layout</li>
							<li id="i4" title="financial" class="list-item">Financial Details</li>
							<li id="i5" title="environmental" class="list-item">Environmental Details</li>
							<li id="i6" title="materials" class="list-item">Materials</li>
						</ul>
						<div id="proposal-pending"><p>Your Sales Rep will contact you within 24 hours.</p></div>
						<div id="approved"></div>
						<a href="#" id="check-out" class="ui-state-default-blue ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>&nbsp;Ready to begin?</a>
						<span class="printer"><br />View printable version <a href="print.php?pro_key=<?php echo $pro_key; ?>" target="_blank">here</a>.</span>
						<div id="check-out-window" title="<strong>Awesome!</strong><br />You're only a few steps away from securing your <strong>Lighthouse</strong>solar System.<div id='check-out-step'></div>"></div>
					</div>
				</div>
				<div id="proposal-right">
					<div id="proposal-content">
						<div id="proposal-head">
							<img src="gfx/tiny-logo.png" width="84" height="16" alt="small logo" />
							<h1 id="proposal-head-text" class="page-head"></h1>
						</div>
						<div id="proposal-page" class="page"></div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
	<div id="news">
		<ul id="ticker">
			<!--<li><span>1/12/2010</span>Save the Planet. No Money Down. Check out our <a href="http://solarsavernow.com" target="_blank">SolarSaver</a> financing program at <a href="http://solarsavernow.com" target="_blank">www.SolarSaverNow.com</a>.</li>
			<li><span>2/3/2010</span>Watch out! Xcel rates rose over 10% in 2009. Lock into your solar system today and avoid utility rate increases. <a href="http://www.denverpost.com/business/ci_13931077?source=bb">Read more...</a></li>
			<li><span>3/25/2010</span>We know the state of Colorado. Since 2006, <a href="<php echo $LHS_LOC; ?>" target="_blank">Lighthousesolar</a> has been providing home grown solar power to locals like you.</li>-->
		</ul>
	</div>
	<div class="clear"></div>
	<div id="bottomer" class="gradient 5880c0 ffffff vertical">
		<div class="green-line"></div>
		<div id="footer">
			<div class="right">
				<a href="<?php echo $LHS_LOC; ?>" target="_blank"><img src="gfx/logo-black.png" width="124" height="24" alt="lighthouse solar logo" /></a>
			</div>
			<div style="margin:0 20% 0 0;">
				<h6><a href="<?php echo $LHS_LOC; ?>" target="_blank"><?php echo $LHS_LOC; ?></a> | <?php echo $off->off_address." ".$off->off_city.", ".$off->off_state." ".$off->off_zip; ?> | <?php echo $off->off_phone; ?> | <a href="mailto:<?php echo $off->off_email; ?>" target="_self"><?php echo $off->off_email; ?></a> | Copyright © Lighthouse Solar 2010-Today.</h6>
			</div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
	</div>
</body>
</html>
<?php
	}
}
?>