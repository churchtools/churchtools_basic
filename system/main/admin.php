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
  
  $model = new CC_Model("AdminForm", "admin_saveSettings");
  //$model->setHeader("Einstellungen f&uuml;r die Website", "Der Administrator kann hier Einstellung vornehmen. Diese gelten f&uuml;r alle Benutzer, bitte vorsichtig anpassen!");    
  $model->addField("site_name","", "INPUT_REQUIRED","Name der Website");
    $model->fields["site_name"]->setValue($config["site_name"]);
  
  $model->addField("welcome","", "INPUT_REQUIRED","Willkommensnachricht");
    $model->fields["welcome"]->setValue($config["welcome"]);
    
  $model->addField("welcome_subtext","", "INPUT_REQUIRED","Untertitel der Willkommensnachricht");
    $model->fields["welcome_subtext"]->setValue($config["welcome_subtext"]);
    
  $model->addField("login_message","", "INPUT_REQUIRED","Willkommensnachricht vor dem Login");
    $model->fields["login_message"]->setValue($config["login_message"]);
    
  $model->addField("admin_message","", "INPUT_OPTIONAL","Admin-Nachricht auf Login- und Startseite z.B. f&uuml;r geplante Downtimes");
    $model->fields["admin_message"]->setValue(isset($config["admin_message"])?$config["admin_message"]:"");
    
  $model->addField("site_mail","", "EMAIL","E-Mail-Adresse der Website (E-Mails werden von hier aus gesendet)");
    $model->fields["site_mail"]->setValue($config["site_mail"]);

        
  $model->addField("churchdb_name","", "INPUT_OPTIONAL","Name f&uuml;r ChurchDB (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)");
    $model->fields["churchdb_name"]->setValue($config["churchdb_name"]);    
    
  $model->addField("churchresource_name","", "INPUT_OPTIONAL","Name f&uuml;r ChurchResource (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)");
    $model->fields["churchresource_name"]->setValue($config["churchresource_name"]);
    
  $model->addField("churchservice_name","", "INPUT_OPTIONAL","Name von ChurchService (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)");
    $model->fields["churchservice_name"]->setValue($config["churchservice_name"]);
    
  $model->addField("churchcal_name","", "INPUT_OPTIONAL","Name von ChurchCal (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)");
    $model->fields["churchcal_name"]->setValue($config["churchcal_name"]);
    
  $model->addField("churchwiki_name","", "INPUT_OPTIONAL","Name von ChurchWiki (Bitte Feld leerlassen, wenn das Modul nicht ben&ouml;tigt wird)");
    $model->fields["churchwiki_name"]->setValue($config["churchwiki_name"]);
    
  $model->addField("max_uploadfile_size_kb","", "INPUT_REQUIRED","Maximale Upload-Dateigr&ouml;sse in Kilobytes (z.B. 10MB entsprechen hier ca. 10000)");
    $model->fields["max_uploadfile_size_kb"]->setValue($config["max_uploadfile_size_kb"]);
    
  $model->addField("cronjob_delay","", "INPUT_REQUIRED","Zeit in Sekunden zwischen automatischen Cronjob (0=kein automatischer Cron, sinnvolle Werte z.B. 3600)");
    $model->fields["cronjob_delay"]->setValue($config["cronjob_delay"]);
  
    
  $model->addField("show_remember_me","", "CHECKBOX","Anzeige von <i>Zuk&uuml;nftig an mich erinnern</i> auf der Login-Seite");
    $model->fields["show_remember_me"]->setValue($config["show_remember_me"]);
    
  $model->addField("mail_enabled","", "CHECKBOX","Senden von E-Mails erlauben");
    $model->fields["mail_enabled"]->setValue($config["mail_enabled"]);

  $model->addField("site_offline","", "CHECKBOX","Seite offline schalten");
    $model->fields["site_offline"]->setValue($config["site_offline"]);
    
  $model->addButton("Speichern","ok");
  
  $txt_general=$model->render();
  
  $m=array();
 
  $model = new CC_ModulModel("churchdb");

  $model->addField("churchdb_maxexporter","", "INPUT_REQUIRED","Wieviel Datens&auml;tze maximal exportiert werden d&uuml;rfen");
    $model->fields["churchdb_maxexporter"]->setValue($config["churchdb_maxexporter"]);
    
  $model->addField("churchdb_home_lat","", "INPUT_REQUIRED","Koordinaten-Mittelpunkt Latitude (am besten durch Google Maps herauszufinden)");
    $model->fields["churchdb_home_lat"]->setValue($config["churchdb_home_lat"]);
    
  $model->addField("churchdb_home_lng","", "INPUT_REQUIRED","Koordination-Mittelpunkt Longitudinal (am besten durch Google Maps herauszufinden)");
    $model->fields["churchdb_home_lng"]->setValue($config["churchdb_home_lng"]);
        
  $model->addField("churchdb_emailseparator","", "INPUT_REQUIRED","Standard-Separator f&uuml;r mehrere Empf&auml;nger beim ChurchDB-E-Mailer");
    $model->fields["churchdb_emailseparator"]->setValue($config["churchdb_emailseparator"]);
    
  $model->addField("churchdb_groupnotchoosable","", "INPUT_REQUIRED","Wie lange zur&uuml;ck nach Abschlussdatum die Gruppe noch unter Meine Gruppen pr&auml;sent sein soll");
    $model->fields["churchdb_groupnotchoosable"]->setValue($config["churchdb_groupnotchoosable"]);
    
  $model->addField("churchdb_birthdaylist_status","", "INPUT_REQUIRED","Kommaseparierte Liste mit Status-Ids f&uuml;r Geburtstagsliste");
    $model->fields["churchdb_birthdaylist_status"]->setValue($config["churchdb_birthdaylist_status"]);    
  $model->addField("churchdb_birthdaylist_station","", "INPUT_REQUIRED","Kommaseparierte Liste mit Station-Ids f&uuml;r Geburtstagsliste");
    $model->fields["churchdb_birthdaylist_station"]->setValue($config["churchdb_birthdaylist_station"]);

  $model->addField("churchdb_mailchimp_apikey","", "INPUT_OPTIONAL",'Wenn die Integration von MailChimp.com genutzt werden soll, bitte hier den API-Key angeben. <a target="_clean" href="http://intern.churchtools.de/?q=help&doc=MailChimp-Integration">Weitere Informationen</a>');
    $model->fields["churchdb_mailchimp_apikey"]->setValue($config["churchdb_mailchimp_apikey"]);
  $model->addField("churchdb_smspromote_apikey","", "INPUT_OPTIONAL",'Wenn die Integration von smspromote.de genutzt werden soll, bitte hier den API-Key angeben.  <a target="_clean" href="http://intern.churchtools.de/?q=help&doc=smspromote-Integration">Weitere Informationen</a>');
    $model->fields["churchdb_smspromote_apikey"]->setValue($config["churchdb_smspromote_apikey"]);
    
  $model->addField("churchdb_sendgroupmails","", "CHECKBOX","Sende &Auml;nderungen in Gruppen an Leiter, Co-Leiter und Supervisore");
    $model->fields["churchdb_sendgroupmails"]->setValue($config["churchdb_sendgroupmails"]);

  $m["churchdb"]=$model->render();
  
 
  $model = new CC_ModulModel("churchservice");  
  $model->addField("churchservice_entries_last_days","", "INPUT_REQUIRED","Wieviel Tage zur&uuml;ck in ChurchService-Daten geladen werden");
    $model->fields["churchservice_entries_last_days"]->setValue($config["churchservice_entries_last_days"]);    
  $model->addField("churchservice_openservice_rememberdays","", "INPUT_REQUIRED","Nach wieviel Tagen die Dienstanfrage erneut statt findet, wenn sie noch nicht zugesagt oder abgelehnt wurde");
    $model->fields["churchservice_openservice_rememberdays"]->setValue($config["churchservice_openservice_rememberdays"]);  
  $model->addField("churchservice_reminderhours","", "INPUT_REQUIRED","Wieviele Stunden im Vorfeld eine Erinnerung an den Dienst erfolgen soll");
    $model->fields["churchservice_reminderhours"]->setValue($config["churchservice_reminderhours"]);  
    
  $m["churchservice"]=$model->render();

  
  $model = new CC_ModulModel("churchresource");      
  $model->addField("churchresource_entries_last_days","", "INPUT_REQUIRED","Wieviel Tage zur&uuml;ck in ChurchResource-Daten geladen werden");
    $model->fields["churchresource_entries_last_days"]->setValue($config["churchresource_entries_last_days"]);  
  $m["churchresource"]=$model->render();
  
  $model = new CC_ModulModel("churchcheckin");      
  $m["churchcheckin"]=$model->render();
  
  $model = new CC_ModulModel("churchcal");      
  $m["churchcal"]=$model->render();
  
  $model = new CC_ModulModel("churchwiki");      
  $m["churchwiki"]=$model->render();
  
  $txt='<h1>Einstellungen f&uuml;r die Website</h1><p>Der Administrator kann hier Einstellung vornehmen. Diese gelten f&uuml;r alle Benutzer, bitte vorsichtig anpassen!</p>';
  $txt.='<div class="tabbable">';
  $arr=churchcore_getModulesSorted();
  $txt.='<ul class="nav nav-tabs">';
    $txt.='<li class="active"><a href="#tab1" data-toggle="tab">Allgemein</a></li>';
    foreach ($arr as $module) {
      if ($config[$module."_name"]!="")
        $txt.='<li><a href="#tab'.$module.'" data-toggle="tab">'.$config[$module."_name"].'</a></li>';
    }
    $txt.='</ul>';
  $txt.='<div class="tab-content">';
    $txt.='<div class="tab-pane active" id="tab1">';
      $txt.=$txt_general;  
    $txt.='</div>';
    foreach($arr as $module) {
      $txt.='<div class="tab-pane" id="tab'.$module.'">';
        $txt.=$m[$module];    
      $txt.='</div>';
    }
    
  $txt.='</div></div>';
  
  return $txt;
}

?>
