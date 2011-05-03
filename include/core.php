<?php
/**
 * Yishop 核心类文件
 * ============================================================================
 * 版权所有 2011-2012 叶兆滨，并保留所有权利。
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: 叶兆滨 $
 * $Id: index.php 2011-4-30  叶兆滨 $
*/
if(!defined("IN_YISHOP")){
    die("try to hack");
}

class Yishop{
    protected $database;

    function __construct(){
        require_once(YISHOP_PATH."config/config.php");
        require_once(YISHOP_PATH."config/initializers/inflections.php");
        require_once(YISHOP_PATH."config/routes.php");
        require_once(YISHOP_PATH."app/controllers/application_controller.php");
        require_once(YISHOP_PATH."include/functions.php");
        require_once(YISHOP_PATH."app/models/user.php");
            
        $this->database = $database;
            
        //current language
        $lang = isset($_GET["lang"])?$_GET["lang"]:(isset($_COOKIE["lang"])?$_COOKIE["lang"]:"");
        $lang_file_path = YISHOP_PATH."config/locales/i18n_".$lang.".php";
            
        if(file_exists($lang_file_path)){
            require_once($lang_file_path);
            define("LANG",$lang);
        }else{
            require_once(YISHOP_PATH."config/locales/i18n_".SITE_LANGUAGE.".php");
            define("LANG", SITE_LANGUAGE);
        }
            
        //user info
        global $G;
        $G["uid"] = 0;
    }
          
    function run(){
        $route = new ActionDispatch();
        $route->routes(substr($_SERVER["REQUEST_URI"], 1));
    }
}
    
class ActionDispatch{

    function routes($query_string){
        global $ROUTES;
        if($query_string[strlen($query_string)-1] == "/"){
            $query_string = substr($query_string,0,strlen($query_string)-1);
        }
        if(isset($ROUTES[$query_string])){
            $query_string = $ROUTES[$query_string];
        }

        $routes = explode("/", $query_string);
        $tmp = $routes;
        $routes=array();
        foreach($tmp as $key => $value){
            if($value!=""){
                $routes[] = $value;
            }
        }
        if(count($routes)){
            $controller = ucfirst($routes[0])."Controller";
            $controller_path = YISHOP_PATH."app/controllers/".$routes[0]."_controller".".php";
        }
        $http_method = $_SERVER["REQUEST_METHOD"];
        $params = array();
        switch (count($routes)){
            case 0:
                $controller = "HomeController";
                $controller_path = YISHOP_PATH."app/controllers/home_controller".".php";
                $action = "index";                  
                break;
            case 1:
                $action = "index";
                if ($http_method == "POST"){
                    $action = "save";
                }
               break;
            case 2: 
                if ($routes[1] == "new"){
                    $action = "create";
                    break;
                }
                    
                if(is_numeric($routes[1])){
                    $params["id"] = intval($routes[1]);
                    $action = "show";
                    break;
                }

                if($http_method == "POST" && isset($_POST["_method"])&& $_POST["_method"] = "put"){
                    $params["id"] = intval($routes[1]);
                    $action = "update";
                    break;
                }
                
                if ($http_method == "POST" && isset($_POST["_method"])&& $_POST["_method"] = "delete"){
                    $params["id"] = intval($routes[1]);
                    $action = "destroy";
                    break;
                }
                $action = $routes[1];break;
                    case 3:
                if($routes[2] == "edit"){
                    $params["id"] = intval($routes[1]);
                    $action = "edit";
                }
                break;
         }

        if(file_exists($controller_path)){
            require($controller_path);
            $controller_instance = new $controller();
            $controller_instance->current_action = $action;
            $controller_instance->$action($params);
        }else{
            $this->error_page("404");
        }
            
    }

    function error_page($error_code){
        $filepath=YISHOP_PATH."public/".$error_code.".html";
        $f = fopen($filepath, 'r');
        echo fread($f, filesize($filepath));
    }

}    

class Controller{

    function __construct(){
    
    }
    static function call_model($name){
        $name = lcfirst($name);
        require_once (YISHOP_PATH."app/models/".$name.".php");
    }
}

