<?php 

function ical_main() {
  include_once(drupal_get_path('module', 'churchservice').'/churchservice_ajax.inc');
  call_user_func("churchservice_ical");
}

function churchservice__ajax() {
  if (!user_access("view","churchservice")) {
    addInfoMessage("Keine Berechtigung f&uuml;r ChurchService");
    return " ";
  }
  include_once(drupal_get_path('module', 'churchservice').'/churchservice_ajax.inc');
  call_user_func("churchservice_ajax");
}


function churchservice__filedownload() {
  include_once("system/churchcore/churchcore.php");
  churchcore__filedownload();  
}

function churchservice_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 301,'view', 'churchservice', null, 'ChurchService sehen', 1);
  $cc_auth=addAuth($cc_auth, 304,'view servicegroup', 'churchservice', 'cs_servicegroup', 'Dienstanfragen einzelner Service-Gruppe einsehen', 1);
  $cc_auth=addAuth($cc_auth, 305,'edit servicegroup', 'churchservice', 'cs_servicegroup', 'Dienstanfragen einzelner Service-Gruppe editieren', 1);
  $cc_auth=addAuth($cc_auth, 302,'view history', 'churchservice', null, 'Historie der Anfragen anschauen', 1);
  $cc_auth=addAuth($cc_auth, 303,'edit events', 'churchservice', null, 'Events erstellen, l&ouml;schen, etc.', 1);
  $cc_auth=addAuth($cc_auth, 309,'edit template', 'churchservice', null, 'Event-Vorlagen editieren', 1);
  
  $cc_auth=addAuth($cc_auth, 307,'manage absent', 'churchservice', null, 'Abwesenheiten f&uuml;r alle Personen einsehen und pflegen', 1);
  
  $cc_auth=addAuth($cc_auth, 321,'view facts', 'churchservice', null, 'Fakten sehen', 1);
  $cc_auth=addAuth($cc_auth, 308,'edit facts', 'churchservice', null, 'Fakten pflegen', 1);
  $cc_auth=addAuth($cc_auth, 322,'export facts', 'churchservice', null, 'Fakten exportieren', 1);
  
  $cc_auth=addAuth($cc_auth, 331,'view agenda', 'churchservice', 'cc_calcategory', 'Ablaufpl&auml;ne f&uuml;r einzelne Kalender sehen', 1);
  $cc_auth=addAuth($cc_auth, 332,'edit agenda', 'churchservice', 'cc_calcategory', 'Ablaufpl&auml;ne f&uuml;r einzelne Kalender editieren', 1);
  $cc_auth=addAuth($cc_auth, 333,'edit agenda templates', 'churchservice', 'cc_calcategory', 'Ablaufplan-Vorlagen f&uuml;r einzelne Kalender editieren', 1);
  
  $cc_auth=addAuth($cc_auth, 313,'view songcategory', 'churchservice', 'cs_songcategory', 'Einzelne Song-Kategorien einsehen', 1);
  $cc_auth=addAuth($cc_auth, 311,'view song', 'churchservice', null, 'Songs anschauen und Dateien herunterladen', 1);
  $cc_auth=addAuth($cc_auth, 312,'edit song', 'churchservice', null, 'Songs editieren und Dateien hochladen', 1);
  
  $cc_auth=addAuth($cc_auth, 399,'edit masterdata', 'churchservice', null, 'Stammdaten editieren', 1);
  return $cc_auth;
}
 
function churchservice_getAdminModel() {
  global $config;
  
  $model = new CC_ModulModel("churchservice");  
  $model->addField("churchservice_entries_last_days","", "INPUT_REQUIRED","Wieviel Tage zur&uuml;ck in ChurchService-Daten geladen werden");
    $model->fields["churchservice_entries_last_days"]->setValue($config["churchservice_entries_last_days"]);    
  $model->addField("churchservice_openservice_rememberdays","", "INPUT_REQUIRED","Nach wieviel Tagen die Dienstanfrage erneut statt findet, wenn sie noch nicht zugesagt oder abgelehnt wurde");
    $model->fields["churchservice_openservice_rememberdays"]->setValue($config["churchservice_openservice_rememberdays"]);  
  $model->addField("churchservice_reminderhours","", "INPUT_REQUIRED","Wieviele Stunden im Vorfeld eine Erinnerung an den Dienst erfolgen soll");
    $model->fields["churchservice_reminderhours"]->setValue($config["churchservice_reminderhours"]);  
    
  $model->addField("churchservice_songwithcategoryasdir","", "CHECKBOX","Kategorie als Verzeichnisangabe nutzen");
    $model->fields["churchservice_songwithcategoryasdir"]->setValue(variable_get("churchservice_songwithcategoryasdir","0"));
    
  return $model;
}

