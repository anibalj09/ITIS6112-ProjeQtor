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
$monthArray=array();
function colorNameFormatter($value,$idTicket=-1) {
  global $print,$outMode;
  if ($value) {
    $tab=explode("#split#",$value);
    if (count($tab)>1) {
      if (count($tab)==2) { // just found : val #split# color
        $val=$tab[0];
        $color=$tab[1];
        $order='';
      } else if (count($tab)==3) { // val #split# color #split# order
          $val=$tab[1];
          $color=$tab[2];
          $order=$tab[0];
      } else { // should not be found
        return value;
      }
      $foreColor=getForeColor($color);
      return '<div '.($idTicket!=-1 ? 'id="status'.$idTicket.'"' : '').' style="vertical-align:middle;padding: 5px;border:1px solid #CCC;border-radius:10px;text-align: center;'.(($print and $outMode=='pdf')?'width:95%;min-height:18px;':'') . 'background-color: ' . $color . '; color:' . $foreColor . ';">' 
          .$val.'</div>';

    } else {
      return $value;
    }
  } else { 
    return ''; 
  }
}
function classNameFormatter($value) {
  global $outMode;
  $classId=$value;
  $className=i18n($value);
  if ($outMode=='pdf') return '<div><table><tr><td><img src="../view/css/customIcons/grey/icon'.$value.'.png" style="width:16px;height:16px"/>&nbsp;</td><td>'.$className.'</td></tr></table></div>';
  else return '<div><table><tr><td><div class="icon'.$classId.'16 icon'.$classId.' iconSize16">&nbsp;</div></td><td>&nbsp;</td><td>'.$className.'</td></tr></table></div>';
}
function colorTranslateNameFormatter($value) {
	global $print;
	if ($value) {
		$tab=explode("#split#",$value);
		if (count($tab)>1) {
			if (count($tab)==2) { // just found : val #split# color
				$val=$tab[0];
				$color=$tab[1];
				$order='';
			} else if (count($tab)==3) { // val #split# color #split# order
				$val=$tab[1];
				$color=$tab[2];
				$order=$tab[0];
			} else { // should not be found
				return value;
			}
			$foreColor='#000000';
			if (strlen($color)==7) {
				$red=substr($color,1,2);
				$green=substr($color,3,2);
				$blue=substr($color,5,2);
				$light=(0.3)*base_convert($red,16,10)+(0.6)*base_convert($green,16,10)+(0.1)*base_convert($blue,16,10);
				if ($light<128) { $foreColor='#FFFFFF'; }
			}
			return '<div style="text-align: center;width:' . (($print)?'95':'100') . '%;background-color: ' . $color . '; color:' . $foreColor . ';">' . i18n($val) . '</div>';

		} else {
			return i18n($value);
		}
	} else {
		return '';
	}
}

function booleanFormatter($value) {
  if ($value==1) { 
    return '<div style="width:100%;text-align:center"><img src="img/checkedOK.png" width="12" height="12" /></div>'; 
  } else { 
    return '<div style="width:100%;text-align:center"><img src="img/checkedKO.png" width="12" height="12" /></div>'; 
  }
}

function colorFormatter($value) {
  if ($value) { 
    return '<table width="100%"><tr><td style="background-color: ' . $value . '; width: 100%;">&nbsp;</td></tr></table>'; 
  } else { 
    return ''; 
  }
}

function dateFormatter($value) {
  if (strlen($value)==19) $value=substr($value,0,10);
  return htmlFormatDate($value,false);
}

function timeFormatter($value) {
  return htmlFormatTime($value,false);
}

function dateTimeFormatter($value) {
  return htmlFormatDateTime($value,false);
}

function translateFormatter($value) {
  if ($value) { 
    return i18n($value); 
  } else { 
    return ''; 
  }
}

function percentFormatter($value, $withProgressBar=false) {
  if ($value!==null) {
    if ($withProgressBar) {
      $pctTxt ='<div style="width:100%;text-align:center;">'.$value.'&nbsp;%</div>';
      $pctTxt.='<div style="height:3px;width:100%;position: relative; bottom:0px;">';
      $pctTxt.='<div style="height:3px;width:'.$value.'%;position: absolute;left:0%;background-color:#AAFFAA">&nbsp;</div>';
      $pctTxt.='<div style="height:3px;width:'.(100 - $value).'%;position: absolute;left:'.$value.'%; background-color:#FFAAAA">&nbsp;</div>';
      $pctTxt.='</div>';
      return $pctTxt;
    } else {
      return $value . '&nbsp;%';
    }
  } else {
    return ''; 
  }
}

