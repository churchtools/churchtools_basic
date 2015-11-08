<?php

/**
 * upload file
 *
 * TODO: if this is included only to execute churchcore__uploadfile why not remove the surrounding function and include the code only?
 */
function churchcore__uploadfile() {
  global $files_dir, $config;
  // list of valid extensions, ex. array("jpeg", "xml", "bmp")
  $allowedExtensions = array ();
  // max file size in bytes

  $sizeLimit = ($s = getConf("max_uploadfile_size_kb")) ? ($s * 1024) : (10 * 1024 * 1024);

  $result = null;
  try {
    $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
  }
  catch (Exception $e) {
    $result = "Entweder POST_MAX_SIZE und UPLOAD_MAX_SIZE erhöhen oder Zahl erniedrigen. ";
  }
  if ($result == null) {
    $file_dir = $files_dir . "/blobs/";
    if (!file_exists($file_dir)) mkdir($file_dir, 0755, true);
    $result = $uploader->handleUpload($file_dir);
  }
  // to pass data through iframe you will need to encode all html tags
  echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
}
