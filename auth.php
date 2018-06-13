<?
include ('includes/conf/config.inc.php');
require_once $configuration['paths']['filesystem'] . '/vendor/autoload.php';

$auth = new SSOAuth\Auth($configuration['session']['key'], $configuration['session']['name']);

if ($auth->checkLogin()) {

  if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) === true) {
    header('X-Forwarded-For: ' . $_SERVER['HTTP_X_FORWARDED_FOR']);
  }

  if (isset($_SERVER['HTTP_X_REAL_IP']) === true) {
    header('X-Real-IP: ' . $_SERVER['HTTP_X_REAL_IP']);
  }

  header('X-Forwarded-User: ' . $_SESSION['username'], true, 200);
} else {
  header('X-Forwarded-User: ', true, 401);
}
