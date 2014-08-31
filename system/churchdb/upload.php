<?php

/** 
 * FIXME:
 * content of class qqFileUploader in churchcore/uploadfile.php,
 * moved into qqFileUploader.class.php is not the same as here.  
 * There are differences in qqFileUploader::handleUpload, please integrate it to this class
 * 
 */

function churchdb__uploadImage() {
  global $files_dir;

  // list of valid extensions, ex. array("jpeg", "xml", "bmp")
  $allowedExtensions = array();
  // max file size in bytes
  $sizeLimit = 6 * 1024 * 1024;

  $uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
  if (!file_exists("$files_dir/tmp"))
    mkdir("$files_dir/tmp",0777,true);
  $result = $uploader->handleUpload("$files_dir/tmp/");
  // to pass data through iframe you will need to encode all html tags
  echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
}


// class qqFileUploader {
//     private $allowedExtensions = array();
//     private $sizeLimit = 10485760;
//     private $file;

//     function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){        
//         $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
//         $this->allowedExtensions = $allowedExtensions;        
//         $this->sizeLimit = $sizeLimit;
        
//         $this->checkServerSettings();       

//         if (isset($_GET['qqfile'])) {
//             $this->file = new qqUploadedFileXhr();
//         } elseif (isset($_FILES['qqfile'])) {
//             $this->file = new qqUploadedFileForm();
//         } else {
//             $this->file = false; 
//         }
//     }
    
//     private function checkServerSettings(){        
//         $postSize = $this->toBytes(ini_get('post_max_size'));
//         $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
//         if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
//             $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
//             die("{'error':'Bitte post_max_size und upload_max_filesize erhoehen auf mindestens $size (current-PostSize:$postSize, current-UploadSize: $uploadSize)'}");    
//         }        
//     }
    
//     private function toBytes($str){
//         $val = trim($str);
//         $last = strtolower($str[strlen($str)-1]);
//         switch($last) {
//             case 'g': $val *= 1024;
//             case 'm': $val *= 1024;
//             case 'k': $val *= 1024;        
//         }
//         return $val;
//     }
    
//     /**
//      * Returns array('success'=>true) or array('error'=>'error message')
//      */
//     function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
//       global $files_dir;
//         if (!is_writable($uploadDirectory)){
//             return array('error' => "Server error. Upload directory $uploadDirectory isn't writable.");
//         }
        
//         if (!$this->file){
//             return array('error' => 'No files were uploaded.');
//         }
        
//         $size = $this->file->getSize();
        
//         if ($size == 0) {
//             return array('error' => 'File is empty');
//         }
        
//         if ($size > $this->sizeLimit) {
//             return array('error' => 'File is too large');
//         }
        
//         $pathinfo = pathinfo($this->file->getName());
//         $filename = $pathinfo['filename'];
//         //$filename = md5(uniqid());
//         $ext = $pathinfo['extension'];

//         if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
//             $these = implode(', ', $this->allowedExtensions);
//             return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
//         }
        
//         if(!$replaceOldFile){
//             /// don't overwrite previous files that were uploaded
//             while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
//                 $filename .= rand(10, 99);
//             }
//         }
        
//         if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
          
          
//           $person_id = $_GET['userid'];
//           $filename_old=$uploadDirectory . $filename . '.' . $ext;
//          // $filename=$files_dir."/fotos/imageaddr$person_id.jpg";
//           $filename=md5(uniqid()).'.'.$ext;
          
//           if (rename($filename_old, $files_dir."/fotos/".$filename)) {
//               // Content type
//               // header('Content-type: image/jpeg');
              
//               // Get new dimensions
//               list($width, $height) = getimagesize($files_dir."/fotos/".$filename);
//               if ($width>$height) {
//                 $new_width=235; $new_height=$height*$new_width/$width;
//               } else {
//                 $new_height=200; $new_width=$width*$new_height/$height; 
//               }
              
//               // Resample
//               $image_p = imagecreatetruecolor($new_width, $new_height);
//               $image = imagecreatefromjpeg($files_dir."/fotos/".$filename);
//               imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
              
//               // Output
//               imagejpeg($image_p, $files_dir."/fotos/".$filename, 100);    
//               return array('success'=>true, 'filename'=>$filename);
//           }
//           else 
//             return array('error'=> 'Datei konnte nicht von '.$filename_old.' nach '.$filename.' verschoben werden.');
          
//         } 
//         else {
//             return array('error'=> 'Could not save uploaded file.' .
//                 'The upload was cancelled, or server error encountered');
//         }
//     }    
// }
