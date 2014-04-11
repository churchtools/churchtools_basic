<?php

function churchdb__ajax() {
  include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
  call_user_func("churchdb_ajax");
}

function churchdb_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 121,'view birthdaylist', 'churchdb', null, 'Geburtagsliste einsehen', 1);
  $cc_auth=addAuth($cc_auth, 122,'view memberliste', 'churchdb', null, 'Mitgliederliste einsehen', 1);
  
  $cc_auth=addAuth($cc_auth, 101,'view', 'churchdb', null, 'ChurchDB sehen', 1);
  $cc_auth=addAuth($cc_auth, 106,'view statistics', 'churchdb', null, 'Gesamtstatistik einsehen', 1);
  $cc_auth=addAuth($cc_auth, 107,'view tags', 'churchdb', null, 'Tags einsehen', 1);
  $cc_auth=addAuth($cc_auth, 108,'view history', 'churchdb', null, 'Historie eines Datensatzes ansehen', 1);
  $cc_auth=addAuth($cc_auth, 113,'view comments', 'churchdb', 'cdb_comment_viewer', 'Kommentare einsehen', 1);
  $cc_auth=addAuth($cc_auth, 105,'view address', 'churchdb', null, 'Zus&auml;tzlich Adressdaten der sichtbaren Personen einsehen (Strasse)', 1);
  $cc_auth=addAuth($cc_auth, 103,'view alldetails', 'churchdb', null, 'Alle Informationen der sichtbaren Person sehen, inkl. Adressdaten, Gruppenzuordnung, etc.', 1);
  $cc_auth=addAuth($cc_auth, 116,'view archive', 'churchdb', null, 'Personen-Archiv einsehen', 1);
  $cc_auth=addAuth($cc_auth, 120,'complex filter', 'churchdb', null, '"Weitere Filter" darf verwendet werden', 1);
  $cc_auth=addAuth($cc_auth, 118,'push/pull archive', 'churchdb', null, 'Personen ins Archiv verschieben und zur&uuml;ckholen', 1);
  $cc_auth=addAuth($cc_auth, 109,'edit relations', 'churchdb', null, 'Beziehungen der sichtbaren Personen editieren', 1);
  $cc_auth=addAuth($cc_auth, 110,'edit groups', 'churchdb', null, 'Alle Gruppenzuordnungen der sichtbaren Personen editieren', 1);
  $cc_auth=addAuth($cc_auth, 119,'create person', 'churchdb', null, 'Darf Personen erstellen', 1);
  $cc_auth=addAuth($cc_auth, 111,'write access', 'churchdb', null, 'Schreibzugriff auf alle sichtbaren Personen', 1);
  $cc_auth=addAuth($cc_auth, 102,'view alldata', 'churchdb', 'cdb_bereich', 'Alle Personen des jeweiligen Bereiches sichtbar machen', 1);
  $cc_auth=addAuth($cc_auth, 117,'send sms', 'churchdb', null, 'SMS-Schnittstelle verwenden', 1);
  $cc_auth=addAuth($cc_auth, 112,'export data', 'churchdb', null, 'Die Daten aller(!) Personen exportieren', 1);
  
  $cc_auth=addAuth($cc_auth, 115,'view group', 'churchdb', 'cdb_gruppe', 'Einzelne Gruppen einsehen - inklusive versteckte Gruppen', 0);
  $cc_auth=addAuth($cc_auth, 104,'view group statistics', 'churchdb', null, 'Gruppenstatistik aller Gruppen einsehen', 1);  
  $cc_auth=addAuth($cc_auth, 114,'administer groups', 'churchdb', null, 'Gruppen administrieren, d.h. erstellen, l&ouml;schen, etc.', 1);
  
  $cc_auth=addAuth($cc_auth, 199,'edit masterdata', 'churchdb', null, 'Stammdaten editieren', 1);
  return $cc_auth;
}

function churchdb_getAdminModel() {
  global $config;
  
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

  return $model;  
}

