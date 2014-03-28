<?php

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
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
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
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
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

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){        
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
        }        
    }
    
    private function check_jpeg($f, $fix=false ){
      # [070203]
      # check for jpeg file header and footer - also try to fix it
      if ( false !== (@$fd = fopen($f, 'r+b' )) ){
        if ( fread($fd,2)==chr(255).chr(216) ){
          fseek ( $fd, -2, SEEK_END );
          if ( fread($fd,2)==chr(255).chr(217) ){
            fclose($fd);
            return true;
          }else{
            if ( $fix && fwrite($fd,chr(255).chr(217)) ){return true;}
            fclose($fd);
            return false;
          }
        }else{fclose($fd); return false;}
      }
      else{
        return false;
      }
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
      global $user;
      
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $bezeichnung = $pathinfo['filename'];
        //$filename = "aaaaa";
        $filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
                
        if (isset($_GET["domain_type"]) && isset($_GET["domain_id"])) {        
          $dt = new DateTime();
          $id=db_insert('cc_file')->fields(array(
             "domain_type"=>$_GET["domain_type"], 
             "domain_id"=>$_GET["domain_id"], 
             "filename"=>$filename. '.' . $ext,
             "bezeichnung"=>$bezeichnung. '.' . $ext,
             "modified_date"=>$dt->format('Y-m-d H:i:s'),
             "modified_pid"=>$user->id))->execute();
        }
        else $id=null;

        $filename_absolute=$uploadDirectory . $filename . '.' . $ext;
        if ($this->file->save($filename_absolute)){
          
          // If image should be resized
          if (isset($_GET["resize"]) && $this->check_jpeg($filename_absolute)) {
            list($width, $height) = getimagesize($filename_absolute);
            if ($width>$height) {
              $new_width=$_GET["resize"]; $new_height=$height*$new_width/$width;
            } 
            else {
              $new_height=$_GET["resize"]; $new_width=$width*$new_height/$height;
            }
          
            $image_p = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($filename_absolute);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
      
            // Output
            imagejpeg($image_p, $filename_absolute, 100);
            
          }
          
          
          return array('success'=>true, "id"=>$id, "filename"=>$filename.".".$ext, "bezeichnung"=>$bezeichnung.".".$ext);
          
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        
    }    
}


function churchcore__uploadfile() {
  global $files_dir, $config;
  // list of valid extensions, ex. array("jpeg", "xml", "bmp")
  $allowedExtensions = array();
  // max file size in bytes
  
  if (!isset($config["max_uploadfile_size_kb"]))
    $sizeLimit = 10 * 1024 * 1024;
  else $sizeLimit=$config["max_uploadfile_size_kb"]*1024;
  
  $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
  $file_dir=$files_dir."/files/";
  $file_dir.=$_GET["domain_type"]."/";
  if (isset($_GET["domain_id"]))
    $file_dir.=$_GET["domain_id"];
  if (!file_exists($file_dir))
    mkdir($file_dir,0777,true);
  $result = $uploader->handleUpload($file_dir."/");
  // to pass data through iframe you will need to encode all html tags
  echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
}
