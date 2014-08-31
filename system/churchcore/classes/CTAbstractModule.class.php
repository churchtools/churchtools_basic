<?php
/**
 *
 * Base class for modules?
 *
 */
abstract class CTAbstractModule implements CTModuleInterface {
  private $modulename="abstract";

  public function __construct($modulename) {
    global $config, $files_dir, $i18n;
    if ($modulename==null) die("No Modulename given in new CTModule()");
    $this->modulename=$modulename;
  }

  public function getModuleName() {
    return $this->modulename;
  }

  public function getModulePath() {
    return constant(strtoupper($this->modulename));
  }

  public function deleteMasterData($params) {
    if ((user_access("edit masterdata",$this->modulename)) && (churchcore_isAllowedMasterData($this->getMasterDataTablenames(), $params["table"]))) {
      db_query("DELETE FROM {".$params["table"]."} WHERE id=:id", array(":id"=>$params["id"])); //delete Masterdata
      $this->logMasterData($params);
    }
    else throw new CTNoPermission("edit masterdata", $this->modulename);
  }

  public function saveMasterData($params) {
    if ((user_access("edit masterdata",$this->modulename)) && (churchcore_isAllowedMasterData($this->getMasterDataTablenames(), $params["table"]))) {
      churchcore_saveMasterData($params["id"], $params["table"]);
      $this->logMasterData($params);
    }
    else throw new CTNoPermission("edit masterdata",$this->modulename);
  }

  public function saveSetting($params) {
    global $user;
    churchcore_saveUserSetting($this->modulename, $user->id, $params["sub"],
    (isset($params["val"])?$params["val"]:null));
  }

  /**
   * Set cookie
   * TODO: time should be set using constant to be customizable
   *
   */
  public function setCookie($params) {
    setcookie($params["sub"], $params["val"], time()+60*60*24*30); //30 days
  }

  public function getSettings() {
    global $user;
    return churchcore_getUserSettings($this->modulename,$user->id);
  }

  public function editNotification($params) {
    global $user;
    if (empty($params["person_id"]))
      $params["person_id"]=$user->id;

    $i = new CTInterface();
    $i->setParam("domain_type");
    $i->setParam("domain_id");
    $i->setParam("person_id");
    $i->setParam("notificationtype_id", false);

    // Delete if abotype is not set
    if (empty($params["notificationtype_id"])) {
      db_delete("cc_notification")
      ->fields($i->getDBInsertArrayFromParams($params))
      ->condition("domain_type", $params["domain_type"], "=")
      ->condition("domain_id", $params["domain_id"], "=")
      ->condition("person_id", $user->id, "=")
      ->execute(false);
    }
    else {
      try {
        db_insert("cc_notification")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->execute(false);
      }
      catch (Exception $e) {
        db_update("cc_notification")
        ->fields($i->getDBInsertArrayFromParams($params))
        ->condition("domain_type", $params["domain_type"], "=")
        ->condition("domain_id", $params["domain_id"], "=")
        ->condition("person_id", $user->id, "=")
        ->execute(false);
      }
    }
  }

  protected function getMasterDataTables() {
    $tables=$this->getMasterDataTablenames();
    foreach ($tables as $value) {
      $res[$value["shortname"]]=churchcore_getTableData($value["tablename"],$value["sql_order"]);
    }
    $res["service"]=churchcore_getTableData("cs_service");
    return $res;
  }

  protected function checkPerm($auth, $modulename=null, $datafield=null) {
    if ($modulename==null) $modulename=$this->modulename;
    $perm=user_access($auth, $modulename);
    if ((!$perm) || ($datafield!=null && !isset($perm[$datafield])))
      throw new CTNoPermission($auth, $modulename);
  }

  public function notify($domain_type, $domain_id, $txt, $loglevel=2) {
    ct_notify($domain_type, $domain_id, $txt, $loglevel=2);
  }

  /**
   *
   * Prepare parameter for logging
   *
   * @param array $params
   *
   * @return string
   */
  public function prepareForLog($params) {
    $my = array ();
    foreach ( $params as $key => $param ) {
      if (($key != "q") && ($key != "func")) {
        if (is_array ( $param ))
          $my [] = "$key:" . serialize ( $param );
        else
          $my [] = "$key:$param";
      }
    }
    $str = "";
    if (isset ( $params ["func"] )) $str .= $params ["func"] . " ";
    if (count ( $my ) > 0) $str .= "(" . implode ( ", ", $my ) . ")";
    return $str;
  }

  public function log($params, $loglevel=2) {
    ct_log($this->prepareForLog($params), $loglevel);
  }
  public function logPerson($params, $loglevel=2) {
    ct_log($this->prepareForLog($params), $loglevel, (isset($params["id"])?$params["id"]:null), CDB_LOG_PERSON);
  }
  public function logGroup($params, $loglevel=2) {
    ct_log($this->prepareForLog($params), $loglevel, (isset($params["id"])?$params["id"]:null), CDB_LOG_GROUP);
  }
  public function logMasterData($params, $loglevel=2) {
    ct_log($this->prepareForLog($params), $loglevel, (isset($params["id"])?$params["id"]:null), CDB_LOG_MASTERDATA);
  }

  public function getMasterDataTablenames() {
    return null;
  }

}
?>