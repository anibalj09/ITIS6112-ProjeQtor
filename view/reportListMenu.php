<?php 
require_once "../tool/projeqtor.php";
$categ=null;
if (isset($_REQUEST['idCategory'])) {
  $categ=$_REQUEST['idCategory'];
}
$hr=new HabilitationReport();
$user=getSessionUser();
$allowedReport=array();
$allowedCategory=array();
$lst=$hr->getSqlElementsFromCriteria(array('idProfile'=>$user->idProfile, 'allowAccess'=>'1'), false);
foreach ($lst as $h) {
  $report=$h->idReport;
  $allowedReport[$report]=$report;
  $category=SqlList::getFieldFromId('Report', $report, 'idReportCategory',false);
  $allowedCategory[$category]=$category;
}

if (!$categ) {
  echo "<div class='messageData headerReport' style= ''>";
  echo i18n('colCategory');
  echo "</div>";
  $listCateg=SqlList::getList('ReportCategory');
  echo "<ul class='bmenu'>";
  foreach ($listCateg as $id=>$name) {
    if (isset($allowedCategory[$id])) {
      echo "<li class='section' onClick='loadDiv(\"../view/reportListMenu.php?idCategory=$id\",\"reportMenuList\");'><div class='bmenuCategText'>$name</div></li>";
    }
  }
  echo "</ul>";
} else {
  $catObj=new ReportCategory($categ);
  echo "<div class='messageData headerReport' style= ''>";
  echo i18n($catObj->name);
  echo "</div>";
  echo "<div class='arrowBack' style= 'position:absolute;top:0px;left:0px;'>";
  echo "<span class='dijitInline dijitButtonNode backButton'  onClick='loadDiv(\"../view/reportListMenu.php\",\"reportMenuList\")'";
  echo formatBigButton('Back');
  echo "</div>";
  echo '</span>';
  
  $report=new Report();
  $crit=array('idReportCategory'=>$categ);
  $listReport=$report->getSqlElementsFromCriteria($crit, false, null, 'sortOrder asc');
  echo "<ul class='bmenu report' style=''>";
  foreach ($listReport as $rpt) {
    if (isset($allowedReport[$rpt->id])) {
      echo "<li class='section' id='report$rpt->id' onClick='reportSelectReport($rpt->id);'><div class='bmenuText'>".i18n($rpt->name)."</div></li>";   
    }
  }
  echo "</ul>";
}  
  
?>