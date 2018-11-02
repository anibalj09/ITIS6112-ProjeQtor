<?php 
/*** COPYRIGHT NOTICE *********************************************************
 *
 * Copyright 2009-2017 ProjeQtOr - Pascal BERNARD - support@projeqtor.org
 * Contributors : -
 * 
 * Most of properties are extracted from Dojo Framework.
 *
 * This file is part of ProjeQtOr.
 * 
 * ProjeQtOr is free software: you can redistribute it and/or modify it under 
 * the terms of the GNU Affero General Public License as published by the Free 
 * Software Foundation, either version 3 of the License, or (at your option) 
 * any later version.
 * 
 * ProjeQtOr is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for 
 * more details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * ProjeQtOr. If not, see <http://www.gnu.org/licenses/>.
 *
 * You can get complete code of ProjeQtOr, other resource, help and information
 * about contributors at http://www.projeqtor.org 
 *     
 *** DO NOT REMOVE THIS NOTICE ************************************************/

include_once '../tool/projeqtor.php';
//echo "work.php";

$paramProject='';
if (array_key_exists('idProject',$_REQUEST)) {
  $paramProject=trim($_REQUEST['idProject']);
  Security::checkValidId($paramProject);
}
$paramTeam='';
if (array_key_exists('idTeam',$_REQUEST)) {
  $paramTeam=trim($_REQUEST['idTeam']);
  Security::checkValidId($paramTeam);
}
$paramYear='';
if (array_key_exists('yearSpinner',$_REQUEST)) {
	$paramYear=$_REQUEST['yearSpinner'];
	$paramYear=Security::checkValidYear($paramYear);
};
$paramMonth='';
if (array_key_exists('monthSpinner',$_REQUEST)) {
	$paramMonth=$_REQUEST['monthSpinner'];
  $paramMonth=Security::checkValidMonth($paramMonth);
} else {
  $paramMonth="01";
}
$paramWeek='';
if (array_key_exists('weekSpinner',$_REQUEST)) {
	$paramWeek=$_REQUEST['weekSpinner'];
	$paramWeek=Security::checkValidWeek($paramWeek);
};

$user=getSessionUser();

$paramResource='';
if (array_key_exists('idResource',$_REQUEST)) {
  $paramResource=trim($_REQUEST['idResource']);
  $paramResource = preg_replace('/[^0-9]/', '', $paramResource); // only allow digits
  $canChangeResource=false;
  $crit=array('idProfile'=>$user->idProfile, 'scope'=>'reportResourceAll');
  $habil=SqlElement::getSingleSqlElementFromCriteria('HabilitationOther', $crit);
  if ($habil and $habil->id and $habil->rightAccess=='1') {
    $canChangeResource=true;
  }
  if (!$canChangeResource and $paramResource!=$user->id) {
    echo i18n('messageNoAccess',array(i18n('colReport')));
    exit;
  } 
}

$periodType=$_REQUEST['periodType']; // not filtering as data as data is only compared against fixed strings
$periodValue='';
if (array_key_exists('periodValue',$_REQUEST))
{
	$periodValue=$_REQUEST['periodValue'];
	$periodValue=Security::checkValidPeriod($periodValue);
}

// Header
$headerParameters="";
if ($paramProject!="") {
  $headerParameters.= i18n("colIdProject") . ' : ' . htmlEncode(SqlList::getNameFromId('Project', $paramProject)) . '<br/>';
}
if ($paramTeam!="") {
  $headerParameters.= i18n("colIdTeam") . ' : ' . htmlEncode(SqlList::getNameFromId('Team', $paramTeam)) . '<br/>';
}
if ($periodType=='year' or $periodType=='month' or $periodType=='week') {
  $headerParameters.= i18n("year") . ' : ' . $paramYear . '<br/>';
}
//ADD qCazelles - Report fiscal year - Ticket #128
if ($periodType=='year' and $paramMonth!="01") {
  if (!$paramMonth ) {
    echo '<div style="background: #FFDDDD;font-size:150%;color:#808080;text-align:center;padding:20px">';
    echo i18n('messageNoData',array(i18n('month'))); // TODO i18n message
    echo '</div>';
    exit;
  } else {
    $headerParameters.= i18n("startMonth") . ' : ' . i18n(date('F', mktime(0,0,0,$paramMonth,10))) . '<br/>';
  }
}
//END ADD qCazelles - Report fiscal year - Ticket #128
if ($periodType=='month') {
  $headerParameters.= i18n("month") . ' : ' . $paramMonth . '<br/>';
}
if ( $periodType=='week') {
  $headerParameters.= i18n("week") . ' : ' . $paramWeek . '<br/>';
}
if ( $paramResource=='') {
  $headerParameters.= i18n("colIdResource") . ' : ' . htmlEncode(SqlList::getNameFromId('Resource',$paramResource)) . '<br/>';
}
include "header.php";



$where="(".getAccesRestrictionClause('Activity',false,false,true,true) ." or idResource=". getSessionUser()->id . ")"; 
//$where="1=1 ";
$where.=($periodType=='week')?" and week='" . $periodValue . "'":'';
$where.=($periodType=='month')?" and month='" . $periodValue . "'":'';

