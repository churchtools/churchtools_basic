<?php

/**
 * 
 */
class CTChurchReportModule extends CTAbstractModule {
  
  /**
   *
   * @see CTAbstractModule::getMasterDataTablenames()
   */
  public function getMasterDataTablenames() {
    $res = array ();
    $res[1] = churchcore_getMasterDataEntry(1, t("query"), "query", "crp_query", "sortkey,bezeichnung");
    $res[2] = churchcore_getMasterDataEntry(2, t("report"), "report", "crp_report", "sortkey,bezeichnung");
    
    return $res;
  }

  /**
   *
   * @see CTModuleInterface::getMasterData()
   */
  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;
    
    $data["auth"] = $this->getAuthForAjax();
    $data["settings"] = array ();
    $data["masterDataTables"] = $this->getMasterDataTablenames();
    $data["files_url"] = $base_url . $files_dir;
    $data["files_dir"] = $files_dir;
    $data["modulename"] = "churchreport";
    $data["modulespath"] = CHURCHREPORT;
    $data["adminemail"] = getConf('site_mail', 'info@churchtools.de');
    $queries = churchcore_getTableData("crp_query");
    $data["query"] = array ();
    foreach ($queries as $query) {
      $data["query"][$query->id] = array (
          "id" => $query->id, 
          "sortkey" => $query->sortkey, 
          "query_sql" => $query->query_sql, 
          "bezeichnung" => $query->bezeichnung,
      );
    }
    $data["report"] = churchcore_getTableData("crp_report");
    
    return $data;
  }

  /**
   * load query
   * 
   * TODO: query DB, get sql from query and query DB again???
   * 
   * @param a $params          
   * @return array with data
   */
  public function loadQuery($params) {
    $result = array ();
    if ($params["id"] != "") {
      $r = db_query("SELECT * FROM {crp_query} 
                   WHERE id=:id", array (":id" => $params["id"]))->fetch();
      
      $result["data"] = $this->getSqlAsTable($r->query_sql);
    }
    return $result;
  }
  
  /**
   * get Auth
   * @return unknown auth
   */
  private function getAuthForAjax() {
    global $user;
    $auth = $user->auth["churchreport"];
    return $auth;
  }  

  /**
   * TODO: really needed? Was not there a query function to return results as array?
   *
   * @param string $sql          
   * @return multitype:Ambigous <object, boolean, db_accessor>
   */
  private function getSqlAsTable($sql) {
    $db = db_query($sql);
    $arr = array ();
    foreach ($db as $d) {
      $arr[] = $d;
    }
    return $arr;
  }

}
