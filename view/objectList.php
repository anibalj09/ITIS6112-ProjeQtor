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
 * Presents the list of objects of a given class.
 *
 */
require_once "../tool/projeqtor.php";
scriptLog('   ->/view/objectList.php');

if (! isset($comboDetail)) {
  $comboDetail=false;
}
$objectClass=$_REQUEST['objectClass'];
Security::checkValidClass($objectClass);
$objectType='';
if (array_key_exists('objectType',$_REQUEST)) {
  $objectType=$_REQUEST['objectType'];
}
$objectClient='';
if (array_key_exists('objectClient',$_REQUEST)) {
  $objectClient=$_REQUEST['objectClient'];
}
$objectElementable='';
if (array_key_exists('objectElementable',$_REQUEST)) {
  $objectElementable=$_REQUEST['objectElementable'];
}
$obj=new $objectClass;

if (array_key_exists('Directory', $_REQUEST)) {
  setSessionValue('Directory', $_REQUEST['Directory']);
} else {
  unsetSessionValue('Directory');
}
$multipleSelect=false;
if (array_key_exists('multipleSelect', $_REQUEST)) {
  if ($_REQUEST['multipleSelect']) {
    $multipleSelect=true;
  }
}
$showIdle=(! $comboDetail and sessionValueExists('projectSelectorShowIdle') and getSessionValue('projectSelectorShowIdle')==1)?1:0;
if (! $comboDetail and is_array( getSessionUser()->_arrayFilters)) {
  if (array_key_exists($objectClass, getSessionUser()->_arrayFilters)) {
    $arrayFilter=getSessionUser()->_arrayFilters[$objectClass];
    foreach ($arrayFilter as $filter) {
      if ($filter['sql']['attribute']=='idle' and $filter['sql']['operator']=='>=' and $filter['sql']['value']=='0') {
        $showIdle=1;
      }
    }
  }
}

$displayWidthList="1980";
if (RequestHandler::isCodeSet('destinationWidth')) {
  $displayWidthList=RequestHandler::getNumeric('destinationWidth');
}
$rightWidthVal=0;
if (isset($rightWidth)) {
  if (substr($rightWidth,-1)=="%") {
    $rightWidthVal=(intval(str_replace('%', '', $rightWidth))/100)*$displayWidthList;
  } else {
    $rightWidthVal=intval(str_replace('px', '', $rightWidth));
  }
} else {
  $detailRightDivWidth=Parameter::getUserParameter('contentPaneRightDetailDivWidth'.$objectClass);
  if (!$detailRightDivWidth) $detailRightDivWidth=0;
  if($detailRightDivWidth or $detailRightDivWidth==="0"){
    $rightWidthVal=$detailRightDivWidth;
  } else {
    $rightWidth=0;//15/100*$displayWidthList;
  }
}
$displayWidthList-=$rightWidthVal;

$hideTypeSearch=false;
$hideClientSearch=false;
$hideNameSearch=false;
$hideIdSearch=false;
$hideShowIdleSearch=false;
$hideEisSearch=false;
$referenceWidth=50;
if ($comboDetail) {
  $screenWidth=getSessionValue('screenWidth',$displayWidthList);
  $displayWidthList=round($screenWidth*0.55,0)+150;
}
if ($displayWidthList<1400) {
  $referenceWidth=40;
  if ($displayWidthList<1250) {
    $referenceWidth=30;
    if ($displayWidthList<1165) {
      $hideClientSearch=true;
      $hideEisSearch=true;
      if ($displayWidthList<1025) {
        $hideTypeSearch=true;
        if ($displayWidthList<700) {
          $hideIdSearch=true;
          if ($displayWidthList<650) {
            $hideShowIdleSearch=true;
            if ($displayWidthList<550) {
              $hideNameSearch=true;
            }
          }
        }
      }
    }
  }
}
if ($comboDetail) $referenceWidth-=5;

$iconClassName=((SqlElement::is_subclass_of($objectClass, 'PlgCustomList'))?'ListOfValues':$objectClass);

$allProjectsChecked=false;
if (RequestHandler::getValue('objectClass')=='Project' and RequestHandler::getValue('mode')=='search') {
  $allProjectsChecked=true;
}

?>
<div dojoType="dojo.data.ItemFileReadStore" id="objectStore" jsId="objectStore" clearOnClose="true"
  url="../tool/jsonQuery.php?objectClass=<?php echo $objectClass;?><?php echo ($comboDetail)?'&comboDetail=true':'';?><?php echo ($showIdle)?'&idle=true':'';?><?php echo ($allProjectsChecked)?'&showAllProjects=on':'';?>" >
