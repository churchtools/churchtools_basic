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
  $cc_auth=addAuth($cc_auth, 105,'view address', 'churchdb', null, 'Zus&auml;tzlich Adressdaten einsehen (Strasse)', 1);
  $cc_auth=addAuth($cc_auth, 106,'view statistics', 'churchdb', null, 'Gesamtstatistik einsehen', 1);
  $cc_auth=addAuth($cc_auth, 107,'view tags', 'churchdb', null, 'Tags einsehen', 1);
  $cc_auth=addAuth($cc_auth, 108,'view history', 'churchdb', null, 'Historie eines Datensatzes ansehen', 1);
  $cc_auth=addAuth($cc_auth, 113,'view comments', 'churchdb', 'cdb_comment_viewer', 'Kommentare einsehen', 1);
  $cc_auth=addAuth($cc_auth, 103,'view alldetails', 'churchdb', null, 'Alle Informationen der Person sehen, inkl. Adressdaten, Gruppenzuordnung, etc.', 1);
  $cc_auth=addAuth($cc_auth, 104,'view group statistics', 'churchdb', null, 'Gruppenstatistik einsehen', 1);
  
  $cc_auth=addAuth($cc_auth, 116,'view archive', 'churchdb', null, 'Personen-Archiv einsehen', 1);
  $cc_auth=addAuth($cc_auth, 118,'push/pull archive', 'churchdb', null, 'Personen ins Archiv verschieben und zur&uuml;ckholen', 1);
  
  $cc_auth=addAuth($cc_auth, 102,'view alldata', 'churchdb', 'cdb_bereich', 'Alle Datens&auml;tze des jeweiligen Bereiches einsehen', 1);
  $cc_auth=addAuth($cc_auth, 115,'view group', 'churchdb', 'cdb_gruppe', 'Einzelne Gruppen einsehen - inklusive versteckte Gruppen', 0);
  
  $cc_auth=addAuth($cc_auth, 109,'edit relations', 'churchdb', null, 'Beziehungen editieren', 1);
  $cc_auth=addAuth($cc_auth, 110,'edit groups', 'churchdb', null, 'Gruppenzuordnungen editieren', 1);
  
  $cc_auth=addAuth($cc_auth, 117,'send sms', 'churchdb', null, 'SMS-Schnittstelle verwenden', 1);
  
  $cc_auth=addAuth($cc_auth, 111,'write access', 'churchdb', null, 'Schreibzugriff auf einzelne Bereich', 1);
  $cc_auth=addAuth($cc_auth, 112,'export data', 'churchdb', null, 'Daten exportieren', 1);
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
  if (isset($_GET["id"]) && ($_GET["id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"filter_id\" value=\"".$_GET["id"]."\"/>";

  $content=$content."
    <div id=\"cdb_content\" style=\"width:100%;height:500px\"></div>";
  
  return $content;
}


function getExternalGroupData() {
  $res=db_query("select id, bezeichnung, treffzeit, zielgruppe, max_teilnehmer, 
            geolat, geolng, treffname, versteckt_yn, valid_yn, distrikt_id, offen_yn, oeffentlich_yn
            from {cdb_gruppe} where oeffentlich_yn=1 and versteckt_yn=0 and valid_yn=1");
  $arr=array();
  foreach ($res as $g) {
    $arr[$g->id]=$g;    
  }
  return $arr;
}

function externmapview__ajax() {
  $func=$_GET["func"];
  if ($func=='loadMasterData') {
    $res["home_lat"] = variable_get('churchdb_home_lat', '53.568537');
    $res["home_lng"] = variable_get('churchdb_home_lng', '10.03656');
    $res["districts"]=churchcore_getTableData("cdb_distrikt", "bezeichnung");      
    $res["groups"]=getExternalGroupData();
    $res["modulespath"] = drupal_get_path('module', 'churchdb');
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
        $res = churchcore_sendEMailToPersonIds($p->id, "[".variable_get('site_name', 'drupal')."] Formular-Anfrage zur Gruppe ".$p->bezeichnung, $inhalt, null, true, true);            
      }
      if (count($rec)==0)
        $txt="Konnte leider keinen Leiter in der Gruppe finden. Bitte versuchen Sie es auf einem anderen Wege!";
      else     
        $txt="Es wurde eine E-Mail an ".implode($rec," und ")." gesendet!";
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
  if ((!user_access("view birthdaylist","churchdb")) && (!user_access("view","churchdb")))
    return null;
  
  $txt="";
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
  if (user_access("view memberliste","churchdb")) 
    $txt.="<p style=\"line-height:100%\" align=\"right\"><a href=\"?q=churchdb/memberlist\">Mitgliederliste</a>";
      
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
  ));
} 

function churchdb__birthdaylist() {
  $txt="<ul>".getBirthdaylistContent("Letzten 7 Tage",-7,-1, true).
	          getBirthdaylistContent("Heute",0, 0, true).
	          getBirthdaylistContent("Nächsten 30 Tage", 1, 30, true)."</ul>";
  if (user_access("view memberliste","churchdb")) 
    $txt.="<p style=\"line-height:100%\" align=\"right\"><a href=\"?q=churchdb/memberlist\">Mitgliederliste</a>";
	          
  return $txt;  
}  


function churchdb_getMemberList() {
  global $base_url, $files_dir;
  $status_id=variable_get('churchdb_memberlist_status', '1');
  if ($status_id=="") $status_id="-1";
  $station_id=variable_get('churchdb_memberlist_station', '1,2,3');
  if ($station_id=="") $station_id="-1";

  $sql='select person_id, name, vorname, strasse, ort, plz, land, DATE_FORMAT(geburtsdatum, \'%d.%m.%Y\') geburtsdatum, DATE_FORMAT(geburtsdatum, \'%d.%m.\') geburtsdatum_compact, 
         (case when geschlecht_no=1 then \'Herr\' when geschlecht_no=2 then \'Frau\' else \'\' end) "anrede",
         telefonprivat, telefongeschaeftlich, telefonhandy, fax, email, imageurl
         from {cdb_person} p, {cdb_gemeindeperson} gp where gp.person_id=p.id and gp.station_id in ('.$station_id.') 
          and gp.status_id in ('.$status_id.') and archiv_yn=0 order by name, vorname';
  $db=db_query($sql);
  $res=array();
  foreach ($db as $r) {
    $res[]=$r;
  }  
  return $res;
}

function churchdb__memberlist_saveSettings($form) {
  if (isset($_POST["btn_1"])) {
    header("Location: ?q=churchdb/memberlist");
    return null;
  }
  else {
    foreach ($form->fields as $key=>$value) {
      db_query("insert into {cc_config} (name, value) values (:name,:value) on duplicate key update value=:value",
          array(":name"=>$key, ":value"=>$value));
    }
    loadDBConfig();
  }
}

function _churchdb__memberlist_getSettingFields() {
  global $config;
  include_once("system/includes/forms.php");
  
  $model = new CC_Model("AdminForm", "churchdb__memberlist_saveSettings");
  $model->setHeader("Einstellungen f&uuml;r die Mitgliederliste", "Der Administrator kann hier Einstellung vornehmen.");    
  $model->addField("churchdb_memberlist_status","", "INPUT_REQUIRED","Kommaseparierte Liste mit Status-Ids f&uuml;r Mitgliederliste");
    $model->fields["churchdb_memberlist_status"]->setValue($config["churchdb_memberlist_status"]);
  $model->addField("churchdb_memberlist_station","", "INPUT_REQUIRED","Kommaseparierte Liste mit Station-Ids f&uuml;r Mitgliederliste");
    $model->fields["churchdb_memberlist_station"]->setValue($config["churchdb_memberlist_station"]);
    
  $model->addField("memberlist_telefonprivat","", "CHECKBOX","Anzeige der privaten Telefonnummer");
    $model->fields["memberlist_telefonprivat"]->setValue((isset($config["memberlist_telefonprivat"])?$config["memberlist_telefonprivat"]:true));
  $model->addField("memberlist_telefongeschaeftlich","", "CHECKBOX","Anzeige der gesch&auml;ftlichen Telefonnummer");
    $model->fields["memberlist_telefongeschaeftlich"]->setValue((isset($config["memberlist_telefongeschaeftlich"])?$config["memberlist_telefongeschaeftlich"]:true));
  $model->addField("memberlist_telefonhandy","", "CHECKBOX","Anzeige der Mobil-Telefonnumer");
    $model->fields["memberlist_telefonhandy"]->setValue((isset($config["memberlist_telefonhandy"])?$config["memberlist_telefonhandy"]:true));
  $model->addField("memberlist_fax","", "CHECKBOX","Anzeige der FAX-Nummer");
    $model->fields["memberlist_fax"]->setValue((isset($config["memberlist_fax"])?$config["memberlist_fax"]:true));
  $model->addField("memberlist_email","", "CHECKBOX","Anzeige der EMail-Adresse");
    $model->fields["memberlist_email"]->setValue((isset($config["memberlist_email"])?$config["memberlist_email"]:true));
  $model->addField("memberlist_birthday_full","", "CHECKBOX","Anzeige des gesamten Geburtsdatums (inkl. Geburtsjahr)");
    $model->fields["memberlist_birthday_full"]->setValue((isset($config["memberlist_birthday_full"])?$config["memberlist_birthday_full"]:false));
    
  return $model;
}

function churchdb__memberlist_settings() {
  $model=_churchdb__memberlist_getSettingFields();
  $model->addButton("Speichern","ok");
  $model->addButton("Zur&uuml;ck","arrow-left");
  
  return $model->render();
}

function churchdb__memberlist() {
  global $base_url, $files_dir, $config;
  
  if (!user_access("view memberliste","churchdb")) { 
     addErrorMessage("Keine Berechtigung f&uuml;r die Mitgliederliste!");
     return " ";
  }  
  
  $fields=_churchdb__memberlist_getSettingFields()->fields;
  
  $txt='<small><i><a class="cdb_hidden" href="?q=churchdb/memberlist_printview" target="_clean">Druckansicht</a></i></small>';
  if (user_access("administer settings","churchcore"))
    $txt.='&nbsp; <small><i><a class="cdb_hidden" href="?q=churchdb/memberlist_settings">Admin-Einstellung</a></i></small>';
  
  $txt.="<table class=\"table table-condensed\"><tr><th><th>Anrede<th>Name<th>Adresse<th>Geb.<th>Kontaktdaten</tr><tr>";
  $link = $base_url;
  
  $res=churchdb_getMemberList();
  foreach ($res as $arr) {
    
    if ($arr->imageurl==null) $arr->imageurl="nobody.gif";        
    $txt.="<tr><td><img width=\"65px\"src=\"$base_url$files_dir/fotos/".$arr->imageurl."\"/>";         
    $txt.='<td><div class="dontbreak">'.$arr->anrede.'<br/>&nbsp;</div><td><div class="dontbreak">';
    
    if ((user_access("view","churchdb")) && (user_access("view alldata","churchdb")))
      $txt.="<a href=\"$link?q=churchdb#PersonView/searchEntry:#".$arr->person_id."\">".$arr->name.", ".$arr->vorname."</a>";
    else
      $txt.=$arr->name.", ".$arr->vorname;      
    
    $txt.='<br/>&nbsp;</div><td><div class="dontbreak">'.$arr->strasse."<br/>".$arr->plz." ".$arr->ort."</div>";  
       
    $txt.="<td><div class=\"dontbreak\">".($fields["memberlist_birthday_full"]->getValue()?$arr->geburtsdatum:$arr->geburtsdatum_compact)."<br/>&nbsp;</div><td><div class=\"dontbreak\">";
    if (($fields["memberlist_telefonprivat"]->getValue()) && ($arr->telefonprivat!="")) 
      $txt.=$arr->telefonprivat."<br/>";
    if (($fields["memberlist_telefonhandy"]->getValue()) && ($arr->telefonhandy!="")) 
      $txt.=$arr->telefonhandy."<br/>";
    if (($arr->telefonprivat=="") && ($arr->telefonhandy=="")) {  
      if (($fields["memberlist_telefongeschaeftlich"]->getValue()) && ($arr->telefongeschaeftlich!="")) 
        $txt.=$arr->telefongeschaeftlich."<br/>";
      if (($fields["memberlist_fax"]->getValue()) && ($arr->fax!="")) 
        $txt.=$arr->fax." (Fax)<br/>";
    }
    if (($fields["memberlist_email"]->getValue()) && ($arr->email!="")) 
      $txt.='<a href="mailto:'.$arr->email.'">'.$arr->email.'</a><br/>';
    $txt.="</div>";
  }
  
  $txt.="</table>";
  return $txt;
}  


function churchdb__memberlist_printview() {
  global $base_url, $files_dir, $config;
  //  $content='<html><head><meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />';
//   drupal_add_css('system/bootstrap/css/bootstrap.min.css');
//  drupal_add_css(drupal_get_path('module', 'churchdb').'/cdb_printview.css');
//  $content=$content.drupal_get_header();
  if (!user_access("view memberliste","churchdb")) { 
     addErrorMessage("Keine Berechtigung f&uuml;r die Mitgliederliste!");
     return " ";
  }  

  require_once('system/assets/fpdf17/fpdf.php');
  $compact=true;
  if (isset($_GET["compact"])) $compact=$_GET["compact"];
  
  class PDF extends FPDF {
    //Kopfzeile
    function Header() {
      //Logo
//      $this->Image('system/assets/img/ct-icon_256.png',10,8,33);
      //Arial fett 15
      $this->SetFont('Arial','B',9);
      //nach rechts gehen
      $this->Cell(12,7,'',0);
      //Titel
      $this->Cell(13,8,'Anrede',0,0,'L');
      $this->Cell(48,8,'Name',0,0,'L');
      $this->Cell(45,8,'Adresse',0,0,'L');
      $this->Cell(20,8,'Geb.',0,0,'L');
      $this->Cell(30,8,'Kontaktdaten',0,0,'L');
      $fields=_churchdb__memberlist_getSettingFields()->fields;
      if ($fields["memberlist_telefonhandy"]->getValue())
        $this->Cell(30,8,'Handy',0,0,'L');
      //Zeilenumbruch
      $this->SetLineWidth(0.1);
      $this->SetDrawColor(200, 200, 200);
      $this->Line(8,$this->GetY(),204,$this->GetY());
      $this->Ln(9);
      $this->Line(8,$this->GetY()-1,204,$this->GetY()-1);
    }
  
    //Fusszeile
    function Footer() {
      //Position 1,5 cm von unten
      $this->SetY(-10);
      //Arial kursiv 8
      $this->SetFont('Arial','I',8);
      //Seitenzahl
      $this->Cell(0,5,'Seite '.$this->PageNo().'/{nb}',0,0,'C');
    }
  }
  
  //Instanciation of inherited class
  $pdf=new PDF('P','mm','A4');
  $pdf->AliasNbPages();
  $pdf->AddPage();
  $pdf->SetFont('Arial','',9);
  $res=churchdb_getMemberList();
  $pdf->SetLineWidth(0.4);
  $pdf->SetDrawColor(200, 200, 200);
  $fields=_churchdb__memberlist_getSettingFields()->fields;
  foreach ($res as $p) {
      $pdf->Line(8,$pdf->GetY()-1,204,$pdf->GetY()-1);
      $pdf->Cell(10,10,"",0);
      if (($p->imageurl==null) || (!file_exists("$files_dir/fotos/$p->imageurl"))) 
        $p->imageurl="nobody.gif";        
      $pdf->Image("$files_dir/fotos/$p->imageurl",$pdf->GetX()-10,$pdf->GetY()+1,9);
      $pdf->Cell(2);
      $pdf->Cell(13,9,$p->anrede,0,0,'L');
      $pdf->Cell(48,9,utf8_decode("$p->name, $p->vorname"),0,0,'L');
      $pdf->Cell(45,9,utf8_decode("$p->strasse"),0,0,'L');
      if (($fields["memberlist_birthday_full"]->getValue()))  
        $pdf->Cell(20,9,$p->geburtsdatum,0,0,'L');
      else
        $pdf->Cell(20,9,$p->geburtsdatum_compact,0,0,'L');
      
      if (($fields["memberlist_telefonprivat"]->getValue()) && ($p->telefonprivat!="")) 
         $pdf->Cell(30,9,$p->telefonprivat,0,0,'L');
      else if (($fields["memberlist_telefongeschaeftlich"]->getValue()) && ($p->telefongeschaeftlich!="")) 
         $pdf->Cell(30,9,$p->telefongeschaeftlich,0,0,'L');
      else if (($fields["memberlist_telefongeschaeftlich"]->getValue()) && ($p->fax!="")) 
         $pdf->Cell(30,9,$p->fax." (Fax)",0,0,'L');
      else $pdf->Cell(30,9,"",0,0,'L');
      if (($fields["memberlist_telefonhandy"]->getValue()) && ($p->telefonhandy!="")) 
         $pdf->Cell(30,9,$p->telefonhandy,0,0,'L');
      
      //Zeilenumbruch
      $pdf->Ln(5);
      $pdf->Cell(73);
      $pdf->Cell(48,10,"$p->plz ".utf8_decode($p->ort),0,0,'L');
      $pdf->Cell(17);
      if (($fields["memberlist_email"]->getValue()) && ($p->email!="")) {
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(30,9,$p->email);
        $pdf->SetFont('Arial','',9);
      }
      $pdf->Ln(12);
      
  }
  $pdf->Output('mitgliederliste.pdf','I');  
}




function churchdb__vcard() {
  $id=$_GET["id"];
  drupal_add_http_header('Content-type','text/x-vCard; charset=ISO-8859-1; encoding=ISO-8859-1',true);
  drupal_add_http_header('Content-Disposition','attachment; filename="vcard'.$id.'.vcf"',true);
  include_once("churchdb_db.inc");

  $person = db_query("
    SELECT  concat(
    'BEGIN:VCARD\n','VERSION:3.0\n',
	'N:',name,';',vorname,'\n',
	'EMAIL;TYPE=INTERNET:',email,'\n',
	'TEL;TYPE=voice,privat:',telefonprivat,'\n',
	'TEL;TYPE=voice,work:',telefongeschaeftlich,'\n',
	'TEL;TYPE=voice,cell,pref:',telefonhandy,'\n',
	'ADR;TYPE=intl,privat,postal:;',zusatz,';',strasse,';',ort,';;',plz,';',land,'\n',
	if(geburtsdatum is null,'',concat('BDAY:',geburtsdatum,'\n')),
	'END:VCARD'
	) vcard FROM {cdb_person} p, {cdb_gemeindeperson} gp WHERE gp.person_id=p.id and p.id = ".$id)->fetch();
  echo $person->vcard;
}
  
function churchdb__export() {
  drupal_add_http_header('Content-type', 'application/csv; charset=ISO-8859-1; encoding=ISO-8859-1',true);
  drupal_add_http_header('Content-Disposition', 'attachment; filename="churchdb_export.csv"',true);
  include_once("churchdb_db.inc");

  if (isset($_GET["ids"]))
    $ids="and p.id in (".$_GET["ids"].")";
  else $ids="";  

  $allowedDeps=implode(",",churchdb_getAllowedDeps());
  if (user_access("view alldetails","churchdb"))
    $persons_sql = 'SELECT station.bezeichnung station, (case when geschlecht_no=1 then \'Herr\' when geschlecht_no=2 then \'Frau\' else \'\' end) "anrede", vorname, name, strasse adresse, plz,
              ort, land, n.bezeichnung nationalitaet, telefonprivat "tel. priv.", email "e-mail", telefongeschaeftlich "tel. büro", telefonhandy "handy",
  			null bemerkung, eintrittsdatum "mitglied seit", status.kuerzel status,
  			taufdatum getauft, taufort, getauftdurch "getauft durch", ueberwiesenvon "Überwiesen von", 
  			day(geburtsdatum) "geb.tag", month(geburtsdatum) "geb.m.", year(geburtsdatum) "geb.jahr", f.bezeichnung "f.stand", 
  			geburtsname "geb.name", hochzeitsdatum "hochzeitsdatum", geburtsort "geb.ort", beruf, titel "titel",
  			(case when geschlecht_no=1 then \'Lieber\' when geschlecht_no=2 then \'Liebe\' else \'\' end) "anrede2",
  			bereich_id, b.bezeichnung "bereich", 
  			day(eintrittsdatum) "mitgliedseit.tag", month(eintrittsdatum) "mitgliedseit.m", year(eintrittsdatum) "mitgliedseit.jahr",
              (year(curdate())-year(geburtsdatum) - (RIGHT(CURDATE(),5)<RIGHT(geburtsdatum,5))) as "alter",p.id id, null as "e-mail_beziehung", optigem_nr, spitzname
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
  						and bp.bereich_id in ('.$allowedDeps.') ';
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
              and bp.bereich_id in ('.$allowedDeps.') ';
    
  
	$persons = db_query($persons_sql.$ids.' ORDER BY name, vorname, id, b.sortkey ');			  
  // Zuerst werden die Daten in ein Array gepackt und dabei nach Bereich verdichtet, 
  // so dass eine Person in mehreren Bereichen auch nur 1x aufgefuehrt wird.
  // Die Bereiche werden dann per "," getrennt
  $export= array();
  
  foreach ($persons as $arr) {
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
        if (isset($export[$p2]["e-mail"]))
          $export[$p1]["e-mail_beziehung"]=$export[$p2]["e-mail"];
        // Und nehmen den anderen aus dem Export raus
        $export[$p2]=null;
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
