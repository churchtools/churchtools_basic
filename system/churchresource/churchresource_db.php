<?php
include_once ('./' . CHURCHRESOURCE . '/../churchcore/churchcore_db.php');


/**
 * Get booking changes
 * 1. Check from originEvent if this is in newEvent
 * 2a. If not in newEvent, if it is in pastEvent, everything fine. Nothing changed
 * 2b. If is not in newEvent and in pastEvent, then it was deleted!
 * 3. If is in newEvent, then diff the changes
 * 4. Check if newEvent is not in originEvent, then there is something new
 * @param [type] $originEvent
 * @param [type] $newEvent
 */
function churchresource_getEventChangeImpact($newEvent, $pastEvent, $originEvent) {
  $changes = array ();
  $addCRChange = function (&$changes, $booking, $status, $startdate = null, $change = null) {
    $resource = churchcore_getTableData("cr_resource", null, "id = " . $booking["resource_id"]);
    $booking["resource"] = $resource[$booking["resource_id"]]->bezeichnung;
    $changes[] = array("booking" => $booking, "status" => $status, "startdate" => $startdate->format("Y-m-d"),
          "changes" => $change);
  };

  $splitDate = new DateTime($newEvent["startdate"]);

  if (empty($originEvent["bookings"])) {
    if (isset($newEvent["bookings"])) {
      foreach ($newEvent["bookings"] as $booking) {
        $addCRChange($changes, $booking, "new", $splitDate);
      }
    }
  }
  else {
    // 1. Get all Dates for the origin Event
    $ds = getAllDatesWithRepeats((object) $originEvent, 0, 9999, $splitDate);
    if ($ds) foreach ($ds as $d) {
      if (!dateInCCEvent($d, $newEvent)) { // 1. Date is not in newEvent
        if (dateInCCEvent($d, $pastEvent)) {
          // 2a. in past Event, everything fine. Nothing to check, because in past is no changes in bookings!
          foreach ($originEvent["bookings"] as $booking) {
            //$addCRChange($changes, $booking, "no.change", $d);
          }
        }
        else {
          // 2b. Deleted! Now for each booking make change entry
          foreach ($originEvent["bookings"] as $booking) {
            $addCRChange($changes, $booking, "deleted", $d);
          }
        }
      }
      else { // 3. event is in newEvent, now check bookings!
        foreach ($originEvent["bookings"] as $booking) {
          if (empty($newEvent["bookings"])) {
            $addCRChange($changes, $booking, "deleted");
          }
          else {
            $newBooking = findBookingInNewEvent($booking, $newEvent);
            if ($newBooking != null) {
              $change = makeBookingDiff($booking, $newBooking, getOneEventOutOfSeries($originEvent, $d), getOneEventOutOfSeries($newEvent, $d));
              if ($change != null) $addCRChange($changes, $booking, "updated", $d, $change);
              //else $addCRChange($changes, $booking, "no.changes", $d, $change);
            }
            // This is currently not supported, cause delete is not possible. Only set Status to deleted.
            //else $addCRChange($changes, $booking, "error", $d, $change);
          }
        }
        // Perhaps new resource in booking
        foreach ($newEvent["bookings"] as $booking) {
          $originBooking = findBookingInOriginEvent($booking, $originEvent);
          if ($originBooking == null) {
            $addCRChange($changes, $booking, "new", $d);
          }
        }
      }
    }
    // Now do 4.
    $ds = getAllDatesWithRepeats((object) $newEvent, 0, 9999, $splitDate);
    if ($ds) foreach ($ds as $d) {
      if (!dateInCCEvent($d, $originEvent)) {
        foreach ($newEvents["bookings"] as $booking) {
          $addCRChange($changes, $booking, "new", $d);
        }
      }
    }
  }
  return $changes;
}


