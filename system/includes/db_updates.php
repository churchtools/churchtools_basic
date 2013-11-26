<?php 
    

function installDataTables() {
  if (!file_exists("system/schema_install.sql")) {
    addErrorMessage('Kann Datenbank-Install-Datei schema_install.sql nicht finden!<p><a href="?installdb=true" class="btn">Nochmal versuchen</a>');
    return false;
  }
  $file=file_get_contents("system/schema_install.sql");
  $sqls=explode(";\n",$file);
  foreach ($sqls as $sql) {
    db_query($sql, null, false);
  }  
  return true;
}

function initTablesFor20() {
  db_query("create table {cc_config} (name varchar(255) not null, value varchar(255) not null)");
  db_query("ALTER TABLE {cc_config} ADD PRIMARY KEY(name)");
  db_query("insert into {cc_config} (name, value) values ('version', '2.00')");
  db_query("create table {cc_session} (person_id int(11) not null, session varchar(255) not null, hostname varchar(255) not null, datum datetime not null)");

  db_query("CREATE TABLE {cc_auth} (id int(11) NOT NULL, auth varchar(255) NOT NULL, modulename varchar(255) NOT NULL, datenfeld varchar(255) DEFAULT NULL, bezeichnung varchar(255) NOT NULL,PRIMARY KEY (id)) ");
  
  db_query("insert into {cc_auth}  VALUES(1, 'administer settings', 'churchcore', NULL, 'Stammdaten pflegen, Logfile einsehen, Einstellung, ...')");
  db_query("insert into {cc_auth}  VALUES(2, 'administer persons', 'churchcore', NULL, 'Berechtigungen setzen, löschen und Benutzer simulieren')");
  db_query("insert into {cc_auth}  VALUES(3, 'view logfile', 'churchcore', NULL, 'Logfile einsehen')");

  db_query("insert into {cc_auth}  VALUES(101, 'view', 'churchdb', NULL, 'Anwendung ChurchDB sehen')");
  db_query("insert into {cc_auth}  VALUES(102, 'view alldata', 'churchdb', 'cdb_bereich', 'Alle Datensätze des jeweiligen Bereiches einsehen')");
  db_query("insert into {cc_auth}  VALUES(103, 'view birthdaylist', 'churchdb', NULL, 'Geburtagsliste einsehen')");
  db_query("insert into {cc_auth}  VALUES(104, 'view group statistics', 'churchdb', NULL, 'Gruppenstatistik einsehen')");
  db_query("insert into {cc_auth}  VALUES(105, 'view memberliste', 'churchdb', NULL, 'Mitgliederliste einsehen')");
  db_query("insert into {cc_auth}  VALUES(106, 'view statistics', 'churchdb', NULL, 'Gesamtstatistik einsehen')");
  db_query("insert into {cc_auth}  VALUES(107, 'view tags', 'churchdb', NULL, 'Tags einsehen')");
  db_query("insert into {cc_auth}  VALUES(108, 'view history', 'churchdb', NULL, 'Historie eines Datensatzes ansehen')");
  db_query("insert into {cc_auth}  VALUES(109, 'edit relations', 'churchdb', NULL, 'Beziehungen editieren')");
  db_query("insert into {cc_auth}  VALUES(110, 'edit groups', 'churchdb', NULL, 'Gruppenzuordnungen editieren')");
  db_query("insert into {cc_auth}  VALUES(111, 'write access', 'churchdb', NULL, 'Schreibzugriff auf bestimmen Bereich')");
  db_query("insert into {cc_auth}  VALUES(112, 'export data', 'churchdb', NULL, 'Daten exportieren')");
  db_query("insert into {cc_auth}  VALUES(113, 'view comments', 'churchdb', 'cdb_comment_viewer', 'Kommentare einsehen')");
  db_query("insert into {cc_auth}  VALUES(114, 'administer groups', 'churchdb', NULL, 'Gruppen erstellen, löschen, etc.')");
  db_query("insert into {cc_auth}  VALUES(199, 'edit masterdata', 'churchdb', NULL, 'Stammdaten editieren')");

  db_query("insert into {cc_auth}  VALUES(201, 'view', 'churchresource', NULL, 'Anwendung ChurchResource sehen')");
  db_query("insert into {cc_auth}  VALUES(202, 'administer bookings', 'churchresource', NULL, 'Anfragen editieren, ablehen, etc.')");
  db_query("insert into {cc_auth}  VALUES(299, 'edit masterdata', 'churchresource', NULL, 'Stammdaten editieren')");

  db_query("insert into {cc_auth}  VALUES(301, 'view', 'churchservice', NULL, 'Anwendung ChurchService sehen')");
  db_query("insert into {cc_auth}  VALUES(302, 'view history', 'churchservice', NULL, 'Historie anschauen')");
  db_query("insert into {cc_auth}  VALUES(303, 'edit events', 'churchservice', NULL, 'Events erstellen, löschen, etc.')");
  db_query("insert into {cc_auth}  VALUES(304, 'view servicegroup', 'churchservice', 'cs_servicegroup', 'Dienstanfragen der jeweiligen Gruppe einsehen')");
  db_query("insert into {cc_auth}  VALUES(305, 'edit servicegroup', 'churchservice', 'cs_servicegroup', 'Dienstanfragen der jeweiligen Gruppe editieren')");
  db_query("insert into {cc_auth}  VALUES(306, 'create bookings', 'churchresource', NULL, 'Erstelle eigene Anfragen')");
  db_query("insert into {cc_auth}  VALUES(399, 'edit masterdata', 'churchservice', NULL, 'Stammdaten editieren')");
    
  db_query("CREATE TABLE {cc_domain_auth} (domain_type varchar(30) NOT NULL, domain_id int(11) NOT NULL, auth_id int(11) NOT NULL,  daten_id int(11) DEFAULT NULL)");
}

function updateDB_201() {
  db_query("ALTER TABLE {cs_servicegroup} ADD viewall_yn int( 1 ) NOT NULL DEFAULT 0 AFTER bezeichnung");
}

