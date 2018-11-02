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

include_once("../tool/projeqtor.php");
include_once '../tool/formatter.php';

$idNotif = RequestHandler::getId('id');
if($idNotif != ""){
  $readNotif = new Notification($idNotif);
  $readNotif->idStatusNotification = 2;
  $readNotif->save();
}

$notif = new Notification();
$currentUser = getCurrentUserId ();
$crit = array('idStatusNotification'=>1,'idUser'=>$currentUser);
$notifsList=$notif->getSqlElementsFromCriteria($crit, false);
foreach ($notifsList as $result) {
    $type=new Type($result->idNotificationType);
    $color = $type->color;
    $userId =  $result->idResource;
    $userName=SqlList::getNameFromId('User', $userId);
    
    echo '<div style=" border-top:0.1px solid #000000;">';
    echo '  <div style="margin-top:5px; margin-bottom:5px;">';
    echo '<table style=" width:99%;">';
    echo' <tr>';
    echo'   <td  style="border-right: 2px solid '.$color.';" rowspan="2" width="33px">';
    echo'<div style="margin-right:4px;top:1px;">';
    echo      formatUserThumb($userId,$userName,'Creator','32');
    echo'</div>';
    echo'   </td>';
    echo'   <td>';
    echo '  <span onClick="gotoElement(\'Notification\',\''.htmlEncode($result->id).'\')" style="cursor:pointer; width:90%; overflow-x:hidden;position:relative;"> <b>&nbsp; '. $result->title.' </b></span>';
    echo'   </td>';
    echo'   <td width="15px">';
    echo '<span style="cursor:pointer; top:5px; margin-left:14px;" onClick="readNotification(\''.htmlEncode($result->id).'\')"  title="'.i18n('markAsRead').'" class="iconNotification16"> &nbsp;&nbsp;&nbsp;</span>';
    echo'   </td>';
    echo' </tr>';
    echo' <tr>';
    echo'   <td colspan="2" align="left">';
    $not=new Notifiable($result->idNotifiable);
    $ref=$not->notifiableItem;
    //$item=new $ref($result->notifiedObjectId);
    echo '    <div onClick="gotoElement(\''.$ref.'\',\''.htmlEncode($result->notifiedObjectId).'\')"  style="color:blue;cursor:pointer;text-align:left; font-size:90%; max-height:100px; margin-left:8px; margin-top:5px; overflow-y:auto;">'.i18n($ref) . ' #'.$result->notifiedObjectId.'</div>';
    echo '    <div style="text-align:left; font-size:90%; max-height:100px; margin-left:8px; margin-top:5px; overflow-y:auto;">'.$result->content.'</div>';
    echo'   </td>';
    echo' </tr>';
    echo '</table>';
    echo '  </div>';
    echo '</div>';    
}
?>  
