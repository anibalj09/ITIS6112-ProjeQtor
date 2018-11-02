<?php
/*
 * @author: qCazelles
 */

require_once "../tool/projeqtor.php";
scriptLog('   ->/view/versionsPlanningList.php');

$startDate=date('Y-m-d');
$endDate=null;
$user=getSessionUser();
$saveDates=false;
$paramStart=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningStartDate'));
if ($paramStart->id) {
	$startDate=$paramStart->parameterValue;
	$saveDates=true;
}
$paramEnd=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningEndDate'));
if ($paramEnd->id) {
	$endDate=$paramEnd->parameterValue;
	$saveDates=true;
}

$saveShowWbsObj=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningShowWbs'));
$saveShowWbs=$saveShowWbsObj->parameterValue;
$saveShowResourceObj=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningShowResource'));
$saveShowResource=$saveShowResourceObj->parameterValue;
$saveShowWorkObj=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningShowWork'));
$saveShowWork=$saveShowWorkObj->parameterValue;
$saveShowClosedObj=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningShowClosed'));
$saveShowClosed=$saveShowClosedObj->parameterValue;
$saveShowMilestoneObj=SqlElement::getSingleSqlElementFromCriteria('Parameter',array('idUser'=>$user->id,'idProject'=>null,'parameterCode'=>'planningShowMilestone'));
$saveShowMilestone=$saveShowMilestoneObj->parameterValue;

if ($saveShowClosed) {
	$_REQUEST['idle']=true;
}

$proj=null;
if (sessionValueExists('project')) {
	$proj=getSessionValue('project');
}
if ($proj=='*' or !$proj) {
	$proj=null;
}
?>

<div id="mainPlanningDivContainer" dojoType="dijit.layout.BorderContainer">
	<div dojoType="dijit.layout.ContentPane" region="top" id="listHeaderDiv" height="27px"
	style="z-index: 3; position: relative; overflow: visible !important;">
		<table width="100%" height="27px" class="listTitle" >
		  <tr height="27px">
		  	<td style="vertical-align:top; width:250px;">
		      <table >
    		    <tr height="32px">
      		    <td width="50px" align="center">
                <?php echo formatIcon('VersionsPlanning', 32, null, true);?>
              </td>
              <td width="200px" ><span class="title" style="max-width:200px;white-space:normal"><?php echo i18n('menuVersionsPlanning');?></span></td>
      		  </tr>
    		  </table>
		    </td>
		    <td>   
		      <form dojoType="dijit.form.Form" id="listForm" action="" method="" >
		        <table style="width: 100%;">
		          <tr>
		            <td style="width:70px">
		              <input type="hidden" id="objectClass" name="objectClass" value="" /> 
		              <input type="hidden" id="objectId" name="objectId" value="" />
                  	  <input type="hidden" id="versions" name="versions" value="true" />
		              &nbsp;&nbsp;&nbsp;
<?php
//CHANGE qCazelles - Correction GANTT - Ticket #100
//Old
// $tabProductVersions = $_REQUEST['productVersionsListId'];
//New
$tabProductVersions=array();
if ( strpos($_REQUEST['productVersionsListId'], '_')!==false) {
  $tabProductVersions=explode('_', $_REQUEST['productVersionsListId']);
}
else {
  $tabProductVersions[]=$_REQUEST['productVersionsListId'];
}
//END CHANGE qCazelles - Correction GANTT - Ticket #100
$nbPvs = 0;
foreach ($tabProductVersions as $idProductVersion) {
	echo '<input type="hidden" id="pvNo'.$nbPvs.'" name="idsProductVersion[]" value="'.$idProductVersion.'" />';
	$nbPvs += 1;
}
echo '<input type="hidden" id="nbPvs" name="nbPvs" value="'.$nbPvs.'" />';
?>            
		            </td>
		            <td style="white-space:nowrap;width:240px">
		              <table>
                    <tr>
                      <td align="right">&nbsp;&nbsp;&nbsp;<?php echo i18n("displayStartDate");?>&nbsp;&nbsp;</td><td>
                        <div dojoType="dijit.form.DateTextBox"
	                        <?php if (sessionValueExists('browserLocaleDateFormatJs')) {
														echo ' constraints="{datePattern:\''.getSessionValue('browserLocaleDateFormatJs').'\'}" ';
													}?>
                           id="startDatePlanView" name="startDatePlanView"
                           invalidMessage="<?php echo i18n('messageInvalidDate')?>"
                           type="text" maxlength="10"
                           style="width:100px; text-align: center;" class="input roundedLeft"
                           hasDownArrow="true"
                           value="<?php echo $startDate;?>" >
                           <script type="dojo/method" event="onChange" >
                            refreshJsonPlanning();
                           </script>
                         </div>
                      </td>
                    </tr>
                    <tr>
                      <td align="right">&nbsp;&nbsp;&nbsp;<?php echo i18n("displayEndDate");?>&nbsp;&nbsp;</td>
                      <td>
                        <div dojoType="dijit.form.DateTextBox"
	                        <?php if (sessionValueExists('browserLocaleDateFormatJs')) {
														echo ' constraints="{datePattern:\''.getSessionValue('browserLocaleDateFormatJs').'\'}" ';
													}?>
                           id="endDatePlanView" name="endDatePlanView"
                           invalidMessage="<?php echo i18n('messageInvalidDate')?>"
                           type="text" maxlength="10"
                           style="width:100px; text-align: center;" class="input roundedLeft"
                           hasDownArrow="true"
                           value="<?php echo $endDate;?>" >
                           <script type="dojo/method" event="onChange" >
                            refreshJsonPlanning();
                           </script>
                        </div>
                      </td>
                    </tr>
                  </table>
		            </td>
                <td>
                  <table >
                    <tr>
                      <td width="32px">
                        <button title="<?php echo i18n('printPlanning')?>"
                         dojoType="dijit.form.Button"
                         id="listPrint" name="listPrint"
                         iconClass="dijitButtonIcon dijitButtonIconPrint" class="detailButton" 
                         showLabel="false">
                          <script type="dojo/connect" event="onClick" args="evt">