function updateDB_202() {
  db_query("CREATE TABLE {cc_help} (doc_id varchar(255) NOT NULL,
    text blob NOT NULL,
    modifieddate datetime NOT NULL,
    modifieduser int(11) NOT NULL,
    PRIMARY KEY (doc_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
  db_query("update {cc_auth} set id=121 where id=103");
  db_query("update {cc_domain_auth} set auth_id=121 where auth_id=103");
  db_query("update {cc_auth} set id=122 where id=105");
  db_query("update {cc_domain_auth} set auth_id=122 where auth_id=105");
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld ,bezeichnung) VALUES ( 
            103,  'view alldetails',  'churchdb', NULL ,  'Alle Informationen der Person sehen')");
}

function updateDB_203() {
  db_query("ALTER TABLE {cs_eventtemplate_service} ADD UNIQUE (eventtemplate_id , service_id)"); 
}

function updateDB_204() {
  db_query("CREATE TABLE {cc_file} (
  id int(11) NOT NULL AUTO_INCREMENT,
  domain_type varchar(30) NOT NULL,
  domain_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  UNIQUE KEY domain_type (domain_type,domain_id,filename),
  PRIMARY KEY (id))");
  
  db_query("INSERT INTO {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
     VALUES ('307',  'manage absent',  'churchservice', NULL ,  'Abwesenheiten einsehen und pflegen');");
  
  db_query("INSERT INTO {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
     VALUES ('308',  'edit facts',  'churchservice', NULL ,  'Fakten pflegen');");
  
  db_query("CREATE TABLE {cs_absent_reason} (
    id int(11) NOT NULL AUTO_INCREMENT,
    bezeichnung varchar(255) NOT NULL,
    sortkey int(11) NOT NULL,
    PRIMARY KEY (id)
  )");
  
  db_query("INSERT INTO {cs_absent_reason} VALUES(1, 'Abwesend', 2)");
  db_query("INSERT INTO {cs_absent_reason} VALUES(2, 'Urlaub', 1)");
  db_query("INSERT INTO {cs_absent_reason} VALUES(3, 'Krank', 0)");
  
  db_query("CREATE TABLE {cs_absent} (
    id int(11) NOT NULL AUTO_INCREMENT,
    person_id int(11) NOT NULL,
    absent_reason_id int(11) NOT NULL,
    bezeichnung varchar(255) DEFAULT NULL,
    startdate datetime NOT NULL,
    enddate datetime NOT NULL,
    modifieddate datetime NOT NULL,
    modifieduser int(11) NOT NULL,
  PRIMARY KEY (id)
  )");
  
  db_query("CREATE TABLE {cs_event_fact} (
    event_id int(11) NOT NULL,
    fact_id int(11) NOT NULL,
    value int(11) NOT NULL,
    modifieddate datetime DEFAULT NULL,
    modifieduser int(11) DEFAULT NULL,
    PRIMARY KEY (event_id,fact_id)
  )");
  
  db_query("
    CREATE TABLE {cs_fact} (
      id int(11) NOT NULL AUTO_INCREMENT,
      bezeichnung varchar(255) NOT NULL,
      sortkey int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (id)
  )");
  
  db_query("INSERT INTO {cs_fact} VALUES(1, 'Besucher', 0)");
  db_query("INSERT INTO {cs_fact} VALUES(2, 'Kollekte', 0)");
}

function updateDB_205(){
  db_query("INSERT INTO {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
     VALUES ('401',  'view',  'churchcal', NULL ,  'ChurchCal sehen');");  
  db_query("INSERT INTO {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
     VALUES ('402',  'edit events',  'churchcal', NULL ,  'Termine pflegen');");  
  db_query("CREATE TABLE {cc_cal} (
    id int(11) NOT NULL AUTO_INCREMENT,
    bezeichnung varchar(255) NOT NULL,
    startdate datetime NOT NULL,
    enddate datetime NOT NULL,
    repeat_id int(1) NOT NULL,
    repeat_frequence int(2) NOT NULL,
    repeat_until datetime NOT NULL,
    modifieddate datetime NOT NULL,
    modifieduser int(11) NOT NULL,
    PRIMARY KEY (id)
  )");
}

function updateDB_208(){
  db_query("ALTER TABLE {cc_cal} CHANGE repeat_frequence repeat_frequence INT( 2 ) NULL DEFAULT NULL");
  db_query("ALTER TABLE {cc_cal} CHANGE repeat_until repeat_until DATETIME NULL");
  db_query("CREATE TABLE {cc_cal_except} (
    id int(11) NOT NULL AUTO_INCREMENT,
    cal_id int(11) NOT NULL,
    except_date datetime not null,
    modifieddate datetime NOT NULL,
    modifieduser int(11) NOT NULL,
    PRIMARY KEY (id)) ");
  db_query("ALTER TABLE {cs_category} ADD color VARCHAR( 20 ) NULL AFTER bezeichnung");
  db_query("ALTER TABLE {cc_cal} ADD category_id INT( 11 ) NOT NULL DEFAULT '0' AFTER enddate");
  db_query("ALTER TABLE {cdb_status} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
  db_query("ALTER TABLE {cdb_bereich} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
  db_query("ALTER TABLE {cdb_gruppentyp} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
  db_query("ALTER TABLE {cdb_station} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
  db_query("ALTER TABLE {cs_eventservice} ADD counter INT( 11 ) NULL AFTER service_id");
  db_query("ALTER TABLE {cr_booking} CHANGE userid userid VARCHAR( 50 )");
  db_query("insert into {cc_config} (name, value) values ('cronjob_delay','0')");  
}

function updateDB_209(){
  db_query("ALTER TABLE {cdb_person} ADD active_yn INT( 1 ) NOT NULL DEFAULT  '1' AFTER vorname");
  db_query("ALTER TABLE {cdb_person} ADD optigem_nr VARCHAR( 30 ) NOT NULL AFTER cmsuserid");
  db_query("ALTER TABLE {cc_cal} ADD ort VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER bezeichnung");
  db_query("ALTER TABLE {cc_cal} ADD notizen VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER ort");
  db_query("ALTER TABLE {cc_cal} ADD intern_yn int(1) not NULL default '0' AFTER notizen");
}

function updateDB_211() {
  db_query("ALTER TABLE {cs_category} ADD show_in_churchcal_yn INT(1) NOT NULL DEFAULT '1' AFTER color");
  db_query("ALTER TABLE {cr_booking} ADD show_in_churchcal_yn INT(1) NOT NULL DEFAULT '0'");
}

function updateDB_212() {
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (105, 'view address', 'churchdb', 'Darf die Adressdaten einsehen')");
  db_query("UPDATE {cc_auth} set bezeichnung='Alle Informationen der Person sehen, inkl. Adressdaten, Gruppenzuordnung, etc.' where auth='view alldetails'");
  
  db_query("ALTER TABLE {cc_cal} CHANGE modifieddate modified_date DATETIME NOT NULL");
  db_query("ALTER TABLE {cc_cal} CHANGE modifieduser modified_pid int(11) not null");
  db_query("ALTER TABLE {cc_cal_except} CHANGE modifieddate modified_date DATETIME NOT NULL");
  db_query("ALTER TABLE {cc_cal_except} CHANGE modifieduser modified_pid int(11) not null");
  db_query("ALTER TABLE {cc_help} CHANGE modifieddate modified_date DATETIME NOT NULL");
  db_query("ALTER TABLE {cc_help} CHANGE modifieduser modified_pid int(11) not null");
  
  // Erstmal Sturktur ändern, das geht schnell!
  db_query("ALTER TABLE {cdb_log} ADD person_id int(11) not null default -1 after userid");  
  db_query("ALTER TABLE {cdb_comment} ADD person_id int(11) not null default -1 after userid");
  db_query("ALTER TABLE {cs_eventservice} CHANGE modifieddate modified_date DATETIME NOT NULL");
  db_query("ALTER TABLE {cs_eventservice} ADD modified_pid int(11) not null default -1 after modifieduser");  
  db_query("ALTER TABLE {cs_event_fact} CHANGE modifieddate modified_date DATETIME NOT NULL");
  db_query("ALTER TABLE {cs_event_fact} CHANGE modifieduser modified_pid int(11) not null");
  db_query("ALTER TABLE {cs_absent} CHANGE modifieddate modified_date DATETIME NOT NULL");
  db_query("ALTER TABLE {cs_absent} CHANGE modifieduser modified_pid int(11) not null");

  // Jetzt kommen die aufwändigen SQLs...
  db_query("UPDATE {cdb_log} log JOIN {cdb_person} p ON p.cmsuserid=log.userid SET log.person_id=p.id");
  db_query("UPDATE {cdb_comment} c JOIN {cdb_person} p ON p.cmsuserid=c.userid SET c.person_id=p.id");
  db_query("UPDATE {cs_eventservice} es JOIN {cdb_person} p ON p.cmsuserid=es.modifieduser SET es.modified_pid=p.id");
  
}

function updateDB_213() {
  db_query("ALTER TABLE {cr_booking} ADD person_id int(11) not null default -1 after userid");  
  db_query("ALTER TABLE {cr_exception} ADD person_id int(11) not null default -1 after userid");  
  db_query("ALTER TABLE {cr_log} ADD person_id int(11) not null default -1 after userid");  
  db_query("ALTER TABLE {cr_resource} ADD admin_person_ids int(11) not null default -1 after adminmails");  
  db_query("ALTER TABLE {cr_resource} CHANGE adminmails adminmails_old varchar(30) null");  
  
  db_query("UPDATE {cr_booking} a JOIN {cdb_person} p ON p.cmsuserid=a.userid SET a.person_id=p.id");
  db_query("UPDATE {cr_exception} a JOIN {cdb_person} p ON p.cmsuserid=a.userid SET a.person_id=p.id");
  db_query("UPDATE {cr_log} a JOIN {cdb_person} p ON p.cmsuserid=a.userid SET a.person_id=p.id");      
  db_query("UPDATE {cr_resource} a JOIN {cdb_person} p ON p.email=a.adminmails_old SET a.admin_person_ids=p.id");      
}

function updateDB_214() {
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (4, 'view whoisonline', 'churchcore', 'Sieht auf der Startseite, wer aktuell online ist')");
  db_query("CREATE TABLE {cc_loginstr} 
      (person_id int(11) NOT NULL, loginstr varchar(255) NOT NULL, create_date date NOT NULL) ");
  db_query("insert into {cc_loginstr} (person_id, loginstr, create_date) 
               select id person_id, loginstr, now() from {cdb_person} where loginstr is not null");   
}

function updateDB_215() {
  global $files_dir;
  db_query("ALTER TABLE {cs_service} ADD allowtonotebyconfirmation_yn INT( 1 ) NOT NULL DEFAULT 0 AFTER sendremindermails_yn");
}

function updateDB_216() {
  global $files_dir;
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (311, 'view song', 'churchservice', 'Darf die Songs anschauen und Dateien herunterladen')");
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (312, 'edit song', 'churchservice', 'Darf die Songs editieren und Dateien hochladen')");
  db_query("CREATE TABLE {cs_song} (
      id int(11) NOT NULL AUTO_INCREMENT,
      bezeichnung varchar(50) NOT NULL,
      author varchar(255) NOT NULL,
      ccli varchar(50) NOT NULL,
      copyright varchar(255) NOT NULL,
      note varchar(255) NOT NULL,
      modified_date datetime NOT NULL,
      modified_pid int(11) NOT NULL,
    PRIMARY KEY (id)) "
  );
  db_query("CREATE TABLE {cs_song_arrangement} (
      id int(11) NOT NULL AUTO_INCREMENT,
      song_id int(11) NOT NULL,
      bezeichnung varchar(50) NOT NULL,
      default_yn int(1) NOT NULL,
      tonality varchar(20) NOT NULL,
      bpm varchar(10) NOT NULL,
      beat varchar(10) NOT NULL,
      length_min int(3) NOT NULL DEFAULT '0',
      length_sec int(2) NOT NULL DEFAULT '0',
      note varchar(255) NOT NULL,
      modified_date datetime NOT NULL,
      modified_pid int(11) NOT NULL,
    PRIMARY KEY (id)) 
  ");
  db_query("
  CREATE TABLE {cs_servicegroup_person_weight} (
    person_id int(11) NOT NULL,
    servicegroup_id int(11) NOT NULL,
    max_per_month int(1) NOT NULL DEFAULT '4',
    relation_weight int(1) NOT NULL DEFAULT '0',
    morning_weight int(1) NOT NULL DEFAULT '0',
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
    PRIMARY KEY (person_id,servicegroup_id))"
  );
  
  echo "Anpassungen bei den Files...";
  $files=churchcore_getTableData("cc_file");
  if ($files!=null) {
    foreach($files as $file) {
      if (!file_exists("$files_dir/files/$file->domain_type"))
        mkdir("$files_dir/files/$file->domain_type",0777,true);
      if (file_exists("$files_dir/files/$file->domain_id"))
        rename("$files_dir/files/$file->domain_id", "$files_dir/files/$file->domain_type/$file->domain_id");  
    }
  }  
}

function updateDB_217() {
  db_query("insert into {cc_config} values ('max_uploadfile_size_kb', 10000)");
  db_query("insert into {cc_config} values ('cronjob_dbdump', 0)");
  db_query("CREATE TABLE {cs_songcategory} (
    id INT( 11 ) NOT NULL,
    bezeichnung VARCHAR( 100 ) NOT NULL ,
    sortkey int(11) not null default 0,
    PRIMARY KEY (  id ))");
  db_query("insert into {cs_songcategory} values (0,'Unbekannt',0)"); 
  db_query("ALTER TABLE {cs_song} ADD songcategory_id INT( 11 ) NOT NULL AFTER bezeichnung");
  db_query("INSERT INTO  {cc_auth} (
    id, auth , modulename , datenfeld ,bezeichnung )
    VALUES (
    '313',  'view songcategory',  'churchservice',  'cs_songcategory',  'Erlaubt den Zugriff auf bestimmte Song-Kategorien'
    )");
  db_query("ALTER TABLE {cc_file} ADD modified_date DATETIME NOT NULL");
  db_query("ALTER TABLE {cc_file} ADD modified_pid INT( 11 ) NOT NULL");
}

function updateDB_218() {
  db_query("RENAME TABLE {cc_help} TO {cc_wiki}");
  db_query("CREATE TABLE {cc_wikicategory} (id INT( 11 ) NOT NULL, bezeichnung VARCHAR( 50 ) NOT NULL , sortkey INT( 11 ) NOT NULL)");
  db_query("ALTER TABLE {cc_wikicategory} ADD PRIMARY KEY ( id )");
  db_query("insert into {cc_wikicategory} values (0, 'Standard', 1)");
  db_query("ALTER TABLE {cc_wiki} ADD version_no INT( 11 ) NOT NULL default 1 AFTER doc_id");
  db_query("ALTER TABLE {cc_wiki} ADD wikicategory_id INT( 11 ) NOT NULL default 0 AFTER version_no");  
  db_query("ALTER TABLE {cc_wiki} DROP PRIMARY KEY, ADD PRIMARY KEY (doc_id, version_no, wikicategory_id)");
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (501, 'view', 'churchwiki', 'Darf das Wiki sehen')");
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, datenfeld, bezeichnung) values (502, 'view category', 'churchwiki', 'cc_wikicategory', 'Darf bestimmte Wiki-Kategorien einsehen')");
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, datenfeld, bezeichnung) values (503, 'edit category', 'churchwiki', 'cc_wikicategory', 'Darf bestimmte Wiki-Kategorien editieren')");
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (599, 'edit masterdata', 'churchwiki', 'Darf die Stammdaten editieren')");
  db_query("insert into {cc_config} values ('churchwiki_name', 'ChurchWiki')");
  db_query("ALTER TABLE {cdb_gemeindeperson_gruppe} ADD comment VARCHAR( 255 ) null");
  db_query("CREATE TABLE {cdb_feldkategorie} (
    id int(11) NOT NULL,
    bezeichnung varchar(50) NOT NULL,
    intern_code varchar(50) NOT NULL,
    db_tabelle varchar(50) NOT NULL,
    id_name varchar(50) NOT NULL,
    PRIMARY KEY (id)
    );");
  db_query("INSERT INTO {cdb_feldkategorie} VALUES (1, 'Adresse', 'f_address', 'cdb_person', 'id')");
  db_query("INSERT INTO {cdb_feldkategorie} VALUES (2, 'Informationen', 'f_church', 'cdb_gemeindeperson', 'person_id')");
  db_query("INSERT INTO {cdb_feldkategorie} VALUES (3, 'Kategorien', 'f_category', 'cdb_gemeindeperson', 'id')");
  db_query("INSERT INTO {cdb_feldkategorie} VALUES (4, 'Gruppe', 'f_group', 'cdb_gruppe', 'id')");
  
 db_query("
CREATE TABLE {cdb_feldtyp} (
  id int(11) NOT NULL,
  bezeichnung varchar(30) NOT NULL,
  intern_code varchar(10) NOT NULL,
  PRIMARY KEY (id)
)");

db_query("INSERT INTO {cdb_feldtyp} VALUES (1, 'Textfeld', 'text')");
db_query("INSERT INTO {cdb_feldtyp} VALUES (2, 'Auswahlfeld', 'select')");
db_query("INSERT INTO {cdb_feldtyp} VALUES (3, 'Datumsfeld', 'date');");
db_query("INSERT INTO {cdb_feldtyp} VALUES (4, 'Ja-Nein-Feld', 'checkbox')");
db_query("INSERT INTO {cdb_feldtyp} VALUES (5, 'Kommentarfeld', 'textarea')");
 
 db_query("
CREATE TABLE {cdb_feld} (
  id int(11) NOT NULL,
  feldkategorie_id int(11) NOT NULL,
  feldtyp_id int(11) NOT NULL,
  db_spalte varchar(50) NOT NULL,
  db_stammdatentabelle varchar(50) DEFAULT NULL,
  aktiv_yn int(1) NOT NULL DEFAULT '1',
  langtext varchar(200) NOT NULL,
  kurztext varchar(50) NOT NULL,
  zeilenende varchar(10) NOT NULL,
  autorisierung varchar(50) DEFAULT NULL,
  laenge int(3) DEFAULT NULL,
  sortkey int(11) NOT NULL,
  PRIMARY KEY (id)
)");

db_query("INSERT INTO {cdb_feld} VALUES(1, 1, 1, 'titel', NULL, 1, 'Titel', '', ' ', NULL, 12, 1)");
db_query("INSERT INTO {cdb_feld} VALUES(2, 1, 1, 'vorname', NULL, 1, 'Vorname', '', '&nbsp;', NULL, 30, 2)");
db_query("INSERT INTO {cdb_feld} VALUES(3, 1, 1, 'name', NULL, 1, 'Name', '', '<br/>', NULL, 30, 3)");
db_query("INSERT INTO {cdb_feld} VALUES(4, 1, 1, 'strasse', NULL, 1, 'Strasse', '', '<br/>', 'ViewAllDetailsOrPersonLeader', 30, 4)");
db_query("INSERT INTO {cdb_feld} VALUES(5, 1, 1, 'zusatz', NULL, 1, 'Addresszusatz', '', '<br/>', 'ViewAllDetailsOrPersonLeader', 30, 5)");
db_query("INSERT INTO {cdb_feld} VALUES(6, 1, 1, 'plz', NULL, 1, 'Postleitzahl', '', '&nbsp;', NULL, 6, 6)");
db_query("INSERT INTO {cdb_feld} VALUES(7, 1, 1, 'ort', NULL, 1, 'Ort', '', '<br/>', NULL, 40, 7)");
db_query("INSERT INTO {cdb_feld} VALUES(8, 1, 1, 'land', NULL, 1, 'Land', '', '<br/><br/>', NULL, 30, 8)");
db_query("INSERT INTO {cdb_feld} VALUES(9, 1, 2, 'geschlecht_no', 'sex', 1, 'Geschlecht', 'Geschlecht', '<br/>', NULL, 11, 9)");
db_query("INSERT INTO {cdb_feld} VALUES(10, 1, 1, 'telefonprivat', NULL, 1, 'Telefon privat', 'Tel. privat', '<br/>', NULL, 30, 10)");
db_query("INSERT INTO {cdb_feld} VALUES(11, 1, 1, 'telefongeschaeftlich', NULL, 1, 'Telefon gesch&auml;ftl.', 'Tel. gesch&auml;ft.', '<br/>', NULL, 20, 11)");
db_query("INSERT INTO {cdb_feld} VALUES(12, 1, 1, 'telefonhandy', NULL, 1, 'Mobil', 'Mobil', '<br/>', NULL, 20, 12)");
db_query("INSERT INTO {cdb_feld} VALUES(13, 1, 1, 'fax', NULL, 1, 'Fax', 'Fax', '<br/>', NULL, 20, 13)");
db_query("INSERT INTO {cdb_feld} VALUES(14, 1, 1, 'email', NULL, 1, 'E-Mail', 'E-Mail', '<br/>', NULL, 50, 14)");
db_query("INSERT INTO {cdb_feld} VALUES(15, 1, 1, 'cmsuserid', NULL, 1, 'Benutzername', 'Benutzername', '<br/>', NULL, 50, 15)");
db_query("INSERT INTO {cdb_feld} VALUES(16, 2, 3, 'geburtsdatum', NULL, 1, 'Geburtsdatum', 'Geburtsdatum', '<br/>', NULL, 0, 1)");
db_query("INSERT INTO {cdb_feld} VALUES(17, 2, 1, 'geburtsname', NULL, 1, 'Geburtsname', 'Geburtsname', '<br/>', NULL, 30, 2)");
db_query("INSERT INTO {cdb_feld} VALUES(18, 2, 1, 'geburtsort', NULL, 1, 'Geburtsort', 'Geburtsort', '<br/>', NULL, 30, 3)");
db_query("INSERT INTO {cdb_feld} VALUES(19, 2, 1, 'beruf', NULL, 1, 'Beruf', 'Beruf', '<br/>', NULL, 50, 4)");
db_query("INSERT INTO {cdb_feld} VALUES(20, 2, 1, 'nationalitaet_id', 'nationalitaet', 2, 'Nationalit&auml;t', 'Nationalit&auml;t', '<br/>', NULL, 11, 5)");
db_query("INSERT INTO {cdb_feld} VALUES(21, 2, 2, 'familienstand_no', 'familyStatus', 1, 'Familenstand', 'Familenstand', '<br/>', NULL, 11, 6)");
db_query("INSERT INTO {cdb_feld} VALUES(22, 2, 3, 'hochzeitsdatum', NULL, 1, 'Hochzeitstag', 'Hochzeitstag', '<br/><br/>', NULL, 0, 7)");
db_query("INSERT INTO {cdb_feld} VALUES(23, 2, 3, 'erstkontakt', NULL, 1, 'Erstkontakt', 'Erstkontakt', '<br/>', NULL, 0, 8)");
db_query("INSERT INTO {cdb_feld} VALUES(24, 2, 3, 'zugehoerig', NULL, 1, 'Zugeh&ouml;rig', 'Zugeh&ouml;rig', '<br/>', NULL, 0, 9)");
db_query("INSERT INTO {cdb_feld} VALUES(25, 2, 3, 'eintrittsdatum', NULL, 1, 'Mitglied seit', 'Mitglied seit', '<br/>', NULL, 0, 10)");
db_query("INSERT INTO {cdb_feld} VALUES(26, 2, 1, 'ueberweisen von', NULL, 1, '&Uuml;berwiesen von', '&Uuml;berwiesen von', '<br/>', NULL, 30, 11)");
db_query("INSERT INTO {cdb_feld} VALUES(27, 2, 3, 'austrittsdatum', NULL, 1, 'Mitglied bis', 'Mitglied bis', '<br/>', NULL, 0, 12)");
db_query("INSERT INTO {cdb_feld} VALUES(28, 2, 1, 'ueberwiesen nach', NULL, 1, '&Uuml;berwiesen nach', '&Uuml;berwiesen nach', '<br/><br/>', NULL, 30, 13)");
db_query("INSERT INTO {cdb_feld} VALUES(29, 2, 3, 'taufdatum', NULL, 1, 'Taufdatum', 'Taufdatum', '<br/>', NULL, 0, 14)");
db_query("INSERT INTO {cdb_feld} VALUES(30, 2, 1, 'taufort', NULL, 1, 'Taufort', '', '<br/>', NULL, 50, 15)");
db_query("INSERT INTO {cdb_feld} VALUES(31, 2, 1, 'getauftdurch', NULL, 1, 'Getauft durch', 'Getauft durch', '<br/>', NULL, 50, 16)");
db_query("INSERT INTO {cdb_feld} VALUES(32, 3, 2, 'status_id', 'status', 1, 'Status', 'Status', '<br/>', NULL, 11, 1)");
db_query("INSERT INTO {cdb_feld} VALUES(33, 3, 2, 'station_id', 'station', 1, 'Station', 'Station', '<br/>', NULL, 11, 2)");
db_query("INSERT INTO {cdb_feld} VALUES(34, 4, 1, 'bezeichnung', NULL, 1, 'Bezeichnung', 'Bezeichnung', '<br/>', NULL, 35, 1)");
db_query("INSERT INTO {cdb_feld} VALUES(35, 4, 2, 'distrikt_id', 'districts', 1, 'Distrikt', 'Distrikt', '<br/>', 'admingroups', 11, 2)");
db_query("INSERT INTO {cdb_feld} VALUES(36, 4, 2, 'followup_typ_id', 'followupTypes', 1, 'Followup-Typ', 'Followup-Typ', '<br/>', 'admingroups', 11, 3)");
db_query("INSERT INTO {cdb_feld} VALUES(37, 4, 2, 'fu_nachfolge_typ_id', 'FUNachfolgeDomains', 1, 'Followup-Nachfolger', 'Followup-Nachfolger', '<br/>', 'admingroups', 11, 4)");
db_query("INSERT INTO {cdb_feld} VALUES(38, 4, 2, 'fu_nachfolge_objekt_id', 'code:selectNachfolgeObjektId', 1, 'Followup-Nachfolger-Auswahl', 'Followup-Nachfolger-Auswahl', '<br/>', 'admingroups', 11, 5)");
db_query("INSERT INTO {cdb_feld} VALUES(39, 4, 3, 'gruendungsdatum', NULL, 1, 'Gr&uuml;ndungsdatum', 'Gr&uuml;ndungsdatum', '<br/>', NULL, 0, 6)");
db_query("INSERT INTO {cdb_feld} VALUES(40, 4, 3, 'abschlussdatum', NULL, 1, 'Abschlussdatum', 'Abschlussdatum', '<br/>', NULL, 0, 7)");
db_query("INSERT INTO {cdb_feld} VALUES(41, 4, 1, 'treffzeit', NULL, 1, 'Zeit des Treffens', 'Treffzeit', '<br/>', NULL, 30, 8)");
db_query("INSERT INTO {cdb_feld} VALUES(42, 4, 1, 'treffpunkt', NULL, 1, 'Ort des Treffens', 'Treffort', '<br/>', NULL, 50, 9)");
db_query("INSERT INTO {cdb_feld} VALUES(43, 4, 1, 'treffname', NULL, 1, 'Treffen bei', 'Treffen bei', '<br/>', NULL, 30, 10)");
db_query("INSERT INTO {cdb_feld} VALUES(44, 4, 1, 'zielgruppe', NULL, 1, 'Zielgruppe', 'Zielgruppe', '<br/>', NULL, 30, 11)");
db_query("INSERT INTO {cdb_feld} VALUES(45, 4, 5, 'notiz', NULL, 1, 'Notiz', 'Notiz', '<br/>', NULL, 200, 12)");
db_query("INSERT INTO {cdb_feld} VALUES(46, 4, 4, 'valid_yn', NULL, 1, '<p>Gruppe ausw&auml;hlbar<br/><small>Bei Verneinung kann die Gruppe nicht mehr zugeordnet und gefiltert werden</small>', 'Ausw&auml;hlbar', '<br/>', 'admingroups', 1, 13)");
db_query("INSERT INTO {cdb_feld} VALUES(47, 4, 4, 'versteckt_yn', NULL, 1, '<p>Versteckte Gruppe<br/><small>Gruppe ist nur f&uuml;r Gruppenadmins & Leiter sichbar</small>', 'Versteckt', '<br/>', 'admingroups', 1, 14)");
db_query("INSERT INTO {cdb_feld} VALUES(48, 4, 4, 'instatistik_yn', NULL, 1, '<p>Zeige in Statistik<br/><small>In der Statistik explizit aufgef&uuml;hrt</small>', 'In Statistik', '<br/>', 'admingroups', 1, 15)");
db_query("INSERT INTO {cdb_feld} VALUES(49, 4, 4, 'treffen_yn', NULL, 1, '<p>W&ouml;chentliche Teilnahme pflegen<br/><small>Erm&ouml;glicht die Pflege der Teilnahme an dieser Gruppe</small>', 'Teilnahme', '<br/>', 'admingroups', 1, 16)");
db_query("INSERT INTO {cdb_feld} VALUES(50, 1, 1, 'optigem_nr', NULL, 1, 'Optigem-Nr', 'Optigem-Nr.', '<br/>', 'admin', NULL, 16)");
  
db_query("CREATE TABLE {cdb_nationalitaet} (
  id int(11) NOT NULL,
  bezeichnung varchar(50) not null,
  PRIMARY KEY (id)
)");
  
  db_query("INSERT INTO {cdb_nationalitaet} VALUES(0, 'unbekannt')");
  
  // Fülle Nationalitäten auf mit schon vorhandenen
 db_query("ALTER TABLE {cdb_nationalitaet} CHANGE  id id INT( 11 ) NOT NULL AUTO_INCREMENT");
 db_query("insert into {cdb_nationalitaet} (bezeichnung) (select nationalitaet from {cdb_gemeindeperson} gp left join {cdb_nationalitaet} n on (gp.nationalitaet=n.bezeichnung) where n.bezeichnung is null and gp.nationalitaet!=''
group by nationalitaet)");
  
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ägypten')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Äquatorialguinea')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Äthiopien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Afghanistan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Albanien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Algerien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Andorra')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Angola')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Antigua und Barbuda')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Argentinien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Armenien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Aserbaidschan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Australien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Bahamas')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Bahrain')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Bangladesch')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Barbados')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Belgien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Belize')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Benin')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Bhutan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Bolivien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Bosnien und Herzegowina')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Botsuana')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Brasilien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Brunei')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Bulgarien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Burkina Faso')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Burundi')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Chile')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('China')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Costa Rica')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Dänemark')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Deutschland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Dominica')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Dominikanische Republik')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Dschibuti')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ecuador')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('El Salvador')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Elfenbeinküste')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Eritrea')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Estland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Fidschi')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Finnland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Frankreich')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Gabun')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Gambia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Georgien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ghana')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Grenada')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Griechenland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Großbritannien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Guatemala')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Guinea')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Guinea-Bissau')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Guyana')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Haiti')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Honduras')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Indien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Indonesien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Irak')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Iran')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Irland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Island')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Israel')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Italien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Jamaika')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Japan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Jemen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Jordanien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kambodscha')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kamerun')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kanada')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kap Verde')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kasachstan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Katar')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kenia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kirgistan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kiribati')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kolumbien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Komoren')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kongo, Republik')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kongo, Demokratische Republik')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kroatien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kuba')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Kuwait')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Laos')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Lesotho')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Lettland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Libanon')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Liberia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Libyen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Liechtenstein')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Litauen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Luxemburg')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Madagaskar')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Malawi')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Malaysia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Malediven')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mali')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Malta')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Marokko')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Marshallinseln')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mauretanien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mauritius')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mazedonien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mexiko')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mikronesien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Moldawien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Monaco')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mongolei')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Montenegro')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Mosambik')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Myanmar')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Namibia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Nauru')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Nepal')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Neuseeland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Nicaragua')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Niederlande')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Niger')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Nigeria')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Niue')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Nordkorea')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Norwegen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Österreich')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Oman')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Pakistan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Palau')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Palästinensische Gebiete')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Panama')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Papua-Neuguinea')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Paraguay')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Peru')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Philippinen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Polen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Portugal')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ruanda')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Rumänien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Russland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Sahara')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Salomonen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Sambia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Samoa')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('San Marino')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('São Tomé und Príncipe')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Saudi-Arabien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Schweden')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Schweiz')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Senegal')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Serbien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Seychellen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Sierra Leone')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Simbabwe')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Singapur')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Slowakei')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Slowenien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Somalia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Spanien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Sri Lanka')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('St. Kitts und Nevis')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('St. Lucia')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('St. Vincent und die Grenadinen')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Sudan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Südafrika')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Südkorea')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Suriname')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Swasiland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Syrien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Tadschikistan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Taiwan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Tansania')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Thailand')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Timor-Leste')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Togo')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Tonga')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Trinidad und Tobago')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Tschad')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Tschechien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Tunesien')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Turkmenistan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Turks- und Caicosinseln')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Tuvalu')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Türkei')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Uganda')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ukraine')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ungarn')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Uruguay')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('USA')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Usbekistan')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Vanuatu')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Vatikanstadt')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Venezuela')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Vereinigte Arabische Emirate')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Vietnam')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Weißrussland')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Zentralafrikanische Republik')");
db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Zypern')");

 
 db_query("ALTER TABLE {cdb_gemeindeperson} ADD nationalitaet_id INT( 11 ) NOT NULL AFTER nationalitaet");
 // Mappe nun die schon gesetzen