</div>
<div dojoType="dijit.layout.BorderContainer">
<div dojoType="dijit.layout.ContentPane" region="top" id="listHeaderDiv">
  <form dojoType="dijit.form.Form" id="quickSearchListForm" action="" method="" >
  <script type="dojo/method" event="onSubmit" >
    quickSearchExecute();
    return false;        
  </script>
  <div class="listTitle" id="quickSearchDiv" 
     style="display:none; height:100%; width: 100%; position: absolute;">
    <table >
      <tr height="100%" style="vertical-align: middle;">
        <td width="50px" align="center">        
         <div style="position:absolute; top:0px;left:5px ;" class="icon<?php echo $iconClassName;?>32 icon<?php echo $iconClassName;?> iconSize32" style="margin-left:9px;width:32px;height:32px" /></div>    
        </td>
        <td><span class="title" ><?php echo i18n("menu" . $objectClass);?></span></td>
        <td style="text-align:right;" width="200px">
                <span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php echo i18n("quickSearch");?>
                &nbsp;</span> 
        </td>
        <td style="vertical-align: middle;">
          <div title="<?php echo i18n('quickSearch')?>" type="text" class="filterField rounded" dojoType="dijit.form.TextBox" 
             id="quickSearchValue" name="quickSearchValue"
             style="width:200px;">
          </div>
        </td>
	      <td style="width:36px">            
	        <button title="<?php echo i18n('quickSearch')?>"  
	          dojoType="dijit.form.Button" 
	          id="listQuickSearchExecute" name="listQuickSearchExecute"
	          iconClass="dijitButtonIcon dijitButtonIconSearch" class="detailButton" showLabel="false">
	          <script type="dojo/connect" event="onClick" args="evt">
              //dijit.byId('quickSearchListForm').submit();
              quickSearchExecute();
          </script>
	        </button>
	      </td>      
        <td style="width:36px">
          <button title="<?php echo i18n('comboCloseButton')?>"  
            dojoType="dijit.form.Button" 
            id="listQuickSearchClose" name="listQuickSearchClose"
            iconClass="dijitButtonIcon dijitButtonIconUndo" class="detailButton" showLabel="false">
            <script type="dojo/connect" event="onClick" args="evt">
              quickSearchClose();
            </script>
          </button>
        </td>    
      </tr>
    </table>
  </div>
  </form>
