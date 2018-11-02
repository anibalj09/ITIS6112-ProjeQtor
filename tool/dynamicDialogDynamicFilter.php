<?php
/*
 *	@author: qCazelles 
 */
//Prompt the dynamic value of a filter when selected

require_once "../tool/projeqtor.php";

if (! array_key_exists('idFilter',$_REQUEST)) {
	throwError('Filter id not found in REQUEST');
}
$idFilter=$_REQUEST['idFilter'];
Security::checkValidId($idFilter);

$filter=new Filter($idFilter);
$filterObjectClass=RequestHandler::getValue('filterObjectClass');
if (!$filterObjectClass) $filterObjectClass=$filter->refType;
$objectClass=$filterObjectClass;
if ($objectClass=='Planning' or $objectClass=='Planning') $objectClass='Activity';
Security::checkValidClass($objectClass);
?>
<table xstyle="border: 1px solid grey;">

    <tr>
     <td class="section"><?php echo i18n('colFilter').' - '.$filter->name; ?></td>
    </tr>

    <tr>
      <td style="margin: 2px;"> 
        <div id='listDynamicFilterClauses' dojoType="dijit.layout.ContentPane" region="center" style="overflow: hidden"></div>
        <form id='dialogDynamicFilterForm' name='dialogDynamicFilterForm' onSubmit="return false;">
         <input type="hidden" id="idFilter" name="idFilter" value="<?php echo $filter->id;?>" />
         <input type="hidden" id="filterObjectClass" name="filterObjectClass" value="<?php echo $filterObjectClass;?>" />
         <table width="100%" style="border: 1px solid grey;">
           
           <?php
           $cpt=0;
           foreach ($filter->_FilterCriteriaArray as $filterCriteria) {
           	if ($filterCriteria->isDynamic!="1") continue;
           ?>
           
           <tr style="vertical-align: top;">
             <td style="width: 210px;" >
               <input readOnly class="dijit dijitInline dijitLeft input dijitTextBox" value="<?php echo $filterCriteria->dispAttribute;?>" style="width: 200px;padding:5px 10px;" />
               <input type="hidden" id="idFilterAttribute<?php echo $cpt;?>" name="idFilterAttribute<?php echo $cpt;?>" value="<?php echo $filterCriteria->sqlAttribute;?>" />
             </td>
             <td style="width: 110px;">
             	<input readOnly class="dijit dijitInline input dijitTextBox" value="<?php echo $filterCriteria->dispOperator;?>" style="width: 100px;padding:5px 10px;text-align:center" />
             	<input type="hidden" id="idFilterOperator<?php echo $cpt;?>" name="idFilterOperator<?php echo $cpt;?>" value="<?php echo $filterCriteria->sqlOperator;?>" />
             </td>
             <td style="width:330px;vertical-align:middle;">
                 <?php 
                 if (in_array($filterCriteria->sqlOperator, array('IN', 'NOT IN'))) {
                 	?>
               <select id="filterValueList<?php echo $cpt;?>" name="filterValueList<?php echo $cpt;?>[]" value=""  
                 dojoType="dijit.form.MultiSelect" multiple
                 style="width:400px" size="10" class="selectList" onDblClick="selectDynamicFilter();">
                 <!-- REMPLIR LISTE -->
                 <?php
                 if($filterCriteria->sqlAttribute=='idBusinessFeature'){
                   $bf = new BusinessFeature();
                   $lstbf = $bf->getSqlElementsFromCriteria(null, false, null,'name');
                   foreach ($lstbf as $bf) {
                     $product = new Product($bf->idProduct);
                     echo '<option value ="'.$bf->id.'">'.$bf->name.' ('.$product->name.')</option>';
                   }
                 }else{
                   htmlDrawOptionForReference($filterCriteria->sqlAttribute, null, null, true);
                 }
                 ?>
               </select>
                 	<?php
                 }
                 else if (in_array($filterCriteria->sqlOperator, array('LIKE', 'NOT LIKE')) or $filterCriteria->sqlValue == 'int') {
                 	?>
               <input id="filterValue<?php echo $cpt;?>" name="filterValue<?php echo $cpt;?>" value=""  
                 dojoType="dijit.form.TextBox" 
                 style="width:400px" />
                 <?php 
                    if ($filterCriteria->sqlValue == 'int') { ?>
                 <input type="hidden" name="filterDataType<?php echo $cpt;?>" id="filterDataType<?php echo $cpt;?>" value="int" />
                 	<?php
                    }
                    else { ?>
                 <input type="hidden" name="filterDataType<?php echo $cpt;?>" id="filterDataType<?php echo $cpt;?>" value="varchar<?php echo ($filterCriteria->sqlValue=='startBy' ? 'StartBy' : '');?>" />  
                 <?php }
                 }
                 else if ($filterCriteria->sqlValue == 'date') {
                 	?>
               <input id="filterValueDate<?php echo $cpt;?>" name="filterValueDate<?php echo $cpt;?>" value=""  
                 dojoType="dijit.form.DateTextBox" 
                 constraints="{datePattern:browserLocaleDateFormatJs}"
                 style="width:100px" />
               <input type="hidden" name="filterDataType<?php echo $cpt;?>" id="filterDataType<?php echo $cpt;?>" value="date" />
                 	<?php
                 }
                 else if ($filterCriteria->sqlValue == 'bool') {
                 	?>
                 <input type="checkbox" id="filterValueCheckbox<?php echo $cpt;?>" name="filterValueCheckbox<?php echo $cpt;?>" value=""
                 	dojoType="dijit.form.CheckBox" style="padding-top:7px;" />
                 <input type="hidden" name="filterDataType<?php echo $cpt;?>" id="filterDataType<?php echo $cpt;?>" value="bool" />
                 	<?php
                 }
                 else if ($filterCriteria->sqlValue == 'intDate') {
                 	?>
                <input id="filterValue<?php echo $cpt;?>" name="filterValue<?php echo $cpt;?>" value=""  
                 dojoType="dijit.form.TextBox" 
                 style="width:400px" />
               <input type="hidden" name="filterDataType<?php echo $cpt;?>" id="filterDataType<?php echo $cpt;?>" value="intDate" />
                 	<?php
                 }
                 ?>
                 <input type="hidden" name="orOperator<?php echo $cpt;?>" id="orOperator<?php echo $cpt;?>" value="<?php echo $filterCriteria->orOperator;?>" />
             </td>
           </tr>
           <?php 
           	$cpt++;
           } ?>
		   <input id="nbDynamicFilterClauses" name="nbDynamicFilterClauses" type="hidden" value="<?php echo $cpt;?>" />
         </table>
        </form>
      </td>
    </tr>                                                                                                                                                    
    <tr style="height:32px">
      <td align="center">
        <table><tr><td>
        <button class="mediumTextButton" dojoType="dijit.form.Button" type="button" onclick="dijit.byId('dialogDynamicFilter').hide();">
          <?php echo i18n("buttonCancel");?>
        </button>
        </td><td>
        <button class="mediumTextButton" dojoType="dijit.form.Button" type="submit" id="dialogDynamicFilterSubmit" onclick="protectDblClick(this);selectDynamicFilter();return false;">
          <?php echo i18n("buttonOK");?>
        </button>
        </td></tr></table>
      </td>
    </tr>
  </table>