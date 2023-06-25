<?php

declare(strict_types=1);

use dmyers\orange\Log;
use dmyers\orange\Data;
use dmyers\orange\View;
use dmyers\orange\Error;
use dmyers\orange\Event;
use dmyers\orange\Input;
use dmyers\orange\Config;
use dmyers\orange\Output;
use dmyers\orange\Router;
use dmyers\orange\Dispatcher;
use dmyers\orange\interfaces\LogInterface;
use dmyers\orange\interfaces\ErrorInterface;
use dmyers\orange\interfaces\EventInterface;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConfigInterface;
use dmyers\orange\interfaces\OutputInterface;
use dmyers\orange\interfaces\RouterInterface;
use dmyers\orange\interfaces\ViewerInterface;
use dmyers\orange\interfaces\ContainerInterface;
use dmyers\orange\interfaces\DispatcherInterface;

return [
	'error' => function (ContainerInterface $container): ErrorInterface {
		$config = $container->config->error;

		$config['request type2'] = $container->input->requestType();

		return Error::getInstance($config, $container->phpview, $container->output, $container->log);
	},
	'log' => function (ContainerInterface $container): LogInterface {
		return Log::getInstance($container->config->log);
	},
	'events' => function (ContainerInterface $container): EventInterface {
		return Event::getInstance($container->config->events);
	},
	'input' => function (ContainerInterface $container): InputInterface {
		return Input::getInstance($container->config->input);
	},
	'config' => function (ContainerInterface $container): ConfigInterface {
		// get from the container the saved config
		$config = $container->{'$config'};

		$configFolders[] = $config['config folder'];
		$configFolders[] = $config['config folder'] . '/' . $config['environment'];

		return Config::getInstance($configFolders);
	},
	'output' => function (ContainerInterface $container): OutputInterface {
		return Output::getInstance($container->config->output);
	},
	'router' => function (ContainerInterface $container): RouterInterface {
		$config = $container->config->routes;

		$config['isHttps']  = $container->input->isHttpsRequest();

		return Router::getInstance($config);
	},
	'dispatcher' => function (ContainerInterface $container): DispatcherInterface {
		return Dispatcher::getInstance($container->input, $container->output, $container->config);
	},
	'@phpview' => 'view', // alias
	'view' => function (ContainerInterface $container): ViewerInterface {
		return View::getInstance($container->config->view, $container->data);
	},
	'data' => function (ContainerInterface $container) {
		return Data::getInstance();
	},

	'pdo' => function (ContainerInterface $container) {
		return new PDO('mysql:host=' . fetchEnv('db.host') . ';dbname=' . fetchEnv('db.database'), fetchEnv('db.username'), fetchEnv('db.password'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
	},

	// you can use anything for a server name
	// model.foo or $value
	// scalar values
	// $container->{'$test'}
	// $container->get('$test');
	'$test' => 'This is a test',
];
