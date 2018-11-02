<?php 
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

/** ============================================================================
 * Project is the main object of the project managmement.
 * Almost all other objects are linked to a given project.
 */ 
require_once('_securityCheck.php');
class PlannedWork extends GeneralWork {

  public $_noHistory;
    
  // List of fields that will be exposed in general user interface
  
  // Define the layout that will be used for lists
  private static $_layout='
    <th field="id" formatter="numericFormatter" width="10%" ># ${id}</th>
    <th field="nameResource" formatter="thumbName22" width="35%" >${resourceName}</th>
    <th field="nameProject" width="35%" >${projectName}</th>
    <th field="rate" width="15%" formatter="percentFormatter">${rate}</th>  
    <th field="idle" width="5%" formatter="booleanFormatter" >${idle}</th>
    ';
  
   /** ==========================================================================
   * Constructor
   * @param $id the id of the object in the database (null if not stored yet)
   * @return void
   */ 
  function __construct($id = NULL, $withoutDependentObjects=false) {
    parent::__construct($id,$withoutDependentObjects);
  }

   /** ==========================================================================
   * Destructor
   * @return void
   */ 
  function __destruct() {
    parent::__destruct();
  }

// ============================================================================**********
// GET STATIC DATA FUNCTIONS
// ============================================================================**********
  
  /** ==========================================================================
   * Return the specific layout
   * @return the layout
   */
  protected function getStaticLayout() {
    return self::$_layout;
  }


// ============================================================================**********
// GET VALIDATION SCRIPT
// ============================================================================**********
  
  /** ==========================================================================
   * Return the validation sript for some fields
   * @return the validation javascript (for dojo frameword)
   */
  public function getValidationScript($colName) {
    $colScript = parent::getValidationScript($colName);

    if ($colName=="idle") {   
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= '  if (this.checked) { ';
      $colScript .= '    if (dijit.byId("PlanningElement_realEndDate").get("value")==null) {';
      $colScript .= '      dijit.byId("PlanningElement_realEndDate").set("value", new Date); ';
      $colScript .= '    }';
      $colScript .= '  } else {';
      $colScript .= '    dijit.byId("PlanningElement_realEndDate").set("value", null); ';
      //$colScript .= '    dijit.byId("PlanningElement_realDuration").set("value", null); ';
      $colScript .= '  } '; 
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }
    return $colScript;
  }
  
// ============================================================================**********
// MISCELLANOUS FUNCTIONS
// ============================================================================**********
  
  /**
   * Run planning calculation for project, starting at start date
   * @static
   * @param string $projectId id of project to plan
   * @param string $startDate start date for planning
   * @return string result
   */

// ================================================================================================================================
// PLAN
// ================================================================================================================================

