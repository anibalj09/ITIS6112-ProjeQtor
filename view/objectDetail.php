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

/*
 * ============================================================================ Presents the detail of an object, for viewing or editing purpose.
 */
require_once "../tool/projeqtor.php";
require_once "../tool/formatter.php";

$reorg=(isset($paramReorg) and $paramReorg==false)?false:true;
$leftPane="";
$rightPane="";
$extraPane="";
$bottomPane="";
scriptLog('   ->/view/objectDetail.php');
if (!isset($comboDetail)) {
  $comboDetail=false;
}
$collapsedList=Collapsed::getCollaspedList();
$readOnly=false;
if (false===function_exists('lcfirst')) {

  function lcfirst($str) {
    $str[0]=strtolower($str[0]);
    return (string)$str;
  }
}
$preseveHtmlFormatingForPDF=true;
// ********************************************************************************************************
// MAIN PAGE
// ********************************************************************************************************

// fetch information depending on, request
$objClass=$_REQUEST['objectClass'];
Security::checkValidClass($objClass, 'objectClass');
if (isset($_REQUEST['noselect'])) {
  $noselect=true;
}
if (!isset($noselect)) {
  $noselect=false;
}
if ($noselect) {
  $objId="";
  $obj=null;
  $profile=getSessionUser()->idProfile;
} else {
  $objId=$_REQUEST['objectId'];
  if ($objClass=='GlobalView') {
    $expl=explode('|', $objId);
    $objClass=$expl[0];
    $objId=$expl[1];
  }
  $obj=new $objClass($objId);
  $profile=getSessionUser()->getProfile($obj);
  // gautier
  if ($objClass=='Resource' and $obj->isResourceTeam) {
    $objClass='ResourceTeam';
    $obj=new ResourceTeam($objId);
  }
  if (array_key_exists('refreshNotes', $_REQUEST)) {
    drawNotesFromObject($obj, true);
    exit();
  }
  if (array_key_exists('refreshBillLines', $_REQUEST)) {
    drawBillLinesFromObject($obj, true);
    exit();
  }
  if (array_key_exists('refreshJobDefinition', $_REQUEST)) {
    drawJobDefinitionFromObject($obj, true);
    exit();
  }
  if (array_key_exists('refreshChecklistDefinitionLines', $_REQUEST)) {
    drawChecklistDefinitionLinesFromObject($obj, true);
    exit();
  }
  if (array_key_exists('refreshAttachments', $_REQUEST)) {
    drawAttachmentsFromObject($obj, true);
    exit();
  }
  /*
   * On assignment change refresh all item if (array_key_exists ( 'refreshAssignment', $_REQUEST )) { drawAssignmentsFromObject($obj->_Assignment, $obj, true ); exit (); }
   */
  if (array_key_exists('refreshResourceCost', $_REQUEST)) {
    drawResourceCostFromObject($obj->$_ResourceCost, $obj, true);
    exit();
  }
  if (array_key_exists('refreshVersionProject', $_REQUEST)) {
    drawVersionProjectsFromObject($obj->$_VersionProject, $obj, true);
    exit();
  }
  if (array_key_exists('refreshProductProject', $_REQUEST)) {
    drawProductProjectsFromObject($obj->$_ProductProject, $obj, true);
    exit();
  }
  if (array_key_exists('refreshDocumentVersion', $_REQUEST)) {
    drawVersionFromObjectFromObject($obj->$_DocumentVersion, $obj, true);
    exit();
  }
  if (array_key_exists('refreshTestCaseRun', $_REQUEST)) {
    drawTestCaseRunFromObject($obj->_TestCaseRun, $obj, true);
    exit();
  }
  if (array_key_exists('refreshLinks', $_REQUEST)) {
    $refreshLinks=$_REQUEST['refreshLinks'];
    if (property_exists($obj, '_Link_'.$refreshLinks)) {
      $lnkFld='_Link_'.$refreshLinks;
      drawLinksFromObject($obj->$lnkFld, $obj, $refreshLinks, true);
    } else if (property_exists($obj, '_Link')&&$refreshLinks) {
      drawLinksFromObject($obj->_Link, $obj, null, true);
    }
    exit();
  }
  if (array_key_exists('refreshHistory', $_REQUEST)) {
    $treatedObjects[]=$obj;
    foreach ($obj as $col=>$val) {
      if (is_object($val)) {
        $treatedObjects[]=$val;
      }
    }
    drawHistoryFromObjects(true);
    if (isset($dynamicDialogHistory) and $dynamicDialogHistory and function_exists('showCloseButton')) {
      showCloseButton();
    }
    exit();
  }
}
// save the current object in session
$print=false;
if (array_key_exists('print', $_REQUEST) or isset($callFromMail)) {
  $print=true;
}
if (!$print and $obj) {
  if (!$comboDetail) {
    SqlElement::setCurrentObject($obj);
  } else {
    SqlElement::setCurrentObject($obj, true);
  }
}
$refresh=false;
if (array_key_exists('refresh', $_REQUEST)) {
  $refresh=true;
}

$treatedObjects=array();

$displayWidth='98%';
if ($print) $reorg=false;
if ($print and isset($outMode) and $outMode=='pdf') {
  $reorg=false;
  if (isset($orientation) and $orientation=='L') $printWidth=1080;
  else $printWidth=760;
} else {
  $printWidth=980;
}
if (array_key_exists('destinationWidth', $_REQUEST)) {
  $width=$_REQUEST['destinationWidth'];
  $width-=30;
  $displayWidth=$width.'px';
} else {
  if (sessionValueExists('screenWidth')) {
    $detailWidth=round((getSessionValue('screenWidth')*0.8)-15); // 80% of screen - split barr - padding (x2)
  } else {
    $displayWidth='98%';
  }
}
if ($print) {
  $displayWidth=$printWidth.'px'; // must match iFrame size (see main.php)
}
$colWidth=intval($displayWidth); // Initialized to be sure...

if ($print) {
  echo '<br/>';
  echo '<div class="reportTableHeader" style="width:'.($printWidth-10).'px;font-size:150%;">'.i18n($objClass).' #'.($objId+0).((property_exists($objClass, 'name') and $obj->name)?'&nbsp;-&nbsp;'.$obj->name:'').'</div>';
  echo '<br/>';
}

// New refresh method
if (array_key_exists('refresh', $_REQUEST)) {
  if (!$print) {
    echo '<input type="hidden" id="objectClassName" name="objectClassName" value="'.$objClass.'" />'.$cr;
  }
  drawTableFromObject($obj);
  drawChecklistFromObject($obj);
  drawJoblistFromObject($obj);
  exit();
}
?>
<div <?php echo ($print)?'x':'';?>
  dojoType="dijit.layout.BorderContainer">
  <?php
  if (!$refresh and !$print) {
    ?>
  <div id="buttonDiv" dojoType="dijit.layout.ContentPane" region="top"
    style="z-index: 3; height: 35px; position: relative; overflow: visible !important;">
    <div id="resultDiv" dojoType="dijit.layout.ContentPane" region="top"
      style="display: none; z-index: 99999;"></div>
		<?php  include 'objectButtons.php'; ?>
		<div id="detailBarShow" class="dijitAccordionTitle"
      onMouseover="hideList('mouse');" onClick="hideList('click');"
      <?php if (RequestHandler::isCodeSet('switchedMode') and RequestHandler::getValue('switchedMode')=='on') echo ' style="display:block;"'?>>
      <div id="detailBarIcon" align="center"></div>
    </div>
  </div>
  <div id="formDiv" dojoType="dijit.layout.ContentPane" region="center">

	<?php
  }
  if (!$print) {
    ?>  
<form dojoType="dijit.form.Form" id="objectForm" jsId="objectForm"
      name="objectForm" encType="multipart/form-data" action=""
      method="">
      <script type="dojo/method" event="onShow">
        if (dijit.byId('name')) dijit.byId('name').focus();
      </script>
      <script type="dojo/method" event="onSubmit">
        // Don't do anything on submit, just cancel : no button is default => must click
		    //submitForm("../tool/saveObject.php","resultDiv", "objectForm", true);
		    return false;        
        </script>
      <div style="width: 100%; height: 100%;">
        <div id="detailFormDiv" dojoType="dijit.layout.ContentPane"
          region="top" style="width: 100%; height: 100%;"
          onmouseout="hideGraphStatus();">
          <?php
  }
  $noData=htmlGetNoDataMessage($objClass);
  $canRead=securityGetAccessRightYesNo('menu'.get_class($obj), 'read', $obj)=="YES";
  if (!$obj->id) {
    $canRead=securityGetAccessRightYesNo('menu'.get_class($obj), 'create', $obj)=="YES";
    $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj, $user)=="YES";
    if (!$canRead or !$canUpdate) {
      $accessRightRead=securityGetAccessRight('menu'.get_class($obj), 'read', $obj, $user);
      $accessRightUpdate=securityGetAccessRight('menu'.get_class($obj), 'update', null, $user);
      if (($accessRightRead=='OWN' or $accessRightUpdate=='OWN') and property_exists($obj, 'idUser')) {
        $canRead=true;
        $obj->idUser=$user->id;
      } else if (($accessRightRead=='RES' or $accessRightUpdate=='RES') and property_exists($obj, 'idResource')) {
        $canRead=true;
        $obj->idResource=$user->id;
      }
    }
  }
  
  if (get_class($obj)=='Project' and isset($obj->codeType) and $obj->codeType=='TMP') {
    $canRead=true;
  }
  if ($noselect) {
    echo $noData;
  } else if (!$canRead) {
    echo htmlGetNoAccessMessage($objClass);
    echo "</div></form>";
    exit();
  } else if ($objId and !$obj->id) {
    echo htmlGetDeletedMessage($objClass);
    echo "</div></form>";
    exit();
  } else {
    if (!$print or $comboDetail) {
      echo '<input type="hidden" id="objectClassName" name="objectClassName" value="'.$objClass.'" />'.$cr;
    }
    drawTableFromObject($obj);
    drawChecklistFromObject($obj);
    drawJoblistFromObject($obj);
  }
  
  if (!$print) {
    ?> 
  </div>
      </div>
    </form>
  <?php
  }
  $widthPct=setWidthPct($displayWidth, $print, $printWidth, $obj, "2");
  if (!$noselect and isset($obj->_ChecklistDefinitionLine)) {
    ?> <br />
  <?php if ($print) {?>
<table width="<?php echo $printWidth;?>px;">
      <tr>
        <td class="section"><?php echo i18n('sectionChecklistLines');?></td>
      </tr>
      <tr>
        <td><?php drawChecklistDefinitionLinesFromObject($obj);?></td>
      </tr>
    </table>
  <?php
    } else {
      $titlePane=$objClass."_checklistDefinitionLine";
      ?>
<div style="width: <?php echo $displayWidth;?>" dojoType="dijit.TitlePane" 
     title="<?php echo i18n('sectionChecklistLines');?>"
     open="<?php echo ( array_key_exists($titlePane, $collapsedList)?'false':'true');?>"
     id="<?php echo $titlePane;?>"       
     onHide="saveCollapsed('<?php echo $titlePane;?>');"
     onShow="saveExpanded('<?php echo $titlePane;?>');" >
     <?php  drawChecklistDefinitionLinesFromObject($obj); ?>
</div>
<?php }?> <?php
  }
  if (!$noselect and isset($obj->_JobDefinition)) {
    ?> <br />
  <?php if ($print) {?>
  <table width="<?php echo $printWidth;?>px;">
      <tr>
        <td class="section"><?php echo i18n('sectionJoblist');?></td>
      </tr>
      <tr>
        <td><?php drawJobDefinitionFromObject($obj);?></td>
      </tr>
    </table>
  <?php
    } else {
      $titlePane=$objClass."_jobDefinition";
      ?>
  <div style="width: <?php echo $displayWidth;?>" dojoType="dijit.TitlePane"
     title="<?php echo i18n('sectionJoblist');?>"
     open="<?php echo ( array_key_exists($titlePane, $collapsedList)?'false':'true');?>"
     id="<?php echo $titlePane;?>"
     onHide="saveCollapsed('<?php echo $titlePane;?>');"
     onShow="saveExpanded('<?php echo $titlePane;?>');" >
     <?php  drawJobDefinitionFromObject($obj); ?>
  </div>
  <?php }?> <?php
  }
  $displayHistory='REQ';
  $paramDisplayHistory=Parameter::getUserParameter('displayHistory');
  if ($paramDisplayHistory) {
    $displayHistory=$paramDisplayHistory;
  }
  if ($obj and (property_exists($obj, '_noHistory') or property_exists($obj, '_noDisplayHistory'))) {
    $displayHistory='NO';
  }
  if ($print and Parameter::getUserParameter('printHistory')!='YES') {
    $displayHistory='NO';
  }
  echo '<br/>';
  if ((!$noselect) and ($displayHistory=='YES' or $displayHistory=='YESW') and !$comboDetail) {
    if ($print) {
      ?>
<table width="<?php echo $printWidth;?>px;">
      <tr>
        <td class="section"><?php echo i18n('elementHistoty');?></td>
      </tr>
    </table>
<?php drawHistoryFromObjects();?> <?php
    } else {
      $titlePane=$objClass."_history";
      ?>
<div style="width: <?php echo $displayWidth;?>;" dojoType="dijit.TitlePane" 
       title="<?php echo i18n('elementHistoty');?>"
       open="<?php echo ( array_key_exists($titlePane, $collapsedList)?'false':'true');?>"
       id="<?php echo $titlePane;?>"         
       onHide="saveCollapsed('<?php echo $titlePane;?>');"
       onShow="saveExpanded('<?php echo $titlePane;?>');" ><?php drawHistoryFromObjects();?>
</div>
    <br />
<?php }?> <?php
  } else if (!$print) {
    $titlePane=$objClass."_history";
    ?>
<div style="display:none; width: <?php echo $displayWidth;?>;" dojoType="dijit.TitlePane" 
       title="<?php echo i18n('elementHistoty');?>"
       open="<?php echo ( array_key_exists($titlePane, $collapsedList)?'false':'true');?>"
       id="<?php echo $titlePane;?>"         
       onHide="saveCollapsed('<?php echo $titlePane;?>');"
       onShow="saveExpanded('<?php echo $titlePane;?>');" ></div>
	<?php
  }
  ?> <?php if ( ! $refresh and  ! $print) { ?></div>
<?php
  }
  ?></div>
<?php

/**
 * ===========================================================================
 * Draw all the properties of object as html elements, depending on type of data
 *
 * @param $obj the
 *          object to present
 * @param $included boolean
 *          indicating wether the function is called recursively or not
 * @return void
 */
function drawTableFromObject($obj, $included=false, $parentReadOnly=false, $parentHidden=false) {
  scriptLog("drawTableFromObject(obj, included=$included, parentReadOnly=$parentReadOnly)");
  global $toolTip, $cr, $print, $treatedObjects, $displayWidth, $outMode, $comboDetail, $collapsedList, $printWidth, $profile, $detailWidth, $readOnly, $largeWidth, $widthPct, $nbColMax, $preseveHtmlFormatingForPDF, $reorg, $leftPane, $rightPane, $extraPane, $bottomPane, $nbColMax, $section, $beforeAllPanes, $colWidth;
  $ckEditorNumber=0; // Will be used only if getEditor=="CK" for CKEditor
  
  if (property_exists($obj, '_sec_Assignment')) {
    $habil=SqlElement::getSingleSqlElementFromCriteria('HabilitationOther', array('idProfile'=>$profile, 'scope'=>'assignmentView'));
    if ($habil and $habil->rightAccess!=1) {
      unset($obj->_sec_Assignment);
    }
  }
  if ($print) $obj->_nbColMax=1;
  $currency=Parameter::getGlobalParameter('currency');
  $currencyPosition=Parameter::getGlobalParameter('currencyPosition');
  $showThumb=Parameter::getUserParameter('paramShowThumb'); // show thumb between label and field ?
  if ($showThumb=='NO') {
    $showThumb=false;
  } else {
    $showThumb=true;
  }
  $treatedObjects[]=$obj;
  $dateWidth='72';
  $verySmallWidth='44';
  $smallWidth='72';
  $mediumWidth='197';
  $largeWidth='300';
  $labelWidth=160; // To be changed if changes in css file (label and .label)
  $labelStyleWidth='145px';
  if ($outMode=='pdf') {
    // $labelWidth=40;
    // $labelStyleWidth=$labelWidth . 'px;';
  }
  $fieldWidth=$smallWidth;
  $extName="";
  $user=getSessionUser();
  $displayComboButton=false;
  $habil=SqlElement::getSingleSqlElementFromCriteria('habilitationOther', array('idProfile'=>$profile, 'scope'=>'combo'));
  if ($habil) {
    $list=new ListYesNo($habil->rightAccess);
    if ($list->code=='YES') {
      $displayComboButton=true;
    }
  }
  if ($comboDetail) {
    $extName="_detail";
  }
  $detailWidth=null; // Default detail div width
                     // Check screen resolution, to determine max field width (largeWidth)
  if (array_key_exists('destinationWidth', $_REQUEST)) {
    $detailWidth=$_REQUEST['destinationWidth'];
  } else {
    if (sessionValueExists('screenWidth')) {
      $detailWidth=round((getSessionValue('screenWidth')*0.8)-15); // 80% of screen - split barr - padding (x2)
    }
  }
  // Set some king of responsive design : number of display columns depends on screen width
  $nbColMax=getNbColMax($displayWidth, $print, $printWidth, $obj);
  $currentCol=0;
  $nbCol=$nbColMax;
  
  // Define internalTable values, to present data as a table
  $internalTable=0;
  $internalTableCols=0;
  $internalTableRows=0;
  $internalTableCurrentRow=0;
  $internalTableSpecial='';
  $internalTableRowsCaptions=array();
  $classObj=get_class($obj);
  if ($obj->id=='0') {
    $obj->id=null;
  }
  $type=$classObj.'Type';
  $idType='id'.$type;
  $objType=null;
  $defaultProject=null;
  if (sessionValueExists('project') and getSessionValue('project')!='*') {
    $defaultProject=getSessionValue('project');
  } else {
    $table=SqlList::getList('Project', 'name', null);
    $restrictArray=array();
    if (!$user->_accessControlVisibility) {
      $user->getAccessControlRights(); // Force setup of accessControlVisibility
    }
    if ($user->_accessControlVisibility!='ALL') {
      $restrictArray=$user->getVisibleProjects(true);
    }
    if (count($table)>0) {
      foreach ($table as $idTable=>$valTable) {
        if (count($restrictArray)==0 or isset($restrictArray[$idTable])) {
          $firstId=$idTable;
          break;
        }
      }
      $defaultProject=$firstId;
    }
  }
  if (property_exists($obj, $idType)) {
    if (!$obj->id) {
      if (SqlElement::class_exists($type)) {
        $listRestrictType=Type::listRestritedTypesForClass($type, $defaultProject, null, null);
        $listType=SqlList::getList($type);
        foreach ($listType as $keyType=>$valType) {
          if (in_array($keyType, $listRestrictType) or count($listRestrictType)==0) {
            $objType=new $type($keyType);
            break;
          }
        }
      }
    } else {
      if (SqlElement::class_exists($type)) $objType=new $type($obj->$idType);
    }
  } else if ($included) {
    $type=$obj->refType.'Type';
    $idType='id'.$type;
    if (!$obj->id) {
      if (SqlElement::class_exists($type)) {
        $listRestrictType=Type::listRestritedTypesForClass($type, $defaultProject, null, null);
        $listType=SqlList::getList($type);
        foreach ($listType as $keyType=>$valType) {
          if (in_array($keyType, $listRestrictType) or count($listRestrictType)==0) {
            $objType=new $type($keyType);
            break;
          }
        }
      }
    } else {
      if (SqlElement::class_exists($obj->refType)) {
        $orig=new $obj->refType($obj->refId);
        if (SqlElement::class_exists($type)) $objType=new $type($orig->$idType);
      }
    }
  }
  if (!$included) $section='';
  $nbLineSection=0;
  
  if (SqlElement::is_subclass_of($obj, 'PlanningElement')) {
    $obj->setVisibility();
    $workVisibility=$obj->_workVisibility;
    $costVisibility=$obj->_costVisibility;
    if (get_class($obj)=="MeetingPlanningElement" or get_class($obj)=="PeriodicMeetingPlanningElement") {
      $obj->setAttributes($workVisibility, $costVisibility);
    } else if (method_exists($obj, 'setAttributes')) {
      $obj->setAttributes();
    }
    // ADD BY Marc TABARY - 2017-02-16 - WORK AND COST VISIBILITY
  } else if (SqlElement::is_subclass_of($obj, 'BudgetElement')) {
    $obj->setVisibility();
    $workVisibility=$obj->_workVisibility;
    $costVisibility=$obj->_costVisibility;
    if (get_class($obj)=="OrganizationBudgetElement" or get_class($obj)=="OrganizationBudgetElementCurrent") {
      $obj->setAttributes($workVisibility, $costVisibility);
    }
    // END ADD BY Marc TABARY - 2017-02-16 - WORK AND COST VISIBILITY
  } else if (method_exists($obj, 'setAttributes')) {
    $obj->setAttributes();
  }
  $nobr=false;
  if (!$obj->id) {
    $canUpdate=(securityGetAccessRightYesNo('menu'.$classObj, 'create', $obj)=='YES');
  } else {
    $canUpdate=(securityGetAccessRightYesNo('menu'.$classObj, 'update', $obj)=='YES');
  }
  if ((isset($obj->locked) and $obj->locked and $classObj!='User') or isset($obj->_readOnly)) {
    $canUpdate=false;
  }
  $obj->setAllDefaultValues();
  $arrayRequired=$obj->getExtraRequiredFields(($objType)?$objType->id:null); // will define extra required fields, depending on status, planning mode...
  $extraHiddenFields=$obj->getExtraHiddenFields(($objType)?$objType->id:null);
  $extraReadonlyFields=$obj->getExtraReadonlyFields(($objType)?$objType->id:null);
  
  // BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
  if (isNotificationSystemActiv() and isNotifiable($classObj)) {
    $arrayClass=getUserVisibleObjectClassWithFieldDateType();
    if (in_array($classObj, $arrayClass)) {
      $arrayFields=getObjectClassFieldsList($classObj, true, true);
      if (!in_array("_sec_Notification", $arrayFields)) {
        $obj->{"_sec_Notification"}=null;
      }
      if (!in_array("_Notification", $arrayFields)) {
        $obj->{"_Notification"}=null;
      }
    }
  }
  // END - ADD BY TABARY - NOTIFICATION SYSTEM
  
  // Loop on each property of the object
  foreach ($obj as $col=>$val) {
    if ($detailWidth) {
      $colWidth=round((intval($displayWidth))/$nbCol); // 3 columns should be displayable
      $maxWidth=$colWidth-$labelWidth; // subtract label width
      if ($maxWidth>=$mediumWidth) {
        $largeWidth=$maxWidth;
      } else {
        $largeWidth=$mediumWidth;
      }
    }
    
    // BEGIN - ADD BY TABARY - TOOLTIP
    $toolTip=$obj->getFieldTooltip($col);
    // END - ADD BY TABARY - TOOLTIP
    
    $style=$obj->getDisplayStyling($col);
    $labelStyle=$style["caption"];
    $fieldStyle=$style["field"];
    $hide=false;
    $notReadonlyClass=" generalColClassNotReadonly ";
    $notRequiredClass=" generalColClassNotRequired ";
    $nobr_before=$nobr;
    $nobr=false;
    if ($included and ($col=='id' or $col=='refId' or $col=='refType' or $col=='refName')) {
      $hide=true;
    }
    // For PDF Export : hide line if section is hidden
    $hiddenSection=false;
    if ($section) {
      if ($parentHidden or $obj->isAttributeSetToField('_sec_'.$section, 'hidden') or in_array('_sec_'.$section, $extraHiddenFields)) {
        $hide=true;
        $hiddenSection=true;
      }
    }
    if (substr($col, 0, 7)=='_label_') {
      $attFld=substr($col, 7);
      if ($attFld=='expected') $attFld='expectedProgress';
      else if ($attFld=='planning') $attFld='id'.str_replace('PlanningElement', '', get_class($obj)).'PlanningMode';
      if (property_exists(get_class($obj), $attFld) and $obj->isAttributeSetToField($attFld, "hidden")) {
        $hide=true;
      } else if (in_array($attFld, $extraHiddenFields)) {
        $hide=true;
      }
    }
    if (substr($col, 0, 5)=='_lib_') {
      $attFld=substr($col, 5);
      if (substr($attFld, 0, 3)=='col' and substr($attFld, 3, 1)==strtoupper(substr($attFld, 3, 1))) $attFld=lcfirst(substr($attFld, 3));
      if (substr($attFld, 0, 3)=='col' and ucfirst(substr($attFld, 3, 1))==substr($attFld, 3, 1)) $attFld=substr($attFld, 3);
      if (property_exists(get_class($obj), $attFld) and $obj->isAttributeSetToField($attFld, "hidden")) {
        $hide=true;
      } else if (in_array($attFld, $extraHiddenFields)) {
        $hide=true;
      }
    }
    // If field is _tab_x_y, start a table presentation with x columns and y lines
    // the field _tab_x_y must be an array containing x + y values :
    // - the x column headers
    // - the y line headers
    
    // gautier #3251
    if (substr($col, 0, 4)=='_tab') {
      // BEGIN - ADD BY TABARY - FORCE HEADER TAB VISIBLE
      $forceHeader=$obj->isAttributeSetToField($col, "forceHeader");
      // END - ADD BY TABARY - FORCE HEADER TAB VISIBLE
      $decomp=explode("_", $col);
      $internalTableCols=$decomp[2];
      $internalTableRows=$decomp[3];
      // ADD qCazelles - dateComposition
      // if (count($val) == 8 and $val[4]=='startDate' and $val[5]=='deliveryDate' and Parameter::getGlobalParameter('displayMilestonesStartDelivery') != 'YES') $internalTableRows -= 2;
      // END ADD qCazelles - dateComposition
      $internalTableSpecial='';
      if (count($decomp)>4) {
        $internalTableSpecial=$decomp[4];
      }
      // Determine how many items to be displayed per line and column
      $arrTab=array('rows'=>array(), 'cols'=>array());
      $arrStart=-99;
      $arrStop=$internalTableCols*$internalTableRows;
      for ($ii=0; $ii<$internalTableCols; $ii++) {
        $arrTab['cols'][$ii]=0;
      }
      for ($ii=0; $ii<$internalTableRows; $ii++) {
        $arrTab['rows'][$ii]=0;
      }
      foreach ($obj as $arrCol=>$arrVal) {
        if ($arrCol==$col) {
          $arrStart=-1;
          continue;
        }
        if ($arrStart<-1) continue;
        $arrStart++;
        if ($arrStart>=$arrStop) break;
        if (substr($arrCol, 0, 6)=='_void_' or (substr($arrCol, 0, 7)=='_label_' and $outMode!='pdf') or substr($arrCol, 0, 8)=='_button_') {
          continue;
        }
        if ($hiddenSection) continue;
        // BEGIN - CHANGE BY TABARY - FORCE HEADER TAB VISIBLE
        // $spinnerAttr = $obj->getSpinnerAttributes($arrCol);
        // $showLabelInTab = (strpos($spinnerAttr,'showLabelInTab')===false?false:true);
        // if ($obj->isAttributeSetToField($arrCol, "hidden") or $parentHidden) continue;
        if (($obj->isAttributeSetToField($arrCol, "hidden") and !$forceHeader) or $parentHidden) continue;
        // END - CHANGE BY TABARY - FORCE HEADER TAB VISIBLE
        // if ($obj->isAttributeSetToField($arrCol, "hidden") or $parentHidden) continue;
        // END - ADD BY TABARY - IF ISSPINNER AND HIDE
        if (in_array($arrCol, $extraHiddenFields)) continue;
        $indCol=$arrStart%$internalTableCols;
        $indLin=floor($arrStart/$internalTableCols);
        $arrTab['rows'][$indLin]++;
        $arrTab['cols'][$indCol]++;
      }
      //
      $internalTable=$internalTableCols*$internalTableRows;
      // ADD qCazelles - dateComposition
      // if (count($val) == 8 and $val[4]=='startDate' and $val[5]=='deliveryDate' and Parameter::getGlobalParameter('displayMilestonesStartDelivery') != 'YES') {
      // unset($val[4]);
      // unset($val[5]);
      // }
      // END ADD qCazelles - dateComposition
      $internalTableRowsCaptions=array_slice($val, $internalTableCols);
      $internalTableCurrentRow=0;
      $colWidth=($detailWidth)/$nbCol;
      // #3538 - This part is no use any more since genericity of display of headers
      // if (SqlElement::is_subclass_of($obj, 'PlanningElement') and $internalTableRows>3) {
      // for ($i=0; $i<$internalTableRows; $i++) {
      // $testRowCaption=strtolower($internalTableRowsCaptions[$i]);
      // if ($workVisibility=='NO' and substr($testRowCaption, -4)=='work') {
      // $internalTableRowsCaptions[$i]='';
      // }
      // if ($costVisibility=='NO' and (substr($testRowCaption, -4)=='cost' or substr($testRowCaption, -7)=='expense')) {
      // $internalTableRowsCaptions[$i]='';
      // }
      // if ($costVisibility!='ALL' and substr($testRowCaption, 0, 13)=='reserveamount') {
      // $internalTableRowsCaptions[$i]='';
      // }
      // }
      // if ($workVisibility!='ALL' and $costVisibility!='ALL') {
      // $val[2]='';
      // $val[5]='';
      // }
      // }
      echo '</table><table id="'.$col.'" class="detail">';
      echo '<tr class="detail">';
      echo '<td class="detail"></td>'; // Empty label, to have column header in front of columns
                                       // $internalTableBorderTitle=($print)?'border:1px solid #A0A0A0;':'';
      $internalTableBorderTitle=($print)?'padding-top:5px;text-decoration: underline;padding-bottom:2px;':'';
      // Optimize width column
      if ($internalTableCols<=4) {
        $minWidth=300/$internalTableCols;
      } else {
        $minWidth=75;
      }
      for ($i=0; $i<$internalTableCols; $i++) { // draw table headers
                                                // echo '<td class="detail" style="min-width:75px;' . $internalTableBorderTitle . '">';
        echo '<td class="detail" style="min-width:'.$minWidth.'px;'.$internalTableBorderTitle.'">';
        if ($arrTab['cols'][$i]==0) {
          echo '<div class=""></div>';
          // CHANGE BY Marc TABARY - 2017-03-31 - COLEMPTY
        } else if ($val[$i] and $val[$i]!='empty') {
          // old
          // } else if ($val [$i]) {
          // END CHANGE BY Marc TABARY - 2017-03-31 - COLEMPTY
          echo '<div class="tabLabel" style="text-align:left;white-space:nowrap;">'.htmlEncode($obj->getColCaption($val[$i])).'</div>';
        } else {
          echo '<div class="tabLabel" style="text-align:left;white-space:nowrap;"></div>';
        }
        if ($i<$internalTableCols-1) {
          echo '</td>';
        }
      }
      // echo '</tr>'; NOT TO DO HERE - WILL BE DONE AFTER
    } else if (substr($col, 0, 5)=='_sec_' and (!$comboDetail or $col!='_sec_Link')) { // if field is _section, draw a new section bar column
      if ($col=='_sec_language' and Parameter::getGlobalParameter('displayLanguage')!='YES') continue;
      if ($col=='_sec_context' and Parameter::getGlobalParameter('displayContext')!='YES') continue;
      // END ADD qCazelles - Lang-Context
      // ADD by qCazelles - Bsuiness features
      if ($col=='_sec_ProductBusinessFeatures' and Parameter::getGlobalParameter('displayBusinessFeature')!='YES') continue;
      // END ADD qCazelles
      // ADD qCazelles - Manage ticket at customer level - Ticket #87
      if (($col=='_sec_TicketsClient' or $col=='_sec_TicketsContact') and Parameter::getGlobalParameter('manageTicketCustomer')!='YES') continue;
      // END ADD qCazelles - Manage ticket at customer level - Ticket #87
      // ADD qCazelles - Version compatibility
      if ($col=='_sec_ProductVersionCompatibility' and Parameter::getGlobalParameter('versionCompatibility')!='YES') continue;
      // END ADD qCazelles - Version compatibility
      // if ($col=='_sec_delivery' and Parameter::getGlobalParameter('productVersionOnDelivery') != 'YES') continue;
      
      // BEGIN - ADD BY TABARY - NO PRINT SECTION
      if (($print||$outMode=='pdf')&&substr($col, 0, 5)==="_sec_"&&$obj->isAttributeSetToField($col, 'noPrint')) {
        continue;
      }
      // END - ADD BY TABARY - NO PRINT SECTION
      
      $prevSection=$section;
      $currentCol+=1;
      if (strlen($col)>8) {
        $section=substr($col, 5);
      } else {
        $section='';
      }
      // Determine number of items to be displayed in Header
      // ADD BY Marc TABARY - 2017-02-22 - OBJECTS LINKED BY ID TO MAIN OBJECT
      if (\strpos($section, 'sOfObject')>0) {
        // It's a section that draws the object linked by be to the 'main object'
        // naming rule to draw list of objects linked by id ('foreign key') to the object
        // _sec_ : For section (it's generic to the FrameWork
        // _xxxs : xxx the object linked by id - Don't forget the 's' at the end
        // OfObject : indicate, it's a section for linked by id object
        $sectionField='_'.substr($section, 0, strpos($section, 'sOfObject'));
      } else {
        // END ADD BY Marc TABARY - 2017-02-22 - OBJECTS LINKED BY ID TO MAIN OBJECT
        $sectionField='_'.$section;
      }
      $sectionFieldDep='_Dependency_'.ucfirst($section);
      $sectionFieldDoc='_Document'.$section;
      $sectionFieldVP='_VersionProject';
      if ($section=='trigger') {
        $sectionFieldDep='_Dependency_Predecessor';
      }
      if (substr($section, 0, 14)=="Versionproject") {
        $sectionField='_VersionProject';
      }
      $cpt=null;
      if (property_exists($obj, $sectionField)&&isset($obj->$sectionField)&&is_array($obj->$sectionField)) {
        $cptt = 0;
        if($sectionField == "_Link"){
          $cptt = 0;
          $findme   = '_Link_';
          foreach ($obj as $idVal=>$listVal){
            $finded = strstr($idVal, $findme);
            if($finded){
              if(is_array($listVal)){
                $cptt+= count($listVal);
              }
            }
          }
        }
        $cpt=count($obj->$sectionField)-$cptt;
      } else if (property_exists($obj, $sectionFieldDep)&&is_array($obj->$sectionFieldDep)) {
        $cpt=count($obj->$sectionFieldDep);
      } else if (property_exists($obj, $sectionFieldDoc)&&is_array($obj->$sectionFieldDoc)) {
        $cpt=count($obj->$sectionFieldDoc);
      } else if (substr($section, 0, 14)=='Versionproject' and property_exists($obj, $sectionFieldVP) and is_array($obj->$sectionFieldVP)) {
        $cpt=count($obj->$sectionFieldVP);
      } else if ($section=='Affectations') {
        $crit=array('idProject=>'=>'0', 'idResource'=>'0');
        if ($classObj=='Project') {
          $crit=array('idProject'=>$obj->id);
        } else {
          $crit=array('idResource'=>$obj->id);
        }
        $aff=new Affectation();
        $cpt=$aff->countSqlElementsFromCriteria($crit);
      } else if ($section=='affectationsResourceTeam') {
        $crit=array('idResourceTeam'=>$obj->id);
        $aff=new ResourceTeamAffectation();
        $cpt=$aff->countSqlElementsFromCriteria($crit);
      } else if ($section=='affectationResourceTeamResource') {
        $crit=array('idResource'=>$obj->id);
        $aff=new ResourceTeamAffectation();
        $cpt=$aff->countSqlElementsFromCriteria($crit);
      } else {
        // ADD BY Marc TABARY - 2017-03-16 - FORCE SECTION ITEM'S COUNT
        // Want a item's count on section header
        // => In the section's declaration in the class : _sec_XXXXXXXX='itemsCount=method to call to count item'
        // Ex : Fields declaration in model class
        // $_sec_MySection='itemCount=getItemCount'
        // Sample : See OrganizationMain.php :
        // - Attributs declaration
        if (strpos($val, 'itemsCount=')!==false) {
          $cpt=null;
          $methodToCall=substr($val, strpos($val, '=')+1);
          if (method_exists($obj, $methodToCall)) {
            $cpt=count($obj->$methodToCall());
          }
        }
        // END ADD BY Marc TABARY - 2017-03-16 - FORCE SECTION ITEM'S COUNT
      }
      // Determine colSpan
      $colSpan=null;
      $colSpanSection='_'.lcfirst($section).'_colSpan';
      if (property_exists($obj, $colSpanSection)) {
        $colSpan=$obj->$colSpanSection;
      }
      $widthPct=setWidthPct($displayWidth, $print, $printWidth, $obj, $colSpan);
      if ($col=='_sec_void') {
        if ($prevSection) {
          echo '</table>';
          if (!$print) {
            echo '</div>';
          } else {
            echo '<br/>';
          }
        }
        if (!$print) {
          echo '<div style="float:left;width:'.$widthPct.'" ><table><tr><td>&nbsp;</td></tr>';
        } else {
          echo '<table>';
        }
      } else {
        startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, $cpt, $included, $obj);
      }
      // ADD qCazelles - Manage ticket at customer level - Ticket #87
    } else if ($col=='_spe_tickets' and !$obj->isAttributeSetTofield($col, 'hidden')) {
      drawTicketsList($obj);
      // END ADD qCazelles - Manage ticket at customer level - Ticket #87
    }     // Add mOlives - ticket 215 - 09/05/2018
    else if ($col=='_spe_activity' and !$obj->isAttributeSetTofield($col, 'hidden')) {
      drawActivityList($obj);
      // End mOlives - ticket 215 - 09/05/2018
    } else if (substr($col, 0, 5)=='_spe_') { // if field is _spe_xxxx, draw the specific item xxx
      $item=substr($col, 5);
      if ($internalTable) {
        if ($internalTable%$internalTableCols==0) {
          echo '</td><td>'.$cr;
          $internalTableCurrentRow++;
        } else {
          echo '</td><td>';
        }
      } else {
        echo '<tr><td colspan=2>';
      }
      // CHANGE BY Marc TABARY - 2017-03-08 - FORCE DRAWING A SPECIFIC ITEM
      if ((!$hide and !$parentHidden and !$obj->isAttributeSetToField($col, 'hidden') and !in_array($col, $extraHiddenFields)) or $obj->isAttributeSetToField($col, 'drawforce')==true) {
        echo $obj->drawSpecificItem($item, ($included?$parentReadOnly:$readOnly)); // the method must be implemented in the corresponidng class
      }
      // Old
      // if (!$hide and !$obj->isAttributeSetToField($col,'hidden')) {echo $obj->drawSpecificItem($item);} // the method must be implemented in the corresponidng class
      // END CHANGE BY Marc TABARY - 2017-03-08 - FORCE DRAWING A SPECIFIC ITEM
      if ($internalTable) {
        // echo '<td>';
      } else {
        echo '</td></tr>';
      }
    } else if (substr($col, 0, 6)=='_calc_') { // if field is _calc_xxxx, draw calculated item
      $item=substr($col, 6);
      echo $obj->drawCalculatedItem($item); // the method must be implemented in the corresponidng class
    } else if (substr($col, 0, 5)=='_lib_') { // if field is just a caption
      $item=substr($col, 5);
      if (strpos($obj->getFieldAttributes($col), 'nobr')!==false) {
        $nobr=true;
      }
      if ($obj->getFieldAttributes($col)!='hidden' and !$hide) {
        if ($nobr) echo '&nbsp;';
        echo '<span class="tabLabel" style="font-weight:normal">'.i18n($item).'</span>';
        echo '&nbsp;';
      }
      
      if (!$nobr and (!$hide or !$print)) {
        echo "</td></tr>";
      }
    } else if (substr($col, 0, 5)=='_Link' and !$comboDetail) { // Display links to other objects
      $linkClass=null;
      if (strlen($col)>5) {
        $linkClass=substr($col, 6);
      }
      drawLinksFromObject($val, $obj, $linkClass);
    } else if ($col=='_productComposition' and !$obj->isAttributeSetToField($col, "hidden")) { // Display Composition of Product (structure)
      drawStructureFromObject($obj, false, 'composition', 'Product');
      // ADD qCazelles - Lang-Context
    } else if ($col=='_productLanguage' and Parameter::getGlobalParameter('displayLanguage')=='YES') {
      drawLanguageSection($obj);
    } else if ($col=='_productContext' and Parameter::getGlobalParameter('displayContext')=='YES') {
      drawContextSection($obj);
      // END ADD qCazelles - Lang-Context
      // ADD by qCazelles - Business features
    } else if ($col=='_productBusinessFeatures' and Parameter::getGlobalParameter('displayBusinessFeature')=='YES') {
      drawBusinessFeatures($obj);
      // END ADD
      // ADD qCazelles - Version compatibility
    } else if ($col=='_productVersionCompatibility' and Parameter::getGlobalParameter('versionCompatibility')=='YES') {
      drawVersionCompatibility($obj);
      // END ADD qCazelles - Version compatibility
      // ADD qCazelles
    } else if ($col=='_versionDelivery' and Parameter::getGlobalParameter('productVersionOnDelivery')=='YES') {
      drawDeliverysFromObject($obj);
      // END ADD qCazelles
    } else if ($col=='_componentComposition' and !$obj->isAttributeSetToField($col, "hidden")) { // Display Composition of component (structure)
      drawStructureFromObject($obj, false, 'composition', 'Component');
    } else if ($col=='_componentStructure' and !$obj->isAttributeSetToField($col, "hidden")) { // Display Structure of component (structure)
      drawStructureFromObject($obj, false, 'structure', 'Component');
    } else if ($col=='_productVersionComposition' and !$obj->isAttributeSetToField($col, "hidden")) { // Display ProductVersionStructure (structure)
      drawVersionStructureFromObject($obj, false, 'composition', 'ProductVersion');
    } else if ($col=='_componentVersionStructure' and !$obj->isAttributeSetToField($col, "hidden")) { // Display ProductVersionStructure (structure)
      drawVersionStructureFromObject($obj, false, 'structure', 'ComponentVersion');
    } else if ($col=='_componentVersionComposition' and !$obj->isAttributeSetToField($col, "hidden")) { // Display ProductVersionStructure (structure)
      drawVersionStructureFromObject($obj, false, 'composition', 'ComponentVersion');
    } else if (substr($col, 0, 11)=='_Assignment') { // Display Assignments
      drawAssignmentsFromObject($val, $obj);
    } else if (substr($col, 0, 11)=='_Approver') { // Display Assignments
      drawApproverFromObject($val, $obj);
    } else if (substr($col, 0, 15)=='_VersionProject') { // Display Version Project
      drawVersionProjectsFromObject($val, $obj);
    } else if (substr($col, 0, 15)=='_ProductProject') { // Display Version Project
      drawProductProjectsFromObject($val, $obj);
    } else if (substr($col, 0, 11)=='_Dependency') { // Display Dependencies
      $depType=(strlen($col)>11)?substr($col, 12):"";
      drawDependenciesFromObject($val, $obj, $depType);
    } else if ($col=='_ResourceCost') { // Display ResourceCost
      drawResourceCostFromObject($val, $obj, false);
    } else if ($col=='_BillLineTerm') {
      if (get_class($obj)=='ProviderBill') {
        $providerTerm=new ProviderTerm();
        $listProvTerm=$providerTerm->getSqlElementsFromCriteria(array("idProviderBill"=>$obj->id));
        $lines=array();
        foreach ($listProvTerm as $term) {
          $providerTerm=new ProviderTerm($term->id);
          array_push($lines, $providerTerm->_BillLineTerm);
        }
        if ($lines) {
          $prevSection=$section;
          $section="BillLineTerm";
          $colSpanSection='_'.lcfirst($section).'_colSpan';
          if (property_exists($obj, $colSpanSection)) {
            $colSpan=$obj->$colSpanSection;
          }
          $widthPct=setWidthPct($displayWidth, $print, $printWidth, $obj, "2");
          startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, count($val), $included, $obj);
          drawBillLinesProviderTerms($obj, false);
        }
      } else {
        $prevSection=$section;
        $section="BillLineTerm";
        $colSpanSection='_'.lcfirst($section).'_colSpan';
        if (property_exists($obj, $colSpanSection)) {
          $colSpan=$obj->$colSpanSection;
        }
        $widthPct=setWidthPct($displayWidth, $print, $printWidth, $obj, "2");
        startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, count($val), $included, $obj);
        drawBillLinesProviderTerms($obj, false);
      }
    } else if ($col=='_DocumentVersion') { // Display
      drawDocumentVersionFromObject($val, $obj, false);
    } else if ($col=='_ExpenseDetail') { // Display ExpenseDetail
      if ($obj->getFieldAttributes($col)!='hidden') {
        drawExpenseDetailFromObject($val, $obj, false);
      }
    } else if (substr($col, 0, 12)=='_TestCaseRun') { // Display TestCaseRun
      drawTestCaseRunFromObject($val, $obj);
    } else if (substr($col, 0, 13)=='_ProviderTerm') {
      drawProviderTermFromProviderBill($val, $obj);
    } else if (substr($col, 0, 11)=='_Attachment' and !$comboDetail) {
      if (!isset($isAttachmentEnabled)) {
        $isAttachmentEnabled=true; // allow attachment
        if (!Parameter::getGlobalParameter('paramAttachmentDirectory') or !Parameter::getGlobalParameter('paramAttachmentMaxSize')) {
          $isAttachmentEnabled=false;
        }
      }
      if ($isAttachmentEnabled and !$comboDetail) {
        if ($obj->isAttributeSetToField('_Attachment', 'hidden') or in_array('_Attachment', $extraHiddenFields)) continue;
        $prevSection=$section;
        $section="Attachment";
        $ress=new Resource(getCurrentUserId());
        $cpt=0;
        foreach ($obj->_Attachment as $cptObjTmp) {
          if ($user->id==$cptObjTmp->idUser or $cptObjTmp->idPrivacy==1 or ($cptObjTmp->idPrivacy==2 and $ress->idTeam==$cptObjTmp->idTeam)) {
            $cpt++;
          }
        }
        startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, $cpt, $included, $obj);
        drawAttachmentsFromObject($obj, false);
      }
      // ADD qCazelles - Lang
    } else if ($col=='idLanguage' and Parameter::getGlobalParameter('displayLanguage')!='YES') {
      continue;
      // END ADD qCazelles - Lang
    } else if (substr($col, 0, 5)=='_Note' and !$comboDetail) {
      if ($obj->isAttributeSetToField('_Note', 'hidden') or in_array('_Note', $extraHiddenFields)) continue;
      $prevSection=$section;
      $section="Note";
      $ress=new Resource(getCurrentUserId());
      $cpt=0;
      foreach ($obj->_Note as $cptObjTmp) {
        if ($user->id==$cptObjTmp->idUser or $cptObjTmp->idPrivacy==1 or ($cptObjTmp->idPrivacy==2 and $ress->idTeam==$cptObjTmp->idTeam)) {
          $cpt++;
        }
      }
      startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, $cpt, $included, $obj);
      drawNotesFromObject($obj, false);
    } else if ($col=='_BillLine') {
      if (get_class($obj)=='ProviderBill') {
        $providerTerm=new ProviderTerm();
        $listProvTerm=$providerTerm->getSqlElementsFromCriteria(array("idProviderBill"=>$obj->id));
        $lines=array();
        foreach ($listProvTerm as $term) {
          $providerTerm=new ProviderTerm($term->id);
          array_push($lines, $providerTerm->_BillLineTerm);
        }
        if (!$lines) {
          $prevSection=$section;
          $section="BillLine";
          $colSpanSection='_'.lcfirst($section).'_colSpan';
          if (property_exists($obj, $colSpanSection)) {
            $colSpan=$obj->$colSpanSection;
          }
          $widthPct=setWidthPct($displayWidth, $print, $printWidth, $obj, "2");
          startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, count($val), $included, $obj);
          drawBillLinesFromObject($obj, false);
        }
      } else {
        $prevSection=$section;
        $section="BillLine";
        $colSpanSection='_'.lcfirst($section).'_colSpan';
        if (property_exists($obj, $colSpanSection)) {
          $colSpan=$obj->$colSpanSection;
        }
        $widthPct=setWidthPct($displayWidth, $print, $printWidth, $obj, "2");
        startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, count($val), $included, $obj);
        drawBillLinesFromObject($obj, false);
      }
      // BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
    } else if ($col==='_Notification') {
      drawNotificationsLinkedToObject($obj);
      // END - ADD BY TABARY - NOTIFICATION SYSTEM
      
      // ADD BY Marc TABARY - 2017-02-23 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT
    } else if (substr($col, 0, 1)=='_' and strpos($section, 'sOfObject')>0 and strpos($col, '_colSpan')==false) {
      drawObjectLinkedByIdToObject($obj, substr($col, 1), false);
      // END ADD BY Marc TABARY - 2017-02-23 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT
    } else if (substr($col, 0, 1)=='_' and //
                                           // CHANGE BY Marc TABARY - 2017-02-28 - DATA CONSTRUCTED BY FUNCTION
    substr($col, 0, 6)!='_void_' and substr($col, 0, 7)!='_label_' and substr($col, 0, 8)!='_button_' and substr($col, 0, 7)!='_byMet_') { // field not to be displayed
                                                                                                                                             // Old
                                                                                                                                             // substr($col, 0, 6) != '_void_' and substr($col, 0, 7) != '_label_' and substr($col, 0, 8) != '_button_') { // field not to be displayed
                                                                                                                                             // END CHANGE BY Marc TABARY - 2017-02-28 - DATA CONSTRUCTED BY FUNCTION //
    } else {
      $attributes='';
      // ADD BY Marc TABARY - 2017-03-02 - DRAW SPINNER
      $isSpinner=($obj->getSpinnerAttributes($col)==''?false:true);
      // END ADD BY Marc TABARY - 2017-03-02 - DRAW SPINNER
      $isRequired=false;
      $readOnly=false;
      $specificStyle='';
      $specificStyleWithoutCustom='';
      if (($col=="idle" or $col=="done" or $col=="handled" or $col=="cancelled" or $col=="solved") and $objType) {
        $lock='lock'.ucfirst($col);
        if (!$obj->id or (property_exists($objType, $lock) and $objType->$lock)) {
          $attributes.=' readonly tabindex="-1"';
          $notReadonlyClass="";
          $readOnly=true;
        }
      }
      // ADD qCazelles - Ticket #53
      if (($col=="realStartDate" or $col=="isStarted") and $objType) {
        $lock='lockHandled';
        if (!$obj->id or (property_exists($objType, $lock) and $objType->$lock)) {
          $attributes.=' readonly tabindex="-1"';
          $notReadonlyClass="";
          $readOnly=true;
        }
      }
      if (($col=="realDeliveryDate" or $col=="isDelivered") and $objType) {
        $lock='lockDone';
        if (!$obj->id or (property_exists($objType, $lock) and $objType->$lock)) {
          $attributes.=' readonly tabindex="-1"';
          $notReadonlyClass="";
          $readOnly=true;
        }
      }
      if (($col=="realEisDate" or $col=="isEis") and $objType) {
        $lock='lockIntoservice';
        if (!$obj->id or (property_exists($objType, $lock) and $objType->$lock)) {
          $attributes.=' readonly tabindex="-1"';
          $notReadonlyClass="";
          $readOnly=true;
        }
      }
      if (($col=="realEndDate" or $col=="idle") and $objType) {
        $lock='lockIdle';
        if (!$obj->id or (property_exists($objType, $lock) and $objType->$lock)) {
          $attributes.=' readonly tabindex="-1"';
          $notReadonlyClass="";
          $readOnly=true;
        }
      }
      // END ADD qCazelles - Ticket #53
      if (strpos($obj->getFieldAttributes($col), 'required')!==false) {
        // $attributes.=' required="true" missingMessage="' . i18n('messageMandatory', array($obj->getColCaption($col))) . '" invalidMessage="' . i18n('messageMandatory', array($obj->getColCaption($col))) . '"';
        $isRequired=true;
        $notRequiredClass="";
      }
      if (array_key_exists($col, $arrayRequired)) {
        $attributes.=' required="true" missingMessage="'.i18n('messageMandatory', array($obj->getColCaption($col))).'" invalidMessage="'.i18n('messageMandatory', array(
            $obj->getColCaption($col))).'"';
        $isRequired=true;
      }
      if (strpos($obj->getFieldAttributes($col), 'hidden')!==false) {
        $hide=true;
      } else if (in_array($col, $extraHiddenFields)) {
        $specificStyle.=' display:none';
        if ($print) $hide=true;
      }
      if ($col=='idBusinessFeature' and Parameter::getGlobalParameter('displayBusinessFeature')!='YES') {
        $hide=true;
      }
      // ADD qCazelles
      // if ($col=='idProductVersion' and get_class($obj) == 'Delivery' and Parameter::getGlobalParameter('productVersionOnDelivery') != 'YES') {
      // $hide=true;
      // }
      // END ADD qCazelles
      // ADD qCazelles - Project restriction
      if ($col=='idProject') {
        $uniqueProjectRestriction=false;
        if (getSessionValue('project')!="" and getSessionValue('project')!="*" and Parameter::getGlobalParameter('projectRestriction')=='YES') {
          $proj=new Project(getSessionValue('project'));
          $subProjs=$proj->getSubProjects();
          if (count($subProjs)==0) {
            $uniqueProjectRestriction=true;
            $hide=true;
          }
        }
      }
      // END ADD qCazelles - Project restriction
      // ADD qCazelles - dateComposition
      // if (SqlElement::is_a($obj,'Version') and Parameter::getGlobalParameter('displayMilestonesStartDelivery') != 'YES' and ($col=='initialStartDate' or $col=='plannedStartDate' or $col=='realStartDate' or $col=='isStarted' or $col=='initialDeliveryDate' or $col=='plannedDeliveryDate' or $col=='realDeliveryDate' or $col=='isDelivered')) {
      // $hide=true; //continue;
      // }
      // END ADD qCazelles - dateComposition
      if (($col=='idUser' or $col=='creationDate' or $col=='creationDateTime' or $col=='lastUpdateDateTime') and !$print) {
        $hide=true;
      }
      if ($obj->isAttributeSetToField($col, 'nobr')) {
        $nobr=true;
        $tempCurrentFound=false;
        foreach ($obj as $tmpCol=>$tmpVal) {
          if ($tmpCol==$col) {
            $tempCurrentFound=true;
            continue;
          } else if ($tempCurrentFound==false) {
            continue;
          }
          // Here current was found and
          if ($obj->isAttributeSetToField($tmpCol, 'hidden') or in_array($tmpCol, $extraHiddenFields)) {
            if (!$obj->isAttributeSetToField($tmpCol, 'nobr')) {
              $nobr=false; // Current is NOBR but next is hidden and not NOBR (no next on same line) : remove NOBR
              break;
            }
          } else {
            break; // OK current is NOBR and next is visible
          }
        }
      }
      if (strpos($obj->getFieldAttributes($col), 'invisible')!==false) {
        $specificStyle.=' display:none';
      }
      if (strpos($obj->getFieldAttributes($col), 'title')!==false) {
        $attributes.=' title="'.$obj->getTitle($col).'"';
      }
      if ($col=='idComponent' or $col=='idComponentVersion' or $col=='idOriginalComponentVersion' or $col=='idTargetComponentVersion') {
        if (Component::canViewComponentList($obj)!='YES') {
          $hide=true;
        }
      }
      if ($parentHidden) {
        $hide=true;
      }
      // CHANGE BY Marc TABARY - 2017-03-01 - DATA CONSTRUCTED BY FUNCTION
      if (!$canUpdate or (strpos($obj->getFieldAttributes($col), 'readonly')!==false) or $parentReadOnly or (property_exists($obj, 'idle') and ($obj->idle==1 and $col!='idle' and $col!='idStatus')) or substr($col, 0, 7)=='_byMet_') {
        // END CHANGE BY Marc TABARY - 2017-03-01 - DATA CONSTRUCTED BY FUNCTION
        // COMMENT BY Marc TABARY - 2017-03-01 - DATA CONSTRUCTED BY FUNCTION
        // Old
        // if (!$canUpdate or (strpos($obj->getFieldAttributes($col), 'readonly') !== false) or $parentReadOnly or ($obj->idle == 1 and $col != 'idle' and $col != 'idStatus')) {
        // END COMMENT BY Marc TABARY - 2017-03-01 - DATA CONSTRUCTED BY FUNCTION
        // ADD BY Marc TABARY - 2017-03-09 - PERIODIC YEAR BUDGET ELEMENT
        if ((strpos($obj->getFieldAttributes($col), 'forceInput')!==false and substr($col, 0, 7)=='_byMet_' and !$parentReadOnly) or (strpos($obj->getFieldAttributes($col), 'superforceInput')!==false and substr($col, 0, 7)=='_byMet_')) {} else {
          // END ADD BY Marc TABARY - 2017-03-09 - PERIODIC YEAR BUDGET ELEMENT
          $attributes.=' readonly tabindex="-1"';
          $notReadonlyClass="";
          $readOnly=true;
        }
      } else if (in_array($col, $extraReadonlyFields)) {
        $attributes.=' readonly tabindex="-1"';
        $readOnly=true;
      }
      // ADD BY Marc TABARY - 2017-02-28 - DATA CONSTRUCTED BY FUNCTION
      if (substr($col, 0, 7)=='_byMet_') {
        if (substr($col, -4, 4)=='Work' or substr($col, -3, 3)=='Pct' or substr($col, -4, 4)=='Rate' or strpos(strtolower($col), 'amount')!==false) {
          $dataType='decimal';
          $dataLength='14.5';
        }
        if (substr($col, -4, 4)=='Name') {
          $dataType='varchar';
          $dataLength=400;
        }
      } else {
        // END ADD BY Marc TABARY - 2017-02-28 - DATA CONSTRUCTED BY FUNCTION
        $dataType=$obj->getDataType($col);
        $dataLength=$obj->getDataLength($col);
      }
      if ($obj->isAttributeSetToField($col, 'calculated') and (substr($col, -4, 4)=='Cost' or substr($col, -6, 6)=='Amount' or $col=='amount')) {
        $dataType='decimal';
      }
      if ($internalTable==0) {
        if (!is_object($val) and !is_array($val) and !$hide and !$nobr_before) {
          echo '<tr class="detail'.((!$nobr)?' generalRowClass '.$col.'Class':'').'" style="'.((!$nobr)?$specificStyle:'').'">';
          if ($dataLength>4000 and getEditorType()!='text') {
            // Will have to add label
            echo '<td colspan="2">';
          } else {
            echo '<td class="label" style="position:relative;width:'.$labelStyleWidth.';">';
            $thumbRes=SqlElement::isThumbableField($col);
            $thumbColor=SqlElement::isColorableField($col);
            $formatedThumb='';
            if ($thumbRes) {
              $formatedThumb=formatUserThumb($val, null, null, 22, 'right');
            } else if ($thumbColor) {
              $formatedThumb=formatColorThumb($col, $val, 20, 'right');
            }
            // $thumbIcon=SqlElement::isIconableField($col);
            $thumb=(!$print&&$val&&($thumbRes or $thumbColor)&&$showThumb&&$formatedThumb)?true:false;
            echo '<label for="'.$col.'" class="'.(($thumb)?'labelWithThumb ':'').'generalColClass '.$col.'Class" style="'.$specificStyle.';'.$labelStyle.'">';
            if ($outMode=='pdf') {
              echo str_replace(' ', '&nbsp;', htmlEncode($obj->getColCaption($col), 'stipAllTags'));
            } else {
              echo htmlEncode($obj->getColCaption($col), 'stipAllTags');
            }
            echo '&nbsp;'.(($thumb)?'':':&nbsp;').'</label>'.$cr;
            if ($thumb) {
              // echo $formatedThumb;
              if (!$print) echo '<div style="position:absolute;top:1px;right:0px;float:right;">';
              if ($col=='idStatus') {
                echo '<a onmouseover="drawGraphStatus();">';
              }
              echo $formatedThumb;
              if ($col=='idStatus') {
                echo '</a>';
                echo '<div id="graphStatusDiv" dojoType="dijit.layout.ContentPane" region="center" class="graphStatusDiv">';
                echo '</div>';
              }
              if (!$print) echo "</div>";
            }
            echo '</td>';
            if ($print and $outMode=="pdf") {
              echo '<td style="width:'.($largeWidth+10).'px">';
            } else {
              echo '<td style="width:'.($largeWidth+10).'px">';
            }
          }
        }
      } else {
        // $internalTableBorder=($print)?'border:1px dotted #A0A0A0;':'';
        $internalTableBorder='';
        if ($internalTable%$internalTableCols==0) {
          echo '</td></tr>'.$cr;
          echo '<tr class="detail">';
          echo '<td class="'.$internalTableSpecial.'" style="text-align:right;width:'.$labelStyleWidth.';">';
          if ($internalTableRowsCaptions[$internalTableCurrentRow] and $arrTab['rows'][$internalTableCurrentRow]>0) {
            // ADD BY Marc TABARY - 2017-03-10 - NO ':' IF LABEL IS EMPTY
            $theLabelTab=htmlEncode($obj->getColCaption($internalTableRowsCaptions[$internalTableCurrentRow]));
            if ($internalTableRowsCaptions[$internalTableCurrentRow]=='empty') {
              $theLabelTab='';
            }
            if ($theLabelTab=='') {
              echo '<label class="label '.$internalTableSpecial.'">'.$theLabelTab.'&nbsp;&nbsp;</label>';
            } else {
              // END ADD BY Marc TABARY - 2017-03-10 - NO ':' IF LABEL IS EMPTY
              echo '<label class="label '.$internalTableSpecial.'">'.htmlEncode($obj->getColCaption($internalTableRowsCaptions[$internalTableCurrentRow])).'&nbsp;:&nbsp;</label>';
            }
          }
          echo '</td><td style="width:90%;white-space:nowrap;'.$internalTableBorder.'">';
          $internalTableCurrentRow++;
        } else {
          if ($obj->isAttributeSetToField($col, "colspan3")) {
            echo '</td><td class="detail" colspan="3">';
            $internalTable-=2;
          } else {
            echo '</td><td class="detail" style="white-space:nowrap;'.$internalTableBorder.'">';
          }
        }
      }
      // echo $col . "/" . $dataType . "/" . $dataLength;
      if ($dataLength) {
        if ($dataLength<=3) {
          $fieldWidth=$verySmallWidth;
        } else if ($dataLength<=10) {
          $fieldWidth=$smallWidth;
        } else if ($dataLength<=25) {
          $fieldWidth=$mediumWidth;
        } else {
          $fieldWidth=$largeWidth;
        }
      }
      if (substr($col, 0, 2)=='id' and $dataType=='int' and strlen($col)>2 and substr($col, 2, 1)==strtoupper(substr($col, 2, 1))) {
        $fieldWidth=$largeWidth;
      }
      if (strpos($obj->getFieldAttributes($col), 'Width')!==false) {
        if (strpos($obj->getFieldAttributes($col), 'smallWidth')!==false) {
          $fieldWidth=$smallWidth;
        }
        if (strpos($obj->getFieldAttributes($col), 'mediumWidth')!==false) {
          $fieldWidth=$mediumWidth;
        }
        if (strpos($obj->getFieldAttributes($col), 'truncatedWidth')!==false) {
          $pos=strpos($obj->getFieldAttributes($col), 'truncatedWidth');
          $truncValue=substr($obj->getFieldAttributes($col), $pos+14, 3);
          $fieldWidth-=$truncValue;
        }
      }
      // echo $dataType . '(' . $dataLength . ') ';
      if ($included) {
        $name=' id="'.$classObj.'_'.$col.'" name="'.$classObj.'_'.$col.$extName.'" ';
        $nameBis=' id="'.$classObj.'_'.$col.'Bis" name="'.$classObj.'_'.$col.'Bis'.$extName.'" ';
        $fieldId=$classObj.'_'.$col;
      } else {
        $name=' id="'.$col.'" name="'.$col.$extName.'" ';
        $nameBis=' id="'.$col.'Bis" name="'.$col.'Bis'.$extName.'" ';
        $fieldId=$col;
      }
      // prepare the javascript code to be executed
      $colScript="";
      if ($outMode!='pdf') $colScript=$obj->getValidationScript($col);
      $colScriptBis="";
      if ($dataType=='datetime' and $outMode!='pdf') {
        $colScriptBis=$obj->getValidationScript($col."Bis");
      }
      // if ($comboDetail) {
      // $colScript=str_replace($col,$col . $extName,$colScript);
      // $colScriptBis=str_replace($col,$col . $extName,$colScriptBis);
      // }
      $specificStyleWithoutCustom=$specificStyle;
      $specificStyle.=";".$fieldStyle;
      if (strpos($obj->getFieldAttributes($col), 'size1/3')!==false) {
        $fieldWidth=$fieldWidth/3-3;
      } else if (strpos($obj->getFieldAttributes($col), 'size1/2')!==false) {
        $fieldWidth=$fieldWidth/2-2;
      } else if (($nobr_before or $nobr) and $fieldWidth>$mediumWidth) {
        $fieldWidth=$fieldWidth/2-2;
      }
      if (is_object($val)) {
        if ($col=='Origin' and !$hide) {
          drawOrigin($obj->Origin, $val->originType, $val->originId, $obj, $col, $print);
        } else {
          // Draw an included object (recursive call) =========================== Type Object
          $visibileSubObject=true;
          if (get_class($val)=='WorkElement') {
            $hWork=SqlElement::getSingleSqlElementFromCriteria('HabilitationOther', array('idProfile'=>$profile, 'scope'=>'work'));
            if ($hWork and $hWork->id) {
              $visibility=SqlList::getFieldFromId('VisibilityScope', $hWork->rightAccess, 'accessCode', false);
              if ($visibility!='ALL') {
                $visibileSubObject=false;
              }
            }
          }
          if ($hide or $obj->isAttributeSetToField($col, 'hidden') or in_array($col, $extraHiddenFields)) {
            $visibileSubObject=false;
          }
          // if () {
          drawTableFromObject($val, true, $readOnly, !$visibileSubObject);
          $hide=true; // to avoid display of an extra field for the object and an additional carriage return
                        // }
        }
        // }
      } else if (is_array($val)) {
        // Draw an array ====================================================== Type Array
        traceLog("Error : array fileds management not implemented for fiels $col");
      } else if (substr($col, 0, 6)=='_void_') {
        // Empty field for tabular presentation
        // echo $col . ' is an array' . $cr;
        //
      } else if (substr($col, 0, 7)=='_label_') {
        $captionName=substr($col, 7);
        if (!$hide) {
          echo '<label class="label shortlabel">'.i18n('col'.ucfirst($captionName)).'&nbsp;:&nbsp;</label>';
        }
      } else if (substr($col, 0, 8)=='_button_') {
        if (!$print and !$comboDetail and !$obj->isAttributeSetToField($col, 'hidden') and !$hide) {
          $item=substr($col, 8);
          echo $obj->drawSpecificItem($item);
        }
      } else if ($print) {
        // ============================================================================================================
        // ================================================
        // ================================================ PRINT
        // ================================================
        // ============================================================================================================
        if ($hide) { // hidden field
                       // nothing
        } else if (strpos($obj->getFieldAttributes($col), 'displayHtml')!==false) {
          // Display full HTML ================================================== Hidden field
          // echo '<div class="displayHtml">';
          echo '<span style="'.$fieldStyle.'">';
          if ($outMode=='pdf') {
            echo htmlRemoveDocumentTags($val);
          } else {
            echo $val;
          }
          echo '</span>';
        } else if ($col=='id') { // id
          echo '<span style="color:grey;'.$fieldStyle.'">#'.$val."&nbsp;&nbsp;&nbsp;</span>";
        } else if ($col=='password') {
          echo "..."; // nothing
        } else if ($dataType=='date' and $val!=null and $val!='') {
          echo '<span style="'.$fieldStyle.'">';
          echo htmlFormatDate($val);
          echo '</span>';
        } else if ($dataType=='datetime' and $val!=null and $val!='') {
          // echo str_replace(' ','&nbsp;',htmlFormatDateTime($val, false));
          echo '<span style="'.$fieldStyle.'">';
          echo htmlFormatDateTime($val, false);
          echo '</span>';
        } else if ($dataType=='time' and $val!=null and $val!='') {
          echo '<span style="'.$fieldStyle.'">';
          echo htmlFormatTime($val, false);
          echo '</span>';
        } else if ($col=='color' and $dataLength==7) { // color
          echo '<table><tr><td style="width: 100px;">';
          echo '<div class="colorDisplay" readonly tabindex="-1" ';
          echo '  value="'.htmlEncode($val).'" ';
          echo '  style="width:'.($smallWidth/2).'px; border-radius:10px;';
          echo ' color: '.$val.'; ';
          echo ' background-color: '.$val.';"';
          echo ' >';
          echo '</div>';
          echo '</td>';
          if ($val!=null and $val!='') {
            // echo '<td class="detail">&nbsp;(' . htmlEncode($val) . ')</td>';
          }
          echo '</tr></table>';
        } else if ($dataType=='int' and $dataLength==1) { // boolean
          $checkImg="checkedKO.png";
          if ($val!='0' and !$val==null) {
            $checkImg='checkedOK.png';
          }
          if ($col=='cancelled' or $col=='solved') echo "&nbsp;&nbsp;&nbsp;";
          echo '<img src="img/'.$checkImg.'" />';
          // BEGIN - REPLACE BY TABARY - USE isForeignKey GENERIC FUNCTION
        } else if (isForeignKey($col, $obj)) { // Idxxx
                                               // } else if (substr($col, 0, 2) == 'id' and $dataType == 'int' and strlen($col) > 2 and substr($col, 2, 1) == strtoupper(substr($col, 2, 1))) { // Idxxx
                                               // END - REPLACE BY TABARY - USE isForeignKey GENERIC FUNCTION
          echo '<span style="'.$fieldStyle.'">';
          // BEGIN - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES SAME idXXXX IN THE SAME OBJECT
          echo htmlEncode(SqlList::getNameFromId(substr(foreignKeyWithoutAlias($col), 2), $val));
          // echo htmlEncode(SqlList::getNameFromId(substr($col, 2), $val));
          // END - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES SAME idXXXX IN THE SAME OBJECT
          echo '</span>';
        } else if ($dataLength>4000) {
          // echo '</td></tr><tr><td colspan="2">';
          echo '<div style="text-align:left;font-weight:normal" class="tabLabel">'.htmlEncode($obj->getColCaption($col), 'stipAllTags').'&nbsp;:&nbsp;</div>';
          echo '<div style="border:1px dotted #AAAAAA;width:'.($colWidth-20).'px;padding:5px;'.$fieldStyle.'">';
          if (isTextFieldHtmlFormatted($val)) $val=htmlEncode($val, 'formatted');
          if ($outMode=="pdf") { // Must purge data, otherwise will never be generated
            if ($preseveHtmlFormatingForPDF) {
              $val='<div>'.$val.'</div>';
            } else {
              $val=htmlEncode($val, 'pdf'); // remove all tags but line breaks
            }
          }
          echo $val.'&nbsp;';
          echo '</div>';
        } else if ($dataLength>100) { // Text Area (must reproduce BR, spaces, ...
          echo '<span style="'.$fieldStyle.'">';
          echo htmlEncode($val, 'print');
          $fldFull='_'.$col.'_full';
          if ($outMode=='pdf' and isset($obj->$fldFull)) {
            echo '<img src="../view/css/images/doubleArrowDown.png" />';
          }
          echo '</span>';
        } else if ($dataType=='decimal' and (substr($col, -4, 4)=='Cost' or substr($col, -6, 6)=='Amount' or $col=='amount')) {
          echo '<span style="'.$fieldStyle.'">';
          if ($currencyPosition=='after') {
            echo htmlEncode($val, 'print').' '.$currency;
          } else {
            echo $currency.' '.htmlEncode($val, 'print');
          }
          echo '</span>';
        } else if ($dataType=='decimal' and substr($col, -4, 4)=='Work') {
          echo '<span style="'.$fieldStyle.'">';
          echo Work::displayWork($val).' '.Work::displayShortWorkUnit();
          echo '</span>';
        } else if (strtolower(substr($col, -8, 8))=='progress' or substr($col, -3, 3)=='Pct' or substr($col, -4, 4)=='Rate') {
          echo '<span style="'.$fieldStyle.'">';
          echo $val.'&nbsp;%';
          echo '</span>';
        } else if ($col=='icon') {
          if ($val) {
            echo '<img src="../view/icons/'.$val.'" />';
          }
        } else {
          if ($obj->isFieldTranslatable($col)) {
            $val=i18n($val);
          }
          if (0 and $internalTable==0) {
            echo '<div style="width: 80%;'.$fieldStyle.'"> ';
            if (strpos($obj->getFieldAttributes($col), 'html')!==false) {
              echo $val;
            } else {
              echo htmlEncode($val, 'print');
            }
            echo '</div>';
          } else {
            echo '<span style="'.$fieldStyle.'">';
            if (strpos($obj->getFieldAttributes($col), 'html')!==false) {
              echo $val;
            } else {
              echo htmlEncode($val, 'print');
            }
            echo '</span>';
          }
        }
        // ============================================================================================================
        // ================================================
        // ================================================ END OF PRINT : Entering general case
        // ================================================
        // ============================================================================================================
      } else if ($hide) {
        // Don't draw the field =============================================== Hidden field
        if (!$print) {
          if ($col=='creationDate' and ($val=='' or $val==null) and !$obj->id) {
            $val=date('Y-m-d');
          }
          if ($col=='creationDateTime' and ($val=='' or $val==null) and !$obj->id) {
            $val=date('Y-m-d H:i:s');
          }
          if ($col=='idUser' and ($val=='' or $val==null) and !$obj->id) {
            $val=$user->id;
          }
          // BEGIN - ADD BY TABARY - IF SPINNER AND HIDE => Draw but display:none
          if ($isSpinner and is_integer(intval($val))) {
            $title=' title="'.$obj->getTitle($col).'"';
            echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
            echo htmlDrawSpinner($col, $val, $obj->getSpinnerAttributes($col), $obj->getFieldAttributes($col), $name, $title, $smallWidth, $colScript);
          } else {
            // END - ADD BY TABARY - IF SPINNER AND HIDE => Draw but display:none
            echo '<div dojoType="dijit.form.TextBox" type="hidden"  ';
            echo $name;
            if ($dataType=='decimal' and (substr($col, -4, 4)=='Work')) {
              $val=Work::displayWork($val);
            }
            echo ' value="'.htmlEncode($val).'" ></div>';
          }
        }
      } else if (strpos($obj->getFieldAttributes($col), 'displayHtml')!==false) {
        // Display full HTML ================================================== Simple Display html field
        echo '<div class="displayHtml generalColClass '.$col.'Class" style="'.$specificStyle.'">';
        echo $val;
        echo '</div>';
      } else if ($col=='id') {
        // Draw Id (only visible) ============================================= ID
        // id is only visible
        $ref=$obj->getReferenceUrl();
        echo '<span class="roundedButton" style="padding:1px 5px 5px 5px;font-size:8pt; height: 50px; color:#AAAAAA;'.$specificStyle.'" >';
        echo '  <a  href="'.$ref.'" onClick="copyDirectLinkUrl(\'Detail\');return false;"'.' title="'.i18n("rightClickToCopy").'" style="cursor: pointer;">';
        echo '    <span style="color:grey;vertical-align:middle;padding: 2px 0px 2px 0px !important;'.$specificStyle.'">#</span>';
        echo '    <span dojoType="dijit.form.TextBox" type="text"  ';
        echo $name;
        echo '     class="display pointer" ';
        echo '     readonly tabindex="-1" style="background: transparent; border: 0; cursor: pointer !important;width: '.$smallWidth.'px; padding: 2px 0px 2px 0px !important;'.$specificStyle.'" ';
        echo '     value="'.htmlEncode($val).'" >';
        echo '    </span>';
        echo '  </a>';
        echo '</span>';
        echo '<input readOnly type="text" onClick="this.select();" id="directLinkUrlDivDetail" style="display:none;font-size:9px; color: #000000;position :absolute; top: 6px; left: 150px; border: 0;background: transparent;width:300px;" value="'.$ref.'" />';
        $alertLevelArray=$obj->getAlertLevel(true);
        $alertLevel=$alertLevelArray['level'];
        $colorAlert="background-color:#FFFFFF";
        if ($alertLevel!='NONE') {
          if ($alertLevel=='ALERT') {
            $colorAlert='background-color:#FFAAAA;';
          } else if ($alertLevel=='WARNING') {
            $colorAlert='background-color:#FFFFAA;';
          }
          echo '<span style="width:20px; position: absolute; left: 5px;" id="alertId" >';
          if ($alertLevel=='ALERT') {
            echo '<image style="z-index:3;position:relative" src="../view/css/images/indicatorAlert32.png" />';
          } else {
            echo '<image style="z-index:3;position:relative" src="../view/css/images/indicatorWarning32.png" />';
          }
          echo '</span>';
          echo '<div dojoType="dijit.Tooltip" connectId="alertId" position="below">';
          echo $alertLevelArray['description'];
          echo '</div>';
        }
      } else if ($col=='reference') {
        // Draw reference (only visible) ============================================= ID
        // id is only visible
        echo '<span dojoType="dijit.form.TextBox" type="text"  ';
        echo $name;
        echo ' class="display generalColClass '.$col.'Class" ';
        $refWidth=$largeWidth-$smallWidth-40;
        if ($fieldWidth<$refWidth) $refWidth=$fieldWidth;
        echo ' readonly tabindex="-1" style="'.$specificStyle.';width: '.$refWidth.'px;" ';
        echo ' value="'.htmlEncode($val).'" ></span>';
      } else if ($col=='password') {
        $paramDefaultPassword=Parameter::getGlobalParameter('paramDefaultPassword');
        // Password specificity ============================================= PASSWORD
        if ($canUpdate) {
          echo '<button id="resetPassword" dojoType="dijit.form.Button" showlabel="true"';
          echo ' class="generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" style="'.$specificStyleWithoutCustom.'"';
          echo $attributes;
          $salt=hash('sha256', "projeqtor".date('YmdHis'));
          echo ' title="'.i18n('helpResetPassword').'" >';
          echo '<span>'.i18n('resetPassword').'</span>';
          echo '<script type="dojo/connect" event="onClick" args="evt">';
          echo '  dijit.byId("salt").set("value","'.$salt.'");';
          echo '  dijit.byId("crypto").set("value","sha256");';
          echo '  dojo.byId("password").value="'.hash('sha256', $paramDefaultPassword.$salt).'";';
          echo '  formChanged();';
          echo '  showInfo("'.i18n('passwordReset', array($paramDefaultPassword)).'");';
          echo '</script>';
          echo '</button>';
        }
        // password not visible
        echo '<input type="password"  ';
        echo $name;
        echo ' class="display generalColClass '.$col.'Class" style="width:150px;position:relative; left: 3px;'.$specificStyle.'"';
        echo ' readonly tabindex="-1" ';
        echo ' value="'.htmlEncode($val).'" />';
      } else if ($col=='color' and $dataLength==7) {
        // Draw a color selector ============================================== COLOR
        echo '<table class="generalColClass '.$col.'Class" style="'.$specificStyleWithoutCustom.'"><tr><td class="detail">';
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<input xdojoType="dijit.form.TextBox" class="colorDisplay" type="text" readonly tabindex="-1" ';
        echo $name;
        echo $attributes;
        echo '  value="'.htmlEncode($val).'" ';
        echo '  style="border-radius:10px; height:20px; border: 0;width: '.$smallWidth.'px; ';
        echo ' color: '.$val.'; ';
        if ($val) {
          echo ' background-color: '.$val.';';
        } else {
          echo ' background-color: transparent;';
        }
        echo '" />';
        // echo $colScript;
        // echo '</div>';
        echo '</td><td class="detail">';
        if (!$readOnly) {
          echo '<div id="'.'colorButton" dojoType="dijit.form.DropDownButton"  ';
          // echo ' style="width: 100px; background-color: ' . $val . ';"';
          echo ' showlabel="false" iconClass="colorSelector" style="position:relative;top:-2px;height:19px">';
          echo '  <span>'.i18n('selectColor').'</span>';
          echo '  <div dojoType="dijit.ColorPalette" >';
          echo '    <script type="dojo/method" event="onChange" >';
          echo '      var fld=dojo.byId("color");';
          echo '      fld.style.color=this.value;';
          echo '      fld.style.backgroundColor=this.value;';
          echo '      fld.value=this.value;';
          echo '      formChanged();';
          echo '    </script>';
          echo '  </div>';
          echo '</div>';
        }
        echo '</td><td>';
        if (!$readOnly) {
          echo '<button id="resetColor" dojoType="dijit.form.Button" showlabel="true"';
          echo ' title="'.i18n('helpResetColor').'" >';
          echo '<span>'.i18n('resetColor').'</span>';
          echo '<script type="dojo/connect" event="onClick" args="evt">';
          echo '      var fld=dojo.byId("color");';
          echo '      fld.style.color="transparent";';
          echo '      fld.style.backgroundColor="transparent";';
          echo '      fld.value="";';
          echo '      formChanged();';
          echo '</script>';
          echo '</button>';
        }
        echo '</td></tr></table>';
      } else if ($col=='durationSla') {
        // Draw a color selector ============================================== SLA as a duration
        echo '<div class="generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" style="width: 30px;'.$specificStyleWithoutCustom.'">';
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<div dojoType="dijit.form.TextBox" class="colorDisplay generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" type="text"  ';
        echo $name;
        echo $attributes;
        echo '  value="'.htmlEncode($val).'" ';
        echo '  style="width: 30px;'.$specificStyle.'"';
        echo ' >';
        echo '</div>';
        echo i18n("shortDay")."  ";
        echo '<div dojoType="dijit.form.TextBox" class="colorDisplay" type="text"  ';
        echo $attributes;
        echo '  value="'.htmlEncode($val).'" ';
        echo '  style="width: 30px; "';
        echo ' >';
        echo '</div>';
        echo i18n("shortHour")."  ";
        echo '<div dojoType="dijit.form.TextBox" class="colorDisplay" type="text"  ';
        echo $attributes;
        echo '  value="'.htmlEncode($val).'" ';
        echo '  style="width: 30px; "';
        echo ' >';
        echo '</div>';
        echo i18n("shortMinute")."  ";
        echo "</div>";
      } else if ($dataType=='date') {
        // Draw a date ======================================================== DATE
        if ($col=='creationDate' and ($val=='' or $val==null) and !$obj->id) {
          $val=date('Y-m-d');
        }
        $negative='';
        if (property_exists($obj, 'validatedEndDate')) {
          $negative=($col=="plannedEndDate" and $obj->plannedEndDate and $obj->validatedEndDate and $obj->plannedEndDate>$obj->validatedEndDate)?'background-color: #FFAAAA !important;':'';
        }
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<div dojoType="dijit.form.DateTextBox" ';
        echo $name;
        echo $attributes;
        echo ' invalidMessage="'.i18n('messageInvalidDate').'"';
        echo ' type="text" maxlength="'.$dataLength.'" ';
        if (sessionValueExists('browserLocaleDateFormatJs')) {
          $min='';
          if (substr($col, -7)=="EndDate" and !$readOnly) {
            $start=str_replace("EndDate", "StartDate", $col);
            if (property_exists($obj, $start)&&property_exists($obj, 'refType')&&$obj->refType!="Milestone") {
              $min=$obj->$start;
            } else {
              $start=str_replace("EndDate", "EisDate", $col);
              if (property_exists($obj, $start)) {
                $min=$obj->$start;
              }
            }
            // Babynus - For test purpose
            if ($val and $val<$min) $val=$min;
            if ($min) echo ' dropDownDefaultValue="'.$min.'" ';
          }
          echo ' constraints="{datePattern:\''.getSessionValue('browserLocaleDateFormatJs').'\', min:\''.$min.'\' }" ';
        }
        echo ' style="'.$negative.'width:'.$dateWidth.'px; text-align: center;'.$specificStyle.'" class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" ';
        echo ' value="'.htmlEncode($val).'" ';
        echo ' hasDownArrow="false" ';
        echo ' >';
        echo $colScript;
        echo '</div>';
      } else if ($dataType=='datetime') {
        // Draw a date ======================================================== DATETIME
        if (strlen($val>11)) {
          $valDate=substr($val, 0, 10);
          $valTime=substr($val, 11);
        } else {
          $valDate=$val;
          $valTime='';
        }
        if ($col=='creationDateTime' and ($val=='' or $val==null) and !$obj->id) {
          $valDate=date('Y-m-d');
          $valTime=date("H:i");
        }
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<div dojoType="dijit.form.DateTextBox" ';
        echo $name;
        echo $attributes;
        echo ' invalidMessage="'.i18n('messageInvalidDate').'"';
        echo ' type="text" maxlength="10" ';
        if (sessionValueExists('browserLocaleDateFormatJs')) {
          echo ' constraints="{datePattern:\''.getSessionValue('browserLocaleDateFormatJs').'\'}" ';
        }
        echo ' style="width:'.$dateWidth.'px; text-align: center;'.$specificStyle.'" class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" ';
        echo ' value="'.$valDate.'" ';
        echo ' hasDownArrow="false" ';
        echo ' >';
        echo $colScript;
        echo '</div>';
        $fmtDT=($classObj=="Audit"&&strlen($valTime)>5&&strpos($attributes, 'readonly')!==false)?'text':'time'; // valTime=substr($valTime,0,5);
        echo '<div dojoType="dijit.form.'.(($fmtDT=='time')?'Time':'').'TextBox" ';
        echo $nameBis;
        echo $attributes;
        echo ' invalidMessage="'.i18n('messageInvalidTime').'"';
        echo ' type="text" maxlength="8" ';
        if (sessionValueExists('browserLocaleTimeFormat')) {
          echo ' constraints="{timePattern:\''.getSessionValue('browserLocaleTimeFormat').'\'}" ';
        }
        // echo ' constraints="{datePattern:\'yy-MM-dd\'}" ';
        echo ' style="width:60px; text-align: center;'.$specificStyle.'" class="input '.(($isRequired)?'required':'').'" ';
        echo ' value="'.(($fmtDT=='time')?'T':'').$valTime.'" ';
        echo ' hasDownArrow="false" ';
        echo ' >';
        echo $colScriptBis;
        echo '</div>';
      } else if ($dataType=='time') {
        // Draw a date ======================================================== TIME
        if ($col=='creationTime' and ($val=='' or $val==null) and !$obj->id) {
          $val=date("H:i");
        }
        $fmtDT=($classObj=="Audit"&&strlen($val)>5&&strpos($attributes, 'readonly')!==false)?'text':'time'; // valTime=substr($valTime,0,5);
                                                                                                            // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<div dojoType="dijit.form.'.(($fmtDT=='time')?'Time':'').'TextBox" ';
        echo $name;
        echo $attributes;
        echo ' invalidMessage="'.i18n('messageInvalidTime').'"';
        echo ' type="text" maxlength="'.$dataLength.'" ';
        if (sessionValueExists('browserLocaleTimeFormat')) {
          echo ' constraints="{timePattern:\''.getSessionValue('browserLocaleTimeFormat').'\'}" ';
        }
        // echo ' constraints="{datePattern:\'yy-MM-dd\'}" ';
        echo ' style="width:'.(($fmtDT=='time')?'60':'65').'px; text-align: center;'.$specificStyle.'" class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" ';
        echo ' value="'.(($fmtDT=='time')?'T':'').$val.'" ';
        echo ' hasDownArrow="false" ';
        echo ' >';
        echo $colScript;
        echo '</div>';
      } else if ($dataType=='int' and $dataLength==1) {
        if ($col=='cancelled' or $col=='solved') echo "&nbsp;&nbsp;&nbsp;";
        // Draw a boolean (as a checkbox ====================================== BOOLEAN
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<div dojoType="dijit.form.CheckBox" type="checkbox" ';
        echo $name;
        echo ' class="greyCheck generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class"';
        echo $attributes;
        echo ' style="'.$specificStyle.'" ';
        // echo ' value="' . $col . '" ' ;
        if ($val!='0' and !$val==null) {
          echo 'checked';
        }
        echo ' >';
        echo $colScript;
        if (!strpos('formChanged()', $colScript)) {
          echo '<script type="dojo/connect" event="onChange" args="evt">';
          echo '    formChanged();';
          echo '</script>';
        }
        echo '</div>';
        // BEGIN - REPLACE BY TABARY - USE isForeignKey GENERIC FUNCTION
      } else if (isForeignKey($col, $obj)) {
        // } else if (substr($col, 0, 2) == 'id' and $dataType == 'int' and strlen($col) > 2 and substr($col, 2, 1) == strtoupper(substr($col, 2, 1))) {
        // END - REPLACE BY TABARY - USE isForeignKey GENERIC FUNCTION
        // Draw a reference to another object (as combo box) ================== IDxxxxx => ComboBox (as a FilteringSelect)
        $displayComboButtonCol=$displayComboButton;
        $displayDirectAccessButton=true;
        $canCreateCol=false;
        if ($comboDetail or strpos($attributes, 'readonly')!==false) {
          $displayComboButtonCol=false;
        }
        if (strpos($obj->getFieldAttributes($col), 'nocombo')!==false) {
          $displayComboButtonCol=false;
          $displayDirectAccessButton=false;
        }
        if ($displayComboButtonCol or $displayDirectAccessButton) {
          // BEGIN - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
          $colWithoutAlias=foreignKeyWithoutAlias($col);
          $idMenu='menu'.substr($colWithoutAlias, 2);
          $comboClass=substr($colWithoutAlias, 2);
          // $idMenu='menu' . substr($col, 2);
          // $comboClass=substr($col, 2);
          // END - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
          if ($col=="idResourceSelect" or $col=='idAccountable' or $col=='idResponsible') {
            $idMenu='menuResource';
            $comboClass='Resource';
          } else if (substr($col, -14)=="ProductVersion") {
            $idMenu='menuProductVersion';
            $comboClass='ProductVersion';
          } else if (substr($col, -16)=="ComponentVersion") {
            $idMenu='menuComponentVersion';
            $comboClass='ComponentVersion';
          }
          $menu=SqlElement::getSingleSqlElementFromCriteria('Menu', array('name'=>$idMenu));
          $crit=array();
          $crit['idProfile']=$profile;
          $crit['idMenu']=$menu->id;
          $habil=SqlElement::getSingleSqlElementFromCriteria('Habilitation', $crit);
          if ($habil and $habil->allowAccess) {
            $accessRight=SqlElement::getSingleSqlElementFromCriteria('AccessRight', array(
                'idMenu'=>$menu->id, 
                'idProfile'=>$profile));
            if ($accessRight) {
              $accessProfile=new AccessProfile($accessRight->idAccessProfile);
              if ($accessProfile) {
                $accessScope=new AccessScope($accessProfile->idAccessScopeCreate);
                if ($accessScope and $accessScope->accessCode!='NO') {
                  $canCreateCol=true;
                }
              }
            }
            // ADD BY Marc TABARY - 2017-02-22 - ORGANIZATION PARENT
            // Special case for Organization Parent - Can access to parent only if the user is link
            // directly (idOrganization) to the parent or on one of the parent's of the parent organization
            if ($col=='idOrganization') {
              $orga=new Organization();
              $listOrga=$orga->getUserOrganizationsListAsArray();
              if (!array_key_exists($val, $listOrga)) {
                $displayComboButtonCol=false;
                $displayDirectAccessButton=false;
              }
            }
            // END ADD BY Marc TABARY - 2017-02-22 - ORGANIZATION PARENT
            
            // ADD BY Marc TABARY - 2017-02-22 - RESOURCE VISIBILITY (list teamOrga)
            // Special case for idResource, idLocker, idAuthor, idResponsive
            // Don't see or access to the resource if is not visible for the user connected (respect of HabilitationOther - teamOrga)
            $arrayIdSpecial=array('idResource', 'idLocker', 'idAuthor', 'idResponsible', 'idAccountable');
            if (in_array($col, $arrayIdSpecial)) {
              $idList=getUserVisibleResourcesList(true, "List");
              if ($val and !array_key_exists($val, $idList)) {
                $displayComboButtonCol=false;
                $displayDirectAccessButton=false;
              }
            }
            // END ADD BY Marc TABARY - 2017-02-22 - RESOURCE VISIBILITY (list teamOrga)
          } else {
            $displayComboButtonCol=false;
            $displayDirectAccessButton=false;
          }
        }
        if ($obj->isAttributeSetToField($col, 'canSearchForAll')) {
          $displayComboButtonCol='force';
          // $displayDirectAccessButton=false;
        }
        if ($col=='idProfile' and !$obj->id and !$val and ($classObj=='Resource' or $classObj=='User')) { // set default
          $val=Parameter::getGlobalParameter('defaultProfile');
        }
        if ($col=='idProject') {
          if ($obj->id==null) {
            $projSelected=new Project(getSessionValue('project'));
            if ((sessionValueExists('project') and !$obj->$col) and $projSelected->idle!='1') {
              $val=getSessionValue('project');
            }
            $accessRight=securityGetAccessRight('menu'.$classObj, 'create'); // TODO : study use of this variable...
          } else {
            $accessRight=securityGetAccessRight('menu'.$classObj, 'update'); // TODO : study use of this variable...
          }
          if (securityGetAccessRight('menu'.$classObj, 'read')=='PRO' and $classObj!='Project') {
            $isRequired=true; // TODO : study condition above : why security for 'read'', why not for project, ...
          }
          $controlRightsTable=$user->getAccessControlRights();
          $controlRights=$controlRightsTable['menu'.$classObj];
          if ($classObj=='Project' and $controlRights["create"]!="ALL" and $controlRights["create"]!="PRO") {
            $isRequired=true;
          }
        }
        $critFld=null;
        $critVal=null;
        $valStore='';
        if ($col=='idResource' or $col=="idAccountable" or $col=='idActivity' or $col=='idProduct' or $col=='idComponent' or $col=='idProductOrComponent' or $col=='idProductVersion' or $col=='idComponentVersion' or $col=='idVersion' or $col=='idOriginalVersion' or $col=='idTargetVersion' or $col=='idOriginalProductVersion' or $col=='idTargetProductVersion' or $col=='idOriginalComponentVersion' or $col=='idTargetComponentVersion' or $col=='idTestCase' or $col=='idRequirement' or $col=='idContact' or $col=='idMilestone' or $col=='idTicket' or $col=='idUser' or $col=='id'.$classObj.'Type') {
          if ($col=='idContact' and property_exists($obj, 'idClient') and $obj->idClient) {
            $critFld='idClient';
            $critVal=$obj->idClient;
          } else if ($col=='idContact' and property_exists($obj, 'idProvider') and $obj->idProvider) {
            $critFld='idProvider';
            $critVal=$obj->idProvider;
          } else if (property_exists($obj, 'idProject') and get_class($obj)!='Project' and get_class($obj)!='Affectation') {
            if ($obj->id) {
              $critFld='idProject';
              $critVal=$obj->idProject;
            } else if ($obj->isAttributeSetToField('idProject', 'required') or (sessionValueExists('project') and getSessionValue('project')!='*')) {
              if ($defaultProject) {
                $critFld='idProject';
                $critVal=$defaultProject;
              }
            }
          }
        }
        if ($col=='idComponent' and isset($obj->idProduct)) {
          $critFld='idProduct';
          $critVal=$obj->idProduct;
        }
        // if version and idProduct exists and is set : criteria is product
        if ((isset($obj->idProduct) or isset($obj->idComponent) or isset($obj->idProductOrComponent)) and ($col=='idVersion' or $col=='idProductVersion' or $col=='idComponentVersion' or $col=='idOriginalVersion' or $col=='idTargetVersion' or $col=='idOriginalProductVersion' or $col=='idTargetProductVersion' or $col=='idOriginalComponentVersion' or $col=='idTargetComponentVersion' or $col=='idTestCase' or ($col=='idRequirement' and (isset($obj->idProductOrComponent) or isset($obj->idProduct))))) {
          if (isset($obj->idProduct) and ($col=='idVersion' or $col=='idTargetVersion' or $col=='idProductVersion' or $col=='idOriginalProductVersion' or $col=='idTargetProductVersion' or $col=='idRequirement')) {
            $critFld='idProduct';
            $critVal=$obj->idProduct;
          } else if (isset($obj->idComponent) and ($col=='idComponentVersion' or $col=='idOriginalComponentVersion' or $col=='idTargetComponentVersion')) {
            $critFld='idProduct';
            $critVal=$obj->idComponent;
          } else if (isset($obj->idProductOrComponent)) {
            $critFld='idProduct';
            $critVal=$obj->idProductOrComponent;
          }
        }
        if (substr($col, -16)=='ComponentVersion') {
          $prodVers=str_replace('Component', 'Product', $col);
          if (property_exists($obj, $prodVers) and $obj->$prodVers) {
            $critFld='idProductVersion';
            $critVal=$obj->$prodVers;
          }
          if (property_exists($obj, 'idComponent') and $obj->idComponent) {
            $critFld=array($critFld, 'idComponent');
            $critVal=array($critVal, $obj->idComponent);
          }
        }
        if (get_class($obj)=='IndicatorDefinition') {
          if ($col=='idIndicator') {
            $critFld='idIndicatorable';
            $critVal=$obj->idIndicatorable;
          }
          if ($col=='idType') {
            $critFld='scope';
            $critVal=SqlList::getNameFromId('Indicatorable', $obj->idIndicatorable);
          }
          if ($col=='idWarningDelayUnit' or $col=='idAlertDelayUnit') {
            $critFld='idIndicator';
            $critVal=$obj->idIndicator;
          }
        }
        if (get_class($obj)=='PredefinedNote') {
          if ($col=='idType') {
            $critFld='scope';
            $critVal=SqlList::getNameFromId('Textable', $obj->idTextable, false);
          }
        }
        if (get_class($obj)=='StatusMail' or property_exists($obj,'idMailable')) {
          if ($col=='idType') {
            $critFld='scope';
            $critVal=SqlList::getNameFromId('Mailable', $obj->idMailable, false);
            // BEGIN add Gmartin Ticket #157
          } else if ($col=='idEmailTemplate') {
            $critFld[]='idMailable';
            $critVal[]=$obj->idMailable;
            $critFld[]='idType';
            $critVal[]=$obj->idType;
          }
        }
        if (get_class($obj)=='EmailTemplate') {
          if ($col=='idType') {
            $critFld='scope';
            $critVal=SqlList::getNameFromId('Mailable', $obj->idMailable, false);
          }
        }
        // END add Gmartin
        if (get_class($obj)=='ChecklistDefinition'||get_class($obj)=='JoblistDefinition') { // Can be replaced by a specific table Joblistable if needed
          if ($col=='idType') {
            $critFld='scope';
            $critVal=SqlList::getNameFromId('Checklistable', $obj->idChecklistable, false);
          }
        }
        // ADD by qCazelles - Business features
        if ($col=='idBusinessFeature') {
          $critFld='idProduct';
          $critVal=$obj->idProduct;
        }
        // END ADD qCazelles
        
        if (SqlElement::is_a($obj, 'PlanningElement')) {
          $planningModeName='id'.$obj->refType.'PlanningMode';
          if ($col==$planningModeName and !$obj->id and $objType) {
            if (property_exists($objType, $planningModeName)) {
              $obj->$planningModeName=$objType->$planningModeName;
              $val=$obj->$planningModeName;
            }
          }
        }
        if ($displayComboButtonCol) {
          if ($displayDirectAccessButton) {
            $fieldWidth-=50;
          } else {
            $fieldWidth-=30;
          }
        } else if ($displayDirectAccessButton) {
          $fieldWidth-=30;
        }
        $hasOtherVersion=false;
        $versionType='';
        $otherVersion='';
        if ((substr($col, 7)=='Version' and SqlElement::is_a(substr($col, 2), 'Version')) or ($col=='idOriginalVersion' or $col=='idOriginalProductVersion' or $col=='idOriginalComponentVersion') or ($col=='idTargetVersion' or $col=='idTargetProductVersion' or $col=='idTargetComponentVersion')) {
          $versionType=substr($col, 2);
          $otherVersion='_Other'.$versionType;
          if (isset($obj->$otherVersion) and !$obj->isAttributeSetToField($col, 'hidden') and !$obj->isAttributeSetToField($col, 'readonly') and !$readOnly and !$hide and $canUpdate and !$obj->idle) {
            $hasOtherVersion=true;
            $fieldWidth-=28;
          }
        }
        $hasOtherClient=false;
        $otherClient='';
        if ($col=='idClient') {
          $otherClient='_OtherClient';
          if (isset($obj->$otherClient) and !$obj->isAttributeSetToField($col, 'hidden') and !$obj->isAttributeSetToField($col, 'readonly') and !$readOnly and !$hide and $canUpdate and !$obj->idle) {
            $hasOtherClient=true;
            $fieldWidth-=28;
          }
        }
        $showExtraButton=false;
        if ($col=='idStatus' or $col=='idResource' or $col=='idAccountable' or $col=='idResponsible') {
          if ((($col=='idStatus') or (($col=='idResource' or $col=='idAccountable' or $col=='idResponsible') and $user->isResource and $user->id!=$val and $obj->id and $classObj!='Affectation')) and $classObj!='Document' and $classObj!='StatusMail' and $classObj!="TicketSimple" and $canUpdate) {
            $showExtraButton=true;
            $fieldWidth=round($fieldWidth/2)-5;
          }
        }
        
        // BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
        if ($col=='idStatusNotification' and $classObj!='StatusNotification') {
          $showExtraButton=true;
          $fieldWidth=round($fieldWidth/2)-5;
        }
        // END - ADD BY TABARY - NOTIFICATION SYSTEM
        
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<select dojoType="dijit.form.FilteringSelect" class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" xlabelType="html" ';
        echo '  style="width: '.($fieldWidth).'px;'.$specificStyle.'"';
        echo $name;
        
        // ADD BY Marc TABARY - 2017-02-24 - ORGANIZATION MANAGER
        if (get_class($obj)=='Resource' and $col=='idOrganization') {
          // Implement the rule : A manager of an organization can't be dissocied from it.
          $orga=new Organization($val);
          if ($obj->id==$orga->idResource) {
            if (strpos($attributes, 'disabled')==false) {
              $attributes.=' disabled';
            }
            $displayComboButtonCol=false;
          }
        }
        // END ADD BY Marc TABARY - 2017-02-24 - ORGANIZATION MANAGER
        
        echo $attributes;
        echo $valStore;
        echo autoOpenFilteringSelect();
        echo ' >';
        if ($classObj=='IndividualExpense' and $col=='idResource' and securityGetAccessRight('menuIndividualExpense', 'read', $obj, $user)=='OWN') {
          $next=htmlDrawOptionForReference($col, $val, $obj, $isRequired, 'id', $user->id);
        } else {
          $next=htmlDrawOptionForReference($col, $val, $obj, $isRequired, $critFld, $critVal);
        }
        echo $colScript;
        echo '</select>';
        if ($displayDirectAccessButton) {
          echo '<div id="'.$col.'ButtonGoto" ';
          echo ' title="'.i18n('showDirectAccess').'" style="float:right;margin-right:3px;'.$specificStyleWithoutCustom.'"';
          echo ' class="roundedButton  generalColClass '.$col.'Class">';
          echo '<div class="iconGoto" ';
          $jsFunction="var sel=dijit.byId('$fieldId');"."if (sel && trim(sel.get('value'))) {"." gotoElement('".$comboClass."','$val');"."} else {"." showAlert(i18n('cannotGoto'));"."}";
          echo ' onclick="'.$jsFunction.'"';
          echo '></div>';
          echo '</div>';
        }
        if ($displayComboButtonCol) {
          echo '<div id="'.$col.'ButtonDetail" ';
          echo ' title="'.i18n('showDetail').'" style="float:right;margin-right:3px;'.$specificStyleWithoutCustom.'"';
          echo ' class="roundedButton generalColClass '.$col.'Class">';
          echo '<div class="iconView" ';
          echo ' onclick="showDetail(\''.$col.'\','.(($canCreateCol)?1:0).',\''.$comboClass.'\',false,null,'.(($obj->isAttributeSetToField($col, 'canSearchForAll'))?'true':'false').')"';
          echo '></div>';
          echo '</div>';
        }
        if ($hasOtherVersion) {
          if ($obj->id and $canUpdate) {
            echo '<a class="generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" style="float:right;margin-right:5px;'.$specificStyleWithoutCustom.'" ';
            echo ' onClick="addOtherVersion('."'".$versionType."'".');" ';
            echo ' title="'.i18n('otherVersionAdd').'">';
            echo formatSmallButton('Add');
            echo '</a>';
          }
          if (count($obj->$otherVersion)>0) {
            drawOtherVersionFromObject($obj->$otherVersion, $obj, $versionType);
          }
        }
        if ($hasOtherClient) {
          if ($obj->id and $canUpdate) {
            echo '<a class="generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" style="float:right;margin-right:5px;'.$specificStyleWithoutCustom.'" ';
            echo ' onClick="addOtherClient();" ';
            echo ' title="'.i18n('otherClientAdd').'">';
            echo formatSmallButton('Add');
            echo '</a>';
          }
          if (count($obj->$otherClient)>0) {
            drawOtherClientFromObject($obj->$otherClient, $obj);
          }
        }
        if ($col=='idStatus' and $next and $showExtraButton) {
          echo '<div class="roundedVisibleButton roundedButton generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class"';
          echo ' title="'.i18n("moveStatusTo", array(SqlList::getNameFromId('Status', $next))).'"';
          echo ' style="text-align:left;float:right;margin-right:10px; width:'.($fieldWidth-5).'px;'.$specificStyleWithoutCustom.'"';
          $saveFunction=($comboDetail)?'top.saveDetailItem();':'saveObject()';
          echo ' onClick="dijit.byId(\''.$fieldId.'\').set(\'value\','.$next.');setTimeout(\''.$saveFunction.'\',100);">';
          echo '<img src="css/images/iconMoveTo.png" style="position:relative;left:5px;top:2px;"/>';
          echo '<div style="position:relative;top:-16px;left:25px;width:'.($fieldWidth-30).'px">'.SqlList::getNameFromId('Status', $next).'<div>';
          echo '</div>';
        }
        // BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
        if ($col=='idStatusNotification' and $next and $showExtraButton) {
          echo '<div class="roundedVisibleButton roundedButton generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class"';
          echo ' title="'.i18n("moveStatusTo", array(SqlList::getNameFromId('StatusNotification', $next))).'"';
          echo ' style="text-align:left;float:right;margin-right:10px; width:'.($fieldWidth-5).'px;'.$specificStyleWithoutCustom.'"';
          $saveFunction=($comboDetail)?'top.saveDetailItem();':'saveObject()';
          echo ' onClick="dijit.byId(\''.$fieldId.'\').set(\'value\','.$next.');setTimeout(\''.$saveFunction.'\',100);">';
          echo '<img src="css/images/iconMoveTo.png" style="position:relative;left:5px;top:2px;"/>';
          echo '<div style="position:relative;top:-16px;left:25px;width:'.($fieldWidth-30).'px">'.i18n(SqlList::getNameFromId('StatusNotification', $next)).'<div>';
          echo '</div>';
        }
        // END - ADD BY TABARY - NOTIFICATION SYSTEM
        // BEGIN - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
        $colWithoutAlias=foreignKeyWithoutAlias($col);
        if (($colWithoutAlias=='idResource' or $colWithoutAlias=='idAccountable' or $colWithoutAlias=='idResponsible') and $next and $showExtraButton) {
          // if (($col == 'idResource' or $col == 'idAccountable' or $col == 'idResponsible') and $next and $showExtraButton) {
          // END - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
          // ADD BY Marc TABARY - 2017-03-09 - EXTRA BUTTON (Assign to me) IS VISIBLE EVEN IDLE=1
          if ($obj->idle==1 and $classObj=='Organization') {} else {
            // END ADD BY Marc TABARY - 2017-03-09 - EXTRA BUTTON (Assign to me) IS VISIBLE EVEN IDLE=1
            echo '<div class="roundedVisibleButton roundedButton generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class"';
            echo ' title="'.i18n("assignToMe").'"';
            echo ' style="text-align:left;float:right;margin-right:10px; width:'.($fieldWidth-5).'px;'.$specificStyle.'"';
            $saveFunction=($comboDetail)?'top.saveDetailItem();':'saveObject()';
            echo ' onClick="dijit.byId(\''.$fieldId.'\').set(\'value\','.htmlEncode($user->id).');setTimeout(\''.$saveFunction.'\',100);"';
            echo '>';
            echo '<img src="css/images/iconMoveTo.png" style="position:relative;left:5px;top:2px;"/>';
            echo '<div style="position:relative;top:-16px;left:25px;width:'.($fieldWidth-30).'px">'.i18n('assignToMeShort').'<div>';
            echo '</div>';
          }
        }
      } else if (strpos($obj->getFieldAttributes($col), 'display')!==false) {
        echo '<div ';
        // echo ' class="display generalColClass input'.$col.'Class" style="'.$specificStyle.'width: '.$fieldWidth.'px;"';
        echo ' class="display generalColClass input'.$col.'Class" style="'.$specificStyle.'"';
        if ($col=="wbs") echo ' title="'.htmlEncode($val).'"';
        echo ' >';
        if (strpos($obj->getFieldAttributes($col), 'html')!==false) {
          echo $val;
        } else if ($dataType=='decimal' and substr($col, -4, 4)=='Work') {
          echo Work::displayWorkWithUnit($val);
        } else {
          echo htmlEncode($val);
        }
        if (!$print) {
          // BEGIN - ADD BY TABARY - TOOLTIP
          echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
          // END - ADD BY TABARY - TOOLTIP
          echo '<input type="hidden" '.$name.' value="'.htmlEncode($val).'" />';
        }
        if (strtolower(substr($col, -8, 8))=='progress' or substr($col, -3, 3)=='Pct' or substr($col, -4, 4)=='Rate') {
          echo '&nbsp;%';
        }
        echo '</div>';
        // ADD BY Marc TABARY - 2017-03-02 - DRAW SPINNER
      } else if ($isSpinner and is_integer(intval($val))) {
        // Draw an integer as spinner ================================================ SPINNER
        $title=' title="'.$obj->getTitle($col).'"';
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo htmlDrawSpinner($col, $val, $obj->getSpinnerAttributes($col), $obj->getFieldAttributes($col), $name, $title, $smallWidth, $colScript);
        // END ADD BY Marc TABARY - 2017-03-02 - DRAW SPINNER
      } else if ($dataType=='int' or $dataType=='decimal') {
        // Draw a number field ================================================ NUMBER
        $colScript=($outMode!='pdf')?NumberFormatter52::completeKeyDownEvent($colScript):'';
        $isCost=false;
        $isAmount=false; // Amount will have 2 decimals
        $isWork=false;
        $isDuration=false;
        $isPercent=false;
        if (SqlElement::is_a($obj, 'PlanningElement')) {
          if ($col=='priority' and !$obj->id and $objType) {
            if (property_exists($objType, 'priority')&&$objType->priority) {
              $obj->priority=$objType->priority;
              $val=$obj->priority;
            }
          }
        }
        if ($dataType=='decimal' and (substr($col, -4, 4)=='Cost' or substr($col, -6, 6)=='Amount' or $col=='amount')) {
          $isCost=true;
          $fieldWidth=$smallWidth;
          if (substr($col, -6, 6)=='Amount' or $col=='amount') {
            $isAmount=true;
          }
        }
        if ($dataType=='decimal' and (substr($col, -4, 4)=='Work')) {
          $isWork=true;
          $fieldWidth=$smallWidth;
        }
        if ($dataType=='int' and (substr($col, -8, 8)=='Duration')) {
          $isDuration=true;
          $fieldWidth=$smallWidth;
        }
        
        if (strtolower(substr($col, -8, 8))=='progress' or substr($col, -3, 3)=='Pct' or substr($col, -4, 4)=='Rate') {
          $isPercent=true;
          // ADD BY Marc TABARY - 2017-03-01 - DIM CORRECT Pct
          if (substr($col, -3, 3)=='Pct' or substr($col, -4, 4)=='Rate') {
            $fieldWidth=$smallWidth;
          }
          // END ADD BY Marc TABARY - 2017-03-01 - DIM CORRECT Pct
        }
        if ($isCost) {
          $possibleWidth=intval($widthPct)-80;
          if ($internalTable) {
            $possibleWidth=round($possibleWidth/$internalTableCols, 0)-12;
          }
          $expected=100;
          if ($isAmount) $expected+=20;
          if ($possibleWidth>$expected) {
            $fieldWidth=$expected;
          } else {
            $fieldWidth=$possibleWidth;
          }
        }
        if (($isWork or $isDuration or $isPercent) and $internalTable!=0 and $displayWidth<1600) {
          $fieldWidth-=12;
        }
        $spl=explode(',', $dataLength);
        $dec=0;
        if (count($spl)>1) {
          $dec=intval($spl[1]);
        }
        $ent=intval($spl[0])-$dec;
        $max=substr('99999999999999999999', 0, $ent);
        if ($isCost and $currencyPosition=='before') {
          echo '<span class="generalColClass '.$col.'Class" style="display:inline-block;height:100%;'.$specificStyleWithoutCustom.$labelStyle.'">'.$currency.'</span>';
        }
        // ADD BY Marc TABARY - 2017-03-01 - COLOR PERCENT WITH ATTRIBUTE 'alertOverXXXwarningOverXXXokUnderXXX'
        if ($isPercent and (strpos($obj->getFieldAttributes($col), 'alertOver')!==false or strpos($obj->getFieldAttributes($col), 'warningOver')!==false or strpos($obj->getFieldAttributes($col), 'okUnder')!==false)) {
          // Note : reuse $negative (it's pratical)
          $negative='';
          $colAttributes=$obj->getFieldAttributes($col);
          // alertOver
          $posAWO=strpos($colAttributes, 'alertOver');
          if ($posAWO and $val!==null) {
            $overValue=substr($colAttributes, $posAWO+9, 3);
            if (is_numeric($overValue) and $val>intval($overValue)) {
              // Red
              $negative='background-color: #FFAAAA !important;';
            } else {
              // warningOver
              $posAWO=strpos($colAttributes, 'warningOver');
              if ($posAWO) {
                $overValue=substr($colAttributes, $posAWO+11, 3);
                if (is_numeric($overValue) and $val>intval($overValue)) {
                  // Orange
                  $negative='background-color: #FFBE00 !important;';
                } else {
                  // okUnder
                  $posAWO=strpos($colAttributes, 'okUnder');
                  if ($posAWO) {
                    $overValue=substr($colAttributes, $posAWO+7, 3);
                    if (is_numeric($overValue) and $val<intval($overValue)) {
                      // Green
                      $negative='background-color: #B5DE8E !important;';
                    }
                  }
                }
              }
            }
          }
        } else {
          $negative=(($isCost or $isWork) and $val<0)?'color: #AA0000 !important;':'';
        }
        // END ADD BY Marc TABARY - 2017-03-01 - COLOR PERCENT WITH ATTRIBUTE 'alertOverXXXwarningOverXXXokUnderXXX'
        // COMMENT BY Marc TABARY - 2017-03-01 - COLOR PERCENT WITH ATTRIBUTE 'alertOverXXXwarningOverXXXokUnderXXX'
        // $negative=(($isCost or $isWork) and $val<0)?'background-color: #FFAAAA !important;':'';
        // END COMMENT BY Marc TABARY - 2017-03-01 - COLOR PERCENT WITH ATTRIBUTE 'alertOverXXXwarningOverXXXokUnderXXX'
        if ($col=='workElementEstimatedWork' and property_exists($obj, 'assignedWork')) {
          $negative=($obj->workElementEstimatedWork>$obj->assignedWork)?'background-color: #FFAAAA !important;':'';
        }
        if ($col=='workElementLeftWork' and property_exists($obj, 'leftWork')) {
          $negative=($obj->workElementLeftWork>$obj->leftWork)?'background-color: #FFAAAA !important;':'';
        }
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        // gautier #work
        echo '<div dojoType="dijit.form.NumberTextBox" ';
        echo $name;
        echo $attributes;
        // echo ' style="text-align:right; width: ' . $fieldWidth . 'px;' . $specificStyle . '" ';
        echo ' style="'.$negative.'width: '.$fieldWidth.'px;'.$specificStyle.'" ';
        // ADD BY Marc TABARY - 2017-03-06 - PATTERN FOR YEAR
        if (strpos(strtolower($col), 'year')!==false) {
          echo ' constraints="{min:2000,max:2100,pattern:\'###0\'}" ';
        } else if ($max) {
          //gautier min amount
          $arrayPossibleNegativeAmounts=array('update3Amount','update3FullAmount','update4Amount','update4FullAmount',
                                              'addUntaxedAmount','addFullAmount','availableAmount','availableFullAmount',
                                              'leftAmount','leftFullAmount','reserveAmount','totalLeftCost', 'totalPlannedCost',
          		                                'marginCost'
        	          );
          if(($isAmount or $isCost) and ! in_array($col,$arrayPossibleNegativeAmounts) ){
              echo ' constraints="{min:0,max:'.$max.(($isAmount)?',places:2':'').'}" ';
          } else {
            echo ' constraints="{min:-'.$max.',max:'.$max.(($isAmount)?',places:2':'').'}" ';
          }
        } else if ($isAmount) {
          echo ' constraints="{places:2}" ';
        }
        echo ' class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" ';
        // echo ' layoutAlign ="right" ';
        if ($isWork) {
          if ($classObj=='WorkElement') {
            $dispVal=Work::displayImputation($val);
          } else {
            $dispVal=Work::displayWork($val);
          }
        } else if ($dataLength>4000) {
          $dispVal=htmlEncode($val, 'formatted');
        } else {
          $dispVal=htmlEncode($val);
        }
        echo ' value="'.$dispVal.'" ';
        // echo ' value="' . htmlEncode($val) . '" ';
        echo ' >';
        echo $colScript;
        echo '</div>';
        if ($isCost and $currencyPosition=='after') {
          echo '<span class="generalColClass '.$col.'Class" style="'.$specificStyleWithoutCustom.'">'.$currency.'</span>';
        }
        if ($isWork or $isDuration or $isPercent) {
          echo '<span class="generalColClass '.$col.'Class" style="'.$specificStyleWithoutCustom.'">';
        }
        if ($isWork) {
          if ($classObj=='WorkElement') {
            echo Work::displayShortImputationUnit();
          } else {
            echo Work::displayShortWorkUnit();
          }
        }
        if ($isDuration) {
          echo i18n("shortDay");
        }
        if ($isPercent) {
          echo '%';
        }
        if ($isWork or $isDuration or $isPercent) {
          echo '</span>';
        }
      } else if ($dataLength>200 and ($dataLength<=4000 or getEditorType()=='text')) {
        // Draw a long text (as a textarea) =================================== TEXTAREA
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<textarea dojoType="dijit.form.Textarea" ';
        echo ' onKeyPress="if (dojo.isFF || isEditingKey(event)) {formChanged();}" '; // hard coding default event
        echo $name;
        echo $attributes;
        if (strpos($attributes, 'readonly')>0) {
          $specificStyle.=' color:#606060 !important; background:none; background-color: #F0F0F0; ';
        }
        echo ' rows="2" style="max-height:150px;width: '.$largeWidth.'px;'.$specificStyle.'" ';
        echo ' maxlength="'.$dataLength.'" ';
        echo ' class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" >';
        /*
         * if (isTextFieldHtmlFormatted($val)) {
         * $text=new Html2Text($val);
         * $val=$text->getText();
         * echo htmlEncode($val);
         * } else {
         * echo str_replace(array("\n",'<br>','<br/>','<br />'),array("","\n","\n","\n"),$val);
         * }
         */
        if ($dataLength>4000) echo formatAnyTextToPlainText($val);
        else echo $val;
        echo '</textarea>';
      } else if ($dataLength>4000) {
        // Draw a long text (as a textarea) =================================== TEXTAREA
        // No real need to hide and apply class : long fields will be hidden while hiding row
        // class="generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" style="'.$specificStyle.'"
        if (getEditorType()=="CK"||(getEditorType()=="CKInline")) {
          // if (isIE() and ! $val) $val='<div></div>';
          echo '<div style="text-align:left;font-weight:normal; width:300px;" class="tabLabel">'.htmlEncode($obj->getColCaption($col), 'stipAllTags').'</div>';
          $ckEditorNumber++;
          // gautier
          $ckeDivheight=Parameter::getUserParameter('ckeditorHeight'.$classObj.$col.$extName);
          $ckeDivheight=($ckeDivheight)?$ckeDivheight.'':'180';
          echo '<input type="hidden" id="ckeditorObj'.$ckEditorNumber.'" value="'.$classObj.$col.$extName.'" />';
          
          // BEGIN - ADD BY TABARY - TOOLTIP
          echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
          // END - ADD BY TABARY - TOOLTIP
          echo '<textarea style="height:300px"'; // Important to set big height to retreive correct scroll position after save
          echo ' name="'.$col.$extName.'" ';
          echo ' id="'.$col.$extName.'" ';
          echo ' class="input '.(($isRequired)?'required':'').'" ';
          // echo $name.' '.$attributes;
          echo ' maxlength="'.$dataLength.'"';
          echo '>';
          if (!isTextFieldHtmlFormatted($val)) {
            echo formatPlainTextForHtmlEditing($val);
          } else {
            echo htmlspecialchars($val);
          }
          echo '</textarea>';
          // echo str_replace( "\n", '<br/>', $val );
          echo '<input type="hidden" id="ckeditor'.$ckEditorNumber.'" value="'.$col.$extName.'" />';
          if ($readOnly) {
            echo '<input type="hidden" id="ckeditor'.$ckEditorNumber.'ReadOnly" value="true" />';
          }
          echo '<input type="hidden" id="ckeditorType" value="'.getEditorType().'" />';
          echo '<input type="hidden" id="ckeditorHeight'.$ckEditorNumber.'" value="'.$ckeDivheight.'" />';
        } else {
          $val=str_replace("\n", "", $val);
          
          // BEGIN - ADD BY TABARY - TOOLTIP
          echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
          // END - ADD BY TABARY - TOOLTIP
          
          echo '<textarea style="display:none; visibility:hidden;" ';
          echo ' maxlength="'.$dataLength.'" ';
          echo $name;
          echo $attributes;
          echo '>';
          if (!isTextFieldHtmlFormatted($val)) {
            echo formatPlainTextForHtmlEditing($val, 'single');
          } else {
            echo ($val);
          }
          echo '</textarea>';
          if (isIE() and !$val) $val='<div></div>';
          echo '<div style="text-align:left;font-weight:normal; width:300px;" class="tabLabel">'.htmlEncode($obj->getColCaption($col), 'stipAllTags').'</div>';
          if (getEditorType()=="Dojo") {
            echo '<div data-dojo-type="dijit.Editor"'; // TEST
            echo ' id="'.$fieldId.'Editor" ';
            echo ' title="'.i18n('clickToEditRichText').'"';
            if ($readOnly) echo ' disabled=true';
            echo ' data-dojo-props="height:\'200px\'';
            if ($readOnly) echo ', disabled:true';
            echo ',onChange:function(){dojo.byId(\''.$fieldId.'\').value=arguments[0];formChanged();}';
            echo ",plugins:['bold','italic','underline','removeFormat'";
            echo ",'|', 'indent', 'outdent', 'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'";
            echo ",'|','insertOrderedList','insertUnorderedList','|']";
            echo ',onKeyDown:function(event){onKeyDownFunction(event,\''.$fieldId.'\',this);}'; // hard coding default event
                                                                                                // echo ',onBlur:function(event){top.editorBlur(\'' . $fieldId . '\',this)}'; // hard coding default event
            echo ",extraPlugins:['dijit._editor.plugins.AlwaysShowToolbar','foreColor','hiliteColor'";
            // Full screen mode disabled : sets many issues on some keys : tab, esc or ctrl+S, ...
            echo ",'|','print'";
            echo ",'fullScreen'";
            // Font Choice ...
            if (0) echo ",'fontName','fontSize'";
            // Print option
            
            // echo ",{name: 'LocalImage', uploadable: true, uploadUrl: '../../form/tests/UploadFile.php', baseImageUrl: '../../form/tests/', fileMask: '*.jpg;*.jpeg;*.gif;*.png;*.bmp'}";
            echo "]";
            echo '" ';
            echo $attributes;
            if (strpos($attributes, 'readonly')>0) {
              $specificStyle.=' color:#606060 !important; background:none; background-color: #F0F0F0; ';
            }
            echo ' rows="2" style="min-height:16px;width: '.($largeWidth+145).'px;'.$specificStyle.'" ';
            echo ' maxlength="'.$dataLength.'" ';
            echo ' class="input '.(($isRequired)?'required':'').'" ';
            // echo ' style="background: none; background-color: #AAAAFF" ';
            echo '>';
          } else { // getEditorType()=="DojoInline"
            echo '<div data-dojo-type="dijit.InlineEditBox"'; // TEST
                                                              // echo '<div data-dojo-type="dijit.Editor"'; // TEST
            echo ' id="'.$fieldId.'Editor" ';
            echo ' height="50px" title="'.i18n('clickToEditRichText').'"';
            echo ' data-dojo-props="editor:\'dijit/Editor\',renderAsHtml:true';
            if ($readOnly) echo ', disabled:true';
            echo ',onChange:function(){dojo.byId(\''.$fieldId.'\').value=arguments[0];formChanged();}';
            echo ",editorParams:{height:'200px',plugins:['bold','italic','underline','removeFormat'";
            echo ",'|', 'indent', 'outdent', 'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'";
            echo ",'|','insertOrderedList','insertUnorderedList','|']";
            echo ',onKeyDown:function(event){onKeyDownFunction(event,\''.$fieldId.'\',this);}'; // hard coding default event
            echo ',onBlur:function(event){editorBlur(\''.$fieldId.'\',this)}'; // hard coding default event
            echo ",extraPlugins:['dijit._editor.plugins.AlwaysShowToolbar','foreColor','hiliteColor'";
            echo ",'|','print'";
            echo ",'fullScreen'";
            // Font Choice ...
            if (0) echo ",'fontName','fontSize'";
            // echo ",{name: 'LocalImage', uploadable: true, uploadUrl: '../../form/tests/UploadFile.php', baseImageUrl: '../../form/tests/', fileMask: '*.jpg;*.jpeg;*.gif;*.png;*.bmp'}";
            echo "]}";
            echo '" ';
            echo $attributes;
            if (strpos($attributes, 'readonly')>0) {
              $specificStyle.=' color:#606060 !important; background:none; background-color: #F0F0F0; ';
            }
            echo ' rows="2" style="padding:3px 0px 3px 3px;margin-right:2px;max-height:150px;min-height:16px;overflow:auto;width: '.($largeWidth+145).'px;'.$specificStyle.'" ';
            echo ' maxlength="'.$dataLength.'" ';
            echo ' class="input '.(($isRequired)?'required':'').'" ';
            echo ' style="background: none; background-color: #AAAAFF" ';
            echo '>';
          }
          // echo ' <script type="dojo/connect" event="onKeyPress" args="evt">';
          // echo ' alert("OK");';
          // echo ' </script>';
          if (!isTextFieldHtmlFormatted($val)) {
            echo formatPlainTextForHtmlEditing($val, 'single');
          } else {
            echo ($val);
          }
          // echo $val;
          echo '</div>';
        }
      } else if ($col=='icon') {
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<div dojoType="dijit.form.Select" class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" ';
        echo '  style="width: '.($fieldWidth).'px;'.$specificStyle.'"';
        echo $name;
        echo $attributes;
        echo ' >';
        // htmlDrawOptionForReference($col, $val, $obj, $isRequired,$critFld, $critVal);
        echo '<span value=""> </span>';
        if ($handle=opendir(getcwd().'/icons')) {
          while (false!==($entry=readdir($handle))) {
            if ($entry!="."&&$entry!="..") {
              $ext=strtolower(pathinfo($entry, PATHINFO_EXTENSION));
              if ($ext=="png" or $ext=="gif" or $ext=="jpg" or $ext=="jpeg") {
                echo '<span value="'.$entry.'" '.(($entry==$val)?'selected="selected"':'').'><img src="../view/icons/'.$entry.'" /></span>';
              }
            }
          }
          closedir($handle);
        }
        echo $colScript;
        echo '</div>';
      } else {
        // Draw defaut data (text medium size) ================================ TEXT (default)
        if ($obj->isFieldTranslatable($col)) {
          $fieldWidth=$fieldWidth/2;
        }
        // BEGIN - ADD BY TABARY - TOOLTIP
        echo htmlDisplayTooltip($toolTip, $col, $print, $outMode);
        // END - ADD BY TABARY - TOOLTIP
        echo '<div type="text" dojoType="dijit.form.ValidationTextBox" ';
        echo $name;
        echo $attributes;
        echo '  style="width: '.$fieldWidth.'px;'.$specificStyle.';" ';
        echo ' trim="true" maxlength="'.$dataLength.'" class="input '.(($isRequired)?'required':'').' generalColClass '.$notReadonlyClass.$notRequiredClass.$col.'Class" ';
        echo ' value="'.htmlEncode($val).'" ';
        if ($obj->isFieldTranslatable($col)) {
          echo ' title="'.i18n("msgTranslatable").'" ';
        }
        echo ' >';
        echo $colScript;
        echo '</div>';
        if ($obj->isFieldTranslatable($col)) {
          echo '<div dojoType="dijit.form.TextBox" type="text"  ';
          echo ' class="display" ';
          echo ' readonly tabindex="-1" style="width: '.$fieldWidth.'px;" ';
          echo ' title="'.i18n("msgTranslation").'" ';
          echo ' value="'.htmlEncode(i18n($val)).'" ></div>';
        }
      }
      if ($internalTable>0) {
        $internalTable--;
        if ($internalTable==0) {
          echo '</td></tr></table><table  style="width: 100%;">';
        }
      } else {
        if ($internalTable==0 and !$hide and !$nobr) {
          echo '</td></tr>'.$cr;
        }
      }
    }
  }
  if (!$included) {
    if ($currentCol==0) {
      if ($section and !$print) {
        echo '</div>';
      }
      echo '</table>';
    } else {
      echo '</table>';
      if ($section and !$print) {
        echo '</div>';
      }
      // echo '</td></tr></table>';
    }
  }
  if (!$included) endBuffering($section, $included);
  if (!$included) finalizeBuffering();
  if ($outMode=='pdf') {
    $cpt=0;
    foreach ($obj as $col=>$val) {
      if (substr($col, 0, 1)=='_' and substr($col, -5)=='_full') {
        $cpt++;
        $section=substr($col, 1, strlen($col)-6);
        // echo '</page><page>';
        if ($cpt==1) echo '<page><br/>';
        echo '<table style="width:'.$printWidth.'px;"><tr><td class="section">'.$obj->getColCaption($section).'</td></tr></table>';
        echo htmlEncode($val, 'print');
        echo '<br/><br/>';
      }
    }
    if ($cpt>0) echo '</page>';
  }
}

function startTitlePane($classObj, $section, $collapsedList, $widthPct, $print, $outMode, $prevSection, $nbCol, $nbBadge=null, $included=null, $obj=null) {
  // scriptLog("startTitlePane(classObbj=$classObj, section=$section, collapsedList=array, widthPct=$widthPct, print=$print, outMode=$outMode, prevSection=$prevSection, nbCol=$nbCol, nbBadge=$nbBadge)");
  global $currentColumn, $reorg, $leftPane, $rightPane, $extraPane, $bottomPane, $beforeAllPanes;
  if (!$currentColumn) $currentColumn=0;
  if ($prevSection) {
    echo '</table>';
    if (!$print) {
      echo '</div>';
    } else {
      echo '<br/>';
    }
  }
  endBuffering($prevSection, $included);
  $sectionName=$section;
  if(strstr($sectionName,'Link_')){
    $split=explode('_', $sectionName);
    $sectionName=$split[0].$split[1];
  }
  if (strpos($sectionName, '_')!=0) {
    $split=explode('_', $sectionName);
    $sectionName=$split[0];
  }
  if (!$obj) $obj=new $classObj();
  if ($section=='Note' or $section=='Attachment') {
    $style=$obj->getDisplayStyling('_'.$section);
  } else {
    $style=$obj->getDisplayStyling('_sec_'.$section);
  }
  $labelStyle=$style["caption"];
  $extraHiddenFields=$obj->getExtraHiddenFields();
  if (!$print) {
    $float='left';
    $clear='none';
    $lc=strtolower($section);
    $titlePane=$classObj."_".$section;
    startBuffering($included);
    $display='inline-block';
    if ($obj->isAttributeSetToField('_sec_'.$section, 'hidden') or in_array('_sec_'.$section, $extraHiddenFields)) {
      $display='none';
    }
    $attrs=splitCssAttributes($labelStyle);
    $fontSize=(isset($attrs['font-size']))?intval($attrs['font-size']):'';
    // gautier #resourceTeam
    echo '<div dojoType="dijit.TitlePane" title="'.i18n('section'.ucfirst($sectionName)).(($nbBadge!==null)?'<div id=\''.$section.'Badge\' class=\'sectionBadge\'>'.$nbBadge.'</div>':'').'"';
    echo ' open="'.(array_key_exists($titlePane, $collapsedList)?'false':'true').'" ';
    echo ' id="'.$titlePane.'" ';
    echo ' class="titlePaneFromDetail generalColClass _sec_'.$section.'Class" ';
    echo ' titleStyle="'.$labelStyle.'"';
    echo ' style="display:'.$display.';position:relative;width:'.$widthPct.';float: '.$float.';clear:'.$clear.';margin: 0 0 4px 4px; padding: 0;top:0px;"';
    echo ' onHide="saveCollapsed(\''.$titlePane.'\');"';
    echo ' onShow=";saveExpanded(\''.$titlePane.'\');">';
    $titleHeight=($fontSize)?$fontSize*1.6:'';
    echo ' <script type="dojo/method" event="titlePaneHandler" > setAttributeOnTitlepane(\''.$titlePane.'\',\''.$labelStyle.'\',\''.$titleHeight.'\');</script>';
    echo '<table class="detail"  style="width: 100%;" >';
  } else {
    $hide=false;
    $display='';
    if ($obj->isAttributeSetToField('_sec_'.$section, 'hidden') or in_array('_sec_'.$section, $extraHiddenFields)) {
      $display='display:none;';
      $hide=true;
    }
    if (!$hide) {
      echo '<table class="detail" style="width:'.$widthPct.';'.$display.'" >';
      echo '<tr><td class="section">'.i18n('section'.ucfirst($sectionName)).'</td></tr>';
      echo '<tr class="detail" style="height:2px;font-size:2px;">';
      echo '<td class="detail" >&nbsp;</td>';
      echo '</tr>';
      echo '</table>';
    }
    echo '<table class="detail" style="width:'.$widthPct.';'.$display.'" >';
  }
}

function drawDocumentVersionFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->locked) {
    $canUpdate=false;
  }
  // if ($obj->idle==1) {$canUpdate=false;}
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  $typeEvo="EVO";
  $type=new VersioningType($obj->idVersioningType);
  $typeEvo=$type->code;
  $num="";
  $vers=new DocumentVersion($obj->idDocumentVersion);
  if ($typeEvo=='SEQ') {
    $num=intVal($vers->name)+1;
  }
  echo '<tr>';
  if (!$print) {
    $statusTable=SqlList::getList('Status', 'name', null);
    reset($statusTable);
    echo '<td class="assignHeader" style="width:10%">';
    if ($obj->id!=null and !$print and $canUpdate and !$obj->idle) {
      echo '<a onClick="addDocumentVersion('."'".key($statusTable)."'".",'".$typeEvo."'".",'".$num."'".",'".htmlEncode($vers->name)."'".",'".htmlEncode($vers->name)."'".');" ';
      echo ' title="'.i18n('addDocumentVersion').'" > ';
      echo formatSmallButton('Add');
      echo '</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" style="width:15%" >'.i18n('colIdVersion').'</td>';
  echo '<td class="assignHeader" style="width:15%" >'.i18n('colDate').'</td>';
  echo '<td class="assignHeader" style="width:15%">'.i18n('colIdStatus').'</td>';
  echo '<td class="assignHeader" style="width:'.(($print)?'55':'45').'%">'.i18n('colFile').'</td>';
  echo '</tr>';
  $preserveFileName=Parameter::getGlobalParameter('preserveUploadedFileName');
  if (!$preserveFileName) {
    $preserveFileName="NO";
  }
  rsort($list);
  foreach ($list as $version) {
    echo '<tr>';
    if (!$print) {
      echo '<td class="assignData" style="text-align:center; white-space: nowrap;vertical-align:top;">';
      if (!$print) {
        echo '<a href="../tool/download.php?class=DocumentVersion&id='.htmlEncode($version->id).'"';
        echo ' target="printFrame" title="'.i18n('helpDownload')."\n".(($preserveFileName=='YES')?$version->fileName:$version->fullName).'">'.formatSmallButton('Download').'</a>';
      }
      if ($canUpdate and !$print and (!$obj->idle or $obj->idDocumentVersion==$version->id)) {
        echo '  <a onClick="editDocumentVersion('."'".htmlEncode($version->id)."'".",'".htmlEncode($version->version)."'".",'".htmlEncode($version->revision)."'".",'".htmlEncode($version->draft)."'".",'".htmlEncode($version->versionDate)."'".",'".htmlEncode($version->idStatus)."'".",'".$version->isRef."'".",'".$typeEvo."'".",'".htmlEncode($version->name)."'".",'".htmlEncode($version->name)."'".",'".htmlEncode($version->name)."'".');" '.'title="'.i18n('editDocumentVersion').'" >'.formatSmallButton('Edit').'</a> ';
      }
      if ($canUpdate and !$print and !$obj->idle) {
        echo '  <a onClick="removeDocumentVersion('."'".htmlEncode($version->id)."'".', \''.htmlEncode($version->name).'\');" '.'title="'.i18n('removeDocumentVersion').'" >'.formatSmallButton('Remove').'</a> ';
      }
      if (count($obj->_Approver)>=1) {
        echo '  <a onClick="displayListOfApprover('."'".htmlEncode($version->id)."'".');" '.'title="'.i18n('dialogApproverByVersion').'" >'.formatSmallButton('ListApprover').'</a> ';
      }
      echo '<input type="hidden" id="documentVersion_'.htmlEncode($version->id).'" name="documentVersion_'.htmlEncode($version->id).'" value="'.htmlEncode($version->description).'"/>';
      echo '</td>';
    }
    echo '<td class="assignData">'.(($version->isRef)?'<b>':'').htmlEncode($version->name).(($version->isRef)?'</b>':'');
    if ($version->approved) {
      echo '&nbsp;&nbsp;<img src="../view/img/check.png" height="12px" title="'.i18n('approved').'"/>';
    }
    echo '</td>';
    echo '<td class="assignData">'.htmlFormatDate($version->versionDate).'</td>';
    $objStatus=new Status($version->idStatus);
    echo '<td class="assignData" style="width:15%">'.colorNameFormatter($objStatus->name."#split#".$objStatus->color).'</td>';
    echo '<td class="assignData" title="'.htmlencode($version->description).'">';
    echo '<table style="width:100%"><tr><td style="width:20px">';
    if ($version->isThumbable()) {
      $ext=pathinfo($version->fileName, PATHINFO_EXTENSION);
      if (file_exists("../view/img/mime/$ext.png")) {
        $img="../view/img/mime/$ext.png";
      } else {
        $img="../view/img/mime/unknown.png";
      }
      echo '<img src="'.$img.'" '.' title="'.htmlEncode($version->fileName).'" style="float:left;cursor:pointer"'.' onClick="showImage(\'DocumentVersion\',\''.htmlEncode($version->id).'\',\''.htmlEncode($version->fileName, 'protectQuotes').'\');" />';
    } else {
      echo htmlGetMimeType($version->mimeType, $version->fileName, $version->id, 'DocumentVersion');
    }
    echo '</td><td>';
    echo htmlEncode($version->fileName, 'print');
    if ($version->description and !$print) {
      echo formatCommentThumb($version->description);
    }
    echo '</td></tr></table>';
    echo '</td></tr>';
  }
  echo '</table></td></tr>';
}

function drawOrigin($list, $refType, $refId, $obj, $col, $print) {
  echo '<tr class="detail"><td class="label" xstyle="width:10%;">';
  echo '<label for="'.$col.'" >'.htmlEncode($obj->getColCaption($col), 'stipAllTags').'&nbsp;:&nbsp;</label>';
  echo '</td>';
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if ($print) {
    echo '<td style="width: 120px">';
  } else {
    echo '<td>';
  }
  if ($refType and $refId) {
    echo '<table width="100%"><tr height="20px"><td xclass="noteData" width="1%" xvalign="top">';
    if (!$print and $canUpdate) {
      echo '<a onClick="removeOrigin(\''.$obj->$col->id.'\',\''.$refType.'\',\''.$refId.'\');" title="'.i18n('removeOrigin').'" > '.formatSmallButton('Remove').'</a>';
    }
    echo '</td><td width="30%" xclass="noteData" xvalign="top" style="xwhite-space:nowrap;padding:0px 5px;max-width:200px">';
    echo i18n($refType).'&nbsp;#'.$refId.'';
    
    foreach ($list as $origin) {
      // $origObj=null;
      $origObjClass=null;
      $origObjId=null;
      if ($list->originType==get_class($obj) and $list->originId==$obj->id) {
        // $origObj=new $list->refType($list->refId);
        $origObjClass=$list->refType;
        $origObjId=$list->refId;
      } else {
        // $origObj=new $list->originType($list->originId);
        $origObjClass=$list->originType;
        $origObjId=$list->originId;
      }
      $gotoE=' onClick="gotoElement('."'".$origObjClass."','".htmlEncode($origObjId)."'".');" style="cursor: pointer;" ';
      echo '</td><td xclass="noteData" '.$gotoE.' style="height: 15px">';
    }
    $orig=new $refType($refId, true);
    echo htmlEncode($orig->name);
    echo '</td></tr></table>';
  } else {
    echo '<table><tr height="20px"><td>';
    if ($obj->id and !$print and $canUpdate) {
      echo '<a onClick="addOrigin();" title="'.i18n('addOrigin').'" class="roundedButtonSmall"> '.formatSmallButton('Add').'</a>';
    }
    echo '</td></tr></table>';
  }
}

function drawHistoryFromObjects($refresh=false) {
  global $cr, $print, $treatedObjects, $comboDetail;
  SqlElement::$_cachedQuery['Note']=array();
  SqlElement::$_cachedQuery['Attachment']=array();
  if ($comboDetail) {
    return;
  }
  $inList="( ('x',0)"; // initialize with non existing element, to avoid error if 1 only object involved
  foreach ($treatedObjects as $obj) {
    // $inList.=($inList=='')?'(':', ';
    if ($obj->id) {
      $inList.=", ('".get_class($obj)."', ".Sql::fmtId($obj->id).")";
    }
  }
  $showWorkHistory=false;
  $paramDisplayHistory=Parameter::getUserParameter('displayHistory');
  if (($paramDisplayHistory=='REQ' and getSessionValue('showWorkHistory')) or $paramDisplayHistory=='YESW') {
    $showWorkHistory=true;
  }
  $inList.=')';
  $where=' (refType, refId) in '.$inList;
  $order=' operationDate desc, id asc';
  $hist=new History();
  $historyList=$hist->getSqlElementsFromCriteria(null, false, $where, $order);
  echo '<table style="width:100%;">';
  echo '<tr>';
  echo '<td class="historyHeader" style="width:10%">'.i18n('colOperation').'</td>';
  echo '<td class="historyHeader" style="width:14%">'.i18n('colColumn').'</td>';
  echo '<td class="historyHeader" style="width:23%">'.i18n('colValueBefore').'</td>';
  echo '<td class="historyHeader" style="width:23%">'.i18n('colValueAfter').'</td>';
  echo '<td class="historyHeader" style="width:15%">'.i18n('colDate').'</td>';
  echo '<td class="historyHeader" style="width:15%">'.i18n('colUser').'</td>';
  echo '</tr>';
  $stockDate=null;
  $stockUser=null;
  $stockOper=null;
  foreach ($historyList as $hist) {
    if (substr($hist->colName, 0, 24)=='subDirectory|Attachment|' or substr($hist->colName, 0, 18)=='idTeam|Attachment|' or substr($hist->colName, 0, 25)=='subDirectory|Attachement|' or substr($hist->colName, 0, 19)=='idTeam|Attachement|') {
      continue;
    }
    $colName=($hist->colName==null)?'':$hist->colName;
    $split=explode('|', $colName);
    if (count($split)==3) {
      $colName=$split[0];
      $refType=$split[1];
      $refId=$split[2];
      $refObject='';
    } else if (count($split)==4) {
      $refObject=$split[0];
      $colName=$split[1];
      $refType=$split[2];
      $refId=$split[3];
    } else {
      $refType='';
      $refId='';
      $refObject='';
    }
    if ($refType=='Attachement') {
      $refType='Attachment'; // New in V5 : change Class name, must preserve display for history
    }
    $curObj=null;
    $dataType="";
    $dataLength=0;
    $hide=false;
    $oper=i18n('operation'.ucfirst($hist->operation));
    $user=$hist->idUser;
    $user=SqlList::getNameFromId('User', $user);
    $date=htmlFormatDateTime($hist->operationDate);
    $class="NewOperation";
    if ($stockDate==$hist->operationDate and $stockUser==$hist->idUser and $stockOper==$hist->operation) {
      $oper="";
      $user="";
      $date="";
      $class="ContinueOperation";
    }
    if ($colName!='' or $refType!="") {
      if ($refType) {
        if ($refType=="TestCase") {
          $curObj=new TestCaseRun();
        } else {
          $curObj=new $refType();
        }
      } else {
        $curObj=new $hist->refType();
      }
      if ($curObj) {
        if ($refType) {
          $colCaption=i18n($refType).' #'.$refId.' '.$curObj->getColCaption($colName);
          if ($refObject) {
            $colCaption=i18n($refObject).' - '.$colCaption;
          }
        } else {
          $colCaption=$curObj->getColCaption($colName);
        }
        $dataType=$curObj->getDataType($colName);
        $dataLength=$curObj->getDataLength($colName);
        if (strpos($curObj->getFieldAttributes($colName), 'hidden')!==false) {
          $hide=true;
        }
      }
    } else {
      $colCaption='';
    }
    if (substr($hist->refType, -15)=='PlanningElement' and $hist->operation=='insert') {
      $hide=true;
    }
    if ($hist->isWorkHistory and !$showWorkHistory) {
      $hide=true;
    }
    if (substr($hist->colName, 0, 6)=='|Note|' or substr($hist->colName, 0, 12)=='|Attachment|') {
      $expl=explode('|', $hist->colName);
      if (count($expl)==3) {
        $clSub=$expl[1];
        $idSub=$expl[2];
        $sub=new $clSub($idSub);
        if (property_exists($sub, 'idPrivacy') and $sub->idPrivacy==3 and $sub->idUser!=getCurrentUserId()) {
          $hide=true;
        } else if (property_exists($sub, 'idPrivacy') and $sub->idPrivacy==2 and property_exists($sub, 'idTeam') and $sub->idTeam!=getSessionUser()->idTeam) {
          $hide=true;
        }
      }
    }
    if (!$hide) {
      echo '<tr>';
      echo '<td class="historyData'.$class.'" width="10%">'.$oper.'</td>';
      
      echo '<td class="historyData" width="14%">'.$colCaption.'</td>';
      $oldValue=$hist->oldValue;
      $newValue=$hist->newValue;
      if ($dataType=='int' and $dataLength==1) { // boolean
        $oldValue=htmlDisplayCheckbox($oldValue);
        $newValue=htmlDisplayCheckbox($newValue);
      } else if (substr($colName, 0, 2)=='id' and strlen($colName)>2 and strtoupper(substr($colName, 2, 1))==substr($colName, 2, 1)) {
        if ($oldValue!=null and $oldValue!='') {
          if ($oldValue==0 and $colName=='idStatus') {
            $oldValue='';
          } else {
            // BEGIN - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
            $colWithoutAlias=foreignKeyWithoutAlias($colName);
            $oldValue=SqlList::getNameFromId(substr($colWithoutAlias, 2), $oldValue);
            // END - REPLACE BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
            // $oldValue=SqlList::getNameFromId(substr($colName, 2), $oldValue);
          }
        }
        if ($newValue!=null and $newValue!='') {
          // BEGIN - ADD BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
          $colWithoutAlias=foreignKeyWithoutAlias($colName);
          $newValue=SqlList::getNameFromId(substr($colWithoutAlias, 2), $newValue);
          // $newValue=SqlList::getNameFromId(substr($colName, 2), $newValue);
          // END - ADD BY TABARY - POSSIBILITY TO HAVE X TIMES IDXXXX IN SAME OBJECT
        }
      } else if ($colName=="color") {
        $oldValue=htmlDisplayColored("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $oldValue);
        $newValue=htmlDisplayColored("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $newValue);
      } else if ($dataType=='date') {
        $oldValue=htmlFormatDate($oldValue);
        $newValue=htmlFormatDate($newValue);
      } else if ($dataType=='datetime') {
        $oldValue=htmlFormatDateTime($oldValue);
        $newValue=htmlFormatDateTime($newValue);
      } else if ($dataType=='decimal' and substr($colName, -4, 4)=='Work') {
        $oldValue=Work::displayWork($oldValue).' '.Work::displayShortWorkUnit();
        $newValue=Work::displayWork($newValue).' '.Work::displayShortWorkUnit();
      } else if ($dataType=='decimal' and (substr($colName, -4, 4)=='Cost' or strtolower(substr($colName, -6, 6))=='amount')) {
        $oldValue=htmlDisplayCurrency($oldValue);
        $newValue=htmlDisplayCurrency($newValue);
      } else if (substr($colName, -8, 8)=='Duration') {
        $oldValue=$oldValue.' '.i18n('shortDay');
        $newValue=$newValue.' '.i18n('shortDay');
      } else if (substr($colName, -8, 8)=='Progress') {
        $oldValue=$oldValue.' '.i18n('colPct');
        $newValue=$newValue.' '.i18n('colPct');
      } else if ($dataLength>4000 or $refType=='Note') {
        // $diff=diffValues($oldValue,$newValue);
        // Nothing, preserve html format
        // $oldValue=$colName;
      } else if ($colName=='password' or $colName=='apiKey') {
        $allstars="**********";
        if ($oldValue) $oldValue=substr($oldValue, 0, 5).$allstars.substr($oldValue, -5);
        if ($newValue) $newValue=substr($newValue, 0, 5).$allstars.substr($newValue, -5);
      } else {
        // $diff=diffValues($oldValue,$newValue);
        $oldValue=htmlEncode($oldValue, 'print');
        $newValue=htmlEncode($newValue, 'print');
      }
      echo '<td class="historyData" width="23%">'.$oldValue.'</td>';
      echo '<td class="historyData" width="23%">'.$newValue.'</td>';
      echo '<td class="historyData'.$class.'" width="15%">';
      // echo formatDateThumb($creationDate, $updateDate);
      echo $date.'</td>';
      echo '<td class="historyData'.$class.'" style="border-right: 1px solid #AAAAAA;" width="15%">';
      if ($user) {
        echo formatUserThumb($hist->idUser, $user, null, '16', 'left').'&nbsp;';
      }
      echo $user;
      echo '</td>';
      echo '</tr>';
      $stockDate=$hist->operationDate;
      $stockUser=$hist->idUser;
      $stockOper=$hist->operation;
    }
  }
  echo '<tr>';
  echo '<td class="historyDataClosetable">&nbsp;</td>';
  echo '<td class="historyDataClosetable">&nbsp;</td>';
  echo '<td class="historyDataClosetable">&nbsp;</td>';
  echo '<td class="historyDataClosetable">&nbsp;</td>';
  echo '<td class="historyDataClosetable">&nbsp;</td>';
  echo '<td class="historyDataClosetable">&nbsp;</td>';
  echo '</tr>';
  echo '</table>';
}

// ADD BY Marc TABARY - 2017-02-23 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT
/**
 * =====================================================================================
 * Draw section of an object linked by an id with the object to which we draw the detail
 * Sample : drawObjectLinkedByIdToObject($obj, 'Project', true)
 * Draw a section for projects with idxxxx (where xxxx the name of the $obj's classe)
 * --------------------------------------------------------------------------------------
 *
 * @global type $cr
 * @global type $print
 * @global type $outMode
 * @global type $comboDetail
 * @global type $displayWidth
 * @global type $printWidth
 * @param object $obj
 *          : The object's instance to which we draw the detail
 * @param object $objLinkedByIdObject
 *          : The name of the object's classe to which we draw the section
 * @param boolean $refresh          
 * @return nothing
 */
function drawObjectLinkedByIdToObject($obj, $objLinkedByIdObject='', $refresh=false) {
  global $cr, $print, $outMode, $comboDetail, $displayWidth, $printWidth;
  
  if ($comboDetail) {
    return;
  }
  
  if (!class_exists($objLinkedByIdObject)) {
    return;
  }
  
  // ADD BY Marc TABARY - 2017-03-10 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - href
  $goto='';
  // END ADD BY Marc TABARY - 2017-03-10 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - href
  
  $theClassName='_'.$objLinkedByIdObject;
  // Get the visible list of linked Object
  $listVisibleLinkedObj=getUserVisibleObjectsList($objLinkedByIdObject);
  
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($canUpdate) {
    $canUpdate=securityGetAccessRightYesNo('menu'.$objLinkedByIdObject, 'update', $obj)=="YES";
  }
  
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (isset($obj->$theClassName)) {
    $objects=$obj->$theClassName;
  } else {
    $objects=array();
  }
  
  if (!$refresh and !$print) echo '<tr><td colspan="2">';
  echo '<input type="hidden" id="objectIdle" value="'.htmlEncode($obj->idle).'" />';
  
  if (!$print) {
    echo '<table width="99.9%">';
  }
  echo '<tr>';
  if (!$print) {
    echo '<td class="assignHeader smallButtonsGroup" style="width:5%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      // Parameters passed at addLinkObjectToObject
      // 1 - The main object's class name
      // 2 - The id of main object
      // 3 - The linked object's class name
      echo '<a onClick="addLinkObjectToObject(\''.get_class($obj).'\',\''.htmlEncode($obj->id).'\',\''.$objLinkedByIdObject.'\');" title="'.i18n('addLinkObject').'" >'.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" style="width:5%">'.i18n('colId').'</td>';
  echo '<td class="assignHeader" style="width:'.(($print)?'85':'80').'%">'.i18n('colName').'</td>';
  // ADD BY Marc TABARY - 2017-03-16 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - idle
  echo '<td class="assignHeader" style="width:'.(($print)?'10':'10').'%">'.i18n('colIdle').'</td>';
  // ADD BY Marc TABARY - 2017-03-16 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - idle
  echo '</tr>';
  $nbObjects=0;
  foreach ($objects as $theObj) {
    $nbObjects++;
    echo '<tr>';
    if (!$print) {
      echo '<td class="assignData smallButtonsGroup">';
      if (!$print and $canUpdate and array_key_exists($theObj->id, $listVisibleLinkedObj)) {
        
        // Implement to following rule :
        // A manager of an organization can't be remove from it
        if (get_class($obj)=='Organization' and get_class($theObj)=='Resource' and $obj->idResource==$theObj->id) {
          echo ' <a title="'.i18n('isOrganizationManager').'" >'.formatSmallButton('Blocked').'</a>';
        } else {
          // ADD BY Marc TABARY - 2017-03-16 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - idle
          if ($theObj->idle==0) {
            // END ADD BY Marc TABARY - 2017-03-16 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - idle
            // Parameters passed at removeLinkObjectFromObject
            // 1 - The main object's class name
            // 2 - The linked object's class name
            // 3 - The id of the selected linked object
            // 4 - The name of the selected linked object
            echo ' <a onClick="removeLinkObjectFromObject(\''.get_class($obj).'\',\''.$objLinkedByIdObject.'\',\''.htmlEncode($theObj->id).'\',\''.htmlEncode($theObj->name).'\');" title="'.i18n('removeLinkObject').'" > '.formatSmallButton('Remove').'</a>';
          }
        }
      }
      echo '</td>';
    }
    if (array_key_exists($theObj->id, $listVisibleLinkedObj)) {
      echo '<td class="assignData" style="width:5%">#'.htmlEncode($theObj->id).'</td>';
      // ADD BY Marc TABARY - 2017-03-10 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - href
      if (!$print and securityCheckDisplayMenu(null, get_class($theObj)) and securityGetAccessRightYesNo('menu'.get_class($theObj), 'read', '')=="YES") {
        $goto=' onClick="gotoElement(\''.get_class($theObj).'\',\''.htmlEncode($theObj->id).'\');" style="cursor: pointer;" ';
      }
      // END ADD BY Marc TABARY - 2017-03-10 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - href
      // CHANGE BY Marc TABARY - 2017-03-10 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - href
      echo '<td '.$goto.' class="assignData hyperlink" style="width:'.(($print)?'85':'80').'%">'.htmlEncode($theObj->name).'</td>';
      // Old
      // echo '<td class="assignData" style="width:' . (($print)?'95':'85') . '%">' . htmlEncode($theObj->name) . '</td>';
      // END CHANGE BY Marc TABARY - 2017-03-10 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - href
    } else {
      echo '<td class="assignData" style="width:5%"></td>';
      echo '<td class="assignData" style="width:'.(($print)?'85':'80').'%">'.i18n('isNotVisible').'</td>';
    }
    // ADD BY Marc TABARY - 2017-03-16 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - idle
    echo '<td class="assignData dijitButtonText" style="width:'.(($print)?'10':'10').'%">'.htmlDisplayCheckbox($theObj->idle).'</td>';
    // END ADD BY Marc TABARY - 2017-03-16 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT - idle
    
    echo '</tr>';
  }
  if (!$print) {
    echo '</table>';
  }
  if (!$refresh and !$print) echo '</td></tr>';
  if (!$print) {
    echo '<input id="ObjectSectionCount" type="hidden" value="'.$nbObjects.'" />';
  }
}
// END ADD BY Marc TABARY - 2017-02-23 - DRAW LIST OF OBJECTS LINKED BY ID TO MAIN OBJECT

// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
/**
 * =====================================================================================
 * Draw section of Notification for the object passed in parameter
 * --------------------------------------------------------------------------------------
 *
 * @global type $print
 * @global type $comboDetail
 * @param object $obj
 *          : The object's instance to which we draw the notifications
 * @param boolean $unreadOnly
 *          : If True, draw only the unread notifications
 * @param boolean $refresh          
 * @return nothing
 */
function drawNotificationsLinkedToObject($obj, $unreadOnly=true, $refresh=false) {
  global $print, $comboDetail;
  if ($comboDetail) {
    return;
  }
  
  if (get_class($obj)===null) {
    return;
  }
  
  $notificationObjClass=get_class($obj);
  
  // The 'unread' status
  // $idStatus = SqlElement::getSingleSqlElementFromCriteria("Status", array("name" => "unread"))->id;
  $idStatusNotification=1;
  
  // The notifiable's id
  $idNotificationObjClass=SqlElement::getSingleSqlElementFromCriteria("Notifiable", array("notifiableItem"=>$notificationObjClass))->id;
  
  // The connected user
  $userId=getSessionUser()->id;
  
  $crit=array('idle'=>'0', 'idNotifiable'=>$idNotificationObjClass, 'idUser'=>$userId, 'notifiedObjectId'=>$obj->id);
  
  if ($unreadOnly) {
    // $crit['idStatus'] = $idStatus;
    $crit['idStatusNotification']=$idStatusNotification;
  }
  
  $notif=new Notification();
  $list=$notif->getSqlElementsFromCriteria($crit);
  
  $canUpdate=securityGetAccessRightYesNo('menuNotification', 'update')=="YES";
  if (!$refresh) {
    echo '<tr><td colspan="4">';
  }
  echo '<table style="width:100%;">';
  echo '<tr>';
  $listClass='Notification';
  echo '<td class="linkHeader" style="width:10%">'.i18n('colId').'</td>';
  echo '<td class="linkHeader" style="width:50%">'.i18n('colName').'</td>';
  echo '<td class="linkHeader" style="width:20%">'.i18n('colType').'</td>';
  echo '<td class="linkHeader" style="width:20%">'.i18n('colIdStatus').'</td>';
  echo '</tr>';
  
  foreach ($list as $notif) {
    
    $notificationDefinition=new NotificationDefinition($notif->idNotificationDefinition);
    
    $canGoto=(securityCheckDisplayMenu(null, $listClass) and securityGetAccessRightYesNo('menu'.$listClass, 'read', $notif)=="YES")?true:false;
    echo '<tr>';
    $classCompName=i18n($listClass);
    echo '<td class="linkData" style="white-space:nowrap;width:10%">&nbsp;#'.$notif->id.'</td>';
    $goto="";
    if (!$print and $canGoto) {
      $goto=' onClick="gotoElement('."'".$listClass."','".htmlEncode($notif->id)."'".');" style="cursor: pointer;" ';
    }
    echo '<td class="linkData" '.$goto.' style="position:relative;width:50%">';
    echo htmlEncode($notificationDefinition->name);
    echo '</td>';
    echo '<td class="linkData" style="position:relative;width:20%;">';
    echo colorNameFormatter(i18n(SqlList::getNameFromId('Type', $notif->idNotificationType))."#split#".SqlList::getFieldFromId('Type', $notif->idNotificationType, 'color'));
    echo '</td>';
    $changeStatus='';
    if (!$print and $canUpdate) {
      $changeStatus=' onClick="changeStatusNotification('."'".htmlEncode($notif->id)."','".htmlEncode($notif->idStatusNotification)."'".');" style="cursor:pointer;" ';
    }
    echo '<td class="linkData" '.$changeStatus.' style="position:relative;width:20%;">';
    echo colorNameFormatter(i18n(SqlList::getNameFromId('StatusNotification', $notif->idStatusNotification))."#split#".SqlList::getFieldFromId('StatusNotification', $notif->idStatusNotification, 'color'));
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="NotificationSectionCount" type="hidden" value="'.count($list).'" />';
  }
}
// END - ADD BY TABARY - NOTIFICATION SYSTEM
function drawNotesFromObject($obj, $refresh=false) {
  global $cr, $print, $outMode, $user, $comboDetail, $displayWidth, $printWidth, $preseveHtmlFormatingForPDF;
  $widthPct=setWidthPct($displayWidth, $print, $printWidth, $obj);
  $widthPctNote=((substr($widthPct, 0, strlen($widthPct)-2)*0.85)-45).'px';
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (isset($obj->_Note)) {
    $notes=$obj->_Note;
  } else {
    $notes=array();
  }
  if (!$refresh and !$print) echo '<tr><td colspan="2">';
  echo '<input type="hidden" id="noteIdle" value="'.htmlEncode($obj->idle).'" />';
  if (!$print) {
    echo '<table width="99.9%">';
  }
  echo '<tr>';
  if (!$print) {
    echo '<td class="noteHeader smallButtonsGroup" style="width:10%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addNote();" title="'.i18n('addNote').'" >'.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="noteHeader" style="width:5%">'.i18n('colId').'</td>';
  echo '<td class="noteHeader" style="width:'.(($print)?'95':'85').'%">'.i18n('colNote').'</td>';
  // echo '<td class="noteHeader" style="width:15%">' . i18n ( 'colDate' ) . '</td>';
  // echo '<td class="noteHeader" style="width:15%">' . i18n ( 'colUser' ) . '</td>';
  echo '</tr>';
  $nbNotes=0;
  $ress=new Resource($user->id);
  foreach ($notes as $note) {
    if ($user->id==$note->idUser or $note->idPrivacy==1 or ($note->idPrivacy==2 and $ress->idTeam==$note->idTeam)) {
      $nbNotes++;
      $userId=$note->idUser;
      $userName=SqlList::getNameFromId('User', $userId);
      $creationDate=$note->creationDate;
      $updateDate=$note->updateDate;
      if ($updateDate==null) {
        $updateDate='';
      }
      echo '<tr>';
      if (!$print) {
        echo '<td class="noteData smallButtonsGroup">';
        if ($note->idUser==$user->id and !$print and $canUpdate) {
          echo ' <a onClick="editNote('.htmlEncode($note->id).','.htmlEncode($note->idPrivacy).');" title="'.i18n('editNote').'" > '.formatSmallButton('Edit').'</a>';
          echo ' <a onClick="removeNote('.htmlEncode($note->id).');" title="'.i18n('removeNote').'" > '.formatSmallButton('Remove').'</a>';
        }
        echo '</td>';
      }
      echo '<td class="noteData" style="width:5%">#'.htmlEncode($note->id).'</td>';
      echo '<td class="noteData" style="width:'.(($print)?'95':'85').'%">';
      /*
       * if (!$print) {
       * echo '<div style="display:none" type="hidden" id="note_' . htmlEncode($note->id) . '">';
       * echo $note->note;
       * echo '</div>';
       * }
       */
      echo formatUserThumb($userId, $userName, 'Creator');
      echo formatDateThumb($creationDate, $updateDate);
      echo formatPrivacyThumb($note->idPrivacy, $note->idTeam);
      // ADDED BRW
      // $strDataHTML=htmlEncode($note->note, ''); // context = '' => only htmlspecialchar, not htmlentities
      if (!$print) echo '<div style="max-width:'.$widthPctNote.';overflow-x:auto;" >';
      $strDataHTML=$note->note;
      // $strDataHTML=preg_replace('@(https?://([-\w\.]<+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $strDataHTML);
      // $strDataHTML=nl2br($strDataHTML); // then convert line breaks : must be after preg_replace of url
      if ($print and $outMode=="pdf") { // Must purge data, otherwise will never be generated
        if ($preseveHtmlFormatingForPDF) {
          // $strDataHTML='<div>'.$strDataHTML.'</div>';
        } else {
          $strDataHTML=htmlEncode($strDataHTML, 'pdf'); // remove all tags but line breaks
        }
      } else {
        if (!isTextFieldHtmlFormatted($strDataHTML)) {
          $strDataHTML=htmlEncode($strDataHTML, 'plainText');
        } else {
          $strDataHTML=preg_replace('@(https?://([-\w\.]<+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $strDataHTML);
        }
      }
      echo $strDataHTML;
      if (!$print) echo '</div>';
      // END ADDED BRW
      echo '</td>';
      /*
       * echo '<td class="noteData">' . htmlFormatDateTime ( $creationDate ) . '<br/>'; if ($note->fromEmail) { echo '<b>' . i18n ( 'noteFromEmail' ) . '</b>'; } echo '<i>' . htmlFormatDateTime ( $updateDate ) . '</i></td>'; echo '<td class="noteData">' . $userName . '</td>';
       */
      echo '</tr>';
    }
  }
  echo '<tr>';
  if (!$print) {
    echo '<td class="noteDataClosetable">&nbsp;</td>';
  }
  echo '<td colspan="'.(($print)?'2':'3').'" class="noteDataClosetable">&nbsp;</td>';
  echo '</tr>';
  if (!$print) {
    echo '</table>';
  }
  if (!$refresh and !$print) echo '</td></tr>';
  if (!$print) {
    echo '<input id="NoteSectionCount" type="hidden" value="'.count($notes).'" />';
  }
}

function drawBillLinesFromObject($obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $widthPct;
  // $canUpdate=securityGetAccessRightYesNo('menu' . get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  $lock=false;
  if ($obj->done or $obj->idle or (property_exists($obj, 'billingType') and $obj->billingType=="N")) {
    $lock=true;
  }
  if (isset($obj->_BillLine)) {
    $lines=$obj->_BillLine;
  } else {
    $lines=array();
  }
  if (!$print) {
    echo '<input type="hidden" id="billLineIdle" value="'.htmlEncode($obj->idle).'" />';
    if ($refresh) echo '<table width="100%">';
  }
  echo '<tr>';
  $billingType='M';
  if (property_exists($obj, 'billingType') and $obj->billingType) {
    $billingType=$obj->billingType;
  }
  if (!$print) {
    echo '<td class="noteHeader" style="width:5%">'; // changer le header
    if ($obj->id!=null and !$print and !$lock) {
      echo '<a onClick="addBillLine(\'M\');" title="'.i18n('addLine').'" > '.formatSmallButton('Add').'</a>';
      if ($billingType!='M') {
        // echo '<a onClick="addBillLine(\''.$billingType.'\');" title="' . i18n('addFormattedBillLine') . '" style="cursor: pointer;display: inline-block;margin-left:5px;" class="roundedButtonSmall"> '.formatIcon('Bill',16).'</a>';
        echo '<a onClick="addBillLine(\''.$billingType.'\');" title="'.i18n('addFormattedBillLine').'" > '.formatSmallButton('Bill', true).'</a>';
      }
    }
    echo '</td>';
  }
  echo '<td class="noteHeader" style="width:5%">'.i18n('colId').'</td>';
  echo '<td class="noteHeader" style="width:5%">'.i18n('colLineNumber').'</td>';
  echo '<td class="noteHeader" style="width:20%">'.i18n('colDescription').'</td>';
  echo '<td class="noteHeader" style="width:25%">'.i18n('colDetail').'</td>';
  echo '<td class="noteHeader" style="width:10%">'.i18n('colUnitPrice').'</td>';
  echo '<td class="noteHeader" style="width:10%">'.i18n('colQuantity').'</td>';
  echo '<td class="noteHeader" style="width:10%">'.strtolower(i18n('sum')).'</td>';
  if (get_class($obj)!='Tender' and get_class($obj)!='ProviderOrder' and get_class($obj)!='ProviderBill') {
    echo '<td class="noteHeader" style="width:15%">'.i18n('colDays').'</td>';
  }
  echo '</tr>';
  
  $fmt=new NumberFormatter52($browserLocale, NumberFormatter52::INTEGER);
  $fmtd=new NumberFormatter52($browserLocale, NumberFormatter52::DECIMAL);
  $lines=array_reverse($lines);
  foreach ($lines as $line) {
    $unit=new MeasureUnit($line->idMeasureUnit);
    echo '<tr>';
    if (!$print) {
      echo '<td class="noteData" style="text-align:center;white-space:nowrap">';
      if ($lock==0) {
        echo ' <a onClick="editBillLine('.htmlEncode($line->id).',\''.htmlEncode(($line->billingType)?$line->billingType:$billingType).'\');" ';
        echo '  title="'.i18n('editLine').'" > '.formatSmallButton('Edit').'</a>';
        if (get_class($obj)=='ProviderOrder') {
          $providerTerm=new ProviderTerm();
          $listProvTerm=$providerTerm->getSqlElementsFromCriteria(array("idProviderOrder"=>$obj->id));
          $billLineTerm=new BillLine();
          $hide=false;
          foreach ($listProvTerm as $providerTerms) {
            $billLineList=$billLineTerm->getSqlElementsFromCriteria(array("refType"=>"ProviderTerm", "refId"=>$providerTerms->id));
            if ($billLineList) {
              $hide=true;
            }
          }
          if ($hide==false) {
            echo ' <a onClick="removeBillLine('.htmlEncode($line->id).');"'.' ';
            echo '  title="'.i18n('removeLine').'" > '.formatSmallButton('Remove').'</a>';
          }
        } else {
          echo ' <a onClick="removeBillLine('.htmlEncode($line->id).');"'.' ';
          echo '  title="'.i18n('removeLine').'" > '.formatSmallButton('Remove').'</a>';
        }
      }
      echo '</td>';
    }
    echo '<td class="noteData" style="width:5%">#'.htmlEncode($line->id).'</td>';
    echo '<td class="noteData" style="width:5%">'.htmlEncode($line->line).'</td>';
    echo '<td class="noteData" style="width:20%">'.htmlEncode($line->description, 'withBR');
    if (!$print) {
      echo '<input type="hidden" id="billLineDescription_'.htmlEncode($line->id).'" value="'.htmlEncode($line->description).'" />';
    }
    echo '</td>';
    echo '<td class="noteData" style="width:25%">'.htmlEncode($line->detail, 'withBR');
    if (!$print) {
      echo '<input type="hidden" id="billLineDetail_'.htmlEncode($line->id).'" value="'.htmlEncode($line->detail).'" />';
    }
    echo '</td>';
    $unitPrice=($unit->name)?' / '.$unit->name:'';
    echo '<td class="noteData" style="width:10%">'.htmlDisplayCurrency($line->price).$unitPrice.'</td>';
    $unitQuantity=($unit->name)?' '.(($line->quantity>1)?$unit->pluralName:$unit->name):'';
    echo '<td class="noteData" style="width:10%">'.htmlDisplayNumericWithoutTrailingZeros($line->quantity).$unitQuantity.'</td>';
    echo '<td class="noteData" style="width:10%">'.htmlDisplayCurrency($line->amount).'</td>';
    if (get_class($obj)!='Tender' and get_class($obj)!='ProviderOrder' and get_class($obj)!='ProviderBill') {
      echo '<td class="noteData" style="width:15%">'.htmlDisplayNumericWithoutTrailingZeros($line->numberDays).'</td>';
    }
    echo '</tr>';
  }
  echo '<tr>';
  if (!$print) {
    echo '<td class="noteDataClosetable">&nbsp;</td>';
  }
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '</tr>';
  if (!$print) {
    if ($refresh) echo '</table>';
  }
}

function drawBillLinesProviderTerms($obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $widthPct;
  if (get_class($obj)=='ProviderBill') {
    $providerTerm=new ProviderTerm();
    $listProvTerm=$providerTerm->getSqlElementsFromCriteria(array("idProviderBill"=>$obj->id));
    $lines=array();
    $i=0;
    foreach ($listProvTerm as $term) {
      $providerTerm=new ProviderTerm($term->id);
      if ($providerTerm->idProviderOrder) {
        $providerOrder=new ProviderOrder($providerTerm->idProviderOrder);
        $discountRate[$i]=$providerOrder->discountRate;
      }
      $i++;
      array_push($lines, $providerTerm->_BillLineTerm);
    }
  } else {
    if (isset($obj->_BillLineTerm)) {
      $providerBill=new ProviderBill($obj->idProviderBill);
      $lines=$obj->_BillLineTerm;
    } else {
      $lines=array();
    }
  }
  if (!$print) {
    echo '<input type="hidden" id="billLineIdle" value="'.htmlEncode($obj->idle).'" />';
    if ($refresh) echo '<table width="100%">';
  }
  echo '<tr>';
  echo '  <td class="noteHeader" style="width:5%">'.i18n('colId').'</td>';
  echo '  <td class="noteHeader" style="width:5%">'.i18n('colLineNumber').'</td>';
  echo '  <td class="noteHeader" style="width:20%">'.i18n('colDescription').'</td>';
  echo '  <td class="noteHeader" style="width:20%">'.i18n('colDetail').'</td>';
  echo '  <td class="noteHeader" style="width:10%">'.i18n('colInitialAmount').'</td>';
  echo '  <td class="noteHeader" style="width:8%">'.i18n('colRate').'</td>';
  echo '  <td class="noteHeader" style="width:8%">'.i18n('colTermAmount').'</td>';
  echo '  <td class="noteHeader" style="width:8%">'.i18n('colDiscount').'</td>';
  echo '  <td class="noteHeader" style="width:8%">'.i18n('colTaxAmount').'</td>';
  echo '  <td class="noteHeader" style="width:8%">'.i18n('colFullAmount').'</td>';
  echo '</tr>';
  
  $fmt=new NumberFormatter52($browserLocale, NumberFormatter52::INTEGER);
  $fmtd=new NumberFormatter52($browserLocale, NumberFormatter52::DECIMAL);
  $discountRate=null;
  if (get_class($obj)!='ProviderBill') {
    $lines=array_reverse($lines);
    if (isset($obj->idProviderOrder)) {
      $providerOrder=new ProviderOrder($obj->idProviderOrder);
      $discountRate=$providerOrder->discountRate;
    }
  }
  if (get_class($obj)=='ProviderBill') {
    $i=0;
    foreach ($lines as $line) {
      foreach ($line as $linee) {
        $billLine=new BillLine($linee->idBillLine);
        $unit=new MeasureUnit($linee->idMeasureUnit);
        if ($linee->rate==0) {
          continue;
        }
        echo '<tr>';
        echo '<td class="noteData" style="width:5%">#'.htmlEncode($linee->id).'</td>';
        echo '<td class="noteData" style="width:5%">'.htmlEncode($billLine->line).'</td>';
        echo '<td class="noteData" style="width:20%">'.htmlEncode($billLine->description, 'withBR');
        if (!$print) {
          echo '<input type="hidden" id="billLineDescription_'.htmlEncode($linee->id).'" value="'.htmlEncode($linee->description).'" />';
        }
        echo '</td>';
        echo '<td class="noteData" style="width:20%">'.htmlEncode($billLine->detail, 'withBR');
        if (!$print) {
          echo '<input type="hidden" id="billLineDetail_'.htmlEncode($linee->id).'" value="'.htmlEncode($linee->detail).'" />';
        }
        echo '</td>';
        echo '<td class="noteData" style="width:10%">'.htmlDisplayCurrency($billLine->amount).'</td>';
        echo '<td class="noteData" style="width:8%">'.htmlDisplayPct($linee->rate).'</td>';
        echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency($linee->price).'</td>';
        echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency($linee->price*$discountRate[$i]/100).'</td>';
        echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency((($linee->price)-($linee->price*$discountRate[$i]/100))*$obj->taxPct/100).'</td>';
        echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency((($linee->price)-($linee->price*$discountRate[$i]/100))*$obj->taxPct/100+($linee->price-($linee->price*$discountRate[$i]/100))).'</td>';
        echo '</tr>';
      }
      $i++;
    }
  } else {
    foreach ($lines as $linee) {
      $billLine=new BillLine($linee->idBillLine);
      $unit=new MeasureUnit($linee->idMeasureUnit);
      echo '<tr>';
      echo '<td class="noteData" style="width:5%">#'.htmlEncode($linee->id).'</td>';
      echo '<td class="noteData" style="width:5%">'.htmlEncode($billLine->line).'</td>';
      echo '<td class="noteData" style="width:20%">'.htmlEncode($billLine->description, 'withBR');
      if (!$print) {
        echo '<input type="hidden" id="billLineDescription_'.htmlEncode($linee->id).'" value="'.htmlEncode($linee->description).'" />';
      }
      echo '</td>';
      echo '<td class="noteData" style="width:20%">'.htmlEncode($billLine->detail, 'withBR');
      if (!$print) {
        echo '<input type="hidden" id="billLineDetail_'.htmlEncode($linee->id).'" value="'.htmlEncode($linee->detail).'" />';
      }
      echo '</td>';
      echo '<td class="noteData" style="width:10%">'.htmlDisplayCurrency($billLine->amount).'</td>';
      echo '<td class="noteData" style="width:8%">'.htmlDisplayPct($linee->rate).'</td>';
      echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency($linee->price).'</td>';
      echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency($linee->price*$discountRate/100).'</td>';
      echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency((($linee->price)-($linee->price*$discountRate/100))*$obj->taxPct/100).'</td>';
      echo '<td class="noteData" style="width:8%">'.htmlDisplayCurrency((($linee->price)-($linee->price*$discountRate/100))*$obj->taxPct/100+($linee->price-($linee->price*$discountRate/100))).'</td>';
      echo '</tr>';
      $newRefId=$linee->refId;
    }
  }
  
  echo '<tr>';
  if (!$print) {
    echo '<td class="noteDataClosetable">&nbsp;</td>';
  }
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '</tr>';
  if (!$print) {
    if ($refresh) echo '</table>';
  }
}

function drawChecklistDefinitionLinesFromObject($obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $outMode;
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (isset($obj->_ChecklistDefinitionLine)) {
    $lines=$obj->_ChecklistDefinitionLine;
  } else {
    $lines=array();
  }
  echo '<input type="hidden" id="ChecklistDefinitionIdle" value="'.htmlEncode($obj->idle).'" />';
  echo '<table width="100%">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="noteHeader" style="width:5%">'; // changer le header
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addChecklistDefinitionLine('.htmlEncode($obj->id).');"'.' title="'.i18n('addLine').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="noteHeader" style="width:30%">'.i18n('colName').'</td>';
  echo '<td class="noteHeader" style="width:'.(($print)?'65':'60').'%">'.i18n('colChoices').'</td>';
  echo '<td class="noteHeader" style="width:5%">'.i18n('colExclusiveShort').'</td>';
  echo '</tr>';
  
  usort($lines, "ChecklistDefinitionLine::sort");
  foreach ($lines as $line) {
    echo '<tr>';
    if (!$print) {
      echo '<td class="noteData" style="width:5%;text-align:center;">';
      if ($canUpdate) {
        echo ' <a onClick="editChecklistDefinitionLine('.htmlEncode($obj->id).','.htmlEncode($line->id).');"'.' title="'.i18n('editLine').'" > '.formatSmallButton('Edit').'</a>';
        echo ' <a onClick="removeChecklistDefinitionLine('.htmlEncode($line->id).');"'.' title="'.i18n('removeLine').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    if ($line->check01) {
      echo '<td class="noteData" style="width:30%;border-right:0; text-align:right" title="'.htmlEncode($line->title).'">';
      if ($outMode!='pdf') echo '<div style="position: relative;">';
      echo htmlEncode($line->name);
      if ($outMode!='pdf') echo '<div style="position:absolute;top:0px; left:0px; color: #AAAAAA;">'.htmlEncode($line->sortOrder).'</div>';
      echo ' : ';
      if ($outMode!='pdf') echo '</div>';
      echo '</td>';
      echo '<td class="noteData" style="width:'.(($print)?'65':'60').'%;border-left:0;">';
      echo '<table witdh="100%"><tr>';
      for ($i=1; $i<=5; $i++) {
        $check='check0'.$i;
        $title='title0'.$i;
        echo '<td style="min-width:100px; white-space:nowrap; vertical-align:top; " '.(($line->$title)?'title="'.$line->$title.'"':'').'>';
        if ($line->$check) {
          echo "<table><tr><td>".htmlDisplayCheckbox(0)."&nbsp;</td><td valign='top'>".$line->$check."&nbsp;&nbsp;</td></tr></table>";
        }
        echo '</td>';
      }
      echo '</tr></table>';
      echo '</td>';
      echo '<td class="noteData" style="width:5%">'.htmlDisplayCheckbox($line->exclusive).'</td>';
    } else {
      echo '<td class="reportTableHeader" colspan="3" style="width:'.(($print)?'100':'95').'%,text-align:center" title="'.htmlEncode($line->title).'">'.htmlEncode($line->name).'</td>';
    }
    echo '</tr>';
  }
  echo '<tr>';
  if (!$print) {
    echo '<td class="noteDataClosetable">&nbsp;</td>';
  }
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '</tr>';
  echo '</table>';
}

function drawAttachmentsFromObject($obj, $refresh=false) {
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  echo '<input type="hidden" id="attachmentIdle" value="'.htmlEncode($obj->idle).'" />';
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (isset($obj->_Attachment)) {
    $attachments=$obj->_Attachment;
  } else {
    $attachments=array();
  }
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table width="100%">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="attachmentHeader smallButtonsGroup" style="width:10%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addAttachment(\'file\');" title="'.i18n('addAttachment').'"> '.formatSmallButton('Add').'</a>';
      echo '<a onClick="addAttachment(\'link\');" title="'.i18n('addHyperlink').'" > '.formatSmallButton('Link').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="attachmentHeader" style="width:5%">'.i18n('colId').'</td>';
  echo '<td colspan="2" class="attachmentHeader" style="width:'.(($print)?'95':'85').'%">'.i18n('colFile').'</td>';
  echo '</tr>';
  foreach ($attachments as $attachment) {
    $userId=$attachment->idUser;
    $ress=new Resource($user->id);
    if ($user->id==$attachment->idUser or $attachment->idPrivacy==1 or ($attachment->idPrivacy==2 and $ress->idTeam==$attachment->idTeam)) {
      $userName=SqlList::getNameFromId('User', $userId);
      $creationDate=$attachment->creationDate;
      $updateDate=null;
      echo '<tr>';
      if (!$print) {
        echo '<td class="attachmentData smallButtonsGroup" style="width:10%"">';
        if ($attachment->fileName and $attachment->subDirectory and !$print) {
          echo '<a href="../tool/download.php?class=Attachment&id='.htmlEncode($attachment->id).'"';
          echo ' target="printFrame" title="'.i18n('helpDownload').'">'.formatSmallButton('Download').'</a>';
        }
        if ($attachment->link and !$print) {
          echo '<a href="'.htmlEncode(urldecode($attachment->link)).'"';
          echo ' target="#" title="'.urldecode($attachment->link).'">'.formatSmallButton('Link').'</a>';
        }
        if ($attachment->idUser==$user->id and !$print and $canUpdate) {
          echo ' <a onClick="removeAttachment('.htmlEncode($attachment->id).');" title="'.i18n('removeAttachment').'" >'.formatSmallButton('Remove').'</a>';
        }
        echo '</td>';
      }
      echo '<td class="attachmentData" style="width:5%;">#'.htmlEncode($attachment->id).'</td>';
      echo '<td class="attachmentData" style="width:5%;border-right:none;text-align:center;">';
      if ($attachment->isThumbable()) {
        echo '<img src="'.getImageThumb($attachment->getFullPathFileName(), 32).'" '.' title="'.htmlEncode($attachment->fileName).'" style="float:left;cursor:pointer"'.' onClick="showImage(\'Attachment\',\''.htmlEncode($attachment->id).'\',\''.htmlEncode($attachment->fileName, 'protectQuotes').'\');" />';
      } else if ($attachment->link and !$print) {
        echo '<div style="float:left;cursor:pointer" onClick="showLink(\''.htmlEncode(urldecode($attachment->link)).'\');">';
        echo '<img src="../view/img/mime/html.png" title="'.htmlEncode($attachment->link).'" />';
        echo '</div>';
      } else {
        echo htmlGetMimeType($attachment->mimeType, $attachment->fileName, $attachment->id);
      }
      echo '</td><td class="attachmentData" style="border-left:none;width:'.(($print)?'90':'80').'%" >';
      echo formatUserThumb($userId, $userName, 'Creator');
      echo formatDateThumb($creationDate, $updateDate);
      echo formatPrivacyThumb($attachment->idPrivacy, $attachment->idTeam);
      if ($attachment->link) {
        $fileName=htmlEncode(urldecode($attachment->link), 'print');
      } else {
        $fileName=htmlEncode($attachment->fileName, 'print');
      }
      if ($attachment->description and !$print) {
        echo formatCommentThumb($fileName);
        echo htmlEncode($attachment->description, 'print');
      } else {
        echo $fileName;
      } 
      echo '</td>';
      echo '</tr>';
    }
  }
  echo '<tr>';
  if (!$print) {
    echo '<td class="attachmentDataClosetable">&nbsp;';
    echo '<input type="hidden" name="nbAttachments" id="nbAttachments" value="'.count($attachments).'" />';
    echo '</td>';
  }
  echo '<td class="attachmentDataClosetable">&nbsp;</td>';
  echo '<td class="attachmentDataClosetable">&nbsp;</td>';
  echo '<td class="attachmentDataClosetable">&nbsp;</td>';
  echo '</tr>';
  echo '</table>';
  if (!$refresh) echo "</td></tr>";
  if (!$print) {
    echo '<input id="AttachmentSectionCount" type="hidden" value="'.count($attachments).'" />';
  }
}

function drawLinksFromObject($list, $obj, $classLink, $refresh=false) {
  if ($obj->isAttributeSetToField("_Link", "hidden")) {
    return;
  }
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  
  $cptt = 0;
  if($classLink==""){
    $findme   = '_Link_';
    foreach ($obj as $idVal=>$listVal){
      $finded = strstr($idVal, $findme);
      if($finded){
        if(is_array($listVal)){
          $cptt+= count($listVal);
        }
      }
    }
  }
  
  if (get_class($obj)=='Document') {
    $dv=new DocumentVersion();
    $lstVers=$dv->getSqlElementsFromCriteria(array('idDocument'=>$obj->id));
    foreach ($lstVers as $dv) {
      $crit="(ref1Type='DocumentVersion' and ref1Id=".htmlEncode($dv->id).")";
      $crit.="or (ref2Type='DocumentVersion' and ref2Id=".htmlEncode($dv->id).")";
      $lnk=new Link();
      $lstLnk=$lnk->getSqlElementsFromCriteria(null, null, $crit);
      foreach ($lstLnk as $lnk) {
        if ($lnk->ref1Type=='DocumentVersion') {
          $lnk->ref1Type='Document';
          $lnk->ref1Id=$obj->id;
        } else {
          $lnk->ref2Type='Document';
          $lnk->ref2Id=$obj->id;
        }
        $list[]=$lnk;
      }
    }
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="linkHeader" style="width:5%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      $linkable=SqlElement::getSingleSqlElementFromCriteria('Linkable', array('name'=>get_class($obj)));
      $default=$linkable->idDefaultLinkable;
      echo '<a onClick="addLink('."'".$classLink."','".$default."'".');" title="'.i18n('addLink').'" class="roundedButtonSmall">'.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  if (!$classLink) {
    echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n('colElement').'</td>';
  } else {
    echo '<td class="linkHeader" style="width:'.(($print)?'10':'5').'%">'.i18n('colId').'</td>';
  }
  
  echo '<td class="linkHeader" style="width:'.(($classLink)?'65':'55').'%">'.i18n('colName').'</td>';
  // if ($classLink and property_exists($classLink, 'idStatus')) {
  echo '<td class="linkHeader" style="width:15%">'.i18n('colIdStatus').'</td>';
  echo '<td class="linkHeader" style="width:10%">'.i18n('colResponsibleShort').'</td>';
  // }
  // echo '<td class="linkHeader" style="width:15%">' . i18n('colDate') . '</td>';
  // echo '<td class="linkHeader" style="width:15%">' . i18n('colUser') . '</td>';
  echo '</tr>';
  foreach ($list as $link) {
    $linkObj=null;
    if ($link->ref1Type==get_class($obj) and $link->ref1Id==$obj->id) {
      $linkObj=new $link->ref2Type($link->ref2Id);
    } else {
      $linkObj=new $link->ref1Type($link->ref1Id);
    }
    $userId=$link->idUser;
    $userName=SqlList::getNameFromId('User', $userId);
    $creationDate=$link->creationDate;
    $prop='_Link_'.get_class($linkObj);
    if ($classLink or !property_exists($obj, $prop)) {
      $gotoObj=(get_class($linkObj)=='DocumentVersion')?new Document($linkObj->idDocument):$linkObj;
      $canGoto=(securityCheckDisplayMenu(null, get_class($gotoObj)) and securityGetAccessRightYesNo('menu'.get_class($gotoObj), 'read', $gotoObj)=="YES")?true:false;
      echo '<tr>';
      if (substr(get_class($linkObj), 0, 7)=='Context') {
        $classLinkName=SqlList::getNameFromId('ContextType', substr(get_class($linkObj), 7, 1));
      } else {
        $classLinkName=i18n(get_class($linkObj));
      }
      if (!$print) {
        echo '<td class="linkData" style="text-align:center;width:5%;white-space:nowrap;">';
        if ($canGoto and (get_class($linkObj)=='DocumentVersion' or get_class($linkObj)=='Document') and isset($gotoObj->idDocumentVersion) and $gotoObj->idDocumentVersion) {
          echo '<a href="../tool/download.php?class='.get_class($linkObj).'&id='.htmlEncode($linkObj->id).'"';
          echo ' target="printFrame" title="'.i18n('helpDownload').'">'.formatSmallButton('Download').'</a>';
        }
        if ($canUpdate) {
          echo '  <a onClick="removeLink('."'".htmlEncode($link->id)."','".get_class($linkObj)."','".htmlEncode($linkObj->id)."','".$classLinkName."','".$classLink."'".');" title="'.i18n('removeLink').'" > '.formatSmallButton('Remove').'</a>';
        }
        echo '</td>';
      }
      $goto="";
      if (!$print and $canGoto) {
        $goto=' onClick="gotoElement('."'".get_class($gotoObj)."','".htmlEncode($gotoObj->id)."'".');" style="cursor: pointer;" ';
      }
      if (!$classLink) {
        echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"> <table><tr><td>';
        
        if (get_class($linkObj)=='DocumentVersion' or get_class($linkObj)=='Document') {
          if (get_class($linkObj)=='DocumentVersion') $version=$linkObj;
          else $version=new DocumentVersion($linkObj->idDocumentVersion);
          if ($version->isThumbable()) {
            $ext=pathinfo($version->fileName, PATHINFO_EXTENSION);
            if (file_exists("../view/img/mime/$ext.png")) {
              $img="../view/img/mime/$ext.png";
            } else {
              $img="../view/img/mime/unknown.png";
            }
            echo '<img src="'.$img.'" '.' title="'.htmlEncode($version->fileName).'" style="float:left;cursor:pointer"'.' onClick="showImage(\'DocumentVersion\',\''.htmlEncode($version->id).'\',\''.htmlEncode($version->fileName, 'protectQuotes').'\');" />';
          } else {
            echo htmlGetMimeType($version->mimeType, $version->fileName, $version->id, 'DocumentVersion');
          }
        } else {
          echo formatIcon(get_class($linkObj), 16);
        }
        echo '</td><td '.$goto.' style="vertical-align:top">&nbsp;'.$classLinkName.' #'.$linkObj->id.'</td></tr></table>';
      } else {
        echo '<td '.$goto.' class="linkData" style="white-space:nowrap;width:'.(($print)?'10':'5').'%">#'.$linkObj->id;
      }
      echo '</td>';
      echo '<td class="linkData" '.$goto.' style="position:relative;width:'.(($classLink)?'65':'55').'%">';
      
      echo (get_class($linkObj)=='DocumentVersion')?htmlEncode($linkObj->fullName):htmlEncode($linkObj->name);
      
      echo formatUserThumb($userId, $userName, 'Creator');
      echo formatDateThumb($creationDate, null);
      echo formatCommentThumb($link->comment);
      
      echo '</td>';
      $idStatus='idStatus';
      $statusClass='Status';
      if (!property_exists($linkObj, $idStatus) and property_exists($linkObj, 'id'.get_class($linkObj).'Status')) {
        $idStatus='id'.get_class($linkObj).'Status';
        $statusClass=get_class($linkObj).'Status';
      }
      if (property_exists($linkObj, $idStatus)) {
        $objStatus=new $statusClass($linkObj->$idStatus);
        echo '<td class="dependencyData"  style="width:15%">'.colorNameFormatter($objStatus->name."#split#".$objStatus->color).'</td>';
      } else {
        echo '<td class="dependencyData"  style="width:15%">&nbsp;</td>';
      }
      // //KROWRY
      if (property_exists($linkObj, 'idResource')&&$linkObj->idResource!=null) {
        $objR=get_class($linkObj);
        $objResp=new $objR($linkObj->id);
        echo '<td class="dependencyData"  style="width:10%">'.formatLetterThumb($objResp->idResource, 22).'</td>';
      } else {
        echo '<td class="dependencyData"  style="width:10%">&nbsp;</td>';
      }
      echo '</tr>';
    }
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
  $valueTotal = count($list)-$cptt;
    echo '<input id="'.$classLink.'LinkSectionCount" type="hidden" value="'.$valueTotal.'" />';
  }
}

function drawStructureFromObject($obj, $refresh=false, $way, $item) {
  $crit=array();
  if ($way=='composition') {
    $crit['idProduct']=$obj->id;
  } else if ($way=='structure') {
    $crit['idComponent']=$obj->id;
  } else {
    errorLog("unknown way=$way in drawStructureFromObject()");
  }
  $pcs=new ProductStructure();
  $list=$pcs->getSqlElementsFromCriteria($crit);
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  // TEST TICKET #2680
  $canUpdateComp=securityGetAccessRightYesNo('menuComponent', 'update', $obj)=="YES";
  //
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="linkHeader" style="width:5%">';
    // TEST TICKET #2680
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addProductStructure(\''.$way.'\');" title="'.i18n('addProductStructure').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  $listClass=($item=='Product')?'Component':(($way=='structure')?'ProductOrComponent':'Component');
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n($listClass).'</td>';
  echo '<td class="linkHeader" style="width:80%">'.i18n('colName').'</td>';
  echo '</tr>';
  foreach ($list as $comp) {
    $compObj=null;
    if ($way=='structure') {
      $compObj=new ProductOrComponent($comp->idProduct);
    } else {
      $compObj=new ProductOrComponent($comp->idComponent);
    }
    if ($compObj->scope=='Product') $compObj=new Product($compObj->id);
    else $compObj=new Component($compObj->id);
    $userId=$comp->idUser;
    $userName=SqlList::getNameFromId('User', $userId);
    $creationDate=$comp->creationDate;
    $canGoto=(securityCheckDisplayMenu(null, get_class($compObj)) and securityGetAccessRightYesNo('menu'.get_class($compObj), 'read', $compObj)=="YES")?true:false;
    echo '<tr>';
    $classCompName=i18n(get_class($compObj));
    if (!$print) {
      echo '<td class="linkData" style="text-align:center;width:5%;white-space:nowrap;">';
      if ($canUpdate) {
        echo '  <a onClick="editProductStructure(\''.$way.'\','.htmlEncode($comp->id).');" '.'title="'.i18n('editProductStructure').'" > '.formatSmallButton('Edit').'</a>';
        echo '  <a onClick="removeProductStructure('."'".htmlEncode($comp->id)."','".get_class($compObj)."','".htmlEncode($compObj->id)."','".$classCompName."'".');" '.'title="'.i18n('removeProductStructure').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    // echo '<td class="linkData" style="white-space:nowrap;width:' . (($print)?'20':'15') . '%"><img src="css/images/icon'.get_class($compObj).'16.png" />&nbsp;'.$classCompName .' #' . $compObj->id;
    echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr>';
    echo '<td>'.formatIcon(get_class($compObj), 16).'</td><td style="vertical-align:top">&nbsp;'.'#'.$compObj->id.'</td></tr></table>';
    echo '</td>';
    $goto="";
    if (!$print and $canGoto) {
      $goto=' onClick="gotoElement('."'".get_class($compObj)."','".htmlEncode($compObj->id)."'".');" style="cursor: pointer;" ';
    }
    echo '<td class="linkData" '.$goto.' style="position:relative;">';
    echo htmlEncode($compObj->name);
    echo formatUserThumb($userId, $userName, 'Creator');
    echo formatDateThumb($creationDate, null);
    echo formatCommentThumb($comp->comment);
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="ProductStructureSectionCount" type="hidden" value="'.count($list).'" />';
  }
}

// ADD by qCazelles - Business features
function drawBusinessFeatures($obj, $refresh=false) {
  $crit=array();
  $crit['idProduct']=$obj->id;
  $pcs=new BusinessFeature();
  $list=$pcs->getSqlElementsFromCriteria($crit, null, null, 'name asc');
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="linkHeader" style="width:5%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addBusinessFeature();" title="'.i18n('addBusinessFeature').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n('colId').'</td>';
  echo '<td class="linkHeader" style="width:80%">'.i18n('BusinessFeature').'</td>';
  echo '</tr>';
  
  foreach ($list as $bf) {
    $userId=$bf->idUser;
    $userName=SqlList::getNameFromId('User', $userId);
    $creationDate=$bf->creationDate;
    echo '<tr>';
    if (!$print) {
      echo '<td class="linkData" style="text-align:center;width:5%;white-space:nowrap;">';
      if ($canUpdate) {
        // ADD qCazelles - Business Feature (Correction) - Ticket #96
        echo '  <a onClick="editBusinessFeature('.htmlEncode($bf->id).');" '.'title="'.i18n('editBusinessFeature').'" > '.formatSmallButton('Edit').'</a>';
        // END ADD qCazelles - Business Feature (Correction) - Ticket #96
        // CHANGE qCazelles - Business Feature (Correction) - Ticket #96
        // Old
        // echo ' <a onClick="removeBusinessFeature(' . "'" . htmlEncode($bf->id) . "','" . get_class($bf) . "'" . ');" '
        // .'title="' . i18n('removeBusinessFeature') . '" > '.formatSmallButton('Remove').'</a>';
        // New
        $crit=array('idBusinessFeature'=>$bf->id);
        $ticket=new Ticket();
        $listBfTicket=$ticket->getSqlElementsFromCriteria($crit);
        if (count($listBfTicket)==0) {
          echo '  <a onClick="removeBusinessFeature('."'".htmlEncode($bf->id)."','".get_class($bf)."'".');" '.'title="'.i18n('removeBusinessFeature').'" > '.formatSmallButton('Remove').'</a>';
        }
        // END CHANGE qCazelles - Business Feature (Correction) - Ticket #96
      }
      echo '</td>';
    }
    echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr>';
    // echo '<td>IMG</td><td style="vertical-align:top">&nbsp;'.'#' . $bf->id.'</td></tr></table>';
    echo '<td style="vertical-align:top">&nbsp;'.'#'.$bf->id.'</td></tr></table>';
    echo '</td>';
    echo '<td class="linkData" style="cursor: pointer;">';
    echo htmlEncode($bf->name);
    echo formatUserThumb($userId, $userName, 'Creator');
    echo formatDateThumb($creationDate, null);
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="BusinessFeatureSectionCount" type="hidden" value="'.count($list).'" />';
  }
}
// END ADD qCazelles

// ADD qCazelles - Lang-Context
function drawLanguageSection($obj, $refresh=false) {
  $crit=array();
  $scope=get_class($obj);
  if ($scope=='Product' or $scope=='Component') {
    $crit['idProduct']=$obj->id;
    // $crit['scope']=$scope; // useless because an idProduct can't be the same for Component and Product
    $langClass='ProductLanguage';
  } else if (get_class($obj)=='ProductVersion' or get_class($obj)=='ComponentVersion') {
    $crit['idVersion']=$obj->id;
    $crit['scope']=str_replace('Version', '', $scope);
    $langClass='VersionLanguage';
  } else {
    errorLog("drawLanguageSection for item not taken into account : ".get_class($obj));
  }
  $langsProduct=new $langClass();
  $list=$langsProduct->getSqlElementsFromCriteria($crit);
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="linkHeader" style="width:5%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addProductLanguage();" title="'.i18n('addProductLanguage').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  $listClass='Language';
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n($listClass).'</td>';
  echo '<td class="linkHeader" style="width:80%">'.i18n('colName').'</td>';
  echo '</tr>';
  
  foreach ($list as $lang) { // $lang is ProductLanguage
    $langObj=new Language($lang->idLanguage);
    $userId=$lang->idUser;
    $userName=SqlList::getNameFromId('User', $userId);
    $creationDate=$lang->creationDate;
    $canGoto=(securityCheckDisplayMenu(null, get_class($langObj)) and securityGetAccessRightYesNo('menu'.get_class($langObj), 'read', $langObj)=="YES")?true:false;
    echo '<tr>';
    $classLangName=i18n(get_class($langObj));
    if (!$print) {
      echo '<td class="linkData" style="text-align:center;width:5%;white-space:nowrap;">';
      if ($canUpdate) {
        echo '  <a onClick="editProductLanguage('.htmlEncode($lang->id).');" '.'title="'.i18n('editProductLanguage').'" > '.formatSmallButton('Edit').'</a>';
        echo '  <a onClick="removeProductLanguage('."'".htmlEncode($lang->id)."','".get_class($obj)."'".');" '.'title="'.i18n('removeProductLanguage').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr>';
    echo '<td>'.formatIcon(get_class($langObj), 16).'</td><td style="vertical-align:top">&nbsp;'.'#'.$langObj->id.'</td></tr></table>';
    echo '</td>';
    $goto="";
    if (!$print and $canGoto) {
      $goto=' onClick="gotoElement('."'".get_class($langObj)."','".htmlEncode($langObj->id)."'".');" style="cursor: pointer;" ';
    }
    echo '<td class="linkData" '.$goto.' style="position:relative;">';
    echo htmlEncode($langObj->name);
    echo formatUserThumb($userId, $userName, 'Creator');
    echo formatDateThumb($creationDate, null);
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="ProductLanguageSectionCount" type="hidden" value="'.count($list).'" />';
  }
}

function drawContextSection($obj, $refresh=false) {
  $crit=array();
  $scope=get_class($obj);
  if ($scope=='Product' or $scope=='Component') {
    $crit['idProduct']=$obj->id;
    $crit['scope']=$scope;
    $langClass='ProductContext';
  } else if (get_class($obj)=='ProductVersion' or get_class($obj)=='ComponentVersion') {
    $crit['idVersion']=$obj->id;
    $crit['scope']=str_replace('Version', '', $scope);
    $langClass='VersionContext';
  } else {
    errorLog("drawLanguageSection for item not taken into account : ".get_class($obj));
  }
  $contextProduct=new $langClass();
  $list=$contextProduct->getSqlElementsFromCriteria($crit);
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="linkHeader" style="width:5%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addProductContext();" title="'.i18n('addProductContext').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  $listClass='Context';
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n($listClass).'</td>';
  echo '<td class="linkHeader" style="width:80%">'.i18n('colName').'</td>';
  echo '</tr>';
  foreach ($list as $context) { // $context is a ProductContext
    $contextObj=new Context($context->idContext);
    $userId=$context->idUser;
    $userName=SqlList::getNameFromId('User', $userId);
    $creationDate=$context->creationDate;
    $canGoto=(securityCheckDisplayMenu(null, get_class($contextObj)) and securityGetAccessRightYesNo('menu'.get_class($contextObj), 'read', $contextObj)=="YES")?true:false;
    echo '<tr>';
    $classLangName=i18n(get_class($contextObj));
    if (!$print) {
      echo '<td class="linkData" style="text-align:center;width:5%;white-space:nowrap;">';
      if ($canUpdate) {
        echo '  <a onClick="editProductContext('.htmlEncode($context->id).');" '.'title="'.i18n('editProductContext').'" > '.formatSmallButton('Edit').'</a>';
        echo '  <a onClick="removeProductContext('."'".htmlEncode($context->id)."','".get_class($obj)."'".');" '.'title="'.i18n('removeProductContext').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr>';
    echo '<td>'.formatIcon(get_class($contextObj), 16).'</td><td style="vertical-align:top">&nbsp;'.'#'.$contextObj->id.'</td></tr></table>';
    echo '</td>';
    $goto="";
    if (!$print and $canGoto) {
      $goto=' onClick="gotoElement('."'".get_class($contextObj)."','".htmlEncode($contextObj->id)."'".');" style="cursor: pointer;" ';
    }
    echo '<td class="linkData" '.$goto.' style="position:relative;">';
    echo htmlEncode($contextObj->name);
    echo formatUserThumb($userId, $userName, 'Creator');
    echo formatDateThumb($creationDate, null);
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="ProductContextSectionCount" type="hidden" value="'.count($list).'" />';
  }
}
// END qCazelles - Lang-Context

// ADD qCazelles - Manage ticket at customer level - Ticket #87
function drawTicketsList($obj, $refresh=false) {
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan="4">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  $listClass='Ticket';
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n($listClass).'</td>';
  echo '<td class="linkHeader" style="width:60%">'.i18n('colName').'</td>';
  echo '<td class="linkHeader" style="width:40%">'.i18n('colIdStatus').'</td>';
  echo '</tr>';
  if (!$obj->id) {
    $list=array();
  } else if (get_class($obj)=='Contact') {
    $crit=array('idContact'=>$obj->id, 'idle'=>'0');
    $ticket=new Ticket();
    $list=$ticket->getSqlElementsFromCriteria($crit);
  } else if (get_class($obj)=='Client') {
    $contact=new Contact();
    $crit=array('idClient'=>$obj->id);
    $listContacts=$contact->getSqlElementsFromCriteria($crit);
    if (count($listContacts)>0) {
      $clauseWhere='idContact in (0';
      foreach ($listContacts as $contact) {
        $clauseWhere.=",".$contact->id;
      }
      $clauseWhere.=') and idle=0';
    } else {
      $clauseWhere='1=0';
    }
    $ticket=new Ticket();
    if (property_exists('Ticket', 'idClient')) {
      $clauseWhere='idle=0 and (idClient='.Sql::fmtId($obj->id).' ';
      if (property_exists('Ticket', '_OtherClient')) {
        $otherclient=new OtherClient();
        $clauseWhere.=" or exists (select 'x' from ".$otherclient->getDatabaseTableName()." other "." where other.refType='Ticket' and other.refId=".$ticket->getDatabaseTableName().".id and other.idClient=".Sql::fmtId($obj->id).")";
      }
      $clauseWhere.=')';
    }
    $list=$ticket->getSqlElementsFromCriteria(null, false, $clauseWhere);
  } else if (get_class($obj)=='Product' or get_class($obj)=='Component') {
    $crit=array('id'.get_class($obj)=>$obj->id);
    $ticket=new Ticket();
    $list=$ticket->getSqlElementsFromCriteria($crit);
  } else if (get_class($obj)=='ProductVersion' or get_class($obj)=='ComponentVersion') {
    $crit=array('idTarget'.get_class($obj)=>$obj->id);
    $ticket=new Ticket();
    $list=$ticket->getSqlElementsFromCriteria($crit);
  }
  if (!isset($list)) $list=array();
  
  foreach ($list as $ticket) {
    $canGoto=(securityCheckDisplayMenu(null, $listClass) and securityGetAccessRightYesNo('menu'.$listClass, 'read', $ticket)=="YES")?true:false;
    echo '<tr>';
    $classCompName=i18n($listClass);
    echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr><td>'.formatIcon($listClass, 16).'</td><td style="vertical-align:top">&nbsp;'.'#'.$ticket->id.'</td></tr></table>';
    echo '</td>';
    $goto="";
    if (!$print and $canGoto) {
      $goto=' onClick="gotoElement('."'".$listClass."','".htmlEncode($ticket->id)."'".');" style="cursor: pointer;" ';
    }
    echo '<td class="linkData" '.$goto.' style="position:relative;">';
    echo htmlEncode($ticket->name);
    echo '</td><td class="linkData">';
    // $objStatus=new $statusClass($linkObj->$idStatus);
    echo colorNameFormatter(SqlList::getNameFromId('Status', $ticket->idStatus)."#split#".SqlList::getFieldFromId('Status', $ticket->idStatus, 'color')).'</td>';
    echo '</td>';
    echo '</tr>';
  }
  
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="TicketSectionCount" type="hidden" value="'.count($list).'" />';
  }
}
// END ADD qCazelles - Manage ticket at customer level - Ticket #87

// Add mOlives - ticket 215 - 09/05/2018
function drawActivityList($obj, $refresh=false) {
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan="4">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  $listClass='Activity';
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n($listClass).'</td>';
  echo '<td class="linkHeader" style="width:45%">'.i18n('colName').'</td>';
  echo '<td class="linkHeader" style="width:15%">'.i18n('colProgress').'</td>';
  echo '<td class="linkHeader" style="width:20%">'.i18n('colIdStatus').'</td>';
  echo '</tr>';
  if (!$obj->id) {
    $list=array();
  } else if (get_class($obj)=='ComponentVersion') {
    $crit=array('idTarget'.get_class($obj)=>$obj->id);
    $activity=new Activity();
    $list=$activity->getSqlElementsFromCriteria($crit);
  }
  if (!isset($list)) $list=array();
  $showClosedActivity=Parameter::getUserParameter('showClosedActivity');
  foreach ($list as $activity) {
    if ($showClosedActivity==1 or ($showClosedActivity==0 and $activity->idle==0)) {
      $canGoto=(securityCheckDisplayMenu(null, $listClass) and securityGetAccessRightYesNo('menu'.$listClass, 'read', $activity)=="YES")?true:false;
      echo '<tr>';
      $classCompName=i18n($listClass);
      echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr><td>'.formatIcon($listClass, 16).'</td><td style="vertical-align:top">&nbsp;'.'#'.$activity->id.'</td></tr></table>';
      echo '</td>';
      $goto="";
      if (!$print and $canGoto) {
        $goto=' onClick="gotoElement('."'".$listClass."','".htmlEncode($activity->id)."'".');" style="cursor: pointer;" ';
      }
      echo '<td class="linkData" '.$goto.' style="position:relative;">';
      echo htmlEncode($activity->name);
      echo '</td><td class="linkData">';
      $pe=new PlanningElement();
      $crit=array('refId'=>$activity->id);
      $arrayActivity=$pe->getSqlElementsFromCriteria($crit);
      $act=reset($arrayActivity);
      $activityProgress=$act->progress;
      
      echo progressFormatter($activityProgress, null);
      echo '</td><td class="linkData">';
      
      echo colorNameFormatter(SqlList::getNameFromId('Status', $activity->idStatus)."#split#".SqlList::getFieldFromId('Status', $activity->idStatus, 'color')).'</td>';
      echo '</td>';
      echo '</tr>';
    }
  }
  
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="ActivitySectionCount" type="hidden" value="'.count($list).'" />';
  }
}
// END mOlives - ticket 215 - 09/05/2018
function drawVersionStructureFromObject($obj, $refresh=false, $way, $item) {
  $crit=array();
  if ($way=='composition') {
    $crit['idProductVersion']=$obj->id;
  } else if ($way=='structure') {
    $crit['idComponentVersion']=$obj->id;
  } else {
    errorLog("unknown way=$way in drawVersionStructureFromObject()");
  }
  $pcs=new ProductVersionStructure();
  $list=$pcs->getSqlElementsFromCriteria($crit);
  // ADD qCazelles - Sort version composition-structure - Ticket 142
  if (Parameter::getGlobalParameter('sortCompositionStructure')=='YES') {
    if ($way=='composition') {
      SqlElement::$_cachedQuery['ComponentVersion']=array(); // PBE : performance improvments
      SqlElement::$_cachedQuery['ComponentVersionType']=array(); // PBE : performance improvments
      usort($list, "ProductVersionStructure::sortComponentVersionListOnType");
    } else if ($way=='structure') {
      SqlElement::$_cachedQuery['Version']=array(); // PBE : performance improvments
      SqlElement::$_cachedQuery['Type']=array(); // PBE : performance improvments
      usort($list, "ProductVersionStructure::sortVersionListOnType");
    }
  }
  // END ADD qCazelles - Sort version composition-structure - Ticket 142
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="linkHeader" style="width:5%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addProductVersionStructure(\''.$way.'\');" title="'.i18n('addProductVersionStructure').'" > '.formatSmallButton('Add').'</a>';
      if ($way=='composition' and count($list)>0) {
        echo '<a onClick="upgradeProductVersionStructure(null,false);" title="'.i18n('upgradeProductVersionStructure').'" > '.formatSmallButton('Switch').'</a>';
      }
    }
    echo '</td>';
  }
  $listClass=($item=='ProductVersion')?'ComponentVersion':(($way=='structure')?'Version':'ComponentVersion');
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n($listClass).'</td>';
  echo '<td class="linkHeader" style="width:80%">'.i18n('colName').'</td>';
  echo '</tr>';
  foreach ($list as $comp) {
    $compObj=null;
    if ($way=='structure') {
      $compObj=new Version($comp->idProductVersion);
    } else {
      $compObj=new Version($comp->idComponentVersion);
    }
    if ($compObj->scope=='Product') $compObj=new ProductVersion($compObj->id);
    else $compObj=new ComponentVersion($compObj->id);
    $userId=$comp->idUser;
    $userName=SqlList::getNameFromId('User', $userId);
    $creationDate=$comp->creationDate;
    $canGoto=(securityCheckDisplayMenu(null, get_class($compObj)) and securityGetAccessRightYesNo('menu'.get_class($compObj), 'read', $compObj)=="YES")?true:false;
    echo '<tr>';
    $classCompName=i18n(get_class($compObj));
    if (!$print) {
      echo '<td class="linkData" style="text-align:center;width:5%;white-space:nowrap;">';
      if ($canUpdate) {
        echo '  <a onClick="editProductVersionStructure(\''.$way.'\','.htmlEncode($comp->id).');" '.'title="'.i18n('editProductStructure').'" > '.formatSmallButton('Edit').'</a>';
        
        echo '  <a onClick="removeProductVersionStructure('."'".htmlEncode($comp->id)."','".get_class($compObj)."','".htmlEncode($compObj->id)."','".$classCompName."'".');" '.'title="'.i18n('removeProductStructure').'" > '.formatSmallButton('Remove').'</a>';
        if ($way=='composition') {
          echo '<a onClick="upgradeProductVersionStructure(\''.$comp->id.'\',false);" title="'.i18n('upgradeProductVersionStructureSingle').'" > '.formatSmallButton('Switch').'</a>';
        }
      }
      echo '</td>';
    }
    // echo '<td class="linkData" style="white-space:nowrap;width:' . (($print)?'20':'15') . '%"><img src="css/images/icon'.get_class($compObj).'16.png" />&nbsp;'.$classCompName .' #' . $compObj->id;
    echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr><td>'.formatIcon(get_class($compObj), 16).'</td><td style="vertical-align:top">&nbsp;'.'#'.$compObj->id.'</td></tr></table>';
    echo '</td>';
    $goto="";
    if (!$print and $canGoto) {
      $goto=' onClick="gotoElement('."'".get_class($compObj)."','".htmlEncode($compObj->id)."'".');" style="cursor: pointer;" ';
    }
    echo '<td class="linkData" '.$goto.' style="position:relative;">';
    echo htmlEncode($compObj->name);
    echo formatUserThumb($userId, $userName, 'Creator');
    echo formatDateThumb($creationDate, null);
    echo formatCommentThumb($comp->comment);
    // ADD qCazelles - dateComposition
    
    if (Parameter::getGlobalParameter('displayMilestonesStartDelivery')=='YES' and property_exists($compObj, 'realDeliveryDate')) {
      if ($compObj->realDeliveryDate) {
        $deliveryDate=$compObj->realDeliveryDate;
      } elseif ($compObj->plannedDeliveryDate) {
        $deliveryDate=$compObj->plannedDeliveryDate;
      } elseif ($compObj->initialDeliveryDate) {
        $deliveryDate=$compObj->initialDeliveryDate;
      }
      
      $errorDatesDelivery=false;
      if ($way=='composition') {
        // CHANGE qCazelles - Correction red dates - Ticket 186
        // Old
        // if (isset ( $deliveryDate ) and $obj->plannedDeliveryDate and $obj->plannedDeliveryDate < $deliveryDate) {
        // New
        if (isset($deliveryDate) and (($obj->isDelivered and $obj->realDeliveryDate<$deliveryDate) or (!$obj->isDelivered and $obj->plannedDeliveryDate and $obj->plannedDeliveryDate<$deliveryDate))) {
          // END CHANGE qCazelles - Correction red dates - Ticket 186
          $errorDatesDelivery=true;
        }
      } elseif ($way=='structure') {
        // CHANGE qCazelles - Correction red dates - Ticket 186
        // Old
        // if (isset ( $deliveryDate ) and $obj->plannedDeliveryDate and $obj->plannedDeliveryDate > $deliveryDate) {
        // New
        if (isset($deliveryDate) and (($obj->isDelivered and $obj->realDeliveryDate>$deliveryDate) or (!$obj->isDelivered and $obj->plannedDeliveryDate and $obj->plannedDeliveryDate>$deliveryDate))) {
          // END CHANGE qCazelles - Correction red dates - Ticket 186
          $errorDatesDelivery=true;
        }
      }
      
      if (isset($deliveryDate)) {
        echo '<br />'.(($errorDatesDelivery)?'<span style="color: red;">':'').htmlFormatDate($deliveryDate).(($errorDatesDelivery)?'</span>':'').' ';
        // ADD qCazelles - DeliveryDateXLS - Ticket #126
        unset($deliveryDate);
        // END ADD qCazelles - DeliveryDateXLS - Ticket #126
      }
    }
    
    // END ADD qCazelles - dateComposition
    
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="ProductVersionStructureSectionCount" type="hidden" value="'.count($list).'" />';
  }
}

// ADD qCazelles - Version compatibility
function drawVersionCompatibility($obj, $refresh=false) {
  global $idObj;
  $vcs=new VersionCompatibility();
  $crit=array();
  $crit['idVersionA']=$obj->id;
  $list=$vcs->getSqlElementsFromCriteria($crit);
  
  $crit=array();
  $crit['idVersionB']=$obj->id;
  foreach ($vcs->getSqlElementsFromCriteria($crit) as $vc) {
    $list[]=$vc;
  }
  $idObj=$obj->id;
  
  SqlElement::$_cachedQuery['ProductVersion']=array(); // PBE : performance improvments
  usort($list, "ProductVersionStructure::sortProductVersionList");
  
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if (!$refresh) echo '<tr><td colspan="2">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="linkHeader" style="width:10%;white-space:nowrap">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addVersionCompatibility();" title="'.i18n('addVersionCompatibility').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '<button dojoType="dijit.form.Button" title="'.i18n('exportVersionCompatibilities').'" iconClass="dijitButtonIcon dijitButtonIconCsv" class="roundedButtonSmall" style="border:0">';
    echo '<script type="dojo/connect" event="onClick" args="evt">';
    $page='../report/productVersionCompatibility.php?objectClass='.get_class($obj).'&objectId='.$obj->id;
    echo "var url='$page';";
    echo 'url+="&format=csv";';
    echo 'showPrint(url, null, null, "csv", "P");';
    echo '</script>';
    echo '</button>';
    echo '</td>';
  }
  $listClass='ProductVersion';
  echo '<td class="linkHeader" style="width:'.(($print)?'20':'15').'%">'.i18n($listClass).'</td>';
  echo '<td class="linkHeader" style="width:80%">'.i18n('colName').'</td>';
  echo '</tr>';
  
  foreach ($list as $vc) {
    $userId=$vc->idUser;
    $userName=SqlList::getNameFromId('User', $userId);
    $creationDate=$vc->creationDate;
    if ($vc->idVersionA==$obj->id) {
      $vcObj=new ProductVersion($vc->idVersionB);
    } else {
      $vcObj=new ProductVersion($vc->idVersionA);
    }
    $canGoto=(securityCheckDisplayMenu(null, get_class($vcObj)) and securityGetAccessRightYesNo('menu'.get_class($vcObj), 'read', $vcObj)=="YES")?true:false;
    echo '<tr>';
    $classVersionName=i18n(get_class($vcObj));
    if (!$print) {
      echo '<td class="linkData" style="text-align:center;width:5%;white-space:nowrap;">';
      if ($canUpdate) {
        echo '  <a onClick="removeVersionCompatibility('."'".htmlEncode($vc->id)."','".get_class($vcObj)."','".htmlEncode($vcObj->id)."','".$classVersionName."'".');" '.'title="'.i18n('removeVersionCompatibility').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="linkData" style="white-space:nowrap;width:'.(($print)?'20':'15').'%"><table><tr><td>'.formatIcon(get_class($vcObj), 16).'</td><td style="vertical-align:top">&nbsp;'.'#'.$vcObj->id.'</td></tr></table>';
    echo '</td>';
    $goto="";
    if (!$print and $canGoto) {
      $goto=' onClick="gotoElement('."'".get_class($vcObj)."','".htmlEncode($vcObj->id)."'".');" style="cursor: pointer;" ';
    }
    echo '<td class="linkData" '.$goto.' style="position:relative;">';
    echo htmlEncode($vcObj->name);
    echo formatUserThumb($userId, $userName, 'Creator');
    echo formatDateThumb($creationDate, null);
    echo formatCommentThumb($vc->comment);
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="VersionCompatibilitySectionCount" type="hidden" value="'.count($list).'" />';
  }
}
// END ADD qCazelles - Version compatibility

// ADD qCazelles
function drawDeliverysFromObject($obj) {
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  
  echo '<tr>';
  echo '<td class="linkHeader" style="width:'.(($print)?'10':'5').'%">'.i18n('Delivery').'</td>';
  echo '<td class="linkHeader" style="width:40%">'.i18n('colName').'</td>';
  echo '<td class="linkHeader" style="width:50%">'.i18n('colIdDeliveryStatus').'</td>';
  echo '</tr>';
  
  $delivery=new Delivery();
  $list=$delivery->getSqlElementsFromCriteria(array('idProductVersion'=>$obj->id), false, null, 'creationDateTime desc');
  
  $userId=$delivery->idUser;
  $user=new User($userId);
  $userName=$user->name;
  
  foreach ($list as $delivery) {
    $status=new Status($delivery->idStatus);
    echo '<tr onClick="gotoElement('."'Delivery','".htmlEncode($delivery->id)."'".');" style="cursor: pointer;">';
    echo '<td class="linkData">#'.htmlEncode($delivery->id).'</td>';
    echo '<td class="linkData">'.htmlEncode($delivery->name).'</td>';
    echo '<td class="linkData">'.htmlEncode($status->name);
    echo formatUserThumb($userId, $userName, 'Creator');
    
    // CHANGE qCazelles - Ticket #170
    // Old
    // if ($delivery->idle) {
    // echo formatDateThumb($delivery->idleDateTime, null);
    // } else if ($delivery->done) {
    // echo formatDateThumb($delivery->doneDateTime, null);
    // } else if ($delivery->handled) {
    // echo formatDateThumb($delivery->handledDateTime, null);
    // } else if ($delivery->plannedDate) {
    // echo formatDateThumb($delivery->plannedDate, null);
    // } else {
    // echo formatDateThumb($delivery->creationDateTime, null);
    // }
    // New
    if ($delivery->idle) {
      echo formatDateThumbWithText($delivery->idleDateTime, 'thumbIdleDateTitle');
    } else if ($delivery->cancelled) {
      echo formatDateThumbWithText(null, 'cancelled');
    } else if ($delivery->done) {
      echo formatDateThumbWithText($delivery->doneDateTime, 'thumbDoneDateTitle');
    } else if ($delivery->handled) {
      echo formatDateThumbWithText($delivery->handledDateTime, 'thumbHandledDateTitle');
    } else if ($delivery->plannedDate) {
      echo formatDateThumbWithText($delivery->plannedDate, 'thumbPlannedDateTitle');
    } else {
      echo formatDateThumb($delivery->creationDateTime, null);
    }
    // END CHANGE qCazelles - Ticket #170
    echo '</td>';
    echo '</tr>';
  }
}
// END ADD qCazelles
function drawApproverFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="dependencyHeader" style="width:5%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addApprover();" title="'.i18n('addApprover').'" class="roundedButtonSmall"> '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="dependencyHeader" style="width:'.(($print)?'10':'5').'%">'.i18n('colId').'</td>';
  echo '<td class="dependencyHeader" style="width:40%">'.i18n('colName').'</td>';
  echo '<td class="dependencyHeader" style="width:50%">'.i18n('colIdStatus').'</td>';
  echo '</tr>';
  if ($obj and get_class($obj)=='Document') {
    $docVers=new DocumentVersion($obj->idDocumentVersion);
  }
  foreach ($list as $app) {
    $appName=SqlList::getNameFromId('Affectable', $app->idAffectable);
    echo '<tr>';
    if (!$print) {
      echo '<td class="dependencyData" style="text-align:center;">';
      if ($canUpdate) {
        echo '  <a onClick="removeApprover('."'".htmlEncode($app->id)."','".$appName."'".');" title="'.i18n('removeApprover').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="dependencyData">#'.htmlEncode($app->id).'</td>';
    echo '<td class="dependencyData">'.htmlEncode($appName).'</td>';
    echo '<td class="dependencyData">';
    $approved=0;
    $compMsg="";
    $date="";
    $approverId=null;
    if ($obj and get_class($obj)=='Document') {
      $crit=array('refType'=>'DocumentVersion', 'refId'=>$obj->idDocumentVersion, 'idAffectable'=>$app->idAffectable);
      $versApp=SqlElement::getSingleSqlElementFromCriteria('Approver', $crit);
      if ($versApp->id) {
        $approved=$versApp->approved;
        $compMsg=' '.$docVers->name;
        $date=" (".htmlFormatDateTime($versApp->approvedDate, false).")";
        $approverId=$versApp->id;
      }
    } else {
      $approved=$app->approved;
      $approverId=$app->id;
      $date=" (".htmlFormatDateTime($app->approvedDate, false).")";
    }
    if ($approved) {
      echo '<img src="../view/img/check.png" height="12px"/>&nbsp;';
      echo i18n("approved").$compMsg.$date;
    } else {
      echo i18n("notApproved").$compMsg;
      if ($user->id==$app->idAffectable and !$print and $versApp->id) {
        echo '&nbsp;&nbsp;<button dojoType="dijit.form.Button" showlabel="true" >';
        echo i18n('approveNow');
        echo '  <script type="dojo/connect" event="onClick" args="evt">';
        echo '   approveItem('.$approverId.');';
        echo '  </script>';
        echo '</button>';
      }
    }
    echo '</td>';
    echo '</tr>';
  }
  echo '</table></td></tr>';
}

function drawDependenciesFromObject($list, $obj, $depType, $refresh=false) {
  global $cr, $print, $user, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  $canEdit=$canUpdate;
  if (get_class($obj)=="Term" or get_class($obj)=="Requirement" or get_class($obj)=="TestCase") {
    $canEdit=false;
  }
  if (get_class($obj)=="Term") {
    if ($obj->idBill) $canUpdate=false;
  }
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$refresh) echo '<tr><td colspan=2 style="width:100%;">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="dependencyHeader" style="width:10%">';
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addDependency('."'".$depType."'".');" title="'.i18n('addDependency'.$depType).'"> '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="dependencyHeader" style="width:'.(($print)?'30':'20').'%">'.i18n('colElement').'</td>';
  echo '<td class="dependencyHeader" style="width:55%">'.i18n('colName').'</td>';
  echo '<td class="dependencyHeader" style="width:15%">'.i18n('colIdStatus').'</td>';
  echo '</tr>';
  foreach ($list as $dep) {
    $depObj=null;
    if ($dep->predecessorRefType==get_class($obj) and $dep->predecessorRefId==$obj->id) {
      $depObj=new $dep->successorRefType($dep->successorRefId);
      // $depType="Successor";
    } else {
      $depObj=new $dep->predecessorRefType($dep->predecessorRefId);
      // $depType="Predecessor";
    }
    echo '<tr>';
    if (!$print) {
      echo '<td class="dependencyData" style="text-align:center;white-space:nowrap;">';
      if ($canEdit) {
        echo '  <a onClick="editDependency(\''.$depType.'\',\''.htmlEncode($dep->id).'\',\''.SqlList::getIdFromName('Dependable', i18n(get_class($depObj))).'\',\''.get_class($depObj).'\',\''.htmlEncode($depObj->id).'\',\''.htmlEncode($dep->dependencyDelay).'\',\''.$dep->dependencyType.'\');" title="'.i18n('editDependency'.$depType).'" > '.formatSmallButton('Edit').'</a>';
      }
      if ($canUpdate) {
        echo '  <a onClick="removeDependency('."'".htmlEncode($dep->id)."','".get_class($depObj)."','".htmlEncode($depObj->id)."'".');" '.'title="'.i18n('removeDependency'.$depType).'"/> '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="dependencyData" style="white-space:nowrap"><table><tr><td>'.formatIcon(get_class($depObj), 16).'</td><td>&nbsp;'.i18n(get_class($depObj)).' #'.htmlEncode($depObj->id).'</td></tr></table></td>';
    echo '<td class="dependencyData"';
    $goto="";
    if (securityCheckDisplayMenu(null, get_class($depObj)) and securityGetAccessRightYesNo('menu'.get_class($depObj), 'read', $depObj)=="YES") {
      $goto=' onClick="gotoElement('."'".get_class($depObj)."','".htmlEncode($depObj->id)."'".');" style="cursor: pointer;" ';
    }
    if (!$print) {
      echo $goto;
    }
    echo '>'.htmlEncode($depObj->name);
    // //KEVIN TICKET #2038
    $titleType=(substr($dep->dependencyType, 0, 1)=='E')?i18n('colEnd'):i18n('colStart');
    $titleType.='-';
    $titleType.=(substr($dep->dependencyType, -1)=='E')?i18n('colEnd'):i18n('colStart');
    echo '<img style="float:right;margin: 0px 2px;" title="'.$titleType.'" src="../view/css/images/dependency_'.$dep->dependencyType.'.png"/>';
    if ($dep->dependencyDelay!=0 and $canEdit) {
      echo '<span style="float:right;background-color:#FFF8DC; color:#696969; border:1px solid #A9A9A9;" title="'.i18n("colDependencyDelay").'">&nbsp;'.htmlEncode($dep->dependencyDelay).'&nbsp;'.i18n('shortDay').'&nbsp;</span>';
    }
    if ($dep->comment) {
      echo '<div style="float:right;margin: 0px 2px;">'.formatCommentThumb($dep->comment).'</div>';
    }
    echo '</td>';
    if (property_exists($depObj, 'idStatus')) {
      $objStatus=new Status($depObj->idStatus);
    } else {
      $objStatus=new Status();
    }
    // $color=$objStatus->color;
    // $foreColor=getForeColor($color);
    // echo '<td class="dependencyData"><table><tr><td style="background-color: ' . htmlEncode($objStatus->color) . '; color:' . $foreColor . ';">' . htmlEncode($objStatus->name) . '</td></tr></table></td>';
    // echo '<td class="dependencyData" style="background-color: ' . htmlEncode($objStatus->color) . '; color:' . $foreColor . ';">' . htmlEncode($objStatus->name) . '</td>';
    echo '<td class="dependencyData" style="width:15%">'.colorNameFormatter($objStatus->name."#split#".$objStatus->color).'</td>';
    echo '</tr>';
  }
  echo '</table>';
  if (!$refresh) echo '</td></tr>';
  if (!$print) {
    echo '<input id="'.$depType.'DependencySectionCount" type="hidden" value="'.count($list).'" />';
  }
}

function drawAssignmentsFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail, $section, $collapsedList, $widthPct, $outMode, $profile;
  if ($comboDetail) {
    return;
  }
  $pluginObjectClass='Assignment';
  $tableObject=$list;
  $lstPluginEvt=Plugin::getEventScripts('list', $pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $list=$tableObject;
  $habil=SqlElement::getSingleSqlElementFromCriteria('HabilitationOther', array('idProfile'=>$profile, 'scope'=>'assignmentView'));
  if ($habil and $habil->rightAccess!=1) {
    return;
  }
  // $section='Assignment';
  // startTitlePane(get_class ( $obj ), $section, $collapsedList, $widthPct, $print, $outMode, "yes", $nbCol);
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  $habil=SqlElement::getSingleSqlElementFromCriteria('HabilitationOther', array('idProfile'=>$profile, 'scope'=>'assignmentEdit'));
  if ($habil and $habil->rightAccess!=1) {
    $canUpdate=false;
  }
  $pe=new PlanningElement();
  $pe->setVisibility();
  $workVisible=($pe->_workVisibility=='ALL')?true:false;
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  $planningMode=null;
  $peName=get_class($obj).'PlanningElement';
  if (property_exists($obj, $peName)) {
    $idPm=$obj->$peName->idPlanningMode;
    $pmObj=new PlanningMode($idPm);
    $planningMode=$pmObj->code;
  }
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (!$print and $canUpdate) {
    echo '<td class="assignHeader" style="width:10%;vertical-align:middle;">';
    if ($obj->id!=null and !$print and $canUpdate and !$obj->idle and $workVisible) {
      echo '<a onClick="addAssignment(\''.Work::displayShortWorkUnit().'\',\''.Work::getWorkUnit().'\',\''.Work::getHoursPerDay().'\');" ';
      echo ' title="'.i18n('addAssignment').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" style="width:'.(($print)?'40':'30').'%">'.i18n('colIdResource').'</td>';
  echo '<td class="assignHeader" style="width:15%" >'.i18n('colRateOrEtp').'</td>';
  if ($workVisible) {
    echo '<td class="assignHeader" style="width:15%">'.i18n('colAssigned').' ('.Work::displayShortWorkUnit().')'.'</td>';
    echo '<td class="assignHeader"style="width:15%">'.i18n('colReal').' ('.Work::displayShortWorkUnit().')'.'</td>';
    echo '<td class="assignHeader" style="width:15%">'.i18n('colLeft').' ('.Work::displayShortWorkUnit().')'.'</td>';
  }
  echo '</tr>';
  $fmt=new NumberFormatter52($browserLocale, NumberFormatter52::DECIMAL);
  foreach ($list as $assignment) {
    echo '<tr>';
    $isResource=true;
    $resName=SqlList::getNameFromId('Resource', $assignment->idResource);
    if ($resName==$assignment->idResource) {
      $affName=SqlList::getNameFromId('Affectable', $assignment->idResource);
      if ($affName!=$resName) {
        $isResource=false;
        $resName=$affName;
      }
    }
    if (!$print and $canUpdate) {
      echo '<td class="assignData" style="width:10%;text-align:center;white-space:nowrap;vertical-align:middle">';
      if ($canUpdate and !$print and $workVisible) {
        echo '  <a onClick="editAssignment('."'".htmlEncode($assignment->id)."'".",'".htmlEncode($assignment->idResource)."'".",'".htmlEncode($assignment->idRole)."'".",'".($assignment->dailyCost*100)."'".",'".htmlEncode($assignment->rate)."'".",'".(Work::displayWork($assignment->assignedWork)*100)."'".",'".(Work::displayWork($assignment->realWork)*100)."'".",'".(Work::displayWork($assignment->leftWork)*100)."'".",'".Work::displayShortWorkUnit()."'".",".$assignment->optional.');" '.'title="'.i18n('editAssignment').'" > '.formatSmallButton('Edit').'</a>';
        echo '<textarea style="display:none" id="comment_assignment_'.htmlEncode($assignment->id).'" >'.htmlEncode($assignment->comment)."</textarea>";
      }
      if ($assignment->realWork==0 and $canUpdate and !$print and $workVisible) {
        echo '  <a onClick="removeAssignment('."'".htmlEncode($assignment->id)."','".(Work::displayWork($assignment->realWork)*100)."','".htmlEncode($resName, 'quotes')."'".');" '.'title="'.i18n('removeAssignment').'" > '.formatSmallButton('Remove').'</a>';
      }
      if ($canUpdate and !$print and $workVisible) {
        echo '  <a onClick="divideAssignment('.htmlEncode($assignment->id).',\''.Work::displayShortWorkUnit().'\');" '.'title="'.i18n('divideAssignment').'" > '.formatSmallButton('Split').'</a>';
        echo '</td>';
      }
    }
    echo '<td class="assignData" style="width:'.(($print)?'40':'30').'%;vertical-align:middle">';
    echo '<table width="100%"><tr>';
    $goto="";
    $resource=new ResourceAll($assignment->idResource);
    if ($resource->isResourceTeam) {
      if (securityCheckDisplayMenu(null, 'ResourceTeam') and securityGetAccessRightYesNo('menuResourceTeam', 'read', '')=="YES") {
        $goto=' onClick="gotoElement(\'ResourceTeam\',\''.htmlEncode($assignment->idResource).'\');" style="cursor: pointer;" ';
      }
    } else {
      if (!$print and $isResource and securityCheckDisplayMenu(null, 'Resource') and securityGetAccessRightYesNo('menuResource', 'read', '')=="YES") {
        $goto=' onClick="gotoElement(\'Resource\',\''.htmlEncode($assignment->idResource).'\');" style="cursor: pointer;" ';
      }
    }
    echo '<td '.$goto.'>'.$resName;
    echo ($assignment->idRole)?' ('.SqlList::getNameFromId('Role', $assignment->idRole).')':'';
    echo '</td>';
    if ($assignment->notPlannedWork>0) {
      echo '<td>';
      echo '&nbsp;<span style="float:right;background-color:#FFAAAA; color:#696969; border:1px solid #A9A9A9;" title="'.i18n("colNotPlannedWork").'">&nbsp;'.Work::displayWorkWithUnit($assignment->notPlannedWork).'&nbsp;</span>';
      echo '</td>';
    }
    if ($assignment->comment and !$print) {
      echo '<td>';
      echo formatCommentThumb($assignment->comment);
      echo '</td>';
    }
    // gautier #1702
    if (!$assignment->optional and (get_class($obj)=='Meeting' or get_class($obj)=='PeriodicMeeting')) {
      echo '<td>';
      echo '<a style="float:right; vertical-align:middle;"> '.formatIcon('Favorite', 16, i18n('mandatoryAttendant')).'</a>';
      echo '</td>';
    }
    // resourceTeam
    if ($resource->isResourceTeam) {
      echo '<td>';
      echo '<a style="float:right; vertical-align:middle;"> '.formatIcon('Team', 16, i18n('ResourceTeam')).'</a>';
      echo '</td>';
    }
    echo '</tr></table>';
    echo '</td>';
    // gautier #resourceTeam
    
    if ($resource->isResourceTeam) {
      echo '<td class="assignData" align="center" style="width:15%;vertical-align:middle;text-align:center;">'.htmlDisplayNumericWithoutTrailingZeros($assignment->capacity).' '.i18n('unitCapacity').'</td>';
    } else {
      echo '<td class="assignData" align="center" style="width:15%;vertical-align:middle;text-align:center;">'.htmlEncode($assignment->rate).' '.i18n('percent').'</td>';
    }
    if ($workVisible) {
      $keyDownEventScript=NumberFormatter52::getKeyDownEvent();
      // echo '<td class="assignData" align="right" style="vertical-align:middle">'
      // mehdi======================ticket#1776
      if (!$print) echo '<input type="hidden" id="initAss_'.$assignment->id.'" value="'.Work::displayWork($assignment->assignedWork).'"/>';
      echo '<td class="assignData" align="right" style="width:15%;vertical-align:middle;">';
      if ($canUpdate and get_class($obj)!='PeriodicMeeting' and !$print and $planningMode!='RECW') {
        echo '<img  id="idImageAssignedWork'.$assignment->id.'" src="img/savedOk.png" 
                style="display: none; position:relative;top:2px;left:5px; height:16px;float:left;"/>';
        echo '<div dojoType="dijit.form.NumberTextBox" id="assAssignedWork_'.$assignment->id.'" name="assAssignedWork_'.$assignment->id.'"
    						  class="dijitReset dijitInputInner dijitNumberTextBox"
      					  value="'.Work::displayWork($assignment->assignedWork).'"
                  style="padding:1px;background:none;max-width:100%; box-sizing:border-box;display:block;border:1px solid #A0A0A0 !important;margin:2px 0px" >
                   <script type="dojo/method" event="onChange">
                    assUpdateLeftWork('.$assignment->id.'); 
                    saveLeftWork('.$assignment->id.',\'AssignedWork\'); 
                    //saveLeftWork('.$assignment->id.',\'LeftWork\');
                   </script>';
        echo $keyDownEventScript;
        echo '</div>';
      } else {
        echo $fmt->format(Work::displayWork($assignment->assignedWork));
      }
      echo '</td>';
      
      echo '<input type="hidden" id="RealWork_'.$assignment->id.'" value="'.Work::displayWork($assignment->realWork).'"/>';
      echo '<td class="assignData" align="right" style="width:15%;vertical-align:middle;">'.$fmt->format(Work::displayWork($assignment->realWork)).'</td>';
      
      if (!$print) echo '<input type="hidden" id="initLeft_'.$assignment->id.'" value="'.Work::displayWork($assignment->leftWork).'"/>';
      echo '<td class="assignData" align="right" style="width:15%;vertical-align:middle;">';
      if ($canUpdate and get_class($obj)!='PeriodicMeeting' and !$print and $planningMode!='RECW') {
        echo '<img  id="idImageLeftWork'.$assignment->id.'" src="img/savedOk.png" style="display: none; position:relative;top:2px;left:5px; height:16px;float:left;"/>';
        echo '<div dojoType="dijit.form.NumberTextBox" id="assLeftWork_'.$assignment->id.'" name="assLeftWork_'.$assignment->id.'"
        				class="dijitReset dijitInputInner dijitNumberTextBox"
        				value="'.Work::displayWork($assignment->leftWork).'"
                style="padding:1px;max-width:100%; background:none;box-sizing:border-box;display:block;border:1px solid #A0A0A0 !important;margin:2px 0px"  >
                <script type="dojo/method" event="onChange">
                    saveLeftWork('.$assignment->id.',\'LeftWork\');
                </script>';
        echo $keyDownEventScript;
        echo '</div>';
      } else {
        echo $fmt->format(Work::displayWork($assignment->leftWork));
      }
      echo '</td>';
    }
    echo '</tr>';
  }
  echo '</table></td></tr>';
}

function drawExpenseDetailFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  // $pe=new PlanningElement();
  // $pe->setVisibility();
  // $workVisible=($pe->_workVisibility=='ALL')?true:false;
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  $scope=str_replace('expense', '', strtolower(get_class($obj)));
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (!$print) {
    echo '<td class="assignHeader" style="width:5%">';
    // if ($obj->id!=null and ! $print and $canUpdate and !$obj->idle and $workVisible) {
    if ($obj->id!=null and !$print and $canUpdate and !$obj->idle) {
      echo '<a onClick="addExpenseDetail(\''.$scope.'\');" title="'.i18n('addExpenseDetail').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" style="width:'.(($print)?'13':'8').'%">'.i18n('colDate').'</td>';
  echo '<td class="assignHeader" style="width:10%">'.i18n('colReference').'</td>';
  echo '<td class="assignHeader" style="width:30%">'.i18n('colName').'</td>';
  echo '<td class="assignHeader" style="width:12%" >'.i18n('colType').'</td>';
  echo '<td class="assignHeader" style="width:25%">'.i18n('colDetail').'</td>';
  // if ($workVisible) {
  echo '<td class="assignHeader" style="width:10%">'.i18n('colAmount').'</td>';
  // }
  echo '</tr>';
  $fmt=new NumberFormatter52($browserLocale, NumberFormatter52::DECIMAL);
  foreach ($list as $expenseDetail) {
    echo '<tr>';
    if (!$print) {
      echo '<td class="assignData" style="text-align:center;white-space:nowrap;width:5%">';
      // if ($canUpdate and ! $print and $workVisible) {
      if ($canUpdate and !$print) {
        echo '  <a onClick="editExpenseDetail(\''.$scope.'\','."'".htmlEncode($expenseDetail->id)."'".",'".htmlEncode($expenseDetail->idExpense)."'".",'".htmlEncode($expenseDetail->idExpenseDetailType)."'".",'".htmlEncode($expenseDetail->expenseDate)."'".",'".$fmt->format($expenseDetail->amount)."'".');" '.'title="'.i18n('editExpenseDetail').'" > '.formatSmallButton('Edit').'</a>';
      }
      // if ($canUpdate and ! $print and $workVisible ) {
      if ($canUpdate and !$print) {
        echo '  <a onClick="removeExpenseDetail('."'".htmlEncode($expenseDetail->id)."'".');" '.'title="'.i18n('removeExpenseDetail').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="assignData" style="width:'.(($print)?'13':'8').'%">'.htmlFormatDate($expenseDetail->expenseDate).'</td>';
    echo '<td class="assignData" style="width:10%">'.$expenseDetail->externalReference.'</td>';
    echo '<td class="assignData" style="width:30%"';
    echo '>'.$expenseDetail->name;
    if ($expenseDetail->description and !$print) {
      echo formatCommentThumb($expenseDetail->description);
    }
    echo '<input type="hidden" id="expenseDetail_'.htmlEncode($expenseDetail->id).'" value="'.htmlEncode($expenseDetail->name, 'none').'"/>';
    echo '<input type="hidden" id="expenseDetailRef_'.htmlEncode($expenseDetail->id).'" value="'.htmlEncode($expenseDetail->externalReference, 'none').'"/>';
    
    echo '</td>';
    echo '<td class="assignData" style="width:12%">'.SqlList::getNameFromId('ExpenseDetailType', $expenseDetail->idExpenseDetailType).'</td>';
    echo '<td class="assignData" style="width:25%">';
    echo $expenseDetail->getFormatedDetail();
    echo '</td>';
    echo '<td class="assignData" style="text-align:right;width:10%"">'.htmlDisplayCurrency($expenseDetail->amount).'</td>';
    echo '</tr>';
  }
  echo '</table></td></tr>';
}

function drawResourceCostFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  $pe=new PlanningElement();
  $pe->setVisibility();
  $costVisible=($pe->_costVisibility=='ALL')?true:false;
  if (!$costVisible) return;
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  $funcList=' ';
  foreach ($list as $rcost) {
    $key='#'.htmlEncode($rcost->idRole).'#';
    if (strpos($funcList, $key)===false) {
      $funcList.=$key;
    }
  }
  if (!$print) {
    echo '<td class="assignHeader" style="width:10%">';
    if ($obj->id!=null and !$print and $canUpdate and !$obj->idle) {
      echo '<a onClick="addResourceCost(\''.htmlEncode($obj->id).'\', \''.htmlEncode($obj->idRole).'\',\''.$funcList.'\');" title="'.i18n('addResourceCost').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" style="width:'.(($print)?'40':'30').'%">'.i18n('colIdRole').'</td>';
  echo '<td class="assignHeader" style="width:20%">'.i18n('colCost').'</td>';
  echo '<td class="assignHeader" style="width:20%">'.i18n('colStartDate').'</td>';
  echo '<td class="assignHeader" style="width:20%">'.i18n('colEndDate').'</td>';
  
  echo '</tr>';
  $fmt=new NumberFormatter52($browserLocale, NumberFormatter52::DECIMAL);
  foreach ($list as $rcost) {
    echo '<tr>';
    if (!$print) {
      echo '<td class="assignData" style="text-align:center;">';
      if (!$rcost->endDate and $canUpdate and !$print) {
        echo '  <a onClick="editResourceCost('."'".htmlEncode($rcost->id)."'".",'".htmlEncode($rcost->idResource)."'".",'".htmlEncode($rcost->idRole)."'".",'".($rcost->cost*100)."'".",'".htmlEncode($rcost->startDate)."'".",'".htmlEncode($rcost->endDate)."'".');" '.'title="'.i18n('editResourceCost').'" > '.formatSmallButton('Edit').'</a>';
      }
      if (!$rcost->endDate and $canUpdate and !$print) {
        echo '  <a onClick="removeResourceCost('."'".htmlEncode($rcost->id)."'".",'".htmlEncode($rcost->idRole)."'".",'".SqlList::getNameFromId('Role', $rcost->idRole)."'".",'".htmlFormatDate($rcost->startDate)."'".');" '.'title="'.i18n('removeResourceCost').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="assignData" align="left">'.SqlList::getNameFromId('Role', $rcost->idRole).'</td>';
    echo '<td class="assignData" align="right">'.htmlDisplayCurrency($rcost->cost);
    echo " / ".i18n('shortDay');
    echo '</td>';
    echo '<td class="assignData" align="center">'.htmlFormatDate($rcost->startDate).'</td>';
    echo '<td class="assignData" align="center">'.htmlFormatDate($rcost->endDate).'</td>';
    echo '</tr>';
  }
  echo '</table></td></tr>';
}

function drawVersionProjectsFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (get_class($obj)=='Project') {
    $idProj=$obj->id;
    $idVers=null;
  } else if (SqlElement::is_a($obj, 'Version')) {
    $idProj=null;
    $idVers=$obj->id;
  }
  if (!$print) {
    echo '<td class="assignHeader" style="width:10%">';
    if ($obj->id!=null and !$print and $canUpdate and !$obj->idle) {
      echo '<a onClick="addVersionProject(\''.$idVers.'\', \''.$idProj.'\');" title="'.i18n('addVersionProject').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  if ($idProj) {
    echo '<td class="assignHeader" style="width:'.(($print)?'60':'50').'%">'.i18n('colIdVersion').'</td>';
  } else {
    echo '<td class="assignHeader" style="width:'.(($print)?'60':'50').'%">'.i18n('colIdProject').'</td>';
  }
  echo '<td class="assignHeader" style="width:15%">'.i18n('colStartDate').'</td>';
  echo '<td class="assignHeader" style="width:15%">'.i18n('colEndDate').'</td>';
  echo '<td class="assignHeader" style="width:10%">'.i18n('colIdle').'</td>';
  
  echo '</tr>';
  if (get_class($obj)=='Project') {
    SqlElement::$_cachedQuery['Version']=array(); // PBE : performance improvments
                                                  // ADD qCazelles - Sorting Project versions list - Ticket 182
    usort($list, "ProductVersionStructure::sortVersionList");
    // END ADD qCazelles - Sorting Project versions list - Ticket 182
  }
  foreach ($list as $vp) {
    $vers=new Version($vp->idVersion);
    if ($vers->scope!='Product') continue;
    echo '<tr>';
    if (!$print) {
      echo '<td class="assignData" style="text-align:center;white-space:nowrap">';
      if ($canUpdate and !$print) {
        echo '  <a onClick="editVersionProject('."'".htmlEncode($vp->id)."'".",'".htmlEncode($vp->idVersion)."'".",'".htmlEncode($vp->idProject)."'".');" '.'title="'.i18n('editVersionProject').'" > '.formatSmallButton('Edit').'</a>';
      }
      if ($canUpdate and !$print) {
        echo '  <a onClick="removeVersionProject('."'".htmlEncode($vp->id)."'".');" '.'title="'.i18n('removeVersionProject').'" > '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    $goto="";
    if ($idProj) {
      if (!$print and securityCheckDisplayMenu(null, 'ProductVersion') and securityGetAccessRightYesNo('menuProductVersion', 'read', '')=="YES") {
        $goto=' onClick="gotoElement(\'ProductVersion\',\''.htmlEncode($vp->idVersion).'\');" style="cursor: pointer;" ';
      }
      echo '<td class="assignData" align="left"'.$goto.'>'.htmlEncode(SqlList::getNameFromId('Version', $vp->idVersion)).'</td>';
    } else {
      if (!$print and securityCheckDisplayMenu(null, 'Project') and securityGetAccessRightYesNo('menuProject', 'read', '')=="YES") {
        $goto=' onClick="gotoElement(\'Project\',\''.htmlEncode($vp->idProject).'\');" style="cursor: pointer;" ';
      }
      echo '<td class="assignData" align="left"'.$goto.'>'.htmlEncode(SqlList::getNameFromId('Project', $vp->idProject)).'</td>';
    }
    // CHANGE qCazelles - Ticket #119
    // Old
    // echo '<td class="assignData" align="center">' . htmlFormatDate($vp->startDate) . '</td>';
    // echo '<td class="assignData" align="center">' . htmlFormatDate($vp->endDate) . '</td>';
    // New
    if (Parameter::getGlobalParameter('displayMilestonesStartDelivery')!='YES') {
      echo '<td class="assignData" align="center">'.htmlFormatDate($vp->startDate).'</td>';
      echo '<td class="assignData" align="center">'.htmlFormatDate($vp->endDate).'</td>';
    } else {
      $hasStartDate=false;
      if ($vers->isStarted) {
        $startDate=$vers->realStartDate;
        $hasStartDate=true;
      } else if ($vers->plannedStartDate) {
        $startDate=$vers->plannedStartDate;
      } else {
        $startDate='';
      }
      $hasEndDate=false;
      if ($vers->isDelivered) {
        $endDate=$vers->realDeliveryDate;
        $hasEndDate=true;
      } else if ($vers->plannedDeliveryDate) {
        $endDate=$vers->plannedDeliveryDate;
      } else {
        $endDate='';
      }
      echo '<td class="assignData" align="center">';
      if (!$hasStartDate and $startDate!='') echo '<span style="color: red;">[';
      echo htmlFormatDate($startDate);
      if (!$hasStartDate and $startDate!='') echo ']</span>';
      echo '</td>';
      echo '<td class="assignData" align="center">';
      if (!$hasEndDate and $endDate!='') echo '<span style="color: red;">[';
      echo htmlFormatDate($endDate);
      if (!$hasEndDate and $endDate!='') echo ']</span>';
      echo '</td>';
    }
    // END CHANGE qCazelles - Ticket #119
    echo '<td class="assignData" align="center"><img src="../view/img/checked'.(($vp->idle)?'OK':'KO').'.png" /></td>';
    
    echo '</tr>';
  }
  echo '</table></td></tr>';
}

function drawProductProjectsFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (get_class($obj)=='Project') {
    $idProj=$obj->id;
    $idProd=null;
  } else if (get_class($obj)=='Product') {
    $idProj=null;
    $idProd=$obj->id;
  }
  if (!$print) {
    echo '<td class="assignHeader" style="width:10%">';
    if ($obj->id!=null and !$print and $canUpdate and !$obj->idle) {
      echo '<a onClick="addProductProject(\''.$idProd.'\', \''.$idProj.'\');" title="'.i18n('addProductProject').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  if ($idProj) {
    echo '<td class="assignHeader" style="width:'.(($print)?'60':'50').'%">'.i18n('colIdProduct').'</td>';
  } else {
    echo '<td class="assignHeader" style="width:'.(($print)?'60':'50').'%">'.i18n('colIdProject').'</td>';
  }
  echo '<td class="assignHeader" style="width:15%">'.i18n('colStartDate').'</td>';
  echo '<td class="assignHeader" style="width:15%">'.i18n('colEndDate').'</td>';
  echo '<td class="assignHeader" style="width:10%">'.i18n('colIdle').'</td>';
  
  echo '</tr>';
  foreach ($list as $pp) {
    // $prod=new Product($pp->idProduct);
    echo '<tr>';
    if (!$print) {
      echo '<td class="assignData" style="text-align:center;white-space:nowrap">';
      if ($canUpdate and !$print) {
        echo '  <a onClick="editProductProject('."'".htmlEncode($pp->id)."'".",'".htmlEncode($pp->idProduct)."'".",'".htmlEncode($pp->idProject)."'".');" '.'title="'.i18n('editProductProject').'" > '.formatSmallButton('Edit').'</a>';
      }
      if ($canUpdate and !$print) {
        echo '  <a onClick="removeProductProject('."'".htmlEncode($pp->id)."'".');" '.'title="'.i18n('removeProductProject').'"> '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    $goto="";
    if ($idProj) {
      $p=new Product($pp->idProduct, true);
      if (!$print and securityCheckDisplayMenu(null, 'Product') and securityGetAccessRightYesNo('menuProduct', 'read', $p)=="YES") {
        $goto=' onClick="gotoElement(\'Product\',\''.htmlEncode($pp->idProduct).'\');" style="cursor: pointer;" ';
      }
      echo '<td class="assignData" align="left"'.$goto.'>'.htmlEncode(SqlList::getNameFromId('Product', $pp->idProduct)).'</td>';
    } else {
      $p=new Project($pp->idProject, true);
      if (!$print and securityCheckDisplayMenu(null, 'Project') and securityGetAccessRightYesNo('menuProject', 'read', $p)=="YES") {
        $goto=' onClick="gotoElement(\'Project\',\''.htmlEncode($pp->idProject).'\');" style="cursor: pointer;" ';
      }
      echo '<td class="assignData" align="left"'.$goto.'>'.htmlEncode(SqlList::getNameFromId('Project', $pp->idProject)).'</td>';
    }
    echo '<td class="assignData" align="center">'.htmlFormatDate($pp->startDate).'</td>';
    echo '<td class="assignData" align="center">'.htmlFormatDate($pp->endDate).'</td>';
    echo '<td class="assignData" align="center"><img src="../view/img/checked'.(($pp->idle)?'OK':'KO').'.png" /></td>';
    
    echo '</tr>';
  }
  echo '</table></td></tr>';
}
// gautier #resourceTeam
function drawAffectationsResourceTeamFromObject($list, $obj, $type, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  $pluginObjectClass='Affectation';
  $tableObject=$list;
  $lstPluginEvt=Plugin::getEventScripts('list', $pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $listTemp=$tableObject;
  $list=array();
  foreach ($listTemp as $aff) {
    $name=SqlList::getNameFromId('Resource', $aff->idResource);
    $aff->name=$name;
    $list[$name.'#'.$aff->id]=$aff;
  }
  ksort($list);
  if ($comboDetail) {
    return;
  }
  $canCreate=securityGetAccessRightYesNo('menuAffectation', 'create')=="YES";
  if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
    $canCreate=false;
    $canUpdate=false;
    $canDelete=false;
  }
  if ($obj->idle==1) {
    $canUpdate=false;
    $canCreate=false;
    $canDelete=false;
  }
  if (get_class($obj)=='GlobalView') {
    $canUpdate=false;
    $canCreate=false;
    $canDelete=false;
  }
  
  echo '<table style="width:100%">';
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (get_class($obj)=='Resource' or get_class($obj)=='ResourceTeam') {
    $idRess=$obj->id;
  } else {
    $idRess=null;
  }
  if (!$print) {
    echo '<td class="assignHeader" style="width:15%">';
    if ($obj->id!=null and !$print and $canCreate and !$obj->idle) {
      echo '<a onClick="addAffectationResourceTeam(\''.get_class($obj).'\',\''.$type.'\',\''.$idRess.'\');" title="'.i18n('addAffectation').'" /> '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" style="width:8%">'.i18n('colId').'</td>';
  echo '<td class="assignHeader" style="width:39%">'.i18n('colName').'</td>';
  echo '<td class="assignHeader" style="width:13%">'.i18n('colStartDate').'</td>';
  echo '<td class="assignHeader" style="width:13%">'.i18n('colEndDate').'</td>';
  echo '<td class="assignHeader" style="width:12%">'.i18n('colRate').'</td>';
  
  echo '</tr>';
  foreach ($list as $aff) {
    $canUpdate=securityGetAccessRightYesNo('menuAffectation', 'update', $aff)=="YES";
    $canDelete=securityGetAccessRightYesNo('menuAffectation', 'delete', $aff)=="YES";
    if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
      $canCreate=false;
      $canUpdate=false;
      $canDelete=false;
    }
    if ($obj->idle==1) {
      $canUpdate=false;
      $canCreate=false;
      $canDelete=false;
    }
    $idleClass=($aff->idle or ($aff->endDate and $aff->endDate<$dateNow=date("Y-m-d")))?' affectationIdleClass':'';
    $res=new Resource($aff->idResource);
    $isResource=($res->id)?true:false;
    $goto="";
    $name=$aff->name;
    $typeAffectable='Resource';
    if (!$print and securityCheckDisplayMenu(null, $typeAffectable) and securityGetAccessRightYesNo('menu'.$typeAffectable, 'read', '')=="YES") {
      $goto=' onClick="gotoElement(\''.$typeAffectable.'\',\''.htmlEncode($aff->idResource).'\');" style="cursor: pointer;" ';
    }
    if ($aff->idResource!=$name and trim($name)) {
      echo '<tr>';
      if (!$print) {
        echo '<td class="assignData'.$idleClass.'" style="text-align:center;white-space: nowrap;">';
        if ($canUpdate and !$print) {
          echo '  <a onClick="editAffectationResourceTeam('."'".htmlEncode($aff->id)."'".",'".get_class($obj)."'".",'".$type."'".",'".htmlEncode($aff->idResource)."'".",'".htmlEncode($aff->rate)."'".",'".htmlEncode($aff->idle)."'".",'".$aff->startDate."'".",'".htmlEncode($aff->endDate)."'".');" '.'title="'.i18n('editAffectation').'" > '.formatSmallButton('Edit').'</a>';
        }
        if ($canDelete and !$print) {
          echo '  <a onClick="removeAffectationResourceTeam('."'".htmlEncode($aff->id)."'".',\''.$aff->idResource.'\');" '.'title="'.i18n('removeAffectation').'" > '.formatSmallButton('Remove').'</a>';
        }
        echo '</td>';
      }
      echo '<td class="assignData'.$idleClass.'" align="center">'.htmlEncode($aff->idResource).'</td>';
      
      echo '<td class="assignData'.$idleClass.'" align="left"'.$goto.'>';
      if ($aff->description and !$print) {
        echo '<div style="float:right">'.formatCommentThumb($aff->description).'</div>';
      }
      echo htmlEncode($name);
      echo '</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlFormatDate($aff->startDate).'</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlFormatDate($aff->endDate).'</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlEncode($aff->rate).'</td>';
      echo '</tr>';
    }
  }
  echo '</table></td></tr>';
  echo '</table>';
}

// gautier #ProviderTerm
function drawProviderTermFromObject($list, $obj, $type, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  $pluginObjectClass='ProviderTerm';
  $tableObject=$list;
  $lstPluginEvt=Plugin::getEventScripts('list', $pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $listTemp=$tableObject;
  $list=array();
  foreach ($listTemp as $term) {
    $list['#'.$term->id]=$term;
  }
  ksort($list);
  if ($comboDetail) {
    return;
  }
  $canCreate=securityGetAccessRightYesNo('menuAffectation', 'create')=="YES";
  if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
    $canCreate=false;
    $canUpdate=false;
    $canDelete=false;
  }
  if ($obj->idle==1) {
    $canUpdate=false;
    $canCreate=false;
    $canDelete=false;
  }
  
  if (get_class($obj)=='ProviderOrder') {
    $idProviderOrder=$obj->id;
  } else {
    $idProviderOrder=null;
  }
  
  echo '<table style="width:100%">';
  // echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  echo '<td class="assignHeader" style="width:10%">';
  
  $provTerm=new ProviderTerm();
  $billLine=new BillLine();
  $critArray=array("idProviderOrder"=>$obj->id);
  $listProvTerm=$provTerm->getSqlElementsFromCriteria($critArray);
  $test=false;
  $y=0;
  foreach ($listProvTerm as $prov) {
    $y=1;
    $critArray2=array("refType"=>"ProviderTerm", "refId"=>$prov->id);
    $cpt=$billLine->countSqlElementsFromCriteria($critArray2);
    if ($cpt!=0) {
      $test=true;
    }
  }
  $isLineProviderTerm='test';
  if ($obj->totalUntaxedAmount!=0) {
    if ($test!=true or $y==0) {
      $isLineProviderTerm=false;
      echo '<a onClick="addProviderTerm(\''.get_class($obj).'\',\''.$type.'\',\''.$idProviderOrder.'\',\'false\');" title="'.i18n('addProviderTerm').'" > '.formatSmallButton('Add').'</a>';
    }
    $billLineO=new BillLine();
    $billLineList=$billLineO->getSqlElementsFromCriteria(array("refType"=>"ProviderOrder", "refId"=>$obj->id));
    if ($billLineList) {
      if ($y==0 or $test==true) {
        $isLineProviderTerm=true;
        echo '<a onClick="addProviderTerm(\''.get_class($obj).'\',\''.$type.'\',\''.$idProviderOrder.'\',\'true\');" title="'.i18n('addProviderTermLine').'" > '.formatSmallButton('Split').'</a>';
      }
    }
  }
  echo '</td>';
  echo '<td class="assignHeader" style="width:10%">'.i18n('colId').'</td>';
  echo '<td class="assignHeader" style="width:15%">'.i18n('colStatusDateTime').'</td>';
  echo '<td class="assignHeader" style="width:20%">'.i18n('colValidatedAmount2').'</td>';
  echo '<td class="assignHeader" style="width:45%">'.i18n('colIdProviderBill').'</td>';
  $sumTermAmount=0;
  echo '</tr>';
  foreach ($list as $term) {
    $canUpdate=securityGetAccessRightYesNo('menuProvideTerm', 'update', $term)=="YES";
    $canDelete=securityGetAccessRightYesNo('menuProvideTerm', 'delete', $term)=="YES";
    if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
      $canCreate=false;
      $canUpdate=false;
      $canDelete=false;
    }
    if ($obj->idle==1) {
      $canUpdate=false;
      $canCreate=false;
      $canDelete=false;
    }
    if ($term->idProviderBill) {
      $canUpdate=false;
      $canDelete=false;
    }
    
    $idleClass=($term->idle)?' affectationIdleClass':'';
    $goto="";
    $typeAffectable='ProviderTerm';
    if (!$print and securityCheckDisplayMenu(null, $typeAffectable) and securityGetAccessRightYesNo('menu'.$typeAffectable, 'read', '')=="YES") {
      $goto=' onClick="gotoElement(\''.$typeAffectable.'\',\''.htmlEncode($term->id).'\');" style="cursor: pointer;" ';
    }
    $goto2="";
    $typeAffectable2='ProviderBill';
    if (!$print and securityCheckDisplayMenu(null, $typeAffectable2) and securityGetAccessRightYesNo('menu'.$typeAffectable2, 'read', '')=="YES") {
      $goto2=' onClick="gotoElement(\''.$typeAffectable2.'\',\''.htmlEncode($term->idProviderBill).'\');" style="cursor: pointer;" ';
    }
    echo '<tr>';
    if (!$print) {
      echo '<td class="assignData'.$idleClass.'" style="text-align:center;white-space: nowrap;">';
      if ($canUpdate and !$print) {
        echo '  <a onClick="editProviderTerm(\''.get_class($obj).'\',\''.$obj->id.'\',\''.$isLineProviderTerm.'\',\''.$term->id.'\',\''.$term->name.'\',\''.$term->date.'\',\''.htmlDisplayNumericWithoutTrailingZeros($term->taxPct).'\',\''.htmlDisplayNumericWithoutTrailingZeros($obj->discountRate).'\',\''.$term->untaxedAmount.'\',\''.$term->taxAmount.'\',\''.$term->fullAmount.'\',\''.$obj->totalUntaxedAmount.'\');" '.'title="'.i18n('editProviderTerm').'" > '.formatSmallButton('Edit').'</a>';
      }
      if ($canDelete and !$print) {
        echo '  <a onClick="removeProviderTerm('."'".htmlEncode($term->id)."'".');" '.'title="'.i18n('removeProviderTerm').'" > '.formatSmallButton('Remove').'</a>';
      }
//       if ($term->idle) {
//         echo '<div style="display:table-cell;width:20px;"><img style="position:relative;top:4px;left:2px" src="css/images/tabClose.gif" '.'title="'.i18n('colIdle').'"/></div>';
//       } else {
//         echo '<div style="display:table-cell;width:20px;">&nbsp;</div>';
//       }
      if ($term->isPaid) {
        echo i18n('colIsPaid');
      } else if ($term->isBilled) {
        echo i18n('colIsBilled');
      }
      echo '</td>';
    }
    echo '<td class="assignData'.$idleClass.'" align="center" '.$goto.'>#'.htmlEncode($term->id).'</td>';
    echo '<td class="assignData'.$idleClass.'" align="center" '.$goto.' style="white-space: nowrap;">'.htmlFormatDate($term->date).'</td>';
    echo '<td class="assignData'.$idleClass.'" align="right" '.$goto.' style="white-space: nowrap;">'.htmlDisplayCurrency($term->fullAmount).'</td>';
    $sumTermAmount+=$term->fullAmount;
    if ($term->idProviderBill) {
      $bill=new ProviderBill($term->idProviderBill);
      $objStatus=new Status($bill->idStatus);
      echo '<td class="assignData" align="center" '.$goto2.' style="white-space:nowrap; padding:0px !important;" >';
      echo '<table style="width:100%;padding:0;marin:0;"><tr>';
      echo '<td class="assignData" style="width:10%;border:0;">#'.htmlEncode($term->idProviderBill).'</td>';
      echo '<td class="assignData" style="width:50%;border:0;">'.htmlEncode($bill->externalReference).'</td>';
      echo '<td class="assignData" style="width:40%;border:0;">'.colorNameFormatter($objStatus->name."#split#".$objStatus->color).'</td>';
      echo '</tr></table>';
      echo '</td>';
    } else {
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;"></td>';
    }
    echo '</tr>';
  }
  // Summ for terms
  if (count($list)>0) {
    echo '<tr>';
    echo '<td colspan="'.(($print)?'2':'3').'" class="assignHeader" style="text-align:right">'.i18n('sum').'&nbsp;</td>';
    echo '<td class="assignData" style="font-weight:bold;vertical-align:middle;" align="right">'.htmlDisplayCurrency($sumTermAmount).'</td>';
    echo '<td class="assignHeader" >&nbsp;</td>';
    echo '</tr>';
  }
  // echo '</table></td></tr>';
  echo '</table>';
}

function drawAffectationsResourceTeamResourceFromObject($list, $obj, $type, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  $pluginObjectClass='Affectation';
  $tableObject=$list;
  $lstPluginEvt=Plugin::getEventScripts('list', $pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $listTemp=$tableObject;
  $list=array();
  foreach ($listTemp as $aff) {
    $name=SqlList::getNameFromId('ResourceTeam', $aff->idResourceTeam);
    $aff->name=$name;
    $list[$name.'#'.$aff->id]=$aff;
  }
  ksort($list);
  if ($comboDetail) {
    return;
  }
  $canCreate=securityGetAccessRightYesNo('menuAffectation', 'create')=="YES";
  if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
    $canCreate=false;
    $canUpdate=false;
    $canDelete=false;
  }
  if ($obj->idle==1) {
    $canUpdate=false;
    $canCreate=false;
    $canDelete=false;
  }
  
  echo '<table style="width:100%">';
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (get_class($obj)=='Resource' or get_class($obj)=='ResourceTeam') {
    $idRess=$obj->id;
  } else {
    $idRess=null;
  }
  
  echo '<td class="assignHeader" style="width:8%">'.i18n('colId').'</td>';
  echo '<td class="assignHeader" style="width:39%">'.i18n('colName').'</td>';
  echo '<td class="assignHeader" style="width:13%">'.i18n('colStartDate').'</td>';
  echo '<td class="assignHeader" style="width:13%">'.i18n('colEndDate').'</td>';
  echo '<td class="assignHeader" style="width:12%">'.i18n('colRate').'</td>';
  
  echo '</tr>';
  foreach ($list as $aff) {
    $canUpdate=securityGetAccessRightYesNo('menuAffectation', 'update', $aff)=="YES";
    $canDelete=securityGetAccessRightYesNo('menuAffectation', 'delete', $aff)=="YES";
    if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
      $canCreate=false;
      $canUpdate=false;
      $canDelete=false;
    }
    if ($obj->idle==1) {
      $canUpdate=false;
      $canCreate=false;
      $canDelete=false;
    }
    $idleClass=($aff->idle or ($aff->endDate and $aff->endDate<$dateNow=date("Y-m-d")))?' affectationIdleClass':'';
    $res=new Resource($aff->idResource);
    $isResource=($res->id)?true:false;
    $goto="";
    $name=$aff->name;
    $typeAffectable='ResourceTeam';
    if (!$print and securityCheckDisplayMenu(null, $typeAffectable) and securityGetAccessRightYesNo('menu'.$typeAffectable, 'read', '')=="YES") {
      $goto=' onClick="gotoElement(\''.$typeAffectable.'\',\''.htmlEncode($aff->idResourceTeam).'\');" style="cursor: pointer;" ';
    }
    if ($aff->idResource!=$name and trim($name)) {
      echo '<tr>';
      echo '<td class="assignData'.$idleClass.'" align="center">'.htmlEncode($aff->id).'</td>';
      
      echo '<td class="assignData'.$idleClass.'" align="left"'.$goto.'>';
      if ($aff->description and !$print) {
        echo '<div style="float:right">'.formatCommentThumb($aff->description).'</div>';
      }
      echo htmlEncode($name);
      echo '</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlFormatDate($aff->startDate).'</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlFormatDate($aff->endDate).'</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlEncode($aff->rate).'</td>';
      echo '</tr>';
    }
  }
  echo '</table></td></tr>';
  echo '</table>';
}

function drawAffectationsFromObject($list, $obj, $type, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  $pluginObjectClass='Affectation';
  $tableObject=$list;
  $lstPluginEvt=Plugin::getEventScripts('list', $pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $listTemp=$tableObject;
  $list=array();
  foreach ($listTemp as $aff) {
    if ($type=='Project') {
      $name=SqlList::getNameFromId($type, $aff->idProject);
    } else {
      $name=SqlList::getNameFromId($type, $aff->idResource);
    }
    if ($aff->idResource==$name and $type=='Resource') {
      $name=SqlList::getNameFromId('User', $aff->idResource);
      $typeAffectable='User';
      if ($aff->idResource!=$name and trim($name)) {
        $name.=" (".i18n('User').")";
      }
    }
    $aff->name=$name;
    $list[$name.'#'.$aff->id]=$aff;
  }
  ksort($list);
  if ($comboDetail) {
    return;
  }
  $canCreate=securityGetAccessRightYesNo('menuAffectation', 'create')=="YES";
  if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
    $canCreate=false;
    $canUpdate=false;
    $canDelete=false;
  }
  if ($obj->idle==1) {
    $canUpdate=false;
    $canCreate=false;
    $canDelete=false;
  }
  
  echo '<table style="width:100%">';
  echo '<tr><td colspan=2 style="width:100%;"><table style="width:100%;">';
  echo '<tr>';
  if (get_class($obj)=='Project') {
    $idProj=$obj->id;
    $idRess=null;
  } else if (get_class($obj)=='Resource' or get_class($obj)=='ResourceTeam' or get_class($obj)=='Contact' or get_class($obj)=='User') {
    $idProj=null;
    $idRess=$obj->id;
  } else {
    $idProj=null;
    $idRess=null;
  }
  
  if (!$print) {
    echo '<td class="assignHeader" style="width:15%">';
    if ($obj->id!=null and !$print and $canCreate and !$obj->idle) {
      echo '<a onClick="addAffectation(\''.get_class($obj).'\',\''.$type.'\',\''.$idRess.'\', \''.$idProj.'\');" title="'.i18n('addAffectation').'" /> '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" style="width:8%">'.i18n('colId').'</td>';
  echo '<td class="assignHeader" style="width:'.(($print)?'35':'20').'%">'.i18n('colId'.$type).'</td>';
  echo '<td class="assignHeader" style="width:18%">'.i18n('colIdProfile').'</td>';
  echo '<td class="assignHeader" style="width:13%">'.i18n('colStartDate').'</td>';
  echo '<td class="assignHeader" style="width:13%">'.i18n('colEndDate').'</td>';
  echo '<td class="assignHeader" style="width:12%">'.i18n('colRate').'</td>';
  // echo '<td class="assignHeader" style="width:10%">' . i18n('colIdle'). '</td>';
  
  echo '</tr>';
  foreach ($list as $aff) {
    $canUpdate=securityGetAccessRightYesNo('menuAffectation', 'update', $aff)=="YES";
    $canDelete=securityGetAccessRightYesNo('menuAffectation', 'delete', $aff)=="YES";
    if (!(securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES")) {
      $canCreate=false;
      $canUpdate=false;
      $canDelete=false;
    }
    if ($obj->idle==1) {
      $canUpdate=false;
      $canCreate=false;
      $canDelete=false;
    }
    $idleClass=($aff->idle or ($aff->endDate and $aff->endDate<$dateNow=date("Y-m-d")))?' affectationIdleClass':'';
    $res=new Resource($aff->idResource);
    $isResource=($res->id)?true:false;
    $goto="";
    $idToShow=$aff->id;
    $classToShow='Affectation';
    if ($type=='Project') {
      $name=$aff->name;
      if (!$print and securityCheckDisplayMenu(null, 'Project') and securityGetAccessRightYesNo('menuProject', 'read', '')=="YES") {
        $goto=' onClick="gotoElement(\'Project\',\''.htmlEncode($aff->idProject).'\');" style="cursor: pointer;" ';
      }
      $idToShow=$aff->idProject;
      $classToShow='Project';
    } else {
      $name=$aff->name;
      $typeAffectable=$type;
      // resourceTeam
      $idToShow=$aff->idResource;
      $classToShow='Resource';
      if ($typeAffectable=='ResourceAll') {
        $resource=new ResourceAll($aff->idResource);
        if ($resource->isResourceTeam) {
          if (securityCheckDisplayMenu(null, 'ResourceTeam') and securityGetAccessRightYesNo('menuResourceTeam', 'read', '')=="YES") {
            $goto=' onClick="gotoElement(\'ResourceTeam\',\''.htmlEncode($aff->idResource).'\');" style="cursor: pointer;" ';
          }
          $classToShow='ResourceTeam';
        } else {
          if (!$print and $isResource and securityCheckDisplayMenu(null, 'Resource') and securityGetAccessRightYesNo('menuResource', 'read', '')=="YES") {
            $goto=' onClick="gotoElement(\'Resource\',\''.htmlEncode($aff->idResource).'\');" style="cursor: pointer;" ';
          }
          $classToShow='Resource';
        }
      } else {
        if (!$print and securityCheckDisplayMenu(null, $typeAffectable) and securityGetAccessRightYesNo('menu'.$typeAffectable, 'read', '')=="YES") {
          $goto=' onClick="gotoElement(\''.$typeAffectable.'\',\''.htmlEncode($aff->idResource).'\');" style="cursor: pointer;" ';
        }
        $classToShow=$typeAffectable;
      }
    }
    if ($aff->idResource!=$name and trim($name)) {
      echo '<tr>';
      if (!$print) {
        echo '<td class="assignData'.$idleClass.'" style="text-align:center;white-space: nowrap;">';
        if ($canUpdate and !$print) {
          echo '  <a onClick="editAffectation('."'".htmlEncode($aff->id)."'".",'".get_class($obj)."'".",'".$type."'".",'".htmlEncode($aff->idResource)."'".",'".htmlEncode($aff->idProject)."'".",'".htmlEncode($aff->rate)."'".",'".htmlEncode($aff->idle)."'".",'".$aff->startDate."'".",'".htmlEncode($aff->endDate)."'".','.htmlEncode($aff->idProfile).');" '.'title="'.i18n('editAffectation').'" > '.formatSmallButton('Edit').'</a>';
        }
        if ($canDelete and !$print) {
          echo '  <a onClick="removeAffectation(\''.htmlEncode($aff->id).'\','.(($aff->idResource==getSessionUser()->id)?'1':'0').',\''.$classToShow.'\',\''.$idToShow.'\');" '.'title="'.i18n('removeAffectation').'" > '.formatSmallButton('Remove').'</a>';
        }
        if ($canUpdate and !$print and $isResource and !$aff->idle) {
          echo '  <a onClick="replaceAffectation('."'".htmlEncode($aff->id)."'".",'".get_class($obj)."'".",'".$type."'".",'".htmlEncode($aff->idResource)."'".",'".htmlEncode($aff->idProject)."'".",'".htmlEncode($aff->rate)."'".",'".htmlEncode($aff->idle)."'".",'".$aff->startDate."'".",'".htmlEncode($aff->endDate)."'".','.htmlEncode($aff->idProfile).');" '.'title="'.i18n('replaceAffectation').'" > '.formatSmallButton('SwitchUser').'</a>';
        } else {
          if ($aff->idle) {
            echo '<a><div style="display:table-cell;width:20px;"><img style="position:relative;top:4px;left:2px" src="css/images/tabClose.gif" '.'title="'.i18n('colIdle').'"/></div></a>';
          } else {
            echo '<a><div style="display:table-cell;width:20px;">&nbsp;</div></a>';
          }
        }
        
        echo '</td>';
      }
      echo '<td class="assignData'.$idleClass.'" align="center">'.htmlEncode($idToShow).'</td>';
      /*
       * if ($idProj) {
       * echo '<td class="assignData' . $idleClass . '" align="left"' . $goto . '>' . htmlEncode($name) . '</td>';
       * } else {
       * echo '<td class="assignData' . $idleClass . '" align="left"' . $goto . '>' . htmlEncode($name) . '</td>';
       * }
       */
      echo '<td class="assignData'.$idleClass.'" align="left"'.$goto.'>';
      // resourceTeam
      if (isset($typeAffectable)=='ResourceAll') {
        $resource=new ResourceAll($aff->idResource);
        if ($resource->isResourceTeam) {
          echo '<div style="float:right; vertical-align:middle;"> '.formatIcon('Team', 16, i18n('ResourceTeam')).'</div>';
        }
      }
      if ($aff->description and !$print) {
        echo '<div style="float:right">'.formatCommentThumb($aff->description).'</div>';
      }
      echo htmlEncode($name);
      echo '</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" >'.SqlList::getNameFromId('Profile', $aff->idProfile, true).'</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlFormatDate($aff->startDate).'</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlFormatDate($aff->endDate).'</td>';
      echo '<td class="assignData'.$idleClass.'" align="center" style="white-space: nowrap;">'.htmlEncode($aff->rate).'</td>';
      // echo '<td class="assignData" align="center"><img src="../view/img/checked' . (($aff->idle)?'OK':'KO') . '.png" /></td>';
      echo '</tr>';
    }
  }
  echo '</table></td></tr>';
  echo '</table>';
}

function drawTestCaseRunFromObject($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $class=get_class($obj);
  $otherClass=($class=='TestCase')?'TestSession':'TestCase';
  $nameWidth=($print)?45:25;
  $canCreate=securityGetAccessRightYesNo('menu'.$class, 'update', $obj)=="YES";
  $canUpdate=$canCreate;
  $canDelete=$canCreate;
  if ($obj->idle==1) {
    $canUpdate=false;
    $canCreate=false;
    $canDelete=false;
  }
  usort($list, "TestCaseRun::sort");
  echo '<tr><td colspan="2" style="width:100%;">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print and $class=='TestSession') {
    echo '<td class="assignHeader" style="width:10%;">';
    if ($obj->id!=null and !$print and $canCreate and !$obj->idle) {
      echo '<a onClick="addTestCaseRun();" title="'.i18n('addTestCaseRun').'" > '.formatSmallButton('Add').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" colspan="4" style="width:'.($nameWidth+20).'%">'.i18n('col'.$otherClass).'</td>';
  // gautier #1716
  echo '<td class="assignHeader" colspan="1" style="width:10%">'.i18n('colResult').'</td>';
  echo '<td class="assignHeader" colspan="1" style="width:10%">'.i18n('colComment').'</td>';
  //
  if (!$print and $class=='TestSession') {
    echo '<td class="assignHeader" style="width:10%">'.i18n('colDetail').'</td>';
  }
  echo '<td class="assignHeader" colspan="2" style="width:15%">'.i18n('colIdStatus').'</td>';
  echo '</tr>';
  foreach ($list as $tcr) {
    if ($otherClass=='TestCase') {
      $tc=new TestCase($tcr->idTestCase);
    } else {
      $tc=new TestSession($tcr->idTestSession);
    }
    $st=new RunStatus($tcr->idRunStatus);
    echo '<tr>';
    if (!$print and $class=='TestSession') {
      echo '<td class="assignData" style="width:10%;text-align:center;">';
      echo '<table style="width:100%"><tr><td style="width:30%;white-space:nowrap;">';
      if ($canUpdate and !$print) {
        echo '  <a onClick="editTestCaseRun(\''.htmlEncode($tcr->id).'\', null, null);" '.'title="'.i18n('editTestCaseRun').'" > '.formatSmallButton('Edit').'</a>';
      }
      if ($canDelete and !$print) {
        echo '  <a onClick="removeTestCaseRun('."'".htmlEncode($tcr->id)."'".",'".htmlEncode($tcr->idTestCase)."'".');" '.'title="'.i18n('removeTestCaseRun').'" > '.formatSmallButton('Remove').'</a>';
      }
      if (!$print) {
        echo '<input type="hidden" id="comment_'.htmlEncode($tcr->id).'" value="'.htmlEncode($tcr->comment, 'none').'"/>';
      }
      echo '</td><td>&nbsp;&nbsp;&nbsp;</td><td style="white-space:nowrap;">';
      if ($tcr->idRunStatus==1 or $tcr->idRunStatus==3 or $tcr->idRunStatus==4) {
        echo '  <a onClick="passedTestCaseRun(\''.htmlEncode($tcr->id).'\');" '.'title="'.i18n('passedTestCaseRun').'" /> '.formatSmallButton('Passed').'</a>';
      }
      if ($tcr->idRunStatus==1 or $tcr->idRunStatus==4) {
        echo '  <a onClick="failedTestCaseRun(\''.htmlEncode($tcr->id).'\');" '.'title="'.i18n('failedTestCaseRun').'" > '.formatSmallButton('Failed').'</a>';
      }
      if ($tcr->idRunStatus==1 or $tcr->idRunStatus==3) {
        echo '  <a onClick="blockedTestCaseRun(\''.htmlEncode($tcr->id).'\');" '.'title="'.i18n('blockedTestCaseRun').'" > '.formatSmallButton('Blocked').'</a>';
      }
      echo '</td></tr></table>';
      echo '</td>';
    }
    $goto="";
    if (!$print and securityCheckDisplayMenu(null, 'TestCase') and securityGetAccessRightYesNo('menuTestCase', 'read', $tc)=="YES") {
      $goto=' onClick="gotoElement(\''.$otherClass.'\',\''.htmlEncode($tc->id).'\');" style="cursor: pointer;" ';
    }
    $typeClass='id'.$otherClass.'Type';
    echo '<td class="assignData" align="center" style="width:5%">'.htmlEncode($tcr->sortOrder).'</td>';
    echo '<td class="assignData" align="center" style="width:10%">'.htmlEncode(SqlList::getNameFromId($otherClass.'Type', $tc->$typeClass)).'</td>';
    echo '<td class="assignData" align="center" style="width:5%">#'.htmlEncode($tc->id).'</td>';
    echo '<td class="assignData" align="left"'.$goto.' style="width:'.$nameWidth.'%" >'.htmlEncode($tc->name).'</td>';
    // gautier #1716
    $checkImg='savedOk.png';
    echo '<td class="assignData" style="width:10%">';
    if (!$print or $tcr->result) {
      if (!$print) {
        echo '<textarea dojoType="dijit.form.Textarea" id="tcrResult_'.$tcr->id.'" name="tcrResult_'.$tcr->id.'"
                style="float:left;width: 125px;min-height: 25px;font-size: 90%; background:none;display:block;border:none;" maxlength="4000" onchange="saveTcrData('.$tcr->id.',\'Result\');">';
        echo $tcr->result;
        echo '</textarea>';
        echo '<img  id="idImageResult'.$tcr->id.'" src="img/'.$checkImg.'" style="display: none; float:right; top:2px;right:5px; height:16px;"/>';
      } else {
        echo htmlEncode($tcr->result);
      }
    }
    echo '</td>';
    
    echo '<td class="assignData" style="width:10%">';
    if (!$print or $tcr->comment) {
      if (!$print) {
        echo '<img  id="idImageComment'.$tcr->id.'" src="img/'.$checkImg.'" style="display: none; float:right; top:2px;right:5px; height:16px;"/>';
        echo '<textarea dojoType="dijit.form.Textarea" id="tcrComment_'.$tcr->id.'" name="tcrComment_'.$tcr->id.'"
                style="float:left;width: 125px;min-height: 25px;font-size: 90%; background:none;display:block;border:none;" maxlength="4000" onchange="saveTcrData('.$tcr->id.',\'Comment\');">';
        echo $tcr->comment;
        echo '</textarea>';
      } else {
        echo htmlEncode($tcr->comment);
      }
    }
    echo '</td>';
    //
    // echo '</td>';
    if (!$print and $class=='TestSession') {
      echo '<td class="assignData" style="width:10%" align="center">';
      if ($tc->description) {
        echo formatCommentThumb('<b>'.i18n('colDescription').":</b>\n\n".$tc->description, '../view/css/images/description.png');
        // echo '<img src="../view/css/images/description.png" title="' . i18n('colDescription') . ":\n\n" . htmlEncode($tc->description) . '" alt="desc" />';
        echo '&nbsp;';
      }
      if ($tc->result) {
        echo formatCommentThumb('<b>'.i18n('colExpectedResult').":</b>\n\n".$tc->result, '../view/css/images/result.png');
        // echo '<img src="../view/css/images/result.png" title="' . i18n('colExpectedResult') . ":\n\n" . htmlEncode($tc->result,'protectQuotes') . '" alt="desc" />';
        echo '&nbsp;';
      }
      if (isset($tc->prerequisite) and $tc->prerequisite) {
        echo formatCommentThumb('<b>'.i18n('colPrerequisite').":</b>\n\n".$tc->prerequisite, '../view/css/images/prerequisite.png');
        // echo '<img src="../view/css/images/prerequisite.png" title="' . i18n('colPrerequisite') . ":\n\n" . htmlEncode($tc->prerequisite,'protectQuotes') . '" alt="desc" />';
      }
      echo '</td>';
    }
    echo '<td class="assignData" style="width:8%;text-align:left;border-right:0px;">';
    echo colorNameFormatter(i18n($st->name).'#split#'.$st->color);
    echo '</td>';
    echo '<td class="assignData" style="width:7%;border-left:0px;font-size:'.(($tcr->idTicket and $tcr->idRunStatus=='3')?'100':'80').'%; text-align: center;">';
    if ($tcr->idTicket and $tcr->idRunStatus=='3') {
      echo i18n('Ticket').' #'.$tcr->idTicket;
    } else if ($tcr->statusDateTime) {
      echo ' <i>('.htmlFormatDateTime($tcr->statusDateTime, false).')</i> ';
    }
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
  echo '</td></tr>';
}
// gautier #providerTerm
function drawProviderTermFromProviderBill($list, $obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $class=get_class($obj);
  
  $canCreate=securityGetAccessRightYesNo('menu'.$class, 'update', $obj)=="YES";
  $canDelete=$canCreate;
  $canUpdate=$canCreate;
  if ($obj->idle==1) {
    $canCreate=false;
    $canDelete=false;
    $canUpdate=false;
  }
  echo '<tr><td colspan="2" style="width:100%;">';
  echo '<table style="width:100%;">';
  echo '<tr>';
  if (!$print and $class=='ProviderBill') {
    $provTerm=new ProviderTerm();
    $billLine=new BillLine();
    $critArray=array("idProviderBill"=>$obj->id);
    $listProvTerm=$provTerm->getSqlElementsFromCriteria($critArray);
    $test=false;
    $y=0;
    foreach ($listProvTerm as $prov) {
      $y=1;
      $critArray2=array("refType"=>"ProviderTerm", "refId"=>$prov->id);
      $cpt=$billLine->countSqlElementsFromCriteria($critArray2);
      if ($cpt!=0) {
        $test=true;
      }
    }
    if (get_class($obj)=='ProviderBill') {
      $idProviderBill=$obj->id;
    } else {
      $idProviderBill=null;
    }
    $isLineProviderTerm='test';
    echo '<td class="assignHeader" style="width:10%;">';
    if ($obj->id!=null and !$print and $canCreate and !$obj->idle) {
      $depType='ProviderTerm';
      if ($obj->totalUntaxedAmount!=0) {
        if ($test!=true or $y==0) {
          $isLineProviderTerm=false;
          echo '<a onClick="addProviderTerm(\''.get_class($obj).'\',\'\',\''.$idProviderBill.'\',\'false\');" title="'.i18n('addProviderTerm').'" > '.formatSmallButton('Add').'</a>';
        }
      }
      echo '<a onClick="addProviderTermFromProviderBill();" title="'.i18n('addDependency'.$depType).'"> '.formatSmallButton('List').'</a>';
    }
    echo '</td>';
  }
  echo '<td class="assignHeader" colspan="1" style="width:10%">'.i18n('colId').'</td>';
  echo '<td class="assignHeader" colspan="1" style="width:15%">'.i18n('colDate').'</td>';
  echo '<td class="assignHeader" colspan="1" style="width:20%">'.i18n('colAmount').'</td>';
  echo '<td class="assignHeader" colspan="1" style="width:45%">'.i18n('colIdProviderOrder').'</td>';
  echo '</tr>';
  $sumTermAmount=0;
  foreach ($list as $prT) {
    $goto="";
    $typeAffectable='ProviderOrder';
    if (!$print and securityCheckDisplayMenu(null, $typeAffectable) and securityGetAccessRightYesNo('menu'.$typeAffectable, 'read', '')=="YES") {
      $goto=' onClick="gotoElement(\''.$typeAffectable.'\',\''.htmlEncode($prT->idProviderOrder).'\');" style="cursor: pointer;" ';
    }
    $goto2="";
    $typeAffectable2='ProviderTerm';
    if (!$print and securityCheckDisplayMenu(null, $typeAffectable2) and securityGetAccessRightYesNo('menu'.$typeAffectable2, 'read', '')=="YES") {
      $goto2=' onClick="gotoElement(\''.$typeAffectable2.'\',\''.htmlEncode($prT->id).'\');" style="cursor: pointer;" ';
    }
    echo '<tr>';
    echo '  <td class="assignData" align="center" style="width:10%;white-space:nowrap">';
    if ($obj->id!=null and $canUpdate and !$print and !$obj->idle and !$prT->idProviderOrder) {
      echo '  <a onClick="editProviderTerm(\''.get_class($obj).'\',\''.$obj->id.'\',\''.$isLineProviderTerm.'\',\''.$prT->id.'\',\''.$prT->name.'\',\''.$prT->date.'\',\''.htmlDisplayNumericWithoutTrailingZeros($prT->taxPct).'\',\''.htmlDisplayNumericWithoutTrailingZeros($obj->discountRate).'\',\''.$prT->untaxedAmount.'\',\''.$prT->taxAmount.'\',\''.$prT->fullAmount.'\',\''.$obj->totalUntaxedAmount.'\');" '.'title="'.i18n('editProviderTerm').'" > '.formatSmallButton('Edit').'</a>';
    }
    if ($canDelete and !$print) {
      if ($prT->idProviderOrder) {
        echo '  <a onClick="removeProviderTermFromBill('."'".htmlEncode($prT->id)."'".');" '.'title="'.i18n('removeProviderTermFromBill').'" > '.formatSmallButton('Mark').'</a>';
      } else { 
        echo '  <a onClick="removeProviderTerm('."'".htmlEncode($prT->id)."'".',true);" '.'title="'.i18n('removeProviderTerm').'" > '.formatSmallButton('Remove').'</a>';
      }
    }
    if ($prT->isPaid) {
      echo i18n('colIsPaid');
    }
    echo '   </td>';
    echo '  <td class="assignData" align="center" '.$goto2.' style="width:10%">#'.htmlEncode($prT->id).'</td>';
    echo '  <td class="assignData" align="center" '.$goto2.' style="width:15%">'.htmlFormatDate($prT->date).'</td>';
    echo '  <td class="assignData" align="right" '.$goto2.' style="width:20%;text-align:right;">'.htmlDisplayCurrency($prT->fullAmount).'</td>';
    $sumTermAmount+=$prT->fullAmount;
    if ($prT->idProviderOrder) {
      //echo '  <td class="assignData" align="center"'.$goto.' style="width:45%">#'.htmlEncode($prT->idProviderOrder).'</td>';
      $order=new ProviderOrder($prT->idProviderOrder);
      $objStatus=new Status($order->idStatus);
      echo '<td class="assignData" align="center" '.$goto.' style="white-space:nowrap; padding:0px !important;" >';
      echo '<table style="width:100%;padding:0;marin:0;"><tr>';
      echo '<td class="assignData" style="width:10%;border:0;">#'.htmlEncode($prT->idProviderOrder).'</td>';
      echo '<td class="assignData" style="width:50%;border:0;">'.htmlEncode($order->name).'</td>';
      echo '<td class="assignData" style="width:40%;border:0;">'.colorNameFormatter($objStatus->name."#split#".$objStatus->color).'</td>';
      echo '</tr></table>';
      echo '</td>';
    } else {
      echo '  <td class="assignData" align="center" style="width:45%"></td>';
    }
    echo '</tr>';
  }
  if (count($list)>0) {
    echo '<tr>';
    echo '<td colspan="'.(($print)?'2':'3').'" class="assignHeader" style="text-align:right">'.i18n('sum').'&nbsp;</td>';
    echo '<td class="assignData" style="font-weight:bold;vertical-align:middle;" align="right">'.htmlDisplayCurrency($sumTermAmount).'</td>';
    echo '<td class="assignHeader" >&nbsp;</td>';
    echo '</tr>';
  }
  echo '</table>';
  echo '</td></tr>';
}

function drawTabExpense($obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale, $comboDetail;
  if ($comboDetail) {
    return;
  }
  $class=get_class($obj);
  echo '<tr><td colspan="2" style="width:100%;">';
  echo '<table style="width:100%;">';
  echo '  <tr>';
  echo '    <td class="" colspan="1" style="width:25%"></td>';
  echo '    <td class="assignHeader" colspan="1" style="width:30%">'.i18n('colUntaxedAmount').'</td>';
  echo '    <td class="assignHeader" colspan="1" style="width:30%">'.i18n('colFullAmount').'</td>';
  echo '    <td class="assignHeader" colspan="1" style="width:15%">'.i18n('colWorkElementCount').'</td>';
  echo '  </tr>';
  
  $clauseStatus=transformListIntoInClause(SqlList::getListWithCrit('tenderStatus', array('isSelected'=>'1')));
  $providerTender = new Tender();
  $listTender = $providerTender->getSqlElementsFromCriteria(null,false,"idTenderStatus in $clauseStatus and idProjectExpense=$obj->id ");
  $untaxedAmount = 0;
  $fullAmount = 0;
  foreach ($listTender as $tender ){
    $untaxedAmount += $tender->totalUntaxedAmount;
    $fullAmount += $tender->totalFullAmount;
  }
  echo '  <tr>';
  echo '    <td class="assignHeader" colspan="1" style="width:25%">'.i18n('menuTender').'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($untaxedAmount).'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($fullAmount).'</td>';
  echo '    <td class="assignData" align="center" colspan="1" style="width:15%">'.count($listTender).'</td>';
  echo '  </tr>';
  $untaxedAmountTerm = 0;
  $fullAmountTerm =0;
  $nbTerm = 0;
  $providerOrder = new ProviderOrder();
  $listProviderOrder = $providerOrder->getSqlElementsFromCriteria(array("idProjectExpense"=>$obj->id));
  $untaxedAmount = 0;
  $fullAmount = 0;
  $clauseProviderBill=transformListIntoInClause(SqlList::getListWithCrit('providerBill', array('idProvider'=>$obj->id)));
  $arrayTerm=array();
  foreach ($listProviderOrder as $order ){
    $untaxedAmount += $order->totalUntaxedAmount;
    $fullAmount += $order->totalFullAmount;
    $providerTerm = new ProviderTerm();
    $listProviderTerm = $providerTerm->getSqlElementsFromCriteria(null, false,'idProviderOrder='.$order->id);
    foreach ($listProviderTerm as $term){
    	$arrayTerm[$term->id]=$term;
    }
  }
  echo '  <tr>';
  echo '    <td class="assignHeader" colspan="1" style="width:25%">'.i18n('menuProviderOrder').'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($untaxedAmount).'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($fullAmount).'</td>';
  echo '    <td class="assignData" align="center" colspan="1" style="width:15%">'.count($listProviderOrder).'</td>';
  echo '  </tr>';
  $providerBill = new ProviderBill();
  $listProviderBill = $providerBill->getSqlElementsFromCriteria(array("idProjectExpense"=>$obj->id));
  $untaxedAmount = 0;
  $fullAmount = 0;
  $fullAmountPayment =0;
  $nbPayment = 0;
  foreach ($listProviderBill as $bill ){
    $untaxedAmount += $bill->totalUntaxedAmount;
    $fullAmount += $bill->totalFullAmount;
    $payment = new ProviderPayment();
    $listProviderPayment = $payment->getSqlElementsFromCriteria(array("idProviderBill"=>$bill->id));
    foreach ($listProviderPayment as $provPayment){
      $nbPayment++;
      $fullAmountPayment += $provPayment->paymentAmount;
    }
    $providerTerm = new ProviderTerm();
    $listProviderTerm = $providerTerm->getSqlElementsFromCriteria(array("idProviderBill"=>$bill->id));
    foreach ($listProviderTerm as $term){
      $arrayTerm[$term->id]=$term;
    }
  }
  
  foreach ($arrayTerm as $term){
    $nbTerm++;
    $untaxedAmountTerm += $term->untaxedAmount;
    $fullAmountTerm += $term->fullAmount;
  }
  echo '  <tr>';
  echo '    <td class="assignHeader" colspan="1" style="width:25%">'.i18n('menuProviderBill').'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($untaxedAmount).'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($fullAmount).'</td>';
  echo '    <td class="assignData" align="center" colspan="1" style="width:15%">'.count($listProviderBill).'</td>';
  echo '  </tr>';
  echo '  <tr>';
  echo '    <td class="assignHeader" colspan="1" style="width:25%">'.i18n('menuProviderTerm').'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($untaxedAmountTerm).'</td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($fullAmountTerm).'</td>';
  echo '    <td class="assignData" align="center" colspan="1" style="width:15%">'.$nbTerm.'</td>';
  echo '  </tr>';
  echo '  <tr>';
  echo '    <td class="assignHeader" colspan="1" style="width:25%">'.i18n('menuProviderPayment').'</td>';
  echo '    <td class="assignHeader" align="right" colspan="1" style="width:30%"></td>';
  echo '    <td class="assignData" align="right" colspan="1" style="width:30%">'.htmlDisplayCurrency($fullAmountPayment).'</td>';
  echo '    <td class="assignData" align="center" colspan="1" style="width:15%">'.$nbPayment.'</td>';
  echo '  </tr>';
  echo '</table>';
  echo '</td></tr>';
}


function drawOtherVersionFromObject($otherVersion, $obj, $type) {
  global $print;
  usort($otherVersion, "OtherVersion::sort");
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$otherVersion or count($otherVersion)==0) return;
  echo '<table>';
  foreach ($otherVersion as $vers) {
    if ($vers->id) {
      echo '<tr>';
      if ($obj->id and $canUpdate and !$print) {
        echo '<td style="width:20px">';
        echo '<a onClick="removeOtherVersion('."'".htmlEncode($vers->id)."'".', \''.SqlList::getNameFromId('Version', $vers->idVersion).'\''.', \''.htmlEncode($vers->scope).'\''.');" '.'title="'.i18n('otherVersionDelete').'" > '.formatSmallButton('Remove').'</a>';
        echo '</td>';
        echo '<td style="width:20px">';
        echo '<a onClick="swicthOtherVersionToMain('."'".htmlEncode($vers->id)."'".', \''.SqlList::getNameFromId('Version', $vers->idVersion).'\''.', \''.htmlEncode($vers->scope).'\''.');" '.'title="'.i18n('otherVersionSetMain').'" > '.formatSmallButton('Switch').'</a>';
        echo '</td>';
      }
      echo '<td>'.htmlEncode(SqlList::getNameFromId('Version', $vers->idVersion)).'</td>';
      echo '</tr>';
    }
  }
  echo '</table>';
}

function drawOtherClientFromObject($otherClient, $obj) {
  global $print;
  usort($otherClient, "OtherClient::sort");
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (!$otherClient or count($otherClient)==0) return;
  echo '<table>';
  foreach ($otherClient as $client) {
    if ($client->id) {
      echo '<tr>';
      if ($obj->id and $canUpdate and !$print) {
        echo '<td style="width:20px">';
        echo '<a onClick="removeOtherClient('."'".htmlEncode($client->id)."'".', \''.SqlList::getNameFromId('Client', $client->idClient).'\');" '.'title="'.i18n('otherClientDelete').'" > '.formatSmallButton('Remove').'</a>';
        echo '</td>';
        echo '<td style="width:20px">';
        echo '<a onClick="swicthOtherClientToMain('."'".htmlEncode($client->id)."'".', \''.SqlList::getNameFromId('Client', $client->idClient).'\');" '.'title="'.i18n('otherClientSetMain').'" > '.formatSmallButton('Switch').'</a>';
        echo '</td>';
      }
      echo '<td>'.htmlEncode(SqlList::getNameFromId('Client', $client->idClient)).'</td>';
      echo '</tr>';
    }
  }
  echo '</table>';
}

function drawChecklistFromObject($obj) {
  global $print, $noselect, $collapsedList, $displayWidth, $printWidth, $profile;
  if (!$obj or !$obj->id) return; // Don't try and display checklist for non existant objects
  $displayChecklist='NO';
  $crit="nameChecklistable='".get_class($obj)."' and idle=0";
  $type='id'.get_class($obj).'Type';
  if (property_exists($obj, $type)) {
    $crit.=' and (idType is null ';
    if ($obj->$type) {
      $crit.=" or idType='".$obj->$type."'";
    }
    $crit.=')';
  }
  $cd=new ChecklistDefinition();
  $cdList=$cd->getSqlElementsFromCriteria(null, false, $crit);
  if (count($cdList)==0) return; // Don't display checklist if non definition exist for it
  $user=getSessionUser();
  $habil=SqlElement::getSingleSqlElementFromCriteria('HabilitationOther', array('idProfile'=>$profile, 'scope'=>'checklist'));
  $list=new ListYesNo($habil->rightAccess);
  $displayChecklist=Parameter::getUserParameter('displayChecklist');
  if (!$displayChecklist) $displayChecklist='YES';
  if (!$noselect and $obj->id and $list->code=='YES' and ($displayChecklist=='YES' or $print)) {
    if ($print) {
      // echo '<table class="detail" width="'.$printWidth.'px;">';
      // echo '<tr><td>';
      include_once "../tool/dynamicDialogChecklist.php";
      // echo '</td></tr>';
      // echo '</table>';
    } else {
      $titlePane=get_class($obj)."_checklist";
      echo '<div style="width:'.$displayWidth.'" dojoType="dijit.TitlePane"';
      echo ' title="'.i18n('sectionChecklist').'"';
      echo ' open="'.((array_key_exists($titlePane, $collapsedList))?'false':'true').'"';
      echo ' id="'.$titlePane.'"';
      echo ' onHide="saveCollapsed(\''.$titlePane.'\');"';
      echo ' onShow="saveExpanded(\''.$titlePane.'\');"';
      echo '>';
      include_once "../tool/dynamicDialogChecklist.php";
      echo '</div>';
    }
  }
}

function setWidthPct($displayWidth, $print, $printWidth, $obj, $colSpan=null) {
  // scriptLog("setWidthPct(displayWidth=$displayWidth, print=$print, printWidth=$printWidth, obj,colSpan=$colSpan)");
  if (intval($displayWidth)<=0 and intval($printWidth)>0) {
    $displayWidth=(intval($printWidth)-50).'px';
  }
  $nbCol=getNbColMax($displayWidth, $print, $printWidth, $obj);
  if ($print) {
    $nbCol=1;
  }
  $widthPct=round(99/$nbCol)."%";
  if ($nbCol=='1') {
    $widthPct=$displayWidth;
  }
  if (substr($displayWidth, -2, 2)=="px") {
    $val=substr($displayWidth, 0, strlen($displayWidth)-2);
    $widthPct=floor(($val/$nbCol)-($nbCol+1))."px";
  }
  if ($colSpan and $nbCol>=$colSpan) {
    $widthPct=$colSpan*substr($widthPct, 0, strlen($widthPct)-2)."px";
  }
  if ($print) {
    $widthPct=round(($printWidth/$nbCol)-2*($nbCol-1))."px";
  }
  return $widthPct;
}

function getNbColMax($displayWidth, $print, $printWidth, $obj) {
  global $nbColMax;
  if ($displayWidth>1380) {
    $nbColMax=3;
  } else if ($displayWidth>900) {
    $nbColMax=2;
  } else {
    $nbColMax=1;
  }
  if (property_exists($obj, '_nbColMax')) {
    if ($nbColMax>$obj->_nbColMax) {
      $nbColMax=$obj->_nbColMax;
    }
  } else {
    if ($nbColMax>2) {
      $nbColMax=2;
    }
  }
  $paramMax=Parameter::getUserParameter('maxColumns');
  if ($paramMax and $paramMax<$nbColMax) $nbColMax=$paramMax;
  return $nbColMax;
}

function startBuffering() {
  global $reorg, $leftPane, $rightPane, $extraPane, $bottomPane, $nbColMax, $section;
  if (!$reorg) return;
  ob_start();
}

function endBuffering($prevSection, $included) {
  global $reorg, $leftPane, $rightPane, $extraPane, $bottomPane, $nbColMax, $section, $beforeAllPanes;
  $sectionPosition=array(
      'assignment'=>array('2'=>'left', '3'=>'extra'), 
      'attachment'=>array('2'=>'bottom', '3'=>'extra'), 
      'attendees'=>array('2'=>'right', '3'=>'extra'), 
      'billline'=>array('2'=>'bottom', '3'=>'bottom'), 
      'billlineterm'=>array('2'=>'bottom', '3'=>'bottom'), 
      'calendar'=>array('2'=>'bottom', '3'=>'bottom'), 
      'description'=>array('2'=>'left', '3'=>'left'), 
      'evaluation'=>array('2'=>'left', '3'=>'extra'), 
      'evaluationcriteria'=>array('2'=>'right', '3'=>'extra'), 
      'expensedetail'=>array('2'=>'bottom', '3'=>'bottom'), 
      'helpallowedwords'=>array('3'=>'bottom', '3'=>'extra'), 
      'helpallowedreceivers'=>array('3'=>'bottom', '3'=>'extra'), 
      'hierarchicorganizationprojects'=>array('2'=>'bottom', '3'=>'extra'), 
      'iban'=>array('2'=>'right', '3'=>'extra'), 
      'internalalert'=>array('2'=>'right', '3'=>'extra'), 
      'link'=>array('2'=>'bottom', '3'=>'extra'), 
      'link_requirement'=>array('2'=>'bottom', '3'=>'extra'), 
      'link_deliverable'=>array('2'=>'left', '3'=>'extra'), 
      'link_activity'=>array('2'=>'left', '3'=>'extra'),
      'listtypeusingworkflow'=>array('2'=>'right', '3'=>'extra'), 
      'lock'=>array('2'=>'left', '3'=>'left'), 
      'mailtext'=>array('2'=>'bottom', '3'=>'bottom'), 
      'miscellaneous'=>array('2'=>'right', '3'=>'extra'), 
      'note'=>array('2'=>'bottom', '3'=>'extra'), 
      'notificationtitle'=>array('2'=>'left', '3'=>'left'), 
      'notificationrule'=>array('2'=>'left', '3'=>'left'), 
      'notificationcontent'=>array('2'=>'left', '3'=>'right'), 
      'notification'=>array('3'=>'bottom', '3'=>'extra'), 
      'predecessor'=>array('2'=>'bottom', '3'=>'bottom'), 
      'projectsofobject'=>array('2'=>'bottom', '3'=>'extra'), 
      'progress'=>array('2'=>'right', '3'=>'extra'), 
      'progress_left'=>array('2'=>'left', '3'=>'extra'), 
      'progress_center'=>array('2'=>'right', '3'=>'right'), 
      'productcomponent'=>array('2'=>'left', '3'=>'extra'), 
      'providerterm'=>array('2'=>'right', '3'=>'extra'), 
      'receivers'=>array('3'=>'bottom', '3'=>'extra'), 
      'resourcesofobject'=>array('2'=>'bottom', '3'=>'extra'), 
      'resourcecost'=>array('2'=>'right', '3'=>'extra'), 
      'subbudgets'=>array('2'=>'right', '3'=>'extra'), 
      'submissions'=>array('2'=>'right', '3'=>'extra'), 
      'successor'=>array('2'=>'bottom', '3'=>'bottom'), 
      'target'=>array('2'=>'bottom', '3'=>'extra'), 
      'treatment_right'=>array('2'=>'right', '3'=>'extra'), 
      'link_testcase'=>array('2'=>'bottom', '3'=>'extra'), 
      'testcaserun'=>array('2'=>'bottom', '3'=>'bottom'), 
      'testcaserunsummary'=>array('2'=>'left', '3'=>'extra'), 
      'testcasesummary'=>array('2'=>'right', '3'=>'extra'),
      'totalfinancialsynthesis'=>array('2'=>'right', '3'=>'extra'),
      'void'=>array('2'=>'right', '3'=>'right'), 
      'workflowdiagram'=>array('2'=>'bottom', '3'=>'bottom'), 
      'workflowstatus'=>array('2'=>'bottom', '3'=>'bottom'));
  
  // ADD BY TABARY Marc - 2017-06-06 - USE OR NOT ORGANIZATION BUDGETELEMENT
  // if(Parameter::getGlobalParameter('useOrganizationBudgetElement')==="YES") {
  // $sectionPosition['hierarchicorganizationprojects'] = array('2'=>'bottom', '3'=>'extra');
  // }
  if (!$reorg) return;
  $display=ob_get_clean();
  if (!$prevSection and !$included) {
    $beforeAllPanes=$display;
    return;
  }
  if ($nbColMax==1) {
    $leftPane.=$display;
  } else {
    $position='right'; // Not placed sections are located right (default)
    $sectionName=strtolower($prevSection);
    if (isset($sectionPosition[$sectionName]) and isset($sectionPosition[$sectionName][$nbColMax])) {
      $position=$sectionPosition[$sectionName][$nbColMax];
    } else {
      if (substr($sectionName,-5)=='_left') {
        $position='left';
      } else if (substr($sectionName,-5)=='_center') {
        $position='center';
      } 
    }
    if ($position=='extra') {
      $extraPane.=$display;
    } else if ($position=='bottom') {
      $bottomPane.=$display;
    } else if ($position=='right') {
      $rightPane.=$display;
    } else if ($position=='left') {
      $leftPane.=$display;
    } else {
      traceLog("ERROR at endBuffering() : '$position' is not an expected position");
    }
  }
  // echo $display; // firt test !!!
}

function finalizeBuffering() {
  global $reorg, $leftPane, $rightPane, $extraPane, $bottomPane, $nbColMax, $section, $beforeAllPanes;
  if (!$reorg) return;
  if (!$leftPane and $rightPane) {
    $leftPane=$rightPane;
    $rightPane='';
  }
  // $leftPane="";$rightPane="";$extraPane="";$bottomPane="";
  echo $beforeAllPanes;
  echo '<table style="width=100%">';
  $showBorders=false;
  if ($nbColMax==1) {
    echo '<tr><td style="width:100%;vertical-align: top;'.(($showBorders)?'border:1px solid red':'').'">'.$leftPane.'</td></tr>';
    if ($rightPane) {
      echo '<tr><td style="width:100%;vertical-align: top;'.(($showBorders)?'border:1px solid green':'').'">'.$rightPane.'</td></tr>';
    }
    if ($bottomPane) {
      echo '<tr><td style="width:100%;vertical-align: top;'.(($showBorders)?'border:1px solid yellow':'').'">'.$bottomPane.'</td></tr>';
    }
    if ($extraPane) {
      echo '<tr><td style="width:100%;vertical-align: top;'.(($showBorders)?'border:1px solid blue':'').'">'.$extraPane.'</td></tr>';
    }
  } else if ($nbColMax==2) {
    echo '<tr><td style="width:50%;vertical-align: top;'.(($showBorders)?'border:1px solid red':'').'">'.$leftPane.'</td>'.'<td style="width:50%;vertical-align: top;'.(($showBorders)?'border:1px solid green':'').'">'.$rightPane.'</td>'.'</tr>';
    echo '<tr><td colspan="2" style="width:100%;vertical-align: top;'.(($showBorders)?'border:1px solid yellow':'').'">'.$bottomPane.'</td></tr>';
    if ($extraPane) {
      echo '<tr><td colspan="2" style="vertical-align: top;'.(($showBorders)?'border:1px solid blue':'').'">'.$extraPane.'</td></tr>';
    }
  } else if ($nbColMax==3) {
    echo '<tr style="height:10px">'.'<td style="width:33%;vertical-align: top;'.(($showBorders)?'border:1px solid red':'').'">'.$leftPane.'</td>'.'<td style="width:33%;vertical-align: top;'.(($showBorders)?'border:1px solid green':'').'">'.$rightPane.'</td>'.'<td rowspan="2" style="width:34%;vertical-align: top;'.(($showBorders)?'border:1px solid blue':'').'">'.$extraPane.'</td>'.'</tr>';
    echo '<tr><td colspan="2" style="width:66%;vertical-align: top;'.(($showBorders)?'border:1px solid yellow':'').'">'.$bottomPane.'</td></tr>';
  } else {
    traceLog("ERROR at finalizeBuffering() : '$nbColMax' is not an expected max column count");
  }
  echo '</table>';
}

function drawJobDefinitionFromObject($obj, $refresh=false) {
  global $cr, $print, $user, $browserLocale;
  $canUpdate=securityGetAccessRightYesNo('menu'.get_class($obj), 'update', $obj)=="YES";
  if ($obj->idle==1) {
    $canUpdate=false;
  }
  if (isset($obj->_JobDefinition)) {
    $lines=$obj->_JobDefinition;
  } else {
    $lines=array();
  }
  echo '<input type="hidden" id="JoblistDefinitionIdle" value="'.$obj->idle.'" />';
  echo '<table width="100%">';
  echo '<tr>';
  if (!$print) {
    echo '<th class="noteHeader" style="width:5%">'; // changer le header
    if ($obj->id!=null and !$print and $canUpdate) {
      echo '<a onClick="addJobDefinition('.$obj->id.');"'.' title="'.i18n('addLine').'" class="roundedButtonSmall">'.formatSmallButton('Add').'</a>';
    }
    echo '</th>';
  }
  echo '<th class="noteHeader" style="width: 20%">'.i18n('colSortOrder').'</th>';
  echo '<th class="noteHeader" style="width:'.(($print)?'60':'55').'%">'.i18n('colName').'</th>';
  echo '<th class="noteHeader" style="width: 20%">'.i18n('colDaysBeforeWarning').'</th>';
  echo '</tr>';
  
  usort($lines, "JobDefinition::sort");
  foreach ($lines as $line) {
    echo '<tr>';
    if (!$print) {
      echo '<td class="noteData" style="text-align:center;">';
      if ($canUpdate) {
        echo ' <a onClick="editJobDefinition('.$obj->id.','.$line->id.');"'.' title="'.i18n('editLine').'" class="roundedButtonSmall">'.formatSmallButton('Edit').'</a>';
        echo ' <a onClick="removeJobDefinition('.$line->id.');"'.' title="'.i18n('removeLine').'" class="roundedButtonSmall"> '.formatSmallButton('Remove').'</a>';
      }
      echo '</td>';
    }
    echo '<td class="noteData" title="'.$line->title.'">'.$line->sortOrder.'</td>';
    echo '<td class="noteData" title="'.$line->title.'">';
    echo "<table><tr><td>".htmlDisplayCheckbox(0)."&nbsp;</td><td valign='top'>".htmlEncode($line->name)."</td></tr></table>";
    echo '</td>';
    echo '<td class="noteData">';
    echo $line->daysBeforeWarning.' '.i18n('shortDay');
    echo '</td>';
    echo '</tr>';
  }
  echo '<tr>';
  if (!$print) {
    echo '<td class="noteDataClosetable">&nbsp;</td>';
  }
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '<td class="noteDataClosetable">&nbsp;</td>';
  echo '</tr>';
  echo '</table>';
}

function drawJoblistFromObject($obj) {
  global $print, $noselect, $collapsedList, $displayWidth, $printWidth, $profile;
  if (!$obj or !$obj->id) return; // Don't try and display joblist for non existing objects
  $crit="nameChecklistable='".get_class($obj)."' and idle=0";
  $type='id'.get_class($obj).'Type';
  if (property_exists($obj, $type)) {
    $crit.=' and (idType is null ';
    if ($obj->$type) {
      $crit.=" or idType='".$obj->$type."'";
    }
    $crit.=')';
  }
  $cd=new JoblistDefinition();
  $cdList=$cd->getSqlElementsFromCriteria(null, false, $crit);
  if (count($cdList)==0) return; // Don't display joblist if no definition exists for it
  $user=getSessionUser();
  $habil=SqlElement::getSingleSqlElementFromCriteria('HabilitationOther', array('idProfile'=>$profile, 'scope'=>'joblist'));
  $list=new ListYesNo($habil->rightAccess);
  if (!$noselect and $obj->id and $list->code=='YES') {
    if ($print) {
      // echo '<table class="detail" width="'.$printWidth.'px;">';
      // echo '<tr><td>';
      include_once "../tool/dynamicDialogJoblist.php";
      // echo '</td></tr>';
      // echo '</table>';
    } else {
      $titlePane=get_class($obj)."_joblist";
      echo '<div style="width:'.$displayWidth.'" dojoType="dijit.TitlePane"';
      echo ' title="'.i18n('sectionJoblist').'"';
      echo ' open="'.((array_key_exists($titlePane, $collapsedList))?'false':'true').'"';
      echo ' id="'.$titlePane.'"';
      echo ' onHide="saveCollapsed(\''.$titlePane.'\');"';
      echo ' onShow="saveExpanded(\''.$titlePane.'\');"';
      echo '>';
      include_once "../tool/dynamicDialogJoblist.php";
      echo '</div>';
    }
  }
}
?>