<?php

/**
 */
class CTChurchServiceModule extends CTAbstractModule {

  public function getMasterDataTablenames() {
    $res = array ();
    // $res[2]=churchcore_getMasterDataEntry(2, "Service", "service", "cs_service");
    $res[3] = churchcore_getMasterDataEntry(3, "Service-Gruppe", "servicegroup", "cs_servicegroup", "sortkey");
    // $res[4]=churchcore_getMasterDataEntry(4, "Event-Kategorien", "category", "cs_category","sortkey");
    $res[5] = churchcore_getMasterDataEntry(5, "Abwesenheitsgrund", "absent_reason", "cs_absent_reason", "sortkey");
    $res[6] = churchcore_getMasterDataEntry(6, "Fakten", "fact", "cs_fact", "sortkey");
    $res[7] = churchcore_getMasterDataEntry(7, "Song-Kategorien", "songcategory", "cs_songcategory", "sortkey");

    return $res;
  }

  /**
   *
   * @see CTModuleInterface::getMasterData()
   */
  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;
    include_once (CHURCHCAL . '/churchcal_db.php');
    $auth = churchservice_getAuthorization();
    $res = $this->getMasterDataTables();
    $res["masterDataTables"] = $this->getMasterDataTablenames();
    $res["auth"] = $auth;
    $res["modulespath"] = churchservice_getModulesPath();
    $res["base_url"] = $base_url;
    $res["files_url"] = $base_url . $files_dir;
    $res["files_dir"] = $files_dir;
    $res["modulename"] = "churchservice";
    $res["adminemail"] = getConf('site_mail', '');
    $res["user_pid"] = $user->id;
    $res["user_name"] = $user->vorname . " " . $user->name;
    $res["userid"] = $user->cmsuserid;
    $res["settings"] = churchservice_getUserSettings($user->id);
    $res["notification"] = churchcore_getMyNotifications();
    $res["notificationtype"] = churchcore_getTableData("cc_notificationtype");
    $res["lastLogId"] = churchservice_getLastLogId();
    $res["eventtemplate"] = churchcore_getTableData("cs_eventtemplate", "sortkey");
    $res["category"] = churchcal_getAllowedCategories(false);
    $res["repeat"] = churchcore_getTableData("cc_repeat");

    $res["eventtemplate_services"] = churchservice_getEventtemplateServices($auth);
    $res["churchcal_name"] = $config["churchcal_name"];
    $res["churchservice_name"] = $config["churchservice_name"];
    $res["songwithcategoryasdir"] = getConf("churchservice_songwithcategoryasdir", "0");
    $res["songcategory"] = churchcore_getTableData("cs_songcategory", "sortkey");
    $res["views"] = array("ListView" => array("filename"=>"cs_listview"),
      "SettingsView" => array("filename"=>"cs_settingsview"),
      "CalView" => array("filename"=>"cs_calview"),
      "SongView" => array("filename"=>"cs_songview"),
      "AgendaView" => array("filename"=>"cs_agendaview"),
      "FactView" => array("filename"=>"cs_factview"),
      "MaintainView" => array("filename"=>"cs_maintainview"));