  public static function plan($projectIdArray, $startDate,$withCriticalPath=true) {
  	projeqtor_set_time_limit(300);
  	projeqtor_set_memory_limit('512M');
  	
  	if (!is_array($projectIdArray)) $projectIdArray=array($projectIdArray);
  	// Strict dependency means when B follows A (A->B), B cannot start same date as A ends, but only day after
  	$strictDependency=(Parameter::getGlobalParameter('dependencyStrictMode')=='NO')?false:true;
  	
  	//-- Manage cache
  	SqlElement::$_cachedQuery['Resource']=array();
  	SqlElement::$_cachedQuery['Project']=array();
  	SqlElement::$_cachedQuery['Affectation']=array();
  	SqlElement::$_cachedQuery['PlanningMode']=array();
  	
  	$workUnit=Work::getWorkUnit();
  	$hoursPerDay=Work::getHoursPerDay();
  	$hour=round(1/$hoursPerDay,10);
  	$halfHour=round(1/$hoursPerDay/2,10);
  	
    $withProjectRepartition=true;
    $result="";
    $startTime=time();
    $startMicroTime=microtime(true);
    $globalMaxDate=date('Y')+3 . "-12-31"; // Don't try to plan after Dec-31 of current year + 3
    $globalMinDate=date('Y')-1 . "-01-01"; // Don't try to plan before Jan-01 of current year -1
    
    $arrayPlannedWork=array();
    $arrayRealWork=array();
    $arrayAssignment=array();
    $arrayPlanningElement=array();

    //-- Controls (check that current user can run planning)
    $accessRightRead=securityGetAccessRight('menuActivity', 'read');
    $allProjects=false;
    if (count($projectIdArray)==1 and ! trim($projectIdArray[0])) $allProjects=true;
    if ($accessRightRead=='ALL' and $allProjects) {
      $listProj=explode(',',getVisibleProjectsList());
      if (count($listProj)-1 > Parameter::getGlobalParameter('maxProjectsToDisplay')) {
        $result=i18n('selectProjectToPlan');
        $result .= '<input type="hidden" id="lastPlanStatus" value="INVALID" />';
        echo '<div class="messageINVALID" >' . $result . '</div>';
        return $result;
      }
    }
    
    $daysPerWeek=7;
    if (Parameter::getGlobalParameter('OpenDaySunday')=='offDays') $daysPerWeek--;
    if (Parameter::getGlobalParameter('OpenDayMonday')=='offDays') $daysPerWeek--;
    if (Parameter::getGlobalParameter('OpenDayTuesday')=='offDays') $daysPerWeek;
    if (Parameter::getGlobalParameter('OpenDayWednesday')=='offDays') $daysPerWeek--;
    if (Parameter::getGlobalParameter('OpenDayThursday')=='offDays') $daysPerWeek--;
    if (Parameter::getGlobalParameter('OpenDayFriday')=='offDays') $daysPerWeek--;
    if (Parameter::getGlobalParameter('OpenDaySaturday')=='offDays') $daysPerWeek--;
    
    //-- Build in list to get a where clause : "idProject in ( ... )"

    $inClause="(";
    foreach ($projectIdArray as $projectId) {
      $proj=new Project($projectId,true);
      $inClause.=($inClause=="(")?'':' or ';
      $inClause.="idProject in " . transformListIntoInClause($proj->getRecursiveSubProjectsFlatList(true, true));
    }
    $inClause.=" )";
    //$inClause.=" and " . getAccesRestrictionClause('Activity',false);
    //-- Remove Projects with Fixed Planning flag
    $inClause.=" and idProject not in " . Project::getFixedProjectList() ;
    $user=getSessionUser();
    $inClause.=" and idProject in ". transformListIntoInClause($user->getListOfPlannableProjects());
    //-- Purge existing planned work
    $plan=new PlannedWork();
    $plan->purge($inClause);
    //-- #697 : moved the administrative project clause after the purge
    //-- Remove administrative projects
    $inClause.=" and idProject not in " . Project::getAdminitrativeProjectList() ;
    //-- Get the list of all PlanningElements to plan (includes Activity, Projects, Meetings, Test Sessions)
    $pe=new PlanningElement();
    $clause=$inClause;
    $order="wbsSortable asc";
    $list=$pe->getSqlElementsFromCriteria(null,false,$clause,$order,true);
    if (count($list)==0) {
      $result=i18n('planEmpty');
      $result.= '<input type="hidden" id="lastPlanStatus" value="INCOMPLETE" />';
      echo '<div class="messageINCOMPLETE" >' . $result . '</div>';
      return $result;
    }
    $fullListPlan=PlanningElement::initializeFullList($list);
    $listProjectsPriority=$fullListPlan['_listProjectsPriority'];
    unset($fullListPlan['_listProjectsPriority']);
    $listPlan=self::sortPlanningElements($fullListPlan, $listProjectsPriority);
    $resources=array();
    $a=new Assignment();
    $topList=array();
    $reserved=array();
    // Will constitute an array $reserved to be sure to reserve the availability of tasks as RECW that will be planned "after" predecessors to get start and end
    // $reserved[type='W'][idPE][idResource][day]=value         // sum of work to reserve for resource on week day for a given task
    // $reserved[type='W'][idPE]['start']=date                  // start date, that will be set when known
    // $reserved[type='W'][idPE]['end']=date                    // end date, that will be set when known
    // $reserved[type='W'][idPE]['pred'][idPE]['id']=idPE       // id of precedessor PlanningElement
    // $reserved[type='W'][idPE]['pred'][idPE]['delay']=delay   // Delay of dependency
    // $reserved[type='W'][idPE]['pred'][idPE]['type']=type     // type of dependency (E-E, E-S, S-S)
    // $reserved[type='W'][idPE]['succ'][idPE]['id']=idPE       // id of successor PlanningElement
    // $reserved[type='W'][idPE]['succ'][idPE]['delay']=delay   // Delay of dependency
    // $reserved[type='W'][idPE]['succ'][idPE]['type']=type     // type of dependency (E-E, E-S, S-S)
    // $reserved[type='W']['sum'][idResource][day]+=value       // sum of work to reserve for resource on week day
    // $reserved['allPreds'][idPE]=idPE                         // List of all PE who are predecessors of RECW task
    // $reserved['allSuccs'][idPE]=idPE                         // List of all PE who are successors of RECW task
    foreach ($listPlan as $plan) { // Store RECW to reserve avaialbility
      if (property_exists($plan, '_profile') and $plan->_profile=='RECW') { // $plan->_profile may not be set for top Project when calculating for all project (then $plan->id is null)
        $ar=new AssignmentRecurring();
        $artype=substr($plan->_profile,-1);
        $arList=$ar->getSqlElementsFromCriteria(array('refType'=>$plan->refType, 'refId'=>$plan->refId, 'type'=>$artype));
        if (!isset($reserved[$artype])) $reserved[$artype]=array();
        if (!isset($reserved[$artype][$plan->id])) $reserved[$artype][$plan->id]=array();
        if (!isset($reserved[$artype]['sum'])) $reserved[$artype]['sum']=array();
        foreach ($arList as $ar) {
          if (!isset($reserved[$artype][$plan->id][$ar->idResource])) $reserved[$artype][$plan->id][$ar->idResource]=array();
          if (!isset($reserved[$artype]['sum'][$ar->idResource])) $reserved[$artype]['sum'][$ar->idResource]=array();
          $reserved[$artype][$plan->id][$ar->idResource][$ar->day]=$ar->value;
          if (!isset($reseved[$artype]['sum'][$ar->idResource][$ar->day])) $reserved[$artype]['sum'][$ar->idResource][$ar->day]=0;
          $reserved[$artype]['sum'][$ar->idResource][$ar->day]+=$ar->value;
          if (!isset($reseved[$artype][$plan->id]['assignments'])) $reseved[$artype][$plan->id]['assignments']=array();
          $reseved[$artype][$plan->id]['assignments'][$ar->idAssignment]=$ar->idAssignment;
        }
        $reserved[$artype][$plan->id]['start']=null;
        $reserved[$artype][$plan->id]['end']=null;
        $reserved[$artype][$plan->id]['pred']=array();
        $reserved[$artype][$plan->id]['succ']=array();
        $crit="successorId=$plan->id or predecessorId=$plan->id";
        $dep=new Dependency();
        $depList=$dep->getSqlElementsFromCriteria(null, false, $crit);
        foreach ($depList as $dep ) {
          if ($dep->successorId==$plan->id) {
            $reserved[$artype][$plan->id]['pred'][$dep->predecessorId]=array('id'=>$dep->predecessorId,'delay'=>$dep->dependencyDelay, 'type'=>$dep->dependencyType);
            $reserved['allPreds'][$dep->predecessorId]=$dep->predecessorId;
          }
          if ($dep->predecessorId==$plan->id) {
            $reserved[$artype][$plan->id]['succ'][$dep->successorId]=array('id'=>$dep->successorId,'delay'=>$dep->dependencyDelay, 'type'=>$dep->dependencyType);
            $reserved['allSuccs'][$dep->successorId]=$dep->successorId;
          }
        }
      }
    }
    $arrayNotPlanned=array();
    $arrayWarning=array();
//-- Treat each PlanningElement ---------------------------------------------------------------------------------------------------
    foreach ($listPlan as $plan) {
      if (! $plan->id) {
        continue;
      }
    	$plan=$fullListPlan['#'.$plan->id];
      //-- Determine planning profile
      if ($plan->idle) {
      	$plan->_noPlan=true;
      	$fullListPlan=self::storeListPlan($fullListPlan,$plan);
      	continue;
      }
      if (isset($plan->_noPlan) and $plan->_noPlan) {
      	continue;
      }
      $startPlan=$startDate;
      $startFraction=0;
      $endPlan=null;
      $step=1;
      $profile=$plan->_profile;
      if ($profile=="ASAP" and $plan->assignedWork==0 and $plan->leftWork==0 and $plan->validatedDuration>0) {
        $profile="FDUR";
      }
      if ($profile=="REGUL" or $profile=="FULL" 
       or $profile=="HALF" or $profile=="QUART") { // Regular planning
        $startPlan=$plan->validatedStartDate;
        $endPlan=$plan->validatedEndDate;
        $step=1;
      } else if ($profile=="FDUR") { // Fixed duration
        // #V5.1.0 : removed this option
        // This leads to issue when saving validate dates : it fixed start, which may not be expected
        // If one want Fixed duration with fixed start, use regular beetween dates, or use milestone to define start
      	//if ($plan->validatedStartDate) {   
      	//  $startPlan=$plan->validatedStartDate;
      	//}
        if (isset($plan->isGlobal) and $plan->isGlobal and count($plan->_directPredecessorList)==0 )  {
          if ($plan->plannedEndDate>$startDate and !$plan->realEndDate) {
            $startPlan=$plan->plannedEndDate;
          } else if (! $plan->plannedEndDate and $plan->validatedEndDate>$startDate) {
            $startPlan=$plan->validatedEndDate;
          }
        } else if (count($plan->_directPredecessorList)==0 and $plan->validatedStartDate>$startDate and ! $plan->realStartDate and $plan->validatedStartDate) {
          $startPlan=$plan->validatedStartDate;
        }
        $step=1;
      } else if ($profile=="ASAP" or $profile=="GROUP") { // As soon as possible
        //$startPlan=$plan->validatedStartDate;
      	$startPlan=$startDate; // V4.5.0 : if validated is fixed, must not be concidered as "Must not start before"
      	$endPlan=null;
        $step=1;
      } else if ($profile=="ALAP") { // As late as possible (before end date)
          $startPlan=$plan->validatedEndDate;
          $endPlan=$startDate;
          $step=-1;         
      } else if ($profile=="FLOAT") { // Floating milestone
        if (count($plan->_predecessorListWithParent)==0 and $plan->validatedEndDate>$startDate and !$plan->realEndDate) $startPlan=$plan->validatedEndDate; 
        else $startPlan=$startDate;
        $endPlan=null;
        $step=1;
      } else if ($profile=="FIXED") { // Fixed milestone
        if ($plan->refType=='Milestone') {
          $startPlan=$plan->validatedEndDate;
          $plan->plannedStartDate=$plan->validatedEndDate;
          $plan->plannedEndDate=$plan->validatedEndDate;
          $fullListPlan=self::storeListPlan($fullListPlan,$plan);          
        } else {
          $startPlan=$plan->validatedStartDate;
          //$startFraction=$plan->validatedStartFraction; // TODO : implement control of time on meeting
        }
        $endPlan=$plan->validatedEndDate;
        $step=1;
      } else if ($profile=="START") { // Start not before validated date
        $startPlan=$plan->validatedStartDate;
      	$endPlan=null;
        $step=1;
        $profile='ASAP'; // Once start is set, treat as ASAP mode (as soon as possible)
      } else if ($profile=="RECW") {
        $plan->assignedWork=$plan->realWork;
        $plan->leftWork=0;
        $plan->plannedWork=$plan->realWork;
        $startPlan=null;
        if (isset($reserved['W'][$plan->id]['start']) and $reserved['W'][$plan->id]['start'] ) {
          $startPlan=$reserved['W'][$plan->id]['start'];
        } 
        if (isset($reserved['W'][$plan->id]['end'])   and $reserved['W'][$plan->id]['end'] ) {
          $endPlan=$reserved['W'][$plan->id]['end'];
        } 
        if (!$endPlan or !$startPlan) {
          $idPeProj=null;
          $curPe=$plan;
          while (!$idPeProj) {
            if (!$curPe->topId or !isset($fullListPlan['#'.$curPe->topId])) {
              $idPeProj=-1;
              break;
            }
            $topPe=$fullListPlan['#'.$curPe->topId];
            if ($topPe->refType=='Project') {
              $idPeProj=$topPe->id; // Will exit loop, after setting curPe
            }
            $curPe=$topPe;
          }
          if ($idPeProj>0) {
            if (!$endPlan) {
              if ($curPe->plannedEndDate) $endPlan=$curPe->plannedEndDate;
              else $endPlan=$curPe->validatedEndDate;
            } 
            if (!$startPlan) {
              if ($curPe->plannedStartDate) $startPlan=$curPe->plannedStartDate;
              else $startPlan=$curPe->validatedStartDate;
            }
          }
        }
        $plan->plannedStartDate=$startPlan;
        $plan->plannedEndDate=$endPlan;
        if ( (!$endPlan or !$startPlan) and isset($reseved[$artype][$plan->id]['assignments']) ) {
          foreach ($reseved[$artype][$plan->id]['assignments'] as $idAssignment) {
            $dates='';
            if (!isset($reserved['W'][$plan->id]['start']) or ! $reserved['W'][$plan->id]['start'] ) {
              $dates="'".i18n('colStartDate')."'";
            } 
            if (!isset($reserved['W'][$plan->id]['end']) or ! $reserved['W'][$plan->id]['end'] ) {
              if ($dates) $dates.=' '.mb_strtolower(i18n('AND')).' ';
              $dates.="'".i18n('colEndDate')."'";
            }
            $arrayNotPlanned[$idAssignment]=i18n('planImpossibleForREC',array($dates));
          }   
          $fullListPlan=self::storeListPlan($fullListPlan,$plan);
        }
      } else {
        $profile=="ASAP"; // Default is ASAP
        $startPlan=$startDate;
        $endPlan=null;
        $step=1;
      }
      //-- Take into accound predecessors
      $precList=$plan->_predecessorListWithParent;
      foreach ($precList as $precId=>$precValArray) { // $precValArray = array(dependency delay,dependency type)
        $precVal=$precValArray['delay'];
        $precTyp=$precValArray['type'];
      	$prec=$fullListPlan[$precId];
        $precEnd=$prec->plannedEndDate;
        $precStart=$prec->plannedStartDate;
        $precFraction=$prec->plannedEndFraction;       
        if ($prec->realEndDate) {
        	$precEnd=$prec->realEndDate;
        	$precFraction=1;
        }
        if ($prec->realStartDate) {
          $precStart=$prec->realStartDate;
        }
        if ($strictDependency or $precVal!=0 or $precFraction==1) {
          if ( ( $prec->refType!='Milestone' and $plan->refType!='Milestone') or $precFraction==1 or ($strictDependency and $plan->refType=='Milestone') ) {
          //if ($prec->refType!='Milestone') {
            $startPossible=addWorkDaysToDate($precEnd,($precVal>=0)?2+$precVal:1+$precVal); // #77
          } else {
            if ($prec->refType=='Milestone') {
              $startPossible=addWorkDaysToDate($precEnd,($precVal>=0)?1+$precVal:$precVal);
            } else {
              $startPossible=addWorkDaysToDate($precEnd,1+$precVal);
            }
          }
          $startPossibleFraction=0;
        } else {
          $startPossible=$precEnd;
          $startPossibleFraction=$precFraction;
        }
        if ($precTyp=='S-S') {
          if ($precVal>0) {
            $startPossible=addWorkDaysToDate($precStart,$precVal+1);
          } else if ($precVal<0) {
            $startPossible=addWorkDaysToDate($precStart,$precVal);
          } else {
            $startPossible=$precStart;
          }
          $startPossibleFraction=0;
        }
        if ($precTyp=='E-E' and $profile=="FDUR" ) {
          $startPlan=addWorkDaysToDate($precEnd, $plan->validatedDuration *(-1) + 1 + $precVal);
        } else if ($precTyp=='E-E' and ($profile=="ASAP" or $profile=="GROUP") ) {
          //$profile="ALAP";
          $step=-1;
          $endPlan=$startPlan;
          if ($precVal>0) {
            $startPlan=addWorkDaysToDate($precEnd,$precVal+1);
          } else if ($precVal<0) {
            $startPlan=addWorkDaysToDate($precEnd,$precVal);
          } else {
            $startPlan=$precEnd;
          }
        } else if ($precTyp=='E-E' and $profile=="RECW") {
          // Nothing, start / End already set
        } else if ($profile=="ALAP") {
          if ($startPossible>=$endPlan) {
            $endPlan=$startPossible;
            if ($startPlan<$endPlan) {
              $startPlan=$endPlan;
              $endPlan=null;
              $step=1;
              $profile="ASAP";
            }
          }
        } else if ($startPossible>=$startPlan or ($startPossible==$startPlan and $startPossibleFraction>$startFraction)) { // #77       
          $startPlan=$startPossible;
          $startFraction=$startPossibleFraction;
        }
      }
      if ($plan->refType=='Milestone') {
        if ($profile!="FIXED") {
          if ($strictDependency) {
            $plan->plannedStartDate=addWorkDaysToDate($startPlan,1);
          } else if ($startFraction==1) {
          	if (count($precList)>0) {
              $plan->plannedStartDate=addWorkDaysToDate($startPlan,2);
          	} else {
          		$plan->plannedStartDate=addWorkDaysToDate($startPlan,1);
          	}
          	$plan->plannedStartFraction=0;
          } else {
            $plan->plannedStartDate=$startPlan;
            $plan->plannedStartFraction=$startFraction;
          }
          $plan->plannedEndDate=$plan->plannedStartDate;
          $plan->plannedEndFraction=$plan->plannedStartFraction;
          $plan->plannedDuration=0;
          //$plan->save();
          $fullListPlan=self::storeListPlan($fullListPlan,$plan);
        }
        if ($profile=="FIXED") { // We are on Milestone ;)
        	$plan->plannedEndDate=$plan->validatedEndDate;
        	$plan->plannedEndFraction=$plan->plannedStartFraction;
        	$plan->plannedDuration=0;
          //$plan->save();
          $fullListPlan=self::storeListPlan($fullListPlan,$plan);
        }
      } else {        
        if (! $plan->realStartDate and $profile!='RECW') {
          //$plan->plannedStartDate=($plan->leftWork>0)?$plan->plannedStartDate:$startPlan;
        	if ($plan->plannedWork==0 and $plan->elementary==1) {
	        	if ($plan->validatedStartDate and $plan->validatedStartDate>$startPlan) {
	            $plan->plannedStartDate=$plan->validatedStartDate;
	          } else if ($plan->initialStartDate and $plan->initialStartDate>$startPlan) {
	            $plan->plannedStartDate=$plan->initialStartDate;
	          } else {
	            // V5.1.0 : should never start before startplan
	            //$plan->plannedStartDate=date('Y-m-d');
	            $plan->plannedStartDate=$startPlan;
	          }
        	}
        }
        if (! $plan->realEndDate and $profile!='RECW') {
          //$plan->plannedEndDate=($plan->plannedWork==0)?$plan->validatedEndDate:$plan->plannedEndDate;
        	if ($plan->plannedWork==0 and $plan->elementary==1) {
	          if ($plan->validatedEndDate and $plan->validatedEndDate>$startPlan) {
	            $plan->plannedEndDate=$plan->validatedEndDate;
	          } else if ($plan->initialEndDate and $plan->initialEndDate>$startPlan) {
	            $plan->plannedEndDate=$plan->initialEndDate;
	          } else {
	            // V5.1.0 : should never start before startplan
	            //$plan->plannedEndDate=date('Y-m-d');
	            $plan->plannedEndDate=$startPlan;
	          }
          }        	
        }
        if ($profile=="FDUR") {
          if (! $plan->realStartDate) {
            if ($plan->elementary) {
              $plan->plannedStartDate=$startPlan;
              $endPlan=addWorkDaysToDate($startPlan,$plan->validatedDuration);
            }
          } else {
            $endPlan=addWorkDaysToDate($plan->realStartDate,$plan->validatedDuration);
          }
          if (! $plan->realEndDate) {
            $plan->plannedEndDate=$endPlan;
          }
          $fullListPlan=self::storeListPlan($fullListPlan,$plan);
          //$plan->save();
        }
        if ($profile=="ASAP" and $plan->assignedWork==0 and $plan->realWork==0 and $plan->leftWork==0 and $plan->validatedWork>0) {
          if (! $plan->realStartDate) {
            if ($plan->elementary) {
              $plan->plannedStartDate=$startPlan;
              $endPlan=addWorkDaysToDate($startPlan,$plan->validatedWork);
            }
          } else {
            $endPlan=addWorkDaysToDate($plan->realStartDate,$plan->validatedWork);
          }
          if (! $plan->realEndDate) {
            $plan->plannedEndDate=$endPlan;
          }
          $fullListPlan=self::storeListPlan($fullListPlan,$plan);
          //$plan->save();
        }
        
        // get list of top project to chek limit on each project
        if ($withProjectRepartition) {
          $proj = new Project($plan->idProject,true);
          $listTopProjects=$proj->getTopProjectList(true);
        }
        $crit=array("refType"=>$plan->refType, "refId"=>$plan->refId);
        $listAss=$a->getSqlElementsFromCriteria($crit,false);
        $groupAss=array();
        //$groupMaxLeft=0;
        //$groupMinLeft=99999;           
        if ($profile=='GROUP') {
          if (count($listAss)<2) {
        	  $profile='ASAP';
          } else {
            foreach ($listAss as $assTmp) {
              if ($assTmp->isResourceTeam) {
                $profile='ASAP';
                $arrayWarning[$assTmp->id]=i18n("warningPlanningModePool",array(i18n("PlanningModeGROUP")));
                break;
              }
            }
            
          }
        }
        if ($profile=='GROUP') {
        	foreach ($listAss as $ass) {
	        	$r=new Resource($ass->idResource,true);
	          $capacity=($r->capacity)?$r->capacity:1;
	          if (array_key_exists($ass->idResource,$resources)) {
	            $ress=$resources[$ass->idResource];
	          } else {
	            $ress=$r->getWork($startDate, $withProjectRepartition);      
	            $resources[$ass->idResource]=$ress;
	          }
	        	$assRate=1;
	          if ($ass->rate) {
	            $assRate=$ass->rate / 100;
	          }
	          //if ($ass->leftWork>$groupMaxLeft) $groupMaxLeft=$ass->leftWork;
	          //if ($ass->leftWork<$groupMinLeft) $groupMinLeft=$ass->leftWork;
	          if (! isset($groupAss[$ass->idResource]) ) {
		          $groupAss[$ass->idResource]=array();
	            $groupAss[$ass->idResource]['leftWork']=$ass->leftWork;
	            //$groupAss[$ass->idResource]['TogetherWork']=array();
		          $groupAss[$ass->idResource]['capacity']=$capacity;
		          $groupAss[$ass->idResource]['ResourceWork']=$ress;
	            $groupAss[$ass->idResource]['assRate']=$assRate;	
	            $groupAss[$ass->idResource]['calendar']=$r->idCalendarDefinition;
	          } else {
	          	$groupAss[$ass->idResource]['leftWork']+=$ass->leftWork;
	          	$assRate=$groupAss[$ass->idResource]['assRate']+$assRate;
	          	if ($assRate>1) $assRate=1;
	          	$groupAss[$ass->idResource]['assRate']=$assRate;
	          	$groupAss[$ass->idResource]['calendar']=$r->idCalendarDefinition;
	          }
        	  if ($withProjectRepartition) {
              foreach ($listTopProjects as $idProject) {
	              $projKey='Project#' . $idProject;
	              if (! array_key_exists($projKey,$groupAss[$ass->idResource]['ResourceWork'])) {
	                $groupAss[$ass->idResource]['ResourceWork'][$projKey]=array();
	              }
	              if (! array_key_exists('rate',$groupAss[$ass->idResource]['ResourceWork'][$projKey])) {
	                $groupAss[$ass->idResource]['ResourceWork'][$projKey]['rate']=$r->getAffectationRate($idProject);
	              }
	              $groupAss[$ass->idResource]['ResourceWork']['init'.$projKey]=$groupAss[$ass->idResource]['ResourceWork'][$projKey];
	            }
	          }
        	}
        }
        $plan->notPlannedWork=0;
        foreach ($listAss as $ass) {
          if ($ass->notPlannedWork>0) {
            $ass->notPlannedWork=0;
            $changedAss=true;
          }
          if ($profile=='GROUP' and $withProjectRepartition) {
          	foreach ($listAss as $asstmp) {
	            foreach ($listTopProjects as $idProject) {
	              $projKey='Project#' . $idProject;
	              $groupAss[$asstmp->idResource]['ResourceWork'][$projKey]=$groupAss[$asstmp->idResource]['ResourceWork']['init'.$projKey];
	            }
          	}
          }
          $changedAss=true;
          $ass->plannedStartDate=null;
          $ass->plannedEndDate=null;
          $r=new ResourceAll($ass->idResource,true);
          $capacity=($r->capacity)?$r->capacity:1;
          if (array_key_exists($ass->idResource,$resources)) {
            $ress=$resources[$ass->idResource];
          } else {
            $ress=$r->getWork($startDate, $withProjectRepartition);
          }
          $ress['capacity']=$capacity;
          if ($startPlan>$startDate) {
            $currentDate=$startPlan;
          } else {
            $currentDate=$startDate;
            if ($step==-1) {
              $step=1;
            }
          }
          if ($profile=='GROUP') {
            foreach($groupAss as $id=>$grp) {
              $groupAss[$id]['leftWorkTmp']=$groupAss[$id]['leftWork'];	
            }
          }  
          $assRate=1;
          if ($ass->rate) {
            $assRate=$ass->rate / 100;
          }
          // Get data to limit to affectation on each project           
          if ($withProjectRepartition) {
            foreach ($listTopProjects as $idProject) {
              $projKey='Project#' . $idProject;
              if (! array_key_exists($projKey,$ress)) {
                $ress[$projKey]=array();
              }
              if (! array_key_exists('rate',$ress[$projKey])) {
                $ress[$projKey]['rate']=$r->getAffectationRate($idProject);
              }
            }
          }
          //$projRate=$ress['Project#' . $ass->idProject]['rate'];
          if ($ress['team']) {
            $capacityRate=$ass->capacity;
          } else {
            $capacityRate=round($assRate*$capacity,2);
          }
          $keyElt=$ass->refType.'#'.$ass->refId;
          $left=$ass->leftWork;
          $regul=false;
          if ($profile=="REGUL" or $profile=="FULL" or $profile=="HALF" or $profile=="QUART" or $profile=="FDUR") {
          	$delaiTh=workDayDiffDates($currentDate,$endPlan);
          	if ($delaiTh and $delaiTh>0) { 
              $regulTh=round($ass->leftWork/$delaiTh,10);
          	}
          	$delai=0;          	
          	for($tmpDate=$currentDate; $tmpDate<=$endPlan;$tmpDate=addDaysToDate($tmpDate, 1)) {
          		if (isOffDay($tmpDate,$r->idCalendarDefinition)) continue;
          		if (isset($ress['real'][$keyElt][$tmpDate])) continue;
          		$tempCapacity=$capacityRate;
          		if (isset($ress[$tmpDate])) {
          			$tempCapacity-=$ress[$tmpDate];
          		}
          		if ($tempCapacity<0) $tempCapacity=0;
          		if ($tempCapacity>=$regulTh or $regulTh==0) {
          			$delai+=1;
          		} else {
          			$delai+=round($tempCapacity/$regulTh,2);
          		}
          	}            
            if ($delai and $delai>0) { 
              $regul=round(($ass->leftWork/$delai)+0.000005,5);                            
              $regulDone=0;
              $interval=0;
              $regulTarget=0;
            }
          }
          if ($profile=='RECW') {
            $ass->assignedWork=$ass->realWork;
            $ass->leftWork=0;
            $ass->plannedWork=$ass->realWork;
          }
          while (1) {           
            if ($ress['team']) { // For team resource, check if unitary resources have enought availability
              $period=ResourceTeamAffectation::findPeriod($currentDate,$ress['periods']); 
              if ($period===null) {
                $capacity=0;
              } else {
                $capacity=0;
                foreach ($ress['members'] as $idMember=>$member) {
                  if (isset($ress['periods'][$period]['idResource'][$idMember])) {
                    $tmpCapa=$ress['periods'][$period]['idResource'][$idMember];
                    if (isset($member[$currentDate])) {
                      if (isset($resources[$idMember]) and isset($resources[$idMember]['capacity'])) {
                        $capaMember=$resources[$idMember]['capacity'];
                      } else {
                        $capaMember=SqlList::getFieldFromId('Resource', $idMember, 'capacity');
                      }
                      if ($capaMember-$member[$currentDate]>=$tmpCapa) {
                        // tmpCapa preserved : enough left 
                      } else {
                        $tmpCapa=$capaMember-$member[$currentDate];
                      }                      
                    }
                    if ($tmpCapa>0) $capacity+=$tmpCapa;
                  }                
                }
              }
            }
            if ($profile=='RECW') {
              if ($currentDate<=$endPlan) {
                $left=$capacity;
              } else {
                $left=0;
              } 
            }
            if ($left<0.01) {
              break;
            }
            if ($profile=='FIXED' and $currentDate>$plan->validatedEndDate) {
              $changedAss=true;
              $ass->notPlannedWork=$left;
              if ($ass->optional==0) {
                $plan->notPlannedWork+=$left;
                $arrayNotPlanned[$ass->id]=$left;
              }              
              $left=0;
              break;
            }
            // Set limits to avoid eternal loop
            if ($currentDate==$globalMaxDate) { break; }         
            if ($currentDate==$globalMinDate) { break; } 
            if ($ress['Project#' . $plan->idProject]['rate']==0) { break ; } // Resource allocated to project with rate = 0, cannot be planned
            if (isOpenDay($currentDate, $r->idCalendarDefinition)) {              
              $planned=0;
              $plannedReserved=0;
              $week=weekFormat($currentDate);
              if (array_key_exists($currentDate, $ress)) {
                $planned=$ress[$currentDate];
              }
              // Specific reservation for RECW that are not planned yet but will be when start and end are known
              $dow=date('N',strtotime($currentDate));
              $resourceHasReserved=false;
              if ($profile=='GROUP') {
                foreach($groupAss as $assIdResource=>$groupData) {
                  if (isset($reserved['W']['sum'][$assIdResource][$dow])) {
                    $resourceHasReserved=true;
                    break;
                  }
                }
              } else {
                if (isset($reserved['W']['sum'][$ass->idResource][$dow])) $resourceHasReserved=true;
              }
              if ($resourceHasReserved) {
                foreach($reserved['W'] as $idPe=>$arPeW) {                  
                  if ($idPe=='sum') continue;
                  if ($idPe==$plan->id) continue; // we are treating the one we reserved for
                  // === Determine if we must start to reserve work on this task for RECW tasks that will be planned after
                  $startReserving=false;
                  if ($arPeW['start'] ) { // Start is defined from predecessor
                    if ($arPeW['start']<=$currentDate) { // Start is defined (from predecessor) and passed
                      $startReserving=true;  
                    }
                  } else if (count($reserved['W'][$idPe]['pred'])==0) { // No predecessor, so start is start of project
                    $startReserving=true; 
                  } else if (isset($reserved['W'][$idPe]['pred'][$plan->id]) and ($reserved['W'][$idPe]['pred'][$plan->id]['type']=='S-S')) { // Current is predecessor type S-S
                    $delayPred=$reserved['W'][$idPe]['pred'][$plan->id]['delay'];
                    if ($delayPred<=0 or addWorkDaysToDate($startPlan,$delayPred+1)<=$currentDate) { // ... and delay make it started 
                      $startReserving=true;
                    } 
                  } else { // Start Date not Set, check if some predecessor exist (but do not count E-E wich are not real predecessors)
                    $cpt=0;
                    foreach ($reserved['W'][$idPe]['pred'] as $idPredTmp=>$predTmp) {
                      if ($predTmp['type']!='E-E') $cpt++;
                    }
                    if ($cpt==0) $startReserving=true;
                  }
                  // === Determine if we must end to reserve work on this task for RECW tasks that will be planned after
                  $endReserving=false;
                  if ($arPeW['end'] and $arPeW['end']<$currentDate) {
                    $endReserving=true;
                  } // NB : cannot take into account E-E with negative delay : we don't know yet when current task will end to determine [end - x days] 
                  // OK, reserve work ...
                  if ( $startReserving and ! $endReserving ) {
                    $reservedWork=0;
                    if ($profile=='GROUP') {
                      foreach($groupAss as $assIdResource=>$groupData) {
                        if (isset($arPeW[$assIdResource]) and isset($arPeW[$assIdResource][$dow]) and $arPeW[$assIdResource][$dow]>$reservedWork) {
                          $reservedWork=$arPeW[$assIdResource][$dow];
                        }
                      }
                    } else if (isset($arPeW[$ass->idResource][$dow])) {
                      $reservedWork=$arPeW[$ass->idResource][$dow];
                    }
                    $planned+=$reservedWork;
                    $plannedReserved+=$reservedWork;
                  }
                }
              } 
              if ($regul) {
              	if (! isset($ress['real'][$keyElt][$currentDate])) {
                  $interval+=$step;
              	}
              }
              if ($planned < $capacity)  {
                $value=$capacity-$planned; 
                if ($profile=='RECW') {                 
                  $dow=date('N',strtotime($currentDate));  
                  if (isset($reserved['W'][$plan->id][$ass->idResource][$dow]) ) {
                    $value=$reserved['W'][$plan->id][$ass->idResource][$dow];
                    $ass->assignedWork+=$value;
                    $ass->leftWork+=$value;
                    $ass->plannedWork+=$value;
                    $plan->assignedWork+=$value;
                    $plan->leftWork+=$value;
                    $plan->plannedWork+=$value;
                  } else {
                    $value=0; 
                  }
                }
                if ($value>$capacityRate) {
               	  $value=$capacityRate;
                }
                if (isset($ress['real'][$keyElt][$currentDate])) {
                  //$value-=$ress['real'][$keyElt][$currentDate]; // Case 1 remove existing
                  //if ($value<0) $value=0;
                  $value=0; // Case 2 : if real is already defined for the given activity, no more work to plan
                }
                if ($withProjectRepartition) {
                  foreach ($listTopProjects as $idProject) {
                    $projectKey='Project#' . $idProject;
                    $plannedProj=0;
                    $rateProj=1;
                    if (array_key_exists($week,$ress[$projectKey])) {
                      $plannedProj=$ress[$projectKey][$week];
                    }
                    $rateProj=Resource::findAffectationRate($ress[$projectKey]['rate'],$currentDate) / 100;
                    // ATTENTION, if $rateProj < 0, this means there is no affectation left ...
                    if ($rateProj<0) {
                    	$changedAss=true;
                    	$ass->notPlannedWork=$left;
                    	$plan->notPlannedWork+=$left;
                    	if (!$ass->plannedStartDate) $ass->plannedStartDate=$currentDate;
                    	if (!$ass->plannedEndDate) $ass->plannedEndDate=$currentDate;
                    	if (!$plan->plannedStartDate) $plan->plannedStartDate=$currentDate;
                    	if (!$plan->plannedEndDate) $plan->plannedEndDate=$currentDate;
                    	$arrayNotPlanned[$ass->id]=$left;
                    	$left=0;
                    }
                    if ($rateProj==1) {
                    	$leftProj=round(7*$capacity*$rateProj,2)-$plannedProj; // capacity for a full week
                    	// => to be able to plan weekends
                    } else {
                      $leftProj=round($daysPerWeek*$capacity*$rateProj,2)-$plannedProj; // capacity for a week
                    }
                    if ($value>$leftProj) {
                      $value=$leftProj;
                    }
                  }
                }
                $value=($value>$left)?$left:$value;
                if ($currentDate==$startPlan and $value>((1-$startFraction)*$capacity)) {
                  $value=((1-$startFraction)*$capacity);
                }
                if ($regul) {
                	$tmpTarget=$regul;
                	if (isset($ress['real'][$keyElt][$currentDate])) {
                	  $tmpTarget=0;
                	}
                  $tempCapacity=$capacityRate;
                  if (isset($ress[$currentDate])) {
                    $tempCapacity-=$ress[$currentDate];
                  }
                  if ($tempCapacity<0) $tempCapacity=0;
                  if ($tempCapacity<$regulTh and $regulTh!=0) {
                    $tmpTarget=round($tmpTarget*$tempCapacity/$regulTh,10);
                  }                                    
                	$regulTarget=round($regulTarget+$tmpTarget,10);              
                  $toPlan=$regulTarget-$regulDone;
                  if ($value>$toPlan) {
                    $value=$toPlan;
                  }
                  if ($workUnit=='days') {
                    $value=round($value,1);
                  } else {
                  	$value=round($value/$halfHour,0)*$halfHour;
                  }
                  if ($profile=="FULL" and $toPlan<1 and $interval<$delaiTh) {
                    $value=0;
                  }
                  if ($profile=="HALF" and $interval<$delaiTh) {
                    if ($toPlan<0.5) {
                      $value=0;
                    } else {
                      $value=0.5;
                    }
                  }
                  if ($profile=="QUART" and $interval<$delaiTh) {
                    if ($toPlan<0.25) {
                      $value=0;
                    } else {
                      $value=0.25;
                    }
                  }
                  if ($value>($capacity-$planned)) {
                    $value=$capacity-$planned;
                    if ($value<0.1) $value=0;
                  }
                  $regulDone+=$value;
                }
                if ($profile=='GROUP') {
                	foreach($groupAss as $id=>$grp) {
                		$grpCapacity=1;
                		if ($grp['leftWorkTmp']>0) {
	                		$grpCapacity=$grp['capacity']*$grp['assRate'];
	                		if (isOffDay($currentDate,$grp['calendar'])) {
	                		  $grpCapacity=0;
	                		} else if (isset($grp['ResourceWork'][$currentDate])) {
	                			$grpCapacity-=$grp['ResourceWork'][$currentDate];
	                		}
                		}
                		if ($value>$grpCapacity-$plannedReserved) {
                			$value=$grpCapacity-$plannedReserved;
                		}
                	}
                	// Check Project Affectation Rate
                	foreach($groupAss as $id=>$grp) {
	                  foreach ($listTopProjects as $idProject) {
	                    $projectKey='Project#' . $idProject;
	                    $plannedProj=0;
	                    $rateProj=1;
	                    if (isset($grp['ResourceWork'][$projectKey][$week])) {
	                      $plannedProj=$grp['ResourceWork'][$projectKey][$week];
	                    }
	                    $rateProj=Resource::findAffectationRate($grp['ResourceWork'][$projectKey]['rate'],$currentDate) / 100;
	                    if ($rateProj==1) {
	                      $leftProj=round(7*$grp['capacity']*$rateProj,2)-$plannedProj; // capacity for a full week
	                      // => to be able to plan weekends
	                    } else {
	                      $leftProj=round($daysPerWeek*$grp['capacity']*$rateProj,2)-$plannedProj; // capacity for a week
	                    }
	                    if ($value>$leftProj) {
	                      $value=$leftProj;
	                    }
	                  }
                	}
                	
                	foreach($groupAss as $id=>$grp) {
                		$groupAss[$id]['leftWorkTmp']-=$value;
                		//$groupAss[$id]['weekWorkTmp'][$week]+=$value;
	                	if ($withProjectRepartition and $value >= 0.01) {
	                    foreach ($listTopProjects as $idProject) {
	                      $projectKey='Project#' . $idProject;
	                      $plannedProj=0;
	                      if (array_key_exists($week,$grp['ResourceWork'][$projectKey])) {
	                        $plannedProj=$grp['ResourceWork'][$projectKey][$week];
	                      }
	                      $groupAss[$id]['ResourceWork'][$projectKey][$week]=$value+$plannedProj;
	                    }
	                  }
                	}
                }
                if ($value>=0.01) { // Store value on Resource Team if current resource belongs to a Resource Team
                  if (!$ress['team'] and isset($ress['isMemberOf']) and count($ress['isMemberOf'])>0) {
                    // For each Pool current resource is member of
                    foreach($ress['isMemberOf'] as $idRT=>$rt) {
                      if (!isset($resources[$idRT]) ) {
                        $r=new ResourceAll($idRT,true);
                        $resources[$idRT]=$r->getWork($startDate, $withProjectRepartition);
                      }
                      $period=ResourceTeamAffectation::findPeriod($currentDate, $resources[$idRT]['periods']);
                      // For current date : if 1) some work exists on Pool 2) current resource has not null capacity on Pool  
                      // => must check that there is no constraint 
                      if ($period and isset($resources[$idRT][$currentDate]) 
                      and isset($resources[$idRT]['periods'][$period]['idResource'][$ass->idResource])
                      and $resources[$idRT]['periods'][$period]['idResource'][$ass->idResource]>0) {
                        $ctrlPlannedWorkOnPool=$resources[$idRT][$currentDate];
                        $ctrlCanBeDoneByOthersOnPool=0;
                        foreach ($resources[$idRT]['members'] as $idMember=>$workMember) {
                          if ($idMember==$ass->idResource) continue; // Do not count work that can be done by current (we count only "others")
                          if (isset($resources[$idMember]) and isset($resources[$idMember]['capacity'])) {
                            $ctrlCapaMember=$resources[$idMember]['capacity'];
                          } else {
                            $ctrlCapaMember=SqlList::getFieldFromId('Resource', $idMember, 'capacity');
                          }
                          $ctrlCanBeDoneByMember=$ctrlCapaMember; // Limit to own capacity of resource
                          if (isset($resources[$idMember]) and isset($resources[$idMember][$currentDate])) {
                            $ctrlCanBeDoneByMember-=$resources[$idMember][$currentDate]; // Subtract already planned for member
                          }
                          if (isset($resources[$idRT]['periods'][$period]['idResource'][$idMember])) { // If member has capacity on the period
                            $capaMaxMemberOnPool=$resources[$idRT]['periods'][$period]['idResource'][$idMember];
                            if ($capaMaxMemberOnPool<$ctrlCanBeDoneByMember) {
                              $ctrlCanBeDoneByMember=$capaMaxMemberOnPool;
                            }
                          } else {
                            $ctrlCanBeDoneByMember=0;
                          }
                          $ctrlCanBeDoneByOthersOnPool+=$ctrlCanBeDoneByMember;
                        }
                        $mustBeDoneByCurrentResourceOnPool=$ctrlPlannedWorkOnPool-$ctrlCanBeDoneByOthersOnPool;
                        $available=$capacity-$mustBeDoneByCurrentResourceOnPool;
                        if (isset($resources[$ass->idResource][$currentDate]) ) {
                          $available-=$resources[$ass->idResource][$currentDate]; // Subtract already planned for current user
                        }
                        if ($available<$value) {
                          $value=$available;
                        }
                        if ($value<0) $value=0;
                      }
                    }
                    foreach($ress['isMemberOf'] as $idRT=>$rt) {
                      // Store detail of already planned for each member (will be used when planning Pool)
                      // Attention, must be done after controlling every Pool, to have the correc $value
                      $period=ResourceTeamAffectation::findPeriod($currentDate, $resources[$idRT]['periods']);
                      if ($period and isset($resources[$idRT]['periods'][$period]['idResource'][$ass->idResource])) {
                        if (! isset($resources[$idRT]['members'][$ass->idResource][$currentDate])) $resources[$idRT]['members'][$ass->idResource][$currentDate]=0;
                        $resources[$idRT]['members'][$ass->idResource][$currentDate]+=$value;
                      }
                    }
                  }
                }
                if ($value>=0.01) {
                  if ($profile=='FIXED' and $currentDate==$plan->validatedStartDate) {
                    $fractionStart=$plan->validatedStartFraction;
                  } else {
                    $fractionStart=($capacity!=0)?round($planned/$capacity,2):'0';
                  }
                  $fraction=($capacity!=0)?round($value/$capacity,2):'1';;             
                  $plannedWork=new PlannedWork();
                  $plannedWork->idResource=$ass->idResource;
                  $plannedWork->idProject=$ass->idProject;
                  $plannedWork->refType=$ass->refType;
                  $plannedWork->refId=$ass->refId;
                  $plannedWork->idAssignment=$ass->id;
                  $plannedWork->work=$value;
                  $plannedWork->setDates($currentDate);
                  $arrayPlannedWork[]=$plannedWork;
                  if (! $ass->plannedStartDate or $ass->plannedStartDate>$currentDate) {
                    $ass->plannedStartDate=$currentDate;
                    $ass->plannedStartFraction=$fractionStart;
                  }
                  if (! $ass->plannedEndDate or $ass->plannedEndDate<$currentDate) {
                    $ass->plannedEndDate=$currentDate;
                    $ass->plannedEndFraction=min(($fractionStart+$fraction),1);
                  }
                  if (! $plan->plannedStartDate or $plan->plannedStartDate>$currentDate) {
                    $plan->plannedStartDate=$currentDate;
                    $plan->plannedStartFraction=$fractionStart;
                  } else if ($plan->plannedStartDate==$currentDate and $plan->plannedStartFraction<$fractionStart) {
                    $plan->plannedStartFraction=$fractionStart;
                  }
                  if (! $plan->plannedEndDate or $plan->plannedEndDate<$currentDate) {
                    if ($ass->realEndDate && $ass->realEndDate>$currentDate) {
                  		$plan->plannedEndDate=$ass->realEndDate;
                  		$plan->plannedEndFraction=1;
                  	} else {
                      $plan->plannedEndDate=$currentDate;
                      $plan->plannedEndFraction=min(($fractionStart+$fraction),1);
                  	}
                  } else if ($plan->plannedEndDate==$currentDate and $plan->plannedEndFraction<$fraction) {
                    $plan->plannedEndFraction=min(($fractionStart+$fraction),1);
                  }
                  $changedAss=true;
                  $left-=$value;
                  $ress[$currentDate]=$value+$planned-$plannedReserved;
                  // Set value on each project (from current to top)
                  if ($withProjectRepartition and $value >= 0.01) {
                    foreach ($listTopProjects as $idProject) {
                      $projectKey='Project#' . $idProject;
                      $plannedProj=0;
                      if (array_key_exists($week,$ress[$projectKey])) {
                        $plannedProj=$ress[$projectKey][$week];
                      }
                      $ress[$projectKey][$week]=$value+$plannedProj;               
                    }
                  }

                }
              }            
            }
            $currentDate=addDaysToDate($currentDate,$step);
            if ($currentDate<$endPlan and $step==-1) {
              $currentDate=$endPlan;
              $step=1;
            }
          }
          if ($changedAss) {
            $ass->_noHistory=true; // Will only save planning data, so no history required  
            $arrayAssignment[]=$ass;
          }
          $resources[$ass->idResource]=$ress;
        } 
      }
      $fullListPlan=self::storeListPlan($fullListPlan,$plan);
      if (isset($reserved['allPreds'][$plan->id]) ) {
        foreach($reserved['W'] as $idPe=>$pe) {
          if (isset($pe['pred'][$plan->id])) {
            $typePred=$pe['pred'][$plan->id]['type'];
            $delayPred=$pe['pred'][$plan->id]['delay'];
            if ($typePred=='E-S') { // TODO : check existing start / end
              $tmpPred=$fullListPlan['#'.$plan->id];
              if ($tmpPred->refType=='Milestone') {
                if ($delayPred>0) {
                  $reserved['W'][$idPe]['start']=addWorkDaysToDate($plan->plannedEndDate,$delayPred+1);
                } else {
                  $reserved['W'][$idPe]['start']=addWorkDaysToDate($plan->plannedEndDate,$delayPred);
                }
              } else {
                if ($delayPred>=0) {
                  $reserved['W'][$idPe]['start']=addWorkDaysToDate($plan->plannedEndDate,$delayPred+2);
                } else {
                  $reserved['W'][$idPe]['start']=addWorkDaysToDate($plan->plannedEndDate,$delayPred+1);
                }
              }
            } else if ($typePred=='S-S') {
              if ($delayPred>0) {
                $reserved['W'][$idPe]['start']=addWorkDaysToDate($plan->plannedStartDate,$delayPred+1);
              } else { // delay <= 0 
                $reserved['W'][$idPe]['start']=addWorkDaysToDate($plan->plannedStartDate,$delayPred);
              }              
            } else if ($typePred=='E-E') {
              if ($delayPred>0) {
                $reserved['W'][$idPe]['end']=addWorkDaysToDate($plan->plannedEndDate,$delayPred+1);
              } else { // delay <= 0
                $reserved['W'][$idPe]['end']=addWorkDaysToDate($plan->plannedEndDate,$delayPred);
              }
            }
          }
        }
      }
      if (isset($reserved['W'][$plan->id]) ) { // remove $reserved when planned for RECW
        foreach ($reserved['W'][$plan->id] as $idRes=>$resRes) {
          if (!is_numeric($idRes)) continue;
          foreach ($resRes as $day=>$val) {
            if (isset($reserved['W']['sum'][$idRes][$day])) {
              $reserved['W']['sum'][$idRes][$day]-=$val;
            }
          }
        }
        unset($reserved['W'][$plan->id]);
      }
      if (isset($reserved['allSuccs'])) {
        // TODO : take into acount E-S dependency to determine end
      }
    }
    // Moved transaction at end of procedure (out of script plan.php) to minimize lock possibilities
    Sql::beginTransaction();
    $cpt=0;
    $query='';
    foreach ($arrayPlannedWork as $pw) {
      if ($cpt==0) {
        $query='INSERT into ' . $pw->getDatabaseTableName() 
          . ' (idResource,idProject,refType,refId,idAssignment,work,workDate,day,week,month,year)'
          . ' VALUES ';
      } else {
        $query.=', ';
      }
      $query.='(' 
        . "'" . Sql::fmtId($pw->idResource) . "',"
        . "'" . Sql::fmtId($pw->idProject) . "',"
        . "'" . $pw->refType . "',"
        . "'" . Sql::fmtId($pw->refId) . "',"
        . "'" . Sql::fmtId($pw->idAssignment) . "',"
        . "'" . $pw->work . "',"
        . "'" . $pw->workDate . "',"
        . "'" . $pw->day . "',"
        . "'" . $pw->week . "',"
        . "'" . $pw->month . "',"
        . "'" . $pw->year . "')";
      $cpt++; 
      if ($cpt>=100) {
        $query.=';';
        SqlDirectElement::execute($query);
        $cpt=0;
        $query='';
      }
    }
    if ($query!='') {
      $query.=';';
      SqlDirectElement::execute($query);
    }
    // save Assignment
    foreach ($arrayAssignment as $ass) {
      $ass->simpleSave();
    }
    
    if ($withCriticalPath) {
      if ($allProjects) {
        $proj=new Project(' ',true);
        $projectIdArray=array_keys($proj->getRecursiveSubProjectsFlatList(true, true));
      }
      foreach ($projectIdArray as $idP) {
        $fullListPlan=self::calculateCriticalPath($idP,$fullListPlan);
      }
    }
    $arrayProj=array();
    foreach ($fullListPlan as $pe) {
      if (!$pe->refType) continue;
      if ($pe->refType!='Project') $arrayProj[$pe->idProject]=$pe->idProject;
      if (property_exists($pe,'_profile') and $pe->_profile=='RECW') { 
        PlanningElement::updateSynthesis($pe->refType, $pe->refId);
        $resPe=$pe->simpleSave();
      } else {
   	    $resPe=$pe->simpleSave();
      }
   	  if ($pe->refType=='Milestone' and SqlElement::is_a($pe,'MilestonePlanningElement')) {
   	    $pe->updateMilestonableItems();
   	  }
     }
    foreach ($arrayProj as $idP) {
      Project::unsetNeedReplan($idP);
    }
    $messageOn = false;
    $endTime=time();
    $endMicroTime=microtime(true);
    $duration = round(($endMicroTime - $startMicroTime)*1000)/1000;
    if (count($arrayNotPlanned)>0 or count($arrayWarning)>0) {
    	$result=i18n('planDoneWithLimits', array($duration));
    	$result.='<br/><br/><table style="width:100%">';
    	$result .='<tr style="color:#888888;font-weight:bold;border:1px solid #aaaaaa"><td style="width:40%">'.i18n('colElement').'</td><td style="width:40%">'.i18n('colCause').'</td><td style="width:20%">'.i18n('colIdResource').'</td></tr>';
    	foreach ($arrayNotPlanned as $assId=>$left) {
    		$ass=new Assignment($assId,true);
    		$rName=SqlList::getNameFromId('ResourceAll', $ass->idResource);
    		$oName=SqlList::getNameFromId($ass->refType, $ass->refId);
    		$msg = (is_numeric($left))?i18n('colNotPlannedWork').' : '.Work::displayWorkWithUnit($left):$left;
    		$result .='<tr style="border:1px solid #aaaaaa;"><td style="padding:1px 10px;">'.i18n($ass->refType).' #'.htmlEncode($ass->refId).' : '.$oName. '</td><td style="padding:1px 10px;">'.$msg.'</td><td style="padding:1px 10px;">'.$rName.'</td></tr>'; 
    	}	
    	foreach ($arrayWarning as $assId=>$msg) {
    	  $ass=new Assignment($assId,true);
    	  $rName=SqlList::getNameFromId('ResourceAll', $ass->idResource);
    	  $oName=SqlList::getNameFromId($ass->refType, $ass->refId);
    	  $result .='<tr style="border:1px solid #aaaaaa;"><td style="padding:1px 10px;">'.i18n($ass->refType).' #'.htmlEncode($ass->refId).' : '.$oName. '</td><td style="padding:1px 10px;">'.$msg.'</td><td style="padding:1px 10px;">'.$rName.'</td></tr>';
    	}
    	$result.='</table>';
    	$result .= '<input type="hidden" id="lastPlanStatus" value="INCOMPLETE" />';
    } else {
    	$result=i18n('planDone', array($duration));
    	$result .= '<input type="hidden" id="lastPlanStatus" value="OK" />';
    }
    // Moved transaction at end of procedure (out of script plan.php) to minimize lock possibilities
    $status = getLastOperationStatus ( $result );
    if ($status == "OK" or $status=="NO_CHANGE" or $status=="INCOMPLETE") {
      Sql::commitTransaction ();
    } else {
      Sql::rollbackTransaction ();
    }
    echo '<div class="message' . $status . '" >' . $result . '</div>';
    return $result;
  }
// End of PLAN
// ================================================================================================================================
  
