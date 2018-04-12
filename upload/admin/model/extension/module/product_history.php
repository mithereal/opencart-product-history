<?php

class ModelExtensionModuleProductHistory extends Controller
{
    private $error = array();


    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/product_history')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function validateHistoryForm()
    {
        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['product_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 3) || (utf8_strlen($value['name']) > 255)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }

            if ((utf8_strlen($value['meta_title']) < 3) || (utf8_strlen($value['meta_title']) > 255)) {
                $this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
            }
        }

        if ((utf8_strlen($this->request->post['model']) < 1) || (utf8_strlen($this->request->post['model']) > 64)) {
            $this->error['model'] = $this->language->get('error_model');
        }

        if (utf8_strlen($this->request->post['keyword']) > 0) {
            $this->load->model('catalog/url_alias');

            $url_alias_info = $this->model_catalog_url_alias->getUrlAlias($this->request->post['keyword']);

            if ($url_alias_info && isset($this->request->get['product_id']) && $url_alias_info['query'] != 'product_id=' . $this->request->get['product_id']) {
                ## remove old url alias
                $this->model_catalog_url_alias->deleteUrlAlias($this->request->post['keyword']);
                ##	$this->error['keyword'] = sprintf($this->language->get('error_keyword'));
            }

            if ($url_alias_info && !isset($this->request->get['product_id'])) {
                $this->error['keyword'] = sprintf($this->language->get('error_keyword'));
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    public function cloneProductHistory($product_id, $original_product_id, $new_product_id)
    {
        $this->hideProduct($product_id);

        $sql = "INSERT INTO " . DB_PREFIX . "product_history SET original_id = $original_product_id, previous_id = $product_id, current_id =  $new_product_id, modified = NOW()";
        $result = $this->db->query($sql);
        return $new_product_id;

    }

    public function hideProduct($data)
    {
        $sql = "UPDATE " . DB_PREFIX . "product SET status = 2 where product_id = " . $data;
        $this->db->query($sql);
    }

    public function addProductHistory($product_id, $data)
    {
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
        return $new_prod_id;
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $sql = "INSERT INTO " . DB_PREFIX . "setting (
`setting_id` ,
`store_id` ,
`code` ,
`key` ,
`value` ,
`serialized`
)
VALUES (
NULL , '0', 'product_history', 'product_history_status', '1', '0'
)";

        $this->db->query($sql);

 $sql = "CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "product_history (
  `_id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `previous_id` int(11) NOT NULL,
  `current_id` int(11) NOT NULL,
  `modified` date NOT NULL
)";
        $this->db->query($sql);

 $sql ="ALTER TABLE " . DB_PREFIX . "product CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0'";
        $this->db->query($sql);
    }

    public function uninstall()
    {
     $sql ="DELETE FROM " . DB_PREFIX . "setting
WHERE `code` = 'product_history'
";
        $this->db->query($sql);

        $sql ="DROP TABLE " . DB_PREFIX . "product_history";
        
        $this->db->query($sql);
    }

    public function getOriginalId($product_id)
    {
        $sql = "select original_id from " . DB_PREFIX . "product_history where current_id =" . $product_id . " order by modified desc limit 1";

        $result = $this->db->query($sql);

        return $result->row;
    }

    public function totalRevisions($product_id)
    {
        $sql = "select count(*) as total from " . DB_PREFIX . "product_history where original_id =" . $product_id;

        $result = $this->db->query($sql);
        return $result->row['total'];
    }

    public function getRevisions($product_id)
    {
        $sql = "select *  from " . DB_PREFIX . "product_history where original_id =" . $product_id . ' order by _id desc';

        $result = $this->db->query($sql);
        return $result->rows;
    }

    public function getRevision($id)
    {
        $sql = "select *  from " . DB_PREFIX . "product_history where _id =" . $id . ' order by _id desc';

        $result = $this->db->query($sql);
        return $result->row;
    }

    public function currentRevision($product_id)
    {
        $sql = "select * from " . DB_PREFIX . "product_history where original_id =" . $product_id . " order by modified desc limit 1";

        $result = $this->db->query($sql);

        return $result->row;
    }

}
