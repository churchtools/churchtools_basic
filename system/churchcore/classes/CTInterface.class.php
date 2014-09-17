<?php

/**
 * TODO: add description, rename to explain function - interface to what?
 */
class CTInterface {
  private $data = null;

  /**
   * add param $name to data array
   *
   * @param unknown $name          
   * @param string $mandatory          
   */
  function setParam($name, $mandatory = true) {
    $this->data[$name] = $mandatory;
  }

  /**
   * add params modified_date and modified_pid for modified fields
   */
  function addModifiedParams() {
    $this->data["modified_date"] = false;
    $this->data["modified_pid"] = false;
  }

  /**
   * add common parameters: startdate, enddate, repeat_...
   */
  function addTypicalDateFields() {
    $this->setParam("startdate");
    $this->setParam("enddate");
    $this->setParam("repeat_id");
    $this->setParam("repeat_frequence", false);
    $this->setParam("repeat_until", false);
    $this->setParam("repeat_option_id", false);
  }

  /**
   * Build array for use as DB parameter from $params.
   * Test against $this->data for needed parameters.
   *
   * @param a $params          
   * @throws CTException
   *
   * @return multitype:unknown
   */
  function getDBParamsArrayFromParams($params) {
    $p = array ();
    foreach ($this->data as $key => $val) {
      if (!isset($params[$key]) && $val == true) {
        throw new CTException("Pflicht-Parameter $key wurde nicht uebergeben!");
      }
      else $p[":" . $key] = $params[$key];
    }
    return $p;
  }

  /**
   * Build array for use in DB funtions.
   * Test against $this->data for needed parameters
   *
   * @param array $params          
   * @param string $setOptionalToNullValue          
   * @throws CTException
   *
   * @return array with parameters
   */
  function getDBInsertArrayFromParams($params, $setOptionalToNull = false) {
    global $user;
    $p = array ();
    foreach ($this->data as $key => $val) {
      if (!isset($params[$key])) {
        if ($val == true) throw new CTException("Pflicht-Parameter $key wurde nicht uebergeben!");
        else if ($setOptionalToNull) $p[$key] = null;
        else if ($key == "modified_date") {
          $dt = new DateTime();
          $p[$key] = $dt->format('Y-m-d H:i:s');
        }
        else if ($key == "modified_pid") $p[$key] = $user->id;
      }
      else
        $p[$key] = $params[$key];
    }
    return $p;
  }

}

?>