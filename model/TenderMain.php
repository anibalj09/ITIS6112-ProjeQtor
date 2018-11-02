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
 * Action is establised during meeting, to define an action to be followed.
 */ 
require_once('_securityCheck.php');
class TenderMain extends SqlElement {

  // List of fields that will be exposed in general user interface
  public $_sec_description;
  public $id;    // redefine $id to specify its visible place
  public $reference; 
  public $name;
  public $idTenderType;
  public $idProject;
  public $idCallForTender;
  public $idTenderStatus;
  public $idUser;
  public $creationDate;
  public $idProvider;
  public $externalReference;
  public $description;
  
  public $_sec_treatment;
  public $idStatus;  
  public $idResource;
  public $idContact;
  
  public $requestDateTime;
  public $expectedTenderDateTime;
  public $receptionDateTime;
  public $offerValidityEndDate;
  public $_tab_4_3_smallLabel = array('untaxedAmount', 'taxPct', 'taxAmount', 'fullAmount','initial','discount', 'countTotal');
  //init
  public $untaxedAmount;
  public $taxPct;
  public $taxAmount;
  public $fullAmount;
  //remise
  public $discountAmount;
  public $_label_rate;
  public $discountRate;
  public $discountFrom;
  //total
  public $totalUntaxedAmount;
  public $_void_2;
  public $totalTaxAmount;
  public $totalFullAmount;
  public $idProjectExpense;
  public $_button_generateProjectExpense;
  public $paymentCondition;
  public $deliveryDelay;
  public $deliveryDate;
  public $handled;
  public $handledDate;
  public $done;
  public $doneDate;
  public $idle;
  public $idleDate;
  public $cancelled;
  public $_lib_cancelled;
  public $result;
  
  public $_BillLine=array();
  public $_BillLine_colSpan="2";
  
  public $_sec_evaluation;
  public $_spe_evaluation;
  public $evaluationValue;
  public $evaluationRank;
  
  public $_sec_Link;
  public $_Link=array();
  public $_Attachment=array();
  public $_Note=array();
  
 

  public $_nbColMax=3;  
  // Define the layout that will be used for lists
  private static $_layout='
    <th field="id" formatter="numericFormatter" width="5%" ># ${id}</th>
    <th field="nameProject" width="10%" >${idProject}</th>
    <th field="nameTenderType" width="10%" >${type}</th>
    <th field="name" width="30%" >${name}</th>
    <th field="colorNameTenderStatus" width="10%" formatter="colorNameFormatter">${idTenderStatus}</th>
    <th field="evaluationValue" width="10%" >${evaluationValue}</th>
    <th field="totalUntaxedAmount" width="10%" formatter="amountFormatter">${totalUntaxedAmount}</th>
    <th field="colorNameStatus" width="10%" formatter="colorNameFormatter">${idStatus}</th>
    <th field="idle" width="5%" formatter="booleanFormatter" >${idle}</th>
    ';

  private static $_fieldsAttributes=array("id"=>"nobr", "reference"=>"readonly",
                                  "idProject"=>"required",
                                  "name"=>"required",
                                  "idTenderType"=>"required",
                                  "handled"=>"nobr",
                                  "done"=>"nobr",
                                  "idle"=>"nobr",
                                  "idleDate"=>"nobr",
                                  "cancelled"=>"nobr",
                                  "totalTaxAmount"=>"readonly",
                                  "taxAmount"=>"readonly",
                                  "fullAmount"=>"readonly",
                                  "totalUntaxedAmount"=>"readonly",
                                  "totalTaxAmount"=>"readonly",
                                  "totalFullAmount"=>"readonly",
                                  "idStatus"=>"required",
                                  "idTenderStatus"=>"",
                                  "evaluationValue"=>"readonly",
                                  "evaluationRank"=>"hidden,readonly",
                                  "idProvider"=>"required",
                                  "discountFrom"=>"hidden"
  );  
  
  private static $_colCaptionTransposition = array('idTenderType'=>'type', 'requestDateTime'=>'requestDate', 'expectedTenderDateTime'=>'expectedTenderDate',
     'idResource'=>'responsible' );
  
  private static $_databaseColumnName = array();
  
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
  
  /** ==========================================================================
   * Return the specific fieldsAttributes
   * @return the fieldsAttributes
   */
  protected function getStaticFieldsAttributes() {
    return self::$_fieldsAttributes;
  }
  
