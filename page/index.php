<?php
namespace romaninsh\opauth;
class page_index extends \Page {
    function init(){
        parent::init();

        $config=$this->api->getConfig('opauth');
        $config['path']=(string)$this->api->url('auth').'/';
        $config['callback_url']='{path}callback';
        $config['callback_transport']='get';
        $config['security_salt']=$this->learn('salt',);

        var_dump($config);



    }
    function subPageHandler(){}
}
