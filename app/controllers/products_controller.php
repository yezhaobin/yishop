<?php
class ProductsController extends ApplicationController{
    
    function __construct(){
        parent::__construct();
        $this::call_model("Product");
    }
        
    function index(){
        $products = new Product();
        $products->all()->query();
            
        $this->render($local_var = array("products"=>$products));
    }

    function show($params){
        $products = new Product();
        $products->find($params["id"])->query();
        $this->render($local_var = array("products"=>$products));
    }
        
    function create(){
        $product = new Product();
        $this->render($local_var = array("product" =>$product));
    }
        
    function update($params){
        $product = new Product();
        $product->find($params["id"])->query();
            
        if($product.update_attributes($_POST)){
            $this->flash["notice"] = i18("update_record_success");
        }else{
            $this->flash["notice"] = i18("update_record_failed");
        }    
    }
        
    function edit($params){
        $this::call_model("Product");    
        $product = new Products();
        $product->find($params["id"])->query();
            
        $this->render($local_var = array("product"=>$product));
    }
}
?>    