function findBookingInNewEvent($booking, $newEvent) {
  $ret = null;
  if (empty($newEvent["bookings"])) return $ret;

  foreach ($newEvent["bookings"] as $newBooking) {
    if ((!empty($newBooking["id"]) && !empty($booking["id"]) && $newBooking["id"] == $booking["id"])
       || (!empty($newBooking["old_id"]) && !empty($booking["id"]) && $newBooking["old_id"] == $booking["id"]))
       $ret = $newBooking;
  }
  return $ret;
}
function findBookingInOriginEvent($booking, $originEvent) {
  $ret = null;
  if (empty($originEvent["bookings"])) return $ret;

  foreach ($originEvent["bookings"] as $originBooking) {
    if ((!empty($originBooking["id"]) && !empty($booking["id"]) && $originBooking["id"] == $booking["id"])
       || (!empty($originBooking["id"]) && !empty($booking["old_id"]) && $originBooking["id"] == $booking["old_id"]))
       $ret = $originBooking;
  }
  return $ret;
}

function makeBookingDiff($booking, $newBooking, $originEvent, $newEvent) {
  if ($booking == null || $newBooking == null) return null;
  $ret = array ();
  foreach ($newBooking as $key=>$newEntry) {
    if ($key != "old_id" && $newEntry != $booking[$key]) {
      $einheit = "";
      $old = $booking[$key];
      $new = $newEntry;
      $k = $key;
      if ($key == "minpre") { $k = "booking.before"; $einheit = "min"; }
      else if ($key == "minpost") { $k = "booking.after"; $einheit = "min"; }
      else if ($key == "status_id") {
        $k = "status";
        $status = churchcore_getTableData("cr_status");
        $old = $status[$old]->bezeichnung;
        $new = $status[$new]->bezeichnung;
      }
      else if ($key == "resource_id") {
        $k = "resource";
        $res = churchcore_getTableData("cr_resource");
        $old = $res[$old]->bezeichnung;
        $new = $res[$new]->bezeichnung;
      }
      $ret[$k] = array ("old" => $old . $einheit, "new" => $new . $einheit);
    }
  }
  if (churchcore_stringToDateDe($originEvent["startdate"]) != churchcore_stringToDateDe($newEvent["startdate"])) {
    $ret["start.date"] = array ("old" => _shiftDate($originEvent["startdate"], $booking["minpre"], 'd.m.Y H:i'),
                                "new" => _shiftDate($newEvent["startdate"], $booking["minpre"], 'd.m.Y H:i'));
  }
  if (churchcore_stringToDateDe($originEvent["enddate"]) != churchcore_stringToDateDe($newEvent["enddate"])) {
    $ret["end.date"] = array ("old" => _shiftDate($originEvent["enddate"], $booking["minpre"], 'd.m.Y H:i'),
                                "new" => _shiftDate($newEvent["enddate"], $booking["minpre"], 'd.m.Y H:i'));
  }
  return $ret;
}

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
 * TODO: DB column ort should be renamed to subtitle, note or similar
 *
 * @param array $params
 * @return unknown
 */
