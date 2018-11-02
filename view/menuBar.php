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
 * Presents left menu of application. 
 */
  require_once "../tool/projeqtor.php";
  scriptLog('   ->/view/menuBar.php');
  //$iconSize=Parameter::getUserParameter('paramTopIconSize');
  $iconSize=32;
  $showMenuBar=Parameter::getUserParameter('paramShowMenuBar');
  $showMenuBar='YES';
  //$showMenuBar='NO';
  if (! $iconSize or $showMenuBar=='NO') $iconSize=16;
  $allMenuClass=array('menuBarItem'=>'all','menuBarCustom'=>'custom');
  
  $customMenuArray=SqlList::getListWithCrit("MenuCustom",array('idUser'=>getSessionUser()->id));
  
  $cptAllMenu=0;
  $obj=new Menu();
  $menuList=$obj->getSqlElementsFromCriteria(null, false);
  $pluginObjectClass='Menu';
  $tableObject=$menuList;
  $lstPluginEvt=Plugin::getEventScripts('list',$pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $menuList=$tableObject;
  $defaultMenu=Parameter::getUserParameter('defaultMenu');

// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM  
  $isNotificationSystemActiv = isNotificationSystemActiv();
// END - ADD BY TABARY - NOTIFICATION SYSTEM
  $isLanguageActive=(Parameter::getGlobalParameter('displayLanguage')=='YES')?true:false;
  
  if (! $defaultMenu) $defaultMenu='menuBarItem';
  foreach ($menuList as $menu) {
// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM  
    if (! $isNotificationSystemActiv and strpos($menu->name, "Notification")!==false) { continue; }
// END - ADD BY TABARY - NOTIFICATION SYSTEM          if (securityCheckDisplayMenu($menu->id,substr($menu->name,4))) {
    if (! $isLanguageActive and $menu->name=="menuLanguage") { continue; }
    if (securityCheckDisplayMenu($menu->id,substr($menu->name,4))) {
      $menuClass=$menu->menuClass;
      if (in_array($menu->name,$customMenuArray)) $menuClass.=" menuBarCustom";
      if ($menu->type!='menu' and (strpos(' menuBarItem '.$menuClass, $defaultMenu)>0)) {
        $cptAllMenu+=1;
      }
      if ($menu->type=='menu' or $menu->name=='menuAlert' or $menu->name=='menuToday' or $menu->name=='menuReports' or $menu->name=='menuParameter' or $menu->name=='menuUserParameter') {
        continue;
      }
      $sp=explode(" ", $menu->menuClass);
      foreach ($sp as $cl) {
        if (trim($cl)) {
          $allMenuClass[$cl]=$cl;
        }
      }
    }
  }
  
  function drawMenu($menu) {
  	global $iconSize, $defaultMenu,$customMenuArray;
  	$menuName=$menu->name;
  	$menuClass=' menuBarItem '.$menu->menuClass;
  	if (in_array($menu->name,$customMenuArray)) $menuClass.=' menuBarCustom';
  	$idMenu=$menu->id;
    $style=(strpos($menuClass, $defaultMenu)===false)?'display: none;':'display: block; opacity: 1;';
  	if ($menu->type=='menu') {
    	if ($menu->idMenu==0) {
    		//echo '<td class="menuBarSeparator" style="width:5px;"></td>';
    	}
    } else if ($menu->type=='item') {
    	  $class=substr($menuName,4); 
        //echo '<td  title="' .(($menuName=='menuReports')?'':i18n($menu->name)) . '" >';
    	  echo '<td  title="' .i18n($menu->name) . '" >';
        echo '<div class="'.$menuClass.'" style="position:relative;'.$style.'" id="'.$class.'" ';
        echo 'onClick="hideReportFavoriteTooltip(0);loadMenuBarItem(\'' . $class .  '\',\'' . htmlEncode(i18n($menu->name),'quotes') . '\',\'bar\');" ';
        echo 'oncontextmenu="event.preventDefault();customMenuManagement(\''.$class.'\');" ';
        if ($menuName=='menuReports' and isHtml5() ) {
          echo ' onMouseEnter="showReportFavoriteTooltip();"';
          echo ' onMouseLeave="hideReportFavoriteTooltip(2000);"';
        }
        echo '>';
        //echo '<img src="../view/css/images/icon' . $class . $iconSize.'.png" />';
        echo '<div class="icon' . $class . $iconSize.' icon'.$class.' iconSize'.$iconSize.'" style="margin-left:9px;width:'.$iconSize.'px;height:'.$iconSize.'px" ></div>';
        echo '<div class="menuBarItemCaption">'.i18n($menu->name).'</div>';
        if ($menuName=='menuReports' and isHtml5() ) {?>
          <button class="comboButtonInvisible" dojoType="dijit.form.DropDownButton" 
           id="listFavoriteReports" name="listFavoriteReports" style="position:relative;top:-10px;left:-10px;height: 0px; overflow: hidden; ">
            <div dojoType="dijit.TooltipDialog" id="favoriteReports" style="position:absolute;"
              href="../tool/refreshFavoriteReportList.php"
              onMouseEnter="clearTimeout(closeFavoriteReportsTimeout);"
              onMouseLeave="hideReportFavoriteTooltip(200)"
              onDownloadEnd="checkEmptyReportFavoriteTooltip()">
              <?php Favorite::drawReportList();?>
            </div>
          </button>
        <?php }
        echo '</div>';
        echo '</td>'; 
    } else if ($menu->type=='plugin') {
      $class=substr($menuName,4);
      echo '<td  title="' .i18n($menu->name) . '" >';
      echo '<div class="'.$menuClass.'" style="'.$style.'" id="'.$class.'"';
      echo 'oncontextmenu="event.preventDefault();customMenuManagement(\''.$class.'\');" ';
      echo 'onClick="loadMenuBarPlugin(\'' . $class .  '\',\'' . htmlEncode(i18n($menu->name),'quotes') . '\',\'bar\');">';
      echo '<img src="../view/css/images/icon' . $class . $iconSize.'.png" />';
      echo '<div class="menuBarItemCaption">'.i18n($menu->name).'</div>';
      echo '</div>';
      echo '</td>';
    } else if ($menu->type=='object') { 
      $class=substr($menuName,4);
      if (securityCheckDisplayMenu($idMenu, $class)) {
      	echo '<td title="' .i18n('menu'.$class) . '" >';
      	echo '<div class="'.$menuClass.'" style="'.$style.'" id="'.$class.'" ';
      	echo 'oncontextmenu="event.preventDefault();customMenuManagement(\''.$class.'\');" ';
      	echo 'onClick="loadMenuBarObject(\'' . $class .  '\',\'' . htmlEncode(i18n($menu->name),'quotes') . '\',\'bar\');" >';
      	echo '<div class="icon' . $class . $iconSize.' icon'.$class.' iconSize.'.$iconSize.'" style="margin-left:9px;width:'.$iconSize.'px;height:'.$iconSize.'px" ></div>';
      	//echo '<img src="../view/css/images/icon' . $class . $iconSize. '.png" />';
      	echo '<div class="menuBarItemCaption">'.i18n('menu'.$class).'</div>';
      	echo '</div>';
      	echo '</td>';
      }
    }
  }  
  
  function drawAllMenus($menuList) {
    global $isLanguageActive, $isNotificationSystemActiv;
    //echo '<td>&nbsp;</td>';
    $obj=new Menu();
    $menuList=$obj->getSqlElementsFromCriteria(null, false);
    $pluginObjectClass='Menu';
    $tableObject=$menuList;
    $lstPluginEvt=Plugin::getEventScripts('list',$pluginObjectClass);
    foreach ($lstPluginEvt as $script) {
      require $script; // execute code
    }
    $menuList=$tableObject;
    $lastType='';
    foreach ($menuList as $menu) { 
      if (! $isLanguageActive and $menu->name=="menuLanguage") { continue; }
      if (! $isNotificationSystemActiv and strpos($menu->name, "Notification")!==false) { continue; }
      if (securityCheckDisplayMenu($menu->id,substr($menu->name,4)) ) {
    		drawMenu($menu);
    		$lastType=$menu->type;
    	}
    }
    //echo '<td>&nbsp;</td>';
  }
?>
<table width="100%">
  <tr height="<?php echo $iconSize+8; ?>px">  
    <td style="min-width:320px;width:40%;position:relative; white-space:nowrap">
      <button id="menuBarUndoButton" dojoType="dijit.form.Button" showlabel="false"
       title="<?php echo i18n('buttonUndoItem');?>"
       disabled="disabled"
       style="position:relative;left: 5px; top: -7px; z-index:30;height:18px"
       iconClass="dijitButtonIcon dijitButtonIconPrevious" class="detailButton" >
        <script type="dojo/connect" event="onClick" args="evt">
          undoItemButton();
        </script>
      </button>  
      <button id="menuBarRedoButton" dojoType="dijit.form.Button" showlabel="false"
       title="<?php echo i18n('buttonRedoItem');?>"
       disabled="disabled"
       style="position:relative;left: 0px; top: -7px; z-index:30;height:18px"
       iconClass="dijitButtonIcon dijitButtonIconNext" class="detailButton" >
        <script type="dojo/connect" event="onClick" args="evt">
          redoItemButton();
        </script>
      </button>
      <a id="menuBarNewtabButton" title="<?php echo i18n('buttonNewtabItem');?>"
         style="height:18px; position:relative;left: 5px; top:-7px; z-index:30; width:60px;" 
         href="" target="_blank">
        <button dojoType="dijit.form.Button" iconClass="dijitButtonIcon iconNewtab" class="detailButton"
          style="height:18px;width:60px;">
          <script type="dojo/connect" event="onClick" args="evt">
            var url="main.php?directAccess=true";
            if (dojo.byId('objectClass') && dojo.byId('objectClass').value) { 
              url+="&objectClass="+dojo.byId('objectClass').value;
            } else {
              url+="&objectClass=Today";
            }
            if (dojo.byId('objectId') && dojo.byId('objectId').value) {
              url+="&objectId="+dojo.byId('objectId').value;
            } else {
              url+="&objectId=";
            }
            dojo.byId("menuBarNewtabButton").href=url;
          </script>
        </button>
      </a>
          
      <span class="titleProject" style="position: relative; left:20px; top:-6px; text-align:right;">
        &nbsp;<?php echo (i18n("projectSelector"));?>&nbsp;:&nbsp;
      </span>
      <span style="display:inline-block;width:170px; position:relative;left : 10px; top:-6px" title="<?php echo i18n("projectSelectorHelp");?>" >
        <span style="postion:absolute;height:16px;" dojoType="dijit.layout.ContentPane" region="center"   id="projectSelectorDiv">
          &nbsp;<?php include "menuProjectSelector.php"?>
        </span>
      </span>
      <span style="position: relative; left:7px; top:-7px; height: 20px">
        <button id="projectSelectorParametersButton" dojoType="dijit.form.Button" showlabel="false"
         title="<?php echo i18n('dialogProjectSelectorParameters');?>" style="top:2px;height:20px;"
         iconClass="iconParameter16 iconParameter iconSize16" xclass="detailButton">
          <script type="dojo/connect" event="onClick" args="evt">
           loadDialog('dialogProjectSelectorParameters', null, true);
          </script>
        </button>
      </span>
      <span style="position: relative; left:7px; top:-7px; height: 20px">
      <button id="projectSelectorSelectCurrent" dojoType="dijit.form.Button" showlabel="false"
         title="<?php echo i18n('selectCurrentProject');?>" style="top:2px;height:20px;"
         ondblclick="directUnselectProject();"
         onClick="if (!timeoutDirectSelectProject) {showWait();timeoutDirectSelectProject=setTimeout('directSelectProject();',500);}"
         iconClass="iconProject16 iconProject iconSize16" xclass="detailButton">
        </button>

      </span>
    </td>
    <td width="" style="text-align:center;">
      <div style="position:absolute;font-size:130%;top:5px;left:50%;width:600px;margin-left:-300px;z-index:-1"><?php htmlDisplayDatabaseInfos();?></div>
    </td>    
    <?php if(isNotificationSystemActiv() and securityCheckDisplayMenu(null,'Notification')) {?>
    <td  width="63px" style=""> 
     <div dojoType="dijit.layout.ContentPane" id="menuBarNotificationCount"  style="text-align: center; position:relative;top:-4px">
       <div dojoType="dijit.form.DropDownButton"  id=""
            style="display: table-cell;background-color: #D3D3D3;vertical-align: middle;" >
          <span  class="iconNotification32 iconNotification iconSize32" style="display: table-cell;">  
            <span id="countNotifications" class="menuBarNotificationCount" style="text-align:center;" >
              0
            </span>
          </span>
          <div id="drawNotificationUnread" dojoType="dijit.TooltipDialog"
               style="  max-width:360px; overflow-x:hidden; height:300px;  max-height:300px;  width:360px;">
              <?php include "menuNotificationRead.php" ?>          
          </div>       
       </div>         
     </div>
    </td>
    <?php } ?>    
    <td title="<?php echo i18n('infoMessage');?>" style="vertical-align: middle;text-align:center;width:105px;"> 
      <div class="pseudoButton"  style="height:28px; position:relative;top:-5px; z-index:30; width:100px;" >
        <a target="#" href="<?php echo $website;?>" >
          <table style="width:100%">
            <tr>
              <td class="dijitTreeRow" style="position:relative; top:-2px;vertical-align: middle;text-align:center;width:70px">
                <?php echo "$copyright<br>$version";?>
              </td>
              <td  style="width:35px">
                <img style="height:28px;width:28px" src="img/logoSmall.png" />
              </td>
            </tr>
          </table>
        </a>
      </div>  
    </td>
      
    <td title="<?php echo i18n('menuUserParameter');?>"  style="position:relative;width:100px;">
      <div dojoType="dijit.layout.ContentPane"  id="menuUserParameterTop" class="pseudoButton" style="position:relative;overflow:hidden; height:28px; min-width:100px;top:-5px;" title="<?php echo i18n('menuUserParameter');?>">
        <div dojoType="dijit.form.DropDownButton"  id="iconMenuUserPhoto" style="display: table-cell;background-color: #D3D3D3;vertical-align: middle;position:relative;min-width:100px;top:-3px" >
			    <table style="width:100%">
    			  <tr>
      			  <?php $user=getSessionUser();
      					 $imgUrl=Affectable::getThumbUrl('User',$user->id, 22,true);
      				if ($imgUrl) {?>  
      				<td style="width:24px;vertical-align:middle;position:relative;">          
      				  <img style="border-radius:13px;height:26px" src="<?php echo $imgUrl; ?>" />
      				</td>
      			  <?php } else {?>
      				<td style="width:24px;padding-top:2px;">
      				  <div class="iconUserParameter22 iconUserParameter iconSize22">&nbsp;</div> 
      				</td>
      			   <?php }?>
      			  <td style="vertical-align:middle;">&nbsp;<?php echo ($user->resourceName)?$user->resourceName:$user->name; ?>&nbsp;&nbsp;</td>
    			  </tr>
			    </table>
			    <div id="drawMenuUser" dojoType="dijit.TooltipDialog"
             style="max-width:600px; overflow-x:hidden; height:450px;  max-height:500px;  width:350px;">
             <?php include "menuUserTop.php" ?>          
          </div> 
		    </div>
      </div>
    </td> 
    
    <td width="<?php echo (isIE())?37:35;?>px" style="padding-left:3px;" > 
      <div class="pseudoButton" onclick="switchMode();" style="height:28px; position:relative;top:-5px; z-index:30; width:30px;" title="<?php echo i18n("buttonSwitchedMode");?>">
        <table >
          <tr>
            <td style="width:28x;text-align:center">
              <div class="dijitButtonIcon dijitButtonIconSwitchMode" style="position:absolute;top:2px;left:3px" ></div>
            </td>
          </tr>
        </table>    
     </div>
    </td>
     <?php if (! isIE()) {?>
    <td  width="35px" > 
      <div  class="pseudoButtonFullScreen" style="height:28px; position:relative; top:-5px; z-index:30; width:28px;" onclick="toggleFullScreen()" >
        <table>
          <tr>
            <td style="width:28px" >
              <?php echo formatIcon('FullScreen', 32,i18n("fullScreen"));?>
            </td>
          </tr>
        </table>
      </div>
    </td>
     <?php }?>  
    <td width="24px">   
      <div id="hideMenuBarShowButtonTop" style="cursor:pointer;position:relative; top:-11px;right:0px; z-index:949" >
  		  <a onClick="hideMenuBarShowModeTop();" id="buttonSwitchedMenuBarTopShow" title="<?php echo i18n("buttonShowMenu");?>" >
  		    <span style='display:inline-block;width:24px;height:22px;'>
  		     <div style="position:absolute;top:1px;right:0px;height:26px;width:24px" dojoType="dijit.form.Button" iconClass="dijitButtonIcon iconHideStream22" class="detailButton">&nbsp;</div>
  		    </span>
  		  </a>
		  </div>
    </td>
  </tr>
</table>
<div class="customMenuAddRemove"  id="customMenuAdd" onClick="customMenuAddItem();"><?php echo i18n('customMenuAdd');?></div>
<div class="customMenuAddRemove"  id="customMenuRemove" onClick="customMenuRemoveItem();"><?php echo i18n('customMenuRemove');?></div>
      