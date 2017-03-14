<?php

$cfg = require __DIR__ . '/#/config.inc';

// path definitions
define('CORELIB_PATH' , __DIR__ . '/#/corelib');
define('HANDLER_PATH' , __DIR__ . '/#/handlers');
define('TEMPLATE_PATH', __DIR__ . '/#/templates');
define('TMP_PATH'     , __DIR__ . '/#/tmp');

// url definitions
define('BASE_URL'    , $cfg['base_url']);
define('CSS_BASE_URL', BASE_URL . '/-/css/');
define('JS_BASE_URL' , BASE_URL . '/-/js/');
define('LOGIN_URL'   , BASE_URL . '/login');
define('LOGOUT_URL'  , BASE_URL . '/logout');

// setup encoding
mb_internal_encoding('utf-8');
mb_http_output('utf-8');

// setup locale
setlocale(LC_ALL,
  'id', 'ID',
  'id_ID.UTF8', 'id_ID.UTF-8', 'id_ID.8859-1', 'id_ID',
  'IND.UTF8', 'IND.UTF-8', 'IND.8859-1', 'IND',
  'Indonesian.UTF8', 'Indonesian.UTF-8', 'Indonesian.8859-1', 'Indonesian', 'Indonesia',
  'en_US.UTF8', 'en_US.UTF-8', 'en_US.8859-1', 'en_US', 'American', 'ENG', 'English');

// setup default timezone
date_default_timezone_set('Asia/Jakarta');

// setup session
session_save_path(TMP_PATH . '/sessions');
session_name('shift-ims');
session_start();

// setup database
$db = new PDO($cfg['db']['dsn'], $cfg['db']['username'], $cfg['db']['password'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_PERSISTENT => false
]);

// fix path info
if (!isset($_SERVER['PATH_INFO'])) {
  $_SERVER['PATH_INFO'] = '/';
}

// sync user
if (!isset($_SESSION['CURRENT_USER'])) {
  $_SESSION['CURRENT_USER'] = null;
}
else {
  $_SESSION['CURRENT_USER'] = $db->query(
    'select'
    . ' id, username, active, groupId from users'
    . ' where id=' . $_SESSION['CURRENT_USER']->id
  )->fetch(PDO::FETCH_OBJ);

  if ($_SESSION['CURRENT_USER']) {
    if (!$_SESSION['CURRENT_USER']->active) {
      $_SESSION['CURRENT_USER'] = null;
    }
    else {
      unset($_SESSION['CURRENT_USER']->active);
      require CORELIB_PATH . '/acl.php';
    }
  }
}

// redirect to login page if user not logged in
if (!$_SESSION['CURRENT_USER'] && BASE_URL . $_SERVER['PATH_INFO'] !== LOGIN_URL) {
  session_regenerate_id(true);
  session_destroy();
  exit(header('Location: ' . LOGIN_URL));
}

// setup handlers
$__handler  = HANDLER_PATH . $_SERVER['PATH_INFO'];
$__handler .= substr($__handler, -1) === '/' ? 'index.php' : '.php';
$__handler  = realpath($__handler);

require CORELIB_PATH . '/global.php';

if ($__handler === false) {
  http_response_code(404);
  render('error/404');
  exit;
}

chdir(dirname($__handler));
require $__handler;