  private static function calculateCriticalPath($idProject,$fullListPlan) {
    if (!trim($idProject) or $idProject=='*') return $fullListPlan;
    $start=null;
    $end=null;
    $arrayNode=array('early'=>null,'late'=>null,'before'=>array(),'after'=>array());
    $arrayTask=array('duration'=>null,'start'=>null,'end'=>null,'type'=>'task','class'=>'','name'=>'', 'mode'=>'');
    if ($fullListPlan) {
      $peList=array();
      foreach ($fullListPlan as $id=>$plan) {
        if ($plan->idProject==$idProject and $plan->refType!='Project') {
          $peList[$id]=$plan;
        }
        if ($plan->refType=='Project' and $plan->refId==$idProject) {
          $start=$plan->plannedStartDate;
          $end=$plan->plannedEndDate;
        }
      }
    } else {
      $pe=new PlanningElement();
      $peList=$pe->getSqlElementsFromCriteria(null,null, "(idProject=$idProject and refType!='Project') or ( refType=='Project' and refId=$idProject)", "wbsSortable asc", true);
      foreach ($peList as $id=>$plan) {
        if ($plan->refType=='Project' and $plan->refId==$idProject) {
          $start=$plan->plannedStartDate;
          $end=$plan->plannedEndDate;
          unset($peList[$id]);
          break;
        }
      } 
      // TODO : get predecessors
    }
    $cp=array('node'=>array(),'task'=>array());
    $cp['node']['S']=$arrayNode; 
    $cp['node']['S']['early']=$start;
    $cp['node']['E']=$arrayNode;
    $cp['node']['E']['early']=$end;
    $cp['node']['E']['late']=$end;
    foreach($peList as $id=>$plan) {
      $cp['task'][$id]=$arrayTask;
      $cp['task'][$id]['duration']=workDayDiffDates($plan->plannedStartDate, $plan->plannedEndDate);//$plan->plannedDuration;
      $cp['task'][$id]['name']=$plan->refName;
      $cp['task'][$id]['class']=$plan->refType;
      $cp['task'][$id]['start']='S'.$id;
      if (!isset($cp['node']['S'.$id])) $cp['node']['S'.$id]=$arrayNode;
      $cp['node']['S'.$id]['early']=$plan->plannedStartDate;
      if (!in_array($id,$cp['node']['S'.$id]['after'])) $cp['node']['S'.$id]['after'][]=$id;
      $cp['task'][$id]['end']='E'.$id;
      if (!isset($cp['node']['E'.$id])) $cp['node']['E'.$id]=$arrayNode;
      $cp['node']['E'.$id]['early']=$plan->plannedEndDate;
      if (!in_array($id,$cp['node']['E'.$id]['before'])) $cp['node']['E'.$id]['before'][]=$id;
      foreach ($plan->_directPredecessorList as $idPrec=>$prec) {
        if (!isset($peList[$idPrec]) ) continue; // Predecessor not in current project
        if (!isset($cp['task'][$idPrec.'-'.$id])) $cp['task'][$idPrec.'-'.$id]=$arrayTask;
        $cp['task'][$idPrec.'-'.$id]['type']='dependency';
        if ($peList[$idPrec]->refType=='Milestone' or $prec['type']=='S-S' or $prec['type']=='E-E') {
          $cp['task'][$idPrec.'-'.$id]['duration']=$prec['delay'];
        } else {
          $cp['task'][$idPrec.'-'.$id]['duration']=$prec['delay']+1;
        }
        $typS=substr($prec['type'],0,1);
        $typE=substr($prec['type'],-1);
        if ($prec['type']!='E-E') {
          $cp['task'][$idPrec.'-'.$id]['start']=$typS.$idPrec;
          if (!isset($cp['node'][$typS.$idPrec])) $cp['node'][$typS.$idPrec]=$arrayNode;
          if (!in_array($idPrec.'-'.$id,$cp['node'][$typS.$idPrec]['after'])) $cp['node'][$typS.$idPrec]['after'][]=$idPrec.'-'.$id;
          $cp['task'][$idPrec.'-'.$id]['end']=$typE.$id;
          if (!isset($cp['node'][$typE.$id])) $cp['node'][$typE.$id]=$arrayNode;
          if (!in_array($idPrec.'-'.$id,$cp['node'][$typE.$id]['before'])) $cp['node'][$typE.$id]['before'][]=$idPrec.'-'.$id;
        } else {
          $cp['task'][$idPrec.'-'.$id]['duration']=$prec['delay'];
          if ($cp['task'][$id]) $cp['task'][$id]['duration'];
          $cp['task'][$idPrec.'-'.$id]['start']='E'.$id;
          if (!isset($cp['node']['E'.$id])) $cp['node']['E'.$id]=$arrayNode;
          if (!in_array($idPrec.'-'.$id,$cp['node']['E'.$id]['after'])) $cp['node']['E'.$id]['after'][]=$idPrec.'-'.$id;
          $cp['task'][$idPrec.'-'.$id]['end']='E'.$idPrec;
          if (!isset($cp['node']['E'.$idPrec])) $cp['node']['E'.$idPrec]=$arrayNode;
          if (!in_array($idPrec.'-'.$id,$cp['node']['E'.$idPrec]['before'])) $cp['node']['E'.$idPrec]['before'][]=$idPrec.'-'.$id;
          if (!isset($cp['task'][$id])) $cp['task'][$id]=$arrayTask;
          $cp['task'][$id]['mode']='reverse';
        }
      }
    }
    foreach ($cp['node'] as $id=>$node) { // Attach loose nodes to S or E
      if ($id=='S' or $id=='E') continue;
      if (count($node['before'])==0) { // No predecessor 
        $cp['task']['S-'.$id]=$arrayTask;
        $cp['task']['S-'.$id]['type']='fake';
        $cp['task']['S-'.$id]['duration']=0;
        $cp['task']['S-'.$id]['start']='S';
        $cp['task']['S-'.$id]['end']=$id;
        if (!in_array('S-'.$id,$cp['node']['S']['after'])) $cp['node']['S']['after'][]='S-'.$id;
      }
      if (count($node['after'])==0) { // No successor
        $cp['task'][$id.'-E']=$arrayTask;
        $cp['task'][$id.'-E']['type']='fake';
        $cp['task'][$id.'-E']['duration']=0;
        $cp['task'][$id.'-E']['start']=$id;
        $cp['task'][$id.'-E']['end']='E';
        if (!in_array($id.'-E',$cp['node']['E']['before'])) $cp['node']['E']['before'][]=$id.'-E';
      }
    }
    self::reverse('E',$cp);
    foreach ($cp['task'] as $idP=>$plan) {
      if ($plan['type']!='task') continue;
      $pe=$fullListPlan[$idP];
      $pe->latestStartDate=$cp['node'][$plan['start']]['late'];
      $pe->latestEndDate=$cp['node'][$plan['end']]['late'];
      $profile=(isset($pe->_profile))?$pe->_profile:'ASAP';
      if ($profile=='RECW' or $profile=="REGUL" or $profile=="FULL" or $profile=="HALF" or $profile=="QUART" 
          or (count($pe->_directPredecessorList)==0 and $pe->latestStartDate>$start) 
          or !$pe->elementary) {
        $pe->isOnCriticalPath=0;
      } else if ( ($pe->latestStartDate<=$pe->plannedStartDate and $pe->latestEndDate<=$pe->plannedEndDate and $plan['mode']!='reverse') 
          or ( $plan['mode']=='reverse' and $pe->latestStartDate<$pe->plannedStartDate) ) {
        $pe->isOnCriticalPath=1;
      } else {
        $pe->isOnCriticalPath=0;
      }
      $fullListPlan[$idP]=$pe;
    }
    return $fullListPlan;
  }
  private static function reverse($nodeId,&$cp) {
    $node=$cp['node'][$nodeId];
    $cp['TEST']='OK';
    foreach ($cp['node'][$nodeId]['before'] as $taskId) {
      $task=$cp['task'][$taskId];
      $diff=($task['duration'])?($task['duration'])*(-1):0;
      if ($nodeId=='E' or $nodeId=='S') {
        $diff==0;
      } else if ($task['type']=='task' and $diff!=0) {
        $diff+=1;
      } else if ($diff>0) {
        $diff+=1;
      } 
      $start=addWorkDaysToDate($node['late'],$diff);
      if (!$cp['node'][$task['start']]['late'] or $start<$cp['node'][$task['start']]['late']) $cp['node'][$task['start']]['late']=$start;
      self::reverse($task['start'],$cp);
    }
  }
  
