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
require_once "../tool/projeqtor.php";
if (! array_key_exists('objectClass',$_REQUEST)) {
  throwError('objectClass parameter not found in REQUEST');
}
$objectClass=$_REQUEST['objectClass'];
Security::checkValidClass($objectClass);
//gautier
if($objectClass == 'Work'){
  $week = RequestHandler::getValue('dateWeek');
  $month = RequestHandler::getValue('dateMonth');
  $isMyUser = RequestHandler::getValue('userId');
}

$obj=new $objectClass();
$idUser = getSessionUser()->id;
$cs=new ColumnSelector();
$crit=array('scope'=>'export','objectClass'=>$objectClass, 'idUser'=>$user->id);
$csList=$cs->getSqlElementsFromCriteria($crit);
$hiddenFields=array();
foreach ($csList as $cs) {
	if ($cs->hidden) {
		$hiddenFields[$cs->field]=true;
	}
}
$arrayDependantObjects=array('Document'=>array('_DocumentVersion'=>'withSection'));
$htmlresult='<td valign="top" style="width:1px">';
$FieldsArray=$obj->getFieldsArray(true);
foreach($FieldsArray as $key => $val) {
	if ( ! SqlElement::isVisibleField($val) ) {
		unset($FieldsArray[$key]);
    continue;
	}
	if ($objectClass=='GlobalView' and $key=='id') {
	  unset($FieldsArray[$key]);
	  continue;
	}
	if (substr($val,0,5)=='_sec_') {
		if (strlen($val)>6) {
			$section=substr($val,5);
			if ($section=='Assignment' or $section=='Affectations' or substr($section,0,14)=='Versionproject'
       or $section=='Subprojects' or $section=='Approver' or $section=='ExpenseDetail' 
       or $section=='predecessor' or $section=='successor' or $section =='TestCaseRun'
       or $section=='Projects' or $section=='Link' or $section=='Note' or $section=='Attachment') {
			  unset($FieldsArray[$key]);
			  continue;
			}
			$FieldsArray[$key]=i18n('section' . ucfirst($section));
		}
  } else if (substr($key,0,1)=='_' or strtoupper(substr($key,0,1))==substr($key,0,1) ){ // Object
    if (isset($arrayDependantObjects[$objectClass]) and isset($arrayDependantObjects[$objectClass][$key])) {
      $included=ltrim($key,'_');
      if (SqlElement::class_exists($included)) {
        $crit=array('scope'=>'export','objectClass'=>$included, 'idUser'=>$user->id);
        $csList=$cs->getSqlElementsFromCriteria($crit);
        foreach ($csList as $cs) {
          if ($cs->hidden) {
            $hiddenFields[$included.'_'.$cs->field]=true;
          }
        }
        if ($arrayDependantObjects[$objectClass][$key]=='withSection') {   
          $FieldsArray['_sec_'.$included]=i18n('_sec_'.$included);//i18n('section' . ltrim($key,'_'));
        }
        $incObj=new $included();
        foreach ($incObj as $incKey=>$incVal) {
          if (substr($incKey,0,1)=='_') continue;
          if ($incKey=='refType' or $incKey=='refId' or $incKey=='id'.$objectClass) continue;
          if ($incObj->isAttributeSetToField($incKey,'noExport')) continue;
          $FieldsArray[$included.'_'.$incKey]=i18n('col'.ucfirst($incKey));
        }
      }
    }
    unset($FieldsArray[$key]);
    continue;
	} else {
	  $FieldsArray[$key]=$obj->getColCaption($val);
	}
	if(isset($FieldsArray[$key]) and substr($FieldsArray[$key],0,1)=="["){
		unset($FieldsArray[$key]);
		continue;
	}
}

// ADD BY Marc TABARY - 2017-03-20 - EXPORT - DON'T DRAW SECTION WITHOUT FIELD
$FieldsArrayNext = $FieldsArray;
foreach($FieldsArray as $key=>$val) {
    if(substr($key,0,5)=="_sec_") {
        reset($FieldsArrayNext);
        $next_=true;
        while(current($FieldsArrayNext)!=$val and $next_!==false) {
            $next_ = next($FieldsArrayNext);
        }
        $next_ = next($FieldsArrayNext);
        if ($next_!==false) {
            $next_key = key($FieldsArrayNext);
            if(substr($next_key,0,5)=='_sec_') {
                unset($FieldsArray[$key]);
            }
        }
    }    
}
// END ADD BY Marc TABARY - 2017-03-20 - EXPORT - DON'T DRAW SECTION WITHOUT FIELD

