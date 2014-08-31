<?php

$churchauth=null;

function churchauth_getModule() {
  global $churchauth;
  if ($churchauth==null) 
  	$churchauth=new CTAuthModule("churchauth");
  return $churchauth;
}

function churchauth__ajax() {
  $module = new CTAuthModule("churchauth");
  $ajax = new CTAjaxHandler($module);
  drupal_json_output($ajax->call());
}

function churchauth_main() {
  if (!user_access("administer persons","churchcore")) {
  		addInfoMessage(t("no.permission.for", "administer persons"));
  		return " ";
  }
   
  drupal_add_css(ASSETS.'/fileuploader/fileuploader.css');
   
  drupal_add_js(BOOTSTRAP.'/js/bootstrap-multiselect.js');
  drupal_add_js(ASSETS.'/fileuploader/fileuploader.js');
  drupal_add_js(ASSETS.'/js/jquery.history.js');
   
  drupal_add_css(ASSETS.'/dynatree/ui.dynatree.css');
  drupal_add_js(ASSETS.'/dynatree/jquery.dynatree-1.2.4.js');
   
  drupal_add_js(CHURCHCORE .'/churchcore.js');
  drupal_add_js(CHURCHCORE .'/churchforms.js');
  drupal_add_js(CHURCHCORE .'/cc_abstractview.js');
  drupal_add_js(CHURCHCORE .'/cc_standardview.js');
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js');
  drupal_add_js(CHURCHCORE .'/cc_interface.js');
   
  drupal_add_js(CHURCHCORE .'/cc_authview.js');
  drupal_add_js(CHURCHCORE .'/cc_authmain.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  
  $content = '
<div class="row-fluid">
  <div class="span3">
    <div id="cdb_menu"></div>
    <div id="cdb_filter"></div>
  </div>
  <div class="span9">
    <div id="cdb_search"></div>
    <div id="cdb_group"></div>
    <div id="cdb_content"></div>
  </div>
</div>';
  
  return $content;
}