db_query("update {cdb_gemeindeperson} gp join {cdb_nationalitaet} n on gp.nationalitaet=n.bezeichnung
set gp.nationalitaet_id=n.id");

  db_query("insert into {cc_config} values ('show_remember_me', '1')");

  db_query("ALTER TABLE {cdb_gruppe} ADD max_teilnehmer INT( 11 ) NULL AFTER zielgruppe");
  db_query("ALTER TABLE {cdb_gruppe} ADD oeffentlich_yn INT( 1 ) NOT NULL DEFAULT 0 AFTER notiz");
  db_query("ALTER TABLE {cdb_gruppe} ADD offen_yn INT( 1 ) NOT NULL DEFAULT 0 AFTER notiz");
  db_query("insert into {cdb_feldtyp} (id, bezeichnung, intern_code) VALUES (6,  'Nummernfeld',  'number')");
  db_query("INSERT INTO {cdb_feld} VALUES (51, 4, 6, 'max_teilnehmer', NULL, 1, 'Maximale Teilnehmer', 'Max. Teilnehmer', '<br/>', null, 11, 10)");
  db_query("INSERT INTO {cdb_feld} VALUES (52, 4, 4, 'oeffentlich_yn', NULL, 1, '<p>&Ouml;ffentliche Gruppe<br/><small>Die Info-Daten der Gruppe kann ohne Autorisierung eingesehen werden', '&Ouml;ffentlich', '<br/>', 'admingroups', 1, 17)");
  db_query("INSERT INTO {cdb_feld} VALUES (53, 4, 4, 'offen_yn', NULL, 1, '<p>Offene Gruppe<br/><small>Man kann eine Teilnehmeranfrage an diese Gruppe stellen', '&Offen', '<br/>', 'admingroups', 1, 18)");
  db_query("ALTER TABLE {cdb_feld} CHANGE id id INT( 11 ) NOT NULL AUTO_INCREMENT");
  db_query("CREATE TABLE {cdb_gruppenteilnehmer_email} (
  id int(11) NOT NULL AUTO_INCREMENT,
  gruppe_id int(11) NOT NULL,
  status_no int(11) NOT NULL,
  aktiv_yn int(1) NOT NULL,
  sender_pid int(11) NOT NULL,
  email_betreff varchar(255) NOT NULL,
  email_inhalt blob NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY gruppe_id (gruppe_id,status_no)) ");
  db_query("ALTER TABLE  {cr_resource} CHANGE admin_person_ids admin_person_ids VARCHAR( 50 ) NOT NULL DEFAULT  '-1'");
  
}

