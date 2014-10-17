<?php

/**
 * ChurchTools 2.0
 * http://www.churchtools.de
 *
 * Copyright (c) 2014 Jens Martin Rauen
 * Licensed under the MIT license, located in LICENSE.txt
 *
 * ChurchDB Module
 * Depends on ChurchCore
 */

/**
 */
function churchdb__ajax() {
  include_once (CHURCHDB . '/churchdb_ajax.php');
  call_user_func("churchdb_ajax"); // TODO: why not calling churchdb_ajax() direct?
}

/**
 * get auth for churchdb
 *
 * @return array
 */
function churchdb_getAuth() {
  $cc_auth = array ();
  $cc_auth = addAuth($cc_auth, 121, 'view birthdaylist', 'churchdb', null, t('view.birthdaylist'), 1);
  $cc_auth = addAuth($cc_auth, 122, 'view memberliste', 'churchdb', null, t('view.memberliste'), 1);
  
  $cc_auth = addAuth($cc_auth, 101, 'view', 'churchdb', null, t('view.x', 'ChurchDB'), 1);
  $cc_auth = addAuth($cc_auth, 106, 'view statistics', 'churchdb', null, t('view.statistics'), 1);
  $cc_auth = addAuth($cc_auth, 107, 'view tags', 'churchdb', null, t('view.tags'), 1);
  $cc_auth = addAuth($cc_auth, 108, 'view history', 'churchdb', null, t('view.history'), 1);
  $cc_auth = addAuth($cc_auth, 113, 'view comments', 'churchdb', 'cdb_comment_viewer', t('view.comments'), 1);
  $cc_auth = addAuth($cc_auth, 105, 'view address', 'churchdb', null, t('view.address'), 1);
  $cc_auth = addAuth($cc_auth, 103, 'view alldetails', 'churchdb', null, t('view.alldetails'), 1);
  $cc_auth = addAuth($cc_auth, 116, 'view archive', 'churchdb', null, t('view.archive'), 1);
  $cc_auth = addAuth($cc_auth, 120, 'complex filter', 'churchdb', null, t('use.complex.filters'), 1);
  $cc_auth = addAuth($cc_auth, 118, 'push/pull archive', 'churchdb', null, t('archivate.persons'), 1);
  $cc_auth = addAuth($cc_auth, 109, 'edit relations', 'churchdb', null, t('edit.relations'), 1);
  $cc_auth = addAuth($cc_auth, 110, 'edit groups', 'churchdb', null, t('edit.groups'), 1);
  $cc_auth = addAuth($cc_auth, 119, 'create person', 'churchdb', null, t('create.persons'), 1);
  $cc_auth = addAuth($cc_auth, 123, 'create person without agreement', 'churchdb', null, t('create.persons.without.agreement'), 1);
  
  $cc_auth = addAuth($cc_auth, 111, 'write access', 'churchdb', null, 'write.access.persons', 1);
  $cc_auth = addAuth($cc_auth, 102, 'view alldata', 'churchdb', 'cdb_bereich', t('view.alldata'), 1);
  $cc_auth = addAuth($cc_auth, 117, 'send sms', 'churchdb', null, t('send.sms'), 1);
  $cc_auth = addAuth($cc_auth, 112, 'export data', 'churchdb', null, t('export.data'), 1);
  
  $cc_auth = addAuth($cc_auth, 115, 'view group', 'churchdb', 'cdb_gruppe', t('view.group'), 0);
  $cc_auth = addAuth($cc_auth, 104, 'view group statistics', 'churchdb', null, 'view.group.statistics', 1);
  $cc_auth = addAuth($cc_auth, 114, 'administer groups', 'churchdb', null, t('administer.groups'), 1);
  
  $cc_auth = addAuth($cc_auth, 199, 'edit masterdata', 'churchdb', null, 'edit.masterdata', 1);
  
  return $cc_auth;
}

/**
 * TODO - rethink naming - looks like preferences - AdminModel???
 *
 * @return CTModuleForm
 */
function churchdb_getAdminForm() {
  global $config;
  
  $form = new CTModuleForm("churchdb");
  
  $form->addField("churchdb_maxexporter", "", "INPUT_REQUIRED", t('max.allowed.rows.to.export'))
    ->setValue($config["churchdb_maxexporter"]);
  
  $form->addField("churchdb_home_lat", "", "INPUT_REQUIRED", t('center.coordinates.latitude'))
    ->setValue($config["churchdb_home_lat"]);
  
  $form->addField("churchdb_home_lng", "", "INPUT_REQUIRED", t('center.coordinates.longitude'))
    ->setValue($config["churchdb_home_lng"]);
  
  $form->addField("churchdb_emailseparator", "", "INPUT_REQUIRED", t('email.default.separator'))
    ->setValue($config["churchdb_emailseparator"]);
  
  $form->addField("churchdb_groupnotchoosable", "", "INPUT_REQUIRED", t('days.to.show.terminated.groups'))
    ->setValue($config["churchdb_groupnotchoosable"]);
  
  $form->addField("churchdb_birthdaylist_status", "", "INPUT_REQUIRED", t('xxx.ids.for.birthdaylist.comma.separated',t('status')))
    ->setValue($config["churchdb_birthdaylist_status"]);
  $form->addField("churchdb_birthdaylist_station", "", "INPUT_REQUIRED", t('xxx.ids.for.birthdaylist.comma.separated',t('station')))
    ->setValue($config["churchdb_birthdaylist_station"]);
  
  $form->addField("churchdb_mailchimp_apikey", "", "INPUT_OPTIONAL", t('api.key.mailchimp.if.used') .
       ' <a target="_clean" href="http://intern.churchtools.de/?q=help&doc=MailChimp-Integration">' .  t('more.information') . '</a>')
    ->setValue($config["churchdb_mailchimp_apikey"]);
  $form->addField("churchdb_smspromote_apikey", "", "INPUT_OPTIONAL", t('api.key.smspromote.if.used') .
       ' <a target="_clean" href="http://intern.churchtools.de/?q=help&doc=smspromote-Integration">' . t('more.information') . '</a>')
    ->setValue($config["churchdb_smspromote_apikey"]);
  
  $form->addField("churchdb_sendgroupmails", "", "CHECKBOX", t('send.groupchanges.to.leaders'))
    ->setValue($config["churchdb_sendgroupmails"]);
  
  if (!isset($config["churchdb_changeownaddress"])) $config["churchdb_changeownaddress"] = false;
  $form->addField("churchdb_changeownaddress", "", "CHECKBOX", t('user.is.allowed.to.change.own.address'))
    ->setValue($config["churchdb_changeownaddress"]);
  
  $form->addField("churchdb_archivedeletehistory", "", "CHECKBOX", t('delete.history.when.moving.to.archive'))
    ->setValue(getVar("churchdb_archivedeletehistory", false, $config));
  
  return $form;
}

/**
 *
 * @return string
 */
