<?php
session_start();
$config = (object)array();

include("db_connect.php");
require("../config/globals.php");
require("../config/modules.inc.php");
global $config;

if (!isset($config->auth_mode) || $config->auth_mode !== "casdoor") {
	header("Location:index.php?err=1");
	exit();
}

if (!isset($_GET['code']) || !isset($_GET['state']) || !isset($_SESSION['casdoor_oauth_state'])
	|| !hash_equals($_SESSION['casdoor_oauth_state'], $_GET['state'])) {
	header("Location:index.php?err=1");
	exit();
}
unset($_SESSION['casdoor_oauth_state']);

$token_url = rtrim($config->casdoor_endpoint, '/').'/api/login/oauth/access_token';
$token_post = http_build_query(array(
	'grant_type' => 'authorization_code',
	'client_id' => $config->casdoor_client_id,
	'client_secret' => $config->casdoor_client_secret,
	'code' => $_GET['code'],
	'redirect_uri' => $config->casdoor_redirect_uri,
));

$ctx = stream_context_create(array('http' => array(
	'method' => 'POST',
	'header' => "Content-type: application/x-www-form-urlencoded\r\n",
	'content' => $token_post,
	'timeout' => 10,
)));
$token_response = @file_get_contents($token_url, false, $ctx);
if ($token_response === false) {
	header("Location:index.php?err=1");
	exit();
}
$token_data = json_decode($token_response, true);
if (!is_array($token_data) || !isset($token_data['access_token'])) {
	header("Location:index.php?err=1");
	exit();
}

$claim = isset($config->casdoor_username_claim) ? $config->casdoor_username_claim : 'Name';
$info_url = rtrim($config->casdoor_endpoint, '/').'/api/get-account?owner='.
	urlencode($config->casdoor_organization).'&name='.urlencode($config->casdoor_application);
$info_ctx = stream_context_create(array('http' => array(
	'method' => 'GET',
	'header' => "Authorization: Bearer ".$token_data['access_token']."\r\n",
	'timeout' => 10,
)));
$info_response = @file_get_contents($info_url, false, $info_ctx);
if ($info_response === false) {
	header("Location:index.php?err=1");
	exit();
}
$user_data = json_decode($info_response, true);
if (!is_array($user_data)) {
	header("Location:index.php?err=1");
	exit();
}

if (isset($user_data[$claim])) {
	$name = $user_data[$claim];
} else if (isset($user_data['data']) && is_array($user_data['data']) && isset($user_data['data'][$claim])) {
	$name = $user_data['data'][$claim];
} else {
	header("Location:index.php?err=1");
	exit();
}

$stmt = $link->prepare("SELECT * FROM ocp_admin_privileges WHERE username = ?");
if (!$stmt->execute(array($name))) {
	print_r("Failed to fetch credentials!");
	error_log(print_r($stmt->errorInfo(), true));
	die;
}
$resultset = $stmt->fetchAll();
if (!isset($resultset) || count($resultset)==0) {
	header("Location:index.php?err=1");
	exit();
}

$avail_tools = $resultset[0]['available_tools'];
$avail_perms = $resultset[0]['permissions'];

$_SESSION['temp_user_login'] = $name;
if (!is_null($resultset[0]['secret']))
	$_SESSION['secret'] = $resultset[0]['secret'];
else unset($_SESSION['secret']);

$_SESSION['temp_user_tabs'] = ($avail_tools == "all") ? "*" : $avail_tools;
$_SESSION['temp_user_priv'] = ($avail_perms == "all") ? "*" : $avail_perms;

if ($config->twoFactor) {
	header("Location:auth_index.php");
	exit();
}

$_SESSION['user_login'] = $_SESSION['temp_user_login'];
$_SESSION['user_tabs'] = $_SESSION['temp_user_tabs'];
$_SESSION['user_priv'] = $_SESSION['temp_user_priv'];

$dashboard = false;
$default_path = NULL;
foreach ($config_modules as $menuitem => $menuitem_config) {
	if (!$menuitem_config['enabled'] || !isset($menuitem_config['modules']))
		continue;
	if (isset($menuitem_config['modules']['dashboard'])
		&& $menuitem_config['modules']['dashboard']['enabled']
		&& ($avail_tools == "all" || array_key_exists("dashboard", explode(",",$avail_tools))))
		$dashboard = true;
	foreach ($menuitem_config['modules'] as $module => $values) {
		if (isset($values['enabled']) && !$values['enabled'])
			continue;
		if (isset($values['default']) && $values['default']) {
			$default_path = 'tools/';
			if (!isset($value['path']))
				$default_path .= $menuitem . '/' . $module;
			else
				$default_path .= $value['path'];
			$default_path .= '/index.php';
			if (!file_exists($default_path))
				$default_path = NULL;
		}
	}
}

if ($default_path != NULL) {
	$_SESSION['path'] = $default_path;
} else if ($dashboard) {
	$query = "SELECT COUNT(*) as panel_no FROM ocp_dashboard;";
	$stmt = $link->prepare($query);
	if (!$stmt->execute(NULL)) {
		print_r("Failed to fetch db!");
		error_log(print_r($stmt->errorInfo(), true));
		die;
	}
	$resultset = $stmt->fetchAll();
	if ($resultset[0]['panel_no'] > 0)
		$_SESSION['path'] = "tools/system/dashboard/dashboard.php";
}

header("Location:main.php");
exit();
?>
