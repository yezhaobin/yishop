<?php
class ApplicationController extends ActionController{
    public $current_action = "";

    function __construct(){
        parent::__construct();
        $this->get_categories_tree();
        
        UserStuff::check_login();
    }
    
    function get_categories_tree(){

        require(YISHOP_PATH."app/models/category.php");
        $menu = array();
        $categories = new Category();
        $categories = $categories->all()->query()->fetch_all();

        foreach($categories as $key =>$value){
            if($value["cid"] == 0){
                $menu[] = $value;
            }
        }
        
        foreach($menu as $key => $value){
            foreach($categories as $value2){
                if($value["id"] == $value2["cid"]){
                    $menu[$key] = $value2;
                }
            }
        }

        return $menu;
    }

    function check_login(){

    }
}

