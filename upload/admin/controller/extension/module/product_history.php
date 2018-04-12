<?php
class ControllerExtensionModuleProductHistory extends Controller {
	private $error = array();

	public function index() {

		$this->load->language('extension/module/product_history');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('product_history', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['entry_settings'] = $this->language->get('entry_settings');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

        $data['status'] = $this->config->get('product_history_status');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/extension/product_history', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['action'] = $this->url->link('extension/module/product_history', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], 'SSL');

		if (isset($this->request->post['filter_status'])) {
			$data['filter_status'] = $this->request->post['filter_status'];
		} else {
			$data['filter_status'] = $this->config->get('filter_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/producthistory', $data));
	}

	protected function validate() {
		//if (!$this->user->hasPermission('modify', 'extension/module/product_history')) {
			//$this->error['warning'] = $this->language->get('error_permission');
		//}

		return !$this->error;
	}


	public function install() {
		## $this->load->model('extension/event');
		## $this->model_extension_event->addEvent('product_history', 'admin/model/catalog/product/edit/before', 'catalog/product_history/eventAddHistory');

		$this->load->model('extension/module/product_history');

		$this->model_extension_module_product_history->install();
	}

	public function uninstall() {
		## $this->load->model('extension/event');
		## $this->model_extension_event->deleteEvent('product_history');
		
		$this->load->model('extension/module/product_history');

		$this->model_extension_module_product_history->uninstall();
	}


public function copyRevision() {
	$this->load->model('catalog/product');
	$this->load->model('extension/module/product_history');

	$revision_id = $this->request->post['product_revision'];
	$product_id = $this->request->post['product_id'] ;

	$revision_info = $this->model_extension_module_product_history->getRevision($revision_id);

	$original_product_id = $revision_info['original_id'] ;
	$selected_product_id = $revision_info['current_id'] ;

    $new_product_id = $this->model_catalog_product->copyProduct((int)$selected_product_id);

    $history_id = $this->model_extension_module_product_history->cloneProductHistory($product_id,$original_product_id,$new_product_id);

    if($history_id){
    $result['message'] = 'Product History Success';
    $result['product_id'] = $new_product_id;
    }else{
    $result['message'] = 'Product History Failed';
    }

    $this->response->addHeader('Content-Type: application/json');
	$this->response->setOutput(json_encode($result));
    }




}
