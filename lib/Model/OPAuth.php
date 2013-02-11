<?php
namespace romaninsh\opauth;

class Model_OPauth extends \Model_Table {
    public $table='opauth_opauth';

    function init(){
        parent::init();

        $this->addField('test');
    }
}