$countFields=count($FieldsArray);
$htmlresult.='<input type="hidden" dojoType="dijit.form.TextBox" id="column0" name="column0" value="'.$countFields.'">';
$index=1;
$last_key = end($FieldsArray);
$allChecked="checked";
foreach($FieldsArray as $key => $val){
	if(substr($key,0,5)=="_sec_"){
		if($val!=$last_key) {
			$htmlresult.='</td><td style="vertical-align:top;width: 200px;" valign="top">';
			$htmlresult.='<div class="section" style="width:90%"><b>'.$val.'</b>';
			$htmlresult.='</div><br/>';
			if ($key=='_sec_DocumentVersion') {
				$htmlresult.='<div class="noteHeader" style="width:94%">';
				$htmlresult.= '<table style="width:100%"><tr>';
				$htmlresult.='<td><input type="checkbox" dojoType="dijit.form.CheckBox" id="documentVersionAll" name="documentVersionAll" 
						onChange="dijit.byId(\'documentVersionLastOnly\').set(\'checked\',!this.checked);" />'.i18n('all').'</td>';
				$htmlresult.='<td><input type="checkbox" dojoType="dijit.form.CheckBox" id="documentVersionLastOnly" name="documentVersionLastOnly" 
						onChange="dijit.byId(\'documentVersionAll\').set(\'checked\',!this.checked);" checked=checked />'.i18n('colCurrentDocumentVersion').'</td>';
				$htmlresult.= '</tr></table>';
				$htmlresult.='</div><br/>';
			}
		}
	} else if(substr($key,0,5)=="input"){
	}else {
		$checked='checked';
		if (array_key_exists($key, $hiddenFields)) {
			$checked='';
			$allChecked='';
		}
    if (substr($key,0,9)=='idContext' and strlen($key)==10) {
      $ctx=new ContextType(substr($key,-1));
      $val=$ctx->name;
    } 
		$htmlresult.='<input type="checkbox" dojoType="dijit.form.CheckBox" id="column'.$index.'" name="column'.$index.'" value="'.$key.'" '.$checked.' />';
		$htmlresult.='<label for="column'.$index.'" class="checkLabel">'.$val.'</label><br/>';
		$index++;
	}
}
$htmlresult.='</td>';
$htmlresult.="<br/>";
?>
<form id="dialogExportForm" name="dialogExportForm">
<table style="width: 100%;">
  <tr>
    <td colspan="2" class="reportTableHeader"><?php echo i18n("chooseColumnExport");?></td>
  </tr>
  <tr><td colspan="2" >&nbsp;</td></tr>
  <tr>
    <td>
      <input type="checkbox" dojoType="dijit.form.CheckBox" id="checkUncheck" name="checkUncheck" value="Check" onclick="checkExportColumns();" <?php echo $allChecked?> />
      <label for="checkUncheck" class="checkLabel"><b><?php echo i18n("checkUncheckAll")?></b></label>&nbsp;&nbsp;&nbsp;
    </td>
    <td>
      <input type="checkbox" dojoType="dijit.form.Button" id="checkAsList" name="checkAsList" onclick="checkExportColumns('aslist');" 
       showLabel="true" label="<?php echo i18n("checkAsList")?>" />
    </td>
  </tr>
  <tr>
    <td style="width:300px;text-align:right" class="dialogLabel"><?php echo i18n("exportReferencesAs")?> :&nbsp;</td>
    <td > <select dojoType="dijit.form.FilteringSelect" class="input" 
           <?php echo autoOpenFilteringSelect();?>
				   style="width: 150px;" name="exportReferencesAs" id="exportReferencesAs">         
           <option value="name"><?php echo i18n("colName");?></option>                            
           <option value="id"><?php echo i18n("colId");?></option>
			    </select></td>
  </tr>
  <tr>
    <td style="width:300px;text-align:right" class="dialogLabel"><?php echo i18n("exportHtml")?> :&nbsp;</td>
    <td > <div type="checkbox" dojoType="dijit.form.CheckBox" id="exportHtml" name="exportHtml" ></div></td>
  </tr>
   <?php if( $objectClass != 'Work' ){?>
  <tr><td colspan="2" >&nbsp;</td></tr>
  
  <?php  } if( $objectClass == 'Work' ){?>
  <tr>
    <td style="width:300px;text-align:right" class="dialogLabel"><?php echo i18n("exportDateAs")?> :&nbsp;</td>
    <td > <select dojoType="dijit.form.FilteringSelect" class="input" 
           <?php echo autoOpenFilteringSelect();?>
				   style="width: 150px;" name="exportDateAs" id="exportDateAs">         
           <option value="<?php echo 'W'.$week ;?>"> <?php echo i18n("selectWeek");?> </option>                            
           <option value="<?php echo 'M'.$month ;?>"><?php echo i18n("selectMonth");?></option>
           <option value="<?php echo 'Y'.substr($month,0,4);?>"><?php echo i18n("selectYear");?></option>
           <option value="<?php echo 'All' ;?>"><?php echo i18n("getAll");?></option>
			    </select></td>
  </tr>
  <tr>
    <td style="width:300px;text-align:right" class="dialogLabel"><?php echo i18n("exportRessourceAs")?> :&nbsp;</td>
    <td > <select dojoType="dijit.form.FilteringSelect" class="input" 
           <?php echo autoOpenFilteringSelect();?>
				   style="width: 150px;" name="exportRessourceAs" id="exportRessourceAs">         
           <option value="<?php echo 'C'.$isMyUser ;?>"> <?php echo i18n("selectResource");?> </option>                            
           <option value="<?php echo 'A' ;?>"><?php echo i18n("allResource");?></option>
			    </select></td>
  </tr>
  <?php }?>

  </table>
<table style="width: 100%;">
  <tr>
  <?php  echo $htmlresult;?>
  </tr>
</table>
<div style="height:10px;"></div>    
<div style="height:5px;border-top:1px solid #AAAAAA"></div>    
<table style="width: 100%">
  <tr>
    <td style="width: 50%; text-align: right;">
    <button align="right" dojoType="dijit.form.Button"
      onclick="closeExportDialog();">
      <?php echo i18n("buttonCancel");?></button>&nbsp;
    </td>
    <td style="width: 50%; text-align: left;">&nbsp;
    <button align="left" dojoType="dijit.form.Button"
      id="dialogPrintSubmit"
      onclick="executeExport('<?php echo $objectClass;?>','<?php echo $idUser;?>');">
      <?php echo i18n("buttonOK");?></button>
    </td>
  </tr>
</table>
</form>
