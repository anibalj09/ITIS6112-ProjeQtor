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
 * Html specific functions
 */
require_once "../tool/projeqtor.php";
//DO NOT SCRIPT LOG : html.php is included in projeqtor.php, so for each script 
//scriptLog('   ->/tool/html.php');
/** ===========================================================================
 * Draw the options list for a select  
 * @param $col the name of the field, as idXxx. The table ref is then xxx.
 * @param $selection the value of the field, to be selected in the list
 * @param $obj optional - object for which list is generated
 * @param $required optional - indicates wether the list may present an empty value or not
 * @return void
 */
// CHANGE BY Marc TABARY - 2017-02-17
function htmlDrawOptionForReference($col, $selection, $obj=null, $required=false, $critFld=null, $critVal=null, $limitToActiveProjects=true, $limitToActiveOrganizations=true) { 
	scriptLog("      =>htmlDrawOptionForReference(col=$col,selection=$selection,object=" .debugDisplayObj($obj).",required=$required,critFld=".debugDisplayObj($critFld).",critVal=".debugDisplayObj($critVal).")");
  // Take into account array of $critFld // TODO : check where it is used 

// BEGIN - ADD BY TABARY - POSSIBILITY TO HAVE AT X TIMES SAME idXXXX IN THE SAME OBJECT
    $col = foreignKeyWithoutAlias($col);
// END - ADD BY TABARY - POSSIBILITY TO HAVE AT X TIMES SAME idXXXX IN THE SAME OBJECT
  $critArray=array();
  if (is_array($critFld) and is_array($critVal) and count($critFld)==count($critVal)) {
    foreach($critFld as $id=>$fld) {
      $critArray[$critFld[$id]]=$critVal[$id];
    }
  } 
    
  if (is_array($critFld)) {
	  foreach ($critFld as $tempId=>$tempCrt) {
	    $crtName='critFld'.$tempId;
	    $$crtName=$tempCrt;
	  }
	  $critFld=$critFld[0];
	}
	// Take into account array of $critVal // TODO : check where it is used
	if (is_array($critVal)) {
	  foreach ($critVal as $tempId=>$tempVal) {
	    $valName='critVal'.$tempId;
	    $$valName=$tempVal;
	  }
	  $critVal=$critVal[0];
	}
  if ($col=='planning') {
    $listType='Project';
  } else {
    $listType=substr($col,2);
  }
  if ($obj and $col=='id'.get_class($obj).'Type') {
    if ($critFld and $critVal) {
      $$critFld=$critVal;
    }
    $critFld=null;$critVal=null;
  }
	$column='name';
	$user=getSessionUser();
	if ($listType=='DocumentDirectory') {
		$column='location';
	}	
	// idProductOrComponent is an idProduct
  if ((substr($col,0,2)=='id' and substr($col,0,-7)=='Version') and ($critFld=='idProductOrComponent')) {
    $critFld='idProduct';
  }
  if ($col=='idProfile'){
    // Limit list of profiles to profiles with sortOrder >= sortOrder of user profile
    $idPrj = ($obj)?$obj->id:null;
    $user=new User();
    $prf = new Profile(getSessionUser()->getProfile($idPrj));
    $lstPrf=$prf->getSqlElementsFromCriteria(null,false,"idle=0 and ".(($prf->sortOrder)?'sortOrder>='.$prf->sortOrder:'1=1'),"sortOrder asc");
    $listPrf=array();
    foreach ($lstPrf as $profile) {
      $listPrf[$profile->id]=$profile->id;
    }
    $critFld='id';
    $critVal=$listPrf;
    // Attention, this case will then use standard process$table is not retreived yet)
  }
  if (($col=='idResource' or $col=='idResourceAll' or $col=='idAccountable' or $col=='idResponsible') and $critFld=='idProject') {
    // List of "affectable" with restriction to project : restrict on allocation to project (object Affectation)
  	$prj=new Project($critVal, true);
    $lstTopPrj=$prj->getTopProjectList(true);
    $in=transformValueListIntoInClause($lstTopPrj);
    $today=date('Y-m-d');
    $where="idProject in " . $in; 
    $where.=" and idle=0";
    //$where.=" and (startDate is null or startDate<='$today')";
    $where.=" and (endDate is null or endDate>='$today')";
    $aff=new Affectation();
    $list=$aff->getSqlElementsFromCriteria(null,null, $where);
    $nbRows=0;
    $table=array();
    if ($selection) {
       $table[$selection]=SqlList::getNameFromId('Affectable', $selection);
    }
    foreach ($list as $aff) {
      if (! array_key_exists($aff->idResource, $table)) {
        $id=$aff->idResource;
        $name=SqlList::getNameFromId(substr($col, 2), $id);
        //if ($name==$id and $col=='idResource') { // PBE V6.0 : this would insert users in Reosurce list (for instance responsible on Ticket)
        //	$name=SqlList::getNameFromId('User', $id);
        //}
        if ($name!=$id) {
          $table[$id]=$name;
        } 
      }
    }
    asort($table);
  } else if ($critFld and ($col=='idProductVersion' or $col=='idComponentVersion') and ($critFld=='idVersion' or $critFld=='idComponentVersion' or $critFld=='idProductVersion') ) {
    // Limit Versions depending on product structure
    $critClass=substr($critFld,2);
    $versionField=str_replace('Version', '', $critFld);
    $version=new Version($critVal,true);
    $critArray=array($versionField=>$version->idProduct);
    $list=SqlList::getListWithCrit('ProductStructure',$critArray,str_replace('Version', '',$col),$selection);
    $table=array();
    foreach ($list as $id) {
      $crit=array('idProduct'=>$id);
      $list=SqlList::getListWithCrit('Version',$crit);
      $table=array_merge_preserve_keys($table,$list);
    }  
    if ($selection) {
      $table[$selection]=SqlList::getNameFromId('Version', $selection);
    }
  } else if ($critFld and ! (($col=='idProduct' or $col=='idProductOrComponent' or $col=='idComponent') and $critFld=='idProject') ) {
    // Limit on criteria : this is main case for criteria selection
    // but not for Product and Component depending on Project (will be managed with restriction table)
    if (count($critArray)==0) $critArray=array($critFld=>$critVal);
    
    $limitPlanning=Parameter::getGlobalParameter('limitPlanningActivity');
    $class=null;
    if($obj!=null){
      $class=get_class($obj);
    }  
    if($listType=="Activity" and $class=='Ticket' and $limitPlanning=="YES"){
      $critArray['isPlanningActivity']=1;
    }    
    
    if ($col =='idTargetComponentVersion' and $obj and (get_class($obj)=='Activity') and $obj->idProduct == null and $obj->idComponent == null){
      $type = new Type();
      $componentVersionTypeDisplay = $type->getSqlElementsFromCriteria(array('lockUseOnlyForCC'=>'0','scope'=>'ComponentVersion'));
      $cpt=0;
      foreach ($componentVersionTypeDisplay as $cvtd){     
        $arrayType[$cpt] = $cvtd->id;
        $cpt+=1;
      }  
      $critArray['idVersionType'] = array_values($arrayType);        
    }
    
    $table=SqlList::getListWithCrit($listType,$critArray,$column,$selection);
    
    if ($selection) {
      $refTable=substr($col,2);
      if (substr($listType,-7)=='Version' and SqlElement::is_a($refTable, 'Version')) $refTable='Version';
      $table[$selection]=SqlList::getFieldFromId($refTable, $selection,$column);
    }
    if ($col=="idProject" or $col=="planning") { 
    	$wbsList=SqlList::getListWithCrit($listType,$critArray,'sortOrder',$selection);
    }
    if ($col=='idEmailTemplate') {
    	$mailable=($critVal0)?$critVal0:'0';
    	$type=($critVal1)?$critVal1:'0';
    	$crit=array('idMailable'=>$mailable,'idType'=>$type);
    	$list = SqlList::getListWithCrit ( "EmailTemplate", $crit, 'name', $selection, false );
    	$crit=array('idMailable'=>null);
    	$listAll = SqlList::getListWithCrit ( "EmailTemplate", $crit, 'name', $selection, false );
    	$crit=array('idMailable'=>$mailable, 'idType'=>null);
    	$listAllType = SqlList::getListWithCrit ( "EmailTemplate", $crit, 'name', $selection, false );
    	$table=array_merge_preserve_keys($list,$listAll,$listAllType);
    }
  } else if ($col=='idBill' or $col=='idProviderBill')  {
    // Limit Bills list to done but not paid (this isd used on Payment screen to list bills to link to the payment)
    if ($col=='idBill'){
      $crit=array('paymentDone'=>'0','done'=>'1');
    }else{
      $crit=array('done'=>'1');
    }
    $table=SqlList::getListWithCrit($listType, $crit,$column,$selection, (! $obj)?!$limitToActiveProjects:false);
  } else if ($col=='idLinkable' || $col=='idCopyable'){
    // Limit list of object to Link or to Copy to to objects visible to the user (depending on his access rights
    $typeRight='read';
    if($col=='idCopyable') $typeRight='create';
    $table=SqlList::getListNotTranslated($listType,$column,$selection, (! $obj)?!$limitToActiveProjects:false );
    $arrayToDel=array();
    foreach($table as $key => $val){
      $objTmp=new $val();
      if(property_exists($objTmp, "idProject") && $obj && property_exists($obj, "idProject")){
        $objTmp->idProject=$obj->idProject;
      }
      if(securityGetAccessRightYesNo('menu'.$val, $typeRight, $objTmp)=="NO" or !securityCheckDisplayMenu(null,$val))$arrayToDel[]=$key;
    }
    if ($col=='idLinkable' and $obj) {
    	foreach ($obj as $objFld=>$objVal) {
    		if (substr($objFld,0,6)=='_Link_') {
    			$clsLinked=substr($objFld,6);
    			$idLinked=SqlList::getIdFromTranslatableName('Linkable', $clsLinked);
    			$arrayToDel[]=$idLinked;
    		}
    	}
    }
    $table=SqlList::getList($listType,$column,$selection, (! $obj)?!$limitToActiveProjects:false );
    foreach($arrayToDel as $key)unset($table[$key]);
  } else if ($col=='idActivity' or $col=='idTicket') { 
    // List Activity or Ticket without a criteria // TODO : analyse effect of this... 
  	$cls=substr($col,2);
  	$table=SqlList::getList($cls,'name',$selection,false,true);
  } else if ($col=='idTestCase') { 
    // List Test case with criteria on project or visible product
    $table=SqlList::getList($listType,$column,$selection, (! $obj)?!$limitToActiveProjects:false,true );
  } else {
    // None of the previous cases : no criteria and not of the expected above cases
    $table=SqlList::getList($listType,$column,$selection, (! $obj)?!$limitToActiveProjects:false );
    if ($col=="idProject" or $col=="planning") { 
      // $wbsList will able to order list depending on WBS
    	$wbsList=SqlList::getList($listType,'sortOrder',$selection, (! $obj)?!$limitToActiveProjects:false );
    } 
    if ($col=="idOrganization") { 
      // Spmecificity for list of organisation // TODO : study if no other way is possible
    	$orgaList=SqlList::getList($listType,'sortOrder',$selection, (! $obj)?!$limitToActiveOrganizations:true );
    }
    if ($selection) {
      // Add selected value in the table // TODO : possibly move this after the closing } because it may be used in all cases (to be studied)
      $refTable=$listType;
      if (substr($listType,-7)=='Version' and SqlElement::is_a($refTable, 'Version')) $refTable='Version';
      $table[$selection]=SqlList::getFieldFromId($refTable, $selection,$column);
    } 
  }
  // Here $table is full with items to list
  $restrictArray=array();  // Prepare restriction array : if empty, no restriction will be applied, if not empty, will limit list the these items
  $excludeArray=array();   // Prepare exclusion array : all items in this list will not be listed
  if ($col=='idBaselineSelect') {
    $critWhere=  'idProject in ' . getVisibleProjectsList() ;
    $bl=new Baseline();
    $lstBaseline=$bl->getSqlElementsFromCriteria(null,null,$critWhere);
    foreach ($lstBaseline as $bl) {
      $restrictArray[$bl->id]=$bl->name;
    }
  }
  if ($obj) {
    // Current object is passed to the function, use it to apply restrictions or exclusions 
    // All lists froms objectDetail.php come here
  	$class=get_class($obj);
    if ( $class=='Project' and $col=="idProject" and $obj->id!=null) { 
      // on "is sub-project of", remove subprojects of current project and current project itself
      $excludeArray=$obj->getRecursiveSubProjectsFlatList();
      $excludeArray[$obj->id]=$obj->name;
    } 
    if ( $class=='Organization' and $col=="idOrganization" and $obj->id!=null) { 
      // on "is sub-organization of", remove suborganization of current organization and current organization itself
      $excludeArray=$obj->getRecursiveSubOrganizationsFlatList();
      $excludeArray[$obj->id]=$obj->name;
    }  
    if ($col=="idProject") {
      // On list of project, restrict list to the one the user has visibility, depending on access rights
    	$menuClass=$obj->getMenuClass();
    	if ($class=='DocumentDirectory') {
    	  // Document directory has no specific access rights, retreive them from Document
    		$doc=new Document();
    		$menuClass=$doc->getMenuClass();
    	}
      $controlRightsTable=$user->getAccessControlRights($obj);
      if (! array_key_exists($menuClass,$controlRightsTable)) {
	      // If AccessRight not defined for object and for user profile => empty list + log error
	      traceLog('error in htmlDrawOptionForReference : no control rights for ' . $class);
        return;		
	    }
      $controlRights=$controlRightsTable[$menuClass];    
      if ($obj->id==null) {
        // creation mode
        if ($controlRights["create"]!="ALL") {         
          $restrictArray=$user->getVisibleProjects();
          if (count($restrictArray)==0) { // If user is affected to no project, only possible value is 0 (never users)
            $restrictArray[0]=0;
          }
        }
      } else {
        // read or update mode
        if (securityGetAccessRightYesNo($menuClass, 'update', $obj)=="YES") {
          // update
          if ($controlRights["update"]=="PRO" or $controlRights["update"]=="OWN" or $controlRights["update"]=="RES") {
            $restrictArray=$user->getVisibleProjects();
          }            
        }
      }
      if (count($restrictArray) and $controlRights["create"]!="ALL") {
        foreach ($restrictArray as $idP=>$nameP) {
            $tmpAccessRight="NO";
            $tmpAccessRightList = $user->getAccessControlRights($idP);
            if (array_key_exists ( $menuClass, $tmpAccessRightList )) {
              $tmpAccessRightObj = $tmpAccessRightList [$menuClass];
              if (array_key_exists ( 'create', $tmpAccessRightObj )) {
                $tmpAccessRight = $tmpAccessRightObj ['create'];
              }
            }
            if ($tmpAccessRight!='ALL' and $tmpAccessRight!='PRO') {
              unset($restrictArray[$idP]);
            }
        }
        if (count($restrictArray)==0) { 
          $restrictArray[0]=0;
        }
      }
      if(Parameter::getUserParameter("restrictProjectList")=="true" and getSessionValue("project") and getSessionValue("project")!='*') {
        $class=get_class($obj);
        $proj = new Project(getSessionValue("project"));
        $lstChild = $proj->getRecursiveSubProjectsFlatList(true,true);
        if (count($restrictArray)>0) {
          $restrictArray=array_intersect_assoc($restrictArray,$lstChild);
        } else {
          $restrictArray=$lstChild;
        }
      }
      // end of $col=="idProject"
    } else if ($col=='idStatus') {
      // On list of Status, limit list depending on workflow
    	if ($class=='TicketSimple') $class='Ticket';        
      $idType='id' . $class . 'Type';
      $typeClass=$class . 'Type';
      if (property_exists($obj,$idType) ) {
      	reset($table);
        $firstKey=key($table);
        $firstName=current($table);
        // look for workflow
        if ($obj->$idType and $obj->idStatus) {
          $profile="";
          if (sessionUserExists()) {
            $profile=getSessionUser()->getProfile($obj);
          } 
          $type=new $typeClass($obj->$idType,true);
          if (property_exists($type,'idWorkflow') ) {
            $ws=new WorkflowStatus();
            $crit=array('idWorkflow'=>$type->idWorkflow, 'allowed'=>1, 'idProfile'=>$profile, 'idStatusFrom'=>$obj->idStatus);
            $wsList=$ws->getSqlElementsFromCriteria($crit, false);
            $compTable=array($obj->idStatus=>'ok');
            foreach ($wsList as $ws) {
              $compTable[$ws->idStatusTo]="ok";
            }
            $table=array_intersect_key($table,$compTable);
          }
        } else {
           $table=array($firstKey=>$firstName);
        }
      }
      if ($selection) {
        $selStatus=new Status($selection,true);
        if ($selStatus->isCopyStatus and isset($firstKey)) {
        	$table[$firstKey]=$firstName;
        }
      }
      // End $col=='idStatus'
    } else if (($col=='idProduct' or $col=='idComponent' or  $col=='idProductOrComponent') and $critFld=='idProject' and $critVal) {
      // Limit list of products and components depending on Project : list only items with version linked to the project
    	$restrictArray=array();
    	$versProj=new VersionProject();
    	$proj=new Project($critVal,true);
    	$lst=$proj->getTopProjectList(true);
    	$inClause='(0';
    	foreach ($lst as $prj) {
    	  if ($prj){
    	    $inClause.=',';
    	    $inClause.=$prj;
    	  }
    	}
    	$inClause.=')';
    	$versProjList=$versProj->getSqlElementsFromCriteria(null, false, 'idProject in '.$inClause);
    	if (count($versProjList)==0) $table=array();
    	    	
    	// hide automatically component depending of his type - Add mOlives - Ticket 178 - 17/05/2018
    	if ($col =='idComponent' and get_class($obj)=='Activity' and $obj->idProduct == null){
    	  $type = new Type();
    	  $componentTypeDisplay = $type->getSqlElementsFromCriteria(array('lockUseOnlyForCC'=>'0','scope'=>'Component'));
    	  
    	  foreach ($versProjList as $versProj) {
    	    $vers=new Version($versProj->idVersion,true);
    	    $comp = new Component($vers->idProduct);   	      	    
    	    foreach ($componentTypeDisplay as $filterType){
    	      if ($comp->idComponentType == $filterType->id)
    	        $restrictArray[$vers->idProduct]="OK";
    	    }
    	  }  	    
    	}
    	else{
    	  foreach ($versProjList as $versProj) {
    	    $vers=new Version($versProj->idVersion,true);
    	    $restrictArray[$vers->idProduct]="OK";
    	  }
    	}  	
    	//End mOlives - Ticket 178 - 17/05/2018
    	
    	// Add list of products  directly linked to project (not only through version)
    	$pp=new ProductProject();
    	$ppList=$pp->getSqlElementsFromCriteria(null, false, 'idProject in '.$inClause);
    	foreach ($ppList as $pp) {
    	  $restrictArray[$pp->idProduct]="OK";
    	}
    	if ($selection) {
    	  $table[$selection]=SqlList::getNameFromId(substr($col,2), $selection);
    	}
    	if (isset($restrictArray[$selection])) unset($restrictArray[$selection]);
    	// End ($col=='idProduct' or $col=='idComponent') and $critFld=='idProject'
    } else if ($col=='idComponent' and $critFld=='idProduct' and $critVal) {
      // Limit list of components depending on Product (only components linked to the product) 
      $prod=new Product($critVal,true);
      $table=$prod->getComposition(true,true);
      
     // hide automatically component depending of his type - Add mOlives - Ticket 178 - 17/05/2018
      if ($col =='idComponent' and (get_class($obj)=='Activity' or get_class($obj)=='Ticket') and $obj->idProduct != null){
        $type = new Type();
        $componentTypeNoDisplay = $type->getSqlElementsFromCriteria(array('lockUseOnlyForCC'=>'1','scope'=>'Component'));        
        foreach($table as $key => $val){
           $comp = new Component($key);
           foreach ($componentTypeNoDisplay as $ctnd){
             if ($comp->idComponentType == $ctnd->id )
               unset($table[$key]);       
           }
        }        
      }
      //End mOlives - Ticket 178 - 17/05/2018
      if ($selection) {
        $table[$selection]=SqlList::getNameFromId(substr($col,2), $selection);
      }
      // End $col=='idComponent' and $critFld=='idProduct'
    } else if (substr($col,-16)=='ComponentVersion' and $critFld=='idProductVersion' and $critVal) {
      // Limit Component version (target, source or else) depending on Product Version
      $prodVers=new ProductVersion($critVal,true);
      $table=$prodVers->getComposition(true,true);
      
      if (isset($critFld1) and isset($critVal1) and $critFld1=='idComponent') {
        $listVers=SqlList::getListWithCrit('ComponentVersion', array('idComponent'=>$critVal1));
        $table=array_intersect_assoc($table,$listVers);
      }
      
      if (get_class($obj) == 'Ticket'){
        $type = new Type();
        $componentTypeNoDisplay = $type->getSqlElementsFromCriteria(array('lockUseOnlyForCC'=>'1','scope'=>'ComponentVersion'));
        //// hide automatically component Version depending of his type - Add mOlives - Ticket 178 - 17/05/2018
        foreach ($table as $key => $val){
          $compVers = new ComponentVersion($key);
          foreach($componentTypeNoDisplay as $ctnd){
            if ($compVers->idComponentVersionType == $ctnd->id)
              unset($table[$key]); 
          }
        }    
      }
      
      if ($selection) {
        $table[$selection]=SqlList::getNameFromId('ComponentVersion', $selection);
      }
      // End Limit Component version (target, source or else) depending on Product Version
    } else if ($col=='id'.$class.'Type' and property_exists($obj, 'idProject')) {
      // List of type on the item, where item has project : 
      if (! isset($idProject)) {
        if ($obj->idProject and $class!='Project' ) {
          $idProject=$obj->idProject;
        } else {
          $idProject=0;
        }
      }
      if ($class=='Project') {
        if ($obj and $obj->id) $idProject=$obj->id;
        else $idProject=null;
      }
      $critFld=null;$critVal=null;
      $rtListProjectType=Type::listRestritedTypesForClass($class.'Type',$idProject, null,null);
      if (count($rtListProjectType)) {
        foreach($rtListProjectType as $id=>$idType) {
          $restrictArray[$idType]="OK";
        }
        if ($selection) {$restrictArray[$selection]="OK";}
      }
      // End List of type on the item, where item has project 
    }
    // End of $obj set
  } else { 
    // $obj not set. For lists not on Object context (on most cases combos)
  	if ($col=="idProject") {
  	  // Restrict list f project depending on user rights
      $user=getSessionUser();
      if (! $user->_accessControlVisibility) {
        $user->getAccessControlRights(); // Force setup of accessControlVisibility
      }      
      if ($user->_accessControlVisibility != 'ALL') {
      	$restrictArray=$user->getVisibleProjects($limitToActiveProjects);
  	  }
    } else if ($col=="planning") {
      // Restrict planning depending on user rights
      $user=getSessionUser();
      $restrictArray=$user->getListOfPlannableProjects();
    } else if (($col=="idProduct" or $col=="idProductOrComponent" or $col=="idComponent") and $critFld=='idProject') {
      // List of products with crit on project : propose empty value // TODO : identify why 
   		echo '<option value=" " ></option>';
    	return ;
    }
    // End of $obj not set
  }
  if ( ($col=='idResource'  or $col=='idResourceAll'  or $col=='idAccountable' or $col=='idResponsible') and Affectable::getVisibilityScope()!="all") {
    // Restrict List of affectables : restrict visibility (same Organization, or same Team or All)
    $restrictArray = getUserVisibleResourcesList(true);
    if ($selection) $restrictArray[$selection]="OK";
  }
  if (($col=='idResource' or $col=='idResourceAll') and $obj and get_class($obj)==='Organization') {
    // An organization's manager must belong to the organization (no cascade on parent organizations)
    // Get resources linked by id to organization
    $resourcesOfThisOrga = $obj->getResourcesOfOrganizationsListAsArray();
    $restrictArray = array_intersect_key($restrictArray, $resourcesOfThisOrga);
  }    
  if ($col=='idTargetProductVersion' or $col=='idOriginProductVersion') { //or $col=='idProductVersion'
    // Must restrict to versions visible to user
    $restrictArrayVersion=getSessionUser()->getVisibleVersions();
    if (isset($restrictArray) && count($restrictArray)>0) {
      $restrictArray=array_intersect_key($restrictArray, $restrictArrayVersion);
    } else {
      $restrictArray=$restrictArrayVersion;
    }
  } 
  if ($col=='idProduct' and $obj and get_class($obj)!='ProductVersion') {
    // Must restrict to products visible to user
    $restrictArrayProduct=getSessionUser()->getVisibleProducts();
    if (isset($restrictArray) && count($restrictArray)>0) {
      $restrictArray=array_intersect_key($restrictArray, $restrictArrayProduct);
    } else { 
      $restrictArray=$restrictArrayProduct;
    }
  }
  if ($col=='idOrganization' and Affectable::getOrganizationVisibilityScope()!="all") {
    // Restrict list of Organizations
    $restrictArray=array();
    $orga=new Organization();
    $scope=Affectable::getOrganizationVisibilityScope();
    if ($scope=='subOrga') {     // Can see his organization et sub-organization
      $crit="id in (". Organization::getUserOrganizationList().")";
    } else if ($scope=='orga') { // Can see only his organization
      $aff=new Affectable(getSessionUser()->id,true);
      $crit="id='$aff->idOrganization'";
    } else {
      traceLog("Error on htmlDrawOptionForReference() : Organization::getOrganizationVisibilityScope returned something different from 'all', 'subOrga', 'orga'");
      $crit=array('id'=>'0');
    }
    $list=$orga->getSqlElementsFromCriteria(null,false,$crit);
    foreach ($list as $orga) {
      $restrictArray[$orga->id]=$orga->name;
    }
    if ($selection) $restrictArray[$selection]="OK";    
  }
  
  // Add empty selectiopn if not required
  if (! $required) {
    echo '<option value=" " ></option>';
  }
  // For affectables, get the correct name
  if ($selection and ($col=='idResource' or $col=='idResourceAll' or $col=='idAccountable' or $col=='idResponsible') and (! isset($table[$selection]) or $table[$selection]==$selection) ) {
    $table[$selection]=SqlList::getNameFromId('Affectable', $selection);
  }
  // Sort array of classes
  if ($listType=='Linkable' or $listType=='Copyable' or $listType=='Importable' or $listType=='Mailable'
   or $listType=='Indicatorable' or $listType=='Checklistable' or $listType=='Dependable' or $listType=='Originable'
   or $listType=='Referencable' or $listType == 'Notifiable') {
    asort($table);
  }
  // Retreive the char to indent projects structure
  if ($col=="idProject" or $col=="planning") {
    $sepChar=Parameter::getUserParameter('projectIndentChar');
    if (!$sepChar) $sepChar='__';
    $wbsLevelArray=array();
  }
  // Retreive the char to indent organizations structure
  if ($col=="idOrganization") {
    $sepChar=Parameter::getUserParameter('projectIndentChar');
    if (!$sepChar) $sepChar='__';
    $orgaLevelArray=array();      
  }
  
  //class where language will be filtered if already selected ones, in others class, all languages will be proposed
  $classMultiLanguage =array("Component","Product","ProductVersion","ComponentVersion");
  // Exclude in list of languages and contexts the already selected ones
  if ( ($col=='idLanguage' or $col=='idContext') and $obj and in_array(get_class($obj),$classMultiLanguage)) {
    $crtProd='idProduct';
    if ($col=='idLanguage'){
      $productContext = new ProductLanguage();
      $fldCtx='idLanguage';
    } else if ($col=='idContext'){
      $productContext = new ProductContext();
      $fldCtx='idContext';
    }
    if (substr(get_class($obj),-7)=='Version') {
      $crtProd='idVersion';
      if ($col=='idLanguage'){
        $productContext = new VersionLanguage();
      } else if ($col=='idContext'){
        $productContext = new VersionContext();
      }
    }
    $listProductContext = $productContext->getSqlElementsFromCriteria(array($crtProd=>$obj->id));
    foreach ($listProductContext as $context){
      $excludeArray[$context->$fldCtx]=$context->$fldCtx;
    }
  }
  
  $pluginObjectClass=substr($col,2);
  $lstPluginEvt=Plugin::getEventScripts('list',$pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  if (! $obj and $col!="planning") $sepChar='no';
  $selectedFound=false;
  $next="";
  if (isset($table['*'])) unset($table['*']);
  foreach($table as $key => $val) {
    if (! array_key_exists($key, $excludeArray) and ( count($restrictArray)==0 or array_key_exists($key, $restrictArray) or $key==$selection) ) {
      if ( ($col=="idProject" or $col=="planning") and $sepChar!='no') {   
        if (isset($wbsList[$key])) {
          $wbs=$wbsList[$key];
        } else {
          $wbs='';
        }
        $wbsTest=$wbs;
        $level=1;
        while (strlen($wbsTest)>3) {
          $wbsTest=substr($wbsTest,0,strlen($wbsTest)-4);
          if (array_key_exists($wbsTest, $wbsLevelArray)) {
            $level=$wbsLevelArray[$wbsTest]+1;
            $wbsTest="";
          }
        }
        $wbsLevelArray[$wbs]=$level;
        $sep='';for ($i=1; $i<$level;$i++) {$sep.=$sepChar;}
        $val = $sep.$val;
      }

// ADD BY Marc TABARY - 2017-02-12 - ORGANIZATIONS COMBOBOX LIST
      if ($col=="idOrganization" and $sepChar!='no' and isset($orgaList[$key])) {   
        $orgOrder=$orgaList[$key];
        $orgTest=$orgOrder;
        $level=1;
        while (strlen($orgTest)>4) {
          $orgTest=substr($orgTest,0,strlen($orgTest)-5);
          if (array_key_exists($orgTest, $orgaLevelArray)) {
            $level=$orgaLevelArray[$orgTest]+1;
            $orgTest="";
          }
        }
        $orgaLevelArray[$orgOrder]=$level;
        $sep='';
        for ($i=1; $i<$level;$i++) {
            if (strpos($sepChar,'|')!==FALSE and $i<$level-1 and strlen($sepChar)>1) {
                $sepCharW = str_repeat('..', 2);
//                $sepCharW = str_replace('|', $sepChar[1], $sepChar);
            } else {$sepCharW = $sepChar;}
            $sep.=$sepCharW;
        }
                
        $val = $sep.$val;
      }      
// END ADD BY Marc TABARY - 2017-02-12 - ORGANIZATIONS COMBOBOX LIST

      if ($col=='idResource' or $col=='idResourceAll' or $col=='idAccountable' or $col=='idResponsible') {
      	if ($key==$user->id) {
      		$next=$key;
      	}
      } else if ($selectedFound) {
      	$selectedFound=false;
      	$next=$key;
      }
      echo '<option value="' . $key . '"';
      if ( $selection and $key==$selection ) { 
      	echo ' SELECTED ';
      	$selectedFound=true; 
      }
// BEGIN - CHANGE BY TABARY - NOTIFICATION SYSTEM
      if ($col=="idNotificationType" or $col=="idStatusNotification") {          
          echo '><span >'. htmlEncode(i18n($val)) . '</span></option>';
          if ($col=="idStatusNotification") {
              if ($selection==1) { $next=2; } else { $next=1;}
          }
      } else {
        echo '><span >'. htmlEncode($val) . '</span></option>';
      }
// END - CHANGE BY TABARY - NOTIFICATION SYSTEM      
    }
  }
  // This function is not expected to return value, but is used to return next value (for status)
  return $next;
}

function htmlReturnOptionForWeekdays($selection, $required=false) {
	$arrayWeekDay=array('1'=>'Monday', '2'=>'Tuesday', '3'=>'Wednesday', '4'=>'Thursday',
	                    '5'=>'Friday', '6'=>'Saturday', '7'=>'Sunday');
  $result="";
	if (! $required) {
    $result.='<option value=" " ></option>';
  }
  for ($key=1; $key<=7; $key++) {
    $result.= '<option value="' . $key . '"';
    if ( $selection and $key==$selection ) { $result.= ' SELECTED '; } 
    $result.= '>'. i18n($arrayWeekDay[$key]) . '</option>';
  }
  return $result;
}

function htmlReturnOptionForMonths($selection, $required=false) {
  $arrayMonth=array('1'=>'January', '2'=>'February', '3'=>'March', '4'=>'April',
                      '5'=>'May', '6'=>'June', '7'=>'July','8'=>'August',
                      '9'=>'September', '10'=>'October', '11'=>'November','12'=>'December');
  $result="";
  if (! $required) {
    $result.='<option value=" " ></option>';
  }
  for ($key=1; $key<=12; $key++) {
    $result.= '<option value="' . $key . '"';
    if ( $selection and $key==$selection ) { $result.= ' SELECTED '; } 
    $result.= '>'. i18n($arrayMonth[$key]) . '</option>';
  }
  return $result;
}
/** ===========================================================================
 * Display the info of the aplication (name, version) with link to website
 * @return void
 */
function htmlDisplayInfos() {
  global $copyright, $version, $website, $aboutMessage;
  echo "<a class='statusBar' target='#' href='$website' >$copyright $version&nbsp;</a>";
}

/** ===========================================================================
 * Display the info of the aplication (name, version) with link to website
 * @return void
 */
function htmlDisplayDatabaseInfos() {
  $paramDbName=Parameter::getGlobalParameter('paramDbName');
  $paramDbDisplayName=Parameter::getGlobalParameter('paramDbDisplayName');
  if (! $paramDbDisplayName) {
    $paramDbDisplayName=$paramDbName;
  }
  echo "<div style='text-align:center;'><b>$paramDbDisplayName</b></div>";
}

/** ===========================================================================
 * Display the message No object selected for the corresponding object,
 * translate using i18n
 * @param $className the class of the object
 * @return void
 */
function htmlGetNoDataMessage($className) {
    return '<br/><i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . i18n('messageNoData',array(i18n($className))) . '</i>';
}

function htmlGetNoAccessMessage($className) {
	return '<br/><i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . i18n('messageNoAccess',array(i18n($className))) . '</i>';
}

function htmlGetDeletedMessage($className) {
  return '<br/><i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . i18n('messageDeleted',array(i18n($className))) . '</i>';
}


/** ===========================================================================
 * Draw an html Table as cross reference
 * @param $lineObj the object class containing line data
 * @param $columnTable the table containing column data
 * @param $pivotTable the table containing pivot data (must contain id'ColumnTable' and id'LineTable'
 * @param $pivotValue the name of the field in pivot table containing pivot data
 * @param $format the format of data : check, text, label
 * @return void
 */
function htmlDrawCrossTable($lineObj, $lineProp, $columnObj, $colProp, $pivotObj, $pivotProp, $format='label', $formatList=null, $break=null) {
  global $collapsedList;
	if (is_array($lineObj)) {
    $lineList=$lineObj;
  } else {
    $lineList=SqlList::getList($lineObj);
  }
  // Filter on line (for instance will filter menu)
  if (is_array($lineObj)) {
    $pluginObjectClass='';
  } else {
    $pluginObjectClass=ucfirst($lineObj);
  }
  $table=$lineList;
  $lstPluginEvt=Plugin::getEventScripts('list',$pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $lineList=$table;
  // Filter on columns (for instance will filter profile)
  $columnList=SqlList::getList($columnObj);
  $pluginObjectClass=ucfirst($columnObj);
  $table=$columnList;
  $lstPluginEvt=Plugin::getEventScripts('list',$pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $columnList=$table;
  echo '<div style="width:98%; overflow-x:auto;  overflow-y:hidden;">';
  if ( ! ($break and ! is_array($lineObj)) ) {
	  echo '<table class="crossTable" >';
	  // Draw Header
	  echo '<tr><td>&nbsp;</td>';
	  foreach ($columnList as $col) {
	    echo '<td class="tabLabel">' . $col . '</td>';
	  }
	  echo '</tr>';
  }
  $breakVal='';
  $breakNum=0;
  foreach($lineList as $lineId => $lineName) {
  	if ($break and ! is_array($lineObj)) {
  		$class=ucfirst($lineObj);
  		$test=new $class($lineId,true);
  		if ($test->$break != $breakVal) {
  			$breakNum++;
  			$breakClass=substr($break,2);
  			$breakObj=new $breakClass($test->$break,true);
  			$breakName="";
  			if ($breakObj->name) {
  			  $breakName=(property_exists($breakObj,'_isNameTranslatable'))?i18n($breakObj->name):$breakObj->name;
  			} 
  			//echo '<tr><td class="tabLabel" style="text-align:left;border-top:2px solid #A0A0A0;">' . $breakName  . '</td>';
  			if ($test->$break) {
  			  $breakCode=$breakObj->name;
  			} else {
  				$breakCode=$breakNum;
  			} 
  			echo '</table></div><br/>';
        $divName='CrossTable_'.$lineObj.'_'.$breakCode;
        echo '<div id="' . $divName . '" dojoType="dijit.TitlePane"';
        echo ' open="' . (array_key_exists($divName, $collapsedList)?'false':'true') . '"';
        echo ' onHide="saveCollapsed(\'' . $divName . '\');"';
        echo ' onShow="saveExpanded(\'' . $divName . '\');"';
        echo ' title="' .$breakName . '"';
        echo ' style="width:98%; overflow-x:auto;  overflow-y:hidden;"';
        echo '>';
        echo '<table class="crossTable">';
        echo '<tr><td>&nbsp;</td>';
			  foreach ($columnList as $col) {
			    echo '<td class="tabLabel">' . $col . '</td>';
			  }
			  echo '</tr>';
        echo '<tr>';  			
  		}
  		$breakVal=$test->$break;
  	}
    echo '<tr><td class="crossTableLine"><label class="label largeLabel">' . $lineName . '</label></td>';
    foreach ($columnList as $colId => $colName) {
      $crit=array();
      $crit[$lineProp]=$lineId;
      $crit[$colProp]=$colId;
      $name=$pivotObj . "_" . $lineId . "_" . $colId;
      $class=ucfirst($pivotObj);
      $obj=SqlElement::getSingleSqlElementFromCriteria($class, $crit);
      $val=$obj->$pivotProp;
      echo '<td class="crossTablePivot">';
      switch ($format) {
        case 'check':
          $checked = ($val!='0' and ! $val==null) ? 'checked' : '';
          echo '<input dojoType="dijit.form.CheckBox" type="checkbox" ' . $checked . ' id="' . $name . '" name="' . $name . '" />'; 
          break;
        case 'text':
          echo '<input dojoType="dijit.form.TextBox id="' . $name . '" name="' . $name . '" type="text" class="input" style="width: 100px;" value="' . $val . '" />';
          break;
        case 'list':
          //echo '<input dojoType="dijit.form.TextBox id="' . $name . '" name="' . $name . '" type="text" class="input" style="width: 100px;" value="' . $val . '" />';
          echo '<select dojoType="dijit.form.FilteringSelect" class="input" '; 
          echo autoOpenFilteringSelect();
          echo ' style="width: 100px; font-size: 80%;"';
          echo ' id="' . $name . '" name="' . $name . '" ';
          echo ' >';
          htmlDrawOptionForReference('id' . $formatList, $val, null, true); 
          echo '</select>';
          break;  
        case 'label':
          echo $val;
          break;
      }
      echo '</td>';
    }
    echo '</tr>';
  }
  
  
  echo '</table></div>';
  
}

/** ===========================================================================
 * Get the data of a form table designed with htmlDrawCrossTable
 * @param $lineTable the table containing line data
 * @param $columnTable the table containing column data
 * @param $pivotTable the table containing pivot data (must contain id'ColumnTable' and id'LineTable'
 * @param $pivotValue the name of the field in pivot table containing pivot data
 * @param $format the format of data : check, text, label
 * @return an array containing 
 */
function htmlGetCrossTable($lineObj, $columnObj, $pivotObj) {
  if (is_array($lineObj)) {
    $lineList=$lineObj;
  } else {
    $lineList=SqlList::getList($lineObj);
  }
  // Filter on line (for menu)
  if (! is_array($lineObj)) {
    $pluginObjectClass=ucfirst($lineObj);
    $table=$lineList;
    $lstPluginEvt=Plugin::getEventScripts('list',$pluginObjectClass);
    foreach ($lstPluginEvt as $script) {
      require $script; // execute code
    }
    $lineList=$table;
  }
  $columnList=SqlList::getList($columnObj);
  // Filter on columns (for profile)
  $pluginObjectClass=ucfirst($columnObj);
  $table=$columnList;
  $lstPluginEvt=Plugin::getEventScripts('list',$pluginObjectClass);
  foreach ($lstPluginEvt as $script) {
    require $script; // execute code
  }
  $columnList=$table;
  $result=array();
  foreach($lineList as $lineId => $lineName) {
    foreach ($columnList as $colId => $colName) {
      $name=$pivotObj . "_" . $lineId . "_" . $colId;
      $val="";
      if (array_key_exists($name,$_REQUEST)) {
        $val=$_REQUEST[$name];
      }
      // Note: this needs an in-depth security review - seems to allow arbitrary manipulations of values (including access rights) by calls to saveParameter.php
      // TODO (SECURITY) : check validity of returned values
      $result[$lineId][$colId]=$val; 
    }
  }
  return $result;
}

/** ===========================================================================
 * Construct a Js table from a Php table (got from a database table)
 * @param $tableName name of database table containing data
 * @param $colName column name co,ntaining requested data in table
 * @return javascript creating an array 
 */
function htmlGetJsTable($tableName, $colName, $jsTableName=null) {
  $tab=SqlList::getList($tableName,$colName);
  $jsTableName=(! $jsTableName) ? 'tab'.ucfirst($tableName):$jsTableName;
  $script='var ' . $jsTableName . ' = [ ';
  $nb=0;
  foreach ($tab as $id=>$value) {
    $script .= (++$nb>1) ? ', ': '';
    $script .= ' { id: "' . $id . '", ' . $colName . ': "' . $value . '" } ';
  }
  $script.= ' ];';
  return $script;
}

/**
 * Format a date, depending on currentLocale
 * @param $val
 * @return unknown_type
 */
function htmlFormatDate($val,$trunc=false) {
  global $browserLocaleDateFormat;
  if (strlen($val)!=10) {
  	if (strlen($val)==19) {
  		if ($trunc) {
  			$val=substr($val,0,10);
  		} else {
  		  return htmlFormatDateTime($val);
  		}
  	} else {
      return $val;
  	}
  }
  $year=substr($val,0,4);
  $month=substr($val,5,2);
  $day=substr($val,8,2);
  $result=str_replace('YYYY', $year, $browserLocaleDateFormat);
  $result=str_replace('MM', $month, $result);
  $result=str_replace('DD', $day, $result);
  $result=str_replace('YY', substr($year,2,2), $result);
  return $result;
}

/**
 * Format a dateTime, depending on currentLocale
 * @param $val
 * @return unknown_type
 */
function htmlFormatDateTime($val, $withSecond=true, $hideZeroTime=false) {
  global $browserLocale;
  $locale=substr($browserLocale, 0,2);
  if (strlen($val)!=19 and strlen($val)!=16) {
    if (strlen($val)=="10") {
      return htmlFormatDate($val);
    } else {
      return $val;
    }
  }
  $result=htmlFormatDate(substr($val,0,10));
  if (! $hideZeroTime or substr($val,11,5)!='00:00') {
    $result.= " " . (($withSecond)?substr($val,11):substr($val,11,5));
  }
  if(getSessionValue('browserLocaleTimeFormat')=='h:mm a'){
    $result = htmlFormatDate(substr($val,0,10)) .' '. date('g:i A',strtotime($val));
  }
  return $result;
}
//gautier #time
function htmlFormatTime($val, $withSecond=true) {
  global $browserLocale;
  $locale=substr($browserLocale, 0,2);
  $result=(($withSecond)?$val:substr($val,0,5));
  if(getSessionValue('browserLocaleTimeFormat')=='h:mm a'){
    $result2 =   date('g:i A',strtotime($result));
    $result = $result2;
  }
  return $result;
}
/** ============================================================================
 * Transform string to be displays in html, pedending on context 
 * @param $context Printing context : 
 *   'print' : for printing purpose, also converts nl to <br> 
 *   'default' : default for conversion
 *   'none' : no convertion
 * @return string - the formated value 
 */
function htmlEncode($val,$context="default") {
  if ($context=='none') {
    return str_replace('"',"''",$val);
  } else if ($context=='print' or $context=='html') {
    return nl2br(htmlentities($val,ENT_COMPAT,'UTF-8'));
  } else if ($context=='withBR') {
    return nl2br(htmlspecialchars($val,ENT_QUOTES,'UTF-8'));
  } else if ($context=='mail') {
    $str=$val;
    if (get_magic_quotes_gpc()) {
      $str=str_replace('\"','"',$str);
      $str=str_replace("\'","'",$str);
      $str=str_replace('\\\\','\\',$str);
    }
    return nl2br(htmlentities($str,ENT_QUOTES,'UTF-8'));
  } else if ($context=='quotes') {
  	$str=str_replace("'"," ",$val);
  	$str=str_replace('"'," ",$str);
  	return $str;
  } else if ($context=='xml') {
  	$str=$val;
  	$str=str_replace("	"," ",$val);
  	return htmlspecialchars($str,ENT_QUOTES,'UTF-8');
  } else if ($context=="parameter") {
  	$str=str_replace('"',"''",$val);
  	return htmlspecialchars($str,ENT_QUOTES,'UTF-8');
  } else if ($context=="title") {
    //$str=$val;
    $str=br2nl($val);
    if (isTextFieldHtmlFormatted($str)) {
      $str=htmlspecialchars($str,ENT_QUOTES,'UTF-8');
    } else {
      $str=htmlspecialchars(htmlspecialchars($str,ENT_QUOTES,'UTF-8'),ENT_QUOTES,'UTF-8');
    }
    $str=str_replace( array("\r\n","\n","\r"), array('<br/>','<br/>','<br/>'),$str);
    return $str;
  } else if ($context=="formatted") { // For long text, html format must be preserved but <script> must be removed (Mandatory for Editor fields)
    // Step one : remove <script> tags
    $str = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $val);
    // Step two : if some dangerous scripting capacitites still exist : replace text by warning image
    $test=strtolower($str);
    if (strpos($test,'<script')!==false or strpos($test,'onmouseover')!==false) {
      $str='<img src="../view/img/error.png"/><br/>'.i18n('textHiddenForSecurity');
    }
    return $str;
  } else if ($context=="pdf") {
    $str=str_replace(array('</div>','</p>'),array('</div><br/>','</p><br/>'), $val);
    $str=strip_tags($str,'<br><br/><font><b>');
    return $str;
  } else if ($context=="stipAllTags") {
    //$str=str_replace(array('</div>','</p>',"\n"),array('</div><br/>','</p><br/>',''), $val);
    $str=strip_tags($val);
    //$str=str_replace('<br/>',"\n",$str);
    return $str;
  } else if ($context=='protectQuotes') {
    $str=str_replace(array("'",'"'), array('\\'."'",'\\'.'"'), $val);
    return htmlspecialchars($str,ENT_QUOTES,'UTF-8');    
  } else if ($context=='plainText') {
  	return nl2br(htmlentities(str_replace(array("\n",'<br>','<br/>','<br />'),array("","\n","\n","\n"),$val)));
  }
  return htmlspecialchars($val,ENT_QUOTES,'UTF-8');
}

/**
 * Remove all caracters that may lead to error on Json file rendering
 * @param $val
 * @return unknown_type
 */
function htmlEncodeJson($val, $numericLength=0) {
  //$val=htmlspecialchars($val,ENT_QUOTES,'UTF-8');
	/*$val = str_replace('&quot;',"''",$val);
  $val = str_replace("&#039;","'",$val);
  $val = str_replace("&amp;","&",$val);
  $val = str_replace("&lt;","<",$val);
  $val = str_replace("&gt;",">",$val);*/
  $val = str_replace("\\","\\\\",$val);
  $val = str_replace("\"","\\\"",$val);
  $val = str_replace("\n"," ",$val);	     
  $val = preg_replace('/[ ]{2,}|[\t]/', ' ', trim($val));
  
  if ($numericLength>0) {
    $val=str_pad($val,$numericLength,'0', STR_PAD_LEFT);
  }
  return $val;
}

/** ============================================================================
 * Return an error message formated as a resultDiv result
 * @param $message the message to display
 * @return formated html message, with corresponding html input
 */
function htmlGetErrorMessage($message) {
  $returnValue = '<div class="messageERROR" >' . $message . '</div>';
  $returnValue .= '<input type="hidden" id="lastSaveId" value="" />';
  $returnValue .= '<input type="hidden" id="lastOperation" value="control" />';
  $returnValue .= '<input type="hidden" id="lastOperationStatus" value="ERROR" />';
  return $returnValue;
}

/** ============================================================================
 * Return a warning message formated as a resultDiv result
 * @param $message the message to display
 * @return formated html message, with corresponding html input
 */
function htmlGetWarningMessage($message) {
  $returnValue = '<div class="messageWARNING" >' . $message . '</div>';
  $returnValue .= '<input type="hidden" id="lastSaveId" value="" />';
  $returnValue .= '<input type="hidden" id="lastOperation" value="control" />';
  $returnValue .= '<input type="hidden" id="lastOperationStatus" value="WARNING" />';
  return $returnValue;
}

/** ============================================================================
 * Return an mime/Type formated as an image
 * @param $mimeType the textual mimeType
 * @return formated html mimeType, as an image
 */
function htmlGetMimeType($mimeType,$fileName, $id=null, $type='Attachment') {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (file_exists("../view/img/mime/$ext.png")) {
      $img="../view/img/mime/$ext.png";
    } else {
      $img= "../view/img/mime/unknown.png";
    }
    $image='<img src="' . $img . '" title="' . $mimeType . '" ';
    //or $ext=='msg' or $ext=='emg'
    if ($id and ($ext=="htm" or $ext=="html" or $ext=="pdf" or $ext=="txt" or $ext=="log" )) {
    	$image.=' style="cursor:pointer;float:left;" onClick="showHtml(\''.$id.'\',\''.htmlEncode($fileName,'quotes').'\',\''.$type.'\')" ';
    }
    else {
      $image.=' style="float:left;opacity: 0.4;filter: alpha(opacity=40);" ';
    }
    $image.='/>&nbsp;';
  return $image;
}

/** ============================================================================
 * Return an fileSize formated as GB, MB KB or B 
 * @param $mimeType the textual mimeType
 * @return formated html mimeType, as an image
 */
function htmlGetFileSize($fileSize) {
  $nbDecimals=1;
  $limit=1000;
  if ($fileSize==null) {
    return '';
  }
  if ($fileSize<$limit) {
    return $fileSize . ' ' . i18n('byteLetter');
  } else {
    $fileSize=round($fileSize/1024,$nbDecimals);
    if ($fileSize<$limit) {
      return $fileSize . ' K' . i18n('byteLetter');
    } else {
      $fileSize=round($fileSize/1024,$nbDecimals);
      if ($fileSize<$limit) {
        return $fileSize . ' M' . i18n('byteLetter');
      } else {
        $fileSize=round($fileSize/1024,$nbDecimals);
        if ($fileSize<$limit) {
          return $fileSize . ' G' . i18n('byteLetter');
        } else {
          $fileSize=round($fileSize/1024,$nbDecimals);
          return $fileSize . ' T' . i18n('byteLetter');
        }      
      }
    }
  }
}

/**
 * Extract argument from condition
 * @param $tag String to extract from
 * @param $arg 
 * @return String
 */
function htmlExtractArgument($tag, $arg) {
  $sp=explode($arg . '=', $tag);
  $fld="";
  if (isset($sp[1])) {
    $fld=$sp[1];
    if (strpos($fld,' ')>1) {
      $fld=substr($fld,0,strpos($fld,' '));
    }
    if (strpos($fld,'>')>1) {
      $fld=substr($fld,0,strpos($fld,'>'));
    }
    $fld=trim($fld,'"');
  }
  return $fld;
}

/**
 * Display a, Array of filter criteria
 * @param $filterArray Array
 * @return Void
 */
function htmlDisplayFilterCriteria($filterArray, $filterName="") {
  // Display Result
  echo "<table width='99.9%'>";
  echo "<tr><td class='dialogLabel'>";
  echo '<label for="filterNameDisplay" >' . i18n("filterName") . '&nbsp;:&nbsp;</label>';
  echo '<div type="text" dojoType="dijit.form.ValidationTextBox" ';
  echo ' name="filterNameDisplay" id="filterNameDisplay"';
  echo '  style="width: 564px;" ';
  echo ' trim="true" maxlength="100" class="input" ';
  echo ' value="' . $filterName . '" ';
  echo ' >';
  echo '</td><td>';
  echo '<button title="' . i18n('saveFilter') . '" ';  
  echo ' dojoType="dijit.form.Button" '; 
  echo ' id="dialogFilterSave" name="dialogFilterSave" ';
  echo ' iconClass="dijitButtonIcon dijitButtonIconSave" showLabel="false"> ';
  echo ' <script type="dojo/connect" event="onClick" args="evt">saveFilter();</script>';
  echo '</button>';
  echo "</td></tr>";
  echo "<tr>";
  echo "<td class='filterHeader' style='width:525px;'>" . i18n("criteria") . "</td>";
  echo "<td class='filterHeader' style='width:25px;'>";
  echo ' <a src="css/images/smallButtonRemove.png" onClick="removefilterClause(\'all\');" title="' . i18n('removeAllFilters') . '" > ';
  echo formatSmallButton('Remove');
  echo ' </a>';
  echo "</td>";
  echo "</tr>";
  //ADD qCazelles - Dynamic filter - Ticket #78
  $nbDynamicFilterCriteria=0;
  $nbFilters=0;
  //END ADD qCazelles - Dynamic filter - Ticket #78
  $nbHiddenFilters=0;   //ADD qCazelles - Ticket 165
  if (count($filterArray)>0) { 
    foreach ($filterArray as $id=>$filter) {
      if (isset($filter['hidden']) and $filter['hidden']=='1') {    //ADD qCazelles - Ticket 165
        $nbHiddenFilters+=1;
        continue;
      }
      echo "<tr>";
      echo "<td class='filterData'>";
      //ADD qCazelles - Dynamic filter - Ticket #78
    if (!isset($filter['orOperator'])) $filter['orOperator']=0;
    if (!isset($filter['isDynamic'])) $filter['isDynamic']=0;
	  if ($filter['orOperator']=='1') {
      	echo i18n('OR').' ';
      }
      	elseif ($nbFilters==0) { //Nothing is displayed on the first criteria
       	$nbFilters+=1;
      }
      else {
      	echo i18n('AND').' ';
      }
      //END ADD qCazelles - Dynamic filter - Ticket #78
      echo $filter['disp']['attribute'] . " " .
           $filter['disp']['operator'] . " " .
           //CHANGE qCazelles - Dynamic filter - Ticket #78
           //Old
      	   //$filter['disp']['value'] .
           //New
           ($filter['isDynamic']=="1" ? '{'.i18n('dynamicValue').'}' : $filter['disp']['value']) .
           //END CHANGE qCazelles - Dynamic filter - Ticket #78
      	   "</td>";
      echo "<td class='filterData' style='text-align: center;'>";
      echo ' <a src="css/images/smallButtonRemove.png" onClick="removefilterClause(' . $id . ');" title="' . i18n('removeFilter') . '" > ';
      echo formatSmallButton('Remove');
      echo ' </a>';
      echo "</td>";
      echo "</tr>";
      //ADD qCazelles - Dynamic filter - Ticket #78
      if ($filter['isDynamic']=="1") {
      	$nbDynamicFilterCriteria+=1;
      }
      //END ADD qCazelles - Dynamic filter - Ticket #78
    }
  }
  //CHANGE qCazelles - Ticket 165
  //Old
  //else {
  //  echo "<tr><td class='filterData' colspan='2'><i>" . i18n("noFilterClause") . "</i></td></tr>";
  //}
  //echo "</table>";
  //echo '<input id="nbFilterCriteria" name="nbFilterCriteria" type="hidden" value="' . count($filterArray) . '" />';
  //New
  if (count($filterArray)==$nbHiddenFilters) {
    echo "<tr><td class='filterData' colspan='2'><i>" . i18n("noFilterClause") . "</i></td></tr>";
  }
  echo "</table>";
  echo '<input id="nbFilterCriteria" name="nbFilterCriteria" type="hidden" value="' . (count($filterArray) - $nbHiddenFilters) . '" />';
  //END CHANGE qCazelles - Ticket 165
  //ADD qCazelles - Dynamic filter - Ticket #78
  echo '<input id="nbDynamicFilterCriteria" name="nbDynamicFilterCriteria" type="hidden" value="'.$nbDynamicFilterCriteria.'" />';
  //END ADD qCazelles - Dynamic filter - Ticket #78
}

/**
 * Display a, Array of filter criteria
 * @param $filterArray Array
 * @return Void
 */
function htmlDisplayStoredFilter($filterArray,$filterObjectClass,$currentFilter="", $context="") {
  // Display Result
  $param=SqlElement::getSingleSqlElementFromCriteria('Parameter', 
       array('idUser'=>getSessionUser()->id, 'parameterCode'=>'Filter'.$filterObjectClass));
  $defaultFilter=($param)?$param->parameterValue:'';
  echo "<table width='100%'>";
  echo "<tr style='height:22px;'>";
  if ($context!='directFilterList') {
  	echo "<td class='filterHeader' style='width:699px;'>" . i18n("storedFilters") . "</td>";
    echo "<td class='filterHeader' style='width:25px;'>";
    echo "<td class='filterHeader' style='width:25px;'>";
  } else {
  	echo "<td class='filterHeader' style='font-size:8pt;width:300px;'>" . i18n("storedFilters") . "</td>";
  }
  echo "</td>";
  echo "</tr>";
  if ($context=='directFilterList') {
    echo "<tr>";
    echo '<td style="cursor:pointer;font-size:8pt;font-style:italic;padding:5px' 
           . '"' 
           . ' class="filterData" '
           . 'onClick="selectStoredFilter(\'0\',\'directFilterList\''.(array_key_exists("contentLoad", $_REQUEST) && array_key_exists("container", $_REQUEST) ? ',\''.$_REQUEST['contentLoad'].'\',\''.$_REQUEST['container'].'\'' : '').');" ' 
           . ' title="' . i18n("selectStoredFilter") . '" >'
           . i18n("noFilterClause")
           . "</td>";
    echo "</tr>";
  }
  if (count($filterArray)>0) {
    foreach ($filterArray as $filter) {
      echo "<tr>";
      //ADD qCazelles - Dynamic filter - Ticket #78
      echo ($filter->isDynamic=="1" ? '<input type="hidden" id="dynamicFilterId'.$filter->id.'" />' : ''); //Used for selection of stored filter, to enable the prompt of dynamic value(s)
      //END ADD qCazelles - Dynamic filter - Ticket #78
      echo '<td style="font-size:8pt;'. (($filter->name==$currentFilter and $context=='directFilterList')?'color:white; background-color: grey;':'cursor: pointer;') . '"' 
           . ' class="filterData" '
           //. ($filter->name==$currentFilter)?'':'onClick="selectStoredFilter('. "'" . htmlEncode($filter->id) . "'" . ');" ')
           . 'onClick="selectStoredFilter(\'' . htmlEncode($filter->id) . '\',\'' . htmlEncode($context) . '\''.(array_key_exists("contentLoad", $_REQUEST) && array_key_exists("container", $_REQUEST) ? ',\''.$_REQUEST['contentLoad'].'\',\''.$_REQUEST['container'].'\'' : '').');" ' 
           . ' title="' . i18n("selectStoredFilter") . '" >'
           . htmlEncode($filter->name)
           . ( ($defaultFilter==$filter->id and $context!='directFilterList')?' (' . i18n('defaultValue') . ')':'')
           . "</td>";
      if ($context!='directFilterList') {
        echo "<td class='filterData' style='text-align: center;'>";      
        echo ' <a src="css/images/smallButtonRemove.png" onClick="removeStoredFilter('. "'" . htmlEncode($filter->id) . "','" . htmlEncode(htmlEncode($filter->name)) . "'" . ');" title="' . i18n('removeStoredFilter') . '" > ';
        echo formatSmallButton('Remove');
        echo ' </a>';
        echo "</td>";
        echo "<td class='filterData' style='text-align: center;'>";
        if($filter->isShared==0)echo ' <img src="css/images/share.png" class="roundedButtonSmall" onClick="shareStoredFilter('. "'" . htmlEncode($filter->id) . "','" . htmlEncode(htmlEncode($filter->name)) . "'" . ');" title="' . i18n('shareStoredFilter') . '" class="smallButton"/> ';
        if($filter->isShared==1)echo ' <img src="css/images/shared.png" class="roundedButtonSmall" onClick="shareStoredFilter('. "'" . htmlEncode($filter->id) . "','" . htmlEncode(htmlEncode($filter->name)) . "'" . ');" title="' . i18n('unshareStoredFilter') . '" class="smallButton"/> ';
        echo "</td>";
      }
      
      echo "</tr>";
    }
  } else {
  	if ($context!='directFilterList') {
      echo "<tr><td class='filterData' colspan='3'><i>" . i18n("noStoredFilter") . "</i></td></tr>";
  	}
  }
  echo "</table>";

}

function htmlDisplaySharedFilter($filterArray,$filterObjectClass,$currentFilter="", $context="") {
  if (count($filterArray)>0) {  
    $nFilterArray=array();
    foreach ($filterArray as $filter) {
      $user=SqlElement::getSingleSqlElementFromCriteria("User", array("id"=>$filter->idUser));
      $cle=$user->name.'|'.$user->id;
      if(!isset($nFilterArray[$cle]))$nFilterArray[$cle]=array();
      $nFilterArray[$cle][$filter->name]=$filter;
      asort($nFilterArray[$cle]);
    }
    asort($nFilterArray);
    // Display Result
    $param=SqlElement::getSingleSqlElementFromCriteria('Parameter',
        array('idUser'=>getSessionUser()->id, 'parameterCode'=>'Filter'.$filterObjectClass));
    $defaultFilter=($param)?$param->parameterValue:'';
    echo '<div dojoType="dijit.form.DropDownButton"
                              style="width: 300px;margin:0 auto;"
                              id="filterSharedSelect" name="entity">';
    echo '<span>'.i18n("selectSharedFilter").'</span><div data-dojo-type="dijit/TooltipDialog">';
    $iterateur=0;
      foreach ($nFilterArray as $userName=>$filters) {
        $nameExplode=explode('|',$userName);
        echo '<span style="float:left;height:15px;font-weight:bold;" disabled="disabled" value="-2" '
            . ' title="' . i18n("selectStoredFilter") . '" >'.$nameExplode[0].'</span><br>';
        foreach ($filters as $filterName=>$filter) {
          echo '<span onclick="selectStoredFilter('.htmlEncode($filter->id).',\'' . htmlEncode($context) . '\');dijit.byId(\'filterSharedSelect\').closeDropDown();" class="menuTree" style="float:left;height:15px;" '
              . ' >&nbsp;&nbsp;&nbsp;&nbsp;'
                  . htmlEncode($filter->name)
                  . ( ($defaultFilter==$filter->id and $context!='directFilterList')?' (' . i18n('defaultValue') . ')':'')
                  . "</span><br>";
        }
        $iterateur++;
        if(sizeof($nFilterArray)>$iterateur)echo '<span style="float:left;height:15px;" value="-1" '
        . ' title="' . i18n("selectStoredFilter") . '" ></span><br>';
      }
    echo "</div></div>";
    echo "<span style='position:relative;left:20px;font-size:90%;color:#a0a0a0;'>".i18n("tipsSharedFilter").'</span>';
  }
}

// BEGIN - ADD BY TABARY - TOOLTIP
function htmlDisplayTooltip($value="", $colName="", $print=false, $outMode="") {
    if ($value=="" or $print or $outMode!="" or $colName=="") { return "";}
    return '<div class="generalColClass" dojoType="dijit.Tooltip" position="before" connectId="'.$colName.'">'. i18n($value).'</div>';
}
// END - ADD BY TABATY - TOOLTIP

function htmlDisplayCheckbox ($value, $remote=false) {
  $checkImg="checkedKO.png";
  if ($value!='0' and ! $value==null) {
    $checkImg= 'checkedOK.png';
  } 
  $baseUrl=($remote)?SqlElement::getBaseUrl().'/view/':'';
  return '<img src="'.$baseUrl.'img/' . $checkImg . '" />';
}

function htmlDisplayColored($value,$color) {
  global $print, $outMode;
  $result= "";
  $foreColor=htmlForeColorForBackgroundColor($color);
  //$result.= '<table><tr><td style="background-color:' . $color . '; color:' . $foreColor . ';">';
  //$result.= $value;
  //$result.= "</td></tr></table>";
  $result.='<div style="vertical-align:middle;padding: 2px 5px;border:1px solid #CCC;border-radius:10px;text-align: center;'
      .(($print and $outMode=='pdf')?'width:95%;min-height:18px;':'') 
      . 'background-color: ' . $color . '; color:' . $foreColor . ';">'
      .$value.'</div>';
  return $result;
}
function htmlDisplayColoredFull($value,$color) {
  global $print, $outMode;
  $result= "";
  $foreColor=htmlForeColorForBackgroundColor($color);
  $result.='<span style="display:block;vertical-align:middle;padding: 0px;text-align: center;'
      .(($print and $outMode=='pdf')?'width:10px;min-height:18px':'')
      . 'background-color: ' . $color . '; color:' . $foreColor . ';">'
          .$value.'</span>';
  return $result;
}

function htmlForeColorForBackgroundColor($color) {
  $foreColor='#000000';
  if (strlen($color)==7) {
    $red=base_convert(substr($color,1,2),16,10);
    $green=base_convert(substr($color,3,2),16,10);
    $blue=base_convert(substr($color,5,2),16,10);
    $light=(0.3)*$red + (0.6)*$green + (0.1)*$blue;
    if ($light<128) { $foreColor='#FFFFFF'; }
  }
  return $foreColor;
}

function htmlDisplayCurrency($val,$noDecimal=false) {
  if (! $val and $val!='0') return '';
  global $browserLocale;
  $currency=Parameter::getGlobalParameter('currency');
  $currencyPosition=Parameter::getGlobalParameter('currencyPosition');
  if ($noDecimal) {
    $fmt = new NumberFormatter52( $browserLocale, NumberFormatter52::INTEGER );
  } else {
    $fmt = new NumberFormatter52( $browserLocale, NumberFormatter52::DECIMAL );
  }
  if (! isset($currencyPosition) or ! isset($currency) or $currencyPosition=='none') {
    return $fmt->format($val) ;
  } 
  if ($currencyPosition=='after') {
    return str_replace(' ','&nbsp;',$fmt->format($val)) . '&nbsp;' . $currency; 
  } else {
    return $currency . '&nbsp;' . str_replace(' ','&nbsp;',$fmt->format($val)) ;
  }
}

function htmlDisplayNumeric($val) {
  global $browserLocale;
  // old version : too restrictive
  $fmt = new NumberFormatter52( $browserLocale, NumberFormatter52::DECIMAL );
  return $fmt->format($val) ;
  // numflt_* functions unvailable in some PHP versions, so keep old version
  //$fmt = numfmt_create( $browserLocale, NumberFormatter::DECIMAL );
  //$data = numfmt_format($fmt, $val);
  //return $data;
}

// ADD BY Marc TABARY - 2017-03-02 - DRAW SPINNER
function htmlDrawSpinner($col, $val, $spinnerAttributes, $attributes, $name, $title, $fieldWidth, $colScript) {
    // Default values of spinner
    $min=0;
    $max=100;
    $step=1;
    $bkColor='';
    
    // Take nobr
    $nobr = (strpos($attributes,'nobr')===false?false:true);
    
// BEGIN - ADD BY TABARY - TAKE DISPLAY ATTRIBUTE    
    $display = (strpos($attributes,'hidden')===false?($nobr?' display:inline-block':' display:block'):' display:none');
// END - ADD BY TABARY - TAKE DISPLAY ATTRIBUTE    
    // List of spinner Attributes
    $spinnerAttrList = explode(',',$spinnerAttributes);
    foreach($spinnerAttrList as $spinnerAttr) {
        $spinnerNameAndValue = explode(':', $spinnerAttr);
        if(count($spinnerNameAndValue)==2) {
            switch(strtolower($spinnerNameAndValue[0])) {
                case 'min' : 
                    $min=(intval($spinnerNameAndValue[1])?$spinnerNameAndValue[1]:0);
                    break;
                case 'max' :
                    $max=(intval($spinnerNameAndValue[1])?$spinnerNameAndValue[1]:0);
                    break;
                case 'step' :    
                    $step=(intval($spinnerNameAndValue[1])?$spinnerNameAndValue[1]:0);
                    break;
                case 'bkcolor' :
                    $bkColor=$spinnerNameAndValue[1];
                    break;
            }
        }
    }
    // min > max ==> invert
    if ($min>$max) {
        $temp=$max;
        $max=$min;
        $min=$temp;
    }
    // step > max ==> step = 1
    $step = ($step>$max?1:$step);
    
    // maxlength = length of max
    $maxlength = strlen(strval($max));
    
// ADD BY Marc TABARY - 2017-03-06 - DRAW SPINNER - ADD VALIDATION SCRIPT ON SPINNER'S EVENT 'Change'
    if (strpos($colScript,'event="onKeyDown"')!==false and strpos($colScript, 'isEditingKey(event)')!==false) {
        $colScript.= '"<script type="dojo/on" data-dojo-event="change" args="event">if (isEditingKey(event)) {formChanged();}</script>';
    }    
// END ADD BY Marc TABARY - 2017-03-06 - DRAW SPINNER - ADD VALIDATION SCRIPT ON SPINNER'S EVENT 'Change'
    // <div ...            
    $result=  '<div ';
    // Style
// BEGIN - ADD BY TABARY - TAKE DISPLAY ATTRIBUTE    
//    $result.=  'style="width:'.$fieldWidth.'px; text-align: center; color: #000000;" ';
    $result.=  'style="width:'.$fieldWidth.'px; text-align: center; color: #000000;'.$display.';" ';
// END - ADD BY TABARY - TAKE DISPLAY ATTRIBUTE        // dojoType
    $readOnly = (strpos($attributes,'readonly')===false?'':' readOnly');
    $result.= 'dojoType="dijit.form.NumberSpinner"'. $readOnly.' ';
    // Constraints
    $result.= 'constraints="{min:'.$min.',max:'.$max.',places:0,pattern:\'###0\'}" ';
    // Change and maxlength
    $result.= 'intermediateChanges="true" maxlength="'.$maxlength.'" ';
    // Class
    $required = (strpos($attributes,'required')!==false?'required':'');
    $result.= ' class="input '.$required.$readOnly.' generalColClass '.$col.'Class" ';    // Value
    $result.= ' value="'. $val.'" ';
    // Step
    $result.= 'smallDelta="'.$step.'" ';
    // id and name
    $result.= $name;
    // title
    $result.= $title; 
    // Close div
    $result.= '>';
    // Script
    $result.= $colScript;
    // </div>
    $result.= '</div>';
    // Pct
    if (substr($col,-3,3)=='Pct' or substr($col, -4, 4)=='Rate') {
        $result.= '<span class="generalColClass '.$col.'Class">%'.($bkColor==''?'&nbsp;':'').'</span>';
    }
    // Background Color            
    if ($bkColor!=='') {
        $result.= '<span style="display:inline-block;widht:50px;margin:0px 20px 0px 3px;background-color:'.$bkColor.'">&nbsp;&nbsp;&nbsp;&nbsp</span><span>&nbsp;</span>';        
    }
    
    return $result;
}
// END ADD BY Marc TABARY - 2017-03-02 - DRAW SPINNER
function htmlDisplayNumericWithoutTrailingZeros($val) {
  global $browserLocale;
  if ($val==0) return 0;
  $fmt = new NumberFormatter52( $browserLocale, NumberFormatter52::DECIMAL );
  $res=$val;
  if (strpos($res, '.')!==false) {
    $res=trim($res,'0');
  }
  if (substr($res, -1)=='.') {
    $res=trim($res,'.');
  }
  if ($res<1 and substr($res,0,1)=='.') $res='0'.$res;
  if ($fmt->decimalSeparator!='.') {
    $res=str_replace('.', $fmt->decimalSeparator, $res);
  }
  return $res ;
}

function htmlDisplayPct($val) {
  return htmlDisplayNumericWithoutTrailingZeros($val) . '&nbsp;%';
}

function htmlRemoveDocumentTags($val) {
  $res=strstr($val, '<body>');
  $res=str_replace(array('<html>','</html>','<body>','</body>') , '', $res);
  return $res;
}

function htmlDrawLink($obj, $display=null) {
	$canRead=securityGetAccessRightYesNo('menu' . get_class($obj), 'read', $obj)=="YES";
	$disp=htmlencode(($display)?$display:$obj->name);
	if ($canRead) {
	  $result='<a class="link" onClick="gotoElement(\'' . get_class($obj) .'\',\''. htmlEncode($obj->id) .'\');">' . $disp . '</a>';
	} else {
		$result=$disp;
	}  
	 
	return $result;
}

function htmlFixLengthNumeric($val, $numericLength=0) {  
  if ($numericLength>0) {
    $val=str_pad($val,$numericLength,'0', STR_PAD_LEFT);
  }
  return $val;
}

function htmlTransformRichtextToPlaintext($string) {
  $string=str_replace(array('</div>  <div>'),
                      array('</div><div>'),
                      $string);
  $string=str_replace(array('&nbsp;','<br /> ','<br>','<br/>'  ,'</div>'  ,'</p>'  ,'</tr>'),
                      array(' '     ,"\n"    ,"\n"  ,"\n","</div>\n","</p>\n","</tr>\n"),
                      $string);
  $string=strip_tags(html_entity_decode($string));
  return $string;
}