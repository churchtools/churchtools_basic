<?php

class CTChurchWikiModule extends CTAbstractModule {

  public function getMasterDataTablenames() {
    $res=array();
    $res[1]=churchcore_getMasterDataEntry(1, "Wiki-Kategorien", "wikicategory", "cc_wikicategory","sortkey,bezeichnung");

    return $res;
  }

  public function getMasterData() {
    global $user, $base_url, $files_dir, $config;

    $data["wikicategory"]=churchcore_getTableData("cc_wikicategory");
    $data["auth"]=churchwiki_getAuthForAjax();

    $data["settings"]=array();
    $data["masterDataTables"] = $this->getMasterDataTablenames();
    $data["files_url"] = $base_url.$files_dir;
    $data["files_dir"] = $files_dir;
    $data["modulename"] = "churchwiki";
    $data["modulespath"] = CHURCHWIKI;
    $data["adminemail"] = variable_get('site_mail', '');
    return $data;
  }

  public function save($params) {
    global $user;
    $auth=churchwiki_getAuthForAjax();
    if (($auth["edit"]==false) || ($auth["edit"][$params["wikicategory_id"]]!=$params["wikicategory_id"]))
      throw new CTNoPermission("edit", "churchwiki");
    $dt = new DateTime();
    $text=$_POST["val"];
    if ($text=="") $text=" "; // Save an emtpy string, so I know there is some data
    $sql="insert into {cc_wiki} (doc_id, version_no, wikicategory_id, text, modified_date, modified_pid)
      values (:doc_id, :version_no, :wikicategory_id, :text, :modified_date, :modified_pid)";
    db_query($sql,array(":doc_id"=>$_POST["doc_id"],
    ":version_no"=>churchwiki_getCurrentNo($_POST["doc_id"],$_POST["wikicategory_id"])+1,
    ":wikicategory_id"=>$_POST["wikicategory_id"],
    ":text"=>$text,
    ":modified_date"=>$dt->format('Y-m-d H:i:s'),
    ":modified_pid"=>$user->id), false);
  }

  public function load($params) {
    $auth=churchwiki_getAuthForAjax();
    if (($auth["view"]==false) || ($auth["view"][$params["wikicategory_id"]]!=$params["wikicategory_id"]))
      throw new CTNoPermission("view", "churchwiki");
    if (!isset($params["version_no"]))
      $data=churchwiki_load($params["doc_id"], $params["wikicategory_id"]);
    else
      $data=churchwiki_load($params["doc_id"], $params["wikicategory_id"], $params["version_no"]);
    return $data;
  }

  public function loadHistory($params) {
    $auth=churchwiki_getAuthForAjax();
    if (($params["wikicategory_id"]!=0) && (($auth["view"]==false) || ($auth["view"][$params["wikicategory_id"]]!=$params["wikicategory_id"])))
      throw new CTNoPermission("view", "churchwiki");
    $data=db_query("select version_no id,
      concat('Version ', version_no,' vom ', modified_date, ' - ',p.vorname, ' ', p.name) as bezeichnung from {cc_wiki} w, {cdb_person} p where w.modified_pid=p.id and doc_id=:doc_id and wikicategory_id=:wikicategory_id order by version_no desc",
        array(':doc_id'=>$params["doc_id"], ":wikicategory_id"=>$params["wikicategory_id"]));
    $res_data=array();
    foreach ($data as $d) {
      $res_data[$d->id]=$d;
    }
    return $res_data;
  }

  public function showonstartpage($params) {
    $auth=churchwiki_getAuthForAjax();
    if (($auth["edit"]==false) || ($auth["edit"][$_POST["wikicategory_id"]]!=$_POST["wikicategory_id"]))
      throw new CTNoPermission("edit", "churchwiki");
    return churchwiki_setShowonstartpage($params);
  }

  public function delFile($params) {
    return churchcore_delFile($params["id"]);
  }

  public function renameFile($params) {
    return churchcore_renameFile($params["id"], $params["filename"]);
  }
}
