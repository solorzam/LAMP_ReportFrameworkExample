<?php

# Version
define('APP_VERSION', '6.3.1');

# Start Time
define('PAGE_START_TIME', microtime(true));

# up time limit
set_time_limit(120);

# IP address change for net scalar
if (isset($_SERVER['HTTP_NS_CLIENT_IP']))
{
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_NS_CLIENT_IP'];
}

# Easier way to handle any of the subdomains (and in that regard the modules)
if (preg_match('/^my/', $_SERVER['HTTP_HOST'])) {
    define('DIR', 'my');
} elseif (preg_match('/^m/', $_SERVER['HTTP_HOST'])) {
    define('DIR', 'm');
} elseif (preg_match('/^poe/', $_SERVER['HTTP_HOST'])) { // poe-qa and poe-test both hit here as well
    define('DIR', 'poe');
} elseif (preg_match('/^api/', $_SERVER['HTTP_HOST'])) {
    define('DIR', 'api');
} elseif (preg_match('/^tools/', $_SERVER['HTTP_HOST'])) {
    define('DIR', 'tools');
} else {
    define('DIR', 'app');
}

# Paths
define('ROOT', dirname($_SERVER['DOCUMENT_ROOT']));
define('SESSIONS', ROOT.'/tmp/sessions/');
define('UPLOADS', ROOT.'/tmp/uploads/');
define('INFECTED', ROOT.'/tmp/uploads/infected/');
define('REPORTS', ROOT.'/tmp/reports/');
define('GRAPHS', ROOT.'/public/images/graphs/');
define('LOGOS', ROOT.'/public/images/logos/');
define('LOCKS', ROOT.'/tmp/locks/');
define('POE_TEMP', ROOT.'/tmp/poe/');

# Library path
define('LIB', ROOT.'/lib/');
define('TEMPLATES', LIB.'/templates/');
define('SCRIPTS', LIB.'/scripts/');

# MVC paths
define('CLASSES', LIB.'/classes/');
define('MODELS', LIB.'/models/');
define('VIEWS', ROOT.'/'.DIR.'/views/');
define('CONTROLLERS', ROOT.'/'.DIR.'/controllers/');
define('REP_CLASEsS', LIB.'/reports/');

# Server constants
define('PROTOCOL', 'https://');
define('ADDRESS', $_SERVER['SERVER_ADDR']);
define('HOST', PROTOCOL.$_SERVER['HTTP_HOST']);

# Path to configuration is based off domain name
if (preg_match('/verifyos/', $_SERVER['HTTP_HOST'])) {
    define('ENV', ROOT.'/config/env/vos/');
    define('APP_SHORT', 'VOS');
} elseif (preg_match('/hmsverify/', $_SERVER['HTTP_HOST'])) {
    define('ENV', ROOT.'/config/env/hms/');
    define('APP_SHORT', 'HMS');
} else {
    define('ENV', ROOT.'/config/env/aos/');
    define('APP_SHORT', 'AOS');
}

# Non-secure controllers and methods
define('NOT_SECURE',"/".implode('|', array(
        "(^\/$)",
        "(^\/login)",
        "(^\/general)",
        "(^\/validate)",
        "(^\/page\/translation)",
        "(^\/twilio)",
        "(^\/monitor)",
        "(^\/fax)",

        # TOOLS/API
        "(^\/classification\/)",
        "(^\/emp\/)",
        "(^\/email)",
        "(^\/dataexport)",
        "(^\/mailer)",
        "(^\/ocr)",
        "(^\/qbo)",
        "(^\/scanexport)",
        "(^\/translate)",
        "(^\/xlstocsv)",
        "(^\/usps)",
        "(^\/outage)",
        "(^\/upload)",
        "(^\/report)",

        # POE
        "(^\/sftpreport)"

    ))."/i");

# Disable logging for URLs
define('NOT_LOGGED',"/".implode('|', array(
        "(^\/page\/notfound)",
        "(^\/page\/translation)",
        "(^\/$)",
        "(^\/login$)",
        "(^\/login\/$)",
        "(^\/login\/reset)",
        "(^\/login\/retrieve)",
        "(^\/validate)",
        "(^\/twilio)",
        "(^\/monitor)",
        "(^\/fax)",

        # TOOLS/API
        "(^\/classification\/)",
        "(^\/email)",
        "(^\/emp\/)",
        "(^\/dataexport)",
        "(^\/mailer)",
        "(^\/ocr)",
        "(^\/qbo)",
        "(^\/scanexport)",
        "(^\/translate)",
        "(^\/xlstocsv)",
        "(^\/usps)",
        "(^\/outage)",
        "(^\/upload)",
        "(^\/report)",

        # POE
        "(^\/sftpreport)"

    ))."/i");
