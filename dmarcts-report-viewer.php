<?php

// dmarcts-report-viewer - A PHP based viewer of parsed DMARC reports.
// Copyright (C) 2016 TechSneeze.com and John Bieling
// with additional extensions (sort order) of Klaus Tachtler.
//
// Available at:
// https://github.com/techsneeze/dmarcts-report-viewer
//
// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
// more details.
//
// You should have received a copy of the GNU General Public License along with
// this program.  If not, see <http://www.gnu.org/licenses/>.
//
//####################################################################
//### configuration ##################################################
//####################################################################

// Copy dmarcts-report-viewer-config.php.sample to
// dmarcts-report-viewer-config.php and edit with the appropriate info
// for your database authentication and location.

//####################################################################
//### functions ######################################################
//####################################################################

function get_status_color($row) {

	$status = "";
	if (($row['item_dkim_result'] == "fail") && ($row['item_spf_result'] == "fail")) {
		$status="red";
	} elseif (($row['item_dkim_result'] == "fail") || ($row['item_spf_result'] == "fail")) {
		$status="orange";
	} elseif (($row['item_dkim_result'] == "pass") && ($row['item_spf_result'] == "pass")) {
		$status="lime";
	} else {
		$status="yellow";
	}
	return $status;
}

function format_date($date, $format) {
	$answer = date($format, strtotime($date));
	return $answer;
};

function tmpl_reportList($allowed_reports, $host_lookup = 1, $sort_order, $dom_select = '', $org_select = '', $per_select = '', $reportid) {

	$reportlist[] = "";
	$reportlist[] = "<!-- Start of report list -->";

	$reportlist[] = "<h1 class='main'>DMARC Reports" . ($dom_select == '' ? '' : " for " . htmlentities($dom_select)) . "</h1>";
	$reportlist[] = "<table class='reportlist'>";
	$reportlist[] = "  <thead>";
	$reportlist[] = "    <tr>";
	$reportlist[] = "      <th></th>";
	$reportlist[] = "      <th>Start Date</th>";
	$reportlist[] = "      <th>End Date</th>";
	$reportlist[] = "      <th>Domain</th>";
	$reportlist[] = "      <th>Reporting Organization</th>";
	$reportlist[] = "      <th>Report ID</th>";
	$reportlist[] = "      <th>Messages</th>";
	$reportlist[] = "    </tr>";
	$reportlist[] = "  </thead>";

	$reportlist[] = "  <tbody>";
	$reportsum    = 0;

	foreach ($allowed_reports[BySerial] as $row) {
		$row = array_map('htmlspecialchars', $row);
		$date_output_format = "r";
		$reportlist[] =  "    <tr" . ( $reportid == $row['report_id'] ? " class='selected' " : "" ) . ">";
		$reportlist[] =  "      <td class='right'><span class=\"circle_".get_status_color($row)."\"></span></td>";
		$reportlist[] =  "      <td class='right'>". format_date($row['report_begin_date'], $date_output_format). "</td>";
		$reportlist[] =  "      <td class='right'>". format_date($row['report_end_date'], $date_output_format). "</td>";
		$reportlist[] =  "      <td class='center'>". $row['report_domain']. "</td>";
		$reportlist[] =  "      <td class='center'>". $row['report_org_name']. "</td>";
		$reportlist[] =  "      <td class='center'><a href='?report=" . $row['report_id']
			. ( $host_lookup ? "&hostlookup=1" : "&hostlookup=0" )
			. ( $sort_order ? "&sortorder=1" : "&sortorder=0" )
			. ($dom_select == '' ? '' : "&d=" . urlencode($dom_select))
			. ($org_select == '' ? '' : "&o=" . urlencode($org_select))
			. ($per_select == '' ? "&p=all" : "&p=" . urlencode($per_select))
			. "#rpt". $row['report_id'] . "'>". $row['report_id']. "</a></td>";
		$reportlist[] =  "      <td class='center'>". number_format($row['item_count']+0,0). "</td>";
		$reportlist[] =  "    </tr>";
		$reportsum += $row['item_count'];
	}
	$reportlist[] = "<tr class='sum'><td></td><td></td><td></td><td></td><td class='right'>Sum:</td><td class='center'>".number_format($reportsum,0)."</td></tr>";
	$reportlist[] =  "  </tbody>";

	$reportlist[] =  "</table>";

	$reportlist[] = "<!-- End of report list -->";
	$reportlist[] = "";

	#indent generated html by 2 extra spaces
	return implode("\n  ",$reportlist);
}

