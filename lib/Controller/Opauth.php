<?php
namespace romaninsh\opauth;
class Controller_Opauth extends \AbstractController {

    public $model;
    public $model_name='romaninsh/opauth/Model_Opauth';
    public $root_page='/';
    public $register_page='/';  // set to page which would complete registartion
    public $startegies=array();
    public $update_login_form=true;  // will add icons to login form
    public $route_auth_page=true;    // if false - create your own page
    public $redirect_style='redirect';  // either redirect, or redirect_me
    public $default_action='dump';   // what to do when authenticated? redirect,close,dump

    function init(){
        parent::init();

        // Verify to make sure this controller is used properly
        if (!($this->owner instanceof \Auth_Basic)) {
            throw $this->exception('Opauth controller must be added into Auth');
        }

        $this->owner->opauth=$this;

        // creates a page "auth/<strategy>" which will automatically log user in
        if($this->route_auth_page){
            $this->api->routePages('auth','romaninsh/opauth');
        }

        // Make sure that our "authenticatino pages" are not blocked by auth->check()
        $this->owner->addHook('isPageAllowed',function($a,$page){
            if(substr($page,0,4)=='auth')$a->breakHook(true);
        });

        $this->owner->allowPage('auth');

        // We will store tokens in this model, once authenticated
        $this->setModel($this->model_name);

        // Make sure that OPauth model is related to the user model
        if(!$this->model->hasElement('user_id')){
            $this->model->hasOne( get_class($this->owner->model), 'user_id' );
        }

        // We want to put icon on the login form for all allowed strategies
        if($this->update_login_form){
            $this->owner->addHook(array('updateForm'),$this);
        }
    }

    /**
     * Implements reasonable strategy merging your account system with
     * external authentication. Redefine this method, but do not
     * manually call it. It will be called after user returns back
     * from 3rd party login.
     *
     * @param array  $data   Response from Opauth
     * @param object $opauth Opauth object in case you want it
     */
    function callback($data, $opauth){
        // Load by auth token
        if(!$data['auth']['uid'])return array('error'=>$data['error']['raw']);
        $this->model->tryLoadBy('oauth_id',$x=$data['auth']['uid']);

        // Authentication found for a user. Perform manual login
        if ($this->model->loaded()
            && !$this->owner->isLoggedIn()
            && $this->model['user_id']
        ) {
            $this->owner->loginByID($this->model['user_id']);
            return array($this->redirect_style=>$this->root_page);
        }

        // Logged and authenticated. Bind token to current user.
        if ($this->model->loaded()
            && $this->owner->isLoggedIn()
            && $this->owner->model->id != $this->model['user_id']
        ) {

            if($this->model['user_id']){

                // TODO: think, perhaps it's better to show error here!?
                throw $this->exception('This social account is already associated
                    with different user');
            }


            $this->model['user_id']=$this->owner->model->id;
            $this->model->save();
            return 'close';

            // continue with regular flow
            //
            //
            //$this->owner->loginByID($this->model['user_id']);
        }

        // Logged and authenticated into same account, do nothing.
        if ($this->model->loaded()
            && $this->owner->isLoggedIn()
            && $this->owner->model->id == $this->model['user_id']
        ) {
            $user = $this->owner->model;
            if ($user->hasMethod('relinkExistingOpauth')) {
                $user->relinkExistingOpauth($this->model);
            }
            return $this->default_action;
        }

        // Either model not loaded, or useless for us
        if ($this->model->loaded()) {
            $this->model->delete();
        }

        $this->collectInfo($data);
        $user = $this->owner->model;

        // Already logged, associate with this account
        if ($this->owner->isLoggedIn()) {
            $this->model['user_id']=$this->owner->model->id;
            $this->model->save();

            if ($user->hasMethod('linkExistingOpauth')) {
                $user->linkExistingOpauth($this->model);
            }

            return $this->default_action;
        }

        // Create new account
        if ($user->hasMethod('registerWithOpauth')) {
            $user->registerWithOpauth($this->model);
        }
        $user->save();
        $this->model['user_id']=$user->id;
        $this->model->save();


        // Login with user
        $this->owner->loginByID($user->id);


        return array($this->redirect_style=>$this->register_page);
    }

    function collectInfo($data){
        // Collect information into model
        foreach(
            array(
                'provider','token','secret','timestamp','signature',
                'name','email','image','nickname','location'
            ) as $key
        ) {
            $this->model[$key] =
                @$data['auth']['credentials'][$key]
                ?: $data[$key]
                ?: $data['auth'][$key]
                ?: $data['auth']['info'][$key]
                ;
            unset($data['auth']['credentials'][$key],
                $data[$key],
                $data['auth'][$key],
                $data['auth']['info'][$key]
            );
        }

        $this->model['oauth_id']=$data['auth']['uid'];
        unset($data['auth']['uid']);

        if ($data['timestamp']) {
            $this->model['timestamp']
                = date('Y-m-d H:i:s', strtotime($data['timestamp']));
        }
        if ($data['expires']) {
            $this->model['expires']
                = date('Y-m-d H:i:s', strtotime($data['expires']));
        }

        if ($this->model->hasElement('raw_info')) {
            $this->model['raw_info']=json_encode($data['auth']['raw']);
        }
        unset($data['auth']['raw']);

        if ($this->model->hasElement('other')) {
            $this->model['other']=json_encode($data);
        }
    }
    function getJSAuth($strategy){
        return $this->api->js()->univ()->newWindow(
            $this->api->url('auth/'.$strategy,array('close'=>1)),
            'auth_'.$strategy,
            'width=700,height=600'
        );
    }
    function addStrategy($strategies){
        if (is_string($strategies) && strpos($strategies, ',')!==false) {
            $strategies=explode(',', $strategies);
        }
        if (is_array($strategies)) {
            foreach ($strategies as $strategy) {
                $this->addStrategy($strategy);
            }
            return $this;
        }
        $this->strategies[]=$strategies;
    }
    function updateForm($auth){
        $form=($auth instanceof \Form)?
            $auth:$auth->form;

        foreach($this->strategies as $strategy){
            $b=$form->add('View',null,'form_buttons')->setElement('img')
                ->setStyle('cursor','pointer')
                ->setAttr(
                    'src',
                    'http://opauth.org/images/favicons/'.$strategy.'.com.png'
                );
            $b->js('click',$this->getJSAuth($strategy));
        }
    }
}