//CHANGE qCazelles - Report start month - Ticket #128
//Old
//$where.=($periodType=='year')?" and year='" . $periodValue . "'":'';
//New
if ($periodType=='year') {
  if (!$periodValue ) {
    echo '<div style="background: #FFDDDD;font-size:150%;color:#808080;text-align:center;padding:20px">';
    echo i18n('messageNoData',array(i18n('year'))); // TODO i18n message
    echo '</div>';
    exit;
  }
  $where.=" and ((year='" . $periodValue . "' and month>='" . $periodValue.($paramMonth<10?'0':'').$paramMonth . "')".
          " or (year='" . ($periodValue + 1) . "' and month<='" . ($periodValue + 1) . ($paramMonth<11?'0':'') . $paramMonth . "'))";
}
//END CHANGE qCazelles - Report start month - Ticket #128

if ($paramProject!='') {
  $where.=  " and idProject in " . getVisibleProjectsList(true, $paramProject); 
}
$where.=($paramResource!='')?" and idResource='" . $paramResource . "'":'';
$order="";
//echo $where;
$work=new Work();
$lstWork=$work->getSqlElementsFromCriteria(null,false, $where, $order);
$result=array();
$projects=array();
$resources=array();
$sumProj=array();
foreach ($lstWork as $work) {
  if (! array_key_exists($work->idResource,$resources)) {
    $resources[$work->idResource]=SqlList::getNameFromId('Resource', $work->idResource);
  }
  if (! array_key_exists($work->idProject,$projects)) {
    $projects[$work->idProject]=SqlList::getNameFromId('Project', $work->idProject);
  }
  if (! array_key_exists($work->idResource,$result)) {
    $result[$work->idResource]=array();
  }
  if (! array_key_exists($work->idProject,$result[$work->idResource])) {
    $result[$work->idResource][$work->idProject]=0;
  } 
  $result[$work->idResource][$work->idProject]+=$work->work;

}

if (checkNoData($result)) exit;
// title
$newProject=array();
foreach ($projects as $id=>$name) {
  $newProject[SqlList::getFieldFromId('Project', $id, 'sortOrder').'-'.$id]=$name;
}

$projects=$newProject;
ksort($projects);
foreach ($projects as $id=>$name) {
  $idExplo=explode('-',$id);
  $id=$idExplo[1];
  $sumProj[$id]=0;  
}

asort($resources);
foreach ($resources as $idR=>$nameR) {
  if ($paramTeam) {
    $res=new Resource($idR);
  }
  if (!$paramTeam or $res->idTeam==$paramTeam) {
    foreach ($projects as $idP=>$nameP) {
      $idExplo=explode('-',$idP);
      $idP=$idExplo[1];
      if (array_key_exists($idR, $result)) {
        if (array_key_exists($idP, $result[$idR])) {
          $val=$result[$idR][$idP];
          $sumProj[$idP]+=$val;
        }
      }
    }
  }
}
$nbProj=0;

foreach ($projects as $id=>$name) {
  $idExplo=explode('-',$id);
  $id=$idExplo[1];
  if($sumProj[$id] != 0)
    $nbProj+=1;
}
if($nbProj != 0)
$colWidth=round(80/$nbProj);
else
$colWidth=round(80/1);

echo '<table style="width:95%;" align="center">';
echo '<tr>';
echo '<td style="width:10%" class="reportTableHeader" rowspan="2">' . i18n('Resource') . '</td>';
echo '<td style="width:80%" colspan="' . $nbProj . '" class="reportTableHeader">' . i18n('Project') . '</td>';
echo '<td style="width:10%" class="reportTableHeader" rowspan="2">' . i18n('sum') . '</td>';
echo '</tr><tr>';

foreach ($projects as $id=>$name) {
  $idExplo=explode('-',$id);
  $id=$idExplo[1];
  if($sumProj[$id] != 0)
  echo '<td style="width:'.$colWidth.'%" class="reportTableColumnHeader">' . htmlEncode($name) . '</td>';
}
echo '</tr>';

$sum=0;
foreach ($resources as $idR=>$nameR) {
	if ($paramTeam) {
		$res=new Resource($idR);
	}
  if (!$paramTeam or $res->idTeam==$paramTeam) {
		$sumRes=0;
	  echo '<tr><td style="width:10%" class="reportTableLineHeader">' . htmlEncode($nameR) . '</td>';
	  foreach ($projects as $idP=>$nameP) {
	    
      $idExplo=explode('-',$idP);
      $idP=$idExplo[1];
    if($sumProj[$idP] != 0){
	    echo '<td style="width:' . $colWidth . '%" class="reportTableData">';
	    if (array_key_exists($idR, $result)) {
	      if (array_key_exists($idP, $result[$idR])) {
	        $val=$result[$idR][$idP];
	        echo Work::displayWorkWithUnit($val);
	        $sumRes+=$val; 
	        $sum+=$val;
	      } 
	    }
	    echo '</td>';
	  }
  }
	  echo '<td style="width:20%" class="reportTableColumnHeader">' . Work::displayWorkWithUnit($sumRes) . '</td>';
	  echo '</tr>';
  }
}
echo '<tr><td class="reportTableHeader">' . i18n('sum') . '</td>';
if ($nbProj == 0)
   echo '<td class="reportTableHeader">' . "" . '</td>';

foreach ($projects as $id=>$name) {
  $idExplo=explode('-',$id);
  $id=$idExplo[1];
  if($sumProj[$id] != 0)
  echo '<td class="reportTableColumnHeader">' . Work::displayWorkWithUnit($sumProj[$id]) . '</td>';
}
echo '<td class="reportTableHeader">' . Work::displayWorkWithUnit($sum) . '</td></tr>';
echo '</table>';