function churchdb_main() {
  
  global $user;
  //drupal_add_css(drupal_get_path('module', 'churchcore').'/churchcore_bootstrap.css');
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  
  drupal_add_js('system/assets/flot/jquery.flot.min.js'); 
  drupal_add_js('system/assets/flot/jquery.flot.pie.js'); 
  drupal_add_js('system/assets/js/jquery.history.js'); 

  drupal_add_js('system/assets/ui/jquery.ui.slider.min.js');
  
  drupal_add_js('system/assets/fileuploader/fileuploader.js'); 
  
  drupal_add_js('system/assets/ckeditor/ckeditor.js');
  drupal_add_js('system/assets/ckeditor/lang/de.js');  
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_cdbstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_geocode.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_loadandmap.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_settingsview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_importview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_personview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_archiveview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_groupview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_statisticview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_mapview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_maintainview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_main.js'); 
  
  drupal_add_js(createI18nFile("churchdb"));
  
  // API v3
  $content='<script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=true"></script>';

  // Übergabe der ID für den Direkteinstieg einer Person
  if (isset($_GET["id"]) && ($_GET["id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"filter_id\" value=\"".$_GET["id"]."\"/>";

  $content=$content."
<div class=\"row-fluid\">
  <div class=\"span3\">
    <div id=\"cdb_menu\"></div>
    <div id=\"cdb_todos\"></div>
    <div id=\"cdb_filter\"></div>
  </div>  
  <div class=\"span9\">
    <div id=\"cdb_info\"></div> 
    <div id=\"cdb_search\"></div> 
    <div id=\"cdb_precontent\"></div>
    <div id=\"cdb_group\"></div> 
    <div id=\"cdb_content\"></div>
  </div>
</div>";  
  
  return $content;
}

function externmapview_main() {
    
  global $user;
  //drupal_add_css(drupal_get_path('module', 'churchcore').'/churchcore_bootstrap.css');
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/shortcut.js'); 
  drupal_add_css('system/assets/ui/jquery-ui-1.8.18.custom.css');
  
  drupal_add_js('system/assets/js/jquery.history.js'); 
  drupal_add_js('system/assets/ui/jquery.ui.core.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.position.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.widget.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.dialog.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.mouse.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.draggable.min.js');
  drupal_add_js('system/assets/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js('system/assets/fileuploader/fileuploader.js'); 
  
  drupal_add_js('system/assets/ckeditor/ckeditor.js');
  drupal_add_js('system/assets/ckeditor/lang/de.js');  
  
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchcore.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/churchforms.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_interface.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_cdbstandardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_geocode.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_loadandmap.js'); 
  //drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_mapview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchdb') .'/cdb_externgroupview.js'); 

  // API v3
  $content='<script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=true"></script>';

  // Übergabe der ID für den Direkteinstieg einer Person
  if (isset($_GET["g_id"]) && ($_GET["g_id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"g_id\" value=\"".$_GET["g_id"]."\"/>";

  $content=$content."
    <div id=\"cdb_content\" style=\"width:100%;height:500px\"></div>";
  
  return $content;
}


function getExternalGroupData() {
  global $user;
  $res=db_query("select id, bezeichnung, treffzeit, zielgruppe, max_teilnehmer, 
            geolat, geolng, treffname, versteckt_yn, valid_yn, distrikt_id, offen_yn, oeffentlich_yn
            from {cdb_gruppe} where oeffentlich_yn=1 and versteckt_yn=0 and valid_yn=1");
  $arr=array();
  foreach ($res as $g) {
    $db=db_query("select status_no from {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
                 where gp.id=gpg.gemeindeperson_id and gpg.gruppe_id=:gruppe_id 
                    and gp.person_id=:person_id", array(":gruppe_id"=>$g->id, ":person_id"=>$user->id))->fetch();
    if ($db!=false)
      $g->status_no=$db->status_no;
    $arr[$g->id]=$g;    
  }
  return $arr;
}

function sendConfirmationMail($mail, $vorname="", $g_id) {
  $g=db_query("select * from {cdb_gruppe} where id=:id", array(":id"=>$g_id))->fetch();
  if ($g!=false) {
    $inhalt="<h3>Hallo $vorname!</h3><p>";
    $inhalt.="Dein Antrag f&uuml;r die Gruppe <i>$g->bezeichnung</i> ist eingegangen. <p>Vielen Dank!";
    $res = churchcore_mail(variable_get('site_mail'), $mail, "[".variable_get('site_name')."] Teilnahmeantrag zur Gruppe ".$g->bezeichnung, $inhalt, true, true, 2);
  }
}

function externmapview__ajax() {
  global $user;
  $func=$_GET["func"];
  if ($func=='loadMasterData') {
    $res["home_lat"] = variable_get('churchdb_home_lat', '53.568537');
    $res["home_lng"] = variable_get('churchdb_home_lng', '10.03656');
    $res["districts"]=churchcore_getTableData("cdb_distrikt", "bezeichnung");      
    $res["groups"]=getExternalGroupData();
    $res["modulespath"] = drupal_get_path('module', 'churchdb');
    $res["user_pid"] =$user->id;
    $res["vorname"]=$user->vorname;
    $res=jsend()->success($res);    
  }
  else if ($func=='addPersonGroupRelation') {
    include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
    $res=churchdb_addPersonGroupRelation($user->id, $_GET["g_id"], -2, null, null, null, "Anfrage &uuml;ber externe MapView");
    sendConfirmationMail($user->email, $user->vorname, $_GET["g_id"]);    
    $res=jsend()->success($res);
  }
  else if ($func=='editPersonGroupRelation') {
    include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
    $res=_churchdb_editPersonGroupRelation($user->id,
       $_GET["g_id"], -2,null, "null", "Anfrage ge&auml;ndert &uuml;ber externe MapView");
    sendConfirmationMail($user->email, $user->vorname, $_GET["g_id"]);    
    $res=jsend()->success($res);
  }
  else if ($func=='sendEMail') {
    $db=db_query('select * from {cdb_person} where upper(email) like upper(:email) and upper(vorname) like upper(:vorname) and upper(name) like upper(:name)',
      array(':email'=>$_GET["E-Mail-Adresse"],
      ':vorname'=>$_GET["Vorname"],
      ':name'=>$_GET["Nachname"])
      )->fetch();
    $txt="";  
    if ($db!=false) {
      include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
      churchdb_addPersonGroupRelation($db->id, $_GET["g_id"], -2, null, null, null, "Anfrage &uuml;ber externe MapView: ".$_GET["Kommentar"]);
      sendConfirmationMail($_GET["E-Mail-Adresse"], $_GET["Vorname"], $_GET["g_id"]);    
      $txt="Person gefunden und Anfrage wurde gesendet!";      
    } 
    else {      
      $res=db_query("select vorname, p.id id, g.bezeichnung from {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp, 
            {cdb_person} p, {cdb_gruppe} g
             where gpg.gemeindeperson_id=gp.id and gp.person_id=p.id and g.id=:gruppe_id 
             and gpg.gruppe_id=g.id and status_no>=1 and status_no!=4",
          array(":gruppe_id"=>$_GET["g_id"]));
      $rec=array();    
      foreach ($res as $p) {
        $rec[]=$p->vorname;
        $inhalt="<h4>Anfrage zur Gruppe ".$p->bezeichnung."<h4/>";
        $inhalt.="<ul><li>Vorname: ".$_GET["Vorname"];
        $inhalt.="<li>Nachname: ".$_GET["Nachname"];
        $inhalt.="<li>E-Mail: ".$_GET["E-Mail-Adresse"];
        $inhalt.="<li>Telefon: ".$_GET["Telefon"];
        $inhalt.="<li>Kommentar: ".$_GET["Kommentar"];
        $inhalt.="</ul>";
        $res = churchcore_sendEMailToPersonIds($p->id, "[".variable_get('site_name', 'drupal')."] Formular-Anfrage zur Gruppe ".$p->bezeichnung, $inhalt, variable_get('site_mail'), true, true);            
      }
      if (count($rec)==0)
        $txt="Konnte leider keinen Leiter in der Gruppe finden. Bitte versuchen Sie es auf einem anderen Wege!";
      else {    
        $txt="Es wurde eine E-Mail an ".implode($rec," und ")." gesendet!";
        sendConfirmationMail($_GET["E-Mail-Adresse"], $_GET["Vorname"], $_GET["g_id"]);
      }
    }  
    $res=jsend()->success($txt);    
  }    
  else {
    $res=jsend()->fail("Unbekannter Aufruf: ".$func);
  }
  drupal_json_output($res);
}


function getBirthdaylistContent($desc, $diff_from, $diff_to, $extended=false) {
  global $base_url, $files_dir;
  $txt="";
  $compact=false;
  if (isset($_GET["compact"])) $compact=true;
  
  if (($extended) && (!user_access("view birthdaylist","churchdb"))) 
    die("Nicht genug rechte");
    
    include_once("churchdb_db.inc");
    
    $see_details=(user_access("view","churchdb")) && (user_access("view alldata","churchdb"));
    
    $res = getBirthdayList($diff_from, $diff_to);
    if ($res!=null) {
      if ($desc!="") $txt.="<p><h4>$desc</h4>";
      if ($extended) {
        $txt.="<table class=\"table table-condensed\"><tr><th style=\"max-width:65px;\"><th>Name".(!$compact?"<th>Alter":"")."<th>Geburtsdatum";
 	     	if ($see_details)
          $txt.="<th>Status<th>Station<th>Bereich";
      }
      foreach ($res as $arr) {
        //if ($extended) 
          $txt.="<tr><td>";
        // Die naechsten Geb. muessen natuerlich noch einen Altersjahr dazu bekommen, wir wollen ja wissen wie alt sie werden.
        if ($diff_from>0) $arr->age=$arr->age+1;

        // link zum Direkteinstieg in die DB
	    if ($extended) {
          if ($arr->imageurl==null)
            $arr->imageurl="nobody.gif";
          $txt.="<img class=\"\" width=\"42px\" style=\"max-width:42px;\" src=\"$base_url$files_dir/fotos/".$arr->imageurl."\"/>";
          $txt.="<td>";
  		  if ($see_details)
		    $txt.="<a data-person-id=\"$arr->person_id\" href=\"$base_url?q=churchdb#PersonView/searchEntry:#".$arr->person_id."\">";
          $txt.=$arr->vorname." ";
          if ((isset($arr->spitzname)) && ($arr->spitzname!="")) 
            $txt.="($arr->spitzname) ";
          $txt.=$arr->name.(!$compact?"<td> ".$arr->age:"")."<td>".(!$compact?$arr->geburtsdatum_d:$arr->geburtsdatum_compact);
          
          if ($see_details)          
          $txt.=" <td> ".$arr->status."<td>".$arr->bezeichnung."<td>".$arr->bereich;
        }            
  	    else {	
          if ($arr->imageurl==null)
            $arr->imageurl="nobody.gif";
          if ($see_details) 
		    $txt.="<a data-person-id=\"$arr->person_id\" href=\"$base_url?q=churchdb#PersonView/searchEntry:#".$arr->person_id."\">";
          $txt.="<img class=\"\" width=\"42px\" style=\"max-width:42px;\" src=\"$base_url$files_dir/fotos/".$arr->imageurl."\"/>";
          if ($see_details) 
            $txt.="</a>";
          $txt.="<td>";
  	      if ($see_details) $txt.="<a class=\"tooltip-person\" data-id=\"$arr->person_id\" href=\"$base_url?q=churchdb#PersonView/searchEntry:#".$arr->person_id."\">";
          $txt.=$arr->vorname." ";
          if ((isset($arr->spitzname)) && ($arr->spitzname!="")) 
            $txt.="($arr->spitzname) ";
          $txt.=$arr->name;
            if ($see_details) $txt.="</a>";		
          if ($see_details) 
            $txt.="<td>".$arr->age."";
        }  

    }
    if ($extended) 
      $txt.="</table><p>&nbsp;</p>";
    }  
    
  //}
  return $txt;	
}

function getWhoIsOnline() {
  global $user;
  if (!user_access("view whoisonline","churchcore"))
    return null;
  $dt = new DateTime();
  $res=db_query("select p.id, vorname, name, hostname, s.datum from {cdb_person} p, {cc_session} s where s.person_id=p.id order by name, vorname");
  $txt="";
  
  foreach ($res as $p) {
    $test=new DateTime($p->datum);
    $seconds=$dt->format('U') - $test->format('U');
    
    if ($seconds<300) {
      $txt.="<li>".$p->vorname." ".$p->name;
    }
  }
  if ($txt!="")
    $txt="<ul>$txt</ul>";
  return $txt;
}

function subscribeGroup() {
  global $user;
  include_once(drupal_get_path('module', 'churchdb').'/churchdb_db.inc');
  
  $sql_gruppenteilnahme="select g.bezeichnung, gpg.* from {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp, {cdb_gruppe} g 
                   where gpg.gemeindeperson_id=gp.id and gp.person_id=:person_id 
                   and gpg.gruppe_id=g.id and g.id=:g_id";
  
  if ((isset($_GET["subscribegroup"])) && ($_GET["subscribegroup"]>0)) {
    $res=db_query("select * from {cdb_gruppe} where id=:id and offen_yn=1",
        array(":id"=>$_GET["subscribegroup"]))->fetch();
    if (!$res)
      addErrorMessage("Gruppenteilnahme konnte nicht beantragt werden.");
    else {
      include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
      $grp=db_query($sql_gruppenteilnahme,
        array(":person_id"=>$user->id, ":g_id"=>$_GET["subscribegroup"]))->fetch();
      if (!$grp)     
        churchdb_addPersonGroupRelation($user->id, $res->id, -2, null, null, null, "Anfrage &uuml;ber Formular");
      else  
        _churchdb_editPersonGroupRelation($user->id, $res->id, -2, null, "null", "Beendung angefragt durch Formular");
      addInfoMessage("Die Teilnahme an <i>$res->bezeichnung</i> ist nun beantragt, der Leiter wird informiert. Vielen Dank!");      
    }          
  }
  if ((isset($_GET["unsubscribegroup"])) && ($_GET["unsubscribegroup"]>0)) {
    $res=db_query($sql_gruppenteilnahme,
        array(":person_id"=>$user->id, ":g_id"=>$_GET["unsubscribegroup"]))->fetch();
    if (!$res)
      addErrorMessage("Gruppenteilnahme konnte nicht beendet werden.");
    else {
      include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
      _churchdb_editPersonGroupRelation($user->id, $res->gruppe_id, -1, null, "null", "Beendung angefragt durch Formular");
      addInfoMessage("Die Teilnahme an <i>$res->bezeichnung</i> wurde als zu l&ouml;schen markiert.");      
    }          
  }
  
  // Hole erst mal meine Gruppen in denen ich TN bin oder schon angefragt hatte
  $res=db_query("select gpg.gruppe_id, status_no from {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
         where gpg.gemeindeperson_id=gp.id and gp.person_id=$user->id");
  $mygroups=array();
  foreach ($res as $p) {
    $mygroups[$p->gruppe_id]=$p;
  }
  
  // Hole nun alle offenen Gruppen
  $res=db_query("select * from {cdb_gruppe} p where offen_yn=1 and 
                       ((abschlussdatum is null) or (DATE_ADD( abschlussdatum, INTERVAL 1  DAY ) > NOW( )))");
  $txt="";
  $txt_subscribe="";  
  $txt_unsubscribe="";  
  foreach ($res as $g) {
    // Nehmen Gruppe wo ich nicht drin bin
    if ((!isset($mygroups[$g->id])) || ($mygroups[$g->id]->status_no==-1)) {
      if (($g->max_teilnehmer==null) || (churchdb_countMembersInGroup($g->id)<$g->max_teilnehmer)) {  
        $txt_subscribe.="<option value=\"".$g->id."\">".$g->bezeichnung;       
        if ($g->max_teilnehmer!=null)
          $txt_subscribe.=" (max. $g->max_teilnehmer)";
        
      }
    }
    // Nehmen Gruppe wo ich drin 
    else if ($mygroups[$g->id]->status_no<=0) {      
      $txt_unsubscribe.='<option value="'.$g->id.'">'.$g->bezeichnung;             
      if ($mygroups[$g->id]->status_no==-2) 
        $txt_unsubscribe.="  [beantragt]";
    }
  }
  if (($txt_subscribe!="") || ($txt_unsubscribe)) {    
    $txt='<form method="GET" action="?q=home">';
    if ($txt_subscribe!="")
      $txt.='<p>Hier kannst Du eine Teilnahme beantragen:<p><select name="subscribegroup"><option>'.$txt_subscribe.'</select>';
    if ($txt_unsubscribe!="")
      $txt.='<p>Hier kannst Du eine Teilnahme beenden:<p><select name="unsubscribegroup"><option>'.$txt_unsubscribe.'</select>';
    $txt.='<P><button class="btn" type="submit" name="btn">Absenden</button>';
    $txt.='</form>';
  }  
  
  return $txt;
}

function churchdb_getBlockBirthdays() {
  
  $txt="";
  if (user_access("view birthdaylist","churchdb")) {
    $t2=getBirthdaylistContent("",-1,-1);
    if ($t2!="") $txt.='<tr><th colspan="3">Gestern'.$t2;
    $t2=getBirthdaylistContent("",0,0);
    if ($t2!="") $txt.='<tr><th colspan="3">Heute'.$t2;
    $t2=getBirthdaylistContent("",1,1);
    if ($t2!="") $txt.='<tr><th colspan="3">Morgen'.$t2;
    if ($txt!="") {
      $txt="<table class=\"table table-condensed\">".$txt."</table>";
    }
    if ((user_access("view","churchdb")) && (user_access("view birthdaylist","churchdb"))) 
      $txt.="<p style=\"line-height:100%\" align=\"right\"><a href=\"?q=churchdb/birthdaylist\">Weitere Geburtstage</a>";
  }
  if (user_access("view memberliste","churchdb")) 
    $txt.="<p style=\"line-height:100%\" align=\"right\"><a href=\"?q=home/memberlist\">Mitgliederliste</a>";
      
  return $txt;
}

function churchdb_getTodos() {
  global $user;
  $mygroups=churchdb_getMyGroups($user->id, true, true, false);
  $mysupergroups=churchdb_getMyGroups($user->id, true, true, true);
  if ($mygroups==null) return "";
  if ($mysupergroups==null) $mysupergroups=array(-1);
  $db=db_query("select p.id, p.vorname, p.name, g.bezeichnung, gpg.status_no, s.bezeichnung status
           from {cdb_person} p, {cdb_gruppe} g, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppenteilnehmerstatus} s 
           where s.intern_code=gpg.status_no and
           gpg.gemeindeperson_id=gp.id and gp.person_id=p.id and gpg.gruppe_id=g.id and
           ((gpg.gruppe_id in (".implode(',',$mygroups).") and gpg.status_no<-1) 
           or (gpg.gruppe_id in (".implode(',',$mysupergroups).") and gpg.status_no=-1))
           order by status");

  $arr=array();
  if ($db==false) return "";
  foreach ($db as $g) {
    if (isset($arr[$g->status_no]))
      $a=$arr[$g->status_no];
    else $a=(object) array();
    if (isset($a->content))
      $c=$a->content;
    else $c=array();              
    $c[]=$g;
    $a->content=$c;
    $a->status_no=$g->status_no;
    $a->status=$g->status;
    $arr[$g->status_no]=$a;
  }
  $txt="";
  $entries="";
  $status="";
  $count=0;
  foreach ($arr as $status) {
    $txt.='<li><p>'.$status->status.' &nbsp;<label class="pull-right badge badge-'.($status->status_no==-1?"important":"info").'">'.count($status->content).'</label>';
    foreach ($status->content as $g) {
      $txt.='<br/><small><a href="?q=churchdb#PersonView/searchEntry:#'.$g->id.'">'.$g->vorname.' '.$g->name.'</a>';
      $txt.=' - '.$g->bezeichnung.'</small>';
    }
  }  
  if ($txt!="") 
    $txt='<ul>'.$txt.'</ul>';
  return $txt;
}

function churchdb_getForum() {
  return '<div id="cc_forum"></div>';
}

function churchdb_getBlockLookPerson() {
  if ((!user_access("view birthdaylist","churchdb")) && (!user_access("view","churchdb")))
    return null;
  
  $txt="moin";
  return $txt;
}

function churchdb_blocks() {
  global $config;
  return (array(
    1=>array(
      "label"=>"Geburtstage",
      "col"=>1,
      "sortkey"=>1,
      "html"=>churchdb_getBlockBirthdays()
    ),  
    2=>array(
      "label"=>"Wer ist online?",
      "col"=>1,
      "sortkey"=>3,
      "html"=>getWhoIsOnline()
    ),  
    3=>array(
      "label"=>"Teilnahme verwalten",
      "col"=>1,
      "sortkey"=>2,
      "html"=>subscribeGroup()
    ),  
    4=>array(
      "label"=>"Aufgaben in ".$config["churchdb_name"],
      "col"=>2,
      "sortkey"=>1,
      "html"=>churchdb_getTodos()
    ),  
    5=>array(
      "label"=>"ChurchMailer",
      "col"=>1,
      "sortkey"=>2,
      "html"=>churchdb_getForum()
    ),  
    ));
} 

function churchdb__birthdaylist() {
  $txt="<ul>".getBirthdaylistContent("Letzten 7 Tage",-7,-1, true).
	          getBirthdaylistContent("Heute",0, 0, true).
	          getBirthdaylistContent("Nächsten 30 Tage", 1, 30, true)."</ul>";
  if (user_access("view memberliste","churchdb")) 
    $txt.="<p style=\"line-height:100%\" align=\"right\"><a href=\"?q=home/memberlist\">Mitgliederliste</a>";
	          
  return $txt;  
}  



function churchdb__vcard() {
  $id=$_GET["id"];
  drupal_add_http_header('Content-type','text/x-vCard; charset=ISO-8859-1; encoding=ISO-8859-1',true);
  drupal_add_http_header('Content-Disposition','attachment; filename="vcard'.$id.'.vcf"',true);
  include_once("churchdb_db.inc");

  $sql="
    SELECT  concat(
    'BEGIN:VCARD\n','VERSION:3.0\n',
	'N:',name,';',vorname,'\n',
    'NICKNAME:',spitzname,'\n',      
	'EMAIL;TYPE=INTERNET:',email,'\n',
	'TEL;type=HOME;type=VOICE:',telefonprivat,'\n',
	'TEL;type=WORK;type=VOICE:',telefongeschaeftlich,'\n',
	'TEL;type=CELL;type=VOICE;type=pref:',telefonhandy,'\n',";
  
  if (user_access("view alldetails", "churchdb"))
    $sql.="'ADR;TYPE=HOME;type=pref:;',zusatz,';',strasse,';',ort,';;',plz,';',land,'\n',
    if(geburtsdatum is null,'',concat('BDAY:',geburtsdatum,'\n')),";
  $sql.="'END:VCARD'
      ) vcard FROM {cdb_person} p, {cdb_gemeindeperson} gp WHERE gp.person_id=p.id and p.id = :id";
    
  $person = db_query($sql, array(":id"=>$id))->fetch();
  echo $person->vcard;
}
  
function churchdb__export() {
  drupal_add_http_header('Content-type', 'application/csv; charset=ISO-8859-1; encoding=ISO-8859-1',true);
  drupal_add_http_header('Content-Disposition', 'attachment; filename="churchdb_export.csv"',true);
  include_once("churchdb_db.inc");

  if (isset($_GET["ids"]))
    $ids="and p.id in (".$_GET["ids"].")";
  else $ids="";  
  
  $ps=churchdb_getAllowedPersonData();
  $allowed_ids=array();
  foreach ($ps as $p) {
    $allowed_ids[]=$p->p_id;
  }

  if (user_access("view alldetails","churchdb"))
    $persons_sql = 'SELECT station.bezeichnung station, (case when geschlecht_no=1 then \'Herr\' when geschlecht_no=2 then \'Frau\' else \'\' end) "anrede", vorname, name, strasse adresse, plz,
              ort, land, n.bezeichnung nationalitaet, telefonprivat "tel. priv.", email "e-mail", telefongeschaeftlich "tel. büro", telefonhandy "handy",
  			null bemerkung, DATE_FORMAT(eintrittsdatum , \'%d.%m.%Y\') "mitglied seit", status.kuerzel status,
  			DATE_FORMAT(taufdatum, \'%d.%m.%Y\') getauft, taufort, getauftdurch "getauft durch", ueberwiesenvon "Überwiesen von", 
  			day(geburtsdatum) "geb.tag", month(geburtsdatum) "geb.m.", year(geburtsdatum) "geb.jahr", f.bezeichnung "f.stand", 
  			geburtsname "geb.name", DATE_FORMAT(hochzeitsdatum , \'%d.%m.%Y\') "hochzeitsdatum", geburtsort "geb.ort", beruf, titel "titel",
  			(case when geschlecht_no=1 then \'Lieber\' when geschlecht_no=2 then \'Liebe\' else \'\' end) "anrede2",
  			bereich_id, b.bezeichnung "bereich", 
  			day(eintrittsdatum) "mitgliedseit.tag", month(eintrittsdatum) "mitgliedseit.m", year(eintrittsdatum) "mitgliedseit.jahr",
              (year(curdate())-year(geburtsdatum) - (RIGHT(CURDATE(),5)<RIGHT(geburtsdatum,5))) as "alter",p.id id, null as "e-mail_beziehung", null as "vorname2", optigem_nr, spitzname, zusatz
             FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_bereich_person} bp, {cdb_bereich} b,
  		        {cdb_station} station, {cdb_status} status, {cdb_familienstand} f,
  		        {cdb_nationalitaet} n
                    WHERE p.id=gp.person_id 
  				        and p.id=bp.person_id 
  				        and bp.bereich_id=b.id 
  						and gp.status_id=status.id
  						and gp.station_id=station.id
  						and gp.familienstand_no=f.id
                        and gp.nationalitaet_id=n.id
                        and p.id in ('.implode(",", $allowed_ids).')';
  else
    $persons_sql = 'SELECT station.bezeichnung station, (case when geschlecht_no=1 then \'Herr\' when geschlecht_no=2 then \'Frau\' else \'\' end) "anrede", vorname, name, spitzname, plz,
              ort, telefonprivat "tel. priv.", email "e-mail", telefongeschaeftlich "tel. büro", telefonhandy "handy",
        day(geburtsdatum) "geb.tag", month(geburtsdatum) "geb.m.", year(geburtsdatum) "geb.jahr", 
        bereich_id, b.bezeichnung "bereich",
              (year(curdate())-year(geburtsdatum) - (RIGHT(CURDATE(),5)<RIGHT(geburtsdatum,5))) as "alter",p.id id
             FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_bereich_person} bp, {cdb_bereich} b,
              {cdb_station} station
                    WHERE p.id=gp.person_id 
                  and p.id=bp.person_id 
                  and bp.bereich_id=b.id 
              and gp.station_id=station.id
              and p.id in ('.implode(",", $allowed_ids).')';
      
	$persons = db_query($persons_sql.$ids.' ORDER BY name, vorname, id, b.sortkey ');			  
  // Zuerst werden die Daten in ein Array gepackt und dabei nach Bereich verdichtet, 
  // so dass eine Person in mehreren Bereichen auch nur 1x aufgefuehrt wird.
  // Die Bereiche werden dann per "," getrennt
  $export= array();
  
  foreach ($persons as $arr) {
    if (isset($arr->{'geb.jahr'})) { 
      if ($arr->{'geb.jahr'}>=7000) {
        $arr->{'geb.tag'}="";
        $arr->{'geb.m.'}="";
        $arr->{'geb.jahr'}=$arr->{'geb.jahr'}-7000;
      }
      else if ($arr->{'geb.jahr'}==1004) {
        $arr->{'geb.jahr'}="";
      }     
    }
    
    // Wenn schon benutzt, dann nehme das
    if (isset($export[$arr->id]))
      $person=$export[$arr->id];
    else $person=array();  
    foreach ($arr as $a=>$key) {
      // Dies dient der Verdichtung nach Bereich
      if (($a=="bereich") && (isset($person["bereich"]))) {
        $person[$a]=$person[$a]."::".$key;        
      } 
      else if (($a=="bereich_id") && (isset($person["bereich_id"]))) {
        $person[$a]=$person[$a]."::".$key;        
      } 
      else  
        $person[$a]=$key;
    }
    $export[$arr->id]=$person;
  }

  // Hier werden wenn nach Beziehung gefiltert wird auch noch die verknuepften Personen
  // mitgeladen und exportiert.
  foreach ($export as $entry) { 
    if ((isset($_GET["rel_part"])) && ($_GET["rel_part"]!=null) && ($_GET["rel_id"]!=null)) {
      $id=null;
      if ($_GET["rel_part"]=="k") {
        $rel=db_query("select * from {cdb_beziehung} where beziehungstyp_id=".$_GET["rel_id"]." and vater_id=".$entry["id"])->fetch();
        $id=$rel->kind_id;
      }
      else {
        $rel=db_query("select * from {cdb_beziehung} where beziehungstyp_id=".$_GET["rel_id"]." and kind_id=".$entry["id"])->fetch();
        $id=$rel->vater_id;
      }
      // Wenn wirklich eine Beziehung gefunden wurde
      if ($id!=null) {
        $person = db_query($persons_sql.' AND p.id='.$id)->fetch();
        foreach ($person as $key=>$value) {       
          $export[$entry["id"]][$_GET["rel_part"]."_".$key]=$value;
        }
      }  
    }
  }
  
  // Nun werden die Beziehungen geprueft und entsprechende Saetze zusammengefasst, falls gewuenscht
  $rels=getAllRelations();
  if ($rels!=null) {
    $rel_types=getAllRelationTypes();
    foreach ($rels as $rel) {
      if ((isset($_GET["agg".$rel->typ_id])) && ($_GET["agg".$rel->typ_id]=="y") && (isset($export[$rel->v_id])) && (isset($export[$rel->k_id]))) {
        // Wir nehmen den Mann als Kopf des Ehepars
        if ($export[$rel->v_id]["anrede2"]=="Lieber") {
          $p1=$rel->v_id; $p2=$rel->k_id;
        } else {
          $p1=$rel->k_id; $p2=$rel->v_id;
        }
        // Fuegen dem Mann die andere zuerst zu
        $export[$p1]["anrede"]=$rel_types[$rel->typ_id]->export_title;
        $export[$p1]["anrede2"]=$export[$p2]["anrede2"]." ".$export[$p2]["vorname"].", ".$export[$p1]["anrede2"];
        $export[$p1]["vorname2"]=$export[$p2]["vorname"];
        if (isset($export[$p2]["e-mail"]))
          $export[$p1]["e-mail_beziehung"]=$export[$p2]["e-mail"];
        // Und nehmen den anderen aus dem Export raus
        $export[$p2]=null;
      }   
    }
  }
  
  // Now check if there is group_id which I can add group relation Infos to the export
  if (isset($_GET["groupid"])) {
    foreach ($export as $k=>$key) {
      $r=db_query("select g.bezeichnung, s.bezeichnung status, DATE_FORMAT(gpg.letzteaenderung, '%d.%m.%Y') letzteaenderung, gpg.comment 
               from {cdb_gruppe} g, {cdb_gemeindeperson} gp, 
                     {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppenteilnehmerstatus} s  
                  where gp.id=gpg.gemeindeperson_id and g.id=:gruppe_id 
                         and s.intern_code=status_no
                       and gpg.gruppe_id=g.id and gp.person_id=:person_id", 
                array(":gruppe_id"=>$_GET["groupid"], ":person_id"=>$key["id"]))->fetch();
      if ($r!=false) {
        $export[$k]["gruppe"]=$r->bezeichnung;
        $export[$k]["gruppe_seit"]=$r->letzteaenderung;
        $export[$k]["gruppe_kommentar"]=$r->comment;
        $export[$k]["gruppe_status"]=$r->status;
      }
    }    
  }
  
  
  // Nun werden die Daten ueber Echo ausgegeben
  $header=true;
  foreach ($export as $key) {
    if (($header) && ($key!=null)) {
      foreach ($key as $a=>$val) {
        echo mb_convert_encoding('"'.$a.'";', 'ISO-8859-1', 'UTF-8');
      }
      $header=false;
      echo "\n";
    }  
    if ($key!=null) {
      foreach ($key as $val) {
        echo mb_convert_encoding('"'.$val.'";', 'ISO-8859-1', 'UTF-8');    
      }
      echo "\n";
    }  
  }
}


function churchdb__mailviewer() {
  global $user, $config;
  if ((!user_access("view","churchdb")) || ($user->email=="")) return t("no.permission.for", $config["churchdb_name"]);
  
  $limit=200;
  if (isset($_GET["showmore"]))
    $limit=1000;
    
  if (user_access("administer settings", "churchcore"))
    $filter="1=1";
  else   
    $filter="modified_pid=$user->id and sender!='".$config["site_mail"]."'";
    
  if (isset($_GET["id"])) $filter.=" and id=".$_GET["id"];    
    
  $val="";  
  if ((isset($_GET["filter"])) && ($_GET["filter"]!="")) {
    $filter.=" and (subject like '%".$_GET["filter"]."%' or body like '%".$_GET["filter"]."%')";
    $val=$_GET["filter"];
  }
  $txt='<anchor id="log1"/><h2>'.t("archive.of.sent.messages").'</h2>';
  $res=db_query("select * from {cc_mail_queue} where
						$filter
						order by modified_date desc
						limit $limit");

  $txt.='<form class="form-inline" action="">';
  $txt.='<input type="hidden" name="q" value="churchdb/mailviewer"/>';
  if (!isset($_GET["id"])) 
    $txt.='<input name="filter" class="input-medium" type="text" value="'.$val.'"></input> <input type="submit" class="btn" value="'.t("filter").'"/>';
  else  
    $txt.='<a href="?q=churchdb/mailviewer" class="btn">'.t("back").'</a>';
  $txt.='</form>';  
  
  $txt.='<table class="table table-condensed table-bordered">';
  $txt.="<tr><th>".t("status")."<th>".t("date")."<th>".t("receiver")."<th>".t("sender")."<th>".t("subject")."<th>".t("read");
  $counter=0;
  if ($res!=false)
  foreach ($res as $arr) {
    $txt.="<tr><td>";
    if ($arr->send_date!=null)
      if ($arr->error==0) $txt.='<img title="'.$arr->send_date.'" style="max-width:20px;" src="system/churchcore/images/check-64.png"/>';
      else $txt.='<img title="'.$arr->send_date.'" style="max-width:20px;" src="system/churchcore/images/delete_2.png"/>';
      $txt.="<td>$arr->modified_date<td>$arr->receiver<td>$arr->sender<td><a href=\"?q=churchdb/mailviewer&id=$arr->id\">$arr->subject</a>";
      $txt.="<td>$arr->reading_count";
    $counter++;
  }
  if (isset($_GET["iframe"])) {
    echo $arr->body;
    return null;       
  }
  else if (isset($_GET["id"])) {
    if ($arr->htmlmail_yn==1)
      $txt.='<tr><td colspan=6><iframe width="100%" height="400px" frameborder="0" src="?q=churchdb/mailviewer&id='.$arr->id.'&iframe=true"></iframe>';
    else     
      $txt.='<tr><td colspan=6>'.strtr($arr->body, array("\n"=>"<br>", " "=>"&nbsp;"));
  }
  
  $txt.='</table>';
  if ((!isset($_GET["showmore"])) && ($counter>=$limit))
    $txt.='<a href="?q=churchdb/mailviewer&showmore=true" class="btn">Mehr Zeilen anzeigen</a> &nbsp; ';
    

  return $txt;
}



function churchdb_cron() {
  global $config;
  include_once("churchdb_db.inc");
  
  createGroupMeetings();
  
  // Loesche nichtbenutze Tags
  
  // Schaue in Services, dass sie auch wirklich nicht verwendet werden!
  $services=churchcore_getTableData('cs_service','','cdb_tag_ids is not null');
  $tag=array();
  if ($services!=false) {
    foreach($services as $service) {
      $arr=explode(',',$service->cdb_tag_ids);
      foreach($arr as $ar) {
        if (trim($ar)!='')
          $tag[trim($ar)]=true;
      }
    }
  }
  $res=db_query("SELECT * FROM {cdb_tag} t LEFT JOIN {cdb_gemeindeperson_tag} gpt ON ( t.id = gpt.tag_id )
  					LEFT JOIN {cdb_gruppe_tag} gt ON ( t.id = gt.tag_id )
                WHERE gpt.tag_id IS NULL AND gt.tag_id IS null");
  foreach($res as $id) {
    if (!isset($tag[$id->id])) {
      db_query("delete from {cdb_tag} where id=:id", array(":id"=>$id->id));
      cdb_log("CRON - Loesche Tag Id:".$id->id." ".$id->bezeichnung.", da nicht verwendet",2);
    }    
  }
  
  db_query("update {cdb_person} set loginerrorcount=0");     

  
  // R�ume MAilarchiv auf
  db_query("delete FROM {cc_mail_queue}
    WHERE (DATE_ADD( modified_date, INTERVAL 30  DAY ) < NOW( ))
    and send_date is not null
    and error=0");
  db_query("delete FROM {cc_mail_queue}
    WHERE (DATE_ADD( modified_date, INTERVAL 14  DAY ) < NOW( ))
    and send_date is not null
    and modified_pid=-1
    and error=0");
  db_query("delete FROM {cc_mail_queue}
    WHERE (DATE_ADD( modified_date, INTERVAL 90  DAY ) < NOW( ))");
  
  
  // Synce MailChimp
  if ($config["churchdb_mailchimp_apikey"]!="") {

    include_once("system/assets/mailchimp-api-class/inc/MCAPI.class.php");
    $api = new MCAPI($config["churchdb_mailchimp_apikey"]);
    $list_id=null;
    $db=db_query("select * from {cdb_gruppe_mailchimp} order by mailchimp_list_id");

    foreach ($db as $lists) {
      $list_id=$lists->mailchimp_list_id;
      // Holle alle, die subscribed sind, aber nicht mehr in der Gruppe sind
      $db_g=db_query("select * from 
                   (select * from {cdb_gruppe_mailchimp_person} m where 
                        m.mailchimp_list_id='$list_id' and gruppe_id=:g_id) as m 
           left join  (select gpg.gruppe_id, gp.person_id from {cdb_gemeindeperson_gruppe} gpg, 
               {cdb_gemeindeperson} gp where gp.id=gpg.gemeindeperson_id) gp on (gp.gruppe_id=m.gruppe_id and gp.person_id=m.person_id)
             where gp.person_id is null", array(":g_id"=>$lists->gruppe_id));
      $batch=array();
      foreach ($db_g as $p) {
        $batch[]=array("EMAIL"=>$p->email);
        db_query("delete from {cdb_gruppe_mailchimp_person} where 
               (email=:email and gruppe_id=:g_id and mailchimp_list_id=:list_id)", 
          array(":email"=>$p->email, ":g_id"=>$lists->gruppe_id, ":list_id"=>$list_id));          
      }
      listBatchUnsubscribe($api, $list_id, $batch, $lists->goodbye_yn==1, $lists->notifyunsubscribe_yn==1);    
      
      // Holle alle, die noch nicht subscribed worden sind, also die noch nicht in der Tabel cdb_gruppe_mailchimp_personen sind
      $db_g=db_query("select * from (select p.id p_id, p.vorname, p.name, p.email p_email, gpg.gruppe_id g_id from {cdb_gemeindeperson} gp, {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg
                where gp.person_id=p.id and gpg.gemeindeperson_id=gp.id and gpg.status_no>=0 and p.email!='' 
                 and gpg.gruppe_id=$lists->gruppe_id) as t 
                 left join {cdb_gruppe_mailchimp_person} m 
                   on (m.gruppe_id=t.g_id and m.person_id=t.p_id and m.mailchimp_list_id='$list_id')
                   where m.gruppe_id is null");
      $batch=array();
      foreach ($db_g as $p) {
        $batch[]=array("EMAIL"=>$p->p_email, "FNAME"=>$p->vorname, "LNAME"=>$p->name);
        db_query("insert into {cdb_gruppe_mailchimp_person} (person_id, gruppe_id, mailchimp_list_id, email) 
                  values (:p_id, :g_id, :list_id, :email)",
          array(":p_id"=>$p->p_id, ":g_id"=>$p->g_id, ":list_id"=>$list_id, ":email"=>$p->p_email));          
      }
      listBatchSubscribe($api, $list_id, $batch, $lists->optin_yn==1);    
    }
  }

  // L�sche auch die alten Mails raus
  db_query("delete from {cc_mail_queue} where send_date is not null and datediff(send_date, now())<-60");

  // Do Statistics
  $db=db_query("select max(date) max, curdate() now from {crp_person}")->fetch();
  if ($db->max!=$db->now) {
    db_query("insert into {crp_person} (
                   SELECT curdate(), status_id, station_id, 
                       sum(case when datediff(erstkontakt,'".$db->max."')>=0 then 1 else 0 end), 
                       count(*) 
                   FROM {cdb_person} p, {cdb_gemeindeperson} gp
                    where p.id=gp.person_id group by status_id, station_id
              )");
    db_query("insert into crp_group (
                    SELECT curdate(), gruppe_id, status_id, station_id, s.id gruppenteilnehmerstatus_id, 
                        sum(case when datediff(gpg.letzteaenderung,'".$db->max."')>=0 then 1 else 0 end), 
                        count(*) 
                     from {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppenteilnehmerstatus} s, {cdb_gemeindeperson} gp, {cdb_gruppe} g
                     where  gpg.gemeindeperson_id=gp.id  and gpg.status_no=s.intern_code
                            and gpg.gruppe_id=g.id and (g.abschlussdatum is null or datediff(g.abschlussdatum, curdate())>-366)
                     group by gruppe_id, status_id, station_id, gruppenteilnehmerstatus_id, s.id
               )");
    ct_log('ChurchDB Tagesstatistik wurde erstellt.', 2);
  }
}

function listBatchSubscribe($api, $list_id, $batch, $optin=true) {
  if (count($batch)==0) return null;
  $up_exist = false; // yes, update currently subscribed users
  $replace_int = false; // no, add interest, don't replace
  $vals = $api->listBatchSubscribe($list_id,$batch,$optin, $up_exist, $replace_int);
  include_once("churchdb_db.inc");
  if ($api->errorCode)
    cdb_log("CRON - Fehler beim Subscribe zu MailChimp: Code=".$api->errorCode. " Msg=".$api->errorMessage,2);
  else cdb_log("CRON - MailChimp-Liste $list_id: Addiere ".count($batch)." Personen.",2);  
}
function listBatchUnsubscribe($api, $list_id, $batch, $send_goodbye=false, $send_notify=false) {
  if (count($batch)==0) return null;
  $delete_member=false; // flag to completely delete the member from your list instead of just unsubscribing, default to false
  $vals = $api->listBatchUnsubscribe($list_id,$batch,$delete_member, $send_goodbye, $send_notify);
  include_once("churchdb_db.inc");
  if ($api->errorCode)
    cdb_log("CRON - Fehler beim Unsubscribe zu MailChimp: Code=".$api->errorCode. " Msg=".$api->errorMessage,2);
  else cdb_log("CRON - MailChimp-Liste $list_id: Entferne ".count($batch)." Personen.",2);  
}
