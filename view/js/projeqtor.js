/*******************************************************************************
 * COPYRIGHT NOTICE *
 * 
 * Copyright 2009-2017 ProjeQtOr - Pascal BERNARD - support@projeqtor.org
 * Contributors : -
 * 
 * This file is part of ProjeQtOr.
 * 
 * ProjeQtOr is free software: you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version.
 * 
 * ProjeQtOr is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * ProjeQtOr. If not, see <http://www.gnu.org/licenses/>.
 * 
 * You can get complete code of ProjeQtOr, other resource, help and information
 * about contributors at http://www.projeqtor.org
 * 
 * DO NOT REMOVE THIS NOTICE **
 ******************************************************************************/

// ============================================================================
// All specific ProjeQtOr functions and variables
// This file is included in the main.php page, to be reachable in every context
// ============================================================================
// =============================================================================
// = Variables (global)
// =============================================================================
var i18nMessages = null; // array containing i18n messages
var i18nMessagesCustom = null; // array containing i18n messages
var currentLocale = null; // the locale, from browser or user set
var browserLocale = null; // the locale, from browser
var cancelRecursiveChange_OnGoingChange = false; // boolean to avoid
// recursive change trigger
var formChangeInProgress = false; // boolean to avoid exit from form when
// changes are not saved
var currentRow = null; // the row num of the current selected
// element in the main grid
var currentFieldId = ''; // Id of the ciurrent form field (got
// via onFocus)
var currentFieldValue = ''; // Value of the current form field (got
// via onFocus)
var g; // Gant chart for JsGantt : must be
// named "g"
var quitConfirmed = false;
var noDisconnect = false;
var forceRefreshMenu = false;
var directAccessIndex = null;

var debugPerf = new Array();

var pluginMenuPage = new Array();

var previousSelectedProject=null;
var previousSelectedProjectName=null;
// =============================================================================
// = Functions
// =============================================================================

/**
 * ============================================================================
 * Refresh the ItemFileReadStore storing Data for the main grid
 * 
 * @param className
 *          the class of objects in the list
 * @param idle
 *          the idle filter parameter
 * @return void
 */

// Function to call console log without messing with debug
function consoleTraceLog(message) {
  // console.log to keep
  console.log(message);
}
function refreshJsonList(className, keepUrl) {
  var grid = dijit.byId("objectGrid");
  if (grid) {
    showWait();
    var sortIndex = grid.getSortIndex();
    var sortAsc = grid.getSortAsc();
    var scrollTop = grid.scrollTop;
    // store = grid.store;
    // store.close();
    unselectAllRows("objectGrid");
    url = "../tool/jsonQuery.php?objectClass=" + className;
    if (dojo.byId('comboDetail')) {
      url = url + "&comboDetail=true";
      if (dojo.byId('comboDetailId')) {
        dojo.byId('comboDetailId').value = '';
      }
    }
    if (dojo.byId('showAllProjects')) {
      if (dojo.byId('showAllProjects').checked) {
        url = url + "&showAllProjects=true";
      }
    }
    if (dojo.byId('listShowIdle')) {
      if (dojo.byId('listShowIdle').checked) {
        url = url + "&idle=true";
      }
    }
    if (dijit.byId('listTypeFilter')) {
      if (dijit.byId('listTypeFilter').get("value") != '') {
        url = url + "&objectType=" + dijit.byId('listTypeFilter').get("value");
      }
    }
    if (dijit.byId('listClientFilter')) {
      if (dijit.byId('listClientFilter').get("value") != '') {
        url = url + "&objectClient="
            + dijit.byId('listClientFilter').get("value");
      }
    }
    if (dijit.byId('listElementableFilter')) {
      if (dijit.byId('listElementableFilter').get("value") != '') {
        url = url + "&objectElementable="
            + dijit.byId('listElementableFilter').get("value");
      }
    }
    //ADD qCazelles - Filter by status
    if (dojo.byId('countStatus')) {
    	var filteringByStatus = false;
    	for (var i = 1; i <= dojo.byId('countStatus').value; i++) {
    		if (dijit.byId('showStatus' + i).checked) {
    			url = url + "&objectStatus" + i + "=" + dijit.byId('showStatus' + i).value;
    			filteringByStatus = true;
    		}
    	}
    	if (filteringByStatus) {
    		url = url + "&countStatus=" + dojo.byId('countStatus').value;
    	}
    }
    //END ADD qCazelles - Filter by status
    if (dijit.byId('quickSearchValue')) {
      if (dijit.byId('quickSearchValue').get("value") != '') {
        // url = url + "&quickSearch=" +
        // dijit.byId('quickSearchValue').get("value");
        url = url + "&quickSearch="
            + encodeURIComponent(dijit.byId('quickSearchValue').get("value"));
      }
    }

    // store.fetch();
    if (!keepUrl) {
      grid.setStore(new dojo.data.ItemFileReadStore({
        url : url,
        clearOnClose : 'true'
      }));
    }
    store = grid.store;
    store.close();
    store
        .fetch({
          onComplete : function() {
            grid._refresh();
            hideBigImage(); // Will avoid resident pop-up always displayed
            var objectId = dojo.byId('objectId');
            setTimeout('dijit.byId("objectGrid").setSortIndex(' + sortIndex
                + ',' + sortAsc + ');', 10);
            setTimeout('dijit.byId("objectGrid").scrollTo(' + scrollTop + ');',
                20);
            setTimeout('selectRowById("objectGrid", '
                + parseInt(objectId.value) + ');', 30);
            setTimeout('hideWait();', 40);
            filterJsonList();
          }
        });
  }
}

/**
 * ============================================================================
 * Refresh the ItemFileReadStore storing Data for the planning (gantt)
 * 
 * @return void
 */
function refreshJsonPlanning() {
  param = false;
  if (dojo.byId("resourcePlanning")) {
    url = "../tool/jsonResourcePlanning.php";
  } else if (dojo.byId("versionsPlanning")) {
	  url = "../tool/jsonVersionsPlanning.php";
  } else if (dojo.byId("globalPlanning")) {
    url = "../tool/jsonPlanning.php?global=true";
    param=true;
  } else {
    url = "../tool/jsonPlanning.php";
  }
  
  //ADD qCazelles - GANTT
  if (dojo.byId('nbPvs')) {
	  url += (param) ? "&" : "?";
	  for (var i = 0; i < dojo.byId('nbPvs').value; i++) {
		  if (i != 0) {
			  url += "&";
		  }
		  url += "pvNo" + i + "=" + dojo.byId('pvNo' + i).value;
	  }
	  if (dojo.byId('nbPvs').value != 0) {
		  param = true;
	  }
  }
  //END ADD qCazelles - GANTT
  
  if (dojo.byId('listShowIdle')) {
    if (dojo.byId('listShowIdle').checked) {
      url += (param) ? "&" : "?";
      url += "idle=true";
      param = true;
    }
  }
  if (dojo.byId('showWBS')) {
    if (dojo.byId('showWBS').checked) {
      url += (param) ? "&" : "?";
      url += "showWBS=true";
      param = true;
    }
  }
  if (dojo.byId('listShowResource')) {
    if (dojo.byId('listShowResource').checked) {
      url += (param) ? "&" : "?";
      url += "showResource=true";
      param = true;
    }
  }
  if (dojo.byId('listShowLeftWork')) {
    if (dojo.byId('listShowLeftWork').checked) {
      url += (param) ? "&" : "?";
      url += "showWork=true";
      param = true;
    }
  }
  if (dojo.byId('listShowProject')) {
    if (dojo.byId('listShowProject').checked) {
      url += (param) ? "&" : "?";
      url += "showProject=true";
      param = true;
    }
  }
  if (dijit.byId('listShowMilestone')) {
    url += (param) ? "&" : "?";
    url += "showMilestone=" + dijit.byId('listShowMilestone').get("value");
    param = true;
  }
  if (dijit.byId('listShowNullAssignment')) {
	  if (dojo.byId('listShowNullAssignment').checked) {
	    url += (param) ? "&" : "?";
	    url += "listShowNullAssignment=true";
	    param = true;
	  }
	} 
  loadContent(url, "planningJsonData", 'listForm', false);
}

/**
 * ============================================================================
 * Filter the Data of the main grid on Id and/or Name
 * 
 * @return void
 */
function filterJsonList() {
  var filterId = dojo.byId('listIdFilter');
  var filterName = dojo.byId('listNameFilter');
  var grid = dijit.byId("objectGrid");
  if (grid && (filterId || filterName)) {
    filter = {};
    unselectAllRows("objectGrid");
    filter.id = '*'; // delfault
    if (filterId) {
      if (filterId.value && filterId.value != '') {
        filter.id = '*' + filterId.value + '*';
      }
    }
    if (filterName) {
      if (filterName.value && filterName.value != '') {
        filter.name = '*' + filterName.value + '*';
      }
    }
    grid.query = filter;
    grid._refresh();
  }
  refreshGridCount();
  selectGridRow();
}

function refreshGrid(noReplan) {
  if (dijit.byId("objectGrid")) { // Grid exists : refresh it
    showWait();
    if (dojo.byId('objectClassList')) refreshJsonList(dojo.byId('objectClassList').value, true);
    else refreshJsonList(dojo.byId('objectClass').value, true);
  } else { // If Grid does not exist, we are displaying Planning : refresh it
    showWait();
    if (dojo.byId('automaticRunPlan') && dojo.byId('automaticRunPlan').checked && ! noReplan ) {
      plan();
    } else {
      refreshJsonPlanning();
    }
  }
}
/**
 * Refresh de display of number of items in the grid
 * 
 * @param repeat
 *          internal use only
 */
avoidRecursiveRefresh = false;
function refreshGridCount(repeat) {
  var grid = dijit.byId("objectGrid");
  if (grid.rowCount == 0 && !repeat) {
    // dojo.byId('gridRowCount').innerHTML="?";
    setTimeout("refreshGridCount(1);", 100);
  } else {
    dojo.byId('gridRowCount').innerHTML = grid.rowCount;
    dojo.byId('gridRowCountShadow1').innerHTML = grid.rowCount;
    dojo.byId('gridRowCountShadow2').innerHTML = grid.rowCount;
  }
}

/**
 * ============================================================================
 * Return the current time, correctly formated as HH:MM
 * 
 * @return the current time correctly formated
 */
function getTime() {
  var currentTime = new Date();
  var hours = currentTime.getHours();
  var minutes = currentTime.getMinutes();
  if (minutes < 10) {
    minutes = "0" + minutes;
  }
  return hours + ":" + minutes;
}

/**
 * ============================================================================
 * Add a new message in the message Div, on top of messages (last being on top)
 * 
 * @param msg
 *          the message to add
 * @return void
 */
function addMessage(msg) {
  msg = msg.replace(" class='messageERROR' ", "");
  msg = msg.replace(" class='messageOK' ", "");
  msg = msg.replace(" class='messageWARNING' ", "");
  msg = msg.replace(" class='messageNO_CHANGE' ", "");
  msg = msg.replace("</div><div>", ", ");
  msg = msg.replace("</div><div>", ", ");
  msg = msg.replace("<div>", "");
  msg = msg.replace("<div>", "");
  msg = msg.replace("</div>", "");
  msg = msg.replace("</div>", "");
  var msgDiv = dojo.byId("messageDiv");
  if (msgDiv) {
    msgDiv.innerHTML = "[" + getTime() + "] " + msg + "<br/>"
        + msgDiv.innerHTML;
  }
}

/**
 * ============================================================================
 * Change display theme to a new one. Themes must be defined is projeqtor.css.
 * The change is also stored in Session.
 * 
 * @param newTheme
 *          the new theme
 * @return void
 */
function changeTheme(newTheme) {
  if (newTheme != "") {
    dojo.byId('body').className = 'tundra ' + newTheme;
    // Mehdi #2887
    var callBack = function() { 
      addMessage("Theme=" + newTheme); 
    };
    saveDataToSession('theme',newTheme, true, callBack);
  }
}

function saveUserParameter(parameter, value) {
  dojo.xhrPost({
    url : "../tool/saveUserParameter.php?parameter=" + parameter + "&value="
        + value,
    handleAs : "text",
    load : function(data, args) {
    }
  });
}
/**
 * ============================================================================
 * Save the browser locale to session. Needed for number formating under PHP 5.2
 * compatibility
 * 
 * @param none
 * @return void
 */
function saveBrowserLocaleToSession() {
  browserLocale = dojo.locale;
  //#2887
  saveDataToSession('browserLocale', browserLocale, null);
  var date = new Date(2000, 11, 31, 0, 0, 0, 0);
  if (browserLocaleDateFormat) {
    format = browserLocaleDateFormat;
  } else {
    var formatted = dojo.date.locale.format(date, {
      formatLength : "short",
      selector : "date"
    });
    var reg = new RegExp("(2000)", "g");
    format = formatted.replace(reg, 'YYYY');
    reg = new RegExp("(00)", "g");
    format = format.replace(reg, 'YYYY');
    reg = new RegExp("(12)", "g");
    format = format.replace(reg, 'MM');
    reg = new RegExp("(31)", "g");
    format = format.replace(reg, 'DD');
    browserLocaleDateFormat = format;
    browserLocaleDateFormatJs = browserLocaleDateFormat.replace(/D/g, 'd')
        .replace(/Y/g, 'y');
  }
  saveDataToSession('browserLocaleDateFormat', encodeURI(format));
  var fmt = "" + dojo.number.format(1.1) + " ";
  var decPoint = fmt.substr(1, 1);
  saveDataToSession('browserLocaleDecimalPoint', decPoint);
  var fmt = dojo.number.format(100000) + ' ';
  var thousandSep = fmt.substr(3, 1);
  if (thousandSep == '0') {
    thousandSep = '';
  }
  saveDataToSession('browserLocaleThousandSeparator', thousandSep);
}

/**
 * ============================================================================
 * Change the current locale. Has an impact on i18n function. The change is also
 * stored in Session.
 * 
 * @param locale
 *          the new locale (en, fr, ...)
 * @return void
 */
function saveDataToSessionAndReload(param, value, saveUserParameter) {
  var callBack = function() { 
    showWait();
    noDisconnect = true;
    quitConfirmed = true;
    dojo.byId("directAccessPage").value = "parameter.php";
    dojo.byId("menuActualStatus").value = menuActualStatus;
    dojo.byId("p1name").value = "type";
    dojo.byId("p1value").value = "userParameter";
    dojo.byId("directAccessForm").submit(); 
  };
  saveDataToSession(param, value, saveUserParameter, callBack);
}

function changeLocale(locale, saveAsUserParam) {
  if (checkFormChangeInProgress()) {
    dijit.byId("langMenuUserTop").set("value",currentLocale);
    return;
  }
  if (locale != "") {
    currentLocale = locale;
    //if (saveAsUserParam) saveDataToSession('lang', locale, true);
    saveDataToSessionAndReload('currentLocale', locale,true);
  }
}

function changeBrowserLocaleForDates(newFormat) {
  saveUserParameter('browserLocaleDateFormat', newFormat);
  // #2887
  var callBack = function() { 
	showWait();
    noDisconnect = true;
    quitConfirmed = true;
    dojo.byId("directAccessPage").value = "parameter.php";
    dojo.byId("menuActualStatus").value = menuActualStatus;
    dojo.byId("p1name").value = "type";
    dojo.byId("p1value").value = "userParameter";
    dojo.byId("directAccessForm").submit();
  };
  saveDataToSession('browserLocaleDateFormat', newFormat,null, callBack);
}
//gautier
function changeBrowserLocaleTimeFormat(newFormat) {
  saveUserParameter('browserLocaleTimeFormat', newFormat);
  //#2887
  var callBack = function() { 
	showWait();
	noDisconnect = true;
	quitConfirmed = true;
	dojo.byId("directAccessPage").value = "parameter.php";
	dojo.byId("menuActualStatus").value = menuActualStatus;
	dojo.byId("p1name").value = "type";
	dojo.byId("p1value").value = "userParameter";
	dojo.byId("directAccessForm").submit();
  };
  saveDataToSession('browserLocaleTimeFormat', newFormat,null, callBack);
}

function requestPasswordChange() {
  showWait();
  noDisconnect = true;
  quitConfirmed = true;
  window.location = "passwordChange.php";
  dojo.byId("directAccessPage").value = "passwordChange.php";
}
/**
 * ============================================================================
 * Change display theme to a new one. Themes must be defined is projeqtor.css.
 * The change is also stored in Session.
 * 
 * @param newTheme
 *          the new theme
 * @return void
 */
function saveResolutionToSession() {
  //var height = screen.height;
  //var width = screen.width;
  var height=document.documentElement.getBoundingClientRect().height;
  var width=document.documentElement.getBoundingClientRect().width;
  //#2887
  saveDataToSession("screenWidth", width);
  saveDataToSession("screenHeight", height);
}

/**
 * ============================================================================
 * Check if the recived key is able to change content of field or not
 * 
 * @param keyCode
 *          the code of the key
 * @return boolean : true if able to change field, else false
 */
/*
 * function isUpdatableKey(keyCode) { if (keyCode==9 // tab || (keyCode>=16 &&
 * keyCode<=20) // shift, ctrl, alt, pause, caps lock || (keyCode>=33 &&
 * keyCode<=40) // Home, end, page up, page down, arrows // (left, right, up,
 * down) || (keyCode==67) // ctrl+C || keyCode==91 // Windows || (keyCode>=112 &&
 * keyCode<=123) // Function keys || keyCode==144 // numlock || keyCode==145 //
 * stop || keyCode>=166 // Media keys ) { return false; } return true; // others }
 */

/**
 * ============================================================================
 * Clean the content of a Div. To be sure all widgets are cleaned before setting
 * new data in the Div. If fadeLoading is true, the Div fades away before been
 * cleaned. (fadeLoadsing is a global var definied in main.php)
 * 
 * @param destination
 *          the name of the Div to clean
 * @return void
 */
function cleanContent(destination) {
  var contentNode = dojo.byId(destination);
  var contentWidget = dijit.byId(destination);
  if (!(contentNode && contentWidget)) {
    return;
  }
  if (contentWidget) {
    contentWidget.set('content', null);
  }
  return;

}

/**
 * ============================================================================
 * Load the content of a Div with a new page. If fadeLoading is true, the Div
 * fades away before, and fades back in after. (fadeLoadsing is a global var
 * definied in main.php)
 * 
 * @param page
 *          the url of the page to fetch
 * @param destination
 *          the name of the Div to load into
 * @param formName
 *          the name of the form containing data to send to the page
 * @param isResultMessage
 *          boolean to specify that the destination must show the result of some
 *          treatment, calling finalizeMessageDisplay
 * @return void
 */
