<?php
	require_once(dirname(__DIR__) . '/admin/config.php');
	require_once(DIR_SYSTEM . 'startup.php');
	$registry = new Registry();

	// Config
	$config = new Config();
	$config->load('default');
	$config->load('admin');
	$registry->set('config', $config);

	// Event
	$event = new Event($registry);
	$registry->set('event', $event);

	// Loader
	$loader = new Loader($registry);
	$registry->set('load', $loader);
	
	$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
	$registry->set('db', $db);

	// Session
	$session = new Session();
	$registry->set('session', $session);

	// Url 
	$url = new Url(HTTP_SERVER, $config->get('config_secure') ? HTTPS_SERVER : HTTP_SERVER);
	$registry->set('url', $url);

	// Log 
	$log = new Log($config->get('config_error_filename'));
	$registry->set('log', $log);

	// Cache
	$cache = new Cache('file');
	$registry->set('cache', $cache);

	// Request
	$request = new Request();
	$registry->set('request', $request);

	// Response
	$response = new Response();
	$response->addHeader('Content-Type: text/html; charset=utf-8');
	$registry->set('response', $response); 
	// Front
	$controller = new Front($registry);
	if (file_exists(DIR_APPLICATION . 'controller/extension/module/import.php')) {
	$controller->dispatch(new Action('extension/module/import/cron'), new Action('error/not_found')); // функция для запуска с названием cron, с проверкой PHP_SAPI == 'cli'
	} else {
		header("HTTP/1.0 404 Not Found");
		echo "<h1>404 Not Found</h1>";
		echo "The page that you have requested could not be found.";
	}
	exit;