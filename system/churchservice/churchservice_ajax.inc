<?php
include_once(drupal_get_path('module', 'churchcore') .'/churchcore_db.inc');
include_once(drupal_get_path('module', 'churchdb') .'/churchdb_db.inc');

// Gibt array mit g_ids zurück
function churchservice_getGroupsOfService($service_id) {
  // Erst die Gruppen_Ids aus cs_service anhand der sg_id
  $res=db_query("select cdb_gruppen_ids from {cs_service} where id=$service_id");
  $arr=array();
  foreach($res as $entry) {
    if ($entry->cdb_gruppen_ids!=null) {
      $arr[]=$entry->cdb_gruppen_ids;
    }    
  }
  // Das ist nun ein Array, bei dem auch einige mit , getrennt sind. Also Join und Explode.
  if (count($arr)==0) 
    return $arr;
  else
    return explode(",", implode(',',$arr));  
}


// Gibt array mit g_ids zurück
function churchservice_getGroupsOfServiceGroup($servicegroup_id) {
  // Erst die Gruppen_Ids aus cs_service anhand der sg_id
  $res=db_query("select cdb_gruppen_ids from {cs_service} where servicegroup_id=$servicegroup_id");
  $arr=array();
  foreach($res as $entry) {
    if ($entry->cdb_gruppen_ids!=null) {
      $arr[]=$entry->cdb_gruppen_ids;
    }    
  }
  // Das ist nun ein Array, bei dem auch einige mit , getrennt sind. Also Join und Explode.
  if (count($arr)==0) 
    return $arr;
  else
    return explode(",", implode(',',$arr));  
}

$auth=null;

function churchservice_getAuthorization() {
  global $auth;
  if (!isset($_SESSION["user"]->auth["churchservice"]))
    return null;
     
  $auth=$_SESSION["user"]->auth["churchservice"];

  $res=null;
  $user_pid=churchcore_getCurrentUserPid();
  $res["user_pid"]=$user_pid;
  
  if (user_access("view","churchdb")) 
    $res["viewchurchdb"]=true;
  
  if (user_access("administer persons", "churchcore"))
    $res["administer persons"]=true;
  
  if (isset($auth["view"]))
    $res["read"]=true;
  if (isset($auth["edit events"])) {
    $res["read"]=true;
    $res["write"]=true;
  }  
  if (isset($auth["view history"]))
    $res["viewhistory"]=true;
  if (isset($auth["export data"]))
    $res["export"]=true;
  if (isset($auth["edit template"])) {
    $res["edit template"]=true;
  }
  if (isset($auth["edit masterdata"])) {
    $res["admin"]=true;
  }
  
  if (isset($auth["manage absent"])) {
    $res["manageabsent"]=true;
  }
  if (isset($auth["view facts"])) {
    $res["viewfacts"]=true;    
  }
  if (isset($auth["export facts"])) {
    $res["exportfacts"]=true;    
  }
  if (isset($auth["edit facts"])) {
    $res["editfacts"]=true;
    $res["viewfacts"]=true;    
  }
  if (isset($auth["view song"])) {
    if (isset($auth["view songcategory"])) {
      $res["viewsong"]=true;
      $res["viewsongcategory"]=$auth["view songcategory"];
    }
  }
  if (isset($auth["edit song"])) {
    if (isset($auth["view songcategory"])) {
      $res["viewsong"]=true;
      $res["editsong"]=true;
      $res["viewsongcategory"]=$auth["view songcategory"];
    }
  }
  
  if (isset($auth["view servicegroup"])) 
    $res_view=$auth["view servicegroup"];
  else 
    $res_view=array();
  // PrÔøΩfe nach, ob ich irgendwo dabei bin
  $arr=churchcore_getTableData("cs_servicegroup","sortkey");
  
  $myTnGroups=churchdb_getMyGroups($user_pid, true, false);
  $myLdGroups=churchdb_getMyGroups($user_pid, true, true);
  
  foreach ($arr as $grp) {
    $groups=churchservice_getGroupsOfServiceGroup($grp->id);    
    if (($grp->viewall_yn==1) || (array_in_array($groups, $myTnGroups))) {
      $res_view[$grp->id]=true;
    }
  }
  $res["viewgroup"]=$res_view;
  
  if (isset($auth["edit servicegroup"])) {
    $res["editgroup"]=$auth["edit servicegroup"];
    // Copy edit permission to view permissions!
    foreach ($auth["edit servicegroup"] as $key=>$a) {
      $res["viewgroup"][$key]=$a;
    }
  }
  else $res["editgroup"]=array();
  
  // Prüfe nun, ob der aktuelle User Leiter oder zumindest Teilnehmer einer Gruppe ist
  // Dies hat entscheidende Auswirkung auf die MÔøΩglichkeiten beim ÔøΩndern der EintrÔøΩge
  $arr=churchcore_getTableData("cs_service","sortkey");
  $res_member=array();
  $res_leader=array();
  $res_edit=array();
  
  foreach ($arr as $service) {
    $groups=churchservice_getGroupsOfService($service->id);    
    if (array_in_array($groups, $myLdGroups)) {
      $res_member[$service->id]=true;
      $res_leader[$service->id]=true;
    }
    else if (array_in_array($groups, $myTnGroups)) {
      $res_member[$service->id]=true;
    }
    // Hat er in der ServiceGroup das Edit-Recht
    if (isset($res["editgroup"][$service->servicegroup_id]))
      $res_edit[$service->id]=true;
  }
  $res["memberservice"]=$res_member;
  $res["leaderservice"]=$res_leader;
  $res["editservice"]=$res_edit;
  
  
  if (isset($auth["view agenda"])) {
    $res["view agenda"]=$auth["view agenda"];    
  }
  if (isset($auth["edit agenda"])) {
    $res["edit agenda"]=$auth["edit agenda"];
    // Dupicate permission to view
    foreach ($res["edit agenda"] as $key=>$edit) {
      $res["view agenda"][$key]=$edit;
    }
  }
  if (isset($auth["edit agenda templates"])) {
    $res["edit agenda templates"]=$auth["edit agenda templates"];
  }
  
  $auth=$res;
  return $res;
}



function churchservice_getModulesPath() {
  return drupal_get_path('module', 'churchservice');
}

function churchservice_extractEventServiceData($arr, $auth=null, $event_admin=false) {
  $res=array();
  $res["id"]=(isset($arr->eventservice_id)?$arr->eventservice_id:$arr->id);
  $res["service_id"]=$arr->service_id;
  $res["counter"]=$arr->counter;
  $res["name"]=$arr->name;
  $res["cdb_person_id"]=$arr->cdb_person_id;
  $res["zugesagt_yn"]=$arr->zugesagt_yn;
  $res["valid_yn"]=$arr->valid_yn;
  $res["datum"]=$arr->modified_date;
  $res["user_id"]=$arr->modified_pid;
  $res["user"]=$arr->modifieduser;
  $res["mailsenddate"]=$arr->mailsenddate;
  if (($auth!=null) && ($arr->reason!=null)) {
    if (((isset($auth["leaderservice"])) && (isset($auth["leaderservice"][$arr->service_id])))||(isset($auth["admin"]))||($event_admin)) 
      $res["reason"]=$arr->reason;
  } 
  return $res;
}