function tmpl_reportData($reportnumber, $allowed_reports, $host_lookup = 1, $sort_order) {

	if (!$reportnumber) {
		return "";
	}

	$reportdata[] = "";
	$reportdata[] = "<!-- Start of report rata -->";
	$reportsum    = 0;

	if (isset($allowed_reports[BySerial][$reportnumber])) {
		$row = $allowed_reports[BySerial][$reportnumber];
		$row = array_map('htmlspecialchars', $row);
		$reportdata[] = "<a id='rpt".$reportnumber."'></a>";
		$reportdata[] = "<div class='center reportdesc'><p> Report from ".$row['report_org_name']." for ".$row['report_domain']."<br>(". format_date($row['report_begin_date'], "r" ). " - ".format_date($row['report_end_date'], "r" ).")<br> Policies: adkim=" . $row['report_policy_adkim'] . ", aspf=" . $row['report_policy_aspf'] .  ", p=" . $row['report_policy_p'] .  ", sp=" . $row['report_policy_sp'] .  ", pct=" . $row['report_policy_pct'] . "</p></div>";
	} else {
		return "Unknown report number!";
	}

	$reportdata[] = "<table class='reportdata'>";
	$reportdata[] = "  <thead>";
	$reportdata[] = "    <tr>";
	$reportdata[] = "      <th>IP Address</th>";
	$reportdata[] = "      <th>Host Name</th>";
	$reportdata[] = "      <th>Message Count</th>";
	$reportdata[] = "      <th>Disposition</th>";
	$reportdata[] = "      <th>Reason</th>";
	$reportdata[] = "      <th>DKIM Domain</th>";
	$reportdata[] = "      <th>Raw DKIM Result</th>";
	$reportdata[] = "      <th>SPF Domain</th>";
	$reportdata[] = "      <th>Raw SPF Result</th>";
	$reportdata[] = "    </tr>";
	$reportdata[] = "  </thead>";

	$reportdata[] = "  <tbody>";

	global $db;
	$sql = "SELECT * FROM item where item_report_id = '$reportnumber'";

        try {
          $query = $db->query($sql);
          if ($query === false) {
            die("Error executing the query: " . $sql);
          }
         } catch (PDOException $e) {
           die($e->getMessage());
         }

         while($row = $query->fetch(PDO::FETCH_ASSOC)) {

		$status = get_status_color($row);

		$ip = $row['item_ip'];

		/* escape html characters after exploring binary values, which will be messed up */
		$row = array_map('htmlspecialchars', $row);

		$reportdata[] = "    <tr class='".$status."'>";
		$reportdata[] = "      <td>". $ip. "</td>";
		if ( $host_lookup ) {
			$reportdata[] = "      <td>". gethostbyaddr($ip). "</td>";
		} else {
			$reportdata[] = "      <td>#off#</td>";
		}
		$reportdata[] = "      <td>". $row['item_count']. "</td>";
		$reportdata[] = "      <td>". $row['item_disposition']. "</td>";
		$reportdata[] = "      <td>". $row['item_reason']. "</td>";
		$reportdata[] = "      <td>". $row['item_dkim_domain']. "</td>";
		$reportdata[] = "      <td>". $row['item_dkim_result']. "</td>";
		$reportdata[] = "      <td>". $row['item_spf_domain']. "</td>";
		$reportdata[] = "      <td>". $row['item_spf_result']. "</td>";
		$reportdata[] = "    </tr>";

		$reportsum += $row['item_count'];
	}
	$reportdata[] = "<tr><td></td><td></td><td>$reportsum</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
	$reportdata[] = "  </tbody>";
	$reportdata[] = "</table>";

	$reportdata[] = "<!-- End of report rata -->";
	$reportdata[] = "";

	#indent generated html by 2 extra spaces
	return implode("\n  ",$reportdata);
}

