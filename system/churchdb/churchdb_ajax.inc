<?php
include_once("churchdb_db.inc");



function getAuthForPerson($id) {
  return getAuthForDomain($id, "person");
}

function getSearchableData() {
  $persons = db_query('SELECT person_id id, geburtsdatum, familienstand_no, geschlecht_no, hochzeitsdatum, nationalitaet_id,  
                  erstkontakt, zugehoerig, eintrittsdatum, austrittsdatum, taufdatum, plz, geburtsort, imageurl, cmsuserid
           FROM {cdb_person} p, {cdb_gemeindeperson} gp 
                  WHERE p.id=gp.person_id');
  foreach ($persons as $arr) {
    $arr->auth=getAuthForPerson($arr->id);
    $arrs[$arr->id]=$arr;
  }
  return $arrs;	
}

function churchdb_getPersonDetails($id) {
  global $user;
  
  // Entweder habe ich viewall auf den Bereich oder ich bin mit dem in einer Gruppe
  $allowed=false;
  $iAmLeader=false;

  // Pr�fe erst, ob ich als Leiter mit dem in der Gruppe bin
  if (!$allowed) {    
    $myGroups=churchdb_getMyGroups($user->id, true, true);
    if (count($myGroups)>0) {
      $res=db_query("SELECT count(*) c
               FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg 
                      WHERE p.id=gp.person_id and gpg.gemeindeperson_id=gp.id and p.id=:id 
                       and gpg.gruppe_id in (".implode(",", $myGroups).") ", array(':id'=>$id))->fetch();
      if ($res->c>0) {
        $iAmLeader=true;
        $allowed=true;
      }
    }
  }
  // Pr�fe nun, ob ich normal mit dem in der Gruppe bin
  if (!$allowed) {
    $myGroups=churchdb_getMyGroups($user->id, true, false);
    if (count($myGroups)>0) {
      $res=db_query("SELECT count(*) c
               FROM {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg 
                      WHERE p.id=gp.person_id and gpg.gemeindeperson_id=gp.id and p.id=:id 
                       and gpg.gruppe_id in (".implode(",", $myGroups).") ", array(':id'=>$id))->fetch();
      if ($res->c>0) {
        $allowed=true;
      }
    }    
  }
  if (!$allowed) {
    $allowedDeps=user_access("view alldata", "churchdb");
    if ($allowedDeps!=null) {
      $res=db_query('select count(*) as c from {cdb_bereich_person} 
          where person_id=:p_id and bereich_id in ('.implode(',', $allowedDeps).')'
        , array(':p_id'=>$id), false)->fetch();
      if ($res->c>0) $allowed=true;
    }
  }
  
  if (!$allowed) 
    return "no access";
    
  $res=db_query("select f.*, fk.intern_code from {cdb_feld} f, {cdb_feldkategorie} fk where f.feldkategorie_id=fk.id
              and fk.intern_code in ('f_address', 'f_church', 'f_category') and aktiv_yn=1");
  $sqlfields=array();
  $sqlfields[]="p.id id";
  $sqlfields[]="gp.id gp_id";
  $sqlfields[]="geolat as lat";
  $sqlfields[]="imageurl";
  $sqlfields[]="geolng as lng";
  $sqlfields[]="cmsuserid";
  
  function _checkAutorisierung($auto) {
    global $iAmLeader;
    if ($auto==null) return true;
    if ($auto=="ViewAllDetailsOrPersonLeader") {
      return user_access('view address',"churchdb") || ($iAmLeader) || (user_access('view alldetails',"churchdb"));      
    } else if ($auto=="admin") {
      return user_access('edit masterdata',"churchdb");      
      
    }
    else echo "Unbekanntes Recht: ".$auto;      
  }
  
  foreach ($res as $res2) {    
    if (($res2->autorisierung==null) || (_checkAutorisierung($res2->autorisierung)))
      if (($res2->intern_code=="f_address") || ($iAmLeader) || (user_access('view alldetails',"churchdb")))
        $sqlfields[]=$res2->db_spalte;  
  } 
       
  $sql="select ".join($sqlfields,",");
  if (($iAmLeader) || (user_access('view alldetails',"churchdb")))
    $sql.=', p.letzteaenderung, p.aenderunguser, p.createdate, if (loginstr is null , 0 , 1) as einladung, p.active_yn, p.lastlogin';
      	
  $sql.=' FROM {cdb_person} p, {cdb_gemeindeperson} gp WHERE p.id=gp.person_id and p.id=:pid';  
    
  $person=db_query($sql, array(':pid'=>$id))->fetch();  
  $comments=db_query("select id, text, person_id, datum, comment_viewer_id, relation_name from {cdb_comment} 
                      where relation_id=:relid and relation_name like 'person%'
					  order by datum desc", array(':relid'=>$id));
  $auth=user_access("view comments","churchdb");
  if (($comments) && ($auth!=null)) {
    $arrs=null;
    foreach ($comments as $arr) {
      if ((isset($auth[$arr->comment_viewer_id])) &&
        ($auth[$arr->comment_viewer_id]==$arr->comment_viewer_id))
        $arrs[]=$arr;		
    }
    $person->comments=$arrs;
  }
  $person->auth=getAuthForPerson($id);
  return $person;				  	
}

function churchdb_getPersonDetailsLogs($id) {
  $arrs="";
  $logs=db_query("select datum, person_id, txt, level from {cdb_log}
                      where domain_id=:pid and domain_type='person'
            order by datum desc", array(':pid'=>$id));
  foreach ($logs as $arr) {
    $arrs[]=$arr;
  }
  return $arrs;  
}

/**
 * 
 * @param $longtext
 * @param $shorttext
 * @param $column_name
 * @param $masterdata_selector
 * @param $eol
 */
function getSelectField($longtext, $shorttext, $column_name, $masterdata_selector, $eol='<br/>', $auth=null) {
  $res["type"]="select";
  $res["selector"]=$masterdata_selector;
  $res["text"]=$longtext;  // Bei Editieren etc.
  $res["shorttext"]=$shorttext; // In der Ansicht
  if ($eol=="") $eol="&nbsp;";
  $res["eol"]=$eol;
  $res["sql"]=$column_name;
  $res["auth"]=$auth;  
  return $res;
}



function churchdb_getFields($feldkategorie_id) {
  $res=db_query("select * from {cdb_feld} f, {cdb_feldtyp} ft where 
                feldkategorie_id='".$feldkategorie_id."' and f.feldtyp_id=ft.id and aktiv_yn=1
                order by feldkategorie_id, sortkey, langtext");
  $fields=array();
  foreach ($res as $f) {
    $field=array();
    $field["type"]=$f->intern_code;
    $field["text"]=$f->langtext;  // Bei Editieren etc.
    $field["shorttext"]=$f->kurztext; // In der Ansicht
    $field["eol"]=$f->zeilenende;
    if ($field["eol"]=="") $field["eol"]="&nbsp;";
    $field["sql"]=$f->db_spalte;
    if ($f->autorisierung!=null)
      $field["auth"]=$f->autorisierung;
    if ($f->db_stammdatentabelle!=null)
      $field["selector"]=$f->db_stammdatentabelle;
    if ($f->laenge!=null)
      $field["length"]=$f->laenge;
    if ($f->inneuerstellen_yn==1)
      $field["inneuerstellen_yn"]=1;
    $fields[$f->db_spalte]=$field;  
  }
  return $fields;  
}

function _churchdb_getGroupMemberType($id, $bez, $kuerzel) {
  $res["id"]=$id;
  $res["bezeichnung"]=$bez;
  $res["kuerzel"]=$kuerzel;
  return $res;
}
function getGroupMemberTypes() {
  $res = db_query("SELECT * FROM {cdb_gruppenteilnehmerstatus}");
  $arrs=null;
  foreach ($res as $arr) {
    $arrs[$arr->intern_code]=_churchdb_getGroupMemberType($arr->intern_code, $arr->bezeichnung, $arr->kuerzel);
  }
  return $arrs;
}

function _churchdb_getGroupFilterType($id, $bez) {
  $res["id"]=$id;
  $res["bezeichnung"]=$bez;
  return $res;  
}

function churchdb_getGroupFilterTypes() {
  $res[0]=_churchdb_getGroupFilterType(0,"in");
  $res[1]=_churchdb_getGroupFilterType(1,"nicht in");
  $res[2]=_churchdb_getGroupFilterType(2,"war in");
  return $res;
}

/**
 * 
 * @return Gibt das alte Array zurueck
 * @param object $fields 
 * @param object $primary_key - id von person_id
 * @param object $data_arr
 */
function saveDataArray($fields, $primary_key, $data_arr) {
global $user;

  $res=db_query("SELECT * from {".$fields["tablename"]."} WHERE ".$fields["idname"]."=".$primary_key);
  $old_arr=$res->fetch();
  
  $error_str="";
  $auth=churchdb_getAuthForAjax();
//  if (($auth["viewall details"]) | (isPersonIsLeaderOf($user->id, diese person hier))))
    $auth["ViewAllDetailsOrPersonLeader"]=true;
    $auth["ViewAddressOrViewAllDetailsOrPersonLeader"]=true;
    
  $sql="UPDATE {".$fields["tablename"]."} SET ";
  foreach ($data_arr as $key=>$param) {
  	if (isset($fields["fields"][$key])) { 
  	  if ((!isset($fields["fields"][$key]["auth"])) 
  	       || (isset($auth[$fields["fields"][$key]["auth"]]))) {  	
	  	switch ($fields["fields"][$key]["type"]) {
	  	  case "number":
	        $sql=$sql.$fields["fields"][$key]["sql"]."=".$param.", ";
			break;
	  	  case "textarea":
	        $sql=$sql.$fields["fields"][$key]["sql"]."='".$param."', ";
			  break;
	  	  case "checkbox":
	        $sql=$sql.$fields["fields"][$key]["sql"]."=".$param.", ";
			  break;
	      case "text":
	        $sql=$sql.$fields["fields"][$key]["sql"]."='".$param."', ";
			  break;
	      case "select":
	        $sql=$sql.$fields["fields"][$key]["sql"]."='".$param."', ";
			  break;
	  	  case "date":
	        if (($param!="") && ($param!="null"))
	          $sql=$sql.$fields["fields"][$key]["sql"]."='".$param."', ";
			else  
	          $sql=$sql.$fields["fields"][$key]["sql"]."=null, ";
			break;
	  	}  
	  }
	  else $error_str.="Fehlendes Recht ".$fields["fields"][$key]["auth"]." fuer Update von Feld: ".$key.". ";
    } 
  }
  if ($error_str!="")
    return $error_str;

  // Wenn es nicht per Array �bergeben wurde, dann setze es auf jetzt
  if (isset($data_arr['letzteaenderung'])) 
    $sql.=" letzteaenderung='".$data_arr['letzteaenderung']."',";    
  else  
    $sql.=" letzteaenderung=now(),";  
  $sql.=" aenderunguser='".$user->cmsuserid.
                   "' WHERE ".$fields["idname"]."=".$primary_key;
  //cdb_log('Update sql:'.$sql,2,-1,CDB_LOG_PERSON,1);
  db_query($sql);
  return $old_arr;
}

function saveGeocodePerson($id, $lat, $lng) {
  db_update('cdb_person')
    ->fields(array(
      "geolat" => $lat,
      "geolng" => $lng,  
    ))
    ->condition("id", $id, "=")
    ->execute();
}

function saveGeocodeGruppe($id, $lat, $lng) {
  db_update('cdb_gruppe')
    ->fields(array(
      "geolat" => $lat,
      "geolng" => $lng,  
    ))
    ->condition("id", $id, "=")
    ->execute();
}

function createAddress($params) {
global $user;

  if ((!isset($params["Inputf_dep"])) || (!isset($params["Inputf_status"]))) {
    return "Error, some Input missing!!";
  }
  if (!isset($params["Inputf_station"])) $params["Inputf_station"]=0;
  if ((!isset($params["vorname"])) && (isset($params["givenname"])))
    $params["vorname"]=$params["givenname"];

  $sql=db_query("SELECT count(*) c from {cdb_person}")->fetch();
  if (variable_get('churchdb_maxuser', '100000')*1<=$sql->c*1) {
    $res["result"]="Maximale Anzahl von Benutzern erreicht. Erlaubt sind ".variable_get('churchdb_maxuser', '50');
    return $res;
  }
  
  $sql="SELECT id from {cdb_person} ".
         "where vorname like '".$params["vorname"]."%' and name like '".$params["name"]."%'";
  $obj=db_query($sql)->fetch();
  if (((!isset($params["force"])) || ($params["force"]=="")) && (isset($obj->id))) {
    $arr["result"]="exist";
    $arr["id"]=$obj->id;
  } 
  else {
    $dt = new DateTime();
    
    // felder f_address durchgehen und schauen was da drin steckt!
    $fields=getAllFields();
    $save=array();
    foreach ($fields["f_address"]["fields"] as $field) {
      if (isset($params[$field["sql"]])) $save[$field["sql"]]=$params[$field["sql"]];
    }
    if (!isset($save["createdate"])) $save["createdate"]=$dt->format('Y-m-d H:i:s'); 
    if (!isset($save["aenderunguser"])) $save["aenderunguser"]=$user->cmsuserid;
    if (!isset($save["letzteaenderung"])) $save["letzteaenderung"]=$dt->format('Y-m-d H:i:s'); 
    $id=db_insert('cdb_person')->fields($save)->execute();

    // felder f_church (Information) durchgehen und schauen was da drin steckt!
    $save=array();
    foreach ($fields["f_church"]["fields"] as $field) {
      if (isset($params[$field["sql"]])) $save[$field["sql"]]=$params[$field["sql"]];
    }
    $save["person_id"]=$id;
    if (!isset($save["erstkontakt"])) $save["erstkontakt"]=$dt->format('Y-m-d H:i:s'); 
    if (!isset($save["status_id"])) $save["status_id"]=$params["Inputf_status"];
    if (!isset($save["station_id"])) $save["station_id"]=$params["Inputf_station"];    
    db_insert('cdb_gemeindeperson')->fields($save)->execute();
    
    db_query("INSERT INTO {cdb_bereich_person} (person_id, bereich_id) ".
               "values ($id, ".$params["Inputf_dep"].")");
    
    $arr["result"]="ok";
    $arr["id"]=$id;
  } 
  return $arr;    
}

function deleteGroup($g_id) {
  db_query("delete from {cdb_gemeindeperson_gruppe} where gruppe_id=:id", 
     array(":id"=>$g_id));  
  db_query("delete from {cdb_gemeindeperson_gruppe_archive} where gruppe_id=:id", 
     array(":id"=>$g_id));  
  db_query("delete from {cdb_gruppe} where id=:id", 
     array(":id"=>$g_id));  
  db_query("delete from {cdb_gruppe_tag} where gruppe_id=:id", 
     array(":id"=>$g_id));  
  db_query("delete from {cc_domain_auth} where domain_type='gruppe' and domain_id=:id", 
     array(":id"=>$g_id));  
   return "ok";
}

function createGroup($name, $grouptype, $district, $force) {
global $user;
  $sql="SELECT id from {cdb_gruppe} ".
         "where bezeichnung like '".$name."'";
  $obj=db_query($sql)->fetch();
  if (($force==null) && (isset($obj->id))) {
    $arr["result"]="exist";
    $arr["id"]=$obj->id;
  } 
  else {

    $dt=new DateTime();
    $fields=array("bezeichnung"=>$name, "treffzeit"=>"", "treffpunkt"=>"", "treffname"=>"", "zielgruppe"=>"", "notiz"=>"", "instatistik_yn"=>0,
          "treffen_yn"=>0, "geolat"=>"", "geolng"=>"", "distrikt_id"=>$district, "gruppentyp_id"=>$grouptype, "aenderunguser"=>$user->cmsuserid, "letzteaenderung"=>$dt->format('Y-m-d H:i:s'),);
    $arr["id"]=db_insert('cdb_gruppe')->fields($fields)->execute();
    
/*    $sql="SELECT max(id) as id from {cdb_gruppe} ".
       "where bezeichnung like '".$name."'";
    $obj=db_query($sql)->fetch();
    $id=$obj->id;*/

    $arr["result"]="ok";
  } 
  return $arr;    
}


function _churchdb_getMembersOfGroup($group_id) {
  $sql="select p.name, p.vorname, p.email, p.id p_id, gp.id id, gpg.status_no, g.bezeichnung , gpg.aenderunguser, DATE_FORMAT(gpg.letzteaenderung, '%d.%m.%Y') letzteaenderung, cmsuserid, gpg.comment
              from {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppe} g 
              where p.id=gp.person_id and gp.id=gpg.gemeindeperson_id and g.id=gpg.gruppe_id and gpg.gruppe_id=$group_id";
  $res=db_query($sql);
  
  foreach ($res as $arr) {
  	$personen[$arr->id]=$arr;
  }
  return $personen;
} 

/**
* Informiert Leiter �ber neue Mitglieder und schickt einen AdditionalText dazu, wenn vorhanden
 */
function informLeaderAboutNewGroupMember($group_id, $gp_id, $add_text=null) {
  global $base_url, $user;
  $personen=_churchdb_getMembersOfGroup($group_id);
  foreach ($personen as $val) {
    // Wenn es Leiter (1), CoLeiter (2) oder SuperVisor (3) sind und ich nicht selber der bin, der es ge�ndert hat
    if (($val->status_no>=1) && ($val->status_no<=3) && ($val->email!=null) && ($val->email!="") && 
             (($val->cmsuserid==null || ($val->cmsuserid!=$user->cmsuserid))))  {
      $mt=getGroupMemberTypes();
      $content="<h3>Hallo ".$mt[$val->status_no]["bezeichnung"]."!</h3><p>";
      if ($val->status_no<3)
        $content=$content."In Deine ";
      else   
        $content=$content."In die ";
      $content=$content."Gruppe \"".$val->bezeichnung."\" wurde von ".$personen[$gp_id]->aenderunguser." ein <i>".$mt[$personen[$gp_id]->status_no]["bezeichnung"]."</i> hinzugef&uuml;gt:";
      $content=$content."<p>Name: ".$personen[$gp_id]->vorname." ".$personen[$gp_id]->name;
      if ($add_text!=null)
        $content=$content."<p>".$add_text;
      $content=$content.'<p><p><a class="btn btn-royal" href="'.$base_url.'?q=churchdb#PersonView/searchEntry:'.$personen[$gp_id]->p_id.'">Person ansehen</a>';

      churchdb_send_mail("[".variable_get('site_name', 'drupal')."] Neuer Teilnehmer in der Gruppe \"".$val->bezeichnung."\"",
	              $content, $val->email);
  	}
  }	
}
function informLeaderAboutEditedGroupMember($group_id, $gp_id, $add_text=null) {
  global $base_url, $user;
  $personen=_churchdb_getMembersOfGroup($group_id);
  foreach ($personen as $val) {
    // Wenn es Leiter (1), CoLeiter (2) oder SuperVisor (3) sind und ich nicht selber der bin, der es ge�ndert hat
    if (($val->status_no>=1) && ($val->status_no<=3) && ($val->email!=null) && ($val->email!="") && 
             (($val->cmsuserid==null || ($val->cmsuserid!=$user->cmsuserid))))  {
      $mt=getGroupMemberTypes();
      $content="<h3>Hallo ".$mt[$val->status_no]["bezeichnung"]."!</h3>";
      if ($val->status_no<3)
        $content=$content."<p>In Deiner ";
      else   
        $content=$content."<p>In der ";
      $content=$content."Gruppe \"".$val->bezeichnung."\" wurde von ".$personen[$gp_id]->aenderunguser." der Teilnehmerstatus angepasst:";
      $content=$content."<p>Name: ".$personen[$gp_id]->vorname." ".$personen[$gp_id]->name;
      $content=$content."<p>Teilnehmerstatus: ".$mt[$personen[$gp_id]->status_no]["bezeichnung"];
      $content=$content."<p>Datum: ".$personen[$gp_id]->letzteaenderung;
      if ($add_text!=null)
        $content=$content."<p>".$add_text;
      
      $content=$content.'<p><p><a class="btn btn-royal" href="'.$base_url.'?q=churchdb#PersonView/searchEntry:'.$personen[$gp_id]->p_id.'">Person ansehen</a>';
                     
      churchdb_send_mail("[".variable_get('site_name', 'drupal')."] Teilnehmerstatus in der Gruppe \"".$val->bezeichnung."\" angepasst",
	              $content, $val->email);
    }
  }	
}
function informLeaderAboutDeletedGroupMember($group_id, $gp_id) {
  global $base_url, $user;
  $personen=_churchdb_getMembersOfGroup($group_id);
  foreach ($personen as $val) {
    // Wenn es Leiter (1), CoLeiter (2) oder SuperVisor (3) sind und ich nicht selber der bin, der es ge�ndert hat
    // und ich nicht der bin, der gerade gel�scht wurde!  
    if (($val->status_no>=1) && ($val->status_no<=3) && ($val->email!=null) && ($val->email!="") && 
             ($gp_id!=$val->id) && (($val->cmsuserid==null || ($val->cmsuserid!=$user->cmsuserid))))  {
      $mt=getGroupMemberTypes();
      $content="<h3>Hallo ".$mt[$val->status_no]["bezeichnung"]."!</h3>";
      if ($val->status_no<3)
        $content=$content."<p>In Deiner ";
      else   
        $content=$content."<p>In der ";
      $content=$content."Gruppe \"".$val->bezeichnung."\" wurde von ".$user->cmsuserid." ein ".$mt[$personen[$gp_id]->status_no]["bezeichnung"]." entfernt:";
      $content=$content."<p>Name: ".$personen[$gp_id]->vorname." ".$personen[$gp_id]->name;
      
      $content=$content.'<p><p><a class="btn btn-royal" href="'.$base_url.'?q=churchdb#PersonView/searchEntry:'.$personen[$gp_id]->p_id.'">Person ansehen</a>';
               
      churchdb_send_mail("[".variable_get('site_name', 'drupal')."] Teilnehmer in der Gruppe \"".$val->bezeichnung."\" entfernt",
	              $content, $val->email);

    }
  }	
}


function churchdb_addPersonGroupRelation($p_id, $g_id, $leader, $date, $followup, $followup_erfolglos_zurueck_gruppen_id, $comment) {
  global $user;
  $gp_id=_churchdb_getGemeindepersonIdFromPersonId($p_id);
  if ($date==null) {
    $dt=new DateTime();
    $date=$dt->format('Y-m-d H:i:s');
  }

  $fields= array(
            'gemeindeperson_id'=>$gp_id, 
            'gruppe_id' =>$g_id, 
            'status_no' => $leader, 
            'letzteaenderung' =>$date, 
            'aenderunguser' =>$user->cmsuserid,
            'followup_count_no'=>$followup,
            'followup_erfolglos_zurueck_gruppen_id'=>$followup_erfolglos_zurueck_gruppen_id,
            'comment'=>$comment
           );
  try {         
    db_insert('cdb_gemeindeperson_gruppe')->fields($fields)->execute(false);
  }
  catch (Exception $e) {
    return "Fehler: ".$e;      
  }
  
  $info=getGroupInfo($g_id);
  cdb_log("Neu: ".$info->gruppentyp." ".$info->gruppe." (P".$p_id.":G".$g_id." Leiter:".$leader.")",2,$gp_id,CDB_LOG_PERSON,1);
  $automail=chuchdb_sendAutomaticGroupEMail($g_id, $p_id, $leader);
  if ((variable_get('churchdb_sendgroupmails', true)) && ($info->mail_an_leiter_yn==1)) {
    $txt="";
    if ($comment!=null) $txt.='<p>Kommentar: <i>'.$comment.'</i>';
    if ($automail!=null) $txt.='<p>Eine automatische E-Mail wurde an die Person gesendet: <i>"'.$automail.'"</i>';
    informLeaderAboutNewGroupMember($g_id, $gp_id, $txt);
  }  
  return "ok";
}


/*
 * Sendet eine automatische Mail wenn vorhanden. Gibt dann Betreff zur�ck, ansonsten null.
 */
function chuchdb_sendAutomaticGroupEMail($g_id, $p_id, $leader) {
  $res=db_query("select * from {cdb_gruppenteilnehmer_email} where gruppe_id=$g_id and status_no=$leader and aktiv_yn=1")->fetch();
  if ($res!=false) {
    $p=db_query("select email from {cdb_person} where id=:id", array(":id"=>$res->sender_pid))->fetch();
    if ($p!=false) {    
      churchcore_sendEMailToPersonIds($p_id, $res->email_betreff, $res->email_inhalt, $p->email, true, false);
      return $res->email_betreff;  
    }  
  }  
}

function _churchdb_editPersonGroupRelation($p_id, $g_id, $leader, $date, $followup, $comment) {
  global $user;
  $gp_id=_churchdb_getGemeindepersonIdFromPersonId($p_id);
  if ($date==null) {
    $dt=new DateTime();
    $date=$dt->format('Y-m-d H:i:s');
  }
  
  $info_rel=getPersonGroupRelation($gp_id, $g_id);
  
  $fields= array(
             'gemeindeperson_id'=>$gp_id, 
             'gruppe_id'=>$g_id, 
             'status_no'=>$info_rel->status_no, 
             'letzteaenderung'=>$info_rel->letzteaenderung, 
             'aenderunguser'=>$info_rel->aenderunguser
           );
  db_insert('cdb_gemeindeperson_gruppe_archive')->fields($fields)->execute();
           
  if (!db_query("update {cdb_gemeindeperson_gruppe} set status_no=$leader, letzteaenderung='$date', 
      followup_count_no=$followup, aenderunguser='".$user->cmsuserid."', comment='$comment' 
      where gemeindeperson_id=$gp_id and gruppe_id=$g_id"))
    return "error by updateing gemeindeperson_gruppe";
            
  $info=getGroupInfo($g_id);
  cdb_log("Aktualisiere: ".$info->gruppentyp." ".$info->gruppe." (P".$p_id.":G".$g_id." Leiter:".$leader.")",2,$p_id,CDB_LOG_PERSON,1);
  $automail=null;
  if ($info_rel->status_no!=$leader) 
    $automail=chuchdb_sendAutomaticGroupEMail($g_id, $p_id, $leader);
  if ((variable_get('churchdb_sendgroupmails', true)) && ($info->mail_an_leiter_yn==1)) {
    $txt="";
    if ($comment!=null) $txt.='<p>Kommentar: <i>'.$comment.'</i>';
    if ($automail!=null) $txt.='<p>Eine automatische E-Mail wurde an die Person gesendet: <i>"'.$automail.'"</i>';
    informLeaderAboutEditedGroupMember($g_id, $gp_id, $txt);
  }  
  return "ok";
}

function _churchdb_delPersonGroupRelation($p_id, $g_id) {
  global $user;
  $gp_id=_churchdb_getGemeindepersonIdFromPersonId($p_id);
  
  $info_rel=getPersonGroupRelation($gp_id, $g_id);

  $fields= array(
             'gemeindeperson_id'=>$gp_id, 
             'gruppe_id'=>$g_id, 
             'status_no'=>$info_rel->status_no, 
             'letzteaenderung'=>$info_rel->letzteaenderung, 
             'aenderunguser'=>$info_rel->aenderunguser
           );
  db_insert('cdb_gemeindeperson_gruppe_archive')->fields($fields)->execute();
            
  // F�ge noch die aktuelle Info dazu, damit man wei�, wann er gel�scht wurde!
  $dt = new DateTime();
  $fields= array(
             'gemeindeperson_id'=>$gp_id, 
             'gruppe_id'=>$g_id, 
             'status_no'=>-99, 
             'letzteaenderung'=>$dt->format('Y-m-d H:i:s'), 
             'aenderunguser'=>$user->cmsuserid
           );
  db_insert('cdb_gemeindeperson_gruppe_archive')->fields($fields)->execute();  
    
  $info=getGroupInfo($g_id);
  
  if ((variable_get('churchdb_sendgroupmails', true)) && ($info->mail_an_leiter_yn==1))
    informLeaderAboutDeletedGroupMember($g_id, $gp_id);  
    
  db_query("delete from {cdb_gemeindeperson_gruppe} where gemeindeperson_id=$gp_id and gruppe_id=$g_id");
    
  cdb_log("Entferne: ".$info->gruppentyp." ".$info->gruppe." (P".$p_id.":G".$g_id." Leiter:".$info_rel->status_no.")",2,$p_id,CDB_LOG_PERSON,1);
  return "ok";
}

function saveBereich($rel_id) {
global $user, $_GET;
  $arr=churchdb_getAllowedDeps();
   
  foreach ($arr as $value) {
    db_query("DELETE FROM {cdb_bereich_person} WHERE person_id=:rel_id and bereich_id=:bereich_id", 
      array(":rel_id"=>$rel_id, ":bereich_id"=>$value)); 
	if ((isset($_GET["bereich".$value])) && ($_GET["bereich".$value]==1)) {
      db_query("INSERT INTO {cdb_bereich_person} VALUES (".$value.", ".$rel_id.")");  		  	
	}
  }  
  return "ok";	
}

function saveImage($id, $url) {
  db_query("UPDATE {cdb_gemeindeperson} SET imageurl='$url' WHERE person_id=$id");
  return "ok";
}

/**
 * Speichert zu einer Relation eine Notiz 
 * @param $rel_id - ID der Relation
 * @param $note - Notiz
 * @param $cmt_viewer - ViewerID f�r die Autorisierung der News 0=Alle (Standard)
 * @param $rel_name - Relation, z.Bsp person (Standard)
 */
function saveNote($rel_id, $note, $cmt_viewer=0, $rel_name="person") {
global $user;
  db_query("INSERT INTO {cdb_comment} (relation_id, relation_name, text, person_id, datum, comment_viewer_id) 
  VALUES (".$rel_id.", '".$rel_name."', '".$note."', '".$user->id."', now(), $cmt_viewer)");
  return "ok";	
}



/**
 * 
 * @param $subject
 * @param $message
 * @param $to, mehrere mit Komma getrennt
 */
function churchdb_send_mail ($subject, $message, $to) { 
  churchcore_systemmail($to, $subject, $message, true);
}


function sendFieldNotifications($field, $txt) {
global $user;
  $arr=getAllMailNotifys();
  if ((isset($arr[$field])) && ($txt!=null)) {
    $txt="<p>Information:<p>".$txt."<p>Anpassungen von $user->cmsuserid";
    churchdb_send_mail("[".variable_get('site_name', 'drupal')."] Info Anpassungen in $field",$txt,$arr[$field]->emails);
  }
}

function getAllFields($where="1=1") {
  $db=db_query("select * from {cdb_feldkategorie} where $where");
  $res=array();
  foreach ($db as $row) {
    $fk=array();
    $fk["tablename"]=$row->db_tabelle;
    $fk["arrayname"]=$row->intern_code;
    $fk["idname"]=$row->id_name;
    $fk["text"]=$row->bezeichnung;
    $fk["fields"]=churchdb_getFields($row->id);
    $res[$row->intern_code]=$fk;   
    if ($where!="1=1") return $fk; 
  }    
  return $res;  
}

function churchdb_getModulesPath() {
  return drupal_get_path('module', 'churchdb');
}

function churchdb_getVersion() {
  $config = parse_ini_file(churchdb_getModulesPath()."/churchdb.info"); 
  return $config["version"];
}

/**
 * Erzeugt eine Uebersicht ueber alle Stammdatentabellen, die per MaintainView gepflegt werden sollen
 * Diese Infos werden per JSON an das JS uebergeben
 */
function churchdb_getMasterDataTablenames() {
  $res=array();
  // Nummern nicht �ndern, Statistik bezieht sich darauf!
  $res[1]=churchcore_getMasterDataEntry(1, "Status", "status", "cdb_status");
  $res[1]["special_func"]=array("name"=>"Berechtigungen", "image"=>"schluessel", "func"=>"editAuth");
  
  $res[2]=churchcore_getMasterDataEntry(2, "Station", "station", "cdb_station");
  $res[3]=churchcore_getMasterDataEntry(3, "Bereich", "dep", "cdb_bereich");  
  $res[4]=churchcore_getMasterDataEntry(4, "Geschlecht", "sex", "cdb_geschlecht");
  $res[5]=churchcore_getMasterDataEntry(5, "Gruppen-Typen", "groupTypes", "cdb_gruppentyp");
  $res[6]=churchcore_getMasterDataEntry(6, "Familienstand", "familyStatus", "cdb_familienstand");
  $res[7]=churchcore_getMasterDataEntry(7, "Distrikt", "districts", "cdb_distrikt", "bezeichnung");
  $res[8]=churchcore_getMasterDataEntry(8, "Beziehungstyp", "relationType", "cdb_beziehungstyp");
  $res[9]=churchcore_getMasterDataEntry(9, "Kommentare-Viewer", "comment_viewer", "cdb_comment_viewer","bezeichnung");
  $res[10]=churchcore_getMasterDataEntry(10, "FollowUp-Typen", "followupTypes", "cdb_followup_typ","id");
  $res[11]=churchcore_getMasterDataEntry(11, "FollowUp-Typen-Intervalle", "followupTypIntervall", "cdb_followup_typ_intervall","followup_typ_id,count_no");
  $res[13]=churchcore_getMasterDataEntry(13, "Nationalitaet", "nationalitaet", "cdb_nationalitaet");
  $res[14]=churchcore_getMasterDataEntry(14, "Gruppenteilnehmestatus", "groupmembertypes", "cdb_gruppenteilnehmerstatus", "bezeichnung");
  //  $res[14]=churchcore_getMasterDataEntry(14, "Newsletter", "newsletter", "cdb_newsletter");
  
  return $res;
}

function churchdb_getMasterDataTables() {
  $tables=churchdb_getMasterDataTablenames();
  foreach ($tables as $value) {
    $res[$value["shortname"]]=churchcore_getTableData($value["tablename"],$value["sql_order"]);
    
    // Schaue ob es spezielle weiteren Bedarf gibt f�r die masterDatentabelle
    if (isset($value["special_func"])) {
      // Pr�fe nun anhand der JS-Func.
      if ($value["special_func"]["func"]=="editAuth") {
        foreach($res[$value["shortname"]] as $data) {
          $data->auth=getAuthForDomain($data->id, $value["shortname"]);
        }        
      }          
    }
  }
  return $res;
}

function getTagRelations() {
  $res = db_query("SELECT person_id id, tag_id from {cdb_gemeindeperson_tag} gpt, {cdb_gemeindeperson} gp
                        where gpt.gemeindeperson_id=gp.id");
  $arrs=null;
  foreach ($res as $arr) {
    $arrs[]=$arr;   
  }
  return $arrs;   
}

function getOldGroupRelations() {
  if (!user_access("view history", "churchdb")) 
    return null;
  
  $res = db_query("SELECT gp.person_id id, gpa.gruppe_id gp_id, status_no leiter, gpa.letzteaenderung d, gpa.aenderunguser user FROM {cdb_gemeindeperson_gruppe_archive} gpa, {cdb_gemeindeperson} gp 
                    where gpa.gemeindeperson_id=gp.id order by gpa.letzteaenderung desc");
  $arrs=null;
  foreach ($res as $arr) {
    $arrs[]=$arr;   
  }
  return $arrs;   
}

function churchdb_getUserSettings($user_pid) { 
  $arr=churchcore_getUserSettings("churchdb", $user_pid);
  if (!isset($arr["mailerType"]))
    $arr["mailerType"]=0;
  if (!isset($arr["mailerSeparator"]))
    if (variable_get('churchdb_emailseparator', ';')==';')
      $arr["mailerSeparator"]=0;
    else   
      $arr["mailerSeparator"]=1;    
      if (isset($arr["filter"])) {
        $arr["filter"]=unserialize($arr["filter"]);    
      }
  return $arr;  
}


function _churchdb_getPersonById($id) {
  global $user;

  // Erst m�ssen wir nach View all schauen, dann gibt es noch die M�glichkeit, dass ich in seiner Gruppe bin!  
  $arr=user_access("view alldata","churchdb");
  $data=null;
  if ($arr!=null) {
    // Hole alle passenden Leute aus dem Bereich
    $res=db_query("SELECT p.name, p.vorname, p.id, p.cmsuserid,  p.email, p.telefonprivat, p.telefongeschaeftlich, p.telefonhandy, gp.imageurl
        from {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_bereich_person} bp where p.archiv_yn=0 and 
        bp.person_id=p.id and gp.person_id=p.id and bp.bereich_id in (".implode(",",$arr).") and p.id in ($id)");
    foreach ($res as $k) {
      $data[$k->id]=$k;
    }
  }
  // Hole meine Gruppen, bei denen ich schauen darf
  $ids=churchdb_getMyGroups($user->id, true, false);
  if (count($ids)>0) {
  
    // Hole nun die Person in den Gruppen die passen
    $res=db_query("SELECT p.name, p.vorname, p.id, p.cmsuserid, p.email, p.telefonprivat, p.telefongeschaeftlich, p.telefonhandy, gp.imageurl
          FROM {cdb_gemeindeperson} gp, {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg
          WHERE p.archiv_yn=0 and gpg.gemeindeperson_id = gp.id AND gp.person_id = p.id
            and gpg.gruppe_id in (".implode(",",$ids).")
            and p.id in ($id) order by p.vorname, p.name");
    foreach ($res as $k) {
      // Wenn er nciht schon oben gef�llt wurde
      if (!isset($data[$k->id])) {
        $data[$k->id]=$k;
      }
    }
  }
    
  $arrs["data"]=$data;
  $arrs["result"]="ok";
  return $arrs;
}

/**
 * Holt sich eine Person entweder in den Gruppen in den ich auch bin oder die Bereiche, wo ich ViewAll habe. 
 * @param unknown_type $searchpattern Suchparameter
 * @param withMyDepartemtnt (Default=false) soll ich auch in dem Bereich suchen, in dem ich bin, auch wenn ich da kein ViewAll habe?
 * return: array mit (name="Vorname Nachname" und Id=123)
 */
function _churchdb_getPersonByName($searchpattern, $withMyDepartment=false) {
  global $user;
  // Erst m�ssen wir nach View all schauen, dann gibt es noch die M�glichkeit, dass ich in seiner Gruppe bin!  
  $arr=user_access("view alldata","churchdb");
  
  if ($withMyDepartment)
    if ($arr!=null)
      $arr=array_merge($arr, churchdb_getAllowedDeps());
    else
      $arr=churchdb_getAllowedDeps();
  
  $data=null;
  if ($arr!=null) {
    // Hole alle passenden Leute aus dem Bereich
    $res=db_query("select p.*, gp.imageurl from {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_bereich_person} bp where
        p.archiv_yn=0 and bp.person_id=p.id and gp.person_id=p.id and bp.bereich_id in (".implode(",",$arr).") and
        (upper(name) like upper('".$searchpattern."%') or upper(vorname) like upper('".$searchpattern."%')
          or (concat(upper(vorname),' ',upper(name)) like upper('".$searchpattern."%') )
          or (concat(upper(spitzname),' ',upper(name)) like upper('".$searchpattern."%') )
          ) 
        
        order by vorname, name");
    foreach ($res as $k) {
      $arr=array();
      $arr["id"]=$k->id;
      if ($k->spitzname!="") {
        $arr["name"]="$k->vorname ($k->spitzname) $k->name";
        $arr["shortname"]="$k->spitzname $k->name";
      }
      else {  
        $arr["name"]=$k->vorname." ".$k->name;
        $arr["shortname"]="$k->vorname $k->name";
      }
      $arr["imageurl"]=$k->imageurl;
      $data[$k->id]=$arr;
    }
  }
  // Hole meine Gruppen, bei denen ich schauen darf
  $ids=churchdb_getMyGroups($user->id, true, false);
  // Hole nun die Person in den Gruppen die passen
  if (sizeof($ids)>0) {
    $res=db_query("SELECT p.name, p.vorname, p.id, gp.imageurl
          FROM {cdb_gemeindeperson} gp, {cdb_person} p, {cdb_gemeindeperson_gruppe} gpg
          WHERE p.archiv_yn=0 and gpg.gemeindeperson_id = gp.id AND gp.person_id = p.id
            and gpg.gruppe_id in (".implode(",",$ids).")
          AND (upper(p.vorname) like upper('".$searchpattern."%') or upper(p.vorname) like upper('".$searchpattern."%')) order by p.vorname, p.name");
    foreach ($res as $k) {
      // Wenn er nciht schon oben gef�llt wurde
      if (!isset($data[$k->id])) {
        $arr=array();
        $arr["id"]=$k->id;
        $arr["name"]=$k->vorname." ".$k->name;
        $arr["imageurl"]=$k->imageurl;
        $data[]=$arr;
      }
    }
  }
  
  $arrs["result"]="ok";
  $arrs["data"]=$data;
  return $arrs;
}

function churchdb_getAuthForAjax() {
  global $config; 
  $auth=$_SESSION["user"]->auth["churchdb"];
  $allowedDeps=churchdb_getAllowedDeps();
  $res["dep"]=churchcore_getTableData("cdb_bereich","","id in (".implode(",",$allowedDeps).")");
  if (isset($auth["view comments"]))
    foreach ($auth["view comments"] as $key=>$value) {
  	  $res["comment_viewer"][$key]=$value;
    }  
  
  if (isset($auth["view address"]))
    $res["viewaddress"]=true;
  if (isset($auth["view alldetails"])) {
    $res["viewaddress"]=true;
    $res["viewalldetails"]=true;
  }
  if (isset($auth["view statistics"]))
    $res["viewstats"]=true;
  if (isset($auth["view history"]))
    $res["viewhistory"]=true;
  if (isset($auth["view tags"]))
    $res["viewtags"]=true;

  if (isset($auth["edit groups"]))
    $res["editgroups"]=true;
  if (isset($auth["edit relations"]))
    $res["editrelations"]=true;
    
  if (isset($auth["export data"]))
    $res["export"]=true;

  if (isset($auth["write access"]))
    $res["write"]=true;
    
  if (isset($auth["view archive"])) {
    $res["viewarchive"]=true;
  }
  
  if (isset($auth["edit masterdata"])) {
    $res["admin"]=true;
    $res["read"]=true;
    $res["write"]=true;
    $res["export"]=true;
    $res["viewalldata"]=true;
    $res["viewalldetails"]=true;
    $res["viewhistory"]=true;
    $res["viewtags"]=true;
    $res["editgroups"]=true;
    $res["editrelations"]=true;
    $res["viewstats"]=true;
    $res["groupstats"]=true;
    $res["admingroups"]=true;
    $res["write"]=true;
  }
  if (isset($auth["administer groups"])) {
    $res["admingroups"]=true;
    $res["editgroups"]=true;
  }
  else if (isset($auth["view group"])) {
    $res["viewgroups"]=$auth["view group"];
  }
  
  if (isset($auth["view group statistics"])) {
    $res["viewgroupstats"]=true;
  }
  // TODO: Hier mu� nach Bereich unterschieden werden
  if (isset($auth["view alldata"])) {
    $res["viewalldata"]=true;
  } 
  
  if (user_access("administer persons","churchcore"))
    $res["adminpersons"]=true;
    
  if (isset($auth["edit newsletter"]))  
    $res["newsletter"]=$auth["edit newsletter"];
  
  if (($config["churchdb_smspromote_apikey"]!="") && (isset($auth["send sms"])))  
    $res["sendsms"]=true;
    
  return $res;

}

function churchdb_getAuthTable() {
  $res=db_query("select * from {cc_auth}");
  $auth=null;
  foreach($res as $entry) {
    $auth[$entry->modulename][$entry->auth]=$entry;    
  }
  return $auth;  
}

function churchdb_saveDomainAuth($params) {
  db_query("delete from {cc_domain_auth} where domain_id=".$params["id"]." and domain_type='".$params["domain_type"]."'");
//  print_r($params);
  foreach($params as $key=>$val) {
    if (($val) && (strpos($key,"authid")===0)) {
      $key=substr($key,6,99);
      $pos=strpos($key,"_");
      $fields=array("domain_id"=>$params["id"], "domain_type"=>$params["domain_type"], "auth_id"=>$key);
      if ($pos>0)
        $fields["daten_id"]=substr($key,$pos+1,99);
      db_insert("cc_domain_auth")->fields($fields)->execute();        
    }
  }
  return "ok";
}
function churchdb_addPersonAuth($id, $auth_id) {
  $fields=array("domain_id"=>$id, "domain_type"=>"person", "auth_id"=>$auth_id);
  db_insert("cc_domain_auth")->fields($fields)->execute();        
  return "ok";
}
function churchdb_deactivatePerson($id) {
  // Entferne direkt zugeordnete Berechtigungen.
  db_query("delete from {cc_domain_auth} where domain_type='person' and domain_id=:id", array(":id"=>$id));
  // Setze Person auf deactive
  db_query("update {cdb_person} set active_yn=0, loginstr=null where id=:id", array(":id"=>$id));
  
  return jsend()->success();
}

function churchdb_activatePerson($id) {
  // Setze Person auf active
  db_query("update {cdb_person} set active_yn=1 where id=:id", array(":id"=>$id));
  
  return jsend()->success();
}

function churchdb_setPersonPassword($id, $password) {
  if ((!isset($password)) || ($password==null) || ($password==""))
    return jsend()->fail("Password nicht akzeptiert");
        
  db_query("update {cdb_person} set password=md5(:password) where id=:id", array(":id"=>$id, ":password"=>$password));
  
  return jsend()->success();
}


/**
 * Person mit der p_id id bekommt einen Loginstr und diesen per Mail zugestellt. Damit kann man sich einmal einloggen.
 * @param unknown_type $id
 * @param inviter = user object, wer den eingeladen hat oder null, wenn es Anonym ist.
 */
function churchdb_invitePersonToSystem($id, $inviter=null) {
  global $base_url;

  $loginstr=churchcore_createPersonLoginStr($id);
  $content="<h3>Hallo [Vorname],</h3>";
  if ($inviter==null)
    $content.="<p>Du wurdest zur ";
  else 
    $content.="<p>".$inviter->vorname." ".$inviter->name." hat Dich zu der ";
  $content.="Nutzung von ".variable_get('site_name', 'drupal')." eingeladen.";
  $content.="<p>Klicke auf die folgende Schaltfl&auml;che, um Dich direkt dort anzumelden und um Dein Passwort zu w&auml;hlen:";
  $content.='<p><a href="'.$base_url."?q=profile&loginstr=$loginstr&id=$id".'" class="btn btn-royal">Auf %sitename anmelden</a>';
  if ($inviter==null)
    $res = churchcore_sendEMailToPersonIds($id, "Einladung zu ".variable_get('site_name', 'drupal'), $content, variable_get('site_mail'),true);
  else    
    $res = churchcore_sendEMailToPersonIds($id, "Einladung zu ".variable_get('site_name', 'drupal'), $content, $inviter->email,true);
    
  cdb_log("Person $id wurde zu ".variable_get('site_name', 'drupal')." eingeladen:".$content,2,$id);   
}

function churchdb_addMailchimpRelation($params) {
  $i=new CTInterface();
  $i->setParam("gruppe_id");
  $i->setParam("mailchimp_list_id");
  $i->setParam("optin_yn");
  $i->setParam("goodbye_yn");
  $i->setParam("notifyunsubscribe_yn");
  $i->addModifiedParams();    
  try {
    $res=db_insert("cdb_gruppe_mailchimp")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->execute(false);
  } 
  catch (Exception $e) {
    return jsend()->error($e);      
  }
  return jsend()->success($res);    
}
function churchdb_delMailchimpRelation($params) {
  db_query("delete from {cdb_gruppe_mailchimp} 
            where mailchimp_list_id=:list_id and gruppe_id=:gruppe_id",
    array(":gruppe_id"=>$params["gruppe_id"], ":list_id"=>$params["mailchimp_list_id"]));
  return jsend()->success();    
}

function churchdb_loadMailchimp() {
  global $config;
  
  $db=db_query("select * from {cdb_gruppe_mailchimp}");
  $zuordnung=array();
  foreach ($db as $g) {
    $zuordnung[]=$g;
  }
  
  include_once("system/assets/mailchimp-api-class/inc/MCAPI.class.php");
  $api = new MCAPI($config["churchdb_mailchimp_apikey"]);
 
  $res=new stdClass();
  $res->lists=$api->lists();
  if ($api->errorCode){
    $txt="Unable to load lists()! Code=".$api->errorCode. " Msg=".$api->errorMessage;
    return jsend()->fail($txt);
  } 
  else {
    $res->zuordnung=$zuordnung;  
    return jsend()->success($res);
  }
}



function churchdb_smspromote($param) {
  global $config, $user;
  $url = "https://gateway.smspromote.de"; // URL des Gateways
//  $url = "https://gateway.smspromote.de/bulk/";
  $request = ""; // Request Variable initialisieren
  $param["key"] = $config["churchdb_smspromote_apikey"]; // Gateway Key
  $param["route"] = "gold";// Nutzung der Goldroute
  //$param["route"] = "basic";// Nutzung der Goldroute
  $param["debug"] = "0";// SMS wird nicht versendet - Testmodus
  
  $param["message"]=utf8_decode($param["message"]);
  
  foreach($param as $key=>$val) {
    $request.= $key."=".urlencode($val); 
    $request.= "&"; 
  }
  
  /*** �ber file, Problem, man mu� allow_url_fopen=on setzen!
  // Versenden der SMS
  $response = @file($url."?".$request); // Request absetzen
  $response_code = intval($response[0]); // Responsecode auslesen

  **/ 
  
  /** �BER FSOCKOPEN, GEHT KEIN SSL, ODER? *///
  // Connection vorbereiten
  $host = "gateway.smspromote.de";
  $script = "/";
  $request_length = strlen($request);
  $method = "POST";
  
  // HTTP Header generieren Momentan �ber 1.0, damit es kein Chunked gibt.
  $header = "$method $script HTTP/1.0\r\n";
  $header .= "Host: $host\r\n";
  $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $header .= "Content-Length: $request_length\r\n";
  $header .= "Connection: close\r\n\r\n";
  $header .= "$request\r\n";

  // Connection �ffnen
  $socket = @fsockopen($host, 80, $errno, $errstr);
  if ($socket) // wenn ge�ffnet, dann...
  {
    fputs($socket, $header); // Header senden
    while(!feof($socket))
    {
      $output[] = fgets($socket); // Response empfangen
    }
    fclose($socket);
  }
  
//  print_r($output);
  
  $response_code = intval($output[count($output)-1]);
  
  $response_code_arr=array();
  $response_code_arr[0] = "Keine Verbindung zum Gateway";
  $response_code_arr[10] = "Empfänger fehlerhaft";
  $response_code_arr[20] = "Absenderkennung zu lang";
  $response_code_arr[30] = "Nachrichtentext zu lang";
  $response_code_arr[31] = "Messagetyp nicht korrekt";
  $response_code_arr[40] = "Falscher SMS-Typ";
  $response_code_arr[50] = "Fehler bei Login";
  $response_code_arr[60] = "Guthaben zu gering";
  $response_code_arr[70] = "Netz wird von Route nicht unterstützt";
  $response_code_arr[71] = "Feature nicht über diese Route möglich";
  $response_code_arr[80] = "SMS konnte nicht versendet werden";
  $response_code_arr[90] = "Versand nicht möglich";
  $response_code_arr[100] = "SMS wurde erfolgreich versendet.";
  
  $body=$param["message"]."<br><br><i>Status: ".$response_code_arr[$response_code]."</i>";
  
  db_query('insert into {cc_mail_queue} (receiver, sender, subject, body, htmlmail_yn, priority, 
     modified_date, modified_pid, send_date, error, reading_count) values 
     (:receiver, :sender, :subject, :body, :htmlmail_yn, :priority, 
         :modified_date, :modified_pid, :send_date, :error, :reading_count)',
     array(":receiver"=>$param["to"], ":sender"=>"$user->vorname $user->name", 
          ":subject"=>shorten_string($param["message"], 30),
          ":body"=>$body, ":htmlmail_yn"=>0, ":priority"=>1, 
         ":modified_date"=>current_date(), ":modified_pid"=>$user->id, ":send_date"=>current_date(), 
          ":error"=>($response_code==100?0:1), ":reading_count"=>0));
       
  return $response_code_arr[$response_code];
}

function churchdb_sendsms($ids, $txt) {
  global $user;
  $param=array();
  // Hole Handynummer aus der DB, es kann sein, dass in der Session noch eine alte steckt.
  $handy=db_query("select telefonhandy from {cdb_person} where id=:id", array(":id"=>$user->id))->fetch();
  if (($handy!=false) && ($handy->telefonhandy!=""))
    $param["from"] = preg_replace('![^0-9]!', '', $handy->telefonhandy); 
  else
    $param["from"]="ChurchTools";
  
  $db=db_query("select id, telefonhandy, vorname, name, spitzname from {cdb_person} where id in (".implode(',',$ids).")");
  $res=array();
  $res["withoutmobilecount"]=0;
  $res["smscount"]=0;
  
  foreach($db as $p) {
    if ($p->telefonhandy=="") {
      $res["withoutmobilecount"]++;
      $res[$p->id]="Keine SMS gesendet, Person hat keine Handynummer gespeichert!"; 
    }
    else {
      $param["to"]=$p->telefonhandy; 
      $mailtxt=$txt;     
      $mailtxt=str_replace("[Vorname]",$p->vorname,$mailtxt);
      $mailtxt=str_replace("[Nachname]",$p->name,$mailtxt);
      $mailtxt=str_replace("[Spitzname]",($p->spitzname==""?$p->vorname:$p->spitzname),$mailtxt);
      $mailtxt=str_replace("[Id]",$p->id,$mailtxt);
      $param["message"]=$mailtxt;       
      $res[$p->id]=churchdb_smspromote($param);
      $res["smscount"]++;
    }    
  }
  return jsend()->success($res);  
}

function churchdb_ajax() {
global $user, $base_url, $files_dir;
  $t=microtime(true);

  $user_pid = churchcore_getCurrentUserPid();      

  $function="";
  if (isset($_GET["func"]))
    $function=$_GET["func"];
  else if (isset($_POST["func"])) 
    $function=$_POST["func"];
  else {
    $res[]="Unknown function call: ";
  } 
  
  if (isset($_GET["id"]))
    $id=$_GET["id"];
  else $id=null;  
  if (isset($_GET["sub"]))
    $sub=$_GET["sub"];
  else $sub="-";  
  $res="";
  $timer="start:".round(microtime(true)-$t,3)." ";
  
  switch ($function) {
    case "getMasterData":
      
    $res=churchdb_getMasterDataTables();
$timer.="masterData:".round(microtime(true)-$t,3)." ";

    $res["feldtyp"]=churchcore_getTableData("cdb_feldtyp");
    $res["fields"]=getAllFields();
    $res["groups"] = getAllGroups();
    $res["tags"] = getAllTags();
    $res["FUNachfolgeDomains"] = array("0"=> array ("id"=>"0", "bezeichnung"=>"Kein"), 
                                       "1"=> array ("id"=>"1", "bezeichnung"=>"Gruppentyp"),
                                       "2"=> array ("id"=>"2", "bezeichnung"=>"Distrikt"),
                                       "3"=> array ("id"=>"3", "bezeichnung"=>"Gruppe")
    );
    $res["groupMemberTypes"] = getGroupMemberTypes();
    $res["groupFilterTypes"] = churchdb_getGroupFilterTypes();

    // Das sind sie Stammdateninfos f�r die Stammdatenpflege und die Statistik    
    if ((user_access("edit masterdata", "churchdb")) || (user_access("view statistics", "churchdb")))
      $res["masterDataTables"] = churchdb_getMasterDataTablenames();

    $res["user_pid"]=$user_pid;
    $res["userid"]=$user->vorname." ".$user->cmsuserid." [".$user_pid."]";
    $res["auth"] = churchdb_getAuthForAjax();
    $res["site_name"] = variable_get("site_name");
    $res["version"] = churchdb_getVersion();
    $res["modulespath"] = churchdb_getModulesPath();
    $res["files_url"] = $base_url.$files_dir; 
    $res["modulename"] = "churchdb";
    $res["adminemail"] = variable_get('site_mail', '');    
    $res["max_exporter"] = variable_get('churchdb_maxexporter', '150');
    $res["groupnotchoosable"] = variable_get('churchdb_groupnotchoosable', 30);
    $res["home_lat"] = variable_get('churchdb_home_lat', '53.568537');
    $res["home_lng"] = variable_get('churchdb_home_lng', '10.03656');
    $res["settings"] = churchdb_getUserSettings($user_pid);
    $res["last_log_id"] = churchdb_getLastLogId();
    $res["mailchimp"] = variable_get('churchdb_mailchimp_apikey')!="";
    if (user_access("administer persons", "churchcore")) 
      $res["auth_table"] = churchdb_getAuthTable();

    if (isset($res["auth"]["edit newsletter"])) {
      $nl=churchdb_getTableData("cdb_newsletter");
      $newsletter=array();  
      foreach ($res["auth"]["edit newsletter"] as $n) {
        $newsletter=$nl[$n];
      }  
      $res["newsletter"]=$newsletter;
    }
      
$timer.="end:".round(microtime(true)-$t,3)." ";
	  $res["timer"]=$timer;

	break;
  case "getAllRels": 
    $res["rels"] = getAllRelations();
    break;
  case "mailchimp":
    if ($_GET["sub"]=="load") 
      $res = churchdb_loadMailchimp();
    else if ($_GET["sub"]=="add")
      $res = churchdb_addMailchimpRelation($_GET);    
    else if ($_GET["sub"]=="del")
      $res = churchdb_delMailchimpRelation($_GET);    
    break;
  case "sendsms":
    if (!user_access("send sms", "churchdb"))
      $res = jsend()->error("Keine Berechtigung!");
    else  
      $res = churchdb_sendsms($_GET["ids"], $_GET["smstxt"]);
    break;  
  case "pollForNews": 
    $res = churchdb_pollForNews($_GET["last_id"]);
    break;
  case "getAllPersonData": 
	  if (isset($_GET["p_id"])) {
	    // Pr�fe gegen SQL-Injection
	    if (!is_numeric($_GET["p_id"])) { echo "Unallowed access!"; return null;};
	    $res["persons"] = churchdb_getAllowedPersonData('archiv_yn=0 and p.id='.$_GET["p_id"]);
	  }
	  else  
        $res["persons"] = churchdb_getAllowedPersonData('archiv_yn=0');
	  break;
  case "getAllPersonArchiveData": 
      $res["persons"] = churchdb_getAllowedPersonData('archiv_yn=1');
	  break;
 case "getOptigemPersonData":
    $res='OPTIGEM nur bei der ChurchTools Pro-Version!';
    break;
    
  case "getPersonDetails": 
	  $res = churchdb_getPersonDetails($id);
    cdb_log("f:$function id:$id",3,$id);
	  break;
  case "getPersonDetailsLogs":
    $res = churchdb_getPersonDetailsLogs($id);
    break;
	case "getSearchableData": 
	  $res["searchable"] = getSearchableData();
	  $res["oldGroupRelations"] =getOldGroupRelations();
	  $res["tagRelations"] =getTagRelations();
	  break;
	case "f_geocode_person":
		saveGeocodePerson($id,$_GET["lat"],$_GET["lng"]);
    $res="ok";
    break;
	case "f_geocode_gruppe":
    saveGeocodeGruppe($id,$_GET["lat"],$_GET["lng"]);
    $res="ok";
    break;
	case "createAddress":
	  if (!user_access("write access","churchdb"))
	    $res="Keine Rechte!";
	  else {  
        $res=createAddress($_GET);
        if (isset($res["id"]))
         cdb_log("f:$function res:".$res["result"]." ".(isset($_GET["givenname"])?$_GET["givenname"]:$_GET["vorname"])." ".$_GET["name"]." ".$_GET["Inputf_dep"]." ".$_GET["Inputf_status"],2,$res["id"],CDB_LOG_PERSON,1);
	  }
    break;
	case "createGroup":
      $res=createGroup($_GET["name"],$_GET["Inputf_grouptype"],$_GET["Inputf_district"], (isset($_GET["force"])?$_GET["force"]:null));
      cdb_log("f:$function res:".$res["result"]." ".$_GET["name"]." ".$_GET["Inputf_grouptype"],3,$res["id"],CDB_LOG_GROUP,1);
    break;
  case "deleteLastGroupStatistic":
    $res=churchdb_deleteLastGroupStatistik($id);
    break;
  case "deleteGroup":
      $res=deleteGroup($id);
      cdb_log("f:$function id:".$id,2,$id,CDB_LOG_GROUP,1);
    break;
    case "f_image":
		$res=saveImage($id, $_GET["url"]);		
        cdb_log("f:$function id:$id ".$_GET["url"],2,$id,CDB_LOG_PERSON,1);
		break;
	case "f_bereich": 
      $res=saveBereich($id);
      cdb_log("f:$function id:$id",3,$id,CDB_LOG_PERSON,1);
      break;
	case "f_note": 
      $res=saveNote($id,$_GET["note"],$_GET["comment_viewer"],(isset($_GET["relation_name"])?$_GET["relation_name"]:"person"));      
      if (isset($_GET["followup_count_no"])) {
        $gp_id=_churchdb_getGemeindepersonIdFromPersonId($id);
        db_query("UPDATE {cdb_gemeindeperson_gruppe} set followup_count_no=".$_GET["followup_count_no"].", followup_add_diff_days=".$_GET["followup_add_diff_days"].
                    " where gemeindeperson_id=".$gp_id." and gruppe_id=".$_GET["followup_gid"]);
      }
      cdb_log("f:$function ".$_GET["note"]." ".$_GET["comment_viewer"],3,$id,CDB_LOG_PERSON,1);
    break;
	case "add_rel":
	  $res=addRelation($id, $_GET["child_id"], $_GET["rel_id"]);
	  cdb_log("Neue Beziehung zu ".$_GET["child_id"]." Typ:".$_GET["rel_id"],2,$id,CDB_LOG_PERSON,1);
	break;  
	case "del_rel":
	  $res=delRelation($_GET["rel_id"]);
	  cdb_log("Beziehung ".$_GET["rel_id"]." entfernt",2,$id,CDB_LOG_PERSON,1);
	  break;
	case "GroupMeeting":
    cdb_log("f:$function id:$id sub:$sub",3);
		if ($_GET["sub"]=="getList")
        $res=getGroupMeeting($_GET["g_id"]);
      else if ($_GET["sub"]=="canceled")
        $res=cancelGroupMeeting($_GET["gt_id"]);
      else if ($_GET["sub"]=="create") 
        createGroupMeetings();
      else if ($_GET["sub"]=="stats") 
        $res=getGroupMeetingStats($id);
      else if ($_GET["sub"]=="delete") 
        $res=deleteGroupMeetingStats($id);
      else if ($_GET["sub"]=="saveProperties") 
        $res=savePropertiesGroupMeetingStats($_GET);
      else if ($_GET["sub"]=="editCheckin")  
        $res=editCheckinGroupMeetingStats($_GET);
      else 
        $res="Error in GroupMeeting"; 
	  break;
  case "addEvent":
      $res=churchdb_addEvent($_GET);
    break;	  
  case "del_note": 
    if (user_access("write access","churchdb")) {
      $sql="DELETE FROM {cdb_comment} WHERE id=".$_GET["comment_id"];
      db_query($sql);
      cdb_log("Kommentar geloescht ".$_GET["comment_id"],2,$_GET["id"],CDB_LOG_PERSON,1);
      $res="ok";
	}
    break;
  case "setCMSUser" :
  	cdb_log("f:$function id:$id",3,$id,CDB_LOG_PERSON,1);
  	$res=setCMSUser($id, $user->cmsuserid);
  	break;
  case "send_email" :
    cdb_log("f:$function ".$_GET["subject"]." ".$_GET["body"],3);    
    churchdb_send_mail($_GET["subject"],$_GET["body"],$_GET["to"]);
    $res="ok";
  	break;
  case "delete_user":
    if ((user_access("administer persons","churchcore")) || (user_access("edit masterdata","churchdb"))) {
      cdb_log("f:$function id:$id",2,$id,CDB_LOG_PERSON,1);
      $res=deleteUser($id);
    } else $res="Nicht genuegend Recht zum loeschen!";
    break;  
  case "archivePerson":
    if ((user_access("administer persons","churchcore"))) {
      cdb_log("f:$function id:$id",2,$id,CDB_LOG_PERSON,1);
      $res=archiveUser($id);
    } else $res="Nicht genuegend Rechte!";
    break;  
  case "undoArchivePerson":
    if ((user_access("administer persons","churchcore"))) {
      cdb_log("f:$function id:$id",2,$id,CDB_LOG_PERSON,1);
      $res=archiveUser($id, true);
    } else $res="Nicht genuegend Rechte!";
    break;  
  case "auth" :
    print_r(churchdb_getAuthForAjax());
    break; 
  case "deleteMasterData" :
    if ((user_access("edit masterdata","churchdb")) && (churchcore_isAllowedMasterData(churchdb_getMasterDataTablenames(), $_GET["table"]))) {
      churchcore_deleteMasterData($id, $_GET["table"]);
      cdb_log("f:$function id:$id",2,-1,CDB_LOG_MASTERDATA,1);
      $res=jsend()->success();
    }
    else $res=jsend()->error("Keine Rechte! ".(!user_access("edit masterdata","churchdb")?"Bitte edit masterdata vergeben.":"Unerlaubte Tabelle!"));
    break;
  case "saveMasterData" :
    if ((user_access("edit masterdata","churchdb")) && (churchcore_isAllowedMasterData(churchdb_getMasterDataTablenames(), $_GET["table"]))) {      
      churchcore_saveMasterData($id, $_GET["table"]);
      cdb_log("f:$function id:$id",2,-1,CDB_LOG_MASTERDATA,1);
      $res=jsend()->success();
    } 
    else $res=jsend()->error("Keine Rechte! ".(!user_access("edit masterdata","churchdb")?"Bitte edit masterdata vergeben.":"Unerlaubte Tabelle!"));
    break;  
  case "saveSetting":
    churchcore_saveUserSetting("churchdb", $user_pid, $sub, $_GET["val"]);
    $res="ok";
    break;     
  case "test" :
    print $base_url;
    print "\nchurchdb_sendgroupmails: ".variable_get('churchdb_sendgroupmails', true);
    break;
  case "saveMyFilter":
    $data=churchcore_getUserSettings("churchdb",$user_pid);
    $arr=array();
    if (isset($data["filter"]))
      $arr=unserialize($data["filter"]);
    $arr[$_GET["name"]]=$_GET["filter"];
    churchcore_saveUserSetting("churchdb",$user_pid,"filter",serialize($arr));
    
    $res="ok";
    break;    
  case "delMyFilter":
    $data=churchcore_getUserSettings("churchdb",$user_pid);  
    $arr=unserialize($data["filter"]);    
    unset($arr[$_GET["name"]]);
    churchcore_saveUserSetting("churchdb",$user_pid,"filter",serialize($arr));
    $res="ok";
    break;
  case "delPersonTag":    
    $gp_id=_churchdb_getGemeindepersonIdFromPersonId($id);    
    db_query("delete from {cdb_gemeindeperson_tag} where tag_id=".$_GET["tag_id"]." and gemeindeperson_id=$gp_id");
    break;    
  case "delGroupTag":    
    db_query("delete from {cdb_gruppe_tag} where tag_id=".$_GET["tag_id"]." and gruppe_id=$id");
    break;    
  case "addNewTag":
    $dt = new DateTime();    
    $new_id=db_insert('cdb_tag')->fields(array(
      "bezeichnung"=>$_GET["bezeichnung"], 
      "letzteaenderung"=>$dt->format('Y-m-d H:i:s'),
      "aenderunguser"=>$user->cmsuserid))->execute();
    $res=$new_id;
    cdb_log("$function - addNewTag: ".$_GET["bezeichnung"],2,$new_id,CDB_LOG_TAG);
    break;    
  case "addPersonTag":
    $dt = new DateTime();   
    $gp_id=_churchdb_getGemeindepersonIdFromPersonId($id);    
    $new_id=db_insert('cdb_gemeindeperson_tag')->fields(array(
      "gemeindeperson_id"=>$gp_id,
      "tag_id"=>$_GET["tag_id"],
      "letzteaenderung"=>$dt->format('Y-m-d H:i:s')))->execute();
    $res="ok";
    cdb_log("$function - addPersonTag: ".$_GET["tag_id"]." P:".$id,2,$id,CDB_LOG_PERSON,1);
    break;    
  case "addGroupTag":
    $dt = new DateTime();   
    $new_id=db_insert('cdb_gruppe_tag')->fields(array(
      "gruppe_id"=>$_GET["id"],
      "tag_id"=>$_GET["tag_id"],
      "letzteaenderung"=>$dt->format('Y-m-d H:i:s')))->execute();
    $res="ok";
    cdb_log("$function - addGroupTag: ".$_GET["tag_id"]." G:".$id,2,$id,CDB_LOG_PERSON,1);
    break;    
  case "addPersonDistrictRelation":
    if (!user_access("administer groups", "churchdb"))
      $res=jsend()->fail("Fehlende Berechtigung");
    else {  
      $dt = new DateTime();   
      $new_id=db_insert('cdb_person_distrikt')->fields(array(
        "person_id"=>$_GET["id"],
        "distrikt_id"=>$_GET["distrikt_id"],
        "modified_pid"=>$user->id,
      "modified_date"=>$dt->format('Y-m-d H:i:s')))->execute();
      $res=jsend()->success();
      cdb_log("$function - addPersonDistrikt: ".$_GET["distrikt_id"]." G:".$id,2,$id,CDB_LOG_PERSON,1);
    }
    break;       
  case "delPersonDistrictRelation":
    if (!user_access("administer groups", "churchdb"))
      $res=jsend()->fail("Fehlende Berechtigung");
    else {  
      db_query("delete from {cdb_person_distrikt} where person_id=:id and distrikt_id=:distrikt_id",
        array(":id"=>$_GET["id"], ":distrikt_id"=>$_GET["distrikt_id"]));      
      $res=jsend()->success();
      cdb_log("$function - delPersonDistrikt: ".$_GET["distrikt_id"]." G:".$id,2,$id,CDB_LOG_PERSON,1);
    }    
    break;
  case "addPersonGruppentypRelation":
    if (!user_access("administer groups", "churchdb"))
      $res=jsend()->fail("Fehlende Berechtigung");
    else {  
      $dt = new DateTime();   
      $new_id=db_insert('cdb_person_gruppentyp')->fields(array(
        "person_id"=>$_GET["id"],
        "gruppentyp_id"=>$_GET["gruppentyp_id"],
        "modified_pid"=>$user->id,
      "modified_date"=>$dt->format('Y-m-d H:i:s')))->execute();
      $res=jsend()->success();
      cdb_log("$function - addPersonGruppentyp: ".$_GET["gruppentyp_id"]." G:".$id,2,$id,CDB_LOG_PERSON,1);
    }
    break;       
  case "delPersonGruppentypRelation":
    if (!user_access("administer groups", "churchdb"))
      $res=jsend()->fail("Fehlende Berechtigung");
    else {  
      db_query("delete from {cdb_person_gruppentyp} where person_id=:id and gruppentyp_id=:gruppentyp_id",
        array(":id"=>$_GET["id"], ":gruppentyp_id"=>$_GET["gruppentyp_id"]));      
      $res=jsend()->success();
      cdb_log("$function - delPersonGruppentyp: ".$_GET["gruppentyp_id"]." G:".$id,2,$id,CDB_LOG_PERSON,1);
    }    
    break;
  case "delPersonGroupRelation":
    $res=_churchdb_delPersonGroupRelation($id, $_GET["g_id"]);
    break;
  case "editPersonGroupRelation":
    if (isset($_GET["followup_count_no"]))
      $f=$_GET["followup_count_no"];
    else
      $f="null";
       
    $res=_churchdb_editPersonGroupRelation($id, 
        $_GET["g_id"], 
        $_GET["leader"], 
        $_GET["date"], 
        $f,
        (isset($_GET["comment"])?$_GET["comment"]:null)
      );
    break;
  case "addPersonGroupRelation":
    $res=churchdb_addPersonGroupRelation($id, 
        $_GET["g_id"], 
        $_GET["leader"], 
        $_GET["date"], 
        (isset($_GET["followup_count_no"])?$_GET["followup_count_no"]:null),
        (isset($_GET["followup_erfolglos_zurueck_gruppen_id"])?$_GET["followup_erfolglos_zurueck_gruppen_id"]:null),
        (isset($_GET["comment"])?$_GET["comment"]:null)
      );
    break;
  case "getPersonByName":
    $res=_churchdb_getPersonByName($_GET["searchpattern"],isset($_GET["withmydeps"]));
    break;  
  case "getPersonById":
    $res=_churchdb_getPersonById($_GET["id"]);
    break;  
  case "sendInvitationMail":
    churchdb_invitePersonToSystem($_GET["id"], $_SESSION["user"]);
    $res="ok";
    break;
  case "setPersonPassword":
    if (user_access("administer persons","churchcore"))
      $res=churchdb_setPersonPassword($id, $_GET["password"]);
    else $res=jsend()->error("Nicht genug Rechte!");    
    break;
  case "sendEMailToPersonIds":
    $res = churchcore_sendEMailToPersonIds($_POST["ids"], $_POST["betreff"], $_POST["inhalt"], null, true, false);    
    break;
  case "loadAuthData":
    if (user_access("administer persons", "churchcore")) {
      $res["cdb_bereich"] = (object) churchcore_getTableData("cdb_bereich");
      $res["cdb_comment_viewer"] = (object) churchcore_getTableData("cdb_comment_viewer");
      $res["cs_servicegroup"] = (object) churchcore_getTableData("cs_servicegroup");
      $res["cs_songcategory"] = (object) churchcore_getTableData("cs_songcategory");
      $res["cc_wikicategory"] = (object) churchcore_getTableData("cc_wikicategory");
      $res["cc_calcategory"] = (object) churchcore_getTableData("cc_calcategory");
      //$res["cdb_newsletter"] = churchcore_getTableData("cdb_newsletter");
    }
    break;      
  case "deactivatePerson":
    if (user_access("administer persons","churchcore"))
      $res = churchdb_deactivatePerson($id);
    else $res=jsend()->error("Nicht genug Rechte!");    
    break;
  case "activatePerson":
    if (user_access("administer persons","churchcore"))
      $res = churchdb_activatePerson($id);
    else $res=jsend()->error("Nicht genug Rechte!");    
  break;
  case "getGroupAutomaticEMail":
    if (user_access("administer groups", "churchdb")) {
      $res=jsend()->success(db_query("select * from {cdb_gruppenteilnehmer_email} where gruppe_id=:gruppe_id and status_no=:status_no",
        array(":gruppe_id"=>$_GET["id"], ":status_no"=>$_GET["status_no"]))->fetch());
    }      
    else $res=jsend()->error("Nicht genug Rechte!");    
    break;
  case "saveGroupAutomaticEMail":
    if (user_access("administer groups", "churchdb")) {
      db_query("insert into {cdb_gruppenteilnehmer_email} (gruppe_id, status_no, aktiv_yn, sender_pid, email_betreff, email_inhalt)
           values (:gruppe_id, :status_no, :aktiv_yn, :sender_pid, :email_betreff, :email_inhalt)
            on duplicate key update aktiv_yn=:aktiv_yn, sender_pid=:sender_pid, email_betreff=:email_betreff, email_inhalt=:email_inhalt",
        array(':gruppe_id'=>$_GET["id"], 
              ':status_no'=>$_GET["status_no"],
              ':aktiv_yn'=>$_GET["aktiv_yn"],
              ':sender_pid'=>$_GET["sender_pid"],
              ':email_betreff'=>$_GET["email_betreff"],
              ':email_inhalt'=>$_GET["email_inhalt"]));
      $res=jsend()->success();
    }      
    else $res=jsend()->error("Nicht genug Rechte!");    
  break;
  case "addPersonAuth":
    if (user_access("administer persons","churchcore"))
      $res = churchdb_addPersonAuth($id, $_GET["auth_id"]);
    else $res="not enough rights!";
    break;
  case "saveDomainAuth":
    if (user_access("administer persons","churchcore"))
      $res = churchdb_saveDomainAuth($_GET);
    else $res="not enough rights!";
    break;
  case "getImportTables":
    if (user_access("edit masterdata","churchdb")) {
      $db=db_query('show tables');
      $arr=array();
      foreach ($db as $table) {
        foreach ($table as $row) {
          if (!isCTDBTable($row)) $arr[$row]=array("id"=>$row, "bezeichnung"=>$row);
        }        
      }      
      $res=jsend()->success($arr);
    }
    else $res=jsend()->error("not enough rights!");
    break;
  case "getTableContent":
    if (user_access("edit masterdata","churchdb")) {
      $res=jsend()->success(churchcore_getTableData($_GET["table"]));
    }
    else $res=jsend()->error("not enough rights!");
    break;
  case "f_address" || "f_church" || "f_category" || "f_group":
    $fields=getAllFields("intern_code='".$function."'");
    if ($fields==null) {
      $res=jsend()->error("Unbekannter Aufruf: ".$function);        	
      break;
    } 
    // Pr�fe, ob eine E_Mail-Adresse gesetz werden soll die es schon gibt ohne Schreibrechte. 
    // Das soll verhindert werden, denn sonst kann sich jemand die E-Mail eines Admins geben...
    else if ((isset($_GET["email"]) && (!user_access("write access", "churchdb")))) {
      $db=db_query("select * from {cdb_person} p where email=:email and id!=:id",
         array(":email"=>$_GET["email"], ":id"=>$_GET["id"]))->fetch();
      if ($db!=false) {
        $res="Die E-Mail-Adresse ist schon vergeben. Um eine vergebene E-Mail-Adresse zu setzen werden mehr Rechte gebraucht.";
        break;
      }
    }
  	if ($function=="f_group") {
  	  saveGeocodeGruppe($_GET["id"],"","");
    }  
    foreach ($fields["fields"] as $key=>$value) {
      if (isset($_GET[$key]))
        $arr[$key]=$_GET[$key];
    }
    // Wenn die letzteaenderung mit �bergeben wird (z.B. bei Sync mit externen Tools)
    // Soll das hier mit gesetzt werden
    if (isset($_GET['letzteaenderung']))
      $arr['letzteaenderung']=$_GET['letzteaenderung'];

    $oldarr=saveDataArray($fields, $_GET["id"], $arr);
    if (is_string($oldarr))
      $res=$oldarr;
    else {  
      $txt=churchcore_getFieldChanges($fields["fields"], $oldarr, $arr);
      if ($txt!=null) {
        if ($function=="f_group") {
      	  $txt="Gruppe: ".$arr["bezeichnung"]."\n".$txt;  
        } 
        else {
          $details=churchdb_getPersonDetails($id);
          $txt="Person: ".$details->vorname." ".$details->name." ($id)\n".$txt;
      	} 
      }  
      sendFieldNotifications($function,$txt);
       
      if ($txt!=null) 
        if ($function=="f_group") 
          cdb_log("$function - ".$txt,2,$id,CDB_LOG_GROUP,1);
        else
          cdb_log("$function - ".$txt,2,$id,CDB_LOG_PERSON,1);
      $res="ok";
    }   
    break;
	default: $res[]="Unknown function call: ".$function;    
  }
  drupal_json_output($res);
}

