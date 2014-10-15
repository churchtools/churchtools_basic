<?php
include_once("churchdb_db.php");

/**
 * get person data to search for
 *
 * @return array with persons, key = id
 */
function getSearchableData() { 
  $persons = churchdb_getAllowedPersonData('', 'person_id p_id, person_id id, geburtsdatum, familienstand_no, geschlecht_no, hochzeitsdatum, nationalitaet_id,  
              erstkontakt, zugehoerig, eintrittsdatum, austrittsdatum, taufdatum, plz, geburtsort, imageurl, cmsuserid');
  foreach ($persons as $arr) {
    unset($persons[$arr->id]->p_id);
    $persons[$arr->id]->auth = getAuthForPerson($arr->id);
  }
  return $persons;
}

/**
 * get log entries regarding person details for a person
 *
 * @param int $id          
 * @return array with db result
 */
function churchdb_getPersonDetailsLogs($id) {
  $arrs = "";
  $logs = db_query("SELECT datum, person_id, txt, level FROM {cdb_log}
                    WHERE domain_id=:pid AND domain_type='person'
                    ORDER BY datum DESC", 
                    array (':pid' => $id)
  );
  foreach ($logs as $arr) {
    $arrs[] = $arr;
  }
  return $arrs;
}

/**
 * get select field
 *
 * @param string $longtext          
 * @param string $shorttext          
 * @param string $column_name          
 * @param string $masterdata_selector          
 * @param string $eol          
 * @param unknown $auth          
 */
function getSelectField($longtext, $shorttext, $column_name, $masterdata_selector, $eol = '<br/>', $auth = null) {
  $res["type"]      = "select";
  $res["selector"]  = $masterdata_selector;
  $res["text"]      = $longtext; // Bei Editieren etc.
  $res["shorttext"] = $shorttext; // In der Ansicht
  $res["eol"]       = $eol ? $eol : "&nbsp;";
  $res["sql"]       = $column_name;
  $res["auth"]      = $auth;
  
  return $res;
}

/**
 * get additional DB fields
 *
 * @param id $category          
 * @return array containing arrays with field data
 */
function churchdb_getFields($feldkategorie_id) {
  $res = db_query("SELECT * FROM {cdb_feld} f, {cdb_feldtyp} ft WHERE 
                feldkategorie_id='". $feldkategorie_id. "' AND f.feldtyp_id=ft.id AND aktiv_yn=1
                ORDER BY feldkategorie_id, sortkey, langtext");
  $fields = array ();
  foreach ($res as $f) {
    $fields[$f->db_spalte]["type"]      = $f->intern_code;
    $fields[$f->db_spalte]["text"]      = $f->langtext; // Bei Editieren etc.
    $fields[$f->db_spalte]["shorttext"] = $f->kurztext; // In der Ansicht
    $fields[$f->db_spalte]["eol"]       = !empty($f->zeilenende) ? $f->zeilenende : "&nbsp;";
    $fields[$f->db_spalte]["sql"]       = $f->db_spalte;
    if ($f->autorisierung)         $fields[$f->db_spalte]["auth"]     = $f->autorisierung;
    if ($f->db_stammdatentabelle)  $fields[$f->db_spalte]["selector"] = $f->db_stammdatentabelle;
    if ($f->laenge)                $fields[$f->db_spalte]["length"]   = $f->laenge;
    if ($f->inneuerstellen_yn== 1) $fields[$f->db_spalte]["inneuerstellen_yn"] = 1;
    if ($f->del_when_move_to_archive_yn== 1) $fields[$f->db_spalte]["del_when_move_to_archive_yn"] = 1;
  }
  return $fields;
}

//TODO: was only used below, not needed?
//function _churchdb_getGroupMemberType($id, $bez, $kuerzel) {
//  $res["id"]=$id;
//  $res["bezeichnung"]=$bez;
//  $res["kuerzel"]=$kuerzel;
//  return $res;
//}

/**
 * get group member types
 *
 * @return array with group member types or null
 */
function getGroupMemberTypes() {
  $res = db_query("SELECT * FROM {cdb_gruppenteilnehmerstatus}");
  $arrs = null;
  foreach ($res as $arr) {
    $arrs[$arr->intern_code] = array (
      "id" => $arr->intern_code, 
      "bezeichnung" => $arr->bezeichnung, 
      "kuerzel" => $arr->kuerzel,
    );
  }
  return $arrs;
}

//TODO: was only used below, not needed?
//function _churchdb_getGroupFilterType($id, $bez) {
//  $res["id"]=$id;
//  $res["bezeichnung"]=$bez;
//  return $res;  
//}
  
/**
 * get group filter types
 * 
 * @return array
 */
function churchdb_getGroupFilterTypes() {
  $res[0] = array ("id" => 0, "bezeichnung" => 'in');
  $res[1] = array ("id" => 1, "bezeichnung" => 'nicht_in');
  $res[2] = array ("id" => 2, "bezeichnung" => 'war in');
  
  return $res;
}

/**
 * Check if at least one part of f.e.
 * " view all || leader " is in $permissions
 *
 * @param $auth string
 *          with optional || as OR combinations
 * @param array $permissions          
 * @return boolean
 */
function checkFieldAuth($auth, $permissions) {
  if ($auth == null) return true;
  
  foreach (explode('||', $auth) as $val) {
    if (isset($permissions[trim($val)])) return true;
  }
  return false;
}

/**
 * save data array
 * 
 * @param array $fields          
 * @param int $primary_key - id von person_id
 * @param array $data_arr          
 *
 * @return Gibt das alte Array zurueck
 */
function saveDataArray($fields, $primary_key, $data_arr) {
  global $user;
  
  $res = db_query("SELECT * FROM {". $fields["tablename"]. "} 
                   WHERE ". $fields["idname"]. "=". $primary_key);
  $old_arr = $res->fetch();
  
  $error_str = "";
  $auth = churchdb_getAuthForAjax();
  
  $person_id = null;
  if ($fields["tablename"] == "cdb_person" || $fields["tablename"] == "cdb_gemeindeperson") {
    if (churchdb_isPersonSuperLeaderOfPerson($user->id, $primary_key)) {
      $auth["leader"] = true;
      $auth["superleader"] = true;
    }
    else if (churchdb_isPersonLeaderOfPerson($user->id, $primary_key)) {
      $auth["leader"] = true;
    }
  }
  else if ($fields["tablename"]== "cdb_gruppe") {
    $myGroups = churchdb_getMyGroups($user->id, true, false, true);
    if (count($myGroups)) {
      $auth["superleader"] = true;
      $auth["leader"] = true;
    }
    else {
      $myGroups = churchdb_getMyGroups($user->id, true, true);
      if (count($myGroups)) {
        $auth["leader"] = true;
      }
    }
  }
  
  // TODO: use new db methods, with :params
  $sql = "UPDATE {". $fields["tablename"]. "} SET ";
  
  foreach ($data_arr as $key => $param) {
    if (isset($fields["fields"][$key])) {
      if (!isset($fields["fields"][$key]["auth"])|| checkFieldAuth($fields["fields"][$key]["auth"], $auth)) {
        $param = str_replace("'", "\'", $param);
        switch ($fields["fields"][$key]["type"]) {
          case "number":
            if ($param== "") $sql .= $fields["fields"][$key]["sql"]. "=null, ";
            else $sql .= $fields["fields"][$key]["sql"]. "=". $param. ", ";
            break;
          case "textarea":
          case "text":
          case "select":
            $sql .= $fields["fields"][$key]["sql"]. "='". $param. "', ";
            break;
          case "checkbox":
            $sql .= $fields["fields"][$key]["sql"]. "=". $param. ", ";
            break;
          case "date":
            if (($param!= "")&& ($param!= "null")) $sql .= $fields["fields"][$key]["sql"]. "='". $param. "', ";
            else $sql .= $fields["fields"][$key]["sql"]. "=null, ";
            break;
        }
      }
      else {
        $error_str .= "Fehlendes Recht ". $fields["fields"][$key]["auth"]. " fuer Update von Feld: ". $key. ". ";
      }
    }
  }
  if ($error_str) throw new CTException($error_str);
  
  // if no change date given set it to now()
  if (isset($data_arr['letzteaenderung'])) $sql .= " letzteaenderung='". $data_arr['letzteaenderung']. "',";
  else $sql .= " letzteaenderung=now(),";
  $sql .= " aenderunguser='". $user->cmsuserid. "' WHERE ". $fields["idname"]. "=". $primary_key;
  // cdb_log('Update sql:'.$sql,2,-1,CDB_LOG_PERSON,1);
  db_query($sql);
  return $old_arr;
}

/**
 * save geocodes for a person
 * 
 * TODO: return success
 * 
 * @param int $id
 * @param number $lat latitude
 * @param number $lng longitude
 */
function saveGeocodePerson($id, $lat, $lng) {
  db_update('cdb_person')
    ->fields(array ("geolat" => $lat, "geolng" => $lng))
    ->condition("id", $id, "=")
    ->execute();
}

/**
 * save geocodes for a group
 * 
 * TODO: return success
 * 
 * @param int $id
 * @param number $lat latitude
 * @param number $lng longitude
 */
function saveGeocodeGruppe($id, $lat, $lng) {
  db_update('cdb_gruppe')
    ->fields(array ("geolat" => $lat, "geolng" => $lng))
    ->condition("id", $id, "=")
    ->execute();
}

/**
 * create address
 * 
 * @param array $params
 * @return array (result => ?, id => ?)
 */
function createAddress($params) {
  global $user;
  
  if ((!isset($params["Inputf_dep"]))|| (!isset($params["Inputf_status"]))) {
    return "Error, some Input missing!!";
  }
  if (!isset($params["Inputf_station"])) $params["Inputf_station"] = 0;
  if (!isset($params["vorname"]) && isset($params["givenname"])) $params["vorname"] = $params["givenname"];
  
  $count = db_query("SELECT count(*) c FROM {cdb_person}")->fetch();
  
  if (getConf('churchdb_maxuser', '100000') * 1 <= $count->c * 1) { //why * 1?
    $res["result"] = "Maximale Anzahl von Benutzern erreicht. Erlaubt sind ". getConf('churchdb_maxuser', '50');
    return $res;
  }

  $obj = db_query("SELECT id FROM {cdb_person} 
                   WHERE vorname LIKE :vorname AND name LIKE :name", 
                   array (":vorname" => (isset($params["vorname"]) ? $params["vorname"]. "%" : ""), 
                          ":name"    => (isset($params["name"])    ? $params["name"]. "%"    : "")
                   ))->fetch();
  // existing person    
  if (empty($params["force"]) && isset($obj->id)) {
    $arr["result"] = "exist";
    $arr["id"] = $obj->id;
  }
  else {
    $dt = new DateTime();
    
    // check f_address fields
    $fields = getAllFields();
    $save = array();
    foreach ($fields["f_address"]["fields"] as $field) {
      if (isset($params[$field["sql"]])) $save[$field["sql"]] = $params[$field["sql"]];
    }
    if (empty($save["createdate"])) $save["createdate"] = $dt->format('Y-m-d H:i:s');
    if (empty($save["aenderunguser"])) $save["aenderunguser"] = $user->cmsuserid;
    if (empty($save["letzteaenderung"])) $save["letzteaenderung"] = $dt->format('Y-m-d H:i:s');
    $id = db_insert('cdb_person')->fields($save)->execute(); 
    
    // check f_church fields (information)
    $save = array ();
    foreach ($fields["f_church"]["fields"] as $field) {
      if (isset($params[$field["sql"]])) $save[$field["sql"]] = $params[$field["sql"]];
    }
    $save["person_id"] = $id;
    if (empty($save["erstkontakt"])) $save["erstkontakt"] = $dt->format('Y-m-d H:i:s');
    if (empty($save["status_id"])) $save["status_id"] = $params["Inputf_status"];
    if (empty($save["station_id"])) $save["station_id"] = $params["Inputf_station"];
    db_insert('cdb_gemeindeperson')->fields($save)->execute(); 
    
    db_query("INSERT INTO {cdb_bereich_person} (person_id, bereich_id) 
              VALUES ($id, ". $params["Inputf_dep"]. ")");
    
    $arr["result"] = "ok"; // result was not really checked :-(
    $arr["id"] = $id;
  }
  return $arr;
}

/**
 * delete group and all group realtions
 * 
 * @param int $g_id
 * @return string
 */
function deleteGroup($g_id) {
  db_query("DELETE FROM {cdb_gemeindeperson_gruppe} 
            WHERE gruppe_id=:id", 
            array (":id" => $g_id));
  
  db_query("DELETE FROM {cdb_gemeindeperson_gruppe_archive}
            WHERE gruppe_id=:id", 
            array (":id" => $g_id));
  
  db_query("DELETE FROM {cdb_gruppe} 
            WHERE id=:id", 
            array (":id" => $g_id));
  
  db_query("DELETE FROM {cdb_gruppe_tag} 
            WHERE gruppe_id=:id", 
            array (":id" => $g_id));
  
  db_query("DELETE FROM {cc_domain_auth} 
            WHERE domain_type='gruppe' AND domain_id=:id", 
            array (":id" => $g_id));
  
  return "ok";
}

/**
 * create group 
 * @param string $name
 * @param int $grouptype
 * @param int $district
 * @param unknown $force bolean?
 * @return string
 */
function createGroup($name, $grouptype, $district, $force) {
  global $user;
  $obj = db_query("SELECT id FROM {cdb_gruppe} 
                   WHERE bezeichnung LIKE '$name'")
                   ->fetch();
  if (!$force && isset($obj->id)) {
    $arr["result"] = "exist";
    $arr["id"] = $obj->id;
  }
  else {
    $dt = new DateTime();
    $fields = array(
        "bezeichnung" => $name, 
        "treffzeit" => "", 
        "treffpunkt" => "", 
        "treffname" => "", 
        "zielgruppe" => "", 
        "notiz" => "", 
        "instatistik_yn" => 0, 
        "treffen_yn" => 0, 
        "geolat" => "", 
        "geolng" => "", 
        "distrikt_id" => $district, 
        "gruppentyp_id" => $grouptype, 
        "aenderunguser" => $user->cmsuserid, 
        "letzteaenderung" => $dt->format('Y-m-d H:i:s'),
    );
    $arr["id"] = db_insert('cdb_gruppe')->fields($fields)->execute();
    
    /*
      $sql="SELECT max(id) as id FROM {cdb_gruppe} 
      WHERE bezeichnung LIKE '".$name."'";
      $obj=db_query($sql)->fetch(); 
      $id=$obj->id;
     */
    $arr["result"] = "ok";
  }
  return $arr;
}

/**
 * get all members of a group
 * 
 * TODO: is this used fom another function beside the next 3?
 * if yes, why not get only needed persons by adding an additionalWhere parameter?
 * else include it into informLeaderAboutChangedGroupMember
 * 
 * @param int $group_id
 * @return array with persons
 */
function _churchdb_getMembersOfGroup($group_id) {
  $res = db_query(
    "SELECT p.name, p.vorname, p.email, p.id p_id, p.lastlogin, gp.id id, gpg.status_no, g.bezeichnung, 
       gpg.aenderunguser, DATE_FORMAT(gpg.letzteaenderung, '%d.%m.%Y') letzteaenderung, cmsuserid, gpg.comment
     FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppe} g 
     WHERE p.id=gp.person_id AND gp.id=gpg.gemeindeperson_id AND g.id=gpg.gruppe_id AND gpg.gruppe_id=$group_id"
  );
  foreach ($res as $p) $persons[$p->id] = $p;
  
  return $persons;
}

/**
 * inform leader about new group member and sends an additional text if available
 * 
 * TODO: use language dependent templates for email text and put this and the next 
 * two functions together as informLeaderAboutChangedGroupMember
 */
function informLeaderAboutNewGroupMember($group_id, $gp_id, $add_text = null) {
  global $base_url, $user;
  //why not get only needed persons by adding an additionalWhere option to _churchdb_getMembersOfGroup?
  $persons = _churchdb_getMembersOfGroup($group_id); 
  foreach ($persons as $p) {
    // if person had logged in in the past and is one of leader(1), coleader(2) or supervisor(3) 
    // and it was not changed by current user
    if (!empty($p->lastlogin) && $p->status_no >= 1 && $p->status_no <= 3 && !empty($p->email) &&
       (!empty($p->cmsuserid) || ($p->cmsuserid != $user->cmsuserid))) {
      
      $mt = getGroupMemberTypes();

      $content = "<h3>Hallo ". $mt[$p->status_no]["bezeichnung"]. "!</h3><p>";
      if ($p->status_no< 3) $content .= "In Deine ";
      else $content .= "In die ";
      $content .= "Gruppe \"". $p->bezeichnung. "\" wurde von ". $persons[$gp_id]->aenderunguser. " ein <i>".
           $mt[$persons[$gp_id]->status_no]["bezeichnung"]. "</i> hinzugef&uuml;gt:";
      $content .=  "<p>Name: ". $persons[$gp_id]->vorname. " ". $persons[$gp_id]->name;
      if ($add_text!= null) $content = $content. "<p>". $add_text;
      $content .= '<p><p><a class="btn btn-royal" href="'. $base_url. '?q=churchdb#PersonView/searchEntry:'.
           $persons[$gp_id]->p_id. '">Person ansehen</a>';
      
      churchdb_send_mail("[". getConf('site_name'). "] Neuer Teilnehmer in der Gruppe ".
           $p->bezeichnung, $content, $p->email);
    }
  }
}

/**
 * inform leader about edited group member and sends an additional text if available
 */
function informLeaderAboutEditedGroupMember($group_id, $gp_id, $add_text = null) {
  global $base_url, $user;
  $personen = _churchdb_getMembersOfGroup($group_id);
  foreach ($personen as $val) {
    // Wenn es Leiter (1), CoLeiter (2) oder SuperVisor (3) sind und ich nicht selber der bin, der es ge�ndert hat
    if (($val->lastlogin != null) && ($val->status_no >= 1) && ($val->status_no <= 3) && ($val->email != null) &&
         ($val->email != "") && (($val->cmsuserid == null || ($val->cmsuserid != $user->cmsuserid)))) {
      $mt = getGroupMemberTypes();
      $content = "<h3>Hallo " . $mt[$val->status_no]["bezeichnung"] . "!</h3>";
      if ($val->status_no < 3) $content .= "<p>In Deiner ";
      else $content .= "<p>In der ";
      $content .= "Gruppe \"" . $val->bezeichnung . "\" wurde von " . $personen[$gp_id]->aenderunguser .
           " der Teilnehmerstatus angepasst:";
      $content .= "<p>Name: " . $personen[$gp_id]->vorname . " " . $personen[$gp_id]->name;
      $content .= "<p>Teilnehmerstatus: " . $mt[$personen[$gp_id]->status_no]["bezeichnung"];
      $content .= "<p>Datum: " . $personen[$gp_id]->letzteaenderung;
      if ($add_text != null) $content .= "<p>" . $add_text;
      
      $content .= '<p><p><a class="btn btn-royal" href="' . $base_url . '?q=churchdb#PersonView/searchEntry:' .
           $personen[$gp_id]->p_id . '">Person ansehen</a>';
      
      churchdb_send_mail("[" . getConf('site_name') . "] Teilnehmerstatus in der Gruppe " .
           $val->bezeichnung . " angepasst", $content, $val->email);
    }
  }
}

/**
 * inform leader about deleted group member and sends an additional text if available
 */
function informLeaderAboutDeletedGroupMember($group_id, $gp_id) {
  global $base_url, $user;
  $personen = _churchdb_getMembersOfGroup($group_id);
  foreach ($personen as $val) {
    // Wenn es Leiter (1), CoLeiter (2) oder SuperVisor (3) sind und ich nicht selber der bin, der es ge�ndert hat
    // und ich nicht der bin, der gerade gel�scht wurde!
    if (($val->lastlogin != null) && ($val->status_no >= 1) && ($val->status_no <= 3) && ($val->email != null) &&
         ($val->email != "") && ($gp_id != $val->id) && (($val->cmsuserid == null || ($val->cmsuserid != $user->cmsuserid)))) {
      $mt = getGroupMemberTypes();
      $content = "<h3>Hallo " . $mt[$val->status_no]["bezeichnung"] . "!</h3>";
      if ($val->status_no < 3) $content .= "<p>In Deiner ";
      else $content .= "<p>In der ";
      $content .= "Gruppe \"" . $val->bezeichnung . "\" wurde von " . $user->cmsuserid . " ein " .
           $mt[$personen[$gp_id]->status_no]["bezeichnung"] . " entfernt:";
      $content .= "<p>Name: " . $personen[$gp_id]->vorname . " " . $personen[$gp_id]->name;
      
      $content .= '<p><p><a class="btn btn-royal" href="' . $base_url . '?q=churchdb#PersonView/searchEntry:' .
           $personen[$gp_id]->p_id . '">Person ansehen</a>';
      
      churchdb_send_mail("[" . getConf('site_name') . "] Teilnehmer in der Gruppe " . $val->bezeichnung .
           " entfernt", $content, $val->email);
    }
  }
}

/**
 * put person in group by adding a group - person relation
 * 
 * TODO: rename to addMembership or addGroupMembership
 * 
 * @param int $p_id
 * @param int $g_id
 * @param int $leader
 * @param string $date
 * @param int $followup
 * @param int $followup_erfolglos_zurueck_gruppen_id
 * @param string $comment
 * 
 * @return string
 */
function churchdb_addPersonGroupRelation($p_id, $g_id, $leader, $date, $followup, $followup_erfolglos_zurueck_gruppen_id, $comment) {
  global $user;
  $gp_id = _churchdb_getGemeindepersonIdFromPersonId($p_id);
  if (!$date) {
    $dt = new DateTime();
    $date = $dt->format('Y-m-d H:i:s');
  }
  if (empty($user->cmsuserid)) $user->cmsuserid = "anonymous";
  $fields = array (
      'gemeindeperson_id' => $gp_id, 
      'gruppe_id' => $g_id, 
      'status_no' => $leader, 
      'letzteaenderung' => $date, 
      'aenderunguser' => $user->cmsuserid, 
      'followup_count_no' => $followup, 
      'followup_erfolglos_zurueck_gruppen_id' => $followup_erfolglos_zurueck_gruppen_id, 
      'comment' => $comment
  );
  try {
    db_insert('cdb_gemeindeperson_gruppe')
    ->fields($fields)
    ->execute(false);
  }
  catch (Exception $e) {
    return "Fehler: ". $e;
  }
  
  $info = getGroupInfo($g_id);
  cdb_log("Neu: $info->gruppentyp $info->gruppe (P$p_id:G$g_id, ". "Leiter". ": $leader)", 2, $gp_id, CDB_LOG_PERSON, 1);
  $automail = chuchdb_sendAutomaticGroupEMail($g_id, $p_id, $leader);
  if ((getConf('churchdb_sendgroupmails', true)) && ($info->mail_an_leiter_yn == 1)) {
    $txt = "";
    if ($comment) $txt .= '<p>Kommentar: <i>'. $comment. '</i>';
    if ($automail) $txt .= '<p>Eine automatische E-Mail wurde an die Person gesendet: <i>"'. $automail. '"</i>';
    informLeaderAboutNewGroupMember($g_id, $gp_id, $txt);
  }
  return "ok";
}

/**
 * send group specific welcome mail if available
 * TODO: rename to editMembership or editGroupMembership
 * not tested!
 * 
 * @param unknown $g_id
 * @param unknown $p_id
 * @param unknown $leader
 * 
 * @return string mail subject or nothing
 */
function chuchdb_sendAutomaticGroupEMail($g_id, $p_id, $leader) {
  $res = db_query("SELECT * FROM {cdb_gruppenteilnehmer_email} 
                   WHERE gruppe_id=$g_id AND status_no=$leader AND aktiv_yn=1")
                   ->fetch();
  if (!$res) return false;
  
  $p = db_query("SELECT email FROM {cdb_person} 
                 WHERE id=:id", 
                 array (":id" => $res->sender_pid))
                 ->fetch();
  if (!$p) return false;
  
  churchcore_sendEMailToPersonIds($p_id, $res->email_betreff, $res->email_inhalt, $p->email, true, false);
  return $res->email_betreff;
}

/**
 * edit group membership
 * 
 * @param int $p_id
 * @param int $g_id
 * @param int $leader
 * @param string $date
 * @param int $followup
 * @param string $comment
 * 
 * @return string
 */
function _churchdb_editPersonGroupRelation($p_id, $g_id, $leader, $date, $followup, $comment) {
  global $user;
  $gp_id = _churchdb_getGemeindepersonIdFromPersonId($p_id);
  if (!$date) {
    $dt = new DateTime();
    $date = $dt->format('Y-m-d H:i:s');
  }
  $info_rel = getPersonGroupRelation($gp_id, $g_id);
  $fields = array (
      'gemeindeperson_id' => $gp_id, 
      'gruppe_id' => $g_id, 
      'status_no' => $info_rel->status_no, 
      'letzteaenderung' => $info_rel->letzteaenderung, 
      'comment' => $info_rel->comment,
      'aenderunguser' => $info_rel->aenderunguser
  );
  db_insert('cdb_gemeindeperson_gruppe_archive')->fields($fields)->execute();
  
  if (!db_query("UPDATE {cdb_gemeindeperson_gruppe} 
                 SET status_no=$leader, letzteaenderung='$date', followup_count_no=$followup, 
                     aenderunguser='". $user->cmsuserid. "', comment='$comment' 
                 WHERE gemeindeperson_id=$gp_id AND gruppe_id=$g_id")) {
     return "error by updateing gemeindeperson_gruppe";
  }
  
  $info = getGroupInfo($g_id);
  cdb_log("Aktualisiere: ". $info->gruppentyp. " ". $info->gruppe. " (P". $p_id. ":G". $g_id. " Leiter:". $leader. ")", 2, $p_id, CDB_LOG_PERSON, 1);
  $automail = null;
  if ($info_rel->status_no != $leader) {
    $automail = chuchdb_sendAutomaticGroupEMail($g_id, $p_id, $leader);
  }
  if (getConf('churchdb_sendgroupmails', true) && ($info->mail_an_leiter_yn == 1)) {
    $txt = "";
    if ($comment)  $txt .= '<p>Kommentar: <i>'. $comment. '</i>';
    if ($automail) $txt .= '<p>Eine automatische E-Mail wurde an die Person gesendet: <i>"'. $automail. '"</i>';
    informLeaderAboutEditedGroupMember($g_id, $gp_id, $txt);
  }
  return "ok";
}

/**
 * delete group membership
 * 
 * @param int $p_id
 * @param int $g_id
 *
 * @return string
 */
function _churchdb_delPersonGroupRelation($p_id, $g_id) {
  global $user;
  $gp_id = _churchdb_getGemeindepersonIdFromPersonId($p_id);
  $info_rel = getPersonGroupRelation($gp_id, $g_id);

  db_insert('cdb_gemeindeperson_gruppe_archive')
    ->fields(array (
        'gemeindeperson_id' => $gp_id, 
        'gruppe_id' => $g_id, 
        'status_no' => $info_rel->status_no, 
        'letzteaenderung' => $info_rel->letzteaenderung, 
         'aenderunguser' => $info_rel->aenderunguser,
         'comment' => $info_rel->comment
        ))
    ->execute();
  
  // add info about archiving
  $dt = new DateTime();
  db_insert('cdb_gemeindeperson_gruppe_archive')
    ->fields(array (
        'gemeindeperson_id' => $gp_id, 
        'gruppe_id' => $g_id, 
        'status_no' => -99, 
        'letzteaenderung' => $dt->format('Y-m-d H:i:s'), //does new DateTime()->format('Y-m-d H:i:s') work?
          'aenderunguser' => $user->cmsuserid,
         'comment' => $info_rel->comment
    ))
    ->execute();
  
  $info = getGroupInfo($g_id);
  if (getConf('churchdb_sendgroupmails', true) && $info->mail_an_leiter_yn == 1) 
    informLeaderAboutDeletedGroupMember($g_id, $gp_id);
  
  db_query("DELETE FROM {cdb_gemeindeperson_gruppe} 
            WHERE gemeindeperson_id=$gp_id AND gruppe_id=$g_id");
  cdb_log("Entferne: ". $info->gruppentyp. " ". $info->gruppe. " (P". $p_id. ":G". $g_id. " Leiter:".
       $info_rel->status_no. ")", 2, $p_id, CDB_LOG_PERSON, 1);
  
  return "ok";
}

/**
 * save department
 * 
 * @param array $params
 * @return string
 */
function saveBereich($params) {
  global $user;
  $arr = churchdb_getAllowedDeps();
  
  foreach ($arr as $dep) {
    if (isset($params["bereich". $dep])) {
      if ($params["bereich". $dep] == 0) 
        db_query("DELETE FROM {cdb_bereich_person} 
                  WHERE person_id=:id AND bereich_id=:bereich_id", 
                  array(":id" => $params["id"], ":bereich_id" => $dep));
      
      else db_query("INSERT INTO {cdb_bereich_person} VALUES ($dep, :id) 
                     ON DUPLICATE KEY UPDATE bereich_id=bereich_id", 
                     array(':id' => $params["id"]));
    }
  }
  return "ok";
}

/**
 * save image for person
 * delete existing image file and updates image url in DB?
 * 
 * @param int $id
 * @param string $url
 * @return string ok
 */
function saveImage($id, $url) {
  global $files_dir;
  $p = db_query("SELECT imageurl FROM {cdb_gemeindeperson} 
                 WHERE person_id=:id", array (":id" => $id))
                 ->fetch();
  if ($p->imageurl && file_exists($files_dir. "/fotos/". $p->imageurl)) unlink($files_dir. "/fotos/". $p->imageurl);
  
  if (empty($url)) db_query("UPDATE {cdb_gemeindeperson} 
                             SET imageurl=null 
                             WHERE person_id=$id");
  
  else db_query("UPDATE {cdb_gemeindeperson} 
                 SET imageurl='$url'
                 WHERE person_id=$id");
  return "ok";
}

/**
 * save note to a relation
 * TODO: add :params
 * 
 * @param int $rel_id, relation id
 * @param string $note
 * @param int $cmt_viewer, default = 0 (means all), id for auth to view this note
 * @param string $rel_name, default = "person"
 * @return string ok
 */
function saveNote($rel_id, $note, $cmt_viewer = 0, $rel_name = "person") {
  global $user;
  db_query("INSERT INTO {cdb_comment} (relation_id, relation_name, text, person_id, datum, comment_viewer_id) 
            VALUES (". $rel_id. ", '". $rel_name."', '". $note. "', '". $user->id. "', now(), $cmt_viewer)");
  
  return "ok";
}

/**
 * send mail using churchcore_systemmail()
 * 
 * @param string $subject
 * @param string $message
 * @param string $to, emails, comma separated
 */
function churchdb_send_mail($subject, $message, $to) {
  churchcore_systemmail($to, $subject, $message, true);
}

/**
 * send noteification for changed field
 * 
 * @param string $field
 * @param string $txt
 */
function sendFieldNotifications($field, $txt) {
  global $user;
  $arr = getAllMailNotifys();
  if (isset($arr[$field]) && $txt!= null) {
    $txt = "<p>Information:<p>". $txt. "<p>Anpassungen von $user->cmsuserid";
    churchdb_send_mail("[". getConf('site_name'). "] Info Anpassungen in $field", $txt, $arr[$field]->emails);
  }
}

/**
 * get all fields
 * 
 * @param string $where
 * @return array
 */
function getAllFields($where = "1=1") {
  $db = db_query("SELECT * FROM {cdb_feldkategorie} WHERE $where");
  $res = array ();
  foreach ($db as $row) {
    $fk = array ();
    $fk["tablename"] = $row->db_tabelle;
    $fk["arrayname"] = $row->intern_code;
    $fk["idname"] = $row->id_name;
    $fk["text"] = $row->bezeichnung;
    $fk["fields"] = churchdb_getFields($row->id);
    
    if ($where!= "1=1") return $fk;
    
    $res[$row->intern_code] = $fk;
  }
  return $res;
}

/**
 * get module path
 * @return string
 */
function churchdb_getModulesPath() {
  return CHURCHDB;
}

/**
 * get masterdata tablenames
 * 
 *   $res[1] = array( "id"          => $id,
 *                    "bezeichnung" => $bezeichnung,
 *                    "shortname"   => $shortname,
 *                    "tablename"   => $tablename,
 *                    "sql_order"   => $sql_order,
 *             );
 *
 * @return array
 */
function churchdb_getMasterDataTablenames() {
  $res = array ();  
  // don't change numbers, statistic is correlated with it!
  $res[1] = churchcore_getMasterDataEntry(1, "Status", "status", "cdb_status");
  $res[1]["special_func"] = array ("name" => "Berechtigungen", "image" => "schluessel", "func" => "editAuth");
  $res[2] = churchcore_getMasterDataEntry(2, "Station", "station", "cdb_station");
  $res[3] = churchcore_getMasterDataEntry(3, "Bereich", "dep", "cdb_bereich");
  $res[4] = churchcore_getMasterDataEntry(4, "Geschlecht", "sex", "cdb_geschlecht");
  $res[5] = churchcore_getMasterDataEntry(5, "Gruppen-Typen", "groupTypes", "cdb_gruppentyp");
  $res[6] = churchcore_getMasterDataEntry(6, "Familienstand", "familyStatus", "cdb_familienstand");
  $res[7] = churchcore_getMasterDataEntry(7, "Distrikt", "districts", "cdb_distrikt", "bezeichnung");
  $res[8] = churchcore_getMasterDataEntry(8, "Beziehungstyp", "relationType", "cdb_beziehungstyp");
  $res[9] = churchcore_getMasterDataEntry(9, "Kommentare-Viewer", "comment_viewer", "cdb_comment_viewer", "bezeichnung");
  $res[10] = churchcore_getMasterDataEntry(10, "FollowUp-Typen", "followupTypes", "cdb_followup_typ", "id");
  $res[11] = churchcore_getMasterDataEntry(11, "FollowUp-Typen-Intervalle", "followupTypIntervall", "cdb_followup_typ_intervall", "followup_typ_id,count_no");
  $res[13] = churchcore_getMasterDataEntry(13, "Nationalitaet", "nationalitaet", "cdb_nationalitaet");
  $res[14] = churchcore_getMasterDataEntry(14, "Gruppenteilnehmestatus", "groupmembertypes", "cdb_gruppenteilnehmerstatus", "bezeichnung");
  // $res[14]=churchcore_getMasterDataEntry(14, "Newsletter", "newsletter", "cdb_newsletter");
  
  return $res;
}

/**
 * get masterdata tables
 * 
 * @return array
 */
function churchdb_getMasterDataTables() {
  $tables = churchdb_getMasterDataTablenames();
  foreach ($tables as $t) {
    $res[$t["shortname"]] = churchcore_getTableData($t["tablename"], $t["sql_order"]);
    // auth data needed?
    if (isset($t["special_func"]) && $t["special_func"]["func"] == "editAuth") {
      foreach ($res[$t["shortname"]] as $data) {
        $data->auth = getAuthForDomain($data->id, $t["shortname"]);
      }
    }
  }
  return $res;
}

/**
 * get tag relations
 * @return array with tags
 */
function getTagRelations() {
  $res = db_query("SELECT person_id id, tag_id 
                   FROM {cdb_gemeindeperson_tag} gpt, {cdb_gemeindeperson} gp
                   WHERE gpt.gemeindeperson_id=gp.id");
  $arrs = null;
  foreach ($res as $arr) $arrs[] = $arr;

  return $arrs;
}

/**
 * get old group relation data, if user is allowed to view history
 * @return array
 */
function getOldGroupRelations() {
  if (!user_access("view history", "churchdb")) return null;
  
  $res = db_query("SELECT gp.person_id id, gpa.gruppe_id gp_id, status_no leiter, gpa.letzteaenderung d, 
                   gpa.aenderunguser user, gpa.comment 
                   FROM {cdb_gemeindeperson_gruppe_archive} gpa, {cdb_gemeindeperson} gp 
                   WHERE gpa.gemeindeperson_id=gp.id ORDER BY gpa.letzteaenderung DESC");
  $arrs = null;
  foreach ($res as $arr) $arrs[] = $arr;

  return $arrs;
}

/**
 * get user settings for churchDB
 * 
 * @param int $user_pid
 * @return array
 */
function churchdb_getUserSettings($user_pid) {
  $arr = churchcore_getUserSettings("churchdb", $user_pid);
  if (empty($arr["mailerType"])) $arr["mailerType"] = 0;
  if (empty($arr["mailerSeparator"])) {
    if (getConf('churchdb_emailseparator', ';') == ';') $arr["mailerSeparator"] = 0;
    else $arr["mailerSeparator"] = 1;
  }
  return $arr;
}

/**
 * get person(s) by id
 * 
 * @param int $id, more then one comma separated
 * @return json data with persons
 */
function _churchdb_getPersonById($id) {
  global $user;
  
  // check for view all permisson
  $auth = user_access("view alldata", "churchdb");
  $data = null;
  if ($auth) {
    // get matching persons by departement and id
    $res = db_query("SELECT p.name, p.vorname, p.id, p.cmsuserid,  p.email, p.telefonprivat, p.telefongeschaeftlich, p.telefonhandy, gp.imageurl
        FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_bereich_person} bp 
        WHERE p.archiv_yn=0 AND bp.person_id=p.id AND gp.person_id=p.id AND bp.bereich_id IN (". db_implode($auth). ") AND p.id IN ($id)");
    foreach ($res as $p) $data[$p->id] = $p;
  }
  // get groups i have view permission for
  $g_ids = churchdb_getMyGroups($user->id, true, false);
  if ($g_ids) {
    // get matching persons from this groups
    $res = db_query(
         "SELECT p.name, p.vorname, p.id, p.cmsuserid, p.email, p.telefonprivat, p.telefongeschaeftlich, p.telefonhandy, gp.imageurl
          FROM {cdb_gemeindeperson} gp, {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg
          WHERE p.archiv_yn=0 AND gpg.gemeindeperson_id = gp.id AND gp.person_id = p.id
            AND gpg.gruppe_id IN (". db_implode($g_ids). ")
            AND p.id IN ($id) ORDER BY p.vorname, p.name");
    foreach ($res as $p) if (!isset($data[$p->id])) { 
      $data[$p->id] = $p; // if person not already inserted, add them
    }
  }
  $arrs["data"] = $data;
  $arrs["result"] = "ok";
  return $arrs;
}

/**
 * Holt sich eine Person entweder in den Gruppen in denen ich auch bin oder die Bereiche, wo ich ViewAll habe.
 *
 * @param string $searchpattern 
 * @param bool $withMyDepartemtnt, default=false; search also in my department, even if i dont have view all there?
 * 
 * @return array  (name => "surname name", id => 123)
 */
function _churchdb_getPersonByName($searchpattern, $withMyDepartment = false) {
  global $user;
  // check for view all permisson
  $auth = user_access("view alldata", "churchdb");
  
  if ($withMyDepartment) {
    if ($auth) $auth = array_merge($auth, churchdb_getAllowedDeps());
    else $auth = churchdb_getAllowedDeps();
  }
  
  $data = null;
  if ($auth) {
    // get matching persons by departement and searchpattern
    $res = db_query(
       "SELECT p.*, gp.imageurl 
        FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_bereich_person} bp 
        WHERE p.archiv_yn=0 AND bp.person_id=p.id AND gp.person_id=p.id AND bp.bereich_id IN (". db_implode($auth). ") 
          AND (UPPER(name) LIKE UPPER('". $searchpattern. "%') OR UPPER(vorname) LIKE UPPER('". $searchpattern. "%')
               OR (CONCAT(UPPER(vorname),' ',UPPER(name)) LIKE UPPER('". $searchpattern. "%') )
          OR (CONCAT(UPPER(spitzname),' ',UPPER(name)) LIKE UPPER('". $searchpattern. "%') )
          OR (UPPER(email) LIKE UPPER('". $searchpattern. "%') )
        ) ORDER BY vorname, name");
    
    foreach ($res as $p) {
      $data[$p->id]["id"] = $p->id;
      if ($p->spitzname) {
        $data[$p->id]["name"] = "$p->vorname ($p->spitzname) $p->name";
        $data[$p->id]["shortname"] = "$p->spitzname $p->name";
      }
      else {
        $data[$p->id]["name"] = $p->vorname. " ". $p->name;
        $data[$p->id]["shortname"] = "$p->vorname $p->name";
      }
      $data[$p->id]["imageurl"] = $p->imageurl;
    }
  }
  // get groups i have view permission for
  $g_ids = churchdb_getMyGroups($user->id, true, false);
  // get matching persons from this groups
  if (count($g_ids)) {
    $res = db_query("SELECT p.name, p.vorname, p.id, gp.imageurl
          FROM {cdb_gemeindeperson} gp, {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg
          WHERE p.archiv_yn=0 AND gpg.gemeindeperson_id = gp.id AND gp.person_id = p.id
            AND gpg.gruppe_id IN (". db_implode($g_ids). ")
            AND (UPPER(p.vorname) LIKE UPPER('". $searchpattern. "%') 
            OR UPPER(p.vorname) LIKE UPPER('". $searchpattern. "%')) 
          ORDER BY p.vorname, p.name");
    foreach ($res as $p) if (!isset($data[$p->id])) { // if person not already inserted, add them
      $arr = array ();  // TODO: why not $data[$p->id] here? If not important replace with code below
      $arr["id"] = $p->id;
      $arr["name"] = $p->vorname. " ". $p->name;
      $arr["imageurl"] = $p->imageurl;
      $data[] = $arr;
//       $data[$p->id]["id"] = $p->id;
//       $data[$p->id]["name"] = $p->vorname. " ". $p->name;
//       $data[$p->id]["imageurl"] = $p->imageurl;
    }
  }
  $arrs["result"] = "ok";
  $arrs["data"] = $data;
  return $arrs;
}

/**
 * geth auth for ajax
 * @return array with auth data
 */
function churchdb_getAuthForAjax() {
  global $config;
  $auth = $_SESSION["user"]->auth["churchdb"];
  $allowedDeps = churchdb_getAllowedDeps();
  $res["dep"] = churchcore_getTableData("cdb_bereich", "", "id IN (". db_implode($allowedDeps). ")");
  if (isset($auth["view comments"])) foreach ($auth["view comments"] as $key => $value) {
    $res["comment_viewer"][$key] = $value;
  }
  
  if (isset($auth["view address"])) $res["viewaddress"] = true;
  if (isset($auth["view alldetails"])) {
    $res["viewaddress"] = true;
    $res["viewalldetails"] = true;
  }
  if (isset($auth["view statistics"])) $res["viewstats"] = true;
  if (isset($auth["view history"])) $res["viewhistory"] = true;
  if (isset($auth["view tags"])) $res["viewtags"] = true;
  
  if (isset($auth["edit groups"])) $res["editgroups"] = true;
  if (isset($auth["edit relations"])) $res["editrelations"] = true;
  
  if (isset($auth["export data"])) $res["export"] = true;
  
  if (isset($auth["write access"])) $res["write"] = true;
  
  if (isset($auth["create person"])) $res["create person"] = true;
  
  if (isset($auth["create person without agreement"])) $res["create person without agreement"] = true;
  
  if (isset($auth["view archive"])) {
    $res["viewarchive"] = true;
  }
  if (isset($auth["push/pull archive"])) $res["push/pull archive"] = true;
  
  if (isset($auth["edit masterdata"])) {
    $res["admin"] = true;
    $res["read"] = true;
    $res["write"] = true;
    $res["export"] = true;
    $res["viewalldata"] = true;
    $res["viewalldetails"] = true;
    $res["viewhistory"] = true;
    $res["viewtags"] = true;
    $res["editgroups"] = true;
    $res["editrelations"] = true;
    $res["viewstats"] = true;
    $res["groupstats"] = true;
    $res["admingroups"] = true;
    $res["write"] = true;
  }
  if (isset($auth["administer groups"])) {
    $res["admingroups"] = true;
    $res["editgroups"] = true;
  }
  else if (isset($auth["view group"])) $res["viewgroups"] = $auth["view group"];
  if (isset($auth["view group statistics"]))  $res["viewgroupstats"] = true;
  
  // TODO: here must be differentiated by department
  if (isset($auth["view alldata"])) {
    $res["viewalldata"] = true;
  }
  
  if (user_access("complex filter", "churchdb")) $res["complex filter"] = true;
  if (user_access("administer persons", "churchcore")) $res["adminpersons"] = true;
  if (isset($auth["edit newsletter"])) $res["newsletter"] = $auth["edit newsletter"];
  if (isset($auth["send sms"]) && $config["churchdb_smspromote_apikey"]) $res["sendsms"] = true;
  if (!empty($config["churchdb_changeownaddress"]) && $config["churchdb_changeownaddress"] == 1) $res["changeownaddress"] = true;
  
  return $res;
}

/**
 * get auth table
 * @return array
 */
function churchdb_getAuthTable() {
  $res = getAuthTable();
  $auth = null;
  foreach ($res as $entry) {
    $auth[$entry->modulename][$entry->auth] = $entry;
  }
  return $auth;
}

/**
 * save domain auth
 * @param array $params
 * @return string
 */
function churchdb_saveDomainAuth($params) {
  db_query("DELETE FROM {cc_domain_auth}
            WHERE domain_id=". $params["id"]. " AND domain_type='". $params["domain_type"]. "'");
  foreach ($params as $key => $val) {
    if ($val && strpos($key, "authid") === 0) {
      $key = substr($key, 6, 99);
      $pos = strpos($key, "_");
      $fields = array (
          "domain_id" => $params["id"], 
          "domain_type" => $params["domain_type"], 
          "auth_id" => $key,
      );
      if ($pos > 0) $fields["daten_id"] = substr($key, $pos+ 1, 99);
      db_insert("cc_domain_auth")->fields($fields)->execute();
    }
  }
  return "ok";
}

/**
 * add person auth
 * 
 * @param int $id
 * @param int $auth_id
 * @return string
 */
function churchdb_addPersonAuth($id, $auth_id) {
  $fields = array (
      "domain_id" => $id, 
      "domain_type" => "person", 
      "auth_id" => $auth_id,
  );
  db_insert("cc_domain_auth")->fields($fields)->execute();
  return "ok";
}

/**
 * deactivate person
 * @param int $id
 */
function churchdb_deactivatePerson($id) {
  // remove permissions of person
  db_query("DELETE FROM {cc_domain_auth} 
            WHERE domain_type='person' AND domain_id=:id", 
            array (":id" => $id), false);
  // set person to deactive
  db_query("UPDATE {cdb_person} 
            SET active_yn=0, loginstr=null WHERE id=:id", 
            array (":id" => $id), false);
}
/**
 * activate person
 * @param int $id
 */
function churchdb_activatePerson($id) {
  // set person to active
  db_query("UPDATE {cdb_person}   
            SET active_yn=1 WHERE id=:id", 
            array (":id" => $id), false);
}

/**
 * set password of person (will be scrambled before storing in DB)
 * @param int $id
 * @param string $password
 * @throws CTFail
 */
function churchdb_setPersonPassword($id, $password) {
  $scrambled_password = scramble_password($password);
  if ($scrambled_password == null) throw new CTFail("Password nicht akzeptiert"); 
  // TODO: shouldnt better scramble_password throw the exception?
  
  db_query("UPDATE {cdb_person} 
            SET password=:password WHERE id=:id", 
            array (":id" => $id, ":password" => $scrambled_password), false);
}

/**
 * send person an invitation with singleuse loginstring per email 
 * 
 * @param int $id          
 */
function churchdb_invitePersonToSystem($id) {
  global $base_url;
  
  $loginstr = churchcore_createOnTimeLoginKey($id);
  $content = "<h3>Hallo [Vorname],</h3><P>";
  
  $content .= htmlize(getConf('invite_email_text', "invitation.email.standard.text", getConf('site_name')));
  $content .= '<p><a href="'. $base_url. "?q=profile&loginstr=$loginstr&id=$id".
       '" class="btn btn-royal">Auf %sitename anmelden</a>';
  $res = churchcore_sendEMailToPersonIds($id, "Einladung zu ". getConf('site_name'), $content, getConf('site_mail'), true);
  cdb_log("Person $id wurde zu ". getConf('site_name'). " eingeladen:". $content, 2, $id); 
  //TODO: is $content in log really needed? no name; if admin clicks link, ontimelogin will be deleted, ...
}

/**
 * add mail chimp relation
 * 
 * @param array $params
 * @return last insert id
 */
function churchdb_addMailchimpRelation($params) {
  $i = new CTInterface();
  $i->setParam("gruppe_id");
  $i->setParam("mailchimp_list_id");
  $i->setParam("optin_yn");
  $i->setParam("goodbye_yn");
  $i->setParam("notifyunsubscribe_yn");
  $i->addModifiedParams();
  $res = db_insert("cdb_gruppe_mailchimp")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->execute(false);
  
  return $res;
}

/**
 * delete mail chimp relation
 * 
 * @param array $params
 */
function churchdb_delMailchimpRelation($params) {
  db_query("DELETE FROM {cdb_gruppe_mailchimp} 
            WHERE mailchimp_list_id=:list_id AND gruppe_id=:gruppe_id", 
            array (":gruppe_id" => $params["gruppe_id"], 
                   ":list_id" => $params["mailchimp_list_id"],
            ));
}

/**
 * load mail chimp
 * 
 * @throws CTFail
 * @return stdClass
 */
function churchdb_loadMailchimp() {
  global $config;
  
  $db = db_query("SELECT * FROM {cdb_gruppe_mailchimp}");
  $assignment = array ();
  foreach ($db as $g) {
    $assignment[] = $g;
  }
  
  include_once (ASSETS. "/mailchimp-api-class/inc/MCAPI.class.php");
  $api = new MCAPI($config["churchdb_mailchimp_apikey"]);
  
  $res = new stdClass();
  $res->lists = $api->lists();
  if ($api->errorCode) {
    $txt = "Unable to load Mailchimp lists()! Code=". $api->errorCode. " Msg=". $api->errorMessage;
    throw new CTFail($txt);
  }
  $res->zuordnung = $assignment;
  return $res;
}

/**
 * smspromote (german provider for sending paid SMS)
 * 
 * @param unknown $param
 * @return Ambigous <string>
 */
function churchdb_smspromote($param) {
  global $config, $user;
  $url = "https://gateway.smspromote.de"; // Gateway URL
  // $url = "https://gateway.smspromote.de/bulk/";
  $request = ""; 
  $param["key"] = $config["churchdb_smspromote_apikey"]; // Gateway Key
  $param["route"] = "gold"; // use of Goldroute
  // $param["route"] = "basic";// use of Basicroute
  $param["debug"] = "0"; // SMS will not be send - Testmode
  
  $param["message"] = utf8_decode($param["message"]);
  
  foreach ($param as $key => $val) {
    $request .= $key. "=". urlencode($val). "&";
  }
  
  /********************************************************
   * through file, Problem, allow_url_fopen=on needed!
   * // send SMS
   * $response = @file($url."?".$request); // send request
   * $response_code = intval($response[0]); // read response code
   ********************************************************/
  
  /**
   * Through FSOCKOPEN, SSL DON'T WORKS, DOES IT?
   */
  //
  // prepare connection 
  $host = "gateway.smspromote.de";
  $script = "/";
  $request_length = strlen($request);
  $method = "POST";
  
  // generate HTTP Header, currently use 1.0 to prevent chunked
  $header = "$method $script HTTP/1.0\r\n";
  $header .= "Host: $host\r\n";
  $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $header .= "Content-Length: $request_length\r\n";
  $header .= "Connection: close\r\n\r\n";
  $header .= "$request\r\n";
  
  // open connection
  $socket = @fsockopen($host, 80, $errno, $errstr);
  if ($socket)   // if opened ...
  {
    fputs($socket, $header); // send Header 
    while (!feof($socket)) {
      $output[] = fgets($socket); // get Response 
    }
    fclose($socket);
  }
  
  $response_code = intval($output[count($output)- 1]);
  
  $response_code_arr = array ();
  $response_code_arr[0] = "Keine Verbindung zum Gateway";
  $response_code_arr[10] = "Empf�nger fehlerhaft";
  $response_code_arr[20] = "Absenderkennung zu lang";
  $response_code_arr[30] = "Nachrichtentext zu lang";
  $response_code_arr[31] = "Messagetyp nicht korrekt";
  $response_code_arr[40] = "Falscher SMS-Typ";
  $response_code_arr[50] = "Fehler bei Login";
  $response_code_arr[60] = "Guthaben zu gering";
  $response_code_arr[70] = "Netz wird von Route nicht unterst�tzt";
  $response_code_arr[71] = "Feature nicht �ber diese Route m�glich";
  $response_code_arr[80] = "SMS konnte nicht versendet werden";
  $response_code_arr[90] = "Versand nicht m�glich";
  $response_code_arr[100]= "SMS wurde erfolgreich versendet.";
  
  $body = $param["message"]. "<br><br><i>Status: ". $response_code_arr[$response_code]. "</i>";
  
  db_query('INSERT INTO {cc_mail_queue} (receiver, sender, subject, body, htmlmail_yn, priority, 
               modified_date, modified_pid, send_date, error, reading_count) 
            VALUES (:receiver, :sender, :subject, :body, :htmlmail_yn, :priority, 
               :modified_date, :modified_pid, :send_date, :error, :reading_count)', 
            array(":receiver" => $param["to"], 
                  ":sender" => "$user->vorname $user->name", 
                  ":subject" => shorten_string($param["message"], 30), 
                  ":body" => $body, 
                  ":htmlmail_yn" => 0, 
                  ":priority" => 1, 
                  ":modified_date" => current_date(), 
                  ":modified_pid" => $user->id, 
                  ":send_date" => current_date(), 
                  ":error" => ($response_code== 100 ? 0 : 1), 
                  ":reading_count" => 0,
  ));
  return $response_code_arr[$response_code];
}

/**
 * send sms
 * 
 * @param string $ids, comma separated
 * @param string $txt
 * @return array
 */
function churchdb_sendsms($ids, $txt) {
  global $user;
  $param = array ();
  // get cell phone number of user as sender from DB - maybe session contains an outdated one.
  $mobile = db_query("SELECT telefonhandy FROM {cdb_person} 
                      WHERE id=:id", 
                      array (":id" => $user->id))
                      ->fetch();
  if (!empty($mobile->telefonhandy)) $param["from"] = preg_replace('![^0-9]!', '', $mobile->telefonhandy);
  else $param["from"] = "ChurchTools";
  
  $db = db_query("SELECT id, telefonhandy, vorname, name, spitzname 
                  FROM {cdb_person} 
                  WHERE id IN (". db_implode($ids). ")");
  $res = array ();
  $res["withoutmobilecount"] = 0;
  $res["smscount"] = 0;
  
  foreach ($db as $p) {
    if (!$p->telefonhandy) {
      $res["withoutmobilecount"]++;
      $res[$p->id] = t("no.sms.sent.person.has.no.mobile.number");
    }
    else {
      $param["to"] = $p->telefonhandy;
      $mailtxt = $txt;
      $mailtxt = str_replace("[Vorname]", $p->vorname, $mailtxt);
      $mailtxt = str_replace("[Nachname]", $p->name, $mailtxt);
      $mailtxt = str_replace("[Spitzname]", ($p->spitzname== "" ? $p->vorname : $p->spitzname), $mailtxt);
      $mailtxt = str_replace("[Id]", $p->id, $mailtxt);
      $param["message"] = $mailtxt;
      $res[$p->id] = churchdb_smspromote($param);
      $res["smscount"]++;
    }
  }
  return $res;
}

/**
 * 
 * @param unknown $params
 * @throws CTFail
 */
function f_functions($params) {
  $function = $params["func"];
  $fields = getAllFields("intern_code = '$function'");
  
  // Check if someone try to set an existing email, but have no write access to churchdb
  // otherwise someone could use the email of an admin...
  if (isset($params["email"]) && !user_access("write access", "churchdb")) {
    // Check, if the email address has changed
    $db = db_query("SELECT * FROM {cdb_person} p 
                    WHERE id=:id", 
                    array (":id" => $params["id"]))
                    ->fetch();
    if ($db->email != $params["email"]) {
      // Check, if another user has this email
      $db = db_query("SELECT * FROM {cdb_person} p 
                      WHERE email=:email AND id!=:id", 
                      array (":email" => $params["email"], ":id" => $params["id"]))
                      ->fetch();
      if ($db) throw new CTFail(t('email.already.used.you.need.more.rights.to.change.this'));
    }
  }
  if ($function == "f_group") saveGeocodeGruppe($params["id"], "", "");

  foreach ($fields["fields"] as $key => $value) {
    if (isset($params[$key])) $arr[$key] = $params[$key];
  }
  // Wenn die letzteaenderung mit �bergeben wird (z.B. bei Sync mit externen Tools)
  // Soll das hier mit gesetzt werden
  if (isset($params['letzteaenderung'])) $arr['letzteaenderung'] = $params['letzteaenderung'];
  
  $oldarr = saveDataArray($fields, $params["id"], $arr);
  if (is_string($oldarr)) $res = $oldarr;
  else {
    $txt = churchcore_getFieldChanges($fields["fields"], $oldarr, $arr);
    if ($txt) {
      if ($function == "f_group") {
        $txt = t("group").": ". $arr["bezeichnung"]. "\n". $txt;
      }
      else {
        $details = churchdb_getPersonDetails($params["id"]);
//var_dump($details);        
//        $txt = t("person").": ". $details->vorname. " ". $details->name. " (". $params["id"]. ")\n". $txt;
      }
    }
    sendFieldNotifications($function, $txt);
    
    if ($txt) cdb_log("$function - ". $txt, 2, $params["id"], $function == "f_group" ? CDB_LOG_GROUP : CDB_LOG_PERSON, 1);
  }
}


/**
 * churchdb ajax
 */
function churchdb_ajax() {
  include_once ("churchdb_db.php");
  
  $module = new CTChurchDBModule("churchdb");
  $ajax = new CTAjaxHandler($module);
  
  // $t = microtime(true);
  // $timer = "start:".round(microtime(true)-$t,3)." ";
  
  drupal_json_output($ajax->call());
}