class ActiveRecord implements Iterator{
    public static $db_connect;
    public $table_name = "";
    protected $select_string = "*";
    protected $where_string = "";
    protected $order_string = "";
    protected $offset_string = "";
    protected $limit_string = "";
    public    $model_name = "";
    public    $fields = array();
    public    $flash = array();
    protected $validate_rules = array();
    public    $validate_flag =true;
    public    $q = "";
    public    $current_key = 0;
    public    $length = 0;

    function __construct($attributes=""){
        global $database;
        $this->table_name = $this->get_Table_Name();
        if(empty(ActiveRecord::$db_connect)){
            $this->db_connect = mysql_connect($database["host"], $database["username"], $database["password"]) or die(system_error("db_connect_error"));
            mysql_set_charset($database["charset"]);
            mysql_select_db($database["db_name"]);
        }

        $this->get_struct();//获取字段信息
            
        if(!empty($attributes)){
            foreach($this->fields as $key => $value){
                if(isset($attributes[$key])){
                    $this->fields[$key] = $value;
                }
            }
        }            
    }

    function __get($name){
        return  $this->fields[$name];
    }

     function __set($name,$value){
        $this->fields[$name] = $value;
     }

    function get_Table_Name(){
        $this->model_name = get_class($this);
        $this->table_name = Inflections::pluralize($this->model_name);
        $table_name = lcfirst($this->table_name);
        return $table_name;
    }

    function get_struct(){
        $q = mysql_query("select * from ".$this->table_name." limit 0");
        $clomuns = mysql_num_fields($q);

        for($i = 0;$i < $clomuns;$i++){
            $name = mysql_field_name($q,$i);
            $this->fields[$name]="";
        }
    }

    function select($fields){
        $this->select_string = "";
        $length = $fields.length;
        foreach($fields as $key=>$value){
            $this->select_string .= $this->table_name.".`$value`";
            if ($key+1!=$length){
                $this->select_string .= "," ;
            }
        }  
    }

    function find($id){
        $id = intval($id);
        $this->where_string = "where id = $id";
            
        return $this;
    }
        
    function find_all(){
        $this->where_string = "";
        return $this;
    }
        
        
    function first(){
        $this->limit = "limt 1";
        return $this;
    }

    function all(){
        $this->limit = "";
        return $this;
    }

    function where($condition){
        foreach($condition as  $key => $value){
            $this->where_string .=" ".$this->sql_slashes($key)."='".$this->sql_slashes($value)."' and";
        }

        $this->where_string=substr($this->where_string,0,strlen($this->where_string)-4);
        $this->where_string = "where ".$this->where_string;
        return $this;
    }   

    function order($order){
        $this->order_string = "order by ".$order;
        return $this;
    }

    function limit($limit){
        $limit = intval($limit);
        $this->limit_string = "limit ".$limit;
        return $this;
    }

    function offest($offset){
        $offset = intval($offset);
        $this->offset_string = "offset $offset";
        return $this;
    }
            
    function query(){
        
        $sql = "select ".$this->select_string." from ".$this->table_name." ".$this->where_string." ".$this->order_string." ".$this->limit_string." ".$this->offset_string;
        try{
            $this->q = mysql_query($sql);
            if($this->q == false){
                 system_error("db_query_error");
            }
        }catch(Exception $e){
            system_error("db_query_error",$e);
        }
            return $this;
    }
       
    function update_attributes($data=array(),$validate = true){
        
        $length = size($data);
        $set_str = "";    
        foreach($this->fields as $key => $value){
            if(!array_key_exists($key, $data)){
                continue;
            }
            $this->fields[$key] = sql_slashes($data[$key]);
            $set_str .= $this->table_name.".".$key."='".$this->fields[$key]."',";
            if($key!=$length-1){
                $set_str .=", ";
            }
        }
            
        $sql = "update ".$this->table_name." set ".$set_str." ".$this->where_string;
            
        if($validate&&$this->validate()==false){
            return false;
        }
        return mysql_query($sql);
    }

    function count(){
        return mysql_num_rows($this->q);
    }
        
