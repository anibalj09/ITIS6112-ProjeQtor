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

/* ============================================================================
 * Technical class to implement consistency checks
 */ 
require_once('_securityCheck.php');

class Consistency {

   /** ==========================================================================
   * Constructor
   * @param $id the id of the object in the database (null if not stored yet)
   * @return void
   */ 
  function __construct($id = NULL, $withoutDependentObjects=false) {

  }

   /** ==========================================================================
   * Destructor
   * @return void
   */ 
  function __destruct() {
    
  }
  
  // =================================================================================================================
  // WBS Ordering
  // =================================================================================================================
  
  /**
   * Check consistency of WBS ordering
   * @param string $display
   * @param string $correct
   */
  public static function checkWbs($correct=false,$trace=false) {
    $pe=new PlanningElement();
    $peList=$pe->getSqlElementsFromCriteria(null,null,null,'wbsSortable asc');
    $lastWbs='';
    $lastPe=$pe;
    $errors=0;
    $arrayWbs=array();
    foreach ($peList as $idx=>$pe) {
      $currentWbs=$pe->wbsSortable;
      if ($trace) echo "$pe->wbsSortable - $pe->refType #$pe->refId - $pe->refName<br/>";
      // check for duplicate WBS
      if ($pe->wbsSortable==$lastWbs) {
        displayError(i18n("checkWbsDuplicate",array($lastWbs,i18n($lastPe->refType),$lastPe->refId,i18n($pe->refType),$pe->refId)));
        $errors++;
        if ($correct) {
          self::fixOrder($pe,"duplicate",$peList,$idx);
        }
      }
      // Check Parent
      $parentWbs='';
      if ($pe->topRefType and $pe->topRefId) {
        $key=$pe->topRefType.'#'.$pe->topRefId;
        $parentWbs=(isset($arrayWbs[$key]))?$arrayWbs[$key]:'';
        if ($parentWbs=='') {
          displayError(i18n("checkWbsParentNotFound",array($pe->topRefType,$pe->topRefId, i18n($pe->refType), $pe->refId)));
          $errors++;
          if ($correct) {
            displayError(i18n("checkCannotFix"),true);
          }
        }
      }
      if ($parentWbs and $parentWbs!=substr($pe->wbsSortable,0,strlen($parentWbs))) {
        displayError(i18n("checkWbsParentIssue",array($pe->wbsSortable,i18n($pe->refType),$pe->refId,$parentWbs,$pe->topRefType,$pe->topRefId)));
        $errors++;
        if ($correct) {
          self::fixOrder($pe,"parent",$peList,$idx);
        }
      } else if ($parentWbs and strlen($pe->wbsSortable)!=strlen($parentWbs)+4) {
        displayError(i18n("checkWbsParentIssue",array($pe->wbsSortable,i18n($pe->refType),$pe->refId,$parentWbs,$pe->topRefType,$pe->topRefId)));
        $errors++;
        if ($correct) {
          self::fixOrder($pe,"parent",$peList,$idx);
        }
      }
      // Check Order
      $order=substr($pe->wbsSortable,-3);
      if ($lastWbs==$parentWbs) { // Previous is parent, so must be 001
        if (intval($order)!=1) {
          displayError(i18n("checkWbsFirst", array($pe->wbsSortable,i18n($pe->refType),$pe->refId)));
          $errors++;
          if ($correct) {
            self::fixOrder($pe,"first",$peList,$idx);
          }
        }
      } else if (substr($lastWbs,0,-4)==$parentWbs) { // Previous has same root (same parent), number must be is sequence
        if (intval($order)!=intval(substr($lastWbs,-3))+1) {
          displayError(i18n("checkWbsOrder",array($pe->wbsSortable, i18n($pe->refType), $pe->refId, $lastWbs)));
          $errors++;
          if ($correct) {
            self::fixOrder($pe,"order",$peList,$idx);
          }
        }
      } else { // Change root, current numbering must be is sequence
        $rootPrev=substr($lastWbs,0,strlen($pe->wbsSortable));
        if (intval($order)!=intval(substr($rootPrev,-3))+1) {
          displayError(i18n("checkWbsOrder",array($pe->wbsSortable, i18n($pe->refType), $pe->refId,$lastWbs)));
          $errors++;
          if ($correct) {
            self::fixOrder($pe,"order",$peList,$idx);
          }
        }
      }
      // Check displayed wbs compared to wbsSortable
  
      // Check project order
      if ($pe->refType=='Project') {
        $prj=new Project($pe->refId);
        $pe=$prj->ProjectPlanningElement;
        if ($prj->sortOrder!=$pe->wbsSortable) {
          displayError(i18n("checkWbsSortOrderProject",array($prj->id,$prj->sortOrder,$pe->wbsSortable)));
          $errors++;
          if ($correct) {
            $prj->sortOrder=$pe->wbsSortable;
            $res=$prj->save();
            if (getLastOperationStatus($res)=='OK'  or getLastOperationStatus($res)=='NO_CHANGE') displayOK(i18n("checkFixed"),true);
            else displayError($res,true);
          }
        }
  
      }
      // Continue
      $key=$pe->refType.'#'.$pe->refId;
      $arrayWbs[$key]=$currentWbs;
      $lastWbs=$currentWbs;
      $lastPe=$pe;
    }
    if (!$errors) {
      displayOK(i18n("checkNoError"));
    }
  }

