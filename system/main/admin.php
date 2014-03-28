<?php 

include_once("system/includes/forms.php");

class CC_ModulModel extends CC_Model {
  public function __construct($modulename) {
    global $config;
    
    parent::__construct("AdminForm_$modulename", "admin_saveSettings");
    $this->addField($modulename."_inmenu","", "CHECKBOX",$config[$modulename."_name"]." im Menu auff&uuml;hren");
    $this->fields[$modulename."_inmenu"]->setValue($config[$modulename."_inmenu"]);    
    $this->addField($modulename."_startbutton","", "CHECKBOX",$config[$modulename."_name"]." auf der Startseite als Button anzeigen");
    $this->fields[$modulename."_startbutton"]->setValue($config[$modulename."_startbutton"]);    
    $this->addField($modulename."_sortcode","", "INPUT_REQUIRED","Sortierungsnummer im Menu (sortcode)");
    $this->fields[$modulename."_sortcode"]->setValue($config[$modulename."_sortcode"]);    
  }
  public function render() {
    
    $this->addButton("Speichern","ok");
    return parent::render();    
  }
}



function admin_saveSettings($form) {
  foreach ($form->fields as $key=>$value) {
    db_query("insert into {cc_config} (name, value) values (:name,:value) on duplicate key update value=:value",
       array(":name"=>$key, ":value"=>$value));
  }
  loadDBConfig();
}

function admin_main() {
  global $config;
  
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  drupal_add_js('system/assets/fileuploader/fileuploader.js');
  
  $model = new CC_Model("AdminForm", "admin_saveSettings");
  $model->addField("site_name","", "INPUT_REQUIRED","Name der Website");
    $model->fields["site_name"]->setValue($config["site_name"]);
  

  $model->addField("site_logo","", "FILEUPLOAD","Logo der Website (max. 32x32px)");
  if (isset($config["site_logo"]))
    $model->fields["site_logo"]->setValue($config["site_logo"]);
    
  $model->addField("welcome","", "INPUT_REQUIRED","Willkommensnachricht");
    $model->fields["welcome"]->setValue($config["welcome"]);
    
  $model->addField("welcome_subtext","", "INPUT_REQUIRED","Untertitel der Willkommensnachricht");
    $model->fields["welcome_subtext"]->setValue($config["welcome_subtext"]);
    
  $model->addField("login_message","", "INPUT_REQUIRED","Willkommensnachricht vor dem Login");
    $model->fields["login_message"]->setValue($config["login_message"]);
    
  $model->addField("admin_message","", "INPUT_OPTIONAL","Admin-Nachricht auf Login- und Startseite z.B. f&uuml;r geplante Downtimes");
    $model->fields["admin_message"]->setValue(isset($config["admin_message"])?$config["admin_message"]:"");
    
  if (!isset($config["site_startpage"])) $config["site_startpage"]="home";
  $model->addField("site_startpage","", "INPUT_REQUIRED","Startseite beim Aufrufen von ".variable_get("site_name")." (Standard ist <i>home</i>, m&ouml;glich ist z.B. churchwiki, churchcal)");
    $model->fields["site_startpage"]->setValue($config["site_startpage"]);
    
  $model->addField("site_mail","", "EMAIL","E-Mail-Adresse der Website (E-Mails werden von hier aus gesendet)");
    $model->fields["site_mail"]->setValue($config["site_mail"]);

  // Now iterate through each module for naming the module
  $modules=churchcore_getModulesSorted(false, true);
  foreach ($modules as $module) {
    $model->addField($module."_name","", "INPUT_OPTIONAL","Name f&uuml;r <i>$module</i> (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)");
      $model->fields[$module."_name"]->setValue($config[$module."_name"]);       
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
    
  $model->addButton("Speichern","ok");
  
  $txt_general=$model->render();
  
 
  // Now iterate through each module getting the admin forms
  $m=array();
  foreach ($modules as $module) {
    include_once(drupal_get_path('module', $module)."/$module.php");
    if (function_exists($module."_getAdminModel")) {
      $model=call_user_func($module."_getAdminModel");
      if ($model!=null)
        $m[$module]=$model->render();
    }
  }
    
  $txt='<h1>Einstellungen f&uuml;r '.variable_get("site_name").'</h1><p>Der Administrator kann hier Einstellung vornehmen. Diese gelten f&uuml;r alle Benutzer, bitte vorsichtig anpassen!</p>';
  $txt.='<div class="tabbable">';
  $txt.='<ul class="nav nav-tabs">';
    $txt.='<li class="active"><a href="#tab1" data-toggle="tab">Allgemein</a></li>';
    foreach ($modules as $module) {
      if ((isset($m[$module])) && ($config[$module."_name"]!=""))
        $txt.='<li><a href="#tab'.$module.'" data-toggle="tab">'.$config[$module."_name"].'</a></li>';
    }
    $txt.='</ul>';
  $txt.='<div class="tab-content">';
    $txt.='<div class="tab-pane active" id="tab1">';
      $txt.=$txt_general;  
    $txt.='</div>';
    foreach($modules as $module) {
      if (isset($m[$module])) {
        $txt.='<div class="tab-pane" id="tab'.$module.'">';
          $txt.=$m[$module];    
        $txt.='</div>';
      }
    }
    
  $txt.='</div></div>';
  
  return $txt;
}


function admin__uploadfile() {
  global $files_dir, $config;
  
  include_once("system/churchcore/uploadFile.php");
  churchcore__uploadFile();
}

class CTAdminModule extends CTAbstractModule {
  function getMasterData() {
    
  }
  function saveLogo($params) {
    if ($params["filename"]==null)
      db_query("delete from {cc_config} where name='site_logo'");
    else 
      db_query("insert into {cc_config} (name, value) values ('site_logo', :filename) on duplicate key update value=:filename", 
       array(":filename"=>$params["filename"]));
  }
}

function admin__ajax() {
  $module=new CTAdminModule("admin");
  $ajax = new CTAjaxHandler($module);

  drupal_json_output($ajax->call());
}



?>
