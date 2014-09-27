<?php

/**
 * CTAdminModule
 *
 */
class CTAdminModule extends CTAbstractModule {


  /**
   * save logo
   *
   * TODO: only used to del logo - rename or use for save too
   *
   * @param array $params (filename)
   */
  function saveLogo($params) {
    if (!$params["filename"]) {
      db_query("DELETE from {cc_config}
                WHERE name='site_logo'");
    }
    else db_query("INSERT INTO {cc_config} (name, value)
                   VALUES ('site_logo', :filename)
                   ON DUPLICATE KEY UPDATE value=:filename",
                   array (":filename" => $params["filename"]));
  }
  
  /**
   * Not needed in Admin module, but forced to exist by CTInterface :-)
   */
  public function getMasterData() {

  }

}
