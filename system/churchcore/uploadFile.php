<?php

/**
 * upload file
 */
function churchcore__uploadfile() {
  global $files_dir, $config;
  // list of valid extensions, ex. array("jpeg", "xml", "bmp")
  $allowedExtensions = array ();
  // max file size in bytes
  
  $sizeLimit = (readConf("max_uploadfile_size_kb") ? readConf("max_uploadfile_size_kb") * 1024 : 10 * 1024 * 1024);
  
  $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
  $file_dir = $files_dir . "/files/" . $_GET["domain_type"] . "/";
  if (isset($_GET["domain_id"])) $file_dir .= $_GET["domain_id"];
  if (!file_exists($file_dir)) mkdir($file_dir, 0777, true);
  $result = $uploader->handleUpload($file_dir . "/");
  // to pass data through iframe you will need to encode all html tags
  echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
}
