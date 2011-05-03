<?php
class UserController extends ApplicationController{
	
	function index(){
		
	}
	
    function login(){
	  if($_SERVER["REQUEST_METHOD"] == "POST"){
        UserStuff::login();
		$this->render(array("temple"=>"index"));
	  }else{
		$user = new User();
		$this->render();
	  }
    }

    function logout(){
        UserStuff::login();
    }

    function register(){
        UserStuff::create();
    }

}
