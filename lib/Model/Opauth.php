<?php
namespace romaninsh\opauth;

class Model_Opauth extends \Model_Table {
    public $table='opauth';

    function init(){
        parent::init();

        $this->addField('provider')->caption('OPauth provider');
        $this->addField('oauth_id')->caption('Remote User ID')->mandatory(true);

        // field user_id will be added by your controller, which will
        // be used to tie authentication to the users. You can also
        // extend this model and add the field yourself.

        // Credentials
        $this->addField('token')->mandatory(true);
        $this->addField('expires');
        $this->addField('secret');

        $this->addField('timestamp');
        $this->addField('signature');


        $this->addField('name');
        $this->addField('email');
        $this->addField('image');
        $this->addField('nickname');
        $this->addField('location');


        $this->addField('other');
        $this->addField('raw_info');
    }
}
