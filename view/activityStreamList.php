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
 * ============================================================================ Presents an object.
 */
require_once "../tool/projeqtor.php";
require_once "../tool/formatter.php";
scriptLog ( '   ->/view/objectStream.php' );
global $print, $user;
$user = getSessionUser ();


if (RequestHandler::isCodeSet('activityStreamNumberElement')) {
	$activityStreamNumberElement=RequestHandler::getValue("activityStreamNumberElement");
	Parameter::storeUserParameter("activityStreamNumberElement", $activityStreamNumberElement);
} else {
	$activityStreamNumberElement=Parameter::getUserParameter("activityStreamNumberElement");
}

if (RequestHandler::isCodeSet('activityStreamAuthorFilter')) {
	$paramAuthorFilter=RequestHandler::getId("activityStreamAuthorFilter");
	Parameter::storeUserParameter("activityStreamAuthorFilter", $paramAuthorFilter);
} else {
	$paramAuthorFilter=Parameter::getUserParameter("activityStreamAuthorFilter");
}

if (RequestHandler::isCodeSet('activityStreamTeamFilter')) {
  $paramTeamFilter=RequestHandler::getId("activityStreamTeamFilter");
  Parameter::storeUserParameter("activityStreamTeamFilter", $paramTeamFilter);
} else {
  $paramTeamFilter=Parameter::getUserParameter("activityStreamTeamFilter");
}

if (RequestHandler::isCodeSet('activityStreamTypeNote')) {
  $paramTypeNote=RequestHandler::getId("activityStreamTypeNote");
  Parameter::storeUserParameter("activityStreamElementType", $paramTypeNote);
} else {
  $paramTypeNote=Parameter::getUserParameter("activityStreamElementType");
}
$typeNote = SqlList::getNameFromId('Importable', $paramTypeNote,false);

if (RequestHandler::isCodeSet('activityStreamIdNote')) {
  $paramStreamIdNote=RequestHandler::getId("activityStreamIdNote");
  Parameter::storeUserParameter("activityStreamIdNote", $paramStreamIdNote);
} else {
  $paramStreamIdNote=Parameter::getUserParameter("activityStreamIdNote");
}

if (RequestHandler::isCodeSet('activityStreamNumberDays')) {
  $activityStreamNumberDays=RequestHandler::getNumeric("activityStreamNumberDays");
  Parameter::storeUserParameter("activityStreamNumberDays", $activityStreamNumberDays);
} else {
  $activityStreamNumberDays=Parameter::getUserParameter("activityStreamNumberDays");
}

if (RequestHandler::isCodeSet('activityStreamShowClosed')) {
  $activityStreamShowClosed=RequestHandler::getValue("activityStreamShowClosed");
  Parameter::storeUserParameter("activityStreamShowClosed", $activityStreamShowClosed);
} else {
  $activityStreamShowClosed=Parameter::getUserParameter("activityStreamShowClosed");
}

$activityStreamAddedRecently=null;
  if (RequestHandler::isCodeSet('activityStreamAddedRecently')) {
    $activityStreamAddedRecently=RequestHandler::getValue("activityStreamAddedRecently");
    Parameter::storeUserParameter("activityStreamAddedRecently", $activityStreamAddedRecently);
  } else {
    $activityStreamAddedRecently=Parameter::getUserParameter("activityStreamAddedRecently");
  }

$activityStreamUpdatedRecently=null;
  if (RequestHandler::isCodeSet('activityStreamUpdatedRecently')) {
    $activityStreamUpdatedRecently=RequestHandler::getValue("activityStreamUpdatedRecently");
    Parameter::storeUserParameter("activityStreamUpdatedRecently", $activityStreamUpdatedRecently);
  } else {
    $activityStreamUpdatedRecently=Parameter::getUserParameter("activityStreamUpdatedRecently");
  }
    
$paramProject=getSessionValue('project');

$note = new Note ();
$critWhere="1=1";
if (trim($paramAuthorFilter)!="") {
	$critWhere.=" and idUser=$paramAuthorFilter";
}

if (trim($paramTeamFilter)!="") {
	$team = new Resource();
	$teamResource=$team->getDatabaseTableName();
	$critWhere.=" and idUser in (select id from $teamResource where idTeam=$paramTeamFilter)";
}

if (trim($paramTypeNote)!="") {
  $critWhere.=" and refType='$typeNote'";
}

if (trim($paramStreamIdNote)!="") {
  $critWhere.=" and refId=$paramStreamIdNote";
}

if ($paramProject!='*') {
	$critWhere.=" and (idProject is null or idProject in ".getVisibleProjectsList(true).')';
} else {
	$critWhere.=" and (idProject is null or idProject in ".getVisibleProjectsList($paramProject).')';
}

if ($activityStreamNumberDays!==""){
  if (Sql::isPgsql()) {
    if ($activityStreamAddedRecently and $activityStreamUpdatedRecently) {
      $critWhere.=" AND creationDate>=CURRENT_DATE - INTERVAL '" . intval($activityStreamNumberDays) . " day' ";
      $critWhere.=" or updateDate>=CURRENT_DATE - INTERVAL '" . intval($activityStreamNumberDays) . " day ' ";
    } else if ($activityStreamAddedRecently=="added" && trim($activityStreamNumberDays)!=""){
      $critWhere.=" and creationDate>=CURRENT_DATE -INTERVAL '" . intval($activityStreamNumberDays) . " day ' ";
    } else if ($activityStreamUpdatedRecently=="updated"){
      $critWhere.=" and updateDate>=CURRENT_DATE - INTERVAL '" . intval($activityStreamNumberDays) . " day ' ";
    }   
  } else {
  if ($activityStreamAddedRecently and $activityStreamUpdatedRecently) {   
    $critWhere.=" and ( creationDate>=ADDDATE(CURDATE(), INTERVAL (-" . intval($activityStreamNumberDays) . ") DAY) ";
    $critWhere.=" or updateDate>=ADDDATE(CURDATE(), INTERVAL (-" . intval($activityStreamNumberDays) . ") DAY) )";
  } else if ($activityStreamAddedRecently=="added" && trim($activityStreamNumberDays)!=""){
    $critWhere.=" and creationDate>=ADDDATE(CURDATE(), INTERVAL (-" . intval($activityStreamNumberDays) . ") DAY) ";
  } else if ($activityStreamUpdatedRecently=="updated"){
    $critWhere.=" and updateDate>=ADDDATE(CURDATE(), INTERVAL (-" . intval($activityStreamNumberDays) . ") DAY) ";
  }
  }
}

if ($activityStreamShowClosed!='1') {
	$critWhere.=" and idle=0";
}
//echo '<br/>';
$order = "COALESCE (updateDate,creationDate) ASC";
$notes=$note->getSqlElementsFromCriteria(null,false,$critWhere,$order,null,null,$activityStreamNumberElement);

$countIdNote = count ( $notes );
if ($countIdNote == 0) {
  echo "<div style='padding:10px'>".i18n ( "noNoteToDisplay" )."</div>";
  exit ();
}
$onlyCenter = (RequestHandler::getValue ( 'onlyCenter' ) == 'true') ? true : false;
?>
<div dojo-type="dijit.layout.BorderContainer" class="container" style="overflow-y:auto;">
	<table id="objectStream" style="width: 100%;"> 
	<?php 
	  foreach ($notes as $note) {
      activityStreamDisplayNote($note,"activityStream");
	  }?>
	</table>
	<div id="scrollToBottom" type="hidden"></div>
</div>