  /** ============================================================================
   * Return the specific colCaptionTransposition
   * @return the colCaptionTransposition
   */
  protected function getStaticColCaptionTransposition($fld=null) {
    return self::$_colCaptionTransposition;
  }
  
  /** ========================================================================
   * Return the specific databaseTableName
   * @return the databaseTableName
   */
  protected function getStaticDatabaseColumnName() {
    return self::$_databaseColumnName;
  }

  
  /**=========================================================================
   * Overrides SqlElement::save() function to add specific treatments
   * @see persistence/SqlElement#save()
   * @return the return message of persistence/SqlElement#save() method
   */
  public function save() {
    if (trim($this->idProvider)) {
      $provider=new Provider($this->idProvider);
      if ($provider->taxPct!='' and !$this->taxPct) {
        $this->taxPct=$provider->taxPct;
      }
    }
    
    //generate project expense
    if(RequestHandler::getBoolean('generateProjectExpenseButton')){
      $canCreate=securityGetAccessRightYesNo('menuProjectExpense', 'create')=="YES";
      if($canCreate){
        $projExpense = new ProjectExpense();
        $lstType=SqlList::getList('ProjectExpenseType');
        reset($lstType);
        $projExpense->idProjectExpenseType=key($lstType);
        $lstStatus=SqlList::getList('Status');
        reset($lstStatus);
        $projExpense->idStatus=key($lstStatus);
        $projExpense->name = $this->name;
        $projExpense->idProject = $this->idProject;
        $projExpense->taxPct = $this->taxPct;
        $projExpense->plannedAmount = $this->totalUntaxedAmount;
        $projExpense->plannedTaxAmount = $this->totalTaxAmount;
        $projExpense->plannedFullAmount = $this->totalFullAmount;
        if(trim($this->receptionDateTime)){
          $projExpense->expensePlannedDate = $this->receptionDateTime;
        }else if (trim($this->requestDateTime)){
          $projExpense->expensePlannedDate = $this->requestDateTime;
        }else{
          $projExpense->expensePlannedDate = date('Y-m-d');
        }
        $projExpense->save();
        $this->idProjectExpense = $projExpense->id;
      }
    }
    
    //convert project expense  to bill lines
    if($this->idProjectExpense){
      $billLine = new BillLine();
      $critArray=array('refType'=>'Tender','refId'=>$this->id);
      $cptBillLine=$billLine->countSqlElementsFromCriteria($critArray, false);
      if ($cptBillLine < 1) {
        $expD = new ExpenseDetail();
        $critArray=array('idExpense'=>$this->idProjectExpense);
        $listExpD = $expD->getSqlElementsFromCriteria($critArray);
        $number = 1;
        foreach ($listExpD as $exp){
          $billLine = new BillLine();
          $billLine->line = $number;
          $billLine->refType = 'Tender';
          $billLine->refId = $this->id;
          $billLine->price = $exp->amount;
          $billLine->quantity = 1;
          $billLine->save();
          $number++;
        }
      }
    }
    
    
    // Update amounts
    if ($this->untaxedAmount!=null) {
      if ($this->taxPct!=null) {
        $this->taxAmount=round(($this->untaxedAmount*$this->taxPct/100),2);
      } else {
        $this->taxAmount=null;
      } 
      $this->fullAmount=$this->untaxedAmount+$this->taxAmount;
    } else {
      $this->taxAmount=null;
      $this->fullAmount=null;
    }  
    if ($this->totalUntaxedAmount!=null) {
      if ($this->taxPct!=null) {
        $this->totalTaxAmount=round(($this->totalUntaxedAmount*$this->taxPct/100),2);
      } else {
        $this->totalTaxAmount=null;
      }
      $this->totalFullAmount=$this->totalUntaxedAmount+$this->totalTaxAmount;
    } else {
      $this->totalTaxAmount=null;
      $this->totalFullAmount=null;
    }
    
    if ($this->idCallForTender and $this->idProvider) {
      $this->name=SqlList::getNameFromId('CallForTender', $this->idCallForTender).' - '.SqlList::getNameFromId('Provider', $this->idProvider);
    }
    $cft=new CallForTender($this->idCallForTender);
    if (!$this->deliveryDate) $this->deliveryDate=$cft->deliveryDate;
    // Save data from Call For Tender
    if ($this->idCallForTender) {
      // Project
      if ($cft->idProject) $this->idProject=$cft->idProject;
      // Type
      $cftTypeName=SqlList::getNameFromId('CallForTenderType', $cft->idCallForTenderType);
      $list=SqlList::getList('TenderType');
      foreach ($list as $tenderTypeId=>$tenderTypeName) {
        if ($this->idTenderType==null) $this->idTenderType=$tenderTypeId;
        if ($tenderTypeName==$cftTypeName) $this->idTenderType=$tenderTypeId;
      }
    }
    // Status : set defaut or move with TenderStatus (with same name)
    $tenderStatusName=SqlList::getNameFromId('TenderStatus', $this->idTenderStatus);
    $list=SqlList::getList('Status');
    foreach ($list as $statusId=>$statusName) {
      if ($this->idStatus==null) $this->idStatus=$statusId;
      if ($statusName==$tenderStatusName) $this->idStatus=$statusId;
    }
    // Save evaluation
    $eval=new TenderEvaluationCriteria();
    $evalList=$eval->getSqlElementsFromCriteria(array('idCallForTender'=>$this->idCallForTender));
    $sum=null;
    $this->evaluationValue=null;
    $sumMax=0;
    $resultEval=""; $resultEvalId="##";
    foreach ( $evalList as $eval ) {
      $tenderEval=SqlElement::getSingleSqlElementFromCriteria('TenderEvaluation', array('idTender'=>$this->id,'idTenderEvaluationCriteria'=>$eval->id));
      $tenderEval->idTenderEvaluationCriteria=$eval->id;
      $tenderEval->idTender=$this->id;
      $value=$tenderEval->evaluationValue;
      if (isset($_REQUEST['tenderEvaluation_'.$eval->id])) {
        $value=$_REQUEST['tenderEvaluation_'.$eval->id];
      }
      $tenderEval->evaluationValue=$value;
      if ($tenderEval->evaluationValue!=null) $sum+=$tenderEval->evaluationValue*$eval->criteriaCoef;
      $sumMax+=$eval->criteriaMaxValue*$eval->criteriaCoef;
      $resultEvalTemp=$tenderEval->save();
      if (getLastOperationStatus($resultEvalTemp)=="OK") {        
        $resultEval=$resultEvalTemp;
        $resultEvalId=$tenderEval->id;
      }
    }
    if ($cft->fixValue and $sum!=null and $sumMax!=0) {
      $this->evaluationValue=round($cft->evaluationMaxValue*$sum/$sumMax,2);
    } else {
      $this->evaluationValue=$sum;
    }
    $result=parent::save();
    if (getLastOperationStatus($result)=='NO_CHANGE' and $resultEval!="" and getLastOperationStatus($resultEval)=="OK") {
      return str_replace(array(getLastOperationMessage($result),'NO_CHANGE','#'.$resultEvalId),array(getLastOperationMessage($resultEval),"OK",'#'.$this->id),$result);
    }
    
    $billLine=new BillLine();
    $crit = array("refType"=> "Tender", "refId"=>$this->id);
    $billLineList = $billLine->getSqlElementsFromCriteria($crit,false);
    if (count($billLineList)>0) {
      $amount=0;
      foreach ($billLineList as $line) {
        $amount+=$line->amount;
      }
      $this->untaxedAmount=$amount;
    }
    $this->fullAmount=$this->untaxedAmount*(1+$this->taxPct/100);
    $this->taxAmount=$this->fullAmount-$this->untaxedAmount;
    $this->totalUntaxedAmount=$this->untaxedAmount-$this->discountAmount;
    $this->totalFullAmount=$this->totalUntaxedAmount*(1+$this->taxPct/100);
    $this->totalTaxAmount=$this->totalFullAmount-$this->totalUntaxedAmount;
    parent::simpleSave();
    return $result;
  }
  
