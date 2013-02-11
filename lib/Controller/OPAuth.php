<?php
namespace romaninsh\opauth;
class Controller_OPAuth extends \AbstractController {

    public $model;
    public $model_name='romaninsh/opauth/Model_OPAuth';

    function init(){
        parent::init();

        if (!($this->owner instanceof \Auth_Basic)) {
            throw $this->exception('OPAuth controller must be added into Auth');
        }

        $this->owner->opauth=$this;

        $this->api->routePages('auth','romaninsh/opauth');

        $this->setModel($this->model_name);
        if(!$this->model->hasElement('user_id')){
            $this->model->hasOne( get_class($this->owner->model), 'user_id' );
        }
    }
    function getJSAuth($startegy){
        //$this->js();
    }
    function addStrategy($strategies){
    }
}
