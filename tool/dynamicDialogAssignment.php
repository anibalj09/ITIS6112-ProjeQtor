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
include_once ("../tool/projeqtor.php");
$currency=Parameter::getGlobalParameter('currency');
$currencyPosition=Parameter::getGlobalParameter('currencyPosition');
$keyDownEventScript=NumberFormatter52::getKeyDownEvent();
$idProject=RequestHandler::getId('idProject',false,null);
$refType=RequestHandler::getValue('refType',false,null);
$refId=RequestHandler::getId('refId',false,null);
$idRole=RequestHandler::getId('idRole',false,null);
$idResource = RequestHandler::getId('idResource',false,null);
$idAssignment=RequestHandler::getId('idAssignment',false,null);
$assignmentObj = new Assignment($idAssignment);
$unit=RequestHandler::getValue('unit',false,null);
$assignedIdOrigin=RequestHandler::getId('assignedIdOrigin',false,null);
$assignmentObjOrigin = new Assignment($assignedIdOrigin);
$validatedWorkPeOld = RequestHandler::getValue('validatedWorkPe',false,null);
$assignedWorkPeOld = RequestHandler::getValue('assignedWorkPe',false,null);
$realWork = RequestHandler::getNumeric('realWork',false,0);
$validatedWorkPe = str_replace(',', '.', $validatedWorkPeOld);
$assignedWorkPe = str_replace(',', '.', $assignedWorkPeOld);
$hoursPerDay=Work::getHoursPerDay();
$delay=null;
if ($assignmentObj->realWork==null){
  $assignmentObj->realWork="0";
}
if($assignmentObj->leftWork==null){
  $assignmentObj->leftWork="0";
}
$obj=new $refType($refId);
if($refType=="Meeting" || $refType=="PeriodicMeeting") {
	$delay=Work::displayWork(workTimeDiffDateTime('2000-01-01T'.$obj->meetingStartTime,'2000-01-01T'.$obj->meetingEndTime));
}
$mode = RequestHandler::getValue('mode',false,true);
$arrayDefaultOffDays=array();
if (Parameter::getGlobalParameter('OpenDayMonday')=='offDays') $arrayDefaultOffDays[]=1;
if (Parameter::getGlobalParameter('OpenDayTuesday')=='offDays') $arrayDefaultOffDays[]=2;
if (Parameter::getGlobalParameter('OpenDayWednesday')=='offDays') $arrayDefaultOffDays[]=3;
if (Parameter::getGlobalParameter('OpenDayThursday')=='offDays') $arrayDefaultOffDays[]=4;
if (Parameter::getGlobalParameter('OpenDayFriday')=='offDays') $arrayDefaultOffDays[]=5;
if (Parameter::getGlobalParameter('OpenDaySaturday')=='offDays') $arrayDefaultOffDays[]=6;
if (Parameter::getGlobalParameter('OpenDaySunday')=='offDays') $arrayDefaultOffDays[]=7;

