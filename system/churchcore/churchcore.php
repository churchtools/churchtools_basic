<?php

function churchcore_getAuth() {
  $cc_auth = array();
  $cc_auth=addAuth($cc_auth, 1, 'administer settings', "churchcore", null, 'Admin-Einstellungen anpassen', 1);
  $cc_auth=addAuth($cc_auth, 2, 'administer persons', 'churchcore', null, 'Berechtigungen setzen, l&ouml;schen und Benutzer simulieren', 1);
  $cc_auth=addAuth($cc_auth, 3, 'view logfile', 'churchcore', null, 'Logfile einsehen', 1);
  $cc_auth=addAuth($cc_auth, 4, 'view whoisonline', 'churchcore', null, 'Auf der Startseite sehen, wer aktuell online ist', 1);
  return $cc_auth;
}

function getMimeTypes() {
  return array("323" => "text/h323",
  "acx" => "application/internet-property-stream",
  "ai" => "application/postscript",
  "aif" => "audio/x-aiff",
  "aifc" => "audio/x-aiff",
  "aiff" => "audio/x-aiff",
  "asf" => "video/x-ms-asf",
  "asr" => "video/x-ms-asf",
  "asx" => "video/x-ms-asf",
  "au" => "audio/basic",
  "avi" => "video/x-msvideo",
  "axs" => "application/olescript",
  "bas" => "text/plain",
  "bcpio" => "application/x-bcpio",
  "bin" => "application/octet-stream",
  "bmp" => "image/bmp",
  "c" => "text/plain",
  "cat" => "application/vnd.ms-pkiseccat",
  "cdf" => "application/x-cdf",
  "cer" => "application/x-x509-ca-cert",
  "class" => "application/octet-stream",
  "clp" => "application/x-msclip",
  "cmx" => "image/x-cmx",
  "cod" => "image/cis-cod",
  "cpio" => "application/x-cpio",
  "crd" => "application/x-mscardfile",
  "crl" => "application/pkix-crl",
  "crt" => "application/x-x509-ca-cert",
  "csh" => "application/x-csh",
  "css" => "text/css",
  "dcr" => "application/x-director",
  "der" => "application/x-x509-ca-cert",
  "dir" => "application/x-director",
  "dll" => "application/x-msdownload",
  "dms" => "application/octet-stream",
  "doc" => "application/msword",
  "dot" => "application/msword",
  "dvi" => "application/x-dvi",
  "dxr" => "application/x-director",
  "eps" => "application/postscript",
  "etx" => "text/x-setext",
  "evy" => "application/envoy",
  "exe" => "application/octet-stream",
  "fif" => "application/fractals",
  "flr" => "x-world/x-vrml",
  "gif" => "image/gif",
  "gtar" => "application/x-gtar",
  "gz" => "application/x-gzip",
  "h" => "text/plain",
  "hdf" => "application/x-hdf",
  "hlp" => "application/winhlp",
  "hqx" => "application/mac-binhex40",
  "hta" => "application/hta",
  "htc" => "text/x-component",
  "htm" => "text/html",
  "html" => "text/html",
  "htt" => "text/webviewhtml",
  "ico" => "image/x-icon",
  "ief" => "image/ief",
  "iii" => "application/x-iphone",
  "ins" => "application/x-internet-signup",
  "isp" => "application/x-internet-signup",
  "jfif" => "image/pipeg",
  "jpe" => "image/jpeg",
  "jpeg" => "image/jpeg",
  "jpg" => "image/jpeg",
  "js" => "application/x-javascript",
  "latex" => "application/x-latex",
  "lha" => "application/octet-stream",
  "lsf" => "video/x-la-asf",
  "lsx" => "video/x-la-asf",
  "lzh" => "application/octet-stream",
  "m13" => "application/x-msmediaview",
  "m14" => "application/x-msmediaview",
  "m3u" => "audio/x-mpegurl",
  "m4a" => "audio/x-m4a",
  "man" => "application/x-troff-man",
  "mdb" => "application/x-msaccess",
  "me" => "application/x-troff-me",
  "mht" => "message/rfc822",
  "mhtml" => "message/rfc822",
  "mid" => "audio/mid",
  "mny" => "application/x-msmoney",
  "mov" => "video/quicktime",
  "movie" => "video/x-sgi-movie",
  "mp2" => "video/mpeg",
  "mp3" => "audio/mpeg",
  "mpa" => "video/mpeg",
  "mpe" => "video/mpeg",
  "mpeg" => "video/mpeg",
  "mpg" => "video/mpeg",
  "mpp" => "application/vnd.ms-project",
  "mpv2" => "video/mpeg",
  "ms" => "application/x-troff-ms",
  "mvb" => "application/x-msmediaview",
  "nws" => "message/rfc822",
  "oda" => "application/oda",
  "p10" => "application/pkcs10",
  "p12" => "application/x-pkcs12",
  "p7b" => "application/x-pkcs7-certificates",
  "p7c" => "application/x-pkcs7-mime",
  "p7m" => "application/x-pkcs7-mime",
  "p7r" => "application/x-pkcs7-certreqresp",
  "p7s" => "application/x-pkcs7-signature",
  "pbm" => "image/x-portable-bitmap",
  "pdf" => "application/pdf",
  "pfx" => "application/x-pkcs12",
  "pgm" => "image/x-portable-graymap",
  "pko" => "application/ynd.ms-pkipko",
  "pma" => "application/x-perfmon",
  "pmc" => "application/x-perfmon",
  "pml" => "application/x-perfmon",
  "pmr" => "application/x-perfmon",
  "pmw" => "application/x-perfmon",
  "pnm" => "image/x-portable-anymap",
  "pot" => "application/vnd.ms-powerpoint",
  "ppm" => "image/x-portable-pixmap",
  "pps" => "application/vnd.ms-powerpoint",
  "ppt" => "application/vnd.ms-powerpoint",
  "prf" => "application/pics-rules",
  "ps" => "application/postscript",
  "pub" => "application/x-mspublisher",
  "qt" => "video/quicktime",
  "ra" => "audio/x-pn-realaudio",
  "ram" => "audio/x-pn-realaudio",
  "ras" => "image/x-cmu-raster",
  "rgb" => "image/x-rgb",
  "rmi" => "audio/mid",
  "roff" => "application/x-troff",
  "rtf" => "application/rtf",
  "rtx" => "text/richtext",
  "scd" => "application/x-msschedule",
  "sct" => "text/scriptlet",
  "setpay" => "application/set-payment-initiation",
  "setreg" => "application/set-registration-initiation",
  "sh" => "application/x-sh",
  "shar" => "application/x-shar",
  "sit" => "application/x-stuffit",
  "snd" => "audio/basic",
  "spc" => "application/x-pkcs7-certificates",
  "spl" => "application/futuresplash",
  "src" => "application/x-wais-source",
  "sst" => "application/vnd.ms-pkicertstore",
  "stl" => "application/vnd.ms-pkistl",
  "stm" => "text/html",
  "svg" => "image/svg+xml",
  "sv4cpio" => "application/x-sv4cpio",
  "sv4crc" => "application/x-sv4crc",
  "t" => "application/x-troff",
  "tar" => "application/x-tar",
  "tcl" => "application/x-tcl",
  "tex" => "application/x-tex",
  "texi" => "application/x-texinfo",
  "texinfo" => "application/x-texinfo",
  "tgz" => "application/x-compressed",
  "tif" => "image/tiff",
  "tiff" => "image/tiff",
  "tr" => "application/x-troff",
  "trm" => "application/x-msterminal",
  "tsv" => "text/tab-separated-values",
  "txt" => "text/plain",
  "uls" => "text/iuls",
  "ustar" => "application/x-ustar",
  "vcf" => "text/x-vcard",
  "vrml" => "x-world/x-vrml",
  "wav" => "audio/x-wav",
  "wcm" => "application/vnd.ms-works",
  "wdb" => "application/vnd.ms-works",
  "wks" => "application/vnd.ms-works",
  "wmf" => "application/x-msmetafile",
  "wps" => "application/vnd.ms-works",
  "wri" => "application/x-mswrite",
  "wrl" => "x-world/x-vrml",
  "wrz" => "x-world/x-vrml",
  "xaf" => "x-world/x-vrml",
  "xbm" => "image/x-xbitmap",
  "xla" => "application/vnd.ms-excel",
  "xlc" => "application/vnd.ms-excel",
  "xlm" => "application/vnd.ms-excel",
  "xls" => "application/vnd.ms-excel",
  "xlt" => "application/vnd.ms-excel",
  "xlw" => "application/vnd.ms-excel",
  "xof" => "x-world/x-vrml",
  "xpm" => "image/x-xpixmap",
  "xwd" => "image/x-xwindowdump",
  "z" => "application/x-compress",
  "zip" => "application/zip");
}

