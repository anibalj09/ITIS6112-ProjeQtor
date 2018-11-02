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
 * defines a term for a payment
 */ 
require_once('_securityCheck.php');
class ProviderTermMain extends SqlElement {

  // extends SqlElement, so has $id
  public $_sec_Description;
  public $id;    // redefine $id to specify its visible place 
  public $name;
  public $idProject;
  public $idProviderOrder;
  public $idProviderBill;
  public $idUser;
  public $creationDate;
  public $idResource;
  public $_sec_Price;
  public $_tab_4_1_smallLabel = array('untaxedAmountShort', 'tax', '', 'fullAmountShort','initial');
  public $untaxedAmount;
  public $taxPct;
  public $taxAmount;
  public $fullAmount;
  public $date;
  public $isBilled;
  public $isPaid;
  public $idle;
  public $idProjectExpense;
  public $_Note=array();
  public $_sec_Link;
  public $_Link=array();
  public $_BillLineTerm=array();
  public $_BillLineTerm_colSpan="2";
  public $_nbColMax=3;
  
  // Define the layout that will be used for lists
  private static $_layout='
    <th field="id" formatter="numericFormatter" width="4%"># ${id}</th>
    <th field="nameProject" width="10%">${idProject}</th>
    <th field="name" width="20%">${name}</th>
    <th field="nameProviderOrder"  width="15%">${idProviderOrder}</th>
    <th field="nameProviderBill" formatter="noCalculate" width="16%">${idProviderBill}</th>
    <th field="date" width="5%" formatter="dateFormatter">${date}</th>
    <th field="untaxedAmount" width="8%" formatter="costFormatter">${untaxedAmount}</th>
    <th field="fullAmount" width="8%" formatter="costFormatter">${fullAmount}</th>
    <th field="isBilled" width="5%" formatter="booleanFormatter" >${isBilled}</th>
    <th field="isPaid" width="5%" formatter="booleanFormatter" >${isPaid}</th>
    <th field="idle" width="5%" formatter="booleanFormatter" >${idle}</th>
    ';
  
  private static $_fieldsAttributes=array("name"=>"required",
                                          "idProject"=>"required",
                                          "date"=>"required",
                                          "idProjectExpense"=>"hidden",
  								                        //"idProviderBill"=>"display,readonly",
                                          "isBilled"=>"readonly",
                                          "isPaid"=>"readonly"
  );  
  
  private static $_colCaptionTransposition = array("idUser"=>"issuer", 'idResource'=> 'responsible');
  
  //private static $_databaseColumnName = array('realAmount'=>'amount');
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
   * Return the specific databaseColumnName
   * @return the databaseTableName
   */
//  protected function getStaticDatabaseColumnName() {
//    return self::$_databaseColumnName;
//  }
 /** =========================================================================
   * Overrides SqlElement::deleteControl() function to add specific treatments
   * @see persistence/SqlElement#deleteControl()
   * @return the return message of persistence/SqlElement#deleteControl() method
   */  
  
  public function deleteControl() {
  	$result = "";
  	if ($this->isPaid){
  	  $result .= "<br/>" . i18n("cannotDeletePaidTerm");
  	} else if ($this->idProviderBill){
  		$result .= "<br/>" . i18n("cannotDeleteBilledTerm");
  	}

  	if (! $result) {  
      $result=parent::deleteControl();
    }
    return $result;
  }
  
  
  /** ==========================================================================
   * Return the validation sript for some fields
   * @return the validation javascript (for dojo frameword)
   */
  public function getValidationScript($colName) {
    $colScript = parent::getValidationScript($colName);
    if ($colName=="untaxedAmount" || $colName=="taxPct") {
      $colScript .= '<script type="dojo/connect" event="onChange" >';
      $colScript .= '  updateBillTotal();';
      $colScript .= '  formChanged();';
      $colScript .= '</script>';
    } 
    return $colScript;
  }
  
/** =========================================================================
   * Overrides SqlElement::save() function to add specific treatments
   * @see persistence/SqlElement#save()
   * @return the return message of persistence/SqlElement#save() method
   */  

	public function save() {
	  if ($this->idProviderBill) {
	    $this->isBilled=1;
	  } else {
	    $this->isBilled=0;
	  }
		$result = parent::save();	
		return $result;
	}
	
  public function copyTo($newClass, $newType, $newName, $setOrigin, $withNotes, $withAttachments, $withLinks, $withAssignments = false, $withAffectations = false, $toProject = NULL, $toActivity = NULL, $copyToWithResult = false,$copyToWithVersionProjects=false) {
    if($newClass=='ProviderBill' and $this->idProviderBill){
      $result=i18n('copyNotAsProviderBill').'<br/>';
      $this->_copyResult=$result;
      return $this;
    }else {
      $newObj =  parent::copyTo($newClass, $newType, $newName, $setOrigin, $withNotes, null, $withLinks);
      if($newClass=='ProviderBill') {
        $this->idProviderBill = $newObj->id;
        $this->isBilled=1;
        $this->saveForced();
        if ($this->idProviderOrder) { // Copy to Bill a Term that is linked to Order => link Bill and Order
          $link=new Link();
          $link->ref1Type='ProviderBill';
          $link->ref1Id=$newObj->id;
          $link->ref2Type='ProviderOrder';
          $link->ref2Id=$this->idProviderOrder;
          $link->save();
          $providerOrder = new ProviderOrder($this->idProviderOrder);
          if($providerOrder->idProjectExpense){
            $newObj->idProjectExpense = $providerOrder->idProjectExpense;
            $newObj->save();
          }
        }
      }
      return $newObj;
    }
  }
  
  public function setAttributes() {
    if($this->idProviderOrder or $this->idProviderBill){
      self::$_fieldsAttributes["idProject"]='readonly';
    }
    if ($this->idProviderOrder) {
      self::$_fieldsAttributes['taxPct']='readonly';
      self::$_fieldsAttributes['idProviderOrder']='readonly';
      $billLine=new BillLine();
      if ($this->id) {
        $cpt=$billLine->countSqlElementsFromCriteria(array('refType'=>'ProviderTerm','refId'=>$this->id));
        if ($cpt>0) { 
          self::$_fieldsAttributes['untaxedAmount']='readonly';
        }
      }
    }
    if ($this->idProviderBill) {
      $bill=new ProviderBill($this->idProviderBill);
      if ($bill->done) {
        self::$_fieldsAttributes['idProviderBill']='readonly';
      }
    }
    self::$_fieldsAttributes['taxAmount']='readonly';
    self::$_fieldsAttributes['fullAmount']='readonly';
  }
  
}
?>