<?php 
/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2014 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt
 *
 * ChurchWiki Module
 * Depends on ChurchCore
 *
 */


function churchwiki_getAdminForm() {
  $model = new CTModuleForm("churchwiki");      
  return $model;
}

function churchwiki_getCurrentNo($doc_id, $wikicategory_id=0) {
  $res=db_query("select max(version_no) c from {cc_wiki} where doc_id=:doc_id and wikicategory_id=:wikicategory_id",
    array(":doc_id"=>$doc_id, ":wikicategory_id"=>$wikicategory_id))->fetch();
  if ($res==false) 
    return -1;
  else 
    return $res->c;  
}

function churchwiki_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 501,'view', 'churchwiki', null, 'ChurchWiki sehen', 1);
  $cc_auth=addAuth($cc_auth, 502,'view category', 'churchwiki', 'cc_wikicategory', 'Einzelne Wiki-Kategorien sehen', 1);
  $cc_auth=addAuth($cc_auth, 503,'edit category', 'churchwiki', 'cc_wikicategory', 'Einzelne Wiki-Kategorien editieren', 1);
  $cc_auth=addAuth($cc_auth, 599,'edit masterdata', 'churchwiki', null, 'Stammdaten editieren', 1);
  return $cc_auth;
}



function churchwiki_setShowonstartpage($params) {
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

function churchwiki_load($doc_id, $wikicategory_id, $version_no=null) {
  if ($version_no==null)
    $version_no=churchwiki_getCurrentNo($doc_id, $wikicategory_id);

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

function churchwiki_cron() {
  // Hole mir alle Daten, die �ber 90 Tage alt sind und dann die neuste Version_No
  $db=db_query("SELECT MAX( version_no ) version_no, wikicategory_id, doc_id FROM {cc_wiki}
    WHERE DATE_ADD( modified_date, INTERVAL 90  DAY ) < NOW( )   GROUP BY wikicategory_id, doc_id");
  foreach ($db as $e) {  
    db_query("delete from {cc_wiki} where wikicategory_id=$e->wikicategory_id and doc_id='$e->doc_id'
              and version_no<$e->version_no");
  }
  
}

function churchwiki__filedownload() {
  include_once(CHURCHCORE."/churchcore.php");
  churchcore__filedownload();  
}



function churchwiki_getAuthForAjax() {
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

function churchwiki__ajax() {
  global $user, $files_dir, $base_url, $mapping, $config;
    
  $auth=churchwiki_getAuthForAjax();
  
  if ((!user_access("view","churchwiki")) && (!in_array("churchwiki",$mapping["page_with_noauth"]))
        && (!in_array("churchwiki",$config["page_with_noauth"]))) 
    throw new CTNoPermission("view", "churchwiki");

  $module=new CTChurchWikiModule("churchwiki");
  $ajax = new CTAjaxHandler($module);  
  $res=$ajax->call();  
  drupal_json_output($res);  
}


function churchwiki_getWikiOnStartpage() {
  if (!user_access("view", "churchwiki"))
    return "";
  $ids=user_access("view category", "churchwiki");
  if ($ids==false)
    return "";
  $db=db_query("select w.wikicategory_id, w.doc_id, wc.bezeichnung, version_no, 
             DATE_FORMAT(w.modified_date , '%d.%m.%Y %H:%i') date,
             concat(p.vorname, ' ', p.name) user  
             from {cc_wiki} w, {cc_wikicategory} wc, {cdb_person} p 
         where wikicategory_id in (".implode(",",$ids).")". 
              " and w.wikicategory_id=wc.id and w.modified_pid=p.id ".
                "and w.auf_startseite_yn=1 order by w.wikicategory_id, modified_date Asc");
  $ar=array();
  foreach ($db as $wiki) {
    // Hole nun die max. Version_no
    $w=db_query("select max(version_no) version_no from {cc_wiki} where doc_id=:doc_id and wikicategory_id=:wikicategory_id",
         array(":doc_id"=>$wiki->doc_id, ":wikicategory_id"=>$wiki->wikicategory_id))->fetch();
    if ($w->version_no==$wiki->version_no)     
      $ar[$wiki->bezeichnung][$wiki->doc_id]=$wiki;
   
  }
  $txt="";
  foreach ($ar as $key=>$cat) {
    $txt.='<li><p>Wiki '.$key;
    foreach ($cat as $wiki) {
      $txt.='<br/><small><a href="?q=churchwiki#WikiView/filterWikicategory_id:'.$wiki->wikicategory_id.'/doc:'.$wiki->doc_id.'">'.($wiki->doc_id=="main"?"Hauptseite":$wiki->doc_id)."</a>";
      $txt.=" - $wiki->date $wiki->user</small>";     
    }
  }
  if ($txt!="")
    $txt="<ul>".$txt."</ul>"; 
  return $txt;
}

function churchwiki_getWikiInfos() {
  if (!user_access("view", "churchwiki"))
    return "";
  $ids=user_access("view category", "churchwiki");
  if ($ids==false)
    return "";
  $db=db_query("select w.wikicategory_id, w.doc_id, wc.bezeichnung, 
             DATE_FORMAT(w.modified_date , '%d.%m.%Y %H:%i') date,
             concat(p.vorname, ' ', p.name) user  
             from {cc_wiki} w, {cc_wikicategory} wc, {cdb_person} p 
         where wikicategory_id in (".implode(",",$ids).")". 
              " and w.wikicategory_id=wc.id and w.modified_pid=p.id ".
                "and datediff(now(),modified_date)<2 order by w.wikicategory_id, modified_date Asc");
  $ar=array();
  foreach ($db as $wiki) {
    $ar[$wiki->bezeichnung][$wiki->doc_id]=$wiki;
  }
  $txt="";
  foreach ($ar as $key=>$cat) {
    $txt.='<li><p>Wiki '.$key;
    foreach ($cat as $wiki) {
      $txt.='<br/><small><a href="?q=churchwiki#WikiView/filterWikicategory_id:'.$wiki->wikicategory_id.'/doc:'.$wiki->doc_id.'">'.($wiki->doc_id=="main"?"Hauptseite":$wiki->doc_id)."</a>";
      $txt.=" - $wiki->date $wiki->user</small>";     
    }
  }
  if ($txt!="")
    $txt="<ul>".$txt."</ul>"; 
  return $txt;
}

function churchwiki_blocks() {
  global $config;
  return (array(
    1=>array(
      "label"=>t("important.from", $config["churchwiki_name"]),
      "col"=>2,
      "sortkey"=>1,
      "html"=>churchwiki_getWikiOnStartpage()
      //"help"=>"Offene Dienstanfragen"
    ),
    2=>array(
      "label"=>t("news.from", $config["churchwiki_name"]),
      "col"=>2,
      "sortkey"=>8,
      "html"=>churchwiki_getWikiInfos()
      //"help"=>"Offene Dienstanfragen"
    )
  ));
}
      

function churchwiki__create() {

  $model = new CTForm("EditHtml", "editHtml");
  $model->setHeader("Editieren eines Hilfeeintrages", "Hier kann die Hilfe editiert werden.");    
  $model->addField("doc_id", "", "INPUT_REQUIRED","Doc-Id");
  $model->addField("text", "", "TEXTAREA","Text");
  if (isset($_GET["doc"])) {
    $model->fields["doc_id"]->setValue($_GET["doc"]);
    $res=db_query("select text from {cc_wiki} where doc_id=:doc_id", array(":doc_id"=>$_GET["doc"]))->fetch();
    if ($res) {
      $res->text = preg_replace('/\\\/', "", $res->text);
      $model->fields["text"]->setValue($res->text);
    }
  }
  $model->addButton("Speichern","ok");
  
  return $model->render();
}


function editHtml($form) {
  global $user;
  $dt=new DateTime();
  db_query("insert into {cc_wiki} (doc_id, text, modified_date, modified_pid) 
            values (:doc_id, :text, :date, :pid) ON DUPLICATE KEY UPDATE text=:text, modified_date=:date, modified_pid=:pid", 
    array(":text"=>$form->fields["text"]->getValue(), 
          ":doc_id"=>$form->fields["doc_id"]->getValue(),
          ":date"=>$dt->format('Y-m-d H:i:s'),
          ":pid"=>$user->id));
  
  ct_log("Aktualisierung Hilfeseite ".$form->fields["doc_id"]->getValue(), 2,"-1", "help");
    
  header("Location: ?q=churchwiki&doc=".$form->fields["doc_id"]->getValue());
}

function help_main() {
  return churchwiki_main();
}

function churchwiki_main() {
  global $files_dir;

  drupal_add_js(ASSETS.'/js/jquery.history.js'); 
  
  drupal_add_css(ASSETS.'/fileuploader/fileuploader.css'); 
  drupal_add_js(ASSETS.'/fileuploader/fileuploader.js'); 
  
  drupal_add_js(ASSETS.'/tablesorter/jquery.tablesorter.min.js'); 
  drupal_add_js(ASSETS.'/tablesorter/jquery.tablesorter.widgets.min.js'); 
  
  drupal_add_js(ASSETS.'/mediaelements/mediaelement-and-player.min.js'); 
  drupal_add_css(ASSETS.'/mediaelements/mediaelementplayer.css');
  
  drupal_add_js(CHURCHCORE .'/cc_abstractview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_standardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js'); 
  
  drupal_add_js(ASSETS.'/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS.'/ckeditor/lang/de.js');
  drupal_add_js(CHURCHWIKI.'/wiki_maintainview.js');
  drupal_add_js(CHURCHWIKI.'/churchwiki.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchwiki"));
 
  
  $doc_id="";
  if (isset($_GET["doc"])) $doc_id=$_GET["doc"];

  $text='<div id="cdb_navi"></div>';
  $text.='<div id="cdb_content"></div>';
  if ($doc_id!="")
    $text.='<input type="hidden" id="doc_id" name="doc_id" value="'.$doc_id.'"/>';    
    
  $page='<div class="row-fluid">';
    $page.='<div class="span3 bs-docs-sidebar">';   
      $page.='<div class="bs-docs-sidebar" id="cdb_menu"></div>';   
      $page.='<div class="bs-docs-sidebar" id="sidebar"></div>';   
    
    $page.='</div>';
    $page.='<div class="span9">'.$text.'</div>';
  $page.='</div>';
 
  return $page;
}

function churchwiki__printview() {
  global $files_dir;

  drupal_add_js(ASSETS.'/js/jquery.history.js'); 
  
  drupal_add_css(ASSETS.'/fileuploader/fileuploader.css'); 
  drupal_add_js(ASSETS.'/fileuploader/fileuploader.js'); 
  
  drupal_add_js(ASSETS.'/tablesorter/jquery.tablesorter.min.js'); 
  drupal_add_js(ASSETS.'/tablesorter/jquery.tablesorter.widgets.min.js'); 
  
  drupal_add_js(CHURCHCORE .'/shortcut.js'); 
  
  drupal_add_js(CHURCHCORE .'/cc_abstractview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_standardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js'); 
  
  drupal_add_js(ASSETS.'/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS.'/ckeditor/lang/de.js');
  drupal_add_js(CHURCHWIKI.'/wiki_maintainview.js');
  drupal_add_js(CHURCHWIKI.'/churchwiki.js');
  
  $doc_id="main";
  if (isset($_GET["doc"])) $doc_id=$_GET["doc"];

  $text='<div id="cdb_content"></div>';
  $text.='<input type="hidden" id="doc_id" name="doc_id" value="'.$doc_id.'"/>';        
  $text.='<input type="hidden" id="printview" name="doc_id" value="true"/>';        
  $page='<div class="row-fluid">';
    $page.='<div class="span12">'.$text.'</div>';
  $page.='</div>';
 
  return $page;
}
?>
