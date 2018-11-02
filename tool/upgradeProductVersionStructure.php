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

/** ===========================================================================
 * Save a note : call corresponding method in SqlElement Class
 * The new values are fetched in $_REQUEST
 */
require_once "../tool/projeqtor.php";
// Get the link info
$objectClass=RequestHandler::getClass('objectClass',true);
$objectId=RequestHandler::getId('objectId',true);
$confirm=(RequestHandler::getAlphanumeric('confirm',true)=='true')?true:false;
$strId=RequestHandler::getId('structureId',false);

$str=new ProductVersionStructure();
$crit = array('idProductVersion'=>$objectId);
if ($strId) {
	$crit=array('id'=>$strId);
}
$strList=$str->getSqlElementsFromCriteria($crit);
global $doNotUpdateAllVersionProject;
$doNotUpdateAllVersionProject=true;
Sql::beginTransaction();
$result="";
//Retrieve the existing list of versions 
// and for each version, find the next version for the component
if (!$confirm) {
	echo '<b>'.i18n('upgradeProductVersionStructure'.(($strId)?'Single':'')).'</b><br/><br/>';
	echo '<table style="width:100%">';
	echo '<tr><td class="noteHeader">'.i18n('colValueBefore').'</td><td class="noteHeader">'.i18n('colValueAfter').'</td></tr>';
}
foreach ($strList as $str) {
	$vers=new ComponentVersion($str->idComponentVersion);
	$oldLabel=$vers->name;
	$newLabel='<i>'.i18n('noChange').'</i>';
	$change=false;
	if ($vers->isEis) $oldLabel.=' <i>('.htmlFormatDate($vers->realEisDate).')</i>';
	//ADD qCazelles
	else if ($vers->isDelivered) $oldLabel.=' <i>('.htmlFormatDate($vers->realDeliveryDate).')</i>';
  //END ADD qCazelles
	$crit="idProduct=$vers->idComponent and isEis=1 and realEisDate is not null";
	$lstCompVers=$vers->getSqlElementsFromCriteria(null,false,$crit,'realEisDate desc');
	if (count($lstCompVers)>0) {
		$new=reset($lstCompVers);
		//ADD qCazelles
		if (Parameter::getGlobalParameter('displayMilestonesStartDelivery') == 'YES') {
  		$crit="idProduct=$vers->idComponent and isDelivered=1 and realDeliveryDate is not null";
  		$lstCompVers=$vers->getSqlElementsFromCriteria(null,false,$crit,'realDeliveryDate desc');
  		if (count($lstCompVers)>0) {
  		  $newBis=reset($lstCompVers);
  		  if (strtotime($newBis->realDeliveryDate) > strtotime($new->realEisDate)) {
  		    $new=$newBis;
  		  }
  		}
		}
		//END ADD qCazelles
		if ($new->id!=$vers->id) {
			$change=true;
			$str->idComponentVersion=$new->id;
			//CHANGE qCazelles
			if ($new->isEis) {
			 $newLabel=$new->name.' <i>('.htmlFormatDate($new->realEisDate).')</i>';
			} else if ($new->isDelivered) {
			 $newLabel=$new->name.' <i>('.htmlFormatDate($new->realDeliveryDate).')</i>';
			}
			//END CHANGE qCazelles
		}
	}
	if ($confirm) {
	  $prod=new ProductOrComponent($str->idProductVersion);
	  $doNotUpdateAllVersionProject=($prod->scope=='Product')?false:true;// If link is between component versions, do not update all version
	  $res=$str->save();
	} else {
		echo '<tr><td class="noteData">'.$oldLabel.'</td><td class="noteData">'.$newLabel.'</td></tr>';
	}
	if ($confirm) {
	  if (!$result) {
	    $result=$res;
	  } else if (stripos($res,'id="lastOperationStatus" value="OK"')>0 ) {
	  	if (stripos($result,'id="lastOperationStatus" value="OK"')>0 ) {
	  		$deb=stripos($res,'#');
	  		$fin=stripos($res,' ',$deb);
	  		$resId=substr($res,$deb, $fin-$deb);
	  		$deb=stripos($result,'#');
	      $fin=stripos($result,' ',$deb);
	      $result=substr($result, 0, $fin).','.$resId.substr($result,$fin);
	  	} else {
	  	  $result=$res;
	  	} 
	  }
	}
}
if (!$confirm) {
	echo "</table>";
	echo '<br/>'.i18n("messageConfirmationNeeded").'<br/><br/>';
}
// Message of correct saving
if ($confirm) displayLastOperationStatus($result);
?>