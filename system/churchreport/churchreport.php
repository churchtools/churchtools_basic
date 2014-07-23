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
  $cc_auth=addAuth($cc_auth, 702,'view category', 'churchreport', 'cc_wikicategory', 'Einzelne Report-Kategorien sehen', 1);
  $cc_auth=addAuth($cc_auth, 703,'edit category', 'churchreport', 'cc_wikicategory', 'Einzelne Report-Kategorien editieren', 1);
  $cc_auth=addAuth($cc_auth, 799,'edit masterdata', 'churchreport', null, 'Stammdaten editieren', 1);
  return $cc_auth;
}



function churchreport_setShowonstartpage($params) {
  $i=new CTInterface();
  $i->setParam("doc_id");
  $i->setParam("version_no");
  $i->setParam("wikicategory_id");
  $i->setParam("auf_startseite_yn");
  
  db_update("cc_wiki")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("doc_id", $params["doc_id"], "=")
    ->condition("version_no", $params["version_no"], "=")
    ->condition("wikicategory_id", $params["wikicategory_id"], "=")
    ->execute(false);
}

function churchreport_load($doc_id, $wikicategory_id, $version_no=null) {
  if ($version_no==null)
    $version_no=churchreport_getCurrentNo($doc_id, $wikicategory_id);

  ct_log("Aufruf Hilfeseite $wikicategory_id:$doc_id ($version_no)",2,"-1", "help");
      
  $sql="select p.vorname, p.name, doc_id,  version_no, wikicategory_id, text, 
          modified_date, modified_pid, auf_startseite_yn from {cc_wiki} w LEFT JOIN {cdb_person} p ON (w.modified_pid=p.id) 
          where version_no=:version_no and doc_id=:doc_id and wikicategory_id=:wikicategory_id";
  $data=db_query($sql, array(':doc_id'=>$doc_id, ":wikicategory_id"=>$wikicategory_id,
     ':version_no'=>$version_no)
  )->fetch();
  if (isset($data->text)) {
    $data->text = preg_replace('/\\\/', "", $data->text);
    $data->text = preg_replace('/===([^===]*)===/', "<h3>$1</h3>", $data->text);
    $data->text = preg_replace('/==([^==]*)==/', "<h2>$1</h2>", $data->text);
    $data->files=churchcore_getFilesAsDomainIdArr("wiki_".$wikicategory_id, $doc_id);
  }
  return $data;
}

function churchreport_cron() {
  // Hole mir alle Daten, die �ber 90 Tage alt sind und dann die neuste Version_No
  $db=db_query("SELECT MAX( version_no ) version_no, wikicategory_id, doc_id FROM {cc_wiki}
    WHERE DATE_ADD( modified_date, INTERVAL 90  DAY ) < NOW( )   GROUP BY wikicategory_id, doc_id");
  foreach ($db as $e) {  
    db_query("delete from {cc_wiki} where wikicategory_id=$e->wikicategory_id and doc_id='$e->doc_id'
              and version_no<$e->version_no");
  }
  
}

function churchreport__filedownload() {
  include_once("system/churchcore/churchcore.php");
  churchcore__filedownload();  
}

function churchreport_getSqlAsTable($sql) {
  $db=db_query($sql);
  $arr;
  foreach ($db as $d) {
    $arr[]=$d;
  }
  return $arr;
}

class CTChurchReportModule extends CTAbstractModule {

  public function getMasterDataTablenames() {
    $res=array();
    $res[1]=churchcore_getMasterDataEntry(1, "Report-Kategorien", "wikicategory", "cc_wikicategory","sortkey,bezeichnung");
  
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
             "bezeichnung"=>$query->bezeichnung);
    }        
    return $data;   
  }
  
  public function loadQuery($params) {
    $result = array();
    if ($params["id"]!="") {
      $r=db_query("select * from {crp_query} where id=:id", array(":id"=>$params["id"]))->fetch();
      $result["data"]=churchreport_getSqlAsTable($r->sql);
      $result["reports"]=churchcore_getTableData("crp_report", null, "query_id=".$params["id"]);
    }
    return $result;
  }  

    
}


function churchreport_getAuthForAjax() {
  $auth["view"]=user_access("view category", "churchwiki");
  $auth["edit"]=user_access("edit category", "churchwiki");
  $auth["admin"]=user_access("edit masterdata", "churchwiki");
  if (((isset($mapping["page_with_noauth"])) && (in_array("churchwiki",$mapping["page_with_noauth"])))
  || ((isset($config["page_with_noauth"]) && (in_array("churchwiki",$config["page_with_noauth"]))))) {
    if (!isset($auth["view"])) $auth["view"]=array();
    $auth["view"][0]="0";
  }
  
  return $auth;
}

function churchreport__ajax() {
  global $user, $files_dir, $base_url, $mapping, $config;
    
  $auth=churchreport_getAuthForAjax();
  
  if ((!user_access("view","churchwiki")) && (!in_array("churchwiki",$mapping["page_with_noauth"]))
        && (!in_array("churchwiki",$config["page_with_noauth"]))) 
    throw new CTNoPermission("view", "churchwiki");

  $module=new CTChurchReportModule("churchreport");
  $ajax = new CTAjaxHandler($module);  
  $res=$ajax->call();  
  drupal_json_output($res);  
}



function churchreport_main() {
  global $files_dir;
  include_once("system/includes/forms.php");

  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  drupal_add_js('system/assets/fileuploader/fileuploader.js'); 
  
  drupal_add_js('system/assets/tablesorter/jquery.tablesorter.min.js'); 
  drupal_add_js('system/assets/tablesorter/jquery.tablesorter.widgets.min.js'); 
  
  drupal_add_js('system/assets/mediaelements/mediaelement-and-player.min.js'); 
  drupal_add_css('system/assets/mediaelements/mediaelementplayer.css');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  
  drupal_add_js('system/assets/pivottable/pivot.js');
  drupal_add_css('system/assets/pivottable/pivot.css');
  
  
  drupal_add_js('system/churchreport/report_maintainview.js');
  drupal_add_js('system/churchreport/churchreport.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchreport"));
 
  
  $doc_id="";
  if (isset($_GET["doc"])) $doc_id=$_GET["doc"];

  $text='<div id="cdb_navi"></div>';
  $text.='<div id="cdb_content"></div>';
  if ($doc_id!="")
    $text.='<input type="hidden" id="doc_id" name="doc_id" value="'.$doc_id.'"/>';    
    
  $page='<div class="row-fluid">';
    $page.='<div class="span12">'.$text.'</div>';
  $page.='</div>';
 
  return $page;
}

?>
