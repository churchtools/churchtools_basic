<?php


function churchwiki__uploadfile() {
  include_once(drupal_get_path('module', 'churchcore') .'/uploadFile.php');
  churchcore__uploadfile();
}