<table width="100%" class="listTitle" >
  <tr >
    <td width="50px" align="center">
       <div style="position:absolute;left:0px;width:43px;top:0px;height:36px;" class="iconHighlight">&nbsp;</div>
       <div style="position:absolute; top:0px;left:5px ;" class="icon<?php echo $iconClassName;?>32 icon<?php echo $iconClassName;?> iconSize32" style="margin-left:9px;width:32px;height:32px" /></div>
    </td>
    <td><span class="title"><?php echo i18n("menu" . $objectClass);?></span></td>
    <td>   
      <form dojoType="dijit.form.Form" id="listForm" action="" method="" >
        <script type="dojo/method" event="onSubmit" >
          return false;        
        </script>  
        <input type="hidden" id="objectClass" name="objectClass" value="<?php echo $objectClass;?>" />  
        <input type="hidden" id="objectId" name="objectId" value="<?php if (isset($_REQUEST['objectId']))  { echo htmlEncode($_REQUEST['objectId']);}?>" />
        <input type="hidden" id="objectClassList" name="objectClassList" value="<?php echo $objectClass;?>" />
        <table style="width: 100%; height: 27px;">
          <tr>
          <?php if ( ! $hideIdSearch ) { ?>
            <td style="text-align:right;" width="5px">
              <span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;
              <?php echo i18n("colId");?>
              &nbsp;</span> 
            </td>
            <td width="5px">
              <div title="<?php echo i18n('filterOnId')?>" style="width:<?php echo $referenceWidth;?>px" class="filterField rounded" dojoType="dijit.form.TextBox" 
               type="text" id="listIdFilter" name="listIdFilter">
                <script type="dojo/method" event="onKeyUp" >
					        setTimeout("filterJsonList()",10);
                </script>
              </div>
            </td>
            <?php }?>
              <?php if ( ! $hideNameSearch and property_exists($obj,'name')) { ?>
              <td style="text-align:right;" width="5px">
                <span class="nobr">&nbsp;&nbsp;&nbsp;
                <?php echo i18n("colName");?>
                &nbsp;</span> 
              </td>
              <td width="5px">
                <div title="<?php echo i18n('filterOnName')?>" type="text" class="filterField rounded" dojoType="dijit.form.TextBox" 
                id="listNameFilter" name="listNameFilter" style="width:<?php echo $referenceWidth*2;?>px">
                  <script type="dojo/method" event="onKeyUp" >
                  	setTimeout("filterJsonList()",10);
                </script>
                </div>
              </td>
              <?php }?>              
              <?php if ( !$hideTypeSearch and property_exists($obj,'id' . $objectClass . 'Type') ) { ?>
              <td style="vertical-align: middle; text-align:right;" width="5px">
                 <span class="nobr">&nbsp;&nbsp;&nbsp;
                <?php echo i18n("colType");?>
                &nbsp;</span>
              </td>
              <td width="5px">
                <select title="<?php echo i18n('filterOnType')?>" type="text" class="filterField roundedLeft" dojoType="dijit.form.FilteringSelect"
                <?php echo autoOpenFilteringSelect();?> 
                id="listTypeFilter" name="listTypeFilter" style="width:<?php echo $referenceWidth*4;?>px">
                  <?php htmlDrawOptionForReference('id' . $objectClass . 'Type', $objectType, $obj, false); ?>
                  <script type="dojo/method" event="onChange" >
                    refreshJsonList('<?php echo $objectClass;?>');
                  </script>
                </select>
              </td>
              <?php }?>
              <?php if ( $objectClass=='GlobalView') { ?>
              <td style="vertical-align: middle; text-align:right;" width="5px">
                 <span class="nobr">&nbsp;&nbsp;&nbsp;
                <?php echo i18n("listTodayItems");?>&nbsp;
              </td>
              <td width="5px">
                <div dojoType="dijit.form.DropDownButton"							    
  							  id="listItemsSelector" jsId="listItemsSelector" name="listItemsSelector" 
  							  showlabel="false" class="comboButton" iconClass="iconGlobalView iconSize22" 
  							  title="<?php echo i18n('itemSelector');?>">
                  <span>title</span>
  							  <div dojoType="dijit.TooltipDialog" class="white" id="listItemsSelectorDialog"
  							    style="position: absolute; top: 50px; right: 40%">   
                    <script type="dojo/connect" event="onShow" args="evt">
                      oldSelectedItems=dijit.byId('globalViewSelectItems').get('value');
                    </script>                 
                    <div style="text-align: center;position: relative;"> 
                      <button title="" dojoType="dijit.form.Button" 
                        class="mediumTextButton" id="" name="" showLabel="true"><?php echo i18n('buttonOK');?>
                        <script type="dojo/connect" event="onClick" args="evt">
                          dijit.byId('listItemsSelector').closeDropDown();
                        </script>
                      </button>
                      <div style="position: absolute;top: 34px; right:42px;"></div>
                    </div>   
                    <div style="height:5px;border-bottom:1px solid #AAAAAA"></div>    
  							    <div>                       
  							      <?php GlobalView::drawGlobalizableList();?>
  							    </div>
                    <div style="height:5px;border-top:1px solid #AAAAAA"></div>    
                    <div style="text-align: center;position: relative;">
                      <button title="" dojoType="dijit.form.Button" 
                         class="mediumTextButton" id="" name="" showLabel="true"><?php echo i18n('buttonOK');?>
                        <script type="dojo/connect" event="onClick" args="evt">
                          dijit.byId('listItemsSelector').closeDropDown();
                        </script>
                      </button>
                      <div style="position: absolute;bottom: 33px; right:42px;" ></div>
                    </div>   
  							  </div>
  							</div>                   
              </td>
              <?php }?>
             <?php  if (sessionValueExists('project')){
                 $proj=getSessionValue('project');
               }else{
                  $proj = '*';
               }
            if($comboDetail && property_exists($objectClass,'idProject') && $proj != '*'){
               ?> 
            <td style="width:200px;text-align: right; align: right;min-width:150px" >
                &nbsp;&nbsp;<?php echo i18n("showAllProjects");?>
              </td>
              <td style="width:10px;text-align: center; align: center;white-space:nowrap;">&nbsp;
                <div title="<?php echo i18n('showAllProjects')?>" dojoType="dijit.form.CheckBox" type="checkbox" class="whiteCheck"
                  id="showAllProjects" name="showAllProjects" <?php if ($allProjectsChecked) echo "checked=ckecked"?>>
                  <script type="dojo/method" event="onChange" >
                    refreshJsonList('<?php echo $objectClass;?>');
                  </script>
                </div>&nbsp;
              </td>
              <?php }?>
              <!-- ADD qCazelles - Predefined Action -->
              <?php
				if ($objectClass == 'Action' and Parameter::getGlobalParameter('enablePredefinedActions') == 'YES') { ?>
              <td style="vertical-align: middle; text-align:right;" width="5px">
                 <span class="nobr">&nbsp;&nbsp;&nbsp;
                <?php echo i18n("predefinedAction");?>
                &nbsp;</span>
              </td>
              <td width="5px">
                <select title="<?php echo i18n('predefinedAction')?>" type="text" class="filterField roundedLeft" dojoType="dijit.form.FilteringSelect"
                <?php echo autoOpenFilteringSelect();?> 
                id="listPredefinedActions" name="listPredefinedActions" style="width:<?php echo $referenceWidth*4;?>px">                
                  <?php htmlDrawOptionForReference('idPredefinedAction', null); ?>
                  <script type="dojo/method" event="onChange" >
					id=dojo.byId('objectId');
	        		if (id) { 	
		    			id.value="";
		     			unselectAllRows("objectGrid");
					}
					loadContent("objectDetail.php", "detailDiv", dojo.byId('listForm'));
					setTimeout(loadPredefinedAction, 100, "<?php echo getEditorType(); ?>");
                  </script>
                </select>
              </td>
              <?php } ?>
              <!-- END ADD qCazelles -->
              
              <?php if ( !$hideClientSearch and property_exists($obj,'idClient') ) { ?>
              <td style="vertical-align: middle; text-align:right;" width="5px">
                 <span class="nobr">&nbsp;&nbsp;&nbsp;
                <?php echo i18n("colClient");?>
                &nbsp;</span>
              </td>
              <td width="5px">
                <select title="<?php echo i18n('filterOnClient')?>" type="text" class="filterField roundedLeft" dojoType="dijit.form.FilteringSelect"
                <?php echo autoOpenFilteringSelect();?> 
                data-dojo-props="queryExpr: '*${0}*',autoComplete:false"
                id="listClientFilter" name="listClientFilter" style="width:<?php echo $referenceWidth*4;?>px">
                  <?php htmlDrawOptionForReference('idClient', $objectClient, $obj, false); ?>
                  <script type="dojo/method" event="onChange" >
                    refreshJsonList('<?php echo $objectClass;?>');
                  </script>
                </select>
              </td>
              <?php } 
                 $elementable=null;
                 if ( property_exists($obj,'idMailable') ) $elementable='idMailable';
                 else if (property_exists($obj,'idIndicatorable')) $elementable='idIndicatorable';
                 else if (property_exists($obj,'idTextable')) $elementable='idTextable';
                 else if ( property_exists($obj,'idChecklistable')) $elementable='idChecklistable';
                 //$elementable=null;
                 if ($elementable) { ?>
              <td style="vertical-align: middle; text-align:right;" width="5px">
                 <span class="nobr">&nbsp;&nbsp;&nbsp;
                <?php echo i18n("colElement");?>
                &nbsp;</span>
              </td>
              <td width="5px">
                <select title="<?php echo i18n('filterOnElement')?>" type="text" class="filterField roundedLeft" dojoType="dijit.form.FilteringSelect"
                <?php echo autoOpenFilteringSelect();?> 
                id="listElementableFilter" name="listElementableFilter" style="width:140px">
                  <?php htmlDrawOptionForReference($elementable, $objectElementable, $obj, false); ?>
                  <script type="dojo/method" event="onChange" >
                    refreshJsonList('<?php echo $objectClass;?>');
                  </script>
                </select>
              </td>
              <?php }?>                     
              <?php $activeFilter=false;
                 if (! $comboDetail and is_array(getSessionUser()->_arrayFilters)) {
                   if (array_key_exists($objectClass, getSessionUser()->_arrayFilters)) {
                     if (count(getSessionUser()->_arrayFilters[$objectClass])>0) {
                     	//CHANGE qCazelles - Dynamic filter - Ticket #78
                     	//Old
                     	//$activeFilter=true;
                     	//New
                     	//A filter with isDynamic=1 is not active
                     	foreach (getSessionUser()->_arrayFilters[$objectClass] as $filter) {
                     		if (!isset($filter['isDynamic']) or $filter['isDynamic']=="0") {
                     			$activeFilter=true;
                     		}
                     	}
                     	//END CHANGE qCazelles - Dynamic filter - Ticket #78
                     }
                   }
                 } else if ($comboDetail and is_array(getSessionUser()->_arrayFiltersDetail)) {
                   if (array_key_exists($objectClass, getSessionUser()->_arrayFiltersDetail)) {
                     if (count(getSessionUser()->_arrayFiltersDetail[$objectClass])>0) {
                     	//CHANGE qCazelles - Dynamic filter - Ticket #78
                     	//Old
                     	//$activeFilter=true;
                     	//New
                     	foreach (getSessionUser()->_arrayFiltersDetail[$objectClass] as $filter) {
                     	  //CHANGE qCazelles - Ticket 165
                     	  //Old
                     	  //if (!isset($filter['isDynamic']) or $filter['isDynamic']=="0") {
                     	  //New
                     		if ((!isset($filter['isDynamic']) or $filter['isDynamic']=="0") and (!isset($filter['hidden']) or $filter['hidden']=="0")) {
                     		//END CHANGE qCazelles - Ticket 165
                     			$activeFilter=true;
                     		}
                     	}
                     	//END CHANGE qCazelles - Dynamic filter - Ticket #78
                     }
                   }
                 }
                 ?>
            <td >&nbsp;</td>
            <td width="5px"><span class="nobr">&nbsp;</span></td>
            <!-- CHANGE qCazelles - Filter by status button is moved here -->
            <?php //Filter by status button is moved here
              if ( property_exists($obj, 'idStatus') and Parameter::getGlobalParameter('filterByStatus') == 'YES' and $objectClass!='GlobalView') {  ?>
            <td width="36px">
            	<button title="<?php echo i18n('filterByStatus');?>"
			             dojoType="dijit.form.Button"
			             id="iconStatusButton" name="iconStatusButton"
			             iconClass="dijitButtonIcon dijitButtonIconStatusChange" class="detailButton" showLabel="false">
			             <script type="dojo/connect" event="onClick" args="evt">
						         if (dijit.byId('barFilterByStatus').domNode.style.display == 'none') {
							         dijit.byId('barFilterByStatus').domNode.style.display = 'block';
						         } else {
							         dijit.byId('barFilterByStatus').domNode.style.display = 'none';
						         }
						         dijit.byId('barFilterByStatus').getParent().resize();
                     saveDataToSession("displayByStatusList_<?php echo $objectClass;?>", dijit.byId('barFilterByStatus').domNode.style.display, true);
          				 </script>
			        </button>
			      </td>
			      <?php } ?>
