<?php


namespace MyApp\Controllers;


use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Dispatcher;

class ControllerBase extends Controller
{

    /**
     * @link http://php.net/manual/en/book.gettext.php
     * @link http://www.laruence.com/2009/07/19/1003.html
     * @param Dispatcher $dispatcher
     * ./lang/zh_CN/LC_MESSAGES/zh_CN.mo
     * ./lang/en_US/LC_MESSAGES/en_US.mo
     * lang=zh_CN,en_US
     */
    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $lang = $this->request->get('lang');
        if ($lang) {
            setlocale(LC_ALL, $lang);
            $domain = $lang;
            bind_textdomain_codeset($domain, 'UTF-8');
            bindtextdomain($domain, APP_DIR . '/lang');
            textdomain($domain);
        }
    }


    public function initialize()
    {
    }


    public function afterExecuteRoute(Dispatcher $dispatcher)
    {
    }

}