function tmpl_page ($body, $reportid, $host_lookup = 1, $sort_order, $dom_select, $domains = array(),$cssfile, $org_select, $orgs = array(), $per_select, $periods = array() ) {

	$html       = array();
        $url_hswitch = ( $reportid ? "?report=$reportid&hostlookup=" : "?hostlookup=" )
                . ($host_lookup ? "0" : "1" )
                . ( "&sortorder=" ) . ($sort_order)
                . (isset($dom_select) && $dom_select <> "" ? "&d=$dom_select" : "" )
                ;
        $url_dswitch = "?hostlookup=" . ($host_lookup ? "1" : "0" ) . "&sortorder=" . ($sort_order); // drop selected report on domain switch
        $url_sswitch = ( $reportid ? "?report=$reportid&hostlookup=" : "?hostlookup=" )
                . ($host_lookup)
                . ( "&sortorder=" ) . ($sort_order ? "0" : "1" )
                . (isset($dom_select) && $dom_select <> "" ? "&d=$dom_select" : "" )
                ;

	$html[] = "<!DOCTYPE html>";
	$html[] = "<html>";
	$html[] = "  <head>";
	$html[] = "    <title>DMARC Report Viewer</title>";
	$html[] = "    <link rel='stylesheet' href='$cssfile'>";
	$html[] = "  </head>";

	$html[] = "  <body>";


  # optionblock form
  #--------------------------------------------------------------------------
	$html[] = "    <div class='optionblock'><form action=\"?\" method=\"post\">";


  # handle host lookup (on/off should not reset selected report)
  #--------------------------------------------------------------------------
  $html[] = "<div class='options'><span class='optionlabel'>Hostname(s):</span> <input type=\"radio\" name=\"selHostLookup\" value=\"1\" onchange=\"this.form.submit()\"" . ($host_lookup ? " checked=\"checked\"" : "" ) . "> on<input type=\"radio\" name=\"selHostLookup\" value=\"0\" onchange=\"this.form.submit()\"" . ($host_lookup ? "" : " checked=\"checked\"" ) . "> off</div>";


  # handle sort direction
  #--------------------------------------------------------------------------
  $html[] = "<div class='options'><span class='optionlabel'>Sort order:</span> <input type=\"radio\" name=\"selOrder\" value=\"1\" onchange=\"this.form.submit()\"" . ($sort_order ? " checked=\"checked\"" : "" ) . "> ascending<input type=\"radio\" name=\"selOrder\" value=\"0\" onchange=\"this.form.submit()\"" . ($sort_order ? "" : " checked=\"checked\"" ) . "> decending</div>";


  # handle domains
  #--------------------------------------------------------------------------
  if ( count( $domains ) > 1 ) {
    $html[] = "<div class='options'><span class='optionlabel'>Domain(s):</span>";
    $html[] = "<select name=\"selDomain\" id=\"selDomain\" onchange=\"this.form.submit()\">";
    if( $dom_select != "" ) {
      $html[] = "<option value=\"all\">[all]</option>";
    } else {
      $html[] = "<option selected=\"selected\" value=\"all\">[all]</option>";
    }
    foreach( $domains as $d) {
      $arg = "";
      if( $d == $dom_select ) {
        $arg =" selected=\"selected\"";
      }
      $html[] = "<option $arg value=\"$d\">$d</option>";
    }
    $html[] = "</select>";
  }
  $html[] = "</div>";


  # handle orgs
  #--------------------------------------------------------------------------
  if ( count( $orgs ) > 0 ) {
    $html[] = "<div class='options'><span class='optionlabel'>Organisation(s):</span>";
    $html[] = "<select name=\"selOrganisation\" id=\"selOrganisation\" onchange=\"this.form.submit()\">";
    if( $org_select != "" ) {
      $html[] = "<option value=\"all\">[all]</option>";
    } else {
      $html[] = "<option selected=\"selected\" value=\"all\">[all]</option>";
    }
    foreach( $orgs as $o) {
      $arg = "";
      if( $o == $org_select ) {
        $arg =" selected=\"selected\"";
      }
      $html[] = "<option $arg value=\"$o\">" . ( strlen( $o ) > 25 ? substr( $o, 0, 22) . "..." : $o ) . "</option>";
    }
    $html[] = "</select>";
  }
  $html[] = "</div>";


  #--------------------------------------------------------------------------
  # handle period
  #--------------------------------------------------------------------------
  if ( count( $periods ) > 0 ) {
    $html[] = "<div class='options'><span class='optionlabel'>Time:</span>";
    $html[] = "<select name=\"selPeriod\" id=\"selPeriod\" onchange=\"this.form.submit()\">";
    if( $org_select != "" ) {
      $html[] = "<option value=\"all\">[all]</option>";
    } else {
      $html[] = "<option selected=\"selected\" value=\"all\">[all]</option>";
    }
    foreach( $periods as $p) {
      $arg = "";
      if( $p == $per_select ) {
        $arg =" selected=\"selected\"";
      }
      $html[] = "<option $arg value=\"$p\">$p</option>";
    }
    $html[] = "</select>";
  }
  $html[] = "</div>";


  # end optionblock
  #--------------------------------------------------------------------------
  $html[] = "</form></div>";


  # add body
  #--------------------------------------------------------------------------
  $html[] = $body;


  # footter
  #--------------------------------------------------------------------------
	$html[] = "  <div class='footer'>Brought to you by <a href='http://www.techsneeze.com'>TechSneeze.com</a> - <a href='mailto:dave@techsneeze.com'>dave@techsneeze.com</a></div>";
	$html[] = "  </body>";
	$html[] = "</html>";

	return implode("\n",$html);
}


