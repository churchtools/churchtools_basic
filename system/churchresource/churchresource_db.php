<?php
include_once ('./' . CHURCHRESOURCE . '/../churchcore/churchcore_db.php');

/**
 *
 * @param string $subject
 * @param string $message
 * @param string $to
 */
function churchresource_send_mail($subject, $message, $to) {
  churchcore_systemmail($to, $subject, $message, true);
}

/**
 * 
 * @param array $params
 * @return unknown
 */
function churchresource_createBooking($params) {
  global $base_url, $user;
  
  $i = new CTInterface();
  $i->setParam("resource_id");
  $i->addTypicalDateFields();
  $i->setParam("person_id");
  $i->setParam("status_id");
  $i->setParam("text");
  $i->setParam("location");
  $i->setParam("note");
  $i->setParam("cc_cal_id", false);
  
  $id = db_insert("cr_booking")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->execute(false);
  
  $res = db_query("SELECT * FROM {cr_booking}
                   WHERE id=$id")
                   ->fetch();
  $res->ok = true;
  
  $exceptions_txt = "";
  if (isset($params["exceptions"])) foreach ($params["exceptions"] as $exception) {
    addException($res->id, $exception["except_date_start"], $exception["except_date_end"], $user->id);
    $exceptions_txt .= churchcore_stringToDateDe($exception["except_date_start"], false) + " &nbsp;";
  }
  $additions_txt = "";
  if (isset($params["additions"])) {
    $days = array(); // not used here?
    foreach ($params["additions"] as $addition) {
      addAddition($res->id, $addition["add_date"], $addition["with_repeat_yn"], $user->id);
      $additions_txt .= churchcore_stringToDateDe($addition["add_date"], false) + " " 
        . ($addition["with_repeat_yn"] == 1) ? $additions_txt .= "{R} &nbsp;" : '&nbsp;';
    }
  }
  
  $info = churchcore_getTableData("cr_resource");
  $txt = t('here.are.all.ressources.listed') . ":<p><small>";
  $txt .= '<table class="table table-condensed">';
  $txt .= "<tr><td>" . t('purpose') . "<td>$res->text";
  $txt .= "<tr><td>" . t('resource') . "<td>" . $info[$params["resource_id"]]->bezeichnung;
  $txt .= "<tr><td>" . t('start') . "<td>" . churchcore_stringToDateDe($res->startdate);
  $txt .= "<tr><td>" . t('end') . "<td>" . churchcore_stringToDateDe($res->enddate);
  
  $status = churchcore_getTableData("cr_status");
  $txt .= "<tr><td>" . t('status') . "<td>" . $status[$res->status_id]->bezeichnung;
  
  if ($res->location) $txt .= "<tr><td>" . t('location') . "<td>$res->location";
  if ($res->repeat_id != "0") {
    $repeats = churchcore_getTableData("cc_repeat");
    $txt .= "<tr><td>" . t('repeat.type') . "<td>" . $repeats[$res->repeat_id]->bezeichnung;
    if ($res->repeat_id != 999) $txt .= "<tr><td>" . t('repeat.to') . "<td>" . churchcore_stringToDateDe($res->repeat_until, false);
    if ($exceptions_txt) $txt .= "<tr><td>" . t('exceptions') . "<td>$exceptions_txt";
  }
  if ($additions_txt) $txt .= "<tr><td>" . t('additions') . "<td>$additions_txt";
  if ($res->note) $txt .= "<tr><td>" . t('note') . "<td>$res->note";
  if (getVar("conflicts", false, $params)) $txt .= "<tr><td>" . t('conflicts') . "<td>" . $params["conflicts"];
  
  $txt .= "</table>" . NL;
  
  $txt .= '</small><p><a class="btn" href="' . $base_url . "?q=churchresource&id=" . $res->id .
       '">Zur Buchungsanfrage &raquo;</a>';
  
  // TODO: use template
  if ($params["status_id"] == 1) {
    $txt_user = "<h3>Hallo " . $user->vorname . "!</h3><p>Deine Buchungsanfrage '" . $params["text"] .
         "' ist bei uns eingegangen und wird bearbeitet. Sobald es einen neuen Status gibt, wirst Du informiert.<p>" .
         $txt;
    $txt_admin = "<h3>Hallo Admin!</h3><p>Eine neue Buchungsanfrage von <i>$user->vorname $user->name</i> wartet auf Genehmigung.<p>" .
         $txt;
  }
  else {
    $txt_user = "<h3>Hallo " . $user->vorname . "!</h3><p>Deine Buchung  '" . $params["text"] . "' war erfolgreich.<p>" .
         $txt;
    $txt_admin = "<h3>Hallo Admin!</h3><p>Eine neue Buchung von <i>$user->vorname $user->name</i> wurde erstellt und automatisch genehmigt.<p>" .
         $txt;
  }
  $userIsAdmin = false;
  if (getConf("churchresource_send_emails", true)) {
    if ($info[$params["resource_id"]]->admin_person_ids != -1) {
      foreach (explode(',', $info[$params["resource_id"]]->admin_person_ids) as $adminId) {
        // dont send mails for own actions to admin
        if ($user->id != $adminId) {
          $p = churchcore_getPersonById($adminId);
          if ($p && $p->email) {
            churchresource_send_mail("[". getConf('site_name')."] ". t('new.booking.request'). ": ". $params["text"], $txt_admin, $p->email);
          }
        }
        else $userIsAdmin = true;
      }
    }
    if (!$userIsAdmin) {
      churchresource_send_mail("[". getConf('site_name'). "] ". t('new.booking.request').": " . $params["text"], $txt_user, $user->email);
    }
  }
  $txt = churchcore_getFieldChanges(getBookingFields(), null, $res);
  cr_log("CREATE BOOKING\n" . $txt, 3, $res->id);
  
  return $res;
}

/**
 * 
 * @return array
 */
function getBookingFields() {
  $res = array(
    "id"            => churchcore_getTextField("Booking-Id", "Id", "id"),
    "resource_id"   => churchcore_getTextField("Resource", "Res", "resource_id"),
    "person_id"     => churchcore_getTextField("UserId", "User", "person_id"),
    "startdate"     => churchcore_getDateField("Startdatum", "Start", "startdate"),
    "enddate"       => churchcore_getDateField("Enddatum", "Ende", "enddate"),
    "repeat_id"     => churchcore_getTextField("Wiederholungs-Id", "Wdh.", "repeat_id"),
    "repeat_frequence" => churchcore_getTextField("Wiederholungsfrequenz", "Wdh.-Freq.", "repeat_frequence"),
    "repeat_until"  => churchcore_getDateField("Wiederholungs-Ende", "Wdh.-Ende", "repeat_until"),
    "status_id"     => churchcore_getTextField("Status", "Status", "status_id"),
    "text"          => churchcore_getTextField("Text", "Text", "text"),
    "location"      => churchcore_getTextField("Ort", "Ort", "location"),
    "note"          => churchcore_getTextField("Notiz", "Notiz", "note"),
    );
  
  return $res;
}

/**
 * TODO: too much code in churchresource_updateBooking, split it up
 * 
 * @param array $params
 * @param array $changes; default: null
 * @return multitype:multitype:unknown
 */
function churchresource_updateBooking($params, $changes = null) {
  global $base_url, $user;
  
  // Only bigchange, when I get repeat_id. Otherwise it is only a time shift.
  $bigChange = isset($params["repeat_id"]);
  
  $oldArr = getBooking($params["id"]);
  $bUser = churchcore_getPersonById($oldArr->person_id);
  $ressources = churchcore_getTableData("cr_resource", "resourcetype_id,sortkey,bezeichnung");
  
  $i = new CTInterface();
  $i->setParam("resource_id");
  $i->setParam("status_id");
  if ($bigChange) {
    $i->addTypicalDateFields();
    $i->setParam("text");
    $i->setParam("location");
    $i->setParam("note");
  }
  else {
    $i->setParam("startdate");
    $i->setParam("enddate");
    
    $res = db_query('SELECT text FROM {cr_booking} 
                     WHERE id=:id', 
                     array (":id" => $params["id"]))
                     ->fetch();
    $params["text"] = $res->text;
  }
  $i->setParam("person_id");
  $id = db_update("cr_booking")
          ->fields($i->getDBInsertArrayFromParams($params))
          ->condition("id", $params["id"], "=")
          ->execute(false);
  
  // No changes mean not from Cal, so I have to check changes manually
  if (is_null($changes) && $bigChange) {
    // TODO: put removing add/exceptions into a function (updateDates($type)?) with parameter for add/exc
    // TODO: maybe put add/exceptions in one table with an flag set to 1 for add - could it simplify exception handling?
    // get alle exceptions
    $exceptions = churchcore_getTableData("cr_exception", null, "booking_id=" . $params["id"]);
    // look which exceptions are already saved in DB.
    if (isset($params["exceptions"])) foreach ($params["exceptions"] as $exception) {
      $current_exc = null;
      // It is not possible to search exceptions by id, because ChurchCal Exc have other IDs
      if ($exceptions)  foreach ($exceptions as $e) {
        if (churchcore_isSameDay($e->except_date_start, $exception["except_date_start"])
            && churchcore_isSameDay($e->except_date_end, $exception["except_date_end"])) {
          $current_exc = $e;
        }
      }
      if ($current_exc) $exceptions[$current_exc->id]->exists = true;
      else $changes["add_exception"][] = $exception;
    }
    // delete removed exceptions from DB.
    if ($exceptions) foreach ($exceptions as $e) {
      if (!isset($e->exists)) $changes["del_exception"][] = (array) $e;
    }
    
    // get all additions
    $additions = churchcore_getTableData("cr_addition", null, "booking_id=" . $params["id"]);
    // look which additions are already saved in DB.
    if (isset($params["additions"])) foreach ($params["additions"] as $addition) {
      $current_add = null;
      // It is not possible to search additions by id, because ChurchCal adds have other IDs
      if ($additions) foreach ($additions as $a) {
        if (churchcore_isSameDay($a->add_date, $addition["add_date"]) // this is different for add/exc
            && $a->with_repeat_yn == $addition["with_repeat_yn"]) {
          $current_add = $a;
        }
      }
      if ($current_add) $additions[$current_add->id]->exists = true;
      else $changes["add_addition"][] = $addition;
    }
    // delete removed additions from DB.
    if ($additions) foreach ($additions as $a) {
      // churchresource_delAddition($a->id);
      if (!isset($a->exists)) $changes["del_addition"][] = (array) $a;
    }
  }
  
  // save new exceptions
  $res_exceptions = array ();
  $res_additions = array ();
  $days = array ();
  
  if ($changes) {
    if (isset($changes["add_exception"])) {
      foreach ($changes["add_exception"] as $exc) {
        // Check, if exception not alreay in DB (only possible when coming from Cal)
        $db = db_query("SELECT id FROM {cr_exception} 
                        WHERE booking_id=:booking_id AND except_date_start=:start", 
                        array (":booking_id" => $params["id"], 
                               ":start" => $exc["except_date_start"],
                        ))->fetch();
        if (!$db) {
          $id = addException($params["id"], $exc["except_date_start"], $exc["except_date_end"], $user->id);
          if (isset($exc["id"])) $res_exceptions[$exc["id"]] = $id;
          $days[] = $exc["except_date_start"];
        }
      }
      if (getConf("churchresource_send_emails", true)) {
        if (count($days) && $bUser) {
          // TODO: use email template
          // TODO: dont send such emails to users adding exceptions to their event in cal
          $txt = "<h3>Hallo " . $bUser->vorname . "!</h3><p>Bei Deiner Serien-Buchungsanfrage '" . $params["text"] .
               "' fuer " . $ressources[$params["resource_id"]]->bezeichnung . " mussten leider von " . $user->vorname . " " .
               $user->name . " folgende Tage abgelehnt werden: <b>" . implode(", ", $days) . "</b><p>";
          churchresource_send_mail("[" . getConf('site_name') . "] " . t('updated.booking.request') . ": " . $params["text"], $txt, $bUser->email);
        }
      }
    }
    
    if (isset($changes["del_exception"])) {
      foreach ($changes["del_exception"] as $exc) {
        $db = db_query("SELECT id FROM {cr_exception} 
                        WHERE booking_id=:booking_id AND except_date_start=:start", 
                        array (
                          ":booking_id" => $params["id"], 
                          ":start" => $exc["except_date_start"],
                        ))->fetch();
        if ($db) churchresource_delException(array ("id" => $db->id));
      }
    }
    
    if (isset($changes["add_addition"])) foreach ($changes["add_addition"] as $add) {
      $db = db_query("SELECT id FROM {cr_addition} 
                      WHERE booking_id=:booking_id AND add_date=:date", 
                      array (
                        ":booking_id" => $params["id"], 
                        ":date" => $add["add_date"],
                      ))->fetch();
      if (!$db) {
        $id = addAddition($params["id"], $add["add_date"], $add["with_repeat_yn"], $user->id);
        if (isset($add["id"])) $res_additions[$add["id"]] = $id;
      }
    }
    if (isset($changes["del_addition"])) foreach ($changes["del_addition"] as $add) {
      $db = db_query("SELECT id FROM {cr_addition} 
                      WHERE booking_id=:booking_id AND add_date=:date", 
                      array (
                        ":booking_id" => $params["id"], 
                        ":date" => $add["add_date"],
                      ))->fetch();
      if ($db != false) {
        churchresource_delAddition($db->id);
      }
    }
  }
  
  $txt = "";
  $location = ($params["location"]) ? t('booking.in', $params["location"]) : '';
  $info = t('bookingX.for.resource.on.datetime', $params["text"], $ressources[$params["resource_id"]]->bezeichnung, $params["startdate"], $location);
  
  $arr = getBooking($params["id"]);
  $changes = churchcore_getFieldChanges(getBookingFields(), $oldArr, $arr, false);
  
  if ($params["status_id"] == 1) {
    $txt = " wurde aktualisiert und wartet auf Genehmigung.<p>"; 
  }
  else if (($params["status_id"] == 2) && ($oldArr->status_id != 2 || $changes != null)) {
    $txt = " wurde von $user->vorname $user->name genehmigt!<p>";
  }
  else if ($params["status_id"] == 3) {
    $txt = " wurde leider abgelehnt, bitte suche Dir einen anderen Termin.<p>";
  }
  else if ($params["status_id"] == 99) {
    $txt = " wurde geloescht, bei Fragen dazu melde Dich bitte bei: " .
         getConf('site_mail', 'Gemeinde-Buero unter info@elim-hamburg.de oder 040-2271970') . "<p>";
  }
  if ($txt && $bUser) {
    // TODO: use email template
    $txt = "<h3>Hallo " . $bUser->vorname . "!</h3><p>Deine Buchungsanfrage " . $info . $txt;
    if ($changes != null) {
      $txt .= "<p><b>Folgende Anpassung an der Buchung wurden vorgenommen:</b><br/>" .
           str_replace("\n", "<br>", $changes);
    }
    if ($params["status_id"] < 3) $txt .= '<p><a class="btn" href="' . $base_url . "?q=churchresource&id=" .
         $params["id"] . '">Zur Buchungsanfrage &raquo;</a>';
    $adminmails = explode(",", $ressources[$params["resource_id"]]->admin_person_ids);
    if (getConf("churchresource_send_emails", true)) {
      // if current user is not admin OR is not the booking creating user
      if (!in_array($user->id, $adminmails) || $user->id != $bUser->id) {
        churchresource_send_mail("[". getConf('site_name'). "] Aktualisierung der Buchungsanfrage: ". $params["text"], $txt, $bUser->email);
      }
    }
  }
  
  if ($changes) cr_log("UPDATE BOOKING\n" . $txt, 3, $arr->id);
  $res = array ("exceptions" => $res_exceptions, "additions" => $res_additions);
  
  return $res;
}

/**
 * shift date for $minutes minutes
 * @param string $date; a DateTime understandable date
 * @param string|int $minutes
 * @return string formatted date
 */
function _shiftDate($date, $minutes) {
  $dt = new DateTime($date);
  $dt->modify("+$minutes Minute");
  return $dt->format('Y-m-d H:i:s');
}

/**
 * 
 * @param array $params
 * @param string $source
 */
function churchresource_deleteResourcesFromChurchCal($params, $source=null) {
  global $user;
  $db = db_query('SELECT * FROM {cr_booking} 
                  WHERE cc_cal_id=:cal_id', 
                  array (":cal_id" => $params["cal_id"]));
  
  foreach ($db as $b) {
    cr_log("UPDATE BOOKING\n" . "Set status=99 from source " . $source, 3, $b->id);
    
    db_update("cr_booking")
      ->fields(array ("status_id" => 99, "repeat_id" => 0))
      ->condition("id", $b->id, "=")
      ->execute();
  }  
}

/**
 * 
 * @param array $params
 * @param string $source
 * @param array $changes arr["add_exception"], ...
 */
function churchresource_updateResourcesFromChurchCal($params, $source, $changes = null) {
  global $user;
  $newbookingstatus = 1;
  
  $resources = churchcore_getTableData("cr_resource");
  $db = db_query('SELECT * FROM {cr_booking} 
                  WHERE cc_cal_id=:cal_id', 
                  array (":cal_id" => $params["id"]));
  
  $params["location"] = "";
  $params["note"] = "";
  
  foreach ($db as $booking) {
    if (isset($params["bookings"]) && isset($params["bookings"][$booking->resource_id])) {
      $save = array_merge(array (), $params);
      $save["cc_cal_id"] = $params["id"];
      
      if (!isset($params["bookings"][$booking->resource_id]["status_id"])) $save["status_id"] = $newbookingstatus;
      else $save["status_id"] = $params["bookings"][$booking->resource_id]["status_id"];
      $save["id"] = $booking->id;
      $save["person_id"] = $user->id;
      $save["resource_id"] = $booking->resource_id;
      // if big update, not only a time shift
      if (isset($params["repeat_id"])) $save["text"] = $params["bezeichnung"];

      $save["startdate"] = _shiftDate($save["startdate"], -$params["bookings"][$booking->resource_id]["minpre"]);
      $save["enddate"]   = _shiftDate($save["enddate"], $params["bookings"][$booking->resource_id]["minpost"]);
      
      // if not to delete
      if ($save["status_id"] != 99) {
        // on date change set status to need confirmation!
        if ((strtotime($save["startdate"]) != strtotime($booking->startdate)) ||
             (strtotime($save["enddate"]) != strtotime($booking->enddate))) {
          // But only if I am not an admin and resource is not autoaccept!
          if (!user_access("administer bookings", "churchresource") 
              && $resources[$booking->resource_id]->autoaccept_yn == 0) {
            $save["status_id"] = 1;
          }
        }
      }
      
      churchresource_updateBooking($save, $changes);
      
      $params["bookings"][$booking->resource_id]["updated"] = true;
    }
    else if (isset($params["bookings"]) && isset($params["cal_id"])) {
    }
  }

  // Gehe nun noch die neuen Bookings durch, die nicht in der DB sind
  if (!isset($params["bookings"])) return;
  foreach ($params["bookings"] as $booking) {
    if (!isset($booking["updated"])) {
      $save = array_merge(array (), $params);
      $save["cc_cal_id"]  = $params["id"];
      $save["status_id"]  = isset($booking["status_id"]) ? $booking["status_id"] : $newbookingstatus;
      $save["person_id"]  = $user->id;
      $save["resource_id"]= $booking["resource_id"];
      $save["text"]       = $params["bezeichnung"];
      $save["startdate"]  = _shiftDate($save["startdate"], -$params["bookings"][$booking["resource_id"]]["minpre"]);
      $save["enddate"]    = _shiftDate($save["enddate"], $params["bookings"][$booking["resource_id"]]["minpost"]);
      
      churchresource_createBooking($save);
    }
  }
}      

/**
 * 
 * @return array bookings
 */
function getOpenBookings() {
  $res = db_query("
    SELECT b.id, b.person_id, concat(p.vorname,' ',p.name) AS person_name, DATE_FORMAT(startdate, '%d.%m.%Y %H:%i') AS startdate, 
      enddate, b.text, r.bezeichnung resource 
	FROM {cr_booking} b, {cr_resource} r, {cdb_person} p 
    WHERE b.person_id=p.id AND status_id=1 AND b.resource_id=r.id AND DATEDIFF(startdate, NOW())>=0 ORDER BY startdate");
  $arrs=array();
  foreach ($res as $arr) $arrs[$arr->id]=$arr;   

  return $arrs; 
}

/**
 * get last log id
 * @return int id
 */
function churchresource_getLastLogId() {
  $arr = db_query("SELECT MAX(id) id FROM {cr_log}")
                 ->fetch();
  return $arr->id;  
}

/**
 * 
 * @param int $booking_id
 * @param string $date_start
 * @param string $date_end
 * @param int $pid
 */
function addException($booking_id, $date_start, $date_end, $pid) {
  $dt = new DateTime();  
  $res = db_insert("cr_exception")
    ->fields(array(
      "booking_id"=>$booking_id,
      "except_date_start"=>$date_start,
      "except_date_end"=>$date_end,
      "modified_pid"=>$pid,
      "modified_date"=>$dt->format('Y-m-d H:i:s'),
    ))->execute(false);
  
  return $res;
}

/**
 * 
 * @param array $params
 */
function churchresource_delException($params) {
  $exc_id = $params["id"];
  $res = db_query("DELETE FROM {cr_exception} 
                   WHERE id=:id",
                   array(':id'=>$exc_id));
}

/**
 * 
 * @param int $booking_id
 * @param string $date_start
 * @param unknown $with_repeat
 * @param int $pid
 */
function addAddition($booking_id, $date_start, $with_repeat, $pid) {
  $dt=new DateTime();  
  $res = db_insert("cr_addition")
    ->fields(array(
      "booking_id"=>$booking_id,
      "add_date"=>$date_start,
      "with_repeat_yn"=>$with_repeat,
      "modified_pid"=>$pid,
      "modified_date"=>$dt->format('Y-m-d H:i:s'),
    ))->execute();
  
  return $res;
}

/**
 * 
 * @param int $add_id
 */
function churchresource_delAddition($add_id) {
  $res=db_query("DELETE FROM {cr_addition} WHERE id=:id",
                 array(':id'=>$add_id));
}

/**
 * TODO: use :params for query?
 * 
 * @param string $from
 * @param string $to
 * @param string $status_id_in
 * @return array bookings
 */
function getBookings($from = null, $to = null, $status_id_in = "") {
  if ($from == null) $from = -getConf('churchresource_entries_last_days', '90');
  if ($to == null) $to = 999;
  if ($status_id_in) $status_id_in = " AND status_id IN ($status_id_in)";
  
  $res = db_query("
    SELECT b.id , b.cc_cal_id, b.resource_id, b.person_id, b.startdate, b.enddate, 
      b.repeat_id, b.repeat_frequence, b.repeat_until, b.repeat_option_id, b.status_id, b.text,
      b.location, b.note, b.show_in_churchcal_yn, concat(p.vorname, ' ',p.name) person_name 
    FROM {cr_booking} b left join {cdb_person} p on (b.person_id=p.id)  
    WHERE ((startdate<=DATE_ADD(NOW(),INTERVAL $from day) AND enddate>DATE_ADD(NOW(),INTERVAL $from day) )
      OR (enddate>=DATE_ADD(now(),INTERVAL $from day) and enddate<=DATE_ADD(now(),INTERVAL $to day)) 
      OR (repeat_id>0 and startdate<=DATE_ADD(now(),INTERVAL $to day) 
      AND (repeat_until>=DATE_ADD(now(),INTERVAL $from day) or repeat_id=999))) $status_id_in");
  
  $arrs = array();
  foreach ($res as $b) {
    $res2 = db_query("SELECT * FROM {cr_exception} 
                      WHERE booking_id= $b->id 
                      ORDER by except_date_start");
    
    foreach ($res2 as $e) $b->exceptions[$e->id] = $e;
     
    $res2 = db_query("SELECT * FROM {cr_addition} 
                      WHERE booking_id= $b->id 
                      ORDER by add_date");
    
    foreach ($res2 as $a) $b->additions[$a->id] = $a;
    
    if (isset($b->cc_cal_id)) {
      $r = db_query("SELECT category_id FROM {cc_cal} 
                     WHERE id=:cal_id", 
                     array (":cal_id" => $b->cc_cal_id))
                     ->fetch();
      if ($r) $b->category_id = $r->category_id;
    }
    if (!$b->person_name)  $b->person_name = t('user.was.deleted');
    
    $arrs[$b->id] = $b;
  }
  
  return count($arrs) ? $arrs : '';
}

/**
 * get one booking
 * 
 * @param int $id
 * @return object booking
 */
function getBooking($id) {
  $res = db_query("SELECT b.*, CONCAT(p.vorname, ' ', p.name) AS person_name 
                   FROM {cr_booking} b LEFT JOIN {cdb_person} p ON (b.person_id=p.id)
                   WHERE b.id=" . $id)->fetch(); 
  return $res;	
}

/**
 * @param array $params
 * @return string ok
 */
function churchresource_delBooking($params) {
  $id = $params["id"];
  $res = db_query("DELETE FROM {cr_exception} where booking_id=" . $id);
  $res = db_query("DELETE FROM {cr_addition} where booking_id=" . $id);
  $res = db_query("DELETE FROM {cr_booking} where id=" . $id);
  
  return "ok"; 
}

/**
 * 
 * @param string $txt
 * @param int $level; default: -1, 3 = not important, 2 = appears in person details, 1 = important!!
 * @param int $personid 
 */
function cr_log($txt, $level = 3, $booking_id = -1) {
	global $user;
	$txt = str_replace("'", "\'", $txt);
	
	db_query("INSERT INTO {cr_log} (person_id, level, datum, booking_id, txt) 
	          VALUES ('$user->id', $level, current_timestamp(), $booking_id, '$txt')");
}