    function create($params){
        foreach($this->attributes as $key => $value){
            if(array_key_exists($key,$params)){
                $this->attributes[$key] = $params[$key];   
            }
        }
    }

    function save($validate=true){
        $sql = "";
        $fields = "";
        $values = "";
        if($validate == true){
            $pass = $this->validate();
        }else{
            $pass = true;
        }
        if($pass){
            foreach($this->attributes as $key => $value){
                $fields.= $this->table_name."`$key`,";
                $values.= "'".$this->sql_slashes($value)."',";
            }

            $fields[strlen($fields)-1]="";
            $values[strlen($values)-1]="";
            
            $sql = "insert into ".$this->table_name." ($fields) values ($values)";
            $q = mysql_query($sql);
            
            return $q;
        }else{
            return false;
        }
    }
        

    function delete($id = ""){
        if($id != "" && is_numeric($id)){
            $id = intval($id);
            $result = mysql_query("delete from ".$this->table_name." where id=$id");
        }elseif(isset($this->fields["id"])&&!empty($this->fields["id"])){
            $result = mysql_query("delete from ".$this->table_name." where id=".intval($this->fields["id"]));
        }elseif(!empty($this->where_string)){
            $result = mysql_query("delete from ".$this->table_name." ".$this->where);
        }
    }

    function sql_slashes($str){
        if(!get_magic_quotes_gpc()){
            $str = mysql_real_escape_string($str);
        }
        return $str;
    }

    function validate($call_method){
        foreach($this->validate_rules as $field=>$rule){
            if(count($rule)==2){
                $on_method = $rule[1]["on"];
            }else{
                $on_method = "all";
            }
            $field = array("name"=>$field, "value"=>$this->fields[$field] );
            if($on_method == $call_method || in_array($call_method, $on_method || $on_method == "all")){
                foreach($rule as $method=>$flag){
                    if(is_array($flag)){
                        foreach($flag as $condition=>$cvalue){
                            $method($field,$condition,$cvalue);
                        }
                    }else{
                        $method($field,$flag);
                    }
                }
            }
        }

        return $this->validate_flag;
    }

    function email($field, $flag){
        if((preg_match('/^[0-9a-zA-Z._-]+@[0-9a-zA-Z]\.[a-zA-Z]+$/',$field["value"]) == 0)==$flag){
            $this->validate_flag = false;
            $this->flash["error"][$field["name"]] = i18n("is_not_a_email"); 
        }
    }
        
    function presence($field, $flag){
        if(empty($field["value"]) == $flag){
            $this->validate_flag = false;
            $this->flash["error"][$field["name"]] = i18n("is_not_allow_blank");
        } 
    }

    function length($field, $condition,$cvalue){
        switch($condition){
            case "max":
                if(mb_strlen($field[$value]) > $cvalue){
                    $this->validate_flag = false;
                    $this->flash["error"][$field["name"]] = i18n("length_must_lower_than").$cvalue;                  
                }
                break;
            case "min":
                if(mb_strlen($field[$value]) < $cvalue ){
                    $this->validate_flag = false;
                    $this->flash["error"][$field["name"]] = i18n("length_must_bigger_than").$cvalue; 
                }
                break;
            case "in":
                $in = explode("..",$cvalue);
                $cvalue = rang(intval($in[0]),intval($in[1]));
                if(!in_array($field[$value],$cvalue)){
                    $this->validate_flag = false;
                    $this->flash["error"][$field["name"]] = i18n("length_must_in").$in[0]."..".$in[1];
                }
                break;
        }
    }