  private static function storeListPlan($listPlan,$plan) {
scriptLog("storeListPlan(listPlan,$plan->id)");
    $listPlan['#'.$plan->id]=$plan;
    // Update planned dates of parents
    if (($plan->plannedStartDate or $plan->realStartDate) and ($plan->plannedEndDate or $plan->realEndDate) ) {
      foreach ($plan->_parentList as $topId=>$topVal) {
        $top=$listPlan[$topId];
        $startDate=($plan->realStartDate)?$plan->realStartDate:$plan->plannedStartDate;
        if (!$top->plannedStartDate or $top->plannedStartDate>$startDate) {
          $top->plannedStartDate=$startDate;
        }
        $endDate=($plan->realEndDate)?$plan->realEndDate:$plan->plannedEndDate;
        if (!$top->plannedEndDate or $top->plannedEndDate<$endDate) {
          $top->plannedEndDate=$endDate;
        }
        $listPlan[$topId]=$top;
      }
    }
    return $listPlan;
  }
  
  private static function sortPlanningElements($list,$listProjectsPriority) {
  	// first sort on simple criterias
    foreach ($list as $id=>$elt) {
    	if ($elt->idPlanningMode=='16' or $elt->idPlanningMode=='6') { // FIXED
    		$crit='1';
    	} else if ($elt->idPlanningMode=='2' or  $elt->idPlanningMode=='3' or  $elt->idPlanningMode=='7' or  $elt->idPlanningMode=='20'
    	  or $elt->idPlanningMode=='10' or $elt->idPlanningMode=='11' or $elt->idPlanningMode=='13') { // REGUL or FULL or HALF or QUART)
    	  $crit='2';
    	} else if ($elt->idPlanningMode=='8' or  $elt->idPlanningMode=='14') { // FDUR  
    	  $crit='3';
    	} else if ($elt->idPlanningMode=='22') { // RECW
    	  $crit='5'; // Lower priority (availability will be reserved)
    	} else { // Others (includes GROUP, with is not a priority but a constraint)
        $crit='4';
    	}
      $crit.='.';
      $prio=$elt->priority;
      if (isset($listProjectsPriority[$elt->idProject])) {
        $projPrio=$listProjectsPriority[$elt->idProject];
      } else { 
      	$projPrio=500;
      }
      if (! $elt->leftWork or $elt->leftWork==0) {$prio=0;}
      $crit.=str_pad($projPrio,5,'0',STR_PAD_LEFT).'.'.str_pad($prio,5,'0',STR_PAD_LEFT).'.'.$elt->wbsSortable;
      $elt->_sortCriteria=$crit;
      $list[$id]=$elt;
    }
    //self::traceArray($list);
    $bool = uasort($list,array(new PlanningElement(), "comparePlanningElementSimple"));
    //self::traceArray($list);
    // then sort on predecessors
    $result=self::specificSort($list);
    //self::traceArray($result);
    return $result;
  }
  
