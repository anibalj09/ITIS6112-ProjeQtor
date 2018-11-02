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
class Version extends SqlElement {

  // List of fields that will be exposed in general user interface
  public $_sec_Description;
  public $id;    // redefine $id to specify its visible place 
  public $scope;
  public $idProduct;
  public $versionNumber;
  public $name;
  public $idContact;
  public $idResource;
  public $creationDate;
  //CHANGE qCazelles - dateComposition
  //OLD
  //public $_tab_4_2 = array('initial', 'planned', 'real', 'done', 'eisDate', 'endDate');
  //NEW
  public $_tab_4_4 = array('initial', 'planned', 'real', 'done', 'startDate', 'deliveryDate', 'eisDate', 'endDate');
  //ADD
  public $initialStartDate;
  public $plannedStartDate;
  public $realStartDate;
  public $isStarted;
  public $initialDeliveryDate;
  public $plannedDeliveryDate;
  public $realDeliveryDate;
  public $isDelivered;
  //END ADD qCazelles - dateComposition
  public $initialEisDate;
  public $plannedEisDate;
  public $realEisDate;
  public $isEis;
  public $initialEndDate;
  public $plannedEndDate;
  public $realEndDate;
  public $idle;
  public $idVersionType; //ADD PBE - Ticket #53
  public $idStatus; //ADD qCazelles - Ticket #53
  public $description;
  public $_Attachment=array();
  public $_Note=array();
  
  // Define the layout that will be used for lists
  private static $_layout='
    <th field="id" formatter="numericFormatter" width="5%" ># ${id}</th>
    <th field="name" width="20%" >${versionName}</th>
    <th field="nameProduct" width="25%" >${productName}</th>
    <th field="plannedEisDate" width="10%" formatter="dateFormatter">${plannedEis}</th>
    <th field="realEisDate" width="10%" formatter="dateFormatter">${realEis}</th>
    <th field="plannedEndDate" width="10%" formatter="dateFormatter">${plannedEnd}</th>
    <th field="realEndDate" width="10%" formatter="dateFormatter">${realEnd}</th>
    <th field="isEis" width="5%" formatter="booleanFormatter" >${isEis}</th>
    <th field="idle" width="5%" formatter="booleanFormatter" >${idle}</th>
    ';

  private static $_fieldsAttributes=array("name"=>"required", 
      "idProduct"=>"required",
      "idStatus"=>"required" //ADD qCazelles - Ticket #53
  );   

