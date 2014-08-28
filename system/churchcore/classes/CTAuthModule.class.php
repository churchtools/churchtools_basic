<?php

/**
 * Churchtools Auth Module
 */
class CTAuthModule extends CTAbstractModule {
  
  // uses __constructor of parent class
  
  /**
   * Save Auth
   *
   * @param array $params          
   * @throws CTNoPermission
   */
  public function saveAuth($params) {
    if (!user_access("administer persons", "churchcore")) throw new CTNoPermission("administer persons", "churchcore");
    
    db_query("DELETE FROM {cc_domain_auth} 
              WHERE domain_type=:domain_type AND domain_id=:domain_id", 
              array (
                ":domain_type" => $params["domain_type"], 
                ":domain_id" => $params["domain_id"]
    ));
    if (isset($params["data"])) foreach ($params["data"] as $data) {
      if (isset($data["daten_id"])) {
        db_query("INSERT INTO {cc_domain_auth} (domain_type, domain_id, auth_id, daten_id)
                  VALUES (:domain_type, :domain_id, :auth_id, :daten_id)", array (
                     ":domain_type" => $params["domain_type"], 
                     ":domain_id" => $params["domain_id"], 
                     ":auth_id" => $data["auth_id"], 
                     ":daten_id" => $data["daten_id"]
        ));
      }
      else {
        db_query("INSERT INTO {cc_domain_auth} (domain_type, domain_id, auth_id)
                  VALUES (:domain_type, :domain_id, :auth_id)", array (
                     ":domain_type" => $params["domain_type"], 
                     ":domain_id" => $params["domain_id"], 
                     ":auth_id" => $data["auth_id"]
        ));
      }
    }
  }

  /**
   * get MasterData
   * 
   * @return array with objects
   *
   */
  public function getMasterData() {
    global $config;
    
    $res = array ();
    $res["auth_table_plain"] = getAuthTable();
    
    foreach ($res["auth_table_plain"] as $auth) {
      if ($auth->datenfeld && !isset($res[$auth->datenfeld])) {
        $res[$auth->datenfeld] = churchcore_getTableData($auth->datenfeld);
      }
    }
    $res["modules"] = churchcore_getModulesSorted(true, false);

    $res["person"] = churchcore_getTableData("cdb_person", "name, vorname", null, "id, concat(name, ', ', vorname) as bezeichnung");
    $res["person"][-1] = new stdClass();
    $res["person"][-1]->id = -1;
    $res["person"][-1]->bezeichnung = "- " + _("public.user") + " -";
    $res["gruppe"] = churchcore_getTableData("cdb_gruppe", null, null, "id, bezeichnung");
    $res["status"] = churchcore_getTableData("cdb_status");
    $res["publiccalendar_name"] = variable_get("churchcal_maincalname", "Church Calendar");
    $res["category"] = churchcore_getTableData("cc_calcategory", null, null, "id, bezeichnung, privat_yn, oeffentlich_yn");
    $res["modulename"] = "churchcore";
    $res["admins"] = $config["admin_ids"];
    
    $auths = churchcore_getTableData("cc_domain_auth");
    if ($auths) foreach ($auths as $auth) {
      $domaintype = array ();
      // initalize $res[domain_tye]
      if (isset($res[$auth->domain_type])) $domaintype = $res[$auth->domain_type];
      
      $object = new stdClass();
      if (isset($domaintype[$auth->domain_id])) $object = $domaintype[$auth->domain_id];
      else {
        $object->id = $auth->domain_id;
        if (isset($db[$auth->domain_type][$auth->domain_id])) $object->bezeichnung = $db[$auth->domain_type][$auth->domain_id]->bezeichnung;
        else $object->bezeichnung = t("non.existent");
      }
      
      if (!$auth->daten_id) $object->auth[$auth->auth_id] = $auth->auth_id;
      else {
        if (!isset($object->auth[$auth->auth_id])) $object->auth[$auth->auth_id] = array ();
        $object->auth[$auth->auth_id][$auth->daten_id] = $auth->daten_id;
      }
      
      $domaintype[$auth->domain_id] = $object;
      $res[$auth->domain_type] = $domaintype;
    }
    foreach (churchcore_getModulesSorted() as $name) {
      if (isset($config[$name . "_name"])) $res["names"][$name] = $config[$name . "_name"];
    }
    return $res;
  }

}
