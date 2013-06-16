<?php
namespace romaninsh\opauth;
class page_index extends \Page {
    function init(){
        parent::init();

        $this->op=$this->api->auth->opauth;

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
        $r=$this->op->recall('result',null);
        $this->op->forget('result');
        $this->opauth = new \Opauth($this->config,false);
        $response = $_SESSION['opauth'];

        // Controller_Opauth or it's descendand (which you can tweak)
        // will determine, what should be done upon successful initialization.
        // See documentation of 

        if($this->api->auth->opauth){
            $r = $r?:$this->op->callback($response, $this->opauth);
        }else {
            $r = $r?:'dump';
        }


        if($r==='dump'){
            echo '<h2>default_action is not specified fo OPauth Controller. Dumping...</h2>';
            echo "<pre>";
            var_Dump($response);
            exit;
        }
        if($r==='close'){
            echo '<script>window.opener.location.reload(true);window.close()</script>';
            exit;
        }
        if(isset($r['redirect_me'])){
            if($r['redirect_me']['0']=='/'){
                header('Location: '.$r['redirect_me']);
                exit;
            }
            $this->api->redirect($r['redirect_me']);
        }
        if(isset($r['redirect'])){
            echo '<script>window.opener.location="'.$this->api->url($r['redirect']).'";window.close()</script>';
            exit;
        }

        $this->add('View_Info')->set('Authentication is successful, but no action is defined');
    }
    // Enables catch-all for all sub-pages, then send them for authentication
    function subPageHandler(){

        if($_GET['close']){
            $this->op->memorize('result','close');
        }elseif($_GET['redirect']){
            $this->op->memorize('result',array('redirect'=>$_GET['redirect']));
        }elseif($_GET['redirect_me']){
            $this->op->memorize('result',array('redirect_me'=>$_GET['redirect_me']));
        }elseif($_GET['dump']){
            $this->op->memorize('result','dump');
        }else{
           // $this->op->forget('result');
        }
        /*
        echo "<pre>";
        var_Dump($_SESSION);
        exit;
        /* */



        $this->opauth = new \Opauth($this->config);
    }
}
