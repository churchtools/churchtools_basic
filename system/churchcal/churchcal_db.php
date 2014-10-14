<?php 

include_once(CHURCHCORE."/churchcore_db.php");
 
/**
 * TODO: i would rename category to calendar for it beeing different calendars in churchcal, not categories
 */
 
/**
 * meeting request
 * TODO: use lang dependent template for email
 * @param unknown $cal_id
 * @param unknown $params
 */
function churchcal_handleMeetingRequest($cal_id, $params) {
  global $base_url, $user;
  
  $i = new CTInterface();
  $i->setParam("cal_id");
  $i->setParam("person_id");
  $i->setParam("mailsend_date");
  $i->setParam("event_date");
  $dt = new DateTime();
  foreach ($params["meetingRequest"] as $id=>$param) {
    $param["mailsend_date"]=$dt->format('Y-m-d H:i:s');
    $param["person_id"]=$id;
    $param["event_date"]=$params["startdate"];
    $param["cal_id"]=$cal_id;
    
    $db=db_query('SELECT mr.*, c.modified_pid 
                  FROM {cc_meetingrequest} mr, {cc_cal} c 
                  WHERE c.id=mr.cal_id and mr.person_id=:person_id and mr.cal_id=:cal_id',
                  array(":person_id"=>$param["person_id"], ":cal_id"=>$param["cal_id"]))
                  ->fetch();
    
    if (!$db) {
      db_insert("cc_meetingrequest")
        ->fields($i->getDBInsertArrayFromParams($param))
        ->execute(false);
      
      $txt = "<h3>" . t('hello') . "[Spitzname]!</h3><p>";
      
      $txt .= "<P>Du wurdest auf ".getConf('site_name');
      $txt .= ' von <i>'.$user->vorname." ".$user->name."</i>";
      $txt .= " f&uuml;r einen Termin angefragt. ";
      
      // if person was not yet invited to churchtools send invitation
      $db=db_query("SELECT IF (password IS NULL AND loginstr IS NULL AND lastlogin IS NULL,1,0) as invite 
                    FROM {cdb_person}
                    WHERE id=:id", 
                    array(":id"=>$id))
                    ->fetch();
      if ($db) {
        if ($db->invite == 1) {
          include_once(CHURCHDB.'/churchdb_ajax.php');
          churchdb_invitePersonToSystem($id);
          $txt.="Da Du noch nicht keinen Zugriff auf das System hast, bekommst Du noch eine separate E-Mail, mit der Du Dich dann anmelden kannst!";
        }
      
        $txt.="<p>Zum Zu- oder Absagen bitte hier klicken:";      
        $loginstr=churchcore_createOnTimeLoginKey($id);      
        $txt.='<p><a href="'.$base_url.'?q=home&id='.$id.'&loginstr='.$loginstr.'" class="btn btn-primary">%sitename aufrufen</a>';      
        churchcore_sendEMailToPersonids($id, "[".getConf('site_name')."] " . t('new.meeting.request'), $txt, null, true);
      }
    }
    else {
/*      db_update("cc_meetingrequest")
        ->fields($i->getDBInsertArrayFromParams($param))
        ->condition("person_id", $param["person_id"], "=")
        ->condition("cal_id", $param["cal_id"], "=")
        ->execute(false);
      churchcore_sendEMailToPersonids($id, "[".getConf('site_name')."] Anpassung in einer Termin-Anfrage", "anpassung", null, true);*/
    }    
  }
}

/**
 * 
 * @param array $params
 */
function churchcal_updateMeetingRequest($params) {
  global $user;
  $i = new CTInterface();
  $i->setParam("cal_id");
  $i->setParam("person_id");
  $i->setParam("mailsend_date");
  $i->setParam("event_date");
  $i->setParam("zugesagt_yn", false);
  $i->setParam("response_date");
  
  $dt = new DateTime();

  if (!$params["zugesagt_yn"]) unset($params["zugesagt_yn"]);
  
  db_update("cc_meetingrequest")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
}

/**
 * 
 * @return 
 */
function churchcal_getMyMeetingRequest() {
  global $user; // why 2x event_date?
  $db=db_query("SELECT mr.*, mr.event_date, c.startdate, c.enddate, c.bezeichnung, 
                  CONCAT(p.vorname,' ',p.name) AS modified_name, p.id modified_pid 
                FROM {cc_meetingrequest} mr, {cc_cal} c, {cdb_person} p 
                WHERE mr.person_id=:person_id AND c.modified_pid=p.id 
                  AND DATEDIFF(mr.event_date, NOW())>0 AND mr.cal_id=c.id", 
                array(":person_id"=>$user->id));
  $res=array();
  foreach ($db as $d) $res[$d->id]=$d; //TESTEN!!
  
  return $res;
}


