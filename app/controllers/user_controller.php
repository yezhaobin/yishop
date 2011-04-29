<?php
class UserController(){

    function login(){
        UserStuff::login();
    }

    function logout(){
        UserStuff::login();
    }

    function register(){
        UserStuff::create();
    }

}
