<?php

include_once('./'. CHURCHSERVICE .'/../churchcore/churchcore_db.php');

/**
 * Rebind an event to a new Cal Event
 * @param [type] $oldEventId
 * @param [type] $newEventId
 * @param [type] $splitDate
 * @param [type] $untilEnd_yn
*/
function churchservice_rebindServicesToNewEvent($oldEventId, $newEventId, $splitDate, $untilEnd_yn) {
  ct_log("Split CSEvent $oldEventId to $newEventId at " . $splitDate->format('Y-m-d H:i:s') . " until end: $untilEnd_yn", 2);
  db_update("cs_event")
  ->fields(array("cc_cal_id" => $newEventId))
  ->condition('cc_cal_id', $oldEventId, "=")
  ->condition('DATE(startdate)', $splitDate->format('Y-m-d'), ( $untilEnd_yn ? ">=" : "="))
  ->execute();
}

function churchservice_getEventChangeImpact($csparams) {
  $services = array();
  foreach ($csparams as $csparam) {
    if (isset($csparam["id"])) {
      $res = db_query("SELECT es.id, e.startdate, s.bezeichnung service, es.zugesagt_yn, es.name, es.cdb_person_id
                       FROM {cs_event} e, {cs_eventservice} es, {cs_service} s
                       WHERE e.id = :id AND es.service_id = s.id
                       AND es.event_id = e.id AND es.valid_yn = 1 AND cdb_person_id is not null",
          array(":id" => $csparam["id"]));
      $param_startdate = new DateTime($csparam["startdate"]);
      foreach ($res as $es) {
        $orig_startdate = new DateTime($es->startdate);
        if ((getVar("action", null, $csparam)!=null)
        || ($orig_startdate->getTimestamp()!=$param_startdate->getTimestamp())) {
          $services[] = array("date" => $es->startdate,
              "confirmed" => $es->zugesagt_yn==1,
              "name" => $es->name,
              "person_id" => $es->cdb_person_id,
              "service" => $es->service,
              // for debug reasons
              //"untilEnd" =>$untilEnd_yn, "checkDate" => $checkDate, "splitDate" =>$splitDate,
              "orig_startdate->getTimestamp()" => $orig_startdate->getTimestamp(),
              "param_startdate->getTimestamp()" => $param_startdate->getTimestamp()
          );
        }
      }
    }
  }
  return $services;
}

/**
 * Get all Active Servies in the CSEvents belonging to Cal eventId
 * @param $eventId ChurchCal EventId
 * @param DateTime $splitDate
 * @param String $untilEnd_yn
 * @return array services with array with informations or empty array
 */
function churchservice_getActiveServicesInEvent($eventId, $splitDate, $untilEnd_yn) {
  $res = db_query("SELECT es.id, e.startdate, s.bezeichnung service, es.zugesagt_yn, es.name, es.cdb_person_id
                   FROM {cs_event} e, {cs_eventservice} es, {cs_service} s
                   WHERE e.cc_cal_id = :cal_id AND es.service_id = s.id
                   AND es.event_id = e.id AND es.valid_yn = 1 AND cdb_person_id is not null",
      array(":cal_id" => $eventId));
  $services = array();
  foreach ($res as $es) {
    $checkDate = new DateTime($es->startdate);
    if (($untilEnd_yn==0 && $checkDate->format("Y-m-d") == $splitDate->format("Y-m-d"))
    || ($untilEnd_yn==1 && $checkDate->format("Y-m-d") >= $splitDate->format("Y-m-d") )) {
      $services[] = array("date" => $es->startdate,
          "confirmed" => $es->zugesagt_yn==1,
          "name" => $es->name,
          "person_id" => $es->cdb_person_id,
          "service" => $es->service,
          // for debug reasons
          "untilEnd" =>$untilEnd_yn, "checkDate" => $checkDate, "splitDate" =>$splitDate
      );
    }
  }
  return $services;
}

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

        $ids[] = db_insert("cs_eventservice")
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
 * This function is called from ChurchCal when Event was created, updated or deleted
 * Each event will be iterated through and then decide wheater to create, update or delete event
 * @param array $params
 */
