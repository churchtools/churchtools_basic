<?php 
/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2014 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt
 *
 * ChurchReport Module
 * Depends on ChurchCore, ChurchDB
 *
 */


function churchreport_getAdminForm() {
  $model = new CTModuleForm("churchreport");      
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

  drupal_add_js(ASSETS.'/js/jquery.history.js'); 
  
  drupal_add_js(CHURCHCORE .'/cc_abstractview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_standardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js'); 
  
  drupal_add_js(ASSETS.'/pivottable/pivot.js');
  drupal_add_css(ASSETS.'/pivottable/pivot.css');
    
  drupal_add_js(CHURCHREPORT.'/report_maintainview.js');
  drupal_add_js(CHURCHREPORT.'/churchreport.js');
  
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
