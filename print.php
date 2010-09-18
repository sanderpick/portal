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
		if($host=='einstein-beta.cleanenergysolutionsinc.com') $EINSTEIN_LOC = "../einstein-beta.cleanenergysolutionsinc.com/";
		else $EINSTEIN_LOC = "../einstein.cleanenergysolutionsinc.com/";
		$EINSTEIN_URI = "http://einstein.cleanenergysolutionsinc.com/";
		break;
}
$LHS_LOC = "http://lighthousesolar.com";
#——————————————————————————————–—————————————————————– BEGIN SESSION
require_once($EINSTEIN_LOC."includes/es-manager.class.php");
$m = new EstimatorManager();
$e = FALSE;
// ge the pro key
$pro_key = (isset($_GET['pro_key'])) ? $_GET['pro_key'] : FALSE;
if(!$pro_key) exit("Sorry, you must have a valid key to view a proposal.");
else {
	// get the pro
	if(!$m->getRow("es_proposals",$pro_key,"pro_key")) exit("Sorry, your proposal key is invalid or expired.");
	else {
		$pro = $m->lastData();
		require("includes/portal.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php echo str_replace(" ","_",$job_title)."-LHS-".$f->size."kW-PV_Proposal_#".$pro->ID; ?></title>
	<!--[if IE]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	<link href="css/visualize/visualize.css" rel="stylesheet" type="text/css" />
	<link href="css/visualize/visualize-skin.css" rel="stylesheet" type="text/css" />
	<link href="css/print-style.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="css/print.css" rel="stylesheet" type="text/css" media="print" />
	<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
	<meta name="keywords" content="lighthousesolar, lighthouse, solar, lumos"/>
	<meta name="description" content="My Lighthouse Solar. The portal into your new solar energy savings." />
	<!--[if lt IE 9]>
		<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js">IE7_PNG_SUFFIX=".png";</script>
	<![endif]-->
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
	<script type="text/javascript" src="js/EnhanceJS/enhance.js"></script>
	<script type="text/javascript" src="js/excanvas.js"></script>
	<script type="text/javascript" src="js/visualize.jQuery.js"></script>
	<script type="text/javascript">
		$(function() {
			// move graphs
			$(".system-graphs").appendTo("#system-graphs-holder");
			$(".performance-graphs").appendTo("#performance-graphs-holder");
			$(".financials-graphs").appendTo("#financials-graphs-holder");
			// move tables
			$("#financials-table").appendTo("#financials-table-holder");
			// remove breaks
			$("#financials-graphs-holder br").remove();
			$("#financials-table-holder br").remove();
			// determine whether or to draw bill comparison
			var draw_bill = <?php echo $draw_bill; ?>;
			// visualize tables
			$(".vis table").each(function(i) {
				var type, colors, key, post, pre;
				switch(i) {
					case 0 : type = "pie"; colors = ["#333333","#808080","#b1cd49"]; key = true;
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"200px",height:"200px" });
						break;
					case 1 : type = "pie"; colors = ["#333333","#ccde89","#b1cd49"]; key = true;
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"200px",height:"200px" });
						break;
					case 2 : type = "pie"; colors = ["#333333","#b1cd49"]; key = true;
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"130px",height:"130px" });
						break;
					case 3 : type = "area"; colors = ["#333333","#b1cd49"]; key = true; pre = ""; post = " kWh";
						if(draw_bill) $(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",height:"100px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
						break;
					case 4 : type = "bar"; colors = ["#b1cd49"]; key = false; pre = "$"; post = "";
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",height:"60px",yLabelPre:pre,yLabelPost:post,xTitle:"– First Year –" });
						break;
					case 5 : type = "bar"; colors = ["#eae854"]; key = false; pre = ""; post = "";
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",height:"60px",yLabelPre:pre,yLabelPost:post,xTitle:"– kWh/m<span class='super'>2</span>/day over First Year –" });
						break;
					case 6 : type = "bar"; colors = ["#333333","#b1cd49"]; key = true; pre = "$"; post = "";
						$(this).visualize({ type:type,colors:colors,appendKey:key,width:"548px",height:"160px",yLabelPre:pre,yLabelPost:post,xTitle:"– Year –" });
						break;
				}
			});
		});			
	</script>
</head>
<body>
	<div id="wrap">	
		<div id="proposal-content">
			<?php $current_section = "Overview"; ?>
			<?php require("includes/header.print.php"); ?>
				<?php require("includes/overview.proposal.php"); ?>
			</div>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php $current_section = "System Details"; ?>
			<?php require("includes/header.print.php"); ?>
				<?php require("includes/system.proposal.php"); ?>
			</div>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php require("includes/header.print.php"); ?>
				<div id="system-graphs-holder"></div>
			</div>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php $current_section = "System Performance"; ?>
			<?php require("includes/header.print.php"); ?>
				<?php require("includes/performance.proposal.php"); ?>
			</div>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php require("includes/header.print.php"); ?>
				<div id="performance-graphs-holder"></div>
			</div>
			<?php echo $print_layout_html; ?>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php $current_section = "Financial Details"; ?>
			<?php require("includes/header.print.php"); ?>
				<?php require("includes/financials.proposal.php"); ?>
			</div>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php require("includes/header.print.php"); ?>
				<div id="financials-table-holder"></div>
			</div>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php require("includes/header.print.php"); ?>
				<div id="financials-graphs-holder"></div>
			</div>
			<div style="page-break-before:always;" class="fake-break"></div>
			<?php $current_section = "Environmental Details"; ?>
			<?php require("includes/header.print.php"); ?>
				<?php require("includes/environmental.proposal.php"); ?>
			</div>
			<?php echo $print_materials_html; ?>
		</div>
	</div>
</body>
</html>
<?php
	}
}
?>