//####################################################################
//### main ###########################################################
//####################################################################

// The file is expected to be in the same folder as this script, and it
// must exist.
include "dmarcts-report-viewer-config.php";
$dom_select= '';
$org_select= '';
$per_select= '';
$where = '';

if(!isset($dbport)) {
  $dbport="5432";
}

if(!isset($cssfile)) {
  $cssfile="default.css";
}

// parameters of by GET / POST - POST has priority
// --------------------------------------------------------------------------
if(isset($_GET['report'])) {
  $reportid=$_GET['report'];
}elseif(!isset($_GET['report'])){
  $reportid=false;
}else{
  die('Invalid Report ID');
}
if(isset($_POST['selHostLookup']) && is_numeric($_POST['selHostLookup'])){
  $hostlookup=$_POST['selHostLookup']+0;
} elseif(isset($_GET['hostlookup']) && is_numeric($_GET['hostlookup'])){
  $hostlookup=$_GET['hostlookup']+0;
}elseif(!isset($_GET['hostlookup'])){
  $hostlookup= isset( $default_lookup ) ? $default_lookup : 1;
}else{
  die('Invalid hostlookup flag');
}
if(isset($_POST['selOrder']) && is_numeric($_POST['selOrder'])){
  $sortorder=$_POST['selOrder']+0;
} elseif(isset($_GET['sortorder']) && is_numeric($_GET['sortorder'])){
  $sortorder=$_GET['sortorder']+0;
}elseif(!isset($_GET['sortorder'])){
  $sortorder= isset( $default_sort ) ? $default_sort : 1;
}else{
  die('Invalid sortorder flag');
}
if(isset($_POST['selDomain'])){
  $dom_select=$_POST['selDomain'];
} elseif(isset($_GET['d'])){
  $dom_select=$_GET['d'];
}else{
  $dom_select= '';
}
if( $dom_select == "all" ) {
  $dom_select= '';
}
if(isset($_POST['selOrganisation'])){
  $org_select=$_POST['selOrganisation'];
} elseif(isset($_GET['o'])){
  $org_select=$_GET['o'];
}else{
  $org_select= '';
}
if( $org_select == "all" ) {
  $org_select= '';
}
if(isset($_POST['selPeriod'])){
  $per_select=$_POST['selPeriod'];
} elseif(isset($_GET['p'])){
  $per_select=$_GET['p'];
}else{
  $per_select= date( 'Y-m' );
}
if( $per_select == "all" ) {
  $per_select= '';
}
// Debug
//echo "D=$dom_select <br /> O=$org_select <br />";

$dsn = "pgsql:host=$dbhost;port=$dbport;dbname=$dbname;user=$dbuser;password=$dbpass";

try{
  // create a PostgreSQL database connection
  $db = new PDO($dsn);
} catch (PDOException $e) {
  // report error message
  die($e->getMessage());
}


define("BySerial", 1);
define("ByDomain", 2);
define("ByOrganisation", 3);

// get all domains reported
// --------------------------------------------------------------------------
$sql="SELECT DISTINCT report_domain FROM report ORDER BY report_domain";
$domains = array();
try {
  $query = $db->query($sql);
  if ($query === false) {
    die("Error executing the query: " . $sql);
  }
} catch (PDOException $e) {
  // report error message
  die($e->getMessage());
}

while($row = $query->fetch(PDO::FETCH_ASSOC)) {
  $domains[] = $row['report_domain'];
}
if( $dom_select <> '' && array_search($dom_select, $domains) === FALSE ) {
  $dom_select = '';
}
if( $dom_select <> '' ) {
  $where .= ( $where <> '' ? " AND" : " WHERE" ) . " report_domain='" . $mysqli->real_escape_string($dom_select) . "'";
}