var formDivPosition = null; // to replace scrolling of detail after save.
var editorArray = new Array();
//var loadContentRetryArray=new Array();
var loadContentStack=new Array();
var loadContentCallSequential=false; // Should be ok to false, if errors, place to true
function truncUrlFromParameter(page,param) {
  if (page.indexOf("?"+param+"=") > 0) {
    page=page.substring(0,page.indexOf("?"+param+"="));
  } else if (page.indexOf("&"+param+"=") > 0) {
    page=page.substring(0,page.indexOf("&"+param+"="));
  }
  return page;
}
function getLoadContentStackKey(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading) {
  page=truncUrlFromParameter(page,'destinationWidth');
  page=truncUrlFromParameter(page,'directAccessIndex');
  page=truncUrlFromParameter(page,'isIE');
  page=truncUrlFromParameter(page,'xhrPostDestination');
  page=truncUrlFromParameter(page,'xhrPostTimestamp');
  var callKey=page
         +"|"+destination
         +"|"+((formName==undefined || formName==null || formName==false)?'':formName)
         +"|"+((isResultMessage==undefined || isResultMessage==null || isResultMessage==false)?'false':isResultMessage)
         +"|"+((validationType==undefined || validationType==null || validationType==false)?'':validationType);
  return callKey;
}
function storeLoadContentStack(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading) {
  var arrayStack=new Array();
  var callKey=getLoadContentStackKey(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
  arrayStack['page']=page;
  arrayStack['destination']=destination;
  arrayStack['formName']=formName;
  arrayStack['isResultMessage']=isResultMessage;
  arrayStack['validationType']=validationType;
  arrayStack['directAccess']=directAccess;
  arrayStack['silent']=silent;
  arrayStack['callBackFunction']=callBackFunction;
  arrayStack['noFading']=noFading;
  loadContentStack[callKey]=arrayStack;
}
function cleanLoadContentStack(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading) {
  var callKey=getLoadContentStackKey(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
  if (loadContentStack[callKey]!==undefined) {
    //loadContentStack.splice(callKey,1);
    delete loadContentStack[callKey];
  }
  if (loadContentCallSequential==true) {
    // Call next
    for (var arrKey in loadContentStack) {
      firstItemKey=arrKey;
      break;
    }
    var firstItem=loadContentStack[firstItemKey];
    if (firstItem===undefined) return;
    delete loadContentStack[firstItemKey];
    loadContent(firstItem['page'], firstItem['destination'], firstItem['formName'], firstItem['isResultMessage'], firstItem['validationType'], firstItem['directAccess'], firstItem['silent'], firstItem['callBackFunction'], firstItem['noFading']);
  }
}
function warnLoadContentError(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading) {
  console.warn("Error while calling xhrPost for loadContent()");
  console.warn("  => page='"+page+"'");
  console.warn("  => destination='"+destination+"'");
  console.warn("  => formName'"+formName+"'");
  console.warn("  => isResultMessage='"+isResultMessage+"'");
  console.warn("  => validationType='"+validationType+"'");
  console.warn("  => directAccess='"+directAccess+"'");
  console.warn("  => silent='"+silent+"'");
  console.warn("  => callBackFunction='"+"?"+"'");
  console.warn("  => noFading='"+noFading+"'");
}
function loadContent(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading) {
  var debugStart = (new Date()).getTime();
  // Test validity of destination : must be a node and a widget
  var contentNode = dojo.byId(destination);
  var contentWidget = dijit.byId(destination);
  var fadingMode = top.fadeLoading;
  var callKey=getLoadContentStackKey(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
  //if (loadContentRetryArray[callKey]===undefined) {
  //  loadContentRetryArray[callKey]=1;
  //} else {
  //  loadContentRetryArray[callKey]+=1;
  //}
  if (loadContentStack[callKey]===undefined) {
    storeLoadContentStack(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
    // If only call sequential, wait don't process : will be triggered when current has ended
    if (loadContentCallSequential==true && Object.keys(loadContentStack).length>1) {
      return; 
    }
  } else {
    // already calling same request for same target with same parameters.
    // avoid double call
    return;
  }
  
  if (dojo.byId('formDiv')) {
    formDivPosition = dojo.byId('formDiv').scrollTop;
  }
  if (page.substr(0, 16) == 'objectDetail.php') {
    // if item = current => refresh without fading
    if (dojo.byId('objectClassName') && dojo.byId('objectId') && dojo.byId('objectClass') && dojo.byId('id')) {
      if (dojo.byId('objectClass').value == dojo.byId('objectClassName').value
          && dojo.byId('objectId').value == dojo.byId('id').value) {
        fadingMode = false;
      }
    }
  }
  if (noFading) fadingMode = false;
  if (page.substr(0, 16) == 'objectStream.php') {
    fadingMode = false;
    silent=true;
  }
  
  if (!(contentNode && contentWidget)) {
    consoleTraceLog(i18n("errorLoadContent", new Array(page, destination,
        formName, isResultMessage, destination)));
    hideWait();
    cleanLoadContentStack(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
    return;
  }
  if (contentNode && page.indexOf("destinationWidth=")<0) {
    destinationWidth = dojo.style(contentNode, "width");
    destinationHeight = dojo.style(contentNode, "height");
    if (destination == 'detailFormDiv' && !editorInFullScreen()) {
      widthNode = dojo.byId('detailDiv');
      if (widthNode) {
        destinationWidth = dojo.style(widthNode, "width");
        destinationHeight = dojo.style(widthNode, "height");
      }
    }
    if (page.indexOf("?") > 0) {
      page += "&destinationWidth=" + destinationWidth + "&destinationHeight="
          + destinationHeight;
    } else {
      page += "?destinationWidth=" + destinationWidth + "&destinationHeight="
          + destinationHeight;
    }
  }
  if (directAccessIndex && page.indexOf("directAccessIndex=")<0) {
    if (page.indexOf("?") > 0) {
      page += "&directAccessIndex=" + directAccessIndex;
    } else {
      page += "?directAccessIndex=" + directAccessIndex;
    }
  }
  if (page.indexOf("isIE=")<0) {
    page += ((page.indexOf("?") > 0) ? "&" : "?") + "isIE=" + ((dojo.isIE) ? dojo.isIE : '');
  }
  if (!silent) showWait();
  // NB : IE Issue (<IE8) must not fade load
  // send Ajax request
    // add to url main parameters of call to loadContent 
    page += ((page.indexOf("?") > 0) ? "&" : "?") + "xhrPostDestination="+((destination)?destination:'')
                                                  + "&xhrPostIsResultMessage="+((isResultMessage)?'true':'false')
                                                  + "&xhrPostValidationType="+((validationType)?validationType:'');
    // add a Timestamp to url
    page += '&xhrPostTimestamp='+Date.now();
    dojo.xhrPost({
        url : page,
        form : dojo.byId(formName),
        handleAs : "text",
        load : function(data, args) {       
          var sourceUrl=args['url'];
          if (sourceUrl && sourceUrl!='undefined' && sourceUrl.indexOf('xhrPostDestination=')>0) {
            var xhrPostArgsString=sourceUrl.substr(sourceUrl.indexOf('xhrPostDestination='));
            var xhrPostParams=xhrPostArgsString.split('&');
            for (var i=0; i<xhrPostParams.length;i++) {
              var str=xhrPostParams[i];
              var callParam=str.split('=');
              if (callParam[0]=='xhrPostDestination' ) {
                destination=(callParam[1] && callParam[1]!='undefined')?callParam[1]:'';
              } else if (callParam[0]=='xhrPostIsResultMessage') {
                isResultMessage=(callParam[1] && callParam[1]!='undefined' && callParam[1]=='true')?true:false;
              } else if (callParam[0]=='xhrPostValidationType') {
                validationType=(callParam[1] && callParam[1]!='undefined')?callParam[1]:'';
              }
            }
          }
          // retreive parameters of loadContent from url
          var debugTemp = (new Date()).getTime();
          var contentNode = dojo.byId(destination);
          var contentWidget = dijit.byId(destination);
          if (fadingMode) {
            dojo.fadeIn({
              node : contentNode,
              duration : 500,
              onEnd : function() {
              }
            }).play();
          }
          // update the destination when ajax request is received
          if (!contentNode || !contentWidget) {
            //if (loadContentRetryArray[callKey]!==undefined) {
            //  loadContentRetryArray.splice(callKey, 1);
            //}
            warnLoadContentError(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading)
            console.warn("return from xhrPost for a loadContent : '"+destination+"' is not a node or not a widget");
            console.warn(contentNode);
            console.warn(contentWidget);
            cleanLoadContentStack(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
            hideWait();
            return;
          }
          if (dijit.byId('planResultDiv')) {
            if (dojo.byId("lastPlanStatus")
                && dojo.byId("lastPlanStatus").value == "INCOMPLETE") {
              // Do not clean result content
            } else {
              // dijit.byId('planResultDiv').set('content',"");
            }
          }
          // Must destroy existing instances of CKEDITOR before refreshing the page
          // page.
          if (page.substr(0, 16) == 'objectDetail.php'
              && (destination == 'detailDiv' || destination == 'detailFormDiv' || destination == "formDiv") && !editorInFullScreen()) {
            editorArray = new Array();
            for (name in CKEDITOR.instances) {
              CKEDITOR.instances[name].removeAllListeners();
              CKEDITOR.instances[name].destroy(false);
            }
          }
          hideBigImage(); // Will avoid resident pop-up always displayed
          cleanContent(destination);
          if(!editorInFullScreen()) contentWidget.set('content', data);
          checkDestination(destination);
          // Create instances of CKEDITOR
          if (page.substr(0, 16) == 'objectDetail.php'
              && (destination == 'detailDiv' || destination == 'detailFormDiv' || destination == "formDiv")&& !editorInFullScreen()) {
            ckEditorReplaceAll();
          }
          if (page.substr(0, 16) == 'objectDetail.php'
              && destination == 'detailDiv') {
            if (dojo.byId('attachmentFileDirectDiv')
                && dijit.byId('attachmentFileDirect')) {
              dijit.byId('attachmentFileDirect').addDropTarget(
                  dojo.byId('attachmentFileDirectDiv'));
            }
          }
          if (dojo.byId('objectClass') && destination.indexOf(dojo.byId('objectClass').value) == 0) { // If refresh a section
            var section = destination
                .substr(dojo.byId('objectClass').value.length + 1);
            if (dojo.byId(section + "SectionCount")
                && dojo.byId(section + "Badge")) {
              dojo.byId(section + "Badge").innerHTML = dojo.byId(section
                  + "SectionCount").value;
            }
          }
          if (destination == "detailDiv" || destination == "centerDiv") {
            finaliseButtonDisplay();
          }
          if (destination == "detailDiv" && dojo.byId('objectClass')
              && dojo.byId('objectClass').value && dojo.byId('objectId')
              && dojo.byId('objectId').value) {
            stockHistory(dojo.byId('objectClass').value,
                dojo.byId('objectId').value);
          } 
          if (dojo.byId('formDiv') && formDivPosition >= 0) {
            dojo.byId('formDiv').scrollTop = formDivPosition;
          }
          if (destination == "centerDiv" && switchedMode && !directAccess) {
            showList();
          }
          if (destination == "dialogLinkList") {
            selectLinkItem();
          }
          if (destination == "directFilterList") {
            if (!validationType && validationType != 'returnFromFilter') {
              if (top.dojo.byId('noFilterSelected')
                  && top.dojo.byId('noFilterSelected').value == 'true') {
                dijit.byId("listFilterFilter").set("iconClass", "iconFilter");
              } else {
                dijit.byId("listFilterFilter").set("iconClass",
                    "iconActiveFilter");
              }
              if (dojo.byId('objectClassManual') && dojo.byId('objectClassManual').value=='Planning') {
                refreshJsonPlanning();
              } else if (dojo.byId('objectClassList')) {
                refreshJsonList(dojo.byId('objectClassList').value);
              } else {
                refreshJsonList(dojo.byId('objectClass').value);
              }
            }
          }
          if (destination == "expenseDetailDiv") {
            expenseDetailRecalculate();
          }
          if (directAccess) {
            if (dojo.byId('objectClass') && dojo.byId('objectId') && dijit.byId('listForm')) {
              if (dojo.byId('objectClassList') && dojo.byId('objectClassList').value=='GlobalView') {
                var expl= directAccess.split('|');
                dojo.byId('objectClass').value = expl[0];
                dojo.byId('objectId').value = expl[1];
              } else {
                dojo.byId('objectId').value = directAccess;
                directAccess=parseInt(directAccess);
              }
              showWait();
              loadContent("objectDetail.php", "detailDiv", 'listForm');
              if (dijit.byId('detailRightDiv')) loadContent("objectStream.php", "detailRightDiv",'listForm');
              showWait();
              hideList();
              setTimeout('selectRowById("objectGrid", '+directAccess+');', 100);
            }
          }
          if (isResultMessage) {    
            var contentNode = dojo.byId(destination);
            dojo.fadeIn({
              node : contentNode,
              duration : 100,
              onEnd : function() {
                if(!editorInFullScreen()) {
                  finalizeMessageDisplay(destination, validationType);
                } else {
                  var elemDiv = document.createElement('div');
                  elemDiv.id='testFade';
                  var leftMsg=(window.innerWidth - 400)/2;
                  elemDiv.style.cssText = 'position:absolute;text-align:center;width:400px;height:auto;z-index:10000;top:50px;left:'+leftMsg+'px';
                  //elemDiv.className='messageOK';
                  elemDiv.innerHTML=data;
                  document.body.appendChild(elemDiv);
                  resultDivFadingOut = dojo.fadeOut({
                    node : elemDiv,
                    duration : 3000,
                    onEnd : function() {
                      elemDiv.remove();
                    }
                  }).play();
                  hideWait();
                  formInitialize();
                  if (whichFullScreen>=0 && editorArray[whichFullScreen]) editorArray[whichFullScreen].focus();
                }
              }
            }).play();
          } else if (destination == "loginResultDiv") {
            checkLogin();
          } else if (destination == "passwordResultDiv") {
            checkLogin();
          } else if (page.indexOf("planningMain.php") >= 0
              || page.indexOf("planningList.php") >= 0
              || (page.indexOf("jsonPlanning.php") >= 0 && dijit.byId("startDatePlanView"))
              || page.indexOf("resourcePlanningMain.php") >= 0
              || page.indexOf("resourcePlanningList.php") >= 0
              || (page.indexOf("jsonResourcePlanning.php") >= 0 && dijit.byId("startDatePlanView"))
              || page.indexOf("globalPlanningMain.php") >= 0
              || page.indexOf("globalPlanningList.php") >= 0
              || (page.indexOf("jsonGlobalPlanning.php") >= 0 && dijit.byId("startDatePlanView"))
              || page.indexOf("portfolioPlanningMain.php") >= 0
              || page.indexOf("portfolioPlanningList.php") >= 0
              || (page.indexOf("jsonPortfolioPlanning.php") >= 0 && dijit.byId("startDatePlanView"))
              //ADD qCazelles - GANTT
              || page.indexOf("versionsPlanningMain.php") >= 0 || page.indexOf("versionsPlanningList.php") >= 0
              || (page.indexOf("jsonVersionsPlanning.php") >= 0 && dijit.byId("startDatePlanView"))) {
        	  //END ADD qCazelles - GANTT
        	  drawGantt();
            selectPlanningRow();
            if (!silent)
              hideWait();
            var bt = dijit.byId('planButton');
            if (bt) {
              bt.set('iconClass', "iconPlanStopped");
            }
          } else if (destination == "resultDivMultiple") {
            finalizeMultipleSave();
          } else {
            if (!silent)
              hideWait();
          }
          // For debugging purpose : will display call page with execution time
          var debugEnd = (new Date()).getTime();
          var debugDuration = debugEnd - debugStart;
          var msg = "=> " + debugDuration + "ms";
          msg += " | page='"
              + ((page.indexOf('?')) ? page.substring(0, page.indexOf('?'))
                  : page) + "'";
          msg += " | destination='" + destination + "'";
          if (formName)
            msg += " | formName=" + formName + "'";
          if (isResultMessage)
            msg += " | isResultMessage='" + isResultMessage + "'";
          if (validationType)
            msg += " | validationType='" + validationType + "'";
          if (directAccess)
            msg += " | directAccess='" + directAccess + "'";
          if (callBackFunction != null)
            setTimeout(callBackFunction, 100);
          var debugDurationServer = debugTemp - debugStart;
          var debugDurationClient = debugEnd - debugTemp;
          msg += " (server:" + debugDurationServer + "ms, client:"
              + debugDurationClient + "ms)";
          consoleTraceLog(msg);
          cleanLoadContentStack(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
          //if (loadContentRetryArray[callKey]!==undefined) {
          //  loadContentRetryArray.splice(callKey, 1);
          //}
        },
        error : function(error, args) {
          //var retries=-1;          
          //if (loadContentRetryArray[callKey]!==undefined) {
          //  retries=loadContentRetryArray[callKey];
          //}
          cleanLoadContentStack(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
          warnLoadContentError(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction, noFading);
          console.warn(error);
          if (!silent) hideWait();
          finaliseButtonDisplay();
          //formChanged();
          //if (retries>0 && retries <3) { // On error, will retry ou to 3 times before raising an error
          //  console.warn('['+retries+'] '+i18n("errorXhrPost", new Array(page, destination,formName, isResultMessage, error)));
          //  loadContent(page, destination, formName, isResultMessage, validationType, directAccess, silent, callBackFunction);
          //} else {
            enableWidget('saveButton');
            enableWidget('undoButton');
            //console.warn(i18n("errorXhrPost", new Array(page, destination,formName, isResultMessage, error))); // No use with warnLoadContentError
            hideWait();
            //showError(i18n('errorXhrPostMessage'));
          //}
        }
      });
  if (fadingMode) {
    dojo.fadeOut({
      node : contentNode,
      duration : 200,
      onEnd : function() {
      }
    }).play();
  }
}

/**
 * ============================================================================
 * Load some non dojo content div (like loadContent, but for simple div) Content
 * will not be parsed by dojo
 * 
 * @param page
 *          php page to load
 * @param destinationDiv
 *          name of distination div
 * @param formName
 *          nale of form to post (optional)
 */

function loadDiv(page, destinationDiv, formName, callback) {
  var contentNode = dojo.byId(destinationDiv);
  if (page.indexOf('getObjectCreationInfo')>=0 && dijit.byId('detailDiv') && page.indexOf('destinationWidth')<0) {
    var destinationWidth = dojo.style(dojo.byId('detailDiv'), "width");
    // var destinationHeight = dojo.style(dojo.byId('detailDiv'), "height");
    page+=((page.indexOf('?')>=0)?'&':'?')+'destinationWidth='+destinationWidth;
  }
  dojo.xhrPost({
    url : page,
    form : dojo.byId(formName),
    handleAs : "text",
    load : function(data, args) {
      contentNode.innerHTML = data;
      if (callback)
        setTimeout(callback, 10);
    }
  });
}
/**
 * ============================================================================
 * Check if destnation is correct If not in main page and detect we have login
 * page => wrong destination
 */
function checkDestination(destination) {
  if (dojo.byId("isLoginPage") && destination != "loginResultDiv") {
    // if (dojo.isFF) {
    consoleTraceLog("errorConnection: isLoginPage but destination is not loginResultDiv");
    quitConfirmed = true;
    noDisconnect = true;
    window.location = "main.php?lostConnection=true";
    // } else {
    // hideWait();
    // showAlert(i18n("errorConnection"));
    // }
  }
  if (!dijit.byId('objectGrid') && dojo.byId('multiUpdateButtonDiv')) {
    dojo.byId('multiUpdateButtonDiv').style.display = 'none';
  }
  if (dojo.byId('indentButtonDiv')) {
    if (dijit.byId('objectGrid')) {
      dojo.byId('indentButtonDiv').style.display = 'none';
    } else if (dojo.byId('objectClassManual') && (dojo.byId('objectClassManual').value != 'Planning' && dojo.byId('objectClassManual').value != 'GlobalPlanning')) {
      dojo.byId('indentButtonDiv').style.display = 'none';
    }
  }
  dojo.query('.titlePaneFromDetail').forEach(function(node, index, nodelist) { // Apply specific style for title panes
    dijit.byId(node.id).titlePaneHandler();
  });
}
/**
 * ============================================================================
 * Chek the return code from login check, if valid, refresh page to continue
 * 
 * @return void
 */
function checkLogin() {
  resultNode = dojo.byId('validated');
  resultWidget = dojo.byId('validated');
  if (resultNode && resultWidget) {
    saveResolutionToSession();
    // showWait();
    if (changePassword) {
      quitConfirmed = true;
      noDisconnect = true;
      var tempo=300;
      if (dojo.byId('notificationOnLogin')) {
        tempo=1500;
      } 
      setTimeout('window.location = "main.php?changePassword=true";',tempo);
    } else {
      quitConfirmed = true;
      noDisconnect = true;
      url = "main.php";
      if (dojo.byId('objectClass') && dojo.byId("objectId")) {
        url += "?directAccess=true&objectClass="
            + dojo.byId('objectClass').value + "&objectId="
            + dojo.byId("objectId").value;
      }
      var tempo=400;
      if (dojo.byId('notificationOnLogin')) {
        tempo=1500;
      } 
      setTimeout('window.location ="'+url+'";',tempo);
    }
  } else {
    hideWait();
  }
}

/**
 * ============================================================================
 * Submit a form, after validating the data
 * 
 * @param page
 *          the url of the page to fetch
 * @param destination
 *          the name of the Div to load into
 * @param formName
 *          the name of the form containing data to send to the page
 * @return void
 */
function submitForm(page, destination, formName) {
  var formVar = dijit.byId(formName);
  if (!formVar) {
    showError(i18n("errorSubmitForm", new Array(page, destination, formName)));
    return;
  }
  // validate form Data
  if (formVar.validate()) {
    formLock();
    // form is valid, continue and submit it
    var isResultDiv = true;
    if (formName == 'passwordForm') {
      isResultDiv = false;
    }
    loadContent(page, destination, formName, isResultDiv);
  } else {
    showAlert(i18n("alertInvalidForm"));
  }
}

// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
/**
 * ============================================================================
 * Refresh the Notification tree on the 'Unread Notifications' Accordion
 * @param bCheckFormChangeInProgress : True if FormChangeInProgress is to check
 * @return void
 */
function refreshNotificationTree(bCheckFormChangeInProgress) {
    if (paramNotificationSystemActiv == "NO") {
        return;
    }
    if (bCheckFormChangeInProgress && waitingForReply) {
        return;
    }
    dijit.byId("notificationTree").model.store.clearOnClose = true;
    dijit.byId("notificationTree").model.store.close();
    // Completely delete every node from the dijit.Tree
    dijit.byId("notificationTree")._itemNodesMap = {};
    dijit.byId("notificationTree").rootNode.state = "UNCHECKED";
    dijit.byId("notificationTree").model.root.children = null;
    // Destroy the widget
    dijit.byId("notificationTree").rootNode.destroyRecursive();
    // Recreate the model, (with the model again)
    dijit.byId("notificationTree").model.constructor(dijit.byId("notificationTree").model);
    // Rebuild the tree
    dijit.byId("notificationTree").postMixInProperties();
    dijit.byId("notificationTree")._load();
}
// END - ADD BY TABARY - NOTIFICATION SYSTEM

/**
 * ============================================================================
 * Finalize some operations after receiving validation message of treatment
 * 
 * @param destination
 *          the name of the Div receiving the validation message
 * @return void
 */
var resultDivFadingOut = null;
var planningResultDivFadingOut = null;
var forceRefreshCreationInfo = false;
function finalizeMessageDisplay(destination, validationType) {
  var contentNode = dojo.byId(destination);
  var contentWidget = dijit.byId(destination);
  var lastOperationStatus = dojo.byId('lastOperationStatus');
  var lastOperation = dojo.byId('lastOperation');
  var needProjectListRefresh = false;
  // scpecific Plan return
  if (destination == "planResultDiv" && (! validationType || validationType=='dependency')) {
    if (dojo.byId('lastPlanStatus')) {
      lastOperationStatus = dojo.byId('lastPlanStatus');
      lastOperation = "plan";
      validationType = null;
    }
  }
  if (destination == 'resultDiv' || destination == 'planResultDiv') {
    contentNode.style.display = "block";
  }
  var noHideWait = false;
  if (!(contentWidget && contentNode && lastOperationStatus && lastOperation)) {
    returnMessage = "";
    if (contentWidget) {
      returnMessage = contentWidget.get('content');
    }
    consoleTraceLog("***** ERROR ***** on finalizeMessageDisplay(" + destination + ", " + validationType + ")");
    if (!contentNode) {
      consoleTraceLog("contentNode unknown");
    } else {
      consoleTraceLog("contentNode='" + contentNode.innerHTML+"'");
    }
    if (!contentWidget) {
      consoleTraceLog("contentWidget unknown");
    } else {
      consoleTraceLog("contentWidget='" + contentWidget.get("content")+"'");
    }
    if (!lastOperationStatus) {
      consoleTraceLog("lastOperationStatus unknown");
    } else {
      consoleTraceLog("lastOperationStatus='" + lastOperationStatus.value+"'");
    }
    if (!lastOperation) {
      consoleTraceLog("lastOperation unknown");
    } else {
      consoleTraceLog("lastOperation='" + lastOperation.value+"'");
    }
    hideWait();
    //showError(i18n("errorFinalizeMessage", new Array(destination, returnMessage)));
    formInitialize();
    return;
  }
  if (!contentWidget) {
    return;
  }
  // fetch last message type
  var message = contentWidget.get('content');
  posdeb = message.indexOf('class="message') + 7;
  posfin = message.indexOf('>', posdeb) - 1;
  typeMsg = message.substr(posdeb, posfin - posdeb);
  // if operation is OK
  if (lastOperationStatus.value == "OK"
      || lastOperationStatus.value == "INCOMPLETE") {
    posdeb = posfin + 2;
    posfin = message.indexOf('<', posdeb);
    msg = message.substr(posdeb, posfin - posdeb);
    // add the message in the message Div (left part) and prepares form to new
    // changes
    addMessage(msg);
    // alert('validationType='+validationType);
    if (validationType) {
      if (validationType == 'note') {
        if(!dijit.byId('dialogKanbanGetObjectStream')) loadContent("objectDetail.php?refreshNotes=true", dojo.byId('objectClass').value+ '_Note', 'listForm');
        if (dijit.byId('detailRightDiv')) loadContent("objectStream.php", "detailRightDiv", "listForm");
        if (dijit.byId('dialogKanbanGetObjectStream')) loadContent("../tool/dynamicDialogKanbanGetObjectStream.php","dialogKanbanGetObjectStream","noteFormStreamKanban");   	
        if (dojo.byId('buttonDivCreationInfo')) {
          var url = '../tool/getObjectCreationInfo.php?objectClass='+ dojo.byId('objectClass').value +'&objectId='+dojo.byId('objectId').value;
          loadDiv(url, 'buttonDivCreationInfo', null);
        }
      } else if (validationType == 'attachment') {
        if (dojo.byId('buttonDivCreationInfo')) {
          var url = '../tool/getObjectCreationInfo.php?objectClass='+ dojo.byId('objectClass').value 
          + '&objectId='+dojo.byId('objectId').value;
          loadDiv(url, 'buttonDivCreationInfo', null);
        }
        if (dojo.byId('parameter') && dojo.byId('parameter').value == 'true') {
          formChangeInProgress = false;
          waitingForReply = false;
          loadMenuBarItem('UserParameter', 'UserParameter', 'bar');
          
        } else if (dojo.byId('objectClass')
            && (dojo.byId('objectClass').value == 'Resource'
                || dojo.byId('objectClass').value == 'ResourceTeam'  
                || dojo.byId('objectClass').value == 'User' 
                || dojo.byId('objectClass').value == 'Contact')) {
          loadContent("objectDetail.php?refresh=true", "detailFormDiv",
              'listForm');
          refreshGrid();
        } else {
          loadContent("objectDetail.php?refreshAttachments=true", dojo.byId('objectClass').value+ '_Attachment', 'listForm');
        }
        dojo.style(dojo.byId('downloadProgress'), {
          display : 'none'
        });
      } else if (validationType == 'billLine') {
        loadContent("objectDetail.php?refreshBillLines=true", dojo
            .byId('objectClass').value
            + '_BillLine', 'listForm');
        loadContent("objectDetail.php?refresh=true", "detailFormDiv",
            'listForm');
        refreshGrid();
        // } else if (validationType=='documentVersion') {
        // loadContent("objectDetail.php?refresh=true", "detailFormDiv",
        // 'listForm');
      } else if (validationType == 'checklistDefinitionLine') {
        loadContent("objectDetail.php?refreshChecklistDefinitionLines=true",
            dojo.byId('objectClass').value + '_checklistDefinitionLine',
            'listForm');
      } else if (validationType == 'jobDefinition') {
        loadContent("objectDetail.php?refreshJobDefinition=true",
            dojo.byId('objectClass').value + '_jobDefinition',
            'listForm');
      } else if (validationType == 'testCaseRun') {
        loadContent("objectDetail.php?refresh=true", "detailFormDiv",
            'listForm');
        if (dojo.byId(dojo.byId('objectClass').value + '_history')) {
          loadContent("objectDetail.php?refreshHistory=true", dojo
              .byId('objectClass').value
              + '_history', 'listForm');
        }
      } else if (validationType == 'copyTo' || validationType == 'copyProject') {
        if (validationType == 'copyProject') {
          needProjectListRefresh = true;
          dojo.byId('objectClass').value = "Project";
        } else {
          if (dijit.byId('copyToClass') ) {
            dojo.byId('objectClass').value = copyableArray[dijit.byId('copyToClass').get('value')];
          }
        }
        var lastSaveId = dojo.byId('lastSaveId');
        var lastSaveClass = dojo.byId('objectClass');
        if (lastSaveClass && lastSaveId) {
          waitingForReply = false;
          gotoElement(lastSaveClass.value, lastSaveId.value, null, true);
          waitingForReply = true;
        }
      } else if (validationType == 'admin') {
        hideWait();
      } else if (validationType != 'link'  && validationType.substr(0,4)=='link' && (dojo.byId('objectClass').value == 'Requirement' || dojo.byId('objectClass').value == 'TestSession')) {
        loadContent("objectDetail.php?refresh=true", "detailFormDiv",'listForm');
        if (dojo.byId('buttonDivCreationInfo')) {
          var url = '../tool/getObjectCreationInfo.php?objectClass='+ dojo.byId('objectClass').value +'&objectId='+dojo.byId('objectId').value;
          loadDiv(url, 'buttonDivCreationInfo', null);  
        }
        refreshGrid();
      } else if ( validationType=='linkObject') {
        loadContent("objectDetail.php?refresh=true", "detailFormDiv",'listForm');
      }else if( (validationType =='link' || validationType.substr(0,4)=='link') && validationType !='linkObject'){
        var refTypeName=validationType.substr(4);     
        if (dojo.byId('buttonDivCreationInfo')) {
          var url = '../tool/getObjectCreationInfo.php?objectClass='+ dojo.byId('objectClass').value +'&objectId='+dojo.byId('objectId').value;
          loadDiv(url, 'buttonDivCreationInfo', null);  
        }
        if(refTypeName && dijit.byId(dojo.byId('objectClass').value+'_Link_'+refTypeName)){
          var url = "objectDetail.php?refreshLinks="+refTypeName;
          loadContent("objectDetail.php?refreshLinks="+refTypeName,dojo.byId('objectClass').value+ '_Link_'+refTypeName,'listForm');  
          //gautier #2947
          loadContent("objectDetail.php?refresh=true", "detailFormDiv",'listForm');
        }else{
          loadContent("objectDetail.php?refreshLinks=true",dojo.byId('objectClass').value+ '_Link','listForm');
        }
      } else if (validationType == 'report') {
        hideWait();
      } else if (validationType == 'checklist' || validationType == 'joblist') {
        hideWait();
      } else if (validationType == 'dispatchWork') {
        hideWait();
      } else if (lastOperation != 'plan') {
        if (dijit.byId('detailFormDiv')) { // only refresh is detail is show
                                            // (possible when DndLing on
                                            // planning
          loadContent("objectDetail.php?refresh=true", "detailFormDiv",
              'listForm');
        }
        if (validationType == 'assignment'
            || validationType == 'documentVersion') {
          refreshGrid();
        } else if ( (validationType == 'dependency' || validationType == 'affectation')
            && (dojo.byId(destination) == "planResultDiv" || dojo.byId("GanttChartDIV"))) {
          noHideWait = true;
          refreshGrid(); // Will call refreshJsonPlanning() if needed and
                          // plan() if required
        }
        // hideWait();
      }
    } else { // ! validationType
      formInitialize();
      // refresh the grid to reflect changes
      var lastSaveId = dojo.byId('lastSaveId');
      var objectId = dojo.byId('objectId');
      if (objectId && lastSaveId && destination != "planResultDiv") {
        objectId.value = lastSaveId.value;
      }
      // Refresh the Grid list (if visible)
      var grid = dijit.byId("objectGrid");
      if (grid) {
        var sortIndex = grid.getSortIndex();
        var sortAsc = grid.getSortAsc();
        var scrollTop = grid.scrollTop;
        store = grid.store;
        store.close();
        store.fetch({
          onComplete : function() {
            grid._refresh();
            setTimeout('dijit.byId("objectGrid").setSortIndex(' + sortIndex
                + ',' + sortAsc + ');', 10);
            setTimeout('dijit.byId("objectGrid").scrollTo(' + scrollTop + ');',
                20);
            setTimeout('selectRowById("objectGrid", '
                + parseInt(objectId.value) + ');', 30);
          }
        });
      }
      // Refresh the planning Gantt (if visible)
      if (dojo.byId(destination) == "planResultDiv"
          || dojo.byId("GanttChartDIV")) {
        noHideWait = true;
        if (destination == "planResultDiv") {
          if (dojo.byId("saveDependencySuccess")
              && dojo.byId("saveDependencySuccess").value == 'true') {
            refreshGrid(); // It is a dependency add throught D&D => must
                            // replan is needed
          } else if (dojo.byId('lastOperation')
              && dojo.byId('lastOperation').value == 'move') {
            refreshGrid();
          } else {
            refreshJsonPlanning(); // Must not call refreshGrid() to avoid
                                    // never ending loop
          }
        } else {
          refreshGrid();
        }
        // loadContent("planningList.php", "listDiv", 'listForm');
      } else if (dojo.byId('id') && lastOperation && (lastOperation.value == "insert" || forceRefreshCreationInfo)) {
     // last operations depending on the executed operatoin (insert, delete, ...)
        if (lastSaveId) dojo.byId('id').value=lastSaveId.value;
        if (dojo.byId('objectClass')
            && dojo.byId('objectClass').value == "Project") {
          needProjectListRefresh = true;
        }
        if (dojo.byId("buttonDivObjectId")
            && (dojo.byId("buttonDivObjectId").innerHTML == "" || forceRefreshCreationInfo)
            && lastSaveId && lastSaveId.value) {
          dojo.byId("buttonDivObjectId").innerHTML = "&nbsp;#"
              + lastSaveId.value;
          //gautier
          if(dojo.byId("buttonDivObjectName") && dijit.byId('name')){
              if(dijit.byId('name').get("value")){
                dojo.byId("buttonDivObjectName").innerHTML="&nbsp;-&nbsp;"+dijit.byId('name').get("value");
            }
          }
          if (dojo.byId('buttonDivCreationInfo')) {
            var url = '../tool/getObjectCreationInfo.php' + '?objectClass='
                + dojo.byId('objectClass').value + '&objectId='
                + lastSaveId.value;
            var callback=null;
            if (dojo.byId('objectClass').value=='ProductVersion' || dojo.byId('objectClass').value=='ComponentVersion' ) {
              callback=function() {
                if (! dojo.byId('isCurrentUserSubscription')) return;
                var subs=dojo.byId('isCurrentUserSubscription').value;
                if(subs == '1'){
                  dijit.byId('subscribeButton').set('iconClass','dijitButtonIcon dijitButtonIconSubscribeValid');
                  enableWidget('subscribeButtonUnsubscribe');
                  disableWidget('subscribeButtonSubscribe');
                }else{
                  dijit.byId('subscribeButton').set('iconClass','dijitButtonIcon dijitButtonIconSubscribe');
                  disableWidget('subscribeButtonUnsubscribe');
                  enableWidget('subscribeButtonSubscribe');
                }
              };
            }
            loadDiv(url, 'buttonDivCreationInfo',null, callback);
          }
        }
        forceRefreshCreationInfo = false;
        if (dojo.byId('attachmentFileDirectDiv')) {
          dojo.byId('attachmentFileDirectDiv').style.visibility = 'visible';
        }
        if (dojo.byId('objectClass') && dojo.byId('objectId')) {
          stockHistory(dojo.byId('objectClass').value,dojo.byId('objectId').value);
        }
      }
      if (lastOperation.value == "delete") {
        var zone = dijit.byId("formDiv");
        var zoneRight = dijit.byId("detailRightDiv");
        var msg = dojo.byId("noDataMessage");
        if (zone && msg) {
          zone.set('content', msg.value);
        }
        if (zoneRight && msg) {
          zoneRight.set('content', msg.value);
        }
        if (dojo.byId('objectClass')
            && dojo.byId('objectClass').value == "Project") {
          needProjectListRefresh = true;
        }
        if (dojo.byId("buttonDivObjectId")) {
          dojo.byId("buttonDivObjectId").innerHTML = "";
        }
        
        if (dojo.byId('buttonDivCreationInfo')) {
          dojo.byId("buttonDivCreationInfo").innerHTML = "";
        }
        if (dojo.byId('attachmentFileDirectDiv')) {
          dojo.byId('attachmentFileDirectDiv').style.visibility = 'hidden';
        }
        // unselectAllRows("objectGrid");
        finaliseButtonDisplay();
      }
      if ((grid || dojo.byId("GanttChartDIV")) && dojo.byId("detailFormDiv")
          && refreshUpdates == "YES" && lastOperation.value != "delete") {
        // loadContent("objectDetail.php?refresh=true", "formDiv",
        // 'listForm');
        if (lastOperation.value == "copy") {
          loadContent("objectDetail.php?", "detailDiv", 'listForm');
        } else {
          loadContent("objectDetail.php?refresh=true", "detailFormDiv", 'listForm');
          if(dojo.byId('detailRightDiv')){
            loadContent("objectStream.php", "detailRightDiv", "listForm");  
          } else if (validationType == 'noteKanban' ){
          	loadContent("../tool/dynamicDialogKanbanGetObjectStream.php","dialogKanbanGetObjectStream","noteFormStreamKanban");   	
          } 
          // Need also to refresh History
          if (dojo.byId(dojo.byId('objectClass').value + '_history') && dojo.byId(dojo.byId('objectClass').value + '_history').style.display!='none') {
            loadContent("objectDetail.php?refreshHistory=true", dojo
                .byId('objectClass').value
                + '_history', 'listForm');
          }
          if (dojo.byId(dojo.byId('objectClass').value + '_BillLine')) {
            loadContent("objectDetail.php?refreshBillLines=true", dojo
                .byId('objectClass').value
                + '_BillLine', 'listForm');
          }
          var refreshDetailElse = false;
          if (lastOperation.value == "insert") {
            refreshDetailElse = true;
          } else {
            if (dijit.byId('idle') && dojo.byId('attachmentIdle')) {
              if (dijit.byId('idle').get("value") != dojo
                  .byId('attachmentIdle').value) {
                refreshDetailElse = true;
              }
            }
            if (dijit.byId('idle') && dojo.byId('noteIdle')) {
              if (dijit.byId('idle').get("value") != dojo.byId('noteIdle').value) {
                refreshDetailElse = true;
              }
            }
            if (dijit.byId('idle') && dojo.byId('billLineIdle')) {
              if (dijit.byId('idle').get("value") != dojo.byId('billLineIdle').value) {
                refreshDetailElse = true;
              }
            }
          }
          if (refreshDetailElse && !validationType) {
            if (dojo.byId(dojo.byId('objectClass').value + '_Attachment')) {
              loadContent("objectDetail.php?refreshAttachments=true", dojo
                  .byId('objectClass').value
                  + '_Attachment', 'listForm');
            }
            if (dojo.byId(dojo.byId('objectClass').value + '_Note')) {
              loadContent("objectDetail.php?refreshNotes=true", dojo
                  .byId('objectClass').value
                  + '_Note', 'listForm');
            }
            if (dojo.byId(dojo.byId('objectClass').value + '_BillLine')) {
              loadContent("objectDetail.php?refreshBillLines=true", dojo
                  .byId('objectClass').value
                  + '_BillLine', 'listForm');
            }
            if (dojo.byId(dojo.byId('objectClass').value + '_checklistDefinitionLine')) {
              loadContent(
                  "objectDetail.php?refreshChecklistDefinitionLines=true", dojo
                      .byId('objectClass').value
                      + '_checklistDefinitionLine', 'listForm');
            }
            if (dojo.byId(dojo.byId('objectClass').value + '_jobDefinition')) {
              loadContent(
                  "objectDetail.php?refreshJobDefinition=true", dojo
                      .byId('objectClass').value
                      + '_jobDefinition', 'listForm');
            }
          }
        }
      } else {
        if (!noHideWait) {
          hideWait();
        }
      }
      // Manage checkList button
      if (dojo.byId('buttonCheckListVisible')
          && dojo.byId('buttonCheckListVisibleObject')) {
        var visible = dojo.byId('buttonCheckListVisible').value;
        var visibleObj = dojo.byId('buttonCheckListVisibleObject').value;
        // loadContent('objectButtons.php', 'buttonDivContainer','listForm');
        if (visible != 'never' && visible != visibleObj) {
          // loadContent('objectButtons.php', 'buttonDivContainer','listForm');
          if (visibleObj == 'visible') {
            dojo.byId("checkListButtonDiv").style.display = "inline";
          } else {
            dojo.byId("checkListButtonDiv").style.display = "none";
          }
          dojo.byId('buttonCheckListVisible').value = visibleObj;
        }
      }
      if (lastOperation.value == "insert" && dojo.byId("buttonHistoryVisible")
          && dojo.byId("buttonHistoryVisible").value == 'REQ') {
        dojo.byId("historyButtonDiv").style.display = "inline";
      }
      if (lastOperation.value == "delete" && dojo.byId("buttonHistoryVisible")) {
        dojo.byId("historyButtonDiv").style.display = "none";
      }
    }
    var classObj = null;
    if (dojo.byId('objectClass'))
      classObj = dojo.byId('objectClass');
    if (classObj && classObj.value == 'DocumentDirectory') {
      dijit.byId("documentDirectoryTree").model.store.clearOnClose = true;
      dijit.byId("documentDirectoryTree").model.store.close();
      // Completely delete every node from the dijit.Tree
      dijit.byId("documentDirectoryTree")._itemNodesMap = {};
      dijit.byId("documentDirectoryTree").rootNode.state = "UNCHECKED";
      dijit.byId("documentDirectoryTree").model.root.children = null;
      // Destroy the widget
      dijit.byId("documentDirectoryTree").rootNode.destroyRecursive();
      // Recreate the model, (with the model again)
      dijit.byId("documentDirectoryTree").model.constructor(dijit
          .byId("documentDirectoryTree").model);
      // Rebuild the tree
      dijit.byId("documentDirectoryTree").postMixInProperties();
      dijit.byId("documentDirectoryTree")._load();
    }
// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
    if (classObj && (classObj.value == 'Notification' || classObj.value == 'NotificationDefinition')) {
      refreshNotificationTree(false);
    }
// END - ADD BY TABARY - NOTIFICATION SYSTEM
    if (dojo.byId("forceRefreshMenu")
        && dojo.byId("forceRefreshMenu").value != "") {
      forceRefreshMenu = dojo.byId("forceRefreshMenu").value;
    }
    if (forceRefreshMenu) {
      // loadContent("../view/menuTree.php", "mapDiv",null,false);
      // loadContent("../view/menuBar.php", "toolBarDiv",null,false);
      showWait();
      noDisconnect = true;
      quitConfirmed = true;
      // window.location="../view/main.php?directAccessPage=parameter.php&menuActualStatus="
      // + menuActualStatus + "&p1name=type&p1value="+forceRefreshMenu;
      dojo.byId("directAccessPage").value = "parameter.php";
      dojo.byId("menuActualStatus").value = menuActualStatus;
      dojo.byId("p1name").value = "type";
      dojo.byId("p1value").value = forceRefreshMenu;
      forceRefreshMenu = "";
      dojo.byId("directAccessForm").submit();
    }
  } else if (lastOperationStatus.value == "INVALID"
      || lastOperationStatus.value == "CONFIRM") {
    if (formChangeInProgress) {
      formInitialize();
      formChanged();
    } else {
      formInitialize();
    }
  } else {
    if (dojo.byId('objectClass') && dojo.byId('objectId')) {
      var url = '../tool/getObjectCreationInfo.php?objectClass='+ dojo.byId('objectClass').value +'&objectId='+dojo.byId('objectId').value;
      loadDiv(url, 'buttonDivCreationInfo', null);
      var objClass=dojo.byId('objectClass').value;
      if (lastOperationStatus.value=='NO_CHANGE' && !validationType && dojo.byId(objClass+'PlanningElement_assignedCost') 
          && dojo.byId(objClass+'PlanningElement_assignedCost').style.textDecoration=="line-through") {
        // No change but assignment changed so that refresh is required
        loadContent("objectDetail.php?", "detailDiv", 'listForm');
        refreshGrid();
      }
    }
    if (validationType != 'note' && validationType != 'attachment') {
      formInitialize();
    }
    hideWait(); 
  }
  // If operation is correct (not an error) slowly fade the result message
  if (destination == 'planResultDiv') {
    if (planningResultDivFadingOut)
      planningResultDivFadingOut.stop();
  } else {
    if (resultDivFadingOut)
      resultDivFadingOut.stop();
  }
  if ((lastOperationStatus.value != "ERROR"
      && lastOperationStatus.value != "INVALID"
      && lastOperationStatus.value != "CONFIRM" && lastOperationStatus.value != "INCOMPLETE")) {
    if (destination == 'planResultDiv') {
      planningResultDivFadingOut = dojo.fadeOut({
        node : contentNode,
        duration : 3000,
        onEnd : function() {
          contentNode.style.display = "none";
          contentWidget.set("content",null);
        }
      }).play();
    } else {
      resultDivFadingOut = dojo.fadeOut({
        node : contentNode,
        duration : 3000,
        onEnd : function() {
          contentNode.style.display = "none";
          contentWidget.set("content",null);
        }
      }).play();
    }
  } else {
    if (lastOperationStatus.value == "ERROR") {
      showError(message);
      addCloseBoxToMessage(destination);
    } else {
      if (lastOperationStatus.value == "CONFIRM") {
        if (message.indexOf('id="confirmControl" value="delete"') > 0 || message.indexOf('id="confirmControl" type="hidden" value="delete"') > 0) {
          confirm = function() {
            dojo.byId("deleteButton").blur();
            loadContent("../tool/deleteObject.php?confirmed=true", "resultDiv",
                'objectForm', true);
          };
        } else {
          confirm = function() {
            dojo.byId("saveButton").blur();
            loadContent("../tool/saveObject.php?confirmed=true", "resultDiv",
                'objectForm', true);
          };
        }
        showConfirm(message, confirm);
        contentWidget = dijit.byId(destination);
        contentNode = dojo.byId(destination);
        contentNode.style.display = "none";
      } else {
        // showAlert(message);
        addCloseBoxToMessage(destination);
      }
    }
    hideWait();
  }
  if (dojo.byId('needProjectListRefresh')
      && dojo.byId('needProjectListRefresh').value == 'true') {
    needProjectListRefresh = true;
  }
  if (needProjectListRefresh) {
    refreshProjectSelectorList();
  }
  forceRefreshCreationInfo = false;
}
function addCloseBoxToMessage(destination) {
  contentWidget = dijit.byId(destination);
  var closeBox = '<div class="closeBoxIcon" onClick="clickCloseBoxOnMessage('
      + "'" + destination + "'" + ');">&nbsp;</div>';
  contentWidget.set("content", closeBox + contentWidget.get("content"));
}
var clickCloseBoxOnMessageAction = null;
function clickCloseBoxOnMessage(destination) {
  contentWidget = dijit.byId(destination);
  contentNode = dojo.byId(destination);
  dojo.fadeOut({
    node : contentNode,
    duration : 500,
    onEnd : function() {
      // contentWidget.set("content","");
      contentNode.style.display = "none";
      if (clickCloseBoxOnMessageAction != null) {
        clickCloseBoxOnMessageAction();
      }
      clickCloseBoxOnMessageAction = null;
    }
  }).play();
}
/**
 * ============================================================================
 * Operates locking, hide and show correct buttons after loadContent, when
 * destination is detailDiv
 * @param specificWidgetArray : array or null
 *                              List of specific widget to enable
 * @return void
 */
// CHANGE BY Marc TABARY - 2017-03-06 - ALLOW DISABLED SPECIFIC WIDGET
function finaliseButtonDisplay(specificWidgetArray) {
// Old    
//function finaliseButtonDisplay() {
// END CHANGE BY Marc TABARY - 2017-03-06 - ALLOW DISABLED SPECIFIC WIDGET

// ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET
  if (specificWidgetArray!==undefined) {
        // This parameter must be an array
        if (specificWidgetArray instanceof Array) {
            for (i = 0; i < specificWidgetArray.length; i++) {
               enableWidget(specificWidgetArray[i]);
            }
        }
  }
// END ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET

  id = dojo.byId("id");
  if (id) {
    if (id.value == "") {
      // id exists but is not set => new item, all buttons locked until first
      // change
      formLock();
      enableWidget('newButton');
      enableWidget('newButtonList');
      enableWidget('saveButton');
      disableWidget('undoButton');
      disableWidget('mailButton');
      disableWidget('changeStatusButton');
      disableWidget('subscribeButton');
      if (dijit.byId("objectGrid")) {
        enableWidget('multiUpdateButton');
      } else {
        disableWidget('multiUpdateButton');
        disableWidget('indentDecreaseButton');
        disableWidget('indentIncreaseButton');
      }
    }
  } else {
    // id does not exist => not selected, only new button possible
    formLock();
    enableWidget('newButton');
    enableWidget('newButtonList');
    disableWidget('changeStatusButton');
    disableWidget('subscribeButton');
    if (dijit.byId("objectGrid")) {
      enableWidget('multiUpdateButton');
    } else {
      disableWidget('multiUpdateButton');
    }
    // but show print buttons if not in objectDetail (buttonDiv exists)
    if (!dojo.byId("buttonDiv")) {
      enableWidget('printButton');
      enableWidget('printButtonPdf');
    }
  }
  buttonRightLock();
}

function finalizeMultipleSave() {
  // refreshGrid();
  var grid = dijit.byId("objectGrid");
  if (grid) {
    // unselectAllRows("objectGrid");
    var sortIndex = grid.getSortIndex();
    var sortAsc = grid.getSortAsc();
    var scrollTop = grid.scrollTop;
    store = grid.store;
    store.close();
    store
        .fetch({
          onComplete : function(items) {
            grid._refresh();
            setTimeout('dijit.byId("objectGrid").setSortIndex(' + sortIndex
                + ',' + sortAsc + ');', 10);
            setTimeout('dijit.byId("objectGrid").scrollTo(' + scrollTop + ');',
                20);
            selection = ';' + dojo.byId('selection').value;
            dojo.forEach(items, function(item, index) {
              if (selection.indexOf(";" + parseInt(item.id) + ";") >= 0) {
                grid.selection.setSelected(index, true);
              } else {
                grid.selection.setSelected(index, false);
              }
            })
          }
        });
  }
  if (dojo.byId('summaryResult')) {
    contentNode = dojo.byId('resultDiv');
    contentNode.innerHTML = dojo.byId('summaryResult').value;
    msg = dojo.byId('summaryResult').value;
    msg = msg.replace(" class='messageERROR' ", "");
    msg = msg.replace(" class='messageOK' ", "");
    msg = msg.replace(" class='messageWARNING' ", "");
    msg = msg.replace(" class='messageNO_CHANGE' ", "");
    msg = msg.replace("</div><div>", ", ");
    msg = msg.replace("</div><div>", ", ");
    msg = msg.replace("<div>", "");
    msg = msg.replace("<div>", "");
    msg = msg.replace("</div>", "");
    msg = msg.replace("</div>", "");
    addMessage(msg);
    dojo.fadeIn({
      node : contentNode,
      duration : 10,
      onEnd : function() {
        dojo.fadeOut({
          node : contentNode,
          duration : 3000
        }).play();
      }
    }).play();
  }
  hideWait();
}
/**
 * ============================================================================
 * Operates locking, hide and show correct buttons when a change is done on form
 * to be able to validate changes, and avoid actions that may lead to loose
 * change
// ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET
 * @param specificWidgetArray : Array of specific widget to disabled  
// END ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET
 * @return void
 */
// CHANGE BY Marc TABARY - 2017-03-06 - ALLOW DISABLED SPECIFIC WIDGET
function formChanged(specificWidgetArray) {
// Old    
//function formChanged() {
// END CHANGE BY Marc TABARY - 2017-03-06 - ALLOW DISABLED SPECIFIC WIDGET
  var updateRight = dojo.byId('updateRight');
  if (updateRight && updateRight.value == 'NO') {
    return;
  }
  disableWidget('newButton');
  disableWidget('newButtonList');
  enableWidget('saveButton');
  disableWidget('printButton');
  disableWidget('printButtonPdf');
  disableWidget('copyButton');
  enableWidget('undoButton');
  showWidget('undoButton');
  disableWidget('deleteButton');
  disableWidget('refreshButton');
  hideWidget('refreshButton');
  disableWidget('mailButton');
  disableWidget('multiUpdateButton');
  disableWidget('indentDecreaseButton');
  disableWidget('indentIncreaseButton');
  formChangeInProgress = true;
  grid = dijit.byId("objectGrid");
  if (grid) {
    // saveSelection=grid.selection;
    grid.selectionMode = "none";

  }
  buttonRightLock();

// ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET
    if (specificWidgetArray!==undefined) {
        // This parameter must be an array
        if (specificWidgetArray instanceof Array) {
            for (i = 0; i < specificWidgetArray.length; i++) {
               if (dijit.byId(specificWidgetArray[i])) {               // Widget
                   disableWidget(specificWidgetArray[i]);
               } else if(specificWidgetArray[i].indexOf('_spe_')!=-1) { // Specific attributes '_spe_'
                   // Search the id DOM
                   var theIdName = 'id_'+specificWidgetArray[i].replace('_spe_','');
                   var theId = document.getElementById(theIdName);
                   if (theId!==null) {
                       theIdName = theIdName.toLowerCase();
                       if(theIdName.indexOf('button')!=-1) {  // Button => Hide
                           theId.style.visibility = "hidden";
                       } else {                                         // Else, readonly
                           theId.readOnly=true;
                           theId.class +=' "readOnly"';
}
                   }
               }
            }
        }
    }
// END ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET

}

/**
 * ============================================================================
 * Operates unlocking, hide and show correct buttons when a form is refreshed to
 * be able to operate actions only available on forms with no change ongoing,
 * and avoid actions that may lead to unconsistancy
 * @param specificWidgetArray : Array of specific widget to disabled  
 * @return void
 */
// CHANGE BY Marc TABARY - 2017-03-06 - ALLOW DISABLED SPECIFIC WIDGET
function formInitialize(specificWidgetArray) {
// Old    
//function formInitialize() {
// END CHANGE BY Marc TABARY - 2017-03-06 - ALLOW DISABLED SPECIFIC WIDGET

// ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET
    if (specificWidgetArray!==undefined) {
        // This parameter must be an array
        if (specificWidgetArray instanceof Array) {
            for (i = 0; i < specificWidgetArray.length; i++) {
               enableWidget(specificWidgetArray[i]);
            }
        }
    }
// END ADD BY Marc TABARY - 2017-03-06 -  - ALLOW DISABLED SPECIFIC WIDGET
  enableWidget('newButton');
  enableWidget('newButtonList');
  enableWidget('saveButton');
  enableWidget('printButton');
  enableWidget('printButtonPdf');
  enableWidget('copyButton');
  disableWidget('undoButton');
  hideWidget('undoButton');
  enableWidget('deleteButton');
  enableWidget('refreshButton');
  showWidget('refreshButton');
  enableWidget('mailButton');
  if ( (dojo.byId("id") && dojo.byId("id").value != "") || (dojo.byId("lastSaveId") && dojo.byId("lastSaveId")!= "") ) {
    enableWidget('changeStatusButton');
    enableWidget('subscribeButton');
  } else {
    disableWidget('changeStatusButton');
    disableWidget('subscribeButton');
  }
  if (dijit.byId("objectGrid")) {
    enableWidget('multiUpdateButton');
  } else {
    disableWidget('multiUpdateButton');
    enableWidget('indentDecreaseButton');
    enableWidget('indentIncreaseButton');
  }
  formChangeInProgress = false;
  buttonRightLock();
}

/**
 * ============================================================================
 * Operates locking, to disable all actions during form submition
 * 
 * @return void
 */
function formLock() {
  disableWidget('newButton');
  disableWidget('newButtonList');
  disableWidget('saveButton');
  disableWidget('printButton');
  disableWidget('printButtonPdf');
  disableWidget('copyButton');
  disableWidget('undoButton');
  hideWidget('undoButton');
  disableWidget('deleteButton');
  disableWidget('refreshButton');
  showWidget('refreshButton');
  disableWidget('mailButton');
  disableWidget('multiUpdateButton');
  disableWidget('indentDecreaseButton');
  disableWidget('changeStatusButton');
  disableWidget('subscribeButton');
}

/**
 * ============================================================================
 * Lock some buttons depending on access rights
 */
function buttonRightLock() {
  var createRight = dojo.byId('createRight');
  var updateRight = dojo.byId('updateRight');
  var deleteRight = dojo.byId('deleteRight');
  if (createRight) {
    if (createRight.value != 'YES') {
      disableWidget('newButton');
      disableWidget('newButtonList');
      disableWidget('copyButton');
    }
  }
  if (updateRight) {
    if (updateRight.value != 'YES') {
      disableWidget('saveButton');
      disableWidget('undoButton');
      disableWidget('multiUpdateButton');
      disableWidget('indentDecreaseButton');
      disableWidget('indentIncreaseButton');
      disableWidget('changeStatusButton');
      disableWidget('subscribeButton');
    }
  }
  if (deleteRight) {
    if (deleteRight.value != 'YES') {
      disableWidget('deleteButton');
    }
  }
}

/**
 * ============================================================================
 * Disable a widget, testing it exists before to avoid error
 * 
 * @return void
 */
function disableWidget(widgetName) {
  if (dijit.byId(widgetName)) {
    dijit.byId(widgetName).set('disabled', true);
  }
}

/**
 * ============================================================================
 * Enable a widget, testing it exists before to avoid error
 * 
 * @return void
 */
function enableWidget(widgetName) {
  if (dijit.byId(widgetName)) {
    dijit.byId(widgetName).set('disabled', false);
  }
}

/**
 * ============================================================================
 * Hide a widget, testing it exists before to avoid error
 * 
 * @return void
 */
function hideWidget(widgetName) {
  if (dojo.byId(widgetName)) {
    dojo.style(dijit.byId(widgetName).domNode, {
      display : 'none'
    });
  }
}
/**
 * ============================================================================
 * Show a widget, testing it exists before to avoid error
 * 
 * @return void
 */
function showWidget(widgetName) {
  if (dojo.byId(widgetName)) {
    dojo.style(dijit.byId(widgetName).domNode, {
      display : 'inline-block'
    });
  }
}
/**
 * ============================================================================
 * Loack a widget, testing it exists before to avoid error
 * 
 * @return void
 */
function lockWidget(widgetName) {
  if (dijit.byId(widgetName)) {
    dijit.byId(widgetName).set('readOnly', true);
  }
}

/**
 * ============================================================================
 * Unlock a widget, testing it exists before to avoid error
 * 
 * @return void
 */
function unlockWidget(widgetName) {
  if (dijit.byId(widgetName)) {
    dijit.byId(widgetName).set('readOnly', false);
  }
}

/**
 * ============================================================================
 * Check if change is possible : to avoid recursive change when computing data
 * from other changes
 * 
 * @return boolean indicating if change is allowed or not
 */
function testAllowedChange(val) {
  if (cancelRecursiveChange_OnGoingChange == true) {
    return false;
  } else {
    if (val == null) {
      return false;
    } else {
      cancelRecursiveChange_OnGoingChange = true;
      return true;
    }
  }
}

/**
 * ============================================================================
 * Checks that ongoing change is finished, so another change cxan be taken into
 * account so that testAllowedChange() can return true
 * 
 * @return void
 */
function terminateChange() {
  window.setTimeout("cancelRecursiveChange_OnGoingChange=false;", 100);
}

/**
 * ============================================================================
 * Check if a change is waiting for form submission to be able to avoid unwanted
 * actions leading to loose of data change
 * 
 * @return boolean indicating if change is in progress for the form
 */
function checkFormChangeInProgress(actionYes, actionNo) {
  if (waitingForReply) {
    showInfo(i18n("alertOngoingQuery"));
    return true;
  } else if (formChangeInProgress) {
    if (multiSelection) {
      endMultipleUpdateMode();
      return false;
    }
    if (actionYes) {
      if (!actionNo) {
        actionNo = function() {
        };
      }
      showQuestion(i18n("confirmChangeLoosing"), actionYes, actionNo);
    } else {
      showAlert(i18n("alertOngoingChange"));
    }
    return true;
  } else {
    if (actionYes) {
      actionYes();
    }
    return false;
  }
}

/**
 * ============================================================================
 * Unselect all the lines of the grid
 * 
 * @param gridName
 *          the name of the grid
 * @return void
 */
function unselectAllRows(gridName) {
  grid = dijit.byId(gridName); // if the element is not a widget, exit.
  if (!grid) {
    return;
  }
  grid.store.fetch({
    onComplete : function(items) {
      dojo.forEach(items, function(item, index) {
        grid.selection.setSelected(index, false);
      });
    }
  });
}

function selectAllRows(gridName) {
  grid = dijit.byId(gridName); // if the element is not a widget, exit.
  if (!grid) {
    return;
  }
  grid.store.fetch({
    onComplete : function(items) {
      dojo.forEach(items, function(item, index) {
        grid.selection.setSelected(index, true);
      });
    }
  });
}

function countSelectedItem(gridName) {
  grid = dijit.byId(gridName); // if the element is not a widget, exit.
  if (!grid) {
    return 0;
  }
  var lstStore=new Array();
  grid.store.fetch({
    onComplete : function(items) {
      dojo.forEach(items, function(item, index) {
        lstStore[item.id]=item.id;
      });
    }
  });
  var items=grid.selection.getSelected();
  if (items.length) {
    dojo.forEach(items, function(selectedItem) {
      if (selectedItem !== null) {
        if (lstStore.indexOf(selectedItem.id)=== -1) {
          grid.selection.setSelected(selectedItem.id, false);
        }
      }
    });
  } else {
    return 0;
  }
  return grid.selection.getSelectedCount();
}
/**
 * ============================================================================
 * Select a given line of the grid, corresponding to the given id
 * 
 * @param gridName
 *          the name of the grid
 * @param id
 *          the searched id
 * @return void
 */
var gridReposition = false;
function selectRowById(gridName, id, tryCount) {
  if (!tryCount)
    tryCount = 0;
  var grid = dijit.byId(gridName); // if the element is not a widget, exit.
  if (!grid || !id) {
    return;
  }
  unselectAllRows(gridName); // first unselect, to be sure to select only 1
                              // line
  // De-activate this function for IE8 : grid.getItem does not work
  if (dojo.isIE && parseInt(dojo.isIE, 10) <= '8') {
    return;
  }
  if (dojo.byId('objectClassList') && dojo.byId('objectClassList').value=='GlobalView' && dojo.byId('objectClass')) {
    id=dojo.byId('objectClass').value+id;
  }
  var nbRow = grid.rowCount;
  gridReposition = true;
  var j = -1;
  dojo.forEach(grid.store._getItemsArray(), function(item, i) {
    if (item && item.id == id) {
      var j = grid.getItemIndex(item); // if item is in the page, will find
                                        // quickly
      if (j == -1) { // not found : must search
        if (grid.getSortIndex() == -1) { // No sort so order in grid is same as
                                          // order in store
          grid.selection.setSelected(i, true);
        } else {
          tryCount++;
          if (tryCount <= 3) {
            setTimeout("selectRowById('" + gridName + "', " + id + ","
                + tryCount + ");", 100);
          } else {
            var indexLength = grid._by_idx.length;
            var element = null;
            for (var x = 0; x < indexLength; x++) {
              element = grid._by_idx[x];
              if (!element) continue;
              if (parseInt(element.item.id) == id) {
                grid.selection.setSelected(x, true);
                break;
              }
            }
          }
          /*
           * if (1 || j==-1) { for (var i=0;i<nbRow;i++) { var
           * item=grid.getItem(i); if (parseInt(item.id)==id) {
           * grid.selection.setSelected(i,true); } } }
           */
        }
      } else {
        grid.selection.setSelected(j, true);
      }
      // first=grid.scroller.firstVisibleRow; // Remove the scroll : will be a
      // mess when dealing with many items and order of item changes
      // last=grid.scroller.lastVisibleRow;
      // if (j<first || j>last) //grid.scrollToRow(j);
      gridReposition = false;
      return;
    }
  });
  gridReposition = false;
}
function selectPlanningRow() {
  setTimeout("selectPlanningLine(dojo.byId('objectClass').value,dojo.byId('objectId').value);",1);
}
function selectGridRow() {
  setTimeout("selectRowById('objectGrid',dojo.byId('objectId').value);", 100);
}

/**
 * ============================================================================
 * i18n (internationalization) function to return all messages and caption in
 * the language corresponding to the locale File lang.js must exist in directory
 * tool/i18n/nls/xx (xx as locale) otherwise default is uses (english) (similar
 * function exists in php, using same resource)
 * 
 * @param str
 *          the code of the string message
 * @param vars
 *          an array of parameters to replace in the message. They appear as
 *          ${n}.
 * @return the formated message, in the correct language
 */
function i18n(str, vars) {
  if (!i18nMessages) {
    try {
      // dojo.registerModulePath('i18n', '/tool/i18n');
      dojo.requireLocalization("i18n", "lang", currentLocale);
      i18nMessages = dojo.i18n.getLocalization("i18n", "lang", currentLocale);
    } catch (err) {
      i18nMessages = new Array();
    }
    if (customMessageExists) {
      try {
        // dojo.registerModulePath('i18n', '/tool/i18n');
        dojo.requireLocalization("i18nCustom", "lang", currentLocale);
        i18nMessagesCustom = dojo.i18n.getLocalization("i18nCustom", "lang",
            currentLocale);
      } catch (err) {
        i18nMessagesCustom = new Array();
      }
    } else {
      i18nMessagesCustom = new Array();
    }
  }
  var ret = null;
  if (top.i18nMessagesCustom[str]) {
    ret = top.i18nMessagesCustom[str];
  } else if (top.i18nMessages[str]) {
    ret = top.i18nMessages[str];
  } else if (top.i18nPluginArray && top.i18nPluginArray[str]) {
    ret = top.i18nPluginArray[str];
  }
  if (ret) {
    if (vars) {
      for (i = 0; i < vars.length; i++) {
        rep = '${' + (parseInt(i, 10) + 1) + '}';
        pos = ret.indexOf(rep);
        if (pos >= 0) {
          ret = ret.substring(0, pos) + vars[i]
              + ret.substring(pos + rep.length);
          pos = ret.indexOf(rep);
        }
      }
    }
    return ret;
  } else {
    return "[" + str + "]";
  }
}

/**
 * ============================================================================
 * set the selected project (transmit it to session)
 * 
 * @param idProject
 *          the id of the selected project
 * @param nameProject
 *          the name of the selected project
 * @param selectionField
 *          the name of the field where selection is executed
 * @return void
 */
function setSelectedProject(idProject, nameProject, selectionField,resetPrevious) {
  if (selectionField) {
    dijit.byId(selectionField).set(
        "label",
        '<div style="width:140px; overflow: hidden;text-align: left;" >'
            + nameProject + '</div>');
  }
  if (resetPrevious) {
    previousSelectedProject=null;
    previousSelectedProjectName=null;  
  }
  currentSelectedProject = idProject;
  if (idProject != "") {
  	var callBack = function(){
        addMessage(i18n("Project") + "=" + nameProject);
        if (dojo.byId("GanttChartDIV")) {
          if (dojo.byId("resourcePlanning")) {
            loadContent("resourcePlanningList.php", "listDiv", 'listForm');
          } else if (dojo.byId("portfolioPlanning")) {
            loadContent("portfolioPlanningList.php", "listDiv", 'listForm');
          } else if (dojo.byId("globalPlanning")) {
            loadContent("globalPlanningList.php", "listDiv", 'listForm');
          } else {
            loadContent("planningList.php", "listDiv", 'listForm');
          }
        } else if (dijit.byId("listForm") && dojo.byId('objectClassList') && dojo.byId('listShowIdle')) {
          refreshJsonList(dojo.byId('objectClassList').value);
        } else if (dijit.byId("listForm") && dojo.byId('objectClass') && dojo.byId('listShowIdle')) {
          refreshJsonList(dojo.byId('objectClass').value);
        } else if (dojo.byId('objectClassManual') && dojo.byId('objectClassManual').value == 'Today') {
          loadContent("../view/today.php", "centerDiv");
        } else if (dojo.byId('objectClassManual') && dojo.byId('objectClassManual').value == 'Plugin_kanban') {
          loadContent("../plugin/kanban/kanbanViewMain.php", "centerDiv");        
        } else if (dojo.byId('objectClassManual') && dojo.byId('objectClassManual').value == 'ActivityStream') {
          loadContent("../view/activityStreamList.php", "activityStreamListDiv", "activityStreamForm");      
        } else if (dojo.byId('objectClassManual') && dojo.byId('objectClassManual').value == 'DashboardTicket') {
          loadContent("../view/dashboardTicketMain.php", "centerDiv");
        } else if (dojo.byId('currentPhpPage') && dojo.byId('currentPhpPage').value) {
          loadContent("../view/dashboardTicketMain.php", "centerDiv");
        } else if (currentPluginPage) {
          loadContent(currentPluginPage, "centerDiv");
        }
        if (dijit.byId('imputationButtonDiv') && dijit.byId('limitResByProj') && dijit.byId('limitResByProj').get('value')=="on"){
          refreshList('imputationResource', null, null, dijit.byId('userName').get('value'), 'userName', true); 
        }
  	};
  	saveDataToSession('project', idProject, null, callBack);
  }
  if (idProject != "" && idProject != "*" && dijit.byId("idProjectPlan")) {
    dijit.byId("idProjectPlan").set("value", idProject);
  }
  if (selectionField) {
    dijit.byId(selectionField).closeDropDown();
  }
  loadContent('../view/shortcut.php', "projectLinkDiv", null, null, null, null,
      true);
}

/**
 * Ends current user session
 * 
 * @return
 */
function disconnect(cleanCookieHash) {
  disconnectFunction = function() {
    quitConfirmed = true;
    //extUrl="";
    extUrl="origin==disconnect";
    if (cleanCookieHash) {
      extUrl += "&cleanCookieHash=true";
    }
    //#2887
    var callBack = function(){
    	window.location = "../index.php";
    }
    saveDataToSession("disconnect", extUrl, null, callBack);
  };
  if (!checkFormChangeInProgress()) {
    if (paramConfirmQuit != "NO") {
      showConfirm(i18n('confirmDisconnection'), disconnectFunction);
    } else {
      disconnectFunction();
    }
  }
}

/**
 * Disconnect (kill current session)
 * 
 * @return
 */

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  } 
}
function quit() {
  if (!noDisconnect) {
    showWait();
    saveDataToSession('disconnect', '&origin==quit');
    if(dojo.isFF || dojo.isSafari){
      sleep(1000);
    }
    setTimeout("window.location='../index.php'", 100);
  }
}

/**
 * Before quitting, check for updates
 * 
 * @return
 */
function beforequit() {
  if (!quitConfirmed) {
    if (checkFormChangeInProgress()) {
      return (i18n("alertQuitOngoingChange"));
    } else {
      if (paramConfirmQuit != "NO") {
        return (i18n('confirmDisconnection'));
      }
    }
  }
  // return false;
}

/**
 * Draw a gantt chart using jsGantt
 * 
 * @return
 */
function drawGantt() {
  // first, if detail is displayed, reload class
  if (dojo.byId('objectClass') && !dojo.byId('objectClass').value
      && dojo.byId("objectClassName") && dojo.byId("objectClassName").value) {
    dojo.byId('objectClass').value = dojo.byId("objectClassName").value;
  }
  if (dojo.byId("objectId") && !dojo.byId("objectId").value && dijit.byId("id")
      && dijit.byId("id").get("value")) {
    dojo.byId("objectId").value = dijit.byId("id").get("value");
  }
  var startDateView = new Date();
  if (dijit.byId('startDatePlanView')) {
    startDateView = dijit.byId('startDatePlanView').get('value');
  }
  var endDateView = null;
  if (dijit.byId('endDatePlanView')) {
    endDateView = dijit.byId('endDatePlanView').get('value');
  }
  var showWBS = null;
  if (dijit.byId('showWBS')) {
    showWBS = dijit.byId('showWBS').get('checked');
  }
  // showWBS=true;
  var gFormat = "day";
  if (g) {
    gFormat = g.getFormat();
  }
  g = new JSGantt.GanttChart('g', dojo.byId('GanttChartDIV'), gFormat);
  setGanttVisibility(g);
  g.setCaptionType('Caption'); // Set to Show Caption
  // (None,Caption,Resource,Duration,Complete)
  // g.setShowStartDate(1); // Show/Hide Start Date(0/1)
  // g.setShowEndDate(1); // Show/Hide End Date(0/1)
  g.setDateInputFormat('yyyy-mm-dd'); // Set format of input dates
  // ('mm/dd/yyyy', 'dd/mm/yyyy',
  // 'yyyy-mm-dd')
  g.setDateDisplayFormat('default'); // Set format to display dates
  // ('mm/dd/yyyy', 'dd/mm/yyyy',
  // 'yyyy-mm-dd')
  g.setFormatArr("day", "week", "month", "quarter"); // Set format options (up
  if (dijit.byId('selectBaselineBottom')) {
    g.setBaseBottomName(dijit.byId('selectBaselineBottom').get('displayedValue'));
  }
  if (dijit.byId('selectBaselineTop')) {
    g.setBaseTopName(dijit.byId('selectBaselineTop').get('displayedValue'));
  }
  // to 4 :
  // "minute","hour","day","week","month","quarter")
  if (ganttPlanningScale) {
    g.setFormat(ganttPlanningScale);
  }
  g.setStartDateView(startDateView);
  g.setEndDateView(endDateView);
  if (dijit.byId('criticalPathPlanning')) g.setShowCriticalPath(dijit.byId('criticalPathPlanning').get('checked'));
  var contentNode = dojo.byId('gridContainerDiv');
  if (contentNode) {
    g.setWidth(dojo.style(contentNode, "width"));
  }
  jsonData = dojo.byId('planningJsonData');
  if (jsonData.innerHTML.indexOf('{"identifier"') < 0 || jsonData.innerHTML.indexOf('{"identifier":"id", "items":[ ] }')>=0) {
    if (dijit.byId('leftGanttChartDIV')) dijit.byId('leftGanttChartDIV').set('content',null);
    if (dijit.byId('rightGanttChartDIV')) dijit.byId('rightGanttChartDIV').set('content',null);
    if (dijit.byId('topGanttChartDIV')) dijit.byId('topGanttChartDIV').set('content',null);  
    if (jsonData.innerHTML.length > 10 && jsonData.innerHTML.indexOf('{"identifier":"id", "items":[ ] }')<0) {
      showAlert(jsonData.innerHTML);
    }
    hideWait();
    return;
  }
  var now = formatDate(new Date());
  // g.AddTaskItem(new JSGantt.TaskItem( 0, 'project', '', '', 'ff0000', '',
  // 0, '', '10', 1, '', 1, '' , 'test'));
  if (g && jsonData) {
    var store = eval('(' + jsonData.innerHTML + ')');
    var items = store.items;
    // var arrayKeys=new Array();
    var keys = "";
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      // var topId=(i==0)?'':item.topid;
      var topId = item.topid;
      // pStart : start date of task
      var pStart = now;
      var pStartFraction = 0;
      pStart = (trim(item.initialstartdate) != "") ? item.initialstartdate
          : pStart;
      pStart = (trim(item.validatedstartdate) != "") ? item.validatedstartdate
          : pStart;
      pStart = (trim(item.plannedstartdate) != "") ? item.plannedstartdate
          : pStart;
      pStart = (trim(item.realstartdate) != "") ? item.realstartdate : pStart;
      pStart = (trim(item.plannedstartdate)!="" && trim(item.realstartdate) && item.plannedstartdate<item.realstartdate)?item.plannedstartdate:pStart;
      if (trim(item.plannedstartdate) != "" && trim(item.realenddate) == "") {
        pStartFraction = item.plannedstartfraction;
      }
      // If real work in the future, don't take it in account
      if (trim(item.plannedstartdate) && trim(item.realstartdate)
          && item.plannedstartdate < item.realstartdate
          && item.realstartdate > now) {
        pStart = item.plannedstartdate;
      }
      // pEnd : end date of task
      var pEnd = now;
      //var pEndFraction = 1;
      pEnd = (trim(item.initialenddate) != "") ? item.initialenddate : pEnd;
      pEnd = (trim(item.validatedenddate) != "") ? item.validatedenddate : pEnd;
      pEnd = (trim(item.plannedenddate) != "") ? item.plannedenddate : pEnd;

      pRealEnd = "";
      pPlannedStart = "";
      pWork = "";
      if (dojo.byId('resourcePlanning')) {
        pRealEnd = item.realenddate;
        pPlannedStart = item.plannedstartdate;
        pWork = item.leftworkdisplay;
        g.setSplitted(true);
      } else {
        pEnd = (trim(item.realenddate) != "") ? item.realenddate : pEnd;
      }
      if (pEnd < pStart)
        pEnd = pStart;
      //
      var realWork = parseFloat(item.realwork);
      var plannedWork = parseFloat(item.plannedwork);
      var validatedWork = parseFloat(item.validatedwork);
      var progress = 0;
      if (item.isglobal && item.isglobal==1 && item.progress) { 
        progress=item.progress;
      } else {
        if (plannedWork > 0) {
          progress = Math.round(100 * realWork / plannedWork);
        } else {
          if (item.done == 1) {
            progress = 100;
          }
        }
      }
      // pGroup : is the task a group one ?
      var pGroup = (item.elementary == '0') ? 1 : 0;
      //MODIF qCazelles - GANTT
      if (item.reftype=='Project' || item.reftype=='Fixed' || item.reftype=='Replan' || item.reftype=='Construction' || item.reftype=='ProductVersionhasChild' || item.reftype=='ComponentVersionhasChild' ) pGroup=1;
     //END MODIF qCazelles - GANTT
      // runScript : JavaScript to run when click on task (to display the
      // detail of the task)
      var runScript = "runScript('" + item.reftype + "','" + item.refid + "','"+ item.id + "');";
      var contextMenu = "runScriptContextMenu('" + item.reftype + "','" + item.refid + "','"+ item.id + "');";
      // display Name of the task
      var pName = ((showWBS) ? item.wbs : '') + " " + item.refname; // for
                                                                    // testeing
      // purpose, add
      // wbs code
      // var pName=item.refname;
      // display color of the task bar
      var pColor = (pGroup)?'003000':'50BB50'; // Default green
      if (! pGroup && item.notplannedwork > 0) { // Some left work not planned : purple
        pColor = '9933CC';
      } else if (trim(item.validatedenddate) != "" && item.validatedenddate < pEnd) { // Not respected constraints (end date) : red
        if (item.reftype!='Milestone' && ( ! item.assignedwork || item.assignedwork==0 ) && ( ! item.leftwork || item.leftwork==0 ) && ( ! item.realwork || item.realwork==0 )) {
          pColor = (pGroup)?'650000':'BB9099';
        } else {
          pColor = (pGroup)?'650000':'BB5050';
        }
      } else if (! pGroup && item.reftype!='Milestone' && ( ! item.assignedwork || item.assignedwork==0 ) && ( ! item.leftwork || item.leftwork==0 ) && ( ! item.realwork || item.realwork==0 ) ) { // No workassigned : greyed green
        pColor = 'aec5ae';
      }
      
      if (item.redElement == '1') {
    	  pColor = 'BB5050';
      }
      else if(item.redElement == '0') {
    	  pColor = '50BB50';
      }      
      
      // pMile : is it a milestone ?      
      var pMile = (item.reftype == 'Milestone') ? 1 : 0;
      if (pMile) {
        pStart = pEnd;
      }
      pClass = item.reftype;
      pId = item.refid;
      pScope = "Planning_" + pClass + "_" + pId;
      pOpen = (item.collapsed == '1') ? '0' : '1';
      var pResource = item.resource;
      var pCaption = "";
      if (dojo.byId('listShowResource')) {
        if (dojo.byId('listShowResource').checked) {
          pCaption = pResource;
        }
      }
      if (dojo.byId('listShowLeftWork')
          && dojo.byId('listShowLeftWork').checked) {
        if (item.leftwork > 0) {
          pCaption = item.leftworkdisplay;
        } else {
          pCaption = "";
        }
      }
      var pDepend = item.depend;
      topKey = "#" + topId + "#";
      curKey = "#" + item.id + "#";
      if (keys.indexOf(topKey) == -1) {
        topId = '';
      }
      keys += "#" + curKey + "#";
      g.AddTaskItem(new JSGantt.TaskItem(item.id, pName, pStart, pEnd, pColor,
          runScript, contextMenu, pMile, pResource, progress, pGroup, 
          topId, pOpen, pDepend,
          pCaption, pClass, pScope, pRealEnd, pPlannedStart,
          item.validatedworkdisplay, item.assignedworkdisplay, item.realworkdisplay, item.leftworkdisplay, item.plannedworkdisplay,
          item.priority, item.planningmode, 
          item.status, item.type, 
          item.validatedcostdisplay, item.assignedcostdisplay, item.realcostdisplay, item.leftcostdisplay, item.plannedcostdisplay,
          item.baseTopStart, item.baseTopEnd, item.baseBottomStart, item.baseBottomEnd, item.isoncriticalpath));
    }
    g.Draw();
    g.DrawDependencies();
  } else {
    // showAlert("Gantt chart not defined");
    return;
  }
  highlightPlanningLine();
}

function runScript(refType, refId, id) {
  if (refType == 'Fixed' || refType=='Construction' || refType=='Replan') {
    refType = 'Project';
  }
  //ADD by qCazelles - GANTT
  if (refType == 'ProductVersionhasChild') {
	  refType = 'ProductVersion';
  }
  if (refType == 'ComponentVersionhasChild') {
	  refType = 'ComponentVersion';
  }
  //END ADD qCazelles - GANTT
  if (waitingForReply) {
    showInfo(i18n("alertOngoingQuery"));
    return;
  }
  if (checkFormChangeInProgress()) {
    return false;
  }
  dojo.byId('objectClass').value = refType;
  dojo.byId('objectId').value = refId;
  hideList();
  loadContent('objectDetail.php?planning=true&planningType='
      + dojo.byId('objectClassManual').value, 'detailDiv', 'listForm');
  if (dijit.byId("detailRightDiv")) loadContent("objectStream.php", "detailRightDiv", "listForm"); 
  highlightPlanningLine(id);
}
ongoingRunScriptContextMenu=false;
function runScriptContextMenu(refType, refId, id) {
  var objectClassManual = dojo.byId('objectClassManual').value;
  showWait();
  setTimeout("document.body.style.cursor='default';",100);
  dojo.xhrGet({
    url : "../view/planningBarDetail.php?class="+refType+"&id="+refId+"&scale="+ganttPlanningScale+"&objectClassManual="+objectClassManual+"&idAssignment="+id,
    load : function(data, args) {
      ongoingRunScriptContextMenu=true;
      setTimeout("document.body.style.cursor='default';",100);
      var bar = dojo.byId('bardiv_'+id);
      var line = dojo.byId('childgrid_'+id);
      var detail = dojo.byId('rightTableBarDetail');
      detail.style.display="block";
      detail.innerHTML=data;
      detail.style.width=(parseInt(bar.style.width)+202)+'px';
      detail.style.left=(bar.offsetLeft-1)+"px";
      var tableHeight=44;
      if (dojo.byId('planningBarDetailTable')) tableHeight=dojo.byId('planningBarDetailTable').offsetHeight
      if ( dojo.byId('rightTableContainer').offsetHeight + tableHeight > (dojo.byId('rightGanttChartDIV').offsetHeight) && (line.offsetTop+25)> dojo.byId('rightTableContainer').offsetHeight ) {
        detail.style.top=(line.offsetTop-tableHeight+1)+"px";  
      } else {
        detail.style.top=(line.offsetTop+22)+"px";
      }
      hideWait();
      setTimeout("ongoingRunScriptContextMenu=false;",20);
    },
    error : function () {
      console.warn ("error on return from planningBarDetail.php");
      hideWait();
      setTimeout("ongoingRunScriptContextMenu=false;",20);
    }
  });
  return false;
}
function highlightPlanningLine(id) {
  if (id == null)
    id = vGanttCurrentLine;
  if (id < 0)
    return;
  vGanttCurrentLine = id;
  vTaskList = g.getList();
  for (var i = 0; i < vTaskList.length; i++) {
    JSGantt.ganttMouseOut(i);
  }
  var vRowObj1 = JSGantt.findObj('child_' + id);
  if (vRowObj1) {
    // vRowObj1.className = "dojoxGridRowSelected dojoDndItem";// ganttTask" +
    // pType;
    dojo.addClass(vRowObj1, "dojoxGridRowSelected");
  }
  var vRowObj2 = JSGantt.findObj('childrow_' + id);
  if (vRowObj2) {
    // vRowObj2.className = "dojoxGridRowSelected";
    dojo.addClass(vRowObj2, "dojoxGridRowSelected");
  }
}
function selectPlanningLine(selClass, selId) {
  vGanttCurrentLine = id;
  vTaskList = g.getList();
  var tId = null;
  for (var i = 0; i < vTaskList.length; i++) {
    scope = vTaskList[i].getScope();
    spl = scope.split("_");
    if (spl.length > 2 && spl[1] == selClass && spl[2] == selId) {
      tId = vTaskList[i].getID();
    }
  }
  if (tId != null) {
    unselectPlanningLines();
    highlightPlanningLine(tId);
  }
}
function unselectPlanningLines() {
  dojo.query(".dojoxGridRowSelected").forEach(function(node, index, nodelist) {
    dojo.removeClass(node, "dojoxGridRowSelected");
  });
}
/**
 * calculate diffence (in work days) between dates
 */

function workDayDiffDates(paramStartDate, paramEndDate) {
  var currentDate = new Date();
  if (!isDate(paramStartDate))
    return '';
  if (!isDate(paramEndDate))
    return '';
  currentDate.setFullYear(paramStartDate.getFullYear(), paramStartDate.getMonth(), paramStartDate.getDate());
  currentDate.setHours(0,0,0,0);
  var endDate = new Date();
  endDate.setFullYear(paramEndDate.getFullYear(), paramEndDate.getMonth(), paramEndDate.getDate());
  endDate.setHours(0,0,0,0);
  if (endDate < currentDate) {
    return 0;
  }
  var duration = 0;
  if (isOffDay(currentDate) && currentDate.valueOf()!=endDate.valueOf()) duration++;
  while (currentDate <= endDate) {
    if (!isOffDay(currentDate) || currentDate.valueOf()==endDate.valueOf()) {
      duration++;
    }
    currentDate = addDaysToDate(currentDate, 1);
  }
  return duration;
}
/**
 * calculate diffence (in days) between dates
 */
function dayDiffDates(paramStartDate, paramEndDate) {
  var startDate = paramStartDate;
  var endDate = paramEndDate;
  var valDay = (24 * 60 * 60 * 1000);
  var duration = (endDate - startDate) / valDay;
  duration = Math.round(duration);
  return duration;
}

/**
 * Return the day of the week like php function : date("N",$valDate) Monday=1,
 * Tuesday=2, Wednesday=3, Thursday=4, Friday=5, Saturday=6, Sunday=7 (not 0 !)
 */
function getDay(valDate) {
  var day = valDate.getDay();
  day = (day == 0) ? 7 : day;
  return day;
}

/**
 * ============================================================================
 * Calculate new date after adding some days
 * 
 * @param paramDate
 *          start date
 * @param days
 *          numbers of days to add (can be < 0 to subtract days)
 * @return new calculated date
 */
function addDaysToDate(paramDate, paramDays) {
  var date = paramDate;
  var days = paramDays;
  var endDate = date;
  endDate.setDate(date.getDate() + days);
  return endDate;
}

/**
 * ============================================================================
 * Calculate new date after adding some work days, subtracting week-ends
 * 
 * @param $ate
 *          start date
 * @param days
 *          numbers of days to add (can be < 0 to subtract days)
 * @return new calculated date
 */
function addWorkDaysToDate_old(paramDate, paramDays) {
  var startDate = paramDate;
  var days = paramDays;
  if (days <= 0) {
    return startDate;
  }
  days -= 1;
  if (getDay(startDate) >= 6) {
    // startDate.setDate(startDate.getDate()+8-getDay(startDate));
  }
  var weekEnds = Math.floor(days / 5);
  var additionalDays = days - (5 * weekEnds);
  if (getDay(startDate) + additionalDays >= 6) {
    weekEnds += 1;
  }
  days += (2 * weekEnds);
  var endDate = startDate;
  endDate.setDate(startDate.getDate() + days);
  return endDate;
}

function addWorkDaysToDate(paramDate, paramDays) {
  endDate = paramDate;
  left = paramDays;
  left--;
  while (left > 0) {
    endDate = addDaysToDate(endDate, 1);
    if (!isOffDay(endDate)) {
      left--;
    }
  }
  return endDate;
}
/**
 * Check "all" checkboxes on workflow definition
 * 
 * @return
 */
function workflowSelectAll(line, column, profileList) {
  workflowChange(null, null, null);
  var reg = new RegExp("[ ]+", "g");
  var profileArray = profileList.split(reg);
  var check = dijit.byId('val_' + line + "_" + column);
  if (check) {
    var newValue = (check.get("checked")) ? 'checked' : '';
    for (i = 0; i < profileArray.length; i++) {
      var checkBox = dijit.byId('val_' + line + "_" + column + "_"
          + profileArray[i]);
      if (checkBox) {
        checkBox.set("checked", newValue);
      }
    }
  } else {
    var newValue = dojo.byId('val_' + line + "_" + column).checked;
    for (i = 0; i < profileArray.length; i++) {
      var checkBox = dojo.byId('val_' + line + "_" + column + "_"
          + profileArray[i]);
      if (checkBox) {
        checkBox.checked = newValue;
      }
    }
  }
}

/**
 * Flag a change on workflow definition
 * 
 * @return
 */
function workflowChange(line, column, profileList) {
  var change = dojo.byId('workflowUpdate');
  change.value = new Date();
  formChanged();
  if (line == null) {
    return;
  }
  var allChecked = true;
  var reg = new RegExp("[ ]+", "g");
  var profileArray = profileList.split(reg);
  var check = dijit.byId('val_' + line + "_" + column);
  if (check) {
    // var newValue=(check.get("checked"))? 'checked': '';
    for (i = 0; i < profileArray.length; i++) {
      var checkBox = dijit.byId('val_' + line + "_" + column + "_"
          + profileArray[i]);
      if (checkBox) {
        if (checkBox.get("checked") == 'false') {
          allChecked = false;
        }
      }
    }
    check.set('checked', (allChecked ? 'true' : 'false'));
  } else {
    // var newValue=dojo.byId('val_' + line + "_" + column).checked;
    for (i = 0; i < profileArray.length; i++) {
      var checkBox = dojo.byId('val_' + line + "_" + column + "_"
          + profileArray[i]);
      if (checkBox) {
        if (!checkBox.checked) {
          allChecked = false;
        }
      }
    }
    dojo.byId('val_' + line + "_" + column).checked = allChecked;
  }

}

function isDate(date) {
  if (!date)
    return false;
  if (date instanceof Date && !isNaN(date.valueOf()))
    return true;
  return false;
}
/**
 * refresh Projects List on Today screen
 */
function refreshTodayProjectsList(value) {
  loadContent("../view/today.php?refreshProjects=true", "Today_project",
      "todayProjectsForm", false);
}

function gotoElement(eltClass, eltId, noHistory, forceListRefresh, target) {
  if (checkFormChangeInProgress()) {
    return false;
  }
  if (eltClass == 'Project' || eltClass == 'Activity'
      || eltClass == 'Milestone' || eltClass == 'Meeting'
      || eltClass == 'TestSession') {
    if (dojo.byId("GanttChartDIV")) {
      target = 'planning';
      forceListRefresh = true;
    }
  }
  selectTreeNodeById(dijit.byId('menuTree'), eltClass);
  formChangeInProgress = false;
  // if ( dojo.byId("GanttChartDIV")
  // && (eltClass=='Project' || eltClass=='Activity' || eltClass=='Milestone'
  // || eltClass=='TestSession' || eltClass=='Meeting' ||
  // eltClass=='PeriodicMeeting') ) {
  if (target == 'planning') {
    if (!dojo.byId("GanttChartDIV")) {
      vGanttCurrentLine = -1;
      cleanContent("centerDiv");
      var callback = function() {
        gotoElement(eltClass, eltId, noHistory, forceListRefresh, target);
      }
      loadContent("planningMain.php", "centerDiv", null, null, null, null,
          null, callback);
      return;
    }
    if (forceListRefresh) {
      refreshGrid(true);
    }
    dojo.byId('objectClass').value = eltClass;
    dojo.byId('objectId').value = eltId;
    loadContent('objectDetail.php', 'detailDiv', 'listForm');
    //if (dijit.byId("detailRightDiv")) loadContent("objectStream.php", "detailRightDiv", "listForm"); 
  } else {
    if (dojo.byId("detailDiv")) {
      cleanContent("detailDiv");
    }
    if ( ( (!dojo.byId('objectClass') || dojo.byId('objectClass').value != eltClass) && (!dojo.byId('objectClassList') || dojo.byId('objectClassList').value != eltClass))
        || forceListRefresh || dojo.byId('titleKanban')) {
      var callBack=null;
      if (dijit.byId("detailRightDiv")) callBack=function(){loadContent("objectStream.php", "detailRightDiv", "listForm");};
      
      loadContent("objectMain.php?objectClass=" + eltClass, "centerDiv", false,
          false, false, eltId,false,callBack);
    } else {
      if (eltClass=='GlobalView') {
        var explode=eltId.split('|');
        dojo.byId('objectClass').value = explode[0];
        dojo.byId('objectId').value =  explode[1];
      } else {
        dojo.byId('objectClass').value = eltClass;
        dojo.byId('objectId').value = eltId;
      }
      loadContent('objectDetail.php', 'detailDiv', 'listForm');
      if (dijit.byId("detailRightDiv")) loadContent("objectStream.php", "detailRightDiv", "listForm"); 
      hideList();
      var key=(eltClass=='GlobalView')?eltId:parseInt(eltId);
      setTimeout('selectRowById("objectGrid", ' + key + ');', 100);
    }
  }
  if (!noHistory) {
    stockHistory(eltClass, eltId);
  }
}

function runReport() {
  var fileName = dojo.byId('reportFile').value;
  loadContent("../report/" + fileName, "detailReportDiv", "reportForm", false);
}
function saveReportInToday() {
  var fileName = dojo.byId('reportFile').value;
  loadContent("../tool/saveReportInToday.php", "resultDiv", "reportForm", true,
      'report');
}

/**
 * Global save function through [CTRL)+s
 */
function globalSave() {
  if (dijit.byId('dialogDetail') && dijit.byId('dialogDetail').open) {
    var button = dijit.byId('comboSaveButton');
  } else if (dijit.byId('dialogNote') && dijit.byId('dialogNote').open) {
    var button = dijit.byId('dialogNoteSubmit');
  } else if (dijit.byId('dialogLine') && dijit.byId('dialogLine').open) {
    var button = dijit.byId('dialogLineSubmit');
  } else if (dijit.byId('dialogLink') && dijit.byId('dialogLink').open) {
    var button = dijit.byId('dialogLinkSubmit');
  } else if (dijit.byId('dialogOrigin') && dijit.byId('dialogOrigin').open) {
    var button = dijit.byId('dialogOriginSubmit');
  } else if (dijit.byId('dialogCopy') && dijit.byId('dialogCopy').open) {
    var button = dijit.byId('dialogCopySubmit');
    //gautier #2522
  } else if (dijit.byId('dialogCopyDocument') && dijit.byId('dialogCopyDocument').open) {
    var button = dijit.byId('dialogCopyDocumentSubmit');
  } else if (dijit.byId('dialogCopyProject')
      && dijit.byId('dialogCopyProject').open) {
    var button = dijit.byId('dialogProjectCopySubmit');
  } else if (dijit.byId('dialogAttachment')
      && dijit.byId('dialogAttachment').open) {
    var button = dijit.byId('dialogAttachmentSubmit');
  } else if (dijit.byId('dialogDocumentVersion')
      && dijit.byId('dialogDocumentVersion').open) {
    var button = dijit.byId('submitDocumentVersionUpload');
  } else if (dijit.byId('dialogAssignment')
      && dijit.byId('dialogAssignment').open) {
    var button = dijit.byId('dialogAssignmentSubmit');
  } else if (dijit.byId('dialogExpenseDetail')
      && dijit.byId('dialogExpenseDetail').open) {
    var button = dijit.byId('dialogExpenseDetailSubmit');
  } else if (dijit.byId('dialogPlan') && dijit.byId('dialogPlan').open) {
    var button = dijit.byId('dialogPlanSubmit');
  } else if (dijit.byId('dialogDependency')
      && dijit.byId('dialogDependency').open) {
    var button = dijit.byId('dialogDependencySubmit');
  } else if (dijit.byId('dialogResourceCost')
      && dijit.byId('dialogResourceCost').open) {
    var button = dijit.byId('dialogResourceCostSubmit');
  } else if (dijit.byId('dialogVersionProject')
      && dijit.byId('dialogVersionProject').open) {
    var button = dijit.byId('dialogVersionProjectSubmit');
  } else if (dijit.byId('dialogProductProject')
      && dijit.byId('dialogProductProject').open) {
    var button = dijit.byId('dialogProductProjectSubmit');
  } else if (dijit.byId('dialogAffectation')
      && dijit.byId('dialogAffectation').open) {
    var button = dijit.byId('dialogAffectationSubmit');
  } else if (dijit.byId('dialogFilter') && dijit.byId('dialogFilter').open) {
    var button = dijit.byId('dialogFilterSubmit');
  } else if (dijit.byId('dialogBillLine') && dijit.byId('dialogBillLine').open) {
    var button = dijit.byId('dialogBillLineSubmit');
  } else if (dijit.byId('dialogMail') && dijit.byId('dialogMail').open) {
    var button = dijit.byId('dialogMailSubmit');
  } else if (dijit.byId('dialogChecklistDefinitionLine')
      && dijit.byId('dialogChecklistDefinitionLine').open) {
    var button = dijit.byId('dialogChecklistDefinitionLineSubmit');
  } else if (dijit.byId('dialogChecklist')
      && dijit.byId('dialogChecklist').open) {
    var button = dijit.byId('dialogChecklistSubmit');
  } else if (dijit.byId('dialogJobDefinition')
      && dijit.byId('dialogJobDefinition').open) {
    var button = dijit.byId('dialogJobDefinitionSubmit');
  } else if (dijit.byId('dialogJob')
      && dijit.byId('dialogJob').open) {
    var button = dijit.byId('dialogJobSubmit');
  } else if (dijit.byId('dialogCreationInfo')
      && dijit.byId('dialogCreationInfo').open) {
    var button = dijit.byId('dialogCreationInfoSubmit');
  } else if (dijit.byId('dialogJobInfo')
      && dijit.byId('dialogJobInfo').open) {
    var button = dijit.byId('dialogJobInfoSubmit');
  } else if (dijit.byId('dialogDispatchWork')
      && dijit.byId('dialogDispatchWork').open) {
    var button = dijit.byId('dialogDispatchWorkSubmit');
  } else if (dijit.byId('dialogExport') && dijit.byId('dialogExport').open) {
    var button = dijit.byId('dialogPrintSubmit');
  } else if (dijit.byId('dialogRestrictTypes')
      && dijit.byId('dialogRestrictTypes').open) {
    var button = dijit.byId('dialogRestrictTypesSubmit');
  } else if (dojo.byId("editDependencyDiv") && dojo.byId("editDependencyDiv").style.display=="block") {
    dojo.byId("dependencyRightClickSave").click();
    return;
  } else {
    dojo.query(".projeqtorDialogClass").forEach(
        function(node, index, nodelist) {
          var widgetName = node.id;
          if (node.widgetid)
            widgetName = node.widgetid;
          widget = dijit.byId(widgetName);
          if (widget && widget.open) {
            btName1 = "dialog" + widgetName.charAt(0).toUpperCase()
                + widgetName.substr(1) + "Submit";
            btName2 = widgetName + "Submit";
            if (dijit.byId(btName1)) {
              button = dijit.byId(btName1);
            } else if (dijit.byId(btName2)) {
              button = dijit.byId(btName2);
            }
          }
        });
  }
  if (!button) {
    var button = dijit.byId('saveButton');
  }
  if (!button) {
    button = dijit.byId('saveParameterButton');
  }
  if (!button) {
    button = dijit.byId('saveButtonMultiple');
  }
  // for(name in CKEDITOR.instances) { // Moved to saveObject() function
  // CKEDITOR.instances[name].updateElement();
  // }
  if (button && button.isFocusable()) {
    if (dojo.byId('formDiv'))
      formDivPosition = dojo.byId('formDiv').scrollPosition;
    button.focus(); // V5.1 : attention, may loose scroll position on formDiv
                    // (see above and below lines)
    if (dojo.byId('formDiv'))
      dojo.byId('formDiv').scrollPosition = formDivPosition;
    var id = button.get('id');
    setTimeout("dijit.byId('" + id + "').onClick();", 20);
  }
}

function getFirstDayOfWeek(week, year) {
  if (week >= 53) {
    var testDate = new Date(year, 11, 31);
  } else {
    var testDate = new Date(year, 0, 5 + (week - 1) * 7);
  }
  var day = testDate.getDate();
  var month = testDate.getMonth() + 1;
  var year = testDate.getFullYear();
  var testWeek = getWeek(day, month, year);

  while (testWeek >= week) {
    testDate.setDate(testDate.getDate() - 1);
    day = testDate.getDate();
    month = testDate.getMonth() + 1;
    year = testDate.getFullYear();
    testWeek = getWeek(day, month, year);
    if (testWeek > 10 && week == 1) {
      testWeek = 0;
    }
  }
  testDate.setDate(testDate.getDate() + 1);
  return testDate;
}

dateGetWeek = function(paramDate, dowOffset) {
  /*
   * getWeek() was developed by Nick Baicoianu at MeanFreePath:
   * http://www.meanfreepath.com
   */
  dowOffset = (dowOffset == null) ? 1 : dowOffset; // default dowOffset to 1
  // (ISO 8601)
  var newYear = new Date(paramDate.getFullYear(), 0, 1);
  var day = newYear.getDay() - dowOffset; // the day of week the year begins
  // on
  day = (day >= 0 ? day : day + 7);
  var daynum = Math.floor((paramDate.getTime() - newYear.getTime() - (paramDate
      .getTimezoneOffset() - newYear.getTimezoneOffset()) * 60000) / 86400000) + 1;
  var weeknum;
  // if the year starts before the middle of a week
  if (day < 4) {
    weeknum = Math.floor((daynum + day - 1) / 7) + 1;
    if (weeknum > 52) {
      nYear = new Date(paramDate.getFullYear() + 1, 0, 1);
      nday = nYear.getDay() - dowOffset;
      nday = nday >= 0 ? nday : nday + 7;
      /*
       * if the next year starts before the middle of the week, it is week #1 of
       * that year
       */
      weeknum = nday < 4 ? 1 : 53;
    }
  } else {
    weeknum = Math.floor((daynum + day - 1) / 7);
    if (weeknum > 52) {
      nYear = new Date(paramDate.getFullYear() + 1, 0, 1);
      nday = nYear.getDay() - dowOffset;
      nday = nday >= 0 ? nday : nday + 7;
      /*
       * if the next year starts before the middle of the week, it is week #1 of
       * that year
       */
      weeknum = nday < 4 ? 1 : 55;
    }
  }
  return weeknum;
};

function getWeek(day, month, year) {
  var paramDate = new Date(year, month - 1, day);
  return dateGetWeek(paramDate, 1);
}

function moveTask(source, destination) {
  var mode = 'before';
  dndSourceTable.sync();
  var nodeList = dndSourceTable.getAllNodes();
  for (i = 0; i < nodeList.length; i++) {
    if  (nodeList[i].id == source[0]) {
      mode = 'before';
      break;
    } else if (nodeList[i].id == destination) {
      mode = 'after';
      break;
    }
  }
  var url = '../tool/moveTask.php?from=' + source.join() + '&to=' + destination
      + '&mode=' + mode;
  loadContent(url, "planResultDiv", null, true, null);
  
}

function indentTask(way) {
  if (!dojo.byId("planResultDiv") || !dojo.byId('objectClass')
      || !dojo.byId('objectId')) {
    return;
  }
  if (checkFormChangeInProgress()) {
    showAlert(i18n('alertOngoingChange'));
    return;
  }
  objectClass = dojo.byId('objectClass').value;
  objectId = dojo.byId('objectId').value;
  var url = '../tool/indentTask.php?objectClass=' + objectClass + '&objectId='
      + objectId + '&way=' + way;
  loadContent(url, "planResultDiv", null, true, null);
}

var arrayCollapsed=[];
function saveCollapsed(scope) {
  if (waitingForReply == true)  return;
  if (!scope) {
    if (dijit.byId(scope)) {
      scope = dijit.byId(scope);
    } else {
      return;
    }
  }
  if (arrayCollapsed[scope] && arrayCollapsed[scope]=='true') { return; }
  saveCollapsed[scope]='true';
  dojo.xhrPost({
    url : "../tool/saveCollapsed.php?scope=" + scope + "&value=true",
    handleAs : "text",
    load : function(data, args) {
    }
  });
}

function saveExpanded(scope) {
  if (waitingForReply == true)
    return;
  if (!scope) {
    if (dijit.byId(scope)) {
      scope = dijit.byId(scope);
    } else {
      return;
    }
  }
  if (arrayCollapsed[scope] && arrayCollapsed[scope]=='false') { return; }
  saveCollapsed[scope]='false';
  dojo.xhrPost({
    url : "../tool/saveCollapsed.php?scope=" + scope + "&value=false",
    handleAs : "text",
    load : function(data, args) {
    }
  });
}

function togglePane(pane) {
  if (waitingForReply == true)
    return;
  titlepane = dijit.byId(pane);
  if (titlepane) {
    if (titlepane.get('open')) {
      saveExpanded(pane);
    } else {
      saveCollapsed(pane);
    }
  }

}
// *********************************************************************************
// IBAN KEY CALCULATOR
// *********************************************************************************
function calculateIbanKey() {
  var country = ibanFormater(dijit.byId('ibanCountry').get('value'));
  var bban = ibanFormater(dijit.byId('ibanBban').get('value'));
  var number = ibanConvertLetters(bban.toString() + country.toString()) + "00";
  var calculateKey = 0;
  var pos = 0;
  while (pos < number.length) {
    calculateKey = parseInt(calculateKey.toString() + number.substr(pos, 9), 10) % 97;
    pos += 9;
  }
  calculateKey = 98 - (calculateKey % 97);
  var key = (calculateKey < 10 ? "0" : "") + calculateKey.toString();
  dijit.byId('ibanKey').set('value', key);
}

function ibanFormater(text) {
  var text = (text == null ? "" : text.toString().toUpperCase());
  return text;
}

function ibanConvertLetters(text) {
  convertedText = "";
  for (i = 0; i < text.length; i++) {
    car = text.charAt(i);
    if (car > "9") {
      if (car >= "A" && car <= "Z") {
        convertedText += (car.charCodeAt(0) - 55).toString();
      }
    } else if (car >= "0") {
      convertedText += car;
    }
  }
  return convertedText;
}

function trim(myString, car) {
  if (!myString) {
    return myString;
  }
  ;
  myStringAsTring = myString + "";
  return myStringAsTring.replace(/^\s+/g, '').replace(/\s+$/g, '');
}
function trimTag(myString, car) {
  if (!myString) {
    return myString;
  }
  ;
  myStringAsTring = myString + "";
  return myStringAsTring.replace(/^</g, '').replace(/>$/g, '');
}

function moveMenuBar(way, duration) {
  if (!duration)
    duration = 150;
  if (!menuBarMove)
    return;
  var bar = dojo.byId('menubarContainer');
  left = parseInt(bar.style.left.substr(0, bar.style.left.length - 2), 10);
  width = parseInt(bar.style.width.substr(0, bar.style.width.length - 2), 10);
  var step = 56 * 1;
  if (way == 'left') {
    pos = left + step;
  }
  if (way == 'right') {
    pos = left - step;
  }
  if (pos > 0)
    pos = 0;
  if (way == 'right') {
    var visibleWidthRight = dojo.byId('menuBarRight').getBoundingClientRect().left;
    var visibleWidthLeft = dojo.byId('menuBarLeft').getBoundingClientRect().right;
    var visibleWidth = visibleWidthRight - visibleWidthLeft;
    if (visibleWidth - left > width) {
      moveMenuBarStop();
      return;
    }
  }
  dojo.fx.slideTo({
    duration : duration,
    node : bar,
    left : pos,
    easing : function(n) {
      return n;
    },
    onEnd : function() {
      duration -= 10;
      if (duration < 50)
        duration = 50;
      if (menuBarMove) {
        moveMenuBar(way, duration);
      }
      showHideMoveButtons();
    }
  }).play();
}
menuBarMove = false;
function moveMenuBarStop() {
  showHideMoveButtons();
  menuBarMove = false;
}

function isHtml5() {
  if (dojo.isIE && dojo.isIE <= 9) {
    return false;
  } else if (dojo.isFF && dojo.isFF < 4) {
    return false;
  } else {
    return true;
  }
}

function updateCommandTotal() {
  if (cancelRecursiveChange_OnGoingChange)
    return;
  cancelRecursiveChange_OnGoingChange = true;
  // Retrieve values used for calculation
  var untaxedAmount = dijit.byId("untaxedAmount").get("value");
  if (!untaxedAmount)
    untaxedAmount = 0;
  var taxPct = dijit.byId("taxPct").get("value");
  if (!taxPct)
    taxPct = 0;
  var addUntaxedAmount = dijit.byId("addUntaxedAmount").get("value");
  if (!addUntaxedAmount)
    addUntaxedAmount = 0;
  var initialWork = dijit.byId("initialWork").get("value");
  var addWork = dijit.byId("addWork").get("value");
  // Calculated values
  var taxAmount = Math.round(untaxedAmount * taxPct) / 100;
  var fullAmount = taxAmount + untaxedAmount;
  var addTaxAmount = Math.round(addUntaxedAmount * taxPct) / 100;
  var addFullAmount = addTaxAmount + addUntaxedAmount;
  var totalUntaxedAmount = untaxedAmount + addUntaxedAmount;
  var totalTaxAmount = taxAmount + addTaxAmount;
  var totalFullAmount = fullAmount + addFullAmount;
  var validatedWork = initialWork + addWork;
  // Set values to fields
  dijit.byId("taxAmount").set('value', taxAmount);
  dijit.byId("fullAmount").set('value', fullAmount);
  dijit.byId("addTaxAmount").set('value', addTaxAmount);
  dijit.byId("addFullAmount").set('value', addFullAmount);
  dijit.byId("totalUntaxedAmount").set('value', totalUntaxedAmount);
  dijit.byId("totalTaxAmount").set('value', totalTaxAmount);
  dijit.byId("totalFullAmount").set('value', totalFullAmount);
  dijit.byId("validatedWork").set('value', validatedWork);

  cancelRecursiveChange_OnGoingChange = false;
}
//gautier
function providerPaymentIdProviderBill() {
  var idBill=dijit.byId("idProviderBill").get("value");
  url='../tool/getSingleData.php?dataType=providerPayment&idBill='
    + idBill;
  dojo.xhrGet({
    url : url,
    handleAs : "text",
    load : function(data) {
      if(data){
        dijit.byId("paymentAmount").set("value",dojo.number.format(data));
      }
    }
  });
}
function providerPaymentIdProviderTerm() {
  var idTerm=dijit.byId("idProviderTerm").get("value");
  url='../tool/getSingleData.php?dataType=providerPayment&idTerm='
    + idTerm;
  dojo.xhrGet({
    url : url,
    handleAs : "text",
    load : function(data) {
      if(data){
        dijit.byId("paymentAmount").set("value",dojo.number.format(data));
      }
    }
  });
}

function updateFinancialTotal() {
  if (cancelRecursiveChange_OnGoingChange)
    return;
  cancelRecursiveChange_OnGoingChange = true;
  // Retrieve values used for calculation
  var untaxedAmount = dijit.byId("untaxedAmount").get("value");
  if (!untaxedAmount)
    untaxedAmount = 0;
  var taxPct = dijit.byId("taxPct").get("value");
  if (!taxPct)
    taxPct = 0;
  var discount=dijit.byId("discountAmount").get("value");
  var rate=dijit.byId("discountRate").get("value");
  if (!isNaN(discount)) {
    var rateNew=Math.round(10000*discount/untaxedAmount)/100;
    if (!isNaN(rate)) {
      if(Math.round(rate*100)!= Math.round(rateNew*100)){
        dijit.byId("discountRate").set("value",rateNew);
      }
    }else{
      dijit.byId("discountRate").set("value",rateNew);
    }
  }
  if (!discount){
    discount=0;
  }
  // Calculated values
  var taxAmount = Math.round(untaxedAmount * taxPct) / 100;
  var fullAmount = taxAmount + untaxedAmount;
  var totalUntaxedAmount = untaxedAmount - discount;
  var totalTaxAmount = Math.round(totalUntaxedAmount * taxPct) / 100;
  var totalFullAmount = totalUntaxedAmount + totalTaxAmount;
  // Set values to fields
  dijit.byId("taxAmount").set('value', taxAmount);
  dijit.byId("fullAmount").set('value', fullAmount);
  dijit.byId("totalUntaxedAmount").set('value', totalUntaxedAmount);
  dijit.byId("totalTaxAmount").set('value', totalTaxAmount);
  dijit.byId("totalFullAmount").set('value', totalFullAmount);
  setTimeout("cancelRecursiveChange_OnGoingChange = false;",50);
}
//end
function updateBillTotal() { // Also used for Qutation !!!
  if (cancelRecursiveChange_OnGoingChange)
    return;
  cancelRecursiveChange_OnGoingChange = true;
  // Retrieve values used for calculation
  var untaxedAmount = dijit.byId("untaxedAmount").get("value");
  if (!untaxedAmount)
    untaxedAmount = 0;
  var taxPct = dijit.byId("taxPct").get("value");
  if (!taxPct)
    taxPct = 0;
  // Calculated values
  var taxAmount = Math.round(untaxedAmount * taxPct) / 100;
  var fullAmount = taxAmount + untaxedAmount;
  // Set values to fields
  dijit.byId("taxAmount").set('value', taxAmount);
  dijit.byId("fullAmount").set('value', fullAmount);
  cancelRecursiveChange_OnGoingChange = false;
}

function copyDirectLinkUrl(scope) {
  dojo.byId('directLinkUrlDiv'+scope).style.display = 'block';
  dojo.byId('directLinkUrlDiv'+scope).select();
  setTimeout("dojo.byId('directLinkUrlDiv"+scope+"').style.display='none';", 5000);
  return false;
}

/*
 * function copyToClipboard(inElement) { if (inElement.createTextRange) { var
 * range = inElement.createTextRange(); if (range && BodyLoaded==1) {
 * range.execCommand('Copy'); } } else { var flashcopier = 'flashcopier';
 * if(!document.getElementById(flashcopier)) { var divholder =
 * document.createElement('div'); divholder.id = flashcopier;
 * document.body.appendChild(divholder); }
 * document.getElementById(flashcopier).innerHTML = ''; var divinfo = '<embed
 * src="_clipboard.swf" FlashVars="clipboard='+escape(inElement.value)+'"
 * width="0" height="0" type="application/x-shockwave-flash"></embed>';
 * document.getElementById(flashcopier).innerHTML = divinfo; } }
 */

function runWelcomeAnimation() {
  titleNode = dojo.byId("welcomeTitle");
  if (titleNode) {
    dojo.fadeOut({
      node : titleNode,
      duration : 500,
      onEnd : function() {
        var newleft = Math.floor((Math.random() * 60) - 30);
        var newtop = Math.floor((Math.random() * 80) + 10);
        dojo.byId("welcomeTitle").style.top = newtop + "%";
        dojo.byId("welcomeTitle").style.left = newleft + "%";
        dojo.fadeIn({
          node : titleNode,
          duration : 500,
          onEnd : function() {
            setTimeout("runWelcomeAnimation();", 100);
          }
        }).play();
      }
    }).play();

  }
}

function cryptData(data) {
  var arr = data.split(';');
  var crypto = arr[0];
  var userSalt = arr[1];
  var sessionSalt = arr[2];
  var pwd = dijit.byId('password').get('value');
  var login = dijit.byId('login').get('value');
  dojo.byId('hashStringLogin').value = Aes.Ctr.encrypt(login, sessionSalt, aesKeyLength);
  if (crypto == 'md5') {
    crypted = CryptoJS.MD5(pwd + userSalt);
    crypted = CryptoJS.MD5(crypted + sessionSalt);
    dojo.byId('hashStringPassword').value = crypted;
  } else if (crypto == 'sha256') {
    crypted = CryptoJS.SHA256(pwd + userSalt);
    crypted = CryptoJS.SHA256(crypted + sessionSalt);
    dojo.byId('hashStringPassword').value = crypted;
  } else {
    var crypted = Aes.Ctr.encrypt(pwd, sessionSalt, aesKeyLength);
    dojo.byId('hashStringPassword').value = crypted;
  }
}
var getHashTry = 0;
function connect(resetPassword) {
  showWait();
  dojo.byId('login').focus();
  dojo.byId('password').focus();
  changePassword = resetPassword;
  var urlCompl = "";
  if (resetPassword) {
    urlCompl = '?resetPassword=true';
  }
  if (!dojo.byId('isLoginPage')) {
    urlCompl = ((urlCompl == "") ? '?' : '&') + 'isLoginPage=true'; // Patch
                                                                    // (try) for
                                                                    // looping
                                                                    // connections
  }
  quitConfirmed = true;
  noDisconnect = true;
  var login = dijit.byId('login').get('value');
  // in cas login is included in main page, to be more fluent to move next
  var crypted = Aes.Ctr.encrypt(login, aesLoginHash, aesKeyLength);
  dojo.byId('login').focus();
  dojo.xhrGet({
    url : '../tool/getHash.php?username=' + encodeURIComponent(crypted),
    handleAs : "text",
    load : function(data) {
      if (data.substr(0, 5) == "ERROR") {
        showError(data.substr(5));
      } else if (data.substr(0, 7) == "SESSION") {
        getHashTry++;
        if (getHashTry > 1) {
          showError(i18n('errorSessionHash'));
          getHashTry = 0;
        } else {
          aesLoginHash = data.substring(7);
          connect(resetPassword);
        }
      } else {
        getHashTry = 0;
        cryptData(data);
        loadContent("../tool/loginCheck.php" + urlCompl, "loginResultDiv", "loginForm");
      }
    }
  });
}

function addNewItem(item) {
  dojo.byId('objectClass').value = item;
  dojo.byId('objectId').value = null;
  if (switchedMode) {
    setTimeout("hideList(null,true);", 1);
  }
  loadContent("objectDetail.php", "detailDiv", dojo.byId('listForm'));
  dijit.byId('planningNewItem').closeDropDown();
}

function startStopWork(action, type, id, start) {
  loadContent("../tool/startStopWork.php?action=" + action, "resultDiv",
      "objectForm", true);
  var now = new Date();
  var vars = new Array();
  if (start) {
    vars[0] = start;
  } else {
    vars[0] = now.getHours() + ':' + now.getMinutes();
  }
  var msg = '<div style="cursor:pointer" onClick="gotoElement(' + "'" + type
      + "'," + id + ');">' + type + ' #' + id + ' '
      + i18n("workStartedAt", vars) + '</div>';
  if (action == 'start') {
    dojo.byId("currentWorkDiv").innerHTML = msg;
    dojo.byId("currentWorkDiv").style.display = 'block';
    dojo.byId("statusBarInfoDiv").style.display = 'none';
  } else {
    dojo.byId("currentWorkDiv").innerHTML = "";
    dojo.byId("currentWorkDiv").style.display = 'none';
    dojo.byId("statusBarInfoDiv").style.display = 'block';
  }
}

function getBrowserLocaleDateFormatJs() {
  return browserLocaleDateFormatJs;
}

// For FF issue on CTRL+S and F1
// Fix proposed by CACCIA
function stopDef(e) {
  var inputs, index;

  inputs = document.getElementsByTagName('input');
  for (index = 0; index < inputs.length; ++index) {
    inputs[index].blur();
  }
  inputs = document.getElementsByClassName('dijitInlineEditBoxDisplayMode');
  for (index = 0; index < inputs.length; ++index) {
    inputs[index].blur();
  }
  if (e && e.preventDefault)
    e.preventDefault();
  else if (window.event && window.event.returnValue)
    window.eventReturnValue = false;
};
// End Fix

// Button Functions to simplify onClick
function newObject() {
  dojo.byId("newButton").blur();
  id = dojo.byId('objectId');
  if (id) {
    id.value = "";
    unselectAllRows("objectGrid");
    loadContent("objectDetail.php", "detailDiv", dojo.byId('listForm'));
  } else {
    showError(i18n("errorObjectId"));
  }
}

function saveObject() {
  if(dojo.byId('buttonDivCreationInfo')!=null){
    dojo.byId('buttonDivCreationInfo').innerHTML="";
    forceRefreshCreationInfo=true;
  }
  if (waitingForReply) {
    showInfo(i18n("alertOngoingQuery"));
    return true;
  }
  for (name in CKEDITOR.instances) { // Necessary to update CKEditor field
                                      // whith focus, otherwise changes are not
                                      // detected
    CKEDITOR.instances[name].updateElement();
  }
  dojo.byId("saveButton").blur();
  submitForm("../tool/saveObject.php", "resultDiv", "objectForm", true);
}

function onKeyDownFunction(event, field, editorFld) {
  var editorWidth = editorFld.domNode.offsetWidth;
  var screenWidth = document.body.getBoundingClientRect().width;
  var fullScreenEditor = (editorWidth > screenWidth * 0.9) ? true : false; // if
                                                                            // editor
                                                                            // is >
                                                                            // 90%
                                                                            // screen
                                                                            // width
                                                                            // :
                                                                            // editor
                                                                            // is
                                                                            // in
                                                                            // full
                                                                            // mode
  if (event.keyCode == 83
      && (navigator.platform.match("Mac") ? event.metaKey : event.ctrlKey)
      && !event.altKey) { // CTRL + S
    event.preventDefault();
    if (fullScreenEditor)
      return;
    if (top.dojo.isFF) {
      top.stopDef();
    }
    top.setTimeout("top.onKeyDownFunctionEditorSave();", 10);
  } else if (event.keyCode == 112) { // On F1
    event.preventDefault();
    if (fullScreenEditor)
      return;
    if (top.dojo.isFF) {
      top.stopDef();
    }
    top.showHelp();
  } else if (event.keyCode == 9 || event.keyCode == 27) { // Tab : prevent
    if (fullScreenEditor) {
      event.preventDefault();
      editorFld.toggle(); // Not existing function : block some unexpected
                          // resizing // KEEP THIS even if it logs an error in
                          // the console
    }
  } else {
    if (field == 'noteNoteEditor') {
      // nothing
    } else if (isEditingKey(event)) {
      formChanged();
    }
  }
}
function onKeyDownCkEditorFunction(event, editor) {
  var editorWidth = editor.document.$.body.offsetWidth;
  var screenWidth = top.document.body.getBoundingClientRect().width;
  var fullScreenEditor = (editorWidth > screenWidth * 0.9) ? true : false; // if
                                                                            // editor
                                                                            // is >
                                                                            // 90%
                                                                            // screen
                                                                            // width
                                                                            // :
                                                                            // editor
                                                                            // is
                                                                            // in
                                                                            // full
                                                                            // mode
  if (event.data.keyCode == CKEDITOR.CTRL + 83) { // CTRL + S
    event.cancel();
    /*if (fullScreenEditor)
      return;*/
    if (top.dojo.isFF) {
      top.stopDef();
    }
    top.setTimeout("top.onKeyDownFunctionEditorSave();", 10);
  } else if (event.data.keyCode == 112) { // On F1
    event.cancel();
    if (fullScreenEditor)
      return;
    if (top.dojo.isFF) {
      top.stopDef();
    }
    top.showHelp();
  }else if(event.data.keyCode==27){
    if(top.editorInFullScreen() && top.whichFullScreen!=-1){
      top.editorArray[whichFullScreen].execCommand('maximize');
    }
  } 
}

function cancelBothFullScreen(){
  if(top.editorInFullScreen() && top.whichFullScreen!=-1){
    top.editorArray[whichFullScreen].execCommand('maximize');
    dijit.byId("globalContainer").resize();
  }
  
}

function isEditingKey(evt) {
  if (evt.ctrlKey && (evt.keyCode == 65 || evt.keyCode == 67))
    return false; // Copy or Select All
  if (evt.keyCode == 8 || evt.keyCode == 13 || evt.keyCode == 32)
    return true;
  if (evt.keyCode <= 40 || evt.keyCode == 93 || evt.keyCode == 144)
    return false;
  if (evt.keyCode >= 112 && evt.keyCode <= 123)
    return false;
  return true;
}
function onKeyDownFunctionEditorSave() {
  if (dojo.byId('formDiv')) {
    formDivPosition = dojo.byId('formDiv').scrollTop;
    if (dijit.byId('id')) dijit.byId('id').focus();
    dojo.byId('formDiv').scrollTop = formDivPosition;
  }
  top.setTimeout("top.globalSave();", 20);
}

function editorBlur(fieldId, editorFld) {
  var editorWidth = editorFld.domNode.offsetWidth;
  var screenWidth = document.body.getBoundingClientRect().width;
  var fullScreenEditor = (editorWidth > screenWidth * 0.9) ? true : false; // if
                                                                            // editor
                                                                            // is >
                                                                            // 90%
                                                                            // screen
                                                                            // width
                                                                            // :
                                                                            // editor
                                                                            // is
                                                                            // in
                                                                            // full
                                                                            // mode
  top.dojo.byId(fieldId).value = editorFld.document.body.firstChild.innerHTML;
  if (fullScreenEditor) {
    editorFld.toggle(); // Not existing function : block some unexpected
                        // resizing // KEEP THIS even if it logs an error in the
                        // console
  }
  return 'OK';
}

var fullScreenTest = false;
var whichFullScreen=-1;
var isCk=false;
function editorInFullScreen() {
  fullScreenTest = false;
  whichFullScreen=-1;
  dojo.query(".dijitEditor").forEach(function(node, index, arr) {
    var editorWidth = node.offsetWidth;
    var screenWidth = document.body.getBoundingClientRect().width;
    var fullScreenEditor = (editorWidth > screenWidth * (0.8)) ? true : false;
    if (fullScreenEditor) {
      fullScreenTest = true;
    }
  });
  if(!fullScreenTest){
    var numEditor = 1;
    while (dojo.byId('ckeditor' + numEditor)) {
      if(typeof editorArray[numEditor] != 'undefined'){
        if(editorArray[numEditor].toolbar && editorArray[numEditor].toolbar[3] 
        && editorArray[numEditor].toolbar[3].items[1] && editorArray[numEditor].toolbar[3].items[1]._
        && editorArray[numEditor].toolbar[3].items[1]._.state==1){
          fullScreenTest=true;
          whichFullScreen=numEditor;
        }
      }
      numEditor++;
    }
  }
  return fullScreenTest;
}

function menuFilter(filter) {
  /*
   * dojo.query(".menuBarItem").forEach(function(node, index, arr){
   * console.debug(node.innerHTML); });
   */
  menuListAutoshow = false; // the combo will be closed
  var allCollection = dojo.query(".menuBarItem");
  var newCollection = dojo.query("." + filter);
  allCollection
      .fadeOut(
          {
            duration : 200,
            onEnd : function() {
              allCollection.style("display", "none");
              bar = dojo.byId('menubarContainer');
              bar.style.left = 0;
              dojo.byId("menubarContainer").style.width = (newCollection.length * 56)
                  + "px";
              dojo.byId("menuBarVisibleDiv").style.width = (newCollection.length * 56)
                  + "px";
              newCollection.style("display", "block");
              if (newCollection.length < 20) {
                newCollection.fadeIn({
                  duration : 200
                }).play();
              } else {
                newCollection.style("height", "35px");
                newCollection.style("opacity", "1");
              }
              showHideMoveButtons();
            }
          }).play();
  saveUserParameter('defaultMenu', filter);
}

function showHideMoveButtons() {
  var bar = dojo.byId('menubarContainer');
  left = parseInt(bar.style.left.substr(0, bar.style.left.length - 2), 10);
  width = parseInt(bar.style.width.substr(0, bar.style.width.length - 2), 10);
  dojo.byId('menuBarMoveLeft').style.display = (left == 0) ? 'none' : 'block';
  var visibleWidthRight = dojo.byId('menuBarRight').getBoundingClientRect().left;
  var visibleWidthLeft = dojo.byId('menuBarLeft').getBoundingClientRect().right;
  var visibleWidth = visibleWidthRight - visibleWidthLeft;
  dojo.byId('menuBarMoveRight').style.display = (visibleWidth - left > width) ? 'none'
      : 'block';
}

function getExtraRequiredFields() {
  dojo.xhrPost({
    url : "../tool/getExtraRequiredFields.php",
    form : dojo.byId('objectForm'),
    handleAs : "text",
    load : function(data) {
      dojo.query(".generalColClassNotRequired").forEach(function(domNode){
        var key=domNode.id.replace("widget_","");
        var widget=dijit.byId(key);
        if (dijit.byId(key)) {
          dojo.removeClass(dijit.byId(key).domNode, 'required');
          dijit.byId(key).set('required',false);
        } else if (dojo.byId(key + 'Editor')) {
          keyEditor = key + 'Editor';
          dojo.removeClass(dijit.byId(keyEditor).domNode, 'required');
        } else if (dojo.byId('cke_' + key)) {
          var ckeKey = 'cke_' + key;
          dojo.removeClass(ckeKey, 'input required');
        }
      });
      var obj = JSON.parse(data);
      for ( var key in obj) {
        if (dijit.byId(key)) {
          if (obj[key] == 'required') {
            // dijit.byId(key).set('class','input required');
            dojo.addClass(dijit.byId(key).domNode, 'required');
            dijit.byId(key).set('required',true);
          } else if (obj[key] == 'optional') {
            // dijit.byId(key).set('class','input');
            dojo.removeClass(dijit.byId(key).domNode, 'required');
            dijit.byId(key).set('required',false);
          }
        } else if (dojo.byId(key + 'Editor')) {
          keyEditor = key + 'Editor';
          if (obj[key] == 'required') {
            // dijit.byId(keyEditor).set('class','dijitInlineEditBoxDisplayMode
            // input required');
            dojo.addClass(dijit.byId(keyEditor).domNode, 'required');
          } else if (obj[key] == 'optional') {
            // dijit.byId(keyEditor).set('class','dijitInlineEditBoxDisplayMode
            // input');
            dojo.removeClass(dijit.byId(keyEditor).domNode, 'required');
          }
        } else if (dojo.byId('cke_' + key)) {
          var ckeKey = 'cke_editor_' + key;
          if (obj[key] == 'required') {
            dojo.query('.'+ckeKey).addClass('input required','');
          } else if (obj[key] == 'optional') {
            dojo.query('.'+ckeKey).removeClass('input required','');
          }
        }
      }
    }
  });
}
function getExtraHiddenFields(idType,idStatus,idProfile) {
  if (!idStatus) {
    if (dijit.byId('idStatus')) {
      idStatus=dijit.byId('idStatus').get('value');
    }
  }
  if (!idType) {
    if (dojo.byId('objectClass')) {
      var typeName='id'+dojo.byId('objectClass').value+'Type';
      if (dijit.byId(typeName)) {
        idType=dijit.byId(typeName).get('value');
      }
    }
  }
  dojo.xhrGet({
    url : "../tool/getExtraHiddenFields.php" + "?type=" + idType+"&status="+idStatus+"&profile="+idProfile
        + "&objectClass=" + dojo.byId('objectClass').value,
    handleAs : "text",
    load : function(data) {
      var obj = JSON.parse(data);
      dojo.query(".generalRowClass:not(.dijitTooltipData)").style("display", "table-row");
      dojo.query(".generalColClass:not(.dijitTooltipData)").style("display", "inline-block");
      for (key in obj) {
        dojo.query("." + obj[key] + "Class:not(.dijitTooltipData)").style("display", "none");
      }
    }
  });
}
function getExtraReadonlyFields(idType,idStatus,idProfile) {
  if (!idStatus) {
    if (dijit.byId('idStatus')) {
      idStatus=dijit.byId('idStatus').get('value');
    }
  }
  if (!idType) {
    if (dojo.byId('objectClass')) {
      var typeName='id'+dojo.byId('objectClass').value+'Type';
      if (dijit.byId(typeName)) {
        idType=dijit.byId(typeName).get('value');
      }
    }
  }
  dojo.xhrGet({
    url : "../tool/getExtraReadonlyFields.php" + "?type=" + idType+"&status="+idStatus+"&profile="+idProfile
        + "&objectClass=" + dojo.byId('objectClass').value,
    handleAs : "text",
    load : function(data) {
      var obj = JSON.parse(data);
      dojo.query(".generalColClassNotReadonly").forEach(function(domNode){
        var name=domNode.id.replace("widget_","");
        var widget=dijit.byId(name);
        if (widget) {
          widget.set('readOnly',false);
        }
      });
      for (key in obj) {
        dojo.query("." + obj[key] + "Class").forEach(function(domNode){
          var name=domNode.id.replace("widget_","");
          var widget=dijit.byId(name);
          if (widget) {
            widget.set('readOnly',true);
          }
        });
        //if (dijit.byId(obj[key])) dijit.byId(obj[key]).set('readOnly',true); // ("readonly", "true"); ?
      }
    }
  });
}
function intercepPointKey(obj, event) {
  event.preventDefault();
  setTimeout('replaceDecimalPoint("' + obj.id + '");', 1);
  return false;
}
function replaceDecimalPoint(field) {
  var dom = dojo.byId(field);
  dom.value = dom.value + browserLocaleDecimalSeparator;
}
function ckEditorReplaceAll() {
  var numEditor = 1;
  while (dojo.byId('ckeditor' + numEditor)) {
    var editorName = dojo.byId('ckeditor' + numEditor).value;
    ckEditorReplaceEditor(editorName, numEditor);
    numEditor++;
  }
}
var maxEditorHeight = Math.round(screen.height * 0.6);
var tempResizeCK = null;
var currentEditorIsNote=false;
var doNotTriggerResize=false;
function ckEditorReplaceEditor(editorName, numEditor) {
  var height = 200;
  doNotTriggerResize=true;
  if (dojo.byId("ckeditorHeight"+numEditor)) {
    height=dojo.byId("ckeditorHeight"+numEditor).value;
  }
  currentEditorIsNote=false;
  if (editorName == 'noteNote') {
    height = maxEditorHeight - 150;
    currentEditorIsNote=true;
  }
  var readOnly = false;
  if (dojo.byId('ckeditor' + numEditor + 'ReadOnly')
      && dojo.byId('ckeditor' + numEditor + 'ReadOnly').value == 'true') {
    readOnly = true;
  }
  autofocus = (editorName == 'noteNote') ? true : false;
  
  editorArray[numEditor] = CKEDITOR.replace(editorName, {
    customConfig : 'projeqtorConfig.js',
    filebrowserUploadUrl : '../tool/uploadImage.php',
    height : height,
    readOnly : readOnly,
    language : currentLocale,
    startupFocus : autofocus
  });
  if (editorName != 'noteNote') { // No formChanged for notes
    editorArray[numEditor].on('change', function(evt) {
      // evt.editor.updateElement();
      formChanged();
    });
  }
  editorArray[numEditor].on('blur', function(evt) { // Trigger after paster
                                                    // image : notificationShow,
                                                    // afterCommandExec,
                                                    // dialogShow
    evt.editor.updateElement();
    // formChanged();
  });
  //gautier
  editorArray[numEditor].on('resize', function(evt) {
    if(tempResizeCK){
      clearTimeout(tempResizeCK);
    }
    var CkHeight = this.ui.editor.container.$.clientHeight - 102;
    tempResizeCK = setTimeout("CKeEnd("+CkHeight+","+numEditor+");",500);
  }); 
  
  editorArray[numEditor].on('key', function(evt) {
    onKeyDownCkEditorFunction(evt, this);
  });
  editorArray[numEditor].on('instanceReady', function(evt) {
    if (dojo.hasClass(evt.editor.name, 'input required')) {
      dojo.query('.cke_editor_'+evt.editor.name).addClass('input required');
    }
  });
  doNotTriggerResize=false;
}
function CKeEnd(CkHeight,numEditor) {
  if (doNotTriggerResize) return;
  if (! dojo.byId('ckeditorObj'+numEditor)) return;
  var ckeObj = dojo.byId('ckeditorObj'+numEditor).value;
  ckeObj = 'ckeditorHeight'+ckeObj;
  saveDataToSession(ckeObj,CkHeight,true);
  tempResizeCK=null;
}

// Default Planning Mode
function setDefaultPlanningMode(typeValue) {
  dojo.xhrGet({
    url : '../tool/getSingleData.php?dataType=defaultPlanningMode&idType='
        + typeValue + "&objectClass=" + dojo.byId('objectClass').value,
    handleAs : "text",
    load : function(data) {
      var objClass = dojo.byId('objectClass').value;
      var planningMode = objClass + "PlanningElement_id" + objClass
          + "PlanningMode";
      dijit.byId(planningMode).set('value', data);
    }
  });
}

// BEGIN - ADD BY TABARY - NOTIFICATION SYSTEM
function readOnlyNotificationGenerateBeforeInMin(theTargetDate) {
    if (typeof(theTargetDate)=='undefined') {
        theTargetDate = dijit.byId('_spe_targetDateNotifiableField').getValue();
    }
    if (theTargetDate.substr(theTargetDate.length-8)!=="DateTime") {
        dijit.byId("notificationGenerateBeforeInMin").set("readOnly",true);
        dijit.byId("notificationGenerateBeforeInMin").setValue("");
    } else {
        everyChecked = dijit.byId("everyDay").checked + dijit.byId("everyWeek").checked + dijit.byId("everyMonth").checked + dijit.byId("everyYear").checked;
        genBefore = dijit.byId("notificationGenerateBefore").getValue();
        nbRepeatsBefore = dijit.byId("notificationNbRepeatsBefore").getValue();
        if ( everyChecked==0 && !(genBefore>0) && !(nbRepeatsBefore>0) ) {
            dijit.byId("notificationGenerateBeforeInMin").set("readOnly",false);
            dijit.byId("notificationGenerateBeforeInMin").setValue("");
            dijit.byId("notificationGenerateBefore").set("readOnly",true);
            dijit.byId("notificationGenerateBefore").setValue("");
            dijit.byId("notificationNbRepeatsBefore").set("readOnly",true);
            dijit.byId("notificationNbRepeatsBefore").setValue("");
        } else {
            dijit.byId("notificationGenerateBeforeInMin").set("readOnly",true);
            dijit.byId("notificationGenerateBeforeInMin").setValue("");            
            if (dijit.byId("everyDay").checked) {
                dijit.byId("notificationGenerateBefore").set("readOnly",true);
                dijit.byId("notificationGenerateBefore").setValue("");
        }
    }
}
}

function refreshTargetDateFieldNotification(notificationItemValue) {
    url='../tool/getDateFieldsNotifiable.php?idNotifiable='+ notificationItemValue;

    var selectTarget = "_spe_targetDateNotifiableField";
    var idSelectTarget = dijit.byId(selectTarget);
    idSelectTarget.removeOption(idSelectTarget.getOptions());
    dijit.byId(selectTarget).set('value','');
    dojo.xhrGet({
        url : url,
        handleAs : "text",
        load : function(data) {
            var obj = JSON.parse(data);
            if(data){
                first=true;
                for ( var key in obj) {
                    if (first===true) {
                        first=false;
                        readOnlyNotificationGenerateBeforeInMin(key);
                    }
                    var o = dojo.create("option", {label: obj[key], value: key});     
                    dijit.byId(selectTarget).addOption(o); 
                }
            }
            if (first==true) {
              var o = dojo.create("option", {label: i18n('noDataFound'), value: ' '});     
              dijit.byId(selectTarget).addOption(o); 
              //readOnlyNotificationGenerateBeforeInMin(' ');
            }
        }
    });
}

function refreshAllowedWordsForNotificationDefinition(notificationItemValue) {
        url='../tool/getAllowedWordsForNotificationDefinition.php?idNotifiable='+ notificationItemValue;
    var allowedWords = "_spe_allowedWords";
    var element = document.getElementById(allowedWords);
    if (typeof(element)==='undefined' || element==null) {return;}
    element.innerHTML = "";
    dojo.xhrGet({
        url : url,
        handleAs : "text",
        load : function(data) {
            if(data){
                var dataP = JSON.parse(data);
                element.innerHTML = dataP; 
            }
        }
    });
                }

function refreshAllowedReceiversForNotificationDefinition(notificationItemValue) {
        url='../tool/getAllowedReceiversForNotificationDefinition.php?idNotifiable='+ notificationItemValue;        
    var allowedReceivers = "_spe_allowedReceivers";
    var element = document.getElementById(allowedReceivers);
    if (typeof(element)==='undefined' || element==null) {return;}
    element.innerHTML="";
    dojo.xhrGet({
        url : url,
        handleAs : "text",
        load : function(data) {
            if(data){
                var dataP = JSON.parse(data);
                element.innerHTML = dataP; 
            }
        }
    });

}

function refreshListItemsInNotificationDefinition(idNotifiable, forReceivers) {
    url='../tool/getAFieldForAClassById.php?Class=Notifiable&field=notifiableItem&id=' + idNotifiable;
    dojo.xhrGet({
        url : url,
        handleAs : "text",
        load : function(notifItem) {
            if(notifItem){
                var notifiableItem = JSON.parse(notifItem);
                url='../tool/getListItemsForNotificationDefinition.php?notifiableItem='+notifiableItem+'&forReceivers='+forReceivers;
                dojo.xhrGet({
                    url : url,
                    handleAs : "text",
                    load : function(data) {
                        if(data){
                            var obj = JSON.parse(data);
                            if(forReceivers==="NO") {
                                var selectTarget = '_spe_listItemsTitle';
                                dijit.byId(selectTarget).removeOption(dijit.byId(selectTarget).getOptions());
                                dijit.byId(selectTarget).set('value','');
                                first = true;
                                for ( var key in obj) {
                                    var o = dojo.create("option", {label: obj[key], value: key});
                                    dijit.byId(selectTarget).addOption(o);
                                    if (first===true) {
                                        refreshListFieldsInNotificationDefinition(key, "Title");
                                        first=false;
                                    }
                                }
                                var selectTarget = '_spe_listItemsContent';
                                dijit.byId(selectTarget).removeOption(dijit.byId(selectTarget).getOptions());
                                dijit.byId(selectTarget).set('value','');
                                first = true;
                                for ( var key in obj) {
                                    var o = dojo.create("option", {label: obj[key], value: key});
                                    dijit.byId(selectTarget).addOption(o);
                                    if (first===true) {
                                        refreshListFieldsInNotificationDefinition(key, "Content");
                                        first=false;
                                    }
                                }
                                var selectTarget = '_spe_listItemsRule';
                                dijit.byId(selectTarget).removeOption(dijit.byId(selectTarget).getOptions());
                                dijit.byId(selectTarget).set('value','');
                                first = true;
                                for ( var key in obj) {
                                    var o = dojo.create("option", {label: obj[key], value: key});
                                    dijit.byId(selectTarget).addOption(o);
                                    if (first===true) {
                                        refreshListFieldsInNotificationDefinition(key, "Rule");
                                        first=false;
                                    }
                                }
                            } else {
                                var selectTarget = '_spe_listItemsReceiver';
                                dijit.byId(selectTarget).removeOption(dijit.byId(selectTarget).getOptions());
                                dijit.byId(selectTarget).set('value','');
                                first = true;
                                for ( var key in obj) {
                                    var o = dojo.create("option", {label: obj[key], value: key});     
                                    dijit.byId(selectTarget).addOption(o);
                                    if (first===true) {
                                        refreshListFieldsInNotificationDefinition(key, "Receiver");
                                        first=false;
                                    }
                                }                                
                            }
                        }
                    }
                });                
            }
        }
    });   
}

function refreshListFieldsInNotificationDefinition(table, context) {
    url='../tool/getListFieldsForNotificationDefinition.php?table='+ table + '&context=' + context;

    var selectTarget = '_spe_listFields' + context;
    dijit.byId(selectTarget).removeOption(dijit.byId(selectTarget).getOptions());
    dijit.byId(selectTarget).set('value','');
    dojo.xhrGet({
        url : url,
        handleAs : "text",
        load : function(data) {
            if(data){
                var obj = JSON.parse(data);
                for ( var key in obj) {
                    var o = dojo.create("option", {label: obj[key], value: key});     
                    dijit.byId(selectTarget).addOption(o); 
                }
            }
        }
    });
}

function addFieldInTextBoxForNotificationItem(context, textBox, editor) {
                
    var selectItems = '_spe_listItems' + context;
    var selectedItemLabel = dijit.byId(selectItems).attr('displayedValue');
    var selectedItem = dijit.byId(selectItems).getValue();
    var selectFields = '_spe_listFields' + context;
    var selectedField = dijit.byId(selectFields).getValue();
    var selectedNotifiable = document.getElementById('idNotifiable').value;

    var idTextBox = dijit.byId(textBox);
    element = document.getElementById(textBox);
    if (editor==='text' || textBox!=='content') {
      var val = element.value;
      cursPos = val.slice(0, element.selectionStart).length;
    } else if (editor==='CK' || editor==='CKInline') {
      var val = CKEDITOR.instances[textBox].getData();
      cursPos = val.length;
    } else if (editor==='Dojo' || editor==='DojoInline') {
      var val = dijit.byId(textBox+'Editor').getValue();
      cursPos = val.length;
    }

    if (editor=='text' && textBox!=='content') {
        oldText = idTextBox.getValue();
    } else {
        oldText = val;
    }
    
    if (context === 'Receiver') {
      if (oldText.length>0) {
        textToAdd=';';            
      } else {
        textToAdd='';
      }    
    } else {
        textToAdd = '${';
    }    
    if (selectedItemLabel!==selectedNotifiable) {
        textToAdd=textToAdd + 'id' + selectedItem + '.';
    }
    textToAdd=textToAdd + selectedField;
    if (context !== 'Receiver') {
        textToAdd=textToAdd + "}";            
    }
    if (context==="Receiver") {
        newText = oldText + textToAdd;
    } else {
        newText = oldText.substr(0, cursPos) + textToAdd + oldText.substr(cursPos);        
    }
    
    
    if (editor==='text' || textBox!=='content') {
        idTextBox.setValue(newText);
    } else if (editor==='CK' || editor==='CKInline') {
       CKEDITOR.instances[textBox].setData(newText);
    } else if (editor==='Dojo' || editor==='DojoInline') {    
       dijit.byId(textBox+'Editor').setValue(newText)
    }
}

function addOperatorOrFunctionInTextBoxForNotificationItem(textBox) {                
    var selectItems = '_spe_listOperatorsAndFunctionsRule';
    var selectedItem = dijit.byId(selectItems).getValue();

    oldText = dijit.byId(textBox).getValue();
    element = document.getElementById(textBox);
    var val = element.value;
    cursPos = val.slice(0, element.selectionStart).length;

    textToAdd=selectedItem;
    newText = oldText.substr(0, cursPos) + textToAdd + oldText.substr(cursPos);        
    dijit.byId(textBox).setValue(newText);
}

function setGenerateBeforeWhenNotificationDayBeforeChange(colValue) {
    isFixedDay = false;
    if ((dijit.byId('everyMonth').checked && dijit.byId('fixedDay').getValue()>0) ||
        (dijit.byId('everyYear').checked && dijit.byId('fixedMonthDay').getValue()>0)) {
        isFixedDay = true;   
    }
    if (colValue<0 || isFixedDay || dijit.byId('everyDay').checked ) {
        dijit.byId('notificationGenerateBefore').set('readOnly', true);
        dijit.byId('notificationGenerateBefore').setValue(null);
        dojo.addClass('notificationGenerateBefore', 'readonly');        
    } else {
        dijit.byId('notificationGenerateBefore').set('readOnly', false);
        dojo.removeClass('notificationGenerateBefore', 'readonly');                
    }
    
}

function setGenerateBeforeWhenFixedDayChange(colValue) {
    if (colValue>0 || colValue=="" || dijit.byId('notificationNbRepeatsBefore').getValue()<0) {
        dijit.byId('notificationGenerateBefore').set('readOnly', true);
        dijit.byId('notificationGenerateBefore').setValue(null);
        dojo.addClass('notificationGenerateBefore', 'readonly');        
    } else {
        dijit.byId('notificationGenerateBefore').set('readOnly', false);
        dojo.removeClass('notificationGenerateBefore', 'readonly');                
    }
}

function setFixedMonthDayAttributes(colName) {
    if (colName==='everyDay') {
        if(dijit.byId('everyDay').checked) {
            dijit.byId('everyWeek').set('checked', false);
            dijit.byId('everyMonth').set('checked', false);
            dijit.byId('everyYear').set('checked', false);
            dojo.byId('widget_fixedDay').style.display = 'none';
            dojo.byId('widget_fixedMonth').style.display = 'none';
            dojo.byId('widget_fixedMonthDay').style.display = 'none';
            dojo.addClass('_spe_targetDateNotifiableField', 'required');
            dijit.byId('fixedMonth').setValue(null);
            dijit.byId('fixedMonthDay').setValue(null);
            dijit.byId('fixedDay').setValue(null);
            dijit.byId('notificationGenerateBefore').set('readOnly', true);
            dijit.byId('notificationGenerateBefore').setValue(null);
            dojo.addClass('notificationGenerateBefore', 'readonly');
        }
    }

    if (colName==='everyWeek') {
        if(dijit.byId('everyWeek').checked) {
            dijit.byId('everyDay').set('checked', false);
            dijit.byId('everyMonth').set('checked', false);
            dijit.byId('everyYear').set('checked', false);
            dojo.byId('widget_fixedDay').style.display = 'none';
            dojo.byId('widget_fixedMonth').style.display = 'none';
            dojo.byId('widget_fixedMonthDay').style.display = 'none';
            dojo.addClass('_spe_targetDateNotifiableField', 'required');
            dijit.byId('fixedMonth').setValue(null);
            dijit.byId('fixedMonthDay').setValue(null);
            dijit.byId('fixedDay').setValue(null);
            dijit.byId('notificationGenerateBefore').set('readOnly', false);
            dojo.removeClass('notificationGenerateBefore', 'readonly');
        }
    }

    if (colName==='everyMonth') {
        if(dijit.byId('everyMonth').checked) {
            dijit.byId('everyDay').set('checked', false);
            dijit.byId('everyWeek').set('checked', false);
            dijit.byId('everyYear').set('checked', false);
            dojo.byId('widget_fixedDay').style.display = 'block';
            dojo.byId('widget_fixedMonth').style.display = 'none';
            dojo.byId('widget_fixedMonthDay').style.display = 'none';
            dojo.addClass('_spe_targetDateNotifiableField', 'required');
            dijit.byId('fixedMonth').setValue(null);
            dijit.byId('fixedMonthDay').setValue(null);
            if (dijit.byId('fixedDay').getValue()>0 || dijit.byId('fixedDay').getValue() == "" || dijit.byId('notificationNbRepeatsBefore').getValue()<0) {
                dijit.byId('notificationGenerateBefore').set('readOnly', true);
                dijit.byId('notificationGenerateBefore').setValue(null);
                dojo.addClass('notificationGenerateBefore', 'readonly');                                
            } else {
                dijit.byId('notificationGenerateBefore').set('readOnly', false);
                dojo.removeClass('notificationGenerateBefore', 'readonly');                
            }
        } else{
            dojo.byId('widget_fixedDay').style.display = 'none';
            dijit.byId('fixedDay').setValue(null);
        }
    }
    
    if (colName==='everyYear') {
        if(dijit.byId('everyYear').checked) {
            dijit.byId('everyDay').set('checked', false);
            dijit.byId('everyWeek').set('checked', false);
            dijit.byId('everyMonth').set('checked', false);            
            dojo.byId('widget_fixedDay').style.display = 'none';
            dojo.byId('widget_fixedMonth').style.display = 'block';
            dojo.byId('widget_fixedMonthDay').style.display = 'block';
            dijit.byId('fixedDay').setValue('');
            if (dijit.byId('fixedMonthDay').getValue()>0 || dijit.byId('fixedMonthDay').getValue()=="" || dijit.byId('notificationNbRepeatsBefore').getValue()<0) {
                dijit.byId('notificationGenerateBefore').set('readOnly', true);
                dijit.byId('notificationGenerateBefore').setValue(null);
                dojo.addClass('notificationGenerateBefore', 'readonly');                                
            } else {
                dijit.byId('notificationGenerateBefore').set('readOnly', false);
                dojo.removeClass('notificationGenerateBefore', 'readonly');                
            }
        } else{
            dojo.byId('widget_fixedMonth').style.display = 'none';
            dojo.byId('widget_fixedMonthDay').style.display = 'none';            
            dijit.byId('fixedMonth').setValue(null);
            dijit.byId('fixedMonthDay').setValue(null);
        }
    }
        
    if (!dijit.byId('everyDay').checked && !dijit.byId('everyWeek').checked && !dijit.byId('everyMonth').checked && !dijit.byId('everyYear').checked) {
       dijit.byId('notificationNbRepeatsBefore').set('readOnly', true); 
       dijit.byId('notificationNbRepeatsBefore').setValue(""); 
       dojo.addClass('notificationNbRepeatsBefore', 'readonly');
       dijit.byId('notificationGenerateBefore').set('readOnly', false);
       dojo.removeClass('notificationGenerateBefore', 'readonly');
    } else {
       dijit.byId('notificationNbRepeatsBefore').set('readOnly', false);         
       dojo.removeClass('notificationNbRepeatsBefore', 'readonly');
    }
    if (!dijit.byId('everyDay').checked && !dijit.byId('everyWeek').checked && !dijit.byId('everyMonth').checked && !dijit.byId('everyYear').checked) {
       dijit.byId('notificationNbRepeatsBefore').set('readOnly', true); 
       dijit.byId('notificationNbRepeatsBefore').setValue(""); 
       dojo.addClass('notificationNbRepeatsBefore', 'readonly');
       dijit.byId('notificationGenerateBefore').set('readOnly', false);
       dojo.removeClass('notificationGenerateBefore', 'readonly');
    } else {
       dijit.byId('notificationNbRepeatsBefore').set('readOnly', false);         
       dojo.removeClass('notificationNbRepeatsBefore', 'readonly');
}
}

function setDrawLikeFixedDayWhenFixedMonthChange(value, name) {
    var arrayMonth30 = new Array(4,6,9,11);
    var dLFixedDay='';
    if (name==='fixedMonth') {
        if (value===null || value<1 || value>12) {return;}
        var dLFixedDay = 'fixedMonthDay';
        var dayValue = dijit.byId(dLFixedDay).getValue();
        var monthValue = value;
    }
    if (name==='fixedMonthDay') {
        var dLFixedDay = name;
        var dayValue = value;
        var monthValue = dijit.byId('fixedMonth').getValue();        
    }
    
    if (dLFixedDay==='' || dayValue < 29) { return;}
    
    if (monthValue=== 2 && dayValue>28) {
        dijit.byId(dLFixedDay).setValue(28);        
    }

    if (arrayMonth30.includes(monthValue) && dayValue===31) {
        dijit.byId(dLFixedDay).setValue(30);
        return;
    } 
}

// END - ADD BY TABARY - NOTIFICATION SYSTEM
function setDefaultPriority(typeValue) {
  url='../tool/getSingleData.php?dataType=defaultPriority&idType='
    + typeValue + "&objectClass=" + dojo.byId('objectClass').value;
  dojo.xhrGet({
    url : url,
    handleAs : "text",
    load : function(data) {
      var objClass = dojo.byId('objectClass').value;
      var planningPriority = objClass + "PlanningElement_priority" ;
      if(data){
        dijit.byId(planningPriority).set('value', data);
      }
    }
  });
}

function setDefaultCategory(typeValue) {
  dojo.xhrGet({
    url : '../tool/getSingleData.php?dataType=defaultCategory&idType='
        + typeValue + "&objectClass=" + dojo.byId('objectClass').value ,
    handleAs : "text",
    load : function(data) {
      dijit.byId("idCategory").set('value', data);
    }
  });
}

function updateVersionName(sep) {
  var prd = '';
  if (dijit.byId("idComponent")) {
    prd = dijit.byId("idComponent").get("displayedValue");
  } else if (dijit.byId("idProduct")) {
    prd = dijit.byId("idProduct").get("displayedValue");
  }
  var num = dijit.byId("versionNumber").get("value");
  var result = prd + sep + num;
  dijit.byId("name").set("value", result);
}
// GALLERY
function runGallery() {
  loadContent("galleryShow.php", "detailGalleryDiv", "galleryForm", false);
}
function changeGalleryEntity() {
  loadContent("galleryParameters.php", "listGalleryDiv", "galleryForm", false);
}

function saveDataToSession(param, value, saveUserParameter, callBack) {
  var url="../tool/saveDataToSession.php";
  url+="?idData="+param;
  url+="&value="+value;
  if (saveUserParameter && (saveUserParameter==true || saveUserParameter=='true' || saveUserParameter==1)) { 
    url+="&saveUserParam=true";
  }
  dojo.xhrPost({
    url : url,
    load : function(data, args) {
      if(callBack){
    	setTimeout(callBack, 10);
      }
    },
    error : function () {
      consoleTraceLog("error saving data to session param="+param+", value="+value+", saveUserParameter="+saveUserParameter);
    }
 });
}

function showExtraButtons(location) {
  var btnNode=dojo.byId(location);
  var divNode=dojo.byId(location+'Div');
  if (! divNode) return;
  if (divNode.style.display=='block') {
    divNode.style.display='none';
  } else {
    divNode.style.display='block';
    divNode.style.left=(btnNode.offsetLeft-5)+"px";
    var container=dojo.byId('buttonDiv');
    var positionner=dojo.byId('buttonDivContainerDiv');
    if (container) {
      var containerWidth=parseInt(container.style.width);
      var nodeWidth=parseInt(divNode.style.width);
      var nodeLeft=parseInt(divNode.style.left);
      var position=positionner.offsetLeft;
      if (nodeLeft+nodeWidth>containerWidth-position-5) {
        divNode.style.left=(containerWidth-position-nodeWidth-5)+"px";
      }
    }
  }
}
function hideExtraButtons(location) {
  var btnNode=dojo.byId(location);
  var divNode=dojo.byId(location+'Div');
  if (! divNode) return;
  if (divNode.style.display=='block') {
    divNode.style.display='none';
  }
}

//ADD qCazelles - Predefined Action
function loadPredefinedAction(editorType) {
	
	dojo.xhrPost({
		url : "../tool/getPredefinedAction.php?idPA=" + dijit.byId('listPredefinedActions').get("value"),
		handleAs : "text",
		load: function(data,args) {
			if (data) {
				var pa = JSON.parse(data);

				if (dijit.byId('name')) {
					dijit.byId('name').set('value', pa.name);
					dijit.byId('idActionType').set('value', pa.idActionType);
					dijit.byId('idProject').set('value', pa.idProject);
					dijit.byId('idPriority').set('value', pa.idPriority);
					dijit.byId('idContact').set('value', pa.idContact);
					dijit.byId('idResource').set('value', pa.idResource);
					dijit.byId('idEfficiency').set('value', pa.idEfficiency);
					
					if (pa.isPrivate == 1) {
						dijit.byId('isPrivate').set('checked', true);
					}

					dijit.byId('initialDueDate').set('value', null);
					if (Number(pa.initialDueDateDelay) != 0) {
						var myDate = new Date();
						myDate.setDate(myDate.getDate() + Number(pa.initialDueDateDelay));
						dijit.byId('initialDueDate').set('value', myDate);
					}

					dijit.byId('actualDueDate').set('value', null);
					if (Number(pa.actualDueDateDelay) != 0) {
						var myDateBis = myDate;
						myDateBis.setDate(myDateBis.getDate() + Number(pa.actualDueDateDelay));
						dijit.byId('actualDueDate').set('value', myDateBis);
					}

	   				if (editorType=="CK" || editorType=="CKInline") { // CKeditor type
	    				CKEDITOR.instances['description'].setData(pa.description);
						CKEDITOR.instances['result'].setData(pa.result);
					}
					else if (editorType=="text") {
	        			dijit.byId('description').set('value', pa.description);
						dijit.byId('result').set('value', pa.result);
	     			}
					else if (editorType=="Dojo") {   //NOT FUNCTIONNAL
						//dojo.byId('descriptionEditor').value = pa.description;
						//dojo.byId('dijitE').value = pa.description;
					}

				}
			}
		}
	});
}
//END ADD qCazelles - Predefined Action

function showDirectChangeStatus() {
  var divNode=dojo.byId('directChangeStatusDiv');
  if (! divNode) return;
  if (divNode.style.display=='block') {
    divNode.style.display='none';
  } else {
    divNode.style.display='block';
  }
}
function hideDirectChangeStatus() {
  var divNode=dojo.byId('directChangeStatusDiv');
  if (! divNode) return;
  if (divNode.style.display=='block') {
    divNode.style.display='none';
  }
}

function drawGraphStatus() {
  if (! dijit.byId("idStatus") || ! dijit.byId("idProject") || ! dojo.byId('objectClass') ) return;
  var callBack = function(){
    dojo.byId('graphStatusContentDiv');
  };
  graphIdStatus=dijit.byId("idStatus").get('value');
  graphIdProject=dijit.byId("idProject").get('value');
  objectClass=dojo.byId('objectClass').value;
  graphIdType=dijit.byId("id"+objectClass+"Type").get('value');
  var url = '../tool/dynamicDialogGraphStatus.php?idStatus='+graphIdStatus + '&idProject='+graphIdProject + '&idType='+graphIdType;
  loadContent(url,"graphStatusDiv",null,null,null,null,null,callBack);
  
}

function hideGraphStatus(){
  var divNode=dojo.byId("graphStatusContentDiv");
  if (divNode){
    divNode.style.display="none";
  }
}


function scrollInto(){
  var scrollElmnt = dojo.byId("scrollToBottom");
  if(scrollElmnt){
    dojo.window.scrollIntoView('scrollToBottom');
  }
}

// *************************************************************************
// Activity Stream 
// *************************************************************************

function saveNoteStream(event){
  var key = event.keyCode;
  if (key == 13 && !event.shiftKey) {
    var noteEditor = dijit.byId("noteNoteStream");
    var noteEditorContent=noteEditor.get("value");
    if (noteEditorContent.trim()=="") {
      noteEditor.focus();
      return;
    }
    loadContent("../tool/saveNoteStream.php", "resultDiv", "noteFormStream", true, 'note',null,null);
    noteEditor.set("value",null);
    event.preventDefault();
  } 
}

var menuRightDivSize=null;
function hideStreamMode(noRefresh){
  if (! dijit.byId('detailRightDiv')) return;
  if(dijit.byId("detailRightDiv").w != '0'){
    menuRightDivSize=dojo.byId("detailRightDiv").offsetWidth;
    dijit.byId("detailRightDiv").resize({
      w : 0
    });
    dijit.byId("centerDiv").resize();

  } else {
    if (! menuRightDivSize) menuRightDivSize=((dojo.byId("centerDiv").offsetWidth)*0.15);
    dijit.byId("detailRightDiv").resize({
      w : menuRightDivSize
    });
    dijit.byId("centerDiv").resize();
  }
  var detailHidden=false;
  if (dojo.byId('detailBarShow') && dojo.byId('detailBarShow').style.display=='block') detailHidden=true;
  if (!noRefresh && !formChangeInProgress && dojo.byId('id') && dojo.byId('id').value && !detailHidden) {
    setTimeout('loadContent("objectDetail.php", "detailDiv", "listForm");', 50);
  }
}

function focusStream() {
  if(dijit.byId("noteNoteStream") && dijit.byId("noteNoteStream").get('value')==trim(i18n("textareaEnterText"))){
    dijit.byId("noteNoteStream").set('value',"");
  }
  if(dijit.byId("noteStreamKanban") && dijit.byId("noteStreamKanban").get('value')==trim(i18n("textareaEnterText"))){
	dijit.byId("noteStreamKanban").set('value',"");
  }
}

function refreshActivityStreamList(){
  loadContent('activityStreamList.php', 'activityStreamListDiv','activityStreamForm');
}

function resetActivityStreamListParameters() {
  dojo.byId('activityStreamShowClosed').value=1;
  switchActivityStreamListShowClosed();
  dijit.byId("activityStreamAuthorFilter").set('value',null);
  dijit.byId("activityStreamTypeNote").set('value',null);
  dijit.byId("activityStreamIdNote").set('value',null);
  dijit.byId("activityStreamNumberDays").set('value','7'); 
  dijit.byId("activityStreamTeamFilter").set('value',null);
}

function switchActivityStreamListShowClosed() {
  var oldValue=dojo.byId('activityStreamShowClosed').value;
  if (oldValue==1) {
    dojo.byId('activityStreamShowClosed').value=0;
    dojo.byId('activityStreamShowClosedCheck').style.display='none';
  } else {
    dojo.byId('activityStreamShowClosed').value=1;
    dojo.byId('activityStreamShowClosedCheck').style.display='inline-block';
  }
  setTimeout("refreshActivityStreamList();", 100);
}

function switchActivityStreamListAddedRecently() {
  var oldValue=dojo.byId('activityStreamAddedRecently').value;
  if (oldValue=="added") {
    dojo.byId('activityStreamAddedRecently').value="";
    dojo.byId('activityStreamAddedRecentlyCheck').style.display='none';
  } else {
    dojo.byId('activityStreamAddedRecently').value="added";
    dojo.byId('activityStreamAddedRecentlyCheck').style.display='inline-block';
  }
  setTimeout("refreshActivityStreamList();", 100);
}

function switchActivityStreamListUpdatedRecently() {
  var oldValue=dojo.byId('activityStreamUpdatedRecently').value;
  if (oldValue=="updated") {
    dojo.byId('activityStreamUpdatedRecently').value="";
    dojo.byId('activityStreamUpdatedRecentlyCheck').style.display='none';
  } else {
    dojo.byId('activityStreamUpdatedRecently').value="updated";
    dojo.byId('activityStreamUpdatedRecentlyCheck').style.display='inline-block';
  }
}

function activityStreamTypeRead(){
  var typeNote = dijit.byId("activityStreamTypeNote").get('value');
  if(trim(typeNote) == ""){
    dijit.byId("activityStreamIdNote").set('value',null);
    dijit.byId("activityStreamIdNote").set('readOnly', true);
  } else {
    dijit.byId("activityStreamIdNote").set('readOnly', false);
  }
}

var notesHeight=[];
function switchNoteStatus(idNote) {
  var noteDiv=dojo.byId("activityStreamNoteContent_"+idNote);
  var status="closed";
  var img=dojo.byId('imgCollapse_'+idNote);
  if (!noteDiv.style.transition) {
    noteDiv.style.transition="all 0.5s ease";
    if (noteDiv.offsetHeight==0) {
      noteDiv.style.height="100px";
      noteDiv.style.maxHeight="100px";
      noteDiv.style.maxHeight="0px";
      noteDiv.style.height="0px";
      setTimeout("switchNoteStatus("+idNote+")",10);
      return;
    } else {
      noteDiv.style.maxHeight=(noteDiv.offsetHeight)+"px";
    }
  }
  if (noteDiv.style.height=='0px') {
    var newHeight=(idNote in notesHeight)?notesHeight[idNote]:"1000";
    noteDiv.style.maxHeight=newHeight+"px";
    noteDiv.style.height="100%";
    noteDiv.style.marginBottom="10px";
    status="open";
    dojo.query('#imgCollapse_'+idNote+' div').forEach(function(node, index, arr){
      node.className="iconButtonCollapseHide16";
    });
  } else {
    if (noteDiv.offsetHeight) notesHeight[idNote]=noteDiv.offsetHeight;
    noteDiv.style.maxHeight="0px";
    noteDiv.style.height="0px";
    noteDiv.style.marginBottom="0px";
    status="closed";
    dojo.query('#imgCollapse_'+idNote+' div').forEach(function(node, index, arr){
      node.className="iconButtonCollapseOpen16";
    });
  }
  url="../tool/saveClosedNote.php?idNote="+idNote+"&statusNote="+status;
  dojo.xhrPost({
    url : url,
    load : function(data, args) {
    },
    error : function () {
      consoleTraceLog("error saving note status : "+url);
    }
 });
  
}
function switchNotesPrivacyStream() {
  if (! dojo.byId("notePrivacyStream") || !dojo.byId("notePrivacyStreamUserTeam") || !dojo.byId("notePrivacyStreamDiv") ) {
    return;
  }
  var privacy=dojo.byId("notePrivacyStream").value;
  var team=dojo.byId("notePrivacyStreamUserTeam").value;
  if (privacy=="2") {
    dojo.byId("notePrivacyStream").value="3";
    dojo.byId("notePrivacyStreamDiv").className="iconFixed16";
    dojo.byId("notePrivacyStreamDiv").title=i18n("colIdPrivacy")+" : "+i18n("private");
  } else if (privacy=="3") {
    dojo.byId("notePrivacyStream").value="1";
    dojo.byId("notePrivacyStreamDiv").className="";
    dojo.byId("notePrivacyStreamDiv").title=i18n("colIdPrivacy")+" : "+i18n("public");
  } else {
    if (team) {
      dojo.byId("notePrivacyStream").value="2";
      dojo.byId("notePrivacyStreamDiv").className="iconTeam16";
      dojo.byId("notePrivacyStreamDiv").title=i18n("colIdPrivacy")+" : "+i18n("team");
    } else {
      dojo.byId("notePrivacyStream").value="3";
      dojo.byId("notePrivacyStreamDiv").className="iconFixed16";
      dojo.byId("notePrivacyStreamDiv").title=i18n("colIdPrivacy")+" : "+i18n("private");
    }
  }
  var currentClass=(dojo.byId('objectClass'))?dojo.byId('objectClass').value:'';
  saveDataToSession("privacyNotes"+currentClass,dojo.byId("notePrivacyStream").value,true);
}

function setAttributeOnTitlepane(pane,attr,height) {
  if (height) attr+='height:'+height+'px';
  dojo.byId(pane+'_titleBarNode').style=attr;
}

function redirectMobile(){
  redirectMobileFunction = function() {
    var url = "../mobile/";  
    window.location = url;
    quitConfirmed = true;
  };
  showConfirm(i18n('confirmRedirectionMobile'), redirectMobileFunction);
}

function displayImageEditMessageMail(code){
  //var codeParam = code.name;
  var iconMessageMail = dojo.byId(code+'_iconMessageMail');
  iconMessageMail.style.display = "inline-block";
}

function hideImageEditMessageMail(code){
  //var codeParam = code.name;
  var iconMessageMail = dojo.byId(code+'_iconMessageMail');
  iconMessageMail.style.display = "none";
}

var timeoutDirectSelectProject=null;
function directSelectProject() {
  var selected=null;
  var selectedName=null; 
  if (dojo.byId("objectClass") && dijit.byId("idProject") && dijit.byId("id")) {
    if (dojo.byId("objectClass").value=='Project' && dijit.byId("id").value) {
      selected=dijit.byId("id").get("value");
      selectedName=dijit.byId("name").get("value");
    } else {
      selected=dijit.byId("idProject").get("value");
      selectedName=dijit.byId("idProject").get("displayedValue");
    }
  }
  if (selected) {
    if (dojo.byId('projectSelectorMode') && dojo.byId('projectSelectorMode').value=='Standard') {
      setSelectedProject(selected,selectedName,'selectedProject');
    } else {
      dijit.byId("projectSelectorFiletering").set("value",selected);
    }
  } else {
    showAlert(i18n("noCurrentProject"));
  }
  timeoutDirectSelectProject=null;
  hideWait();
}
function directUnselectProject() {
  clearTimeout(timeoutDirectSelectProject);
  if (dojo.byId('projectSelectorMode') && dojo.byId('projectSelectorMode').value=='Standard') { 
    setSelectedProject('*',i18n('allProjects'),'selectedProject'); 
  } else { 
    dijit.byId('projectSelectorFiletering').set('value','*'); 
  }
  timeoutDirectSelectProject=null;
  hideWait();
}