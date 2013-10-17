<?php


include_once("churchresource_db.inc");

function churchresource_printview() {
global $user;
  
  $content="<html><head>";

  drupal_add_css(drupal_get_path('module', 'churchcore').'/churchcore_bootstrap.css');
  drupal_add_css('system/assets/ui/jquery-ui-1.8.18.custom.css');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/shortcut.js'); 
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js('system/assets/ui/jquery.ui.core.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.datepicker.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.position.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.widget.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.dialog.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.mouse.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.draggable.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchcore.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchforms.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_interface.js'); 

  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_loadandmap.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_maintainview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_weekview.js');
  drupal_add_js(drupal_get_path('module', 'churchresource') .'/cr_main.js');
 
  $content=$content.drupal_get_js();
  
//  drupal_add_css(drupal_get_path('module', 'churchresource').'/cr_printview.css');
//  $content=$content.drupal_get_css();
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.drupal_get_path('module', 'churchcore').'/churchcore.css" />';
  $content=$content.'<link type="text/css" rel="stylesheet" media="all" href="'.drupal_get_path('module', 'churchresource').'/cr_printview.css" />';
    
  $content=$content."</head><body>";
  $content=$content."<input type=\"hidden\" id=\"printview\" value=\"true\"/>";

  if (isset($_GET["curdate"]) && ($_GET["curdate"]!=null))
    $content=$content."<input type=\"hidden\" id=\"curdate\" value=\"".$_GET["curdate"]."\"/>";
  
  $content=$content."<div id=\"cdb_menu\"></div> <div id=\"cdb_filter\"></div></div> <div id=\"cdb_content\">Seite wird aufgebaut...</div>";
  $content=$content."</body></html>";
  echo $content;
}

