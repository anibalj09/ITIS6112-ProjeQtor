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
 * Presents an object. 
 */
  require_once "../tool/projeqtor.php";
  scriptLog('   ->/view/objectMain.php');
  $listHeight='40%';
  $objectClass="";
  if (isset($_REQUEST['objectClass'])) {
    $objectClass=$_REQUEST['objectClass'];
    Security::checkValidClass($objectClass);
  	if ($_REQUEST['objectClass']=='CalendarDefinition') {
  		$listHeight='25%';
  	}
  	$topDetailDivHeight=Parameter::getUserParameter('contentPaneTopDetailDivHeight'.$objectClass);
  	$screenHeight=getSessionValue('screenHeight');
  	if ($screenHeight and $topDetailDivHeight>$screenHeight-300) {
  		$topDetailDivHeight=$screenHeight-300;
  	}
  	$listHeight=($topDetailDivHeight)?$topDetailDivHeight.'px':$listHeight; 	
  	$detailDivWidth=Parameter::getUserParameter('contentPaneRightDetailDivWidth'.$objectClass);
  	if (!$detailDivWidth) $detailDivWidth=0;
  	if($detailDivWidth or $detailDivWidth==="0"){
  	  $rightWidth=$detailDivWidth.'px';
  	} else {
  	  $rightWidth="0%";
  	}
  }
?>
<div id="mainDivContainer" class="container" dojoType="dijit.layout.BorderContainer" liveSplitters="false">
  <div dojoType="dijit.layout.ContentPane" region="center" splitter="true">
    <div class="container" dojoType="dijit.layout.BorderContainer" liveSplitters="false">
		  <div id="listDiv" dojoType="dijit.layout.ContentPane" region="top" splitter="true" style="height:<?php echo $listHeight;?>">
		   <script type="dojo/connect" event="resize" args="evt">
         if (switchedMode) return;
           saveDataToSession("contentPaneTopDetailDivHeight<?php echo $objectClass;?>", dojo.byId("listDiv").offsetHeight, true);
       </script>
		   <?php include 'objectList.php'?>
		  </div>
		  <div dojoType="dijit.layout.ContentPane" region="center">
			  <div class="container" dojoType="dijit.layout.BorderContainer" liveSplitters="false">
			   <?php if (property_exists($objectClass, '_Note')) {?>
				  <div id="hideStreamButton" style="cursor:pointer;position:absolute; right:-2px; bottom:2px;z-index:999999">
		        <a onClick="hideStreamMode(false);" id="buttonSwitchedStream" title="" ><span style="top:0px;display:inline-block;width:20px;height:22px;"><div class='iconHideStream22' style='' >&nbsp;</div></span></a>
		      </div>
		     <?php }?>
				  <div id="detailDiv" dojoType="dijit.layout.ContentPane" region="center" >
				   <?php $noselect=true; include 'objectDetail.php'; ?>
				  </div>
				</div>
		  </div>
    </div>
  </div>
  <?php if (property_exists($objectClass, '_Note')) {?>
  <div id="detailRightDiv" dojoType="dijit.layout.ContentPane" region="right" splitter="true" style="width:<?php echo $rightWidth;?>">
    <script type="dojo/connect" event="resize" args="evt">
             saveDataToSession("contentPaneRightDetailDivWidth<?php echo $objectClass;?>", dojo.byId("detailRightDiv").offsetWidth, true);
             var newWidth=dojo.byId("detailRightDiv").offsetWidth;
             dojo.query(".activityStreamNoteContainer").forEach(function(node, index, nodelist) {
              node.style.maxWidth=(newWidth-30)+"px";
             });
       </script>
    <script type="dojo/connect" event="onLoad" args="evt">
        scrollInto();
	  </script>
      <?php include 'objectStream.php'?>
  </div>
  <?php }?>  
</div>