    function numeric($field, $condition,$cvalue = true){

        if (!$is_num=is_numeric($field["value"])){
            $this->validate_flag = false;
        }

        switch($condition){
            case "only_integer":
                if((!$is_num || strpos('.',$field["value"]))){
                $this->validate_flag = false;
                    $this->flash["error"][$field["name"]] = i18n("must_be_integer"); 
                }
                break;
            case "points":
                if(!$is_num || !strpos(".",$field["value"])){
                    $this->validate_flag = false;
                    $this->flash["error"][$field["name"]] = i18n("must_be_float");            
                }
                break;
            case "less_than":
                if(!$is_num || $field["value"] > $cvalue){
                    $this->validate_flag = false;
                    $this->flash["error"][$field["name"]] = i18n("must_be_less_than").$cvalue;         
                }
                break;
            case "greater_than":
                if(!$is_num || $field["value"] < $cvalue){
                    $this->validate_flag = false;
                    $this->flash["error"][$field["name"]] = i18n("must_be_greater_than").$cvalue;         
                }
                break;
        }
    }

    function uniqueness($field, $condition,$scope){
        if($scope=="all"){
            if($this->select(array("id"))->where(array($field["name"]=>$field["value"]))->query->count()){
                $this->validate_flag = false;
                $this->flash["error"][$field["name"]] = i18n("had_the_same_record").$cvalue;
            }
        }elseif(is_array($scope)){
            $tmp = array();
            foreach($scope as $name => $value){
                $tmp[$name] = $value;
            }
            $tmp[$field["name"]] = $field["value"];
            if($this->select(array("id"))->where($tmp)->query()->count()){
                $this->validate_flag = false;
                $this->flash["error"][$field["name"]] = i18n("had_the_same_record").$cvalue;
            }
        }
    }

    function fetch(){
        $result = array();
        $result = mysql_fetch_array($this->q, MYSQL_ASSOC);
        return $result; 
    }

    function fetch_all(){
        $result = array();
        while($tmp = mysql_fetch_array($this->q, MYSQL_ASSOC)){
            $result[] = $tmp;
        }
        return $result;
    }
    //迭代器方法
    function rewind(){
        if(!empty($this->q)){
            mysql_data_seek($this->q, 0);
            $this->fields = mysql_fetch_array($this->q, MYSQL_ASSOC);
        }
    }

    function current(){
        return $this->fields;
    }

    function key(){
        return $this->current_key;
    }

    function next(){
        $this->fields = mysql_fetch_array($this->q, MYSQL_ASSOC);
        $this->current_key += 1;
        return $this->fields;
    }
    
    function valid(){
        return (!empty($this->fields) && $this->fields != false);
    }

    function error(){
        return mysql_error($this::db_connect);
    }
}

class ActionController extends Controller{

        public $current_action = "index";
        public $flash = array();
        
        function __construct(){
            parent::__construct();
            $this->before_filter();        
        }
        
        function render( &$local_var=array(), $options=array()){
            $layout = "application";
            $temple="default";
            $html=true;
            $text=false;
            $json = false;
            $js=false;
            $xml=false;
            
            foreach($options as $key =>$value){
                $$key = $value;
            }
            
            $view = new ActionView(get_class($this), $this->current_action, $local_var);
            $option = array("layout"=>$layout, "temple"=>$temple, "html"=>$html, "text"=>$text, "json"=>$json, "js"=>$js, "xml"=>$xml);
            $view->render($option);
        }

        function before_filter(){
            return false;
        }

        function after_filter(){
            return false;
        }

        function __deconstruct(){
            $this->after_filter();
        }     
}

class ActionView{

        public $layout = "application";
        public $layout_path = "";
        public $temple_path = "";
        public $views_folder = "";
        public $action_name = "index";
        public $local_var = array();
        
        function __construct($controller_name,$action_name, $local_var=array()){
            $this->views_folder = get_views_folder($controller_name);
            $this->action_name = $action_name;
            $this->local_var = $local_var;
            
            //the langauge file of the view
            $lang_file = YISHOP_PATH."config/locales/".$this->views_folder."/".$this->action_name."_".LANG.".php";
            if(file_exists($lang_file)){
                require($lang_file);
            }else{
                system_error("lang_file_doesn't_exists",array($this->views_folder=>$lang_file));
            }
        }

