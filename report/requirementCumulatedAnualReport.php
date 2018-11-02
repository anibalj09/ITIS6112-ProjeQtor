<?php
/*
 * @author: atrancoso ticket #84
 */
include_once '../tool/projeqtor.php';

//include ("../external/pChart/pData.class");
//include ("../external/pChart/pChart.class");
include("../external/pChart2/class/pData.class.php");
include("../external/pChart2/class/pDraw.class.php");
include("../external/pChart2/class/pImage.class.php");

$paramProduct = '';
if (array_key_exists ( 'idProduct', $_REQUEST )) {
  $paramProduct = trim ( $_REQUEST ['idProduct'] );
  $paramProduct = Security::checkValidId ( $paramProduct ); // only allow digits
}
;

$paramVersion = '';
if (array_key_exists ( 'idVersion', $_REQUEST )) {
  $paramVersion = trim ( $_REQUEST ['idVersion'] );
  $paramVersion = Security::checkValidId ( $paramVersion ); // only allow digits
}
;

$paramMonth = '';
if (array_key_exists ( 'monthSpinner', $_REQUEST )) {
  $paramMonth = $_REQUEST ['monthSpinner'];
  $paramMonth = Security::checkValidMonth ( $paramMonth );
}
;

$paramProject = '';
if (array_key_exists ( 'idProject', $_REQUEST )) {
  $paramProject = trim ( $_REQUEST ['idProject'] );
  $paramProject = Security::checkValidId ( $paramProject ); // only allow digits
}
;

$paramYear = '';
if (array_key_exists ( 'yearSpinner', $_REQUEST )) {
  $paramYear = $_REQUEST ['yearSpinner'];
  $paramYear = Security::checkValidYear ( $paramYear );
}
;

$paramPriorities = array();
if (array_key_exists ( 'priorities', $_REQUEST )) {
  foreach ( $_REQUEST ['priorities'] as $idPriority => $boolean ) {
    $paramPriorities [] = $idPriority;
  }
}

$periodType = 'year';
// $periodValue=$_REQUEST['periodValue'];
$periodValue = $paramYear;

// Header
$headerParameters = "";

if ($periodType=='year' and $paramMonth!="01") {
  if(!$paramMonth){
    $paramMonth="01";
  }
  $headerParameters.= i18n("startMonth") . ' : ' . i18n(date('F', mktime(0,0,0,$paramMonth,10))) . '<br/>';
}
if ($periodType=='month') {
  $headerParameters.= i18n("month") . ' : ' . $paramMonth . '<br/>';
}
if ( $periodType=='week') {
  $headerParameters.= i18n("week") . ' : ' . $paramWeek . '<br/>';
}

if ($paramProject != "") {
  $headerParameters .= i18n ( "colIdProject" ) . ' : ' . htmlEncode ( SqlList::getNameFromId ( 'Project', $paramProject ) ) . '<br/>';
}

if ($periodType == 'month') {
  $headerParameters .= i18n ( "month" ) . ' : ' . $paramMonth . '<br/>';
}
if ($paramProduct != "") {
  $headerParameters .= i18n ( "colIdProduct" ) . ' : ' . htmlEncode ( SqlList::getNameFromId ( 'Product', $paramProduct ) ) . '<br/>';
}

if ($paramVersion != "") {
  $headerParameters .= i18n ( "colVersion" ) . ' : ' . htmlEncode ( SqlList::getNameFromId ( 'Version', $paramVersion ) ) . '<br/>';
}
if ($periodType == 'year' or $periodType == 'month' or $periodType == 'week') {
  $headerParameters .= i18n ( "year" ) . ' : ' . $paramYear . '<br/>';
}