function churchcore__filedownload() {
  global $files_dir;
  include_once("system/churchcore/churchcore_db.inc");
  $mime_types=getMimeTypes();
     
  $file=db_query("select * from {cc_file} f where f.id=:id and filename=:filename", 
    array(":id"=>$_GET["id"], ":filename"=>$_GET["filename"]))->fetch();
  $filename="$files_dir/files/$file->domain_type/$file->domain_id/$file->filename";
  
  $handle = fopen($filename, "rb");
  if ($handle==false) {
    echo "Datei konnte nicht gefunden werden!";
  }
  else {    
    $contents = fread($handle, filesize($filename));
    fclose($handle);
    if (isset($mime_types[substr(strrchr($filename, '.'),1)]))
      drupal_add_http_header('Content-Type',$mime_types[substr(strrchr($filename, '.'),1)],false);
    else   
      drupal_add_http_header('Content-Type','application/unknown',false);
    if ((isset($_GET["type"])) && ($_GET["type"]=="download"))
      drupal_add_http_header('Content-Disposition','attachment;filename="'.$file->bezeichnung.'"',false);
    else  
      drupal_add_http_header('Content-Disposition','inline;filename="'.$file->bezeichnung.'"',false);
    drupal_add_http_header('Cache-Control','must-revalidate, post-check=0, pre-check=0',false);  
    drupal_add_http_header('Cache-Control','private',true);
    $content=drupal_get_header();
    echo $contents;
  }  
}