/**
 * create calendar event
 * 
 * @param array $params
 * @param string $source; controls cooperation between modules if event comes from another module
 * @throws CTNoPermission
 * @return int; id of created event
 */
function churchcal_createEvent($params, $source = null) {
  // if source is another module rights are already checked
  if (!$source && !churchcal_isAllowedToEditCategory($params["category_id"])) {
    throw new CTNoPermission(t('no.create.right.for.cal.id.x', $params["category_id"]), "churchcal");
  }
  $i = new CTInterface();
  $i->setParam("startdate");
  $i->setParam("enddate");
  $i->setParam("bezeichnung");
  $i->setParam("category_id");
  $i->setParam("repeat_id");
  $i->setParam("repeat_until", false);
  $i->setParam("repeat_frequence", false);
  $i->setParam("repeat_option_id", false);
  $i->setParam("intern_yn");
  $i->setParam("notizen");
  $i->setParam("link");
  $i->setParam("ort");
  $i->addModifiedParams();
  
  $newId = db_insert("cc_cal")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->execute(false);
  
  if (isset($params["exceptions"])) foreach ($params["exceptions"] as $exception) {
    $res = churchcal_addException(array (
        "cal_id" => $newId, 
        "except_date_start" => $exception["except_date_start"], 
        "except_date_end" => $exception["except_date_end"],
    ));
  }
  if (isset($params["additions"])) foreach ($params["additions"] as $addition) {
    $res = churchcal_addAddition(array (
        "cal_id" => $newId, 
        "add_date" => $addition["add_date"], 
        "with_repeat_yn" => $addition["with_repeat_yn"],
    ));
  }
  // meeting request
  if (isset($params["meetingRequest"])) churchcal_handleMeetingRequest($newId, $params);
  
  // inform other m6odules
  $modules = churchcore_getModulesSorted(false, false);
  if (in_array("churchresource", $modules) && ($source == null || $source != "churchresource")) {
    include_once (CHURCHRESOURCE . '/churchresource_db.php');
    $params["id"] = $newId;
    churchresource_updateResourcesFromChurchCal($params, "churchcal");
  }
  if (in_array("churchservice", $modules) && ($source == null || $source != "churchservice")) {
    include_once (CHURCHSERVICE . '/churchservice_db.php');
    $cs_params = array_merge(array(), $params); // TODO: array_merge???
    $cs_params["cal_id"] = $newId;
    $cs_params["id"] = null;
    
    churchservice_createEventFromChurchCal($cs_params, $source);
  }
  
  return $newId;
}

/**
 * 
 * @param int $categoryId
 * @return boolean
 */
function churchcal_isAllowedToEditCategory($categoryId) {
  if (!$categoryId) return false;
  
  $arr = churchcal_getAuthForAjax();
  if (!isset($arr["edit category"])) return false;
  if (isset($arr["edit category"][$categoryId])) return true;
  return false;
}

/**
 * Store all Exception and Addition changes for communication to other modules
 * @param array $params
 * @param string $sourc; controls cooperation between modules if event comes from another modulee
 */
