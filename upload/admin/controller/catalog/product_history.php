<?php
class ControllerCatalogProductHistory extends Controller {
	private $error = array();
	
	public function eventAddHistory($data){
		$product_id = $data['product_id'];
        $this->load->model('catalog/product');
        $keyword = $data['keyword'];
        $upc = $data['upc'];
        $viewed = '0';
        $status = $data['status'];

        $data['upc'] = '';
        $data['viewed'] = '0';
        $data['keyword'] = '';
        $data['status'] = 2;

        ## toggle the status of the product hidden (2)
        $this->model_catalog_product->editProduct($product_id, $data);

        $data['upc'] = $upc;
        $data['viewed'] = $viewed;
        $data['keyword'] = $keyword;
        $data['status'] = $status;

        $new_prod_id = $this->model_catalog_product->addProduct($data);

        ## get original id for current product id
        $sql = "SELECT original_id from " . DB_PREFIX . "product_history WHERE current_id = " . $product_id;

        $res = $this->db->query($sql);

        $original_id = $res->row;

        if (empty($original_id)) {
            $original_id = $product_id;
        } else {
            $original_id = $original_id['original_id'];
        }

        $sql = "INSERT INTO " . DB_PREFIX . "product_history SET original_id = $original_id, previous_id = $product_id, current_id =  $new_prod_id, modified = NOW();
";
        $result = $this->db->query($sql);
        
    
	}


}
