<?php echo "show";?>
<?php foreach($this->lc_products as $key=>$value){
    echo $key.":".$value["cname"];
}
?>