function churchcal_updateEvent($params, $source = null) {
  $changes = array ();
  
  // if source is another module rights are already checked
  if ($source==null) {
    // can user edit current event category?
    if (!churchcal_isAllowedToEditCategory($params["category_id"])) return CTNoPermission("AllowedToEditCategory[" .
         $params["category_id"] . "]", "churchcal");
    $old_cal = db_query("SELECT category_id, startdate 
                         FROM {cc_cal} 
                         WHERE id=:id", 
                         array (":id" => $params["id"]))
                         ->fetch();
    // can user edit old event category?
    if (!churchcal_isAllowedToEditCategory($old_cal->category_id)) {
      return CTNoPermission("AllowedToEditCategory[" . $old_cal->category_id . "]", "churchcal");
    }
  }
  
  // it is only a move in calendar
  if (!isset($params["repeat_id"])) {
    $i = new CTInterface();
    $i->setParam("startdate", false);
    $i->setParam("enddate", false);
    $i->setParam("bezeichnung", false);
    $i->setParam("category_id", false);
    $f = $i->getDBInsertArrayFromParams($params);
    if (count($f)) db_update("cc_cal")
                    ->fields($f)
                    ->condition("id", $params["id"], "=")
                    ->execute();
  }
  else {
    db_query("
      UPDATE {cc_cal} SET startdate=:startdate, enddate=:enddate, bezeichnung=:bezeichnung, ort=:ort,
        notizen=:notizen, link=:link, category_id=:category_id, intern_yn=:intern_yn, category_id=:category_id, 
        repeat_id=:repeat_id, repeat_until=:repeat_until, repeat_frequence=:repeat_frequence,
        repeat_option_id=:repeat_option_id 
      WHERE id=:event_id", array(
        ":event_id"        => $params["id"],
        ":startdate"       => $params["startdate"],
        ":enddate"         => $params["enddate"],
        ":bezeichnung"     => $params["bezeichnung"],
        ":ort"             => $params["ort"],
        ":intern_yn"       => $params["intern_yn"],
        ":notizen"         => str_replace('\"', '"', $params["notizen"]),
        ":link"            => $params["link"],
        ":category_id"     => $params["category_id"],
        ":repeat_id"       => getVar("repeat_id", null, $params),
        ":repeat_until"    => getVar("repeat_until", null, $params),
        ":repeat_frequence"=> getVar("repeat_frequence", null, $params),
        ":repeat_option_id"=> getVar("repeat_option_id", null, $params),
      ));
    
    // get all exceptions
    $exc = churchcore_getTableData("cc_cal_except", null, "cal_id=" . $params["id"]);
    // look which are already in DB
    if (isset($params["exceptions"])) foreach ($params["exceptions"] as $exception) {
      if ($exception["id"] > 0) {
        $exc[$exception["id"]]->vorhanden = true;
      }
      else {
        $add_exc = array ("cal_id" => $params["id"], 
                          "except_date_start" => $exception["except_date_start"], 
                          "except_date_end" => $exception["except_date_end"],
        );
        churchcal_addException($add_exc);
        $changes["add_exception"][] = $add_exc;
      }
    }
    // delete removed exceptions from DB
    if ($exc) {
      foreach ($exc as $e) if (!isset($e->vorhanden)) {
        $del_exc = array ("id" => $e->id, 
                          "except_date_start" => $e->except_date_start, 
                          "except_date_end" => $e->except_date_end,
        );
        churchcal_delException($del_exc);
        $changes["del_exception"][] = $del_exc;
      }
    }
    
    // get all additions
    $add = churchcore_getTableData("cc_cal_add", null, "cal_id=" . $params["id"]);
    // look which are already in DB.
    if (isset($params["additions"])) foreach ($params["additions"] as $addition) {
      if ($addition["id"] > 0) $add[$addition["id"]]->vorhanden = true;
      else {
        $add_add = array ("cal_id" => $params["id"], 
                          "add_date" => $addition["add_date"], 
                          "with_repeat_yn" => $addition["with_repeat_yn"],
        );
        churchcal_addAddition($add_add);
        $changes["add_addition"][] = $add_add;
      }
    }
    // delete from DB which are deleted.
    if ($add) foreach ($add as $a) {
      if (!isset($a->vorhanden)) {
        $del_add = array ("id" => $a->id, "add_date" => $a->add_date);
        churchcal_delAddition($del_add);
        $changes["del_addition"][] = $del_add;
      }
    }
  }
  
  // meeting request
  if (isset($params["meetingRequest"])) churchcal_handleMeetingRequest($params["id"], $params);
  
  // inform other modules
  $modules = churchcore_getModulesSorted(false, false);
  if ((in_array("churchresource", $modules) && ($source == null || $source != "churchresource"))) {
    include_once (CHURCHRESOURCE . '/churchresource_db.php');
    if ($source == null) $source = "churchcal";
    $params["cal_id"] = $params["id"];
    churchresource_updateResourcesFromChurchCal($params, $source, $changes);
  }
  if ((in_array("churchservice", $modules) && ($source == null || $source != "churchservice"))) {
    include_once (CHURCHSERVICE . '/churchservice_db.php');
    $cs_params = array_merge(array (), $params); //TODO: why array_merge?
    $cs_params["cal_id"] = $params["id"];
    $cs_params["id"] = null;
    
    // FIXME: without the if there was an error on changing events (endtime). Is there somethin else wrong?  
    $cs_params["old_startdate"] = $old_cal->startdate; 
    if ($source == null) $source = "churchcal";
    
    churchservice_updateEventFromChurchCal($cs_params, $source);
  }
}

