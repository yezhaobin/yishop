<?php
	class ProductsController extends ApplicationController{
		function index(){
		    
		}

		function show($params){
			$this::call_model("Product");
            $products = new Product();
            $products->all()->query();
            $this->render(array("products"=>&$products));
		}
	}
?>	
