<?php

/*
 * @author: qCazelles 
 */

require_once "../tool/projeqtor.php";
scriptLog('   ->/view/versionsPlanningMain.php');

$listHeight='60%';
$topDetailDivHeight=Parameter::getUserParameter('contentPaneTopPlanningDivHeight');
$screenHeight=getSessionValue('screenHeight');
if ($screenHeight and $topDetailDivHeight>$screenHeight-300) {
	$topDetailDivHeight=$screenHeight-300;
}
$listHeight=($topDetailDivHeight)?$topDetailDivHeight.'px':$listHeight;
?>
<input type="hidden" name="objectClassManual" id="objectClassManual" value="VersionsPlanning" />
<input type="hidden" name="versionsPlanning" id="versionsPlanning" value="true" />
<div id="mainDivContainer" class="container" dojoType="dijit.layout.BorderContainer" onclick="hideDependencyRightClick();">
  <div id="listDiv" dojoType="dijit.layout.ContentPane" region="top" splitter="true" style="height:<?php echo $listHeight;?>;">
    <script type="dojo/connect" event="resize" args="evt">
         if (switchedMode) return;
             saveDataToSession("contentPaneTopPlanningDivHeight", dojo.byId("listDiv").offsetHeight, true);
    </script>
   <?php include 'versionsPlanningList.php'?>
  </div>
  <div id="detailDiv" dojoType="dijit.layout.ContentPane" region="center">
    <div id="detailBarShow" class="dijitAccordionTitle" onMouseover="hideList('mouse');" onClick="hideList('click');">
      <div id="detailBarIcon" align="center"></div>
    </div>
   <?php $noselect=true; //include 'objectDetail.php'; ?>
  </div>
</div>