  private static function specificSort($list) {
  	// Sort to take dependencies into account
  	$wait=array(); // array to store elements that has predecessors not sorted yet
  	$result=array(); // target array for sorted elements
  	foreach($list as $id=>$pe) {
  		$canInsert=false;
  		if ($pe->_predecessorListWithParent) {
  			$pe->_tmpPrec=array();
  			// retrieve prédecessors not sorted yet
  			foreach($pe->_predecessorListWithParent as $precId=>$precPe) {
  				if (! array_key_exists($precId, $result)) {
  					 $pe->_tmpPrec[$precId]=$precPe;
  				}
  			} 			
  			if (count($pe->_tmpPrec)>0) {
  				// if has some not written predecessor => wait (until no more predecessor)
  				$wait[$id]=$pe;
  				$canInsert=false;
  			} else {
  				// all predecessors are sorted yet => can insert it in sort list
  				$canInsert=true;
  			}
  		} else {
  			// no predecessor, so can insert
  			$canInsert=true;
  		}
  		if ($canInsert) {
  			$result[$id]=$pe;
  			// now, must check if can insert waiting ones
  			self::insertWaiting($result,$wait,$id);
  		}
  	}
  	// in the end, empty wait stack (should be empty !!!!)
  	foreach($wait as $wId=>$wPe) {
  		unset($wPe->_tmpPrec); // no used elsewhere
      $result[$wId]=$wPe;
  	}
  	return $result;
  }
  
