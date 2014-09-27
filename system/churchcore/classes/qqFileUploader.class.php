<?php

/**
 * Fileuploader
 */
class qqFileUploader {
  private $allowedExtensions = array ();
  private $sizeLimit = 10485760;
  private $file;

  /**
   * create qqUploadedFileXhr or qqUploadedFileForm depending of variable set (get or file)
   *
   * @param array $allowedExtensions
   * @param number $sizeLimit
   */
  function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760) {
    $this->allowedExtensions = array_map("strtolower", $allowedExtensions);
    $this->sizeLimit = $sizeLimit;
    
    $this->checkServerSettings();
    
    if (isset($_GET['qqfile'])) {
      $this->file = new qqUploadedFileXhr();
    }
    elseif (isset($_FILES['qqfile'])) {
      $this->file = new qqUploadedFileForm();
    }
    else {
      $this->file = false;
    }
  }

  /**
   * checks if file size match max allowed filesize
   *
   * TODO: use exception rather then die - this error may be shown to web users
   */
  private function checkServerSettings() {
    $postSize = $this->toBytes(ini_get('post_max_size'));
    $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));
    
    if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit) {
      $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
      die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
    }
  }

  /**
   * check for jpeg file header and footer - also try to fix it
   *
   * @param unknown $f, file
   * @param string $fix, default false
   *
   * @return boolean
   */
  private function check_jpeg($f, $fix = false) {
    // [070203]
    if (false !== (@$fd = fopen($f, 'r+b'))) {
      if (fread($fd, 2) == chr(255) . chr(216)) {
        fseek($fd, -2, SEEK_END);
        if (fread($fd, 2) == chr(255) . chr(217)) {
          fclose($fd);
          return true;
        }
        else {
          if ($fix && fwrite($fd, chr(255) . chr(217))) {
            return true;
          }
          fclose($fd);
          return false;
        }
      }
      else {
        fclose($fd);
        return false;
      }
    }
    else {
      return false;
    }
  }

  private function toBytes($str) {
    $val = trim($str);
    $last = strtolower($str[strlen($str) - 1]);
    switch ($last) {
      case 'g': $val *= 1024;
      case 'm': $val *= 1024;
      case 'k': $val *= 1024;
    }
    return $val;
  }

  /**
   * process uploaded files (test, save to DB + upload dir)
   *
   * @param string $uploadDirectory
   * @param bool $replaceOldFile
   *
   * @return array ('success'=>true) or ('error'=>'error message')
   */
  function handleUpload($uploadDirectory, $replaceOldFile = false) {
    global $user;
    if (!is_writable($uploadDirectory))  return array ('error' => t("uploaddircetdory.not.writable"));
    if (!$this->file) return array ('error' => t('no.uploaded.files'));
    
    $size = $this->file->getSize();
    
    if ($size == 0) return array ('error' => t('file.is.empty'));
    
    if ($size > $this->sizeLimit) return array ('error' => t('file.is.to.large'));
    
    $pathinfo = pathinfo($this->file->getName());
    $bezeichnung = $pathinfo['filename'];
    // $filename = "aaaaa";
    $filename = md5(uniqid());
    $ext = $pathinfo['extension'];
    
    if ($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)) {
      return array ('error' => t('invalid.fileextension.should.be.one.of.this', implode(', ', $this->allowedExtensions)));
    }
    //TODO: should return error if no id or type or somethin else is wrong!!!
    if (getVar("domain_type") && getVar("domain_id")) {
      $dt = new DateTime();
      
      $id = db_insert('cc_file')->fields(array (
          "domain_type" =>  getVar("domain_type"),
          "domain_id" =>  getVar("domain_id"),
          "filename" => $filename . '.' . $ext,
          "bezeichnung" => $bezeichnung . '.' . $ext,
          "modified_date" => $dt->format('Y-m-d H:i:s'),
          "modified_pid" => $user->id
      ))->execute();
    }
    else $id = null;
    
    $filename_absolute = "$uploadDirectory$filename.$ext";
    if ($this->file->save($filename_absolute)) {
      
// Sample for resizing using different max values for x, y
//       $maxX = getVar("resizeX");
//       $maxY = getVar("resizeY");
//       list ($x, $y) = getimagesize($filename_absolute)
//       if ($maxX < $x Or $maxY < $y)
//       {
//         $ratio = min($maxX/$x, $maxY/$y);
//         if (!$ratio) $ratio = max($maxX/$x, $maxY/$y);
//         if (!$ratio) $ratio = 1;
//         $width  = round($x * $ratio);
//         $height = round($y * $ratio);
//       }
      
      // If image should be resized
      if (($resize = getVar("resize")) && $this->check_jpeg($filename_absolute)) {
        list ($width, $height) = getimagesize($filename_absolute);
        // only resize if needed!
        if ($resize < $width Or $resize < $height) {
          if ($width > $height ) {
            $new_width = $resize;
            $new_height = $height * $new_width / $width;
          }
          else {
            $new_height = $resize;
            $new_width = $width * $new_height / $height;
          }
          
          $image_p = imagecreatetruecolor($new_width, $new_height);
          $image = imagecreatefromjpeg($filename_absolute);
          imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
          
          // Output
          imagejpeg($image_p, $filename_absolute, 100);
        }
      }
      
      return array ('success' => true, "id" => $id, "filename" => "$filename.$ext", "bezeichnung" => "$bezeichnung.$ext");
    }
    else return array ('error' => t('could.not.save.file.upload.canceled.or.server.error'));
  }

}

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {

  /**
   * Save the file to the specified path
   * @return boolean TRUE on success
   */
  function save($path) {
    $input = fopen("php://input", "r");
    $temp = tmpfile();
    $realSize = stream_copy_to_stream($input, $temp);
    fclose($input);
    
    if ($realSize != $this->getSize()) return false;
    
    $target = fopen($path, "w");
    fseek($temp, 0, SEEK_SET);
    stream_copy_to_stream($temp, $target);
    fclose($target);
    
    return true;
  }

  /**
   * get content of var $_GET['qqfile']
   *
   * @return string
   */
  function getName() {
    return $_GET['qqfile'];
  }

  /**
   * get uploadfile size
   *
   * @throws Exception
   *
   * @return int
   */
  function getSize() {
    if (isset($_SERVER["CONTENT_LENGTH"])) {
      return (int) $_SERVER["CONTENT_LENGTH"];
    }
    else {
      throw new Exception('Getting content length is not supported.');
    }
  }

}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {

  /**
   * Save the file to the specified path
   * @return boolean TRUE on success
   */
  function save($path) {
    if (!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)) {
      return false;
    }
    return true;
  }

  function getName() {
    return $_FILES['qqfile']['name'];
  }

  function getSize() {
    return $_FILES['qqfile']['size'];
  }

}








