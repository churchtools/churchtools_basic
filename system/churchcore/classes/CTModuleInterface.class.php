<?php
/**
 * Interface for implementing ChurchTools-Modules
 */
interface CTModuleInterface {
  /**
   * Get the module name
    */
  public function getModuleName();

  /**
   * Get the relative path to the module, e.g. system/churchresource
   */
  public function getModulePath();

  /**
   * Get an array with all relevant master data for JS-View
   */
  public function getMasterData();
   
  /**
   * Save row in DB
   *
   * @param array $params array(id, table and col0..n columnname and value0..n for data).
   */
  public function saveMasterData($params);

  /**
   * Delete row in DB
   *
   * @param array $params array(id, table)
   */
  public function deleteMasterData($params);

  /**
   * Save user settings for current user
   *
   * @param array $params array(sub, val)
   */
  public function saveSetting($params);

  /**
   * Set cookie
   *
   * @param array $params array(sub, val)
   */
  public function setCookie($params);


  /**
   * Get all user settings for current user
   */
  public function getSettings();

  /**
   * Get array with churchcore_getMasterDataEntry() entries for all MasterData tables.
   * Or null if there is no table needed.
   */
  public function getMasterDataTablenames();
}

?>