<?php if (! $comboDetail or 1) {?>            
            <td width="36px">
              <button title="<?php echo i18n('quickSearch')?>"  
               dojoType="dijit.form.Button" 
               id="iconSearchOpenButton" name="iconSearchOpenButton"
               iconClass="dijitButtonIcon dijitButtonIconSearch" class="detailButton" showLabel="false">
                <script type="dojo/connect" event="onClick" args="evt">
                  quickSearchOpen();
                </script>
              </button>
              <span id="gridRowCountShadow1" class="gridRowCountShadow1"></span>
              <span id="gridRowCountShadow2" class="gridRowCountShadow2"></span>              
              <span id="gridRowCount" class="gridRowCount"></span>             
              <input type="hidden" id="listFilterClause" name="listFilterClause" value="" style="width: 50px;" />
            </td>
<?php }
      if (! $comboDetail or 1) {?>            
            <td width="51px">
              <button 
              title="<?php echo i18n('advancedFilter')?>"  
               class="comboButton"
               dojoType="dijit.form.DropDownButton" 
               id="listFilterFilter" name="listFilterFilter"
               iconClass="icon<?php echo($activeFilter)?'Active':'';?>Filter" showLabel="false">
                <script type="dojo/connect" event="onClick" args="evt">
                  showFilterDialog();
                </script>
                <script type="dojo/method" event="onMouseEnter" args="evt">
                  clearTimeout(closeFilterListTimeout);
                  clearTimeout(openFilterListTimeout);
                  openFilterListTimeout=setTimeout("dijit.byId('listFilterFilter').openDropDown();",popupOpenDelay);
                </script>
                <script type="dojo/method" event="onMouseLeave" args="evt">
                  clearTimeout(openFilterListTimeout);
                  closeFilterListTimeout=setTimeout("dijit.byId('listFilterFilter').closeDropDown();",2000);
                </script>
                <div dojoType="dijit.TooltipDialog" id="directFilterList" style="z-index: 999999;<!-- display:none; --> position: absolute;">
                  <?php 
                     if ($comboDetail) $_REQUEST['comboDetail']=true;
                     include "../tool/displayFilterList.php";?>
                 <script type="dojo/method" event="onMouseEnter" args="evt">
                    clearTimeout(closeFilterListTimeout);
                    clearTimeout(openFilterListTimeout);
                </script>
                <script type="dojo/method" event="onMouseLeave" args="evt">
                  dijit.byId('listFilterFilter').closeDropDown();
                </script>
                </div> 
              </button>
            </td>
<?php }?>   
<?php if (! $comboDetail) {?>  
            <td width="51px">           
							<div dojoType="dijit.form.DropDownButton"							    
							  id="listColumnSelector" jsId="listColumnSelector" name="listColumnSelector" 
							  showlabel="false" class="comboButton" iconClass="dijitButtonIcon dijitButtonIconColumn" 
							  title="<?php echo i18n('columnSelector');?>">
                <span>title</span>
							  <div dojoType="dijit.TooltipDialog" class="white" id="listColumnSelectorDialog"
							    style="position: absolute; top: 50px; right: 40%">   
                  <script type="dojo/connect" event="onHide" args="evt">
                    if (dndMoveInProgress) { this.show(); }
                  </script>
                  <script type="dojo/connect" event="onShow" args="evt">
                    recalculateColumnSelectorName();
                  </script>                 
                  <div style="text-align: center;position: relative;"> 
                    <button dojoType="dijit.form.Button" title="<?php echo i18n('titleResetList');?>"
                        class="mediumTextButton" id="" name="" showLabel="true"><?php echo i18n('buttonReset');?>
                        <script type="dojo/connect" event="onClick" args="evt">
                        resetListColumn();
                      </script>
                      </button>
                    <button title="" dojoType="dijit.form.Button" 
                      class="mediumTextButton" id="" name="" showLabel="true"><?php echo i18n('buttonOK');?>
                      <script type="dojo/connect" event="onClick" args="evt">
                        validateListColumn();
                      </script>
                    </button>
                    <div style="position: absolute;top: 34px; right:42px;" id="columnSelectorTotWidthTop"></div>
                  </div>   
                  <div style="height:5px;border-bottom:1px solid #AAAAAA"></div>    
							    <div id="dndListColumnSelector" jsId="dndListColumnSelector" dojotype="dojo.dnd.Source"  
							      dndType="column"
							      withhandles="true" class="container">                       
							      <?php include('../tool/listColumnSelector.php')?>
							    </div>
                  <div style="height:5px;border-top:1px solid #AAAAAA"></div>    
                  <div style="text-align: center;position: relative;">
	                  <button dojoType="dijit.form.Button" title="<?php echo i18n('titleResetList');?>"
	                      class="mediumTextButton" id="" name="" showLabel="true"><?php echo i18n('buttonReset');?>
	                      <script type="dojo/connect" event="onClick" args="evt">
                        resetListColumn();
                      </script>
	                    </button>
                    <button title="" dojoType="dijit.form.Button" 
                       class="mediumTextButton" id="" name="" showLabel="true"><?php echo i18n('buttonOK');?>
                      <script type="dojo/connect" event="onClick" args="evt">
                        validateListColumn();
                      </script>
                    </button>
                    <div style="position: absolute;bottom: 33px; right:42px;" id="columnSelectorTotWidthBottom"></div>
                  </div>   
							  </div>
							</div>   
             </td>
<?php }?>                 
<?php if (! $comboDetail) {?>                
             <td width="36px">
              <button title="<?php echo i18n('printList')?>"  
               dojoType="dijit.form.Button" 
               id="listPrint" name="listPrint"
               iconClass="dijitButtonIcon dijitButtonIconPrint" class="detailButton" showLabel="false">
                <script type="dojo/connect" event="onClick" args="evt">
                  showPrint("../tool/jsonQuery.php", 'list');
                </script>
              </button>
              </td>
<?php }?>            
<?php if (! $comboDetail) {?> 
<?php   $modePdf='Pdf';
        if (SqlElement::class_exists('TemplateReport')) {
          $tmpMode=TemplateReport::getMode($objectClass,null,'list');
          if ($tmpMode=='multi') {$modePdf='download multi';}
          else if ($tmpMode=='download' or $tmpMode=='show') {$modePdf='download';}
        }
      ?>       
             <td width="36px">
              <button title="<?php echo i18n('reportPrintPdf')?>"  
               dojoType="dijit.form.Button" 
               id="listPrintPdf" name="listPrintPdf"
               iconClass="dijitButtonIcon dijitButtonIcon<?php echo ucfirst($modePdf);?>" class="detailButton" showLabel="false">
                <script type="dojo/connect" event="onClick" args="evt">
                 <?php if (substr($modePdf,-5)=="multi" and SqlElement::class_exists('TemplateReport') ) {?>
                  selectTemplateForReport('<?php echo $objectClass?>','list');
                 <?php } else { ?> 
                  showPrint("../tool/jsonQuery.php", 'list', null, 'pdf');
                 <?php } ?>
                </script>
              </button>              
            </td>
            <td width="36px">
              <button title="<?php echo i18n('reportPrintCsv')?>"  
               dojoType="dijit.form.Button" 
               id="listPrintCsv" name="listPrintCsv"
               iconClass="dijitButtonIcon dijitButtonIconCsv" class="detailButton" showLabel="false">
                <script type="dojo/connect" event="onClick" args="evt">
                  openExportDialog('csv');
                  //showPrint("../tool/jsonQuery.php", 'list', null, 'csv');
                </script>
              </button>              
            </td>
            <td width="36px">
              <?php if ($objectClass=='GlobalView') {?>
              <div dojoType="dijit.form.DropDownButton"
                             class="comboButton"   
                             id="planningNewItem" jsId="planningNewItem" name="planningNewItem" 
                             showlabel="false" class="" iconClass="dijitButtonIcon dijitButtonIconNew"
                             title="<?php echo i18n('comboNewButton');?>">
                          <span>title</span>
                          <div dojoType="dijit.TooltipDialog" class="white" style="width:200px;">   
                            <div style="font-weight:bold; height:25px;text-align:center">
                            <?php echo i18n('comboNewButton');?>
                            </div>
                            <?php $arrayItems=GlobalView::getGlobalizables();
                            foreach($arrayItems as $item=>$itemName) {
                              $canCreate=securityGetAccessRightYesNo('menu' . $item,'create');
                              if ($canCreate=='YES') {
                                if (! securityCheckDisplayMenu(null,$item) ) {
                                  $canCreate='NO';
                                }
                              }
                              if ($canCreate=='YES') {?>
                              <div style="vertical-align:top;cursor:pointer;" class="dijitTreeRow"
                               onClick="addNewItem('<?php echo $item;?>');" >
                                <table width:"100%"><tr style="height:22px" >
                                <td style="vertical-align:top; width: 30px;padding-left:5px"><?php echo formatIcon($item, 22, null, false);;?></td>    
                                <td style="vertical-align:top;padding-top:2px"><?php echo i18n($item)?></td>
                                </tr></table>   
                              </div>
                              <div style="height:5px;"></div>
                              <?php } 
                              }?>
                          </div>
                        </div>
              <?php } else {?>
              <button id="newButtonList" dojoType="dijit.form.Button" showlabel="false"
                title="<?php echo i18n('buttonNew', array(i18n($_REQUEST['objectClass'])));?>"
                iconClass="dijitButtonIcon dijitButtonIconNew" class="detailButton">
                <script type="dojo/connect" event="onClick" args="evt">
		              dojo.byId("newButton").blur();
                  id=dojo.byId('objectId');
	                if (id) { 	
		                id.value="";
		                unselectAllRows("objectGrid");
                    if (switchedMode) {
                      setTimeout("hideList(null,true);", 1);
                    }
                    loadContent("objectDetail.php", "detailDiv", "listForm");
                    if (dijit.byId('detailRightDiv')) loadContent("objectStream.php", "detailRightDiv", "listForm");
                  } else { 
                    showError(i18n("errorObjectId"));
	                }
                </script>
              </button>
              <?php }?>
            </td>
<?php }?>   
      <td width="36px">
              <button id="newButtonRefresh" dojoType="dijit.form.Button" showlabel="false"
                title="<?php echo i18n('buttonRefreshList');?>"
                iconClass="dijitButtonIcon dijitButtonIconRefresh" class="detailButton">
                <script type="dojo/connect" event="onClick" args="evt">
	                 refreshGrid();
                </script>
              </button>
            </td>    
