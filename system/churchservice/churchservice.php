<?php 
/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2014 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt
 *
 * ChurchService Module
 * Depends on ChurchCore, ChurchCal
 *
 */

/**
 * main ical function
 */
function ical_main() {
  include_once(CHURCHSERVICE.'/churchservice_ajax.php');
  call_user_func("churchservice_ical");
}

/**
 * main churchservice ajax function
 * @return string
 */
function churchservice__ajax() {
  if (!user_access("view","churchservice")) {
    addInfoMessage(t("no.permission.for", $config["churchservice_name"]));
    return " "; // which meaning has this?
  }
  include_once(CHURCHSERVICE.'/churchservice_ajax.php');
  call_user_func("churchservice_ajax"); // why user_func? 
}

/**
 * filedownload
 */
function churchservice__filedownload() {
  include_once(CHURCHCORE."/churchcore.php");
  churchcore__filedownload();  
}

function churchservice_getAuth() {
  $cc_auth = array();
  $cc_auth = addAuth($cc_auth, 301, 'view', 'churchservice', null, t('view.churchservice'), 1);
  $cc_auth = addAuth($cc_auth, 304, 'view servicegroup', 'churchservice', 'cs_servicegroup', t('view.servicegroup.churchservice.cs_servicegroup'), 1);
  $cc_auth = addAuth($cc_auth, 305, 'edit servicegroup', 'churchservice', 'cs_servicegroup', t('edit.servicegroup.churchservice.cs_servicegroup'), 1);
  $cc_auth = addAuth($cc_auth, 302, 'view history', 'churchservice', null, t('view.history.churchservice'), 1);
  $cc_auth = addAuth($cc_auth, 303, 'edit events', 'churchservice', null, t('edit.events.churchservice'), 1);
  $cc_auth = addAuth($cc_auth, 309, 'edit template', 'churchservice', null, t('edit.template.churchservice'), 1);
  
  $cc_auth = addAuth($cc_auth, 307, 'manage absent', 'churchservice', null, t('manage.absent.churchservice'), 1);
  
  $cc_auth = addAuth($cc_auth, 321, 'view facts', 'churchservice', null, t('view.facts.churchservice'), 1);
  $cc_auth = addAuth($cc_auth, 308, 'edit facts', 'churchservice', null, t('edit.facts.churchservice'), 1);
  $cc_auth = addAuth($cc_auth, 322, 'export facts', 'churchservice', null, t('export.facts.churchservice'), 1);
  
  $cc_auth = addAuth($cc_auth, 331, 'view agenda', 'churchservice', 'cc_calcategory', t('view.agenda.churchservice.cc_calcategory'), 1);
  $cc_auth = addAuth($cc_auth, 332, 'edit agenda', 'churchservice', 'cc_calcategory', t('edit.agenda.churchservice.cc_calcategory'), 1);
  $cc_auth = addAuth($cc_auth, 333, 'edit agenda templates', 'churchservice', 'cc_calcategory', t('edit.agenda.templates.churchservice.cc_calcategory'), 1);
  
  $cc_auth = addAuth($cc_auth, 313, 'view songcategory', 'churchservice', 'cs_songcategory', t('view.songcategory.churchservice.cs_songcategory'), 1);
  $cc_auth = addAuth($cc_auth, 311, 'view song', 'churchservice', null, t('view.song.churchservice'), 1);
  $cc_auth = addAuth($cc_auth, 312, 'edit song', 'churchservice', null,t('edit.song.churchservice') , 1);
  
  $cc_auth = addAuth($cc_auth, 399,'edit masterdata', 'churchservice', null, t('edit.masterdata'), 1);
  
  return $cc_auth;
}

/**
 * get form for churchservice system preferences  
 * @return CTModuleForm
 */
function churchservice_getAdminForm() {
  global $config;
  
  $model = new CTModuleForm("churchservice");
  $model->addField("churchservice_entries_last_days", "", "INPUT_REQUIRED", t('data.from.x.how.many.days.in.the.past.to.load', 'ChurchService'))
    ->setValue($config["churchservice_entries_last_days"]);
  $model->addField("churchservice_openservice_rememberdays", "", "INPUT_REQUIRED", t('after.how.many.days.service.requests.should.be.repeated'))
    ->setValue($config["churchservice_openservice_rememberdays"]);
  $model->addField("churchservice_reminderhours", "", "INPUT_REQUIRED", t('how.many.hours.before.service.send.remember.email'))
    ->setValue($config["churchservice_reminderhours"]);
  
  $model->addField("churchservice_songwithcategoryasdir", "", "CHECKBOX", t('use.category.as.folder.for.beamersoftware.export'))
    ->setValue(getConf("churchservice_songwithcategoryasdir", "0"));
  
  return $model;
}

/**
 * export facts
 * @return string|NULL
 */
