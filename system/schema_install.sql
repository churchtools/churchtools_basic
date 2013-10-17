-- phpMyAdmin SQL Dump
-- version 3.3.9.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 30. August 2013 um 16:01
-- Server Version: 5.5.9
-- PHP-Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `bootstrap_testpro`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_auth`
--

CREATE TABLE `cc_auth` (
  `id` int(11) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `modulename` varchar(255) NOT NULL,
  `datenfeld` varchar(255) DEFAULT NULL,
  `bezeichnung` varchar(255) NOT NULL,
  `admindarfsehen_yn` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_auth`
--

INSERT INTO `cc_auth` VALUES(1, 'administer settings', 'churchcore', NULL, 'Admin-Einstellungen anpassen', 1);
INSERT INTO `cc_auth` VALUES(2, 'administer persons', 'churchcore', NULL, 'Berechtigungen setzen, löschen und Benutzer simulieren', 1);
INSERT INTO `cc_auth` VALUES(3, 'view logfile', 'churchcore', NULL, 'Logfile einsehen', 1);
INSERT INTO `cc_auth` VALUES(4, 'view whoisonline', 'churchcore', NULL, 'Sieht auf der Startseite, wer aktuell online ist', 1);
INSERT INTO `cc_auth` VALUES(101, 'view', 'churchdb', NULL, 'Anwendung ChurchDB sehen', 1);
INSERT INTO `cc_auth` VALUES(102, 'view alldata', 'churchdb', 'cdb_bereich', 'Alle Datensätze des jeweiligen Bereiches einsehen', 1);
INSERT INTO `cc_auth` VALUES(103, 'view alldetails', 'churchdb', NULL, 'Alle Informationen der Person sehen, inkl. Adressdaten, Gruppenzuordnung, etc.', 1);
INSERT INTO `cc_auth` VALUES(104, 'view group statistics', 'churchdb', NULL, 'Gruppenstatistik einsehen', 1);
INSERT INTO `cc_auth` VALUES(105, 'view address', 'churchdb', NULL, 'Darf die Adressdaten einsehen', 1);
INSERT INTO `cc_auth` VALUES(106, 'view statistics', 'churchdb', NULL, 'Gesamtstatistik einsehen', 1);
INSERT INTO `cc_auth` VALUES(107, 'view tags', 'churchdb', NULL, 'Tags einsehen', 1);
INSERT INTO `cc_auth` VALUES(108, 'view history', 'churchdb', NULL, 'Historie eines Datensatzes ansehen', 1);
INSERT INTO `cc_auth` VALUES(109, 'edit relations', 'churchdb', NULL, 'Beziehungen editieren', 1);
INSERT INTO `cc_auth` VALUES(110, 'edit groups', 'churchdb', NULL, 'Gruppenzuordnungen editieren', 1);
INSERT INTO `cc_auth` VALUES(111, 'write access', 'churchdb', NULL, 'Schreibzugriff auf bestimmen Bereich', 1);
INSERT INTO `cc_auth` VALUES(112, 'export data', 'churchdb', NULL, 'Daten exportieren', 1);
INSERT INTO `cc_auth` VALUES(113, 'view comments', 'churchdb', 'cdb_comment_viewer', 'Kommentare einsehen', 1);
INSERT INTO `cc_auth` VALUES(114, 'administer groups', 'churchdb', NULL, 'Gruppen erstellen, löschen, etc.', 1);
INSERT INTO `cc_auth` VALUES(115, 'view group', 'churchdb', 'cdb_gruppe', 'View-Rechte auf andere Gruppen', 0);
INSERT INTO `cc_auth` VALUES(116, 'view archive', 'churchdb', NULL, 'View-Rechte auf das Personen-Archiv', 1);
INSERT INTO `cc_auth` VALUES(117, 'send sms', 'churchdb', NULL, 'Darf die SMS-Schnittstelle verwenden', 1);
INSERT INTO `cc_auth` VALUES(121, 'view birthdaylist', 'churchdb', NULL, 'Geburtagsliste einsehen', 1);
INSERT INTO `cc_auth` VALUES(122, 'view memberliste', 'churchdb', NULL, 'Mitgliederliste einsehen', 1);
INSERT INTO `cc_auth` VALUES(199, 'edit masterdata', 'churchdb', NULL, 'Stammdaten editieren', 1);
INSERT INTO `cc_auth` VALUES(201, 'view', 'churchresource', NULL, 'Anwendung ChurchResource sehen', 1);
INSERT INTO `cc_auth` VALUES(202, 'administer bookings', 'churchresource', NULL, 'Anfragen editieren, ablehen, etc.', 1);
INSERT INTO `cc_auth` VALUES(299, 'edit masterdata', 'churchresource', NULL, 'Stammdaten editieren', 1);
INSERT INTO `cc_auth` VALUES(301, 'view', 'churchservice', NULL, 'Anwendung ChurchService sehen', 1);
INSERT INTO `cc_auth` VALUES(302, 'view history', 'churchservice', NULL, 'Historie anschauen', 1);
INSERT INTO `cc_auth` VALUES(303, 'edit events', 'churchservice', NULL, 'Events erstellen, löschen, etc.', 1);
INSERT INTO `cc_auth` VALUES(304, 'view servicegroup', 'churchservice', 'cs_servicegroup', 'Dienstanfragen der jeweiligen Gruppe einsehen', 1);
INSERT INTO `cc_auth` VALUES(305, 'edit servicegroup', 'churchservice', 'cs_servicegroup', 'Dienstanfragen der jeweiligen Gruppe editieren', 1);
INSERT INTO `cc_auth` VALUES(306, 'create bookings', 'churchresource', NULL, 'Erstelle eigene Anfragen', 1);
INSERT INTO `cc_auth` VALUES(307, 'manage absent', 'churchservice', NULL, 'Abwesenheiten einsehen und pflegen', 1);
INSERT INTO `cc_auth` VALUES(308, 'edit facts', 'churchservice', NULL, 'Fakten pflegen', 1);
INSERT INTO `cc_auth` VALUES(311, 'view song', 'churchservice', NULL, 'Darf die Songs anschauen und Dateien herunterladen', 1);
INSERT INTO `cc_auth` VALUES(312, 'edit song', 'churchservice', NULL, 'Darf die Songs editieren und Dateien hochladen', 1);
INSERT INTO `cc_auth` VALUES(313, 'view songcategory', 'churchservice', 'cs_songcategory', 'Erlaubt den Zugriff auf bestimmte Song-Kategorien', 1);
INSERT INTO `cc_auth` VALUES(399, 'edit masterdata', 'churchservice', NULL, 'Stammdaten editieren', 1);
INSERT INTO `cc_auth` VALUES(401, 'view', 'churchcal', NULL, 'ChurchCal sehen', 1);
INSERT INTO `cc_auth` VALUES(402, 'edit events', 'churchcal', NULL, 'Termine pflegen', 1);
INSERT INTO `cc_auth` VALUES(403, 'view category', 'churchcal', 'cc_calcategory', 'Darf bestimmte Kalender einsehen', 1);
INSERT INTO `cc_auth` VALUES(404, 'edit category', 'churchcal', 'cc_calcategory', 'Darf bestimmte Kalender anpassen', 1);
INSERT INTO `cc_auth` VALUES(501, 'view', 'churchwiki', NULL, 'Darf das Wiki sehen', 1);
INSERT INTO `cc_auth` VALUES(502, 'view category', 'churchwiki', 'cc_wikicategory', 'Darf bestimmte Wiki-Kategorien einsehen', 1);
INSERT INTO `cc_auth` VALUES(503, 'edit category', 'churchwiki', 'cc_wikicategory', 'Darf bestimmte Wiki-Kategorien editieren', 1);
INSERT INTO `cc_auth` VALUES(599, 'edit masterdata', 'churchwiki', NULL, 'Darf die Stammdaten editieren', 1);
INSERT INTO `cc_auth` VALUES(601, 'view', 'churchcheckin', NULL, 'Darf die Checkin-Anwendung nutzen', 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_cal`
--

CREATE TABLE `cc_cal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(255) NOT NULL,
  `ort` varchar(255) NOT NULL DEFAULT '',
  `notizen` varchar(255) NOT NULL DEFAULT '',
  `intern_yn` int(1) NOT NULL DEFAULT '0',
  `startdate` datetime NOT NULL,
  `enddate` datetime NOT NULL,
  `old_category_id` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL,
  `repeat_id` int(1) NOT NULL,
  `repeat_frequence` int(2) DEFAULT NULL,
  `repeat_until` datetime DEFAULT NULL,
  `repeat_option_id` int(11) DEFAULT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_cal`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_calcategory`
--

CREATE TABLE `cc_calcategory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(100) NOT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  `color` varchar(20) DEFAULT NULL,
  `oeffentlich_yn` int(1) NOT NULL DEFAULT '0',
  `privat_yn` int(1) NOT NULL DEFAULT '0',
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bezeichnung_per_user` (`bezeichnung`,`modified_pid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Daten für Tabelle `cc_calcategory`
--

INSERT INTO `cc_calcategory` VALUES(1, 'Sonstige Veranstaltung', 8, NULL, 1, 0, '2013-08-30 00:00:00', -1);
INSERT INTO `cc_calcategory` VALUES(2, 'Sontagsgodis', 1, NULL, 1, 0, '2013-08-30 00:00:00', -1);
INSERT INTO `cc_calcategory` VALUES(3, 'Jugend', 19, NULL, 1, 0, '2013-08-30 00:00:00', -1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_cal_add`
--

CREATE TABLE `cc_cal_add` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cal_id` int(11) NOT NULL,
  `add_date` datetime NOT NULL,
  `with_repeat_yn` int(1) NOT NULL DEFAULT '1',
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_cal_add`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_cal_except`
--

CREATE TABLE `cc_cal_except` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cal_id` int(11) NOT NULL,
  `except_date_start` datetime NOT NULL,
  `except_date_end` datetime NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_cal_except`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_comment`
--

CREATE TABLE `cc_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_type` varchar(30) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_type` (`domain_type`,`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_comment`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_config`
--

CREATE TABLE `cc_config` (
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_config`
--

INSERT INTO `cc_config` VALUES('accept_datasecurity', '0');
INSERT INTO `cc_config` VALUES('churchcal_inmenu', '1');
INSERT INTO `cc_config` VALUES('churchcal_name', 'ChurchCal');
INSERT INTO `cc_config` VALUES('churchcal_sortcode', '6');
INSERT INTO `cc_config` VALUES('churchcal_startbutton', '1');
INSERT INTO `cc_config` VALUES('churchcheckin_inmenu', '1');
INSERT INTO `cc_config` VALUES('churchcheckin_name', 'Checkin');
INSERT INTO `cc_config` VALUES('churchcheckin_sortcode', '1');
INSERT INTO `cc_config` VALUES('churchcheckin_startbutton', '1');
INSERT INTO `cc_config` VALUES('churchdb_birthdaylist_station', '1,2,3');
INSERT INTO `cc_config` VALUES('churchdb_birthdaylist_status', '1,2,3');
INSERT INTO `cc_config` VALUES('churchdb_emailseparator', ';');
INSERT INTO `cc_config` VALUES('churchdb_groupnotchoosable', '90');
INSERT INTO `cc_config` VALUES('churchdb_home_lat', '53.568537');
INSERT INTO `cc_config` VALUES('churchdb_home_lng', '10.03656');
INSERT INTO `cc_config` VALUES('churchdb_inmenu', '1');
INSERT INTO `cc_config` VALUES('churchdb_mailchimp_apikey', '');
INSERT INTO `cc_config` VALUES('churchdb_maxexporter', '250');
INSERT INTO `cc_config` VALUES('churchdb_memberlist_station', '1,2,3');
INSERT INTO `cc_config` VALUES('churchdb_memberlist_status', '1,2,3');
INSERT INTO `cc_config` VALUES('churchdb_name', 'ChurchDB');
INSERT INTO `cc_config` VALUES('churchdb_sendgroupmails', '1');
INSERT INTO `cc_config` VALUES('churchdb_smspromote_apikey', '');
INSERT INTO `cc_config` VALUES('churchdb_sortcode', '2');
INSERT INTO `cc_config` VALUES('churchdb_startbutton', '1');
INSERT INTO `cc_config` VALUES('churchresource_entries_last_days', '90');
INSERT INTO `cc_config` VALUES('churchresource_inmenu', '1');
INSERT INTO `cc_config` VALUES('churchresource_name', 'ChurchResource');
INSERT INTO `cc_config` VALUES('churchresource_sortcode', '3');
INSERT INTO `cc_config` VALUES('churchresource_startbutton', '1');
INSERT INTO `cc_config` VALUES('churchservice_entries_last_days', '90');
INSERT INTO `cc_config` VALUES('churchservice_inmenu', '1');
INSERT INTO `cc_config` VALUES('churchservice_name', 'ChurchService');
INSERT INTO `cc_config` VALUES('churchservice_openservice_rememberdays', '3');
INSERT INTO `cc_config` VALUES('churchservice_reminderhours', '24');
INSERT INTO `cc_config` VALUES('churchservice_sortcode', '4');
INSERT INTO `cc_config` VALUES('churchservice_startbutton', '1');
INSERT INTO `cc_config` VALUES('churchwiki_inmenu', '1');
INSERT INTO `cc_config` VALUES('churchwiki_name', 'ChurchWiki');
INSERT INTO `cc_config` VALUES('churchwiki_sortcode', '5');
INSERT INTO `cc_config` VALUES('churchwiki_startbutton', '1');
INSERT INTO `cc_config` VALUES('cronjob_dbdump', '1');
INSERT INTO `cc_config` VALUES('cronjob_delay', '60');
INSERT INTO `cc_config` VALUES('currently_mail_sending', '0');
INSERT INTO `cc_config` VALUES('last_cron', '1377871248');
INSERT INTO `cc_config` VALUES('last_db_dump', '1377868285');
INSERT INTO `cc_config` VALUES('login_message', 'Willkommen auf dem neuen ChurchTools 2.0. Zum Anmelden bitte die Felder ausfüllen!');
INSERT INTO `cc_config` VALUES('mail_enabled', '1');
INSERT INTO `cc_config` VALUES('max_uploadfile_size_kb', '10000');
INSERT INTO `cc_config` VALUES('show_remember_me', '1');
INSERT INTO `cc_config` VALUES('site_mail', 'admin@gmail.com');
INSERT INTO `cc_config` VALUES('site_name', 'ChurchTools 2.0');
INSERT INTO `cc_config` VALUES('site_offline', '0');
INSERT INTO `cc_config` VALUES('version', '2.36');
INSERT INTO `cc_config` VALUES('welcome', 'Herzlich willkommen');
INSERT INTO `cc_config` VALUES('welcome_subtext', 'Das ist die Startseite von ChurchTools 2.0');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_domain_auth`
--

CREATE TABLE `cc_domain_auth` (
  `domain_type` varchar(30) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `auth_id` int(11) NOT NULL,
  `daten_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_domain_auth`
--

INSERT INTO `cc_domain_auth` VALUES('status', 0, 403, 1);
INSERT INTO `cc_domain_auth` VALUES('status', 0, 403, 2);
INSERT INTO `cc_domain_auth` VALUES('status', 0, 403, 3);
INSERT INTO `cc_domain_auth` VALUES('status', 1, 403, 1);
INSERT INTO `cc_domain_auth` VALUES('status', 1, 403, 2);
INSERT INTO `cc_domain_auth` VALUES('status', 1, 403, 3);
INSERT INTO `cc_domain_auth` VALUES('status', 2, 403, 1);
INSERT INTO `cc_domain_auth` VALUES('status', 2, 403, 2);
INSERT INTO `cc_domain_auth` VALUES('status', 2, 403, 3);
INSERT INTO `cc_domain_auth` VALUES('status', 3, 403, 1);
INSERT INTO `cc_domain_auth` VALUES('status', 3, 403, 2);
INSERT INTO `cc_domain_auth` VALUES('status', 3, 403, 3);
INSERT INTO `cc_domain_auth` VALUES('person', -1, 401, NULL);
INSERT INTO `cc_domain_auth` VALUES('person', -1, 403, 2);
INSERT INTO `cc_domain_auth` VALUES('person', -1, 403, 3);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_file`
--

CREATE TABLE `cc_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_type` varchar(30) NOT NULL,
  `domain_id` varchar(30) NOT NULL,
  `bezeichnung` varchar(50) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_type` (`domain_type`,`domain_id`,`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_file`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_loginstr`
--

CREATE TABLE `cc_loginstr` (
  `person_id` int(11) NOT NULL,
  `loginstr` varchar(255) NOT NULL,
  `create_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_loginstr`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_mail_queue`
--

CREATE TABLE `cc_mail_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receiver` varchar(255) NOT NULL,
  `sender` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` blob NOT NULL,
  `htmlmail_yn` int(1) NOT NULL,
  `priority` int(1) NOT NULL DEFAULT '2',
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  `send_date` datetime DEFAULT NULL,
  `error` int(11) DEFAULT '0',
  `reading_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_mail_queue`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_printer`
--

CREATE TABLE `cc_printer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(50) NOT NULL,
  `ort` varchar(50) NOT NULL,
  `active_yn` int(1) NOT NULL DEFAULT '0',
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bezeichnung` (`bezeichnung`,`ort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_printer`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_printer_queue`
--

CREATE TABLE `cc_printer_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `printer_id` int(11) NOT NULL,
  `data` blob NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cc_printer_queue`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_repeat`
--

CREATE TABLE `cc_repeat` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(30) NOT NULL,
  `sortkey` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_repeat`
--

INSERT INTO `cc_repeat` VALUES(0, 'Keine Wiederholung', 0);
INSERT INTO `cc_repeat` VALUES(1, 'T&auml;glich', 1);
INSERT INTO `cc_repeat` VALUES(7, 'W&ouml;chentlich', 2);
INSERT INTO `cc_repeat` VALUES(31, 'Monatlich nach Datum', 3);
INSERT INTO `cc_repeat` VALUES(32, 'Monatlich nach Wochentag', 4);
INSERT INTO `cc_repeat` VALUES(365, 'J&auml;hrlich', 5);
INSERT INTO `cc_repeat` VALUES(999, 'Manuell', 6);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_session`
--

CREATE TABLE `cc_session` (
  `person_id` int(11) NOT NULL,
  `session` varchar(100) NOT NULL,
  `hostname` varchar(100) NOT NULL,
  `datum` datetime NOT NULL,
  PRIMARY KEY (`person_id`,`session`,`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_session`
--

INSERT INTO `cc_session` VALUES(1, 'W2dhlw9RE38owo9ri43h', 'localhost:8888', '2013-08-30 16:01:05');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_usermails`
--

CREATE TABLE `cc_usermails` (
  `person_id` int(11) NOT NULL,
  `mailtype` varchar(255) NOT NULL,
  `domain_id` int(11) NOT NULL,
  `letzte_mail` datetime NOT NULL,
  PRIMARY KEY (`person_id`,`mailtype`,`domain_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_usermails`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_usersettings`
--

CREATE TABLE `cc_usersettings` (
  `person_id` int(11) NOT NULL,
  `modulename` varchar(50) NOT NULL,
  `attrib` varchar(100) NOT NULL,
  `value` varchar(8192) DEFAULT NULL,
  PRIMARY KEY (`person_id`,`modulename`,`attrib`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_usersettings`
--

INSERT INTO `cc_usersettings` VALUES(1, 'churchcal', 'filterMeineKalender', '[104]');
INSERT INTO `cc_usersettings` VALUES(1, 'churchcal', 'viewName', 'month');
INSERT INTO `cc_usersettings` VALUES(1, 'churchdb', 'churchdbInitView', 'PersonView');
INSERT INTO `cc_usersettings` VALUES(1, 'churchdb', 'selectedGroupType', '1');
INSERT INTO `cc_usersettings` VALUES(1, 'churchservice', 'lastVisited', '2013-08-30 15:53');
INSERT INTO `cc_usersettings` VALUES(1, 'churchservice', 'remindMe', '1');
INSERT INTO `cc_usersettings` VALUES(736, 'churchcal', 'viewName', 'month');
INSERT INTO `cc_usersettings` VALUES(736, 'churchservice', 'lastVisited', '2013-04-18 8:27');
INSERT INTO `cc_usersettings` VALUES(736, 'churchservice', 'remindMe', '1');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_wiki`
--

CREATE TABLE `cc_wiki` (
  `doc_id` varchar(255) NOT NULL,
  `version_no` int(11) NOT NULL DEFAULT '1',
  `wikicategory_id` int(11) NOT NULL DEFAULT '0',
  `text` mediumblob NOT NULL,
  `auf_startseite_yn` int(1) NOT NULL DEFAULT '0',
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`doc_id`,`version_no`,`wikicategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_wiki`
--

INSERT INTO `cc_wiki` VALUES('main', 1, 0, 0x3c68323ee2808b3c7374726f6e673e57617320697374206461732057696b693f3c2f7374726f6e673e3c2f68323e0a0a3c703e3c7370616e207374796c653d5c22666f6e742d73697a653a313470785c223e443c2f7370616e3e3c696d6720616c743d5c225c22207372633d5c22687474703a2f2f696e7465726e2e636875726368746f6f6c732e64652f73797374656d2f6173736574732f696d672f77696b695f6c6f676f2e706e675c22207374796c653d5c22666c6f61743a72696768743b206865696768743a32373070783b2077696474683a33303070785c22202f3e3c7370616e207374796c653d5c22666f6e742d73697a653a313470785c223e61732057696b6920736f6c6c20616c7320446f6b756d656e746174696f6e2c20496e666f726d6174696f6e732d20756e6420417262656974736772756e646c61676520662675756d6c3b722064696520766572736368696564656e656e204469656e73746265726569636865206465722047656d65696e6465206469656e656e2e204a65646572204d697461726265697465722065696e6573204469656e7374626572656963686573206b616e6e206175662057756e736368205a756772696666206175662064696520656e74737072656368656e64656e2057696b692d4b617465676f7269656e20657268616c74656e2e2044696573652053656974656e206b266f756d6c3b6e6e656e2064616e6e266e6273703b766f6e20616c6c656e206175732064656d73656c62656e204469656e7374626572656963682067656c6573656e20756e6420626561726265697465742077657264656e2e20536f206b266f756d6c3b6e6e656e20616b7475656c6c6520496e666f726d6174696f6e2c2041626c2661756d6c3b7566652c2045696e7374656c6c756e67656e2c206574632e207a6569746e61682067657370656963686572742077657264656e20756e642073696e6420736f666f727420662675756d6c3b7220616c6c652065696e7365686261722e2044616d697420697374206a65646572207a75206a656465727a656974206175662064656d206e65757374656e2057697373656e7374616e642e3c2f7370616e3e3c2f703e0a0a3c6469763e3c7370616e207374796c653d5c22666f6e742d73697a653a313470785c223e4475726368206461732057696b6920686162656e206e657565204d6974617262656974657220616c6c65206e266f756d6c3b746967656e20496e666f726d6174696f6e656e2c20416e6c656974756e67656e20756e642048696e7465726772756e64696e666f726d6174696f6e656e20662675756d6c3b7220696872656e204469656e73742e20457266616872656e65204d69746172626569746572206b266f756d6c3b6e6e656e206968722057697373656e20756e6420676573616d6d656c746520496e666f726d6174696f6e656e20646f6b756d656e74696572656e20756e642061756620736965207a75722675756d6c3b636b6772656966656e2e3c2f7370616e3e3c2f6469763e0a0a3c68323e5765697465726520496e666f733c2f68323e0a0a3c6469763e3c7370616e207374796c653d5c22666f6e742d73697a653a313470785c223e4d65687220496e666f73207a756d2057696b692067696274206573203c6120687265663d5c22687474703a2f2f696e7465726e2e636875726368746f6f6c732e64652f3f713d63687572636877696b692357696b69566965772f66696c74657257696b6963617465676f72795f69643a302f646f633a43687572636857696b692f5c22207461726765743d5c225f626c616e6b5c223e686965723c2f613e2e3c2f7370616e3e3c2f6469763e0a, 0, '2013-08-30 15:59:42', 1);
INSERT INTO `cc_wiki` VALUES('Sicherheitsbestimmungen', 1, 0, 0x3c703e3c7374726f6e673e56657270666c69636874756e67206175662064617320446174656e67656865696d6e69732067656d2661756d6c3b26737a6c69673b2026736563743b20352042756e646573646174656e73636875747a67657365747a202842445347292c2061756620646173204665726e6d656c646567656865696d6e69732067656d2661756d6c3b26737a6c69673b2026736563743b2038382054656c656b6f6d6d756e696b6174696f6e7367657365747a2028544b472920756e64206175662057616872756e6720766f6e2047657363682661756d6c3b66747367656865696d6e697373656e3c2f7374726f6e673e3c6272202f3e0a3c6272202f3e0a48616c6c6f266e6273703b5b566f726e616d655d213c6272202f3e0a4469652070657273266f756d6c3b6e6c696368656e20446174656e20756e7365726572204d6974617262656974657220756e64204d6974676c696564657220776f6c6c656e20776972207363682675756d6c3b747a656e2e20446172756d2062697474656e2077697220446963682c2044696368206175662064617320446174656e67656865696d6e69732077696520666f6c6774207a752076657270666c69636874656e3a3c6272202f3e0a3c6272202f3e0a3c7374726f6e673e312e2056657270666c69636874756e67206175662064617320446174656e67656865696d6e6973206e6163682026736563743b203520424453473c2f7374726f6e673e3c6272202f3e0a4175666772756e6420766f6e2026736563743b2035204244534720697374206d697220756e746572736167742c20706572736f6e656e62657a6f67656e6520446174656e2c20646965206d6972206469656e73746c6963682062656b616e6e742077657264656e2c20756e626566756774207a75206572686562656e2c207a7520766572617262656974656e206f646572207a75206e75747a656e2e20446965732067696c7420736f776f686c20662675756d6c3b7220646965206469656e73746c6963686520542661756d6c3b7469676b65697420696e6e657268616c6220776965206175636820617526737a6c69673b657268616c6220287a2e422e20626569204b756e64656e20756e6420496e746572657373656e74656e292064657320556e7465726e65686d656e732f64657220426568266f756d6c3b7264652e3c6272202f3e0a4469652050666c69636874207a75722057616872756e672064657320446174656e67656865696d6e697373657320626c65696274206175636820696d2046616c6c652065696e6572205665727365747a756e67206f646572206e616368204265656e646967756e672064657320417262656974732d2f4469656e7374766572682661756d6c3b6c746e697373657320626573746568656e2e3c6272202f3e0a3c6272202f3e0a3c7374726f6e673e322e2056657270666c69636874756e672061756620646173204665726e6d656c646567656865696d6e69733c2f7374726f6e673e3c6272202f3e0a4175666772756e6420766f6e2026736563743b20383820544b472062696e20696368207a75722057616872756e6720646573204665726e6d656c646567656865696d6e69737365732076657270666c6963687465742c20736f2d20776569742069636820696d205261686d656e206d65696e657220542661756d6c3b7469676b65697420626569206465722045726272696e67756e672067657363682661756d6c3b6674736d2661756d6c3b26737a6c69673b696765722054656c656b6f6d6d756e696b6174696f6e736469656e737465206d69747769726b652e3c6272202f3e0a3c6272202f3e0a3c7374726f6e673e332e2056657270666c69636874756e67206175662057616872756e6720766f6e2047657363682661756d6c3b66747367656865696d6e697373656e3c2f7374726f6e673e3c6272202f3e0a2655756d6c3b62657220416e67656c6567656e68656974656e2064657320556e7465726e65686d656e732c2064696520626569737069656c7377656973652045696e7a656c68656974656e206968726572204f7267616e69736174696f6e20756e6420696872652045696e7269636874756e672062657472656666656e2c20736f776965202675756d6c3b6265722047657363682661756d6c3b667473766f72672661756d6c3b6e676520756e64205a61686c656e2064657320696e7465726e656e20526563686e756e6773776573656e732c206973742061756368206e616368204265656e646967756e67206465732041726265697473766572682661756d6c3b6c746e697373657320766f6e206d69722056657273636877696567656e68656974207a752077616872656e2c20736f6665726e20736965206e6963687420616c6c67656d65696e20266f756d6c3b6666656e746c6963682062656b616e6e74206765776f7264656e2073696e642e2048696572756e7465722066616c6c656e266e6273703b6175636820566f72672661756d6c3b6e676520766f6e204472697474756e7465726e65686d656e2c206d69742064656e656e20696368206469656e73746c69636820626566617373742062696e2e20417566206469652067657365747a6c692d206368656e2042657374696d6d756e67656e202675756d6c3b62657220756e6c6175746572656e205765747462657765726220777572646520696368206265736f6e646572732068696e676577696573656e2e3c6272202f3e0a416c6c65206469656e73746c6963686520542661756d6c3b7469676b656974656e2062657472656666656e64656e204175667a656963686e756e67656e2c20416273636872696674656e2c2047657363682661756d6c3b667473756e7465726c6167656e2c2041626c69636874756e67656e206469656e73746c6963686572206f6465722067657363682661756d6c3b66746c696368657220566f72672661756d6c3b6e67652c20646965206d6972202675756d6c3b6265726c617373656e206f64657220766f6e206d697220616e6765666572746967742077657264656e2c2073696e6420766f722045696e73696368746e61686d6520556e6265667567746572207a75207363682675756d6c3b747a656e2e3c6272202f3e0a3c6272202f3e0a566f6e2064696573656e2056657270666c69636874756e67656e206861626520696368204b656e6e746e69732067656e6f6d6d656e2e204963682062696e206d697220626577757373742c206461737320696368206d69636820626569205665726c65747a756e67656e2064657320446174656e67656865696d6e69737365732c20646573204665726e6d656c646567656865696d6e6973736573206f64657220766f6e2047657363682661756d6c3b66747367656865696d6e697373656e207374726166626172206d616368656e206b616e6e2c20696e736265736f6e64657265206e6163682026736563743b26736563743b2034342c203433204162732e203220424453472c2026736563743b2032303620537472616667657365747a627563682028537447422920756e64206e6163682026736563743b2031372047657365747a20676567656e2064656e20756e6c6175746572656e20576574746265776572622028555747292e3c2f703e, 0, '0000-00-00 00:00:00', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cc_wikicategory`
--

CREATE TABLE `cc_wikicategory` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(50) NOT NULL,
  `sortkey` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cc_wikicategory`
--

INSERT INTO `cc_wikicategory` VALUES(0, 'Standard', 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_bereich`
--

CREATE TABLE `cdb_bereich` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(20) NOT NULL,
  `kuerzel` varchar(10) NOT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_bereich`
--

INSERT INTO `cdb_bereich` VALUES(1, 'Gemeindeliste', 'G', 0);
INSERT INTO `cdb_bereich` VALUES(2, 'Jugendarbeit', 'J', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_bereich_person`
--

CREATE TABLE `cdb_bereich_person` (
  `bereich_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  PRIMARY KEY (`bereich_id`,`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_bereich_person`
--

INSERT INTO `cdb_bereich_person` VALUES(1, 1);
INSERT INTO `cdb_bereich_person` VALUES(1, 2);
INSERT INTO `cdb_bereich_person` VALUES(2, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_beziehung`
--

CREATE TABLE `cdb_beziehung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vater_id` int(11) NOT NULL,
  `kind_id` int(11) NOT NULL,
  `beziehungstyp_id` int(11) NOT NULL,
  `datum` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cdb_beziehung`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_beziehungstyp`
--

CREATE TABLE `cdb_beziehungstyp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bez_vater` varchar(20) NOT NULL,
  `bez_kind` varchar(20) NOT NULL,
  `bezeichnung` varchar(30) NOT NULL,
  `export_aggregation_yn` int(11) NOT NULL,
  `export_title` varchar(20) NOT NULL,
  `sortkey` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Daten für Tabelle `cdb_beziehungstyp`
--

INSERT INTO `cdb_beziehungstyp` VALUES(1, 'Elternteil', 'Kind', 'Elternteil/Kind', 0, '', 0);
INSERT INTO `cdb_beziehungstyp` VALUES(2, 'Ehepartner', 'Ehepartner', 'Ehepartner', 1, 'Ehepaar', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_comment`
--

CREATE TABLE `cdb_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relation_id` int(11) NOT NULL,
  `relation_name` varchar(20) NOT NULL,
  `text` text NOT NULL,
  `userid` varchar(20) NOT NULL,
  `person_id` int(11) NOT NULL DEFAULT '-1',
  `datum` datetime NOT NULL,
  `comment_viewer_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cdb_comment`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_comment_viewer`
--

CREATE TABLE `cdb_comment_viewer` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_comment_viewer`
--

INSERT INTO `cdb_comment_viewer` VALUES(0, 'Alle');
INSERT INTO `cdb_comment_viewer` VALUES(1, 'Distriktleiter');
INSERT INTO `cdb_comment_viewer` VALUES(2, 'Vorstand');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_distrikt`
--

CREATE TABLE `cdb_distrikt` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(30) NOT NULL,
  `gruppentyp_id` int(11) NOT NULL,
  `imageurl` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_distrikt`
--

INSERT INTO `cdb_distrikt` VALUES(1, 'Nord', 1, 'gruppe_schwarz.png');
INSERT INTO `cdb_distrikt` VALUES(2, 'Süd', 1, 'gruppe_gelb.png');
INSERT INTO `cdb_distrikt` VALUES(3, 'Ost', 1, 'gruppe_blau.png');
INSERT INTO `cdb_distrikt` VALUES(4, 'West', 1, 'gruppe_gruen.png');
INSERT INTO `cdb_distrikt` VALUES(5, 'Sommerfreizeiten', 4, NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_familienstand`
--

CREATE TABLE `cdb_familienstand` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_familienstand`
--

INSERT INTO `cdb_familienstand` VALUES(0, 'unbekannt');
INSERT INTO `cdb_familienstand` VALUES(1, 'ledig');
INSERT INTO `cdb_familienstand` VALUES(2, 'verheiratet');
INSERT INTO `cdb_familienstand` VALUES(3, 'getrennt');
INSERT INTO `cdb_familienstand` VALUES(4, 'geschieden');
INSERT INTO `cdb_familienstand` VALUES(5, 'verwitwet');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_feld`
--

CREATE TABLE `cdb_feld` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feldkategorie_id` int(11) NOT NULL,
  `feldtyp_id` int(11) NOT NULL,
  `db_spalte` varchar(50) NOT NULL,
  `db_stammdatentabelle` varchar(50) DEFAULT NULL,
  `aktiv_yn` int(1) NOT NULL DEFAULT '1',
  `langtext` varchar(200) NOT NULL,
  `kurztext` varchar(50) NOT NULL,
  `zeilenende` varchar(10) NOT NULL,
  `autorisierung` varchar(50) DEFAULT NULL,
  `laenge` int(3) DEFAULT NULL,
  `sortkey` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=56 ;

--
-- Daten für Tabelle `cdb_feld`
--

INSERT INTO `cdb_feld` VALUES(-1, 4, 2, 'fu_nachfolge_gruppenteilnehmerstatus_id', 'groupMemberTypes', 1, 'Followup-Nachfolger-Teilnehmerstatus', 'Followup-Nachfolger-Teilnehmerstatus', '<br/>', 'admingroups', 11, 5);
INSERT INTO `cdb_feld` VALUES(0, 1, 1, 'spitzname', NULL, 1, 'Spitzname', '', '(%) ', NULL, 30, 3);
INSERT INTO `cdb_feld` VALUES(1, 1, 1, 'titel', NULL, 1, 'Titel', '', '', NULL, 12, 1);
INSERT INTO `cdb_feld` VALUES(2, 1, 1, 'vorname', NULL, 1, 'Vorname', '', '&nbsp;', NULL, 30, 2);
INSERT INTO `cdb_feld` VALUES(3, 1, 1, 'name', NULL, 1, 'Name', '', '<br/>', NULL, 30, 4);
INSERT INTO `cdb_feld` VALUES(4, 1, 1, 'strasse', NULL, 1, 'Strasse', '', '<br/>', 'ViewAllDetailsOrPersonLeader', 30, 5);
INSERT INTO `cdb_feld` VALUES(5, 1, 1, 'zusatz', NULL, 1, 'Addresszusatz', '', '<br/>', 'ViewAllDetailsOrPersonLeader', 30, 6);
INSERT INTO `cdb_feld` VALUES(6, 1, 1, 'plz', NULL, 1, 'Postleitzahl', '', '&nbsp;', NULL, 6, 7);
INSERT INTO `cdb_feld` VALUES(7, 1, 1, 'ort', NULL, 1, 'Ort', '', '<br/>', NULL, 40, 8);
INSERT INTO `cdb_feld` VALUES(8, 1, 1, 'land', NULL, 1, 'Land', '', '<br/><br/>', NULL, 30, 9);
INSERT INTO `cdb_feld` VALUES(9, 1, 2, 'geschlecht_no', 'sex', 1, 'Geschlecht', 'Geschlecht', '<br/>', NULL, 11, 10);
INSERT INTO `cdb_feld` VALUES(10, 1, 1, 'telefonprivat', NULL, 1, 'Telefon privat', 'Tel. privat', '<br/>', NULL, 30, 11);
INSERT INTO `cdb_feld` VALUES(11, 1, 1, 'telefongeschaeftlich', NULL, 1, 'Telefon gesch&auml;ftl.', 'Tel. gesch&auml;ft.', '<br/>', NULL, 20, 12);
INSERT INTO `cdb_feld` VALUES(12, 1, 1, 'telefonhandy', NULL, 1, 'Mobil', 'Mobil', '<br/>', NULL, 20, 13);
INSERT INTO `cdb_feld` VALUES(13, 1, 1, 'fax', NULL, 1, 'Fax', 'Fax', '<br/>', NULL, 20, 14);
INSERT INTO `cdb_feld` VALUES(14, 1, 1, 'email', NULL, 1, 'E-Mail', 'E-Mail', '<br/>', NULL, 50, 15);
INSERT INTO `cdb_feld` VALUES(15, 1, 1, 'cmsuserid', NULL, 1, 'Benutzername', 'Benutzername', '<br/>', NULL, 50, 16);
INSERT INTO `cdb_feld` VALUES(16, 2, 3, 'geburtsdatum', NULL, 1, 'Geburtsdatum', 'Geburtsdatum', '<br/>', NULL, 0, 1);
INSERT INTO `cdb_feld` VALUES(17, 2, 1, 'geburtsname', NULL, 1, 'Geburtsname', 'Geburtsname', '<br/>', NULL, 30, 2);
INSERT INTO `cdb_feld` VALUES(18, 2, 1, 'geburtsort', NULL, 1, 'Geburtsort', 'Geburtsort', '<br/>', NULL, 30, 3);
INSERT INTO `cdb_feld` VALUES(19, 2, 1, 'beruf', NULL, 1, 'Beruf', 'Beruf', '<br/>', NULL, 50, 4);
INSERT INTO `cdb_feld` VALUES(20, 2, 2, 'nationalitaet_id', 'nationalitaet', 1, 'Nationalit&auml;t', 'Nationalit&auml;t', '<br/>', NULL, 11, 5);
INSERT INTO `cdb_feld` VALUES(21, 2, 2, 'familienstand_no', 'familyStatus', 1, 'Familenstand', 'Familenstand', '<br/>', NULL, 11, 6);
INSERT INTO `cdb_feld` VALUES(22, 2, 3, 'hochzeitsdatum', NULL, 1, 'Hochzeitstag', 'Hochzeitstag', '<br/><br/>', NULL, 0, 7);
INSERT INTO `cdb_feld` VALUES(23, 2, 3, 'erstkontakt', NULL, 1, 'Erstkontakt', 'Erstkontakt', '<br/>', NULL, 0, 8);
INSERT INTO `cdb_feld` VALUES(24, 2, 3, 'zugehoerig', NULL, 1, 'Zugeh&ouml;rig', 'Zugeh&ouml;rig', '<br/>', NULL, 0, 9);
INSERT INTO `cdb_feld` VALUES(25, 2, 3, 'eintrittsdatum', NULL, 1, 'Mitglied seit', 'Mitglied seit', '<br/>', NULL, 0, 10);
INSERT INTO `cdb_feld` VALUES(26, 2, 1, 'ueberwiesenvon', NULL, 1, '&Uuml;berwiesen von', '&Uuml;berwiesen von', '<br/>', NULL, 30, 11);
INSERT INTO `cdb_feld` VALUES(27, 2, 3, 'austrittsdatum', NULL, 1, 'Mitglied bis', 'Mitglied bis', '<br/>', NULL, 0, 12);
INSERT INTO `cdb_feld` VALUES(28, 2, 1, 'ueberwiesennach', NULL, 1, '&Uuml;berwiesen nach', '&Uuml;berwiesen nach', '<br/><br/>', NULL, 30, 13);
INSERT INTO `cdb_feld` VALUES(29, 2, 3, 'taufdatum', NULL, 1, 'Taufdatum', 'Taufdatum', '<br/>', NULL, 0, 14);
INSERT INTO `cdb_feld` VALUES(30, 2, 1, 'taufort', NULL, 1, 'Taufort', '', '<br/>', NULL, 50, 15);
INSERT INTO `cdb_feld` VALUES(31, 2, 1, 'getauftdurch', NULL, 1, 'Getauft durch', 'Getauft durch', '<br/>', NULL, 50, 16);
INSERT INTO `cdb_feld` VALUES(32, 3, 2, 'status_id', 'status', 1, 'Status', 'Status', '<br/>', NULL, 11, 1);
INSERT INTO `cdb_feld` VALUES(33, 3, 2, 'station_id', 'station', 1, 'Station', 'Station', '<br/>', NULL, 11, 2);
INSERT INTO `cdb_feld` VALUES(34, 4, 1, 'bezeichnung', NULL, 1, 'Bezeichnung', 'Bezeichnung', '<br/>', NULL, 35, 1);
INSERT INTO `cdb_feld` VALUES(35, 4, 2, 'distrikt_id', 'districts', 1, 'Distrikt', 'Distrikt', '<br/>', 'admingroups', 11, 2);
INSERT INTO `cdb_feld` VALUES(36, 4, 2, 'followup_typ_id', 'followupTypes', 1, 'Followup-Typ', 'Followup-Typ', '<br/>', 'admingroups', 11, 3);
INSERT INTO `cdb_feld` VALUES(37, 4, 2, 'fu_nachfolge_typ_id', 'FUNachfolgeDomains', 1, 'Followup-Nachfolger', 'Followup-Nachfolger', '<br/>', 'admingroups', 11, 4);
INSERT INTO `cdb_feld` VALUES(38, 4, 2, 'fu_nachfolge_objekt_id', 'code:selectNachfolgeObjektId', 1, 'Followup-Nachfolger-Auswahl', 'Followup-Nachfolger-Auswahl', '<br/>', 'admingroups', 11, 5);
INSERT INTO `cdb_feld` VALUES(39, 4, 3, 'gruendungsdatum', NULL, 1, 'Gr&uuml;ndungsdatum', 'Gr&uuml;ndungsdatum', '<br/>', NULL, 0, 6);
INSERT INTO `cdb_feld` VALUES(40, 4, 3, 'abschlussdatum', NULL, 1, 'Abschlussdatum', 'Abschlussdatum', '<br/>', NULL, 0, 7);
INSERT INTO `cdb_feld` VALUES(41, 4, 1, 'treffzeit', NULL, 1, 'Zeit des Treffens', 'Treffzeit', '<br/>', NULL, 30, 8);
INSERT INTO `cdb_feld` VALUES(42, 4, 1, 'treffpunkt', NULL, 1, 'Ort des Treffens', 'Treffort', '<br/>', NULL, 50, 9);
INSERT INTO `cdb_feld` VALUES(43, 4, 1, 'treffname', NULL, 1, 'Treffen bei', 'Treffen bei', '<br/>', NULL, 30, 10);
INSERT INTO `cdb_feld` VALUES(44, 4, 1, 'zielgruppe', NULL, 1, 'Zielgruppe', 'Zielgruppe', '<br/>', NULL, 30, 11);
INSERT INTO `cdb_feld` VALUES(45, 4, 5, 'notiz', NULL, 1, 'Notiz', 'Notiz', '<br/>', NULL, 200, 12);
INSERT INTO `cdb_feld` VALUES(46, 4, 4, 'valid_yn', NULL, 1, '<p>Gruppe ausw&auml;hlbar<br/><small>Bei Verneinung kann die Gruppe nicht mehr zugeordnet und gefiltert werden</small>', 'Ausw&auml;hlbar', '<br/>', 'admingroups', 1, 13);
INSERT INTO `cdb_feld` VALUES(47, 4, 4, 'versteckt_yn', NULL, 1, '<p>Versteckte Gruppe<br/><small>Gruppe ist nur f&uuml;r Gruppenadmins & Leiter sichtbar</small>', 'Versteckt', '<br/>', 'admingroups', 1, 14);
INSERT INTO `cdb_feld` VALUES(48, 4, 4, 'instatistik_yn', NULL, 1, '<p>Zeige in Statistik<br/><small>In der Statistik explizit aufgef&uuml;hrt</small>', 'In Statistik', '<br/>', 'admingroups', 1, 15);
INSERT INTO `cdb_feld` VALUES(49, 4, 4, 'treffen_yn', NULL, 1, '<p>W&ouml;chentliche Teilnahme pflegen<br/><small>Erm&ouml;glicht die Pflege der Teilnahme an dieser Gruppe</small>', 'Teilnahme', '<br/>', 'admingroups', 1, 16);
INSERT INTO `cdb_feld` VALUES(50, 1, 1, 'optigem_nr', NULL, 1, 'Optigem-Nr', 'Optigem-Nr.', '<br/>', 'admin', NULL, 17);
INSERT INTO `cdb_feld` VALUES(51, 4, 6, 'max_teilnehmer', NULL, 1, 'Maximale Teilnehmer', 'Max. Teilnehmer', '<br/>', NULL, 11, 2);
INSERT INTO `cdb_feld` VALUES(52, 4, 4, 'oeffentlich_yn', NULL, 1, '<p>&Ouml;ffentliche Gruppe<br/><small>Die Info-Daten der Gruppe kann ohne Autorisierung eingesehen werden', '&Ouml;ffentlich', '<br/>', 'admingroups', 1, 17);
INSERT INTO `cdb_feld` VALUES(53, 4, 4, 'offen_yn', NULL, 1, '<p>Offene Gruppe<br/><small>Man kann eine Teilnehmeranfrage an diese Gruppe stellen', '&Offen', '<br/>', 'admingroups', 1, 18);
INSERT INTO `cdb_feld` VALUES(54, 4, 4, 'mail_an_leiter_yn', NULL, 1, '<p>Leiter informieren<br/><small>(Co-)Leiter und Supverisor bekommen E-Mails bei &Auml;nderungen in der Gruppe', '&Leiter informieren', '<br/>', 'admingroups', 1, 19);
INSERT INTO `cdb_feld` VALUES(55, 4, 2, 'gruppentyp_id', 'groupTypes', 1, 'Gruppentyp', 'Gruppentyp', '<br/>', 'admingroups', 11, 2);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_feldkategorie`
--

CREATE TABLE `cdb_feldkategorie` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(50) NOT NULL,
  `intern_code` varchar(50) NOT NULL,
  `db_tabelle` varchar(50) NOT NULL,
  `id_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_feldkategorie`
--

INSERT INTO `cdb_feldkategorie` VALUES(1, 'Adresse', 'f_address', 'cdb_person', 'id');
INSERT INTO `cdb_feldkategorie` VALUES(2, 'Informationen', 'f_church', 'cdb_gemeindeperson', 'person_id');
INSERT INTO `cdb_feldkategorie` VALUES(3, 'Kategorien', 'f_category', 'cdb_gemeindeperson', 'id');
INSERT INTO `cdb_feldkategorie` VALUES(4, 'Gruppe', 'f_group', 'cdb_gruppe', 'id');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_feldtyp`
--

CREATE TABLE `cdb_feldtyp` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(30) NOT NULL,
  `intern_code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_feldtyp`
--

INSERT INTO `cdb_feldtyp` VALUES(1, 'Textfeld', 'text');
INSERT INTO `cdb_feldtyp` VALUES(2, 'Auswahlfeld', 'select');
INSERT INTO `cdb_feldtyp` VALUES(3, 'Datumsfeld', 'date');
INSERT INTO `cdb_feldtyp` VALUES(4, 'Ja-Nein-Feld', 'checkbox');
INSERT INTO `cdb_feldtyp` VALUES(5, 'Kommentarfeld', 'textarea');
INSERT INTO `cdb_feldtyp` VALUES(6, 'Nummernfeld', 'number');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_followup_typ`
--

CREATE TABLE `cdb_followup_typ` (
  `id` int(1) NOT NULL,
  `bezeichnung` varchar(50) NOT NULL,
  `comment_viewer_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_followup_typ`
--

INSERT INTO `cdb_followup_typ` VALUES(0, 'Kein Followup', 0);
INSERT INTO `cdb_followup_typ` VALUES(1, 'Integration Kontaktkarte', 0);
INSERT INTO `cdb_followup_typ` VALUES(2, 'Ein Monat', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_followup_typ_intervall`
--

CREATE TABLE `cdb_followup_typ_intervall` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `followup_typ_id` int(1) NOT NULL,
  `count_no` int(1) NOT NULL,
  `days_diff` int(2) NOT NULL,
  `info` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `typ_no` (`followup_typ_id`,`count_no`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Daten für Tabelle `cdb_followup_typ_intervall`
--

INSERT INTO `cdb_followup_typ_intervall` VALUES(1, 1, 1, 7, 'Anruf 1 soll erfolgen.<br>Bitte sei nett zu der Person:)');
INSERT INTO `cdb_followup_typ_intervall` VALUES(2, 1, 2, 14, 'Anruf 2 soll erfolgen.<br/>Kleingruppe gefunden?');
INSERT INTO `cdb_followup_typ_intervall` VALUES(3, 1, 3, 90, 'Anruf 3 soll erfolgen. <br>Wie geht es so?');
INSERT INTO `cdb_followup_typ_intervall` VALUES(4, 2, 1, 31, 'Person nachhalten nach einem Monat');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gemeindeperson`
--

CREATE TABLE `cdb_gemeindeperson` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `beruf` varchar(50) NOT NULL,
  `geburtsname` varchar(30) NOT NULL,
  `geburtsdatum` datetime DEFAULT NULL,
  `geburtsort` varchar(30) NOT NULL,
  `nationalitaet` varchar(30) NOT NULL,
  `nationalitaet_id` int(11) NOT NULL DEFAULT '0',
  `familienstand_no` int(11) NOT NULL DEFAULT '0',
  `hochzeitsdatum` datetime DEFAULT NULL,
  `station_id` int(11) NOT NULL DEFAULT '0',
  `status_id` int(11) NOT NULL DEFAULT '0',
  `erstkontakt` datetime DEFAULT NULL,
  `zugehoerig` datetime DEFAULT NULL,
  `eintrittsdatum` datetime DEFAULT NULL,
  `austrittsgrund` varchar(10) NOT NULL,
  `austrittsdatum` datetime DEFAULT NULL,
  `taufdatum` datetime DEFAULT NULL,
  `taufort` varchar(50) NOT NULL,
  `getauftdurch` varchar(50) NOT NULL,
  `ueberwiesenvon` varchar(30) NOT NULL,
  `ueberwiesennach` varchar(30) NOT NULL,
  `imageurl` varchar(50) DEFAULT NULL,
  `letzteaenderung` datetime DEFAULT NULL,
  `aenderunguser` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `person_id` (`person_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Daten für Tabelle `cdb_gemeindeperson`
--

INSERT INTO `cdb_gemeindeperson` VALUES(1, 1, 'Krankenschwester', 'Meierchen', '1979-09-06 00:00:00', 'Hannover', '', 0, 2, NULL, 1, 2, '2010-01-04 00:00:00', '2010-05-20 00:00:00', '2011-01-01 00:00:00', '', NULL, '2010-05-20 00:00:00', 'Hamburg Elim', 'Pastor Manfred', 'Mustergemeinde Hannover', '', 'imageaddr1.jpg', '2011-01-01 00:00:00', 'jmrauen');
INSERT INTO `cdb_gemeindeperson` VALUES(2, 2, '', '', NULL, '', '', 0, 2, NULL, 1, 2, NULL, NULL, NULL, '', NULL, NULL, '', '', 'Mustergemeinde Hamburg', '', NULL, '2011-01-11 00:00:00', 'jmrauen');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gemeindeperson_gruppe`
--

CREATE TABLE `cdb_gemeindeperson_gruppe` (
  `gemeindeperson_id` int(11) NOT NULL,
  `gruppe_id` int(11) NOT NULL,
  `status_no` int(1) NOT NULL DEFAULT '0',
  `letzteaenderung` datetime DEFAULT NULL,
  `aenderunguser` varchar(20) DEFAULT NULL,
  `followup_count_no` int(1) DEFAULT NULL,
  `followup_add_diff_days` int(3) DEFAULT NULL,
  `followup_erfolglos_zurueck_gruppen_id` int(11) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`gemeindeperson_id`,`gruppe_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gemeindeperson_gruppe`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gemeindeperson_gruppe_archive`
--

CREATE TABLE `cdb_gemeindeperson_gruppe_archive` (
  `gemeindeperson_id` int(10) NOT NULL,
  `gruppe_id` int(10) NOT NULL,
  `status_no` int(1) NOT NULL DEFAULT '0',
  `letzteaenderung` datetime DEFAULT NULL,
  `aenderunguser` varchar(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gemeindeperson_gruppe_archive`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gemeindeperson_tag`
--

CREATE TABLE `cdb_gemeindeperson_tag` (
  `gemeindeperson_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `letzteaenderung` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gemeindeperson_tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_geschlecht`
--

CREATE TABLE `cdb_geschlecht` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_geschlecht`
--

INSERT INTO `cdb_geschlecht` VALUES(0, 'unbekannt');
INSERT INTO `cdb_geschlecht` VALUES(1, 'maennlich');
INSERT INTO `cdb_geschlecht` VALUES(2, 'weiblich');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppe`
--

CREATE TABLE `cdb_gruppe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `valid_yn` int(1) NOT NULL DEFAULT '1',
  `versteckt_yn` int(1) NOT NULL DEFAULT '0',
  `bezeichnung` varchar(100) NOT NULL,
  `gruendungsdatum` datetime DEFAULT NULL,
  `abschlussdatum` datetime DEFAULT NULL,
  `treffzeit` varchar(30) NOT NULL,
  `treffpunkt` varchar(50) NOT NULL,
  `zielgruppe` varchar(30) NOT NULL,
  `max_teilnehmer` int(11) DEFAULT NULL,
  `gruppentyp_id` int(11) NOT NULL,
  `distrikt_id` int(11) DEFAULT NULL,
  `geolat` varchar(20) NOT NULL,
  `geolng` varchar(20) NOT NULL,
  `treffname` varchar(30) NOT NULL,
  `notiz` varchar(200) NOT NULL,
  `offen_yn` int(1) NOT NULL DEFAULT '0',
  `oeffentlich_yn` int(1) NOT NULL DEFAULT '0',
  `treffen_yn` int(11) NOT NULL,
  `instatistik_yn` int(1) NOT NULL,
  `mail_an_leiter_yn` int(1) NOT NULL DEFAULT '1',
  `followup_typ_id` int(1) DEFAULT NULL,
  `fu_nachfolge_typ_id` int(11) NOT NULL DEFAULT '0',
  `fu_nachfolge_objekt_id` int(11) DEFAULT NULL,
  `fu_nachfolge_gruppenteilnehmerstatus_id` int(11) DEFAULT NULL,
  `letzteaenderung` datetime DEFAULT NULL,
  `aenderunguser` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cdb_gruppe`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppenteilnehmerstatus`
--

CREATE TABLE `cdb_gruppenteilnehmerstatus` (
  `id` int(11) NOT NULL,
  `intern_code` int(1) NOT NULL,
  `bezeichnung` varchar(50) NOT NULL,
  `kuerzel` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `intern_code` (`intern_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gruppenteilnehmerstatus`
--

INSERT INTO `cdb_gruppenteilnehmerstatus` VALUES(1, 0, 'Teilnehmer', '');
INSERT INTO `cdb_gruppenteilnehmerstatus` VALUES(2, 1, 'Leiter', 'L');
INSERT INTO `cdb_gruppenteilnehmerstatus` VALUES(3, 2, 'Co-Leiter', 'CoL');
INSERT INTO `cdb_gruppenteilnehmerstatus` VALUES(4, 3, 'Supervisor', 'S');
INSERT INTO `cdb_gruppenteilnehmerstatus` VALUES(5, 4, 'Mitarbeiter', 'M');
INSERT INTO `cdb_gruppenteilnehmerstatus` VALUES(6, -2, 'Teilnahme beantragt', '');
INSERT INTO `cdb_gruppenteilnehmerstatus` VALUES(7, -1, 'zu l&ouml;schen', '');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppenteilnehmer_email`
--

CREATE TABLE `cdb_gruppenteilnehmer_email` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gruppe_id` int(11) NOT NULL,
  `status_no` int(11) NOT NULL,
  `aktiv_yn` int(1) NOT NULL,
  `sender_pid` int(11) NOT NULL,
  `email_betreff` varchar(255) NOT NULL,
  `email_inhalt` blob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gruppe_id` (`gruppe_id`,`status_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cdb_gruppenteilnehmer_email`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppentreffen`
--

CREATE TABLE `cdb_gruppentreffen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gruppe_id` int(11) NOT NULL,
  `datumvon` datetime NOT NULL,
  `datumbis` datetime NOT NULL,
  `eintragerfolgt_yn` int(11) NOT NULL,
  `ausgefallen_yn` int(11) NOT NULL,
  `anzahl_gaeste` int(11) DEFAULT NULL,
  `kommentar` text,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cdb_gruppentreffen`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppentreffen_gemeindeperson`
--

CREATE TABLE `cdb_gruppentreffen_gemeindeperson` (
  `gruppentreffen_id` int(11) NOT NULL,
  `gemeindeperson_id` int(11) NOT NULL,
  `treffen_yn` int(11) NOT NULL,
  `zufallscode` varchar(10) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  UNIQUE KEY `gruppentreffen_id` (`gruppentreffen_id`,`gemeindeperson_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gruppentreffen_gemeindeperson`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppentyp`
--

CREATE TABLE `cdb_gruppentyp` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(30) NOT NULL,
  `anzeigen_in_meinegruppen_teilnehmer_yn` int(1) NOT NULL,
  `muss_leiter_enthalten_yn` int(1) NOT NULL,
  `in_neue_person_erstellen_yn` int(1) NOT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gruppentyp`
--

INSERT INTO `cdb_gruppentyp` VALUES(1, 'Kleingruppe', 1, 1, 0, 0);
INSERT INTO `cdb_gruppentyp` VALUES(2, 'Dienst', 1, 0, 0, 0);
INSERT INTO `cdb_gruppentyp` VALUES(3, 'Maßnahme', 0, 0, 1, 0);
INSERT INTO `cdb_gruppentyp` VALUES(4, 'Merkmal', 0, 0, 1, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppe_mailchimp`
--

CREATE TABLE `cdb_gruppe_mailchimp` (
  `gruppe_id` int(11) NOT NULL,
  `modified_pid` int(11) NOT NULL,
  `modified_date` datetime NOT NULL,
  `mailchimp_list_id` varchar(30) NOT NULL,
  `optin_yn` int(1) NOT NULL DEFAULT '1',
  `goodbye_yn` int(1) NOT NULL DEFAULT '0',
  `notifyunsubscribe_yn` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gruppe_id`,`mailchimp_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gruppe_mailchimp`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppe_mailchimp_person`
--

CREATE TABLE `cdb_gruppe_mailchimp_person` (
  `gruppe_id` int(11) NOT NULL,
  `mailchimp_list_id` varchar(20) NOT NULL,
  `person_id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  PRIMARY KEY (`gruppe_id`,`mailchimp_list_id`,`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gruppe_mailchimp_person`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_gruppe_tag`
--

CREATE TABLE `cdb_gruppe_tag` (
  `gruppe_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `letzteaenderung` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_gruppe_tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_log`
--

CREATE TABLE `cdb_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` int(11) NOT NULL,
  `datum` datetime NOT NULL,
  `userid` varchar(255) NOT NULL,
  `person_id` int(11) NOT NULL DEFAULT '-1',
  `domain_type` varchar(255) DEFAULT NULL,
  `domain_id` int(11) DEFAULT NULL,
  `schreiben_yn` int(1) DEFAULT NULL,
  `txt` varchar(2048) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cdb_log`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_mailnotify`
--

CREATE TABLE `cdb_mailnotify` (
  `id` varchar(20) NOT NULL,
  `emails` varchar(200) NOT NULL,
  `enabled` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_mailnotify`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_nationalitaet`
--

CREATE TABLE `cdb_nationalitaet` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`bezeichnung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_nationalitaet`
--

INSERT INTO `cdb_nationalitaet` VALUES(2, '&Auml;gypten');
INSERT INTO `cdb_nationalitaet` VALUES(3, '&Auml;quatorialguinea');
INSERT INTO `cdb_nationalitaet` VALUES(4, '&Auml;thiopien');
INSERT INTO `cdb_nationalitaet` VALUES(127, '&Ouml;sterreich');
INSERT INTO `cdb_nationalitaet` VALUES(5, 'Afghanistan');
INSERT INTO `cdb_nationalitaet` VALUES(6, 'Albanien');
INSERT INTO `cdb_nationalitaet` VALUES(7, 'Algerien');
INSERT INTO `cdb_nationalitaet` VALUES(8, 'Andorra');
INSERT INTO `cdb_nationalitaet` VALUES(9, 'Angola');
INSERT INTO `cdb_nationalitaet` VALUES(10, 'Antigua und Barbuda');
INSERT INTO `cdb_nationalitaet` VALUES(11, 'Argentinien');
INSERT INTO `cdb_nationalitaet` VALUES(12, 'Armenien');
INSERT INTO `cdb_nationalitaet` VALUES(13, 'Aserbaidschan');
INSERT INTO `cdb_nationalitaet` VALUES(14, 'Australien');
INSERT INTO `cdb_nationalitaet` VALUES(15, 'Bahamas');
INSERT INTO `cdb_nationalitaet` VALUES(16, 'Bahrain');
INSERT INTO `cdb_nationalitaet` VALUES(17, 'Bangladesch');
INSERT INTO `cdb_nationalitaet` VALUES(18, 'Barbados');
INSERT INTO `cdb_nationalitaet` VALUES(19, 'Belgien');
INSERT INTO `cdb_nationalitaet` VALUES(20, 'Belize');
INSERT INTO `cdb_nationalitaet` VALUES(21, 'Benin');
INSERT INTO `cdb_nationalitaet` VALUES(22, 'Bhutan');
INSERT INTO `cdb_nationalitaet` VALUES(23, 'Bolivien');
INSERT INTO `cdb_nationalitaet` VALUES(24, 'Bosnien und Herzegowina');
INSERT INTO `cdb_nationalitaet` VALUES(25, 'Botsuana');
INSERT INTO `cdb_nationalitaet` VALUES(26, 'Brasilien');
INSERT INTO `cdb_nationalitaet` VALUES(27, 'Brunei');
INSERT INTO `cdb_nationalitaet` VALUES(28, 'Bulgarien');
INSERT INTO `cdb_nationalitaet` VALUES(29, 'Burkina Faso');
INSERT INTO `cdb_nationalitaet` VALUES(30, 'Burundi');
INSERT INTO `cdb_nationalitaet` VALUES(31, 'Chile');
INSERT INTO `cdb_nationalitaet` VALUES(32, 'China');
INSERT INTO `cdb_nationalitaet` VALUES(33, 'Costa Rica');
INSERT INTO `cdb_nationalitaet` VALUES(34, 'D&auml;nemark');
INSERT INTO `cdb_nationalitaet` VALUES(35, 'Deutschland');
INSERT INTO `cdb_nationalitaet` VALUES(36, 'Dominica');
INSERT INTO `cdb_nationalitaet` VALUES(37, 'Dominikanische Republik');
INSERT INTO `cdb_nationalitaet` VALUES(38, 'Dschibuti');
INSERT INTO `cdb_nationalitaet` VALUES(39, 'Ecuador');
INSERT INTO `cdb_nationalitaet` VALUES(40, 'El Salvador');
INSERT INTO `cdb_nationalitaet` VALUES(41, 'Elfenbeinküste');
INSERT INTO `cdb_nationalitaet` VALUES(42, 'Eritrea');
INSERT INTO `cdb_nationalitaet` VALUES(43, 'Estland');
INSERT INTO `cdb_nationalitaet` VALUES(44, 'Fidschi');
INSERT INTO `cdb_nationalitaet` VALUES(45, 'Finnland');
INSERT INTO `cdb_nationalitaet` VALUES(46, 'Frankreich');
INSERT INTO `cdb_nationalitaet` VALUES(47, 'Gabun');
INSERT INTO `cdb_nationalitaet` VALUES(48, 'Gambia');
INSERT INTO `cdb_nationalitaet` VALUES(49, 'Georgien');
INSERT INTO `cdb_nationalitaet` VALUES(50, 'Ghana');
INSERT INTO `cdb_nationalitaet` VALUES(51, 'Grenada');
INSERT INTO `cdb_nationalitaet` VALUES(52, 'Griechenland');
INSERT INTO `cdb_nationalitaet` VALUES(53, 'Großbritannien');
INSERT INTO `cdb_nationalitaet` VALUES(54, 'Guatemala');
INSERT INTO `cdb_nationalitaet` VALUES(55, 'Guinea');
INSERT INTO `cdb_nationalitaet` VALUES(56, 'Guinea-Bissau');
INSERT INTO `cdb_nationalitaet` VALUES(57, 'Guyana');
INSERT INTO `cdb_nationalitaet` VALUES(58, 'Haiti');
INSERT INTO `cdb_nationalitaet` VALUES(59, 'Honduras');
INSERT INTO `cdb_nationalitaet` VALUES(60, 'Indien');
INSERT INTO `cdb_nationalitaet` VALUES(61, 'Indonesien');
INSERT INTO `cdb_nationalitaet` VALUES(62, 'Irak');
INSERT INTO `cdb_nationalitaet` VALUES(63, 'Iran');
INSERT INTO `cdb_nationalitaet` VALUES(64, 'Irland');
INSERT INTO `cdb_nationalitaet` VALUES(65, 'Island');
INSERT INTO `cdb_nationalitaet` VALUES(66, 'Israel');
INSERT INTO `cdb_nationalitaet` VALUES(67, 'Italien');
INSERT INTO `cdb_nationalitaet` VALUES(68, 'Jamaika');
INSERT INTO `cdb_nationalitaet` VALUES(69, 'Japan');
INSERT INTO `cdb_nationalitaet` VALUES(70, 'Jemen');
INSERT INTO `cdb_nationalitaet` VALUES(71, 'Jordanien');
INSERT INTO `cdb_nationalitaet` VALUES(72, 'Kambodscha');
INSERT INTO `cdb_nationalitaet` VALUES(73, 'Kamerun');
INSERT INTO `cdb_nationalitaet` VALUES(74, 'Kanada');
INSERT INTO `cdb_nationalitaet` VALUES(75, 'Kap Verde');
INSERT INTO `cdb_nationalitaet` VALUES(76, 'Kasachstan');
INSERT INTO `cdb_nationalitaet` VALUES(77, 'Katar');
INSERT INTO `cdb_nationalitaet` VALUES(78, 'Kenia');
INSERT INTO `cdb_nationalitaet` VALUES(79, 'Kirgistan');
INSERT INTO `cdb_nationalitaet` VALUES(80, 'Kiribati');
INSERT INTO `cdb_nationalitaet` VALUES(81, 'Kolumbien');
INSERT INTO `cdb_nationalitaet` VALUES(82, 'Komoren');
INSERT INTO `cdb_nationalitaet` VALUES(84, 'Kongo, Demokratische Republik');
INSERT INTO `cdb_nationalitaet` VALUES(83, 'Kongo, Republik');
INSERT INTO `cdb_nationalitaet` VALUES(85, 'Kroatien');
INSERT INTO `cdb_nationalitaet` VALUES(86, 'Kuba');
INSERT INTO `cdb_nationalitaet` VALUES(87, 'Kuwait');
INSERT INTO `cdb_nationalitaet` VALUES(88, 'Laos');
INSERT INTO `cdb_nationalitaet` VALUES(89, 'Lesotho');
INSERT INTO `cdb_nationalitaet` VALUES(90, 'Lettland');
INSERT INTO `cdb_nationalitaet` VALUES(91, 'Libanon');
INSERT INTO `cdb_nationalitaet` VALUES(92, 'Liberia');
INSERT INTO `cdb_nationalitaet` VALUES(93, 'Libyen');
INSERT INTO `cdb_nationalitaet` VALUES(94, 'Liechtenstein');
INSERT INTO `cdb_nationalitaet` VALUES(95, 'Litauen');
INSERT INTO `cdb_nationalitaet` VALUES(96, 'Luxemburg');
INSERT INTO `cdb_nationalitaet` VALUES(97, 'Madagaskar');
INSERT INTO `cdb_nationalitaet` VALUES(98, 'Malawi');
INSERT INTO `cdb_nationalitaet` VALUES(99, 'Malaysia');
INSERT INTO `cdb_nationalitaet` VALUES(100, 'Malediven');
INSERT INTO `cdb_nationalitaet` VALUES(101, 'Mali');
INSERT INTO `cdb_nationalitaet` VALUES(102, 'Malta');
INSERT INTO `cdb_nationalitaet` VALUES(103, 'Marokko');
INSERT INTO `cdb_nationalitaet` VALUES(104, 'Marshallinseln');
INSERT INTO `cdb_nationalitaet` VALUES(105, 'Mauretanien');
INSERT INTO `cdb_nationalitaet` VALUES(106, 'Mauritius');
INSERT INTO `cdb_nationalitaet` VALUES(107, 'Mazedonien');
INSERT INTO `cdb_nationalitaet` VALUES(108, 'Mexiko');
INSERT INTO `cdb_nationalitaet` VALUES(109, 'Mikronesien');
INSERT INTO `cdb_nationalitaet` VALUES(110, 'Moldawien');
INSERT INTO `cdb_nationalitaet` VALUES(111, 'Monaco');
INSERT INTO `cdb_nationalitaet` VALUES(112, 'Mongolei');
INSERT INTO `cdb_nationalitaet` VALUES(113, 'Montenegro');
INSERT INTO `cdb_nationalitaet` VALUES(114, 'Mosambik');
INSERT INTO `cdb_nationalitaet` VALUES(115, 'Myanmar');
INSERT INTO `cdb_nationalitaet` VALUES(116, 'Namibia');
INSERT INTO `cdb_nationalitaet` VALUES(117, 'Nauru');
INSERT INTO `cdb_nationalitaet` VALUES(118, 'Nepal');
INSERT INTO `cdb_nationalitaet` VALUES(119, 'Neuseeland');
INSERT INTO `cdb_nationalitaet` VALUES(120, 'Nicaragua');
INSERT INTO `cdb_nationalitaet` VALUES(121, 'Niederlande');
INSERT INTO `cdb_nationalitaet` VALUES(122, 'Niger');
INSERT INTO `cdb_nationalitaet` VALUES(123, 'Nigeria');
INSERT INTO `cdb_nationalitaet` VALUES(124, 'Niue');
INSERT INTO `cdb_nationalitaet` VALUES(125, 'Nordkorea');
INSERT INTO `cdb_nationalitaet` VALUES(126, 'Norwegen');
INSERT INTO `cdb_nationalitaet` VALUES(128, 'Oman');
INSERT INTO `cdb_nationalitaet` VALUES(129, 'Pakistan');
INSERT INTO `cdb_nationalitaet` VALUES(131, 'Palästinensische Gebiete');
INSERT INTO `cdb_nationalitaet` VALUES(130, 'Palau');
INSERT INTO `cdb_nationalitaet` VALUES(132, 'Panama');
INSERT INTO `cdb_nationalitaet` VALUES(133, 'Papua-Neuguinea');
INSERT INTO `cdb_nationalitaet` VALUES(134, 'Paraguay');
INSERT INTO `cdb_nationalitaet` VALUES(135, 'Peru');
INSERT INTO `cdb_nationalitaet` VALUES(136, 'Philippinen');
INSERT INTO `cdb_nationalitaet` VALUES(137, 'Polen');
INSERT INTO `cdb_nationalitaet` VALUES(138, 'Portugal');
INSERT INTO `cdb_nationalitaet` VALUES(139, 'Ruanda');
INSERT INTO `cdb_nationalitaet` VALUES(140, 'Rumänien');
INSERT INTO `cdb_nationalitaet` VALUES(141, 'Russland');
INSERT INTO `cdb_nationalitaet` VALUES(166, 'S&uuml;dafrika');
INSERT INTO `cdb_nationalitaet` VALUES(167, 'S&uuml;dkorea');
INSERT INTO `cdb_nationalitaet` VALUES(142, 'Sahara');
INSERT INTO `cdb_nationalitaet` VALUES(143, 'Salomonen');
INSERT INTO `cdb_nationalitaet` VALUES(144, 'Sambia');
INSERT INTO `cdb_nationalitaet` VALUES(145, 'Samoa');
INSERT INTO `cdb_nationalitaet` VALUES(146, 'San Marino');
INSERT INTO `cdb_nationalitaet` VALUES(147, 'São Tomé und Príncipe');
INSERT INTO `cdb_nationalitaet` VALUES(148, 'Saudi-Arabien');
INSERT INTO `cdb_nationalitaet` VALUES(149, 'Schweden');
INSERT INTO `cdb_nationalitaet` VALUES(150, 'Schweiz');
INSERT INTO `cdb_nationalitaet` VALUES(151, 'Senegal');
INSERT INTO `cdb_nationalitaet` VALUES(152, 'Serbien');
INSERT INTO `cdb_nationalitaet` VALUES(153, 'Seychellen');
INSERT INTO `cdb_nationalitaet` VALUES(154, 'Sierra Leone');
INSERT INTO `cdb_nationalitaet` VALUES(155, 'Simbabwe');
INSERT INTO `cdb_nationalitaet` VALUES(156, 'Singapur');
INSERT INTO `cdb_nationalitaet` VALUES(157, 'Slowakei');
INSERT INTO `cdb_nationalitaet` VALUES(158, 'Slowenien');
INSERT INTO `cdb_nationalitaet` VALUES(159, 'Somalia');
INSERT INTO `cdb_nationalitaet` VALUES(160, 'Spanien');
INSERT INTO `cdb_nationalitaet` VALUES(161, 'Sri Lanka');
INSERT INTO `cdb_nationalitaet` VALUES(162, 'St. Kitts und Nevis');
INSERT INTO `cdb_nationalitaet` VALUES(163, 'St. Lucia');
INSERT INTO `cdb_nationalitaet` VALUES(164, 'St. Vincent und die Grenadinen');
INSERT INTO `cdb_nationalitaet` VALUES(165, 'Sudan');
INSERT INTO `cdb_nationalitaet` VALUES(168, 'Suriname');
INSERT INTO `cdb_nationalitaet` VALUES(169, 'Swasiland');
INSERT INTO `cdb_nationalitaet` VALUES(170, 'Syrien');
INSERT INTO `cdb_nationalitaet` VALUES(185, 'T&uuml;rkei');
INSERT INTO `cdb_nationalitaet` VALUES(171, 'Tadschikistan');
INSERT INTO `cdb_nationalitaet` VALUES(172, 'Taiwan');
INSERT INTO `cdb_nationalitaet` VALUES(173, 'Tansania');
INSERT INTO `cdb_nationalitaet` VALUES(174, 'Thailand');
INSERT INTO `cdb_nationalitaet` VALUES(175, 'Timor-Leste');
INSERT INTO `cdb_nationalitaet` VALUES(176, 'Togo');
INSERT INTO `cdb_nationalitaet` VALUES(177, 'Tonga');
INSERT INTO `cdb_nationalitaet` VALUES(178, 'Trinidad und Tobago');
INSERT INTO `cdb_nationalitaet` VALUES(179, 'Tschad');
INSERT INTO `cdb_nationalitaet` VALUES(180, 'Tschechien');
INSERT INTO `cdb_nationalitaet` VALUES(181, 'Tunesien');
INSERT INTO `cdb_nationalitaet` VALUES(182, 'Turkmenistan');
INSERT INTO `cdb_nationalitaet` VALUES(183, 'Turks- und Caicosinseln');
INSERT INTO `cdb_nationalitaet` VALUES(184, 'Tuvalu');
INSERT INTO `cdb_nationalitaet` VALUES(186, 'Uganda');
INSERT INTO `cdb_nationalitaet` VALUES(187, 'Ukraine');
INSERT INTO `cdb_nationalitaet` VALUES(0, 'unbekannt');
INSERT INTO `cdb_nationalitaet` VALUES(188, 'Ungarn');
INSERT INTO `cdb_nationalitaet` VALUES(189, 'Uruguay');
INSERT INTO `cdb_nationalitaet` VALUES(190, 'USA');
INSERT INTO `cdb_nationalitaet` VALUES(191, 'Usbekistan');
INSERT INTO `cdb_nationalitaet` VALUES(192, 'Vanuatu');
INSERT INTO `cdb_nationalitaet` VALUES(193, 'Vatikanstadt');
INSERT INTO `cdb_nationalitaet` VALUES(194, 'Venezuela');
INSERT INTO `cdb_nationalitaet` VALUES(195, 'Vereinigte Arabische Emirate');
INSERT INTO `cdb_nationalitaet` VALUES(196, 'Vietnam');
INSERT INTO `cdb_nationalitaet` VALUES(197, 'Weißrussland');
INSERT INTO `cdb_nationalitaet` VALUES(198, 'Zentralafrikanische Republik');
INSERT INTO `cdb_nationalitaet` VALUES(199, 'Zypern');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_newsletter`
--

CREATE TABLE `cdb_newsletter` (
  `person_id` int(11) NOT NULL,
  `last_send` datetime DEFAULT NULL,
  PRIMARY KEY (`person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_newsletter`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_person`
--

CREATE TABLE `cdb_person` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `vorname` varchar(30) NOT NULL,
  `spitzname` varchar(30) NOT NULL,
  `active_yn` int(1) NOT NULL DEFAULT '1',
  `password` varchar(255) DEFAULT NULL,
  `loginstr` varchar(255) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `loginerrorcount` int(11) NOT NULL,
  `acceptedsecurity` datetime DEFAULT NULL,
  `geschlecht_no` int(11) NOT NULL DEFAULT '0',
  `titel` varchar(30) NOT NULL,
  `strasse` varchar(30) NOT NULL,
  `plz` varchar(6) NOT NULL,
  `ort` varchar(40) NOT NULL,
  `land` varchar(30) NOT NULL,
  `zusatz` varchar(30) NOT NULL,
  `telefonprivat` varchar(30) NOT NULL,
  `telefongeschaeftlich` varchar(20) NOT NULL,
  `telefonhandy` varchar(20) NOT NULL,
  `fax` varchar(20) NOT NULL,
  `email` varchar(50) NOT NULL,
  `geolat` varchar(20) NOT NULL,
  `geolng` varchar(20) NOT NULL,
  `cmsuserid` varchar(50) NOT NULL,
  `archiv_yn` int(11) NOT NULL DEFAULT '0',
  `optigem_nr` varchar(30) NOT NULL,
  `createdate` datetime DEFAULT NULL,
  `letzteaenderung` datetime DEFAULT NULL,
  `aenderunguser` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Daten für Tabelle `cdb_person`
--

INSERT INTO `cdb_person` VALUES(1, 'Ackermeister', 'Sabine', '', 1, '81dc9bdb52d04dc20036dbd8313ed055', NULL, '2013-08-30 15:56:42', 0, NULL, 2, 'Dipl.-Ing.', 'Kedenburgstr. 22', '22041', 'Hamburg', '', '', '040 12345678', '0179 12345678', '', '', 'admin@test.de', '53.5778604', '10.08704130000001', 'admin', 0, '', '2011-01-01 00:00:00', '2011-01-31 00:00:00', 'Administrator');
INSERT INTO `cdb_person` VALUES(2, 'Helmut', 'Meier', '', 1, NULL, NULL, NULL, 0, NULL, 2, '', 'Bostelreihe 9', '22043', 'Hamburg', '', '', '', '', '', '', 'helmut@test.de', '53.5778604', '10.08704130000001', 'admin', 0, '', '2011-01-01 00:00:00', '2011-01-31 00:00:00', 'Administrator');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_person_distrikt`
--

CREATE TABLE `cdb_person_distrikt` (
  `person_id` int(11) NOT NULL,
  `distrikt_id` int(11) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`person_id`,`distrikt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_person_distrikt`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_person_gruppentyp`
--

CREATE TABLE `cdb_person_gruppentyp` (
  `person_id` int(11) NOT NULL,
  `gruppentyp_id` int(11) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`person_id`,`gruppentyp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_person_gruppentyp`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_station`
--

CREATE TABLE `cdb_station` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(20) NOT NULL,
  `kuerzel` varchar(10) NOT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_station`
--

INSERT INTO `cdb_station` VALUES(0, 'unbekannt', '?', 0);
INSERT INTO `cdb_station` VALUES(1, 'Zentrale', 'Z', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_status`
--

CREATE TABLE `cdb_status` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(30) NOT NULL,
  `kuerzel` varchar(10) NOT NULL,
  `mitglied_yn` int(1) NOT NULL,
  `infreitextauswahl_yn` int(1) NOT NULL DEFAULT '1',
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cdb_status`
--

INSERT INTO `cdb_status` VALUES(0, 'unbekannt', '?', 0, 1, 0);
INSERT INTO `cdb_status` VALUES(1, 'Freund', 'F', 0, 1, 0);
INSERT INTO `cdb_status` VALUES(2, 'Mitglied', 'M', 1, 1, 0);
INSERT INTO `cdb_status` VALUES(3, 'zu löschen', 'X', 0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cdb_tag`
--

CREATE TABLE `cdb_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(255) NOT NULL,
  `letzteaenderung` datetime DEFAULT NULL,
  `aenderunguser` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cdb_tag`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_addition`
--

CREATE TABLE `cr_addition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `add_date` datetime NOT NULL,
  `with_repeat_yn` int(1) NOT NULL DEFAULT '1',
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cr_addition`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_booking`
--

CREATE TABLE `cr_booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `userid` varchar(50) DEFAULT NULL,
  `person_id` int(11) NOT NULL DEFAULT '-1',
  `startdate` datetime NOT NULL,
  `enddate` datetime NOT NULL,
  `repeat_id` int(11) NOT NULL,
  `repeat_frequence` int(11) NOT NULL,
  `repeat_until` datetime NOT NULL,
  `repeat_option_id` int(11) DEFAULT NULL,
  `status_id` int(11) NOT NULL,
  `text` varchar(30) NOT NULL,
  `location` varchar(20) NOT NULL,
  `note` tinytext NOT NULL,
  `show_in_churchcal_yn` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cr_booking`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_exception`
--

CREATE TABLE `cr_exception` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `except_date_start` datetime NOT NULL,
  `except_date_end` datetime NOT NULL,
  `userid` varchar(20) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cr_exception`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_hours`
--

CREATE TABLE `cr_hours` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cr_hours`
--

INSERT INTO `cr_hours` VALUES(0, '00');
INSERT INTO `cr_hours` VALUES(1, '01');
INSERT INTO `cr_hours` VALUES(2, '02');
INSERT INTO `cr_hours` VALUES(3, '03');
INSERT INTO `cr_hours` VALUES(4, '04');
INSERT INTO `cr_hours` VALUES(5, '05');
INSERT INTO `cr_hours` VALUES(6, '06');
INSERT INTO `cr_hours` VALUES(7, '07');
INSERT INTO `cr_hours` VALUES(8, '08');
INSERT INTO `cr_hours` VALUES(9, '09');
INSERT INTO `cr_hours` VALUES(10, '10');
INSERT INTO `cr_hours` VALUES(11, '11');
INSERT INTO `cr_hours` VALUES(12, '12');
INSERT INTO `cr_hours` VALUES(13, '13');
INSERT INTO `cr_hours` VALUES(14, '14');
INSERT INTO `cr_hours` VALUES(15, '15');
INSERT INTO `cr_hours` VALUES(16, '16');
INSERT INTO `cr_hours` VALUES(17, '17');
INSERT INTO `cr_hours` VALUES(18, '18');
INSERT INTO `cr_hours` VALUES(19, '19');
INSERT INTO `cr_hours` VALUES(20, '20');
INSERT INTO `cr_hours` VALUES(21, '21');
INSERT INTO `cr_hours` VALUES(22, '22');
INSERT INTO `cr_hours` VALUES(23, '23');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_log`
--

CREATE TABLE `cr_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` int(11) NOT NULL,
  `datum` datetime NOT NULL,
  `userid` varchar(20) NOT NULL,
  `person_id` int(11) NOT NULL DEFAULT '-1',
  `booking_id` int(11) NOT NULL,
  `txt` varchar(400) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cr_log`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_minutes`
--

CREATE TABLE `cr_minutes` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cr_minutes`
--

INSERT INTO `cr_minutes` VALUES(0, '00');
INSERT INTO `cr_minutes` VALUES(15, '15');
INSERT INTO `cr_minutes` VALUES(30, '30');
INSERT INTO `cr_minutes` VALUES(45, '45');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_resource`
--

CREATE TABLE `cr_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resourcetype_id` int(11) NOT NULL,
  `sortkey` int(11) NOT NULL,
  `bezeichnung` varchar(20) NOT NULL,
  `location` varchar(20) NOT NULL,
  `autoaccept_yn` int(1) NOT NULL,
  `adminmails_old` varchar(30) DEFAULT NULL,
  `admin_person_ids` varchar(50) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Daten für Tabelle `cr_resource`
--

INSERT INTO `cr_resource` VALUES(1, 1, 0, 'Buero-Beamer', 'Buero von Max', 1, '', '-1');
INSERT INTO `cr_resource` VALUES(2, 1, 0, 'Mobil-Beamer', 'Buero von Hans', 1, '', '-1');
INSERT INTO `cr_resource` VALUES(3, 1, 0, 'Bus', '', 1, '', '-1');
INSERT INTO `cr_resource` VALUES(5, 2, 0, 'Haupt-Cafe', '', 0, '', '-1');
INSERT INTO `cr_resource` VALUES(4, 2, 0, 'Schokoraum', '', 1, '', '-1');
INSERT INTO `cr_resource` VALUES(6, 2, 0, 'Kickerraum', '', 0, '', '-1');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_resourcetype`
--

CREATE TABLE `cr_resourcetype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(20) NOT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Daten für Tabelle `cr_resourcetype`
--

INSERT INTO `cr_resourcetype` VALUES(2, 'Raum', 1);
INSERT INTO `cr_resourcetype` VALUES(1, 'Gegenstand', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cr_status`
--

CREATE TABLE `cr_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100 ;

--
-- Daten für Tabelle `cr_status`
--

INSERT INTO `cr_status` VALUES(1, 'Wartet auf Bestaetigung');
INSERT INTO `cr_status` VALUES(2, 'Bestaetigt');
INSERT INTO `cr_status` VALUES(3, 'Abgelehnt');
INSERT INTO `cr_status` VALUES(99, 'Geloescht');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_absent`
--

CREATE TABLE `cs_absent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `absent_reason_id` int(11) NOT NULL,
  `bezeichnung` varchar(255) DEFAULT NULL,
  `startdate` datetime NOT NULL,
  `enddate` datetime NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cs_absent`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_absent_reason`
--

CREATE TABLE `cs_absent_reason` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(255) NOT NULL,
  `sortkey` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Daten für Tabelle `cs_absent_reason`
--

INSERT INTO `cs_absent_reason` VALUES(1, 'Abwesend', 2);
INSERT INTO `cs_absent_reason` VALUES(2, 'Urlaub', 1);
INSERT INTO `cs_absent_reason` VALUES(3, 'Krank', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_category`
--

CREATE TABLE `cs_category` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `show_in_churchcal_yn` int(1) NOT NULL DEFAULT '1',
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cs_category`
--

INSERT INTO `cs_category` VALUES(0, 'Sonstige Veranstaltung', NULL, 1, 8);
INSERT INTO `cs_category` VALUES(1, 'Sontagsgodis', NULL, 1, 1);
INSERT INTO `cs_category` VALUES(2, 'Jugend', NULL, 1, 19);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_event`
--

CREATE TABLE `cs_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'TODO: beschreibe dieses Feld!',
  `cc_cal_id` int(11) NOT NULL,
  `startdate` datetime NOT NULL,
  `old_bezeichnung` varchar(255) NOT NULL,
  `special` varchar(255) DEFAULT NULL COMMENT 'TODO: beschreibe dieses Feld!',
  `admin` varchar(255) DEFAULT NULL,
  `old_category_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='TODO: beschreibe diese Tabelle!' AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cs_event`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_eventservice`
--

CREATE TABLE `cs_eventservice` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'TODO: beschreibe dieses Feld!',
  `event_id` int(11) NOT NULL COMMENT 'TODO: beschreibe dieses Feld!',
  `service_id` int(11) NOT NULL COMMENT 'TODO: beschreibe dieses Feld!',
  `counter` int(11) DEFAULT NULL,
  `valid_yn` int(1) NOT NULL DEFAULT '1',
  `zugesagt_yn` int(11) NOT NULL DEFAULT '0' COMMENT 'TODO: beschreibe dieses Feld!',
  `name` varchar(255) DEFAULT NULL COMMENT 'TODO: beschreibe dieses Feld!',
  `cdb_person_id` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `mailsenddate` datetime DEFAULT NULL,
  `modified_date` datetime NOT NULL,
  `modifieduser` varchar(255) NOT NULL COMMENT 'TODO: beschreibe dieses Feld!',
  `modified_pid` int(11) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='TODO: beschreibe diese Tabelle!' AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cs_eventservice`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_eventtemplate`
--

CREATE TABLE `cs_eventtemplate` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `event_bezeichnung` varchar(255) DEFAULT NULL,
  `special` varchar(255) DEFAULT NULL,
  `stunde` int(11) DEFAULT NULL,
  `minute` int(11) DEFAULT NULL,
  `admin` varchar(255) DEFAULT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cs_eventtemplate`
--

INSERT INTO `cs_eventtemplate` VALUES(0, 'Standard', 1, 'Standard', 'Weitere Infos...', 12, 0, '', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_eventtemplate_service`
--

CREATE TABLE `cs_eventtemplate_service` (
  `eventtemplate_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  UNIQUE KEY `eventtemplate_id` (`eventtemplate_id`,`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cs_eventtemplate_service`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_event_fact`
--

CREATE TABLE `cs_event_fact` (
  `event_id` int(11) NOT NULL,
  `fact_id` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`event_id`,`fact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cs_event_fact`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_fact`
--

CREATE TABLE `cs_fact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(255) NOT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Daten für Tabelle `cs_fact`
--

INSERT INTO `cs_fact` VALUES(1, 'Besucher', 0);
INSERT INTO `cs_fact` VALUES(2, 'Kollekte', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_service`
--

CREATE TABLE `cs_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'TODO: beschreibe dieses Feld!',
  `bezeichnung` varchar(50) NOT NULL,
  `notiz` varchar(50) NOT NULL,
  `standard_yn` int(11) NOT NULL DEFAULT '0' COMMENT 'TODO: beschreibe dieses Feld!',
  `servicegroup_id` int(11) NOT NULL COMMENT 'TODO: beschreibe dieses Feld!',
  `cdb_gruppen_ids` varchar(255) DEFAULT NULL,
  `cdb_tag_ids` varchar(255) DEFAULT NULL,
  `sendremindermails_yn` int(1) NOT NULL DEFAULT '0',
  `allowtonotebyconfirmation_yn` int(1) NOT NULL DEFAULT '0',
  `sortkey` int(11) NOT NULL DEFAULT '0' COMMENT 'TODO: beschreibe dieses Feld!',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='TODO: beschreibe diese Tabelle!' AUTO_INCREMENT=9 ;

--
-- Daten für Tabelle `cs_service`
--

INSERT INTO `cs_service` VALUES(1, 'Predigt', '', 1, 1, NULL, NULL, 0, 0, 0);
INSERT INTO `cs_service` VALUES(2, 'Lobpreis', '', 1, 1, NULL, NULL, 0, 0, 0);
INSERT INTO `cs_service` VALUES(3, 'Leitung', '', 1, 1, NULL, NULL, 0, 0, 0);
INSERT INTO `cs_service` VALUES(4, 'Hauptordner', '', 1, 2, NULL, NULL, 0, 0, 1);
INSERT INTO `cs_service` VALUES(5, 'Nebenordner', '', 0, 2, NULL, NULL, 0, 0, 0);
INSERT INTO `cs_service` VALUES(6, 'Ton', '', 1, 3, NULL, NULL, 0, 0, 0);
INSERT INTO `cs_service` VALUES(7, 'Licht', '', 0, 3, NULL, NULL, 0, 0, 0);
INSERT INTO `cs_service` VALUES(8, 'Video', '', 0, 3, NULL, NULL, 0, 0, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_servicegroup`
--

CREATE TABLE `cs_servicegroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'TODO: beschreibe dieses Feld!',
  `bezeichnung` varchar(255) NOT NULL COMMENT 'TODO: beschreibe dieses Feld!',
  `viewall_yn` int(1) NOT NULL DEFAULT '0',
  `sortkey` int(11) NOT NULL DEFAULT '0' COMMENT 'TODO: beschreibe dieses Feld!',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='TODO: beschreibe diese Tabelle!' AUTO_INCREMENT=4 ;

--
-- Daten für Tabelle `cs_servicegroup`
--

INSERT INTO `cs_servicegroup` VALUES(1, 'Programm', 0, 10);
INSERT INTO `cs_servicegroup` VALUES(2, 'Ordner', 0, 20);
INSERT INTO `cs_servicegroup` VALUES(3, 'Techniker', 0, 30);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_servicegroup_person_weight`
--

CREATE TABLE `cs_servicegroup_person_weight` (
  `person_id` int(11) NOT NULL,
  `servicegroup_id` int(11) NOT NULL,
  `max_per_month` int(1) NOT NULL DEFAULT '4',
  `relation_weight` int(1) NOT NULL DEFAULT '0',
  `morning_weight` int(1) NOT NULL DEFAULT '0',
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`person_id`,`servicegroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cs_servicegroup_person_weight`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_song`
--

CREATE TABLE `cs_song` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bezeichnung` varchar(50) NOT NULL,
  `songcategory_id` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `ccli` varchar(50) NOT NULL,
  `copyright` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cs_song`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_songcategory`
--

CREATE TABLE `cs_songcategory` (
  `id` int(11) NOT NULL,
  `bezeichnung` varchar(100) NOT NULL,
  `sortkey` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `cs_songcategory`
--

INSERT INTO `cs_songcategory` VALUES(0, 'Unbekannt', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cs_song_arrangement`
--

CREATE TABLE `cs_song_arrangement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) NOT NULL,
  `bezeichnung` varchar(50) NOT NULL,
  `default_yn` int(1) NOT NULL,
  `tonality` varchar(20) NOT NULL,
  `bpm` varchar(10) NOT NULL,
  `beat` varchar(10) NOT NULL,
  `length_min` int(3) NOT NULL DEFAULT '0',
  `length_sec` int(2) NOT NULL DEFAULT '0',
  `note` varchar(255) NOT NULL,
  `modified_date` datetime NOT NULL,
  `modified_pid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `cs_song_arrangement`
--