;

# load class and library  components
include(LIB."constants.php");
include(LIB."common.php");

if (isset($_SERVER['PHP_ENV']) && in_array($_SERVER['PHP_ENV'], array('prod','qa','test','local')))
{
    require(ENV.$_SERVER['PHP_ENV'].'.php');
}
else
{
    die();
}

# If not declared then setup defaults
isdefined('SESSION_DOMAIN', APP_URL);
isdefined('SESSION_SECURITY', true);
isdefined('SESSION_HTTPONLY', true);
isdefined('SESSION_TIMEOUT', 600);

# Regeneration is rand(1,100) <= SESSION_REGENERATION
# so this is 5%, under debug goes to 50%
isdefined('SESSION_REGENERATION', 5);
isdefined('SESSION_DEBUG' , false);

# Enable access level debugging (old)
isdefined('ACCESS_DEBUGGING', false);

# Debug things to disk file
# level 1 : remote call debug
# level 2 : query and connection
# level 3 : all of above with query connection details
isdefined('EVENT_DEBUGGING_LEVEL', false);

# query debugging, memory foot print, page load time and session array
# visible by clicking AOS logo in portal
isdefined('QUERY_DEBUGGING', false);

# Enable page benchmarking which saves to CentralDB
# lvl - 1 user, uri, memory and load time
# lvl - 2 (nothing)
# lvl - 3 (nothing)
# lvl - 4 + queries
isdefined('BENCHMARKING_LVL', 4);

# threshold for recording a query runtime
isdefined('BENCHMARKING_QUERY_CUTOFF', 1);

# give some pages 5 sec instead
define('BENCHMARK_QUERY_CUTOFF_EXCEPTIONS',"/(upload)|(verification)|(finalnotice)|(amnesty)|(partial)|(term)/i");

# POE
isdefined('POE_CERTIFICATES_LOCATION', '/srv/poe-certificates/');
isdefined('POE_OPENSSL_LOCATION', '/usr/bin/openssl');

# Limit page actions to 100 per 5 min window
isdefined('USE_IPBLOCK', true);

# Action tracker...
isdefined('USE_ACTIONTRACKER', false);

# pdf download
isdefined('PDF_DOWNLOADS', true);

# xls creation is based on xml writer
isdefined('XLS_DOWNLOADS', ( extension_loaded('xmlwriter') ? true : false ));

# enable the redirecting for mobile sites?
isdefined('MOBILE_REDIRECT', true);

# enable the upload virus scanning
isdefined('UPLOAD_SCANNING', true);
isdefined('UPLOAD_SCANNER_PATH', '/usr/bin/clamdscan');
isdefined('UPLOAD_CONVERT','/usr/local/bin/convert');
isdefined('UPLOAD_SIZE', 5242880);
isdefined('MAX_UPLOAD_SIZE', 15728640);

# Since most users come to visit the employer portal, go ahead and serve the css/js files from that domain
# saves another call once redirected.
isdefined('ELEMENT_DOMAIN',PROTOCOL.MY_URL);

# use minimized CSS/JS
isdefined('USE_MIN_FILES', true);

# Redis
isdefined('REDIS_SERVER', '127.0.0.1');
isdefined('REDIS_PORT',   '6379');

# POE/API debugging
isdefined('POE_DEBUG', false);

# cms pdf caching
/*
 * commenting out because this caching was replaced by the PDF generation caching
 */
//define('CMS_CACHING', false);

# Generic email box
define('GENERAL_EMAIL_BOX', "help@" . APP_URL);
define('GENERIC_EMAIL_LINK', '<a href="mailto:' . GENERAL_EMAIL_BOX . '">' . GENERAL_EMAIL_BOX . '</a>');

# Fax intake paths
define('FAX_PDF_PATH',   ROOT . '/tmp/fax/pdf/');
define('FAX_IMG_PATH',   ROOT . '/tmp/fax/img/');
define('FAX_ERROR_PATH', ROOT . '/tmp/fax/error/');

# Google Recaptcha secret result key https://www.google.com/recaptcha/admin#site/340583394?setup
isdefined('GOOGLE_RECAPTCHA_URL', 'https://www.google.com/recaptcha/api/siteverify');
isdefined('GOOGLE_RECAPTCHA_SITE_KEY', '6Lfi40wUAAAAAIpy3SIOK7iFTgi2_0viI4WWhh_3');
isdefined('GOOGLE_RECAPTCHA_SECRET', '6Lfi40wUAAAAAAdzc2wjI5K9XMvvh-YwSQnDXe4o');
