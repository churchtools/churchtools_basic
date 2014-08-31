<?php

class CTChurchReportModule extends CTAbstractModule {

  public function getMasterDataTablenames() {
    $res=array();
    $res[1]=churchcore_getMasterDataEntry(1, t("query"), "query", "crp_query","sortkey,bezeichnung");
    $res[2]=churchcore_getMasterDataEntry(2, t("report"), "report", "crp_report","sortkey,bezeichnung");

    return $res;
  }

  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;

    $data["auth"]=churchreport_getAuthForAjax();

    $data["settings"]=array();
    $data["masterDataTables"] = $this->getMasterDataTablenames();
    $data["files_url"] = $base_url.$files_dir;
    $data["files_dir"] = $files_dir;
    $data["modulename"] = "churchreport";
    $data["modulespath"] = CHURCHREPORT;
    $data["adminemail"] = variable_get('site_mail', 'info@churchtools.de');
    $querys=churchcore_getTableData("crp_query");
    $data["query"] = array();
    foreach ($querys as $query) {
      $data["query"][$query->id] = array("id"=>$query->id,
          "sortkey"=>$query->sortkey,
          "query_sql"=>$query->query_sql,
          "bezeichnung"=>$query->bezeichnung);
    }
    $data["report"] = churchcore_getTableData("crp_report");
    return $data;
  }

  public function loadQuery($params) {
    $result = array();
    if ($params["id"]!="") {
      $r=db_query("select * from {crp_query} where id=:id", array(":id"=>$params["id"]))->fetch();
      $result["data"]=churchreport_getSqlAsTable($r->query_sql);
    }
    return $result;
  }
}