function progressFormatter($value,$displayProgressText) {
  if ($value!==null) {
    $pct = intval($value, 10);
    $pctTxt='<div style="width:100%;text-align:center;">'.$displayProgressText.$pct.'&nbsp;%</div>';
    $pctTxt.='<div style="height:3px;width:100%;position: relative; bottom:0px;">';
    $pctTxt.='<div style="height:3px;width:'.$pct.'%;position: absolute;left:0%;background-color:#AAFFAA">&nbsp;</div>';
    $pctTxt.='<div style="height:3px;width:'.(100 - $pct)
       .'%;position: absolute;left:'.$pct
       .'%; background-color:#FFAAAA">&nbsp;</div>';
    $pctTxt.='</div>';
    return $pctTxt;
  } else {
    return '';
  }
}


function numericFormatter($value) {
  return ltrim($value,"0");
}

function sortableFormatter($value) {
  $tab=explode(".",$value);
  $result='';
  foreach ($tab as $val) {
    $result.=($result!="")?".":"";
    $result.=ltrim($val,"0");
  }
  return $result; 
}

function thumbFormatter($objectClass,$id,$size) {
	$image=SqlElement::getSingleSqlElementFromCriteria('Attachment', array('refType'=>$objectClass, 'refId'=>$id));
  if ($image->id and $image->isThumbable()) {
    return '<img src="'.getImageThumb($image->getFullPathFileName(),$size).'" />';
  } else {
  	return formatLetterThumb($id,$size);
  }
}

function formatLetterThumb($idUser,$size,$userName=null,$floatLetter="right", $idTicket=null) {
  global $print;
	if (!$userName) $userName=SqlList::getNameFromId('Affectable',$idUser);
	$arrayColors=array('#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#99CC00', '#e74c3c', '#95a5a6', '#d35400', '#c0392b', '#bdc3c7', '#7f8c8d');
	//'#3366FF','#FF9900','#99CC00', 
	$ind=(trim($idUser))?$idUser%count($arrayColors):0;
	$bgColor=(isset($arrayColors[$ind]))?$arrayColors[$ind]:'#000000';
	$fontSize=($size==32)?24:(($size==16)?10:15);
	if($print){
	  $result='<span style="position:relative;color:#ffffff;background-color:'.$bgColor.';float:left;font-size:'.$fontSize.'px;border-radius:50%;font-weight:300;text-shadow:none;text-align:center;border:1px solid #eeeeee;height:'.($size-2).'px;width:'.($size-2).'px; top:1px;" >';
	}else{
	  $result='<span style="color:#ffffff;background-color:'.$bgColor.';float:'.$floatLetter.';font-size:'.$fontSize.'px;border-radius:50%;font-weight:300;text-shadow:none;text-align:center;border:1px solid #eeeeee;height:'.($size-2).'px;width:'.($size-2).'px; top:1px;"'
	  		. ' onMouseOver="showBigImage(null,null,this,\''.$userName.'\',false);" onMouseOut="hideBigImage();" '
	  		. (($idTicket>0) ? 'id="responsible'.$idTicket.'"' : '') .'valueuser="'.$userName.'">';
	}
  $result.=strtoupper(mb_substr($userName,0,1,'UTF-8'));
	$result.='</span>';
	return $result;
}

function numericFixLengthFormatter($val, $numericLength=0) {  
  if ($numericLength>0) {
    $val=str_pad($val,$numericLength,'0', STR_PAD_LEFT);
  }
  return $val;
}

function workFormatter($value) {
  //$val=ltrim($value,"0");
  return Work::displayWorkWithUnit($value);
}
function imputationFormatter($value) {
  //$val=ltrim($value,"0");
  return Work::displayImputationWithUnit($value);
}

function costFormatter($value) {
	return htmlDisplayCurrency($value);
}

function iconFormatter($value) {
  if (! $value) return "";
  return '<img src="icons/'.$value.'" />';
}