if (! empty ( $paramPriorities )) {
  $priority = new Priority ();
  $priorities = $priority->getSqlElementsFromCriteria ( null, false, null, 'id asc' );
  
  $prioritiesDisplayed = array();
  for($i = 0; $i < count ( $priorities ); $i ++) {
    if (in_array ( $i + 1, $paramPriorities )) {
      $prioritiesDisplayed [] = $priorities [$i];
    }
  }
  
  $headerParameters .= i18n ( "colPriority" ) . ' : ';
  foreach ( $prioritiesDisplayed as $priority ) {
    $headerParameters .= $priority->name . ', ';
  }
  $headerParameters = substr ( $headerParameters, 0, - 2 );
  
  if (in_array ( 'undefined', $paramPriorities )) {
    $headerParameters .= ', ' . i18n ( 'undefinedPriority' );
  }
}
include "header.php";

if(!$paramMonth){
  $paramMonth="01";
}

$includedReport = true;
// //////////////////////////////////////////////////////////////////////////////////////////////////////
$where = getAccesRestrictionClause ( 'Requirement', false );

$where .= " and ( (    creationDateTime>= '" . $paramYear . "-$paramMonth-01'";
$where .= "        and creationDateTime<='" . ($paramYear + 1) . "-" . ($paramMonth - 1) . "-31' ) )";
if ($paramProject != "") {
  $where .= " and idProject in " . getVisibleProjectsList ( false, $paramProject );
}

if (isset ( $paramProduct ) and $paramProduct != "") {
  $where .= " and idProduct='" . Sql::fmtId ( $paramProduct ) . "'";
}

if (isset ( $paramVersion ) and $paramVersion != "") {
  $where .= " and idOriginalProductVersion='" . Sql::fmtId ( $paramVersion ) . "'";
}

$filterByPriority = false;
if (! empty ( $paramPriorities ) and $paramPriorities [0] != 'undefined') {
  $filterByPriority = true;
  $where .= " and idPriority in (";
  foreach ( $paramPriorities as $idDisplayedPriority ) {
    if ($idDisplayedPriority == 'undefined')
      continue;
    $where .= $idDisplayedPriority . ', ';
  }
  $where = substr ( $where, 0, - 2 ); // To remove the last comma and space
  $where .= ")";
}
if ($filterByPriority and in_array ( 'undefined', $paramPriorities )) {
  $where .= " or idPriority is null";
} else if (in_array ( 'undefined', $paramPriorities )) {
  $where .= " and idPriority is null";
} else if ($filterByPriority) {
  $where .= " and idPriority is not null";
}
// ////////////////////////////////////////////////////////////////////////////////////////////////////////////
$whereC = getAccesRestrictionClause ( 'Requirement', false );

$whereC .= " and ( (    idleDate>= '" . $paramYear . "-$paramMonth-01'";
$whereC .= "        and idleDate<='" . ($paramYear + 1) . "-" . ($paramMonth - 1) . "-31' ) )";
if ($paramProject != "") {
  $whereC .= " and idProject in " . getVisibleProjectsList ( false, $paramProject );
}

if (isset ( $paramProduct ) and $paramProduct != "") {
  $whereC .= " and idProduct='" . Sql::fmtId ( $paramProduct ) . "'";
}

if (isset ( $paramVersion ) and $paramVersion != "") {
  $whereC .= " and idOriginalProductVersion='" . Sql::fmtId ( $paramVersion ) . "'";
}

$filterByPriority = false;
if (! empty ( $paramPriorities ) and $paramPriorities [0] != 'undefined') {
  $filterByPriority = true;
  $whereC .= " and idPriority in (";
  foreach ( $paramPriorities as $idDisplayedPriority ) {
    if ($idDisplayedPriority == 'undefined')
      continue;
    $whereC .= $idDisplayedPriority . ', ';
  }
  $whereC = substr ( $where, 0, - 2 ); // To remove the last comma and space
  $whereC .= ")";
}
if ($filterByPriority and in_array ( 'undefined', $paramPriorities )) {
  $whereC .= " or idPriority is null";
} else if (in_array ( 'undefined', $paramPriorities )) {
  $whereC .= " and idPriority is null";
} else if ($filterByPriority) {
  $whereC .= " and idPriority is not null";
}
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$whereD = getAccesRestrictionClause ( 'Requirement', false );

