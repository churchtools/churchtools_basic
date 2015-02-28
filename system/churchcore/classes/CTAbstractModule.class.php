<?php

/**
 * Base class for modules?
 *
 * TOOD: set modulename in child classes without using function parameter
 * what will happen on calling CTChurchServiceModule("churchcal")?
 */
abstract class CTAbstractModule implements CTModuleInterface {
  private $modulename = "abstract";

  /**
   * constructor, set modulename
   * @param string $modulename
   */
  public function __construct($modulename) {
    global $config, $files_dir, $i18n;
    if (!$modulename) die("No Modulename given in new CTModule()");
    $this->modulename = $modulename;
  }

  public function getModuleName() {
    return $this->modulename;
  }

  public function getModulePath() {
    return constant(strtoupper($this->modulename));
  }

  /**
   * Delete row in DB
   * @param array $params array(id, table)
   * @throws CTNoPermission
   */
  public function deleteMasterData($params) {
    if (user_access("edit masterdata", $this->modulename)
        && churchcore_isAllowedMasterData($this->getMasterDataTablenames(), $params["table"])) {
      db_query("DELETE FROM {" . $params["table"] . "}
                WHERE id=:id",
                array (":id" => $params["id"])); // delete Masterdata
      $this->logMasterData($params);
    }
    else throw new CTNoPermission("edit masterdata", $this->modulename);
  }

  /**
   * Save row in DB
   *
   * @param array $params array(id, table and col0..n columnname and value0..n for data).
   * @throws CTNoPermission
   */
  public function saveMasterData($params) {
    if ((user_access("edit masterdata", $this->modulename)) &&
         (churchcore_isAllowedMasterData($this->getMasterDataTablenames(), $params["table"]))) {

      // Check CDB_Feld for existing db field, because this is support case no 1...
      if ($params["table"] == "cdb_feld") {
        $fk = churchcore_getTableData("cdb_feldkategorie");
        $data = $fk[$params["value0"]];
        try {
          $res = db_query("SELECT ".$params["value2"]." FROM {".$data->db_tabelle."} LIMIT 1", null, false)->fetch();
        }
        catch (Exception $e) {
          throw new CTException("Datenfeld ".$params["value2"]." existiert nicht. Bitte erst vom Datenbankadmin anlegen lassen.");
        }
      }
      churchcore_saveMasterData($params["id"], $params["table"]);
      $this->logMasterData($params);
    }
    else
      throw new CTNoPermission("edit masterdata", $this->modulename);
  }

  /**
   * Save user settings for current user
   *
   * @param array $params array(sub, val)
   */
  public function saveSetting($params) {
    global $user;
    churchcore_saveUserSetting($this->modulename, $user->id, $params["sub"], (isset($params["val"]) ? $params["val"] : null));
  }

  /**
   * Set cookie
   * TODO: time should be set using constant to be customizable
   *
   * @param array $params array(sub, val)
   */
  public function setCookie($params) {
    setcookie($params["sub"], $params["val"], time() + 60 * 60 * 24 * 30); // 30 days
  }

  public function setLanguage($params) {
    global $user;
    setcookie("language", $params["language"], time() + 60 * 60 * 24 * 30); // 30 days
    _churchcore_savePidUserSetting("churchcore", $user->id, "language", $params["language"]);
  }

  /**
   * get module settings for user
   * @see CTModuleInterface::getSettings()
   */
  public function getSettings() {
    global $user;
    return churchcore_getUserSettings($this->modulename, $user->id);
  }

