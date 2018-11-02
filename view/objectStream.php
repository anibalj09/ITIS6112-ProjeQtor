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
  require_once "../tool/formatter.php";
  scriptLog('   ->/view/objectStream.php');
  global $print,$user;
  if (! isset($objectClass) ) $objectClass=RequestHandler::getClass('objectClass');
  if (! isset($objectId)) $objectId=RequestHandler::getId('objectId');
  $obj=new $objectClass($objectId);
  $canUpdate=securityGetAccessRightYesNo('menu' . $objectClass, 'update', $obj) == "YES";
  if (!property_exists($obj, 'idle') or $obj->idle == 1) {
    $canUpdate=false;
  }
  if($objectClass=="PlanningElement"){
    $noData = '<br/><i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . i18n('noItemSelected') . '</i>';
  } else {
    $noData=htmlGetNoDataMessage($objectClass);    
  }
  
  $enterTextHere = '<p style="color:red;">'.i18n("textareaEnterText").'</p>';
  $noNotes = "<div style='padding:10px'>".i18n("noNote").'</div>';
  // get the modifications (from request)
  $note=new Note();
  $order = "COALESCE (updateDate,creationDate) ASC";
  $notes=$note->getSqlElementsFromCriteria(array('refType'=>$objectClass,'refId'=>$objectId),null,null,$order);
  SqlElement::resetCurrentObjectTimestamp();
  $ress=new Resource($user->id);
  //$userId=$note->idUser;
  //$userName=SqlList::getNameFromId('User', $userId);
  $creationDate=$note->creationDate;
  $updateDate=$note->updateDate;
  if ($updateDate == null) {
    $updateDate='';
  }
  if (!$objectId) {
    echo $noData; 
    exit;
  }
  $countIdNote=count($notes);
  $onlyCenter=(RequestHandler::getValue('onlyCenter')=='true')?true:false;
  $privacyNotes=Parameter::getUserParameter('privacyNotes'.$objectClass);
?>
<!-- Titre et listes de notes -->

<?php if (!$onlyCenter) {?>
<div class="container" dojoType="dijit.layout.BorderContainer" liveSplitters="false">
	<div id="activityStreamTop" dojoType="dijit.layout.ContentPane" region="top" style="text-align:center" class="dijitAccordionTitle">
	  <span class="title" ><?php echo i18n("titleStream");?></span>
	</div>
	<div id="activityStreamCenter" dojoType="dijit.layout.ContentPane" region="center" style="overflow-x:hidden;">
	<script type="dojo/connect" event="onLoad" args="evt">
        scrollInto();
	  </script><?php }?>
	  <table id="objectStream" style="width:100%;"> 
	    <?php foreach ( $notes as $note ) {
	      echo activityStreamDisplayNote ($note,"objectStream");
	    };?>
	    <tr><td><div id="scrollToBottom" style="display:block"></div></td></tr>
	  </table>
	   
<?php if (!$onlyCenter) {?>   
<?php if($countIdNote==0){ echo "<div style='padding:10px'>".$noNotes."</div>";}	?>  
	</div>
	<div id="activityStreamBottom" dojoType="dijit.layout.ContentPane" region="bottom" style="height:70px;overflow-x:hidden;">
	  <form id='noteFormStream' name='noteFormStream' onSubmit="return false;" >
       <input id="noteId" name="noteId" type="hidden" value="" />
       <input id="noteRefType" name="noteRefType" type="hidden" value="<?php echo $objectClass;?>" />
       <input id="noteRefId" name="noteRefId" type="hidden" value="<?php echo $objectId;?>" />
       <input id="noteEditorTypeStream" name="noteEditorTypeStream" type="hidden" value="<?php echo getEditorType();?>" />
       <div style="width:99%;position:relative">
         <textarea rows="4"  name="noteNoteStream" id="noteNoteStream" dojoType="dijit.form.SimpleTextarea"
         style="width:98%;height:60px;overflow-x:hidden;overflow-y:auto;border:1px solid grey;margin-top:2px;" onfocus="focusStream();"><?php echo i18n("textareaEnterText");?></textarea>
         <?php
         $privacyClass="";
         $privacyLabel=i18n("public");
         if ($privacyNotes=="3") { // Team privacy
           $privacyClass="iconFixed16";
           $privacyLabel=i18n("private");
         } else if ($privacyNotes=="2") { // Private
           $privacyClass="iconTeam16";
           $privacyLabel=i18n("team");
         }?>
         <div title="<?php echo i18n("colIdPrivacy").' = '.$privacyLabel;?>" id="notePrivacyStreamDiv" class="<?php echo $privacyClass;?>" onclick="switchNotesPrivacyStream();" style="border-radius:7px 0px 0px 0px;width:16px; height:16px;position:absolute;bottom:2px;right:-2px;opacity:1;background-color: #E0E0E0;color:#A0A0A0;cursor:pointer;text-align:center">...</div>
         <input type="hidden" id="notePrivacyStream" name="notePrivacyStream" value="<?php echo $privacyNotes?>" />
         <input type="hidden" id="notePrivacyStreamUserTeam" name="notePrivacyStreamUserTeam" value="<?php echo $ress->idTeam;?>" />
       </div>
     </form>
    
   </div>
</div>
<?php }?>