/*
 * Holt sich neue Eventdaten die nach der LastLogId geÔøΩndert wurden.
 */
function churchservice_getNewEventData($params) {
  global $user;
  $last_id=$params["last_id"];
  $arr=db_query("select event_id from {cs_eventservice} where modified_pid!=:user and id>=:last_id group by event_id",
                   array(":user"=>$user->id, ":last_id"=>$last_id));
  $events=array();                   
  foreach ($arr as $data) {
    $event=churchservice_getAllEventData(array("id"=>$data->event_id));
    if (isset($event[$data->event_id]))
      $events[$data->event_id]=$event[$data->event_id];
  }  
  return $events;
}

/** LÔøΩdt alle Events und nur die Services die sollen **/
function churchservice_getAllEventData($params) { 
  global $user;
  $id=null;
  if (isset($params["id"])) $id=$params["id"]; 
  
  $auth=churchservice_getAuthorization();
  
  include_once(drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
  $cat=churchcal_getAllowedCategories(false, true);
  $cat[]=-1;
  
  $lastday=-variable_get('churchservice_entries_last_days');
  //$auth=user_access("view servicegroup","churchservice");
  $sql = db_query('SELECT e.id, e.startdate startdate, cal.enddate, e.cc_cal_id, cal.bezeichnung, e.special, cal.category_id, e.admin, datediff(e.startdate,CURRENT_DATE) datediff
                   FROM {cs_event} e, {cc_cal} cal where cal.id=e.cc_cal_id and '.($id!=null?"e.id=$id":"1=1").
                   " AND cal.category_id in (".implode(",",$cat).")");
  
  $sql_s = "select es.service_id, es.name, es.cdb_person_id, es.id eventservice_id, es.counter,
               es.zugesagt_yn, es.valid_yn, es.modified_date, es.modified_pid, es.mailsenddate,
               case when p.id is null then '?' else  
               concat(p.vorname, ' ',p.name) end as modifieduser, 
               es.reason, s.servicegroup_id 
               from {cs_service} s, {cs_eventservice} es left join {cdb_person} p on (es.modified_pid=p.id) 
               where es.service_id=s.id and event_id=:event_id";
  $events=array();
  if ($sql!=false) {
    foreach ($sql as $arr) {
      // Hole noch Booking_Infos, damit ich weiß, dass er hier nicht das Datum ändern darf.
      $b=db_query("select * from {cr_booking} where cc_cal_id=:cal_id", array(":cal_id"=>$arr->cc_cal_id))->fetch();
      $arr->bookings=$b!=false;
      // Check if agenda items are available for this event
      $b=db_query("select * from {cs_event_item} where event_id=:event_id limit 1", array(":event_id"=>$arr->id))->fetch();
      $arr->agenda=$b!=false;
      // Nun geht es los!
      $events[$arr->id]=$arr;
      $event_admin=false;
      if ($arr->admin==null)
        $events[$arr->id]->admin=null;
      else if (in_array($user->id, explode(",",$arr->admin))) $event_admin=true;
      if ($arr->special==null)
        $events[$arr->id]->special=null;
        
      if ((1==1) && ($arr->datediff>$lastday)) {
        $services=db_query($sql_s, array(":event_id"=>$arr->id));
        $s=array();
        foreach ($services as $service) {
          if (($service->servicegroup_id!=null) && 
             ((isset($auth["viewgroup"][$service->servicegroup_id])) || ($event_admin))) {        
            $s[]=churchservice_extractEventServiceData($service, $auth, $event_admin);      
          }
        }
        if (count($s)>0)
          $events[$arr->id]->services=$s;
      }
    }
  }
  return $events;
}

function churchService_adminOfEvent($event_id) {
  $res=db_query("select admin from {cs_event} where id=$event_id")->fetch();  
  $split = explode(",",$res->admin);
  $dabei=false;
  foreach ($split as $us) {  
    if ($us==churchcore_getCurrentUserPid())
      $dabei=true;
  }
  return $dabei;  
}

function churchservice_addOrRemoveServiceToEvent($params) {
global $user;
  $auth=churchservice_getAuthorization();
  $k=0;
  while (isset($params["col".$k])) {
    $fields=array();
    $fields["event_id"]=$params["id"];
    $fields["service_id"]=$params["col".$k];
    
    $dt = new datetime();
    $fields["valid_yn"]=1;
    $fields["modified_date"]=$dt->format('Y-m-d H:i:s');
    $fields["modified_pid"]=$user->id;
    
    $db=db_query("select count(*) c from {cs_eventservice} where event_id=:event_id and service_id=:service_id and valid_yn=1",
       array(":service_id"=>$fields["service_id"], ":event_id"=>$fields["event_id"]))->fetch();
    // Soll bleiben bzw. erstellt werden
    $soll=0;
    $ist=$db->c;
    if ((isset($params["val".$k]) && ($params["val".$k]=="checked"))) {
      if (isset($params["count".$k])) $soll=$params["count".$k];
      else $soll=1;
    }

    if ($ist!=$soll) {
      if ((!isset($auth["editservice"][$params["col".$k]])) && (!isset($auth["leaderservice"][$params["col".$k]])) && (!churchService_adminOfEvent($params["id"])))
        return "Not enough rights in addOrRemoveServiceToEvent: ".$params["col".$k];
      if (($ist==1) && ($soll>1))
        // Wenn es nur einen gibt und nun mehr geben soll, lege noch den Counter auf 1, damit es schÔøΩner aussieht.
        db_query("update {cs_eventservice} set counter=1 where event_id=:event_id and service_id=:service_id and counter is null",
           array(":event_id"=>$fields["event_id"], ":service_id"=>$fields["service_id"]));
      if (($ist>1) && ($soll==1))
        // Wenn es mehrer gibt aber nun nur noch einen geben soll, dann setze den Counter auf Null damit es schöner aussieht
        db_query("update {cs_eventservice} set counter=null where event_id=:event_id and service_id=:service_id and counter=1",
           array(":event_id"=>$fields["event_id"], ":service_id"=>$fields["service_id"]));
    }
    //echo "ist:$ist soll$soll";
    while ($ist<$soll) {
      $ist=$ist+1;
      if ($soll==1) {
        // Wenn es nur einen gibt, dann ist es der erste, hier soll Count dann null sein
        $fields["counter"]=null;
      } 
      else if ($soll>1) {
        // Nun prüfe nach dem ersten freien Count
        $count=1;
        $ok=false;
        while ((!$ok) && ($count<100)) {
          $res=db_query("select count(*) c from {cs_eventservice} where 
                   event_id=:event_id and service_id=:service_id and counter=$count",
              array(":event_id"=>$fields["event_id"], ":service_id"=>$fields["service_id"]))
            ->fetch();
          if ($res->c==0) $ok=true;
          else $count=$count+1;
        }
        $fields["counter"]=$count;
      }
        
      db_insert("cs_eventservice")
        ->fields($fields)
        ->execute();
      cdb_log("[ChurchService] Erstelle Service ".$fields["service_id"]." fuer Event",2,$fields["event_id"],"service");
    }
    while ($ist>$soll) {
      // Hole den höchsten freien Count, wenn es keinen gibt, dann stimmt was nicht, denn
      // das wird ja eigentlich über JS sichergestellt, dass nur das gelöscht werden kann, wenn was frei ist       
      $res=db_query("select max(counter) c from {cs_eventservice} where 
                   event_id=:event_id and service_id=:service_id and name is null and valid_yn=1",
          array(":event_id"=>$fields["event_id"], ":service_id"=>$fields["service_id"]))
          ->fetch();
      if ($res==null) {
        throw new CTFail("Error by query max(counter)");
      }
      else {  
        if ($res->c==null)
          $counter="counter is null";
        else $counter="counter=".$res->c;      
        db_query("delete from {cs_eventservice} where event_id=:event_id and service_id=:service_id and ".$counter,
            array(":event_id"=>$fields["event_id"], ":service_id"=>$fields["service_id"]));
        cdb_log("[ChurchService] Entferne Service ".$fields["service_id"]." $counter vom Event",2,$fields["event_id"],"service");
        $ist=$ist-1;
      }                
    }
    $k++;
  }
}

function churchservice_deleteService($params) {
  $service_id=$params["id"];
  $auth=churchservice_getAuthorization();
  if (!isset($auth["editservice"]) || (!isset($auth["editservice"][$service_id])))
    throw new CTNoPermission("editservice", "churchservice"); 
  
  cdb_log("[ChurchService] Entferne Service!",2,$service_id,"service");          
  db_query("delete from {cs_eventservice} where service_id=:service_id", array(":service_id"=>$service_id), false);
  db_query("delete from {cs_service} where id=:service_id", array(":service_id"=>$service_id), false);
}

function churchservice_editService($params) {
  $auth=churchservice_getAuthorization();

  if (!isset($auth["editgroup"]) || (!isset($auth["editgroup"][$params["servicegroup_id"]])))
    throw new CTNoPermission("editservice", "churchservice"); 
  if (($params["id"]!="null") && ($params["id"]!=""))
    if (!isset($auth["editservice"]) || (!isset($auth["editservice"][$params["id"]])))
      throw new CTNoPermission("editservice", "churchservice"); 
  
  $i=new CTInterface();
  $i->setParam("id",false);
  $i->setParam("bezeichnung");
  $i->setParam("notiz");
  $i->setParam("servicegroup_id");
  $i->setParam("cdb_gruppen_ids", false);
  $i->setParam("cdb_tag_ids", false);
  $i->setParam("cal_text_template", false);
  $i->setParam("sendremindermails_yn");
  $i->setParam("allowtonotebyconfirmation_yn");
  $i->setParam("sortkey");
  
  if ($params["id"]=="null" || $params["id"]=="") {
    db_insert("cs_service")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->execute(false);          
  }
  else {
    db_update("cs_service")
      ->fields($i->getDBInsertArrayFromParams($params, true))
      ->condition("id", $params["id"], "=")
      ->execute(false);
  } 
}

function churchservice_updateEventService($params) {
  global $user, $base_url;

  $id=$params["id"];
  $name=isset($params["name"])?$params["name"]:null;
  $cdb_person_id=(isset($params["cdb_person_id"])?$params["cdb_person_id"]:null);
  $reason=(isset($params["reason"])?$params["reason"]:null);
  $zugesagt_yn=$params["zugesagt_yn"];
  
  include_once('system/churchservice/churchservice_db.inc');
  
  $res=array();
  
  if ($name=="null") $name=null;
  if ($cdb_person_id=="null") $cdb_person_id=null;
  
  // Schaue, ob der Eintrag noch gÔøΩltig ist
  $arr=db_query("select * from {cs_eventservice} where id=:id",array(":id"=>$id))->fetch();
  if (!$arr)
    return "Eintrag nicht gefunden, id nicht gÔøΩltig!";
  if ($arr->valid_yn!=1 && !isset($params["valid_yn"]))
    return "Eintrag nicht mehr aktuell und konnte nicht angepasst, bitte neu laden!";

  // Prüfe Rechte
  $auth=churchservice_getAuthorization();
  if ((!isset($auth["editservice"][$arr->service_id])) && (!isset($auth["memberservice"][$arr->service_id])) && (!churchService_adminOfEvent($arr->event_id))
    // Es ist trotzdem erlaubt, wenn die PersonId eingetragen ist, dann wurde er ja angefragt
     && ($arr->cdb_person_id!=$user->id))
    throw new CTNoPermission("editservice","churchservice");

    
  // Wenn die neue Änderung vom gleichen User kommt und noch kein Cron gelaufen ist,
  // Oder wenn valid_yn valide ist, denn dann soll es upgedates werden! 
  // brauchen wir kein neuen Insert, sondern machen nur ein Update.
  // Denn wahrscheinlich war es vorher nur ein Versehen.
  $dt=new datetime();
  if (($arr->modified_pid==$user->id && $arr->mailsenddate==null) || (isset($params["valid_yn"]))) {
    $valid_yn=1;
    if (isset($params["valid_yn"])) $valid_yn=$params["valid_yn"];
    db_update("cs_eventservice")
      ->fields(array("name"=>$name, "cdb_person_id"=>$cdb_person_id, "valid_yn"=>$valid_yn, "zugesagt_yn"=>$zugesagt_yn, "reason"=>$reason, "mailsenddate"=>null,
                "modified_date"=>$dt->format('Y-m-d H:i:s'), "modified_pid"=>$user->id))
      ->condition("id",$id,"=")
      ->execute();    
    $new_id=$id;    
  } 
  else {
    // Neuer Eintrag für die Änderung wird angelegt  
    $new_id=db_insert("cs_eventservice")
      ->fields(array("event_id"=>$arr->event_id, "service_id"=>$arr->service_id, "valid_yn"=>1, "counter"=>$arr->counter,
                "name"=>$name, "cdb_person_id"=>$cdb_person_id, "zugesagt_yn"=>$zugesagt_yn, "reason"=>$reason,
                "modified_date"=>$dt->format('Y-m-d H:i:s'), "modified_pid"=>$user->id))
      ->execute();
  
    // Wenn alles durchlief wird nun der bestehende Eintrag auf alt gestellt
    db_update("cs_eventservice")
      ->fields(array("valid_yn"=>0))
      ->condition("id",$id,"=")
      ->execute();
  }

  include_once("system/churchcore/churchcore_db.inc");
  $leader=churchcore_getPersonById($arr->modified_pid);
  
  
  $event=db_query("select e.startdate datum, c.bezeichnung from {cs_event} e, {cc_cal} c
         where e.cc_cal_id=c.id and e.id=:event_id", (array(":event_id"=>$arr->event_id)))->fetch();
  $service=churchcore_getTableData("cs_service", "", "id=".$arr->service_id);
  
  if (($event!=false) && ($service!=null)) {
    $service=$service[$arr->service_id];
    $subject="";
    $txt="";
    // Sagt zu
    if ($zugesagt_yn==1) {
      $txt.="$user->vorname $user->name hat den Dienst $service->bezeichnung bei $event->datum $event->bezeichnung zugesagt.";
      $subject="[".variable_get('site_name')."] $user->vorname $user->name hat eine Anfrage zugesagt";
    }
    // SchlÔøΩgt jemanden vor
    else if ($name!=null) {
      $txt.="$user->vorname $user->name hat beim Dienst $service->bezeichnung bei $event->datum $event->bezeichnung $name vorgeschlagen";
      $subject="[".variable_get('site_name')."] $user->vorname $user->name hat jemanden vorgeschlagen";
    }
    // sagt ab
    else {
      $txt.="$user->vorname $user->name hat den Dienst $service->bezeichnung bei $event->datum $event->bezeichnung abgesagt";
      $subject="[".variable_get('site_name')."] $user->vorname $user->name hat eine Anfrage abgesagt";
    }    
    if ($reason!=null)
      $txt.="<p>Folgendes wurde als Grund angegeben: ".$reason;
    
    ct_notify("service", $arr->service_id, $txt);
    
    // Benachrichtige, wenn es sich um einen anderen handelt, der hier bestÔøΩtigt oder ablehnt als den Anfrager (Inquierer)
    if (($leader!=null) && ($leader->email!=null) && ($leader->email!="") && ($user!=null) && ($leader->id!=$user->id)) {
      $setting=churchcore_getUserSettings("churchservice",$leader->id);
      if (isset($setting["informInquirer"]) && ($setting["informInquirer"]==1)) {

        $txt="<h3>Hallo ".$leader->vorname.",</h3><p>".$txt;
        
        $txt.='<p><a href="'.$base_url.'?q=churchservice&id='.$arr->event_id.'" class="btn btn-primary">Event aufrufen</a>';
        churchservice_send_mail($subject, $txt, $leader->email);
      } 
      else if (!isset($setting["informInquirer"])) {
        churchcore_saveUserSetting("churchservice", $leader->id, "informInquirer", 0);
      }    
    }
  }          

  $arr=db_query("select es.*, concat(p.vorname,' ',p.name) as modifieduser from {cs_eventservice} es, {cdb_person} p 
                  where p.id=es.modified_pid and es.id=:id",array(":id"=>$new_id))->fetch();
  $res["eventservice"]=churchservice_extractEventServiceData($arr);    
  
  $res["result"]=true;
  return $res;  
}

/**
* params[ids] - Komma-separierte Liste mit GruppenIds
* Result - Array mit ids, darin enthalten, Name, Vorname, Person_id
 */
function churchservice_getPersonByGroupIds($params) {
  $ids=$params["ids"];
  $res = db_query("select g.bezeichnung, gpg.gruppe_id g_id, p.id p_id, gp.id gp_id, p.vorname, p.name, p.email, 
  gp.imageurl, p.cmsuserid, gpg.status_no leiter 
     from {cdb_person} p, {cdb_gemeindeperson} gp, {cdb_gemeindeperson_gruppe} gpg, {cdb_gruppe} g
            where gpg.gemeindeperson_id=gp.id and gp.person_id=p.id and g.id=gpg.gruppe_id 
             and gpg.status_no>=0 and gpg.gruppe_id in (".$ids.")");
  $arr=array();
  foreach ($res as $r) {
    if (!isset($arr[$r->g_id])) 
      $arr[$r->g_id]=array();
    $tags_res=db_query("select * from {cdb_gemeindeperson_tag} where gemeindeperson_id=:gp_id", array(":gp_id"=>$r->gp_id));
    $tags=array();
    foreach($tags_res as $tag) {
      $tags[]=$tag->tag_id;
    }
    $r->tags=$tags;

      
    $arr[$r->g_id][$r->p_id]=$r;
  }
  return $arr;
}

function churchservice_getUserSettings($user_pid) {
  $arr=churchcore_getUserSettings("churchservice", $user_pid);
  $arr2=churchcore_getUserSettings("churchdb", $user_pid);
  if (!isset($arr["remindMe"])) { 
    $arr["remindMe"]="1";
    churchcore_saveUserSetting("churchservice", $user_pid,"remindMe","1");
  }
  if (isset($arr2["signature"]))
    $arr["signature"]=$arr2["signature"];
  return $arr;
}

function churchservice_getLastLogId($last_id=0) {
  global $user;
  $arr=db_query("select max(id) max from {cs_eventservice} where modified_pid!=:user and id>=:last_id",
                   array(":user"=>$user->id, ":last_id"=>$last_id))->fetch();
  return $arr->max;
}

function churchservice_pollForNews($params) {
  $last_id=$params["last_id"];
  $arr["lastLogId"]=churchservice_getLastLogId($last_id);
  return $arr;
}

function churchservice_ical() {
  global $base_url, $config;
  
  if (!isset($_GET["id"])) 
    echo "Bitte id angeben!";
  else $id=$_GET["id"];
  
  drupal_add_http_header('Content-Type','text/calendar;charset=utf-8',false);
  drupal_add_http_header('Content-Disposition','inline;filename="ChurchTools.ics"',false);  
  drupal_add_http_header('Cache-Control','must-revalidate, post-check=0, pre-check=0',false);  
  drupal_add_http_header('Cache-Control','private',false);

  $content=drupal_get_header();

  include_once('./'. drupal_get_path('module', 'churchservice') .'/churchservice_db.inc');  
  $arr=churchservice_getUserCurrentServices($id);
  
  $txt="";
  foreach ($arr as $res) {
    $txt.="BEGIN:VEVENT\r\n"; 
    $txt.="ORGANIZER:MAILTO:".variable_get('site_mail', '')."\r\n";
    if ($res->zugesagt_yn==1)
      $txt.="SUMMARY:".$res->dienst." (".$res->servicegroup.")\r\n"; 
    else       
      $txt.="SUMMARY:Anfrage: ".$res->dienst." (".$res->servicegroup.")?\r\n";
    $txt.="X-MICROSOFT-CDO-BUSYSTATUS:BUSY\r\n"; 
    $txt.="URL:".$base_url."/?q=churchservice/entrylist\r\n";
    if  ($res->ort!="")
      $txt.="LOCATION:".$res->ort."\r\n";
    $txt.="DESCRIPTION:".$res->dienst." (".$res->servicegroup.") bei ".$res->event.".";
    if ($res->zugesagt_yn==1)    
      $txt.="\r\n";
    else   
      $txt.=" Anfrage von $res->vorname $res->name [$res->modified_pid]\r\n";
    $txt.="DTSTAMP:".$res->modified_date."\r\n"; 
    $txt.="UID:".$res->eventservice_id."\r\n"; 
    $txt.="DTSTART;TZID=".$config["timezone"].":".$res->datum_start."\r\n"; 
    $txt.="DTEND;TZID=".$config["timezone"].":".$res->datum_end."\r\n"; 
    $txt.="END:VEVENT\r\n"; 
  }
  echo surroundWithVCALENDER($txt);
}

function churchservice_saveNote($event_id, $text) {
  $text=str_replace("\'","'",$text);  
  $text=str_replace('\"','"',$text);  
  db_query("update {cs_event} set special=:text where id=$event_id",array(":text"=>$text));  
  return "ok";
}

function churchservice_getEventtemplateServices($auth) {
  if (isset($auth["write"])) { 
    $res = db_query("SELECT * FROM {cs_eventtemplate_service}");
    $arrs=null;
    foreach ($res as $arr) {
      if (!isset($arrs[$arr->eventtemplate_id]))
        $es=array();
      else $es=$arrs[$arr->eventtemplate_id];
      $es[$arr->service_id]=$arr->count;
      $arrs[$arr->eventtemplate_id]=$es;
    }
    return $arrs;   
  }
  return null;
}

/**
 * 
 * @param $template_id wenn null, dann insert, ansonsten update
 * @param $stunde
 * @param $minute
 * @param $category_id
 * @param $event_bezeichnung
 * @param $special
 * @param $admin
 * @param $services
 */
function churchservice_updateOrInsertTemplate($template_id, $bezeichnung, $stunde, $minute, $dauer_sec, $category_id, $event_bezeichnung, $special, $admin, $services) {
  $fields=array();
  $fields["bezeichnung"]=$bezeichnung;
  $fields["event_bezeichnung"]=$event_bezeichnung;
  $fields["stunde"]=$stunde;
  $fields["minute"]=$minute;
  $fields["dauer_sec"]=$dauer_sec;
  $fields["special"]=$special;
  $fields["category_id"]=$category_id;
  $fields["admin"]=$admin;
  
  cdb_log("[ChurchService] Update Template $template_id: cat:$category_id, bez:$event_bezeichnung",2);
  
  if ($template_id==null) {
    $arr=db_query("select max(id) id from {cs_eventtemplate}")->fetch();
    $template_id=$arr->id+1;
    $fields["id"]=$template_id;
    db_insert("cs_eventtemplate")
      ->fields($fields)
      ->execute();
  }
  else {
    db_update("cs_eventtemplate")
      ->fields($fields)
      ->condition('id',$template_id,"=")
      ->execute();
  }
  if (isset($services)) {
    foreach ($services as $service=>$val) {
      if ($val>0) {
        db_query("insert into {cs_eventtemplate_service} (eventtemplate_id, service_id, count) values (:eventtemplate_id, :service_id, :count) on duplicate key update service_id=:service_id, count=:count",
           array(":eventtemplate_id"=>$template_id, ":service_id"=>$service, ":count"=>$val));
      }
      else    
        db_query("delete from {cs_eventtemplate_service} where eventtemplate_id=:eventtemplate_id and service_id=:service_id",
          array(":eventtemplate_id"=>$template_id, ":service_id"=>$service));
    }
  }      
}

function churchservice_getAllFacts() {
  global $config;
  $res=db_query("select id, ef.fact_id, ef.value from {cs_event_fact} ef, {cs_event} e 
      where e.id=ef.event_id"); // and datediff(e.datum,current_date)-".$config["churchservice_entries_last_days"]);
  $facts=array();
  foreach ($res as $arr) {
    if (isset($facts[$arr->id]))
      $fact=$facts[$arr->id];
    else $fact=array();
    $fact[]=$arr;
    $facts[$arr->id]=$fact;
  }
  return $facts;
}

function churchservice_getAllSongs() {
  global $config;
  $arr=null;
  
  $db_ss=churchcore_getTableData("cs_song", "bezeichnung");
  if ($db_ss!=null) {
    $files_song=churchcore_getFilesAsDomainIdArr("song");
    $files_song_arrangement=churchcore_getFilesAsDomainIdArr("song_arrangement");
    foreach($db_ss as $db_s) {
      $db_as=db_query("select * from {cs_song_arrangement} where song_id=$db_s->id");
      $arrangement=array();
      foreach($db_as as $db_a) {
        if (isset($files_song_arrangement[$db_a->id]))
          $db_a->files=$files_song_arrangement[$db_a->id];
        unset($db_a->song_id);  
        $arrangement[$db_a->id]=$db_a;
      }
      $db_s->arrangement=$arrangement;
      if (isset($files_song[$db_s->id]))
        $db_s->files=$files_song[$db_s->id];
      $arr["songs"][$db_s->id]=$db_s;    
    }
  }
  
  return $arr;
}

function churchservice_addNewSong($params) {
  $i=new CTInterface();
  $i->setParam("bezeichnung");
  $i->setParam("songcategory_id");
  $i->setParam("ccli");
  $i->setParam("author");
  $i->setParam("copyright");
  $i->addModifiedParams();  
  
  $params["song_id"]=db_insert("cs_song")->fields($i->getDBInsertArrayFromParams($params))->execute(false);
  $params["bezeichnung"]="Standard-Arrangement";
  $params["default_yn"]=1;
  $i=new CTInterface();
  $i->setParam("song_id");
  $i->setParam("bezeichnung");
  $i->setParam("bpm");
  $i->setParam("beat");
  $i->setParam("tonality");
  $i->setParam("default_yn");
  $i->addModifiedParams();
  db_insert("cs_song_arrangement")->fields($i->getDBInsertArrayFromParams($params))->execute(false);
  return $params["song_id"];
}

function churchservice_editSong($params) {
  $i=new CTInterface();
  $i->setParam("id");
  $i->setParam("bezeichnung");
  $i->setParam("songcategory_id");
  $i->setParam("ccli");
  $i->setParam("author");
  $i->setParam("copyright");
  $i->addModifiedParams();  
  
  db_update("cs_song")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
}

function churchservice_editArrangement($params) {
  $i=new CTInterface();
  $i->setParam("id");
  $i->setParam("bezeichnung");
  $i->setParam("tonality");
  $i->setParam("bpm");
  $i->setParam("beat");
  $i->setParam("length_min");
  $i->setParam("length_sec");
  $i->setParam("note");
  $i->addModifiedParams();  
  
  db_update("cs_song_arrangement")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
}


function churchservice_addArrangement($params) {
  $i=new CTInterface();
  $i->setParam("song_id");
  $i->setParam("bezeichnung");
  $i->addModifiedParams();    
  $res=db_insert("cs_song_arrangement")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->execute(false);
  return $res;  
}

function churchservice_delArrangement($params) {
  $i=new CTInterface();
  $i->setParam("id");
  db_delete("cs_song_arrangement")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
}

function churchservice_deleteSong($params) {
  $arrs=churchcore_getTableData('cs_song_arrangement', null, 'song_id='.$params["id"]);    
  $files=churchcore_getFiles("song_arrangement");
  foreach ($arrs as $arr) {
    foreach($files as $file) {
      if ($file->domain_id==$arr->id) {
        churchcore_delFile($file->id); 
      } 
    }
  }  
  $i=new CTInterface();
  $i->setParam("id");
  db_delete("cs_song_arrangement")
  ->fields($i->getDBInsertArrayFromParams($params))
  ->condition("song_id", $params["id"], "=")
  ->execute(false);
  db_delete("cs_song")
  ->fields($i->getDBInsertArrayFromParams($params))
  ->condition("id", $params["id"], "=")
  ->execute(false);
}

function churchservice_makeAsStandardArrangement($params) {
  $i=new CTInterface();
  $params["default_yn"]=0;  
  $i->setParam("default_yn");
  $i->setParam("song_id");
  $i->addModifiedParams();    
  db_update("cs_song_arrangement")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("song_id", $params["song_id"], "=")
    ->execute(false);
  $params["default_yn"]=1;  
  db_update("cs_song_arrangement")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
}


function churchservice_editServiceGroupPersonWeight($params) {
  $i=new CTInterface();
  $i->setParam("servicegroup_id");
  $i->setParam("person_id");
  $i->setParam("max_per_month");
  $i->setParam("relation_weight");
  $i->setParam("morning_weight");
  $i->addModifiedParams();  
  
  try {
    db_insert("cs_servicegroup_person_weight")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->execute(false);
  } 
  catch (Exception $e) {
      db_query("update {cs_servicegroup_person_weight} 
        set max_per_month=:max_per_month, relation_weight=:relation_weight, morning_weight=:morning_weight
        where servicegroup_id=:servicegroup_id and person_id=:person_id",
         $i->getDBParamsArrayFromParams($params));
  }
}

function churchservice_saveFact($params) {
  global $user;
  
  $event_id=$params["event_id"];
  $fact_id=$params["fact_id"];
  $value=$params["value"];
  
  $dt = new datetime();
  
  if ($value=="")
    db_query("delete from {cs_event_fact} where event_id=$event_id and fact_id=$fact_id");
  else  
    db_query("insert into {cs_event_fact} (event_id, fact_id, value, modified_date, modified_pid)
           values ($event_id, $fact_id, $value, '".$dt->format('Y-m-d H:i:s')."', $user->id)
            on duplicate key update value=$value, modified_pid=$user->id, modified_date='".$dt->format('Y-m-d H:i:s')."'");
  cdb_log("[ChurchService] Speichere Fakt $value bei Fakt $fact_id, Event $event_id",2, $fact_id, "fact");  
}

function churchservice_getServiceGroupPersonWeight() {
  $rel_types=db_query("select id from {cdb_beziehungstyp} where export_aggregation_yn=1 limit 1")->fetch();
  $p=db_query("select * from {cs_servicegroup_person_weight}");
  $res=array();
  if ($p!=false)
  foreach ($p as $s) {
    if (isset($res[$s->person_id]))
      $arr=$res[$s->person_id];
      
    if (($rel_types!=null) && ($s->relation_weight!=0)) {
      $rel=db_query("select * from {cdb_beziehung} where vater_id=:id or kind_id=:id and beziehungstyp_id=$rel_types->id",
         array(":id"=>$s->person_id))->fetch();
      if ($rel!=null) {   
        if ($rel->kind_id!=$s->person_id) 
          $s->relation_id=$rel->kind_id;
        else      
          $s->relation_id= $rel->vater_id;
      }
      else $s->relation_id=null;
    }        
    $arr[$s->servicegroup_id]=$s;
    $res[$s->person_id]=$arr; 
  }  
  return $res;
}

function churchservice_addAbsent($person_id, $absent_reason_id, $bezeichnung, $startdate, $enddate) {
  global $user;
  $dt = new datetime();
    
  $id=db_insert("cs_absent")->fields(array(
     "person_id"=>$person_id,
     "absent_reason_id"=>$absent_reason_id,
     "bezeichnung"=>$bezeichnung,
     "startdate"=>$startdate,
     "enddate"=>$enddate,
     "modified_pid"=>$user->id,
     "modified_date"=>$dt->format('Y-m-d H:i:s'))
  )->execute();
  
  return $id;
}

function churchservice_delAbsent($id) {
  db_query("delete from {cs_absent} where id=:id", array(":id"=>$id));
}


class CTChurchServiceModule extends CTAbstractModule {
  
  public function getMasterDataTablenames() {
    $res=array();
    $res[2]=churchcore_getMasterDataEntry(2, "Service", "service", "cs_service");
    $res[3]=churchcore_getMasterDataEntry(3, "Service-Gruppe", "servicegroup", "cs_servicegroup","sortkey");
    //$res[4]=churchcore_getMasterDataEntry(4, "Event-Kategorien", "category", "cs_category","sortkey");
    $res[5]=churchcore_getMasterDataEntry(5, "Abwesenheitsgrund", "absent_reason", "cs_absent_reason","sortkey");
    $res[6]=churchcore_getMasterDataEntry(6, "Fakten", "fact", "cs_fact","sortkey");
    $res[7]=churchcore_getMasterDataEntry(7, "Song-Kategorien", "songcategory", "cs_songcategory","sortkey");
    
    return $res;
  }
  
  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;
      include_once(drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
      $auth=churchservice_getAuthorization();
      $res=$this->getMasterDataTables();
	  $res["masterDataTables"] = $this->getMasterDataTablenames();
	  $res["auth"] = $auth;
	  $res["modulespath"] = churchservice_getModulesPath();
	  $res["base_url"] = $base_url;
	  $res["files_url"] = $base_url.$files_dir;
	  $res["files_dir"] = $files_dir;
	  $res["modulename"] = "churchservice";
	  $res["adminemail"] = variable_get('site_mail', '');
	  $res["user_pid"] = $user->id;
	  $res["user_name"] = $user->vorname." ".$user->name;
	  $res["userid"] = $user->cmsuserid;	  
      $res["settings"] =  churchservice_getUserSettings($user->id);
      $res["notification"]= churchcore_getMyNotifications();
      $res["notificationtype"]=churchcore_getTableData("cc_notificationtype");
      $res["lastLogId"] = churchservice_getLastLogId();
      $res["eventtemplate"] = churchcore_getTableData("cs_eventtemplate","sortkey");
      $res["category"] = churchcal_getAllowedCategories(false);  
      
      $res["eventtemplate_services"] = churchservice_getEventtemplateServices($auth);
      $res["churchcal_name"] =$config["churchcal_name"];
      $res["songwithcategoryasdir"] = variable_get("churchservice_songwithcategoryasdir", "0");
      $res["songcategory"] = churchcore_getTableData("cs_songcategory","sortkey");
      return $res;
  }
  
  public function getEventTemplates() {
    return churchcore_getTableData("cs_eventtemplate","sortkey");
  }
  
  public function updateEventService($params) {
    return churchservice_updateEventService($params);
  }
  
  public function getAbsent($params) {
    global $config;
    return churchcore_getTableData("cs_absent", "startdate"); //, "datediff(startdate,current_date)>-".$config["churchservice_entries_last_days"]);    
  }
  
  public function getGroupAndTagInfos() {
    global $user;
    $a=array();
    if (user_access("view alldata", "churchdb"))
      $a["groups"]=getAllGroups();
    else
      $a["groups"]=churchdb_getMyGroups($user->id, false, true);
    $a["tags"]=getAllTags();
    return $a;    
  }
  
  public function addOrRemoveServiceToEvent($params) { 
    return churchservice_addOrRemoveServiceToEvent($params);
  }
    
  public function sendEMailToPersonIds($params) {
    global $base_url;
    
    $inhalt=$params["inhalt"];
    $usetemplate=(isset($params["usetemplate"]) && ($params["usetemplate"]==true));
    if ($params["domain_id"]!="null") {
      $inhalt.='<p><a class="btn btn-royal" href="'.$base_url.'?q=churchservice&id='.$params["domain_id"].'">Event aufrufen</a>';
    } 
    return churchcore_sendEMailToPersonIds($params["ids"], $params["betreff"], $inhalt, null, true, $usetemplate);
  }
  
  public function saveTemplate($params) {
    $this->checkPerm("edit template");
    churchservice_updateOrInsertTemplate(($params["template_id"]=="null"?null:$params["template_id"]), $params["bezeichnung"], $params["stunde"], $params["minute"], $params["dauer_sec"], $params["category_id"], $params["event_bezeichnung"], $params["special"], $params["admin"], (isset($params["services"])?$params["services"]:null));
  }
  
  public function deleteTemplate($params) {    
    $this->checkPerm("edit template");
    db_query("delete from {cs_eventtemplate_service} where eventtemplate_id=".$params["id"]); 
    db_query("delete from {cs_eventtemplate} where id=".$params["id"]);
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
       
  public function addNewSong($params) {
    $this->checkPerm("edit song");    
    return churchservice_addNewSong($params);
  }
  
  public function editSong($params) {
    $this->checkPerm("edit song");
    return churchservice_editSong($params);
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
  
  public function addAbsent($params) {
    return churchservice_addAbsent($params["person_id"], $params["absent_reason_id"], $params["bezeichnung"], $params["startdate"], $params["enddate"]);
  }
  
  public function delAbsent($params) {
    return churchservice_delAbsent($params["id"]);
  }
  
  public function saveNote($params) {
    churchservice_saveNote($params["event_id"], $params["text"]);
  } 
      
  public function getServiceGroupPersonWeight($params) {
    $res=array();
    $res["weight"]=churchservice_getServiceGroupPersonWeight();
    return $res;
  }
       
  public function editServiceGroupPersonWeight($params) {
    return churchservice_editServiceGroupPersonWeight($params);
  }
    
  public function getChurchDBMasterData($params) {
    $res["cdb_gruppen"]=churchcore_getTableData("cdb_gruppe");
    $res["cdb_tag"]=churchcore_getTableData("cdb_tag");
    return $res;
  }

  /*
   *  NOW ALL FUNCTIONS FOR AGENDA VIEW
   */

  /**
   * Load agenda templates, if allowed.
   * @throws CTNoPermission
   */
  public function loadAllAgendaTemplates($params) {
    $auth=churchservice_getAuthorization();
    $allowedAgendas=$auth["view agenda"];
    
    $where="calcategory_id in (".implode(",",$allowedAgendas).") and template_yn=1";
    $data=churchcore_getTableData("cs_agenda", null, $where);
    return $data;
  }
  
  
  /**
   * Load agendas with ids inclusive belonging Event_ids but without the items 
   * Check if is allowed to view or if I am involved in one of the events
   * @param $params [ids] for ids to get
   * ®return agenda oder null if not found or not allowed
   */
  public function loadAgendas($params) {   
    $where="id in (".implode(",",$params["ids"]).")";
    $data=churchcore_getTableData("cs_agenda", null, $where);
    $auth=churchservice_getAuthorization();
    $allowedAgendas=array();
    if (isset($auth["view agenda"])) $allowedAgendas=$auth["view agenda"];
    if ($data==false) return null;
    else {
      foreach ($data as $key=>$d) {    
        // Check if template
        $d->event_ids=$this->getBelongingEventIdsToAgenda($d->id);
        // Check if allowed
        if (!isset($allowedAgendas[$d->calcategory_id])) {
          // if not allowed, checked if I am involved in services of belonging events
          $involved=false;
          foreach($d->event_ids as $event_id) {
            if (!$involved) 
              $involved=churchservice_amIInvolved($event_id);
          }
          if (!$involved) unset($data[$key]);
        }
      }
      return $data;
    }
  }
  
  /**
   * Gets the agenda belonging to the event $params["event_id"]
   * @param unknown $params
   * @throws CTFail
   * @throws CTNoPermission
   * @return Agenda with all items
   */
  public function loadAgendaForEvent($params) {
        // Get first agenda_id
    $db=db_query('select agenda_id from {cs_event_item} ei, {cs_item} i '. 
                   'where ei.item_id=i.id and event_id=:event_id limit 1', 
               array(":event_id"=>$params["event_id"]))->fetch();
    if (!$db) throw new CTFail("Konnte keine Agenda fuer das Event finden!");
    
    // Now load agenda data
    $agendas=$this->loadAgendas(array("ids"=>array($db->agenda_id)));
    if (isset($agendas[$db->agenda_id]))
      return $agendas[$db->agenda_id];
    else throw new CTNoPermission("view agenda", "churchservice");
  }
  
  private function getBelongingEventIdsToAgenda($agenda_id) {
    // And add belonging event_ids to the agenda
    $db2=db_query("select distinct ei.event_id id from {cs_event_item} ei, {cs_item} i
                  where ei.item_id=i.id and i.agenda_id=:agenda_id", array(":agenda_id"=>$agenda_id));
    $event_ids=array();
    foreach ($db2 as $event) {
      if ($event_ids[]=$event->id);
    }
    return $event_ids;    
  }
  
  
  /**
   * Load Agenda items
   * @param $params["agenda_id"]
   * @throws CTException
   * @throws CTNoPermission
   * @return Ambigous <NULL, unknown, boolean, db_accessor>
   */
  public function loadAgendaItems($params) {
    $auth=churchservice_getAuthorization();
    $db=churchcore_getTableData("cs_agenda", null, "id = ".$params["agenda_id"]);
    if (!$db) throw new CTException("Agenda not found!");
    $agenda=$db[$params["agenda_id"]];
    
    $items=churchcore_getTableData("cs_item", null, "agenda_id=".$params["agenda_id"]);    
    if ($items!=null) {
      $event_ids=array();
      foreach ($items as $item) {
        $ei=churchcore_getTableData("cs_event_item", null, "item_id=".$item->id, "event_id");
        if ($ei!=null) {
          $item->events=array();
          foreach ($ei as $e) {
            $item->event_ids[]=$e->event_id;
            $event_ids[$e->event_id]=$e->event_id;
          }
        }
        
        $sgs=churchcore_getTableData("cs_item_servicegroup", null, "item_id=".$item->id);
        if ($sgs!=null)
          foreach ($sgs as $sg) {
            $item->servicegroup[$sg->servicegroup_id]=$sg->note;
          }      
      
      }
      // Check perms
      if ((!isset($auth["view agenda"])) || (!isset($auth["view agenda"][$agenda->calcategory_id]))) {
        $involved=false;
        foreach ($event_ids as $event_id) {
          if (!$involved)
            $involved=churchservice_amIInvolved($event_id);
        }
        if (!$involved) throw new CTNoPermission("view agenda", "churchservice");
      }    
    } 
    return $items;
  }
  
  /**
   * Saves the item of the agenda agenda_id
   * @param $params[...]
   * @return new item id
   */
  public function saveItem($params) {
    $agenda=$this->loadAgendas(array("ids"=>array($params["agenda_id"])));
    if ($agenda==null)
      throw new CTFail("Agenda nicht gefunden");
    $this->checkPerm("edit agenda", null, $agenda[$params["agenda_id"]]->calcategory_id);
    if ($agenda[$params["agenda_id"]]->template_yn==1)
      $this->checkPerm("edit agenda templates", null, $agenda[$params["agenda_id"]]->calcategory_id);
      
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
  	
  	if (!isset($params["id"])) {
  	  $params["id"]=db_insert("cs_item")
  	  ->fields($i->getDBInsertArrayFromParams($params))
  	  ->execute(false);
  	}
  	else {
      db_update("cs_item")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("id", $params["id"], "=")
      ->execute(false);
  	}
  	// Now insert event-relation  	
    if (isset($params["event_ids"])) {
  	  foreach ($params["event_ids"] as $event_id) {
  	    // Ignore avoid error when item is already mapped to event
  	    db_query("insert ignore into {cs_event_item} (event_id, item_id) values (:event_id, :item_id)", 
  	      array(":event_id"=>$event_id, ":item_id"=>$params["id"]));
  	  }  	    
  	}
  	return $params["id"];
  }
  
  /**
   * Saves the agenda and returns the saved one with all new Ids
   */
  public function saveAgenda($params) {
    $this->checkPerm("edit agenda", null, $params["calcategory_id"]);
    if ((isset($params["id"])) && (churchservice_isAgendaTemplate($params["id"])))
      $this->checkPerm("edit agenda templates", null, $params["calcategory_id"]);
    
    $i = new CTInterface();
  	$i->setParam("calcategory_id");
  	$i->setParam("bezeichnung");
  	$i->setParam("template_yn");
  	$i->setParam("series");
  	$i->setParam("final_yn", false);
  	 
  	if (!isset($params["id"])) {
  	  $params["id"]=db_insert("cs_agenda")
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
  	  $newitems=array();
  	  foreach ($params["items"] as $key=>$item) {
  	    $item["agenda_id"]=$params["id"];
  	  	$item["id"]=$this->saveItem($item);
  	  	$newitems[$item["id"]]=$item;
  	  }
  	  $params["items"]=$newitems;
  	}
    $params["event_ids"]=$this->getBelongingEventIdsToAgenda($params["id"]);
  	return $params;  	 
  }
  
  /**
   * Load item $params["id"] with calcategory_id and template_yn from agenda
   * @param unknown $params
   * @throws CTException - When item not found
   * @throws CTNoPermission - When not allowed to edit or view
   * @return item
   */
  public function loadItem($params) {
    $db=db_query("select i.*, a.calcategory_id, a.template_yn from {cs_item} i, {cs_agenda} a where i.agenda_id=a.id
            and i.id=:id", array(":id"=>$params["id"]))->fetch();
    if (!$db) throw new CTException("Item not found!");
    $auth=churchservice_getAuthorization();
    if ((!isset($auth["view agenda"])) && (!isset($auth["view agenda"][$db->calcategory_id])) &&
        (!isset($auth["edit agenda"])) && (!isset($auth["edit agenda"][$db->calcategory_id])))
      throw new CTNoPermission("view agenda", "churchservice");
    return $db;
  }
  
  public function saveServiceGroupNote($params) {
    $item=$this->loadItem(array("id"=>$params["item_id"]));
    $this->checkPerm("edit agenda", null, $item->calcategory_id);
    if ($item->template_yn==1)
      $this->checkPerm("edit agenda templates", null, $item->calcategory_id);
    
    $i = new CTInterface();
    $i->setParam("item_id");
    $i->setParam("servicegroup_id");
    $i->setParam("note");
    db_delete("cs_item_servicegroup")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("item_id", $params["item_id"], "=")
    ->condition("servicegroup_id", $params["servicegroup_id"], "=")
    ->execute(false);
    db_insert("cs_item_servicegroup")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->execute(false);    
  }
  
  public function deleteItemEventRelation($params) {
    $item=$this->loadItem(array("id"=>$params["item_id"]));
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
  
  public function addItemEventRelation($params) {
    $item=$this->loadItem(array("id"=>$params["item_id"]));
    $this->checkPerm("edit agenda", null, $item->calcategory_id);
        
    $i = new CTInterface();
    $i->setParam("item_id");
    $i->setParam("event_id");
    
    db_insert("cs_event_item")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->execute(false);    
  }

  public function deleteItem($params) {
    $item=$this->loadItem(array("id"=>$params["id"]));
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
  
  public function deleteAgenda($params) {
    $agenda=$this->loadAgendas(array("ids"=>array($params["id"])));
    if ($agenda==null)
      throw new CTException("Agenda not found!");
    $this->checkPerm("edit agenda", null, $agenda[$params["id"]]->calcategory_id);
    if (churchservice_isAgendaTemplate($params["id"]))
      $this->checkPerm("edit agenda templates", null, $agenda[$params["id"]]->calcategory_id);
      
    $i = new CTInterface();
    $i->setParam("id");

    $db=db_query("select * from {cs_item} where agenda_id=:agenda_id", array(":agenda_id"=>$params["id"]), false);
    foreach ($db as $item) {
      $this->deleteItem(array("id"=>$item->id));
    }
    
    db_delete("cs_agenda")
    ->fields($i->getDBInsertArrayFromParams($params))
    ->condition("id", $params["id"], "=")
    ->execute(false);
  }
}

function churchservice_ajax() {
  include_once("churchservice_db.inc");
  
  $module=new CTChurchServiceModule("churchservice");
  $ajax = new CTAjaxHandler($module);
  
  $ajax->addFunction("pollForNews", "view"); 
  $ajax->addFunction("getNewEventData", "view"); 
  $ajax->addFunction("getAllEventData", "view"); 
  $ajax->addFunction("getPersonByGroupIds", "view"); 
  $ajax->addFunction("saveEvent", "edit events"); 
  $ajax->addFunction("deleteEvent", "edit events"); 
  
  // Facts
  $ajax->addFunction("getAllFacts", "view facts || edit facts"); 
  $ajax->addFunction("saveFact", "edit facts"); 
  
  $ajax->addFunction("deleteService");
  $ajax->addFunction("editService");
  
  $ajax->addFunction("addOrRemoveServiceToEvent");
  
  drupal_json_output($ajax->call());
}

