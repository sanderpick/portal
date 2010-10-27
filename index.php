<?php
#——————————————————————————————–—————————————————————–––––––––– HOST
$host = $_SERVER["SERVER_NAME"];
preg_match('/\.([a-z,A-Z]{2,6})$/',$host,$tld);
switch($tld[1]) {
	case "ld" : // local 
		$EINSTEIN_LOC = "../estimator/";
		$EINSTEIN_URI = $EINSTEIN_LOC;
		break;
	default :
		if($host=='beta.mylighthousesolar.com') $EINSTEIN_LOC = "../einstein-beta.cleanenergysolutionsinc.com/";
		else $EINSTEIN_LOC = "../einstein.cleanenergysolutionsinc.com/";
		$EINSTEIN_URI = "http://einstein.cleanenergysolutionsinc.com/";
		break;
}
$LHS_LOC = "http://lighthousesolar.com";
$BINDER_LOC = "http://tools.lighthousesolar.com/files/pdf/Lighthousesolar_Sales_Web.pdf";
#——————————————————————————————–—————————————————————– BEGIN SESSION
require_once($EINSTEIN_LOC."includes/es-manager.class.php");
$m = new EstimatorManager();
// for validation
$is_pro = 0;
// ge the pro key
$pro_key = isset($_GET['pro_key']) ? $_GET['pro_key'] : FALSE;
// get the pro
if($pro_key) {
	if($m->getRow("es_proposals",$pro_key,"pro_key")) { 
		$is_pro = 1;
		$pro = $m->lastData();
		require("includes/portal.php");
	} else exit("Sorry, your proposal key is invalid or expired.");
}
#——————————————————————————————–—————————————————————–––––––––– HTML
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>My Solar Portal - Lighthouse Solar<?php if($is_pro) echo " / ".$off->off_city.", ".$off->off_state; ?></title>
	<!--[if IE]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	<link href="css/custom-theme/jquery-ui-1.8.custom.css" rel="stylesheet" type="text/css" />
	<link href="css/visualize/visualize.css" rel="stylesheet" type="text/css" />
	<link href="css/visualize/visualize-skin.css" rel="stylesheet" type="text/css" />
	<link href="css/style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<meta name="keywords" content="lighthousesolar, lighthouse, solar, lumos"/>
	<meta name="description" content="My Lighthouse Solar. The portal into your new solar energy savings." />
	<!--[if lt IE 9]>
		<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js">IE7_PNG_SUFFIX=".png";</script>
	<![endif]-->
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-1.8.custom.min.js"></script>
	<script type="text/javascript" src="js/EnhanceJS/enhance.js"></script>
	<script type="text/javascript" src="js/excanvas.js"></script>
	<script type="text/javascript" src="js/visualize.jQuery.js"></script>
	<script type="text/javascript" src="js/date.format.js"></script>
	<script type="text/javascript">
		$(function() {
			//———————————————————————————————————————————————————————————————— UTILS
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
			//—————————————————————————————————————————————————————————— SYSTEM INFO
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
			// –––––––––––––––––––––––––––––––––———————————————————————— UI ELEMENTS
			// determine if this is a proposal or not
			var is_pro = <?php echo $is_pro; ?>;
			// determine if this is a published proposal
			var is_published = <?php if($is_pro) echo $pro->pro_published; else echo 0; ?>;
			// determine if this is an approved proposal
			var is_approved = <?php if($is_pro) echo $pro->pro_approved; else echo 0; ?>;
			var approved_timestamp = <?php if($is_pro) echo "'".$pro->pro_approved_date."'"; else echo 0; ?>;
			// determine whether or to draw bill comparison
			var draw_bill = <?php if($is_pro) echo $draw_bill; else echo 0; ?>;
			///////////////////////////////////// PROPOSAL
			if(is_pro) {
				// visualize tables
				$(".vis table").each(function(i) {
					var type, colors, key, post, pre;
					switch(i) { // ["#9faed3","#5880c0","#c9d1e6"]
						case 0 : type = "pie"; colors = ["#5880C0","#C9D2E7","#b1cd49"]; key = true;
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"160px",height:"160px" });
							break;
						case 1 : type = "pie"; colors = ["#5880C0","#ccde89","#b1cd49"]; key = true;
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"160px",height:"160px" });
							break;
						case 2 : type = "pie"; colors = ["#5880C0","#b1cd49"]; key = true;
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"130px",height:"130px" });
							break;
						case 3 : type = "area"; colors = ["#5880C0","#b1cd49"]; key = true; pre = ""; post = " kWh";
							if(draw_bill) $(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",height:"200px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
							break;
						case 4 : type = "bar"; colors = ["#b1cd49"]; key = false; pre = "$"; post = "";
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
							break;
						case 5 : type = "bar"; colors = ["#eae854"]; key = false; pre = ""; post = "";
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",yLabelPre:pre,yLabelPost:post,xTitle:"– kWh/m<span class='super'>2</span>/day over First Year –" });
							break;
						case 6 : type = "bar"; colors = ["#5880C0","#b1cd49"]; key = true; pre = "$"; post = "";
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",height:"200px",yLabelPre:pre,yLabelPost:post,xTitle:"– Year –" });
							break;
						case 7 : type = "bar"; colors = ["#5880C0","#b1cd49"]; key = true; pre = "$"; post = "";
							$(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",height:"200px",yLabelPre:pre,yLabelPost:post,xTitle:"– Year –",altColor:true });
							break;
					}
				});
				// hide all sections
				$(".prop-section").hide();
				// get the job summary
				var job_name = <?php if($is_pro) echo "'".$job_summary."'"; else echo 0; ?>;
				// set the proposal
				$("#menu").tabs({ selected:1 });
				// setup proposal slider
				var proposal_menu_top = $("#proposal-t-of-c").offset().top;
				var proposal_menu_left = $("#proposal-t-of-c").offset().left;
				var wrap_top = $("#wrap").offset().top;
				$("#proposal-menu-over").css({ top:proposal_menu_top-wrap_top });
				// set initial proposal content
				$("#prop-overview").fadeIn("slow");
				// set inital proposal head
				//$("#proposal-head-text").html("<span style='font-weight:bold;'>"+job_name+"</span> Overview");
				$("#proposal-head-text").html("Overview");
				// navigation
				$(".list-item").click(function() {
					// animate
					var y = this.offsetTop;
					$("#proposal-menu-over").animate({ top:y },"fast");
					// set content
					$(".prop-section").hide();
					$("#"+this.title).fadeIn("slow");
					// set head
					//$("#proposal-head-text").html("<span style='font-weight:bold;'>"+job_name+"</span> "+$(this).text());
					$("#proposal-head-text").html($(this).text());
					// hide header if not overview
					if(this.title!="prop-overview") $("#proposal-page-head").hide();
					else $("#proposal-page-head").show();
				});
				// set up checkout
				if(is_approved) {
					var approved_date = $.tsToDate(approved_timestamp);
					$("#check-out").hide();
					$("#approved").text("Approved "+approved_date).show();
				} else {
					// dialog box
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
						
								if(is_published==1) approve();
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
								if(is_published) {
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
					// button hover
					$("#check-out, ul#icons li").hover(
						function() { $(this).addClass("ui-state-hover"); }, 
						function() { $(this).removeClass("ui-state-hover"); }
					);
				}
			} else {
			///////////////////////////////////// BINDER
				// set sales binder
				$("#menu").tabs({ selected:0 });
				// hide proposal tab
				$("a[href='#tabs-2']").hide();
			}
			// binder slider
			binderSliderInit();
			// set initial binder content
			$("#bind-whysolarnow").fadeIn("slow");
			// set initial binder header
			$("#binder-head-text").html("Why Solar Now?");
			// navigation
			$(".binder-list-item").click(function() {
				// animate
				var y = this.offsetTop;
				$("#binder-menu-over").animate({ top:y },"fast");
				// set content
				$(".bind-section").hide();
				$("#"+this.title).fadeIn("slow");
				// set head
				$("#binder-head-text").html($(this).text());
			});
			// setup binder slider
			$("a[href='#tabs-1']").click(binderSliderInit);
			function binderSliderInit() {
				var binder_menu_top = $("#binder-t-of-c").offset().top;
				var binder_menu_left = $("#binder-t-of-c").offset().left;
				var wrap_top = $("#wrap").offset().top;
				$("#binder-menu-over").css({ top:binder_menu_top-wrap_top });
				$("a[href='#tabs-1']").unbind("click",binderSliderInit);
			}
			///////////////////////////////////// MISC
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
		  			data:"pro_key=<?php if($is_pro) echo $pro_key; ?>&es_do=approveProposal",
		  			success:function() {  },
					error:function(e) { console.log(e['responseText']); }
		 		});
			}
			var step_one = "<table cellspacing='0' cellpadding='0'> \
								<tbody> \
									<tr> \
										<td style='padding:0;' colspan='2'> \
											<?php if($is_pro) echo $layout_html; ?> \
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
		});
	</script>
