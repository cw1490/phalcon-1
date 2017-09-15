<?php


use Phalcon\DI\FactoryDefault,
    Phalcon\Mvc\View,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Mvc\Url as UrlResolver,
    Phalcon\Config\Adapter\Yaml,
    Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter,
    Phalcon\Mvc\View\Engine\Volt as VoltEngine,
    Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter,
    Phalcon\Session\Adapter\Files as SessionAdapter,
    Phalcon\Http\Response\Cookies,
    Phalcon\Events\Manager as EventsManager,
    Phalcon\Crypt,
    Phalcon\Logger\Adapter\File as FileLogger,
    Phalcon\Cache\Frontend\Data as FrontData,
    Phalcon\Cache\Backend\File as BackFile,
    Phalcon\Cache\Backend\Redis as BackRedis,
    MyApp\Plugins\SecurityPlugin;


$di = new FactoryDefault();


$di->set('config', function () {
    return new Yaml(APP_DIR . "/config/app.yml");
}, true);


$di->set('router', function () {
    return require APP_DIR . '/bootstrap/routes.php';
}, true);


$di->set('logger', function () {
    $logger = new FileLogger(BASE_DIR . '/running/logs/' . date('Ymd'));
    return $logger;
}, true);


$di->set('crypt', function () use ($di) {
    $crypt = new Crypt();
    $crypt->setKey($di['config']->setting->appKey);
    return $crypt;
}, true);


$di->set('session', function () {
    $session = new SessionAdapter();
    $session->start();
    return $session;
}, true);


$di->set('dispatcher', function () use ($di) {
    $dispatcher = new Dispatcher();
    $dispatcher->setDefaultNamespace('MyApp\Controllers');
    if ($di['config']->setting->security) {
        $di['eventsManager']->attach('dispatch', new SecurityPlugin);
        $dispatcher->setEventsManager($di['eventsManager']);
    }
    return $dispatcher;
}, true);


$di->set('url', function () {
    $url = new UrlResolver();
    $url->setBaseUri('/');
    return $url;
}, true);


$di->set('modelsMetadata', function () {
    return new MetaDataAdapter();
}, true);


$di->set('view', function () use ($di) {
    $view = new View();
    $view->setViewsDir(APP_DIR . '/views/');
    $view->registerEngines(array(
        '.html'  => function ($view, $di) {
            $volt = new VoltEngine($view, $di);
            $volt->setOptions(array(
                'compiledPath'      => BASE_DIR . '/running/cache/',
                'compiledSeparator' => '_'
            ));
            return $volt;
        },
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
    ));
    return $view;
}, true);


$di->set('modelsCache', function () use ($di) {
    $frontCache = new FrontData(array("lifetime" => $di['config']->setting->cacheTime));
    if (isset($di['config']->redis)) {
        return new BackRedis($frontCache, array(
            'prefix' => $di['config']->redis->prefix,
            'host'   => $di['config']->redis->host,
            'port'   => $di['config']->redis->port,
            'index'  => $di['config']->redis->index
        ));
    }
    return new BackFile($frontCache, array('prefix' => 'cache_', 'cacheDir' => APP_DIR . '/cache/'));
}, true);


$di['eventsManager']->attach('db', function ($event, $connection) use ($di) {
    if ($event->getType() == 'beforeQuery') {
        if ($di['config']->setting->logs) {
            $logger = new FileLogger(APP_DIR . '/logs/SQL' . date('Ymd'));
            $logger->log($connection->getSQLStatement());
        }
        if (preg_match('/drop|alter/i', $connection->getSQLStatement())) {
            return false;
        }
    }
});


$di->set('dbData', function () use ($di) {
    $connection = new DbAdapter(array(
        'host'     => $di['config']->dbData->host,
        'port'     => $di['config']->dbData->port,
        'username' => $di['config']->dbData->username,
        'password' => $di['config']->dbData->password,
        'dbname'   => $di['config']->dbData->dbname,
        'charset'  => $di['config']->dbData->charset
    ));
    $connection->setEventsManager($di['eventsManager']);
    return $connection;
}, true);


$di->set('dbLog', function () use ($di) {
    $connection = new DbAdapter(array(
        'host'     => $di['config']->dbLog->host,
        'port'     => $di['config']->dbLog->port,
        'username' => $di['config']->dbLog->username,
        'password' => $di['config']->dbLog->password,
        'dbname'   => $di['config']->dbLog->dbname,
        'charset'  => $di['config']->dbLog->charset
    ));
    $connection->setEventsManager($di['eventsManager']);
    return $connection;
}, true);