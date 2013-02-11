<?php
namespace romaninsh\opauth;
class View_MyAuth extends \Grid {
    function init(){
        parent::init();
        $gr=$this;

        $gr->setModel($this->api->auth->opauth->model,
            array('image','provider','token'))
            ->addCondition('user_id',$this->api->auth->model->id);

        $gr->addButton('Connect with Github')->js(
            'click',
            $this->api->auth->opauth->getJSAuth('github')
        );

        $this->addColumn('delete','delete');

        $this->addFormatter('image','thumb');
    }
    function format_thumb($field){
        $this->current_row_html[$field]=
            '<img src="'.$this->current_row[$field].'" width=32 heigh=32/>';
    }
}
