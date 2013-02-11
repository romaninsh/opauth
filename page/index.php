<?php
namespace romaninsh\opauth;
class page_index extends \Page {
    function init(){
        parent::init();

        /*
        if($_GET['close']){
            $this->memorize('result','close');
        }elseif($_GET['redirect']){
            $this->memorize('result',array('redirect'=>$_GET['redirect']));
        }elseif($_GET['dump']){
            $this->memorize('result','dump');
        }
         */

        $config=$this->api->getConfig('opauth');

        // Path to auth page without ending
        $config['path']=rtrim(
            $this->api->url('auth'),
            $this->api->getConfig('url_postfix')
        ).'/';
        $config['callback_url']=(string)$this->api->url('auth/callback');
        $config['security_salt']=$this->learn('salt', uniqid());
        // memorize will also initialize session, so that Opauth is not 
        // confused
        
        $this->config=$config;

    }
    function page_callback(){
        $this->opauth = new \Opauth($this->config,false);
        $response = $_SESSION['opauth'];

        // Controller_Opauth or it's descendand (which you can tweak)
        // will determine, what should be done upon successful initialization.
        // See documentation of 
        if($this->api->auth->opauth){
            $r = $this->api->auth->opauth->callback($response, $this->opauth);
        }else {
            $r='close';
        }


       // $r=$this->recall('result',null);
        if($r=='dump'){
            echo "<pre>";
            var_Dump($response);
            exit;
        }
        if($r=='close'){
            echo '<script>window.opener.location.reload(true);window.close()</script>';
            exit;
        }
        if(isset($r['redirect'])){
            echo '<script>window.opener.location="'.$this->api->url($r['redirect']).'";window.close()</script>';
            exit;
        }

        $this->add('View_Info')->set('Authentication is successful, but no action is defined');
    }
    // Enables catch-all for all sub-pages, then send them for authentication
    function subPageHandler(){
        $this->opauth = new \Opauth($this->config);
    }
}