function formatUserThumb($userId,$userName,$title,$size=22,$float='right',$alwaysDisplayBigImage=false,$idTicket=-1) {
	global $print;
	if ($print) return "";//$userName;
  if (! $userId) return '';
	$radius=round($size/2,0);
	$file=Affectable::getThumbUrl('Affectable', $userId, $size);
	$searchNocache=strpos($file,'?');
	$nocache='';
	if ($searchNocache) {
	  $nocache=substr($file, $searchNocache);
	  $pos=strpos($nocache,'#');
	  if ($pos>0) $nocache=substr($nocache,0,$pos);
	}
	$known=(substr($file,0,23) != '../view/img/Affectable/')?true:false;
// 	if ($title) {
// 	  $title=htmlEncode(i18n('thumb'.$title.'Title',array('<b>'.$userName.'</b>')),'quotes');
// 	} else if ($userName) {
	  $title=htmlEncode($userName,'quotes');
// 	}
	if (substr($file,0,6)=='letter') {
		$res=formatLetterThumb($userId, $size,$title,$float,$idTicket);
	} else {
	  $res='<img '.($idTicket!=-1 ? 'id="responsible'.$idTicket.'"' : '').' valueuser="'.$title.'" style="border: 1px solid #AAA;width:'.$size.'px;height:'.($size).'px;float:'.$float.';border-radius:'.$radius.'px"';
	  $res.=' src="'.$file.'" ';
		// Ceci est la partie quand on passe la souris sur l'image de la barre ( le "a" de admin par exemple )
		if (! $print and ($known or $alwaysDisplayBigImage)) {
			$res.=' onMouseOver="showBigImage(\'Affectable\',\''.$userId.'\',this,\''.$title.'\''.(($known)?",false":",true").',\''.$nocache.'\');" onMouseOut="hideBigImage();"';
		} else if (!$known and $userName) {
		  $res.=' onMouseOver="showBigImage(\'Affectable\',\''.$userId.'\',this,\''.$title.'\',true,\''.$nocache.'\');" onMouseOut="hideBigImage();"';
		}
		$res.='/>';
	}
	return $res;
}

function formatColorThumb($col,$val, $size=20, $float='right',$name="") {
  $class=substr($col,2);
  if (! SqlElement::class_exists($class)) return ''; 
  $color=SqlList::getFieldFromId($class, $val, 'color');
  if (! $color) return '';
  $radius=round($size/2,0);
  $res='<div style="border: 1px solid #AAAAAA;background:'.$color.';';
  $res.='width:'.($size-2).'px;height:'.($size-2).'px;float:'.$float.';border-radius:'.$radius.'px"';
  //$res.=' onMouseOver="drawGraphStatus();"';
  if($name!="")$res.=' onMouseOver="showBigImage(null,null,this,\''.$name.'\');" onMouseOut="hideBigImage();"';
  $res.='>&nbsp;</div>';
  return $res;
}
function formatDateThumb($creationDate,$updateDate,$float='right',$size=22,$addName="") {
  global $print;
  if ($print) return "";//htmlFormatDate($creationDate);
  if (! trim($creationDate) and ! trim($updateDate)) return '';
  $today=date('Y-m-d');
  $date=($updateDate)?$updateDate:$creationDate;
  $date=substr($date,0,10);
  $color="White";
  if ($date==$today) {
    $color='Red';
  } else if (addWorkDaysToDate($date,2)==$today) {
    $color='Yellow';
  } 
  $title='';
  if($creationDate)$title=i18n('thumbCreationTitle',array('<b>'.htmlFormatDate($creationDate).'</b>'));
  if ($updateDate and $updateDate!=$creationDate) {
    if($title==''){
      $title.="<i>".i18n('thumbUpdateTitle',array('<b>'.htmlFormatDate($updateDate).'</b>')).'</i>';
    }else{
      $title.="<br><i>".i18n('thumbUpdateTitle',array('<b>'.htmlFormatDate($updateDate).'</b>')).'</i>';
    }
  }
  $title=htmlEncode($title,'quotes');
  $file="../view/css/images/calendar$color$addName$size.png";
  $res='<span style="position:relative;float:'.$float.';padding-right:3px">';
  $res.='<a ';
	//$res.=' src="'.$file.'" ';
	if (! $print) {
	  $res.=' onMouseOver="showBigImage(null,null,this,\''.$title.'\');" onMouseOut="hideBigImage();"';
	}
	$res.='>';
	$res.="<div class='calendar$color$addName$size' style=';width:".$size."px;height:".$size."px;' >&nbsp;</div>";
	$res.='</a>';
	
  $month=getMonthName(substr($date, 5,2),5);
  $day=substr($date, 8,2);
  $dispDate=htmlFormatDate($date,true);
  if (substr($dispDate,4,1)=='-') {
    $dispDate=substr($dispDate,5);
  } else {
    $dispDate=substr($dispDate,0,5);
  }
  switch ($size) {
	  case 22:
	    $fontSize=6.5;
	    $width=20;
	    $float="float:right;";
	    $top=8;
	    break;
	  case 32:
	    $fontSize=8;
	    $dispDate.='<br/>'.substr($date, 0,4);
	    $width=31;
	    $float="";
	    $top=10;
	    break;
	  default:
	    $fontSize=11;
	    $width=10;
	    $float="";
	}
	$res.='<div style="z-index:0;color:#000;background: transparent;pointer-events:none;text-align:center;'
	    .'width:'.$width.'px;'.$float.';position:absolute;top:'.$top.'px;font-size:'.$fontSize.'px;">'.$dispDate.'</div>';
	$res.='</span>';  
	return $res;
}