    return $res;
  }

  // EVENTS ARE CREATED, UPDATED AND DELETED OVER CHURCHCAL!!!
  // ChurchCal then call churchservice_operateEventFromChurchCal()
  // So everything with dates will be processed over ChurchCal

  public function updateEvent($params) {
    $this->checkPerm("edit events");
    include_once ('./' . CHURCHCAL . '/churchcal_db.php');
    churchcal_updateEvent($params);
  }

  public function createEvent($params) {
    $this->checkPerm("edit events");
    include_once ('./' . CHURCHCAL . '/churchcal_db.php');
    return churchcal_createEvent($params);
  }

  public function deleteEvent($params) {
    $this->checkPerm("edit events");
    return churchservice_deleteEvent($params);
  }

  public function getEventChangeImpact($params) {
    include_once ('./' . CHURCHCAL . '/churchcal_db.php');
    return churchcal_getEventChangeImpact($params);
  }

  public function saveSplittedEvent($params) {
    include_once ('./' . CHURCHCAL . '/churchcal_db.php');
    return churchcal_saveSplittedEvent($params);
  }


  public function getEventTemplates() {
    return churchcore_getTableData("cs_eventtemplate", "sortkey");
  }

  public function updateEventService($params) {
    return churchservice_updateEventService($params);
  }

  public function getAbsent($params) {
    global $config;
    return churchcore_getTableData("cs_absent", "startdate"); // "datediff(startdate,current_date)>-".$config["churchservice_entries_last_days"]);
  }

  public function getGroupAndTagInfos() {
    global $user;
    $a = array();
    if (user_access("view alldata", "churchdb")) $a["groups"] = getAllGroups();
    else $a["groups"] = churchdb_getMyGroups($user->id, false, true);
    $a["tags"] = getAllTags();

    return $a;
  }

  public function addOrRemoveServiceToEvent($params) {
    return churchservice_addOrRemoveServiceToEvent($params);
  }

  public function sendEMailToPersonIds($params) {
    global $base_url;

    $content = $params["inhalt"];
//     $usetemplate = (isset($params["usetemplate"]) && ($params["usetemplate"] == true));
    $usetemplate = (getVar('usetemplate', false, $params) == true);
    if ($params["domain_id"] != "null") {
      $content .= '<p><a class="btn btn-royal" href="'
          . $base_url . '?q=churchservice&id=' . $params["domain_id"] . '">Event aufrufen</a>';
    }
    return churchcore_sendEMailToPersonIDs($params["ids"], $params["betreff"], $content, null, true, $usetemplate);
  }

  public function saveTemplate($params) {
    $this->checkPerm("edit template");
    churchservice_updateOrInsertTemplate(($params["template_id"] == "null" ? null : $params["template_id"]), $params["bezeichnung"], $params["stunde"], $params["minute"], $params["dauer_sec"], $params["category_id"], $params["event_bezeichnung"], $params["special"], $params["admin"], (isset($params["services"]) ? $params["services"] : null));
  }

  public function deleteTemplate($params) {
    $this->checkPerm("edit template");

    db_query("DELETE FROM {cs_eventtemplate_service}
              WHERE eventtemplate_id=:id",
              array(':id' => $params["id"]));

    db_query("DELETE FROM {cs_eventtemplate}
              WHERE id=:id",
              array(':id' => $params["id"]));
    }

  public function delFile($params) {
    return churchcore_delFile($params["id"]);
  }

  public function renameFile($params) {
    return churchcore_renameFile($params["id"], $params["filename"]);
  }

  public function copyFile($params) {
    return churchcore_copyFileToOtherDomainId($params["id"], $params["domain_id"]);
  }

  public function getFiles($params) {
    return churchcore_getFiles("service");
  }

  public function getAllSongs($params) {
    return churchservice_getAllSongs();
  }

  public function getSongStatistic($params) {
    $this->checkPerm("view song statistics");
    $res = db_query("SELECT arrangement_id, e.startdate FROM {cs_item} i, {cs_event_item} ei, {cs_event} e
               WHERE e.id = ei.event_id AND i.id = ei.item_id AND i.arrangement_id > 0 ORDER BY e.startdate");
    $ret = array();
    foreach ($res as $s) {
      if (!isset($ret[$s->arrangement_id])) $arr = array();
      else $arr = $ret[$s->arrangement_id];
      $arr[] = $s->startdate;
      $ret[$s->arrangement_id] = $arr;
    }
    return $ret;
  }

  public function addNewSong($params) {
    $this->checkPerm("edit song");
    return churchservice_addNewSong($params);
  }

  public function editSong($params) {
    $this->checkPerm("edit song");
    return churchservice_editSong($params);
  }

  public function delSong($params) {
    $this->checkPerm("edit song");
    return churchservice_delSong($params);
  }

  public function editArrangement($params) {
    $this->checkPerm("edit song");
    return churchservice_editArrangement($params);
  }

  public function addArrangement($params) {
    $this->checkPerm("edit song");
    return churchservice_addArrangement($params);
  }

  public function delArrangement($params) {
    $this->checkPerm("edit song");
    return churchservice_delArrangement($params);
  }

  public function deleteSong($params) {
    $this->checkPerm("edit song");
    return churchservice_deleteSong($params);
  }

  public function makeAsStandardArrangement($params) {
    $this->checkPerm("edit song");
    return churchservice_makeAsStandardArrangement($params);
  }

  public function saveAbsent($params) {
    global $user;
    $i = new CTInterface();
    $i->setParam("person_id");
    $i->setParam("absent_reason_id");
    $i->setParam("bezeichnung");
    $i->setParam("startdate");
    $i->setParam("enddate");
    $i->addModifiedParams();
    if (isset($params["id"])) {
      $id = $params["id"];

      db_update("cs_absent")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->condition("id", $params["id"], "=")
        ->execute();
    }
    else {
      $id = db_insert("cs_absent")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->execute();
    }
    return $id;
  }

  public function delAbsent($params) {
    return churchservice_delAbsent($params["id"]);
  }

  public function saveNote($params) {
    churchservice_saveNote($params["event_id"], $params["text"]);
  }

  public function getServiceGroupPersonWeight($params) {
    $res = array ();
    $res["weight"] = churchservice_getServiceGroupPersonWeight();
    return $res;
  }

  public function editServiceGroupPersonWeight($params) {
    return churchservice_editServiceGroupPersonWeight($params);
  }

  public function getChurchDBMasterData($params) {
    $res["cdb_gruppen"] = churchcore_getTableData("cdb_gruppe");
    $res["cdb_tag"] = churchcore_getTableData("cdb_tag");
    return $res;
  }

  /*
   * NOW ALL FUNCTIONS FOR AGENDA VIEW
   */

  /**
   * Load agenda templates, if allowed.
   *
   * @throws CTNoPermission
   */
  public function loadAllAgendaTemplates($params) {
    $auth = churchservice_getAuthorization();
    $allowedAgendas = $auth["view agenda"];

    $where = "calcategory_id IN (" . db_implode($allowedAgendas) . ") AND template_yn=1";
    $data = churchcore_getTableData("cs_agenda", null, $where);
    return $data;
  }

  /**
   * Load agendas with ids including related Event_ids but without items
   * Check if it's allowed to view or if user is involved in one of the events
   *
   * @param array $params; [ids] for ids to get
   * @return agenda oder null if not found or not allowed
   */
  public function loadAgendas($params) {
    $where = "id IN (" . db_implode($params["ids"]) . ")";
    $data = churchcore_getTableData("cs_agenda", null, $where);
    $auth = churchservice_getAuthorization();
    $allowedAgendas = array ();
    if (isset($auth["view agenda"])) $allowedAgendas = $auth["view agenda"];
    if (!$data) return null;
    else {
      foreach ($data as $key => $d) {
        // Check if template
        $d->event_ids = $this->getBelongingEventIdsToAgenda($d->id);
        // Check if allowed
        if (!isset($allowedAgendas[$d->calcategory_id])) {
          // if not allowed, checked if I am involved in services of belonging events
          $involved = false;
          foreach ($d->event_ids as $event_id) {
            if (!$involved) $involved = $this->isUserInvolved($event_id);
          }
          if (!$involved) unset($data[$key]);
        }
      }
      return $data;
    }
  }

  /**
   * Gets the agenda belonging to the event $params["event_id"]
   *
   * @param unknown $params
   * @throws CTFail
   * @throws CTNoPermission
   * @return Agenda with all items
   */
  public function loadAgendaForEvent($params) {
    // Get agenda_id
    $db = db_query('SELECT agenda_id FROM {cs_event_item} ei, {cs_item} i
                    WHERE ei.item_id=i.id and event_id=:event_id limit 1',
                    array (":event_id" => $params["event_id"]))
                    ->fetch();
    if (!$db) throw new CTFail(t('no.agenda.found.for.event.x', $params["event_id"]));

    // load agenda data
    $agendas = $this->loadAgendas(array ("ids" => array ($db->agenda_id)));
    if (isset($agendas[$db->agenda_id])) return $agendas[$db->agenda_id];
    else throw new CTNoPermission("view agenda", "churchservice");
  }

  /**
   * get Event linked with agenda
   * TODO: rename? relatedEvents?
   *
   * @param int $agenda_id
   * @return array ids
   */
  private function getBelongingEventIdsToAgenda($agenda_id) {
    $db = db_query("SELECT distinct ei.event_id id
                    FROM {cs_event_item} ei, {cs_item} i
                    WHERE ei.item_id=i.id and i.agenda_id=:agenda_id",
                    array (":agenda_id" => $agenda_id));
    $event_ids = array();
    foreach ($db as $event) if ($event_ids[] = $event->id); // TODO: why if?

    return $event_ids;
  }

  /**
   * Load Agenda items
   *
   * @param array $params["agenda_id"]
   * @throws CTException
   * @throws CTNoPermission
   *
   * @return array with item objects
   */
  public function loadAgendaItems($params) {
    $auth = churchservice_getAuthorization();

    //get agenda properties
    $db = churchcore_getTableData("cs_agenda", null, "id = " . $params["agenda_id"]);
    if (!$db) throw new CTException(t('no.agenda.found'));

    $agenda = $db[$params["agenda_id"]];

    //get items of agenda
    $items = churchcore_getTableData("cs_item", null, "agenda_id=" . $params["agenda_id"]);
    if ($items) {
      $event_ids = array();
      // TODO: add some comments

      // TODO: use something like this to get the event ids
//       $ids = (implode(',', array_keys($items)));
//       SELECT item_id, GROUP_CONCAT(event_id) AS event_ids
//       FROM `cs_event_item`
//       GROUP BY item_id
//       HAVING `item_id` IN ($ids)
//       $item->event_ids[] = explode(',', $e->event_ids)

      foreach ($items as $item) {
        if ($ei = churchcore_getTableData("cs_event_item", null, "item_id=" . $item->id, "event_id")) {
          $item->events = array();
          foreach ($ei as $e) {
            $item->event_ids[] = $e->event_id;
            $event_ids[$e->event_id] = $e->event_id;
          }
        }
        if ($sgs = churchcore_getTableData("cs_item_servicegroup", null, "item_id=" . $item->id)) {
          foreach ($sgs as $sg) $item->servicegroup[$sg->servicegroup_id] = $sg->note;
        }
      }
      // Check perms
      if (empty($auth["view agenda"]) || empty($auth["view agenda"][$agenda->calcategory_id])) {
        $involved = false;
        foreach ($event_ids as $event_id) {
          if (!$involved) $involved = $this->isUserInvolved($event_id);
        }
        if (!$involved) throw new CTNoPermission("view agenda", "churchservice");
      }
    }
    return $items;
  }

  /**
   * Check, if I have a service in this event
   * @param unknown $event_id
   */
  function isUserInvolved($event_id) {
    global $user;
    $db=db_query("SELECT * FROM {cs_eventservice}
                  WHERE event_id=:event_id AND cdb_person_id=:p_id AND valid_yn=1",
                  array(":event_id"=>$event_id, ":p_id"=>$user->id))
                  ->fetch();
    return $db != false;
  }

  /**
   * Saves item of agenda agenda_id
   *
   * @param array $params[...]
   * @return new item id
   */
  public function saveItem($params) {
    $agenda = $this->loadAgendas(array ("ids" => array ($params["agenda_id"])));
    if ($agenda == null) throw new CTFail("Agenda nicht gefunden");

    $this->checkPerm("edit agenda", null, $agenda[$params["agenda_id"]]->calcategory_id);
    if ($agenda[$params["agenda_id"]]->template_yn == 1) {
      $this->checkPerm("edit agenda templates", null, $agenda[$params["agenda_id"]]->calcategory_id);
    }

    $i = new CTInterface();
    $i->setParam("agenda_id");
    $i->setParam("bezeichnung");
    $i->setParam("header_yn");
    $i->setParam("responsible");
    $i->setParam("arrangement_id", false);
    $i->setParam("note");
    $i->setParam("sortkey");
    $i->setParam("duration");
    $i->setParam("preservice_yn");
    $i->addModifiedParams();

    if (empty($params["id"])) {
      $params["id"] = db_insert("cs_item")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->execute(false);
    }
    else {
      db_update("cs_item")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->condition("id", $params["id"], "=")
        ->execute(false);
    }
    if (isset($params["servicegroup"])) {
      foreach ($params["servicegroup"] as $key => $isg) {
        db_query("INSERT INTO {cs_item_servicegroup} (item_id, servicegroup_id, note)
  	              VALUES(:item_id, :servicegroup_id, :note)
  	              ON DUPLICATE KEY UPDATE note=:note",
  	              array (":item_id" => $params["id"],
                         ":servicegroup_id" => $key,
                         ":note" => $isg,
  	              ));
      }
    }
    // insert event relation
    if (isset($params["event_ids"])) foreach ($params["event_ids"] as $event_id) {
      // IGNORE avoids errors on items already mapped to event
      db_query("INSERT IGNORE INTO {cs_event_item} (event_id, item_id)
                VALUES (:event_id, :item_id)",
                array (":event_id" => $event_id, ":item_id" => $params["id"]));
    }
    return $params["id"];
  }

  /**
   * Save agenda and return saved one with all new Ids
   *
   * @param array $params
   * @return array
   */
  public function saveAgenda($params) {
    $this->checkPerm("edit agenda", null, $params["calcategory_id"]);
    if (isset($params["id"]) && churchservice_isAgendaTemplate($params["id"])) {
      $this->checkPerm("edit agenda templates", null, $params["calcategory_id"]);
    }
    $i = new CTInterface();
    $i->setParam("calcategory_id");
    $i->setParam("bezeichnung");
    $i->setParam("template_yn");
    $i->setParam("series");
    $i->setParam("final_yn", false);

    if (!isset($params["id"])) {
      $params["id"] = db_insert("cs_agenda")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->execute(false);
    }
    else {
      db_update("cs_agenda")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->condition("id", $params["id"], "=")
        ->execute(false);
    }
    if (isset($params["items"])) {
      $newitems = array ();
      foreach ($params["items"] as $key => $item) {
        $item["agenda_id"] = $params["id"];
        $item["id"] = $this->saveItem($item);
        $newitems[$item["id"]] = $item;
      }
      $params["items"] = $newitems;
    }
    $params["event_ids"] = $this->getBelongingEventIdsToAgenda($params["id"]);

    return $params;
  }

  /**
   * Load item $params["id"] with calcategory_id and template_yn from agenda
   *
   * @param array $params
   * @throws CTException - When item not found
   * @throws CTNoPermission - When not allowed to edit or view
   * @return item
   */
  public function loadItem($params) {
    $db = db_query("SELECT i.*, a.calcategory_id, a.template_yn
                    FROM {cs_item} i, {cs_agenda} a
                    WHERE i.agenda_id=a.id AND i.id=:id",
                    array (":id" => $params["id"]))
                    ->fetch();
    if (!$db) throw new CTException(t('x.not.found', t('item')));

    $auth = churchservice_getAuthorization();
    if (empty($auth["view agenda"][$db->calcategory_id]) && empty($auth["edit agenda"][$db->calcategory_id])) {
      throw new CTNoPermission("view agenda", "churchservice");
    }

    return $db;
  }

  /**
   * save item related note for servicegroup
   * @param a $params
   */
  public function saveServiceGroupNote($params) {
    $item = $this->loadItem(array ("id" => $params["item_id"]));
    $this->checkPerm("edit agenda", null, $item->calcategory_id);
    if ($item->template_yn == 1) $this->checkPerm("edit agenda templates", null, $item->calcategory_id);

    $i = new CTInterface();
    $i->setParam("item_id");
    $i->setParam("servicegroup_id");
    $i->setParam("note");

    //TODO: rather then delete and insert use insert, on duplicte key update?
    db_delete("cs_item_servicegroup")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("item_id", $params["item_id"], "=")
      ->condition("servicegroup_id", $params["servicegroup_id"], "=")
      ->execute(false);

    db_insert("cs_item_servicegroup")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->execute(false);
  }

  /**
   * @param array $params
   */
  public function deleteItemEventRelation($params) {
    $item = $this->loadItem(array ("id" => $params["item_id"]));
    $this->checkPerm("edit agenda", null, $item->calcategory_id);

    $i = new CTInterface();
    $i->setParam("item_id");
    $i->setParam("event_id");

    db_delete("cs_event_item")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("item_id", $params["item_id"], "=")
      ->condition("event_id", $params["event_id"], "=")
      ->execute(false);
  }

  /**
   *
   * @param array $params
   */
  public function addItemEventRelation($params) {
    $item = $this->loadItem(array ("id" => $params["item_id"]));
    $this->checkPerm("edit agenda", null, $item->calcategory_id);

    $i = new CTInterface();
    $i->setParam("item_id");
    $i->setParam("event_id");

    db_insert("cs_event_item")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->execute(false);
  }

  /**
   *
   * @param array $params
   */
  public function deleteItem($params) {
    $item = $this->loadItem(array ("id" => $params["id"]));
    $this->checkPerm("edit agenda", null, $item->calcategory_id);

    $i = new CTInterface();
    $i->setParam("id");

    db_delete("cs_event_item")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("item_id", $params["id"], "=")
      ->execute(false);

    db_delete("cs_item")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute(false);
  }

  /**
   *
   * @param a $params
   * @throws CTException
   */
  public function deleteAgenda($params) {
    $agenda = $this->loadAgendas(array ("ids" => array ($params["id"])));
    if ($agenda == null) throw new CTException(t('x.not.found', t('agenda')));
    $this->checkPerm("edit agenda", null, $agenda[$params["id"]]->calcategory_id);
    if (churchservice_isAgendaTemplate($params["id"])) {
      $this->checkPerm("edit agenda templates", null, $agenda[$params["id"]]->calcategory_id);
    }
    $i = new CTInterface();
    $i->setParam("id");

    $db = db_query("SELECT * FROM {cs_item}
                    WHERE agenda_id=:agenda_id",
                    array (":agenda_id" => $params["id"]), false);

    foreach ($db as $item) $this->deleteItem(array ("id" => $item->id));

    db_delete("cs_agenda")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute(false);
  }

}