        function render($option){
            global $G;
            foreach ($this->local_var as $key=>$value){
                $local_var = "lc_".$key;
                $this->$local_var = $value;
            }
            
            $this->layout = $option["layout"];
            if($option["html"] == true){
                
                if($option["temple"]=="default"){
                    $this->temple_path = YISHOP_PATH."app/views/".$this->views_folder."/".$this->action_name.".html.php";
                }elseif(isset($option["temple"])){
                    $this->temple_path = YISHOP_PATH."app/views/".$this->views_folder."/".$option["temple"].".html.php";
                }
                
                $this->layout_path = YISHOP_PATH."app/views/layouts/".$this->layout.".html.php";
                if(!file_exists($this->layout_path)){
                    system_error("layout_temple_does_not_exists",array("file"=>$this->layout_path));
                }
                require($this->layout_path);
                
                return true;
            }
            
            
            if(isset($option["text"]) && !empty($option["text"])){
                echo $option["text"];
                return ; 
            }

            if(isset($option["json"]) && !empty($option["json"])){
                header("Content-type: text/json");
                if (is_define(json_encode)){
                    echo json_encode($option["json"]);
                }
                return ;
            }
            
            if(isset($option["js"]) && !empty($option["js"])){
                header("Content-type: text/javascript");
                echo $option["js"];
                return ; 
            }

            if(isset($option["xml"]) && !empty($option["xml"])){
                header("Content-type: text/xml");
                echo $option["xml"];
                return ;
            }
        }

        function yield(){
            require($this->temple_path);
        }

        function partical($part_view){
            require(YISHOP_PATH."app/views/".$this->views_folder."/_".$part_view.".html.php");
        }

}

class Helper{
     function __construct(){
            
     }
}

class HtmlHelper extends Helper{
        
    static function html_safe($html_content = "", $all_tag = array(), $all_attribute = array()){
            
    }
        
    static function path($arg = array()){
        $str = $arg;
        if(is_array($arg)){
            $str = "";
            $str .= join("/",$arg);
        }
            
        if(URL_MODE == "friendly"){
            return SYSTME_PATH.$str;
        }else{
            return SYSTME_PATH."index.php?r=".$str;
        }
    }
            
    static function link_to($content = "", $href = "#", $html_options = array()){
        $href = $href;
        $html_options_string = "";
            
        foreach($html_options as $attribute => $value){
            $html_options_string = $attribute."=\"$value\" ";
        }
        return "<a href=\"$href\" $html_options_string>$content</a>";
    }

    static function javascript_include_tag($javascript_files_name=array()){
        if(is_array($javascript_files_name)){
            foreach($javascript_files_name as $value){
                $file_path = "/public/javascripts/$value.js";
                echo "<script type=\"text/javascript\" src=\"$file_path?".filemtime(YISHOP_PATH.$file_path)."\"></script>";
            }
            return;
        }
        if($javascript_files_name !="all"){
            $file_path = "/public/javascripts/$javascript_files_name.js";
            echo "<script type=\"text/javascript\" src=\"$file_path?".filemtime(YISHOP_PATH.$file_path)."\"></script>";
            return;
        }

    }

    static function css_link_tag($css_files_name=array()){
        if(is_array($css_files_name)){
            foreach($css_files_name as $value){
                $file_path = "/public/stylesheets/$value.css";
                echo "<link href=\"$file_path?".filemtime(YISHOP_PATH.$file_path)."\" rel=\"stylesheet\" type=\"text/css\" >\n";    
            }
            return;
        }
            
        if($css_files_name != "all"){
            $file_path = "/public/stylesheets/$css_files_name.css";
            echo "<link href=\"$file_path?".filemtime(YISHOP_PATH.$file_path)."\" rel=\"stylesheet\" type=\"text/css\" >\n";
        }
    }

    static function form_for(&$model,$html_options = array("action"=>"default", "method"=>"post" )){
        $form_string = "";
        $controller =  Inflections::pluralize(lcfirst(get_class($model)));
        $_method_input = "<input type=\"hidden\" name=\"_method\" value=\"put\"/>";
            
        if(!array_key_exists("action",$html_options)){
            $html_options["action"] = "default"; 
        }
            
        if(!array_key_exists("method", $html_options)){
            $html_options["method"] = "post";
        }
            
        if($html_options["action"] == "default"){
            if(empty($model->id)){
                $html_options["action"] = $html_options["action"] = $controller."/".$model->id;
                    
            }else{
                $html_options["action"] = self::path($html_options["action"]);
                $_method_input = "";
            }
        }
            
        $form_string .= "<form ";
        foreach ($html_options as $key => $value){
            $form_string .= $key."=\"$value\" ";
        }
        $form_string .= ">";
        $form_string .= "<input type=\"hidden\" name=\"csrf_token\" value=\"".self::csrf_token()."\"/>".$_method_input;

        return $form_string;
    }
        