function churchdb_main() {
  global $user;
  // drupal_add_css(CHURCHCORE.'/churchcore_bootstrap.css');
  drupal_add_css(ASSETS . '/fileuploader/fileuploader.css');
  drupal_add_css(ASSETS . '/dynatree/ui.dynatree.css');
  
  drupal_add_js(ASSETS . '/flot/jquery.flot.min.js');
  drupal_add_js(ASSETS . '/flot/jquery.flot.pie.js');
  drupal_add_js(ASSETS . '/js/jquery.history.js');
  
  drupal_add_js(ASSETS . '/ui/jquery.ui.slider.min.js');
  
  drupal_add_js(ASSETS . '/fileuploader/fileuploader.js');
  
  drupal_add_js(ASSETS . '/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS . '/ckeditor/lang/de.js');
  
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchdb"));
  
  drupal_add_js(CHURCHCORE . '/cc_abstractview.js');
  drupal_add_js(CHURCHCORE . '/cc_standardview.js');
  drupal_add_js(CHURCHCORE . '/cc_maintainstandardview.js');
  drupal_add_js(CHURCHDB . '/cdb_cdbstandardview.js');
  drupal_add_js(CHURCHDB . '/cdb_geocode.js');
  drupal_add_js(CHURCHDB . '/cdb_loadandmap.js');
  drupal_add_js(CHURCHDB . '/cdb_settingsview.js');
  drupal_add_js(CHURCHDB . '/cdb_importview.js');
  drupal_add_js(CHURCHDB . '/cdb_personview.js');
  drupal_add_js(CHURCHDB . '/cdb_archiveview.js');
  drupal_add_js(CHURCHDB . '/cdb_groupview.js');
  drupal_add_js(CHURCHDB . '/cdb_statisticview.js');
  drupal_add_js(CHURCHDB . '/cdb_mapview.js');
  drupal_add_js(CHURCHDB . '/cdb_maintainview.js');
  drupal_add_js(CHURCHDB . '/cdb_main.js');
  
  // API v3
  $content = '<script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=true"></script>';
  
  // id for direct access of a person
  if ($id = getVar("id")) $content .= "<input type='hidden' id='filter_id' value='$id'/>";
  
  // TODO: put in function - appears in several places
  $content .= '
<div class="row-fluid">
  <div class="span3">
    <div id="cdb_menu"></div>
    <div id="cdb_todos"></div>
    <div id="cdb_filter"></div>
  </div>
  <div class="span9">
    <div id="cdb_info"></div>
    <div id="cdb_search"></div>
    <div id="cdb_precontent"></div>
    <div id="cdb_group"></div>
    <div id="cdb_content"></div>
  </div>
</div>';
  
  return $content;
}

/**
 * get external group data
 *
 * @return array with group objects
 */
function getExternalGroupData() {
  global $user;
  $res = db_query("SELECT id, bezeichnung, treffzeit, zielgruppe, max_teilnehmer,
                   geolat, geolng, treffname, versteckt_yn, valid_yn, distrikt_id, offen_yn, oeffentlich_yn
                   FROM {cdb_gruppe}
                   WHERE oeffentlich_yn=1 AND versteckt_yn=0 AND valid_yn=1");
  $arr = array ();
  foreach ($res as $g) {
    $db = db_query("SELECT status_no FROM {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
                    WHERE gp.id=gpg.gemeindeperson_id AND gpg.gruppe_id=:gruppe_id AND gp.person_id=:person_id",
                    array (":gruppe_id" => $g->id,
                           ":person_id" => $user->id,
                    ))->fetch();
    if ($db) $g->status_no = $db->status_no;
    $arr[$g->id] = $g;
  }
  return $arr;
}

/**
 * send confirmation email
 *
 * TODO: use email template (customisable!) from file, f.e.:
 * extract($vars);
 * eval ('$content = "$message"');
 *
 * @param string $mail
 * @param string $vorname
 * @param int $g_id
 */
function sendConfirmationMail($mail, $vorname = "", $g_id) {
  $g = db_query("SELECT * FROM {cdb_gruppe}
                 WHERE id=:id",
                 array (":id" => $g_id))
                 ->fetch();
  if ($g) {
    // TODO: use mail template
    $content = "<h3>" . t("hello.name") . "</h3><p>";
    $content .= "Dein Antrag f&uuml;r die Gruppe <i>$g->bezeichnung</i> ist eingegangen. <p>Vielen Dank!";
    $res = churchcore_mail(getConf('site_mail'), $mail, "[" . getConf('site_name') . "] Teilnahmeantrag zur Gruppe " .
         $g->bezeichnung, $content, true, true, 2);
  }
}

/**
 * view external map
 *
 * TODO: maybe support use of openStreetMap too?
 *
 * @return string
 */
function externmapview_main() {
  global $user;
  drupal_add_css(ASSETS . '/fileuploader/fileuploader.css');
  drupal_add_js(CHURCHCORE . '/shortcut.js');
  drupal_add_css(ASSETS . '/ui/jquery-ui-1.8.18.custom.css');
  
  drupal_add_js(ASSETS . '/js/jquery.history.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.core.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.position.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.widget.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.autocomplete.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.dialog.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.mouse.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.draggable.min.js');
  drupal_add_js(ASSETS . '/ui/jquery.ui.resizable.min.js');
  
  drupal_add_js(ASSETS . '/fileuploader/fileuploader.js');
  
  drupal_add_js(ASSETS . '/ckeditor/ckeditor.js');
  drupal_add_js(ASSETS . '/ckeditor/lang/de.js');
  
  drupal_add_js(CHURCHCORE . '/churchcore.js');
  drupal_add_js(CHURCHCORE . '/churchforms.js');
  drupal_add_js(CHURCHCORE . '/cc_abstractview.js');
  drupal_add_js(CHURCHCORE . '/cc_standardview.js');
  drupal_add_js(CHURCHCORE . '/cc_maintainstandardview.js');
  drupal_add_js(CHURCHCORE . '/cc_interface.js');
  drupal_add_js(CHURCHDB . '/cdb_cdbstandardview.js');
  drupal_add_js(CHURCHDB . '/cdb_geocode.js');
  drupal_add_js(CHURCHDB . '/cdb_loadandmap.js');
  // drupal_add_js(CHURCHDB .'/cdb_mapview.js');
  drupal_add_js(CHURCHDB . '/cdb_externgroupview.js');
  drupal_add_js(createI18nFile("churchcore"));
  drupal_add_js(createI18nFile("churchdb"));
  
  // API v3
  $content = '<script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=true"></script>';
  
  // id for direct access of a person
  if ($id = getVar("id")) $content .= "<input type='hidden' id='g_id' value='$id'/>";

  
  $content .= NL . '<div id="cdb_content" style="width:100%;height:500px"></div>' . NL;
  
  return $content;
}

/**
 * view external map (ajax)
 */
function externmapview__ajax() {
  global $user;
  
  $func    = getVar("func");
  $groupId = getVar("g_id");
  $surname = getVar("Vorname");
  $name    = getVar("Name");
  $groupId = getVar("g_id");
  $email   = getVar("E-Mail-Adresse");
  $fon     = getVar("Telefon");
  $coment  = getVar("Kommentar");
  
  if ($func == 'loadMasterData') {
    $res["home_lat"] = getConf('churchdb_home_lat', '53.568537');
    $res["home_lng"] = getConf('churchdb_home_lng', '10.03656');
    $res["districts"] = churchcore_getTableData("cdb_distrikt", "bezeichnung");
    $res["groups"] = getExternalGroupData();
    $res["modulespath"] = CHURCHDB;
    $res["user_pid"] = $user->id;
    $res["vorname"] = $user->vorname;
    $res = jsend()->success($res);
  }
  else if ($func == 'addPersonGroupRelation') {
    include_once (CHURCHDB . '/churchdb_ajax.php');
    $res = churchdb_addPersonGroupRelation($user->id, $groupId, -2, null, null, null, t("request.by.external.mapview"));
    sendConfirmationMail($user->email, $user->vorname, $groupId);
    $res = jsend()->success($res);
  }
  else if ($func == 'editPersonGroupRelation') {
    include_once (CHURCHDB . '/churchdb_ajax.php');
    $res = _churchdb_editPersonGroupRelation($user->id, $groupId, -2, null, "null", t("request.changed.by.external.mapview"));
    sendConfirmationMail($user->email, $user->vorname, $groupId);
    $res = jsend()->success($res);
  }
  else if ($func == 'sendEMail') {
    $db = db_query('SELECT * FROM {cdb_person}
                    WHERE UPPER(email) LIKE UPPER(:email) AND UPPER(vorname) LIKE UPPER(:vorname) AND UPPER(name) LIKE UPPER(:name)',
                    array (':email' => $email,
                           ':vorname' => $surname,
                           ':name' => $name,
                    ))->fetch();
    $txt = "";
    if ($db) {
      include_once (CHURCHDB . '/churchdb_ajax.php');
      churchdb_addPersonGroupRelation($db->id, $groupId, -2, null, null, null, t("request.by.external.mapview") . ": $comment");
      sendConfirmationMail($email, $surname, $goupId);
      $txt = t("person.found.and.request.sent");
    }
    else {
      $res = db_query("SELECT vorname, p.id id, g.bezeichnung
                       FROM {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp, {cdb_person} p, {cdb_gruppe} g
                       WHERE gpg.gemeindeperson_id=gp.id AND gp.person_id=p.id AND g.id=:gruppe_id
                         AND gpg.gruppe_id=g.id AND status_no>=1 AND status_no!=4",
                       array (":gruppe_id" => $groupId));
      $rec = array ();
      foreach ($res as $p) {
        // TODO: use email template
        $rec[] = $p->vorname;
        $content = "<h4>" . t('request.to.group', $p->bezeichnung) . "<h4/>";
        $content .= "<ul><li>" . t('surname') . ": $surname";
        $content .= "<li>" . t('name') . ": $name";
        $content .= "<li>" . t('email') . ": $email";
        $content .= "<li>" . t('phone') . ": $fon";
        $content .= "<li>" . t('comment') . ": $comment";
        $content .= "</ul>";
        $res = churchcore_sendEMailToPersonIds($p->id, "[" . getConf('site_name') . "] " .
             t('form.request.to.group', $p->bezeichnung), $content, getConf('site_mail'), true, true);
      }
      if (!count($rec)) $txt = t("could.not.find.group.leader.please.try.other.ways");
      else {
        $txt = t("email.send.to", implode($rec, ", "));
        sendConfirmationMail($email, $surname, $groupId);
      }
    }
    $res = jsend()->success($txt);
  }
  else {
    $res = jsend()->fail(t("unknown.call", $func));
  }
  drupal_json_output($res);
}

/**
 * get html formatted content for birthdaylist
 *
 * @param string $desc; title for list?
 * @param int $diff_from; age?
 * @param int $diff_to; age?
 * @param bool $extended; show extended list?
 *
 * @return string; html
 */
function getBirthdaylistContent($desc, $diff_from, $diff_to, $extended = false) {
  global $base_url, $files_dir;
  
  $txt = "";
  $compact = getVar('compact');
  
  if ($extended && !user_access("view birthdaylist", "churchdb")) {
    die(t("no.permission.for", "view birthdaylist")); // TODO: use exception?
  }
  
  include_once ("churchdb_db.php");
  
  $see_details = (user_access("view", "churchdb")) && (user_access("view alldata", "churchdb"));
  
  $res = getBirthdayList($diff_from, $diff_to);
  if ($res) {
    if ($desc) $txt .= "<p><h4>$desc</h4>";
    if ($extended) {
      $txt .= "<table class='table table-condensed'><tr><th style='max-width:65px;'><th>" . t("name") .
           (!$compact ? "<th>" . t("age") : "") . "<th>" . t("birthday");
      // TODO: I would prefer to use closing tags
      // TODO: use template
      if ($see_details) $txt.= "<th>" . t("status") . "<th>" . t("station") . "<th>" . t("department");
    }
    foreach ($res as $arr) {
      // if ($extended)
      $txt .= "<tr><td>";
      // Add 1 to age, so we know the number of the next birthday :-)
      if ($diff_from > 0) $arr->age = $arr->age + 1;
      
      // link to access person on churchDB
      if ($extended) {
        if (!$arr->imageurl) $arr->imageurl = "nobody.gif";
        $txt .= "<img class='' width='42px' style='max-width:42px;' src='$base_url$files_dir/fotos/" . $arr->imageurl . "'/>";
        $txt .= "<td>";
        if ($see_details) $txt .= "<a data-person-id='$arr->person_id' href='$base_url?q=churchdb#PersonView/searchEntry:#" . $arr->person_id . "'>";
        $txt .= $arr->vorname . " ";
        if (!empty($arr->spitzname)) $txt .= "($arr->spitzname) ";
        $txt .= $arr->name . (!$compact ? "<td> ". $arr->age : ""). "<td>". (!$compact ? $arr->geburtsdatum_d : $arr->geburtsdatum_compact);
        
        if ($see_details) $txt .= " <td> ". $arr->status. "<td>". $arr->bezeichnung. "<td>". $arr->bereich;
      }
      else {
        if (!$arr->imageurl) $arr->imageurl = "nobody.gif";
        if ($see_details) $txt .= "<a data-person-id='$arr->person_id' href='$base_url?q=churchdb#PersonView/searchEntry:#" . $arr->person_id . "'>";
        $txt .= "<img class='' width='42px' style='max-width:42px;' src='$base_url$files_dir/fotos/" . $arr->imageurl . "'/>";
        if ($see_details) $txt .= "</a>";
        $txt .= "<td>";
        if ($see_details) $txt .= "<a class='tooltip-person' data-id='$arr->person_id' href='$base_url?q=churchdb#PersonView/searchEntry:#" . $arr->person_id . "'>";
        $txt .= $arr->vorname . " ";
        if (!empty($arr->spitzname)) $txt .= "($arr->spitzname) ";
        $txt .= $arr->name;
        if ($see_details) $txt .= "</a>";
        if ($see_details) $txt .= "<td>" . $arr->age . "";
      }
    }
    if ($extended) $txt .= "</table><p>&nbsp;</p>";
  }
  
  return $txt;
}

/**
 * get list of online users (html)
 *
 * @return string; html user list or null
 */
function getWhoIsOnline() {
  global $user;
  if (!user_access("view whoisonline", "churchcore")) return null;
  $dt = new DateTime();
  $res = db_query("SELECT p.id, vorname, name, hostname, s.datum
                   FROM {cdb_person} p, {cc_session} s
                   WHERE s.person_id=p.id order by name, vorname");
  $txt = "";
  
  foreach ($res as $p) {
    $test = new DateTime($p->datum);
    $seconds = $dt->format('U') - $test->format('U');
    if ($seconds < 300) $txt .= "<li>" . $p->vorname . " " . $p->name;
  }
  if ($txt) $txt = "<ul>$txt</ul>";
  
  return $txt;
}

/**
 * do several things??? with groups and memberships
 *
 * TODO: rename, maybe to groupMembership?
 *
 * @return string; html form
 */
function subscribeGroup() {
  global $user;
  include_once (CHURCHDB . '/churchdb_db.php');
  
  $sql_gruppenteilnahme = "SELECT g.bezeichnung, gpg.*
                           FROM {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp, {cdb_gruppe} g
                           WHERE gpg.gemeindeperson_id=gp.id AND gp.person_id=:person_id AND gpg.gruppe_id=g.id AND g.id=:g_id";
  
  $sGroup = getVar("subscribegroup");
  if ($sGroup > 0) {
    
    $res = db_query("SELECT * FROM {cdb_gruppe}
                     WHERE id=:id AND offen_yn=1",
                     array (":id" => $sGroup))
                     ->fetch();
    
    if (!$res) addErrorMessage(t("error.requesting.group.membership"));
    else {
      include_once (CHURCHDB . '/churchdb_ajax.php');
      
      $grp = db_query($sql_gruppenteilnahme,
                      array (":person_id" => $user->id, ":g_id" => $sGroup))
                      ->fetch();
      
      if (!$grp) churchdb_addPersonGroupRelation($user->id, $res->id, -2, null, null, null, t("request.by.form"));
      else _churchdb_editPersonGroupRelation($user->id, $res->id, -2, null, "null", t("request.quit.membership.by.form"));
      addInfoMessage(t("membership.requested.by.form.leader.will.be.informed", "<i>$res->bezeichnung</i>"));
    }
  }
  $sGroup = getVar("unsubscribegroup");
  if ($sGroup > 0) {
    $res = db_query($sql_gruppenteilnahme,
                    array (":person_id" => $user->id, ":g_id" => $sGroup))
                    ->fetch();
    if (!$res) addErrorMessage(t("error.quitting.membership"));
    else {
      include_once (CHURCHDB . '/churchdb_ajax.php');
      _churchdb_editPersonGroupRelation($user->id, $res->gruppe_id, -1, null, "null", t("request.quit.membership.by.form"));
      addInfoMessage(t("membership.marked.for.deleting", "<i>$res->bezeichnung</i>"));
    }
  }
  
  // get groups the user is member of or requested membership
  $res = db_query("SELECT gpg.gruppe_id, status_no
                   FROM {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
                   WHERE gpg.gemeindeperson_id=gp.id AND gp.person_id=$user->id");
  $mygroups = array ();
  foreach ($res as $p) $mygroups[$p->gruppe_id] = $p;
  
  // get all open groups
  $res = db_query("SELECT * FROM {cdb_gruppe} p
                   WHERE offen_yn=1 AND ((abschlussdatum IS NULL) OR (DATE_ADD( abschlussdatum, INTERVAL 1 DAY ) > NOW( )))");
  $txt = "";
  $txt_subscribe = "";
  $txt_unsubscribe = "";
  foreach ($res as $g) {
    // groups user is not member of
    if (!isset($mygroups[$g->id]) || $mygroups[$g->id]->status_no == -1) {
      if ($g->max_teilnehmer == null || churchdb_countMembersInGroup($g->id) < $g->max_teilnehmer) {
        $txt_subscribe .= "<option value='$g->id'>$g->bezeichnung";
        if ($g->max_teilnehmer) $txt_subscribe .= " (max. $g->max_teilnehmer)";
      }
    }
    // groups user is member of
    else if ($mygroups[$g->id]->status_no <= 0) {
      $txt_unsubscribe .= "<option value='$g->id'>$g->bezeichnung";
      if ($mygroups[$g->id]->status_no == -2) $txt_unsubscribe .= " [beantragt]";
    }
  }
  if ($txt_subscribe || $txt_unsubscribe) {
    $txt = '<form method="GET" action="?q=home">';
    if ($txt_subscribe) $txt .= '<p>' . t("apply.for.group.membership") . ':<p><select name="subscribegroup"><option>' .
         $txt_subscribe . '</select>';
    if ($txt_unsubscribe) $txt .= '<p>' . t("quit.group.membership") . ':<p><select name="unsubscribegroup"><option>' .
         $txt_unsubscribe . '</select>';
    $txt .= '<P><button class="btn" type="submit" name="btn">' . t("send") . '</button>';
    $txt .= '</form>';
  }
  
  return $txt;
}

/**
 *
 * @return string html content
 */
function churchdb_getBlockBirthdays() {
  $txt = "";
  if (user_access("view birthdaylist", "churchdb")) {
    $t2 = getBirthdaylistContent("", -1, -1);
    if ($t2) $txt .= '<tr><th colspan="3">' . t("yesterday") . $t2;
    $t2 = getBirthdaylistContent("", 0, 0);
    if ($t2) $txt .= '<tr><th colspan="3">' . t("today") . $t2;
    $t2 = getBirthdaylistContent("", 1, 1);
    if ($t2) $txt .= '<tr><th colspan="3">' . t("tomorrow") . $t2;
    
    if ($txt) $txt = "<table class='table table-condensed'>" . $txt . "</table>";
    if ((user_access("view", "churchdb")) && (user_access("view birthdaylist", "churchdb"))) {
      $txt .= "<p style='line-height:100%' align='right'><a href='?q=churchdb/birthdaylist'>" .  t("more.birthdays") . "</a>";
    }
  }
  if (user_access("view memberliste", "churchdb")) {
    $txt .= "<p style='line-height:100%' align='right'><a href='?q=home/memberlist'>" . t("list.of.members") . "</a>";
  }
  
  return $txt;
}

/**
 * TODO: churchdb_getTodos is not tested (where?)
 * explain, when gpg.status_no < -1 / replace status_nos by speaking constants
 * @return string
 */
function churchdb_getTodos() {
  global $user;
  $mygroups = churchdb_getMyGroups($user->id, true, true, false);
  $mysupergroups = churchdb_getMyGroups($user->id, true, true, true);
  if (!$mygroups) return "";
  if (!$mysupergroups) $mysupergroups = array (-1);
  
  $groups = db_query("
      SELECT p.id, p.vorname, p.name, g.bezeichnung, gpg.status_no, s.bezeichnung AS status
      FROM {cdb_person} p, {cdb_gruppe} g, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppenteilnehmerstatus} s
      WHERE s.intern_code=gpg.status_no AND gpg.gemeindeperson_id=gp.id AND gp.person_id=p.id AND gpg.gruppe_id=g.id
        AND ((gpg.gruppe_id IN (" . db_implode($mygroups) . ") AND gpg.status_no<-1)
          OR (gpg.gruppe_id IN (" . db_implode($mysupergroups) . ") AND gpg.status_no=-1))
      ORDER BY status");
  
  if (!$groups) return "";
  
  $arr = array();
  foreach ($groups as $g) {
    if (!isset($arr[$g->status_no])) $arr[$g->status_no] = (object) array();
    if (!isset($arr[$g->status_no]->content)) $arr[$g->status_no]->content = array();
    // TODO is this the same as the 2 lines above?
//     if (!isset($arr[$g->status_no])) $arr[$g->status_no] = (object) array ('content' => array());
    $arr[$g->status_no]->content[] = $g;
    $arr[$g->status_no]->status_no = $g->status_no;
    $arr[$g->status_no]->status    = $g->status;
  }
  $txt = "";
  $entries = "";
  $status = "";
  $count = 0;
  foreach ($arr as $status) {
    $txt .= "<li><p>$status->status &nbsp;<label class='pull-right badge badge-" .
         ($status->status_no == -1 ? "important" : "info") . "'>" . count($status->content) . "</label>";
    foreach ($status->content as $g) {
      $txt .= "<br/><small><a href='?q=churchdb#PersonView/searchEntry:#$g->id'>$g->vorname $g->name</a> - $g->bezeichnung</small>";
    }
  }
  if ($txt != "") $txt = '<ul>' . $txt . '</ul>';
  
  return $txt;
}

/**
 *
 * @return string html
 */
function churchdb_getForum() {
  return '<div id="cc_forum"></div>';
}

/**
 * TODO: not used?
 * @return NULL|string
 */
function churchdb_getBlockLookPerson() {
  if (!user_access("view birthdaylist", "churchdb") && !user_access("view", "churchdb")) return null;
  
  $txt = "moin";
  return $txt;
}

/**
 * get array with several content blocks for start page
 *
 * @return array; with metadata + html content
 */
function churchdb_blocks() {
  global $config;
  $return = array (
    1 => array (
        "label" => t("birthdays"),
        "col" => 1,
        "sortkey" => 1,
        "html" => churchdb_getBlockBirthdays(),
        "help" => '',
        "class" => '',
    ),
    2 => array (
        "label" => t("who.is.online"),
        "col" => 1,
        "sortkey" => 3,
        "html" => getWhoIsOnline(),
        "help" => '',
        "class" => '',
    ),
    3 => array (
        "label" => t("manage.my.membership"),
        "col" => 1,
        "sortkey" => 2,
        "html" => subscribeGroup(),
        "help" => '',
        "class" => '',
    ),
    4 => array (
        "label" => t("todos.in", $config["churchdb_name"]),
        "col" => 2,
        "sortkey" => 1,
        "html" => churchdb_getTodos(),
        "help" => '',
        "class" => '',
    ),
    5 => array (
        "label" => "ChurchMailer",
        "col" => 1,
        "sortkey" => 2,
        "html" => churchdb_getForum(),
        "help" => '',
        "class" => '',
    ));
  
  return $return;
}

/**
 * get birthdaylist
 *
 * @return string; html
 */
function churchdb__birthdaylist() {
  $txt = "<ul>"
            . getBirthdaylistContent(t("last.x.days", 7), -7, -1, true)
            . getBirthdaylistContent(t("today"), 0, 0, true)
            . getBirthdaylistContent(t("next.x.days", 30), 1, 30, true) .
         "</ul>";
  if (user_access("view memberliste", "churchdb")) {
    $txt .= "<p style='line-height:100%' align='right'><a href='?q=home/memberlist'>" . t("list.of.members") . "</a>";
  }
  
  return $txt;
}

/**
 * get VCard (send header and echo result)
 */
function churchdb__vcard() {
  $id = getVar("id");
  drupal_add_http_header('Content-type', 'text/x-vCard; charset=ISO-8859-1; encoding=ISO-8859-1', true);
  drupal_add_http_header('Content-Disposition', 'attachment; filename="vcard' . $id . '.vcf"', true);
  include_once ("churchdb_db.php");
  
  $sql = "
    SELECT  concat(
      'BEGIN:VCARD\n','VERSION:3.0\n',
      'N:',name,';',vorname,'\n',
      'NICKNAME:',spitzname,'\n',
      'EMAIL;TYPE=INTERNET:',email,'\n',
      'TEL;type=HOME;type=VOICE:',telefonprivat,'\n',
      'TEL;type=WORK;type=VOICE:',telefongeschaeftlich,'\n',
      'TEL;type=CELL;type=VOICE;type=pref:',telefonhandy,'\n',";
  
  if (user_access("view alldetails", "churchdb")) {
    $sql .= "
      'ADR;TYPE=HOME;type=pref:;',zusatz,';',strasse,';',ort,';;',plz,';',land,'\n',
      if(geburtsdatum is null,'',concat('BDAY:',geburtsdatum,'\n')),";
  }
  $sql .= "
      'END:VCARD'
    ) vcard
    FROM {cdb_person} p, {cdb_gemeindeperson} gp
    WHERE gp.person_id=p.id and p.id = :id";
  
  $person = db_query($sql, array (":id" => $id))->fetch();
  echo $person->vcard;
}

/**
 * optimize person data for export
 *
 * @param unknown $arr
 * @return array <number, string>
 */
function _export_optimzations($arr) {
  if (isset($arr["geburtsdatum"])) {
    $dt = new DateTime($arr["geburtsdatum"]);
    $arr['Geb.-Tag'] = $dt->format("d");
    $arr['Geb.-Monat'] = $dt->format("m");
    $arr['Geb.-Jahr'] = $dt->format("Y");
    
    if ($arr['Geb.-Jahr'] >= 7000) {
      $arr['Geb.-Tag'] = "";
      $arr['Geb.-Monat'] = "";
      $arr['Geb.-Jahr'] = $arr['Geb.-Jahr'] - 7000;
    }
    else if ($arr['Geb.-Jahr'] == 1004) {
      $arr['Geb.-Jahr'] = "";
    }
    unset($arr["geburtsdatum"]);
  }
  return $arr;
}

/**
 *
 * @param string $templatename
 * @throws Exception
 * @return NULL
 */
function _getExportTemplateByName($templatename = null) {
  global $user;
  
  if (!$templatename) return null;
  
  $settings = churchcore_getUserSettings("churchdb", $user->id);
  if (!isset($settings["exportTemplate"][$templatename])) throw new Exception(t('template.x.not.found', $templatename));
  
  return $settings["exportTemplate"][$templatename];
}

/**
 * prepare person data for export
 *
 * @param string $ids; null for all or comma separated list
 * @param string $template; when null, export everything that is possible
 * @throws Exception
 *
 * @return array
 */
function _getPersonDataForExport($person_ids = null, $template = null) {
  global $user;
  
  $ids = null;
  if ($person_ids != null) $ids = explode(",", $person_ids);
  
  // Check allowed persons
  $ps = churchdb_getAllowedPersonData();
  $department = churchcore_getTableData("cdb_bereich");
  $status     = churchcore_getTableData("cdb_status");
  $station    = churchcore_getTableData("cdb_station");
  
  $export = array ();
  foreach ($ps as $p) {
    if ($ids == null || in_array($p->p_id, $ids)) {
      $detail = churchdb_getPersonDetails($p->p_id, false);
      $detail->bereich = "";
      $departments = array ();
      foreach ($p->access as $depId) {
        $departments[] = $department[$depId]->bezeichnung;
      }
      $detail->bereich_id = implode('::', $departments);
      if (isset($detail->station_id))
        $detail->station_id = $station[$detail->station_id]->bezeichnung;
      if (user_access("view alldetails", "churchdb")) $detail->status_id = $status[$detail->status_id]->bezeichnung;
      else if ($status[$detail->status_id]->mitglied_yn == 1) $detail->status_id = "Mitglied";
      else $detail->status_id = "Kein Mitglied";
      
      if ($detail->geschlecht_no == 1) {
        $detail->Anrede1 = t('salutation.man.1');
        $detail->Anrede2 = t('salutation.man.2');
      }
      else if ($detail->geschlecht_no == 2) {
        $detail->Anrede1 = t('salutation.woman.1');
        $detail->Anrede2 = t('salutation.woman.2');
      }
      if (isset($detail->geburtsdatum)) $detail->age = churchcore_getAge($detail->geburtsdatum);
      
      // If template was selected
      if ($template) {
        $export_entry = array ();
        foreach ($template as $key => $field) {
          if (strpos($key, "f_") === 0) {
            $key = substr($key, 2, 99);
            if (isset($detail->$key)) $export_entry[$key] = $detail->$key;
          }
        }
      }
      // Otherwise export everything beside some intern infos
      else {
        $export_entry = (array) $detail;
        if (!user_access("administer persons", "churchcore")) {
          unset($export_entry["letzteaenderung"]);
          unset($export_entry["aenderunguser"]);
          unset($export_entry["einladung"]);
          unset($export_entry["active_yn"]);
          unset($export_entry["lastlogin"]);
          unset($export_entry["createdate"]);
          unset($export_entry["lat"]);
          unset($export_entry["lng"]);
          unset($export_entry["gp_id"]);
          unset($export_entry["imageurl"]);
        }
        // Unset auth, it is not exportable
        unset($export_entry["auth"]);
      }
      $export[$p->p_id] = _export_optimzations($export_entry);
    }
  }
  return $export;
}

/**
 *
 * @param array $export
 * @param string $template
 * @return unknown|string
 */
function _addGroupRelationDataForExport($export, $template = null) {
  if (!$template) return $export;
  
  $groupTypes = churchcore_getTableData("cdb_gruppentyp");
  $groupTnStatus = array ();
  foreach (churchcore_getTableData("cdb_gruppenteilnehmerstatus") as $st) {
    $groupTnStatus[$st->intern_code] = $st;
  }
  foreach ($export as $eKey => $eRow) {
    foreach ($template as $tKey => $tRow) {
      // Look if grouptype is in template
      if (substr($tKey, 0, 15) == "f_grouptype_id_") {
        // Get group type and collect data
        $id = substr($tKey, 15, 99);
        $groups = churchdb_getGroupsForPersonId($eKey, $id);
        $grp_txt = array ();
        foreach ($groups as $group) {
          $txt = $group->bezeichnung;
          if ($group->status_no != 0 && isset($groupTnStatus[$group->status_no])) {
            $txt .= " (" . $groupTnStatus[$group->status_no]->bezeichnung . ")";
          }
          $grp_txt[] = $txt;
        }
        $export[$eKey][$groupTypes[$id]->bezeichnung] = implode("::", $grp_txt);
      }
    }
  }
  return $export;
}

/**
 * export data (send header and echo result)
 */
function churchdb__export() {
  drupal_add_http_header('Content-type', 'application/csv; charset=ISO-8859-1; encoding=ISO-8859-1', true);
  drupal_add_http_header('Content-Disposition', 'attachment; filename="churchdb_export.csv"', true);
  include_once ("churchdb_db.php");
  
  $ids = getVar("ids", null);
  $relPart = getVar("rel_part", null);
  $relId = getVar("rel_id", null);
  $template = _getExportTemplateByName(getVar("template", null));
  
  $export = _getPersonDataForExport($ids, $template);
  
  $export = _addGroupRelationDataForExport($export, $template);
  
  // if filtered by relations, load and export linked persons too
  foreach ($export as $key => $entry) {
    if ($relPart && $relId) {
      $id = null;
      if ($relPart == "k") {
        $rel = db_query("SELECT * FROM {cdb_beziehung}
                        WHERE beziehungstyp_id=:relId AND vater_id=:key",
                        array(":relId"=>$relId, "key"=>$key))
                        ->fetch();
        if ($rel) $id = $rel->kind_id;
      }
      if (!$id) {
        $rel = db_query("SELECT * FROM {cdb_beziehung}
                        WHERE beziehungstyp_id=:relId AND kind_id=:key",
                        array(":relId"=>$relId, "key"=>$key))
                         ->fetch();
        $id = $rel->vater_id;
      }
      // if relation to additional person found
      if ($id && !isset($export[$id])) {
        $person = _getPersonDataForExport($id, $template);
        if ($person && isset($person[$id])) {
          foreach ($person[$id] as $relKey => $relValue) {
            $export[$key][$relPart . "_" . $relKey] = $relValue;
          }
        }
      }
    }
  }
  
  // check for relations and aggregate data sets, if parameter agg is specified
  $rels = getAllRelations();
  if ($rels != null) {
    $rel_types = getAllRelationTypes();
    foreach ($rels as $rel) {
      if (getVar("agg" . $rel->typ_id == "y") && isset($export[$rel->v_id]) && isset($export[$rel->k_id])) {
        // use the male as first person, if available
        if (!isset($export[$rel->v_id]["anrede2"]) || $export[$rel->v_id]["anrede2"] == t('salutation.man.2')) {
          $p1 = $rel->v_id;
          $p2 = $rel->k_id;
        }
        else {
          $p1 = $rel->k_id;
          $p2 = $rel->v_id;
        }
        // add second to the male
        $export[$p1]["anrede"] = $rel_types[$rel->typ_id]->export_title;
        if (isset($export[$p1]["anrede2"]) && (isset($export[$p2]["anrede2"]))) $export[$p1]["anrede2"] = $export[$p2]["anrede2"] .
             " " . $export[$p2]["vorname"] . ", " . $export[$p1]["anrede2"];
        $export[$p1]["vorname2"] = $export[$p2]["vorname"];
        if (isset($export[$p2]["email"])) $export[$p1]["email_beziehung"] = $export[$p2]["email"];
        // remove second from export data
        $export[$p2] = null;
      }
    }
  }
  
  // check if there is a group given to add information about to export data
  if ($groupId = getVar("groupid")) {
    foreach ($export as $k => $key) {
      if ($key != null) {
        $r = db_query("
           SELECT g.bezeichnung, s.bezeichnung status, DATE_FORMAT(gpg.letzteaenderung, '%d.%m.%Y') letzteaenderung, gpg.comment
           FROM {cdb_gruppe} g, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppenteilnehmerstatus} s
           WHERE gp.id=gpg.gemeindeperson_id and g.id=:gruppe_id and s.intern_code=status_no and gpg.gruppe_id=g.id and gp.person_id=:person_id",
           array (":gruppe_id" => $groupId,
                  ":person_id" => $k,
                 ))->fetch();
        if ($r) {
          $export[$k]["Gruppe"] = $r->bezeichnung;
          $export[$k]["Gruppe_Dabeiseit"] = $r->letzteaenderung;
          $export[$k]["Gruppen_Kommentar"] = $r->comment;
          $export[$k]["Gruppen_Status"] = $r->status;
        }
      }
    }
  }
  
  // Get all available columns
  $cols = array ();
  foreach ($export as $key => $row) if ($row) {
    foreach ($row as $a => $val){
      if (!is_object($val) && !is_array($val)) $cols[$a] = $a;
    }
  }
    
  // Add header
  $sql = "SELECT langtext from {cdb_feld}
          WHERE db_spalte=:db_spalte";
  foreach ($cols as $col) {
    $res = db_query($sql, array (":db_spalte" => $col))->fetch();
    if (!$res) {
      $txt = t($col);
      // TODO: test for actually used DB encoding?
      if (substr($txt, 0, 3) != "***") echo mb_convert_encoding('"' . $txt . '";', 'ISO-8859-1', 'UTF-8');
      else echo mb_convert_encoding('"' . $col . '";', 'ISO-8859-1', 'UTF-8');
    }
    else
      echo mb_convert_encoding('"' . $res->langtext . '";', 'ISO-8859-1', 'UTF-8');
  }
  echo "\n";
  
  // Sort data
  usort($export, "sort_export_func");
  
  // Add all data rows
  foreach ($export as $row) if ($row) {
    foreach ($cols as $col) {
      if (isset($row[$col])) echo mb_convert_encoding('"' . $row[$col] . '";', 'ISO-8859-1', 'UTF-8');
      else echo ";";
    }
    echo "\n";
  }
}

/**
 * for use in usort()
 * @param mixed $a
 * @param mixed $b
 * @return number
 */
function sort_export_func($a, $b) {
  $sort_a = "";
  $sort_b = "";

  if (isset($a["name"])) $sort_a .= $a["name"];
  if (isset($a["vorname"])) $sort_a .= $a["vorname"];
  if (isset($b["name"])) $sort_b .= $b["name"];
  if (isset($b["vorname"])) $sort_b .= $b["vorname"];
  if (isset($a["id"])) $sort_a .= $a["id"];
  if (isset($b["id"])) $sort_b .= $b["id"];

  if ($sort_a == $sort_b) {
    return 0;
  }
  return ($sort_a < $sort_b) ? -1 : 1;
}

/**
 * view mails
 *
 * @return string; html
 */
function churchdb__mailviewer() {
  global $user, $config;
  
  if (!user_access("view", "churchdb") || !$user->email) return t("no.permission.for", $config["churchdb_name"]);
  
  $limit = 200;
  if (getVar("showmore")) $limit = 1000;
  
  if (user_access("administer settings", "churchcore")) $filter = "1";
  else $filter = "modified_pid=$user->id AND sender!='" . $config["site_mail"] . "'";
  
  if ($id = getVar("id")) $filter .= " AND id=$id";
  
  $val = "";
  if ($f = getVar("filter")) {
    $filter .= " AND (subject LIKE '%{$f}%' " . " OR body LIKE '%{$f}%' OR receiver LIKE '%{$f}%')";
    $val = $f; //TODO: not used?
  }
  $txt = '<anchor id="log1"/><h2>' . t("archive.of.sent.messages") . '</h2>';
  $res = db_query("SELECT * FROM {cc_mail_queue}
                   WHERE $filter
                   ORDER BY modified_date DESC
                   LIMIT $limit");
  
  $txt .= '<form class="form-inline" action="">';
  $txt .= '<input type="hidden" name="q" value="churchdb/mailviewer"/>';
  if (!isset($_GET["id"])) $txt .= '<input name="filter" class="input-medium" type="text" value="' . $val .
       '"></input> <input type="submit" class="btn" value="' . t("filter") . '"/>';
  else $txt .= '<a href="?q=churchdb/mailviewer" class="btn">' . t("back") . '</a>';
  $txt .= '</form>';
  
  $txt .= '<table class="table table-condensed table-bordered">';
  $txt .= "<tr><th>" . t("status") . "<th>" . t("date") . "<th>" . t("receiver") . "<th>" . t("sender") .
              "<th>" . t("subject"). "<th>" . t("read") . NL;
  $counter = 0;
  if ($res) foreach ($res as $arr) {
    $txt .= "<tr><td>";
    if ($arr->send_date) {
      if ($arr->error == 0) $txt .= "<img title='$arr->send_date' style='max-width:20px;' src='" . CHURCHCORE . "/images/check-64.png'/>";
      else $txt .= "<img title='$arr->send_date' style='max-width:20px;' src='" . CHURCHCORE . "/images/delete_2.png'/>";
    }
    $txt .= "<td>$arr->modified_date<td>$arr->receiver<td>$arr->sender<td><a href='?q=churchdb/mailviewer&id=$arr->id'>$arr->subject</a>";
    $txt .= "<td>$arr->reading_count" . NL;
    $counter++;
  }
  if (getVar("iframe")) {
    echo $arr->body;
    return null;
  }
  else if ($id) {
    if ($arr->htmlmail_yn == 1) {
      $txt .= '<tr><td colspan=6><iframe width="100%" height="400px" frameborder="0" src="?q=churchdb/mailviewer&id='.$arr->id.'&iframe=true"></iframe>';
    }
    else $txt .= '<tr><td colspan=6>' . strtr($arr->body, array ("\n" => "<br>", " " => "&nbsp;"));
  }
  $txt .= NL . '</table>' . NL;
  if (!getVar("showmore") && $counter >= $limit) {
    $txt .= '<a href="?q=churchdb/mailviewer&showmore=true" class="btn">'. t('show.more.rows') . '</a> &nbsp; ' . NL;
  }
  
  return $txt;
}

/**
 * cron job
 */
function churchdb_cron() {
  global $config;
  include_once ("churchdb_db.php");
  
  createGroupMeetings();
  
  // delete tags
  
  // get tags used by churchservices
  $services = churchcore_getTableData('cs_service', '', 'cdb_tag_ids is not null');
  $tag = array ();
  if ($services) {
    foreach ($services as $service) {
      $arr = explode(',', $service->cdb_tag_ids);
      foreach ($arr as $a)  if (trim($a)) $tag[trim($a)] = true;
    }
  }
  $res = db_query("SELECT * FROM {cdb_tag} t LEFT JOIN {cdb_gemeindeperson_tag} gpt ON ( t.id = gpt.tag_id )
                   LEFT JOIN {cdb_gruppe_tag} gt ON ( t.id = gt.tag_id )
                   WHERE gpt.tag_id IS NULL AND gt.tag_id IS null");
  // delete unused tags
  foreach ($res as $id) if (!isset($tag[$id->id])) {
    // TODO this sort of query is for reusing prepared statements - but probably no important speed advantage to
    // change it :-)
    db_query("DELETE FROM {cdb_tag}
              WHERE id=:id",
              array (":id" => $id->id));
    cdb_log("CRON - Delete Tag Id: $id->id $id->bezeichnung, not used", 2);
  }
    
    // reset login error count for all persons
    // TODO: check time of last login try?
  db_query("UPDATE {cdb_person} SET loginerrorcount=0");
  
  // clean mail archive
  db_query("DELETE FROM {cc_mail_queue}
            WHERE (DATE_ADD( modified_date, INTERVAL 30  DAY ) < NOW( )) AND send_date is NOT NULL AND error=0");
  
  db_query("DELETE FROM {cc_mail_queue}
            WHERE (DATE_ADD( modified_date, INTERVAL 14  DAY ) < NOW( )) AND send_date IS NOT NULL AND modified_pid=-1 AND error=0");
  
  db_query("DELETE FROM {cc_mail_queue}
            WHERE (DATE_ADD( modified_date, INTERVAL 90  DAY ) < NOW( ))");
  
  // Synce MailChimp
  if (!empty($config["churchdb_mailchimp_apikey"])) {
    include_once (ASSETS . "/mailchimp-api-class/inc/MCAPI.class.php");
    $api = new MCAPI($config["churchdb_mailchimp_apikey"]);
    $list_id = null;
    $db = db_query("SELECT * FROM {cdb_gruppe_mailchimp}
                    ORDER BY mailchimp_list_id");
    
    foreach ($db as $lists) {
      $list_id = $lists->mailchimp_list_id;
      // get all subscribers not beeing in the group anymore
      $db_group = db_query("
        SELECT *
        FROM (SELECT * FROM {cdb_gruppe_mailchimp_person} m WHERE m.mailchimp_list_id='$list_id' AND gruppe_id=:g_id) AS m
              LEFT JOIN  (SELECT gpg.gruppe_id, gp.person_id FROM {cdb_gemeindeperson_gruppe} gpg, {cdb_gemeindeperson} gp
                WHERE gp.id=gpg.gemeindeperson_id) gp on (gp.gruppe_id=m.gruppe_id and gp.person_id=m.person_id)
        WHERE gp.person_id is null", array (":g_id" => $lists->gruppe_id));
      $batch = array ();
      foreach ($db_group as $p) {
        $batch[] = array ("EMAIL" => $p->email);
        db_query("DELETE FROM {cdb_gruppe_mailchimp_person}
                  WHERE (email=:email AND gruppe_id=:g_id AND mailchimp_list_id=:list_id)",
                  array (":email" => $p->email,
                         ":g_id" => $lists->gruppe_id,
                         ":list_id" => $list_id,
                  ));
      }
      listBatchUnsubscribe($api, $list_id, $batch, $lists->goodbye_yn == 1, $lists->notifyunsubscribe_yn == 1);
      
      // get persons not yet subscribed (not in table cdb_gruppe_mailchimp_personen)
      $db_groups = db_query("
        SELECT *
        FROM (SELECT p.id AS p_id, p.vorname, p.name, p.email AS p_email, gpg.gruppe_id AS g_id
              FROM {cdb_gemeindeperson} gp, {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg
              WHERE gp.person_id=p.id AND gpg.gemeindeperson_id=gp.id AND gpg.status_no>=0 AND p.email!=''
                AND gpg.gruppe_id=$lists->gruppe_id) AS t
              LEFT JOIN {cdb_gruppe_mailchimp_person} m
                ON (m.gruppe_id=t.g_id and m.person_id=t.p_id and m.mailchimp_list_id='$list_id')
        WHERE m.gruppe_id is null");
      $batch = array ();
      foreach ($db_groups as $p) {
        $batch[] = array (
            "EMAIL" => $p->p_email,
            "FNAME" => $p->vorname,
            "LNAME" => $p->name,
        );
        db_query("INSERT INTO {cdb_gruppe_mailchimp_person} (person_id, gruppe_id, mailchimp_list_id, email)
                  VALUES (:p_id, :g_id, :list_id, :email)",
                  array(":p_id" => $p->p_id,
                        ":g_id" => $p->g_id,
                        ":list_id" => $list_id,
                        ":email" => $p->p_email,
                  ));
      }
      listBatchSubscribe($api, $list_id, $batch, $lists->optin_yn == 1);
    }
  }
  
  // delete old mails
  db_query("DELETE FROM {cc_mail_queue}
            WHERE send_date IS NOT NULL AND DATEDIFF(send_date, NOW())<-60");
  
  // Do Statistics
  $db = db_query("SELECT MAX(date) AS max, CURDATE() AS now
                  FROM {crp_person}")
                  ->fetch();
  // TODO: add $db->max != $db->now to sql query?
  if ($db->max != $db->now) {
    db_query("INSERT INTO {crp_person} (
                SELECT CURDATE(), status_id, station_id,
                  SUM(CASE WHEN DATEDIFF(erstkontakt,'" . $db->max . "')>=0 THEN 1 ELSE 0 END),
                  COUNT(*)
                FROM {cdb_person} p, {cdb_gemeindeperson} gp
                WHERE p.id=gp.person_id group by status_id, station_id
              )");
    db_query("INSERT into {crp_group} (
                SELECT curdate(), gruppe_id, status_id, station_id, s.id gruppenteilnehmerstatus_id,
                    SUM(CASE WHEN DATEDIFF(gpg.letzteaenderung,'" . $db->max . "')>=0 THEN 1 ELSE 0 END),
                    COUNT(*)
                FROM {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppenteilnehmerstatus} s, {cdb_gemeindeperson} gp, {cdb_gruppe} g
                WHERE gpg.gemeindeperson_id=gp.id AND gpg.status_no=s.intern_code
                      AND gpg.gruppe_id=g.id AND (g.abschlussdatum IS NULL OR DATEDIFF(g.abschlussdatum, curdate())>-366)
                GROUP BY gruppe_id, status_id, station_id, gruppenteilnehmerstatus_id, s.id
               )");
    
    ct_log('ChurchDB Tagesstatistik wurde erstellt.', 2);
  }
}

/**
 * subscribe all persons in $batch to $list_id
 *
 * @param object $api
 * @param string $list_id
 * @param array $batch
 * @param bool $optin
 */
function listBatchSubscribe($api, $list_id, $batch, $optin = true) {
  if (count($batch) == 0) return;
  
  $update_existing = false; // yes, update currently subscribed users TODO: should be replaced by speaking constants
  $replace_interests = false; // no, add interest, don't replace
  $vals = $api->listBatchSubscribe($list_id, $batch, $optin, $update_existing, $replace_interests);
  include_once ("churchdb_db.php");
  if ($api->errorCode) cdb_log("CRON - Error on Subscribe to MailChimp: Code=" . $api->errorCode . " Msg=" .
       $api->errorMessage, 2);
  else cdb_log("CRON - MailChimp-Liste $list_id: Add " . count($batch) . " Persons.", 2);
}

/**
 * unsubscribe all persons in $batch from $list_id
 *
 * @param string $api
 * @param string $list_id
 * @param array $batch
 * @param bool $send_goodbye
 * @param bool $send_notify
 */
function listBatchUnsubscribe($api, $list_id, $batch, $send_goodbye = false, $send_notify = false) {
  if (count($batch) == 0) return;
  
  $delete_member = false; // flag to completely delete the member from your list instead of just unsubscribing,
                          // default to false
  $vals = $api->listBatchUnsubscribe($list_id, $batch, $delete_member, $send_goodbye, $send_notify);
  include_once ("churchdb_db.php");
  if ($api->errorCode) cdb_log("CRON - Error on Unsubscribe from MailChimp: Code=" . $api->errorCode . " Msg=" .
       $api->errorMessage, 2);
  else cdb_log("CRON - MailChimp-Liste $list_id: Remove " . count($batch) . " Persons.", 2);
}
