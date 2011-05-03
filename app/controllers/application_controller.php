<?php
class ApplicationController extends ActionController{
    public $current_action = "";

    function __construct(){
       parent::__construct();
	  
	   $this->get_categories_tree();
    }
	
	function get_categories_tree(){
	
		require(YISHOP_PATH."app/models/category.php");
		
		$categories = new Category();
		$categories = $categories->all()->query()->fetch_all();
		
		return $categories;
	}
	
}

