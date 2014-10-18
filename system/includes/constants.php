<?php
/**
 *  constants to use anywhere
 */

//TODO: next two maybe not needed?
define('SITES',          'sites');
define('DEFAULT_SITE',   SITES.'/default');
define('PHOTO',          'foto'); //TODO: replace it in code

define('SYSTEM',         'system');
define('ASSETS',         SYSTEM.'/assets');
define('BOOTSTRAP',      SYSTEM.'/bootstrap');
define('INCLUDES',       SYSTEM.'/includes');
define('LIB',            SYSTEM.'/lib');
define('MAIN',           SYSTEM.'/main');
define('CHURCHCORE',     SYSTEM.'/churchcore');
define('CHURCHSERVICE',  SYSTEM.'/churchservice');
define('CHURCHDB',       SYSTEM.'/churchdb');
define('CHURCHCAL',      SYSTEM.'/churchcal');
define('CHURCHWIKI',     SYSTEM.'/churchwiki');
define('CHURCHREPORT',   SYSTEM.'/churchreport');
define('CHURCHRESOURCE', SYSTEM.'/churchresource');
define('CLASSES',        '/classes');
define('RESOURCES',      '/resources');
define('TEMPLATES',      RESOURCES . '/templates');

define('NL',        "\r\n"); //new line, \n, \r, \r\n or \n\r
define('BR',        "<br/>" . NL); //html new line

define('PHP_QPRINT_MAXL', 75);
define('MAX_MAILS', 10);
