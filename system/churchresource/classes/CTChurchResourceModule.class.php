<?php

/**
 * 
 */
class CTChurchResourceModule extends CTAbstractModule {

  public function getBookings($params) {
    return getBookings();
  }

  /**
   * @see CTAbstractModule::getMasterDataTablenames()
   */
  public function getMasterDataTablenames() {
    $res = array ();
    $res[1] = churchcore_getMasterDataEntry(1, "Ressource", "resources", "cr_resource", "resourcetype_id,sortkey,bezeichnung");
    $res[2] = churchcore_getMasterDataEntry(2, "Ressourcen-Typ", "resourceTypes", "cr_resourcetype");
    $res[3] = churchcore_getMasterDataEntry(3, "Status", "status", "cr_status");
    return $res;
  }

  /**
   * @see CTModuleInterface::getMasterData()
   */
  public function getMasterData() {
    global $user;
    $res = array ();
    include_once (CHURCHCAL . '/churchcal_db.php');
    $res = $this->getMasterDataTables();
    $res["masterDataTables"] = $this->getMasterDataTablenames();
    $res["entriesLastDays"] = getConf("churchresource_entries_last_days", 90);
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
    $res["settings"] = $this->getSettings();
    $res["lastLogId"] = churchresource_getLastLogId();
    $res["churchcal_name"] = getConf('churchcal_name');
    $res["category"] = churchcore_getTableData("cc_calcategory", null, null, "id, color, bezeichnung");
    return $res;
  }

  /**
   * poll for news
   * 
   * @param array $params          
   * @return array
   */
  function pollForNews($params) {
    global $user;
    $last_id = $params["last_id"];
    $res = db_query("SELECT * FROM {cr_log} 
                     WHERE id > :last_id AND person_id!=:user_id",
                     array(':last_id' => $last_id,
                           ':user_id' => $user->id,
                   ));
            
    $arrs = array ();
    foreach ($res as $arr) {
      $arrs[$arr->id] = $arr;
    }
    $arr = array ();
    $arr["lastLogId"] = churchresource_getLastLogId();
    $arr["logs"] = $arrs;
    
    return $arr;
  }

  /**
   * get logs
   * @param array $params
   * @return array logs
   */
  function getLogs($params) {
    $id = $params["id"];
    $res = db_query("SELECT l.*, CONCAT(p.vorname,' ',p.name) AS person_name 
                     FROM {cr_log} l, {cdb_person} p
                     WHERE l.person_id=p.id AND booking_id=:id 
                     ORDER BY datum DESC",
                     array(':id' => $id));
    $logs = null;
    foreach ($res as $arr) {
      $logs[] = $arr;
    }
    return $logs;
  }

}
