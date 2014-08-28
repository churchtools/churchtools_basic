<?php

class CTChurchResourceModule extends CTAbstractModule {

  public function getBookings($params) {
    return getBookings();
  }

  public function getMasterDataTablenames() {
    $res=array();
    $res[1]=churchcore_getMasterDataEntry(1, "Ressource", "resources", "cr_resource","resourcetype_id,sortkey,bezeichnung");
    $res[2]=churchcore_getMasterDataEntry(2, "Ressourcen-Typ", "resourceTypes", "cr_resourcetype");
    $res[3]=churchcore_getMasterDataEntry(3, "Status", "status", "cr_status");
    return $res;
  }

  public function getMasterData() {
    global $user;
    $res=array();
    include_once(CHURCHCAL .'/churchcal_db.inc');
    $res=$this->getMasterDataTables();
    $res["masterDataTables"] = $this->getMasterDataTablenames();
    $res["auth"] = churchresource_getAuthForAjax();
    $res["status"] = churchcore_getTableData("cr_status");
    $res["minutes"] = churchcore_getTableData("cr_minutes");
    $res["hours"] = churchcore_getTableData("cr_hours");
    $res["repeat"] = churchcore_getTableData("cc_repeat");
    $res["cdb_bereich"] = churchcore_getTableData("cdb_bereich");
    $res["cdb_status"] = churchcore_getTableData("cdb_status");
    $res["cdb_station"] = churchcore_getTableData("cdb_station");
     
    $res["modulename"] = $this->getModuleName();
    $res["modulespath"] = $this->getModulePath();
    $res["userid"] = $user->cmsuserid; // CMS Username#
    $res["user_pid"] = $user->id;
    $res["user_name"] = "$user->vorname $user->name";
    $res["settings"] =  $this->getSettings();
    $res["lastLogId"] = churchresource_getLastLogId();
    $res["churchcal_name"] =variable_get('churchcal_name');
    $res["category"] =churchcore_getTableData("cc_calcategory", null, null, "id, color, bezeichnung");
    return $res;
  }

  function pollForNews($params) {
    global $user;
    $last_id=$params["last_id"];
    $res=db_query("select * from {cr_log} where id > $last_id and person_id!='".$user->id."'");
    $arrs=Array();
    foreach ($res as $arr) {
      $arrs[$arr->id]=$arr;
    }
    $arr=Array();
    $arr["lastLogId"]=churchresource_getLastLogId();
    $arr["logs"]=$arrs;
    return $arr;
  }

  function getLogs($params) {
    $id=$params["id"];
    $res=db_query("SELECT l.*, concat(p.vorname,' ',p.name) as person_name from {cr_log} l, {cdb_person} p
               where l.person_id=p.id and booking_id=".$id." order by datum desc");
    $ret=null;
    foreach ($res as $arr) {
      $ret[]=$arr;
    }
    return $ret;
  }
}