  /**
   *
   * @param array $params
   */
  public function editNotification($params) {
    global $user;
    if (empty($params["person_id"])) $params["person_id"] = $user->id;

    $i = new CTInterface();
    $i->setParam("domain_type");
    $i->setParam("domain_id");
    $i->setParam("person_id");
    $i->setParam("notificationtype_id", false);

    // Delete if abo type is not set
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

  /**
   * TODO: it dont get tables, but data; rename to getMasterDataOfTable()?
   *
   * @return array data
   */
  protected function getMasterDataTables() {
    $tables = $this->getMasterDataTablenames();
    foreach ($tables as $table) {
      $res[$table["shortname"]] = churchcore_getTableData($table["tablename"], $table["sql_order"]);
    }
    $res["service"] = churchcore_getTableData("cs_service"); //TODO: for what is this?
    return $res;
  }

  /**
   *
   * @param unknown $auth
   * @param string $modulename
   * @param string $datafield
   * @throws CTNoPermission
   */
  protected function checkPerm($auth, $modulename = null, $datafield = null) {
    if (!$modulename) $modulename = $this->modulename;
    $perm = user_access($auth, $modulename);
    if (!$perm || ($datafield != null && !isset($perm[$datafield]))) {
      throw new CTNoPermission($auth, $modulename);
    }
  }

  /**
   *
   * @param string $domain_type
   * @param unknown $domain_id
   * @param string $txt
   * @param int $loglevel
   */
  public function notify($domain_type, $domain_id, $txt, $loglevel = 2) {
    ct_notify($domain_type, $domain_id, $txt, $loglevel = 2);
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
    foreach ($params as $key => $param) if ($key != "q" && $key != "func") {
      if (is_array($param)) $my[] = "$key:" . serialize($param);
      else $my[] = "$key:$param";
    }
    $str = "";
    if (isset($params["func"])) $str .= $params["func"] . " ";
    if (count($my)) $str .= "(" . implode(", ", $my) . ")";

    return $str;
  }

  /**
   *
   * @param array $params
   * @param int $loglevel
   */
  public function log($params, $loglevel = 2) {
    ct_log($this->prepareForLog($params), $loglevel);
  }

  /**
   *
   * @param array $params
   * @param int $loglevel
   */
  public function logPerson($params, $loglevel = 2) {
    ct_log($this->prepareForLog($params), $loglevel, getVar("id", null, $params), CDB_LOG_PERSON);
  }

  /**
   *
   * @param array $params
   * @param int $loglevel
   */
  public function logGroup($params, $loglevel = 2) {
    ct_log($this->prepareForLog($params), $loglevel, getVar("id", null, $params), CDB_LOG_GROUP);
  }

  /**
   *
   * @param array $params
   * @param int $loglevel
   */
  public function logMasterData($params, $loglevel = 2) {
    ct_log($this->prepareForLog($params), $loglevel, getVar("id", null, $params), CDB_LOG_MASTERDATA);
  }

  /**
   * Get array with churchcore_getMasterDataEntry() entries for all MasterData tables.
   * Or null if there is no table needed.
   */
  public function getMasterDataTablenames() {
    return null;
  }

  public function makeDownloadFile($params) {
    global $files_dir;
    if (getVar("remove", false, $params)) {
      unlink($params["filename"]);
    }
    else {
      $downloader_path = $files_dir . "/files/downloader";
      if (!is_dir($downloader_path)) {
        mkdir($downloader_path);
      }
      $temp = $downloader_path . "/" . $params["filename"] . random_string(10) . "." . $params["suffix"];
      file_put_contents($temp, $params["data"]);
      return $temp;
    }
  }

  /**
   * Generate a pdf from html and store it for download
   *
   * @param string $basename
   * @param string $html
   *
   * @return string $path_to_pdf
   */
  public function generatePDF($params) {
    global $files_dir;

    if (!file_exists("phantomjs")) {
      return null;
    }

    $html = $params["html"];
    $filename = $params["basename"] . "_" . random_string(10) . ".pdf";

    // store the html content in a temporary file for processing
    $tempfile = $files_dir . "/tmp/" . random_string(10) . ".html";
    if (!file_put_contents($tempfile, $html)) throw new CTException("Could not create temp file");

    // convert to pdf
    $downloader_path = $files_dir . "/files/downloader";
    if (!is_dir($downloader_path)) mkdir($downloader_path);
    $cmd = "./phantomjs ".ASSETS."/phantomjs/generatePDF.js file://".getcwd()."/$tempfile $downloader_path/$filename A4";
    exec($cmd, $output, $result);

    if ($result != 0) throw new CTException("Could not execute phantomjs, Error code: $result");

    // remove temp file
    unlink($tempfile);

    return "$downloader_path/$filename";
  }

  public function hasPDFGenerator() {
    return file_exists("phantomjs");
  }
}
