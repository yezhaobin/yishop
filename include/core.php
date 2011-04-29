<?php
/*
*Project for KIKI
*/
 class Yishop{
        protected $database;
        public $language = "";
	    function __construct(){
			require_once(YISHOP_PATH."config/config.php");
            require_once(YISHOP_PATH."config/initializers/inflections.php");
            require_once(YISHOP_PATH."config/routes.php");
            require_once(YISHOP_PATH."config/locales/i18n.php");
            require_once(YISHOP_PATH."app/controllers/application_controller.php");
            require_once(YISHOP_PATH."include/functions.php");
            $this->database = $database;
            $this->language = SITE_LANGUAGE;
		}
  		
		function run(){
           $route = new ActionDispatch();
		   $route->routes($_SERVER["REQUEST_URI"]);
		}
	}

    class ActionDispatch{
         function routes($query_string){

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
						$action = "create";
					}
					break;
		    case 2: 
					if ($routes[1] == "new"){
					  $action = "create";
					}else{
					  $params["id"] = intval($routes[1]);
					  $action = "show";
					}

					if($http_method == "PUT"){
						$action = "update";
					}
				
					if ($http_method == "POST" && is_set($_POST["_method"])&& $_POST["method"] = "delete"){
						$action = "destroy";
					}
					break;
            case 3:
					if($routes[2] == "edit"){
					   $params["id"] = intval($routes[1]);
					   $action = "edit";
					}

					if($routes[2] == "new"){
						$action = "create";
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

        function recourses($recourses){
			
		}

        function error_page($error_code){
			$filepath=YISHOP_PATH."public/".$error_code.".html";
		    $f = fopen($filepath, 'r');
		    echo fread($f, filesize($filepath));
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
			if(empty(ActiveRecord::$bd_connect)){
		       $this->db_connect = mysql_connect($database["host"], $database["username"], $database["password"]) or die(system_error("db_connect_error"));
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
            $this->model_name = Inflections::pluralize($this->model_name);
            $table_name = lcfirst($this->model_name);
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
            $this->limit_string = "limit $limit";
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
                system_message("db_query_error");
              }
            }catch(Exception $e){
               system_message("db_query_error",$e);
            }
            return $this->q;
        }
       
        function update_attributes($data){
            $length = size($data);
            $set_str = "";    
            foreach($data as $key => $value){
                $set_str .= $this->table_name.$key."='".$this->sql_slashes($value)."'";
                if($key!=$length-1){
                    $set_str .=", ";
                }
            }
            $sql = "update ".$this->table_name." set ".$set_str." ".$this->where_string;
            
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
             if($q==false){
                $this->flash["error"][] = i18n("create_record_faile");
             }else{
                $this->flash["notice"][] = i18n("create_record_success");
             }
           }else{
                $this->flash["error"]['title'] = i18n("did_not_pass_validate");
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
          while($result[] = mysql_fetch_array($this->q, MYSQL_ASSOC)){}
          return $result;
      }
      //迭代器方法
      function rewind(){
          mysql_data_seek($this->q, 0);
          $this->fields = mysql_fetch_array($this->q, MYSQL_ASSOC);
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
}

class ActionController{
        public $current_action = "index";

        function __construct(){
            $this->before_filter();        
        }
        
        function render($local_var=array(),$option = array("layout"=>"application","partial"=>"null" ,"temple"=>"default", "text"=>false,"json"=>false,"js"=>false,"xml"=>false)){
            $view = new ActionView(get_class($this), $this->current_action, $local_var);
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
        function __construct($controller_name,$action_name, $local_var){
            $this->views_folder = get_views_folder($controller_name);
            $this->action_name = $action_name;

            $this->local_var = $local_var;
        }

        function render($option){
            foreach ($this->local_var as $key=>$value){
                $local_var = "lc_".$key;
                $this->$local_var = $value;
            }
            if(isset($option["temple"])&&$option["temple"]=="default"){
                $this->temple_path = YISHOP_PATH."app/views/".$this->views_folder."/".$this->action_name.".html.php";
            }elseif(isset($option["temple"])){
                $this->temple_path = YISHOP_PATH."app/views/".$this->views_folder."/".$option["temple"].".html.php";
            }
    
            if(isset($option["layout"])){
                $this->layout = $option["layout"];
                $this->layout_path = YISHOP_PATH."app/views/layouts/".$this->layout.".html.php";
                if(!file_exists($this->layout_path)){
                    system_error("layout_temple_does_not_exists",array("file"=>$this->layout_path));
                }
                require($this->layout_path);
            }

            if(isset($option["text"]) && !empty($option["text"])){
                echo $option["text"];
                exit(); 
            }

            if(isset($option["json"]) && !empty($option["json"])){
                header("Content-type: text/json");
                if (is_define(json_encode)){
                    echo json_encode($option["json"]);
                }
                exit();
            }
            
            if(isset($option["js"]) && !empty($option["js"])){
                header("Content-type: text/javascript");
                echo $option["js"];
                exit(); 
            }

            if(isset($option["xml"]) && !empty($option["xml"])){
                header("Content-type: text/xml");
                echo $option["xml"];
                exit();
            }
        }

        function yield(){
            require($this->temple_path);
        }

        function partical($part_view){
            require(YISHOP_PATH."app/views/".$this->controller_name."/".$part_view.".html.php");
        }

}

class Helper{
     function __construct(){
            
     }
}

class Htmlhelper extends Helper{
        
        static function html_safe($html_content = "", $all_tag = array(), $all_attribute = array()){
            
        }
        
        static function image($content = "", $link = "#", $html_options = array()){
            foreach($html_options as $attribute => $value){
                $html_options_string = $attribute."=\"$value\" ";
            }
            return "<a href=\"$link\" $html_options_string>$content</a>";
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

        static function form_for($model, $html_options = array()){
            
        }

        static function csrf_token(){
            require(YISHOP_PATH."config/initializers/secret_token.php");
            $csrf_token = md5(SECRET_TOKEN.$_SERVER["HTTP_USER_AGENT"]);
            set_cookie("csrf_token", $csrf_token);
            return $csrf_token;
        }
}


class UserStuff{

    static function login(){
        global $G;
        $user = check_login();
        if(empty($user)){
           $username = $_POST["username"];
           $password = $_POST["password"];
           $expire = $_POST["remember_me"];
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
              $G["uid"] = $user["uid"];
              $G["username"] = $user["username"];
              $G["password"] = md5($user["password"].$user["salt"]);
              set_cookie(COOKIE_PREF."auth",$G["uid"]."\t".$G["username"]."\t".$G["password"],$expire);
           }
        }else{
           $G["uid"] = $user['uid'];
           $G["username"] = $user["username"];
        }        
    }

   static function check_login(){
        $auth_str = @$_COOKIE[COOKIE_PREF."auth"];
        if(empty($auth_str)){
            return false;
        }
        $auth_str = authcode($auth_str, "DECODE");
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

    function send($to, $from, $subject,$html,$text){

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
?>
