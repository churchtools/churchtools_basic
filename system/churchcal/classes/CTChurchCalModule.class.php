<?php

class CTChurchCalModule extends CTAbstractModule {
  public function getMasterData() {
    global $user, $base_url;
    $ret=array();
    $ret["modulename"]="churchcal";
    $ret["modulespath"]=CHURCHCAL;
    $ret["churchservice_name"]=getConf("churchservice_name");
    $ret["churchcal_name"]=getConf("churchcal_name");
    $ret["churchresource_name"]=getConf("churchresource_name");
    $ret["maincal_name"]=getConf("churchcal_maincalname", "Gemeindekalender");
    $ret["base_url"]=$base_url;
    $ret["user_pid"]=$user->id;
    if (user_access("view","churchdb")) {
      $ret["absent_reason"]=churchcore_getTableData("cs_absent_reason");
    }
    if (user_access("view","churchresource") || user_access("create bookings","churchresource")) {
      $ret["resources"]=churchcore_getTableData("cr_resource");
      $ret["resourceTypes"]=churchcore_getTableData("cr_resourcetype");
      $ret["bookingStatus"]=churchcore_getTableData("cr_status");
    }
    $ret["category"]=churchcal_getAllowedCategories(true);
    $ret["settings"]=churchcore_getUserSettings("churchcal", $user->id);
    $ret["repeat"]=churchcore_getTableData("cc_repeat");
    if (count($ret["settings"])==0) {
      $arr["checkboxEvents"]="true";
      $ret["settings"]=$arr;
    }
    $ret["auth"]=churchcal_getAuthForAjax();
    return $ret;
  }



  public function getAllowedPeopleForCalender($params) {
    include_once('./'. CHURCHDB .'/churchdb_db.php');
    $db=db_query("select * from {cc_domain_auth} where daten_id=:daten_id and auth_id=403",
        array(":daten_id"=>$params["category_id"]));
    $res=array();
    foreach ($db as $d) {
      if ($d->domain_type=="gruppe") {
        $g=array();
        $ids=churchdb_getAllPeopleIdsFromGroups(array($d->domain_id));
        if ($ids!=null) {
          foreach ($ids as $id) {
            $p=churchdb_getPersonDetails($id);
            if ($p!="no access") {
              $g[]=$p;
            }
          }
        }
        if (count($g)>0) {
          $gr=churchcore_getTableData("cdb_gruppe", null, "id=".$d->domain_id);
          if ($gr!=false)
            $res[]=array("type"=>"gruppe", "data"=>$g, "bezeichnung"=>$gr[$d->domain_id]->bezeichnung);
        }
      }
      else if ($d->domain_type=="person") {
        $p=churchdb_getPersonDetails($d->domain_id);
        if ($p!="no access") {
          $res[]=array("type"=>"person", "data"=>$p);
        }
      }
    }
    return $res;
  }
}