function updateDB_221() {
  db_query("update {cdb_feld} set feldtyp_id=2, aktiv_yn=1 where db_spalte='nationalitaet_id'");
  
  db_query("ALTER TABLE {cc_wikicategory} CONVERT TO CHARACTER SET utf8");
  db_query("ALTER TABLE {cc_wiki} CONVERT TO CHARACTER SET utf8");
  db_query("ALTER TABLE {cdb_nationalitaet} CONVERT TO CHARACTER SET utf8");
  
  db_query("update {cdb_nationalitaet} set bezeichnung='&Auml;gypten' where bezeichnung='?gypten'");
  db_query("update {cdb_nationalitaet} set bezeichnung='&Auml;quatorialguinea' where bezeichnung='?quatorialguinea'");
  db_query("update {cdb_nationalitaet} set bezeichnung='&Auml;thiopien' where bezeichnung='?thiopien'");
  db_query("update {cdb_nationalitaet} set bezeichnung='&Ouml;sterreich' where bezeichnung='?sterreich'");
  db_query("update {cdb_nationalitaet} set bezeichnung='T&uuml;rkei' where bezeichnung='T?rkei'");
  db_query("update {cdb_nationalitaet} set bezeichnung='S&uuml;dafrika' where bezeichnung='S?dafrika'");
  db_query("update {cdb_nationalitaet} set bezeichnung='S&uuml;dkorea' where bezeichnung='S?dkorea'");
  db_query("update {cdb_nationalitaet} set bezeichnung='D&auml;nemark' where bezeichnung='D?nemark'");
  
  // Fix error von 220
  db_query("ALTER TABLE {cdb_nationalitaet} CHANGE  id id INT( 11 ) NOT NULL");
  db_query("ALTER TABLE {cdb_gemeindeperson} CHANGE nationalitaet_id  nationalitaet_id INT( 11 ) NOT NULL DEFAULT '0'");

  // Suche nun nach unbekannt mit id 1, und setze es auf 0
  $res=db_query("select count(*) c from {cdb_nationalitaet} where id=1 and upper(bezeichnung) like 'UNBEKANNT'")->fetch();
  if ($res->c>0) {
    db_query("update {cdb_nationalitaet} set id=0 where id=1 and upper(bezeichnung) like 'UNBEKANNT'");
    db_query("update {cdb_gemeindeperson} set nationalitaet_id=0 where nationalitaet_id=1");
  }
  $res=db_query("select count(*) c from {cdb_nationalitaet} where id=0 and upper(bezeichnung) like 'UNBEKANNT'")->fetch();
  if ($res->c==0) {
    db_query("INSERT INTO  {cdb_nationalitaet} (id, bezeichnung) VALUES ('0', 'unbekannt')");
  }
  db_query("ALTER TABLE {cdb_nationalitaet} ADD UNIQUE (bezeichnung)");
  
}

