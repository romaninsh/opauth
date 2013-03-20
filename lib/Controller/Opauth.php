<?php
namespace romaninsh\opauth;
class Controller_Opauth extends \AbstractController {

    public $model;
    public $model_name='romaninsh/opauth/Model_Opauth';
    public $root_page='/';
    public $register_page='/';  // set to page which would complete registartion
    public $startegies=array();

    function init(){
        parent::init();

        if (!($this->owner instanceof \Auth_Basic)) {
            throw $this->exception('Opauth controller must be added into Auth');
        }

        $this->owner->opauth=$this;

        $this->api->routePages('auth','romaninsh/opauth');

        $this->setModel($this->model_name);
        if(!$this->model->hasElement('user_id')){
            $this->model->hasOne( get_class($this->owner->model), 'user_id' );
        }

        $this->owner->addHook(array('updateForm'),$this);
    }
    /**
     * Implements reasonable strategy merging your account system with
     * external authentication. Redefine this method, but do not
     * manually call it.
     *
     * @param array  $data   Response from Opauth
     * @param object $opauth Opauth object in case you want it
     */
    function callback($data, $opauth){
        // Load by auth token
        $this->model->tryLoadBy('token',$x=$data['auth']['credentials']['token']);

        // Authentication found for a user. Perform manual login
        if ($this->model->loaded()
            && !$this->owner->isLoggedIn()
            && $this->model['user_id']
        ) {
            $this->owner->loginByID($this->model['user_id']);
            return array('redirect'=>$this->root_page);
        }

        // Logged and authenticated. Bind token to current user.
        if ($this->model->loaded()
            && $this->owner->isLoggedIn()
            && $this->owner->model->id != $this->model['user_id']
        ) {

            // TODO: think, perhaps it's better to show error here!?

            $this->owner->logout();
            //$this->owner->loginByID($this->model['user_id']);
            return array('redirect'=>$this->root_page);
        }

        // Logged and authenticated into same account, do nothing.
        if ($this->model->loaded()
            && $this->owner->isLoggedIn()
            && $this->owner->model->id == $this->model['user_id']
        ) {
            return 'close';
        }

        // Either model not loaded, or useless for us
        if ($this->model->loaded()) {
            $this->model->delete();
        }

        $this->collectInfo($data);

        // Already logged, associate with this account
        if ($this->owner->isLoggedIn()) {
            $this->model['user_id']=$this->owner->model->id;
            $this->model->save();
            return 'close';
        }

        // Create new account
        $user = $this->owner->model->save();
        $this->model['user_id']=$user->id;
        $this->model->save();

        // Login with user
        $this->owner->loginByID($user->id);

        if ($user->hasMethod('registerWithOpauth')) {
            $user->registerWithOpauth($this->model->id);
        }


        return array('redirect'=>$this->register_page);
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

        if ($this->model['timestamp']) {
            $this->model['timestamp']
                = date('Y-m-d H:i:s', strtotime($this->model['timestamp']));
        }
        if ($this->model['expires']) {
            $this->model['expires']
                = date('Y-m-d H:i:s', strtotime($this->model['expires']));
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
            'width=600,height=300'
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
        foreach($this->strategies as $strategy){
            $b=$auth->form->add('View',null,'form_buttons')->setElement('img')
                ->setStyle('cursor','pointer')
                ->setAttr(
                    'src',
                    'http://opauth.org/images/favicons/'.$strategy.'.com.png'
                );
            $b->js('click',$this->getJSAuth($strategy));
        }
    }
}