<?php if ( property_exists($obj,'isEis') and !$hideEisSearch) { ?>
              <td style="vertical-align: middle; width:15%; min-width:110px; text-align:right;white-space:normal;">
                <div style="max-height:32px;"> 
                <?php echo i18n("hideInService");?>
                </div>
              </td>
              <td style="width: 10px;text-align: center; align: center;white-space:nowrap;">&nbsp;
                <?php $hideInService=Parameter::getUserParameter('hideInService');?>
                <div title="<?php echo i18n('hideInService')?>" dojoType="dijit.form.CheckBox" 
                class="whiteCheck" <?php if ($hideInService=='true') echo " checked ";?>
                type="checkbox" id="hideInService" name="hideInService">
                <script type="dojo/method" event="onChange" >
                  saveDataToSession('hideInService',((this.checked)?true:false),true);
                  setTimeout("refreshJsonList('<?php echo $objectClass;?>');",50);
                </script>
              </div>&nbsp;
              </td>
              <?php }?> 
<?php if (! $hideShowIdleSearch and ! $comboDetail) {?> 
            <td style="text-align: right; width:10%; min-width:80px;width:150px;white-space:normal;">
              <?php echo i18n("labelShowIdle");?>
            </td>
            <td style="width: 10px;text-align: center; align: center;white-space:nowrap;">&nbsp;
              <div title="<?php echo i18n('labelShowIdle')?>" dojoType="dijit.form.CheckBox" 
                class="whiteCheck" <?php if ($showIdle) echo " checked ";?>
                type="checkbox" id="listShowIdle" name="listShowIdle">
                <script type="dojo/method" event="onChange" >
                  refreshJsonList('<?php echo $objectClass;?>');
                </script>
              </div>&nbsp;
            </td>
<?php } else {?>
       <input type="hidden" id="listShowIdle" name="listShowIdle" value="<?php echo $showIdle;?>">
<?php }?>           
          </tr>
        </table>    
      </form>
    </td>
  </tr>