function churchresource_createBooking($params, $sendEMails = true) {
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

  $bookingId = db_insert("cr_booking")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->execute(false);

  $booking = db_query("SELECT * FROM {cr_booking}
                   WHERE id = $bookingId")
                   ->fetch();
  $booking->ok = true;

  $exceptions = "";
  if (isset($params["exceptions"])) foreach ($params["exceptions"] as $exception) {
    addException($booking->id, $exception["except_date_start"], $exception["except_date_end"], $user->id);
    $exceptions .= churchcore_stringToDateDe($exception["except_date_start"], false) + " &nbsp;";
  }
  $additions = "";
  if (isset($params["additions"])) {
    $days = array(); // not used here?
    foreach ($params["additions"] as $addition) {
      addAddition($booking->id, $addition["add_date"], $addition["with_repeat_yn"], $user->id);
      $additions .= churchcore_stringToDateDe($addition["add_date"], false) + " "
        . ($addition["with_repeat_yn"] == 1) ? $additions .= "{R} &nbsp;" : '&nbsp;';
    }
  }

  $resources = churchcore_getTableData("cr_resource"); //TODO: only get needed resource_id
  $status = churchcore_getTableData("cr_status"); //TODO: only get needed status_id
  $data = array(
    'booking'     => $booking,
    'resource'    => $resources[$params["resource_id"]]->bezeichnung,
    'conflicts'   => getVar("conflicts", false, $params),
    'startdate'   => churchcore_stringToDateDe($booking->startdate),
    'enddate'     => churchcore_stringToDateDe($booking->enddate),
    'status'      => $status[$booking->status_id]->bezeichnung,
    'repeatType'  => false,
    'exceptions'  => $exceptions,
    'additions'   => $additions,
    'conflicts'   => getVar("conflicts", false, $params),
    'bookingUrl'  => $base_url . "?q=churchresource&id=" . $booking->id,
    'pending'     => getVar("status_id", false, $params) == CR_PENDING,
    'succesful'   => getVar("status_id", false, $params) == CR_APPROVED,
    'canceled'    => false,
    'person'      => false,
  );
  // Now send email to admin persons
  if (getConf("churchresource_send_emails", true)) {
    if ($resources[$params["resource_id"]]->admin_person_ids > 0) {
      foreach (explode(',', $resources[$params["resource_id"]]->admin_person_ids) as $id) {
        // dont send mails for own actions to resource admins
        if ($user->id != $id) {
          $p = churchcore_getPersonById($id);
          if ($p && $p->email) {
            $data['userIsResourceAdmin'] = true;
            $data['surname']  = $p->vorname;
            $data['nickname'] = $p->spitzname ? $p->spitzname : $p->vorname;
            $data['name']     = $p->name;
  
            $content = getTemplateContent('email/bookingRequest', 'churchresource', $data);
            churchresource_send_mail("[". getConf('site_name')."] ". t('new.booking.request'). ": ". $params["text"], $content, $p->email);
          }
        }
        else $userIsAdmin = true;
      }
    }
    // Send email to author
    if ($sendEMails && !$userIsAdmin) {
      $data['userIsResourceAdmin'] = false;
      $data['nickname'] = $user->spitzname ? $user->spitzname : $user->vorname;
      $content = getTemplateContent('email/bookingRequest', 'churchresource', $data);
      churchresource_send_mail("[". getConf('site_name'). "] ". t('new.booking.request').": " . $params["text"], $content, $user->email);
    }
  }
  // TODO: maybe use $loginfo?
  $logInfo = t('bookingX.for.resource.on.datetime',
                $params["text"],
                $resources[$params["resource_id"]]->bezeichnung,
                $params["startdate"], $params["location"]
  );
  $txt = churchcore_getFieldChanges(getBookingFields(), null, $booking);
  cr_log("CREATE BOOKING\n" . $txt, 3, $booking->id);

  return array ("id" => $bookingId );
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
 * FIXME: the changes for using email template are breaking logging in case no email is send
 * otherwise logging of complete mails dont seems useful => only log important things in a short text?
 *
 * @param array $params
 * @return multitype:multitype:unknown
 */
function churchresource_updateBooking($params, $sendEMails = true) {
  global $base_url, $user;

  $oldBooking = getBooking($params["id"]);
  $bUser = churchcore_getPersonById($oldBooking->person_id);
  $ressources = churchcore_getTableData("cr_resource", "resourcetype_id,sortkey,bezeichnung");

  $i = new CTInterface();
  $i->setParam("resource_id");
  $i->setParam("status_id");
  $i->addTypicalDateFields();
  $i->setParam("text", false);
  $i->setParam("location", false);
  $i->setParam("note", false);

  if (empty($params["text"])) {

    $res = db_query('SELECT text FROM {cr_booking}
                     WHERE id=:id',
                     array (":id" => $params["id"]))
                     ->fetch();
    $params["text"] = $res->text;
  }
  $i->setParam("person_id", false);

  $id = db_update("cr_booking")
          ->fields($i->getDBInsertArrayFromParams($params))
          ->condition("id", $params["id"], "=")
          ->execute(false);

  $changes = null;
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

  // save new exceptions
  $res_exceptions = array ();
  $res_additions = array ();
  $days = array ();
  $resources = churchcore_getTableData("cr_resource"); //TODO: only get needed resource_id

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

      if ($sendEMails && getConf("churchresource_send_emails", true) && count($days) && $bUser) {
        // FIXME: dont send such emails to users adding exceptions to their repeating event in cal
        $data = array(
          'canceled' => true,
          'surname'  => $bUser->vorname,
          'name'     => $bUser->name,
          'nickname' => $bUser->spitzname ? $bUser->spitzname : $bUser->vorname,
          'user'     => $user,
          'resource' => $resources[$params["resource_id"]]->bezeichnung,
          'booking'  => $booking,
          'days'     => implode(", ", $days),
          'person'   => $bUser,
          'contact'  => getConf('site_mail'), // TODO: add church contact data to config an use getConf('churchContact'),
        );
        $content = getTemplateContent('email/bookingRequest', 'churchresource', $data);
        churchresource_send_mail("[" . getConf('site_name') . "] " . t('updated.booking.request') . ": " . $params["text"], $content, $bUser->email);
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
  // FIXME: check logic for correct function; i am not sure what should happen exactly in which cases
  // TODO: maybe use $params as data and add further values
  $booking=getBooking($params["id"]);
  $changedFields = churchcore_getFieldChanges(getBookingFields(), $oldBooking, $booking, false);

  $data = array(
      'enddate'    => churchcore_stringToDateDe($params["enddate"]),
      'startdate'  => churchcore_stringToDateDe($params["startdate"]),
      'resource'   => $resources[$params["resource_id"]]->bezeichnung,
      'changes'    => str_replace("\n", "<br>", $changedFields),
      'booking'    => $booking,
      'bookingUrl' => $base_url . "?q=churchresource&id=" . $params["id"],
      'text'       => $params['text'],
      'note'       => $params['location'],
      'pending'    => $params["status_id"] == CR_PENDING,
      'approved'   => $params["status_id"] == CR_APPROVED && ($oldBooking->status_id != CR_APPROVED || $changedFields != null),
      'canceled'   => $params["status_id"] == CR_CANCELED,
      'deleted'    => $params["status_id"] == CR_DELETED,
      'contact'    => getConf('site_mail'), // TODO: add church contact data to config and use getConf('churchContact'),
  );
  $logInfo = ' :: ' . t('bookingX.for.resource.on.datetime',
                        $params["text"],
                        $resources[$params["resource_id"]]->bezeichnung,
                        $params["startdate"], $params["location"]
  );
  $subject = t('booking.request.updated');
  if ($data['pending']) $logInfo = t('booking.updated') . $logInfo;
  elseif ($data['approved']) $logInfo = t('booking.approved') . $logInfo;
  elseif ($data['canceled']) $logInfo = t('booking.canceled') . $logInfo;
  elseif ($data['deleted']) $logInfo = t('booking.deleted') . $logInfo;

  if ($sendEMails && getConf("churchresource_send_emails", true)) {
    if (($params["status_id"] != $oldBooking->status_id || $changedFields != null)
        && $bUser) {
      $adminmails = explode(",", $resources[$params["resource_id"]]->admin_person_ids);
      // if current user is not resource admin OR is not the booking creating user
      if (!in_array($user->id, $adminmails) || $user->id != $bUser->id) {
        $content = getTemplateContent('email/bookingUpdated', 'churchresource', $data);
        churchresource_send_mail("[". getConf('site_name'). "] $subject: ". $params["text"], $content, $bUser->email);
      }
    }
  }
  if ($changedFields) cr_log("UPDATE BOOKING\n" . $logInfo, 3, $booking->id);

  return array ("exceptions" => $res_exceptions, "additions" => $res_additions);
}

/**
 * shift date for $minutes minutes
 * @param string $date; a DateTime understandable date
 * @param string|int $minutes
 * @return string formatted date
 */
function _shiftDate($date, $minutes, $format = 'Y-m-d H:i:s') {
  $dt = new DateTime($date);
  if (!empty($minutes)) $dt->modify("+$minutes Minute");
  return $dt->format($format);
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
      ->fields(array ("status_id" => CR_DELETED, "repeat_id" => 0))
      ->condition("id", $b->id, "=")
      ->execute();
  }
}

function copyTypicalDateFields($params) {
  $res = array();
  foreach ($params as $key => $param) {
    if ($key == "startdate" || $key == "enddate" || $key == "exceptions" || "key" == "additions"
          || strpos($key, "repeat_")!==false) {
      $res[$key] = $param;
    }
  }
  return $res;
}

/**
 *
 * @param array $params
 * @param string $source
 * @param array $changes arr["add_exception"], ...
 */
function churchresource_operateResourcesFromChurchCal($params) {
  global $user;
  $newBookingStatus = 1;
  $resources = churchcore_getTableData("cr_resource");
  $bookings = db_query('SELECT * FROM {cr_booking}
                  WHERE cc_cal_id=:cal_id',
                  array (":cal_id" => $params["id"]));

  $params["location"] = "";
  $params["note"] = "";

  foreach ($bookings as $booking) {
    if (isset($params["bookings"]) && isset($params["bookings"][$booking->id])) {
      $save = copyTypicalDateFields($params);
      foreach ($params["bookings"][$booking->id] as $key=>$val) $save[$key] = $val;
      $save["cc_cal_id"] = $params["id"];

      if (!isset($params["bookings"][$booking->id]["status_id"])) $save["status_id"] = $newbookingstatus;
      else $save["status_id"] = $params["bookings"][$booking->id]["status_id"];

      $save["id"] = $booking->id;
      if (!empty($params["modified_pid"])) $save["person_id"] = $params["modified_pid"];
      if (!isset($save["resource_id"])) $save["resource_id"] = $booking->resource_id;

      $save["text"] = $params["bezeichnung"];

      $save["startdate"] = _shiftDate($save["startdate"], -$params["bookings"][$booking->id]["minpre"]);
      $save["enddate"]   = _shiftDate($save["enddate"], $params["bookings"][$booking->id]["minpost"]);

      // if not to delete
      if ($save["status_id"] != CR_DELETED) {
        // on date change set status to need confirmation!
        if ((strtotime($save["startdate"]) != strtotime($booking->startdate)) ||
             (strtotime($save["enddate"]) != strtotime($booking->enddate))) {
          // But only if I am not an admin and resource is not autoaccept!
          if (!user_access("administer bookings", "churchresource")
              && $resources[$booking->id]->autoaccept_yn == 0) {
            $save["status_id"] = CR_PENDING;
          }
        }
      }

      churchresource_updateBooking($save, false);

      $params["bookings"][$booking->id]["updated"] = true;
    }
  }

  // Gehe nun noch die neuen Bookings durch, die nicht in der DB sind
  if (!isset($params["bookings"])) return;

  $newIds = array();

  foreach ($params["bookings"] as $oldbookingid => $booking) {
    if (!isset($booking["updated"])) {
      $save = array_merge(array (), $params);
      foreach ($booking as $key=>$val) $save[$key] = $val;

      $save["cc_cal_id"]  = $params["id"];
      $save["status_id"]  = isset($booking["status_id"]) ? $booking["status_id"] : $newBookingStatus;
      $save["person_id"]  = (empty($params["modified_pid"]) ? $user->id :$params["modified_pid"]);
      $save["text"]       = $params["bezeichnung"];
      $save["startdate"]  = _shiftDate($save["startdate"], -$booking["minpre"]);
      $save["enddate"]    = _shiftDate($save["enddate"], $booking["minpost"]);

      $arr = churchresource_createBooking($save, false);
      $newIds[$oldbookingid] = $arr["id"];
    }
  }
  return $newIds;
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
    WHERE b.person_id=p.id AND status_id=" . CR_PENDING . " AND b.resource_id=r.id AND DATEDIFF(startdate, NOW())>=0 ORDER BY startdate");
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
      $r = db_query("SELECT category_id, startdate cal_startdate, enddate cal_enddate FROM {cc_cal}
                     WHERE id=:cal_id",
                     array (":cal_id" => $b->cc_cal_id))
                     ->fetch();
      if ($r) {
        $b->category_id = $r->category_id;
        $b->cal_startdate = $r->cal_startdate;
        $b->cal_enddate = $r->cal_enddate;
      }
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
                   WHERE b.id=:id", array(":id"=>$id))->fetch();
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