  private static function fixOrder($pe, $issue,$peList,$idx) {
    $actual=new PlanningElement($pe->id);
    if ($pe->wbsSortable!=$actual->wbsSortable) {
      displayOK(i18n("checkFixed"),true);
      return;
    }
    $action="unknown";
    $peNext=null;
    $pePrec=null;
    if ($issue=="duplicate" or $issue=="parent") { // Duplicate or inconsistent with Parent => just get a good one (order is sure incorrect)
      $action="recalculate";
    } else if ($issue=="first") {
      $action="recalculateLevel";
    } else if ($issue=="order") {
      $action="recalculate";
      $cur=$idx-1;
      while ($action=="recalculate" and $cur>=0) {
        $pePrec=$peList[$cur];
        if (substr($pe->wbsSortable,0,strlen($pe->wbsSortable)-3)!=substr($pePrec->wbsSortable,0,strlen($pe->wbsSortable)-3)) {
          $cur=-1;
          break;
        }
        if (strlen($pe->wbsSortable)==strlen($pePrec->wbsSortable)) {
          $action="moveFromPrec";
        }
        $cur--;
      }
    } else {
      displayError(i18n("checkCannotFix"),true);
    }
    if ($action=="recalculate") {
      $pe->wbs=null;
      $pe->wbsSortable=null;
      $res=$pe->save();
      if (getLastOperationStatus($res)=='OK' or getLastOperationStatus($res)=='NO_CHANGE') displayOK(i18n("checkFixed"),true);
      else displayError($res,true);
    } else if ($action=='moveFromPrec' and $pePrec) {
      $res=$pe->moveTo($pePrec->id,'after');
      if (getLastOperationStatus($res)=='OK' or getLastOperationStatus($res)=='NO_CHANGE') displayOK(i18n("checkFixed"),true);
      else displayError($res,true);
    } else if ($action=="recalculateLevel") {
      $where="topId=$pe->topId";
      $levelList=$pe->getSqlElementsFromCriteria(null,null,$where,'wbsSortable');
      if (count($levelList)==1) {
        $pe->wbs=null;
        $pe->wbsSortable=null;
        $res=$pe->save();
        if (getLastOperationStatus($res)=='OK' or getLastOperationStatus($res)=='NO_CHANGE') displayOK(i18n("checkFixed"),true);
        else displayError($res,true);
      } else {
        $first=$levelList[0];
        $second=$levelList[1];
        $res=$second->moveTo($first->id,'before');
        $first=new PlanningElement($first->id);
        $second=new PlanningElement($second->id);
        $res=$first->moveTo($second->id,'before');
        if (getLastOperationStatus($res)=='OK' or getLastOperationStatus($res)=='NO_CHANGE') displayOK(i18n("checkFixed"),true);
        else displayError($res,true);
      }
    } else {
      if ($issue!='') {
        displayError(i18n("checkCannotFix"),true);
      }
    }
  }
  
  // =================================================================================================================
  // Work Duplicate
  // =================================================================================================================
  
  public static function checkDuplicateWork($correct=false,$trace=false) {
    $errors=0;
    // Direct Query : valid here for technical needs on grouping
    $work=new Work();
    $workTable=$work->getDatabaseTableName();
    $query="SELECT idAssignment as idassignment, refType as reftype, refId as refid, idWorkElement as idworkelement, day as day, count(*) as cpt from $workTable group by idAssignment, refType, refId, idWorkElement, day having count(*)>1";
    $result=Sql::query($query);
    while ($line = Sql::fetchLine($result)) {
      $idAss=$line['idassignment'];
      $refType=$line['reftype'];
      $refId=$line['refid'];
      $idWork=$line['idworkelement'];
      $day=$line['day'];
      $cpt=$line['cpt'];
      $lstWork=$work->getSqlElementsFromCriteria(array('idAssignment'=>$idAss,'day'=>$day, 'refType'=>$refType, 'refId'=>$refId, 'idWorkElement'=>$idWork),null,'id asc');
      $wk=reset($lstWork);
      $resName=SqlList::getNameFromId('Affectable', $wk->idResource);
      displayError(i18n("checkDuplicateWorkFound",array($resName,htmlFormatDate($wk->workDate),i18n($wk->refType),$wk->refId)));
      $errors++;
      if ($correct) {
        $nb=0;
        $res='';
        foreach ($lstWork as $work) {
          if ($nb==0 and $work->work!=0) {
            $nb++;
            // Do not delete first not null
          } else {            
            $res=$work->delete();
          }
          
        }
        if (getLastOperationStatus($res)=='OK'  or getLastOperationStatus($res)=='NO_CHANGE') displayOK(i18n("checkFixed"),true);
        else displayError($res,true);
      }
    }
    if (!$errors) {
      displayOK(i18n("checkNoError"));
      
    }
  }
}

?>