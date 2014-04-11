<?php

function set_version($db_version) {
  db_query("update {cc_config} set value='$db_version' where name='version'");
  set_time_limit(300);
}

function run_db_updates($db_version) {
  global $config, $base_url, $user;
  set_time_limit(300);

  switch($db_version) {
    case 'nodb':
      if (isset($_GET["installdb"])) {
        db_query("CREATE TABLE {cc_usermails} (
          person_id int(11) NOT NULL,
          mailtype varchar(255) NOT NULL,
          domain_id int(11) NOT NULL,
          letzte_mail datetime NOT NULL,
          PRIMARY KEY (person_id,mailtype,domain_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cc_usersettings} (
          person_id int(11) NOT NULL,
          modulename varchar(50) NOT NULL,
          attrib varchar(100) NOT NULL,
          value varchar(8192) DEFAULT NULL,
          PRIMARY KEY (person_id,modulename,attrib)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cc_usersettings} VALUES
           (1, 'churchcal', 'filterMeineKalender', '[104]'),
           (1, 'churchcal', 'viewName', 'month'),
           (1, 'churchdb', 'churchdbInitView', 'PersonView'),
           (1, 'churchdb', 'selectedGroupType', '1'),
           (1, 'churchservice', 'lastVisited', '2013-08-30 15:53'),
           (1, 'churchservice', 'remindMe', '1'),
           (736, 'churchcal', 'viewName', 'month'),
           (736, 'churchservice', 'lastVisited', '2013-04-18 8:27'),
           (736, 'churchservice', 'remindMe', '1')");
        db_query("CREATE TABLE {cdb_bereich} (
          id int(11) NOT NULL,
          bezeichnung varchar(20) NOT NULL,
          kuerzel varchar(10) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_bereich} VALUES
           (1, 'Gemeindeliste', 'G'),
           (2, 'Jugendarbeit', 'J')");
        db_query("CREATE TABLE {cdb_bereich_person} (
          bereich_id int(11) NOT NULL,
          person_id int(11) NOT NULL,
          PRIMARY KEY (bereich_id,person_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_bereich_person} VALUES
           (1, 1),
           (1, 2),
           (2, 1)");
        db_query("CREATE TABLE {cdb_beziehung} (
          id int(11) NOT NULL AUTO_INCREMENT,
          vater_id int(11) NOT NULL,
          kind_id int(11) NOT NULL,
          beziehungstyp_id int(11) NOT NULL,
          datum datetime NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_beziehungstyp} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bez_vater varchar(20) NOT NULL,
          bez_kind varchar(20) NOT NULL,
          bezeichnung varchar(30) NOT NULL,
          export_aggregation_yn int(11) NOT NULL,
          export_title varchar(20) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_beziehungstyp} VALUES
           (1, 'Elternteil', 'Kind', 'Elternteil/Kind', 0, ''),
           (2, 'Ehepartner', 'Ehepartner', 'Ehepartner', 1, 'Ehepaar')");
        db_query("CREATE TABLE {cdb_comment} (
          id int(11) NOT NULL AUTO_INCREMENT,
          relation_id int(11) NOT NULL,
          relation_name varchar(20) NOT NULL,
          text text NOT NULL,
          userid varchar(20) NOT NULL,
          datum datetime NOT NULL,
          comment_viewer_id int(11) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_comment_viewer} (
          id int(11) NOT NULL,
          bezeichnung varchar(30) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_comment_viewer} VALUES
           (0, 'Alle'),
           (1, 'Distriktleiter'),
           (2, 'Vorstand')");
        db_query("CREATE TABLE {cdb_distrikt} (
          id int(11) NOT NULL,
          bezeichnung varchar(30) NOT NULL,
          gruppentyp_id int(11) NOT NULL,
          imageurl varchar(50) DEFAULT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_distrikt} VALUES
           (1, 'Nord', 1, 'gruppe_schwarz.png'),
           (2, 'Süd', 1, 'gruppe_gelb.png'),
           (3, 'Ost', 1, 'gruppe_blau.png'),
           (4, 'West', 1, 'gruppe_gruen.png'),
           (5, 'Sommerfreizeiten', 4, NULL)");
        db_query("CREATE TABLE {cdb_familienstand} (
          id int(11) NOT NULL,
          bezeichnung varchar(20) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_familienstand} VALUES
           (0, 'unbekannt'),
           (1, 'ledig'),
           (2, 'verheiratet'),
           (3, 'getrennt'),
           (4, 'geschieden'),
           (5, 'verwitwet')");
        db_query("CREATE TABLE {cdb_followup_typ} (
          id int(1) NOT NULL,
          bezeichnung varchar(50) NOT NULL,
          comment_viewer_id int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_followup_typ} VALUES
           (0, 'Kein Followup', 0),
           (1, 'Integration Kontaktkarte', 0),
           (2, 'Ein Monat', 0)");
        db_query("CREATE TABLE {cdb_followup_typ_intervall} (
          id int(11) NOT NULL AUTO_INCREMENT,
          followup_typ_id int(1) NOT NULL,
          count_no int(1) NOT NULL,
          days_diff int(2) NOT NULL,
          info varchar(500) NOT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY typ_no (followup_typ_id,count_no)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_followup_typ_intervall} VALUES
           (1, 1, 1, 7, 'Anruf 1 soll erfolgen.<br>Bitte sei nett zu der Person:)'),
           (2, 1, 2, 14, 'Anruf 2 soll erfolgen.<br/>Kleingruppe gefunden?'),
           (3, 1, 3, 90, 'Anruf 3 soll erfolgen. <br>Wie geht es so?'),
           (4, 2, 1, 31, 'Person nachhalten nach einem Monat')");
        db_query("CREATE TABLE {cdb_gemeindeperson} (
          id int(11) NOT NULL AUTO_INCREMENT,
          person_id int(11) NOT NULL,
          beruf varchar(50) NOT NULL,
          geburtsname varchar(30) NOT NULL,
          geburtsdatum datetime DEFAULT NULL,
          geburtsort varchar(30) NOT NULL,
          nationalitaet varchar(30) NOT NULL,
          familienstand_no int(11) NOT NULL DEFAULT '0',
          hochzeitsdatum datetime DEFAULT NULL,
          station_id int(11) NOT NULL DEFAULT '0',
          status_id int(11) NOT NULL DEFAULT '0',
          erstkontakt datetime DEFAULT NULL,
          zugehoerig datetime DEFAULT NULL,
          eintrittsdatum datetime DEFAULT NULL,
          austrittsgrund varchar(10) NOT NULL,
          austrittsdatum datetime DEFAULT NULL,
          taufdatum datetime DEFAULT NULL,
          taufort varchar(50) NOT NULL,
          getauftdurch varchar(50) NOT NULL,
          ueberwiesenvon varchar(30) NOT NULL,
          ueberwiesennach varchar(30) NOT NULL,
          imageurl varchar(50) DEFAULT NULL,
          letzteaenderung datetime DEFAULT NULL,
          aenderunguser varchar(20) DEFAULT NULL,
          PRIMARY KEY (id),
          KEY person_id (person_id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_gemeindeperson} VALUES
           (1, 1, 'Krankenschwester', 'Meierchen', '1979-09-06 00:00:00', 'Hannover', '', 2, NULL, 1, 2, '2010-01-04 00:00:00', '2010-05-20 00:00:00', '2011-01-01 00:00:00', '', NULL, '2010-05-20 00:00:00', 'Hamburg Elim', 'Pastor Manfred', 'Mustergemeinde Hannover', '', 'imageaddr1.jpg', '2011-01-01 00:00:00', 'jmrauen'),
           (2, 2, '', '', NULL, '', '', 2, NULL, 1, 2, NULL, NULL, NULL, '', NULL, NULL, '', '', 'Mustergemeinde Hamburg', '', NULL, '2011-01-11 00:00:00', 'jmrauen')");
        db_query("CREATE TABLE {cdb_gemeindeperson_gruppe} (
          gemeindeperson_id int(11) NOT NULL,
          gruppe_id int(11) NOT NULL,
          status_no int(1) NOT NULL DEFAULT '0',
          letzteaenderung datetime DEFAULT NULL,
          aenderunguser varchar(20) DEFAULT NULL,
          followup_count_no int(1) DEFAULT NULL,
          followup_add_diff_days int(3) DEFAULT NULL,
          PRIMARY KEY (gemeindeperson_id,gruppe_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_gemeindeperson_gruppe_archive} (
          gemeindeperson_id int(10) NOT NULL,
          gruppe_id int(10) NOT NULL,
          status_no int(1) NOT NULL DEFAULT '0',
          letzteaenderung datetime DEFAULT NULL,
          aenderunguser varchar(20) DEFAULT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_gemeindeperson_tag} (
          gemeindeperson_id int(11) NOT NULL,
          tag_id int(11) NOT NULL,
          letzteaenderung datetime DEFAULT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_geschlecht} (
          id int(11) NOT NULL,
          bezeichnung varchar(20) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_geschlecht} VALUES
           (0, 'unbekannt'),
           (1, 'maennlich'),
           (2, 'weiblich')");
        db_query("CREATE TABLE {cdb_gruppe} (
          id int(11) NOT NULL AUTO_INCREMENT,
          valid_yn int(1) NOT NULL DEFAULT '1',
          versteckt_yn int(1) NOT NULL DEFAULT '0',
          bezeichnung varchar(100) NOT NULL,
          gruendungsdatum datetime DEFAULT NULL,
          abschlussdatum datetime DEFAULT NULL,
          treffzeit varchar(30) NOT NULL,
          treffpunkt varchar(50) NOT NULL,
          zielgruppe varchar(30) NOT NULL,
          gruppentyp_id int(11) NOT NULL,
          distrikt_id int(11) DEFAULT NULL,
          geolat varchar(20) NOT NULL,
          geolng varchar(20) NOT NULL,
          treffname varchar(30) NOT NULL,
          notiz varchar(200) NOT NULL,
          treffen_yn int(11) NOT NULL,
          instatistik_yn int(1) NOT NULL,
          followup_typ_id int(1) DEFAULT NULL,
          fu_nachfolge_typ_id int(11) NOT NULL DEFAULT '0',
          fu_nachfolge_objekt_id int(11) DEFAULT NULL,
          letzteaenderung datetime DEFAULT NULL,
          aenderunguser varchar(20) DEFAULT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_gruppentreffen} (
          id int(11) NOT NULL AUTO_INCREMENT,
          gruppe_id int(11) NOT NULL,
          datumvon datetime NOT NULL,
          datumbis datetime NOT NULL,
          eintragerfolgt_yn int(11) NOT NULL,
          ausgefallen_yn int(11) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_gruppentreffen_gemeindeperson} (
          gruppentreffen_id int(11) NOT NULL,
          gemeindeperson_id int(11) NOT NULL,
          treffen_yn int(11) NOT NULL,
          UNIQUE KEY gruppentreffen_id (gruppentreffen_id,gemeindeperson_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_gruppentyp} (
          id int(11) NOT NULL,
          bezeichnung varchar(30) NOT NULL,
          anzeigen_in_meinegruppen_teilnehmer_yn int(1) NOT NULL,
          muss_leiter_enthalten_yn int(1) NOT NULL,
          in_neue_person_erstellen_yn int(1) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_gruppentyp} VALUES
           (1, 'Kleingruppe', 1, 1, 0),
           (2, 'Dienst', 1, 0, 0),
           (3, 'Maßnahme', 0, 0, 1),
           (4, 'Merkmal', 0, 0, 1)");
        db_query("CREATE TABLE {cdb_gruppe_tag} (
          gruppe_id int(11) NOT NULL,
          tag_id int(11) NOT NULL,
          letzteaenderung datetime NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_log} (
          id int(11) NOT NULL AUTO_INCREMENT,
          level int(11) NOT NULL,
          datum datetime NOT NULL,
          userid varchar(255) NOT NULL,
          domain_type varchar(255) DEFAULT NULL,
          domain_id int(11) DEFAULT NULL,
          schreiben_yn int(1) DEFAULT NULL,
          txt varchar(2048) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_mailnotify} (
          id varchar(20) NOT NULL,
          emails varchar(200) NOT NULL,
          enabled int(11) NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_newsletter} (
          person_id int(11) NOT NULL,
          last_send datetime DEFAULT NULL,
          PRIMARY KEY (person_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cdb_person} (
          id int(11) NOT NULL AUTO_INCREMENT,
          name varchar(30) NOT NULL,
          vorname varchar(30) NOT NULL,
          password varchar(255) DEFAULT NULL,
          loginstr varchar(255) DEFAULT NULL,
          lastlogin datetime DEFAULT NULL,
          geschlecht_no int(11) NOT NULL DEFAULT '0',
          titel varchar(30) NOT NULL,
          strasse varchar(30) NOT NULL,
          plz varchar(6) NOT NULL,
          ort varchar(40) NOT NULL,
          land varchar(30) NOT NULL,
          zusatz varchar(30) NOT NULL,
          telefonprivat varchar(30) NOT NULL,
          telefongeschaeftlich varchar(20) NOT NULL,
          telefonhandy varchar(20) NOT NULL,
          fax varchar(20) NOT NULL,
          email varchar(50) NOT NULL,
          geolat varchar(20) NOT NULL,
          geolng varchar(20) NOT NULL,
          cmsuserid varchar(50) NOT NULL,
          createdate datetime DEFAULT NULL,
          letzteaenderung datetime DEFAULT NULL,
          aenderunguser varchar(20) DEFAULT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_person} VALUES
           (1, 'Ackermeister', 'Sabine', '21232f297a57a5a743894a0e4a801fc3', NULL, '2013-08-30 15:56:42', 2, 'Dipl.-Ing.', 'Kedenburgstr. 22', '22041', 'Hamburg', '', '', '040 12345678', '0179 12345678', '', '', 'admin@test.de', '53.5778604', '10.08704130000001', 'admin', '2011-01-01 00:00:00', '2011-01-31 00:00:00', 'Administrator'),
           (2, 'Helmut', 'Meier', NULL, NULL, NULL, 2, '', 'Bostelreihe 9', '22043', 'Hamburg', '', '', '', '', '', '', 'helmut@test.de', '53.5778604', '10.08704130000001', 'admin', '2011-01-01 00:00:00', '2011-01-31 00:00:00', 'Administrator')");
        db_query("CREATE TABLE {cdb_station} (
          id int(11) NOT NULL,
          bezeichnung varchar(20) NOT NULL,
          kuerzel varchar(10) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_station} VALUES
           (0, 'unbekannt', '?'),
           (1, 'Zentrale', 'Z')");
        db_query("CREATE TABLE {cdb_status} (
          id int(11) NOT NULL,
          bezeichnung varchar(30) NOT NULL,
          kuerzel varchar(10) NOT NULL,
          mitglied_yn int(1) NOT NULL,
          infreitextauswahl_yn int(1) NOT NULL DEFAULT '1',
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cdb_status} VALUES
           (0, 'unbekannt', '?', 0, 1),
           (1, 'Freund', 'F', 0, 1),
           (2, 'Mitglied', 'M', 1, 1),
           (3, 'zu löschen', 'X', 0, 0)");
        db_query("CREATE TABLE {cdb_tag} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bezeichnung varchar(255) NOT NULL,
          letzteaenderung datetime DEFAULT NULL,
          aenderunguser varchar(60) DEFAULT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cr_booking} (
          id int(11) NOT NULL AUTO_INCREMENT,
          resource_id int(11) NOT NULL,
          userid varchar(50) DEFAULT NULL,
          startdate datetime NOT NULL,
          enddate datetime NOT NULL,
          repeat_id int(11) NOT NULL,
          repeat_frequence int(11) NOT NULL,
          repeat_until datetime NOT NULL,
          status_id int(11) NOT NULL,
          text varchar(30) NOT NULL,
          location varchar(20) NOT NULL,
          note tinytext NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cr_exception} (
          id int(11) NOT NULL AUTO_INCREMENT,
          booking_id int(11) NOT NULL,
          except datetime NOT NULL,
          userid varchar(20) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cr_hours} (
          id int(11) NOT NULL,
          bezeichnung varchar(20) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cr_hours} VALUES
           (0, '00'),
           (1, '01'),
           (2, '02'),
           (3, '03'),
           (4, '04'),
           (5, '05'),
           (6, '06'),
           (7, '07'),
           (8, '08'),
           (9, '09'),
           (10, '10'),
           (11, '11'),
           (12, '12'),
           (13, '13'),
           (14, '14'),
           (15, '15'),
           (16, '16'),
           (17, '17'),
           (18, '18'),
           (19, '19'),
           (20, '20'),
           (21, '21'),
           (22, '22'),
           (23, '23')");
        db_query("CREATE TABLE {cr_log} (
          id int(11) NOT NULL AUTO_INCREMENT,
          level int(11) NOT NULL,
          datum datetime NOT NULL,
          userid varchar(20) NOT NULL,
          booking_id int(11) NOT NULL,
          txt varchar(400) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cr_minutes} (
          id int(11) NOT NULL,
          bezeichnung varchar(20) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cr_minutes} VALUES
           (0, '00'),
           (15, '15'),
           (30, '30'),
           (45, '45')");
        db_query("CREATE TABLE {cr_resource} (
          id int(11) NOT NULL AUTO_INCREMENT,
          resourcetype_id int(11) NOT NULL,
          sortkey int(11) NOT NULL,
          bezeichnung varchar(20) NOT NULL,
          location varchar(20) NOT NULL,
          autoaccept_yn int(1) NOT NULL,
          adminmails varchar(30) DEFAULT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cr_resource} VALUES
           (1, 1, 0, 'Buero-Beamer', 'Buero von Max', 1, ''),
           (2, 1, 0, 'Mobil-Beamer', 'Buero von Hans', 1, ''),
           (3, 1, 0, 'Bus', '', 1, ''),
           (5, 2, 0, 'Haupt-Cafe', '', 0, ''),
           (4, 2, 0, 'Schokoraum', '', 1, ''),
           (6, 2, 0, 'Kickerraum', '', 0, '')");
        db_query("CREATE TABLE {cr_resourcetype} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bezeichnung varchar(20) NOT NULL,
          sortkey int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cr_resourcetype} VALUES
           (2, 'Raum', 1),
           (1, 'Gegenstand', 0)");
        db_query("CREATE TABLE {cr_status} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bezeichnung varchar(30) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cr_status} VALUES
           (1, 'Wartet auf Bestaetigung'),
           (2, 'Bestaetigt'),
           (3, 'Abgelehnt'),
           (99, 'Geloescht')");
        db_query("CREATE TABLE {cs_category} (
          id int(11) NOT NULL,
          bezeichnung varchar(255) NOT NULL,
          sortkey int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cs_category} VALUES
           (0, 'Sonstige Veranstaltung', 8),
           (1, 'Sontagsgodis', 1),
           (2, 'Jugend', 19)");
        db_query("CREATE TABLE {cs_event} (
          id int(11) NOT NULL AUTO_INCREMENT,
          datum datetime NOT NULL,
          bezeichnung varchar(255) NOT NULL,
          special varchar(255) DEFAULT NULL,
          admin varchar(255) DEFAULT NULL,
          category_id int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cs_eventservice} (
          id int(11) NOT NULL AUTO_INCREMENT,
          event_id int(11) NOT NULL,
          service_id int(11) NOT NULL,
          valid_yn int(1) NOT NULL DEFAULT '1',
          zugesagt_yn int(11) NOT NULL DEFAULT '0',
          name varchar(255) DEFAULT NULL,
          cdb_person_id int(11) DEFAULT NULL,
          reason varchar(255) DEFAULT NULL,
          mailsenddate datetime DEFAULT NULL,
          modifieddate datetime NOT NULL,
          modifieduser varchar(255) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cs_eventtemplate} (
          id int(11) NOT NULL,
          bezeichnung varchar(255) NOT NULL,
          category_id int(11) DEFAULT NULL,
          event_bezeichnung varchar(255) DEFAULT NULL,
          special varchar(255) DEFAULT NULL,
          stunde int(11) DEFAULT NULL,
          minute int(11) DEFAULT NULL,
          admin varchar(255) DEFAULT NULL,
          sortkey int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cs_eventtemplate} VALUES(0, 'Standard', 1, 'Standard', 'Weitere Infos...', 12, 0, '', 0)");
        db_query("CREATE TABLE {cs_eventtemplate_service} (
          eventtemplate_id int(11) NOT NULL,
          service_id int(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cs_service} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bezeichnung varchar(50) NOT NULL,
          standard_yn int(11) NOT NULL DEFAULT '0',
          servicegroup_id int(11) NOT NULL,
          cdb_gruppen_ids varchar(255) DEFAULT NULL,
          cdb_tag_ids varchar(255) DEFAULT NULL,
          sendremindermails_yn int(1) NOT NULL DEFAULT '0',
          sortkey int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cs_service} VALUES
           (1, 'Predigt', 1, 1, NULL, NULL, 0, 0),
           (2, 'Lobpreis', 1, 1, NULL, NULL, 0, 0),
           (3, 'Leitung', 1, 1, NULL, NULL, 0, 0),
           (4, 'Hauptordner', 1, 2, NULL, NULL, 0, 1),
           (5, 'Nebenordner', 0, 2, NULL, NULL, 0, 0),
           (6, 'Ton', 1, 3, NULL, NULL, 0, 0),
           (7, 'Licht', 0, 3, NULL, NULL, 0, 0),
           (8, 'Video', 0, 3, NULL, NULL, 0, 0)");
        db_query("CREATE TABLE {cr_repeat} (
          id int(11) NOT NULL
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8");
        db_query("CREATE TABLE {cs_servicegroup} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bezeichnung varchar(255) NOT NULL,
          sortkey int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8");
        db_query("INSERT INTO {cs_servicegroup} VALUES
           (1, 'Programm', 10),
           (2, 'Ordner', 20),
           (3, 'Techniker', 30)");
      }
      else {
        addInfoMessage('Keine Datenbank-Tabellen gefunden. Soll ich sie nun anlegen?<p><a href="?installdb=true" class="btn">Tabellen jetzt anlegen</a>');
        return false;
      }
    case 'pre-2.0':
      db_query("create table {cc_config} (name varchar(255) not null, value varchar(255) not null) CHARSET=utf8");
      db_query("ALTER TABLE {cc_config} ADD PRIMARY KEY(name)");
      db_query("INSERT INTO {cc_config} VALUES
         ('churchcal_name', 'ChurchCal'),
         ('churchdb_birthdaylist_station', '1,2,3'),
         ('churchdb_birthdaylist_status', '1,2,3'),
         ('churchdb_emailseparator', ';'),
         ('churchdb_groupnotchoosable', '90'),
         ('churchdb_home_lat', '53.568537'),
         ('churchdb_home_lng', '10.03656'),
         ('churchdb_maxexporter', '250'),
         ('churchdb_memberlist_station', '1,2,3'),
         ('churchdb_memberlist_status', '1,2,3'),
         ('churchdb_name', 'ChurchDB'),
         ('churchdb_sendgroupmails', '1'),
         ('churchresource_entries_last_days', '90'),
         ('churchresource_name', 'ChurchResource'),
         ('churchservice_entries_last_days', '90'),
         ('churchservice_name', 'ChurchService'),
         ('churchservice_openservice_rememberdays', '3'),
         ('currently_mail_sending', '0'),
         ('last_cron', '1377871248'),
         ('last_db_dump', '1377868285'),
         ('login_message', 'Willkommen auf dem neuen ".variable_get("site_name")." Zum Anmelden bitte die Felder ausfüllen!'),
         ('mail_enabled', '1'),
         ('site_mail', 'admin@example.com'),
         ('site_name', '".variable_get("site_name")."'),
         ('version', '2.00'),
         ('welcome', 'Herzlich willkommen'),
         ('welcome_subtext', 'Das ist die Startseite von ".variable_get("site_name")."');");
      db_query("create table {cc_session} (person_id int(11) not null, session varchar(255) not null, hostname varchar(255) not null, datum datetime not null) CHARSET=utf8");

      db_query("CREATE TABLE {cc_auth} (id int(11) NOT NULL, auth varchar(80) NOT NULL, modulename varchar(80) NOT NULL, datenfeld varchar(255) DEFAULT NULL, bezeichnung varchar(255) NOT NULL,PRIMARY KEY (id)) CHARSET=utf8");

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

      db_query("CREATE TABLE {cc_domain_auth} (domain_type varchar(30) NOT NULL, domain_id int(11) NOT NULL, auth_id int(11) NOT NULL,  daten_id int(11) DEFAULT NULL) CHARSET=utf8");
      addInfoMessage("Installiere Tabellen f&uuml;r Version 2.00");
      /* fall through to regular update */
    case '2.00':
      db_query("ALTER TABLE {cs_servicegroup} ADD viewall_yn int( 1 ) NOT NULL DEFAULT 0 AFTER bezeichnung");
      set_version("2.01");
    
    case '2.01':
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
      set_version("2.02");
    
    case '2.02':
      db_query("ALTER TABLE {cs_eventtemplate_service} ADD UNIQUE (eventtemplate_id , service_id)"); 
      set_version("2.03");
    
    case '2.03':
      db_query("CREATE TABLE {cc_file} (
      id int(11) NOT NULL AUTO_INCREMENT,
      domain_type varchar(30) NOT NULL,
      domain_id int(11) NOT NULL,
      filename varchar(255) NOT NULL,
      UNIQUE KEY domain_type (domain_type,domain_id,filename),
      PRIMARY KEY (id)) CHARSET=utf8");
      
      db_query("INSERT INTO {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
         VALUES ('307',  'manage absent',  'churchservice', NULL ,  'Abwesenheiten einsehen und pflegen');");
      
      db_query("INSERT INTO {cc_auth} (id ,auth ,modulename ,datenfeld ,bezeichnung)
         VALUES ('308',  'edit facts',  'churchservice', NULL ,  'Fakten pflegen');");
      
      db_query("CREATE TABLE {cs_absent_reason} (
        id int(11) NOT NULL AUTO_INCREMENT,
        bezeichnung varchar(255) NOT NULL,
        sortkey int(11) NOT NULL,
        PRIMARY KEY (id)
      ) CHARSET=utf8");
      
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
      ) CHARSET=utf8");
      
      db_query("CREATE TABLE {cs_event_fact} (
        event_id int(11) NOT NULL,
        fact_id int(11) NOT NULL,
        value int(11) NOT NULL,
        modifieddate datetime DEFAULT NULL,
        modifieduser int(11) DEFAULT NULL,
        PRIMARY KEY (event_id,fact_id)
      ) CHARSET=utf8");
      
      db_query("
        CREATE TABLE {cs_fact} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bezeichnung varchar(255) NOT NULL,
          sortkey int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (id)
      ) CHARSET=utf8");
      
      db_query("INSERT INTO {cs_fact} VALUES(1, 'Besucher', 0)");
      db_query("INSERT INTO {cs_fact} VALUES(2, 'Kollekte', 0)");
      set_version("2.04");
    
    case '2.04':
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
      ) CHARSET=utf8");
      set_version("2.05");
    
    case '2.05':
    case '2.06':
    case '2.07':
      db_query("ALTER TABLE {cc_cal} CHANGE repeat_frequence repeat_frequence INT( 2 ) NULL DEFAULT NULL");
      db_query("ALTER TABLE {cc_cal} CHANGE repeat_until repeat_until DATETIME NULL");
      db_query("CREATE TABLE {cc_cal_except} (
        id int(11) NOT NULL AUTO_INCREMENT,
        cal_id int(11) NOT NULL,
        except_date datetime not null,
        modifieddate datetime NOT NULL,
        modifieduser int(11) NOT NULL,
        PRIMARY KEY (id)) CHARSET=utf8");
      db_query("ALTER TABLE {cs_category} ADD color VARCHAR( 20 ) NULL AFTER bezeichnung");
      db_query("ALTER TABLE {cc_cal} ADD category_id INT( 11 ) NOT NULL DEFAULT '0' AFTER enddate");
      db_query("ALTER TABLE {cdb_status} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
      db_query("ALTER TABLE {cdb_bereich} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
      db_query("ALTER TABLE {cdb_gruppentyp} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
      db_query("ALTER TABLE {cdb_station} ADD sortkey INT( 11 ) NOT NULL DEFAULT '0'");
      db_query("ALTER TABLE {cs_eventservice} ADD counter INT( 11 ) NULL AFTER service_id");
      db_query("ALTER TABLE {cr_booking} CHANGE userid userid VARCHAR( 50 )");
      db_query("insert into {cc_config} (name, value) values ('cronjob_delay','0')");  
      set_version("2.08");
    
    case '2.08':
      db_query("ALTER TABLE {cdb_person} ADD active_yn INT( 1 ) NOT NULL DEFAULT  '1' AFTER vorname");
      db_query("ALTER TABLE {cdb_person} ADD optigem_nr VARCHAR( 30 ) NOT NULL AFTER cmsuserid");
      db_query("ALTER TABLE {cc_cal} ADD ort VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER bezeichnung");
      db_query("ALTER TABLE {cc_cal} ADD notizen VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER ort");
      db_query("ALTER TABLE {cc_cal} ADD intern_yn int(1) not NULL default '0' AFTER notizen");
      set_version("2.09");
    
    case '2.09':
    case '2.10':
      db_query("ALTER TABLE {cs_category} ADD show_in_churchcal_yn INT(1) NOT NULL DEFAULT '1' AFTER color");
      db_query("ALTER TABLE {cr_booking} ADD show_in_churchcal_yn INT(1) NOT NULL DEFAULT '0'");
      set_version("2.11");
    
    case '2.11':
      db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (105, 'view address', 'churchdb', 'Darf die Adressdaten einsehen')");
      db_query("UPDATE {cc_auth} set bezeichnung='Alle Informationen der Person sehen, inkl. Adressdaten, Gruppenzuordnung, etc.' where auth='view alldetails'");
      
      db_query("ALTER TABLE {cc_cal} CHANGE modifieddate modified_date DATETIME NOT NULL");
      db_query("ALTER TABLE {cc_cal} CHANGE modifieduser modified_pid int(11) not null");
      db_query("ALTER TABLE {cc_cal_except} CHANGE modifieddate modified_date DATETIME NOT NULL");
      db_query("ALTER TABLE {cc_cal_except} CHANGE modifieduser modified_pid int(11) not null");
      db_query("ALTER TABLE {cc_help} CHANGE modifieddate modified_date DATETIME NOT NULL");
      db_query("ALTER TABLE {cc_help} CHANGE modifieduser modified_pid int(11) not null");
      
      // change structure...
      db_query("ALTER TABLE {cdb_log} ADD person_id int(11) not null default -1 after userid");  
      db_query("ALTER TABLE {cdb_comment} ADD person_id int(11) not null default -1 after userid");
      db_query("ALTER TABLE {cs_eventservice} CHANGE modifieddate modified_date DATETIME NOT NULL");
      db_query("ALTER TABLE {cs_eventservice} ADD modified_pid int(11) not null default -1 after modifieduser");  
      db_query("ALTER TABLE {cs_event_fact} CHANGE modifieddate modified_date DATETIME NOT NULL");
      db_query("ALTER TABLE {cs_event_fact} CHANGE modifieduser modified_pid int(11) not null");
      db_query("ALTER TABLE {cs_absent} CHANGE modifieddate modified_date DATETIME NOT NULL");
      db_query("ALTER TABLE {cs_absent} CHANGE modifieduser modified_pid int(11) not null");
    
      // ...and update data to matchh
      db_query("UPDATE {cdb_log} log JOIN {cdb_person} p ON p.cmsuserid=log.userid SET log.person_id=p.id");
      db_query("UPDATE {cdb_comment} c JOIN {cdb_person} p ON p.cmsuserid=c.userid SET c.person_id=p.id");
      db_query("UPDATE {cs_eventservice} es JOIN {cdb_person} p ON p.cmsuserid=es.modifieduser SET es.modified_pid=p.id");
      set_version("2.12");
    
    case '2.12':
      db_query("ALTER TABLE {cr_booking} ADD person_id int(11) not null default -1 after userid");  
      db_query("ALTER TABLE {cr_exception} ADD person_id int(11) not null default -1 after userid");  
      db_query("ALTER TABLE {cr_log} ADD person_id int(11) not null default -1 after userid");  
      db_query("ALTER TABLE {cr_resource} ADD admin_person_ids int(11) not null default -1 after adminmails");  
      db_query("ALTER TABLE {cr_resource} CHANGE adminmails adminmails_old varchar(30) null");  
      
      db_query("UPDATE {cr_booking} a JOIN {cdb_person} p ON p.cmsuserid=a.userid SET a.person_id=p.id");
      db_query("UPDATE {cr_exception} a JOIN {cdb_person} p ON p.cmsuserid=a.userid SET a.person_id=p.id");
      db_query("UPDATE {cr_log} a JOIN {cdb_person} p ON p.cmsuserid=a.userid SET a.person_id=p.id");      
      db_query("UPDATE {cr_resource} a JOIN {cdb_person} p ON p.email=a.adminmails_old SET a.admin_person_ids=p.id");      
      set_version("2.13");
    
    case '2.13':
      db_query("INSERT INTO  {cc_auth} (id, auth, modulename, bezeichnung) values (4, 'view whoisonline', 'churchcore', 'Sieht auf der Startseite, wer aktuell online ist')");
      db_query("CREATE TABLE {cc_loginstr} 
          (person_id int(11) NOT NULL, loginstr varchar(255) NOT NULL, create_date date NOT NULL) CHARSET=utf8");
      db_query("insert into {cc_loginstr} (person_id, loginstr, create_date) 
                   select id person_id, loginstr, now() from {cdb_person} where loginstr is not null");   
      set_version("2.14");
    
    case '2.14':
      db_query("ALTER TABLE {cs_service} ADD allowtonotebyconfirmation_yn INT( 1 ) NOT NULL DEFAULT 0 AFTER sendremindermails_yn");
      set_version("2.15");
    
    case '2.15':
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
        PRIMARY KEY (id)) CHARSET=utf8"
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
        PRIMARY KEY (id)) CHARSET=utf8
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
        PRIMARY KEY (person_id,servicegroup_id)) CHARSET=utf8"
      );
      
      $files=churchcore_getTableData("cc_file");
      if ($files!=null) {
        foreach($files as $file) {
          if (!file_exists("$files_dir/files/$file->domain_type"))
            mkdir("$files_dir/files/$file->domain_type",0777,true);
          if (file_exists("$files_dir/files/$file->domain_id"))
            rename("$files_dir/files/$file->domain_id", "$files_dir/files/$file->domain_type/$file->domain_id");  
        }
      }  
      set_version("2.16");
    
    case '2.16':
      db_query("insert into {cc_config} values ('max_uploadfile_size_kb', 10000)");
      db_query("insert into {cc_config} values ('cronjob_dbdump', 0)");
      db_query("CREATE TABLE {cs_songcategory} (
        id INT( 11 ) NOT NULL,
        bezeichnung VARCHAR( 100 ) NOT NULL ,
        sortkey int(11) not null default 0,
        PRIMARY KEY (  id )) CHARSET=utf8");
      db_query("insert into {cs_songcategory} values (0,'Unbekannt',0)"); 
      db_query("ALTER TABLE {cs_song} ADD songcategory_id INT( 11 ) NOT NULL AFTER bezeichnung");
      db_query("INSERT INTO  {cc_auth} (
        id, auth , modulename , datenfeld ,bezeichnung )
        VALUES (
        '313',  'view songcategory',  'churchservice',  'cs_songcategory',  'Erlaubt den Zugriff auf bestimmte Song-Kategorien'
        )");
      db_query("ALTER TABLE {cc_file} ADD modified_date DATETIME NOT NULL");
      db_query("ALTER TABLE {cc_file} ADD modified_pid INT( 11 ) NOT NULL");
      set_version("2.17");
    
    case '2.17':
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
        ) CHARSET=utf8");
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
    ) CHARSET=utf8");
    
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
    ) CHARSET=utf8");
    
    db_query("INSERT INTO {cdb_feld} VALUES(1, 1, 1, 'titel', NULL, 1, 'Titel', '', '', NULL, 12, 1)");
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
      
      // Add existing nationality values to new table
     db_query("ALTER TABLE {cdb_nationalitaet} CHANGE  id id INT( 11 ) NOT NULL AUTO_INCREMENT");
     db_query("insert into {cdb_nationalitaet} (bezeichnung) (select nationalitaet from {cdb_gemeindeperson} gp left join {cdb_nationalitaet} n on (gp.nationalitaet=n.bezeichnung) where n.bezeichnung is null and gp.nationalitaet!=''
    group by nationalitaet)");
      
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('&Auml;gypten')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('&Auml;quatorialguinea')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('&Auml;thiopien')");
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
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('D&auml;nemark')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Deutschland')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Dominica')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Dominikanische Republik')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Dschibuti')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ecuador')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('El Salvador')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Elfenbeink&uuml;ste')");
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
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Gro&szlig;britannien')");
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
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('&Ouml;sterreich')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Oman')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Pakistan')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Palau')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Pal&auml;stinensische Gebiete')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Panama')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Papua-Neuguinea')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Paraguay')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Peru')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Philippinen')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Polen')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Portugal')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Ruanda')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Rum&auml;nien')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Russland')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Sahara')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Salomonen')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Sambia')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Samoa')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('San Marino')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('S&atilde;o Tom&eacute; und Pr&iacute;ncipe')");
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
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('S&uuml;dafrika')");
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('S&uuml;dkorea')");
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
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('T&uuml;rkei')");
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
    db_query("INSERT INTO {cdb_nationalitaet} (bezeichnung) VALUES('Wei&szlig;russland')");
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
      UNIQUE KEY gruppe_id (gruppe_id,status_no)) CHARSET=utf8");
      db_query("ALTER TABLE  {cr_resource} CHANGE admin_person_ids admin_person_ids VARCHAR( 50 ) NOT NULL DEFAULT  '-1'");
      set_version("2.18");
      
    case '2.18':
    case '2.19':
    case '2.20':
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
      set_version("2.21");
    
    case '2.21':
    case '2.22':
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
      set_version("2.23");
    
    case '2.23':
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
      set_version("2.24");
    
    case '2.24':
      db_query("ALTER TABLE {cc_file} CHANGE domain_id domain_id VARCHAR( 30 ) NOT NULL");
      db_query("ALTER TABLE {cc_file} ADD bezeichnung VARCHAR( 50 ) NOT NULL AFTER domain_id");  
      db_query("update {cc_file} set bezeichnung=filename");
      db_query("ALTER TABLE {cdb_gemeindeperson} CHANGE imageurl imageurl VARCHAR( 50 )"); 
      set_version("2.25");
    
    case '2.25':
      db_query("ALTER TABLE {cdb_gruppe} ADD fu_nachfolge_gruppenteilnehmerstatus_id INT( 11 ) NULL AFTER fu_nachfolge_objekt_id");
      db_query("ALTER TABLE {cdb_gemeindeperson_gruppe} ADD  followup_erfolglos_zurueck_gruppen_id INT( 11 ) NULL AFTER followup_add_diff_days");
      db_query("UPDATE {cdb_feld} set sortkey=2 where db_spalte='max_teilnehmer' and feldkategorie_id=4");
      db_query("INSERT INTO {cdb_feld} VALUES(-1, 4, 2, 'fu_nachfolge_gruppenteilnehmerstatus_id', 'groupMemberTypes', 1, 'Followup-Nachfolger-Teilnehmerstatus', 'Followup-Nachfolger-Teilnehmerstatus', '<br/>', 'admingroups', 11, 5)");
      
      db_query("update {cdb_feld} set langtext='<p>Versteckte Gruppe<br/><small>Gruppe ist nur f&uuml;r Gruppenadmins & Leiter sichtbar</small>' 
                  where langtext='<p>Versteckte Gruppe<br/><small>Gruppe ist nur f&uuml;r Gruppenadmins & Leiter sichbar</small>'");
      set_version("2.26");
    
    case '2.26':
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
      set_version("2.27");
    
    case '2.27':
      db_query("ALTER TABLE {cc_wiki} ADD auf_startseite_yn INT( 1 ) NOT NULL DEFAULT '0' AFTER text");
      db_query("ALTER TABLE {cc_cal_add} ADD with_repeat_yn INT( 1 ) NOT NULL DEFAULT '1' AFTER add_date");
      db_query("ALTER TABLE {cr_addition} ADD with_repeat_yn INT( 1 ) NOT NULL DEFAULT '1' AFTER add_date");
      db_query("ALTER TABLE {cdb_person} ADD spitzname VARCHAR( 30 ) NOT NULL AFTER vorname");
      db_query("update {cdb_feld} set sortkey=sortkey+1 where feldkategorie_id=1 and sortkey>=3");
      db_query("INSERT INTO {cdb_feld} VALUES(0, 1, 1, 'spitzname', NULL, 1, 'Spitzname', '', '(%) ', NULL, 30, 3)");
      db_query("ALTER TABLE {cdb_beziehungstyp} ADD sortkey INT( 11 ) NOT NULL");
      db_query("ALTER TABLE {cdb_person} ADD loginerrorcount INT( 11 ) NOT NULL AFTER lastlogin");
      db_query("INSERT INTO {cc_wiki} VALUES ('main',1,0,'<h2>​<strong>Was ist das Wiki?</strong></h2>\n\n<p><span style=\\\"font-size:14px\\\">D</span><img alt=\\\"\\\" src=\\\"http://intern.churchtools.de/system/assets/img/wiki_logo.png\\\" style=\\\"float:right; height:270px; width:300px\\\" /><span style=\\\"font-size:14px\\\">as Wiki soll als Dokumentation, Informations- und Arbeitsgrundlage f&uuml;r die verschiedenen Dienstbereiche der Gemeinde dienen. Jeder Mitarbeiter eines Dienstbereiches kann auf Wunsch Zugriff auf die entsprechenden Wiki-Kategorien erhalten. Diese Seiten k&ouml;nnen dann&nbsp;von allen aus demselben Dienstbereich gelesen und bearbeitet werden. So k&ouml;nnen aktuelle Information, Abl&auml;ufe, Einstellungen, etc. zeitnah gespeichert werden und sind sofort f&uuml;r alle einsehbar. Damit ist jeder zu jederzeit auf dem neusten Wissenstand.</span></p>\n\n<div><span style=\\\"font-size:14px\\\">Durch das Wiki haben neue Mitarbeiter alle n&ouml;tigen Informationen, Anleitungen und Hintergrundinformationen f&uuml;r ihren Dienst. Erfahrene Mitarbeiter k&ouml;nnen ihr Wissen und gesammelte Informationen dokumentieren und auf sie zur&uuml;ckgreifen.</span></div>\n\n<h2>Weitere Infos</h2>\n\n<div><span style=\\\"font-size:14px\\\">Mehr Infos zum Wiki gibt es <a href=\\\"http://intern.churchtools.de/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:ChurchWiki/\\\" target=\\\"_blank\\\">hier</a>.</span></div>\n',0,'2013-08-30 15:59:42',1)");
      db_query("INSERT INTO {cc_wiki} VALUES ('Sicherheitsbestimmungen',1,0,'<p><strong>Verpflichtung auf das Datengeheimnis gem&auml;&szlig; &sect; 5 Bundesdatenschutzgesetz (BDSG), auf das Fernmeldegeheimnis gem&auml;&szlig; &sect; 88 Telekommunikationsgesetz (TKG) und auf Wahrung von Gesch&auml;ftsgeheimnissen</strong><br />\n<br />\nHallo&nbsp;[Vorname]!<br />\nDie pers&ouml;nlichen Daten unserer Mitarbeiter und Mitglieder wollen wir sch&uuml;tzen. Darum bitten wir Dich, Dich auf das Datengeheimnis wie folgt zu verpflichten:<br />\n<br />\n<strong>1. Verpflichtung auf das Datengeheimnis nach &sect; 5 BDSG</strong><br />\nAufgrund von &sect; 5 BDSG ist mir untersagt, personenbezogene Daten, die mir dienstlich bekannt werden, unbefugt zu erheben, zu verarbeiten oder zu nutzen. Dies gilt sowohl f&uuml;r die dienstliche T&auml;tigkeit innerhalb wie auch au&szlig;erhalb (z.B. bei Kunden und Interessenten) des Unternehmens/der Beh&ouml;rde.<br />\nDie Pflicht zur Wahrung des Datengeheimnisses bleibt auch im Falle einer Versetzung oder nach Beendigung des Arbeits-/Dienstverh&auml;ltnisses bestehen.<br />\n<br />\n<strong>2. Verpflichtung auf das Fernmeldegeheimnis</strong><br />\nAufgrund von &sect; 88 TKG bin ich zur Wahrung des Fernmeldegeheimnisses verpflichtet, so- weit ich im Rahmen meiner T&auml;tigkeit bei der Erbringung gesch&auml;ftsm&auml;&szlig;iger Telekommunikationsdienste mitwirke.<br />\n<br />\n<strong>3. Verpflichtung auf Wahrung von Gesch&auml;ftsgeheimnissen</strong><br />\n&Uuml;ber Angelegenheiten des Unternehmens, die beispielsweise Einzelheiten ihrer Organisation und ihre Einrichtung betreffen, sowie &uuml;ber Gesch&auml;ftsvorg&auml;nge und Zahlen des internen Rechnungswesens, ist auch nach Beendigung des Arbeitsverh&auml;ltnisses von mir Verschwiegenheit zu wahren, sofern sie nicht allgemein &ouml;ffentlich bekannt geworden sind. Hierunter fallen&nbsp;auch Vorg&auml;nge von Drittunternehmen, mit denen ich dienstlich befasst bin. Auf die gesetzli- chen Bestimmungen &uuml;ber unlauteren Wettbewerb wurde ich besonders hingewiesen.<br />\nAlle dienstliche T&auml;tigkeiten betreffenden Aufzeichnungen, Abschriften, Gesch&auml;ftsunterlagen, Ablichtungen dienstlicher oder gesch&auml;ftlicher Vorg&auml;nge, die mir &uuml;berlassen oder von mir angefertigt werden, sind vor Einsichtnahme Unbefugter zu sch&uuml;tzen.<br />\n<br />\nVon diesen Verpflichtungen habe ich Kenntnis genommen. Ich bin mir bewusst, dass ich mich bei Verletzungen des Datengeheimnisses, des Fernmeldegeheimnisses oder von Gesch&auml;ftsgeheimnissen strafbar machen kann, insbesondere nach &sect;&sect; 44, 43 Abs. 2 BDSG, &sect; 206 Strafgesetzbuch (StGB) und nach &sect; 17 Gesetz gegen den unlauteren Wettbewerb (UWG).</p>',0,'0000-00-00 00:00:00',0)");
      db_query("insert into {cc_config} (name, value) values ('accept_datasecurity','0')");
      db_query("ALTER TABLE {cdb_person} ADD acceptedsecurity DATETIME NULL AFTER loginerrorcount");  
      set_version("2.28");
    
    case '2.28':
    case '2.29':
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
      set_version("2.30");
    
    case '2.30':
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
      set_version("2.31");
    
    case '2.31':
      db_query("ALTER TABLE {cs_service} ADD notiz VARCHAR( 50 ) NOT NULL AFTER bezeichnung");
      db_query("ALTER TABLE {cs_service} CHANGE bezeichnung  bezeichnung VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
      db_query("ALTER TABLE {cc_cal} ADD domain_type VARCHAR( 30 ) NULL AFTER id");
      db_query("ALTER TABLE {cc_cal} ADD domain_id INT( 11 ) NULL AFTER domain_type");  
      db_query("insert into {cc_config} values ('churchservice_reminderhours', '24')");
      db_query("INSERT INTO {cdb_feld} VALUES(null, 4, 2, 'gruppentyp_id', 'groupTypes', 1, 'Gruppentyp', 'Gruppentyp', '<br/>', 'admingroups', 11, 2)");
      set_version("2.32");
    
    case '2.32':
    case '2.33':
      db_query("ALTER TABLE {cc_cal} DROP domain_type");
      db_query("ALTER TABLE {cc_cal} DROP domain_id");
        
      db_query("ALTER TABLE {cs_event} ADD cc_cal_id INT( 11 ) NOT NULL AFTER id");
      
      // create parent cc_cal entries
      db_query("insert into {cc_cal} (select null, bezeichnung, '', '', 0, datum, DATE_ADD(datum, INTERVAL 1 HOUR), category_id, 0, null, null, null, current_date(), -1 from {cs_event})");
      // link to parent cc_cal
      db_query("update {cs_event} e 
        inner join (select * from {cc_cal}) as cal
        on cal.category_id=e.category_id and cal.bezeichnung=e.bezeichnung and cal.startdate=e.datum and e.cc_cal_id=0
        set e.cc_cal_id=cal.id");
    
      db_query("ALTER TABLE {cs_event} CHANGE category_id old_category_id INT( 11 ) NOT NULL DEFAULT  '0'");
      db_query("ALTER TABLE {cs_event} CHANGE bezeichnung old_bezeichnung VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
      db_query("ALTER TABLE {cs_event} CHANGE datum startdate DATETIME NOT NULL ");
      
      // CALENDAR
      // first, import descriptions
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
      // adapt IDs since auto_increment is used now
      if ($db!=null)
        foreach ($db as $ids) {
          db_query("update {cc_cal} set category_id=:cal_id where old_category_id=:cs_id", 
            array(":cal_id"=>$ids->cal_id, ":cs_id"=>$ids->cs_id));      
        } 

      // admin may not see everything, eg. personal calendars
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
      set_version("2.34");
    
    case '2.34':
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
    
      // public user may see the calendar function and church service calendars
      db_query("INSERT INTO {cc_domain_auth} (
        domain_type, domain_id, auth_id, daten_id)
        VALUES ('person',  '-1',  '401', NULL);");  
      db_query("INSERT INTO {cc_domain_auth} VALUES('person', -1, 403, 2)");
      db_query("INSERT INTO {cc_domain_auth} VALUES('person', -1, 403, 3)");
      
      db_query("update {cc_auth} set bezeichnung='Admin-Einstellungen anpassen' where id=1");
    
      // authorize viewing the calendar by status
      db_query("insert into {cc_domain_auth} SELECT 'status', s.id, 403, cat.id FROM {cc_calcategory} cat, {cdb_status} s where cat.oeffentlich_yn=1");
      set_version("2.35");
    
    case '2.35':
    case '2.36':
      db_query("update {cc_calcategory} set bezeichnung = replace(bezeichnung, '', '') 
        WHERE  bezeichnung LIKE  '%s Kalender'");
      db_query("ALTER TABLE {cc_cal} ADD link VARCHAR( 255 ) NOT NULL AFTER notizen");
      db_query("ALTER TABLE {cc_calcategory} ADD randomurl VARCHAR( 100 ) NOT NULL AFTER privat_yn");
      db_query("update {cc_calcategory} set randomurl=MD5(RAND()) where randomurl=''");
      db_query("TRUNCATE TABLE {cs_eventtemplate_service}");
      db_query("ALTER TABLE {cs_eventtemplate_service} ADD PRIMARY KEY ( eventtemplate_id ,  service_id )");
      db_query("ALTER TABLE {cr_booking} ADD cc_cal_id INT( 11 ) NULL");
      db_query("ALTER TABLE {cs_absent_reason} ADD color VARCHAR( 20 ) NOT NULL AFTER bezeichnung");
      db_query("ALTER TABLE {cs_eventtemplate_service} ADD count INT( 2 ) NOT NULL DEFAULT 1");  
      set_version("2.37");
    
    case '2.37':
      db_query("ALTER TABLE {cs_eventtemplate} ADD dauer_sec INT( 11 ) NOT NULL DEFAULT  '5400' AFTER minute");
      db_query("ALTER TABLE {cdb_feld} ADD inneuerstellen_yn INT( 1 ) NOT NULL DEFAULT '0' AFTER aktiv_yn");
      db_query("update {cdb_feld} set inneuerstellen_yn=1 where db_spalte in ('strasse', 'plz', 'ort', 'email')");
    
      db_query("UPDATE {cc_auth} SET auth = 'church category',
            bezeichnung = 'Kategorien von Gemeindekalendern anpassen' WHERE id =402");
      db_query("INSERT {cc_auth} (id, auth, modulename, bezeichnung) values(405, 'group category', 'churchcal',
            'Kategorien von Gruppenkalendern anpassen')");
      db_query("INSERT {cc_auth} (id, auth, modulename, bezeichnung) values(406, 'personal category', 'churchcal',
            'Kategorien von persoenlichen Kalendern anpassen')");
      db_query("ALTER TABLE {cc_auth} ADD UNIQUE (auth, modulename)");
      db_query("UPDATE {cc_auth} SET admindarfsehen_yn = 0 WHERE  id =403");
      db_query("UPDATE {cc_auth} SET admindarfsehen_yn = 0 WHERE  id =404");
      
      db_query("insert into {cc_domain_auth} select 'person', modified_pid, 404, id from {cc_calcategory} where id>0 and modified_pid>0");
      db_query("ALTER TABLE {cr_booking} ADD INDEX (cc_cal_id)");
      set_version("2.38");
    
    case '2.38':
    case '2.39':
    case '2.40':
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
      set_version("2.41");

    case '2.41':
      db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (407, 'create personal category', 'churchcal', null, 'Darf persoenliche Kalender erstellen',1)");
      db_query("INSERT INTO {cc_auth} (id, auth, modulename, datenfeld, bezeichnung, admindarfsehen_yn) values (408, 'create group category', 'churchcal', null, 'Darf Gruppenkalender erstellen',1)");
      db_query("UPDATE {cc_auth} SET auth = 'admin church category',
          bezeichnung = 'Gemeindekalender administrieren' WHERE id =402");
      db_query("UPDATE {cc_auth} SET auth = 'admin group category',
          bezeichnung = 'Gruppenkalender administrieren' WHERE id =405");
      db_query("UPDATE {cc_auth} SET auth = 'admin personal category',
          bezeichnung = 'Persoenliche Kalender administrieren' WHERE id =406");
      
      // Drop old userid-col
      db_query("ALTER TABLE {cdb_log} DROP userid");
      db_query("INSERT INTO  {cc_config} (name, value) VALUES ('timezone', 'Europe/Berlin')");

      // Gives the admin the permission to edit categories. Cause 404 has admindarfsehen_yn=0 
      db_query("INSERT INTO {cc_domain_auth} VALUES('person', 1, 404, 1)");
      db_query("INSERT INTO {cc_domain_auth} VALUES('person', 1, 404, 2)");
      db_query("INSERT INTO {cc_domain_auth} VALUES('person', 1, 404, 3)");
      set_version("2.42");

    case '2.42':
      db_query("update {cdb_nationalitaet} set bezeichnung='Elfenbeink&uuml;ste' where bezeichnung='Elfenbeink?ste'");
      db_query("update {cdb_nationalitaet} set bezeichnung='Gro&szlig;britannien' where bezeichnung='Gro?britannien'");
      db_query("update {cdb_nationalitaet} set bezeichnung='Pal&auml;stinensische Gebiete' where bezeichnung='Pal?stinensische Gebiete'");
      db_query("update {cdb_nationalitaet} set bezeichnung='Rum&auml;nien' where bezeichnung='Rum?nien'");
      db_query("update {cdb_nationalitaet} set bezeichnung='S&atilde;o Tom&eacute; und Pr&iacute;ncipe' where bezeichnung='S?o Tom? und Pr?ncipe'");
      db_query("update {cdb_nationalitaet} set bezeichnung='Wei&szlig;russland' where bezeichnung='Wei?russland'");
      db_query("update {cc_config} set value=1 where name='cronjob_dbdump' and value=0");
      db_query("update {cc_config} set value=3600 where name='cronjob_delay' and value=0");
      set_version("2.43");
      
    case '2.43':
      db_query("ALTER TABLE {cs_event} ADD created_by_template_id INT( 11 ) NULL");
      set_version("2.44");
      
    case '2.44':
      set_version("2.45");
      
    case '2.45':
      // Throuh an error in the update in 2.42, the value is 60, that doesnt make sense...
      db_query("update {cc_config} set value=3600 where name='cronjob_delay' and value=60");
      db_query("INSERT INTO {cdb_feldkategorie} (id , bezeichnung , intern_code , db_tabelle , id_name)
      VALUES ( '5',  'Bereich',  'f_dep',  'cdb_bereich',  'id');");      
      db_query("INSERT INTO  {cdb_feld} (id, feldkategorie_id , feldtyp_id , db_spalte , 
         db_stammdatentabelle , aktiv_yn , inneuerstellen_yn , langtext , kurztext , zeilenende , 
         autorisierung , laenge , sortkey )
         VALUES (
         NULL ,  '5',  '2',  'bereich_id',  'dep',  '1',  '0',  'Bereich',  'Bereich',  '<br/>', NULL , NULL , 1
      )");    
      set_version("2.46");
      
    case '2.46':
      db_query("ALTER TABLE {cc_wikicategory} ADD in_menu_yn INT( 1 ) NOT NULL DEFAULT '1'");
      db_query("UPDATE {cdb_feld} set autorisierung='viewalldetails || leader' where autorisierung='ViewAllDetailsOrPersonLeader'");      
      
      db_query("CREATE TABLE {cc_notification} (
          id int(11) NOT NULL AUTO_INCREMENT,
          domain_type varchar(20) NOT NULL,
          domain_id int(11) NOT NULL,
          person_id int(11) NOT NULL,
          notificationtype_id int(11) NOT NULL,
          lastsenddate datetime DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY domain_type (domain_type,domain_id,person_id)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ");
      
      db_query("CREATE TABLE {cc_notificationtype} (
          id int(11) NOT NULL AUTO_INCREMENT,
          bezeichnung varchar(40) NOT NULL,
          delay_hours int(11) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
      
      db_query("INSERT INTO {cc_notificationtype} (bezeichnung, delay_hours) VALUES
        ('sofort', 0),
        ('alle 3 Stunden', 3),
        ('jeden Tag', 24),
        ('alle 3 Tage', 72)
        ");
      
      db_query("CREATE TABLE {cc_meetingrequest} (
          id int(11) NOT NULL AUTO_INCREMENT,
          cal_id int(11) NOT NULL,
          person_id int(11) NOT NULL,
          event_date datetime NOT NULL,
          zugesagt_yn int(1) DEFAULT NULL,
          mailsend_date datetime DEFAULT NULL,
          response_date datetime DEFAULT NULL,
          PRIMARY KEY (id),
          UNIQUE KEY cal_id (cal_id,person_id,event_date)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
      
      // Fix bug when person_id differs from id
      db_query("UPDATE {cdb_feldkategorie} SET id_name = 'person_id' WHERE intern_code='f_category'");

      // Copy permission from viewalldata to new perm: complex filter
      db_query("insert into {cc_domain_auth} (
        select domain_type, domain_id, 120, null from {cc_domain_auth} where auth_id=102
        group by domain_id, domain_type)");
      
      db_query("ALTER TABLE {cs_agenda} ADD final_yn INT( 1 ) NOT NULL DEFAULT '0' AFTER series");
      
      set_version("2.47");
    case '2.47': 
      db_query("ALTER TABLE {cs_service} ADD cal_text_template VARCHAR( 255 ) NULL AFTER allowtonotebyconfirmation_yn");
      // Fix bug when events was created with repeat function in ChurchService
      db_query("update {cc_cal} set enddate=date_add(startdate, interval 1 hour) where datediff(startdate, enddate)>0");
      // Add new report tables
      db_query("CREATE TABLE {crp_person} (
        date date NOT NULL,
        status_id int(11) NOT NULL,
        station_id int(11) NOT NULL,
        newperson_count int(11) NOT NULL,
        count int(11) NOT NULL,
        PRIMARY KEY (date,status_id,station_id)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
      
      db_query("CREATE TABLE {crp_group} (
        date date NOT NULL,
        gruppe_id int(11) NOT NULL,
        status_id int(11) NOT NULL,
        station_id int(11) NOT NULL,
        gruppenteilnehmerstatus_id int(11) NOT NULL,
        newperson_count int(11) NOT NULL,
        count int(11) NOT NULL,
        PRIMARY KEY (date,gruppe_id,status_id,station_id,gruppenteilnehmerstatus_id)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
      
      set_version("2.48");
    }
      
    
	  
    $a=db_query("select * from {cc_config} where name='version'",null,false);
    $software_version=$a->fetch()->value;
    
    $link=' <a href="https://intern.churchtools.de/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:changelog/" target="_clean">Neuigkeiten anschauen</a>';
    
    if ($db_version == "nodb")
      addInfoMessage("Datenbankupdates ausgef&uuml;hrt auf v$software_version.");
    else
      addInfoMessage("Datenbankupdates ausgef&uuml;hrt von ".variable_get("site_name")." v$db_version auf v$software_version. $link");
    cleanI18nFiles();
    $sitename=$config["site_name"];
    churchcore_systemmail($config["site_mail"], "Neue Version auf ".$config["site_name"], 
        "Datenbankupdates ausgef&uuml;hrt von ".variable_get("site_name")."' v$db_version auf v$software_version. $link<br/><br/>".
           "<a href=\"$base_url\" class=\"btn\">$sitename aufrufen</a>", true);
    if (userLoggedIn()) {
      $user=$_SESSION["user"];
      $user->auth=getUserAuthorization($user->id);
      $_SESSION["user"]=$user;
    }
    return true;
}

?>