  private static function insertWaiting(&$result,&$wait,$id) {
    foreach($wait as $wId=>$wPe) {
      if (isset($wPe->_tmpPrec) and array_key_exists($id, $wPe->_tmpPrec)) {
        // ok, prec has been inserted, not waiting for it anymore
        unset($wPe->_tmpPrec[$id]);
        if (count($wPe->_tmpPrec)==0) {
          // Waiting for no more prec => store it
          unset($wPe->_tmpPrec);
          $result[$wId]=$wPe;
          // and remove it from wait list
          unset ($wait[$wId]);
          // and check if this new insertion can release others
          self::insertWaiting($result,$wait,$wId); 
        } else {
          // Store wait stack with new prec list (with less items...)
          $wait[$wId]=$wPe;
        }
      }
    }
  }
  private static function traceArray($list) {
  	debugTraceLog('*****traceArray()*****');
  	foreach($list as $id=>$pe) {
  		debugTraceLog($id . ' - ' . $pe->wbs . ' - ' . $pe->refType . '#' . $pe->refId . ' - ' . $pe->refName . ' - Prio=' . $pe->priority . ' - Left='.$pe->leftWork.' - '.$pe->_sortCriteria);
  		if (count($pe->_predecessorListWithParent)>0) {
  			foreach($pe->_predecessorListWithParent as $idPrec=>$prec) {
  				debugTraceLog('   ' . $idPrec.'=>'.$prec['delay'].' ('.$prec['type'].')');
  			}
  		}
  	}
  }
  