<?php $ganttPlanningPrintOldStyle=Parameter::getGlobalParameter('ganttPlanningPrintOldStyle');
      if (!$ganttPlanningPrintOldStyle) {$ganttPlanningPrintOldStyle="NO";}
      if ($ganttPlanningPrintOldStyle=='YES') {?>
                          showPrint("../tool/jsonPlanning.php?portfolio=true", 'planning');
<?php } else { ?>
                          showPrint("planningPrint.php", 'planning');
<?php }?>   
                          </script>
                        </button>
                      </td>
                      <td width="32px">
                        <button title="<?php echo i18n('reportPrintPdf')?>"
                         dojoType="dijit.form.Button"
                         id="listPrintPdf" name="listPrintPdf"
                         iconClass="dijitButtonIcon dijitButtonIconPdf" class="detailButton"  showLabel="false">
                          <script type="dojo/connect" event="onClick" args="evt">
                          var paramPdf='<?php echo Parameter::getGlobalParameter("pdfPlanningBeta");?>';
                          if(paramPdf!='false') planningPDFBox();
                          else showPrint("../tool/jsonPlanning_pdf.php?portfolio=true", 'planning', null, 'pdf');
                          </script>
                        </button>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="4" style="white-space:nowrap;">
                        <span title="<?php echo i18n('saveDates')?>" dojoType="dijit.form.CheckBox"
                           type="checkbox" id="listSaveDates" name="listSaveDates" class="whiteCheck"
                           <?php if ( $saveDates) {echo 'checked="checked"'; } ?>  >

                          <script type="dojo/method" event="onChange" >
                            refreshJsonPlanning();
                          </script>
                        </span>
                        <span for="listSaveDates"><?php echo i18n("saveDates");?></span>
                      </td>
                    </tr>
                  </table>
                </td>
		        </table>    
		      </form>
		    </td>
		  </tr>
		</table>
		<div id="listBarShow" class="dijitAccordionTitle"  onMouseover="showList('mouse')" onClick="showList('click');">
		  <div id="listBarIcon" align="center"></div>
		</div>
	
		<div dojoType="dijit.layout.ContentPane" id="planningJsonData" jsId="planningJsonData" 
     style="display: none">
		  <?php
            include '../tool/jsonVersionsPlanning.php';
          ?>
		</div>
	</div>
	<div dojoType="dijit.layout.ContentPane" region="center" id="gridContainerDiv">
   <div id="submainPlanningDivContainer" dojoType="dijit.layout.BorderContainer"
    style="border-top:1px solid #ffffff;">
        <?php $leftPartSize=Parameter::getUserParameter('planningLeftSize');
          if (! $leftPartSize) {$leftPartSize='325px';} ?>
	   <div dojoType="dijit.layout.ContentPane" region="left" splitter="true" 
      style="width:<?php echo $leftPartSize;?>; height:100%; overflow-x:scroll; overflow-y:hidden;" class="ganttDiv" 
      id="leftGanttChartDIV" name="leftGanttChartDIV"
      onScroll="dojo.byId('ganttScale').style.left=(this.scrollLeft)+'px'; this.scrollTop=0;" 
      onWheel="leftMouseWheel(event);">
      <script type="dojo/method" event="onUnload" >
         var width=this.domNode.style.width;
         setTimeout("saveUserParameter('planningLeftSize','"+width+"');",1);
         return true;
      </script>
     </div>
     <div dojoType="dijit.layout.ContentPane" region="center" 
      style="height:100%; overflow:hidden;" class="ganttDiv" 
      id="GanttChartDIV" name="GanttChartDIV" >
       <div id="mainRightPlanningDivContainer" dojoType="dijit.layout.BorderContainer">
         <div dojoType="dijit.layout.ContentPane" region="top" 
          style="width:100%; height:45px; overflow:hidden;" class="ganttDiv"
          id="topGanttChartDIV" name="topGanttChartDIV">
         </div>
         <div dojoType="dijit.layout.ContentPane" region="center" 
          style="width:100%; overflow-x:scroll; overflow-y:scroll; position: relative; top:-10px;" class="ganttDiv"
          id="rightGanttChartDIV" name="rightGanttChartDIV"
          onScroll="dojo.byId('rightside').style.left='-'+(this.scrollLeft+1)+'px';
                    dojo.byId('leftside').style.top='-'+(this.scrollTop)+'px';"
         >
         </div>
       </div>
     </div>
   </div>
	</div>
</div>