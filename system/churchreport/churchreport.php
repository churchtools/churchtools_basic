<?php 


function churchreport_getAdminModel() {
  $model = new CC_ModulModel("churchreport");      
  return $model;
}

function churchreport_getCurrentNo($doc_id, $wikicategory_id=0) {
  $res=db_query("select max(version_no) c from {cc_wiki} where doc_id=:doc_id and wikicategory_id=:wikicategory_id",
    array(":doc_id"=>$doc_id, ":wikicategory_id"=>$wikicategory_id))->fetch();
  if ($res==false) 
    return -1;
  else 
    return $res->c;  
}

function churchreport_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 701,'view', 'churchreport', null, 'ChurchReport sehen', 1);
  $cc_auth=addAuth($cc_auth, 799,'edit masterdata', 'churchreport', null, 'Stammdaten editieren', 1);
  return $cc_auth;
}

function churchreport_cron() { 
}

function churchreport_getSqlAsTable($sql) {
  $db=db_query($sql);
  $arr=array();
  foreach ($db as $d) {
    $arr[]=$d;
  }
  return $arr;
}

class CTChurchReportModule extends CTAbstractModule {

  public function getMasterDataTablenames() {
    $res=array();
    $res[1]=churchcore_getMasterDataEntry(1, t("query"), "query", "crp_query","sortkey,bezeichnung");
    $res[2]=churchcore_getMasterDataEntry(2, t("report"), "report", "crp_report","sortkey,bezeichnung");
    
    return $res;
  }
  
  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;
    
    $data["auth"]=churchreport_getAuthForAjax();    
    
    $data["settings"]=array();
    $data["masterDataTables"] = $this->getMasterDataTablenames();
    $data["files_url"] = $base_url.$files_dir;
    $data["files_dir"] = $files_dir;
    $data["modulename"] = "churchreport";
    $data["modulespath"] = drupal_get_path('module', 'churchreport');
    $data["adminemail"] = variable_get('site_mail', 'info@churchtools.de');
    $querys=churchcore_getTableData("crp_query");
    $data["query"] = array();
    foreach ($querys as $query) {
      $data["query"][$query->id] = array("id"=>$query->id, 
            "sortkey"=>$query->sortkey, 
             "query_sql"=>$query->query_sql,
             "bezeichnung"=>$query->bezeichnung);
    }     
    $data["report"] = churchcore_getTableData("crp_report");   
    return $data;   
  }
  
  public function loadQuery($params) {
    $result = array();
    if ($params["id"]!="") {
      $r=db_query("select * from {crp_query} where id=:id", array(":id"=>$params["id"]))->fetch();
      $result["data"]=churchreport_getSqlAsTable($r->query_sql);
    }
    return $result;
  }     
}

function churchreport_getAuthForAjax() {
  global $user;
  $auth=$user->auth["churchreport"];
  return $auth;
}

function churchreport__ajax() {
  $module=new CTChurchReportModule("churchreport");
  $ajax = new CTAjaxHandler($module);  
  $res=$ajax->call();  
  drupal_json_output($res);  
}

function churchreport_main() {
  global $files_dir;
  include_once("system/includes/forms.php");

  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  
  drupal_add_js('system/assets/pivottable/pivot.js');
  drupal_add_css('system/assets/pivottable/pivot.css');
    
  drupal_add_js('system/churchreport/report_maintainview.js');
  drupal_add_js('system/churchreport/churchreport.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchreport"));
  
  $text='<div id="cdb_navi"></div>';
  $text.='<div id="cdb_menu"></div>';    
  $text.='<div id="cdb_content"></div>';    
  $page='<div class="row-fluid">';
    $page.='<div class="span12">'.$text.'</div>';
  $page.='</div>';
 
  return $page;
}

?>
