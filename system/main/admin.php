<?php

// TODO: what is better, replace or on duplicate?
/**
 * save admin settings and reload config
 * 
 * @param CTForm $form          
 */
function admin_saveSettings($form) {
  foreach ($form->fields as $key => $value) {
    db_query("INSERT INTO {cc_config} (name, value) 
              VALUES (:name,:value) 
              ON DUPLICATE KEY UPDATE value=:value", array (":name" => $key, ":value" => $value));
  }
  loadDBConfig();
}

/**
 * main function for admin
 * 
 * @return string
 */
function admin_main() {
  global $config;
  
  drupal_add_css(ASSETS . '/fileuploader/fileuploader.css');
  drupal_add_js(ASSETS . '/fileuploader/fileuploader.js');
  
  $form = new CTForm("AdminForm", "admin_saveSettings");
  
  $form->addField("site_name", "", "INPUT_REQUIRED", t("name.of.website"))
    ->setValue($config["site_name"]);
  
  $form->addField("site_logo", "", "FILEUPLOAD", t("logo.of.website"))
    ->setValue(readConf("site_logo"));
  
  $form->addField("welcome", "", "INPUT_REQUIRED", t("welcome.message"))
    ->setValue($config["welcome"]);
  
  $form->addField("welcome_subtext", "", "INPUT_REQUIRED", "Untertitel der Willkommensnachricht")
    ->setValue($config["welcome_subtext"]);
  
  $form->addField("login_message", "", "INPUT_REQUIRED", "Willkommensnachricht vor dem Login")
    ->setValue($config["login_message"]);
  
  $form->addField("invite_email_text", "", "TEXTAREA", "Text der Einladungs-EMail")
    ->setValue($config["invite_email_text"]);
  
  $form->addField("admin_message", "", "INPUT_OPTIONAL", "Admin-Nachricht auf Login- und Startseite z.B. f&uuml;r geplante Downtimes")
    ->setValue(variable_get("admin_message", ""));
  
  if (!isset($config["site_startpage"])) $config["site_startpage"] = "home";
  $form->addField("site_startpage", "", "INPUT_REQUIRED", "Startseite beim Aufrufen von " . readConf("site_name") . " (Standard ist <i>home</i>, m&ouml;glich ist z.B. churchwiki, churchcal)")
    ->setValue($config["site_startpage"]);
  
  $form->addField("site_mail", "", "EMAIL", "E-Mail-Adresse der Website (E-Mails werden von hier aus gesendet)")
    ->setValue($config["site_mail"]);
  
  $form->addField("admin_mail", "", "EMAIL", "E-Mail-Adressen der Admins f&uuml;r Anfragen von Benutzern (Kommasepariert)")
    ->setValue(isset($config["admin_mail"]) ? $config["admin_mail"] : $config["site_mail"]);
  
  // iterate through modules for naming them
  $modules = churchcore_getModulesSorted(false, true);
  foreach ($modules as $module) {
    $form->addField($module . "_name", "", "INPUT_OPTIONAL", "Name f&uuml;r <i>$module</i> (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)")
      ->setValue(variable_get($module . "_name", ""));
  }
  
  $form->addField("max_uploadfile_size_kb", "", "INPUT_REQUIRED", "Maximale Upload-Dateigr&ouml;sse in Kilobytes (z.B. 10MB entsprechen hier ca. 10000)")
    ->setValue($config["max_uploadfile_size_kb"]);
  
  $form->addField("cronjob_delay", "", "INPUT_REQUIRED", "Zeit in Sekunden zwischen automatischen Cronjob (0=kein automatischer Cron, sinnvolle Werte z.B. 3600)")
    ->setValue($config["cronjob_delay"]);
  
  $form->addField("timezone", "", "INPUT_REQUIRED", "Standard-Zeitzone. Z.b. Europe/Berlin")
    ->setValue($config["timezone"]);
  
  $form->addField("show_remember_me", "", "CHECKBOX", "Anzeige von <i>Zuk&uuml;nftig an mich erinnern</i> auf der Login-Seite")
    ->setValue($config["show_remember_me"]);
  
  $form->addField("mail_enabled", "", "CHECKBOX", "Senden von E-Mails erlauben")
    ->setValue($config["mail_enabled"]);
  
  $form->addField("site_offline", "", "CHECKBOX", "Seite offline schalten")
    ->setValue($config["site_offline"]);
  
  $form->addButton("Speichern", "ok");
  
  $txtCommonForm = $form->render();
  
  // iterate through modules getting the admin forms
  $m = array ();
  foreach ($modules as $module) {
    include_once (constant(strtoupper($module)) . "/$module.php");
    if (function_exists($module . "_getAdminForm")) {
      $form = call_user_func($module . "_getAdminForm");
      if ($form) $m[$module] = $form->render();
    }
  }
  
  $txt = '<h1>' . t("settings.for", variable_get("site_name")) . '</h1>
      <p>Der Administrator kann hier Einstellung vornehmen. Diese gelten f&uuml;r alle Benutzer, bitte vorsichtig anpassen!</p>
      <div class="tabbable">
        <ul class="nav nav-tabs">
          <li class="active"><a href="#tab1" data-toggle="tab">' . t("general") . '</a></li>';
  foreach ($modules as $module) {
    if (isset($m[$module]) && readConf($module . "_name")) {
      $txt .= '
          <li><a href="#tab' . $module . '" data-toggle="tab">' . readConf($module . "_name") . '</a></li>';
    }
  }
  $txt .= '
        </ul>
        <div class="tab-content">
        <div class="tab-pane active" id="tab1">' . $txtCommonForm. '</div>';
  
  foreach ($modules as $module) if (isset($m[$module])) {
    $txt .= '<div class="tab-pane" id="tab' . $module . '">' . $m[$module] . '</div>';
  }
  
  $txt .= '</div></div>';
  
  return $txt;
}


function admin__uploadfile() {
  global $files_dir, $config;
  
  include_once (CHURCHCORE . "/uploadFile.php");
  churchcore__uploadFile();
}

function admin__ajax() {
  $module = new CTAdminModule("admin");
  $ajax = new CTAjaxHandler($module);
  
  drupal_json_output($ajax->call());
}