//ADD qCazelles - Ticket #170
function formatDateThumbWithText($date,$text,$float='right',$size=22,$addName="") {
  global $print;
  if ($print) return "";//htmlFormatDate($creationDate);
  $today=date('Y-m-d');
  $dateTrunc=substr($date,0,10);
  $color="White";
  if ($dateTrunc==$today) {
    $color='Red';
  } else if (addWorkDaysToDate($dateTrunc,2)==$today) {
    $color='Yellow';
  }
  $title=i18n($text,array('<b>'.htmlFormatDate($date).'</b>'));
  $title=htmlEncode($title,'quotes');
  $file="../view/css/images/calendar$color$addName$size.png";
  $res='<span style="position:relative;float:'.$float.';padding-right:3px">';
  $res.='<a ';
  //$res.=' src="'.$file.'" ';
  if (! $print) {
    $res.=' onMouseOver="showBigImage(null,null,this,\''.$title.'\');" onMouseOut="hideBigImage();"';
  }
  $res.='>';
  $res.="<div class='calendar$color$addName$size' style=';width:".$size."px;height:".$size."px;' >&nbsp;</div>";
  $res.='</a>';
  
  $month=getMonthName(substr($date, 5,2),5);
  $day=substr($date, 8,2);
  $dispDate=htmlFormatDate($date,true);
  if (substr($dispDate,4,1)=='-') {
    $dispDate=substr($dispDate,5);
  } else {
    $dispDate=substr($dispDate,0,5);
  }
  switch ($size) {
    case 22:
      $fontSize=6.5;
      $width=20;
      $float="float:right;";
      $top=8;
      break;
    case 32:
      $fontSize=8;
      $dispDate.='<br/>'.substr($date, 0,4);
      $width=31;
      $float="";
      $top=10;
      break;
    default:
      $fontSize=11;
      $width=10;
      $float="";
  }
  $res.='<div style="z-index:0;color:#000;background: transparent;pointer-events:none;text-align:center;'
      .'width:'.$width.'px;'.$float.';position:absolute;top:'.$top.'px;font-size:'.$fontSize.'px;">'.$dispDate.'</div>';
      $res.='</span>';
      return $res;
}
//END ADD qCazelles - Ticket #170

function formatPrivacyThumb($privacy, $team) {
  // privacy=3 => private
  // privacy=2 => team
  // privacy=1 => public 
  if ($privacy == 3) {
    $title=htmlEncode(i18n('private'),'quotes');
    //echo '<img style="float:right;padding-right:3px" src="img/private.png" />';
    echo '<span style="float:right;padding-right:3px">';
    echo formatIcon('Fixed',22,$title,false);
    echo '</span>';
  } else if ($privacy == 2) {
    $title=htmlEncode(i18n('team')." : ".SqlList::getNameFromId ('Team',$team ),'quotes');
    //echo '<img title="'.$title.'" style="float:right;padding-right:3px" src="img/team.png" />';
    echo '<span style="float:right;padding-right:3px">';
    echo formatIcon('Team',22,$title,false);
    echo '</span>';
  }
}