$planningMode=null;
$peName=$refType.'PlanningElement';
if (property_exists($obj, $peName)) {
  $idPm=$obj->$peName->idPlanningMode;
  $pmObj=new PlanningMode($idPm);
  $planningMode=$pmObj->code;
}
$assRec=array();
if ($planningMode=='RECW') {
  for ($i=1;$i<=7;$i++) $assRec[$i]=null;
  $ar=new AssignmentRecurring();
  $arList=$ar->getSqlElementsFromCriteria(array('idAssignment'=>$idAssignment));
  foreach($arList as $ar) {
    $assRec[$ar->day]=$ar->value;
  }
}
$resource=new ResourceAll($idResource);
?>
  <table>
    <tr>
      <td>
       <form dojoType="dijit.form.Form" id='assignmentForm' jsid='assignmentForm' name='assignmentForm' onSubmit="return false;">    
         <input id="assignmentId" name="assignmentId" type="hidden" value="<?php echo $idAssignment ;?>" />
         <input id="assignmentRefType" name="assignmentRefType" type="hidden" value="<?php echo $refType ;?>" />
         <input id="assignmentRefId" name="assignmentRefId" type="hidden" value="<?php echo $refId ;?>" />
         <input id="assignedIdOrigin" name="assignedIdOrigin" type="hidden" value="<?php echo $assignedIdOrigin ;?>" />
         <input id="assignedWorkOrigin" name="assignedWorkOrigin" type="hidden" value="<?php echo $assignmentObj->assignedWork ;?>" />
         <table>
           <tr>
             <td class="dialogLabel" >
               <label for="assignmentIdResource" ><?php echo i18n("colIdResource");?>&nbsp;:&nbsp;</label>
             </td>  
             <td>
              <select dojoType="dijit.form.FilteringSelect"
              <?php echo autoOpenFilteringSelect();?>
                id="assignmentIdResource" name="assignmentIdResource"
                class="input" 
                onChange="assignmentChangeResource();assignmentChangeResourceTeamForCapacity();"
                missingMessage="<?php echo i18n('messageMandatory',array(i18n('colIdResource')));?>" <?php echo ($realWork!=0 && $mode=='edit')?"readonly=readonly":"";?>>
                <?php if($mode=='edit'){                      
                          htmlDrawOptionForReference('idResourceAll', $idResource,null,true,'idProject',$idProject);
                }else{
                          htmlDrawOptionForReference('idResourceAll', null,null,false,'idProject',$idProject);
                }?>
               </select>  
             </td>
             <?php if($refType=="Meeting" || $refType=="PeriodicMeeting") {  ?>
             <td style="vertical-align: top">
               <button id="assignmentDetailButton" dojoType="dijit.form.Button" showlabel="false"
                 title="<?php echo i18n('showDetail')?>"iconClass="iconView">
                 <script type="dojo/connect" event="onClick" args="evt">
                    var canCreate=("<?php echo securityGetAccessRightYesNo('menuAffectable','create');?>"=="YES")?1:0;
                    showDetail('assignmentIdResource', canCreate ,'Affectable',false);
                 </script>
               </button>
             </td> 
             <?php } ?>
           </tr>
           <tr>
             <td class="dialogLabel" >
               <label for="assignmentIdRole" ><?php echo i18n("colIdRole");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
              <select dojoType="dijit.form.FilteringSelect" 
              <?php echo autoOpenFilteringSelect();?>
                id="assignmentIdRole" name="assignmentIdRole"
                class="input" 
                onChange="assignmentChangeRole();" <?php echo ($realWork!=0 && $idRole)?"readonly=readonly":"";?>>                
                 <?php if($mode=='edit'){
                   htmlDrawOptionForReference('idRole', $idRole, null, true);
                 } else {
                   htmlDrawOptionForReference('idRole', null, null, false);
                 }?>            
               </select>  
             </td>
           </tr>
           <?php $pe=new PlanningElement();
           $pe->setVisibility(); ?>
           <tr <?php echo ($pe->_costVisibility=='ALL')?'':'style="display:none;"'?>>
             <td class="dialogLabel" >
               <label for="assignmentDailyCost" ><?php echo i18n("colCost");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
               <?php echo ($currencyPosition=='before')?$currency:''; ?>
               <div id="assignmentDailyCost" name="assignmentDailyCost" value="<?php echo ($mode=='edit')?$assignmentObj->dailyCost:'';?>" 
                 dojoType="dijit.form.NumberTextBox" 
                 constraints="{min:0}" 
                 style="width:97px"            
                 readonly >
                 <?php echo $keyDownEventScript;?>
                 </div>
               <?php echo ($currencyPosition=='after')?$currency:'';
                     echo " / ";
                     echo i18n('shortDay'); ?>
             </td>
           </tr>

           <tr id="assignmentRateRow" name="assignmentRateRow" <?php if ($resource->isResourceTeam) echo 'style="display:none"';?>>
             <td class="dialogLabel" >
               <label for="assignmentRate" ><?php echo i18n("colRate");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
               <div id="assignmentRate" name="assignmentRate" value="<?php echo ($mode=='edit' and $planningMode!='RECW')?$assignmentObj->rate:"100";?>" 
                 dojoType="dijit.form.NumberTextBox" 
                 constraints="{min:0,max:100}" 
                 style="width:97px" 
                 <?php if ($planningMode=='RECW') echo ' readonly';?>
                 missingMessage="<?php echo i18n('messageMandatory',array(i18n('colRate')));?>" 
              <?php if (!$resource->isResourceTeam) { ?>  required="true" <?php } ?> >
                 <?php echo $keyDownEventScript;?>
                 </div>
             </td>
           </tr>
           
             <tr id="assignmentCapacityResourceTeam" name="assignmentCapacityResourceTeam" <?php if (! $resource->isResourceTeam) echo 'style="display:none"';?>>
               <td class="dialogLabel" >
                 <label for="assignmentCapacity" ><?php echo i18n("colCapacity");?>&nbsp;:&nbsp;</label>
               </td>
               <td>
                 <?php if ($mode=='edit' and $assignmentObj->capacity==0 and !$resource->isResourceTeam) round($assignmentObj->capacity=$resource->capacity*$assignmentObj->rate/100,1);?>
                 <div id="assignmentCapacity" name="assignmentCapacity" value="<?php echo ($mode=='edit')?$assignmentObj->capacity:"1";?>"
                   dojoType="dijit.form.NumberTextBox" 
                   style="width:97px" 
                   <?php if ($planningMode=='RECW') echo ' readonly';?>
                   missingMessage="<?php echo i18n('messageMandatory',array(i18n('colCapacity')));?>" 
                   required="true" >
                   <?php echo $keyDownEventScript;?>
                   </div>
               </td>
             </tr>
           
           <tr style="<?php if ($planningMode=='RECW') echo 'display:none;';?>">
             <td class="dialogLabel" >
               <label for="assignmentAssignedWork" ><?php echo i18n("colAssignedWork");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
               <div id="assignmentAssignedWork" name="assignmentAssignedWork" 
                 value="<?php if(($refType=='Meeting' || $refType=='PeriodicMeeting') && $mode=="add" && $obj->meetingStartTime && $obj->meetingEndTime){ 
                                  echo $delay;
                              } else if ($mode=="edit"){
                                  echo Work::displayWork($assignmentObj->assignedWork);
                              } else if($mode=="add") { 
                                  $assignedWork = GeneralWork::convertWork($validatedWorkPe)-GeneralWork::convertWork($assignedWorkPe);
                                  if($assignedWork < 0 or $planningMode=='RECW'){
                                    echo "0";
                                  } else {
                                    echo Work::displayWork($assignedWork);
                                  }                             
                              } else if($mode=="divide"){
                                  echo Work::displayWork($assignmentObjOrigin->leftWork/2);
                              }
                 ?>" 
                 dojoType="dijit.form.NumberTextBox" 
                 constraints="{min:0,max:9999999.99}" 
                 style="width:97px"
                 <?php if ($planningMode=='RECW') echo ' readonly';?>
                 onchange="assignmentUpdateLeftWork('assignment');"
                 onblur="assignmentUpdateLeftWork('assignment');" >
                 <?php echo $keyDownEventScript;?>
                 </div>
               <input id="assignmentAssignedUnit" name="assignmentAssignedUnit" value="<?php echo $unit ;?>" readonly tabindex="-1"
                 xdojoType="dijit.form.TextBox" 
                 class="display" style="width:15px; background-color:white; color:#000000; border:0px;"/>
               <input type="hidden" id="assignmentAssignedWorkInit" name="assignmentAssignedWorkInit" value="<?php echo($mode=="edit")?Work::displayWork($assignmentObj->assignedWork):"";?>" 
                 style="width:97px"/>  
             </td>    
           </tr>
           <tr style="<?php if ($planningMode=='RECW') echo 'display:none;';?>">
             <td class="dialogLabel" >
               <label for="assignmentRealWork" ><?php echo i18n("colRealWork");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
               <div id="assignmentRealWork" name="assignmentRealWork" value="<?php echo ($mode=="edit")?Work::displayWork($assignmentObj->realWork):"0";?>"  
                 dojoType="dijit.form.NumberTextBox" 
                 constraints="{min:0,max:9999999.99}" 
                 style="width:97px" readonly >
                 <?php echo $keyDownEventScript;?>
                 </div>
               <input id="assignmentRealUnit" name="assignmentRealUnit" value="<?php echo $unit ;?>" readonly tabindex="-1"
                 xdojoType="dijit.form.TextBox" 
                 class="display" style="width:15px;background-color:#FFFFFF; color:#000000; border:0px;"/>
             </td>
           </tr>
           <tr style="<?php if ($planningMode=='RECW') echo 'display:none;';?>">
             <td class="dialogLabel" >
               <label for="assignmentLeftWork" ><?php echo i18n("colLeftWork");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
               <div id="assignmentLeftWork" name="assignmentLeftWork"                  
                 value="<?php if(($refType=='Meeting' || $refType=='PeriodicMeeting') && $mode=="add" && $obj->meetingStartTime && $obj->meetingEndTime){ 
                                  echo $delay;
                              } else if($mode=="edit"){
                                  echo Work::displayWork($assignmentObj->leftWork);
                              } else if($mode=="divide"){
                                  echo Work::displayWork($assignmentObjOrigin->leftWork/2);                                                       
                              } else { 
                                  $assignedWork = GeneralWork::convertWork($validatedWorkPe)-GeneralWork::convertWork($assignedWorkPe);
                                    if($assignedWork < 0 or $planningMode=='RECW'){
                                      echo "0";
                                    } else {
                                      echo Work::displayWork($assignedWork) ;
                                  }
                              } 
                 ?>" 
                 dojoType="dijit.form.NumberTextBox" 
                 constraints="{min:0,max:9999999.99}"
                 <?php if ($planningMode=='RECW') echo ' readonly';?> 
                 onchange="assignmentUpdatePlannedWork('assignment');"
                 onblur="assignmentUpdatePlannedWork('assignment');"  
                 style="width:97px" >
                 <?php echo $keyDownEventScript;?>
                 </div>
               <input id="assignmentLeftUnit" name="assignmentLeftUnit" value="<?php echo $unit ;?>" readonly tabindex="-1"
                 xdojoType="dijit.form.TextBox" 
                 class="display" style="width:15px;background-color:#FFFFFF; color:#000000; border:0px;"/>
               <input type="hidden" id="assignmentLeftWorkInit" name="assignmentLeftWorkInit" value="<?php echo ($mode=="edit")?Work::displayWork($assignmentObj->leftWork):"0";?>" 
                 style="width:97px"/>  
             </td>
           </tr>
           <tr style="<?php if ($planningMode=='RECW') echo 'display:none;';?>">
             <td class="dialogLabel" >
               <label for="assignmentPlannedWork" ><?php echo i18n("colPlannedWork");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
               <div id="assignmentPlannedWork" name="assignmentPlannedWork"                  
                 value="<?php if(($refType=='Meeting' || $refType=='PeriodicMeeting') && $mode=="add" && $obj->meetingStartTime && $obj->meetingEndTime){ 
                                  echo $delay;
                              } else { 
                                  $assignedWork = GeneralWork::convertWork($validatedWorkPe)-GeneralWork::convertWork($assignedWorkPe);
                                  if($assignedWork < 0){
                                    echo "0";
                                  } else {
                                    echo Work::displayWork($assignedWork) ;
                                  }
                              } 
                 ?>" 
                 dojoType="dijit.form.NumberTextBox" 
                 constraints="{min:0,max:9999999.99}" 
                 style="width:97px" readonly > 
                 <?php echo $keyDownEventScript;?>
                 </div>
               <input id="assignmentPlannedUnit" name="assignmentPlannedUnit" value="<?php echo $unit;?>" readonly tabindex="-1"
                 xdojoType="dijit.form.TextBox" 
                 class="display" style="width:15px;background-color:#FFFFFF; border:0px;"/>
             </td>
           </tr>
           <tr>
             <td class="dialogLabel" >
               <label for="assignmentComment" ><?php echo i18n("colComment");?>&nbsp;:&nbsp;</label>
             </td>
             <td>
               <input id="assignmentComment" name="assignmentComment" value="<?php echo htmlEncode($assignmentObj->comment);?>"  
                 dojoType="dijit.form.Textarea"
                 class="input" 
                 /> 
             </td>
           </tr>
         </table>       
         
       <div id="optionalAssignmentDiv" style="<?php if ($refType=="Meeting" || $refType=="PeriodicMeeting"){echo "display:block;";}else {echo "display:none;";}?>">
        <table style="margin-left:143px;">
          <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
            <td class="dialogLabel">&nbsp;</td>     
            <td>
              <input dojoType="dijit.form.CheckBox" name="attendantIsOptional" id="attendantIsOptional" <?php echo ($mode=="edit" && $assignmentObj->optional==1)?"checked=checked":"";?> />
              <label style="float:none" for="attendantIsOptional" ><?php echo i18n("attendantIsOptional"); ?></label>
            </td>
           <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
           </tr>
        </table>
      </div>
         
      <div id="recurringAssignmentDiv" style="<?php if ($planningMode=='RECW'){echo "display:block;";}else {echo "display:none;";}?>">
        <table style="margin-left:143px;">
          <tr><td colspan="7">&nbsp;</td></tr>
          <tr>
            <td colspan="7" class="section"><?php echo i18n("sectionRecurringWeek");?></td>
          </tr>
          <tr>
            <?php for ($i=1; $i<=7; $i++) {?>
            <td class="dialogLabel" style="text-align:center"><?php echo i18n('colWeekday' . $i);?></td>
            <?php }?>
          </tr>
          <tr>
            <?php for ($i=1; $i<=7; $i++) {?>
            <td>
            <?php  $value=(isset($assRec[$i]))?Work::displayWork($assRec[$i]):0;?>
              <div dojoType="dijit.form.NumberTextBox"  style="width:53px;" name="recurringAssignmentW<?php echo $i;?>" id="recurringAssignmentW<?php echo $i;?>" value="<?php echo $value;?>" 
              constraints="{min:0,max:999.99}" class="input <?php if (in_array($i,$arrayDefaultOffDays)) echo ' offDay';?>" >
              <?php echo $keyDownEventScript;?> 
              </div>
            </td>
            <?php }?>
          </tr>
          <tr>
            <td colspan="2">
              <button class="smallTextButton" dojoType="dijit.form.Button" type="button" >
              <script type="dojo/connect" event="onClick" >
                var val1=dijit.byId('recurringAssignmentW1').get('value');
                for (var i=2; i<=7; i++) {
                  if (! dojo.hasClass('widget_recurringAssignmentW'+i,'offDay')) dijit.byId('recurringAssignmentW'+i).set("value",val1);
                }
              </script>
               <?php echo i18n("copy");?>
              </button>
            </td>
            <td colspan="5" style="text-align:right">
            <?php echo i18n('paramWorkUnit').'&nbsp;=&nbsp;'.i18n(Work::getWorkUnit());?> 
            </td>
          </tr> 
           <tr><td colspan="5">&nbsp;</td></tr>
        </table>
      </div>
      
        </form>
      </td>
    </tr>
    <tr>
      <td align="center">
        <input type="hidden" id="dialogAssignmentAction">
        <button class="mediumTextButton" dojoType="dijit.form.Button" type="button" onclick="dijit.byId('dialogAssignment').hide();">
          <?php echo i18n("buttonCancel");?>
        </button>
        <button class="mediumTextButton" dojoType="dijit.form.Button" id="dialogAssignmentSubmit" type="submit" onclick="protectDblClick(this);saveAssignment();return false;">
          <?php echo i18n("buttonOK");?>
        </button>
      </td>
    </tr>
  </table>
