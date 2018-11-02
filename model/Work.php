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
 * RiskType defines the type of a risk.
 */ 
require_once('_securityCheck.php');
class Work extends GeneralWork {

	 public $idBill;
	 public $idWorkElement;
	 
	 private static $_colCaptionTransposition = array(
	     'workDate'=>'date'
	 );
	 private static $_fieldsAttributes=array(
	     "day"=>"hidden,noExport,noImport",
	     "week"=>"hidden,noExport,noImport",
	     "month"=>"hidden,noExport,noImport",
	     "year"=>"hidden,noExport,noImport",
	     "dailyCost"=>"hidden,noExport,noImport",
	     "idWorkElement"=>"hidden,noExport,noImport",
	     "idBill"=>"hidden,noExport"
	 );
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
  
  /** ============================================================================
   * Return the specific colCaptionTransposition
   * @return the colCaptionTransposition
   */
  protected function getStaticColCaptionTransposition($fld=null) {
    return self::$_colCaptionTransposition;
  }
  /** ==========================================================================
   * Return the specific fieldsAttributes
   * @return the fieldsAttributes
   */
  protected function getStaticFieldsAttributes() {
    return self::$_fieldsAttributes;
  }
  // ================================================================================================
  //
  // ================================================================================================
  
  public function control(){
    $result="";
    $crit=array('periodValue'=>$this->week,
                'idResource'=>$this->idResource);
    $obj=SqlElement::getSingleSqlElementFromCriteria('WorkPeriod', $crit);
    if ($obj->validated=='1' or $obj->submitted=='1') {
      $result.='<br/>' . i18n('errorWeekValidated');
    }
    $defaultControl=parent::control();
    if ($defaultControl!='OK') {
      $result.=$defaultControl;
    }
    if ($result=="") {
      $result='OK';
    }
    return $result;
  }
  
  public function deleteControl() {
    $result='';
    $crit=array('periodValue'=>$this->week,
                'idResource'=>$this->idResource);
    $obj=SqlElement::getSingleSqlElementFromCriteria('WorkPeriod', $crit);
    if ($obj->validated=='1' or $obj->submitted=='1') {
      $result.='<br/>' . i18n('msgUnableToDeleteRealWork');
    }
    if ($result=='') {
      $result .= parent::deleteControl();
    }
    return $result;
  }
  
  function save() {
    // On saving remove corresponding planned work if exists
    $oldWork=0;
    if ($this->id) { // Update existing
      $old=new Work($this->id);
      $oldWork=$old->work;
    }
    $additionalWork=$this->work-$oldWork;
    if ($additionalWork>0) {
      $pw=new PlannedWork();
      $crit=array('idAssignment'=>$this->idAssignment, 
                  'refType'=>$this->refType, 'refId'=>$this->refId, 
                  'idResource'=>$this->idResource,
                  'workDate'=>$this->workDate);
      $list=$pw->getSqlElementsFromCriteria($crit, null, null, 'workDate asc');
      $needReplanOtherProjects=(count($list)==0)?true:false;
      while ($additionalWork>0 and count($list)>0) {
        $pw=array_shift($list);
        if ($pw->work > $additionalWork) {
          $pw->work-=$additionalWork;
          $pw->save();
          $additionalWork=0;
        } else {
          $additionalWork-=$pw->work;
          $pw->delete();
        }
        if (count($list)==0 and isset($crit['workDate']) ) {
          $needReplanOtherProjects=true;
          unset($crit['workDate']);
          $list=$pw->getSqlElementsFromCriteria($crit, null, null, 'workDate asc');
        }
      }
      if ($needReplanOtherProjects) {
        $where="idResource=$this->idResource and workDate='$this->workDate' and idProject!=$this->idProject";
        $list=$pw->getSqlElementsFromCriteria(null, null, $where, 'workDate asc');
        $arrayProjTreated=array();
        foreach ($list as $pw) {
          if (!isset($arrayProjTreated[$pw->idProject])) {
            $arrayProjTreated[$pw->idProject]=$pw->idProject;
            Project::setNeedReplan($pw->idProject);
          }
        }
      }
    }   
    return parent::save();
  }
  
  public function saveWork() {
    if ($this->id) { // update existing work
      $old=$this->getOld();
      $result=$this->save();
      $this->updateAssignment($this->work-$old->work);
      return $result;
    } else { // add new work
      if (! $this->idResource and ! $this->idAssignment) { // idResource Mandatory
        return "ERROR idResouce mandatory";
      }
      if (! $this->workDate) { 
        if ($this->day) {
          $this->workDate=substr($this->day,0,4).'-'.substr($this->day,4,2).'-'.substr($this->day,6,2);
        } else { // Work Date is mandatory
          return "ERROR workDate mandatory";
        }
      }
      if (!$this->idAssignment) { // unknown assignment
        if ($this->refType and $this->refId) {
          $crit=array('refType'=>$this->refType,'refId'=>$this->refId,'idResource'=>$this->idResource);
          $ass=SqlElement::getSingleSqlElementFromCriteria('Assignment', $crit);
          if ($ass->id) {
            $this->idAssignment=$ass->id;
          } else {
            return "ERROR idAssignment mandatory"; // could not retrieve assignment, so is mandatory
          }
        }
      } else { // refType & refId can be retreived from assignment
        $ass=new Assignment($this->idAssignment);
        $this->refType=$ass->refType;
        $this->refId=$ass->refId;
        $this->idResource=$ass->idResource;
      }
      $crit=array('idAssignment'=>$this->idAssignment,'workDate'=>$this->workDate); // retreive work for this assignment & day (assignment includes resource)
      $work=SqlElement::getSingleSqlElementFromCriteria('Work', $crit);
      if ($work->id) {
        $work->work+=$this->work;
        $result=$work->save();
        $work->updateAssignment($this->work);
        return $result;
      } else {
        $this->setDates($this->workDate);
        $result=$this->save();
        $work->updateAssignment($this->work);
        return $result;
      }
    }
  }
   
  public function deleteWork() {
    $result=$this->delete();
    $this->updateAssignment($this->work*(-1));
    return $result;
  }

  public function updateAssignment($decrementLeftWork=0) {
    $ass=new Assignment($this->idAssignment);
    $ass->leftWork-=$decrementLeftWork; // Remove current work from left work
    if ($ass->leftWork<0) $ass->leftWork=0;
    $resultAss=$ass->saveWithRefresh();
    return $resultAss;
  }
  
  public function getMenuClass() {
    return "menuActivity";
  }
}
?>