// get organisations
// --------------------------------------------------------------------------
$sql="SELECT DISTINCT report_org_name FROM report" . ($dom_select == '' ? "" : "WHERE report_domain='" . $mysqli->real_escape_string($dom_select). "'" ) . " ORDER BY report_org_name";
$orgs= array();
try {
  $query = $db->query($sql);
  if ($query === false) {
    die("Error executing the query: " . $sql);
  }
} catch (PDOException $e) {
  // report error message
  die($e->getMessage());
}

while($row = $query->fetch(PDO::FETCH_ASSOC)) {
  $orgs[] = $row['report_org_name'];
}
if( $org_select <> '' && array_search($org_select, $orgs) === FALSE ) {
  $org_select = '';
}
if( $org_select <> '' ) {
  $where .= ( $where <> '' ? " AND" : " WHERE" ) . " report_org_name='" . $mysqli->real_escape_string($org_select) . "'";
}

// get period
// --------------------------------------------------------------------------
$sql="SELECT DISTINCT extract(year from report_begin_date) as year, extract(month from report_begin_date) as month FROM report $where ORDER BY year desc, month desc";
$periods= array();

try {
  $query = $db->query($sql);
  if ($query === false) {
    die("Error executing the query: " . $sql);
  }
} catch (PDOException $e) {
  // report error message
  die($e->getMessage());
}

while($row = $query->fetch(PDO::FETCH_ASSOC)) {
  $periods[] = sprintf( "%'.04d-%'.02d", $row['year'], $row['month'] );
}
if( $per_select <> '' && array_search($per_select, $periods) === FALSE ) {
  $per_select = '';
}
if( $per_select <> '' ) {
  $ye = substr( $per_select, 0, 4) + 0;
  $mo = substr( $per_select, 5, 2) + 0;
  $where .= ( $where <> '' ? " AND" : " WHERE" ) . " extract(year from report_begin_date)=$ye and extract(month from report_begin_date)=$mo ";

}

// Get allowed reports and cache them - using serial as key
// --------------------------------------------------------------------------
$allowed_reports = array();

// set sort direction
// --------------------------------------------------------------------------
$sort = '';
if( $sortorder ) {
  $sort = "ASC";
} else {
  $sort = "DESC";
}

// Include the rcount via left join, so we do not have to make an sql query
// for every single report.
// --------------------------------------------------------------------------
$sql = "
  SELECT report.*,
    SUM(item.item_count) AS item_count,
    MIN(item.dkim_result) AS item_dkim_result,
    MIN(item.spf_result) AS item_spf_result
  FROM report
  LEFT JOIN (
    SELECT item_count,
      COALESCE(item_dkim_result, 'neutral') AS dkim_result,
      COALESCE(item_spf_result, 'neutral') AS spf_result,
      item_report_id
    FROM item)
  AS item
  ON report.report_id = item.item_report_id
  $where GROUP BY report_id ORDER BY report_begin_date $sort, report_end_date $sort, report_org_name";

// Debug
//echo "sql reports = $sql";

try {
  $query = $db->query($sql);
  if ($query === false) {
    die("Error executing the query: " . $sql);
  }
} catch (PDOException $e) {
  // report error message
  die($e->getMessage());
}

while($row = $query->fetch(PDO::FETCH_ASSOC)) {
	//todo: check ACL if this row is allowed
	if (true) {
		//add data by report_id
		$allowed_reports[BySerial][$row['report_id']] = $row;
		//make a list of serials by domain and by organisation
		//$allowed_reports[ByDomain][$row['report_domain']][] = $row['report_id'];
		//$allowed_reports[ByOrganisation][$row['report_org_name']][] = $row['report_id'];
	}
}

// Generate Page with report list and report data (if a report is selected).
// --------------------------------------------------------------------------
echo tmpl_page( ""
        .tmpl_reportList($allowed_reports, $hostlookup, $sortorder, $dom_select, $org_select, $per_select, $reportid)
        .tmpl_reportData($reportid, $allowed_reports, $hostlookup, $sortorder )
	, $reportid
	, $hostlookup
	, $sortorder
	, $dom_select
	, $domains
	, $cssfile
	, $org_select
	, $orgs
	, $per_select
	, $periods
);
?>