function formatCommentThumb($comment,$img=null) {
  global $print;
  if ($print) return "";//$userName;
  $res='';
  if (! trim($comment)) return '';
  $title=htmlEncode($comment,'title');
  $res.='<span onMouseOver="showBigImage(null,null,this,\''.$title.'\');" onMouseOut="hideBigImage();"  style="margin-right:5px">';
  if ($img) {
    $res.='<img src="'.$img.'"/>';
  } else {
    $res.= formatSmallButton('Comment');
  }
  $res.= '</span>';
  return $res;
}

function getMonthName($month,$maxLength=0) {
  global $monthArray;
  if (! $month or $month==0) return '';
  if (count($monthArray)==0) {
    $monthArray=array(i18n("January"),i18n("February"),i18n("March"),
      i18n("April"), i18n("May"),i18n("June"),
      i18n("July"), i18n("August"), i18n("September"),
      i18n("October"),i18n("November"),i18n("December"));
  }
  $dispMonth=$monthArray[$month-1];
  if ($maxLength) {
    if ($maxLength=='auto') {
      $dispMonth=substr($dispMonth,0,4);
      if (strpos('aàeéèêiîïoôuù',substr($dispMonth,-1))!==false) {
        $dispMonth=substr($dispMonth,0,3);
      }
    } else {
      $dispMonth=substr($dispMonth,0,$maxLength);
    }
  }
  return $dispMonth;
}

function diffValues(&$old,&$new) {
  if ($old) {
    $array=Diff::compare(diffReplaceEOL($old), diffReplaceEOL($new));
    $arrayOld=array();
    $arrayNew=array();
    foreach ($array as $id=>$line) {
      if ($line[1]==Diff::DELETED) {
        $arrayOld[$id]=$line;
      } else if ($line[1]==Diff::INSERTED) {
        $arrayNew[$id]=$line;
      }
    }
    if ( (count($arrayNew)+count($arrayOld))<count($array)) { // Set Diff only if diff is shorter than original
      $new=nl2br(Diff::toString($arrayNew));
      $old=nl2br(Diff::toString($arrayOld));
    }
  }
}
function diffReplaceEOL($valIn) {
  $val=preg_replace('/<p(.)*?>/', "\n", $valIn);
  $val=preg_replace('/<td(.)*?>/', "\n", $val);
  $val=preg_replace('/<tr(.)*?>/', "\n", $val);
  $val=preg_replace('/<table(.)*?>/', "\n", $val);
  $val=str_replace(array('&nbsp;','<br />','<br/>','<div>','</div>','</p>','</td>','</tr>','</table>','<tbody>','</tbody>','color:white'),
                   array(' '     ,"\n"    ,"\n"   ,"\n"   ,''      ,''    ,''     ,''     ,''        ,''       ,''        ,'color:grey'),
                   $val);
  if (substr_count($val,'<o:p> </o:p>')>0 or substr_count($val,'<o:p></o:p>')>0) {
    $val=strip_tags($val);
    //return $valIn;
  }
  return $val;
}
function privateFormatter($value) {
  if ($value==0) {
    return "";
  } else {
    return '<div style="width:100%;text-align:center"><img style="height:16px" src="img/private.png" /></div>';
  }
}

