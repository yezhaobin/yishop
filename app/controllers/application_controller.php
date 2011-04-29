<?php
class ApplicationController extends ActionController{
    public $current_action = "";

    function __construct(){
       parent::__construct();
       if(!UserStuff::check_login()){
            echo "don't login!";
       }
    }
    static function call_model($name){
        $name = lcfirst($name);
        require_once (YISHOP_PATH."app/models/".$name.".php");
    }
}