function churchservice_operateEventFromChurchCal($params) {
  $newIds = array();
  // shall it be copied? Only when I copy a event in Cal
  if (getVar("copychurchservice", false) == "true") {
    $newIds = churchservice_copyEventByCalId($params["orig_id"], $params["id"], $params["startdate"], true);
  }
  else if (!empty($params["csevents"])) foreach ($params["csevents"] as $key=>$csevent) {
    if (empty($csevent["id"])) {
      $newId = churchservice_createEvent($params, $csevent);
      $newIds[$key]=$newId;
    }
    else churchservice_updateEvent($params, $csevent);
  }
  return $newIds;
}

  /**
  * called by ChurchCal on changes in calendar events
  * it uses old_startdate to move all events, then checks if there are events to create or delete
  * from changes in repeats, exceptions or additions
  * @param array $params
  * @param string $source
  */
  function FromChurchCal($params, $csevent) {
    echo "NOT USED ANYMORE FromChurchCal ";
    $diff = null;

    if (isset($params["old_startdate"])) {
      // move events to new startdate
      $startdate     = new DateTime($params["startdate"]);
      $old_startdate = new DateTime($params["old_startdate"]);
      $diff = $startdate->format("U") - $old_startdate->format("U");
      db_query("UPDATE {cs_event} SET startdate = DATEADD(startdate, INTERVAL :diff SECOND)
              WHERE e.cc_cal_id = :cal_id", array(":cal_id" => $params["cal_id"]));
    }

    // without repeat_id, this is only a time shift, so we can end processing here.
    if (empty($params["repeat_id"])) return;

    // Collect events into array to collect the info which has to be created/deleted/updated
    $events = array ();
    // Get all mapped events from DB
    $db = db_query("SELECT id, startdate FROM {cs_event} e
                  WHERE e.cc_cal_id=:cal_id",
        array (":cal_id" => $params["event"]["id"]));
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
        $params["startdate"] = $do["startdate"];
        $params["event"]["cc_cal_id"] = $params["event"]["id"];
        $params["eventTemplate"] = $template;
        churchservice_createEvent($params, $source);
      }
    }
  }

/**
 * save new or update existing event
 * if eventTemplate is given in $params get data from there, else use services
 *
 * @param array $params
 * @throws CTException
 */