function activityStreamDisplayNote ($note,$origin){
  global $print,$user, $userRessource;
  
  $rightWidthScreen=RequestHandler::getNumeric('destinationWidth');
  $userId = $note->idUser;
  $userName = SqlList::getNameFromId ( 'User', $userId );
  $userNameFormatted = '<span style="color:blue"><strong>' . $userName . '</strong></span>';
  $idNote = '<span style="color:blue">#' . $note->id . '</span>';
  $ticketName = '<span style="color:blue;cursor:pointer;" onClick="gotoElement(\''.htmlEncode($note->refType).'\',\''.htmlEncode($note->refId).'\')">' . $note->refType . ' #' . $note->refId . '</span>';
  if ($origin=='activityStream') $ticketName.=' - '.SqlList::getNameFromId($note->refType, $note->refId);
  if ($note->updateDate)  $colCommentStream = i18n ( 'activityStreamUpdateComment', array ($idNote, $ticketName ) );
  else  $colCommentStream = i18n ( 'activityStreamCreationComment', array ($idNote, $ticketName ) );
  if (!$user) $user=getSessionUser();
  if (!$userRessource) $userRessource=new Affectable($user->id);
  $objectClass=$note->refType;
  $objectId=$note->refId;
  $obj=new $objectClass($objectId,true);
  $canUpdate=securityGetAccessRightYesNo('menu' . $objectClass, 'update', $obj) == "YES";
  $canRead=securityGetAccessRightYesNo('menu' . $objectClass, 'read', $obj) == "YES";
  if ($origin=='activityStream' and !$canRead) {
    return ;
  }
  $isNoteClosed=getSessionTableValue("closedNotes", $note->id);
  if ($note->idPrivacy == 1 or ($note->idPrivacy == 3 and $user->id == $note->idUser) or ($note->idPrivacy == 2 and $userRessource->idTeam == $note->idTeam)) {
    echo '<tr style="height:100%;"><td class="noteData" style="width:100%;"><div style="float:left;margin-top:6px;">';
    echo formatUserThumb($note->idUser, $userName, 'Creator',32,'left');
    echo formatPrivacyThumb($note->idPrivacy, $note->idTeam);
    echo '</div><div>';
    echo '<table style="float:right;"><tr><td>';
    if($origin=="objectStream" || $origin=="objectStreamKanban") {
          if ($note->idUser == $user->id and !$print and $canUpdate) echo  '<div style="float:right;" ><a onClick="removeNote(' . htmlEncode($note->id) . ');" title="' . i18n('removeNote') . '" > '.formatSmallButton('Remove').'</a></div>';
    }
    echo '</td></tr><tr><td>';
    echo '<div "style=float:right;"><a  id="imgCollapse_'.$note->id.'" style="float:right;" onclick="switchNoteStatus('.$note->id.');">'.formatSmallButton('Collapse'.(($isNoteClosed)?'Open':'Hide')).'</a></div>';
    echo '</div></td></tr></table>';
    
    if ($origin=='objectStream') {
    	$rightWidth=(intval(Parameter::getUserParameter('contentPaneRightDetailDivWidth'.$objectClass))-30).'px"';
    } else {
    	if (RequestHandler::isCodeSet('destinationWidth')) {
        $rightWidth=(RequestHandler::getNumeric('destinationWidth')-30).'px';
    	} else {
    		$rightWidth="100%";
    	}
    }
    echo '<div class="activityStreamNoteContainer" style="padding-left:4px;max-width:'.$rightWidth.'">';
    $strDataHTML=$note->note;
    echo '<div><div style="margin-top:2px;margin-left:37px;">'.$userNameFormatted.'&nbsp'.$colCommentStream.'</div>'; 
  	echo '<div style="margin-top:3px;margin-left:37px;">'.formatDateThumb($note->creationDate,$note->updateDate,"left").'</div>';
  	if($note->updateDate){
  	 echo '<div style="margin-top:8px;">'.htmlFormatDateTime($note->updateDate,true).'</div></div>';    	 
    } else {
     echo '<div style="margin-top:8px;">'.htmlFormatDateTime($note->creationDate,true).'</div></div>';
    }
    if($rightWidthScreen<100){
      echo '<div class="activityStreamNoteContent" id="activityStreamNoteContent_'.$note->id.'" style="display:block;height:'.(($isNoteClosed)?'0px':'100%').';margin-left:'.(($origin=='activityStream')?'36':'0').'px;margin-bottom:'.(($isNoteClosed)?'0px':'10px').';word-break:break-all;">'.$strDataHTML.'</div></div></td></tr>'; 
    } else {
      echo '<div class="activityStreamNoteContent" id="activityStreamNoteContent_'.$note->id.'" style="display:block;height:'.(($isNoteClosed)?'0px':'100%').';margin-left:'.(($origin=='activityStream')?'36':'0').'px;margin-bottom:'.(($isNoteClosed)?'0px':'10px').';">'.$strDataHTML.'</div></div></td></tr>';
    } 
  }
}
?>