</table>
<div id="listBarShow" class="dijitAccordionTitle" onMouseover="showList('mouse')" onClick="showList('click');">
  <div id="listBarIcon" align="center"></div>
</div>
</div>

<!-- ADD by qCazelles - Filter by Status -->
<?php if ( property_exists($obj, 'idStatus') and Parameter::getGlobalParameter('filterByStatus') == 'YES') {
  $displayStatus=Parameter::getUserParameter("displayByStatusList_$objectClass");
  if (!$displayStatus) $displayStatus='none';
?>

<?php
//change height of filter status bar depending of number of status.
$object = new $objectClass();
$listStatus = $object->getExistingStatus();
$height = ((floor((count($listStatus)-0.1)/10))+1)  * 20;
?>
  
<div class="listTitle" id="barFilterByStatus" dojoType="dijit.layout.ContentPane" region="top" style="display: <?php echo $displayStatus;?>;height:<?php echo $height; ?>px;min-width:1250px;line-height:18px;">
	<table style="position:absolute;top:2px">
		<tr>
			<td style="font-weight:bold;padding-left:50px;"><?php echo i18n("colIdStatus");?>&nbsp;:&nbsp;</td>
<?php
  $cptStatus=0;
	foreach ($listStatus as $status) {
		$cptStatus += 1;
?>		
			<td>
				<div id="showStatus<?php echo $cptStatus; ?>" title="<?php echo $status->name; ?>" dojoType="dijit.form.CheckBox" type="checkbox" value="<?php echo $status->id; ?>">
					<script type="dojo/method" event="onChange">
						refreshJsonList('<?php echo $objectClass; ?>');
					</script>
				</div>
				<?php echo $status->name; ?>&nbsp;&nbsp;
			</td>
<?php
	echo ($cptStatus % 10 == 0) ? '</tr><tr><td></td>' : '';
	 } ?>
		</tr>
	</table>
	<input type="hidden" id="countStatus" value="<?php echo $cptStatus; ?>" />
</div>
<?php 
} ?>
<!-- END ADD qCazelles -->