/**
 * get user auth
 * @return array auth
 */
function churchcal_getAuthForAjax() {
  global $user;
  
  $ret = array ();
  if ($user && isset($_SESSION["user"]->auth["churchcal"])) {
    $ret = $_SESSION["user"]->auth["churchcal"];
    
    // if user has edit right he also get view right
    if (isset($ret["edit category"])) {
      foreach ($ret["edit category"] as $key => $edit)      $ret["view category"][$key] = $edit;
    }
  }
  if (user_access("view", "churchservice"))                 $ret["view churchservice"] = true;
  if (user_access("view", "churchdb")) {
                                                            $ret["view churchdb"] = true;
    if (user_access("view alldata", "churchdb"))            $ret["view alldata"] = true;
  }
  if (user_access("view", "churchresource"))                $ret["view churchresource"] = true;
  if (user_access("create bookings", "churchresource"))     $ret["create bookings"] = true;
  if (user_access("administer bookings", "churchresource")) $ret["administer bookings"] = true;
  
  return $ret;
}

/**
 * TODO: remove private cals?
 * 
 * @param string $withPrivat
 * @param string $onlyIds
 * @return multitype:NULL Ambigous <object, boolean, db_accessor>
 */
function churchcal_getAllowedCategories($withPrivat = true, $onlyIds = false) {
  global $user;
  $withPrivat = false;
  include_once (CHURCHDB . "/churchdb_db.php");
  
  $db = db_query("SELECT * FROM {cc_calcategory}");
  
  $res = array();
  $auth = churchcal_getAuthForAjax();
  
  $privat_vorhanden = false;
  
  foreach ($db as $category) {
    if ($category->privat_yn == 1 && $category->modified_pid == $user->id) $privat_vorhanden = true;
    
    if (($category->privat_yn == 0) || ($withPrivat)) {
      // Zugriff, weil ich View-Rechte auf die Kategorie habe
      if ((isset($auth["view category"]) && isset($auth["view category"][$category->id]))
       || (isset($auth["edit category"]) && isset($auth["edit category"][$category->id]))) {
        $res[$category->id] = ($onlyIds) ? $category->id : $res[$category->id] = $category;
      }
    }
  }
  if (!$privat_vorhanden && $user->id > 0 && user_access("personal category", "churchcal")) {
    $dt = new datetime();
    $id = db_insert("cc_calcategory")
          ->fields(array ("bezeichnung" => $user->vorname . "s Kalender", "sortkey" => 0, 
                          "oeffentlich_yn" => 0, "privat_yn" => 1, "color" => "black", 
                          "modified_date" => $dt->format('Y-m-d H:i:s'), 
                          "modified_pid" => $user->id,
          ))->execute();
    // Add permission for author of event
    db_query("INSERT INTO {cc_domain_auth} (domain_type, domain_id, auth_id, daten_id)
              VALUES ('person', $user->id, 404, $id)");
    
    $_SESSION["user"]->auth = getUserAuthorization($_SESSION["user"]->id);
    churchcore_saveUserSetting("churchcal", $user->id, "filterMeineKalender", "[" . ($id + 100) . "]");
    
    return churchcal_getAllowedCategories($withPrivat, $onlyIds);
  }
  else return $res;
}

/**
 * 
 * @param unknown $params
 * @param string $withintern
 * @return multitype:|Ambigous <multitype:multitype: , NULL, object, boolean, db_accessor>
 */
function churchcal_getCalPerCategory($params, $withintern = null) {
  global $user;
  
  if ($withintern==null) {
    if ($user==null || $user->id==-1) $withintern=false;
    else $withintern=true;
  }
  
  $data = array ();
  
  
  $res = db_query("
      SELECT cal.*, CONCAT(p.vorname, ' ',p.name) AS modified_name, e.id AS event_id, e.startdate AS event_startdate, 
        e.created_by_template_id AS event_template_id, b.id AS booking_id, b.startdate AS booking_startdate, b.enddate AS booking_enddate, 
        b.resource_id AS booking_resource_id, b.status_id AS booking_status_id 
      FROM {cc_cal} cal
      LEFT JOIN {cs_event} e ON (cal.id=e.cc_cal_id) 
      LEFT JOIN {cr_booking} b ON (cal.id=b.cc_cal_id) 
      LEFT JOIN {cdb_person} p ON (cal.modified_pid=p.id)
      WHERE cal.category_id IN (". db_implode($params["category_ids"]).") ".(!$withintern ? " and intern_yn=0" : "")." 
      ORDER by category_id");
  
  $data = null;
  
  // collect bookings/events if more then one per calendar entry
  foreach ($res as $arr) {
    if (isset($data[$arr->id])) $elem = $data[$arr->id];
    else {
      $elem = $arr;
      $req = churchcore_getTableData("cc_meetingrequest", null, "cal_id=" . $arr->id);
      if ($req) {
        $elem->meetingRequest = array();
        foreach ($req as $r) $elem->meetingRequest[$r->person_id] = $r;
      }
    }
    if ($arr->booking_id) {
      $elem->bookings[$arr->booking_resource_id] = array (
          "id" => $arr->booking_id, 
          "minpre" => (strtotime($arr->startdate) - strtotime($arr->booking_startdate)) / 60, 
          "minpost" => (strtotime($arr->booking_enddate) - strtotime($arr->enddate)) / 60, 
          "resource_id" => $arr->booking_resource_id, 
          "status_id" => $arr->booking_status_id,
      );
    }
    if ($arr->event_id) {
      // Get additional Service text infos, like "Preaching with [Vorname]"
      $service_texts = array ();
      $es = db_query("
        SELECT es.name, s.id, es.cdb_person_id, s.cal_text_template from {cs_service} s, {cs_eventservice} es 
        WHERE es.event_id=:event_id AND es.service_id=s.id and es.valid_yn=1 and es.zugesagt_yn=1 
          AND s.cal_text_template IS NOT NULL AND s.cal_text_template!=''", 
        array (":event_id" => $arr->event_id));
      
      foreach ($es as $e) if ($e) {
        if (strpos($e->cal_text_template, "[") === false) {
          $txt = $e->cal_text_template;
        }
        if ($e->cdb_person_id) {
          include_once (CHURCHDB . "/churchdb_db.php");
          $p = db_query("SELECT * FROM {cdb_person} 
                         WHERE id=:id", 
                         array (":id" => $e->cdb_person_id))
                         ->fetch();
          if ($p) {
            $txt = churchcore_personalizeTemplate($e->cal_text_template, $p);
          }
        }
        if (!in_array($txt, $service_texts)) { //TODO: maybe use in_array() instead
          $service_texts[] = $txt;
        }
      }
      // Save event info
      $elem->events[$arr->event_id] = array (
          "id" => $arr->event_id, 
          "startdate" => $arr->event_startdate, 
          "service_texts" => $service_texts,
      );
    }
    $data[$arr->id] = $elem;
  }
  
  if ($data == null) return array();
  
  $exceptions = churchcore_getTableData("cc_cal_except");
  if ($exceptions) foreach ($exceptions as $e) {
    // there may be exceptions without event
    if (isset($data[$e->cal_id])) {
      if (!isset($data[$e->cal_id]->exceptions)) $data[$e->cal_id]->exceptions = array();
      $data[$e->cal_id]->exceptions[$e->id] = new stdClass();
      $data[$e->cal_id]->exceptions[$e->id]->id = $e->id;
      $data[$e->cal_id]->exceptions[$e->id]->except_date_start = $e->except_date_start;
      $data[$e->cal_id]->exceptions[$e->id]->except_date_end = $e->except_date_end;
    }    
  }
  $additions = churchcore_getTableData("cc_cal_add");
  if ($additions) foreach ($additions as $e) {
    // there may be additions without event
    if (isset($data[$e->cal_id])) {
      if (!isset($data[$e->cal_id]->additions)) $data[$e->cal_id]->additions = array();
      $data[$e->cal_id]->additions[$e->id] = new stdClass();
      $data[$e->cal_id]->additions[$e->id]->id = $e->id;
      $data[$e->cal_id]->additions[$e->id]->add_date = $e->add_date;
      $data[$e->cal_id]->additions[$e->id]->with_repeat_yn = $e->with_repeat_yn;
    }
  }
  
  $ret = array ();
  foreach ($params["category_ids"] as $cat) {
    $ret[$cat] = array ();
    foreach ($data as $d) {
      if ($d->category_id == $cat) $ret[$cat][$d->id] = $d;
    }
  }
  
  return $ret;
}
