<?php 

// TODO: what is better, replace or on duplicate?
function admin_saveSettings($form) {
  foreach ($form->fields as $key=>$value) {
    db_query("INSERT INTO {cc_config} (name, value) VALUES (:name,:value) 
              ON DUPLICATE KEY UPDATE value=:value",
              array(":name"=>$key, ":value"=>$value));
  }
  loadDBConfig();
}

function admin_main() {
  global $config;
  
  drupal_add_css(ASSETS.'/fileuploader/fileuploader.css'); 
  drupal_add_js(ASSETS.'/fileuploader/fileuploader.js');
  
  $model = new CTForm("AdminForm", "admin_saveSettings");
  $model->addField("site_name","", "INPUT_REQUIRED",t("name.of.website"))
    ->setValue($config["site_name"]);
  

  $model->addField("site_logo","", "FILEUPLOAD",t("logo.of.website"))
    ->setValue(readConf("site_logo"));
    
  $model->addField("welcome","", "INPUT_REQUIRED",t("welcome.message"));
    $model->fields["welcome"]->setValue($config["welcome"]);
    
  $model->addField("welcome_subtext","", "INPUT_REQUIRED","Untertitel der Willkommensnachricht");
    $model->fields["welcome_subtext"]->setValue($config["welcome_subtext"]);
    
  $model->addField("login_message","", "INPUT_REQUIRED","Willkommensnachricht vor dem Login");
    $model->fields["login_message"]->setValue($config["login_message"]);
          
  $model->addField("invite_email_text","", "TEXTAREA","Text der Einladungs-EMail");
    $model->fields["invite_email_text"]->setValue($config["invite_email_text"]);
    
  $model->addField("admin_message","", "INPUT_OPTIONAL","Admin-Nachricht auf Login- und Startseite z.B. f&uuml;r geplante Downtimes");
    $model->fields["admin_message"]->setValue(variable_get("admin_message",""));
    
  if (!isset($config["site_startpage"])) $config["site_startpage"]="home";
  $model->addField("site_startpage","", "INPUT_REQUIRED","Startseite beim Aufrufen von ".variable_get("site_name")." (Standard ist <i>home</i>, m&ouml;glich ist z.B. churchwiki, churchcal)");
    $model->fields["site_startpage"]->setValue($config["site_startpage"]);
    
  $model->addField("site_mail","", "EMAIL","E-Mail-Adresse der Website (E-Mails werden von hier aus gesendet)");
    $model->fields["site_mail"]->setValue($config["site_mail"]);

  if (!isset($config["admin_mail"])) $config["admin_mail"]=$config["site_mail"];
  $model->addField("admin_mail","", "EMAIL","E-Mail-Adressen der Admins f&uuml;r Anfragen von Benutzern (Kommasepariert)");
    $model->fields["admin_mail"]->setValue($config["admin_mail"]);
    
  // Now iterate through each module for naming the module
  $modules=churchcore_getModulesSorted(false, true);
  foreach ($modules as $module) {
    $model->addField($module."_name","", "INPUT_OPTIONAL","Name f&uuml;r <i>$module</i> (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)");
      $model->fields[$module."_name"]->setValue(variable_get($module."_name", ""));       
  }
    
  $model->addField("max_uploadfile_size_kb","", "INPUT_REQUIRED","Maximale Upload-Dateigr&ouml;sse in Kilobytes (z.B. 10MB entsprechen hier ca. 10000)");
    $model->fields["max_uploadfile_size_kb"]->setValue($config["max_uploadfile_size_kb"]);
    
  $model->addField("cronjob_delay","", "INPUT_REQUIRED","Zeit in Sekunden zwischen automatischen Cronjob (0=kein automatischer Cron, sinnvolle Werte z.B. 3600)");
    $model->fields["cronjob_delay"]->setValue($config["cronjob_delay"]);
  
  $model->addField("timezone","", "INPUT_REQUIRED","Standard-Zeitzone. Z.b. Europe/Berlin");
    $model->fields["timezone"]->setValue($config["timezone"]);
    
  $model->addField("show_remember_me","", "CHECKBOX","Anzeige von <i>Zuk&uuml;nftig an mich erinnern</i> auf der Login-Seite");
    $model->fields["show_remember_me"]->setValue($config["show_remember_me"]);
    
  $model->addField("mail_enabled","", "CHECKBOX","Senden von E-Mails erlauben");
    $model->fields["mail_enabled"]->setValue($config["mail_enabled"]);

  $model->addField("site_offline","", "CHECKBOX","Seite offline schalten");
    $model->fields["site_offline"]->setValue($config["site_offline"]);
    
  $model->addButton(t("save"), "ok");
  
  $txtCommonForm=$model->render();
  
 
  // Now iterate through each module getting the admin forms
  $m=array();
  foreach ($modules as $module) {
    include_once(constant(strtoupper($module))."/$module.php");
    if (function_exists($module."_getAdminForm")) {
      $model=call_user_func($module."_getAdminForm");
      if ($model!=null)
        $m[$module]=$model->render();
    }
  }
    
  $txt='<h1>'.t("settings.for", variable_get("site_name")).'</h1><p>Der Administrator kann hier Einstellung vornehmen. Diese gelten f&uuml;r alle Benutzer, bitte vorsichtig anpassen!</p>';
  $txt.='<div class="tabbable">';
  $txt.='<ul class="nav nav-tabs">';
    $txt.='<li class="active"><a href="#tab1" data-toggle="tab">'.t("general").'</a></li>';
    foreach ($modules as $module) {
      if ((isset($m[$module])) && (isset($config[$module."_name"])) && ($config[$module."_name"]!=""))
        $txt.='<li><a href="#tab'.$module.'" data-toggle="tab">'.$config[$module."_name"].'</a></li>';
    }
    $txt.='</ul>';
  $txt.='<div class="tab-content">';
    $txt.='<div class="tab-pane active" id="tab1">';
      $txt.=$txtCommonForm;  
    $txt.='</div>';
    foreach($modules as $module) if (isset($m[$module])) {
      $txt.='<div class="tab-pane" id="tab'.$module.'">';
        $txt.=$m[$module];    
      $txt.='</div>';
    }
    
  $txt.='</div></div>';
  
  return $txt;
}


function admin__uploadfile() {
  global $files_dir, $config;
  
  include_once(CHURCHCORE."/uploadFile.php");
  churchcore__uploadFile();
}


function admin__ajax() {
  $module=new CTAdminModule("admin");
  $ajax = new CTAjaxHandler($module);

  drupal_json_output($ajax->call());
}



?>
