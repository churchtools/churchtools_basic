<?php

/**
 * 
 */
class CTChurchWikiModule extends CTAbstractModule {

  public function getMasterDataTablenames() {
    $res = array ();
    $res[1] = churchcore_getMasterDataEntry(1, "Wiki-Kategorien", "wikicategory", "cc_wikicategory", "sortkey,bezeichnung");
    
    return $res;
  }

  /**
   * get master data
   * @see CTModuleInterface::getMasterData()
   */
  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;
    
    $data["wikicategory"] = churchcore_getTableData("cc_wikicategory");
    $data["auth"] = churchwiki_getAuthForAjax();
    
    $data["settings"] = array ();
    $data["masterDataTables"] = $this->getMasterDataTablenames();
    $data["files_url"] = $base_url . $files_dir;
    $data["files_dir"] = $files_dir;
    $data["modulename"] = "churchwiki";
    $data["modulespath"] = CHURCHWIKI;
    $data["adminemail"] = getConf('site_mail', '');
    return $data;
  }

  /**
   * save
   * @param array $params
   * @throws CTNoPermission
   */
  public function save($params) {
    global $user;
    $auth = churchwiki_getAuthForAjax();
    if (($auth["edit"] == false) || ($auth["edit"][$params["wikicategory_id"]] != $params["wikicategory_id"])) throw new CTNoPermission("edit", "churchwiki");
    $dt = new DateTime();
    $text = $_POST["val"];
    if (!$text) $text = " "; // Save an emtpy string, so I know there is some data
    db_query("INSERT INTO {cc_wiki} (doc_id, version_no, wikicategory_id, text, modified_date, modified_pid)
              VALUES (:doc_id, :version_no, :wikicategory_id, :text, :modified_date, :modified_pid)", 
              array (":doc_id" => $_POST["doc_id"], 
                     ":version_no" => churchwiki_getCurrentNo($_POST["doc_id"], $_POST["wikicategory_id"]) + 1, 
                     ":wikicategory_id" => $_POST["wikicategory_id"], ":text" => $text, 
                     ":modified_date" => $dt->format('Y-m-d H:i:s'), ":modified_pid" => $user->id,
              ), false);
  }

  /**
   * 
   * @param array $params
   * @throws CTNoPermission
   * @return db result wiki data
   */
  public function load($params) {
    $auth = churchwiki_getAuthForAjax();
    if ($auth["view"] == false || $auth["view"][$params["wikicategory_id"]] != $params["wikicategory_id"]) throw new CTNoPermission("view", "churchwiki");
    $data = churchwiki_load($params["doc_id"], $params["wikicategory_id"], (empty($params["version_no"]) ? null : $params["version_no"]));
    return $data;
  }

  /**
   * 
   * @param array $params
   * @throws CTNoPermission
   * @return db result history
   */
  public function loadHistory($params) {
    $auth = churchwiki_getAuthForAjax();
    if ($params["wikicategory_id"] != 0 && ($auth["view"] == false || $auth["view"][$params["wikicategory_id"]] != $params["wikicategory_id"])) 
       throw new CTNoPermission("view", "churchwiki");
    $res = db_query("SELECT version_no id, CONCAT('Version ', version_no,' vom ', modified_date, ' - ',p.vorname, ' ', p.name) AS bezeichnung 
                     FROM {cc_wiki} w, {cdb_person} p WHERE w.modified_pid=p.id AND doc_id=:doc_id AND wikicategory_id=:wikicategory_id 
                     ORDER BY version_no DESC", 
                     array (':doc_id' => $params["doc_id"], 
                            ':wikicategory_id' => $params["wikicategory_id"],
                     ));
    $data = array ();
    foreach ($res as $d) $data[$d->id] = $d;
    
    return $data;
  }

  /**
   * TODO: rename camelCase?
   * @param array $params
   * @throws CTNoPermission
   */
  public function showonstartpage($params) {
    $auth = churchwiki_getAuthForAjax();
    $wikicategory_id = getVar("wikicategory_id"); 
    if (!$auth["edit"] || $auth["edit"][$wikicategory_id] != $wikicategory_id) throw new CTNoPermission("edit", "churchwiki");
    return churchwiki_setShowonstartpage($params);
  }

  public function delFile($params) {
    return churchcore_delFile($params["id"]);
  }

  public function renameFile($params) {
    return churchcore_renameFile($params["id"], $params["filename"]);
  }

}