  private static $_colCaptionTransposition = array('idContact'=>'contractor', 'idResource'=>'responsible'
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
// ============================================================================**********
// GET VALIDATION SCRIPT
// ============================================================================**********
  
  /** ==========================================================================
   * Return the validation sript for some fields
   * @return the validation javascript (for dojo frameword)
   */
  public function getValidationScript($colName) {
    $colScript = parent::getValidationScript($colName);
    if ($colName=="initialEisDate") {   
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= 'if (! dijit.byId("plannedEisDate").get("value")) {'; 
      $colScript .= '  dijit.byId("plannedEisDate").set("value",this.value);'; 
      $colScript .= '};';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }
    if ($colName=="initialEndDate") {   
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= 'if (! dijit.byId("plannedEndDate").get("value")) {'; 
      $colScript .= '  dijit.byId("plannedEndDate").set("value",this.value);'; 
      $colScript .= '};';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }
    if ($colName=="realEisDate") {   
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= 'if (this.value) {'; 
      $colScript .= '  dijit.byId("isEis").set("checked",true);';
      $colScript .= '} else {;';
      $colScript .= '  dijit.byId("isEis").set("checked",false);';
      $colScript .= '};'; 
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }
    if ($colName=="isEis") { 
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= 'if (this.checked) { ';
      $colScript .= '  if (! dijit.byId("realEisDate").get("value")) {';
      $colScript .= '    var curDate = new Date();';
      $colScript .= '    dijit.byId("realEisDate").set("value", curDate); ';
      //ADD qCazelles - Ticket #53
      $colScript .= '    if (dijit.byId("isStarted") && !dijit.byId("isStarted").checked) {';
      $colScript .= '       dijit.byId("isStarted").set("checked", true);';
      $colScript .= '    }';
      $colScript .= '    if (dijit.byId("isDelivered") && !dijit.byId("isDelivered").checked) {';
      $colScript .= '       dijit.byId("isDelivered").set("checked", true);';
      $colScript .= '    }';
      //END ADD qCazelles - Ticket #53
      $colScript .= '  }';
      //BEGIN - ADD qCazelles #187
      $colScript .= '  dijit.byId("initialEisDate").set("readOnly", true);';
      $colScript .= '  dijit.byId("plannedEisDate").set("readOnly", true);';
      $colScript .= '  dijit.byId("realEisDate").set("readOnly", true);';
      $colScript .= '  dijit.byId("isDelivered").set("readOnly", true);';
      //END - ADD qCazelles #187
      $colScript .= '} else {;';    
      $colScript .= '  dijit.byId("realEisDate").set("value", null); ';
      //BEGIN - ADD qCazelles #187
      $colScript .= '  dijit.byId("initialEisDate").set("readOnly", false);';
      $colScript .= '  dijit.byId("plannedEisDate").set("readOnly", false);';
      $colScript .= '  dijit.byId("realEisDate").set("readOnly", false);';
      $colScript .= '  dijit.byId("isDelivered").set("readOnly", false);';
      //END - ADD qCazelles #187
      $colScript .= '};';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';  
    }
    if ($colName=="realEndDate") {   
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= 'if (this.value) {'; 
      $colScript .= '  dijit.byId("idle").set("checked",true);'; 
      $colScript .= '} else {;';
      $colScript .= '  dijit.byId("idle").set("checked",false);';
      $colScript .= '};'; 
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }
    if ($colName=="idle") { 
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= 'if (this.checked) { ';
      $colScript .= '  if (! dijit.byId("realEndDate").get("value")) {';
      $colScript .= '    var curDate = new Date();';
      $colScript .= '    dijit.byId("realEndDate").set("value", curDate); ';
      $colScript .= '  }';
      //BEGIN - ADD qCazelles #187
      $colScript .= '  dijit.byId("initialEndDate").set("readOnly", true);';
      $colScript .= '  dijit.byId("plannedEndDate").set("readOnly", true);';
      $colScript .= '  dijit.byId("realEndDate").set("readOnly", true);';
      $colScript .= '  dijit.byId("isEis").set("readOnly", true);';
      //END - ADD qCazelles #187
      $colScript .= '} else {;';    
      $colScript .= '  dijit.byId("realEndDate").set("value", null); '; 
      //BEGIN - ADD qCazelles #187
      $colScript .= '  dijit.byId("initialEndDate").set("readOnly", false);';
      $colScript .= '  dijit.byId("plannedEndDate").set("readOnly", false);';
      $colScript .= '  dijit.byId("realEndDate").set("readOnly", false);';
      $colScript .= '  dijit.byId("isEis").set("readOnly", false);';
      //END - ADD qCazelles #187
      $colScript .= '};';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';  
    }
    //ADD qCazelles - dateComposition
    if ($colName=="initialStartDate") {
    	$colScript .= '<script type="dojo/connect" event="onChange" >';
    	$colScript .= 'if (! dijit.byId("plannedStartDate").get("value")) {';
    	$colScript .= '  dijit.byId("plannedStartDate").set("value",this.value);';
    	$colScript .= '};';
    	$colScript .= '  formChanged();';
    	$colScript .= '</script>';
    }
    if ($colName=="realStartDate") {
    	$colScript .= '<script type="dojo/connect" event="onChange" >';
    	$colScript .= 'if (this.value) {';
    	$colScript .= '  dijit.byId("isStarted").set("checked",true);';
    	$colScript .= '} else {;';
    	$colScript .= '  dijit.byId("isStarted").set("checked",false);';
    	$colScript .= '};';
    	$colScript .= '  formChanged();';
    	$colScript .= '</script>';
    }
    if ($colName=="isStarted") {
    	$colScript .= '<script type="dojo/connect" event="onChange" >';
    	$colScript .= 'if (this.checked) { ';
    	$colScript .= '  if (! dijit.byId("realStartDate").get("value")) {';
    	$colScript .= '    var curDate = new Date();';
    	$colScript .= '    dijit.byId("realStartDate").set("value", curDate); ';
    	$colScript .= '  }';
    	//BEGIN - ADD qCazelles #187
    	$colScript .= '  dijit.byId("initialStartDate").set("readOnly", true);';
    	$colScript .= '  dijit.byId("plannedStartDate").set("readOnly", true);';
    	$colScript .= '  dijit.byId("realStartDate").set("readOnly", true);';
    	//END - ADD qCazelles #187
    	$colScript .= '} else {;';
    	$colScript .= '  dijit.byId("realStartDate").set("value", null); ';
    	//BEGIN - ADD qCazelles #187
    	$colScript .= '  dijit.byId("initialStartDate").set("readOnly", false);';
    	$colScript .= '  dijit.byId("plannedStartDate").set("readOnly", false);';
    	$colScript .= '  dijit.byId("realStartDate").set("readOnly", false);';
    	//END - ADD qCazelles #187
    	$colScript .= '};';
    	$colScript .= '  formChanged();';
    	$colScript .= '</script>';
    }
    if ($colName=="initialDeliveryDate") {
    	$colScript .= '<script type="dojo/connect" event="onChange" >';
    	$colScript .= 'if (! dijit.byId("plannedDeliveryDate").get("value")) {';
    	$colScript .= '  dijit.byId("plannedDeliveryDate").set("value",this.value);';
    	$colScript .= '};';
    	$colScript .= '  formChanged();';
    	$colScript .= '</script>';
    }
    if ($colName=="realDeliveryDate") {
    	$colScript .= '<script type="dojo/connect" event="onChange" >';
    	$colScript .= 'if (this.value) {';
    	$colScript .= '  dijit.byId("isDelivered").set("checked",true);';
    	$colScript .= '} else {;';
    	$colScript .= '  dijit.byId("isDelivered").set("checked",false);';
    	$colScript .= '};';
    	$colScript .= '  formChanged();';
    	$colScript .= '</script>';
    }
    if ($colName=="isDelivered") {
    	$colScript .= '<script type="dojo/connect" event="onChange" >';
    	$colScript .= 'if (this.checked) { ';
    	$colScript .= '  if (! dijit.byId("realDeliveryDate").get("value")) {';
    	$colScript .= '    var curDate = new Date();';
    	$colScript .= '    dijit.byId("realDeliveryDate").set("value", curDate); ';
    	//ADD qCazelles - Ticket #53
    	$colScript .= '    if (!dijit.byId("isStarted").checked) {';
    	$colScript .= '       dijit.byId("isStarted").set("checked", true);';
      $colScript .= '    }';
      //END ADD qCazelles - Ticket #53
    	$colScript .= '  }';
    	//BEGIN - ADD qCazelles #187
    	$colScript .= '  dijit.byId("initialDeliveryDate").set("readOnly", true);';
    	$colScript .= '  dijit.byId("plannedDeliveryDate").set("readOnly", true);';
    	$colScript .= '  dijit.byId("realDeliveryDate").set("readOnly", true);';
    	$colScript .= '  dijit.byId("isStarted").set("readOnly", true);';
    	//END - ADD qCazelles #187
    	$colScript .= '} else {;';
    	$colScript .= '  dijit.byId("realDeliveryDate").set("value", null); ';
    	//BEGIN - ADD qCazelles #187
    	$colScript .= '  dijit.byId("initialDeliveryDate").set("readOnly", false);';
    	$colScript .= '  dijit.byId("plannedDeliveryDate").set("readOnly", false);';
    	$colScript .= '  dijit.byId("realDeliveryDate").set("readOnly", false);';
    	$colScript .= '  dijit.byId("isStarted").set("readOnly", false);';
    	//END - ADD qCazelles #187
    	$colScript .= '};';
    	$colScript .= '  formChanged();';
    	$colScript .= '</script>';
    }
    //END ADD qCazelles - dateComposition
    //ADD qCazelles - Ticket #53
    if ($colName=="idStatus") {
      if (Parameter::getGlobalParameter('displayMilestonesStartDelivery') == 'YES') {
        $colScript .= '<script type="dojo/connect" event="onChange" >';
        $colScript .= htmlGetJsTable ( 'Status', 'setHandledStatus', 'tabStatusHandled' );
        $colScript .= '  var setHandled=0;';
        $colScript .= '  var filterStatusHandled=dojo.filter(tabStatusHandled, function(item){return item.id==dijit.byId("idStatus").value;});';
        $colScript .= '  dojo.forEach(filterStatusHandled, function(item, i) {setHandled=item.setHandledStatus;});';
        $colScript .= '  if (setHandled==1) {';
        $colScript .= '    dijit.byId("isStarted").set("checked", true);';
        $colScript .= '    dijit.byId("isDelivered").set("checked", false);';
        $colScript .= '    dijit.byId("isEis").set("checked", false);';
        $colScript .= '    dijit.byId("idle").set("checked", false);';
        $colScript .= '  }';
        $colScript .= htmlGetJsTable ( 'Status', 'setDoneStatus', 'tabStatusDone' );
        $colScript .= '  var setDone=0;';
        $colScript .= '  var filterStatusDone=dojo.filter(tabStatusDone, function(item){return item.id==dijit.byId("idStatus").value;});';
        $colScript .= '  dojo.forEach(filterStatusDone, function(item, i) {setDone=item.setDoneStatus;});';
        $colScript .= '  if (setDone==1) {';
        $colScript .= '    dijit.byId("isDelivered").set("checked", true);';
        $colScript .= '    dijit.byId("isEis").set("checked", false);';
        $colScript .= '    dijit.byId("idle").set("checked", false);';
        $colScript .= '  }';
      }
      $colScript .= htmlGetJsTable ( 'Status', 'setIntoserviceStatus', 'tabStatusIntoservice' );
      $colScript .= '  var setIntoservice=0;';
      $colScript .= '  var filterStatusIntoservice=dojo.filter(tabStatusIntoservice, function(item){return item.id==dijit.byId("idStatus").value});';
      $colScript .= '  dojo.forEach(filterStatusIntoservice, function(item, i) {setIntoservice=item.setIntoserviceStatus;});';
      $colScript .= '  if (setIntoservice==1) {';
      $colScript .= '     dijit.byId("isEis").set("checked", true);';
      $colScript .= '     dijit.byId("idle").set("chekced", false);';
      $colScript .= '  }';
      $colScript .= htmlGetJsTable ( 'Status', 'setIdleStatus', 'tabStatusIdle' );
      $colScript .= '  var setIdle=0;';
      $colScript .= '  var filterStatusIdle=dojo.filter(tabStatusIdle, function(item){return item.id==dijit.byId("idStatus").value;});';
      $colScript .= '  dojo.forEach(filterStatusIdle, function(item, i) {setIdle=item.setIdleStatus;});';
      $colScript .= '  if (setIdle==1) {';
      $colScript .= '    dijit.byId("idle").set("checked", true);';
      $colScript .= '  }';
      $colScript .= '</script>';
    }
    //END ADD qCazelles - Ticket #53
    
    return $colScript;
  }
  
// ============================================================================**********
// MISCELLANOUS FUNCTIONS
// ============================================================================**********
  

  /** =========================================================================
   * Draw a specific item for the current class.
   * @param $item the item. Correct values are : 
   *    - subprojects => presents sub-projects as a tree
   * @return an html string able to display a specific item
   *  must be redefined in the inherited class
   */
  public function drawSpecificItem($item){
    $result="";
    if ($item=='XXXVersionProjects') {
      $result .="<table><tr><td class='label' valign='top'><label>" . i18n('versions') . "&nbsp;:&nbsp;</label>";
      $result .="</td><td>";
      if ($this->id) {
        $result .= "xx";
      }
      $result .="</td></tr></table>";
      return $result;
    } 
  }
  
  public function drawVersionsList($critArray,$withProjects=false) {
    $result="<table>";
    $versList=$this->getSqlElementsFromCriteria($critArray,false,null,'name asc',false,true);
    foreach ($versList as $vers) {
      $result.= '<tr>';
      $result.= '<td style="padding-left:15px;">&nbsp;&nbsp;</td>';
      $result.= '<td valign="top" style="padding-left:5px;width:20px;height:16px;	" class="icon'.$vers->scope.'Version16" >&nbsp;</td>';
      $style="";
      if ($vers->idle) {$style='color#5555;text-decoration: line-through;';}
      else if ($vers->isEis) {$style='font-weight: bold;';}
      $result.= '<td style="vertical-align:top;'.$style.'">';   
      $result.="#$vers->id - ".htmlDrawLink($vers);
      if ($withProjects) {
        //$result.='<td>';
        $vp=new VersionProject();
        $vpList=$vp->getSqlElementsFromCriteria(array('idVersion'=>$vers->id),false,null,null,false,true);
        $result.= '<table>';
        foreach ($vpList as $vp) {
          $result.= '<tr>';
          $result.= '<td style="padding-left:15px;">&nbsp;&nbsp;</td>';
          $result.= '<td valign="top" style="padding-left:5px;width:20px;height:16px" class="iconProject16" >&nbsp;</td>';
          $result.= '<td style="vertical-align:top;">'.SqlList::getNameFromId('Project', $vp->idProject).'</td>';
          $result.= '</tr>';
        }
        $result.= '</table>';
        //$result.='</td>';
      }
      $result.= '</td></tr>';
    }
    $result .="</table>";
    return $result; 
  }
  
  public function save() {
  	$result=parent::save();
    if (! strpos($result,'id="lastOperationStatus" value="OK"')) {
      return $result;     
    }
  	if ($this->idle) {
  		VersionProject::updateIdle('Version', $this->id);
  	}
  	return $result;
  }
  
  /** =========================================================================
   * control data corresponding to Model constraints
   * @param void
   * @return "OK" if controls are good or an error message
   *  must be redefined in the inherited class
   */
  public function control(){
    $result="";
  
    if (trim($this->versionNumber)) {
      $cpt=$this->countSqlElementsFromCriteria(null,"idProduct=".Sql::fmtId($this->idProduct)." and versionNumber='$this->versionNumber' and id!=".Sql::fmtId($this->id));
      if ($cpt>0) {
        $result.="<br/>" . i18n('errorDuplicate');
      }
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
  static protected function drawFlatStructureButton($class,$id) {
    global $print;
    if ($print) return "";
    $result='<br/><table>';
    $result.='<tr><td>';
    $result.='<button id="showFlatStructureButton" dojoType="dijit.form.Button" showlabel="true"';
    $result.=' title="'.i18n('showFlatStructure').'" style="vertical-align: middle;">';
    $result.='<span>' . i18n('showFlatStructure') . '</span>';
    $result.='<script type="dojo/connect" event="onClick" args="evt">';
    $page="../report/productVersionFlatStructure.php?objectClass=$class&objectId=$id";
    $result.="var url='$page';";
    $result.='url+="&format=print";';
    $result.='showPrint(url, null, null, "html", "P");';
    $result.='</script>';
    $result.='</button>';
    $result.='<button id="showFlatStructureButtonCsv" dojoType="dijit.form.Button" showlabel="false" ';
    $result.=' title="'.i18n('showFlatStructure').'" iconClass="dijitButtonIcon dijitButtonIconCsv" class="roundedButtonSmall">';
    $result.='<script type="dojo/connect" event="onClick" args="evt">';
    $page="../report/productVersionFlatStructure.php?objectClass=$class&objectId=$id";
    $result.="var url='$page';";
    $result.='url+="&format=csv";';
    $result.='showPrint(url, null, null, "csv", "P");';
    $result.='</script>';
    $result.='</button>';
    $result.='</td>';
    $result.='</tr></table>';
    return $result;
  }
  
  //gautier #subscription
  public function changeVersionOfProduct(){
    if(Parameter::getGlobalParameter('subscriptionAuto')!='YES'){ return;}
    $sub = new Subscription();
    $crit = array('refId'=>$this->id, 'refType'=> $this->scope.'Version', 'isAutoSub'=>'1');
    $list=$sub->getSqlElementsFromCriteria($crit,null,null,null,null,true);
    foreach ($list as $subList){
      $subList->delete();
    }

  }
  
  public function addVersionSubProduct(){
    if(Parameter::getGlobalParameter('subscriptionAuto')!='YES'){ return;}
    $prod = new ProductOrComponent($this->idProduct);
    $sub = new Subscription();
    $crit = array('refId'=>$prod->id, 'refType'=> $prod->scope);
    $list=$sub->getSqlElementsFromCriteria($crit,null,null,null,null,true);
    foreach($list as $subs){
      if($prod->scope == 'Product'){
        $sub2RefType='ProductVersion';
      }else{
        $sub2RefType='ComponentVersion';
      }
      $sub2 = SqlElement::getSingleSqlElementFromCriteria('Subscription', array('refType'=>$sub2RefType, 'refId'=>$this->id,'idAffectable'=>$subs->idAffectable));
      if ($sub2->id) continue;
      //$sub2->idAffectable=$subs->idAffectable;
      //$sub2->refType=$sub2RefType;
      //$sub2->refId=$this->id;
      $sub2->idUser=getSessionUser()->id;
      $sub2->creationDateTime=date('Y-m-d H:i:s');
      $sub2->isAutoSub=1;
      $sub2->save();
    }
  }
  
  //ADD qCazelles - GANTT
  
  protected static $tabHasChild = array();
  protected static $cpt = 0;
  //ADD qCazelles - Correction GANTT - Ticket #100
  protected static $idVersionsDisplayed=array();
  //END ADD qCazelles - Correction GANTT - Ticket #100
  protected static $existingIDs = array();
  protected static $tabDirectChild = array();
  
  public function treatmentVersionPlanning ($parentVersion) {    
    if ($this->directChild($parentVersion)) {
      $this->displayVersion($parentVersion);
   
      if ($this->hasChild()) {
        foreach (ProductVersionStructure::getComposition($this->id) as $key => $idComponentVersion) {
          $componentVersion = new ComponentVersion($idComponentVersion);
          $hide=SqlList::getFieldFromId('ComponentVersionType', $componentVersion->idComponentVersionType, 'lockUseOnlyForCC');
          if ($hide!=1) $componentVersion->treatmentVersionPlanning($this);
        }
      }
    }
  }
  
  public function displayVersion($parentVersion = NULL) {    
    if (self::$cpt === 1) {
      echo ',';
    }
    
    //ADD qCazelles - Correction GANTT - Ticket #100
    if (!in_array($this->id, self::$idVersionsDisplayed)) {
      self::$idVersionsDisplayed[] = $this->id;
    }
    //END ADD qCazelles - Correction GANTT - Ticket #100
    
    if ( !isset($this->nbOccurences)) {
      $this->nbOccurences = 1;
    }

    while (in_array($this->id.'.'.$this->nbOccurences, self::$existingIDs)) {
      $this->nbOccurences += 1;
    }
    
    $idPE = $this->id.'.'.$this->nbOccurences;
    self::$existingIDs[] = $idPE;
    
    echo '{';
    echo '"id":"'.$idPE.'"';
    echo ',"refname":"'.$this->name.'"';
    //echo ',"refname":"'.$this->name.' - ID : '.$idPE.' - '.(($parentVersion != NULL) ? "TOPID : $parentVersion->id.$parentVersion->nbOccurences" : "").'"';
    
    if ($parentVersion != NULL) {
      echo ',"topid":"'.$parentVersion->id.'.'.$parentVersion->nbOccurences.'"';
      echo ',"topreftype":"'.$parentVersion->scope.'VersionhasChild"';
      echo ',"toprefid":"'.$parentVersion->id.'"';
    }
    
    if ( !$this->hasChild()) {
      echo ',"reftype":"'.$this->scope.'Version"';
    }
    else {
      echo ',"reftype":"'.$this->scope.'VersionhasChild"';
    }
    
    echo ',"refid":"'.$this->id.'"';
    echo ',"collapsed":"0"';
    
    $startDate = $this->startDateVersionsPlanning($parentVersion);
    $endDate = $this->endDateVersionsPlanning($parentVersion);
    
    $this->ownDate=true;

    if ($parentVersion == NULL and empty($startDate) and empty($endDate)) {
      $this->ownDate = false;
    }

    if ($this->isDelivered or $this->isEis) {
      echo ',"redElement":"0"';
    }
    elseif (!$this->ownDate or strtotime($this->plannedDeliveryDate) < time() or $this->hasRedChild() or $this->hasRedParent()) {
      echo ',"redElement":"1"';
    }
    else {
      echo ',"redElement":"0"';
    }

    echo ',"status":"'.$this->statusVersionPlanning().'"';
    
    $this->myStartDate = $startDate;
    $this->myEndDate = $endDate;
    
    echo ',"realstartdate":"'.$startDate.'"';
    echo ',"realenddate":"'.$endDate.'"';
    echo '}';
    self::$cpt = 1;
  }
  //ADD qCazelles - Correction GANTT - Ticket #100
  protected function startDateVersionsPlanning($parentVersion=null) {
    $startDate = '';
    if ($this->realStartDate) {
      $startDate = $this->realStartDate;
    }
    elseif ($this->plannedStartDate) {
      $startDate = $this->plannedStartDate;
    }
    elseif ($this->initialStartDate) {
      $startDate = $this->initialStartDate;
    }
    
    if ($parentVersion != NULL and empty($startDate)) {
      if ($parentVersion->realStartDate) {
        $startDate = $parentVersion->realStartDate;
      }
      elseif ($parentVersion->plannedStartDate) {
        $startDate = $parentVersion->plannedStartDate;
      }
      elseif ($parentVersion->initialStartDate) {
        $startDate = $parentVersion->initialStartDate;
      }
      else {
        $startDate = $parentVersion->myStartDate;
      }
      $this->ownDate = false;
    }
    return $startDate;
  }
  
  protected function endDateVersionsPlanning($parentVersion=null) {
    $deliveryDate = '';
    if ($this->realDeliveryDate) {
      $deliveryDate = $this->realDeliveryDate;
    }
    elseif ($this->plannedDeliveryDate) {
      $deliveryDate = $this->plannedDeliveryDate;
    }
    elseif ($this->initialDeliveryDate) {
      $deliveryDate = $this->initialDeliveryDate;
    }
    elseif ($this->realEisDate) {
      $deliveryDate = $this->realEisDate;
    }
    elseif ($this->plannedEisDate) {
      $deliveryDate = $this->plannedEisDate;
    }
    
    if ($parentVersion != NULL and empty($endDate)) {
      if ($parentVersion->realDeliveryDate) {
        $endDate= $parentVersion->realDeliveryDate;
      }
      elseif ($parentVersion->plannedDeliveryDate) {
        $endDate= $parentVersion->plannedDeliveryDate;
      }
      elseif ($parentVersion->initialDeliveryDate) {
        $endDate= $parentVersion->initialDeliveryDate;
      }
      else {
        $endDate= $parentVersion->myEndDate;
      }
      $this->ownDate = false;
    }
    return $deliveryDate;
  }
  
  protected function isRed($version,$way) {
    if ($this->isDelivered or $this->isEis) {
      return false;
    }
    if (strtotime($this->plannedDeliveryDate) < time()) {
      return true;
    }
    if ($version->isDelivered or $version->isEis) {
      return false;
    }
    if ($way == 'composition') {
      return strtotime($this->plannedDeliveryDate) > strtotime($version->plannedDeliveryDate);
    }
    if ($way == 'structure') {
      return strtotime($this->plannedDeliveryDate) < strtotime($version->plannedDeliveryDate);
    }

  }
  
  protected function hasRedChild() {
    if (!$this->hasChild()) return false;
    foreach (ProductVersionStructure::getComposition($this->id) as $key => $idComponentVersion) {
      $comp=new ComponentVersion($idComponentVersion,true);
      if ($comp->isRed($this,'composition')) {
        return true;
      }
    }
   return false;
  }
  
  protected function hasRedParent() {
    foreach (ProductVersionStructure::getStructure($this->id) as $key => $idVersion) {
      if ( !in_array($idVersion, self::$idVersionsDisplayed)) continue;
      $version=new Version($idVersion,true);
      if ($version->scope=='Product') $version=new ProductVersion($version->id);
      else $version=new ComponentVersion($version->id);
      if ($version->isRed($this,'structure')) {
        return true;
      }
    }
    return false;
  }
  
  protected function statusVersionPlanning() {
    $status = '';
    if ($this->idle) {
      $status = i18n('statusGanttClosed');
    }
    elseif (!$this->isStarted and !empty($this->plannedStartDate) and strtotime($this->plannedStartDate) - time() < 0) {
      $status = i18n('statusGanttStartDelayed');
    }
    elseif (!$this->isStarted and !$this->isEis and !$this->isDelivered) {
      $status = i18n('statusGanttNotStarted');
    }
    elseif ($this->isDelivered or $this->isEis) {
      $status = i18n('statusGanttDelivered');
    }
    elseif ($this->isStarted and (empty($deliveryDate) or (!empty($this->plannedDeliveryDate) and strtotime($this->plannedDeliveryDate) - time() > 0) or (!empty($this->plannedDeliveryDate) and strtotime($this->plannedEisDate) - time() > 0))) {
      $status = i18n('statusGanttInProgress');
    }
    elseif (!$this->isDelivered and $this->isEis and !empty($this->plannedDeliveryDate) and strtotime($this->plannedDeliveryDate) - time() < 0) {
      $status = i18n('statusGanttDeliveryDelayed');
    }
    return $status;
  }
  //END ADD qCazelles - Correction GANTT - Ticket #100
  
  protected function directChild($parentVersion) {
    
    if (array_key_exists($parentVersion->id, self::$tabDirectChild)) {
      if (array_key_exists($this->id, self::$tabDirectChild[$parentVersion->id])) {
        return self::$tabDirectChild[$parentVersion->id][$this->id];
      }
    }
    //CHANGE qCazelles - Correction GANTT - Ticket #100
    //Old
//     $query = 'select id from productversionstructure where idComponentVersion = "'.$this->id.'" and idProductVersion="'.$parentVersion->id.'"';
//     $result = Sql::query($query);
//     $line = Sql::fetchLine($result);
//     if ($line) {
    //New
    $pvs=new ProductVersionStructure();
    $crit=array('idComponentVersion'=>$this->id,'idProductVersion'=>$parentVersion->id);
    $list=$pvs->getSqlElementsFromCriteria($crit,null,null,null,null,true);
    if (count($list)>0) {
    //END CHANGE qCazelles - Correction GANTT - Ticket #100
      $directChild = true;
    }
    else {
      $directChild = false;
    }
    self::$tabDirectChild[$parentVersion->id][$this->id] = $directChild;
    return $directChild;
  }
  
  protected function hasChild() {
    
    if (array_key_exists($this->id, self::$tabHasChild)) {
      return self::$tabHasChild[$this->id];
    }
    //CHANGE qCazelles - Correction GANTT - Ticket #100
    //Old
//     $query = 'select * from productversionstructure where idProductVersion = "'.$this->id.'"';
//     $result = Sql::query($query);
//     $line = Sql::fetchLine($result);
//     if ($line) {
    //New
    $pvs=new ProductVersionStructure();
    $crit=array('idProductVersion'=>$this->id);
    $list=$pvs->getSqlElementsFromCriteria($crit,null,null,null,null,true);
    if (count($list)>0) {
    //END CHANGE qCazelles - Correction GANTT - Ticket #100
      $hasChild = true;
    }
    else {
      $hasChild = false;
    }
    self::$tabHasChild[$this->id] = $hasChild;
    return $hasChild;
  }
  
  //END ADD qCazelles - GANTT

}
?>