<div dojoType="dijit.layout.ContentPane" region="center" id="gridContainerDiv">
<table id="objectGrid" jsId="objectGrid" dojoType="dojox.grid.DataGrid"
  query="{ id: '*' }" store="objectStore"
  queryOptions="{ignoreCase:true}"
  rowPerPage="<?php echo Parameter::getGlobalParameter('paramRowPerPage');?>"
  columnReordering="false"
  rowSelector="false"
  loadingMessage="loading..."
  fastScroll="false"
  onHeaderClick="unselectAllRows('objectGrid');selectGridRow();"
  onHeaderCellContextMenu="dijit.byId('listColumnSelector').toggleDropDown();"
  selectionMode="<?php echo ($multipleSelect)?'extended':'single';?>" >
  <thead>
    <tr>
      <?php echo $obj->getLayout();?>
    </tr>
  </thead>
  <script type="dojo/connect" event="onSelected" args="evt">
    if (gridReposition) {return;}
    if (multiSelection) {updateSelectedCountMultiple();return;} 
	  if ( dojo.byId('comboDetail') ) {
      rows=objectGrid.selection.getSelected();
      row=rows[0]; 
      dojo.byId('comboDetailId').value=row.id;
      dojo.byId('comboDetailId').value=dojo.byId('comboDetailId').value.replace(/^[0]+/g,"");
      dojo.byId('comboDetailName').value=row.name;
      return true;
    }
    actionYes = function () {
      rows=objectGrid.selection.getSelected();
      row=rows[0]; 
      var id = row.id;
	    dojo.byId('objectId').value=id;
<?php if (get_class($obj)=='GlobalView') {?>
      dojo.byId('objectId').value=row.objectId;
      classNameCol=row.objectClass+"";
      className=classNameCol.split('|');
      dojo.byId('objectClass').value=className[1];
<?php }?>
	  //cleanContent("detailDiv");
      formChangeInProgress=false; 
      listClick();
      loadContent("objectDetail.php", "detailDiv", 'listForm');
      if (dijit.byId('detailRightDiv')) loadContent("objectStream.php", "detailRightDiv", 'listForm');
   	}
    actionNo = function () {
	    //unselectAllRows("objectGrid");
      selectRowById('objectGrid', parseInt(dojo.byId('objectId').value));
    }
    if (checkFormChangeInProgress(actionYes, actionNo)) {
      return true;
    }
  </script>
  <script type="dojo/connect" event="onDeselected" args="evt">
    if (multiSelection) {updateSelectedCountMultiple();return;}
  </script>
  <script type="dojo/method" event="onRowDblClick" args="row">
    if ( dojo.byId('comboDetail') ) {
      rows=objectGrid.selection.getSelected();
      row=rows[0]; 
      dojo.byId('comboDetailId').value=row.id;
      dojo.byId('comboDetailId').value=dojo.byId('comboDetailId').value.replace(/^[0]+/g,"");
      dojo.byId('comboDetailName').value=row.name;
      top.selectDetailItem();
      return;
    }
  </script>
  <script type="dojo/connect" event="_onFetchComplete" args="items, req">
     refreshGridCount();
  </script>
</table>
</div>
</div>