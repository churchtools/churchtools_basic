<?php

include_once('./'. CHURCHSERVICE .'/../churchcore/churchcore_db.php');

/**
 * Checks if the agenda is a template
 * @param unknown $agenda_id
 * @throws CTException - When Agenda could not be found
 * @return boolean
 */
function churchservice_isAgendaTemplate($agenda_id) {
  $agenda = db_query("SELECT * FROM {cs_agenda}
                      WHERE id=:id",
                      array (":id" => $agenda_id))
                      ->fetch();
  if (!$agenda) throw new CTException(t('x.not.found', t('agenda'). " ($agenda_id)"));
  
  return $agenda->template_yn == 1;
}

/**
 * renamed to isUserInvolved and moved into class CTChurchServiceModule
 * Check, if I have a service in this event
 * @param unknown $event_id
 */
// function churchservice_amIInvolved($event_id) {
//   global $user;
//   $db=db_query("select * from {cs_eventservice} where event_id=:event_id and cdb_person_id=:p_id
//                and valid_yn=1", array(":event_id"=>$event_id, ":p_id"=>$user->id))->fetch();
//   return $db!=false;
// }

/**
 * copy event by cal id
 *
 * @param int $orig_cal_id
 * @param int $new_cal_id
 * @param DateTime $new_startdate
 * @param bool $allservices
 * @throws CTException
 */