function updateDB_223() {
  db_query("ALTER TABLE {cc_session} CHANGE session session VARCHAR( 100 ) NOT NULL");
  db_query("ALTER TABLE {cc_session} CHANGE hostname hostname VARCHAR( 100 ) NOT NULL");
  db_query("ALTER TABLE {cc_session} ADD PRIMARY KEY (person_id , session , hostname)") ;
  
  db_query("CREATE TABLE {cdb_gruppenteilnehmerstatus} (
  id int(11) NOT NULL,
  intern_code int(1) NOT NULL,
  bezeichnung varchar(50) NOT NULL,
  kuerzel varchar(10) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY intern_code (intern_code)
) DEFAULT CHARSET=utf8");

  db_query("INSERT INTO {cdb_gruppenteilnehmerstatus} VALUES(1, 0, 'Teilnehmer', '')");
  db_query("INSERT INTO {cdb_gruppenteilnehmerstatus} VALUES(2, 1, 'Leiter', 'L')");
  db_query("INSERT INTO {cdb_gruppenteilnehmerstatus} VALUES(3, 2, 'Co-Leiter', 'CoL')");
  db_query("INSERT INTO {cdb_gruppenteilnehmerstatus} VALUES(4, 3, 'Supervisor', 'S')");
  db_query("INSERT INTO {cdb_gruppenteilnehmerstatus} VALUES(5, 4, 'Mitarbeiter', 'M')");
  db_query("INSERT INTO {cdb_gruppenteilnehmerstatus} VALUES(6, -2, 'Teilnahme beantragt', '')");
  db_query("INSERT INTO {cdb_gruppenteilnehmerstatus} VALUES(7, -1, 'zu l&ouml;schen', '');");

}

function updateDB_224() {
  db_query("insert into {cc_config} (name, value) values ('site_offline','0')");
  db_query("update {cdb_feld} set db_spalte='ueberwiesenvon' where db_spalte='ueberweisen von'");
  db_query("update {cdb_feld} set db_spalte='ueberwiesennach' where db_spalte='ueberwiesen nach'");

  db_query("CREATE TABLE {cdb_person_gruppentyp} (
    person_id int(11) NOT NULL,
    gruppentyp_id int(11) NOT NULL,
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
    PRIMARY KEY (person_id,gruppentyp_id)) DEFAULT CHARSET=utf8;");
  db_query("CREATE TABLE {cdb_person_distrikt} (
    person_id int(11) NOT NULL,
    distrikt_id int(11) NOT NULL,
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
    PRIMARY KEY (person_id,distrikt_id)) DEFAULT CHARSET=utf8;");  
}