  public static function planSaveDates($projectId, $initial, $validated) {
  	if ($initial=='NEVER' and $validated=='NEVER') {
  		$result=i18n('planDatesNotSaved');
  		$result .= '<input type="hidden" id="lastPlanStatus" value="WARNING" />';
  		return $result;
  	}
  	$cpt=0;
  	$proj=new Project($projectId,true);
  	$inClause="idProject in " . transformListIntoInClause($proj->getRecursiveSubProjectsFlatList(true, true));
  	$obj=new PlanningElement();
  	$tablePE=$obj->getDatabaseTableName();
  	$inClause.=" and " . getAccesRestrictionClause('Activity',$tablePE);
  	// Remove administrative projects :
  	$inClause.=" and idProject not in " . Project::getAdminitrativeProjectList() ;
  	// Remove Projects with Fixed Planning flag
  	$inClause.=" and idProject not in " . Project::getFixedProjectList() ;
  	// Get the list of all PlanningElements to plan (includes Activity and/or Projects)
  	$pe=new PlanningElement();
  	$order="wbsSortable asc";
  	$list=$pe->getSqlElementsFromCriteria(null,false,$inClause,$order,true);
  	foreach ($list as $pe) {
  		// initial
  		if (($initial=='ALWAYS' or ($initial=='IFEMPTY' and ! $pe->initialStartDate)) and trim($pe->plannedStartDate)) {
  			$pe->initialStartDate=$pe->plannedStartDate;
  			$cpt++;
  		}
  		if (($initial=='ALWAYS' or ($initial=='IFEMPTY' and ! $pe->initialEndDate)) and trim($pe->plannedEndDate)) {
  			$pe->initialEndDate=$pe->plannedEndDate;
  			$cpt++;
  		}
  		// validated
  		if (($validated=='ALWAYS' or ($validated=='IFEMPTY' and ! $pe->validatedStartDate)) and trim($pe->plannedStartDate)) {
  			$pe->validatedStartDate=$pe->plannedStartDate;
  			$cpt++;
  		}
  		if (($validated=='ALWAYS' or ($validated=='IFEMPTY' and ! $pe->validatedEndDate)) and trim($pe->plannedEndDate)) {
  			$pe->validatedEndDate=$pe->plannedEndDate;
  			$cpt++;
  		}
  		$pe->simpleSave();
  	}
  	if ($cpt>0) {
  		$result=i18n('planDatesSaved');
  		$result .= '<input type="hidden" id="lastPlanStatus" value="OK" />';
  	} else {
  		$result=i18n('planDatesNotSaved');
  		$result .= '<input type="hidden" id="lastPlanStatus" value="WARNING" />';
  	}
  	return $result;
  }
  
}
?>