function churchcore__logviewer() {
  
  if (!user_access("view logfile","churchcore")) { 
    addErrorMessage("Keine Berechtigung f&uuml;r den LogViewer!");
    return " ";
  }    

  $txt='<div class="row-fluid">';
    $txt.='<div class="span3 bs-docs-sidebar">';   
    
      $txt.='<ul id="navlist" class="nav nav-list bs-docs-sidenav affix-top">';
      $txt.='<li><a href="#log1">Wichtige Meldungen</a>';
      $txt.='<li><a href="#log2">Letzte Zugriffe</a>';
      $txt.='<li><a href="#log3">Top Zugriffe</a>';
      $txt.='</div>';
    $txt.='<div class="span9">';
  
  $limit=200;
  if (isset($_GET["showmore"]))
    $limit=1000;
  $filter="txt like 'Sende Mail%' or txt like 'Gruppe:%' or level<3";
  $val="";  
  if ((isset($_GET["filter"])) && ($_GET["filter"]!="")) {
    $filter="txt like '%".$_GET["filter"]."%'";
    $val=$_GET["filter"];
  }
  $txt.='<anchor id="log1"/><h2>Log-Meldungen</h2>';
  $res=db_query("select p.id p_id, p.vorname, p.name, log.datum, log.level, log.domain_type, log.domain_id, log.txt  from {cdb_person} p
                 RIGHT JOIN  
                   (select person_id, datum, level, domain_type, domain_id, txt 
                      from {cdb_log} l where
						$filter
						order by l.id desc 
						limit 0,$limit) as log on (log.person_id=p.id)");

  $txt.='<form class="form-inline" action="">';
  $txt.='<input type="hidden" name="q" value="churchcore/logviewer"/>';
  $txt.='<input name="filter" class="input-medium" type="text" value="'.$val.'"></input> <input type="submit" class="btn" value="Filtern"/></form>';
						
  $txt.='<table class="table table-condensed table-bordered">';
  $txt.="<tr><th>Datum<th>#<th>Objekt<th>Name<th>Meldung";
  $counter=0;
  foreach ($res as $arr) {
    $txt.="<tr><td><nobr>$arr->datum &nbsp; </nobr><td>$arr->level<td>$arr->domain_type".($arr->domain_id!=-1?"[$arr->domain_id]":"");
    $txt.="<td>";
    if (isset($arr->p_id))
      $txt.="<nobr>$arr->vorname $arr->name [$arr->p_id]</nobr>";
    $txt.="<td><small style=\"color:grey\">$arr->txt</small>";
    $counter++;
  }
  
  $txt.='</table>';
  if ((!isset($_GET["showmore"])) && ($counter>=$limit))
    $txt.='<a href="?q=churchcore/logviewer&showmore=true" class="btn">Mehr Zeilen anzeigen</a> &nbsp; ';
    
  $txt.='<anchor id="log2"><h2>Letzte Zugriffe</h2>';
  $txt.="<table class=\"table table-condensed table-bordered\"><tr><th>Name<th>Anzahl Zugriffe<th>Letzter Zugriff";
  $res=db_query("SELECT p.id pid, vorname, name, count( l.id ) count, max( lastlogin ) maxdatum
       FROM {cdb_log} l, {cdb_person} p where l.person_id=p.id GROUP BY pid, vorname, name ORDER BY max( lastlogin ) DESC ");
  foreach ($res as $arr) {
    $txt.="<tr><td>$arr->vorname $arr->name [$arr->pid]<td>".$arr->count."<td>".$arr->maxdatum."<br/>";
  }
  $txt.="</table><br/><br/>";
  
  $txt.='<anchor id="log3"><h2>Häufigste Zugriffe</h2>';
  $txt.="<table class=\"table table-condensed table-bordered\"><tr><th>Name<th>Anzahl Zugriffe<th>Letzter Zugriff";
  $res=db_query("SELECT p.id pid, vorname, name, count( l.id ) count, max( lastlogin ) maxdatum
       FROM {cdb_log} l, {cdb_person} p where l.person_id=p.id GROUP BY pid, vorname, name ORDER BY count(l.id) DESC ");
  foreach ($res as $arr) {
    $txt.="<tr><td>$arr->vorname $arr->name [$arr->pid]<td>".$arr->count."<td>".$arr->maxdatum."<br/>";
  }
  $txt.="</table><br/><br/>";
  
  $txt.="</div></div>";
  $txt.='  
    <script>
      !function ($) {
        $(function(){
          // carousel demo
          $("#navlist").affix({offset: {top: 15}});
        })
      }(window.jQuery)
    </script>';
  
  return $txt;
}