function updateDB_225() { 
  db_query("ALTER TABLE {cc_file} CHANGE domain_id domain_id VARCHAR( 30 ) NOT NULL");
  db_query("ALTER TABLE {cc_file} ADD bezeichnung VARCHAR( 50 ) NOT NULL AFTER domain_id");  
  db_query("update {cc_file} set bezeichnung=filename");
  db_query("ALTER TABLE {cdb_gemeindeperson} CHANGE imageurl imageurl VARCHAR( 50 )"); 
}


function updateDB_226() {
  db_query("ALTER TABLE {cdb_gruppe} ADD fu_nachfolge_gruppenteilnehmerstatus_id INT( 11 ) NULL AFTER fu_nachfolge_objekt_id");
  db_query("ALTER TABLE {cdb_gemeindeperson_gruppe} ADD  followup_erfolglos_zurueck_gruppen_id INT( 11 ) NULL AFTER followup_add_diff_days");
  db_query("UPDATE {cdb_feld} set sortkey=2 where db_spalte='max_teilnehmer' and feldkategorie_id=4");
  db_query("INSERT INTO {cdb_feld} VALUES(-1, 4, 2, 'fu_nachfolge_gruppenteilnehmerstatus_id', 'groupMemberTypes', 1, 'Followup-Nachfolger-Teilnehmerstatus', 'Followup-Nachfolger-Teilnehmerstatus', '<br/>', 'admingroups', 11, 5)");
  
  db_query("update {cdb_feld} set langtext='<p>Versteckte Gruppe<br/><small>Gruppe ist nur f&uuml;r Gruppenadmins & Leiter sichtbar</small>' 
              where langtext='<p>Versteckte Gruppe<br/><small>Gruppe ist nur f&uuml;r Gruppenadmins & Leiter sichbar</small>'");
  
}

function updateDB_227() {
  db_query("CREATE TABLE {cc_cal_add} (
    id int(11) NOT NULL AUTO_INCREMENT,
    cal_id int(11) NOT NULL,
    add_date datetime NOT NULL,
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
    PRIMARY KEY (id)
  ) DEFAULT CHARSET=utf8");
  
  db_query("ALTER TABLE {cc_cal_except} CHANGE except_date except_date_start DATETIME NOT NULL");
  db_query("ALTER TABLE  {cc_cal_except} ADD except_date_end DATETIME NOT NULL AFTER except_date_start");
  db_query("update {cc_cal_except} set except_date_end=except_date_start");
  db_query("ALTER TABLE {cc_cal} ADD repeat_option_id INT( 11 ) NULL AFTER repeat_until");  

  db_query("CREATE TABLE {cr_addition} (
    id int(11) NOT NULL AUTO_INCREMENT,
    booking_id int(11) NOT NULL,
    add_date datetime NOT NULL,
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
    PRIMARY KEY (id)
  ) DEFAULT CHARSET=utf8");
  db_query("ALTER TABLE {cr_exception} CHANGE except except_date_start DATETIME NOT NULL");
  db_query("ALTER TABLE  {cr_exception} ADD except_date_end DATETIME NOT NULL AFTER except_date_start");
  db_query("ALTER TABLE  {cr_exception} ADD modified_date DATETIME NOT NULL AFTER userid");
  db_query("ALTER TABLE {cr_exception} CHANGE person_id  modified_pid INT( 11 ) NOT NULL DEFAULT '-1'");
  db_query("update {cr_exception} set except_date_end=except_date_start");
  db_query("ALTER TABLE {cr_booking} ADD repeat_option_id INT( 11 ) NULL AFTER repeat_until");  
  
  db_query("CREATE TABLE {cc_repeat} (
  id int(11) NOT NULL, bezeichnung varchar(30) NOT NULL, sortkey int(11) NOT NULL, PRIMARY KEY (id)
  ) DEFAULT CHARSET=utf8");
  db_query("INSERT INTO {cc_repeat} VALUES(0, 'Keine Wiederholung', 0)");
  db_query("INSERT INTO {cc_repeat} VALUES(1, 'T&auml;glich', 1)");
  db_query("INSERT INTO {cc_repeat} VALUES(7, 'W&ouml;chentlich', 2)");
  db_query("INSERT INTO {cc_repeat} VALUES(31, 'Monatlich nach Datum', 3)");
  db_query("INSERT INTO {cc_repeat} VALUES(32, 'Monatlich nach Wochentag', 4)");
  db_query("INSERT INTO {cc_repeat} VALUES(365, 'J&auml;hrlich', 5)");
  db_query("INSERT INTO {cc_repeat} VALUES(999, 'Manuell', 6)");
  db_query("DROP TABLE  {cr_repeat}");
  
  db_query("ALTER TABLE {cs_eventservice} ADD INDEX ( event_id )");
  
  db_query("ALTER TABLE {cc_wiki} CHANGE text text MEDIUMBLOB NOT NULL");
  
}

function updateDB_228() {
  db_query("ALTER TABLE {cc_wiki} ADD auf_startseite_yn INT( 1 ) NOT NULL DEFAULT '0' AFTER text");
  db_query("ALTER TABLE {cc_cal_add} ADD with_repeat_yn INT( 1 ) NOT NULL DEFAULT '1' AFTER add_date");
  db_query("ALTER TABLE {cr_addition} ADD with_repeat_yn INT( 1 ) NOT NULL DEFAULT '1' AFTER add_date");
  db_query("ALTER TABLE {cdb_person} ADD spitzname VARCHAR( 30 ) NOT NULL AFTER vorname");
  db_query("update {cdb_feld} set sortkey=sortkey+1 where feldkategorie_id=1 and sortkey>=3");
  db_query("INSERT INTO {cdb_feld} VALUES(0, 1, 1, 'spitzname', NULL, 1, 'Spitzname', '', '(%) ', NULL, 30, 3)");
  db_query("ALTER TABLE {cdb_beziehungstyp} ADD sortkey INT( 11 ) NOT NULL");
  db_query("ALTER TABLE {cdb_person} ADD loginerrorcount INT( 11 ) NOT NULL AFTER lastlogin");
  
  db_query("insert into {cc_wiki} (doc_id, version_no, wikicategory_id, text) values ('Sicherheitsbestimmungen', 1, 0,
     '<p><strong>Verpflichtung auf das Datengeheimnis gem&auml;&szlig; &sect; 5 Bundesdatenschutzgesetz (BDSG), auf das Fernmeldegeheimnis gem&auml;&szlig; &sect; 88 Telekommunikationsgesetz (TKG) und auf Wahrung von Gesch&auml;ftsgeheimnissen</strong><br />
<br />
Hallo&nbsp;[Vorname]!<br />
Die pers&ouml;nlichen Daten unserer Mitarbeiter und Mitglieder wollen wir sch&uuml;tzen. Darum bitten wir Dich, Dich auf das Datengeheimnis wie folgt zu verpflichten:<br />
<br />
<strong>1. Verpflichtung auf das Datengeheimnis nach &sect; 5 BDSG</strong><br />
Aufgrund von &sect; 5 BDSG ist mir untersagt, personenbezogene Daten, die mir dienstlich bekannt werden, unbefugt zu erheben, zu verarbeiten oder zu nutzen. Dies gilt sowohl f&uuml;r die dienstliche T&auml;tigkeit innerhalb wie auch au&szlig;erhalb (z.B. bei Kunden und Interessenten) des Unternehmens/der Beh&ouml;rde.<br />
Die Pflicht zur Wahrung des Datengeheimnisses bleibt auch im Falle einer Versetzung oder nach Beendigung des Arbeits-/Dienstverh&auml;ltnisses bestehen.<br />
<br />
<strong>2. Verpflichtung auf das Fernmeldegeheimnis</strong><br />
Aufgrund von &sect; 88 TKG bin ich zur Wahrung des Fernmeldegeheimnisses verpflichtet, so- weit ich im Rahmen meiner T&auml;tigkeit bei der Erbringung gesch&auml;ftsm&auml;&szlig;iger Telekommunikationsdienste mitwirke.<br />
<br />
<strong>3. Verpflichtung auf Wahrung von Gesch&auml;ftsgeheimnissen</strong><br />
&Uuml;ber Angelegenheiten des Unternehmens, die beispielsweise Einzelheiten ihrer Organisation und ihre Einrichtung betreffen, sowie &uuml;ber Gesch&auml;ftsvorg&auml;nge und Zahlen des internen Rechnungswesens, ist auch nach Beendigung des Arbeitsverh&auml;ltnisses von mir Verschwiegenheit zu wahren, sofern sie nicht allgemein &ouml;ffentlich bekannt geworden sind. Hierunter fallen&nbsp;auch Vorg&auml;nge von Drittunternehmen, mit denen ich dienstlich befasst bin. Auf die gesetzli- chen Bestimmungen &uuml;ber unlauteren Wettbewerb wurde ich besonders hingewiesen.<br />
Alle dienstliche T&auml;tigkeiten betreffenden Aufzeichnungen, Abschriften, Gesch&auml;ftsunterlagen, Ablichtungen dienstlicher oder gesch&auml;ftlicher Vorg&auml;nge, die mir &uuml;berlassen oder von mir angefertigt werden, sind vor Einsichtnahme Unbefugter zu sch&uuml;tzen.<br />
<br />
Von diesen Verpflichtungen habe ich Kenntnis genommen. Ich bin mir bewusst, dass ich mich bei Verletzungen des Datengeheimnisses, des Fernmeldegeheimnisses oder von Gesch&auml;ftsgeheimnissen strafbar machen kann, insbesondere nach &sect;&sect; 44, 43 Abs. 2 BDSG, &sect; 206 Strafgesetzbuch (StGB) und nach &sect; 17 Gesetz gegen den unlauteren Wettbewerb (UWG).</p>')");
  db_query("insert into {cc_config} (name, value) values ('accept_datasecurity','0')");
  db_query("ALTER TABLE {cdb_person} ADD acceptedsecurity DATETIME NULL AFTER loginerrorcount");  
}


function updateDB_230() {
  db_query("INSERT INTO {cc_auth} (id ,auth ,modulename ,datenfeld,bezeichnung)
    VALUES ( '601',  'view',  'churchcheckin', NULL ,  'Darf die Checkin-Anwendung nutzen')");

  db_query("ALTER TABLE  {cdb_gruppentreffen_gemeindeperson} 
                   ADD modified_date DATETIME NOT NULL AFTER treffen_yn");
  db_query("ALTER TABLE  {cdb_gruppentreffen_gemeindeperson} 
                   ADD modified_pid int(11) NOT NULL AFTER modified_date");

  db_query("ALTER TABLE  {cdb_gruppentreffen_gemeindeperson} ADD zufallscode VARCHAR( 10 ) NOT NULL AFTER treffen_yn");
  
  db_query("ALTER TABLE  {cdb_gruppentreffen} 
                   ADD modified_date DATETIME NOT NULL AFTER ausgefallen_yn");
  db_query("ALTER TABLE  {cdb_gruppentreffen} 
                   ADD modified_pid int(11) NOT NULL AFTER modified_date");
  
  db_query("insert into {cc_config} values ('churchcheckin_name', 'Checkin')");
  
  db_query("CREATE TABLE {cc_printer} (
  id int(11) NOT NULL AUTO_INCREMENT,
  bezeichnung varchar(50) NOT NULL,
  ort varchar(50) NOT NULL,
  active_yn int(1) not null default '0',
  modified_date datetime NOT NULL,
  modified_pid int(11) NOT NULL,  
  PRIMARY KEY (id),
  UNIQUE KEY bezeichnung (bezeichnung,ort)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ");
  
  db_query("CREATE TABLE {cc_printer_queue} (
   id int(11) NOT NULL AUTO_INCREMENT,
    printer_id int(11) NOT NULL,
    data blob NOT NULL,
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8");
    
  
  db_query("ALTER TABLE {cdb_gruppe} ADD mail_an_leiter_yn INT( 1 ) NOT NULL DEFAULT  '1' AFTER instatistik_yn");
  db_query("INSERT INTO {cdb_feld} VALUES (null, 4, 4, 'mail_an_leiter_yn', NULL, 1, '<p>Leiter informieren<br/><small>(Co-)Leiter und Supverisor bekommen E-Mails bei &Auml;nderungen in der Gruppe', '&Leiter informieren', '<br/>', 'admingroups', 1, 19)");
  
  
  db_query("INSERT INTO  {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
  VALUES ('115',  'view group',  'churchdb',  'cdb_gruppe',  'View-Rechte auf andere Gruppen')");

  db_query("CREATE TABLE {cdb_gruppe_mailchimp} (
  gruppe_id int(11) NOT NULL,
  modified_pid int(11) NOT NULL,
  modified_date datetime NOT NULL,
  mailchimp_list_id varchar(30) NOT NULL,
  optin_yn int(1) NOT NULL DEFAULT '1',
  goodbye_yn int(1) NOT NULL DEFAULT '0',
  notifyunsubscribe_yn int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (gruppe_id,mailchimp_list_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");


  db_query("CREATE TABLE {cdb_gruppe_mailchimp_person} (
  gruppe_id int(11) NOT NULL,
  mailchimp_list_id varchar(20) NOT NULL,
  person_id int(11) NOT NULL,
  email varchar(50) NOT NULL,
  PRIMARY KEY (gruppe_id,mailchimp_list_id,person_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

  db_query("insert into {cc_config} values ('churchdb_mailchimp_apikey', '')");
  db_query("ALTER TABLE {cdb_gruppentreffen} ADD anzahl_gaeste INT( 11 ) NULL AFTER ausgefallen_yn");
  db_query("ALTER TABLE {cdb_gruppentreffen} ADD kommentar TEXT NULL AFTER anzahl_gaeste");
  
  
  db_query("ALTER TABLE {cdb_person} ADD archiv_yn INT( 0 ) NOT NULL DEFAULT  '0' AFTER cmsuserid");
  db_query("INSERT INTO  {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
  VALUES ('116',  'view archive',  'churchdb',  null,  'View-Rechte auf das Personen-Archiv')");  
}


function updateDB_231() {
  db_query("insert into {cc_config} values ('churchcheckin_inmenu', '1')");
  db_query("insert into {cc_config} values ('churchcheckin_sortcode', '1')");
  db_query("insert into {cc_config} values ('churchcheckin_startbutton', '1')");

  db_query("insert into {cc_config} values ('churchdb_inmenu', '1')");
  db_query("insert into {cc_config} values ('churchdb_sortcode', '2')");
  db_query("insert into {cc_config} values ('churchdb_startbutton', '1')");

  db_query("insert into {cc_config} values ('churchresource_inmenu', '1')");
  db_query("insert into {cc_config} values ('churchresource_sortcode', '3')");
  db_query("insert into {cc_config} values ('churchresource_startbutton', '1')");
  
  db_query("insert into {cc_config} values ('churchservice_inmenu', '1')");
  db_query("insert into {cc_config} values ('churchservice_sortcode', '4')");
  db_query("insert into {cc_config} values ('churchservice_startbutton', '1')");

  db_query("insert into {cc_config} values ('churchwiki_inmenu', '1')");
  db_query("insert into {cc_config} values ('churchwiki_sortcode', '5')");
  db_query("insert into {cc_config} values ('churchwiki_startbutton', '1')");

  db_query("insert into {cc_config} values ('churchcal_inmenu', '1')");
  db_query("insert into {cc_config} values ('churchcal_sortcode', '6')");
  db_query("insert into {cc_config} values ('churchcal_startbutton', '1')");
  
  db_query("insert into {cc_config} values ('churchdb_smspromote_apikey', '')");
  db_query("INSERT INTO  {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
  VALUES ('117',  'send sms',  'churchdb',  null,  'Darf die SMS-Schnittstelle verwenden')");  
}

function updateDB_232() {
  db_query("ALTER TABLE {cs_service} ADD notiz VARCHAR( 50 ) NOT NULL AFTER bezeichnung");
  db_query("ALTER TABLE {cs_service} CHANGE bezeichnung  bezeichnung VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
  db_query("ALTER TABLE {cc_cal} ADD domain_type VARCHAR( 30 ) NULL AFTER id");
  db_query("ALTER TABLE {cc_cal} ADD domain_id INT( 11 ) NULL AFTER domain_type");  
  db_query("insert into {cc_config} values ('churchservice_reminderhours', '24')");
  db_query("INSERT INTO {cdb_feld} VALUES(null, 4, 2, 'gruppentyp_id', 'groupTypes', 1, 'Gruppentyp', 'Gruppentyp', '<br/>', 'admingroups', 11, 2)");
}


function updateDB_234() {

  db_query("ALTER TABLE {cc_cal} DROP domain_type");
  db_query("ALTER TABLE {cc_cal} DROP domain_id");
    
  db_query("ALTER TABLE {cs_event} ADD cc_cal_id INT( 11 ) NOT NULL AFTER id");
  
  // Erstelle nun Vater-Calendereinträge in cc_cal
  db_query("insert into {cc_cal} (select null, bezeichnung, '', '', 0, datum, DATE_ADD(datum, INTERVAL 1 HOUR), category_id, 0, null, null, null, current_date(), -1 from {cs_event})");
  // Stelle nun Verknüpfung zum Vater cc_cal her.
  db_query("update {cs_event} e 
    inner join (select * from {cc_cal}) as cal
    on cal.category_id=e.category_id and cal.bezeichnung=e.bezeichnung and cal.startdate=e.datum and e.cc_cal_id=0
    set e.cc_cal_id=cal.id");

  db_query("ALTER TABLE {cs_event} CHANGE category_id old_category_id INT( 11 ) NOT NULL DEFAULT  '0'");
  db_query("ALTER TABLE {cs_event} CHANGE bezeichnung old_bezeichnung VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
  db_query("ALTER TABLE {cs_event} CHANGE datum startdate DATETIME NOT NULL ");
  
  // CALENDAR
  // Erstmal die Bezeichnungen rüberholen
  db_query("CREATE TABLE {cc_calcategory} (
    id int(11) NOT NULL AUTO_INCREMENT,
    bezeichnung varchar(100) NOT NULL,
    sortkey int(11) not null default 0,
    color varchar(20) null,
    oeffentlich_yn int(1) not null default 0,
    privat_yn int(1) not null default 0,
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY bezeichnung_per_user (bezeichnung,modified_pid)
  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8");
  db_query("insert into {cc_calcategory} (select null, bezeichnung, sortkey, color, 1, 0, current_date(), -1 from {cs_category})");
  db_query("ALTER TABLE {cc_cal} CHANGE category_id  old_category_id INT( 11 ) NOT NULL DEFAULT 0");
  db_query("ALTER TABLE {cc_cal} ADD category_id INT( 11 ) NOT NULL AFTER old_category_id");
  $db=db_query("select cal.id cal_id, cs.id cs_id from {cc_calcategory} cal, {cs_category} cs where cal.bezeichnung=cs.bezeichnung");
  // Nun müssen wir die Ids anpassen, da hier nun AUTO_INCREMENT verwendet wird
  if ($db!=null)
    foreach ($db as $ids) {
      db_query("update {cc_cal} set category_id=:cal_id where old_category_id=:cs_id", 
        array(":cal_id"=>$ids->cal_id, ":cs_id"=>$ids->cs_id));      
    } 


  // Admin darf soll auch nicht immer alles sehen, z.B. persönliche Kalender und view auf alle Gruppen (hat er sowieso)
  db_query("ALTER TABLE {cc_auth} ADD admindarfsehen_yn INT( 1 ) NOT NULL DEFAULT 1");
  db_query("UPDATE {cc_auth} SET admindarfsehen_yn =  0  WHERE id=115");
  
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (403, 'view category', 'churchcal', 'cc_calcategory', 'Darf bestimmte Kalender einsehen',1)");
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (404, 'edit category', 'churchcal', 'cc_calcategory', 'Darf bestimmte Kalender anpassen',1)");
  
  // COMMENTS
  db_query("CREATE TABLE {cc_comment} (
    id int(11) NOT NULL AUTO_INCREMENT,
    domain_type varchar(30) NOT NULL,
    domain_id int(11) NOT NULL,
    text text NOT NULL,
    modified_date datetime NOT NULL,
    modified_pid int(11) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY domain_type (domain_type,domain_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ");
  
}


function updateDB_235() {
  db_query("CREATE TABLE {cc_mail_queue} (
  id int(11) NOT NULL AUTO_INCREMENT,
  receiver varchar(255) NOT NULL,
  sender varchar(255) NOT NULL,
  subject varchar(255) NOT NULL,
  body blob NOT NULL,
  htmlmail_yn int(1) NOT NULL,
  priority int(1) NOT NULL DEFAULT '2',
  modified_date datetime NOT NULL,
  modified_pid int(11) NOT NULL,
  send_date datetime DEFAULT NULL,
  error int(11) DEFAULT '0',
  reading_count int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ");
  
  db_query("ALTER TABLE {cdb_gruppe} CHANGE bezeichnung bezeichnung VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");  
  db_query("ALTER TABLE {cdb_log} CHANGE txt txt VARCHAR( 2048 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");

  // Der öffentliche User darf den Kalender sehen
  db_query("INSERT INTO {cc_domain_auth} (
    domain_type, domain_id, auth_id, daten_id)
    VALUES ('person',  '-1',  '401', NULL);");  
  
  db_query("update {cc_auth} set bezeichnung='Admin-Einstellungen anpassen' where id=1");

  // Autorisiere View der öffentlichen Kalendar über den Status. 
  db_query("insert into {cc_domain_auth} SELECT 'status', s.id, 403, cat.id FROM {cc_calcategory} cat, {cdb_status} s where cat.oeffentlich_yn=1");
  
}

function updateDB_237() {
  db_query("update {cc_calcategory} set bezeichnung = replace(bezeichnung, '`', '') 
    WHERE  bezeichnung LIKE  '%`s Kalender'");
  db_query("ALTER TABLE {cc_cal} ADD link VARCHAR( 255 ) NOT NULL AFTER notizen");
  db_query("ALTER TABLE {cc_calcategory} ADD randomurl VARCHAR( 100 ) NOT NULL AFTER privat_yn");
  db_query("update {cc_calcategory} set randomurl=MD5(RAND()) where randomurl=''");
  db_query("TRUNCATE TABLE {cs_eventtemplate_service}");
  db_query("ALTER TABLE {cs_eventtemplate_service} ADD PRIMARY KEY ( eventtemplate_id ,  service_id )");
  db_query("ALTER TABLE {cr_booking} ADD cc_cal_id INT( 11 ) NULL");
  db_query("ALTER TABLE {cs_absent_reason} ADD color VARCHAR( 20 ) NOT NULL AFTER bezeichnung");
  db_query("ALTER TABLE {cs_eventtemplate_service} ADD count INT( 2 ) NOT NULL DEFAULT 1");  
}

function updateDB_238() {
  db_query("ALTER TABLE {cs_eventtemplate} ADD dauer_sec INT( 11 ) NOT NULL DEFAULT  '5400' AFTER minute");
  db_query("ALTER TABLE {cdb_feld} ADD inneuerstellen_yn INT( 1 ) NOT NULL DEFAULT '0' AFTER aktiv_yn");
  db_query("update {cdb_feld} set inneuerstellen_yn=1 where db_spalte in ('strasse', 'plz', 'ort', 'email')");

  db_query("UPDATE {cc_auth} SET auth = 'church category',
        bezeichnung = 'Kategorien von Gemeindekalendern anpassen' WHERE cc_auth.id =402");
  db_query("INSERT {cc_auth} (id, auth, modulename, bezeichnung) values(405, 'group category', 'churchcal',
        'Kategorien von Gruppenkalendern anpassen')");
  db_query("INSERT {cc_auth} (id, auth, modulename, bezeichnung) values(406, 'personal category', 'churchcal',
        'Kategorien von persoenlichen Kalendern anpassen')");
  db_query("ALTER TABLE {cc_auth} ADD UNIQUE (auth, modulename)");
  db_query("UPDATE {cc_auth} SET admindarfsehen_yn = 0 WHERE  id =403");
  db_query("UPDATE {cc_auth} SET admindarfsehen_yn = 0 WHERE  id =404");
  
  db_query("insert into {cc_domain_auth} select 'person', modified_pid, 404, id from {cc_calcategory} where id>0 and modified_pid>0");
  db_query("ALTER TABLE {cr_booking} ADD INDEX (cc_cal_id)");
  
}

function updateDB_241() {
  // Give some more facts permission for CS facts
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (309, 'edit template', 'churchservice', null, 'Darf Event-Templates editieren',1)");
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (321, 'view facts', 'churchservice', null, 'Darf Fakten sehen',1)");
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (322, 'export facts', 'churchservice', null, 'Darf Fakten exportieren',1)");
  
  // Give permission to push/pull people from/to archive in CDB 
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (118, 'push/pull archive', 'churchdb', null, 'Darf Personen ins Archiv verschieben und zurueckholen',1)");

  // Resolves problem with some wiki pages with long page names
  db_query("ALTER TABLE {cc_file} CHANGE filename filename VARCHAR( 100 ) NOT NULL");
  db_query("ALTER TABLE {cc_file} CHANGE domain_id domain_id VARCHAR( 100 ) NOT NULL");

  // Add authorization for agenda in CS module
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (331, 'view agenda', 'churchservice', 'cc_calcategory', 'Darf Ablaufplaene sehen',1)");
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (332, 'edit agenda', 'churchservice', 'cc_calcategory', 'Darf Ablaufplaene editieren',1)");
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (333, 'edit agenda templates', 'churchservice', 'cc_calcategory', 'Darf Ablaufplan-Vorlagen editieren',1)");

  // Add tables for agenda in CS module
  db_query("CREATE TABLE {cs_agenda} (
  id int(11) NOT NULL AUTO_INCREMENT,
  calcategory_id int(11) NOT NULL,
  bezeichnung varchar(100) NOT NULL,
  template_yn int(1) NOT NULL DEFAULT '0',
  series varchar(100) DEFAULT NULL,
  modified_date datetime NOT NULL,
  modified_pid int(11) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
  
  db_query("CREATE TABLE {cs_item} (
  id int(11) NOT NULL AUTO_INCREMENT,
  agenda_id int(11) NOT NULL,
  bezeichnung varchar(100) NOT NULL,
  header_yn int(1) NOT NULL DEFAULT '0',
  responsible varchar(100) NOT NULL,
  arrangement_id int(11) DEFAULT NULL,
  note varchar(255) NOT NULL,
  sortkey int(11) NOT NULL,
  duration int(11) NOT NULL,
  preservice_yn int(1) NOT NULL DEFAULT '0',
  modified_date datetime NOT NULL,
  modified_pid int(11) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
  
  db_query("CREATE TABLE {cs_event_item} (
  event_id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  PRIMARY KEY (event_id,item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

  db_query("CREATE TABLE {cs_item_servicegroup} (
  item_id int(11) NOT NULL,
  servicegroup_id int(11) NOT NULL,
  note varchar(255) NOT NULL,
  PRIMARY KEY (item_id,servicegroup_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  
}

function updateDB_242() {
  // Add some calender permissions
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (407, 'create personal category', 'churchcal', null, 'Darf persoenliche Kalender erstellen',1)");
  db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (408, 'create group category', 'churchcal', null, 'Darf Gruppenkalender erstellen',1)");
  db_query("UPDATE {cc_auth} SET auth = 'admin church category',
      bezeichnung = 'Gemeindekalender administrieren' WHERE cc_auth.id =402");
  db_query("UPDATE {cc_auth} SET auth = 'admin group category',
      bezeichnung = 'Gruppenkalender administrieren' WHERE cc_auth.id =405");
  db_query("UPDATE {cc_auth} SET auth = 'admin personal category',
      bezeichnung = 'Persoenliche Kalender administrieren' WHERE cc_auth.id =406");

  // Add Timezone support
  db_query("INSERT INTO  {cc_config} (name, value) VALUES ('timezone', 'Europe/Berlin')");
  // Drop old userid-col
  db_query("ALTER TABLE {cdb_log} DROP userid");
}


/*  db_query("DROP TABLE {cdb_newsletter}");
  db_query("CREATE TABLE  drupal7_intern.cdb_newsletter (
id INT( 11 ) NOT NULL AUTO_INCREMENT ,
bezeichnung VARCHAR( 50 ) NOT NULL ,
filter BLOB NOT NULL ,
PRIMARY KEY (  id )
) ENGINE = INNODB;");  
  db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung, datenfeld) values (131, 'edit newsletter', 'churchdb', 'Newsletter-Abos editieren und Newsletter schreiben', 'cdb_newsletter')");
  */

?>