function churchservice_copyEventByCalId($orig_cal_id, $new_cal_id, $new_startdate, $allservices = true) {
  $event=db_query('SELECT * FROM {cs_event}
                   WHERE cc_cal_id=:cal_id',
                   array(":cal_id"=>$orig_cal_id))
                   ->fetch();
  if ($event) {
    $new_id = db_insert("cs_event")
              ->fields(array("
                  cc_cal_id"=>$new_cal_id,
                  "startdate"=>$new_startdate,
                  "special"=>$event->special,
                  "admin"=>$event->admin,
              ))->execute(false);
    
    if ($allservices) {
      
      $services = db_query("SELECT * FROM {cs_eventservice}
                            WHERE event_id=$event->id and valid_yn=1");
      
      $fields = array ("event_id" => $new_id);
      foreach ($services as $s) {
        $fields["service_id"] = $s->service_id;
        $fields["counter"] = $s->counter;
        $fields["valid_yn"] = $s->valid_yn;
        $fields["zugesagt_yn"] = $s->zugesagt_yn;
        $fields["name"] = $s->name;
        $fields["cdb_person_id"] = $s->cdb_person_id;
        $fields["reason"] = $s->reason;
        // not set, so a new request will be send for the other date
        // $fields["mailsenddate"]=$s->mailsenddate;
        $fields["modified_date"] = $s->modified_date;
        $fields["modifieduser"] = $s->modifieduser;
        $fields["modified_pid"] = $s->modified_pid;
        
        db_insert("cs_eventservice")
          ->fields($fields)
          ->execute(false);
      }
    }
  }
  else {
    throw new CTException(t('event.could.not.be.copied', $config["churchservice_name"]). BR . t('not.found', t('event')) );
  }
}

/**
 * convert CT eventdate into object,
 * converts dates to DateTime object, set diff, repeat_until
 * convert exceptions and additions to object
 *
 * @param array $params
 * @return StdClass
 */
function _convertCTDateTimeToObjects($params) {
  $o = (object) $params;
  $o->startdate = new DateTime($o->startdate);
  $o->enddate = new DateTime($o->enddate);
  $o->diff = $o->enddate->format("U") - $o->startdate->format("U"); //TODO: use DateTime->diff()?
  if (empty($o->repeat_until)) $o->repeat_until = $o->enddate->format('Y-m-d H:i:s');
  
  // Convert Exceptions and Additions to Object
  if (isset($o->exceptions)) foreach ($o->exceptions as $key => $exc) {
     $o->exceptions[$key] = (object) $exc;
  }
  if (isset($o->additions) ) foreach ($o->additions as $key => $exc) {
     $o->additions[$key] = (object) $exc;
  }
  
  return $o;
}

/**
 *
 * @param array $params
 * @param string $source TODO: not used
 */
function churchservice_createEventFromChurchCal($params, $source = null) {
  $o = _convertCTDateTimeToObjects($params);
//  foreach (getAllDatesWithRepeats($o, -1000, +1000) as $d) { // TODO: why not with $o? Use constants for 1000
  foreach (getAllDatesWithRepeats(_convertCTDateTimeToObjects($params), -1000, +1000) as $d) {
    $params["startdate"] = $d->format('Y-m-d H:i:s');
    $enddate = clone $d;
    $enddate->modify("+$o->diff seconds");
    $params["enddate"] = $enddate->format('Y-m-d H:i:s');
    // shall it be copied?
    if (getVar("copychurchservice", false, $params) == "true") {
      churchservice_copyEventByCalId($params["orig_id"], $params["cal_id"], $params["startdate"], true);
    }
    // create new one
    else if (isset($params["eventTemplate"])) churchservice_saveEvent($params, "churchcal");
  }
}


/**
 * called by ChurchCal on changes in calendar events
 * it uses old_startdate to move all events, then checks if there are events to create or delete
 * from changes in repeats, exceptions or additions
 *
 * TODO: calculate times in DB rather then in php
 * UPDATE {cs_event} SET startdate=DATE_ADD(startdate,INTERVAL $diff SECOND) WHERE e.cc_cal_id=:cal_id
 *
 * @param array $params
 * @param string $source
 */
function churchservice_updateEventFromChurchCal($params, $source = null) {
  $diff = null;
  //TODO: when is this set?
  if (isset($params["old_startdate"])) {
    // move events to new startdate
    $startdate     = new DateTime($params["startdate"]);
    $old_startdate = new DateTime($params["old_startdate"]);
    $diff = $startdate->format("U") - $old_startdate->format("U");
    
    $db = db_query("SELECT id, startdate FROM {cs_event} e
                    WHERE e.cc_cal_id=:cal_id",
                    array (":cal_id" => $params["cal_id"]));
    
    foreach ($db as $e) {
      $sd = new DateTime($e->startdate);
      $sd->modify("+$diff seconds");
      
      db_update("cs_event")
        ->fields(array ("startdate" => $sd->format('Y-m-d H:i:s')))
        ->condition('id', $e->id, "=")
        ->execute();
    }
  }
  
  // without repeat_id, this is only a time shift, so we can end processing here.
  if (empty($params["repeat_id"])) return;
    
    // Collect events into array to collect the info which has to be created/deleted/updated
  $events = array ();
  // Get all mapped events from DB
  $db = db_query("SELECT id, startdate FROM {cs_event} e
                  WHERE e.cc_cal_id=:cal_id",
                  array (":cal_id" => $params["cal_id"]));
  
  foreach ($db as $e) {
    $sd = new DateTime($e->startdate);
    $events[$sd->format('Y-m-d')] = array("status" => "delete", "id" => $e->id);
  }
  $o = _convertCTDateTimeToObjects($params);
  foreach (getAllDatesWithRepeats($o, -1000, +1000) as $d) {
    $sd = $d->format('Y-m-d');
    // Event was already moved above through old_startdate
    if (isset($events[$sd])) $events[$sd]["status"] = "ok";
    else $events[$sd] = array ("status" => "create");
    $events[$sd]["startdate"] = $d->format('Y-m-d H:i:s');
  }
  $template = null;
  if (isset($params["eventTemplate"])) $template = $params["eventTemplate"];
  foreach ($events as $key => $do) {
    if ($do["status"] == "delete") {
      $params["id"] = $do["id"];
      $params["informDeleteEvent"] = 1;
      $params["deleteCalEntry"] = 0;
      churchservice_deleteEvent($params, $source);
    }
    else if ($do["status"] == "create" && $template != null) {
      $params["id"] = null;
      $params["startdate"] = $do["startdate"];
      $params["eventTemplate"] = $template;
      churchservice_saveEvent($params, $source);
    }
  }
}


/**
 * save new or update existing event
 * if eventTemplate is given in $params get data from there, else use services
 *
 * @param array $params
 * @param string $source
 * @throws CTException
 */
function churchservice_saveEvent($params, $source=null) {
  global $user;
  
  include_once (CHURCHCAL . '/churchcal_db.php');
  $cal_id = null;
  if ($source == "churchcal" && $params["id"] == null && isset($params["cal_id"])) {
    $cal_id = $params["cal_id"];
  }
  else {
    // get cc_cal_id, if event already exists
    if ($id = getVar("id")) {
      $cal_id = db_query("SELECT cc_cal_id FROM {cs_event}
                          WHERE id=:id",
                          array (":id" => $id))
                          ->fetch()->cc_cal_id;
    }
  }
  
  // update/insert cs_event
  $fields = array ();
  if (isset($params["startdate"])) $fields["startdate"] = $params["startdate"];
  if (isset($params["valid_yn"]))  $fields["valid_yn"]  = $params["valid_yn"];
  if ($source == null) {
    $fields["special"] = (isset($params["special"]) ? $params["special"] : "");
    $fields["admin"]   = (isset($params["admin"])   ? $params["admin"]   : "");
  }
  
  if (isset($params["eventTemplate"])) {
    $db = db_query('SELECT special, admin
                    FROM {cs_eventtemplate}
                    WHERE id=:id',
                    array (":id" => $params["eventTemplate"]))
                    ->fetch();
    if ($db) {
      if (empty($fields["special"])) $fields["special"] = $db->special;
      if (empty($fields["admin"]))   $fields["admin"]   = $db->admin;
      //FIXME: i hope � is not important :-)
//       if ((!isset($fields["special"])) || �($fields["special"] == "")) $fields["special"] = $db->special;
//       if ((!isset($fields["admin"]))   || �($fields["admin"]   == "")) $fields["admin"]   = $db->admin;
    }
  }
  if (isset($params["id"])) {
    $event_id = $params["id"];
    
    db_update("cs_event")
      ->fields($fields)
      ->condition('id', $params["id"], "=")
      ->execute();
    
    // inform other modules
    if ($source == null && $cal_id != null) {
      $cal_params = array_merge(array (), $params);
      $cal_params["event_id"] = $event_id;
      $cal_params["id"] = $cal_id;
      churchcal_updateEvent($cal_params, "churchservice");
    }
  }
  else {
    if ($source == null) {
      $params["repeat_id"] = 0;
      $params["intern_yn"] = 0;
      $params["notizen"] = "";
      $params["link"] = "";
      $params["ort"] = "";
      $cal_id = churchcal_createEvent($params, "churchservice");
    }
    $fields["cc_cal_id"] = $cal_id;
    if (isset($params["eventTemplate"])) $fields["created_by_template_id"] = $params["eventTemplate"];
    
    $event_id = db_insert("cs_event")
                  ->fields($fields)
                  ->execute();
  }
  
  if (!isset($params["eventTemplate"]) && isset($params["services"])) {
    // update/insert eventservices
    $rm_services = array ();
    $new_services = array ();
    
    $dt = new datetime();
    $fields = array (
        "event_id"      => $event_id,
        "valid_yn"      => 1,
        "modified_date" => $dt->format('Y-m-d H:i:s'),
        "modified_pid"  => $user->id,
    );
    foreach ($params["services"] as $key => $val) {
      $fields["service_id"] = $key;
      $fields["counter"] = null;
      if ($val == 1) {
        
        db_insert("cs_eventservice")
          ->fields($fields)
          ->execute();
      }
      else {
        $i = $val;
        while ($i > 0) {
          $fields["counter"] = $i--;
          
          db_insert("cs_eventservice")
            ->fields($fields)
            ->execute();
        }
      }
    }
  }
  // if template given
  else if (isset($params["eventTemplate"])) {
    if (isset($params["id"])) {
      print_r($params);
      throw new CTException(t('template.can.not.be.added.to.existing.service'));
    }
    
    $dt = new datetime();
    $fields = array (
        "event_id"      => $event_id,
        "valid_yn"      => 1,
        "modified_date" => $dt->format('Y-m-d H:i:s'),
        "modified_pid"  => $user->id,
    );
    $db = db_query("SELECT * FROM {cs_eventtemplate_service}
                    WHERE eventtemplate_id=:eventtemplate_id",
                    array (':eventtemplate_id' => $params["eventTemplate"]));
    foreach ($db as $d) {
      $fields["service_id"] = $d->service_id;
      if ($d->count == 1) {
        $fields["counter"] = null;
        db_insert("cs_eventservice")
          ->fields($fields)
          ->execute();
      }
      else {
        $i = $d->count;
        while ($i > 0) {
          $fields["counter"] = $i--;
          db_insert("cs_eventservice")
            ->fields($fields)
            ->execute();
        }
      }
    }
    // TODO: translate, strange text
    ct_log("[ChurchService] Lege Template an " . $params["eventTemplate"] . " fuer Event", 2, $event_id, "service");
    
  }
}

/**
 * get current services of user $id
 *
 * @param int $user_id
 * @return array services
 */
function churchservice_getUserCurrentServices($user_id) {
  $arr = db_query("
    SELECT cal.bezeichnung AS event, cal.ort, s.bezeichnung AS dienst, es.id AS eventservice_id,
      sg.bezeichnung AS servicegroup, DATE_FORMAT(es.modified_date, '%Y%m%dT%H%i00') AS modified_date,
      p.vorname, p.name, es.modified_pid, zugesagt_yn, e.startdate AS startdate, DATE_FORMAT(e.startdate, '%Y%m%dT%H%i00')
      AS datum_start, ADDDATE(e.startdate, INTERVAL TIMEDIFF(cal.enddate, cal.startdate) HOUR_SECOND) AS enddate,
      DATE_FORMAT(adddate(e.startdate, interval timediff(cal.enddate, cal.startdate) HOUR_SECOND), '%Y%m%dT%H%i00') AS datum_end
    FROM {cs_event} e, {cc_cal} cal, {cs_eventservice} es, {cs_service} s, {cs_servicegroup} sg, {cdb_person} p
    WHERE cal.id=e.cc_cal_id AND es.event_id=e.id AND es.service_id=s.id AND sg.id=s.servicegroup_id
      AND es.modified_pid=p.id AND es.valid_yn=1 AND e.startdate>current_date - INTERVAL 61 DAY AND es.cdb_person_id=:userid",
    array (":userid" => $user_id));
  
  $res = array ();
  foreach ($arr as $a) $res[$a->eventservice_id] = $a;

  return $res;
}


/**
 * send email
 *
 * TODO: add param bool prefix to add [sitename] here (and in similar functions) rather then on function calls?
 *
 * @param string $subject
 * @param string $message
 * @param string $to
 */
function churchservice_send_mail ($subject, $message, $to) {
  churchcore_systemmail($to, $subject, $message, true);
}

/**
 * Delete CS-Event, inform people about deleted event and delete calendar entry
 *
 * @param $params["id"] id of Event
 * @param $params["informDeleteEvent"] 1=inform people. Default=0
 * @param $params["deleteCalEntry"] 1=delete Calender entry. Default=0
 * @throws CTException if Event or Calender Entry could not be found
 * @throws CTNoPermission
 */
function churchservice_deleteEvent($params, $source = null) {
  global $user;
  
  if (!$source) {
    if (!user_access("edit events", "churchservice")) throw new CTNoPermission("edit events", "churchservice");
    ct_log("[ChurchService] ". t('remove.event'), 2, $params["id"], "service");
  }
  
  $db_event = db_query("SELECT e.*, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') AS date_de
                        FROM {cs_event} e
                        WHERE id=:event_id",
                        array (":event_id" => $params["id"]))
                        ->fetch();
  if (!$db_event) {
    if ($params["id"]) throw new CTException(t('x.not.found', t('event')));
    else return;
  }
  
  // Inform people about the deleted event
  if (getVar("informDeleteEvent", false, $params)) {
    $db_cal = db_query("SELECT * FROM {cc_cal}
                        WHERE id=:cal_id",
                        array (":cal_id" => $db_event->cc_cal_id))
                        ->fetch();
    if (!$db_cal) throw new CTException(t('x.not.found', t('event')));
    $db = db_query("SELECT p.vorname, p.name, IF(p.spitzname, p.spitzname, p.vorname) AS nickname, p.email FROM {cs_eventservice} es, {cdb_person} p
                    WHERE event_id = :event_id AND valid_yn = 1 AND p.id = es.cdb_person_id
                      AND es.cdb_person_id IS NOT NULL AND p.email != ''",
                    array (":event_id" => $params["id"]));
    foreach ($db as $p) {
      $subject = "[" . getConf('site_name') . "] " . t('cancelation.of.event.date', $db_cal->bezeichnung, $db_event->date_de);
      //TODO: use mail template
      $data = array(
        'person'     => $p,
        'eventTitle' => $db_cal->bezeichnung,
        'eventDate'  => $db_event->date_de
      );
      // Deine Dienstanfrage wurde entsprechend entfernt.'; //TODO: meine Anfrage oder der (angefragte) Dienst? (Text im Template)
      $content = getTemplateContent('email/eventDeleted', 'churchservice', $data);
      churchservice_send_mail($subject, $content, $p->email);
    }
  }
  
  if (getVar("deleteCalEntry", 1, $params) == 1) {
    
    db_query("DELETE FROM {cs_eventservice}
              WHERE event_id=:event_id",
              array (":event_id" => $params["id"]), false);
    
    db_query("DELETE FROM {cs_event}
              WHERE id=:event_id",
              array (":event_id" => $params["id"]), false);
    
    db_query("DELETE FROM {cc_cal}
              WHERE id=:id and repeat_id=0",
              array (":id" => $db_event->cc_cal_id));
  }
  else {
    db_query("UPDATE {cs_event} SET valid_yn=0
              WHERE id=:id",
              array (":id" => $params["id"]));
  }
}