function churchservice__exportfacts() {
  if (!user_access("export facts", "churchservice")) {
    addInfoMessage(t('no.permisson.to.export.facts'));
    return " ";
  }
  drupal_add_http_header('Content-type', 'application/csv; charset=ISO-8859-1; encoding=ISO-8859-1', true);
  drupal_add_http_header('Content-Disposition', 'attachment; filename="churchservice_fact_export.csv"', true);
  
  $events = churchcore_getTableData("cs_event", "startdate");
  $cond = ($d = getVar("date")) ? " AND e.startdate>='$d'" : '';
  
  $db = db_query("SELECT e.*, c.bezeichnung, c.category_id 
                  FROM {cs_event} e, {cc_cal} c 
                  WHERE e.cc_cal_id=c.id $cond 
                  ORDER BY e.startdate");
  $events = array ();
  foreach ($db as $e) {
    $events[$e->id] = $e;
  }
  
  $category = churchcore_getTableData("cc_calcategory");
  $facts = churchcore_getTableData("cs_fact", "sortkey");
  $res = db_query("SELECT * FROM {cs_event_fact}");
  
  $result = array ();
  foreach ($res as $d) {
    $result[$d->event_id]->facts[$d->fact_id] = $d->value;
  }
  
  echo '"Datum";"Bezeichnung";"Notizen";"Kategorie";';
  foreach ($facts as $fact) {
    echo mb_convert_encoding('"' . $fact->bezeichnung . '";', 'ISO-8859-1', 'UTF-8');
  }
  echo "\n";
  foreach ($events as $key => $event) {
    if (isset($result[$key])) {
      echo "$event->startdate;";
      echo mb_convert_encoding('"' . $event->bezeichnung . '";', 'ISO-8859-1', 'UTF-8');
      echo mb_convert_encoding('"' . $event->special . '";', 'ISO-8859-1', 'UTF-8');
      echo mb_convert_encoding('"' . $category[$event->category_id]->bezeichnung . '";', 'ISO-8859-1', 'UTF-8');
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

/**
 * print view
 * @return string
 */
function churchservice__printview() {
  global $version, $files_dir, $config, $embedded;


  drupal_add_css(ASSETS.'/fileuploader/fileuploader.css');

  drupal_add_js(BOOTSTRAP.'/js/bootstrap-multiselect.js');
  drupal_add_js(ASSETS.'/fileuploader/fileuploader.js');
  drupal_add_js(ASSETS.'/js/jquery.history.js');

  drupal_add_js(ASSETS.'/mediaelements/mediaelement-and-player.min.js');
  drupal_add_css(ASSETS.'/mediaelements/mediaelementplayer.css');

  drupal_add_js(ASSETS.'/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS.'/ckeditor/lang/de.js');

  drupal_add_js(CHURCHCORE .'/cc_abstractview.js');
  drupal_add_js(CHURCHCORE .'/cc_standardview.js');
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js');

  drupal_add_js(CHURCHSERVICE .'/cs_loadandmap.js');
  drupal_add_js(CHURCHSERVICE .'/cs_settingsview.js');
  drupal_add_js(CHURCHSERVICE .'/cs_maintainview.js');
  drupal_add_js(CHURCHSERVICE .'/cs_listview.js');
  //drupal_add_js(CHURCHSERVICE .'/cs_testview.js');
  drupal_add_js(CHURCHSERVICE .'/cs_calview.js');
  drupal_add_js(CHURCHSERVICE .'/cs_factview.js');
  drupal_add_js(CHURCHSERVICE .'/cs_agendaview.js');
  drupal_add_js(CHURCHSERVICE .'/cs_songview.js');
  drupal_add_js(CHURCHSERVICE .'/cs_main.js');

  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchservice"));
  
  $content = "";
  // ids for direct links 
  if ($id = getVar("id")) $content .= "<input type='hidden' id='externevent_id' value='$id'/>";
  if ($sId = getVar("service_id")) $content .= "<input type='hidden' id='service_id' value='$sId'/>";
  if ($date = getVar("date")) $content .= "<input type='hidden' id='currentdate' value='$date'/>";
  if ($filter = getVar("meineFilter")) $content .= "<input type='hidden' id='externmeineFilter' value='$filter'/>";
  
  $embedded = true;
  
  $content .= "<input type='hidden' id='printview' value='true'/>
      
<div class='row-fluid'>
  <div class='span12'>
    <div id='cdb_group'></div>
    <div id='cdb_content'></div>
  </div>
</div>
";
  
  return $content;
}

/**
 * main function for churchservice
 * @return string
 */
function churchservice_main() {
  global $version, $files_dir, $config;
  
  drupal_add_css(ASSETS.'/fileuploader/fileuploader.css'); 
  
  drupal_add_js(BOOTSTRAP.'/js/bootstrap-multiselect.js'); 
  drupal_add_js(ASSETS.'/fileuploader/fileuploader.js'); 
  drupal_add_js(ASSETS.'/js/jquery.history.js'); 
  
  drupal_add_js(ASSETS.'/mediaelements/mediaelement-and-player.min.js'); 
  drupal_add_css(ASSETS.'/mediaelements/mediaelementplayer.css');
    
  drupal_add_js(ASSETS.'/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS.'/ckeditor/lang/de.js');  
    
  drupal_add_js(CHURCHCORE .'/cc_abstractview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_standardview.js'); 
  drupal_add_js(CHURCHCORE .'/cc_maintainstandardview.js'); 
  
  drupal_add_js(CHURCHSERVICE .'/cs_loadandmap.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_settingsview.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_maintainview.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_listview.js'); 
  //drupal_add_js(CHURCHSERVICE .'/cs_testview.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_calview.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_factview.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_agendaview.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_songview.js'); 
  drupal_add_js(CHURCHSERVICE .'/cs_main.js'); 
    
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchservice"));
  
  $content="";
  // ids for direct links
  if ($id = getVar("id")) $content .= "<input type='hidden' id='externevent_id' value='$id'/>";
  if ($sId = getVar("service_id")) $content .= "<input type='hidden' id='service_id' value='$sId'/>";
    
  $content .= " 
<div class='row-fluid'>
  <div class='span3'>
    <div id='cdb_menu'></div>
    <div id='cdb_filter'></div>
  </div>  
  <div class='span9'>
    <div id='cdb_search'></div> 
    <div id='cdb_group'></div> 
    <div id='cdb_content'></div>
  </div>
</div>
";
  return $content;
}

/**
 * get pending service requests of current user
 * TODO: rename to getPendingRequests or getPendingRequestsOfUser
 * 
 * @return string
 */
function churchservice_getUserOpenServices() {
  global $user;
  
  if ($id = getVar("eventservice_id")) {
    include_once('./'. CHURCHSERVICE .'/churchservice_ajax.php');
    $reason = getVar("reason", null);
    if (getVar("zugesagt_yn") == 1) {
      churchservice_updateEventService($id, $user->vorname." ".$user->name, $user->id, 1, $reason);
    } else  {
      churchservice_updateEventService($id, null, null, 0, $reason);
    }
    addInfoMessage(t('"thank.you.for.feedback"'));
  }
  
  include_once('./'. CHURCHDB .'/churchdb_db.php');
  
  $txt =  $txt1 = $txt2 = "";
//  $pid = $user->id; //not used
  $res = db_query("
    SELECT cal.bezeichnung AS event, e.id AS event_id, es.id AS eventservice_id, allowtonotebyconfirmation_yn,
      DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') AS datum, s.bezeichnung AS service, s.id AS service_id, 
      sg.bezeichnung AS servicegroup, concat(p.vorname, ' ', p.name) AS modifieduser, p.id AS modified_pid
    FROM {cs_eventservice} es, {cs_event} e, {cs_servicegroup} sg, {cs_service} s, {cdb_person} p, {cc_cal} cal 
    WHERE e.valid_yn=1 AND cal.id=e.cc_cal_id AND cdb_person_id=:user_id AND e.startdate>=CURRENT_DATE() 
      AND es.modified_pid=p.id AND zugesagt_yn=0 AND es.valid_yn=1 AND es.event_id=e.id  
      AND es.service_id=s.id AND sg.id=s.servicegroup_id 
    ORDER BY datum",
    array(':user_id' => $user->id));
  
  $nr = 0; //TODO: not needed?
  
  foreach ($res as $arr) {
    $nr++;
    $txt2 .= "<div class='service-request' style='display:none;' data-id='$arr->eventservice_id' 
                data-modified-user='$arr->modifieduser'";
    
    if ($arr->allowtonotebyconfirmation_yn == 1) {
      $txt2 .= " data-comment-confirm='$arr->allowtonotebyconfirmation_yn'";
    }
    if (user_access('view', 'churchdb')) $txt2 .= " data-modified-pid='$arr->modified_pid'";
    $txt2 .= ">";
    
    $txt2 .= "<a href='?q=churchservice&id=$arr->event_id'>$arr->datum - $arr->event</a>: 
              <a href='?q=churchservice&id=$arr->event_id'><b>$arr->service</b></a> ($arr->servicegroup)" . NL;
    
    $files = churchcore_getFilesAsDomainIdArr("service", $arr->event_id);
    $txt .= '<span class="pull-right">';
    if (isset($files) && isset($files[$arr->event_id])) {
      $i = 0;
      foreach ($files[$arr->event_id] as $file) {
        $i++;
        if ($i <= 3) $txt .= churchcore_renderFile($file) . "&nbsp;";
        else $txt .= "...";
      }
    }
    $txt .= "</span>";
    // TODO: add some sort of visual style to yes/no - checkmark/cross, green/red color, ...
    $txt2 .= '
        <div style="margin-left:16px;margin-bottom:10px;" class="service-request-answer"></div>
      </div>';
  }
  if ($txt2) $txt .= $txt1 . $txt2 . '
      <p align="right"><a href="#" style="display:none" class="service-request-show-all">' . t("show.all") . '</a>';
  
  return $txt;
}

/**
 * get current events with services of groups i am leader/coleader of
 * @return string
 */
function churchservice_getCurrentEvents() {
  global $user;

  $mygroups = churchdb_getMyGroups($user->id, true, true);
// add this selection to sql to simplify php
  $groupWhere = ' AND ('. implode(' IN (cdb_gruppen_ids) OR ', $mygroups). ' IN (cdb_gruppen_ids))';
  $txt = "";
  
  $events = db_query("SELECT e.id, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') datum, bezeichnung 
                      FROM {cs_event} e, {cc_cal} cal 
                      WHERE e.valid_yn=1 AND cal.id=e.cc_cal_id AND DATE_ADD(e.startdate, INTERVAL 3 hour) > NOW() 
                        AND DATEDIFF(e.startdate, NOW())<3 
                      ORDER BY e.startdate");
  foreach ($events as $event) {
    $firstrow = true;
    $services = db_query("SELECT es.name, s.cdb_gruppen_ids, s.bezeichnung, es.zugesagt_yn
                          FROM {cs_eventservice} es, {cs_service} s, {cs_servicegroup} sg 
                          WHERE es.valid_yn=1 AND es.service_id=s.id AND s.servicegroup_id=sg.id 
                          AND es.event_id=$event->id $groupWhere
                          ORDER BY sg.sortkey, s.sortkey");
    
    if ($services) $txt .= '<li><a href="?q=churchservice&id=' . $event->id . '">' . "$event->datum - $event->bezeichnung</a><p>";
    foreach ($services as $s) {
//       if ($firstrow) { //TODO: should work before foreach :-)
//         $txt .= '<li><a href="?q=churchservice&id=' . $event->id . '">' . "$event->datum - $event->bezeichnung</a><p>";
//         $firstrow = false;
//       }
      $txt .= "<small>&nbsp; $s->bezeichnung: ";
      if ($s->zugesagt_yn == 1) $txt .= $s->name;
      else $txt .= '<font style="color:red">' . ($s->name ? $s->name : "?") . '</font>';
      $txt .= "</small><br/>";
    }
  }
  if ($txt) $txt = "<ul>$txt</ul>";
  
  return $txt;
}

/**
 * get next services for current user
 * @param string $short
 * @return string
 */
function churchservice_getUserNextServices($short = true) {
  global $user;
  
  include_once ('./' . CHURCHDB . '/churchdb_db.php');
  
  $pid = $user->id;
  $res = db_query("
    SELECT e.id event_id, cal.bezeichnung AS event, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') AS datum, 
      s.bezeichnung AS service, sg.bezeichnung AS servicegroup, cdb_person_id, 
      DATE_FORMAT(es.modified_date, '%d.%m.%Y %H:%i') AS modified_date
    FROM {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_servicegroup} sg, {cs_service} s 
    WHERE cal.id=e.cc_Cal_id AND e.valid_yn=1 AND cdb_person_id=:pid AND e.startdate>=current_date AND zugesagt_yn=1 
      AND es.valid_yn=1 AND es.event_id=e.id AND es.service_id=s.id AND sg.id=s.servicegroup_id order by e.startdate",
    array(':pid' => $pid));
  
  $nr = 0;
  $txt = "";
  foreach ($res as $arr) {
    $nr++;
    if (($nr <= 5) || (!$short)) {
      $txt .= "<p><a href='?q=churchservice&id='$arr->event_id'>$arr->datum - $arr->event</a>: 
               <a href='?q=churchservice&id='$arr->event_id'><b>$arr->service</b></a> ($arr->servicegroup)";
      $files = churchcore_getFilesAsDomainIdArr("service", $arr->event_id);
      $txt .= '<span class="pull-right">';
      if (isset($files) && isset($files[$arr->event_id])) {
        $i = 0;
        foreach ($files[$arr->event_id] as $file) {
          $i++;
          if ($i < 4) $txt .= churchcore_renderFile($file) . "&nbsp;";
          else $txt .= "..."; // TODO: ... for each additional file?
        }
      }
      $txt .= "</span><small><br>&nbsp; &nbsp; &nbsp; " . t("confirmed.on", $arr->modified_date) . "</small>";
    }
  }
  return $txt;
}

/**
 * get facts of past 3 days
 * @return string
 */
function churchservice_getFactsOfLastDays() {
  $txt = '';
  if (user_access("view facts", "churchservice")) {
    $res = db_query("
      SELECT e.id, cal.bezeichnung AS eventname, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') AS datum, f.bezeichnung AS factname, value 
      FROM {cs_fact} f, {cs_event_fact} ef, {cs_event} e, {cc_cal} cal
      WHERE cal.id=e.cc_cal_id AND ef.fact_id=f.id AND ef.event_id=e.id 
        AND DATEDIFF(NOW(), e.startdate)<3 and DATEDIFF(NOW(), e.startdate)>=0
      ORDER BY e.startdate, factname");
    $event = null;
    foreach ($res as $val) { // TODO: begins with </small>?
      if ($val->id != $event) {
        $event = $val->id;
        $txt .= "</small><li><a href='?q=churchservice&id=$val->id#FactView/'>$val->datum - $val->eventname</a><p>";
      }
      $txt .= '<small>' . $val->factname . ": " . $val->value . "</small><br/>";
    }
    if ($txt) $txt = '<ul>' . $txt . '</ul>';
  }
  return $txt;
}

/**
 * get absent times
 * @param string $year
 * @return string
 */
function churchservice_getAbsents($year = null) {
  $txt = '';
  
  if (user_access("view", "churchdb")) {
    $user = $_SESSION["user"];
    include_once (CHURCHDB . '/churchdb_db.php');
    $groups = churchdb_getMyGroups($user->id, true, true);
    $allPersonIds = churchdb_getAllPeopleIdsFromGroups($groups);
    
    if (count($groups) > 0 && count($allPersonIds) > 0) {
      $sql = "SELECT p.id p_id, p.name, p.vorname, DATE_FORMAT(a.startdate, '%d.%m.') AS startdate_short, 
                DATE_FORMAT(a.startdate, '%d.%m.%Y') AS startdate, DATE_FORMAT(a.enddate, '%d.%m.%Y') AS enddate, 
                a.bezeichnung, ar.bezeichnung reason 
              FROM {cdb_person} p, {cs_absent} a, {cs_absent_reason} ar 
              WHERE a.absent_reason_id=ar.id AND p.id=a.person_id 
              AND p.id in (" . implode(",", $allPersonIds) . ") ";
      if ($year == null) $sql .= "AND DATEDIFF(a.enddate,NOW())>=-1 AND DATEDIFF(a.enddate,NOW())<=31";
      else $sql .= "AND (DATE_FORMAT(a.startdate, '%Y')=$year OR DATE_FORMAT(a.enddate, '%Y')=$year)";
      $sql .= "
              ORDER BY a.startdate";
      
      $db = db_query($sql);
      $people = array ();
      foreach ($db as $a) {
        if (!isset($people[$a->p_id])) $people[$a->p_id] = array ();
        $people[$a->p_id][] = $a;
      }
      if (count($people)) {
        $txt = '<ul>';
        foreach ($people as $p) {
          $txt .= '<li>' . $p[0]->vorname . " " . $p[0]->name . ": <p>";
          foreach ($p as $abwesend) {
            $reason = $abwesend->bezeichnung ? $abwesend->bezeichnung . " ($reason)" : $abwesend->reason;
            if ($abwesend->startdate == $abwesend->enddate) $txt .= "<small>$abwesend->startdate $reason</small><br/>";
            else $txt .= "<small>$abwesend->startdate_short - $abwesend->enddate $reason</small><br/>";
          }
        }
        $txt .= '</ul>';
      }
      if ($year == null && user_access("view", "churchcal")) {
        $txt .= '<p style="line-height:100%" align="right"><a href="?q=churchcal&viewname=yearView">' . t("more") . '</a></p>';
      }
    }
  }
  return $txt;
}

/**
 * get array of blocks from churchservice
 * @return array
 */
function churchservice_blocks() {
  return (array(
    1 => array(
      "label" => t("your.pending.service.requests"),
      "col" => 2,
      "sortkey" => 1,
      "html" => churchservice_getUserOpenServices(),
      "help" => "Offene Dienstanfragen",
      "class" => "service-request",
    ),  
    2 => array(
      "label" => t("your.next.services"),
      "col" => 2,
      "sortkey" => 2,
      "html" => churchservice_getUserNextServices(),
      "help" =>  '',
      "class" =>  '',
    ),  
    3 => array(
      "label" => t("your.current.event.staff"),
      "col" => 2,
      "sortkey" => 3,
      "html" => churchservice_getCurrentEvents(),
      "help" =>  '',
      "class" =>  '',
    ),  
    4 => array(
      "label" => t("absence.of.next.x.days", 30),
      "col" => 2,
      "sortkey" => 4,
      "html" => churchservice_getAbsents(),
      "help" =>  t('pending.service.requests'),
      "class" =>  '',
    ),  
    5 => array(
      "label" => t("facts.of.last.days"),
      "col" => 2,
      "sortkey" => 5,
      "html" => churchservice_getFactsOfLastDays(),
      "help" =>  '',
      "class" =>  '',
    ),  
  ));
}

/**
 * info for pending requests
 * TODO: rename churchservice_openservice_rememberdays
 * TODO: could sql queries be reduced?
 */
function churchservice_openservice_rememberdays() {
  global $base_url;
  include_once ("churchservice_db.php");
  
  $delay = (int) getConf('churchservice_openservice_rememberdays');
  $dt = new datetime();
  
  // get ONE eventService needed to send (not yet send or still pending).
  // from persons having ?email ??und auch gemappt wurde??.
  $sql = "SELECT es.id, p.id p_id, p.vorname, p.email, es.modified_pid, 
            IF (password IS NULL AND loginstr IS NULL AND lastlogin IS NULL,1,0) AS invite  
          FROM {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_service} s, {cdb_person} p 
          WHERE e.valid_yn=1 AND e.cc_cal_id=cal.id AND es.valid_yn=1 AND es.zugesagt_yn=0 
            AND es.cdb_person_id IS NOT NULL AND es.service_id=s.id AND s.sendremindermails_yn=1 
            AND es.event_id=e.id AND e.Startdate>=current_date
            AND ((es.mailsenddate IS NULL) OR (DATEDIFF(current_date,es.mailsenddate)>=$delay))
            AND p.email!='' AND p.id=es.cdb_person_id LIMIT 1";
  $res = db_query($sql)->fetch();
  
  $sql2 = "SELECT es.id id, cal.bezeichnung AS event, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') AS datum, 
             e.id event_id, s.bezeichnung service, sg.bezeichnung servicegroup, es.mailsenddate
           FROM {cs_eventservice} es, {cs_event} e, {cc_cal} cal, {cs_service} s, {cs_servicegroup} sg 
           WHERE e.valid_yn=1 AND cal.id=e.cc_cal_id AND es.valid_yn=1 AND es.zugesagt_yn=:zugesagt 
             AND es.cdb_person_id=:p_id AND s.sendremindermails_yn=1 AND es.event_id=e.id 
             AND es.service_id=s.id AND sg.id=s.servicegroup_id AND e.startdate>=current_date
           ORDER BY e.startdate";
  $i = 0;
  // process only 15 services to prevent too many mails at once
  while (($res) && ($i < 15)) {
    $txt = "<h3>Hallo " . $res->vorname . ",</h3><p>";
    
    // TODO: use mail template
    $inviter = churchcore_getPersonById($res->modified_pid);
    $txt .= "Du wurdest im Dienstplan auf " . getConf('site_name', 'ChurchTools');
    if ($inviter) $txt .= ' von <i>' . $inviter->vorname . " " . $inviter->name . "</i>";
    $txt .= " zu Diensten vorgeschlagen. <br/>Zum Zu- oder Absagen bitte hier klicken:";
    
    $loginstr = churchcore_createOnTimeLoginKey($res->p_id);
    
    $txt .= "<p><a href='$base_url?q=home&id=$res->p_id&loginstr=$loginstr' class='btn btn-primary'>%sitename</a>";
    
    $txt .= "<p><p><b>Folgende Dienst-Termine sind von Dir noch nicht bearbeitet:</b><ul>";
    
    $arr = db_query($sql2, array (":p_id" => $res->p_id, ":zugesagt" => 0));
    //TODO: get all services of person at once and select in foreach if it goes to $txt or $txt2?
    foreach ($arr as $res2) {
      $txt .= "<li> $res2->datum $res2->event: $res2->service ($res2->servicegroup)";
      db_update("cs_eventservice")
        ->fields(array ("mailsenddate" => $dt->format('Y-m-d H:i:s')))
        ->condition('id', $res2->id, "=")
        ->execute();
    }
    $txt .= '</ul>';
    
    $arr = db_query($sql2, array (":p_id" => $res->p_id, ":zugesagt" => 1));
    $txt2 = "";
    foreach ($arr as $res2) {
      $txt .= "<li> $res2->datum $res2->event: $res2->service ($res2->servicegroup)";
      if ($res2->mailsenddate == null) $txt2 .= " NEU!";
      db_update("cs_eventservice")
        ->fields(array ("mailsenddate" => $dt->format('Y-m-d H:i:s')))
        ->condition('id', $res2->id, "=")
        ->execute();
    }
    if ($txt2) {
      $txt .= "<p><p><b>Bei folgenden Diensten hast Du schon zugesagt:</b><ul>" . $txt2 . "</ul>";
    }
    
    // Person was not yet invited -> send invitation.
    if ($res->invite == 1) {
      include_once (CHURCHDB . '/churchdb_ajax.php');
      churchdb_invitePersonToSystem($res->p_id);
      $txt .= "<p><b>Da Du noch nicht kein Zugriff auf das System hast, bekommst Du noch eine separate E-Mail, mit der Du Dich dann anmelden kannst!.</b>";
    }
    
    churchservice_send_mail("[" . getConf('site_name', 'ChurchTools') . "] " . t('there.are.pending.services'), $txt, $res->email);
    $i = $i + 1;
    $res = db_query($sql)->fetch();
  }
}

/**
 * remind users of there eventservices
 */
function churchservice_remindme() {
  global $base_url;
  include_once ("churchservice_db.php");
  
  $sql = "SELECT p.vorname, p.name, p.email, cal.bezeichnung, s.bezeichnung AS dienst, sg.bezeichnung AS sg, e.id AS event_id, 
           DATE_FORMAT(e.Startdate, '%d.%m.%Y %H:%i') AS datum, es.id AS eventservice_id
          FROM {cs_eventservice} es, {cs_service} s, {cs_event} e, {cc_cal} cal, {cs_servicegroup} sg, {cdb_person} p 
          WHERE cal.id=e.cc_cal_id AND e.id=es.event_id AND s.id=es.service_id
            AND es.cdb_person_id=:person_id AND p.id=:person_id AND p.email!='' 
            AND e.valid_yn=1 AND es.valid_yn=1 AND es.zugesagt_yn=1
            AND UNIX_TIMESTAMP(e.startdate)-UNIX_TIMESTAMP(now())<60*60*(:hours) AND UNIX_TIMESTAMP(e.startdate)-UNIX_TIMESTAMP(now())>0
            AND s.sendremindermails_yn=1 AND s.servicegroup_id=sg.id 
          ORDER BY datum"; //is UNIX_TIMESTAMP outdated here?
  
//  $usersToRemind = db_query("SELECT * FROM {cc_usersettings} 
  $usersToRemind = db_query("SELECT id FROM {cc_usersettings} 
                             WHERE modulename='churchservice' AND attrib='remindMe' AND value=1");
  
  foreach ($usersToRemind as $p) {
    //get eventservices to be reminded now
    $res = db_query($sql, 
        array (":person_id" => $p->person_id, 
               ":hours" => getConf('churchservice_reminderhours'),
        ));
    foreach ($res as $es) {
      //TODO: use mail template
      if (churchcore_checkUserMail($p->person_id, "remindService", $es->eventservice_id, getConf('churchservice_reminderhours'))) {
        $txt = "<h3>Hallo " . $es->vorname . "!</h3>";
        $txt .= '<p>Dies ist eine Erinnerung an Deine n‰chsten Dienste:</p><br/>';
        $txt .= '<table class="table table-condensed">';
        //get eventservices to be reminded in the next 12 hours
        $res2 = db_query($sql, 
                         array (":person_id" => $p->person_id, 
                                ":hours" => getConf('churchservice_reminderhours') + 12,
                         ));
        foreach ($res2 as $es2) {
          if ($es2->eventservice_id == $es->eventservice_id || (churchcore_checkUserMail($p->person_id, 
                                                                                         "remindService", 
                                                                                         $es2->eventservice_id, 
                                                                                         getConf('churchservice_reminderhours')))) {
            $txt .= '<tr><td>' . $es2->datum . ' ' . $es2->bezeichnung . '<td>Dienst: ' . $es2->dienst . " (" . $es2->sg . ")";
            $txt .= '<td style="min-width:79px;"><a href="' . $base_url . '?q=churchservice&id=' . $es2->event_id .
                 '" class="btn btn-primary">Event aufrufen</a>';
          }
        }
        
        $txt .= '</table><br/><br/><a class="btn" href="' . $base_url . '?q=churchservice#SettingsView">Erinnerungen deaktivieren</a>';
        churchservice_send_mail("[" . getConf('site_name', 'ChurchTools') . "] Erinnerung an Deinen Dienst", $txt, $es->email);
        break;
      }
    }
  }
}

/**
 * something about send mails in DB 
 * TODO: explain, maybe rename churchcore_checkUserMail
 * 
 * @param int $personId
 * @param unknown $mailtype
 * @param unknown $domainId
 * @param int $interval
 * @return boolean
 */
function churchcore_checkUserMail($personId, $mailtype, $domainId, $interval) {
  $res = db_query("SELECT letzte_mail FROM {cc_usermails} 
                   WHERE person_id=:person_id AND mailtype=:mailtype AND domain_id=:domain_id", 
                   array (":person_id" => $personId, 
                          ":mailtype" => $mailtype, 
                          ":domain_id" => $domainId,
                         ))->fetch();
  $dt = new DateTime();
  if (!$res) {
    db_insert("cc_usermails")
      ->fields(array ("person_id" => $p, 
                      "mailtype"  => $mailtype, 
                      "domain_id" => $domainId, 
                      "letzte_mail" => $dt->format('Y-m-d H:i:s'),
      ))->execute();
      
    return true; //TODO: use on duplicate update or replace 
  }
  else {
    $lm = new DateTime($result->letzte_mail);
    $dt = new DateTime(date("Y-m-d", strtotime("-" . $interval . " hour")));
    if ($lm < $dt) {
      $dt = new DateTime();
      db_query("UPDATE {cc_usermails} SET letzte_mail=:dt 
                WHERE person_id=:p AND mailtype=:mailtype AND domain_id=:domain_id", 
                array(":person_id" => $personId, 
                      ":mailtype"  => $mailtype, 
                      ":domain_id" => $domainId,
                      ":dt" => $dt->format('Y-m-d H:i:s'),
                ));
      return true;
    }
  }
  return false;
}

/**
 * inform leader about open event services
 * TODO: no idea if this could be improved - lots of sql requests and loops
 * @return boolean
 */
function churchservice_inform_leader() {
  global $base_url;
  include_once ("churchservice_db.php");
  
  // get all group ids from services
  $res = db_query("SELECT cdb_gruppen_ids FROM {cs_service} 
                   WHERE cdb_gruppen_ids!='' AND cdb_gruppen_ids IS NOT NULL");
//                   WHERE cdb_gruppen_ids>''); // TODO: works too
  
  $arr = array ();
  foreach ($res as $g)$arr[] = $g->cdb_gruppen_ids;
  if (!count($arr)) return false;
  
  // get persons being (co)leader of one of this service groups
  $res = db_query("SELECT p.id person_id, gpg.gruppe_id, p.email, p.vorname, p.cmsuserid 
                   FROM {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
                   WHERE gpg.gemeindeperson_id=gp.id and p.id=gp.person_id and status_no>=1 and status_no<=2
                     AND gpg.gruppe_id in (" . implode(",", $arr) . ")");
  // Aggregiere nach Person_Id P1[G1,G2,G3],P2[G3]
  $persons = array ();
  foreach ($res as $p) {
    $data = churchcore_getUserSettings("churchservice", $p->person_id);
    // if person has rights and has not deselected info emails
    $auth = getUserAuthorization($p->person_id);
    if (isset($auth["churchservice"]["view"]) && (!isset($data["informLeader"]) || $data["informLeader"])) {
      
      if (!isset($data["informLeader"])) {
        $data["informLeader"] = 1;
        churchcore_saveUserSetting("churchservice", $p->person_id, "informLeader", "1");
      }
      if (!isset($persons[$p->person_id])) {
        $persons[$p->person_id] = array(
          "group" => array (),
          "service" => array (),
          "person" => $p,
        );
      }
      $persons[$p->person_id]["group"][] = $p->gruppe_id;
    }
  }
  
  // who should get an email?
  foreach ($persons as $person_id => $p) {
    if (!churchcore_checkUserMail($person_id, "informLeaderService", -1, 6 * 24)) {
      $persons[$person_id] = null; // unset($persons[$person_id])?
    }
  }
  
  // get matching services
  // TODO: nearly the same request as above (additonal bezeichnung, id service_id)
  $res = db_query("SELECT cdb_gruppen_ids, bezeichnung, id AS service_id 
                   FROM {cs_service} 
                   WHERE cdb_gruppen_ids is not null");
  foreach ($res as $d) {
    $group_ids = explode(",", $d->cdb_gruppen_ids);
    foreach ($persons as $key => $person) {
      if ($person != null) {
        foreach ($person["group"] as $person_group) {
          if (in_array($person_group, $group_ids)) $persons[$key]["service"][] = $d->service_id;
        }
      }
    }
  }
  
  // Gehe nun die Personen durch und suche nach Events
  // get events for each person
  foreach ($persons as $person_id => $person) if ($person) {
    $res = db_query("SELECT es.id, c.bezeichnung AS event, 
                       DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') AS datum, es.name, s.bezeichnung AS service 
                     FROM {cs_event} e, {cs_eventservice} es, {cs_service} s, {cc_cal} c 
                     WHERE e.valid_yn=1 AND c.id=e.cc_cal_id AND es.service_id in (" . implode(",", $person["service"]) . ")
                       AND es.event_id=e.id AND es.service_id=s.id AND es.valid_yn=1 AND zugesagt_yn=0
                       AND e.startdate>current_date AND DATEDIFF(e.startdate,CURRENT_DATE)<=60 
                     ORDER BY e.startdate");
    $txt = '';
    foreach ($res as $es) {
      $txt .= "<li>" . $es->datum . " " . $es->event . " - Dienst " . $es->service . ": ";
      $txt .= '<font style="color:red">' . ($es->name ? $es->name : "?") . '</font>';
    }
    if ($txt != '') {
      $txt = "<h3>Hallo " . $person["person"]->vorname .
           "!</h3><p>Es sind in den n√§chsten 60 Tagen noch folgende Dienste offen:<ul>" . $txt . "</ul>";
      $txt .= '<p><a href="' . $base_url . '/?q=churchservice" class="btn">' . t("more.information") . '</a>&nbsp';
      $txt .= '<p><a href="' . $base_url . '/?q=churchservice#SettingsView" class="btn">Benachrichtigung deaktivieren</a>';
      churchservice_send_mail("[" . getConf('site_name', 'ChurchTools') . "] Offene Dienste", $txt, $person["person"]->email);
    }
  }
}

/**
 * cron for churchservice (send reminder emails, info about open services and requests
 */
function churchservice_cron() {
  global $base_url;
  
  include_once ('./' . CHURCHSERVICE . '/churchservice_ajax.php');
  
  churchservice_openservice_rememberdays();
  churchservice_remindme();
  churchservice_inform_leader();
}