function churchservice_createEvent($params, $csevent) {
  global $user;

  include_once (CHURCHCAL . '/churchcal_db.php');

  // update/insert cs_event
  $fields = array ();
  if (isset($csevent["startdate"])) $fields["startdate"] = $csevent["startdate"];
  if (isset($params["valid_yn"]))  $fields["valid_yn"]  = $params["valid_yn"];
  $fields["special"] = (isset($csevent["special"]) ? $csevent["special"] : "");
  $fields["admin"]   = (isset($csevent["admin"])   ? $csevent["admin"]   : "");
  $fields["cc_cal_id"] = $params["id"];

  // User eventTemplate to create Event
  if (isset($csevent["eventTemplate"])) {
    $db = db_query('SELECT special, admin
                    FROM {cs_eventtemplate}
                    WHERE id=:id',
                    array (":id" => $csevent["eventTemplate"]))
                    ->fetch();
    if ($db) {
      if (empty($fields["special"])) $fields["special"] = $db->special;
      if (empty($fields["admin"]))   $fields["admin"]   = $db->admin;
    }
  }
  if (isset($csevent["eventTemplate"])) $fields["created_by_template_id"] = $csevent["eventTemplate"];

  $event_id = db_insert("cs_event")
                ->fields($fields)
                ->execute();

  if (!isset($csevent["eventTemplate"]) && isset($csevent["services"])) {
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
    foreach ($csevent["services"] as $key => $val) {
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
  else if (isset($csevent["eventTemplate"])) {
    $dt = new datetime();
    $fields = array (
        "event_id"      => $event_id,
        "valid_yn"      => 1,
        "modified_date" => $dt->format('Y-m-d H:i:s'),
        "modified_pid"  => $user->id,
    );
    $db = db_query("SELECT * FROM {cs_eventtemplate_service}
                    WHERE eventtemplate_id=:eventtemplate_id",
                    array (':eventtemplate_id' => $csevent["eventTemplate"]));
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
    ct_log("[ChurchService] Lege Template an " . $csevent["eventTemplate"] . " fuer Event", 2, $event_id, "service");

  }
  return $event_id;
}

/**
 * Update existing event
 * if eventTemplate is given in $params get data from there, else use services
 *
 * @param array $params
 * @throws CTException
 */
function churchservice_updateEvent($params, $csevent) {
  global $user;

  include_once (CHURCHCAL . '/churchcal_db.php');

  // Delete action, e.g. when adding Exceptions to CalEvent
  if ((isset($csevent["action"])) && ($csevent["action"]=="delete")) {
    churchservice_deleteEvent($csevent);
    return;
  }

  // update/insert cs_event
  $fields = array ();
  if (isset($csevent["startdate"])) $fields["startdate"] = $csevent["startdate"];
  if (isset($csevent["valid_yn"]))  $fields["valid_yn"]  = $csevent["valid_yn"];
  if (isset($csevent["special"]))  $fields["special"]  = $csevent["special"];
  if (isset($csevent["admin"]))  $fields["admin"]  = $csevent["admin"];

  $event_id = $csevent["id"];

  db_update("cs_event")
    ->fields($fields)
    ->condition('id', $event_id, "=")
    ->execute();

  if (!empty($params["services"])) {
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

/**
 * get current services of user $id
 *
 * @param int $user_id
 * @return array services
 */
function churchservice_getUserCurrentServices($user_id) {
  $arr = db_query("
    SELECT es.id, cal.bezeichnung AS event, cal.ort, s.bezeichnung AS dienst, es.id AS eventservice_id,
      sg.bezeichnung AS servicegroup, DATE_FORMAT(es.modified_date, '%Y%m%dT%H%i00') AS modified_date,
      p.vorname, p.name, es.modified_pid, zugesagt_yn, e.startdate AS startdate, DATE_FORMAT(e.startdate, '%Y%m%dT%H%i00')
      AS datum_start, ADDDATE(e.startdate, INTERVAL TIMEDIFF(cal.enddate, cal.startdate) HOUR_SECOND) AS enddate,
      DATE_FORMAT(adddate(e.startdate, interval timediff(cal.enddate, cal.startdate) HOUR_SECOND), '%Y%m%dT%H%i00') AS datum_end
    FROM {cs_event} e, {cc_cal} cal, {cs_eventservice} es, {cs_service} s, {cs_servicegroup} sg, {cdb_person} p
    WHERE cal.id=e.cc_cal_id AND es.event_id=e.id AND es.service_id=s.id AND sg.id=s.servicegroup_id AND e.valid_yn=1
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
function churchservice_deleteEvent($params) {
  global $user;
  if (!user_access("edit events", "churchservice")) throw new CTNoPermission("edit events", "churchservice");
  ct_log("[ChurchService] ". t('remove.event'), 2, $params["id"], "service");

  $db_event = db_query("SELECT e.*, DATE_FORMAT(e.startdate, '%d.%m.%Y %H:%i') AS date_de
                        FROM {cs_event} e
                        WHERE id=:event_id",
                        array (":event_id" => $params["id"]))
                        ->fetch();
  if (!$db_event) {
    if ($params["id"]) throw new CTException("deleteEvent(" . $params["id"] . "): " . t('x.not.found', t('event')));
    else return;
  }

  // Inform people about the deleted event
  if (getVar("informDeleteEvent", false, $params)) {
    $db_cal = db_query("SELECT * FROM {cc_cal}
                        WHERE id=:cal_id AND DATEDIFF(startdate, now())>=0",
                        array (":cal_id" => $db_event->cc_cal_id))
                        ->fetch();
    if ($db_cal!=false) {
      $db = db_query("SELECT p.id p_id, p.vorname, p.name, IF(p.spitzname, p.spitzname, p.vorname) AS nickname, p.email FROM {cs_eventservice} es, {cdb_person} p
                      WHERE event_id = :event_id AND valid_yn = 1 AND p.id = es.cdb_person_id
                        AND es.cdb_person_id IS NOT NULL AND p.email != ''",
                      array (":event_id" => $params["id"]));
      foreach ($db as $p) {
        $lang = getUserLanguage($p->p_id);
        $subject = "[" . getConf('site_name') . "] " . t2($lang, 'cancelation.of.event.date', $db_cal->bezeichnung, $db_event->date_de);
        $data = array(
          'person'     => $p,
          'eventTitle' => $db_cal->bezeichnung,
          'eventDate'  => $db_event->date_de
        );
        // Deine Dienstanfrage wurde entsprechend entfernt.'
        $content = getTemplateContent('email/eventDeleted', 'churchservice', $data, null, $lang);
        churchservice_send_mail($subject, $content, $p->email);
      }
    }
  }

  if (getVar("deleteCalEntry", 1, $params) == 1) {
    db_query("DELETE FROM {cs_eventservice}
              WHERE event_id=:event_id",
              array (":event_id" => $params["id"]), false);

    db_query("DELETE FROM {cs_event}
              WHERE id=:event_id",
              array (":event_id" => $params["id"]), false);
  }
  else {
    db_query("UPDATE {cs_event} SET valid_yn=0
              WHERE id=:id",
              array (":id" => $params["id"]));
  }
}
