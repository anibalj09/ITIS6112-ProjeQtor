<?PHP
/*** COPYRIGHT NOTICE *********************************************************
 *
 * Copyright 2009-2017 ProjeQtOr - Pascal BERNARD - support@projeqtor.org
 * Contributors : -
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

/** ===========================================================================
 * Get the list of objects, in Json format, to display the grid list
 */
  require_once "../tool/projeqtor.php";
  require_once "../tool/jsonFunctions.php";
  
  scriptLog('   ->/tool/jsonPlanning.php');
  SqlElement::$_cachedQuery['Project']=array();
  SqlElement::$_cachedQuery['Ticket']=array();
  SqlElement::$_cachedQuery['Activity']=array();
  SqlElement::$_cachedQuery['Resource']=array();
  SqlElement::$_cachedQuery['PlanningElement']=array();
  
  $global=RequestHandler::getBoolean('global');

  $objectClass='PlanningElement';
  $columnsDescription=Parameter::getPlanningColumnDescription();
  $obj=new $objectClass();
  $table=$obj->getDatabaseTableName();
  $displayResource=Parameter::getGlobalParameter('displayResourcePlan');
  if (!$displayResource) $displayResource="initials";
  $print=false;
  if ( array_key_exists('print',$_REQUEST) ) {
    $print=true;
    include_once('../tool/formatter.php');
  }
  $saveDates=false;
  if ( array_key_exists('listSaveDates',$_REQUEST) ) {
    $saveDates=true;
  }
  if (! isset($portfolio)) {
    $portfolio=false;
  }
  if ( array_key_exists('portfolio',$_REQUEST) ) {
    $portfolio=true;
  }
  $showResource=false;
  if ( array_key_exists('showResource',$_REQUEST) ) {
    $showResource=true;
  }
  $plannableProjectsList=getSessionUser()->getListOfPlannableProjects();
  $startDate="";
  $endDate="";
  if (array_key_exists('startDatePlanView',$_REQUEST) and array_key_exists('endDatePlanView',$_REQUEST)) {
    $startDate=trim($_REQUEST['startDatePlanView']);
  	Security::checkValidDateTime($startDate);
    $endDate= trim($_REQUEST['endDatePlanView']);
	  Security::checkValidDateTime($endDate);
    $user=getSessionUser();
    $paramStart=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningStartDate'));
    $paramEnd=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningEndDate'));
    if ($saveDates) {
      $paramStart->parameterValue=$startDate;
      $paramStart->save();
      $paramEnd->parameterValue=$endDate;
      $paramEnd->save();
    } else {
      if ($paramStart->id) {
        $paramStart->delete();
      }
      if ($paramEnd->id) {
        $paramEnd->delete();
      }
    }
  }
  $baselineTop=null;
  if (array_key_exists('selectBaselineTop',$_REQUEST)) {
    $baselineTop=trim($_REQUEST['selectBaselineTop']);
  } else {
    $baselineTop=trim(getSessionValue('planningBaselineTop'));
  }
  $baselineBottom=null;
  if (array_key_exists('selectBaselineBottom',$_REQUEST)) {
    $baselineBottom=trim($_REQUEST['selectBaselineBottom']);
  } else {
    $baselineBottom=trim(getSessionValue('planningBaselineBottom'));
  }
  // Header
  if (array_key_exists('outMode', $_REQUEST) && $_REQUEST['outMode'] == 'csv') {
    $outMode = 'csv';
  } else if (! isset($outMode) ) {
    $outMode = "html";
  }
  if ( array_key_exists('report',$_REQUEST) ) {
    $headerParameters="";
    if (array_key_exists('startDate',$_REQUEST) and trim($_REQUEST['startDate'])!="") {
  		Security::checkValidDateTime(trim($_REQUEST['startDate']));
      $headerParameters.= i18n("colStartDate") . ' : ' . dateFormatter($_REQUEST['startDate']) . '<br/>';
    }
    if (array_key_exists('endDate',$_REQUEST) and trim($_REQUEST['endDate'])!="") {
		  Security::checkValidDateTime(trim($_REQUEST['endDate']));
      $headerParameters.= i18n("colEndDate") . ' : ' . dateFormatter($_REQUEST['endDate']) . '<br/>';
    }
    if (array_key_exists('format',$_REQUEST)) {
      if(! RequestHandler::getValue("format")){
          echo '<div style="background: #FFDDDD;font-size:150%;margin-top:20px;color:#808080;text-align:center;padding:20px">';
          echo i18n('messageNoData',array(i18n('colFormat'))); // TODO i18n message
          echo '</div>';
          exit;
      }
		  Security::checkValidPeriodScale(trim($_REQUEST['format']));
      $headerParameters.= i18n("colFormat") . ' : ' . i18n($_REQUEST['format']) . '<br/>';
    }
    if (array_key_exists('idProject',$_REQUEST) and trim($_REQUEST['idProject'])!="") {
      Security::checkValidId(trim($_REQUEST['idProject']));
      $headerParameters.= i18n("colIdProject") . ' : ' . (SqlList::getNameFromId('Project', $_REQUEST['idProject'])) . '<br/>';
    }
  	if($outMode == 'csv') {
        include "../report/headerFunctions.php";
    } else {
  	  include "../report/header.php";
  	}
  }
  if (! isset($outMode)) { $outMode=""; }

  $showIdleProjects=(sessionValueExists('projectSelectorShowIdle') and getSessionValue('projectSelectorShowIdle')==1)?1:0;
  
  $showIdle=true;
  if (array_key_exists('idle',$_REQUEST)) {
    $showIdle=true;
  }
  if ($portfolio) {
  	$accessRightRead=securityGetAccessRight('menuProject', 'read');
  } else {
    $accessRightRead=securityGetAccessRight('menuActivity', 'read');
  }
  if ( ! ( $accessRightRead!='ALL' or (sessionValueExists('project') and getSessionValue('project')!='*'))
   and ( ! array_key_exists('idProject',$_REQUEST) or trim($_REQUEST['idProject'])=="") and !$portfolio) {
      $listProj=explode(',',getVisibleProjectsList(! $showIdleProjects));
      if (count($listProj)-1 > Parameter::getGlobalParameter('maxProjectsToDisplay')) {
        echo i18n('selectProjectToPlan');
        return;
      }
  }
  $querySelect = '';
  $queryFrom='';
  $queryWhere='';
  $queryOrderBy='';
  $idTab=0;
  if (! array_key_exists('idle',$_REQUEST) ) {
    $queryWhere= $table . ".idle=0 ";
  }
  $queryWhere.= ($queryWhere=='')?'':' and ';
  if ($portfolio) {
  	$queryWhere.='( ('.getAccesRestrictionClause('Project',$table).')';
  	$queryWhere.=' OR ('.getAccesRestrictionClause('Milestone',$table,$showIdleProjects).') )';
  } else {
    if ($global) $queryWhere.="(1=1)"; // on GlobalPlanning, restriction on acces is applied on query in the FROM 
    else $queryWhere.=getAccesRestrictionClause('Activity',$table,$showIdleProjects);
  }
  if ( array_key_exists('report',$_REQUEST) ) {
    if (array_key_exists('idProject',$_REQUEST) and $_REQUEST['idProject']!=' ') {
      $queryWhere.= ($queryWhere=='')?'':' and ';
      $queryWhere.=  $table . ".idProject in " . getVisibleProjectsList(! $showIdleProjects, $_REQUEST['idProject']) ;
    }
  } else {
  	$queryWhere.= ($queryWhere=='')?'':' and ';
    $queryWhere.=  $table . ".idProject in " . getVisibleProjectsList(! $showIdleProjects) ;
  }
  // Remove administrative projects :
  $queryWhere.= ($queryWhere=='')?'':' and ';
  $queryWhere.=  $table . ".idProject not in " . Project::getAdminitrativeProjectList() ;

  
  $querySelect .= $table . ".* ";
  $queryOrderBy .= $table . ".wbsSortable ";
  if ($global) {
    $queryFrom .= GlobalPlanningElement::getTableNameQuery() .' as '. $table;
  } else {
    $queryFrom .= $table;
  }

  $showMilestone=false;
  if ($portfolio) {
  	$queryWhere.=' and ( refType=\'Project\' ';
    if (array_key_exists('showMilestone',$_REQUEST) ) {
      $showMilestone=trim($_REQUEST['showMilestone']);
    } else if (array_key_exists('listShowMilestone',$_REQUEST) ) {
      $showMilestone=trim($_REQUEST['listShowMilestone']);
    } else {
  	  $showMilestoneObj=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningShowMilestone'));
      $showMilestone=trim($showMilestoneObj->parameterValue);
    }
    if ($showMilestone) {
  	  $queryWhere.=' or refType=\'Milestone\' ';
    }
  	$queryWhere.=')';
  }
  
  // Retreive baseline info
  $arrayBase=array();
  $arrayBase['top']=array();
  $arrayBase['bottom']=array();
  $arrayBase['list']=array();
  if ($baselineTop) $arrayBase['list']['top']=$baselineTop;
  if ($baselineBottom) $arrayBase['list']['bottom']=$baselineBottom;
  $peb=new PlanningElementBaseline();
  $pebTable=$peb->getDatabaseTableName();
  foreach ($arrayBase['list'] as $pos=>$id) {
    $query='select refType as itemtype, refId as itemid,' 
    . ' coalesce(plannedStartDate,validatedStartDate,initialStartDate) as startdate,'
    . ' coalesce(plannedEndDate,validatedEndDate,initialEndDate) as enddate'
    . ' from ' . $pebTable
    . ' where ' . str_replace('planningelement','planningelementbaseline',$queryWhere) . ' and idBaseline='.Sql::fmtId($id)
    . ' order by ' . str_replace('planningelement','planningelementbaseline',$queryOrderBy);
    $resBase=Sql::query($query);
   while ($base = Sql::fetchLine($resBase)) {
     if ($base['startdate'] and $base['enddate'] and $base['itemtype'] and $base['itemid']) {
       $arrayBase[$pos][$base['itemtype'].'_'.$base['itemid']]=array('start'=>$base['startdate'],'end'=>$base['enddate']);
     }
   }
  }
  
  // Apply restrictions on Filter
  $act=new Activity();
  $pe=new PlanningElement();
  $peTable=$pe->getDatabaseTableName();
  $actTable=$act->getDatabaseTableName();
  $querySelectAct="$actTable.id as id, pet.wbsSortable as wbs";
  $queryFromAct="$actTable left join $peTable as pet on (pet.refType='Activity' and pet.refId=$actTable.id)";
  $queryWhereAct="1=1 ";
  $queryOrderByAct="$actTable.id asc";
  $applyFilter=false;
  $arrayFilter=jsonGetFilterArray('Planning', false);
  $arrayRestrictWbs=array();
  $cpt=0;
  if (count($arrayFilter)>0 and ! $portfolio and !$global) {
    $applyFilter=true;
    jsonBuildWhereCriteria($querySelectAct,$queryFromAct,$queryWhereAct,$queryOrderByAct,$cpt,$arrayFilter,$act);
    $queryAct='select ' . $querySelectAct
    . ' from ' . $queryFromAct
    . ' where ' . $queryWhereAct
    . ' order by ' . $queryOrderByAct;
    $resultAct=Sql::query($queryAct);
    while ($line = Sql::fetchLine($resultAct)) {
      //$arrayRestrictWbs[$line['wbs']]=$line['id'];
      $wbsExplode=explode('.',$line['wbs']);
      $wbsParent="";
      foreach ($wbsExplode as $wbsTemp) {
        $wbsParent=$wbsParent.(($wbsParent)?'.':'').$wbsTemp;
        if (!isset($arrayRestrictWbs[$wbsParent])) {
          $arrayRestrictWbs[$wbsParent]=$line['id'];
        } else {
          //$arrayRestrictWbs[$wbsParent].=','.$line['id'];
        }
      }
    }
    ksort($arrayRestrictWbs);
  }
  
  // constitute query and execute
  $queryWhere=($queryWhere=='')?' 1=1':$queryWhere;
  $query='SELECT ' . $querySelect
       . "\n FROM " . $queryFrom
       . "\n WHERE " . $queryWhere
       . "\n ORDER BY " . $queryOrderBy;
  $result=Sql::query($query);
  if (isset($debugJsonQuery) and $debugJsonQuery) { // Trace in configured to
     debugTraceLog("jsonPlanning: ".$query); // Trace query
     debugTraceLog("  => error (if any) = ".Sql::$lastQueryErrorCode.' - '.Sql::$lastQueryErrorMessage);
     debugTraceLog("  => number of lines returned = ".Sql::$lastQueryNbRows);
  }
  $nbQueriedRows=Sql::$lastQueryNbRows;
  
  if ($applyFilter and count($arrayRestrictWbs)==0) {
    $nbQueriedRows=0;
  }
    
  $nbRows=0;
  if ($print) {
    if ( array_key_exists('report',$_REQUEST) ) {
      $test=array();
      if ($nbQueriedRows > 0) $test[]="OK";
      if (checkNoData($test))  exit;
    }
    if ($outMode=='mpp') {
    	exportGantt($result);
    } else {
    	displayGantt($result);
    }
  } else {
    // return result in json format
    $na=Parameter::getUserParameter('notApplicableValue');
    $na=trim($na,"'");
    if (!$na) $na=null;
    $arrayObj=array();
    $rootWbsArray=array();
    $d=new Dependency();
    echo '{"identifier":"id",' ;
    echo ' "items":[';
    if ($nbQueriedRows > 0) {
    	$collapsedList=Collapsed::getCollaspedList();
    	$topProjectArray=array();
      while ($line = Sql::fetchLine($result)) {
      	$line=array_change_key_case($line,CASE_LOWER);
      	if ($applyFilter and !isset($arrayRestrictWbs[$line['wbssortable']])) continue; // Filter applied and item is not selected and not a parent of selected
      	if ($line['id'] and !$line['refname']) { // If refName not set, delete corresponding PE (results from incorrect delete
      	  $peDel=new PlanningElement($line['id'],true);
      	  $peDel->delete();
      	  continue;
      	}
        if ($line['reftype']=='Milestone' and $portfolio and $showMilestone and $showMilestone!='all' ) {   
          $mile=new Milestone($line['refid'],true);
          if ($mile->idMilestoneType!=$showMilestone) {
          	continue;
          }
        }
        echo (++$nbRows>1)?',':'';
        echo  '{';
        $nbFields=0;
        $idPe="";
        // NEW
        if (isset($line['isglobal']) and $line['isglobal']==1 and $line['progress']==$na) {
          // If real is set, start must be lower...
          if (trim($line['realenddate']) and $line['realenddate']!=$na) {
            if (trim($line['realstartdate']) and $line['realstartdate']!=$na and $line['realstartdate']>$line['realenddate']) {
              $line['realstartdate']=$line['realenddate'];
            }
            if (trim($line['plannedstartdate']) and $line['plannedstartdate']!=$na and $line['plannedstartdate']>$line['realenddate']) {
              $line['plannedstartdate']=$line['realenddate'];
            }
            if (trim($line['plannedenddate']) and $line['plannedenddate']!=$na and $line['plannedenddate']>$line['realenddate']) {
              $line['plannedenddate']=$line['realenddate'];
            }
          }
          if ($line['reftype']=='Ticket' and $line['validatedwork']>0 and $line['realwork']>0) {// Ticket by work
            if (trim($line['realenddate'])!="" and $line['realenddate']!=$na and $line['leftwork']==0) {
              $line['progress']='100';
            } else {
              $line['progress']=round(100*$line['realwork']/$line['validatedwork'],0);
            }
          } else if (trim($line['realenddate']) and $line['realenddate']!=$na) { // is started, so try and get progress from duration
            $line['progress']='100';
          } else if (trim($line['realstartdate']) and $line['realstartdate']!=$na) { // is started, so try and get progress from duration 
            $pStart="";
            $pStart=(trim($line['initialstartdate'])!="" and $line['initialstartdate']!=$na)?$line['initialstartdate']:$pStart;
            $pStart=(trim($line['validatedstartdate'])!="" and $line['validatedstartdate']!=$na)?$line['validatedstartdate']:$pStart;
            $pStart=(trim($line['plannedstartdate'])!="" and $line['plannedstartdate']!=$na)?$line['plannedstartdate']:$pStart;
            $pStart=(trim($line['realstartdate']!="" and $line['realstartdate']!=$na)!="")?$line['realstartdate']:$pStart;
            if (trim($line['plannedstartdate'])!="" and $line['plannedstartdate']!=$na 
            and trim($line['realstartdate'])!="" and $line['realstartdate']!=$na
            and $line['plannedstartdate']<$line['realstartdate'] ) {
              $pStart=$line['plannedstartdate'];
            }
            $pEnd="";
            $pEnd=(trim($line['initialenddate'])!="" and $line['initialenddate']!=$na)?$line['initialenddate']:$pEnd;
            $pEnd=(trim($line['validatedenddate'])!="" and $line['validatedenddate']!=$na)?$line['validatedenddate']:$pEnd;
            $pEnd=(trim($line['plannedenddate'])!="" and $line['plannedenddate']!=$na)?$line['plannedenddate']:$pEnd;
            $pEnd=(trim($line['realenddate'])!="" and $line['realenddate']!=$na)?$line['realenddate']:$pEnd;
            //if ($pEnd=="") {$pEnd=date('Y-m-d');}
            if ($line['reftype']=='Milestone') {
              $pStart=$pEnd;
            }
            $pStart=substr($pStart,0,10);
            $pEnd=substr($pEnd,0,10);
            if ($line['reftype']=='Decision') {
              if ($line['done']==1) $line['progress']='100';
              else $line['progress']='0';
            } else if (trim($line['realenddate'])!="" and $line['realenddate']!=$na) {
              $line['progress']='100';
            } else if ($pStart==$pEnd) {
              $line['progress']='50';
            } else {
              $taskLength=dayDiffDates($pStart,$pEnd)+1;
              if ($taskLength>0) {
                $progressLength=dayDiffDates($pStart,date('Y-m-d'))+1;
                $line['progress']=round($progressLength/$taskLength*100,0);
              } else {
                $line['progress']='50';
              }
            }
          } else {
            $line['progress']='0';
          }
          if (strpos($line['wbs'],'._#')!==false) {
            $rootWbs=substr($line['wbs'],0,strpos($line['wbs'],'._#'));
            if (! isset($rootWbsArray[$rootWbs])) {
              $rootWbsSortable=formatSortableWbs($rootWbs);
              $pe=SqlElement::getSingleSqlElementFromCriteria('PlanningElement', array('wbsSortable'=>$rootWbsSortable));
              $topId=$pe->id;
              //$max=substr($pe->getMaxValueFromCriteria('wbsSortable',array('topId'=>$topId)),-3);
              //$rootWbsArray[$rootWbs]=array('topId'=>$topId,'val'=>$max);
              $rootWbsArray[$rootWbs]=array('topId'=>$topId,'val'=>0);
            }
            $rootWbsArray[$rootWbs]['val']+=1;
            $wbsVal=$rootWbsArray[$rootWbs]['val'];
            $line['wbs']=$rootWbs.'._'.$wbsVal;
            $line['wbs']=""; // Hide WBS
            $line['wbssortable']=formatSortableWbs($line['wbs']);
            $line['topid']= $rootWbsArray[$rootWbs]['topId'];
          }
        } else if ($line["plannedwork"]>0 and $line["leftwork"]==0 and $line["elementary"]==1 ) {
        	$line["plannedstartdate"]='';
        	$line["plannedenddate"]='';
        }
        if (! $line["plannedduration"] and $line["validatedduration"]) { // Initialize planned duration to validated
          if (!$line["plannedstartdate"]) $line["plannedstartdate"]=($line["validatedstartdate"])?$line["validatedstartdate"]:date('Y-m-d');
          $line["plannedduration"]=$line["validatedduration"];
          $line["plannedenddate"]=addWorkDaysToDate($line["plannedstartdate"], $line["validatedduration"]);
        }
        $line["validatedworkdisplay"]=($line["validatedwork"]==$na)?$na:Work::displayWorkWithUnit($line["validatedwork"]);
        $line["assignedworkdisplay"]=($line["assignedwork"]==$na)?$na:Work::displayWorkWithUnit($line["assignedwork"]);
        $line["realworkdisplay"]=($line["realwork"]==$na)?$na:Work::displayWorkWithUnit($line["realwork"]);
        $line["leftworkdisplay"]=($line["leftwork"]==$na)?$na:Work::displayWorkWithUnit($line["leftwork"]);
        $line["plannedworkdisplay"]=($line["plannedwork"]==$na)?$na:Work::displayWorkWithUnit($line["plannedwork"]);
        $line["validatedcostdisplay"]=($line["validatedcost"]==$na)?$na:htmlDisplayCurrency($line["validatedcost"],true);
        $line["assignedcostdisplay"]=($line["assignedcost"]==$na)?$na:htmlDisplayCurrency($line["assignedcost"],true);
        $line["realcostdisplay"]=($line["realcost"]==$na)?$na:htmlDisplayCurrency($line["realcost"],true);
        $line["leftcostdisplay"]=($line["leftcost"]==$na)?$na:htmlDisplayCurrency($line["leftcost"],true);
        $line["plannedcostdisplay"]=($line["plannedcost"]==$na)?$na:htmlDisplayCurrency($line["plannedcost"],true);
        if ($columnsDescription['IdStatus']['show']==1 or $columnsDescription['Type']['show']==1) {
          if (isset($line["idstatus"]) and isset($line["idtype"]) and $line["idstatus"]) {
            $line["status"]=SqlList::getNameFromId('Status',$line["idstatus"]);
            $line["type"]=SqlList::getNameFromId('Type',$line["idtype"]);
          } else {
            $ref=$line['reftype'];
            $type='id'.$ref.'Type';
            $item=new $ref($line['refid'],true);
            $line["status"]=(property_exists($item,'idStatus'))?SqlList::getNameFromId('Status',$item->idStatus):null;
            $line["type"]=(property_exists($item,$type))?SqlList::getNameFromId('Type',$item->$type):null;
          }
        }
        $line["planningmode"]=SqlList::getNameFromId('PlanningMode',$line['idplanningmode']);
        if ($line["reftype"]=="Project") {
        	$topProjectArray[$line['refid']]=$line['id'];
        	$proj=new Project($line["refid"],true);
        	if ($proj->isUnderConstruction) {
        	  $line['reftype']='Construction';
        	}
        	if ($proj->fixPlanning) {
        	  $line['reftype']='Fixed';
        	} else if ( ! isset($plannableProjectsList[$line["refid"]]) ) {
        	  $line['reftype']='Fixed';
        	} else if ($line["needreplan"]) {
        	  $line['reftype']='Replan';
        	}
        } else if ($portfolio and $line["reftype"]=="Milestone" and $line["topreftype"]!='Project') {
          $line["topid"]=$topProjectArray[$line['idproject']];
        }
        foreach ($line as $id => $val) {
          if ($val==null) {$val=" ";}
          if ($val=="") {$val=" ";}
          echo (++$nbFields>1)?',':'';
          //echo '"' . htmlEncode($id) . '":"' . htmlEncodeJson(($val)) . '"';
          if ($id=='refname' or $id=='resource') {
          	$val=htmlEncode(htmlEncodeJson($val));
          } else {
          	$val=htmlEncodeJson($val);
          }
          echo '"' . htmlEncode($id) . '":"' . $val . '"';
          if ($id=='id') {$idPe=$val;}
        }
        //add expanded status
        $refItem=$line['reftype'].'_'.$line['refid'];
        if (isset($collapsedList['Planning_'.$refItem])) {
        	echo ',"collapsed":"1"';
        } else {
        	echo ',"collapsed":"0"';
        }
        if ($baselineTop and isset($arrayBase['top'][$refItem])) {
          echo ',"baseTopStart":"'.$arrayBase['top'][$refItem]['start'].'"';
          echo ',"baseTopEnd":"'.$arrayBase['top'][$refItem]['end'].'"';
        }
        if ($baselineBottom and isset($arrayBase['bottom'][$refItem])) {
          echo ',"baseBottomStart":"'.$arrayBase['bottom'][$refItem]['start'].'"';
          echo ',"baseBottomEnd":"'.$arrayBase['bottom'][$refItem]['end'].'"';
        }
        if ($line['reftype']!='Project' and $line['reftype']!='Fixed' and $line['reftype']!='Construction' and $line['reftype']!='Replan') {
          $arrayResource=array();
          // if ($showResource) { //
          if (isset($line['idresource']) and $line['idresource'] ) {
            $ass=new Assignment();
            $ass->idResource=$line['idresource'];
            $assList=array($line['idresource']=>$ass);
            $resp=$line['idresource'];
          } else { // Must always retreive resource to display value in column, even if not displayed
          	$crit=array('refType'=>$line['reftype'], 'refId'=>$line['refid']);
            $ass=new Assignment();
            $assList=$ass->getSqlElementsFromCriteria($crit,false); 
            $resp="";	        
  	        if (isset($arrayObj[$line['reftype']])) {
  	          $objElt=$arrayObj[$line['reftype']];
  	        } else {
              $objElt=new $line['reftype']();
              if (! property_exists($objElt,'idResource')) {
                $objElt=null;
              }
              $arrayObj[$line['reftype']]=$objElt;
  	        }
  	        if ($objElt) {
  	          $resp=SqlList::getFieldFromId($line['reftype'], $line['refid'], 'idResource');
  	        }
          }
	        foreach ($assList as $ass) {       	
	        	$res=new ResourceAll($ass->idResource,true);
	        	if (! isset($arrayResource[$res->id])) {
  	        	$display=($displayResource=='NO')?null:$res->$displayResource;
  	        	if ($displayResource=='initials' and ! $display) {
  	        	  //$encoding=mb_detect_encoding($res->name, 'ISO-8859-1, UTF-8');
  	        	  //$display=$encoding;
  	        	  $words=mb_split(' ',str_replace(array('"',"'"), ' ', $res->name));
  	        	  $display='';
  	        	  foreach ($words as $word) {
  	        	    $display.=(mb_substr($word,0,1,'UTF-8'));
  	        	  }
  	        	}
  	        	if ($display)	{
  	        	  $arrayResource[$res->id]=htmlEncode($display);
  	        	  if ($resp and $resp==$res->id ) {
  	        		  $arrayResource[$res->id]='<b>'.htmlEncode($display).'</b>';
  	        	  }
  	        	}
  	        }
          }
	        //$res=new Resource($ass->idResource);
	        echo ',"resource":"' . htmlEncodeJson(implode(', ',$arrayResource)) . '"';
        } else {
          echo ',"resource":""';
        }
        if (is_numeric($idPe) and floatval($idPe)==$idPe) {
          $crit=array('successorId'=>$idPe);
        } else {
          $crit=array('successorId'=>'0');
        }
        $listPred="";
        $depList=$d->getSqlElementsFromCriteria($crit,false);
        foreach ($depList as $dep) {
          $listPred.=($listPred!="")?',':'';
          $listPred.="$dep->predecessorId#$dep->id#$dep->successorRefType#$dep->dependencyType";
        }
        echo ', "depend":"' . $listPred . '"';
        echo '}';
      }
    }
    echo ' ] }';
  }

  function displayGantt($result) {
  	global $displayResource, $outMode, $showMilestone, $portfolio,  $columnsDescription, $nbQueriedRows;
  	$csvSep=Parameter::getGlobalParameter('csvSeparator');
    $showWbs=false;
    if (array_key_exists('showWBS',$_REQUEST) ) {
      $showWbs=true;
    }
    $showResource=false;
    if ( array_key_exists('showResource',$_REQUEST) ) {
      $showResource=true;
    }
    // calculations
    $startDate=date('Y-m-d');
    if (array_key_exists('startDate',$_REQUEST)) {
      $startDate=$_REQUEST['startDate'];
	    Security::checkValidDateTime($startDate);
    }
    $endDate='';
    if (array_key_exists('endDate',$_REQUEST)) {
      $endDate=$_REQUEST['endDate'];
	    Security::checkValidDateTime($endDate);
    }
    $format='day';
    if (array_key_exists('format',$_REQUEST)) {
      $format=$_REQUEST['format'];
	    Security::checkValidPeriodScale($format);
    }
    if($format == 'day') {
      $colWidth = 18;
      $colUnit = 1;
      $topUnit=7;
    } else if($format == 'week') {
      $colWidth = 50;
      $colUnit = 7;
      $topUnit=7;
    } else if($format == 'month') {
      $colWidth = 60;
      $colUnit = 30;
      $topUnit=30;
    } else if($format == 'quarter') {
      $colWidth = 30;
      $colUnit = 30;
      $topUnit=90;
    }
    $maxDate = '';
    $minDate = '';
    if ($nbQueriedRows > 0) {
      $resultArray=array();
      while ($line = Sql::fetchLine($result)) {
      	$line=array_change_key_case($line,CASE_LOWER);
        if ($line['reftype']=='Milestone' and $portfolio and $showMilestone and $showMilestone!='all' ) {   
          $mile=new Milestone($line['refid'],true);
          if ($mile->idMilestoneType!=$showMilestone) {
            continue;
          }
        }
        if ($line["plannedwork"]>0 and $line["leftwork"]==0) {
          $line["plannedstartdate"]='';
          $line["plannedenddate"]='';
        }
        $pStart="";
        $pStart=(trim($line['initialstartdate'])!="")?$line['initialstartdate']:$pStart;
        $pStart=(trim($line['validatedstartdate'])!="")?$line['validatedstartdate']:$pStart;
        $pStart=(trim($line['plannedstartdate'])!="")?$line['plannedstartdate']:$pStart;
        $pStart=(trim($line['realstartdate'])!="")?$line['realstartdate']:$pStart;
        if (trim($line['plannedstartdate'])!=""
        and trim($line['realstartdate'])!=""
        and $line['plannedstartdate']<$line['realstartdate'] ) {
          $pStart=$line['plannedstartdate'];
        }
        $pEnd="";
        $pEnd=(trim($line['initialenddate'])!="")?$line['initialenddate']:$pEnd;
        $pEnd=(trim($line['validatedenddate'])!="")?$line['validatedenddate']:$pEnd;
        $pEnd=(trim($line['plannedenddate'])!="")?$line['plannedenddate']:$pEnd;
        $pEnd=(trim($line['realenddate'])!="")?$line['realenddate']:$pEnd;
        //if ($pEnd=="") {$pEnd=date('Y-m-d');}
        if ($line['reftype']=='Milestone') {
          $pStart=$pEnd;
        }
        if (strlen($pStart)>10) $pStart=substr($pStart,0,10);
        if (strlen($pEnd)>10) $pStart=substr($pEnd,0,10);
        if (trim($line['realstartdate']) and isset($line['isglobal']) and $line['isglobal']==1 and ! $line['progress']) {
          if ($pStart==$pend) {
            $line['progress']='50';
          } else {
            $taskLength=dayDiffDates($pStart,$pend);
            if ($taskLength>0) {
              $progressLength=dayDiffDates($pStart,date('Y-m-d'));
              $line['progress']=round($progressLength/$taskLength*100,0);
            } else {
              $line['progress']='50';
            }
          } 
        }
        $line['pstart']=$pStart;
        $line['pend']=$pEnd;
        $line['type'] = '';
        if($line['reftype'] == 'Project') {
            $project = new Project($line['refid']);
            $line['color'] = $project->color;
            $type = new Type($project->idProjectType);
            $line['type'] = $type->name;
            $status = new Status($project->idStatus);
            $line['status'] = $status->name;
            $line['statuscolor'] = $status->color;
        } else if ($columnsDescription['IdStatus']['show']==1 or $columnsDescription['Type']['show']==1) {
          $ref=$line['reftype'];
          if ($ref=='PeriodicMeeting') $ref='Meeting';
          $type='id'.$ref.'Type';
          $item=new $ref($line['refid'],true);
          $line["type"]=SqlList::getNameFromId('Type',$item->$type);
          if (property_exists($item,"idStatus")) {
            $status = new Status($item->idStatus);
            $line['status'] = $status->name;
            $line['statuscolor'] = $status->color;
          } else {
            $line['status'] = '';
            $line['statuscolor'] = '';
          }
        }
        if ($line['reftype']!='Project' and $line['reftype']!='Fixed' and $line['reftype']!='Construction' and $line['reftype']!='Replan') { // 'Fixed' and 'Construction' are projects !!!!
          $arrayResource=array();
          if (isset($columnsDescription['Resource']) and $columnsDescription['Resource']['show']==1) { // Must always retreive resource to display value in column, even if not displayed
            $crit=array('refType'=>$line['reftype'], 'refId'=>$line['refid']);
            $ass=new Assignment();
            $assList=$ass->getSqlElementsFromCriteria($crit,false);
            $resp="";
            if (isset($arrayObj[$line['reftype']])) {
              $objElt=$arrayObj[$line['reftype']];
            } else {
              $objElt=new $line['reftype']();
              if (! property_exists($objElt,'idResource')) {
                $objElt=null;
              }
              $arrayObj[$line['reftype']]=$objElt;
            }
            if ($objElt) {
              $resp=SqlList::getFieldFromId($line['reftype'], $line['refid'], 'idResource');
            }
            foreach ($assList as $ass) {
              $res=new Resource($ass->idResource,true);
              if (! isset($arrayResource[$res->id])) {
                $display=$res->$displayResource;
                if ($displayResource=='initials' and ! $display) {
                  $words=mb_split(' ',str_replace(array('"',"'"), ' ', $res->name));
                  $display='';
                  foreach ($words as $word) {
                    $display.=(mb_substr($word,0,1,'UTF-8'));
                  }
                }
                if ($display)	{
                  $arrayResource[$res->id]=htmlEncode($display);
                  if ($resp and $resp==$res->id ) {
                    $arrayResource[$res->id]='<b>'.htmlEncode($display).'</b>';
                  }
                }
              }
            }
          }
          //$res=new Resource($ass->idResource);
          $line["resource"]= htmlEncodeJson(implode(', ',$arrayResource));
        } else {
          $line["resource"]="";
        }
        $resultArray[]=$line;
        if ($maxDate=='' or $maxDate<$pEnd) {$maxDate=$pEnd;}
        //if ($minDate=='' or $minDate>$pStart) {$minDate=$pStart;}
        if ($minDate=='' or ($minDate>$pStart and trim($pStart))) { $minDate=$pStart;}
      }
      if ($minDate<$startDate) {
        $minDate=$startDate;
      }
      if ($endDate and $maxDate>$endDate) {
        $maxDate=$endDate;
      }
      if ($format=='day' or $format=='week') {
        //$minDate=addDaysToDate($minDate,-1);
        $minDate=date('Y-m-d',firstDayofWeek(weekNumber($minDate),substr($minDate,0,4)));
        //$maxDate=addDaysToDate($maxDate,+1);
        $maxDate=date('Y-m-d',firstDayofWeek(weekNumber($maxDate),substr($maxDate,0,4)));
        $maxDate=addDaysToDate($maxDate,+6);
      } else if ($format=='month') {
        //$minDate=addDaysToDate($minDate,-1);
        $minDate=substr($minDate,0,8).'01';
        //$maxDate=addDaysToDate($maxDate,+1);
        $maxDate=addMonthsToDate($maxDate,+1);
        $maxDate=substr($maxDate,0,8).'01';
        $maxDate=addDaysToDate($maxDate,-1);
      } else if ($format=='quarter') {
        $arrayMin=array("01-01"=>"01-01","02-01"=>"01-01","03-01"=>"01-01",
                        "04-01"=>"04-01","05-01"=>"04-01","06-01"=>"04-01",
                        "07-01"=>"07-01","08-01"=>"07-01","09-01"=>"07-01",
                        "10-01"=>"10-01","11-01"=>"10-01","12-01"=>"10-01");
      	$arrayMax=array("01-31"=>"03-31","02-28"=>"03-31","02-29"=>"03-31","03-31"=>"03-01",
                        "04-30"=>"06-30","05-31"=>"06-30","06-30"=>"06-30",
                        "07-31"=>"09-30","08-31"=>"09-30","09-30"=>"09-30",
                        "10-31"=>"12-31","11-30"=>"12-31","12-31"=>"12-31");
        //$minDate=addDaysToDate($minDate,-1);
        $minDate=substr($minDate,0,8).'01';
        $minDate=substr($minDate,0,5).$arrayMin[substr($minDate,5)];
        //$maxDate=addDaysToDate($maxDate,+1);
        $maxDate=addMonthsToDate($maxDate,+1);
        $maxDate=substr($maxDate,0,8).'01';
        $maxDate=addDaysToDate($maxDate,-1);
        $maxDate=substr($maxDate,0,5).$arrayMax[substr($maxDate,5)];
      }
      $numDays = (dayDiffDates($minDate, $maxDate) +1);
      $numUnits = round($numDays / $colUnit);
      $topUnits = round($numDays / $topUnit);
      $days=array();
      $openDays=array();
      $day=$minDate;
      for ($i=0;$i<$numDays; $i++) {
        $days[$i]=$day;
        $openDays[$i]=isOpenDay($day,'1');
        $day=addDaysToDate($day,1);
      }
      //echo "mindate:$minDate maxdate:$maxDate numDays:$numDays numUnits:$numUnits topUnits:$topUnits" ;
      // Header
      //$sortArray=Parameter::getPlanningColumnOrder();
	  $sortArray=array_merge(array(), Parameter::getPlanningColumnOrder());
    $cptSort=0;
    foreach ($columnsDescription as $ganttCol) { 
      if ($ganttCol['show']==1) $cptSort++; 
    }
	  if($outMode != 'csv') {
      //echo '<table dojoType="dojo.dnd.Source" id="wishlistNode" class="container ganttTable" style="border: 1px solid #AAAAAA; margin: 0px; padding: 0px;">';
      echo '<div style="overflow:hidden;">';
      echo '<table style="font-size:80%; border: 1px solid #AAAAAA; margin: 0px; padding: 0px;">';
      echo '<tr style="height: 20px;"><td colspan="' . (1+$cptSort) . '">&nbsp;</td>';
      $day=$minDate;
      for ($i=0;$i<$topUnits;$i++) {
        $span=$topUnit;
        $title="";
        if ($format=='month') {
          $title=substr($day,0,4);
          $span=numberOfDaysOfMonth($day);
        } else if($format=='week') {
          $title=substr($day,2,2) . " #" . weekNumber($day);
        } else if ($format=='day') {
          $tDate = explode("-", $day);
          $date= mktime(0, 0, 0, $tDate[1], $tDate[2]+1, $tDate[0]);
          $title=substr($day,0,4) . " #" . weekNumber($day);
          $title.=' (' . substr(i18n(date('F', $date)),0,4) . ')';
        } else if ($format=='quarter') {
          $arrayQuarter=array("01"=>"1","02"=>"1","03"=>"1",
                        "04"=>"2","05"=>"2","06"=>"2",
                        "07"=>"3","08"=>"3","09"=>"3",
                        "10"=>"4","11"=>"4","12"=>"4");
        
        	$title="Q";
        	$title.=$arrayQuarter[substr($day,5,2)];
        	$title.=" ".substr($day,0,4);
        	$span=numberOfDaysOfMonth($day)+numberOfDaysOfMonth(addMonthsToDate($day,1))+numberOfDaysOfMonth(addMonthsToDate($day,2));
        	$span=3*30/5;
        }
        echo '<td class="reportTableHeader" colspan="' . $span . '">';
        echo $title;
        echo '</td>';
        if ($format=='month') {
          $day=addMonthsToDate($day,1);
        } else if ($format=='quarter') {
        	$day=addMonthsToDate($day,3);
        } else {
          $day=addDaysToDate($day,$topUnit);
        }
      }
      echo '</tr>';
      echo '<TR style="height: 20px;">';
      echo '  <TD class="reportTableHeader" style="width:15px; border-right:0px;"></TD>';
      echo '  <TD class="reportTableHeader" style="width:150px; border-left:0px; text-align: left;">' . i18n('colTask') . '</TD>';
      foreach ($sortArray as $col) {
        if (isset($columnsDescription[$col]) and $columnsDescription[$col]['show']!=1) continue; 
        if ($col=='ValidatedWork') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colValidated') . '</TD>' ;
      	if ($col=='AssignedWork') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colAssigned') . '</TD>' ;
        if ($col=='RealWork') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colReal') . '</TD>' ;
        if ($col=='LeftWork') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colLeft') . '</TD>' ;
        if ($col=='PlannedWork') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colReassessed') . '</TD>' ;
        if ($col=='ValidatedCost') echo '  <TD class="reportTableHeader" style="width:30px">'. i18n('colValidatedCost') . '</TD>' ;
        if ($col=='AssignedCost') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colAssignedCost') . '</TD>' ;
        if ($col=='RealCost') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colRealCost') . '</TD>' ;
        if ($col=='LeftCost') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colLeftCost') . '</TD>' ;
        if ($col=='PlannedCost') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colPlannedCost') . '</TD>' ;
        if ($col=='Type') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colType') . '</TD>' ;
        if ($col=='IdStatus') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colIdStatus') . '</TD>' ;
        if ($col=='Duration') echo '  <TD class="reportTableHeader" style="width:30px">' . i18n('colDuration') . '</TD>' ;
        if ($col=='Progress') echo '  <TD class="reportTableHeader" style="width:30px">'  . i18n('colPct') . '</TD>' ;
        if ($col=='StartDate') echo '  <TD class="reportTableHeader" style="width:50px">'  . i18n('colStart') . '</TD>' ;
        if ($col=='EndDate') echo '  <TD class="reportTableHeader" style="width:50px">'  . i18n('colEnd') . '</TD>' ;
        if ($col=='Resource') echo '  <TD class="reportTableHeader" style="width:50px">'  . i18n('colResource') . '</TD>' ;
        if ($col=='Priority') echo '  <TD class="reportTableHeader" style="width:50px">'  . i18n('colPriorityShort') . '</TD>' ;
        if ($col=='IdPlanningMode') echo '  <TD class="reportTableHeader" style="width:150px">'  . i18n('colIdPlanningMode') . '</TD>' ;
      }
      $weekendColor="#cfcfcf";
      $day=$minDate;
      for ($i=0;$i<$numUnits;$i++) {
        $color="";
        $span=$colUnit;
        if ($format=='month') {
          $tDate = explode("-", $day);
          $date= mktime(0, 0, 0, $tDate[1], $tDate[2]+1, $tDate[0]);
          $title=i18n(date('F', $date));
          $span=numberOfDaysOfMonth($day);
        } else if($format=='week') {
          $title=substr(htmlFormatDate($day),0,5);
        } else if ($format=='day') {
          $color=($openDays[$i]==1)?'':'background-color:' . $weekendColor . ';';
          $title=substr($days[$i],-2);
        } else if ($format=='quarter') {
          $tDate = explode("-", $day);
          $date= mktime(0, 0, 0, $tDate[1], $tDate[2]+1, $tDate[0]);
          $title=substr($day,5,2);
          $span=numberOfDaysOfMonth($day);
          $span=30/5;
        }
        echo '<td class="reportTableColumnHeader" colspan="' . $span . '" style="width:' . $colWidth . 'px;magin:0px;padding:0px;' . $color . '">';
        echo $title . '</td>';
        if ($format=='month') {
          $day=addMonthsToDate($day,1);
        } else if ($format=='quarter') {
          $day=addMonthsToDate($day,1);
        } else {
          $day=addDaysToDate($day,$topUnit);
        }
      }
      echo '</TR>';
	  } else {
	      $currency=' ('.Parameter::getGlobalParameter('currency').')';
	      $workUnit=' ('.Work::displayShortWorkUnit().')';
        echo chr(239) . chr(187) . chr(191); // Needed by Microsoft Excel to make it CSV
        echo i18n('colElement') . $csvSep . i18n('colId') . $csvSep . i18n('colTask') . $csvSep  ; 
        foreach ($sortArray as $col) {
          if (isset($columnsDescription[$col]) and $columnsDescription[$col]['show']!=1) continue; 
          if ($col=='ValidatedWork') echo i18n('colValidatedWork') . $workUnit . $csvSep ;
          if ($col=='AssignedWork') echo i18n('colAssignedWork') . $workUnit . $csvSep ;
          if ($col=='RealWork') echo i18n('colRealWork') . $workUnit . $csvSep ;
          if ($col=='LeftWork') echo i18n('colLeftWork') . $workUnit . $csvSep ;
          if ($col=='PlannedWork') echo i18n('colPlannedWork') . $workUnit . $csvSep ;
          if ($col=='ValidatedCost') echo i18n('colValidatedCost') . $currency . $csvSep ;
          if ($col=='AssignedCost') echo i18n('colAssignedCost') . $currency . $csvSep ;
          if ($col=='RealCost') echo i18n('colRealCost') . $currency . $csvSep ;
          if ($col=='LeftCost') echo i18n('colLeftCost') . $currency . $csvSep ;
          if ($col=='PlannedCost') echo i18n('colPlannedCost') . $currency . $csvSep ;
          if ($col=='Type') echo i18n('colType') . $csvSep ;
          if ($col=='IdStatus') echo i18n('colIdStatus') . $csvSep . i18n('colStatusColor') . $csvSep ;      
          if ($col=='Duration') echo i18n('colDuration') . ' ('.i18n('shortDay') . ')' . $csvSep ;
          if ($col=='Progress') echo i18n('colProgress'). ' (' .i18n('colPct') . ')' . $csvSep ;
          if ($col=='StartDate') echo i18n('colStart') . $csvSep ;
          if ($col=='EndDate') echo i18n('colEnd') . $csvSep ;
          if ($col=='Resource') echo i18n('colResource') . $csvSep ;
          if ($col=='Priority') echo i18n('colPriority') . $csvSep ;
          if ($col=='IdPlanningMode') echo i18n('colIdPlanningMode') . $csvSep ;
        }
        echo "\n";
      }
      // lines
      $width=round($colWidth/$colUnit) . "px;";
      $collapsedList=Collapsed::getCollaspedList();
      $closedWbs='';
      $level=1;
      $wbsLevelArray=array();
      foreach ($resultArray as $line) {
        $pEnd=$line['pend'];
        $pStart=$line['pstart'];
        $realWork=$line['realwork'];
        $plannedWork=$line['plannedwork'];
        $progress=$line['progress'];

        // pGroup : is the task a group one ?
        $pGroup=($line['elementary']=='0')?1:0;
        if ($line['reftype']=='Fixed') $pGroup=1;
        if ($line['reftype']=='Replan') $pGroup=1;
        if ($closedWbs and strlen($line['wbssortable'])<=strlen($closedWbs)) {
          $closedWbs="";
        }
        $scope='Planning_'.$line['reftype'].'_'.$line['refid'];
        $collapsed=false;
        if ($pGroup and array_key_exists($scope, $collapsedList)) {
          $collapsed=true;
          if (! $closedWbs) {
            $closedWbs=$line['wbssortable'];
          }
        }
        $compStyle="";
        $bgColor="";
        if( $pGroup) {
          $rowType = "group";
          $compStyle="font-weight: bold; background: #E8E8E8;";
          $bgColor="background: #E8E8E8;";
        } else if( $line['reftype']=='Milestone'){
          $rowType  = "mile";
        } else {
          $rowType  = "row";
        }
        $wbs=$line['wbssortable'];
        $wbsTest=$wbs;
        $level=1;
        while (strlen($wbsTest)>3) {
        	$wbsTest=substr($wbsTest,0,strlen($wbsTest)-4);
        	if (array_key_exists($wbsTest, $wbsLevelArray)) {
        		$level=$wbsLevelArray[$wbsTest]+1;
        		$wbsTest="";
        	}
        }
        $wbsLevelArray[$wbs]=$level;
        //$level=(strlen($wbs)+1)/4;
        $tab="";
        for ($i=1;$i<$level;$i++) {
          $tab.='<span class="ganttSep" >&nbsp;&nbsp;&nbsp;&nbsp;</span>';
        }
        $pName=($showWbs)?$line['wbs']." ":"";
        $pName.= htmlEncode($line['refname']);
        
        $durationNumeric=($rowType=='mile' or $pStart=="" or $pEnd=="")?'-':workDayDiffDates($pStart, $pEnd);
        $duration=$durationNumeric . "&nbsp;" . i18n("shortDay");
        //echo '<TR class="dojoDndItem ganttTask' . $rowType . '" style="margin: 0px; padding: 0px;">';

        if ($closedWbs and $closedWbs!=$line['wbssortable']) {
          //echo ' display:none;';
          continue;
        }
		if($outMode != 'csv') {
        echo '<TR style="height:18px;' ;

        echo '">';
        echo '  <TD class="reportTableData" style="border-right:0px;' . $compStyle . '">'.formatIcon($line['reftype'], 16).'</TD>';
        echo '  <TD class="reportTableData" style="border-left:0px; text-align: left;' . $compStyle . '"><span class="nobr">' . $tab ;
        echo '<span style="width: 16px;height:100%;vertical-align:middle;">';
        if ($pGroup) {
          if ($collapsed) {
            echo '<img style="width:12px" src="../view/css/images/plus.gif" />';
          } else {
            echo '<img style="width:12px" src="../view/css/images/minus.gif" />';
          }
        } else {
          if ($line['reftype']=='Milestone') {
            echo '<img style="width:12px" src="../view/css/images/mile.gif" />';
          } else {
            echo '<img style="width:12px" src="../view/css/images/none.gif" />';
          }
        }
        //<div style="float: left;width:16px;">&nbsp;</div></span>';
        echo '</span>&nbsp;';
        echo $pName . '</span></TD>';
        foreach ($sortArray as $col) {
          if (isset($columnsDescription[$col]) and $columnsDescription[$col]['show']!=1) continue;
          if ($col=='ValidatedWork') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' . Work::displayWorkWithUnit($line["validatedwork"])  . '</TD>' ;
          if ($col=='AssignedWork') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  Work::displayWorkWithUnit($line["assignedwork"])  . '</TD>' ;
          if ($col=='RealWork') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  Work::displayWorkWithUnit($line["realwork"])  . '</TD>' ;
          if ($col=='LeftWork') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  Work::displayWorkWithUnit($line["leftwork"])  . '</TD>' ;
          if ($col=='PlannedWork') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  Work::displayWorkWithUnit($line["plannedwork"])  . '</TD>' ;
          if ($col=='ValidatedCost') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' . costFormatter($line["validatedcost"])  . '</TD>' ;
          if ($col=='AssignedCost') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  costFormatter($line["assignedcost"])  . '</TD>' ;
          if ($col=='RealCost') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  costFormatter($line["realcost"])  . '</TD>' ;
          if ($col=='LeftCost') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  costFormatter($line["leftcost"])  . '</TD>' ;
          if ($col=='PlannedCost') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  costFormatter($line["plannedcost"])  . '</TD>' ;
          if ($col=='Type') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' . ($line["type"])  . '</TD>' ;
          if ($col=='IdStatus') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' .  ($line["status"])  . '</TD>' ;
          if ($col=='Duration') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' . $duration  . '</TD>' ;
          if ($col=='Progress') echo '  <TD class="reportTableData" style="' . $compStyle . '" >' . percentFormatter($progress) . '</TD>' ;
          if ($col=='StartDate') echo '  <TD class="reportTableData" style="' . $compStyle . '">'  . (($pStart)?dateFormatter($pStart):'-') . '</TD>' ;
          if ($col=='EndDate') echo '  <TD class="reportTableData" style="' . $compStyle . '">'  . (($pEnd)?dateFormatter($pEnd):'-') . '</TD>' ;
          if ($col=='Resource') echo '  <TD class="reportTableData" style="text-align:left;' . $compStyle . '" >' . $line["resource"]  . '</TD>' ;
          if ($col=='Priority') echo '  <TD class="reportTableData" style="text-align:center;' . $compStyle . '" >' . $line["priority"]  . '</TD>' ;
          if ($col=='IdPlanningMode') echo '  <TD class="reportTableData" style="text-align:left;' . $compStyle . '" ><span class="nobr">' . SqlList::getNameFromId('PlanningMode', $line["idplanningmode"])  . '</span></TD>' ;
        }
        if ($pGroup) {
          $pColor='#505050;';
          //$pBackground='background:#505050 url(../view/img/grey.png) repeat-x;';
          $pBackground='background-color:#505050;';
        } else {
        	if ($line['notplannedwork']>0) {        		
        		$pColor='#9933CC';
        		$pBackground='background-color:#9933CC;';
        	} else if (trim($line['validatedenddate'])!="" && $line['validatedenddate'] < $pEnd) {
            $pColor='#BB5050';
            //$pBackground='background:#BB5050 url(../view/img/red.png) repeat-x;';
            $pBackground='background-color:#BB5050;';
          } else  {
            $pColor="#50BB50";
            //$pBackground='background:#50BB50 url(../view/img/green.png) repeat-x;';
            $pBackground='background-color:#50BB50;';
          }
        }
        $dispCaption=false;
        for ($i=0;$i<$numDays;$i++) {
          $color=$bgColor;
          $noBorder="border-left: 0px;";
          if ($format=='month') {
            $fontSize='90%';
            if ( $i<($numDays-1) and substr($days[($i+1)],-2)!='01' ) {
              $noBorder="border-left: 0px;border-right: 0px;";
            }
          } else  if ($format=='quarter') {
            $fontSize='90%';
            if ( substr($days[($i)],-2)!='26' or (substr($days[($i)],5,2)!='03' and substr($days[($i)],5,2)!='06' and substr($days[($i)],5,2)!='09' and substr($days[($i)],5,2)!='12') ) {
               $noBorder="border-left: 0px;border-right: 0px;";
            }
          } else if($format=='week') {
            $fontSize='90%';
            if ( ( ($i+1) % $colUnit)!=0) {
              $noBorder="border-left: 0px;border-right: 0px;";
            }
          } else if ($format=='day') {
            $fontSize='150%';
            $color=($openDays[$i]==1)?$bgColor:'background-color:' . $weekendColor . ';';
          }
          $height=($pGroup)?'8':'12';
          if ($days[$i]>=$pStart and $days[$i]<=$pEnd) {
            if ($rowType=="mile") {
              echo '<td class="reportTableData" style="font-size: ' . $fontSize . ';' . $color . $noBorder . ';color:' . $pColor . ';">';
              if($progress < 100) {
                echo '&loz;' ;
              } else {
                echo '&diams;' ;
              }
            } else {
              $subHeight=round((18-$height)/2);
              echo '<td class="reportTableData" style="width:' . $width .';padding:0px;' . $color . '; vertical-align: middle;' . $noBorder . '">';
              if ($pGroup and ($days[$i]==$pStart or $days[$i]==$pEnd) and $outMode!='pdf') {
                echo '<div class="ganttTaskgroupBarExtInvisible" style="float:left; height:4px"></div>';
              }
              echo '<table width="100%" >';
              echo '<tr height="' . $height . 'px"><td style="width:100%; ' . $pBackground . 'height:' .  $height . 'px;"></td></tr>';
              echo '</table>';
              if ($pGroup and $days[$i]==$pStart and $outMode!='pdf') {
                if ($format=='quarter' or $format=='month') {
                  echo '<div class="" style="float:left; height:4px"></div>';
                } else { 
                  echo '<div class="ganttTaskgroupBarExt" style="float:left; height:4px"></div>'
                  . '<div class="ganttTaskgroupBarExt" style="float:left; height:3px"></div>'
                  . '<div class="ganttTaskgroupBarExt" style="float:left; height:2px"></div>'
                  . '<div class="ganttTaskgroupBarExt" style="float:left; height:1px"></div>';
                }
              }
              if ($pGroup and $days[$i]==$pEnd and $outMode!='pdf') {
                if ($format=='quarter' or $format=='month') {
                  echo '<div class="" style="float:left; height:4px"></div>';
                } else { 
                  echo '<div class="ganttTaskgroupBarExt" style="float:right; height:4px"></div>'
	                . '<div class="ganttTaskgroupBarExt" style="float:right; height:3px"></div>'
	                . '<div class="ganttTaskgroupBarExt" style="float:right; height:2px"></div>'
	                . '<div class="ganttTaskgroupBarExt" style="float:right; height:1px"></div>';
                }
	            }
              $dispCaption=($showResource)?true:false;
            }
          } else {
            echo '<td class="reportTableData" width="' . $width .'" style="width: ' . $width . $color . $noBorder . '">';
          }
          echo '</td>';
          if ($format=="quarter") {
            $dom=intval(substr($days[$i],8,2));
            if ($dom>=26) {
              $lastDayOfMonth=date('t',strtotime($days[$i]));
              $i=array_search(substr($days[$i],0,8).$lastDayOfMonth,$days);
            } else {
              $i+=4;
            }
          }
        }
        echo '</TR>';
      } else {
          echo i18n($line['reftype']) . $csvSep . $line['refid'] . $csvSep . html_entity_decode(strip_tags($tab), ENT_QUOTES, 'UTF-8') . html_entity_decode($pName, ENT_QUOTES, 'UTF-8') . $csvSep;
          foreach ($sortArray as $col) {          
            if (isset($columnsDescription[$col]) and $columnsDescription[$col]['show']!=1) continue;
            if ($col=='ValidatedWork') echo formatNumericOutput(Work::displayWork($line["validatedwork"]))  . $csvSep;
            if ($col=='AssignedWork') echo formatNumericOutput(Work::displayWork($line["assignedwork"]))  . $csvSep;
            if ($col=='RealWork') echo formatNumericOutput(Work::displayWork($line["realwork"]))  . $csvSep;
            if ($col=='LeftWork') echo formatNumericOutput(Work::displayWork($line["leftwork"]))  . $csvSep;
            if ($col=='PlannedWork') echo formatNumericOutput(Work::displayWork($line["plannedwork"]))  . $csvSep;
            if ($col=='ValidatedCost') echo formatNumericOutput($line["validatedcost"])  . $csvSep;
            if ($col=='AssignedCost') echo formatNumericOutput($line["assignedcost"])  . $csvSep;
            if ($col=='RealCost') echo formatNumericOutput($line["realcost"])  . $csvSep;
            if ($col=='LeftCost') echo formatNumericOutput($line["leftcost"])  . $csvSep;
            if ($col=='PlannedCost') echo formatNumericOutput($line["plannedcost"])  . $csvSep;
            if ($col=='Type') echo $line["type"]  . $csvSep;
            if ($col=='IdStatus') echo $line["status"]  . $csvSep . $line["statuscolor"]  . $csvSep;
            if ($col=='Duration') echo $durationNumeric . $csvSep;
            if ($col=='Progress') echo $progress . $csvSep;
            if ($col=='StartDate') echo (($pStart)?dateFormatter($pStart):'-'). $csvSep;
            if ($col=='EndDate') echo (($pEnd)?dateFormatter($pEnd):'-'). $csvSep;
            if ($col=='Resource') echo strip_tags($line["resource"])  . $csvSep;
            if ($col=='Priority') echo $line["priority"]  . $csvSep;
            if ($col=='IdPlanningMode') echo SqlList::getNameFromId('PlanningMode', $line["idplanningmode"])  . $csvSep;
          }
          echo "\n";
		}
      }
    }
  	if($outMode != 'csv') {
  	  echo "</table></div>";
  	}
  }

  function exportGantt($result) {
    global $nbQueriedRows,$applyFilter,$arrayRestrictWbs;
  	$paramDbDisplayName=Parameter::getGlobalParameter('paramDbDisplayName');
  	$currency=Parameter::getGlobalParameter('currency');
  	$currencyPosition=Parameter::getGlobalParameter('currencyPosition');
  	$nl="\n";
  	$hoursPerDay=Parameter::getGlobalParameter('dayTime');
    $startDate=date('Y-m-d');
    $startAM=Parameter::getGlobalParameter('startAM') . ':00';
    $endAM=Parameter::getGlobalParameter('endAM') . ':00';
    $startPM=Parameter::getGlobalParameter('startPM') . ':00';
    $endPM=Parameter::getGlobalParameter('endPM') . ':00';
    $name="export_planning_" . date('Ymd_His') . ".xml";
    $now=date('Y-m-d').'T'.date('H:i:s');
    if (array_key_exists('startDate',$_REQUEST)) {
      $startDate=$_REQUEST['startDate'];
	    Security::checkValidDateTime($startDate);
    }
    $endDate='';
    if (array_key_exists('endDate',$_REQUEST)) {
      $endDate=$_REQUEST['endDate'];
	    Security::checkValidDateTime($endDate);
    }
    $maxDate = '';
    $minDate = '';
    $resultArray=array();
    if ($nbQueriedRows > 0) {
      while ($line = Sql::fetchLine($result)) {
      	$line=array_change_key_case($line,CASE_LOWER);
      	if ($applyFilter and !isset($arrayRestrictWbs[$line['wbssortable']])) continue; // Filter applied and item is not selected and not a parent of selected
        $pStart="";
        $pStart=(trim($line['initialstartdate'])!="")?$line['initialstartdate']:$pStart;
        $pStart=(trim($line['validatedstartdate'])!="")?$line['validatedstartdate']:$pStart;
        $pStart=(trim($line['plannedstartdate'])!="")?$line['plannedstartdate']:$pStart;
        $pStart=(trim($line['realstartdate'])!="")?$line['realstartdate']:$pStart;
        if (trim($line['plannedstartdate'])!=""
        and trim($line['realstartdate'])!=""
        and $line['plannedstartdate']<$line['realstartdate'] ) {
          $pStart=$line['plannedstartdate'];
        }
        $pEnd="";
        $pEnd=(trim($line['initialenddate'])!="")?$line['initialenddate']:$pEnd;
        $pEnd=(trim($line['validatedenddate'])!="")?$line['validatedenddate']:$pEnd;
        $pEnd=(trim($line['plannedenddate'])!="")?$line['plannedenddate']:$pEnd;
        $pEnd=(trim($line['realenddate'])!="")?$line['realenddate']:$pEnd;
        if ($line['reftype']=='Milestone') {
          $pStart=$pEnd;
        }
        if (! $pStart) $pStart=date('Y-m-d');
        if (! $pEnd) $pEnd=date('Y-m-d');
        $line['pstart']=$pStart;
        $line['pend']=$pEnd;
        $line['pduration']=workDayDiffDates($pStart,$pEnd);
        $resultArray[]=$line;
        if ($maxDate=='' or $maxDate<$pEnd) {$maxDate=$pEnd;}
        if ($minDate=='' or $minDate>$pStart) {$minDate=$pStart;}
      }
      if ($endDate and $maxDate>$endDate) {
        $maxDate=$endDate;
      }
    }
    $res=New Resource();
    $resourceList=$res->getSqlElementsFromCriteria(array(), false, false, " id asc");

    echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . $nl;
    echo '<Project xmlns="http://schemas.microsoft.com/project">' . $nl;
    echo '<Name>' . htmlEncode($name,'xml') . '</Name>' . $nl;
    echo '<Title>' . htmlEncode($paramDbDisplayName,'xml') . '</Title>' . $nl;
    echo '<CreationDate>' . $now . '</CreationDate>' . $nl;
    echo '<LastSaved>' . $now . '</LastSaved>' . $nl;
    echo '<ScheduleFromStart>1</ScheduleFromStart>' . $nl;
    echo '<StartDate>' . $minDate . 'T00:00:00</StartDate>' . $nl;
    echo '<FinishDate>' . $maxDate . 'T00:00:00</FinishDate>' . $nl;
    echo '<FYStartDate>1</FYStartDate>' . $nl;
    echo '<CriticalSlackLimit>0</CriticalSlackLimit>' . $nl;
    echo '<CurrencyDigits>2</CurrencyDigits>' . $nl;
    echo '<CurrencySymbol>' . $currency . '</CurrencySymbol>' . $nl;
    echo '<CurrencySymbolPosition>' . (($currencyPosition=='before')?'0':'1') . '</CurrencySymbolPosition>' . $nl;
    echo '<CalendarUID>1</CalendarUID>' . $nl;
    echo '<DefaultStartTime>' . $startAM . '</DefaultStartTime>' . $nl;
    echo '<DefaultFinishTime>' . $endPM . '</DefaultFinishTime>' . $nl;
    echo '<MinutesPerDay>' . ($hoursPerDay*60) . '</MinutesPerDay>' . $nl;
    echo '<MinutesPerWeek>' . ($hoursPerDay*60*5) . '</MinutesPerWeek>' . $nl;
    echo '<DaysPerMonth>20</DaysPerMonth>' . $nl;
    echo '<DefaultTaskType>1</DefaultTaskType>' . $nl;
    echo '<DefaultFixedCostAccrual>2</DefaultFixedCostAccrual>' . $nl;
    echo '<DefaultStandardRate>10</DefaultStandardRate>' . $nl;
    echo '<DefaultOvertimeRate>15</DefaultOvertimeRate>' . $nl;
    echo '<DurationFormat>7</DurationFormat>' . $nl;
    echo '<WorkFormat>3</WorkFormat>' . $nl;
    echo '<EditableActualCosts>0</EditableActualCosts>' . $nl;
    echo '<HonorConstraints>0</HonorConstraints>' . $nl;
    // echo '<EarnedValueMethod>0</EarnedValueMethod>' . $nl;
    echo '<InsertedProjectsLikeSummary>0</InsertedProjectsLikeSummary>' . $nl;
    echo '<MultipleCriticalPaths>0</MultipleCriticalPaths>' . $nl;
    echo '<NewTasksEffortDriven>0</NewTasksEffortDriven>' . $nl;
    echo '<NewTasksEstimated>1</NewTasksEstimated>' . $nl;
    echo '<SplitsInProgressTasks>0</SplitsInProgressTasks>' . $nl;
    echo '<SpreadActualCost>0</SpreadActualCost>' . $nl;
    echo '<SpreadPercentComplete>0</SpreadPercentComplete>' . $nl;
    echo '<TaskUpdatesResource>1</TaskUpdatesResource>' . $nl;
    echo '<FiscalYearStart>0</FiscalYearStart>' . $nl;
    echo '<WeekStartDay>1</WeekStartDay>' . $nl;
    echo '<MoveCompletedEndsBack>0</MoveCompletedEndsBack>' . $nl;
    echo '<MoveRemainingStartsBack>0</MoveRemainingStartsBack>' . $nl;
    echo '<MoveRemainingStartsForward>0</MoveRemainingStartsForward>' . $nl;
    echo '<MoveCompletedEndsForward>0</MoveCompletedEndsForward>' . $nl;
    echo '<BaselineForEarnedValue>0</BaselineForEarnedValue>' . $nl;
    echo '<AutoAddNewResourcesAndTasks>1</AutoAddNewResourcesAndTasks>' . $nl;
    echo '<CurrentDate>' . $now . '</CurrentDate>' . $nl;
    echo '<MicrosoftProjectServerURL>1</MicrosoftProjectServerURL>' . $nl;
    echo '<Autolink>1</Autolink>' . $nl;
    echo '<NewTaskStartDate>0</NewTaskStartDate>' . $nl;
    echo '<DefaultTaskEVMethod>0</DefaultTaskEVMethod>' . $nl;
    echo '<ProjectExternallyEdited>0</ProjectExternallyEdited>' . $nl;
    echo '<ExtendedCreationDate>1984-01-01T00:00:00</ExtendedCreationDate>' . $nl;
    echo '<ActualsInSync>0</ActualsInSync>' . $nl;
    echo '<RemoveFileProperties>0</RemoveFileProperties>' . $nl;
    echo '<AdminProject>0</AdminProject>' . $nl;
    echo '<OutlineCodes/>' . $nl;
    echo '<WBSMasks/>' . $nl;
    echo '<ExtendedAttributes/>' . $nl;
    /*<ExtendedAttributes>
        <ExtendedAttribute>
            <FieldID>188743731</FieldID>
            <FieldName>Text1</FieldName>
        </ExtendedAttribute>
    </ExtendedAttributes>*/
    echo '<Calendars>' . $nl;
    echo '<Calendar>' . $nl;
    echo '<UID>0</UID>' . $nl;
    echo '<Name>Standard</Name>' . $nl;
    echo '<IsBaseCalendar>1</IsBaseCalendar>' . $nl;
    echo '<BaseCalendarUID>-1</BaseCalendarUID>' . $nl;
    echo '<WeekDays>' . $nl;
    for ($i=1;$i<=7;$i++) {
      echo '<WeekDay>' . $nl;
      echo '<DayType>' . $i . '</DayType>' . $nl;
      if (($i==1 or $i==7)) {
      	echo '<DayWorking>0</DayWorking>' . $nl;
      } else {
	      echo '<DayWorking>1</DayWorking>' . $nl;
	      echo '<WorkingTimes>' . $nl;
	      echo '<WorkingTime>' . $nl;
	      echo '<FromTime>' . $startAM . '</FromTime>' . $nl;
	      echo '<ToTime>' . $endAM . '</ToTime>' . $nl;
	      echo '</WorkingTime>' . $nl;
	      echo '<WorkingTime>' . $nl;
	      echo '<FromTime>' . $startPM . '</FromTime>' . $nl;
	      echo '<ToTime>' . $endPM . '</ToTime>' . $nl;
	      echo '</WorkingTime>' . $nl;
	      echo '</WorkingTimes>' . $nl;
      }
      echo '</WeekDay>' . $nl;
    }
    echo ' </WeekDays>' . $nl;
    echo '</Calendar>' . $nl;
    foreach ($resourceList as $resource) {
    	echo "<Calendar>" . $nl;
      echo "<UID>" . htmlEncode($resource->id,'xml') . "</UID>" . $nl;
      echo "<Name>" . htmlEncode($resource->name,'xml') . "</Name>" . $nl;
      echo "<IsBaseCalendar>0</IsBaseCalendar>" . $nl;
      echo "<BaseCalendarUID>0</BaseCalendarUID>" . $nl;
      echo "</Calendar>" . $nl;
    }
    echo '</Calendars>' . $nl;
    echo '<Tasks>' . $nl;
    $cpt=0;
    $arrayTask=array();
    foreach ($resultArray as $line) {
    	$cpt++;
    	$arrayTask[$line['reftype'].'#'.$line['refid']]=$line['id'];
    	$pct=($line['plannedwork']>0)?round(100*$line['realwork']/$line['plannedwork'],0):'';
      echo '<Task>' . $nl;
      echo '<UID>' . $line['id'] . '</UID>' . $nl;
      echo '<ID>' . $cpt . '</ID>' . $nl;  // TODO : should be order of the tack in the list
      echo '<Name>' . htmlEncode($line['refname'],'xml') . '</Name>' . $nl;
      echo '<Type>1</Type>' . $nl; // TODO : 0=Fixed Units, 1=Fixed Duration, 2=Fixed Work.
      echo '<IsNull>0</IsNull>' . $nl;
      echo '<WBS>' . $line['wbs'] . '</WBS>' . $nl;
      echo '<OutlineNumber>' . $line['wbs'] . '</OutlineNumber>' . $nl;
      echo '<OutlineLevel>' . (substr_count($line['wbs'],'.')+1) . '</OutlineLevel>' . $nl;
      echo '<Priority>' . $line['priority'] . '</Priority>' . $nl;
      echo '<Start>' . $line['pstart'] . 'T' . $startAM . '</Start>' . $nl;
      echo '<Finish>' . $line['pend'] . 'T' . $endPM . '</Finish>' . $nl;
      echo '<Duration>' . formatDuration($line['pduration'],$hoursPerDay) . '</Duration>' . $nl;
      echo '<DurationFormat>7</DurationFormat>' . $nl;
      echo '<Work>PT' . round($line['plannedwork']*$hoursPerDay,0) . 'H0M0S</Work>' . $nl;
      //echo '<Stop>' . $line['pstart'] . 'T' . $startAM . '</Stop>' . $nl;
      //echo '<Resume>' . $line['pstart'] . 'T' . $startAM . '</Resume>' . $nl;
      echo '<ResumeValid>0</ResumeValid>' . $nl;
      echo '<EffortDriven>1</EffortDriven>' . $nl;
      echo '<Recurring>0</Recurring>' . $nl;
      echo '<OverAllocated>0</OverAllocated>' . $nl;
      echo '<Estimated>0</Estimated>' . $nl;
      echo '<Milestone>' . (($line['reftype']=='Milestone')?'1':'0') . '</Milestone>' . $nl;
      echo '<Summary>' . (($line['elementary'])?'0':'1') . '</Summary>' . $nl;
      echo '<Critical>0</Critical>' . $nl;
      echo '<IsSubproject>0</IsSubproject>' . $nl;
      echo '<IsSubprojectReadOnly>0</IsSubprojectReadOnly>' . $nl;
      echo '<ExternalTask>0</ExternalTask>' . $nl;
      echo '<EarlyStart>' . $line['pstart'] . 'T' . $startAM . '</EarlyStart>' . $nl;
      echo '<EarlyFinish>' . $line['pend'] . 'T' . $endPM . '</EarlyFinish>' . $nl;
      echo '<LateStart>' . $line['pstart'] . 'T' . $startAM . '</LateStart>' . $nl;
      echo '<LateFinish>' . $line['pend'] . 'T' . $endPM . '</LateFinish>' . $nl;
      echo '<StartVariance>0</StartVariance>' . $nl;
      echo '<FinishVariance>0</FinishVariance>' . $nl;
      echo '<WorkVariance>0</WorkVariance>' . $nl;
      echo '<FreeSlack>0</FreeSlack>' . $nl;
      echo '<TotalSlack>0</TotalSlack>' . $nl;
      echo '<FixedCost>0</FixedCost>' . $nl;
      echo '<FixedCostAccrual>2</FixedCostAccrual>' . $nl;
      echo '<PercentComplete>' . $pct .'</PercentComplete>' . $nl;
      echo '<PercentWorkComplete>' . $pct .'</PercentWorkComplete>' . $nl;
      echo '<Cost>0</Cost>' . $nl;
      echo '<OvertimeCost>0</OvertimeCost>' . $nl;
      echo '<OvertimeWork>PT0H0M0S</OvertimeWork>' . $nl;
      echo '<ActualStart>' .  $line['pstart'] . 'T' . $startAM . '</ActualStart>' . $nl;
      echo '<ActualDuration>PT0H0M0S</ActualDuration>' . $nl;
      echo '<ActualCost>0</ActualCost>' . $nl;
      echo '<ActualOvertimeCost>0</ActualOvertimeCost>' . $nl;
      echo '<ActualWork>PT' . round($line['realwork']*$hoursPerDay,0) . 'H0M0S</ActualWork>' . $nl;
      echo '<ActualOvertimeWork>PT0H0M0S</ActualOvertimeWork>' . $nl;
      echo '<RegularWork>PT' . round($line['plannedwork']*$hoursPerDay,0) . 'H0M0S</RegularWork>' . $nl;
      echo '<RemainingDuration>PT' .  round($line['plannedduration']*$hoursPerDay,0) . 'H0M0S</RemainingDuration>' . $nl;
      echo '<RemainingCost>0</RemainingCost>' . $nl;
      echo '<RemainingWork>PT' . round($line['leftwork']*$hoursPerDay,0) . 'H0M0S</RemainingWork>' . $nl;
      echo '<RemainingOvertimeCost>0</RemainingOvertimeCost>' . $nl;
      echo '<RemainingOvertimeWork>PT0H0M0S</RemainingOvertimeWork>' . $nl;
      echo '<ACWP>0</ACWP>' . $nl;
      echo '<CV>0</CV>' . $nl;
      echo '<ConstraintType>' . (($line['elementary'])?'0':'0') . '</ConstraintType>' . $nl;
      echo '<CalendarUID>-1</CalendarUID>' . $nl;
      if ($line['elementary']) { echo '<ConstraintDate>' . $line['pstart'] . 'T' . $startAM . '</ConstraintDate>' . $nl;}
      echo '<LevelAssignments>0</LevelAssignments>' . $nl;
      echo '<LevelingCanSplit>1</LevelingCanSplit>' . $nl;
      echo '<LevelingDelay>0</LevelingDelay>' . $nl;
      echo '<LevelingDelayFormat>8</LevelingDelayFormat>' . $nl;
      echo '<IgnoreResourceCalendar>0</IgnoreResourceCalendar>' . $nl;
      echo '<HideBar>0</HideBar>' . $nl;
      echo '<Rollup>0</Rollup>' . $nl;
      echo '<BCWS>0</BCWS>' . $nl;
      echo '<BCWP>0</BCWP>' . $nl;
      echo '<PhysicalPercentComplete>0</PhysicalPercentComplete>' . $nl;
      echo '<EarnedValueMethod>0</EarnedValueMethod>' . $nl;
      /*<ExtendedAttribute>
        <FieldID>188743731</FieldID>
        <Value>lmk</Value>
        </ExtendedAttribute>*/
      //echo '<Active>1</Active>' . $nl;
      //echo '<Manual>0</Manual>' . $nl;
      echo '<ActualWorkProtected>PT0H0M0S</ActualWorkProtected>' . $nl;
      echo '<ActualOvertimeWorkProtected>PT0H0M0S</ActualOvertimeWorkProtected>' . $nl;
      $crit=array('successorId'=>$line['id']);
      $d=new Dependency();
      $depList=$d->getSqlElementsFromCriteria($crit,false);
      foreach ($depList as $dep) {
        echo '<PredecessorLink>' . $nl;
        echo '<PredecessorUID>' . htmlEncode($dep->predecessorId) . '</PredecessorUID>' . $nl;
        echo '<Type>1</Type>' . $nl;
        echo '<CrossProject>0</CrossProject>' . $nl;
        echo '<LinkLag>0</LinkLag>' . $nl;
        echo '<LagFormat>7</LagFormat>' . $nl;
        echo '</PredecessorLink>' . $nl;
      }
      echo '</Task>' . $nl;
    }
    echo '</Tasks>' . $nl;
    $arrayRessource=array();
    echo '<Resources>' . $nl;
    foreach ($resourceList as $resource) {
    	$arrayResource[$resource->id]=$resource;
      echo "<Resource>" . $nl;
      echo "<UID>" . htmlEncode($resource->id) . "</UID>" . $nl;
      echo "<ID>" . htmlEncode($resource->id) . "</ID>" . $nl;
      echo "<Name>" . htmlEncode($resource->name,'xml') . "</Name>" . $nl;
      echo "<Type>1</Type>" . $nl;
      echo "<IsNull>0</IsNull>" . $nl;
      echo "<Initials>" . htmlEncode($resource->initials,'xml') . "</Initials>" . $nl;
      echo "<Group>" . htmlEncode(SqlList::getNameFromId('Team',$resource->idTeam),'xml') . "</Group>" . $nl;
      echo "<WorkGroup>0</WorkGroup>" . $nl;
      echo "<EmailAddress>" . htmlEncode($resource->email,'xml') . "</EmailAddress>" . $nl;
      echo "<MaxUnits>" . htmlEncode($resource->capacity) . "</MaxUnits>" . $nl;
      echo "<PeakUnits>0</PeakUnits>" . $nl;
      echo "<OverAllocated>0</OverAllocated>" . $nl;
      echo "<CanLevel>1</CanLevel>" . $nl;
      echo "<AccrueAt>3</AccrueAt>" . $nl;
      echo "<Work>PT0H0M0S</Work>" . $nl;
      echo "<RegularWork>PT0H0M0S</RegularWork>" . $nl;
      echo "<OvertimeWork>PT0H0M0S</OvertimeWork>" . $nl;
      echo "<ActualWork>PT0H0M0S</ActualWork>" . $nl;
      echo "<RemainingWork>PT0H0M0S</RemainingWork>" . $nl;
      echo "<ActualOvertimeWork>PT0H0M0S</ActualOvertimeWork>" . $nl;
      echo "<RemainingOvertimeWork>PT0H0M0S</RemainingOvertimeWork>" . $nl;
      echo "<PercentWorkComplete>0</PercentWorkComplete>" . $nl;
      $rate=0;
      $critCost=array('idResource'=>$resource->id, 'endDate'=>null);
      $rc=new ResourceCost();
      $rcList=$rc->getSqlElementsFromCriteria($critCost, false, null, ' startDate desc');
      if (count($rcList)>0) {
      	$rate=($hoursPerDay)?round($rcList[0]->cost / $hoursPerDay,2):0;

      }
      echo "<StandardRate>" . $rate . "</StandardRate>" . $nl;
      echo "<StandardRateFormat>3</StandardRateFormat>" . $nl;
      echo "<Cost>0</Cost>" . $nl;
      echo "<OvertimeRate>0</OvertimeRate>" . $nl;
      echo "<OvertimeRateFormat>3</OvertimeRateFormat>" . $nl;
      echo "<OvertimeCost>0</OvertimeCost>" . $nl;
      echo "<CostPerUse>0</CostPerUse>" . $nl;
      echo "<ActualCost>0</ActualCost>" . $nl;
      echo "<ActualOvertimeCost>0</ActualOvertimeCost>" . $nl;
      echo "<RemainingCost>0</RemainingCost>" . $nl;
      echo "<RemainingOvertimeCost>0</RemainingOvertimeCost>" . $nl;
      echo "<WorkVariance>0</WorkVariance>" . $nl;
      echo "<CostVariance>0</CostVariance>" . $nl;
      echo "<SV>0</SV>" . $nl;
      echo "<CV>0</CV>" . $nl;
      echo "<ACWP>0</ACWP>" . $nl;
      echo "<CalendarUID>" . htmlEncode($resource->id) . "</CalendarUID>" . $nl;
      echo "<BCWS>0</BCWS>" . $nl;
      echo "<BCWP>0</BCWP>" . $nl;
      echo "<IsGeneric>0</IsGeneric>" . $nl;
      echo "<IsInactive>0</IsInactive>" . $nl;
      echo "<IsEnterprise>0</IsEnterprise>" . $nl;
      echo "<BookingType>0</BookingType>" . $nl;
      echo "<ActualWorkProtected>PT0H0M0S</ActualWorkProtected>" . $nl;
      echo "<ActualOvertimeWorkProtected>PT0H0M0S</ActualOvertimeWorkProtected>" . $nl;
      echo "<CreationDate></CreationDate>" . $nl;
      echo "</Resource>" . $nl;
    }
    echo "</Resources>" . $nl;
    $ass=new Assignment();
    $clauseWhere="";
    $lstAss=$ass->getSqlElementsFromCriteria(null, false, $clauseWhere, null, false);
    echo '<Assignments>' . $nl;
    foreach ($lstAss as $ass) {
    	if (array_key_exists($ass->refType . '#' . $ass->refId, $arrayTask)) {
    	  if (isset($arrayResource[$ass->idResource])) {
          $res=$arrayResource[$ass->idResource];
    	  } else {
    	    $res=new Resource($ass->idResource,true);
    	    $arrayResource[$ass->idResource]=$res;
    	  }
	      echo "<Assignment>" . $nl;
	      echo "<UID>" . htmlEncode($ass->id) . "</UID>" . $nl;
	      echo "<TaskUID>" . $arrayTask[$ass->refType . '#' . $ass->refId] . "</TaskUID>" . $nl;
	      echo "<ResourceUID>" . htmlEncode($ass->idResource) . "</ResourceUID>" . $nl;
	      //echo "<PercentWorkComplete>' (($ass->plannedWork)?round($ass->realWork/$ass->plannedWork*100,0):'0') . '</PercentWorkComplete>" . $nl;
	      //echo "<ActualCost>0</ActualCost>" . $nl;
	      //echo "<ActualOvertimeCost>0</ActualOvertimeCost>" . $nl;
	      //echo "<ActualOvertimeWork>PT0H0M0S</ActualOvertimeWork>" . $nl;
	      echo "<ActualStart>" . htmlEncode($ass->plannedStartDate) . "T" . $startAM . "</ActualStart>" . $nl;
	      //echo "<ActualWork>PT0H0M0S</ActualWork>" . $nl;
	      //echo "<ACWP>0</ACWP>" . $nl;
	      //echo "<Confirmed>0</Confirmed>" . $nl;
	      //echo "<Cost>0</Cost>" . $nl;
	      //echo "<CostRateTable>0</CostRateTable>" . $nl;
	      //echo "<CostVariance>0</CostVariance>" . $nl;
	      //echo "<CV>0</CV>" . $nl;
	      //echo "<Delay>0</Delay>" . $nl;
	      echo "<Finish>" . htmlEncode($ass->plannedEndDate) . "T" . $endPM . "</Finish>" . $nl;
	      //echo "<FinishVariance>0</FinishVariance>" . $nl;
	      //echo "<WorkVariance>0</WorkVariance>" . $nl;
	      //echo "<HasFixedRateUnits>1</HasFixedRateUnits>" . $nl;
	      //echo "<FixedMaterial>0</FixedMaterial>" . $nl;
	      //echo "<LevelingDelay>0</LevelingDelay>" . $nl;
	      //echo "<LevelingDelayFormat>7</LevelingDelayFormat>" . $nl;
	      //echo "<LinkedFields>0</LinkedFields>" . $nl;
	      //echo "<Milestone>0</Milestone>" . $nl;
	      //echo "<Overallocated>0</Overallocated>" . $nl;
	      //echo "<OvertimeCost>0</OvertimeCost>" . $nl;
	      //echo "<OvertimeWork>PT0H0M0S</OvertimeWork>" . $nl;
	      echo "<RegularWork>PT" . round($ass->plannedWork*$hoursPerDay,0) . "H0M0S</RegularWork>" . $nl;
	      //echo "<RemainingCost>0</RemainingCost>" . $nl;
	      //echo "<RemainingOvertimeCost>0</RemainingOvertimeCost>" . $nl;
	      //echo "<RemainingOvertimeWork>PT0H0M0S</RemainingOvertimeWork>" . $nl;
	      echo "<RemainingWork>PT" . round($ass->leftWork*$hoursPerDay,0) ."H0M0S</RemainingWork>" . $nl;
	      //echo "<ResponsePending>0</ResponsePending>" . $nl;
	      //echo "<Start>2011-11-17T08:00:00</Start>" . $nl;
	      //echo "<Stop>2011-11-17T08:00:00</Stop>" . $nl;
	      //echo "<Resume>2011-11-17T08:00:00</Resume>" . $nl;
	      //echo "<StartVariance>0</StartVariance>" . $nl;
	      echo "<Units>" . round(($res->capacity * $ass->rate / 100),1) . "</Units>" . $nl;
	      //echo "<UpdateNeeded>0</UpdateNeeded>" . $nl;
	      //echo "<VAC>0</VAC>" . $nl;
	      echo "<Work>PT" . round($ass->plannedWork*$hoursPerDay,0) . "H0M0S</Work>" . $nl;
	      //echo "<WorkContour>0</WorkContour>" . $nl;
	      //echo "<BCWS>0</BCWS>" . $nl;
	      //echo "<BCWP>0</BCWP>" . $nl;
	      //echo "<BookingType>0</BookingType>" . $nl;
	      //echo "<ActualWorkProtected>PT0H0M0S</ActualWorkProtected>" . $nl;
	      //echo "<ActualOvertimeWorkProtected>PT0H0M0S</ActualOvertimeWorkProtected>" . $nl;
	      //echo "<CreationDate>2011-11-18T21:06:00</CreationDate>" . $nl;
	      //echo "<TimephasedData>" . $nl;
	      //echo "<Type>1</Type>" . $nl;
	      //echo "<UID>1</UID>" . $nl;
	      //echo "<Start>" . htmlEncode($ass->plannedStartDate) . "T08:00:00</Start>" . $nl;
	      //echo "<Finish>" . htmlEncode($ass->plannedEndDate) . "T08:00:00</Finish>" . $nl;
	      //echo "<Unit>2</Unit>" . $nl;
	      //echo "<Value>PT8H0M0S</Value>" . $nl;
	      //echo "</TimephasedData>" . $nl;
	      echo "</Assignment>" . $nl;
    	}
    }
    echo "</Assignments>" . $nl;
    echo '</Project>' . $nl;
  }

  function formatDuration($duration, $hoursPerDay) {
    $hourDuration=$duration*$hoursPerDay;
  	$res = 'PT' . round($hourDuration,0) . 'H0M0S';
  	return $res;
  }
?>
