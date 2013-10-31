<?php

include_once('./'. drupal_get_path('module', 'churchservice') .'/../churchcore/churchcore_db.inc');

function churchservice_copyEventByCalId($orig_cal_id, $new_cal_id, $new_startdate, $allservices=true) {
  $event=db_query('select * from {cs_event} where cc_cal_id=:cal_id', array(":cal_id"=>$orig_cal_id))->fetch();
  if ($event!=false) {
    $new_id=db_insert("cs_event")
            ->fields(array("cc_cal_id"=>$new_cal_id, "startdate"=>$new_startdate, "special"=>$event->special, "admin"=>$event->admin))
            ->execute(false);
    if ($allservices) {
      $services=db_query("select * from {cs_eventservice} where event_id=$event->id and valid_yn=1");
      $fields=array();
      $fields["event_id"]=$new_id;
      foreach ($services as $s) {
        $fields["service_id"]=$s->service_id;
        $fields["counter"]=$s->counter;
        $fields["valid_yn"]=$s->valid_yn;
        $fields["zugesagt_yn"]=$s->zugesagt_yn;
        $fields["name"]=$s->name;
        $fields["cdb_person_id"]=$s->cdb_person_id;
        $fields["reason"]=$s->reason;
        // Nicht gesetzt, damit eine neue Anfrage fŸr das andere Datum gesendet wird.
//        $fields["mailsenddate"]=$s->mailsenddate;
        $fields["modified_date"]=$s->modified_date;
        $fields["modifieduser"]=$s->modifieduser;
        $fields["modified_pid"]=$s->modified_pid;
        db_insert("cs_eventservice")
            ->fields($fields)
            ->execute(false);
      }        
    }
  }  
  else {
    throw new CTException("Event in ".$config["churchservice_name"]." konnte nicht kopiert werden, da es nicht gefunden wurde.");
  }
}

