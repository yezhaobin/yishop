<?php 
    class Inflections{
        static function pluralize($name){
            $str_length = strlen($name);

            switch ($name[$str_length-1]){
                case "s":
                    $result = $name."es";break;
                case "y":
                    $name[$length-1]="i";
                    $result = $name."es";break;

                default :
                    $result = $name."s";
            }
        
            return $result;
        }
    }
