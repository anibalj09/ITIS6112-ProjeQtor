<?php
/*
 * @author: qCazelles 
 */
require_once "../tool/projeqtor.php";
scriptLog('   ->/tool/jsonVersionsPlanning.php');

echo '{"identifier":"id", "items":[';

$pvsArray = array();
//CHANGE qCazelles - Correction GANTT - Ticket #100
//Old
// if (isset($_REQUEST['productVersionsListId'])) {
//   $pvsArray = $_REQUEST['productVersionsListId'];
// }
// else {
//   for ($i = 0; $i < $_REQUEST['nbPvs']; $i++) {
//     $pvsArray[$i] = $_REQUEST['pvNo'.$i];
//   }
// }
//New
if (isset($_REQUEST['productVersionsListId'])) {
  if ( strpos($_REQUEST['productVersionsListId'], '_')!==false) {
    $pvsArray=explode('_', $_REQUEST['productVersionsListId']);
  }
  else {
    $pvsArray[]=$_REQUEST['productVersionsListId'];
  }
}
//END CHANGE qCazelles - Correction GANTT - Ticket #100
else {
	for ($i = 0; $i < $_REQUEST['nbPvs']; $i++) {
		$pvsArray[$i] = $_REQUEST['pvNo'.$i];
	}
}
//$type = new Type();
//$componentTypeNoDisplay = $type->getSqlElementsFromCriteria(array('lockUseOnlyForCC'=>'1','scope'=>'ComponentVersion'));


foreach ($pvsArray as $idProductVersion) {
  $productVersion = new ProductVersion($idProductVersion);
  $productVersion->displayVersion();
  foreach (ProductVersionStructure::getComposition($productVersion->id) as $idComponentVersion) {
    $componentVersion = new ComponentVersion($idComponentVersion);
    //$cond=true;
		//foreach($componentTypeNoDisplay as $ctnd){
		//  if($componentVersion->idVersionType == $ctnd->id)
		//    $cond=false;  
		//}
    $hide=SqlList::getFieldFromId('ComponentVersionType', $componentVersion->idComponentVersionType, 'lockUseOnlyForCC');
    if ($hide!=1) $componentVersion->treatmentVersionPlanning($productVersion);
  }
}


echo ']}';