function churchservice__exportfacts() {
  if (!user_access("export facts","churchservice")) {
    addInfoMessage("Keine Berechtigung zum Exportieren von Faktendaten (edit facts)");
    return " ";
  }
  drupal_add_http_header('Content-type', 'application/csv; charset=ISO-8859-1; encoding=ISO-8859-1',true);
  drupal_add_http_header('Content-Disposition', 'attachment; filename="churchservice_fact_export.csv"',true);
  
  $events=churchcore_getTableData("cs_event", "startdate");
  
  $cond="";
  if (isset($_GET["date"])) {
    $cond=" and e.startdate>='".$_GET["date"]."'";
  }
  
  $db=db_query("select e.*, c.bezeichnung, c.category_id from {cs_event} e, {cc_cal} c where e.cc_cal_id=c.id $cond order by e.startdate");
  $events=array();
  foreach ($db as $e) {
    $events[$e->id]=$e;
  } 
  
  $category=churchcore_getTableData("cc_calcategory");
  $facts=churchcore_getTableData("cs_fact", "sortkey");
  $res=db_query("select * from {cs_event_fact}");  
  
  $result=array();
  foreach($res as $d) {
    $result[$d->event_id]->facts[$d->fact_id]=$d->value;    
  }

  echo '"Datum";"Bezeichnung";"Notizen";"Kategorie";';
  foreach ($facts as $fact) {
    echo mb_convert_encoding('"'.$fact->bezeichnung.'";', 'ISO-8859-1', 'UTF-8');
  }
  echo "\n";
  foreach ($events as $key=>$event) {
    if (isset($result[$key])) {
      echo "$event->startdate;";
      echo mb_convert_encoding('"'.$event->bezeichnung.'";', 'ISO-8859-1', 'UTF-8');
      echo mb_convert_encoding('"'.$event->special.'";', 'ISO-8859-1', 'UTF-8');
      echo mb_convert_encoding('"'.$category[$event->category_id]->bezeichnung.'";', 'ISO-8859-1', 'UTF-8');
      foreach ($facts as $fact) {
        if (isset($result[$key]->facts[$fact->id])) {
          echo $result[$key]->facts[$fact->id];
        }
        echo ";";
      }       
      echo "\n";
    }    
  }
  return null;
}



