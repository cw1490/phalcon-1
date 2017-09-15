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


$di->set('logger', function ($file = null) {
    $logger = new FileLogger(BASE_DIR . '/running/logs/' . ($file ? $file : date('Ymd')));
    return $logger;
}, false);


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
    return new BackFile($frontCache, array('prefix' => 'cache_', 'cacheDir' => BASE_DIR . '/running/cache/'));
}, true);


$di['eventsManager']->attach('db', function ($event, $connection) use ($di) {
    if ($event->getType() == 'beforeQuery') {
        if ($di['config']->setting->logs) {
            $di->get('logger', ['SQL' . date('Ymd')])->log($connection->getSQLStatement());
        }
        if (preg_match('/drop|alter/i', $connection->getSQLStatement())) {
            return false;
        }
    }
});


foreach ($di['config']['database'] as $db => $value) {
    $di->set($db, function () use ($di, $value) {
        $connection = new DbAdapter(array(
            'host'     => $value['host'],
            'port'     => $value['port'],
            'username' => $value['user'],
            'password' => $value['pass'],
            'dbname'   => $value['db'],
            'charset'  => $value['charset']
        ));
        $connection->setEventsManager($di['eventsManager']);
        return $connection;
    }, true);
}