</head>
<body>
	<div id="data"></div>
	<!-- <div id="topper" class="gradient 5880c0 ffffff vertical"> -->
	<div id="topper">
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
				<div id="binder-menu-over"><img src="gfx/glass.png" width="225" height="30" alt="glass over"></div>
				<div id="binder-left">
					<div id="binder-menu">
						<h1 class="list">Table Of Contents:</h1>
						<ul id="binder-t-of-c" class="list-container">
							<li id="i0" title="bind-whysolarnow" class="binder-list-item"><strong>Why Solar</strong> now?</li>
							<li id="i1" title="bind-whylighthousesolar" class="binder-list-item"><strong>Why Lighthouse</strong>solar?</li>
							<li id="i2" title="bind-electric" class="binder-list-item"><strong>Solar</strong> 101: Solar Electric</li>
							<li id="i3" title="bind-thermal" class="binder-list-item"><strong>Solar</strong> 101: Solar Thermal</li>
                            <li id="i4" title="bind-residential" class="binder-list-item"><strong>Residential</strong> Portfolio</li>
                            <li id="i5" title="bind-commercial" class="binder-list-item"><strong>Commercial</strong> Portfolio</li>
                            <li id="i6" title="bind-installation" class="binder-list-item"><strong>The Installation</strong> Process</li>
                            <li id="i7" title="bind-monitoring" class="binder-list-item"><strong>Data</strong> Monitoring</li>
						</ul>
						<span class="printer"><br />Download PDF version <a href="<?php echo $BINDER_LOC; ?>" target="_blank">here</a>.</span>
					</div>
				</div>
				<div id="binder-right">
					<div id="binder-content">
						<div id="binder-head">
							<!-- <img src="gfx/tiny-logo.png" width="84" height="16" alt="small logo" /> -->
							<h1 id="binder-head-text" class="page-head"></h1>
						</div>
						<div id="binder-page" class="page">
							<?php
								require("includes/whysolarnow.binder.php");
								require("includes/whylighthousesolar.binder.php");
								require("includes/electric.binder.php");
								require("includes/thermal.binder.php");
								require("includes/residential.binder.php");
								require("includes/commercial.binder.php");
								require("includes/installation.binder.php");
								require("includes/monitoring.binder.php");
							?>
						</div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
			<!--<div id="tabs-2"><span style='color:#555;'>coming soon...</span></div>
			<div id="tabs-3"><span style='color:#555;'>coming soon...</span></div>
			<div id="tabs-4"><span style='color:#555;'>coming soon...</span></div>-->
			<div id="tabs-2">
				<div id="proposal-menu-over"><img src="gfx/glass.png" width="225" height="30" alt="glass over"></div>
				<div id="proposal-left">
					<div id="persona">
						<h2 class="persona-head"><?php if($is_pro) echo $customer_name; ?></h2>
						<h2 class="persona-sub"><?php if($is_pro) echo $customer_contact; ?></h2>
					</div>
					<div id="proposal-menu">
						<h1 class="select">My Solar System Proposal</h1>
						<span name="choose-proposal" id="choose-proposal">
							<?php if($is_pro) echo $job_summary_short; ?>
						</span>
						<br /><br /><br />
						<h1 class="list">Table Of Contents:</h1>
						<ul id="proposal-t-of-c" class="list-container">
							<li id="i0" title="prop-overview" class="list-item">Overview</li>
							<li id="i1" title="prop-system" class="list-item">System Details</li>
							<li id="i2" title="prop-performance" class="list-item">System Performance</li>
							<li id="i3" title="prop-layout" class="list-item">System Layout</li>
							<li id="i4" title="prop-financials" class="list-item">Financial Details</li>
							<li id="i5" title="prop-environmental" class="list-item">Environmental Details</li>
							<li id="i6" title="prop-materials" class="list-item">Materials</li>
						</ul>
						<div id="proposal-pending"><p>Your Sales Rep will contact you within 24 hours.</p></div>
						<div id="approved"></div>
						<a href="javascript:;" id="check-out" class="ui-state-default-blue ui-corner-all"><span class="ui-icon ui-icon-newwin"></span>&nbsp;Ready to begin?</a>
						<span class="printer"><br />View printable version <a href="print.php?pro_key=<?php if($is_pro) echo $pro_key; ?>" target="_blank">here</a>.</span>
						<div id="check-out-window" title="<strong>Awesome!</strong><br />You're only a few steps away from securing your <strong>Lighthouse</strong>solar System.<div id='check-out-step'></div>"></div>
					</div>
				</div>
				<div id="proposal-right">
					<div id="proposal-content">
						<div id="proposal-head">
							<h1 class="page-head" style="float:right;"><?php if($is_pro) echo "LHS Pr. #".$pro->ID; ?></h1>
							<!-- <img src="gfx/tiny-logo.png" width="84" height="16" alt="small logo" /> -->
							<h1 id="proposal-head-text" class="page-head"></h1>
						</div>
						<div id="proposal-page" class="page">
							<div id="proposal-page-head">
								<?php if($is_pro) require("includes/header.proposal.php"); ?>
							</div>
							<div id="proposal-page-content">
								<?php if($is_pro) {
										require("includes/overview.proposal.php");
										require("includes/system.proposal.php");
										require("includes/performance.proposal.php");
										require("includes/layout.proposal.php");
										require("includes/financials.proposal.php");
										require("includes/environmental.proposal.php");
										require("includes/materials.proposal.php");
									}
								?>
								<br /><br />
							</div>
						</div>
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
	<div id="bottomer">
		<div class="green-line"></div>
		<div id="footer">
			<div class="right">
				<a href="<?php echo $LHS_LOC; ?>" target="_blank"><img src="gfx/logo-black.png" width="124" height="24" alt="lighthouse solar logo" /></a>
			</div>
			<div style="margin:0 20% 0 0;">
				<h6>
					<a href="<?php echo $LHS_LOC; ?>" target="_blank"><?php echo $LHS_LOC; ?></a>
					<?php if($is_pro) echo " | ".$off->off_address." ".$off->off_city.", ".$off->off_state." ".$off->off_zip; ?>
					<?php if($is_pro) echo " | ".$off->off_phone; ?>
					<?php if($is_pro) { ?> | <a href="mailto:<?php echo $off->off_email; ?>" target="_self"><?php echo $off->off_email; ?></a><?php } ?> 
					| Copyright © Lighthouse Solar 2010-Today.
				</h6>
			</div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>
	</div>
</body>
</html>