// Erstellt oder Updated ein Event
// Wenn eventTemplate Ÿbergeben wird, dann holt er die Daten aus dem Template, ansonsten nutzt er services 
function churchservice_saveEvent($params, $source=null) {
  global $user;
  include_once(drupal_get_path('module', 'churchcal') .'/churchcal_db.inc');
  $cal_id=null;
  if (($source=="churchcal") && ($params["id"]==null)) {
    $db=false;
    if (isset($params["old_startdate"])) {
      $db=db_query("select id from {cs_event} where startdate=:startdate and cc_cal_id=:id", 
          array(":startdate"=>$params["old_startdate"], ":id"=>$params["cal_id"]))->fetch();
    }
    if ($db!=false) $params["id"]=$db->id;
    // Wenn es keinen Datensatz gibt und auch kein eventTemplate angelegt werden soll, dann verabschiede ich mich hier
    else if ((!isset($params["eventTemplate"])) || ($params["eventTemplate"]=="")) return;
    $cal_id=$params["cal_id"];  
  }
  else {
    // Hole mir erst mal die zugehšrige cc_cal_id, falls es das Event schon gibt.
    if (isset($_GET["id"])) {
      $cal_id=db_query("select cc_cal_id from {cs_event} where id=:id", array(":id"=>$_GET["id"]))->fetch()->cc_cal_id;
    }   
  }

  // Erst mal das cs_event updated/inserten
  $fields=array();
  $fields["startdate"]=$params["startdate"];
  if ($source==null) {
    $fields["special"]=(isset($params["special"])?$params["special"]:"");
    $fields["admin"]=(isset($params["admin"])?$params["admin"]:"");
  }
  
  if (isset($params["eventTemplate"])) {
    $db=db_query('select special, admin from {cs_eventtemplate} where id=:id', array(":id"=>$params["eventTemplate"]))->fetch();
    if ($db!=false) {
      if ((!isset($fields["special"])) ||Ê($fields["special"]==""))
        $fields["special"]=$db->special;
      if ((!isset($fields["admin"])) ||Ê($fields["admin"]==""))
        $fields["admin"]=$db->admin;
    }    
  }
  
  
  if (isset($params["id"])) {
    $event_id=$params["id"];
    db_update("cs_event")
      ->fields($fields)
      ->condition('id',$params["id"],"=")
      ->execute();
      
    // BENACHRICHTIGE ANDERE MODULE  
    if ($source==null) {
      $cal_params=array_merge(array(), $params);
      $cal_params["event_id"]=$event_id;
      $cal_params["id"]=$cal_id; 
      churchcal_updateEvent($cal_params, "churchservice");
    }
  }
  else {
    if ($source==null) {
      $params["repeat_id"]=0;
      $params["intern_yn"]=0;
      $params["notizen"]=$params["special"];
      $params["link"]="";
      $params["ort"]="";
      $cal_id=churchcal_createEvent($params, "churchservice");
    }
    $fields["cc_cal_id"]=$cal_id;  
    $event_id=db_insert("cs_event")->fields($fields)->execute();
  }  
  
  if ((!isset($params["eventTemplate"])) && (isset($params["services"]))) {
    // Nun die Eintrï¿½ge updaten/inserten
    $rm_services = array();
    $new_services = array();
    $fields=array();
    
    $fields["event_id"]=$event_id;
    $dt = new datetime();
    $fields["valid_yn"]=1;
    $fields["modified_date"]=$dt->format('Y-m-d H:i:s');
    $fields["modified_pid"]=$user->id;
    foreach ($params["services"] as $key=>$arr) {
      $fields["service_id"]=$key;
      $fields["counter"]=null;
      if ($arr==1) {
        db_insert("cs_eventservice")->fields($fields)->execute();          
      }    
      else {
        $i=$arr;
        while ($i>0) {
          $fields["counter"]=$i;
          $i--;
          db_insert("cs_eventservice")->fields($fields)->execute();                    
        }
      }
    }    
  }
  // Also wenn ein Template Ÿbergeben wurde
  else if (isset($params["eventTemplate"])){
    if (isset($params["id"])) 
      throw new CTException("Es kann kein Template uebergeben werden, wenn der Service schon existiert!");
      
    $fields=array();
    $fields["event_id"]=$event_id;
    $fields["valid_yn"]=1;
    $dt = new datetime();
    $fields["modified_date"]=$dt->format('Y-m-d H:i:s');
    $fields["modified_pid"]=$user->id;
    $db=db_query("select * from {cs_eventtemplate_service} where eventtemplate_id=:eventtemplate_id",
       array(':eventtemplate_id'=>$params["eventTemplate"]));
    foreach($db as $d) {
      $fields["service_id"]=$d->service_id;
      if ($d->count==1) {
        $fields["counter"]=null;
        db_insert("cs_eventservice")->fields($fields)->execute();
      }
      else {
        $i=$d->count;
        while ($i>0) {
          $fields["counter"]=$i;
          $i--;
          db_insert("cs_eventservice")->fields($fields)->execute();         
        }      
      }           
    }
    ct_log("[ChurchService] Lege Template an ".$params["eventTemplate"]." fuer Event",2,$event_id,"service");
    
  }
}


function churchservice_getUserCurrentServices($user_id) {  
  $arr=db_query("SELECT cal.bezeichnung event, s.bezeichnung dienst, es.id eventservice_id,".
                "sg.bezeichnung servicegroup,".
                 "DATE_FORMAT(es.modified_date, '%Y%m%dT%H%i00') modified_date, ".
                "p.vorname, p.name, es.modified_pid, zugesagt_yn,
                e.startdate startdate,
                DATE_FORMAT(e.startdate, '%Y%m%dT%H%i00') datum_start,  
                adddate(e.startdate, interval timediff(cal.enddate, cal.startdate)  HOUR_SECOND) enddate,
                DATE_FORMAT(adddate(e.startdate, interval timediff(cal.enddate, cal.startdate)  HOUR_SECOND), '%Y%m%dT%H%i00') datum_end                
                 FROM {cs_event} e, {cc_cal} cal, {cs_eventservice} es, {cs_service} s, {cs_servicegroup} sg, {cdb_person} p
             WHERE cal.id=e.cc_cal_id and es.event_id=e.id and es.service_id=s.id and sg.id=s.servicegroup_id 
             and es.modified_pid=p.id and es.valid_yn=1 and e.startdate>current_date - INTERVAL 61 DAY and es.cdb_person_id=:userid",
      array(":userid"=>$user_id));
      
  $res=array();
  foreach($arr as $a) {
    $res[$a->eventservice_id]=$a;    
  }     
  return $res;  
}
