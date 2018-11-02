<?php
include_once "../tool/projeqtor.php";

$class=null;
if (isset($_REQUEST['class'])) {
  $class=$_REQUEST['class'];
}
Security::checkValidClass($class);

$id=null;
if (isset($_REQUEST['id'])) {
  $id=$_REQUEST['id'];
}
Security::checkValidId($id);
$scale='day';
if (isset($_REQUEST['scale'])) {
  $scale=$_REQUEST['scale'];
}
if ($scale!='day' and $scale!='week') {
  echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('ganttDetailScaleError')."</div>";
  return;
}

$objectClassManual = RequestHandler::getValue('objectClassManual');
if($objectClassManual == 'ResourcePlanning' ){
  $idAssignment = RequestHandler::getId('idAssignment');
}

$dates=array();
$work=array();
$start=null;
$end=null;

$crit=array('refType'=>$class,'refId'=>$id);

$pe=SqlElement::getSingleSqlElementFromCriteria($class.'PlanningElement', $crit);
if ($pe->assignedWork==0 and $pe->leftWork==0 and $pe->realWork==0) {
  echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('noDataToDisplay')."</div>";
  return;
}

if($objectClassManual == 'ResourcePlanning' ){
  $crit=array('refType'=>$class,'refId'=>$id,'idAssignment'=>$idAssignment);
}

$wk=new Work();
$wkLst=$wk->getSqlElementsFromCriteria($crit);
foreach($wkLst as $wk) {
  $dates[$wk->workDate]=$wk->workDate;
  if (!$start or $start>$wk->workDate) $start=$wk->workDate;
  if (!$end or $end<$wk->workDate) $end=$wk->workDate;
  if (! isset($work[$wk->idAssignment])) $work[$wk->idAssignment]=array();
  if (! isset($work[$wk->idAssignment]['resource'])) {
    $ress=new ResourceAll($wk->idResource);
    $work[$wk->idAssignment]['capacity']=($ress->capacity>1)?$ress->capacity:'1';
    $work[$wk->idAssignment]['resource']=$ress->name;
    if ($ress->isResourceTeam) {
      $ass=new Assignment($wk->idAssignment);
      $work[$wk->idAssignment]['capacity']=($ass->capacity>1)?$ass->capacity:'1';
    }
    if ($work[$wk->idAssignment]['capacity']>1) {
      $work[$wk->idAssignment]['resource'].=' ('.i18n('max').' = '.htmlDisplayNumericWithoutTrailingZeros($work[$wk->idAssignment]['capacity']).' '.i18n('days').')';
    }
  }
  $work[$wk->idAssignment][$wk->workDate]=array('work'=>$wk->work,'type'=>'real');
}
$wk=new PlannedWork();
$wkLst=$wk->getSqlElementsFromCriteria($crit);
foreach($wkLst as $wk) {
  $dates[$wk->workDate]=$wk->workDate;
  if (!$start or $start>$wk->workDate) $start=$wk->workDate;
  if (!$end or $end<$wk->workDate) $end=$wk->workDate;
  if (! isset($work[$wk->idAssignment])) $work[$wk->idAssignment]=array();
  if (! isset($work[$wk->idAssignment]['resource'])) {
    $ress=new ResourceAll($wk->idResource);
    $work[$wk->idAssignment]['capacity']=($ress->capacity>1)?$ress->capacity:'1';
    $work[$wk->idAssignment]['resource']=$ress->name;
    if ($ress->isResourceTeam) {
      $ass=new Assignment($wk->idAssignment);
      $work[$wk->idAssignment]['capacity']=($ass->capacity>1)?$ass->capacity:'1';
    }
    if ($work[$wk->idAssignment]['capacity']>1) {
      $work[$wk->idAssignment]['resource'].=' ('.i18n('max').' = '.htmlDisplayNumericWithoutTrailingZeros($work[$wk->idAssignment]['capacity']).' '.i18n('days').')';
    }
  }
  if (! isset($work[$wk->idAssignment][$wk->workDate]) ) {
    $work[$wk->idAssignment][$wk->workDate]=array('work'=>$wk->work,'type'=>'planned');
  }
}
if ($pe->idPlanningMode=='22') { // RECW
  $start=$pe->plannedStartDate;
  $end=$pe->plannedEndDate;
}
if (!$start or !$end) {
  if ($pe->elementary) echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('noDataToDisplay').'<br/>'.i18n('planningCalculationRequired')."</div>";
  else echo '<div style="background-color:#FFF0F0;padding:3px;border:1px solid #E0E0E0;">'.i18n('noDataToDisplay')."</div>";
	return;
}

if($objectClassManual != 'ResourcePlanning' ){
  if ($pe->plannedStartDate && $pe->plannedStartDate<$start){
    $start=$pe->plannedStartDate;
  }
}

$dt=$start;
while ($dt<=$end) {
  if (!isset($dates[$dt])) {
    $dates[$dt]=$dt;
  }
  $dt=addDaysToDate($dt, 1);
}
ksort($dates);

$width=20;
echo '<table id="planningBarDetailTable" style="height:'.(count($work)*22).'px;background-color:#FFFFFF;border-collapse: collapse;marin:0;padding:0">';
foreach ($work as $res) {
  echo '<tr style="height:20px;border:1px solid #505050;">';
  foreach ($dates as $dt) {
    $color="#ffffff";
    $height=0; $w=0;
    $capacity=$res['capacity'];
    if ($capacity==0) $capacity=1;
    if (isset($res[$dt])) {
      $w=$res[$dt]['work'];       
      if (!$pe->validatedEndDate or $dt<=$pe->validatedEndDate) {
        $color=($res[$dt]['type']=='real')?"#507050":"#50BB50";  
      } else {
        $color=($res[$dt]['type']=='real')?"#705050":"#BB5050";
      }
      
      $height=round($w*20/$capacity,0);
    }
    echo '<td style="padding:0;width:'.$width.'px;border-right:1px solid #eeeeee;position:relative;">'
        .'<div style="display:block;background-color:'.$color.';position:absolute;bottom:0px;left:0px;width:100%;height:'.$height.'px;"></div>'
        .'</td>';
  }
  echo '<td style="border-left:1px solid #505050;"><div style="width:200px; max-width:200px;overflow:hidden; text-align:left">&nbsp;'.$res['resource'].'&nbsp;</div></td>';
  echo '</tr>';
}
echo '</table>';