  public function control(){
    $result="";
    // Check dupplicate CallForTender / Provider
    if ($this->idCallForTender and $this->idProvider) {
      $duplicate=SqlElement::getSingleSqlElementFromCriteria('Tender', array('idCallForTender'=>$this->idCallForTender,'idProvider'=>$this->idProvider));
      if ($duplicate->id and $duplicate->id!=$this->id) {
        $result.='<br/>' . i18n('errorDuplicateTender');
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

  
  public function copyTo($newClass, $newType, $newName, $setOrigin, $withNotes, $withAttachments, $withLinks, $withAssignments = false, $withAffectations = false, $toProject = NULL, $toActivity = NULL, $copyToWithResult = false,$copyToWithVersionProjects=false) {
    if ($newClass=='ProjectExpense') {
      if (! $this->totalUntaxedAmount) {
        $this->totalUntaxedAmount=$this->untaxedAmount;
        $this->totalFullAmount=$this->fullAmount;
      }
      $this->expensePlannedDate=$this->deliveryDate;
    }
    return parent::copyTo($newClass, $newType, $newName, $setOrigin, $withNotes, $withAttachments, $withLinks);
  }
  // ============================================================================**********
  // GET VALIDATION SCRIPT
  // ============================================================================**********
  
  /** ==========================================================================
   * Return the validation sript for some fields
   * @return the validation javascript (for dojo framework)
   */
  public function getValidationScript($colName) {
    $colScript = parent::getValidationScript($colName);
    if ($colName=="idProvider" or $colName=="idCallForTender") {
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= '  if (trim(dijit.byId("idCallForTender").get("value")) && trim(dijit.byId("idProvider").get("value"))) {';
      $colScript .= '    dojo.removeClass(dijit.byId("name").domNode, "required");';
      $colScript .= '    dijit.byId("name").set("required",false);';
      $colScript .= '    dijit.byId("name").set("readonly",true);';
      $colScript .= '    dojo.addClass(dijit.byId("idTenderStatus").domNode, "required");';
      $colScript .= '    dijit.byId("idTenderStatus").set("required",true);';
      $colScript .= '  } else {';
      $colScript .= '    dojo.addClass(dijit.byId("name").domNode, "required");';
      $colScript .= '    dijit.byId("name").set("required",true);';
      $colScript .= '    dijit.byId("name").set("readonly",false);';
      $colScript .= '    dojo.removeClass(dijit.byId("idTenderStatus").domNode, "required");';
      $colScript .= '    dijit.byId("idTenderStatus").set("required",false);';
      $colScript .= '  }';
      $colScript .= '  refreshList("idContact", "idProvider", dijit.byId("idProvider").get("value"), dijit.byId("idContact").get("value"),null, false);';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }else if ($colName=="untaxedAmount" or $colName=="taxPct" or $colName=="discountAmount") {
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      if ($colName=="discountAmount") {
        $colScript .= '   if (avoidRecursiveRefresh) {';
        $colScript .= '     updateFinancialTotal();';
        $colScript .= '     return;';
        $colScript .= '   }';
        $colScript .= '   avoidRecursiveRefresh=true;';
        $colScript .= '   setTimeout(\'avoidRecursiveRefresh=false;\',100);';
        $colScript .= '   dijit.byId("discountFrom").set("value","amount");';
      }
      $colScript .= '  updateFinancialTotal();';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }else if ($colName=="discountRate") {
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= '  var rate=dijit.byId("discountRate").get("value");';
      $colScript .= '  var untaxedAmount=dijit.byId("untaxedAmount").get("value");';
      $colScript .= '  if (!isNaN(rate)) {';
      $colScript .= '    if (avoidRecursiveRefresh) return;';
      $colScript .= '    avoidRecursiveRefresh=true;';
      $colScript .= '    setTimeout(\'avoidRecursiveRefresh=false;\',100);';
      $colScript .= '    dijit.byId("discountFrom").set("value","rate");';
      $colScript .= '    var discount=Math.round(untaxedAmount*rate)/100;';
      $colScript .= '    dijit.byId("discountAmount").set("value",discount);';
      $colScript .= '  }';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    }else if ($colName=="idProject") {
    	$colScript .= '<script type="dojo/connect" event="onChange" >';
    	$colScript .= '  refreshList("idProjectExpense", "idProject", this.value, null, null, false);';
    	$colScript .= '  formChanged();';
    	$colScript .= '</script>';
    }else if ($colName=="idProjectExpense") {
    	  $colScript .= '<script type="dojo/connect" event="onChange" >';
    	  $colScript .= ' var idExpense=dijit.byId("idProjectExpense").get("value");';
    	  $colScript .= 'if(idExpense != " "){ ';
    	  $colScript .= '  dojo.query("._button_generateProjectExpenseClass").style("display", "none"); }else{ dojo.query("._button_generateProjectExpenseClass").style("display", "block"); }';
    	  $colScript .= '</script>';
    }
    return $colScript;
  }

  public function drawSpecificItem($item, $included=false) {
    global $print, $comboDetail, $nbColMax;
    $result = "";
    if ($item == 'evaluation' and ! $comboDetail) {
      $this->drawTenderEvaluationFromObject();
    }else if ($item=='generateProjectExpense') {
      echo '<div id="' . $item . 'Button" name="' . $item . 'Button" ';
      echo ' title="' . i18n('generateProjectExpense') . '" class="greyCheck generalColClass _button_generateProjectExpenseClass" ';
      echo ' dojoType="dijit.form.CheckBox"  type="checkbox" >';
      echo '</div> ';
      echo ' ('.i18n("generateProjectExpenseFrom").')';
    } 
    return $result;
  }
  
  function drawTenderEvaluationFromObject() {
    global $cr, $print, $outMode, $user, $comboDetail, $displayWidth, $printWidth;
    if ($comboDetail) {
      return;
    }
    if (! $this->idCallForTender) {
      echo "<div>&nbsp;&nbsp;&nbsp;<i>".i18n('msgNoCallForTender')."</i></div><div style='font-size:5px'>&nbsp;</div>";
      return;
    }
    $canUpdate=securityGetAccessRightYesNo('menu' . get_class($this), 'update', $this) == "YES";
    if ($this->idle == 1) {
      $canUpdate=false;
    }
    $cft=new CallForTender($this->idCallForTender);
    $eval=new TenderEvaluationCriteria();
    $evalList=$eval->getSqlElementsFromCriteria(array('idCallForTender'=>$this->idCallForTender));
    echo '<table width="99.9%">';
    echo '<tr>';
    echo '<td class="noteHeader" style="width:50%">' . i18n('colName') . '</td>';
    echo '<td class="noteHeader" style="width:20%">' . i18n('colValue') . '</td>';
    echo '<td class="noteHeader" style="width:15%">' . i18n('colCoefficient') . '</td>';
    echo '<td class="noteHeader" style="width:15%">' . i18n('colCountTotal') . '</td>';
    echo '</tr>';
    $sum=null;
    $sumMax=0;
    $idList='';
    foreach ( $evalList as $eval ) {
      $tenderEval=SqlElement::getSingleSqlElementFromCriteria('TenderEvaluation', array('idTender'=>$this->id,'idTenderEvaluationCriteria'=>$eval->id));
      echo '<tr>';
      echo '<td class="noteData">' . htmlEncode($eval->criteriaName) . '</td>';
      echo '<td class="noteData"><input type="text" dojoType="dijit.form.NumberTextBox"  
                  id="tenderEvaluation_'.$eval->id.'" name="tenderEvaluation_'.$eval->id.'"
                  constraints="{min:0,max:'.$eval->criteriaMaxValue.'}" style="width: 50px;" class="input" 
                  value="'.$tenderEval->evaluationValue.'" onChange="changeTenderEvaluationValue('.$eval->id.');"/>
            /&nbsp;'.htmlEncode($eval->criteriaMaxValue).'&nbsp;</td>';
      echo '<td class="noteData" style="text-align:center">' . htmlEncode($eval->criteriaCoef) . '<input type="hidden" id="tenderCoef_'.$eval->id.'" value="'.$eval->criteriaCoef.'"/></td>';
      echo '<td class="noteData"><input type="text" dojoType="dijit.form.NumberTextBox"  readonly="true" tabindex="-1"
                  id="tenderTotal_'.$eval->id.'" name="tenderTotal_'.$eval->id.'"
                  value="'.(($tenderEval->evaluationValue===null)?null:($tenderEval->evaluationValue*$eval->criteriaCoef)).'" style="width: 50px;" class="input" /></td>';
      echo '</tr>';
      if ($tenderEval->evaluationValue!==null) $sum+=$tenderEval->evaluationValue*$eval->criteriaCoef;
      $sumMax+=$eval->criteriaMaxValue*$eval->criteriaCoef;
      $idList.=(($idList!='')?';':'').$eval->id;
    }
    echo '<tr>';
    echo '<td class="noteData" style="border-right:0;text-align:center;color:#555555">';
    if ($cft->fixValue) {
      echo '<i>'.i18n('msgEvalutationMaxValue').' '.(($cft->evaluationMaxValue===null)?null:htmlDisplayNumericWithoutTrailingZeros($cft->evaluationMaxValue)).'</i>';
    }
    echo '<input type="hidden" id="evaluationMaxCriteriaValue" value="'.$cft->evaluationMaxValue.'" />';
    echo '<input type="hidden" id="evaluationSumCriteriaValue" value="'.$sumMax.'" />';
    echo '<input type="hidden" id="idTenderCriteriaList" value="'.$idList.'" />';
    echo '</td>';  
    echo '<td class="noteData" colspan="2" style="border-left:0;text-align:center;color:#555555">'.i18n('colEvaluationMaxValue')."&nbsp;:&nbsp;".$sumMax;
    echo '</td>';
    echo '<td class="noteData"><input type="text" dojoType="dijit.form.NumberTextBox"  readonly="true" tabindex="-1"
                  id="tenderTotal" name="tenderTotal"
                  value="'.$sum.'" style="width: 50px;" class="input" /></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td colspan="4" class="noteDataClosetable">&nbsp;</td>';
    echo '</tr>';
    echo '</table>';
  }
  
  public static function drawListFromCriteria($crit,$val) {
    global $print,$collapsedList, $widthPct;
    // TODO : retrict with parameter
    $param=Parameter::getGlobalParameter('showTendersOnVersions');
    if (strpos($param,'#'.substr($crit,2).'#')==null) return;
    $titlePane='sectionTender_'.$crit;
    // Finish previous section
    echo '</table>';
    if (!$print) {
      echo '</div>';
    } else {
      echo '<br/>';
    }
    // Start section
    if (!$print) {
      echo '<div dojoType="dijit.TitlePane" title="' . i18n('menuCallForTender') . '"';
      echo ' open="' . (array_key_exists($titlePane, $collapsedList)?'false':'true') . '" ';
      echo ' id="'.$titlePane.'" ';
      echo ' style="display:inline-block;position:relative;width:'.$widthPct.';float:right;;margin: 0 0 4px 4px; padding: 0;top:0px;"';
      echo ' onHide="saveCollapsed(\'' . $titlePane . '\');"';
      echo ' onShow="saveExpanded(\'' . $titlePane . '\');">';
      echo '<table class="detail"  style="width: 100%;" >';
    } else {
      echo '<table class="detail" style="width:'.$widthPct.';" >';
      echo '<tr><td class="section">' . i18n('sectionTender') . '</td></tr>';
      echo '<tr class="detail" style="height:2px;font-size:2px;">';
      echo '<td class="detail" >&nbsp;</td>';
      echo '</tr>';
      echo '</table><table class="detail" style="width:99%;" >';
    }
    $cft=new CallForTender();
    if (!$val) $val='0';
    $list=$cft->getSqlElementsFromCriteria(array($crit=>$val));
    $cpt=0;
    foreach($list as $cft) {
      $cpt++;
      echo "<tr><td>";
      if ($cpt>1) echo "<br/>";
      echo '<table style="width:99.9%"><tr class="noteHeader">';
      echo '<td  style="padding:3px 10px;vertical-align:middle;font-weight:bold;text-align:left">';
      echo '<img src="../view/css/images/iconTender16.png" />';
      echo '<span onClick="gotoElement(\'CallForTender\','.$cft->id.')" style="cursor:pointer;padding-left:10px;position:relative;top:-3px;">'.$cft->name.'</span>';
      echo '</td></tr></table>';
      $cft->drawTenderSubmissionsFromObject(true);
      echo "</td></tr>";
    } 
    
  }
  
  public function setAttributes() {
    if ($this->idCallForTender) {
      if ($this->idProvider) {
        self::$_fieldsAttributes['name']='readonly';
        self::$_fieldsAttributes['idTenderStatus']='required';
      }
      $cft=new CallForTender($this->idCallForTender,true);
      if ($cft->idProject) {
        self::$_fieldsAttributes['idProject']='readonly';
      }
      if (SqlList::getNameFromId('Type',$cft->idCallForTenderType)==SqlList::getNameFromId('Type',$this->idTenderType)) {
        self::$_fieldsAttributes['idTenderType']='readonly';
      }
    } else {
      self::$_fieldsAttributes['evaluationValue']='hidden';
      self::$_fieldsAttributes['evaluationRank']='hidden';
    }
    if (count($this->_BillLine)) {
      self::$_fieldsAttributes['untaxedAmount']='readonly';
    }
    if($this->idProjectExpense){
      self::$_fieldsAttributes['_button_generateProjectExpense']='hidden';
    }
  }
  
}
?>