function churchservice__printview() {
  global $version, $files_dir, $config, $embedded;


  drupal_add_css('system/assets/fileuploader/fileuploader.css');

  drupal_add_js('system/bootstrap/js/bootstrap-multiselect.js');
  drupal_add_js('system/assets/fileuploader/fileuploader.js');
  drupal_add_js('system/assets/js/jquery.history.js');

  drupal_add_js('system/assets/mediaelements/mediaelement-and-player.min.js');
  drupal_add_css('system/assets/mediaelements/mediaelementplayer.css');

  drupal_add_js('system/assets/ckeditor/ckeditor.js');
  drupal_add_js('system/assets/ckeditor/lang/de.js');

  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js');
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js');
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js');

  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_loadandmap.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_settingsview.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_maintainview.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_listview.js');
  //drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_testview.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_calview.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_factview.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_agendaview.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_songview.js');
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_main.js');

  drupal_add_js(createI18nFile("churchservice"));

  $content="";
  // Übergabe der ID für den Direkteinstieg einer Person
  if (isset($_GET["id"]) && ($_GET["id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"externevent_id\" value=\"".$_GET["id"]."\"/>";
  if (isset($_GET["service_id"]) && ($_GET["service_id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"service_id\" value=\"".$_GET["service_id"]."\"/>";
  if (isset($_GET["date"]) && ($_GET["date"]!=null))
    $content=$content."<input type=\"hidden\" id=\"currentdate\" value=\"".$_GET["date"]."\"/>";
  if (isset($_GET["meineFilter"]) && ($_GET["meineFilter"]!=null))
    $content=$content."<input type=\"hidden\" id=\"externmeineFilter\" value=\"".$_GET["meineFilter"]."\"/>";
  
  $embedded=true;
  
  $content=$content."<input type=\"hidden\" id=\"printview\" value=\"true\"/>";
  $content=$content."
<div class=\"row-fluid\">
  <div class=\"span12\">
    <div id=\"cdb_group\"></div>
    <div id=\"cdb_content\"></div>
  </div>
</div>";
  return $content;
}

function churchservice_main() {
  global $version, $files_dir, $config;
  
  
  drupal_add_css('system/assets/fileuploader/fileuploader.css'); 
  
  drupal_add_js('system/bootstrap/js/bootstrap-multiselect.js'); 
  drupal_add_js('system/assets/fileuploader/fileuploader.js'); 
  drupal_add_js('system/assets/js/jquery.history.js'); 
  
  drupal_add_js('system/assets/mediaelements/mediaelement-and-player.min.js'); 
  drupal_add_css('system/assets/mediaelements/mediaelementplayer.css');
    
  drupal_add_js('system/assets/ckeditor/ckeditor.js');
  drupal_add_js('system/assets/ckeditor/lang/de.js');  
    
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_abstractview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_standardview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchcore') .'/cc_maintainstandardview.js'); 
  
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_loadandmap.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_settingsview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_maintainview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_listview.js'); 
  //drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_testview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_calview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_factview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_agendaview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_songview.js'); 
  drupal_add_js(drupal_get_path('module', 'churchservice') .'/cs_main.js'); 
    
  drupal_add_js(createI18nFile("churchservice"));
  
  $content="";
  // Übergabe der ID für den Direkteinstieg einer Person
  if (isset($_GET["id"]) && ($_GET["id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"externevent_id\" value=\"".$_GET["id"]."\"/>";
  if (isset($_GET["service_id"]) && ($_GET["service_id"]!=null))
    $content=$content."<input type=\"hidden\" id=\"service_id\" value=\"".$_GET["service_id"]."\"/>";
    
  $content=$content." 
<div class=\"row-fluid\">
  <div class=\"span3\">
    <div id=\"cdb_menu\"></div>
    <div id=\"cdb_filter\"></div>
  </div>  
  <div class=\"span9\">
    <div id=\"cdb_search\"></div> 
    <div id=\"cdb_group\"></div> 
    <div id=\"cdb_content\"></div>
  </div>
</div>";
  return $content;
}



function churchservice_getUserOpenServices() {
  global $user;
  
  if (isset($_GET["eventservice_id"])) {
    include_once('./'. drupal_get_path('module', 'churchservice') .'/churchservice_ajax.inc');
    $reason=null;
    if (isset($_GET["reason"])) $reason=$_GET["reason"];
    if ($_GET["zugesagt_yn"]==1)
      churchservice_updateEventService($_GET["eventservice_id"], $user->vorname." ".$user->name, $user->id, 1, $reason);
    else  
      churchservice_updateEventService($_GET["eventservice_id"], null, null, 0, $reason);
    addInfoMessage("Danke f&uuml;r deine R&uuml;ckmeldung!");
  }
  
  include_once('./'. drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');
  $txt="";
  $pid = $user->id;
  $txt1="";        
  $res = db_query("select cal.bezeichnung event, e.id event_id, es.id eventservice_id, allowtonotebyconfirmation_yn,
                       DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, s.bezeichnung service, 
                       s.id service_id, sg.bezeichnung servicegroup, concat(p.vorname, ' ', p.name) as modifieduser, p.id modified_pid
                   from {cs_eventservice} es, {cs_event} e, {cs_servicegroup} sg, {cs_service} s, {cdb_person} p, {cc_cal} cal 
                    where cal.id=e.cc_cal_id and cdb_person_id=$user->id and e.startdate>=current_date() and es.modified_pid=p.id and 
                    zugesagt_yn=0 and valid_yn=1 and es.event_id=e.id and es.service_id=s.id 
                    and sg.id=s.servicegroup_id order by datum ");
  $nr=0;
  $txt2="";
  foreach($res as $arr) {
    $nr=$nr+1;
    $txt2=$txt2.'<div class="service-request" style="display:none;" '.
         'data-id="'.$arr->eventservice_id.'" data-modified-user="'.$arr->modifieduser.'" ';

    if ($arr->allowtonotebyconfirmation_yn==1)
       $txt2.='data-comment-confirm="'.$arr->allowtonotebyconfirmation_yn.'" ';
    if (user_access("view","churchdb"))     
      $txt2.='data-modified-pid="'.$arr->modified_pid.'" ';
    $txt2.=">";
         
    $txt2.='<a href="?q=churchservice&id='.$arr->event_id.'">';
    $txt2.=$arr->datum." - ".$arr->event."</a>: ";
    $txt2.='<a href="?q=churchservice&id='.$arr->event_id.'"><b>'.$arr->service."</b></a> (".$arr->servicegroup.")";

    $files=churchcore_getFilesAsDomainIdArr("service", $arr->event_id);
    $txt.='<span class="pull-right">';
    if ((isset($files)) && (isset($files[$arr->event_id]))) {
      $i=0;
      foreach ($files[$arr->event_id] as $file) {
        $i++;
        if ($i<=3)
          $txt.=churchcore_renderFile($file)."&nbsp;";
        else $txt.="...";  
      }
    }
    $txt.="</span>";     
    $txt2.='<div style="margin-left:16px;margin-bottom:10px;" class="service-request-answer"></div>';
    $txt2.='</div>';
  }           
  if ($txt2!="") $txt=$txt.$txt1.$txt2.
        '<p align="right"><a href="#" style="display:none" class="service-request-show-all">Alle anzeigen</a>';
  return $txt;
}


function churchservice_getCurrentEvents() {
  global $user;
  // Hole Events, wo Dienste sind, wo ich Leiter/Co-Leiter bin.
  $txt="";
  
  
  $mygroups=churchdb_getMyGroups($user->id, true, true);
  
  $events=db_query("select e.id, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, bezeichnung 
      from {cs_event} e, {cc_cal} cal 
      where cal.id=e.cc_cal_id and DATE_ADD(e.startdate, INTERVAL 3 hour) > NOW() and datediff(e.startdate, now())<3 order by e.startdate");
  foreach($events as $event) {
    $firstrow=true;
    $ess=db_query("select es.name, s.cdb_gruppen_ids, s.bezeichnung, es.zugesagt_yn
                  from {cs_eventservice} es, {cs_service} s, {cs_servicegroup} sg 
                  where es.valid_yn=1 and es.service_id=s.id and s.servicegroup_id=sg.id and  
                      es.event_id=$event->id 
                  order by sg.sortkey, s.sortkey");
    foreach($ess as $es) {
      $istdrin=false;
      $service_groups=explode(',',$es->cdb_gruppen_ids);
      foreach ($service_groups as $service_group) {
        if (in_array($service_group, $mygroups))
          $istdrin=true;
      }   
      if ($istdrin) {
        if ($firstrow) {
          $txt.='<li><a href="?q=churchservice&id='.$event->id.'">'."$event->datum - $event->bezeichnung</a><p>";
          $firstrow=false;
        }
        $txt.="<small>&nbsp; $es->bezeichnung: ";
        if ($es->zugesagt_yn==1) {
          $txt.=$es->name;
        }
        else {
          $txt.='<font style="color:red">';
          if ($es->name!=null) $txt.=$es->name;
          $txt.="?";  
          $txt.='</font>';
        }
        $txt.="</small><br/>";
      }
    }
  }  
  if ($txt!="") $txt="<ul>$txt</ul>";
  return $txt;  
}


function churchservice_getUserNextServices($shorty=true) {
  global $user;
  
  include_once('./'. drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');
  
  $pid=$user->id;
  $res = db_query("select e.id event_id, cal.bezeichnung event, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, s.bezeichnung service, sg.bezeichnung servicegroup, cdb_person_id, DATE_FORMAT(es.modified_date, '%d.%m.%Y %H:%i') modified_date
   from {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_servicegroup} sg, {cs_service} s where
   cal.id=e.cc_Cal_id and  
     cdb_person_id=$pid and e.startdate>=current_date and zugesagt_yn=1 and valid_yn=1 and es.event_id=e.id and es.service_id=s.id and sg.id=s.servicegroup_id order by e.startdate");
  $nr=0;
  $txt="";
  foreach($res as $arr) {
    $nr=$nr+1;
    if (($nr<=5) || (!$shorty)) {
      $txt.='<p><a href="?q=churchservice&id='.$arr->event_id.'">'.$arr->datum." - ".$arr->event."</a>: ";
      $txt.='<a href="?q=churchservice&id='.$arr->event_id.'"><b>'.$arr->service."</b></a> (".$arr->servicegroup.")";
      $files=churchcore_getFilesAsDomainIdArr("service", $arr->event_id);
      $txt.='<span class="pull-right">';
      if ((isset($files)) && (isset($files[$arr->event_id]))) {
        $i=0;
        foreach ($files[$arr->event_id] as $file) {
          $i++;
          if ($i<4)
            $txt.=churchcore_renderFile($file)."&nbsp;";
          else $txt.="...";  
        }
      }
      $txt.="</span><small><br>&nbsp; &nbsp; &nbsp; ";
      $txt.="Zugesagt am ".$arr->modified_date."</small>";
    }  
  }
  //if (($shorty) && ($txt!="")) $txt.='<br/><p align="right">'.l("Weiter","?q=churchservice");
  return $txt;  
}


function churchservice_getFactsOfLastDays() {
  $txt='';
  if (user_access("view facts","churchservice")) {
    $res=db_query("select e.id, cal.bezeichnung eventname, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, f.bezeichnung factname, value 
                from {cs_fact} f, {cs_event_fact} ef, {cs_event} e, {cc_cal} cal
             where cal.id=e.cc_cal_id and ef.fact_id=f.id and ef.event_id=e.id and datediff(now(), e.startdate)<3 and datediff(now(), e.startdate)>=0
            order by e.startdate, factname");
    $event=null;
    foreach($res as $val) {
      if ($val->id!=$event) {
        $event=$val->id;
        $txt.='</small><li><a href="?q=churchservice&id='.$val->id.'#FactView/">'.$val->datum." - ".$val->eventname.'</a><p>';
      } 
      $txt.='<small>'.$val->factname.": ".$val->value."</small><br/>";
    }
    if ($txt!='')
      $txt='<ul>'.$txt.'</ul>';           
  }
  return $txt;
}

function churchservice_getAbsents($year=null) {
  $txt='';
  
  if (user_access("view","churchdb")) {
    $user=$_SESSION["user"];
    include_once(drupal_get_path('module', 'churchdb').'/churchdb_db.inc');
    $groups=churchdb_getMyGroups($user->id, true, true);
    $allPersonIds=churchdb_getAllPeopleIdsFromGroups($groups);
    
    if (count($groups)>0 && count($allPersonIds)>0) {
      $sql="select p.id p_id, p.name, p.vorname, DATE_FORMAT(a.startdate, '%d.%m.') startdate_short, DATE_FORMAT(a.startdate, '%d.%m.%Y') startdate, DATE_FORMAT(a.enddate, '%d.%m.%Y') enddate, a.bezeichnung, ar.bezeichnung reason 
                from {cdb_person} p, {cs_absent} a, {cs_absent_reason} ar 
              where a.absent_reason_id=ar.id and p.id=a.person_id and p.id in (".implode(",", $allPersonIds).") ";
      if ($year==null)
        $sql.="and datediff(a.enddate,now())>=-1 and datediff(a.enddate,now())<=31";
      else 
        $sql.="and (DATE_FORMAT(a.startdate, '%Y')=$year or DATE_FORMAT(a.enddate, '%Y')=$year)";
      $sql.=" order by a.startdate";  
        
      $db=db_query($sql);  
      $people=array();    
      foreach ($db as $a) {
        if (isset($people[$a->p_id]))
          $absent=$people[$a->p_id];
        else $absent=array();  
        $absent[]=$a;  
        $people[$a->p_id]=$absent;  
      }
      if (count($people)>0) {
        $txt='<ul>';        
        foreach ($people as $p) {
          $txt.='<li>'.$p[0]->vorname." ".$p[0]->name.": <p>";
          foreach ($p as $abwesend) {
            $reason=$abwesend->reason;
            if ($abwesend->bezeichnung!=null) $reason=$abwesend->bezeichnung." ($reason)";
            if ($abwesend->startdate==$abwesend->enddate)
              $txt.='<small>'.$abwesend->startdate." $reason</small><br/>";
            else          
              $txt.='<small>'.$abwesend->startdate_short."-".$abwesend->enddate." $reason</small><br/>";
          }      
        }
        $txt.='</ul>';
      }
      if (($year==null) && (user_access("view","churchcal")))
        $txt.='<p style="line-height:100%" align="right"><a href="?q=churchcal&viewname=yearView">Weitere</a></p>';
    }
  }
  return $txt;
}

function churchservice_blocks() {
  return (array(
    1=>array(
      "label"=>"Deine offenen Dienstanfragen",
      "col"=>2,
      "sortkey"=>1,
      "html"=>churchservice_getUserOpenServices(),
      "help"=>"Offene Dienstanfragen",
      "class"=>"service-request"
    ),  
    2=>array(
      "label"=>"Deine n&auml;chsten Dienste",
      "col"=>2,
      "sortkey"=>2,
      "html"=>churchservice_getUserNextServices()
    ),  
    3=>array(
      "label"=>"Deine aktuelle Eventbesetzung",
      "col"=>2,
      "sortkey"=>3,
      "html"=>churchservice_getCurrentEvents()
    ),  
    4=>array(
      "label"=>"Abwesenheiten der n&auml;chsten 30 Tage",
      "col"=>2,
      "sortkey"=>4,
      "html"=>churchservice_getAbsents()
    ),  
    5=>array(
      "label"=>"Fakten der letzten Tage",
      "col"=>2,
      "sortkey"=>5,
      "html"=>churchservice_getFactsOfLastDays()
    ),  
    ));
} 


/**
 * Infos für noch zu bestätigende Dienste
 */
function churchservice_openservice_rememberdays() {
  global $base_url;
  include_once("churchservice_db.inc");

  $delay=variable_get('churchservice_openservice_rememberdays');
  $dt = new datetime();
  
  // Checken, ob EIN EventService noch nicht gesendet wurde, bzw. schon so alt ist.
  // Prüfe dabei, ob die Person eine EMail-Adresse hat und auch gemappt wurde.
  $sql="select es.id, p.id p_id, p.vorname, p.email, es.modified_pid, if (password is null and loginstr is null and lastlogin is null,1,0) as invite  
                    from {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_service} s, {cdb_person} p 
                    where e.cc_cal_id=cal.id and es.valid_yn=1 and es.zugesagt_yn=0 and es.cdb_person_id is not null
                      and es.service_id=s.id and s.sendremindermails_yn=1 
                      and es.event_id=e.id and e.Startdate>=current_date
                      and ((es.mailsenddate is null) or (datediff(current_date,es.mailsenddate)>=$delay))
                      and p.email!='' and p.id=es.cdb_person_id limit 1";
  $res=db_query($sql)->fetch();
  
  $sql2="select es.id id, cal.bezeichnung event, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, e.id event_id,
                 s.bezeichnung service, sg.bezeichnung servicegroup, es.mailsenddate
              from {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_service} s, {cs_servicegroup} sg 
                 where cal.id=e.cc_cal_id and es.valid_yn=1 and es.zugesagt_yn=:zugesagt and es.cdb_person_id=:p_id
                  and s.sendremindermails_yn=1 
                  and es.event_id=e.id and es.service_id=s.id and sg.id=s.servicegroup_id
                  and e.startdate>=current_date
                  order by e.startdate";
  $i=0;
  // Lasse 15 EventServices durch, dann warten bis nächste Cron, sonst werden es zu viele Mails
  while (($res) && ($i<15)) {
    // Wenn einer vorhanden ist, dann suche nach weiteren offenen Diensten für die Person
    $txt="<h3>Hallo ".$res->vorname.",</h3><p>";
    
    $inviter=churchcore_getPersonById($res->modified_pid);
    $txt.="Du wurdest in dem Dienstplan auf ".variable_get('site_name', 'drupal');
    if ($inviter!=null)        
      $txt.=' von <i>'.$inviter->vorname." ".$inviter->name."</i>";
    $txt.=" zu Diensten vorgeschlagen. <br/>Zum Zu- oder Absagen bitte hier klicken:";

    $loginstr=churchcore_createPersonLoginStr($res->p_id);
      
    $txt.='<p><a href="'.$base_url.'?q=home&id='.$res->p_id.'&loginstr='.$loginstr.'" class="btn btn-primary">%sitename</a>';
    
    $txt.="<p><p><b>Folgende Dienst-Termine sind von Dir noch nicht bearbeitet:</b><ul>";        
    $arr=db_query($sql2, array(":p_id"=>$res->p_id, ":zugesagt"=>0));
    foreach ($arr as $res2) {
      $txt.="<li> ".$res2->datum." ".$res2->event.":  ".$res2->service." (".$res2->servicegroup.")";
      db_update("cs_eventservice")
        ->fields(array("mailsenddate"=>$dt->format('Y-m-d H:i:s')))
        ->condition('id',$res2->id,"=")
        ->execute();          
    }
    $txt.='</ul>';
    
    $arr=db_query($sql2, array(":p_id"=>$res->p_id, ":zugesagt"=>1));
    $txt2="";
    foreach ($arr as $res2) {
      $txt2.="<li> ".$res2->datum." - ".$res2->event.":  ".$res2->service." (".$res2->servicegroup.")";
      if ($res2->mailsenddate==null) $txt2.=" NEU!";
      db_update("cs_eventservice")
        ->fields(array("mailsenddate"=>$dt->format('Y-m-d H:i:s')))
        ->condition('id',$res2->id,"=")
        ->execute();          
      }
      if ($txt2!="") {
        $txt.="<p><p><b>Bei folgenden Diensten hast Du schon zugesagt:</b><ul>".$txt2;        
        $txt.="</ul>";
    }  

    // Person wurde noch nicht eingeladen, also schicke gleich eine Einladung mit!
    if ($res->invite==1) {
      include_once(drupal_get_path('module', 'churchdb').'/churchdb_ajax.inc');
      churchdb_invitePersonToSystem($res->p_id);
      $txt.="<p><b>Da Du noch nicht kein Zugriff auf das System hast, bekommst Du noch eine separate E-Mail, mit der Du Dich dann anmelden kannst!.</b>";
    }
    
    churchservice_send_mail("[".variable_get('site_name', 'drupal')."] Es sind noch Dienste offen",$txt,$res->email);
    $i=$i+1;
    $res=db_query($sql)->fetch();
  }
}


function churchservice_remindme() {
  global $base_url;
  include_once("churchservice_db.inc");
  
  $sql="SELECT p.vorname, p.name, p.email, 
          cal.bezeichnung, s.bezeichnung dienst, sg.bezeichnung sg, e.id event_id, 
           DATE_FORMAT(e.Startdate, '%d.%m.%Y %H:%i') datum, es.id eventservice_id
         FROM {cs_eventservice} es, {cs_service} s, {cs_event} e, {cc_cal} cal, {cs_servicegroup} sg,
              {cdb_person} p where
           cal.id=e.cc_cal_id and es.cdb_person_id=:person_id and p.id=:person_id and p.email!='' 
         AND es.valid_yn=1 AND es.zugesagt_yn=1
         and UNIX_TIMESTAMP(e.startdate)-UNIX_TIMESTAMP(now())<60*60*(:hours) 
         and UNIX_TIMESTAMP(e.startdate)-UNIX_TIMESTAMP(now())>0
         AND s.id=es.service_id and s.sendremindermails_yn=1   
         AND e.id=es.event_id AND s.servicegroup_id=sg.id 
      ORDER BY datum";
  $set=db_query("select * from {cc_usersettings} where modulename='churchservice' and attrib='remindMe' and value=1");
  foreach ($set as $p) {
    $res=db_query($sql, array(":person_id"=>$p->person_id, ":hours"=>variable_get('churchservice_reminderhours')));
    foreach($res as $es) {
      if (churchcore_checkUserMail($p->person_id, "remindService", $es->eventservice_id, variable_get('churchservice_reminderhours'))) {
        $txt="<h3>Hallo ".$es->vorname."!</h3>";
        $txt.='<p>Dies ist eine Erinnerung an Deine n&auml;chsten Dienste:</p><br/>';
        $txt.='<table class="table table-condensed">';
        // Now he looks 12 hours furhter if there are other services to be reminded
        $res2=db_query($sql, array(":person_id"=>$p->person_id, ":hours"=>variable_get('churchservice_reminderhours')+12));
        foreach ($res2 as $es2) {
          if ($es2->eventservice_id==$es->eventservice_id ||
               (churchcore_checkUserMail($p->person_id, "remindService", $es2->eventservice_id, variable_get('churchservice_reminderhours')))) {
            $txt.='<tr><td>'.$es2->datum.' '.$es2->bezeichnung.'<td>Dienst: '.$es2->dienst." (".$es2->sg.")";
            $txt.='<td style="min-width:79px;"><a href="'.$base_url.'?q=churchservice&id='.$es2->event_id.'" class="btn btn-primary">Event aufrufen</a>';            
          }
        }        
        
        $txt.='</table><br/><br/><a class="btn" href="'.$base_url.'?q=churchservice#SettingsView">Erinnerungen deaktivieren</a>';
        churchservice_send_mail("[".variable_get('site_name', 'drupal')."] Erinnerung an Deinen Dienst",$txt,$es->email);
        break;
      }              
    }
  }  
}

function churchcore_checkUserMail($p, $mailtype, $id, $interval) {
   $result = db_query("SELECT letzte_mail FROM {cc_usermails} WHERE person_id=:person_id and mailtype=:mailtype and domain_id=:domain_id",
     array(":person_id"=>$p, ":mailtype"=>$mailtype, ":domain_id"=>$id))->fetch();
  $dt=new DateTime();
  if ($result==null) {
    db_insert("cc_usermails")
      ->fields(array("person_id"=>$p, "mailtype"=>$mailtype, "domain_id"=>$id, "letzte_mail"=>$dt->format('Y-m-d H:i:s')))
      ->execute();
    return true;
  } 
  else {
    $lm=new DateTime($result->letzte_mail);
    $dt=new DateTime(date("Y-m-d",strtotime("-".$interval." hour")));
    if ($lm<$dt) {
      $dt=new DateTime();
      db_query("update {cc_usermails} set letzte_mail=:dt where person_id=:p and mailtype=:mailtype and domain_id=:domain_id",
           array(":dt"=>$dt->format('Y-m-d H:i:s'), ":p"=>$p, ":mailtype"=>$mailtype, ":domain_id"=>$id));
      return true;
    }    
  }
  return false; 
}
function churchservice_inform_leader() {
  global $base_url;
  include_once("churchservice_db.inc");

  // Hole erst mal die Gruppen_Ids, damit ich gleich nicht alle Personen holen muß
  $res=db_query("select cdb_gruppen_ids from {cs_service} where cdb_gruppen_ids!='' and cdb_gruppen_ids is not null and sendremindermails_yn=1");
  $arr=array();
  foreach($res as $g) {
    $arr[]=$g->cdb_gruppen_ids;
  }
   
  if (count($arr)==0) return false;
  
  // Hole nun die Person/Gruppen wo die Person Leiter oder Co-Leiter ist
  $res=db_query("select p.id person_id, gpg.gruppe_id, p.email, p.vorname, p.cmsuserid from {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
      where gpg.gemeindeperson_id=gp.id and p.id=gp.person_id and status_no>=1 and status_no<=2
      and gpg.gruppe_id in (".implode(",",$arr).")");

  // Aggregiere nach Person_Id P1[G1,G2,G3],P2[G3]
  $persons=array();
  foreach ($res as $p) {
    $data=churchcore_getUserSettings("churchservice",$p->person_id);
    // Darf er überhaupt noch, und wenn ja dann schaue ob der Leiter es will.
    // (Wenn noch nicht bestätigt, dann wird davon ausgegangen
    $auth=getUserAuthorization($p->person_id);
    if (isset($auth["churchservice"]["view"]) && 
        ((!isset($data["informLeader"])) || ($data["informLeader"]==1))) {
      if (!isset($data["informLeader"])) {
        $data["informLeader"]=1;
        churchcore_saveUserSetting("churchservice",$p->person_id,"informLeader","1");
      }  
      
      if (isset($persons[$p->person_id]))
        $arr=$persons[$p->person_id]["group"];
      else {
        $persons[$p->person_id]=array();
        $arr=array();
        $persons[$p->person_id]["service"]=array();
        $persons[$p->person_id]["person"]=$p;
      }
      $arr[]=$p->gruppe_id;
      $persons[$p->person_id]["group"]=$arr;
    }
  }
  
  // Gehe nun die Personen durch und schaue wer seit einer Zeit keine Mail mehr bekommen hatte.
  foreach ($persons as $person_id=>$p) {
    if (!churchcore_checkUserMail($person_id, "informLeaderService", -1, 6*24)) {
      $persons[$person_id]=null;
    }
  }
  
  // Suche nun dazu die passenden Services
  $res=db_query("select cdb_gruppen_ids, bezeichnung, id service_id from {cs_service} where cdb_gruppen_ids is not null");
  foreach ($res as $d) {
    $gruppen_ids=explode(",", $d->cdb_gruppen_ids);
    foreach ($persons as $key=>$person) {
      if ($person!=null) {
        foreach($person["group"] as $person_group) {
          if (in_array($person_group,$gruppen_ids))
            $persons[$key]["service"][]=$d->service_id;
        }
      }  
    }    
  }
  
  // Gehe nun die Personen durch und suche nach Events
  foreach ($persons as $person_id=>$person) {
    if ($person!=null) {
      $res=db_query("select es.id, c.bezeichnung event, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, es.name, s.bezeichnung service 
         from {cs_event} e, {cs_eventservice} es, {cs_service} s, {cc_cal} c 
         where c.id=e.cc_cal_id and es.service_id in (".implode(",",$person["service"]).")
          and es.event_id=e.id and es.service_id=s.id and es.valid_yn=1 and zugesagt_yn=0
          and e.startdate>current_date and datediff(e.startdate,CURRENT_DATE)<=60 order by e.startdate");
      $txt='';
      foreach ($res as $es) {
        $txt.="<li>". $es->datum." ".$es->event." - Dienst ".$es->service.": ";
        $txt.='<font style="color:red">';
        if ($es->name==null)
          $txt.="?";
        else         
          $txt.=$es->name."?";
        $txt.='</font>';
      }
      if ($txt!='') {    
        $txt="<h3>Hallo ".$person["person"]->vorname."!</h3><p>Es sind in den n&auml;chsten 60 Tagen noch folgende Dienste offen:<ul>".$txt."</ul>";
        $txt.='<p><a href="'.$base_url.'/?q=churchservice" class="btn">Weitere Infos</a>&nbsp';
        $txt.='<p><a href="'.$base_url.'/?q=churchservice#SettingsView" class="btn">Benachrichtigung deaktivieren</a>';
        churchservice_send_mail("[".variable_get('site_name')."] Offene Dienste",$txt,$person["person"]->email);
      }
    }                                
  }
}

function churchservice_cron() {
  global $base_url;
  
  include_once('./'. drupal_get_path('module', 'churchservice') .'/churchservice_ajax.inc');
  
  churchservice_openservice_rememberdays();
  churchservice_remindme();
  churchservice_inform_leader();
}


?>