$whereD .= " and ( (    doneDate >= '" . $paramYear . "-$paramMonth-01'";
$whereD .= "        and doneDate <= '" . ($paramYear + 1) . "-" . ($paramMonth - 1) . "-31' ) )";
if ($paramProject != "") {
  $whereD .= " and idProject in " . getVisibleProjectsList ( false, $paramProject );
}

if (isset ( $paramProduct ) and $paramProduct != "") {
  $whereD .= " and idProduct='" . Sql::fmtId ( $paramProduct ) . "'";
}

if (isset ( $paramVersion ) and $paramVersion != "") {
  $whereD .= " and idOriginalProductVersion='" . Sql::fmtId ( $paramVersion ) . "'";
}

$filterByPriority = false;
if (! empty ( $paramPriorities ) and $paramPriorities [0] != 'undefined') {
  $filterByPriority = true;
  $whereD .= " and idPriority in (";
  foreach ( $paramPriorities as $idDisplayedPriority ) {
    if ($idDisplayedPriority == 'undefined')
      continue;
      $whereD .= $idDisplayedPriority . ', ';
  }
  $whereD = substr ( $where, 0, - 2 ); // To remove the last comma and space
  $whereD .= ")";
}
if ($filterByPriority and in_array ( 'undefined', $paramPriorities )) {
  $whereD .= " or idPriority is null";
} else if (in_array ( 'undefined', $paramPriorities )) {
  $whereD .= " and idPriority is null";
} else if ($filterByPriority) {
  $whereD .= " and idPriority is not null";
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
$order = "";
$req = new Requirement ();
$lstReq = $req->getSqlElementsFromCriteria ( null, false, $where, $order );
$created = array();
$closed = array();
for($i = 1; $i <= 13; $i ++) {
  $created [$i] = 0;
  $closed [$i] = 0;
  $done[$i] = 0;
}
$sumProj = array();
foreach ( $lstReq as $t ) {
  if (substr ( $t->creationDateTime, 0, 4 ) == $paramYear or substr ( $t->creationDateTime, 0, 4 ) == ($paramYear + 1)) {
    $month = intval ( substr ( $t->creationDateTime, 5, 2 ) );
    if (substr ( $t->creationDateTime, 0, 4 ) == $paramYear) {
      $created [$month - $paramMonth + 1] += 1;
    } else if (substr ( $t->creationDateTime, 0, 4 ) == $paramYear + 1) {
      if (($month - $paramMonth) > 0) {
        $created [$month - $paramMonth] += 1;
      } else {
        $created [$month + 13 - $paramMonth] += 1;
      }
    }
    $created[13]+=1;
  }
}

$orderC = "";
$reqC = new Requirement ();
$lstReqC = $reqC->getSqlElementsFromCriteria ( null, false, $whereC, $orderC );
foreach ( $lstReqC as $k ) {
  $month = intval ( substr ( $k->idleDate, 5, 2 ) );
  if (substr ( $k->idleDate, 0, 4 ) == $paramYear or substr ( $k->idleDate, 0, 4 ) == ($paramYear + 1)) {
    if (substr ( $k->idleDate, 0, 4 ) == $paramYear and $month >= $paramMonth) {
      $closed [$month - $paramMonth + 1] += 1;
    } else if (substr ( $k->idleDate, 0, 4 ) == $paramYear + 1) {
      if (($month - $paramMonth) > 0) {
        $closed [$month - $paramMonth] += 1;
      } else {
        $closed [$month + 13 - $paramMonth] += 1;
      }
      
    }
    $closed[13]+=1;
  }
}

  $orderD = "";
  $reqD = new Requirement ();
  $lstReqD = $reqD->getSqlElementsFromCriteria ( null, false, $whereD, $orderD );
  foreach ( $lstReqD as $d ) {
    $month = intval ( substr ( $d->doneDate, 5, 2 ) );
    if (substr ( $d->doneDate, 0, 4 ) == $paramYear or substr ( $d->doneDate, 0, 4 ) == ($paramYear + 1)) {
      if (substr ( $d->doneDate, 0, 4 ) == $paramYear and $month >= $paramMonth) {
        $done [$month - $paramMonth + 1] += 1;
      } else if (substr ( $d->doneDate, 0, 4 ) == $paramYear + 1) {
        if (($month - $paramMonth) > 0) {
          $done[$month - $paramMonth] += 1;
        } else {
          $done[$month + 13 - $paramMonth] += 1;
        }
      }
      $done[13]+=1;
    }
  }
  if (checkNoData ( $lstReq ) and checkNoData ( $lstReqC) and checkNoData ( $lstReqD))
    return;

// title;

$arrMonth [0] = getMonth ( 4, ($paramMonth - 1) % 12, true );
$arrMonth [1] = getMonth ( 4, ($paramMonth + 0) % 12, true );
$arrMonth [2] = getMonth ( 4, ($paramMonth + 1) % 12, true );
$arrMonth [3] = getMonth ( 4, ($paramMonth + 2) % 12, true );
$arrMonth [4] = getMonth ( 4, ($paramMonth + 3) % 12, true );
$arrMonth [5] = getMonth ( 4, ($paramMonth + 4) % 12, true );
$arrMonth [6] = getMonth ( 4, ($paramMonth + 5) % 12, true );
$arrMonth [7] = getMonth ( 4, ($paramMonth + 6) % 12, true );
$arrMonth [8] = getMonth ( 4, ($paramMonth + 7) % 12, true );
$arrMonth [9] = getMonth ( 4, ($paramMonth + 8) % 12, true );
$arrMonth [10] = getMonth ( 4, ($paramMonth + 9) % 12, true );
$arrMonth [11] = getMonth ( 4, ($paramMonth + 10) % 12, true );
$arrMonth [13] = i18n ( 'sum' );
$sum = 0;
for($line = 1; $line <= 2; $line ++) {
  if ($line == 1) {
    $tab = $created;
    $caption = i18n ( 'created' );
    $serie = "created";
  } else if ($line == 2) {
    $tab = $closed;
    $caption = i18n ( 'closed' );
    $serie = "closed";
  } else if ($line == 3) {
    $tab = $done;
    $caption = i18n ( 'done' );
    $serie = "done";
  }
}

$createdSum=array(VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,$created[13]);
$doneSum=array(VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,$done[13]);
$closedSum=array(VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,VOID,$closed[13]);
$closed[13]=VOID;
$created[13]=VOID;
$done[13]=VOID;

// Render graph
// pGrapg standard inclusions
if (! testGraphEnabled ()) {
  return;
}

$dataSet=new pData();
$dataSet->addPoints($created,"created");
$dataSet->setSerieDescription("created",i18n("created"));
$dataSet->setSerieOnAxis("created",0);
$serieSettings = array("R"=>200,"G"=>100,"B"=>100,"Alpha"=>80);
$dataSet->setPalette("created",$serieSettings);
$dataSet->addPoints($done,"done");
$dataSet->setSerieDescription("done",i18n("done"));
$dataSet->setSerieOnAxis("done",0);
$serieSettings = array("R"=>100,"G"=>200,"B"=>100,"Alpha"=>80);
$dataSet->setPalette("done",$serieSettings);
$dataSet->addPoints($closed,"closed");
$dataSet->setSerieDescription("closed",i18n("closed"));
$dataSet->setSerieOnAxis("closed",0);
$serieSettings = array("R"=>100,"G"=>100,"B"=>200,"Alpha"=>80);
$dataSet->setPalette("closed",$serieSettings);


$dataSet->addPoints($arrMonth,"month");
$dataSet->setAbscissa("month");
  
// Initialise the graph  
$width=1000;
$legendWidth=100;
$height=400;
$legendHeight=100;
$graph = new pImage($width+$legendWidth, $height,$dataSet);

/* Draw the background */
$graph->Antialias = FALSE;

/* Add a border to the picture */
$settings = array("R"=>240, "G"=>240, "B"=>240, "Dash"=>0, "DashR"=>0, "DashG"=>0, "DashB"=>0);
$graph->drawRoundedRectangle(5,5,$width+$legendWidth-8,$height-5,5,$settings);
$graph->drawRectangle(0,0,$width+$legendWidth-1,$height-1,array("R"=>150,"G"=>150,"B"=>150));

/* Set the default font */
$graph->setFontProperties(array("FontName"=>getFontLocation("verdana"),"FontSize"=>8));

/* title */
$graph->setFontProperties(array("FontName"=>getFontLocation("verdana"),"FontSize"=>8,"R"=>100,"G"=>100,"B"=>100));
$graph->drawLegend($width+18,17,array("Mode"=>LEGEND_VERTICAL, "Family"=>LEGEND_FAMILY_BOX ,
    "R"=>255,"G"=>255,"B"=>255,"Alpha"=>100,
    "FontR"=>55,"FontG"=>55,"FontB"=>55,
    "Margin"=>5));

/* Draw the scale */
$graph->setGraphArea(60,20,$width-20,$height-40);
$formatGrid=array("Mode"=>SCALE_MODE_START0, "GridTicks"=>0,
    "DrawYLines"=>array(0), "DrawXLines"=>true,"Pos"=>SCALE_POS_LEFTRIGHT,
    "LabelRotation"=>90, "GridR"=>200,"GridG"=>200,"GridB"=>200);
//$graph->drawScale($formatGrid);
$graph->Antialias = TRUE;

$dataSet->addPoints($createdSum,"createdSum");
$dataSet->setSerieOnAxis("createdSum",1);
$dataSet->addPoints($doneSum,"doneSum");
$dataSet->setSerieOnAxis("doneSum",1);
$dataSet->addPoints($closedSum,"closedSum");
$dataSet->setSerieOnAxis("closedSum",1);
$dataSet->setAxisName(0,i18n("sum"));
$dataSet->setAxisPosition(1,AXIS_POSITION_RIGHT);
$serieSettings = array("R"=>200,"G"=>100,"B"=>100,"Alpha"=>80);
$dataSet->setPalette("createdSum",$serieSettings);
$serieSettings = array("R"=>100,"G"=>200,"B"=>100,"Alpha"=>80);
$dataSet->setPalette("doneSum",$serieSettings);
$serieSettings = array("R"=>100,"G"=>100,"B"=>200,"Alpha"=>80);
$dataSet->setPalette("closedSum",$serieSettings);

//$formatGrid=array("LabelRotation"=>90,"GridTicks"=>0 ,"AutoAxisLabels"=>FALSE,"Mode"=>SCALE_MODE_START0);
$graph->drawScale($formatGrid);

$dataSet->setSerieDrawable("created",true);
$dataSet->setSerieDrawable("done",true);
$dataSet->setSerieDrawable("closed",true);
$dataSet->setSerieDrawable("createdSum",false);
$dataSet->setSerieDrawable("doneSum",false);
$dataSet->setSerieDrawable("closedSum",false);
//$graph->drawZoneChart("created", "done");
$graph->drawAreaChart(array("DisplayColor"=>DISPLAY_AUTO));
$graph->drawPlotChart();
$dataSet->setSerieDrawable("created",false);
$dataSet->setSerieDrawable("done",false);
$dataSet->setSerieDrawable("closed",false);
$dataSet->setSerieDrawable("createdSum",true);
$dataSet->setSerieDrawable("doneSum",true);
$dataSet->setSerieDrawable("closedSum",true);

$graph->drawBarChart();

$imgName=getGraphImgName("ticketYearlyReport");
$graph->render($imgName);
echo '<table width="95%" style="margin-top:20px;" align="center"><tr><td align="center">';
echo '<img src="' . $imgName . '" />'; 
echo '</td></tr></table>';