    static function csrf_token(){
        require(YISHOP_PATH."config/initializers/secret_token.php");
        $csrf_token = md5(SECRET_TOKEN.$_SERVER["HTTP_USER_AGENT"]);
        return $csrf_token;
    }
}


class UserStuff{

    static function login(){
        global $G;
        $user = UserStuff::check_login();
        if(empty($user)){
            $username = isset($_POST["username"])?$_POST["username"]:"";
            $password = isset($_POST["password"])?$_POST["password"]:"";
            $expire = isset($_POST["remember_me"])?$_POST["remember_me"]:false;
            if(empty($expire)){
                $expire = 0;
            }else{
                $expire = 3600 * 365;
            }
            $user = new User();         
            $user = $user->where(array("username"=>$username,"password"=>$password))->query()->fetch();

            if(empty($user)){
                $G["uid"] = 0;
            }else{
                $G["uid"] = $user["id"];
                $G["username"] = $user["username"];
                $G["password"] = md5($user["password"].$user["salt"]);
                set_cookie(COOKIE_PREF."auth",$G["uid"]."\t".$G["username"]."\t".$G["password"],$expire);
            }
        }else{
           $G["uid"] = $user['id'];
           $G["username"] = $user["username"];
        }        
    }

    static function check_login(){
        $auth_str = @$_COOKIE[COOKIE_PREF."auth"];
        if(empty($auth_str)){
            return false;
        }
        $auth_str = UserStuff::authcode($auth_str, "DECODE");
        $auth_array = explode("\t", $auth_str);       
        if(count($auth_array) >= 3){
            return false;
        }

        $user_id = $auth_str[0];
        $user_name = $auth_str[1];
        $password  = $auth_str[2];
        $user = new User();
        $user = User.find($user_id)->query()->fetch();
        
        if(!empty($user) && md5($user["password"].$user["salt"]) == $password){
            return $user;
        }else{
            return false;
        }

    }

    static function logout(){
        return set_cookie(COOKIE_PREF."auth", "",time()-100000);
    }
    
    static function create($data){

        $user = new User($data);
        $user->save();
    }

    static function ban($uid){
        $user = new User();
        $user = $user->find($uid);
        
        if($user->update_attributes(array("ban" => 1))){
            return true;
        }
        return false;
    }
}

class Email{

    function send($to, $from, $subject,$html,$text)
    {    
        $html=base64_encode($html);
        $to=preg_replace("/\s/","",$to);
        $message ="--$boundary\n"."Content-Type: text/html;charset=utf-8\n"."Content-Transfer-Encoding: base64\n\n";
        $message.=$html."\r\n";
        $message.="--$boundary--\n\n";
        $message.="--$boundary\n"."Content-Type: text/plain;charset=utf-8\n"."Content-Transfer-Encoding: base64\n\n";
        $message.=base64_encode("$text");
        $message.="--$boundary--\n\n";

        $header ="From: =?UTF-8?B?".base64_encode($subject)."?=<$from>\n";
        $header .= "MIME-Version: 1.0\n"; 
        $header .= "Content-Type: multipart/Mixed; boundary=\"$boundary\"\n";
        $result=mail($to, $subject, $message, $header);

        return $result;
    }
}

class Weibo{
    
    public $api_type = "sina";
    
    function __construct($api_type){
        $this->$api_type();
    }

    function sina(){

    }

    function qq(){

    }

    function webeasy(){

    }

    function souhu(){

    }
}

class Sns{

    function __construct($api_type){
        $this->$api_type;
    }

    function renren(){

    }

    function pengyou(){

    }
}