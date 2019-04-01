<?php
class ControllerSaleOrder extends Controller {
	private $error = array();

    public function filterParams($request_params) {

        $available_params = [
            'route',
            'path',
            'category_id',
            'collection',
            'manufacturer',
            'manufacturer_id',
            'season',
            'material',
            'inside_material',
            'product_id',
            'producer',
            'color',
            'style',
            'fullness',
            'size',
            'price',
            'heel',
            'heelheight',
            'toe',
            'clasp',
            'accessory',
            'feature',
            'bootleg',
            'inside_material',
            'last_size',
            'weight',
            'height',
            'code',
            'page',
            'sort'
        ];

        $sorted = array_fill_keys($available_params, '');

        $unsorted = [];
        foreach ($request_params as $param => $value) {

            if (isset($sorted[$param])) {
                $sorted[$param] = $value;
            } else {
                $unsorted[$param] = $value;
            }
        }
        $sorted += $unsorted;

        return array_filter($sorted, function($value) { return $value !== ''; });
    }

    public function smartLength($query) {

        $length = explode('&', $query);
        foreach ($length as $k => $l) {
            $l = explode('=', $l);
            $n = strlen(reset($l));
            $l = intval(end($l));

            $length[$k] = $n + $l;
        }
        return array_sum($length);
    }

    public function getPageUrl($route, $request_queries = '') {

        $queries = [];
        $cache_data = $this->cache->get('seo_pro');
        foreach ($cache_data['queries'] as $query => $data) {
            $queries[$query] = $data['keyword'];
        }
        $manufacturers = $cache_data['manufacturer'];

        $url = '';

        $data = array();
        parse_str($request_queries, $data);

        if (isset($data['path'])) {
            $category = $data['path'];

            $data['category_id'] = $category;
            unset($data['path']);
        }

        if (isset($data['manufacturer'])) {
            $manufacturer = $data['manufacturer'];

            $data['manufacturer_id'] = $manufacturer;
            unset($data['manufacturer']);
        }

        switch ($route) {

            case 'product/category':

                if (!isset($data['category_id'])) {
                    $data['category_id'] = 1;
                }
                if (isset($data['manufacturer_id']) && $data['category_id'] == 1) {

                    if (isset($queries['route=product/manufacturer']) && isset($manufacturers[$data['manufacturer_id']])) {

                        $params = array_filter($data, function($name) {
                            return !in_array($name, ['category_id', 'manufacturer_id']);
                        }, ARRAY_FILTER_USE_KEY);

                        $url = $queries['route=product/manufacturer'] . '/' . $manufacturers[$data['manufacturer_id']] . '/' . (!empty($params)? '?' . http_build_query($params) : '');
                    } else {
                        $url = 'index.php?route=product/category' . (!empty($data)? '&' . http_build_query($data) : '');
                    }
                }

                break;

            case 'product/product':
                if (isset($data['product_id'])) {
                    $url = $data['product_id'];
                }
                break;

            case 'common/home':
                $url = '/';
                break;

            case 'common/home/index':
                $url = '/';
                break;

            default:
                $term = 'route=' . $route;

                if (isset($queries[$term . (!empty($data)? '&' . http_build_query($data) : '')])) {
                    $url = $queries[$term . (!empty($data)? '&' . http_build_query($data) : '')] . '/';
                    $data = [];

                } else {
                    $url = 'index.php?' . $term . (!empty($data)? '&' . http_build_query($data) : '');
                }
                break;
        }

        if (empty($url)) {

            if (count($data)) {

                $query_data = [];
                $data = $this->filterParams($data);

                // find path
                foreach ($data as $param => $value) {
                    $query_data[] = $param . '=' . $value;
                }

                $get_params = [];
                $match_length = 0;
                $string = '';
                for ($i = 0; $i < count($query_data); $i++) {
                    $string .= '&' . $query_data[$i];
                    $string = trim(utf8_strtolower($string), '&');

                    if (isset($queries[$string])) {

                        $length = $this->smartLength($string);

                        if ($length > $match_length) {

                            $match_length = $length;
                            $url = $queries[$string];

                            parse_str($string, $get_params);
                        }
                    }
                }

                foreach ($get_params as $param => $value) {
                    unset($data[$param]);
                }

                if (!empty($url)) {

                    if (!empty($data)) {
                        foreach ($data as $param => $value) {
                            if (isset($queries[$param . '=' . $value])) {
                                $url .= '/' . $queries[$param . '=' . $value];
                                unset($data[$param]);

                                continue;
                            }
                        }
                    }

                    if (isset($data['manufacturer_id'])) {

                        if (isset($manufacturers[$data['manufacturer_id']])) {
                            $url .= '/brand_' . $manufacturers[$data['manufacturer_id']];
                            unset($data['manufacturer_id']);

                            $count = 2;
                            if (isset($data['material'])) {
                                $url .= '/material_' . $data['material'];
                                unset($data['material']);
                                $count--;
                            }
                            if (isset($data['color'])) {
                                $url .= '/color_' . $data['color'];
                                unset($data['color']);
                                $count--;
                            }
                            if (isset($data['season']) && $count > 0) {
                                $url .= '/season_' . $data['season'];
                                unset($data['season']);
                            }
                        }
                    }

                    if (!empty($data)) {
                        $url .= '/?' . urldecode(http_build_query($data, '', '&'));
                    } else {
                        $url .= '/';
                    }

                } else {
                    $url = 'index.php?route=' . $route;

                    if (!empty($data)) {
                        $url .= '&' . urldecode(http_build_query($data, '', '&'));
                    }
                }
            }
        }

        $url = ltrim(utf8_strtolower($url), '/');

        if (substr($url, -2) == '//') {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    protected $alphabet = [
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',    'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',    'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    ];

    public function getProductUrl($product) {

        $query = $this->db->query("SELECT p2c.category_id, cd.*, c.* FROM " . DB_PREFIX . "product_to_category p2c" .
            " LEFT JOIN " . DB_PREFIX . "category_description cd ON p2c.category_id = cd.category_id AND cd.site_id IS NULL" .
            " LEFT JOIN " . DB_PREFIX . "category c on p2c.category_id = c.category_id" .
            " WHERE p2c.product_id = '" . (int) $product['product_id'] . "'" .
            " ORDER BY c.category_id DESC LIMIT 1");

        $product_category = $query->row;

        if ($product['category_id'] != $product_category['category_id']) {
            $product['category_id'] = $product_category['category_id'];
        }

        $product_link = $this->getPageUrl('product/category', 'category_id=' . $product['category_id']);

        if (strpos($product_link, '?') !== false) {
            $product_link = $this->getPageUrl('product/product', 'product_id=' . $product['product_id']);
        } else {
            $product_link = $product_link;

            $data = $this->db->query('SELECT ovd.*, ov.sort_order FROM `' . DB_PREFIX . 'option_value` ov JOIN `' . DB_PREFIX . 'option_value_description` ovd ON ov.option_value_id = ovd.option_value_id AND ovd.language_id = 2 WHERE ov.option_id = 1 ORDER BY ov.option_id, ov.sort_order');

            $options = [];

            foreach ($data->rows as $row) {
                $options[$row['option_value_id']] = $row;
            }

            $product_url = '';

            $materialOption = $this->db->query('SELECT option_value FROM `' . DB_PREFIX . 'product_option` WHERE product_id = ' . $product['product_id'] . ' AND option_id = 1');

            $materialOptionIds = [];
            if (!empty($materialOption->rows)) {
                $materialOptionIds = array_column($materialOption->rows, 'option_value');
            }

            if (!empty($materialOptionIds) && !in_array($product['category_id'], [305, 304, 303])
            ) {
                $materials = [];
                if (!empty($options)) {
                    foreach ((array)$options as $key => $value) {
                        $materials[(int)$key] = $value['nominative_plural'];
                    }
                }

                if (array_key_exists($materialOptionIds[0], $materials) && $materials[$materialOptionIds[0]] != '') {
                    $product_url .= $materials[$materialOptionIds[0]] . '_';
                }
            }

            if (!empty($product['manufacturer'])) {
                $product_url .= $product['manufacturer'] . '_';
            }

            if (!empty($product['name'])) {
                $product_url .= $product['name'] . '_';
            }

            $product_url .= $product['product_id'] .= '_';

            $formatted_string = '';

            for ($i = 0; $i < strlen($product_url); $i++) {
                if (array_key_exists(mb_substr($product_url, $i, 1, "utf-8"), $this->alphabet)) {
                    $formatted_string .= $this->alphabet[mb_substr($product_url, $i, 1, "utf-8")];
                } else {
                    $formatted_string .= mb_substr($product_url, $i, 1, "utf-8");
                }
            }

            $formatted_string = strtolower($formatted_string);

            $formatted_string = preg_replace('~[^a-z0-9_]+~u', '_', $formatted_string);

            $formatted_string = trim($formatted_string, "_");

            $product_link .= $formatted_string;
        }

        return $product_link;
    }

	public function index() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sale/order');

		$this->getList();
	}

	public function insert() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sale/order');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_sale_order->addOrder($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}
			if (isset($this->request->get['filter_h'])) {
				foreach($this->request->get['filter_h'] as $h)
					$url .= '&filter_h[]=' . $h ;
			}
			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}

			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['filter_email'])) {
				$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
			}

			$this->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL'));
		}

		$this->getForm();
	}

	public function update() {
		$lang = $this->load->language('sale/order');

		foreach ($lang as $key => $value) {
			$this->data[$key] = $value;
		}
		$this->data['button_update_total'] = $this->language->get('button_update_total');
		$this->data['invoice'] = $this->url->link(
			'sale/order/invoice',
			'token=' . $this->session->data['token'] . '&order_id=' . (int)$this->request->get['order_id'],
			'SSL'
		);

		$this->load->model('sale/order');
		$this->load->model('page/payment');
		$this->load->model('page/shipping');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $order_product_data = [];
            $post_data = $this->request->post;

            foreach ($post_data['order_product'] as $order_product) {
                if (!array_key_exists($order_product['order_product_id'], $order_product_data)) {
                    $order_product_data[$order_product['order_product_id']] = $order_product;
                } else {
                    $order_product_data[$order_product['order_product_id']]['quantity']++;
                }
            }

            $post_data['order_product'] = $order_product_data;

			// Add Status Change to History
			$order = $this->model_sale_order->getOrder($this->request->get['order_id']);

            if (isset($this->request->post['change_client_id']) && !empty($this->request->post['change_client_id'])) {
                // re-assign order to another customer
                $this->load->model('sale/customer');

                $old_customer = $this->model_sale_customer->getCustomer($order['customer_id']);
                $new_customer = $this->model_sale_customer->getCustomer($this->request->post['change_client_id']);

                if ($new_customer) {
                    $old_customer_string = $old_customer['customer_id'] . ': ' . $old_customer['lastnname'] . ' ' . $old_customer['firstname'] . ' ' . $old_customer['middlename'];
                    $old_customer_string = trim($old_customer_string) . ' (';
                    if (!empty($old_customer['telephone'])) {
                        $old_customer_string .= $old_customer['telephone'] . ', ';
                    }
                    if (!empty($old_customer['telephone2'])) {
                        $old_customer_string .= $old_customer['telephone2'] . ', ';
                    }
                    if (!empty($old_customer['telephone3'])) {
                        $old_customer_string .= $old_customer['telephone3'] . ', ';
                    }
                    if (!empty($old_customer['email'])) {
                        $old_customer_string .= $old_customer['email'] . ', ';
                    }
                    if (!empty($old_customer['email2'])) {
                        $old_customer_string .= $old_customer['email2'] . ', ';
                    }
                    if (!empty($old_customer['email3'])) {
                        $old_customer_string .= $old_customer['email3'];
                    }
                    $old_customer_string = trim($old_customer_string, ', ') . ')';

                    $new_customer_string = $new_customer['customer_id'] . ': ' . $new_customer['lastnname'] . ' ' . $new_customer['firstname'] . ' ' . $new_customer['middlename'];
                    $new_customer_string = trim($new_customer_string) . ' (';
                    if (!empty($new_customer['telephone'])) {
                        $new_customer_string .= $new_customer['telephone'] . ', ';
                    }
                    if (!empty($new_customer['telephone2'])) {
                        $new_customer_string .= $new_customer['telephone2'] . ', ';
                    }
                    if (!empty($new_customer['telephone3'])) {
                        $new_customer_string .= $new_customer['telephone3'] . ', ';
                    }
                    if (!empty($new_customer['email'])) {
                        $new_customer_string .= $new_customer['email'] . ', ';
                    }
                    if (!empty($new_customer['email2'])) {
                        $new_customer_string .= $new_customer['email2'] . ', ';
                    }
                    if (!empty($new_customer['email3'])) {
                        $new_customer_string .= $new_customer['email3'];
                    }
                    $new_customer_string = trim($new_customer_string, ', ') . ')';

                    $modify_str = 'Заказ №' . $this->request->get['order_id']
                        . ' переназначен с клиента ' . $old_customer_string
                        . ' на клиента ' . $new_customer_string;
                    $data = [
                        'order_status_id' => $this->request->post['order_status_id'],
                        'comment'         => $modify_str,
                        'notify'          => false,
                    ];
                    $this->model_sale_order->addOrderHistory($this->request->get['order_id'], $data);

                    $this->db->query('UPDATE `order` SET customer_id = ' . (int)$new_customer['customer_id'] . ' WHERE order_id = ' . (int)$this->request->post['order_id']);

                    $post_data['client_card_number'] = $new_customer['client_card_number'];
                    foreach ($post_data['order_product'] as $key => $product_data) {
                        $post_data['order_product'][$key]['client_card_discount'] = $new_customer['client_card_discount'];
                    }

                    if (isset($this->request->post['remove_current_client']) && ($this->request->post['remove_current_client'] == 1)) {
                        // remove customer which created this order
                        $check_customer_orders = $this->model_sale_order->getOrdersCountByCustomerId($old_customer['customer_id']);

                        if ($check_customer_orders == 0) {
                            $this->model_sale_customer->deleteCustomer($old_customer['customer_id']);

                            $modify_str = 'Удалён предыдущий клиент ' . $old_customer_string;
                            $data = [
                                'order_status_id' => $this->request->post['order_status_id'],
                                'comment'         => $modify_str,
                                'notify'          => false,
                            ];
                            $this->model_sale_order->addOrderHistory($this->request->get['order_id'], $data);
                        } else {
                            $modify_str = 'Не удалось удалить предыдущего клиента ' . $old_customer_string
                                . ' так как у него имеются другие заказы.';
                            $data = [
                                'order_status_id' => $this->request->post['order_status_id'],
                                'comment'         => $modify_str,
                                'notify'          => false,
                            ];
                            $this->model_sale_order->addOrderHistory($this->request->get['order_id'], $data);
                        }
                    }
                }
            }

			$this->model_sale_order->editOrder($this->request->get['order_id'], $post_data);//$this->request->post);


			if ($order['order_status_id'] !== $this->request->post['order_status_id']) {

				$data = array(
					'order_status_id' => $this->request->post['order_status_id'],
					'comment' => 'Статус заказа был изменен оператором',
					'notify' => false
				);
				$this->model_sale_order->addOrderHistory($this->request->get['order_id'], $data);

                if ($order['order_status_id'] != 4 && $this->request->post['order_status_id'] == 4
                    && isset($this->request->post['order_sent_notification'])
                    && $this->request->post['order_sent_notification'] == 1
                ) {
                    // status changed to "Sent" and want to send email - notify client
                    $this->orderSentNotification($this->request->get['order_id']);
                }

                if ($order['order_status_id'] != 3 && $this->request->post['order_status_id'] == 3
                    && isset($this->request->post['order_completed_notification'])
                    && $this->request->post['order_completed_notification'] == 1
                ) {
                    // status changed to "Completed" - send email to the client
                    $this->orderCompletedNotification($this->request->get['order_id']);
                }

                $shipment_price_data = $this->get_shipment_rate($this->request->get['order_id']);
                $this->request->post['shipment_rate'] = $shipment_price_data['shipment_rate'];
                $this->request->post['shipment_agent_fee'] = $shipment_price_data['shipment_agent_fee'];

                // status changed to "Refunded" or "Declined"
                if (in_array($this->request->post['order_status_id'], [6, 5])) {

                    // send request to Google Analytics

                    list($version, $domainDepth, $cid1, $cid2) = explode('.', $order['client_id'],4);
                    $contents = array('version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid1.'.'.$cid2);

                    $requestData = [
                        'v' => 1,
                        'tid' => 'UA-59169628-1',
                        'cid' => $contents['cid'],
                        't' => 'event',
                        'ea' => 'Full Refund',
                        'ec' => 'Enhanced Ecommerce',
                        'pa' => 'refund',
                        'ti' => $order['order_id']
                    ];

                    $url = 'http://www.google-analytics.com/collect';
                    $content = http_build_query($requestData);
                    $content = utf8_encode($content);

					$ch = curl_init();
					curl_setopt($ch,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-type: application/x-www-form-urlencoded'));
					curl_setopt($ch,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
					curl_setopt($ch,CURLOPT_POST, TRUE);
					curl_setopt($ch,CURLOPT_POSTFIELDS, $content);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_exec($ch);
					curl_close($ch);
                }
			}
			$modify = array();
			foreach($this->request->post as $key => $p) {
				if(isset($order[$key])) {
					if($order[$key] != $p) {
						if($key == 'date_sent' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
							continue;
						if($key == 'date_sent' && strtotime($p) == strtotime($order[$key]))
							continue;
						if($key == 'date_take' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
							continue;
						if($key == 'date_take' && strtotime($p) == strtotime($order[$key]))
							continue;
						if($key == 'date_return' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
							continue;
						if($key == 'date_return' && strtotime($p) == strtotime($order[$key]))
							continue;
						if($key == 'date_barter' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
							continue;
						if($key == 'date_barter' && strtotime($p) == strtotime($order[$key]))
							continue;
						if($key == 'date_trans' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
							continue;
						if($key == 'date_trans' && strtotime($p) == strtotime($order[$key]))
							continue;
						if($key == 'shipping_country_id' || $key == 'prefix_region' || $key == 'prefix_city' || $key == 'order_status_id')
							continue;
                        if (in_array($key, ['manager_id', 'packer_id']) && empty($order[$key]) && empty($p))
                            continue;
						$new_value = $p;
						$old_value = $order[$key];
						$name_key = $key;
						switch($key) {
							case 'payment_id':
								$new_value = $this->model_page_payment->getPayment($new_value);
								$old_value = $this->model_page_payment->getPayment($old_value);
								$new_value = $new_value['name'];
								$old_value = $old_value['name'];
								$name_key = 'Способ оплаты';
								break;
							case 'shipping_id':
								$new_value = $this->model_page_shipping->getShipping($new_value);
								$old_value = $this->model_page_shipping->getShipping($old_value);
								$new_value = $new_value['name'];
								$old_value = $old_value['name'];
								$name_key = 'Способ доставки';
								break;
							case 'fio':
								$name_key = 'ФИО';
								break;
							case 'client_card_number':
								$name_key = 'Номер карты';
								break;
							case 'telephone':
								$name_key = 'Телефон';
								break;
							case 'email':
								$name_key = 'Почта';
								break;
							case 'shipping_postcode':
								$name_key = 'Индекс';
								break;
							case 'shipping_region':
								$name_key = 'Регион';
								break;
							case 'shipping_city':
								$name_key = 'Город';
								break;
							case 'shipping_street':
								$name_key = 'Улица';
								break;
							case 'shipping_house':
								$name_key = 'Дом';
								break;
							case 'manager':
								$name_key = 'Менеджер';
								break;
							case 'packer':
								$name_key = 'Упаковщик';
								break;
							case 'shipping_flat':
								$name_key = 'Квартира';
								break;
							case 'date_sent':
								$name_key = 'Дата отправки';
								$old_value = date('d.m.Y',strtotime($old_value));
								break;
							case 'post_no':
								$name_key = 'Номер отправления';
								break;
							case 'shoes_price':
								$name_key = 'Стоимость обуви';
								break;
							case 'shipment_summ':
								$name_key = 'Сумма доставки';
								break;
							case 'shipment_rate':
								$name_key = 'Расход доставки';
								break;
							case 'shipment_agent_fee':
								$name_key = 'Агентское вознаграждение';
								break;
							case 'return_rate':
								$name_key = 'Расход возврата';
								break;
							case 'trans_money':
								$name_key = 'Перевод денег';
								break;
							case 'delivery':
								$name_key = 'Пункт выдачи';
								break;
							case 'comment':
								$name_key = 'Комментарий';
								break;
							case 'notice':
								$name_key = 'Примечание';
								break;
							case 'date_take':
								$name_key = 'Дата получения';
								break;
							case 'date_return':
								$name_key = 'Дата возврата';
								break;
							case 'date_barter':
								$name_key = 'Дата обмена';
								break;
							case 'date_trans':
								$name_key = 'Дата перевода денег';
								break;
						}
						if($key == 'payment_id' && $new_value == $old_value)
							continue;

						$modify[] = 'Поле "'.$name_key . '" изменено с "' . $old_value . '" на "' . $new_value . '"';
					}
				}
			}
			if(!empty($modify)) {
				$modify_str = implode('\n', $modify);
				$data = array(
					'order_status_id' => $this->request->post['order_status_id'],
					'comment' => $modify_str,
					'notify' => false
				);
				$this->model_sale_order->addOrderHistory($this->request->get['order_id'], $data);
			}
			unset($order);

			$this->session->data['success'] = $this->language->get('text_success');
			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';
			/*
			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}
			if (isset($this->request->get['filter_h'])) {
				foreach($this->request->get['filter_h'] as $h)
					$url .= '&filter_h[]=' . $h ;
			}
			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}

			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['filter_email'])) {
				$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
			}*/

			if (isset($this->request->get['order_id'])) {
				$url .= '&order_id=' . $this->request->get['order_id'];
			}

			$this->redirect($this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . $url, 'SSL'));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sale/order');

		if (isset($this->request->post['selected']) && ($this->validateDelete())) {
			foreach ($this->request->post['selected'] as $order_id) {
				$this->model_sale_order->deleteOrder($order_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}
			if (isset($this->request->get['filter_h'])) {
				foreach($this->request->get['filter_h'] as $h)
					$url .= '&filter_h[]=' . $h ;
			}
			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}

			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['filter_email'])) {
				$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
			}

			$this->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL'));
		}

		$this->getList();
	}

	private function getList() {
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = null;
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = null;
		}

		if (isset($this->request->get['filter_telephone'])) {
			$filter_telephone = $this->request->get['filter_telephone'];
		} else {
			$filter_telephone = null;
		}

		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
		} else {
			$filter_email = null;
		}
		if (isset($this->request->get['filter_h'])) {
			$filter_h = $this->request->get['filter_h'];
		} else {
			$filter_h = null;
		}


		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = null;
		}

		if (isset($this->request->get['filter_shipping_city'])) {
			$filter_shipping_city = $this->request->get['filter_shipping_city'];
		} else {
			$filter_shipping_city = null;
		}

		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = null;
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = date('Y-m-d H:i:s', strtotime($this->request->get['filter_date_added']));
		} else {
			$filter_date_added = null;
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$filter_date_modified = $this->request->get['filter_date_modified'];
		} else {
			$filter_date_modified = null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_h'])) {
			foreach($this->request->get['filter_h'] as $h)
			$url .= '&filter_h[]=' . $h ;
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_telephone'])) {
			$url .= '&filter_telephone=' . $this->request->get['filter_telephone'];
		}

		if (isset($this->request->get['filter_shipping_city'])) {
			$url .= '&filter_shipping_city=' . $this->request->get['filter_shipping_city'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL'),
			'separator' => ' :: '
		);

		$this->data['invoice'] = $this->url->link('sale/order/invoice', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['insert'] = $this->url->link('sale/order/insert', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['delete'] = $this->url->link('sale/order/delete', 'token=' . $this->session->data['token'] . $url, 'SSL');

		$this->data['orders'] = array();

		$data = array(
			'filter_order_id'        => $filter_order_id,
			'filter_h'              => $filter_h,
			'filter_customer'	     => $filter_customer,
			'filter_telephone'	     => $filter_telephone,
			'filter_email'	         => $filter_email,
			'filter_order_status_id' => $filter_order_status_id,
			'filter_total'           => $filter_total,
			'filter_date_added'      => $filter_date_added,
			'filter_date_modified'   => $filter_date_modified,
			'sort'                   => $sort,
			'order'                  => $order,
			'start'                  => ($page - 1) * $this->config->get('config_admin_limit'),
			'limit'                  => $this->config->get('config_admin_limit'),
			'filter_shipping_city'	 => $filter_shipping_city
		);

		$results = $this->model_sale_order->getOrders($data);

		$order_total = $this->model_sale_order->getTotalOrders($data);

		foreach ($results as $result) {
			$action = array();

			$action[] = array(
				'ico' => 'boot-icon icon-search',
				'text' => $this->language->get('text_view'),
				'href' => $this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL')
			);

			if (strtotime($result['date_added']) > strtotime('-' . (int)$this->config->get('config_order_edit') . ' day')) {
				$action[] = array(
					'ico' => 'boot-icon icon-pencil',
					'text' => $this->language->get('text_edit'),
					'href' => $this->url->link('sale/order/update', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL')
				);
			}

			$this->data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'count_before'      => $result['count_before'],
				'telephone'     => $result['telephone'],
				'customer_telephones_count' => $result['customer_telephones_count'],
				'customer_emails_count' => $result['customer_emails_count'],
				'email'         => $result['email'],
				'status'        => $result['status'],
				'count_ord'        => $result['count_ord'],
				'count_sell'        => $result['count_sell'],
				'count_return'        => $result['count_return'],
				'count_barter'        => $result['count_barter'],
				'count_buy'        => $result['count_buy'],
				'total'         => (int) $result['total_to_products'],
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
				'selected'      => isset($this->request->post['selected']) && in_array($result['order_id'], $this->request->post['selected']),
				'action'        => $action,
				'shipping_city'  => $result['shipping_city']
			);
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_no_results'] = $this->language->get('text_no_results');
		$this->data['text_missing'] = $this->language->get('text_missing');

		$this->data['column_order_id'] = $this->language->get('column_order_id');
		$this->data['column_customer'] = $this->language->get('column_customer');
		$this->data['column_count'] = $this->language->get('column_count');
		$this->data['column_telephone'] = $this->language->get('column_telephone');
		$this->data['column_email'] = $this->language->get('column_email');
		$this->data['column_count_shoes'] = $this->language->get('column_count_shoes');
		$this->data['column_ord'] = $this->language->get('column_ord');
		$this->data['column_sell'] = $this->language->get('column_sell');
		$this->data['column_buy'] = $this->language->get('column_buy');
		$this->data['column_return'] = $this->language->get('column_return');
		$this->data['column_barter'] = $this->language->get('column_barter');
		$this->data['column_status'] = $this->language->get('column_status');
		$this->data['column_total'] = $this->language->get('column_total');
		$this->data['column_date_added'] = $this->language->get('column_date_added');
		$this->data['column_date_modified'] = $this->language->get('column_date_modified');
		$this->data['column_action'] = $this->language->get('column_action');
		$this->data['column_shipping_city'] = $this->language->get('column_shipping_city');

		$this->data['button_invoice'] = $this->language->get('button_invoice');
		$this->data['button_insert'] = $this->language->get('button_insert');
		$this->data['button_delete'] = $this->language->get('button_delete');
		$this->data['button_filter'] = $this->language->get('button_filter');

		$this->data['token'] = $this->session->data['token'];

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$this->data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$this->data['success'] = '';
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}
		if (isset($this->request->get['filter_h'])) {
			foreach($this->request->get['filter_h'] as $h)
				$url .= '&filter_h[]=' . $h ;
		}
		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_telephone'])) {
			$url .= '&filter_telephone=' . urlencode(html_entity_decode($this->request->get['filter_telephone'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_shipping_city'])) {
			$url .= '&filter_shipping_city=' . urlencode(html_entity_decode($this->request->get['filter_shipping_city'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$this->data['sort_order'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.order_id' . $url, 'SSL');
		$this->data['sort_customer'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=customer' . $url, 'SSL');
		$this->data['sort_telephone'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=telephone' . $url, 'SSL');
		$this->data['sort_email'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=email' . $url, 'SSL');
		$this->data['sort_status'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=status' . $url, 'SSL');
		$this->data['sort_shipping_city'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=shipping_city' . $url, 'SSL');
		$this->data['sort_total'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.total' . $url, 'SSL');
		$this->data['sort_date_added'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.date_added' . $url, 'SSL');
		$this->data['sort_date_modified'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . '&sort=o.date_modified' . $url, 'SSL');

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}
		if (isset($this->request->get['filter_h'])) {
			foreach($this->request->get['filter_h'] as $h)
				$url .= '&filter_h[]=' . $h ;
		}
		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_telephone'])) {
			$url .= '&filter_telephone=' . urlencode(html_entity_decode($this->request->get['filter_telephone'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_shipping_city'])) {
			$url .= '&filter_shipping_city=' . urlencode(html_entity_decode($this->request->get['filter_shipping_city'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_admin_limit');
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url . '&page={page}', 'SSL');

		$this->data['pagination'] = $pagination->render();

		$this->data['filter_order_id'] = $filter_order_id;
		$this->data['filter_h'] = $filter_h;
		$this->data['filter_customer'] = $filter_customer;
		$this->data['filter_telephone'] = $filter_telephone;
		$this->data['filter_email'] = $filter_email;
		$this->data['filter_order_status_id'] = $filter_order_status_id;
		$this->data['filter_shipping_city'] = $filter_shipping_city;
		$this->data['filter_total'] = $filter_total;
		$this->data['filter_date_added'] = ($filter_date_added)? date('d.m.y', strtotime($filter_date_added)) : '';
		$this->data['filter_date_modified'] = $filter_date_modified;

		$this->load->model('localisation/order_status');

		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->data['sort'] = $sort;
		$this->data['order'] = $order;

		$this->template = 'sale/order_list.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	public function savePayShip() {

		$this->load->model('sale/order');
		$this->load->model('page/payment');
		$this->load->model('page/shipping');

		$order = $this->model_sale_order->getOrder($this->request->get['order_id']);
		$this->model_sale_order->editOrder($this->request->get['order_id'], $this->request->post, true);
		if ($order['order_status_id'] !== $this->request->post['order_status_id']) {
			$data = array(
				'order_status_id' => $this->request->post['order_status_id'],
				'comment' => 'Статус заказа был изменен оператором',
				'notify' => false
			);
			$this->model_sale_order->addOrderHistory($this->request->get['order_id'], $data);
		}
		$modify = array();
		foreach($this->request->post as $key => $p) {
			if(isset($order[$key])) {
				if($order[$key] != $p) {
					if($key == 'date_sent' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
						continue;
					if($key == 'date_sent' && strtotime($p) == strtotime($order[$key]))
						continue;
					if($key == 'date_take' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
						continue;
					if($key == 'date_take' && strtotime($p) == strtotime($order[$key]))
						continue;
					if($key == 'date_return' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
						continue;
					if($key == 'date_return' && strtotime($p) == strtotime($order[$key]))
						continue;
					if($key == 'date_barter' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
						continue;
					if($key == 'date_barter' && strtotime($p) == strtotime($order[$key]))
						continue;
					if($key == 'date_trans' && empty($p) && $order[$key] == '0000-00-00 00:00:00')
						continue;
					if($key == 'date_trans' && strtotime($p) == strtotime($order[$key]))
						continue;
					if($key == 'shipping_country_id' || $key == 'prefix_region' || $key == 'prefix_city' || $key == 'order_status_id')
						continue;
					$new_value = $p;
					$old_value = $order[$key];
					$name_key = $key;
					switch($key) {
						case 'payment_id':
							$new_value = $this->model_page_payment->getPayment($new_value);
							$old_value = $this->model_page_payment->getPayment($old_value);
							$new_value = $new_value['name'];
							$old_value = $old_value['name'];
							$name_key = 'Способ оплаты';
							break;
						case 'shipping_id':
							$new_value = $this->model_page_shipping->getShipping($new_value);
							$old_value = $this->model_page_shipping->getShipping($old_value);
							$new_value = $new_value['name'];
							$old_value = $old_value['name'];
							$name_key = 'Способ доставки';
							break;
						case 'fio':
							$name_key = 'ФИО';
							break;
						case 'client_card_number':
							$name_key = 'Номер карты';
							break;
						case 'telephone':
							$name_key = 'Телефон';
							break;
						case 'email':
							$name_key = 'Почта';
							break;
						case 'shipping_postcode':
							$name_key = 'Индекс';
							break;
						case 'shipping_region':
							$name_key = 'Регион';
							break;
						case 'shipping_city':
							$name_key = 'Город';
							break;
						case 'shipping_street':
							$name_key = 'Улица';
							break;
						case 'shipping_house':
							$name_key = 'Дом';
							break;
						case 'manager':
							$name_key = 'Менеджер';
							break;
						case 'packer':
							$name_key = 'Упаковщик';
							break;
						case 'shipping_flat':
							$name_key = 'Квартира';
							break;
						case 'date_sent':
							$name_key = 'Дата отправки';
							$old_value = date('d.m.Y',strtotime($old_value));
							break;
						case 'post_no':
							$name_key = 'Номер отправления';
							break;
						case 'shoes_price':
							$name_key = 'Стоимость обуви';
							break;
						case 'shipment_summ':
							$name_key = 'Сумма доставки';
							break;
						case 'shipment_rate':
							$name_key = 'Расход доставки';
							break;
						case 'shipment_agent_fee':
							$name_key = 'Агентское вознаграждение';
							break;
						case 'return_rate':
							$name_key = 'Расход возврата';
							break;
						case 'trans_money':
							$name_key = 'Перевод денег';
							break;
						case 'delivery':
							$name_key = 'Пункт выдачи';
							break;
						case 'comment':
							$name_key = 'Комментарий';
							break;
						case 'notice':
							$name_key = 'Примечание';
							break;
						case 'date_take':
							$name_key = 'Дата получения';
							break;
						case 'date_return':
							$name_key = 'Дата возврата';
							break;
						case 'date_barter':
							$name_key = 'Дата обмена';
							break;
						case 'date_trans':
							$name_key = 'Дата перевода денег';
							break;
					}
					if($key == 'payment_id' && $new_value == $old_value)
						continue;

					$modify[] = 'Поле "'.$name_key . '" изменено с "' . $old_value . '" на "' . $new_value . '"';
				}
			}
		}
		if(!empty($modify)) {
			$modify_str = implode('\n', $modify);
			$data = array(
				'order_status_id' => $this->request->post['order_status_id'],
				'comment' => $modify_str,
				'notify' => false
			);
			$this->model_sale_order->addOrderHistory($this->request->get['order_id'], $data);
		}
		unset($order);

		$this->response->setOutput(json_encode(array('success' => 1)));
	}

	public function getForm() {
		$this->load->model('sale/customer');

		$url = '';

		$order_info = array();

		if (isset($this->request->get['order_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
		}

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['order_id'])) {
			$url .= '&order_id=' . $this->request->get['order_id'];
		}

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL'),
			'separator' => ' :: '
		);

		if (!isset($this->request->get['order_id'])) {
			$this->data['action'] = $this->url->link('sale/order/insert', 'token=' . $this->session->data['token'] . $url, 'SSL');
		} else {
			$this->data['action'] = $this->url->link('sale/order/update', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . $url, 'SSL');
		}

		$this->data['cancel'] = $this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . $url, 'SSL');

		$date_added = (isset($order_info['date_added']))? date(
			$this->language->get('date_format_short') . ' ' . $this->language->get('time_format'),
			strtotime($order_info['date_added'])) : '';
		$this->data['heading_title'] = sprintf(
			$this->language->get('heading_order_title'),
			$this->request->get['order_id'],
			$date_added
		);
		$this->document->setTitle($this->data['heading_title']);

		$this->data['token'] = $this->session->data['token'];

		$this->load->model('sale/manager');

		$this->data['managers'] = $this->model_sale_manager->getManagerOptions();

		if (isset($this->request->get['order_id'])) {
			$this->data['order_id'] = $this->request->get['order_id'];
		} else {
			$this->data['order_id'] = 0;
		}

		if (isset($this->request->post['date_added'])) {
			$this->data['date_added'] = $this->request->post['date_added'];
		} elseif (!empty($order_info['date_added']) && $order_info['date_added'] != '0000-00-00 00:00:00') {
			$this->data['date_added'] = date(
				$this->language->get('date_format_short') . $this->language->get('time_format'),
				strtotime($order_info['date_added'])
			);
		} else {
			$this->data['date_added'] = '';
		}

		if (isset($this->request->post['date_sent'])) {
			$this->data['date_sent'] = $this->request->post['date_sent'];
		} elseif (!empty($order_info['date_sent']) && $order_info['date_sent'] != '0000-00-00 00:00:00') {
			$this->data['date_sent'] = date(
				$this->language->get('date_format_short'), strtotime($order_info['date_sent']));
		} else {
			$this->data['date_sent'] = '';
		}
		if (isset($this->request->post['date_take'])) {
			$this->data['date_take'] = $this->request->post['date_take'];
		} elseif (!empty($order_info['date_take']) && $order_info['date_take'] != '0000-00-00 00:00:00') {
			$this->data['date_take'] = date(
				$this->language->get('date_format_short'), strtotime($order_info['date_take']));
		} else {
			$this->data['date_take'] = '';
		}
		if (isset($this->request->post['date_return'])) {
			$this->data['date_return'] = $this->request->post['date_return'];
		} elseif (!empty($order_info['date_return']) && $order_info['date_return'] != '0000-00-00 00:00:00') {
			$this->data['date_return'] = date(
				$this->language->get('date_format_short'), strtotime($order_info['date_return']));
		} else {
			$this->data['date_return'] = '';
		}
		if (isset($this->request->post['date_barter'])) {
			$this->data['date_barter'] = $this->request->post['date_barter'];
		} elseif (!empty($order_info['date_barter']) && $order_info['date_barter'] != '0000-00-00 00:00:00') {
			$this->data['date_barter'] = date(
				$this->language->get('date_format_short'), strtotime($order_info['date_barter']));
		} else {
			$this->data['date_barter'] = '';
		}
		if (isset($this->request->post['date_trans'])) {
			$this->data['date_trans'] = $this->request->post['date_trans'];
		} elseif (!empty($order_info['date_trans']) && $order_info['date_trans'] != '0000-00-00 00:00:00') {
			$this->data['date_trans'] = date(
				$this->language->get('date_format_short'), strtotime($order_info['date_trans']));
		} else {
			$this->data['date_trans'] = '';
		}

		if (isset($this->request->post['total'])) {
			$this->data['total'] = (int) $this->request->post['total'];
		} elseif (!empty($order_info)) {
			$this->data['total'] = (int) $order_info['total_to_products'];
		} else {
			$this->data['total'] = '';
		}

		if (isset($this->request->post['client_card_number'])) {
			$this->data['client_card_number'] = $this->request->post['client_card_number'];
		} elseif (!empty($order_info)) {
			$this->data['client_card_number'] = $order_info['client_card_number'];
		} else {
			$this->data['client_card_number'] = '';
		}

		if (isset($this->request->post['fio'])) {
			$this->data['fio'] = $this->request->post['fio'];
		} elseif (!empty($order_info)) {
			$this->data['fio'] = $order_info['fio'];
		} else {
			$this->data['fio'] = '';
		}

		if (isset($this->request->post['manager_id'])) {
			$this->data['manager_id'] = $this->request->post['manager_id'];
		} elseif (!empty($order_info)) {
			$this->data['manager_id'] = $order_info['manager_id'];
		} else {
			$this->data['manager_id'] = '';
		}

		if (isset($this->request->post['packer_id'])) {
			$this->data['packer_id'] = $this->request->post['packer_id'];
		} elseif (!empty($order_info)) {
			$this->data['packer_id'] = $order_info['packer_id'];
		} else {
			$this->data['packer_id'] = '';
		}

        if (!empty($order_info)) {
            $this->data['manager'] = $order_info['manager'];
        } else {
            $this->data['manager'] = '';
        }

		if (isset($this->request->post['email'])) {
			$this->data['email'] = $this->request->post['email'];
		} elseif (!empty($order_info)) {
			$this->data['email'] = $order_info['email'];
		} else {
			$this->data['email'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$this->data['telephone'] = $this->request->post['telephone'];
		} elseif (!empty($order_info)) {
			$this->data['telephone'] = $order_info['telephone'];
		} else {
			$this->data['telephone'] = '';
		}

		if (isset($this->request->post['ready_notify'])) {
			$this->data['ready_notify'] = $this->request->post['ready_notify'];
		} elseif (!empty($order_info)) {
			$this->data['ready_notify'] = $order_info['ready_notify'];
		} else {
			$this->data['ready_notify'] = '';
		}

		if (isset($this->request->post['order_status_id'])) {
			$this->data['order_status_id'] = $this->request->post['order_status_id'];
		} elseif (!empty($order_info)) {
			$this->data['order_status_id'] = $order_info['order_status_id'];
		} else {
			$this->data['order_status_id'] = '';
		}

		$this->load->model('localisation/order_status');

		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['comment'])) {
			$this->data['comment'] = $this->request->post['comment'];
		} elseif (!empty($order_info['comment'])) {
			$this->data['comment'] = $order_info['comment'];
		} else {
			$this->data['comment'] = '';
		}

		if (isset($this->request->post['notice'])) {
			$this->data['notice'] = $this->request->post['notice'];
		} elseif (!empty($order_info['notice'])) {
			$this->data['notice'] = $order_info['notice'];
		} else {
			$this->data['notice'] = '';
		}

		if (isset($this->request->post['post_no'])) {
			$this->data['post_no'] = $this->request->post['post_no'];
		} elseif (!empty($order_info)) {
			$this->data['post_no'] = $order_info['post_no'];
		} else {
			$this->data['post_no'] = '';
		}
		if (isset($this->request->post['shoes_price'])) {
			$this->data['shoes_price'] = (int)$this->request->post['shoes_price'];
		} elseif (!empty($order_info)) {
			$this->data['shoes_price'] = (int)$order_info['shoes_price'];
		} else {
			$this->data['shoes_price'] = '';
		}
		if (isset($this->request->post['shipment_summ'])) {
			$this->data['shipment_summ'] = (int)$this->request->post['shipment_summ'];
		} elseif (!empty($order_info)) {
			$this->data['shipment_summ'] = (int)$order_info['shipment_summ'];
		} else {
			$this->data['shipment_summ'] = '';
		}
		if (isset($this->request->post['shipment_rate'])) {
			$this->data['shipment_rate'] = (float) preg_replace('/\,+/', '.', $this->request->post['shipment_rate']);
		} elseif (!empty($order_info)) {
			$this->data['shipment_rate'] = (float) preg_replace('/\,+/', '.', $order_info['shipment_rate']);
		} else {
			$this->data['shipment_rate'] = '';
		}
		if (isset($this->request->post['shipment_agent_fee'])) {
			$this->data['shipment_agent_fee'] = (float) preg_replace('/\,+/', '.', $this->request->post['shipment_agent_fee']);
		} elseif (!empty($order_info)) {
			$this->data['shipment_agent_fee'] = (float) preg_replace('/\,+/', '.', $order_info['shipment_agent_fee']);
		} else {
			$this->data['shipment_agent_fee'] = '';
		}
		if (isset($this->request->post['return_rate'])) {
			$this->data['return_rate'] = (float) preg_replace('/\,+/', '.', $this->request->post['return_rate']);
		} elseif (!empty($order_info)) {
			$this->data['return_rate'] = (float) preg_replace('/\,+/', '.', $order_info['return_rate']);
		} else {
			$this->data['return_rate'] = '';
		}
		if (isset($this->request->post['trans_money'])) {
			$this->data['trans_money'] = (int)$this->request->post['trans_money'];
		} elseif (!empty($order_info)) {
			$this->data['trans_money'] = (int)$order_info['trans_money'];
		} else {
			$this->data['trans_money'] = '';
		}

		if (isset($this->request->post['payment_region'])) {
			$this->data['payment_region'] = $this->request->post['payment_region'];
		} elseif (!empty($order_info)) {
			$this->data['payment_region'] = $order_info['payment_region'];
		} else {
			$this->data['payment_region'] = '';
		}

		if (isset($this->request->post['payment_city'])) {
			$this->data['payment_city'] = $this->request->post['payment_city'];
		} elseif (!empty($order_info)) {
			$this->data['payment_city'] = $order_info['payment_city'];
		} else {
			$this->data['payment_city'] = '';
		}

		if (isset($this->request->post['payment_postcode'])) {
			$this->data['payment_postcode'] = $this->request->post['payment_postcode'];
		} elseif (!empty($order_info)) {
			$this->data['payment_postcode'] = $order_info['payment_postcode'];
		} else {
			$this->data['payment_postcode'] = '';
		}

		if (isset($this->request->post['payment_method'])) {
			$this->data['payment_method'] = $this->request->post['payment_method'];
		} elseif (!empty($order_info)) {
			$this->data['payment_method'] = $order_info['payment_method'];
		} else {
			$this->data['payment_method'] = '';
		}

		$this->load->model('page/payment');

		if (isset($this->request->post['payment_id'])) {
			$this->data['payment_id'] = $this->request->post['payment_id'];
		} elseif (!empty($order_info)) {
			$this->data['payment_id'] = $order_info['payment_id'];
		} else {
			$this->data['payment_id'] = '';
		}

		if (isset($this->request->post['payment_code'])) {
			$this->data['payment_code'] = $this->request->post['payment_code'];
		} elseif (!empty($order_info)) {
			$this->data['payment_code'] = $order_info['payment_code'];
		} else {
			$this->data['payment_code'] = '';
		}

		if (isset($this->request->post['shipping_company'])) {
			$this->data['shipping_company'] = $this->request->post['shipping_company'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_company'] = $order_info['shipping_company'];
		} else {
			$this->data['shipping_company'] = '';
		}

		if (isset($this->request->post['shipping_region'])) {
			$this->data['shipping_region'] = $this->request->post['shipping_region'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_region'] = $order_info['shipping_region'];
		} else {
			$this->data['shipping_region'] = '';
		}

		if (isset($this->request->post['shipping_city'])) {
			$this->data['shipping_city'] = $this->request->post['shipping_city'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_city'] = $order_info['shipping_city'];
		} else {
			$this->data['shipping_city'] = '';
		}
		if (isset($this->request->post['prefix_region'])) {
			$this->data['prefix_region'] = $this->request->post['prefix_region'];
		} elseif (!empty($order_info)) {
			$this->data['prefix_region'] = $order_info['prefix_region'];
		} else {
			$this->data['prefix_region'] = '';
		}

		if (isset($this->request->post['prefix_city'])) {
			$this->data['prefix_city'] = $this->request->post['prefix_city'];
		} elseif (!empty($order_info)) {
			$this->data['prefix_city'] = $order_info['prefix_city'];
		} else {
			$this->data['prefix_city'] = '';
		}

		if (isset($this->request->post['shipping_street'])) {
			$this->data['shipping_street'] = $this->request->post['shipping_street'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_street'] = $order_info['shipping_street'];
		} else {
			$this->data['shipping_street'] = '';
		}

		if (isset($this->request->post['shipping_house'])) {
			$this->data['shipping_house'] = $this->request->post['shipping_house'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_house'] = $order_info['shipping_house'];
		} else {
			$this->data['shipping_house'] = '';
		}

		if (isset($this->request->post['shipping_flat'])) {
			$this->data['shipping_flat'] = $this->request->post['shipping_flat'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_flat'] = $order_info['shipping_flat'];
		} else {
			$this->data['shipping_flat'] = '';
		}

		if (isset($this->request->post['shipping_postcode'])) {
			$this->data['shipping_postcode'] = $this->request->post['shipping_postcode'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_postcode'] = $order_info['shipping_postcode'];
		} else {
			$this->data['shipping_postcode'] = '';
		}

		if (isset($this->request->post['shipping_country_id'])) {
			$this->data['shipping_country_id'] = $this->request->post['shipping_country_id'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_country_id'] = $order_info['shipping_country_id'];
		} else {
			$this->data['shipping_country_id'] = '';
		}

		if (isset($this->request->post['shipping_zone_id'])) {
			$this->data['shipping_zone_id'] = $this->request->post['shipping_zone_id'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_zone_id'] = $order_info['shipping_zone_id'];
		} else {
			$this->data['shipping_zone_id'] = '';
		}

		$this->load->model('localisation/country');

		$this->data['countries'] = $this->model_localisation_country->getCountries();

		if (isset($this->request->post['shipping_method'])) {
			$this->data['shipping_method'] = $this->request->post['shipping_method'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_method'] = $order_info['shipping_method'];
		} else {
			$this->data['shipping_method'] = '';
		}

		if (isset($this->request->post['delivery'])) {
			$this->data['delivery'] = $this->request->post['delivery'];
		} elseif (!empty($order_info)) {
			$this->data['delivery'] = $order_info['delivery'];
		} else {
			$this->data['delivery'] = '';
		}
		if (isset($this->request->post['delivery_code'])) {
			$this->data['delivery_code'] = $this->request->post['delivery_code'];
		} elseif (!empty($order_info)) {
			$this->data['delivery_code'] = $order_info['delivery_code'];
		} else {
			$this->data['delivery_code'] = '';
		}

		$this->load->model('page/shipping');
		$this->data['shipping'] = $this->model_page_shipping->getShippingList();

		if (isset($this->request->post['shipping_id'])) {
			$this->data['shipping_id'] = $this->request->post['shipping_id'];
			$this->data['payment'] = $this->model_page_payment->getPaymentByShippingId($this->request->post['shipping_id']);
		} elseif (!empty($order_info)) {
			$this->data['shipping_id'] = $order_info['shipping_id'];
			$this->data['payment'] = $this->model_page_payment->getPaymentByShippingId($order_info['shipping_id']);
		} else {
			$this->data['shipping_id'] = '';
			$this->data['payment'] = array();
		}

		if (isset($this->request->post['shipping_code'])) {
			$this->data['shipping_code'] = $this->request->post['shipping_code'];
		} elseif (!empty($order_info)) {
			$this->data['shipping_code'] = $order_info['shipping_code'];
		} else {
			$this->data['shipping_code'] = '';
		}

		if (isset($this->request->post['order_product'])) {
			$order_products = $this->request->post['order_product'];
		} elseif (isset($this->request->get['order_id'])) {
			$order_products = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 0);
		} else {
			$order_products = array();
		}
		if (isset($this->request->post['order_product_r'])) {
			$order_products_r = $this->request->post['order_product_r'];
		} elseif (isset($this->request->get['order_id'])) {
			$order_products_r = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 1);
		} else {
			$order_products_r = array();
		}
		if (isset($this->request->post['order_product_o'])) {
			$order_products_o = $this->request->post['order_product_o'];
		} elseif (isset($this->request->get['order_id'])) {
			$order_products_o = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 2);
		} else {
			$order_products_o = array();
		}
		if (isset($this->request->post['order_product_v'])) {
			$order_products_v = $this->request->post['order_product_v'];
		} elseif (isset($this->request->get['order_id'])) {
			$order_products_v = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 3);
		} else {
			$order_products_v = array();
		}

		$this->load->model('catalog/product');
		$this->load->model('cdek/order');

		$this->document->addScript('view/javascript/jquery/ajaxupload.js');

        $pvz = $this->getPickupPoints($order_info);

		//$pvz = $this->model_cdek_order->getCdekPvz();
		$this->data['pvz'] = $pvz;

		$this->data['order_products'] = array();

		foreach ($order_products as $order_product) {
			if (isset($order_product['order_option'])) {
				$order_option = $order_product['order_option'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_option = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_option = array();
			}

			if (isset($order_product['order_download'])) {
				$order_download = $order_product['order_download'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_download = $this->model_sale_order->getOrderDownloads($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_download = array();
			}

            for ($i = 1; $i <= $order_product['quantity']; $i++) {
                $this->data['order_products'][] = array(
                    'order_product_id' => $order_product['order_product_id'],
                    'product_id'       => $order_product['product_id'],
                    'name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['name_1c']
                    ),
                    'op_name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['op_name_1c']
                    ),
                    'name'             => $order_product['name'],
                    'op_name'             => $order_product['op_name'],
                    'return'             => $order_product['return'],
                    'discount'         => $order_product['discount'],
                    'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
                    'client_card_discount'         => $order_product['client_card_discount'],
                    'promo_code'                => $order_product['promo_code'],
                    'promo_code_discount'       => $order_product['promo_code_discount'],
                    'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                    'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
                    'size'             => $order_product['size'],
                    'option'           => $order_option,
                    'download'         => $order_download,
                    'order_original_price' => (int)$order_product['original_price'],
                    'order_price'      => (int)$order_product['order_price'],
                    'op_order_price'      => (int)$order_product['op_order_price'],
                    'quantity'         => 1,
                    'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
                    'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
                    'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
                    'total'            => (isset($order_product['total']))? (int) ($order_product['total'] / $order_product['quantity']) : '',
                    'tax'              => $order_product['tax'],
                    'reward'           => $order_product['reward']
                );
            }
			/*$this->data['order_products'][] = array(
				'order_product_id' => $order_product['order_product_id'],
				'product_id'       => $order_product['product_id'],
				'name_1c'          => preg_replace(
					array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
					array('. ', ', '),
					$order_product['name_1c']
				),
				'op_name_1c'          => preg_replace(
					array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
					array('. ', ', '),
					$order_product['op_name_1c']
				),
				'name'             => $order_product['name'],
				'op_name'             => $order_product['op_name'],
				'return'             => $order_product['return'],
				'discount'         => $order_product['discount'],
				'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
				'client_card_discount'         => $order_product['client_card_discount'],
                'promo_code'                => $order_product['promo_code'],
                'promo_code_discount'       => $order_product['promo_code_discount'],
                'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
				'size'             => $order_product['size'],
				'option'           => $order_option,
				'download'         => $order_download,
				'order_original_price' => (int)$order_product['original_price'],
				'order_price'      => (int)$order_product['order_price'],
				'op_order_price'      => (int)$order_product['op_order_price'],
				'quantity'         => $order_product['quantity'],
				'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
				'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
				'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
				'total'            => (isset($order_product['total']))? (int) $order_product['total'] : '',
				'tax'              => $order_product['tax'],
				'reward'           => $order_product['reward']
			);*/
		}
		$this->data['order_products_r'] = array();

		foreach ($order_products_r as $order_product) {
			if (isset($order_product['order_option'])) {
				$order_option = $order_product['order_option'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_option = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_option = array();
			}

			if (isset($order_product['order_download'])) {
				$order_download = $order_product['order_download'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_download = $this->model_sale_order->getOrderDownloads($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_download = array();
			}

            for ($i = 1; $i <= $order_product['quantity']; $i++) {
                $this->data['order_products_r'][] = array(
                    'order_product_id' => $order_product['order_product_id'],
                    'product_id'       => $order_product['product_id'],
                    'name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['name_1c']
                    ),
                    'op_name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['op_name_1c']
                    ),
                    'name'             => $order_product['name'],
                    'op_name'             => $order_product['op_name'],
                    'return'             => $order_product['return'],
                    'discount'         => $order_product['discount'],
                    'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
                    'client_card_discount'         => $order_product['client_card_discount'],
                    'promo_code'                => $order_product['promo_code'],
                    'promo_code_discount'       => $order_product['promo_code_discount'],
                    'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                    'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
                    'size'             => $order_product['size'],
                    'option'           => $order_option,
                    'download'         => $order_download,
                    'order_original_price' => (int)$order_product['original_price'],
                    'order_price'      => (int)$order_product['order_price'],
                    'op_order_price'      => (int)$order_product['op_order_price'],
                    'quantity'         => 1,
                    'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
                    'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
                    'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
                    'total'            => (isset($order_product['total']))? (int) ($order_product['total'] / $order_product['quantity']) : '',
                    'tax'              => $order_product['tax'],
                    'reward'           => $order_product['reward']
                );
            }
			/*$this->data['order_products_r'][] = array(
				'order_product_id' => $order_product['order_product_id'],
				'product_id'       => $order_product['product_id'],
				'name_1c'          => preg_replace(
					array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
					array('. ', ', '),
					$order_product['name_1c']
				),
				'name'             => $order_product['name'],
				'return'             => $order_product['return'],
				'discount'         => $order_product['discount'],
				'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
				'client_card_discount'         => $order_product['client_card_discount'],
				'promo_code'                => $order_product['promo_code'],
                'promo_code_discount'       => $order_product['promo_code_discount'],
                'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
				'size'             => $order_product['size'],
				'option'           => $order_option,
				'download'         => $order_download,
				'order_original_price' => (int)$order_product['original_price'],
				'order_price'      => (int)$order_product['order_price'],
				'quantity'         => $order_product['quantity'],
				'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
				'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
				'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
				'total'            => (isset($order_product['total']))? (int) $order_product['total'] : '',
				'tax'              => $order_product['tax'],
				'reward'           => $order_product['reward']
			);*/
		}
		$this->data['order_products_o'] = array();

		foreach ($order_products_o as $order_product) {
			if (isset($order_product['order_option'])) {
				$order_option = $order_product['order_option'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_option = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_option = array();
			}

			if (isset($order_product['order_download'])) {
				$order_download = $order_product['order_download'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_download = $this->model_sale_order->getOrderDownloads($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_download = array();
			}

            for ($i = 1; $i <= $order_product['quantity']; $i++) {
                $this->data['order_products_o'][] = array(
                    'order_product_id' => $order_product['order_product_id'],
                    'product_id'       => $order_product['product_id'],
                    'name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['name_1c']
                    ),
                    'op_name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['op_name_1c']
                    ),
                    'name'             => $order_product['name'],
                    'op_name'             => $order_product['op_name'],
                    'return'             => $order_product['return'],
                    'discount'         => $order_product['discount'],
                    'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
                    'client_card_discount'         => $order_product['client_card_discount'],
                    'promo_code'                => $order_product['promo_code'],
                    'promo_code_discount'       => $order_product['promo_code_discount'],
                    'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                    'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
                    'size'             => $order_product['size'],
                    'option'           => $order_option,
                    'download'         => $order_download,
                    'order_original_price' => (int)$order_product['original_price'],
                    'order_price'      => (int)$order_product['order_price'],
                    'op_order_price'      => (int)$order_product['op_order_price'],
                    'quantity'         => 1,
                    'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
                    'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
                    'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
                    'total'            => (isset($order_product['total']))? (int) ($order_product['total'] / $order_product['quantity']) : '',
                    'tax'              => $order_product['tax'],
                    'reward'           => $order_product['reward']
                );
            }
			/*$this->data['order_products_o'][] = array(
				'order_product_id' => $order_product['order_product_id'],
				'product_id'       => $order_product['product_id'],
				'name_1c'          => preg_replace(
					array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
					array('. ', ', '),
					$order_product['name_1c']
				),
				'name'             => $order_product['name'],
				'return'             => $order_product['return'],
				'discount'         => $order_product['discount'],
				'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
				'client_card_discount'         => $order_product['client_card_discount'],
				'promo_code'                => $order_product['promo_code'],
                'promo_code_discount'       => $order_product['promo_code_discount'],
                'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
				'size'             => $order_product['size'],
				'option'           => $order_option,
				'download'         => $order_download,
				'order_original_price' => (int)$order_product['original_price'],
				'order_price'      => (int)$order_product['order_price'],
				'quantity'         => $order_product['quantity'],
				'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
				'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
				'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
				'total'            => (isset($order_product['total']))? (int) $order_product['total'] : '',
				'tax'              => $order_product['tax'],
				'reward'           => $order_product['reward']
			);*/
		}
		$this->data['order_products_v'] = array();

		foreach ($order_products_v as $order_product) {
			if (isset($order_product['order_option'])) {
				$order_option = $order_product['order_option'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_option = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_option = array();
			}

			if (isset($order_product['order_download'])) {
				$order_download = $order_product['order_download'];
			} elseif (isset($this->request->get['order_id'])) {
				$order_download = $this->model_sale_order->getOrderDownloads($this->request->get['order_id'], $order_product['order_product_id']);
			} else {
				$order_download = array();
			}

            for ($i = 1; $i <= $order_product['quantity']; $i++) {
                $this->data['order_products_v'][] = array(
                    'order_product_id' => $order_product['order_product_id'],
                    'product_id'       => $order_product['product_id'],
                    'name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['name_1c']
                    ),
                    'op_name_1c'          => preg_replace(
                        array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
                        array('. ', ', '),
                        $order_product['op_name_1c']
                    ),
                    'name'             => $order_product['name'],
                    'op_name'             => $order_product['op_name'],
                    'return'             => $order_product['return'],
                    'discount'         => $order_product['discount'],
                    'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
                    'client_card_discount'         => $order_product['client_card_discount'],
                    'promo_code'                => $order_product['promo_code'],
                    'promo_code_discount'       => $order_product['promo_code_discount'],
                    'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                    'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
                    'size'             => $order_product['size'],
                    'option'           => $order_option,
                    'download'         => $order_download,
                    'order_original_price' => (int)$order_product['original_price'],
                    'order_price'      => (int)$order_product['order_price'],
                    'op_order_price'      => (int)$order_product['op_order_price'],
                    'quantity'         => 1,
                    'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
                    'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
                    'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
                    'total'            => (isset($order_product['total']))? (int) ($order_product['total'] / $order_product['quantity']) : '',
                    'tax'              => $order_product['tax'],
                    'reward'           => $order_product['reward']
                );
            }
			/*$this->data['order_products_v'][] = array(
				'order_product_id' => $order_product['order_product_id'],
				'product_id'       => $order_product['product_id'],
				'name_1c'          => preg_replace(
					array('/\.(?=[а-яА-Я])/', '/\,(?=[а-яА-Я])/'),
					array('. ', ', '),
					$order_product['name_1c']
				),
				'name'             => $order_product['name'],
				'return'             => $order_product['return'],
				'discount'         => $order_product['discount'],
				'discount_price'         => (isset($order_product['discount_price']))? (int) $order_product['discount_price'] : 0,
				'client_card_discount'         => $order_product['client_card_discount'],
				'promo_code'                => $order_product['promo_code'],
                'promo_code_discount'       => $order_product['promo_code_discount'],
                'promo_code_discount_price' => $order_product['promo_code_discount_price'],
                'price_final'               => (isset($order_product['price_final'])) ? (int)$order_product['price_final'] : '',
				'size'             => $order_product['size'],
				'option'           => $order_option,
				'download'         => $order_download,
				'order_original_price' => (int)$order_product['original_price'],
				'order_price'      => (int)$order_product['order_price'],
				'quantity'         => $order_product['quantity'],
				'original_price'   => (isset($order_product['original_price']))? (int) $order_product['original_price'] : '',
				'sale_price'       => (isset($order_product['sale_price']))? (int) $order_product['sale_price'] : '',
				'price'            => (isset($order_product['price']))? (int) $order_product['price'] : '',
				'total'            => (isset($order_product['total']))? (int) $order_product['total'] : '',
				'tax'              => $order_product['tax'],
				'reward'           => $order_product['reward']
			);*/
		}

		if (isset($this->request->post['order_voucher'])) {
			$this->data['order_vouchers'] = $this->request->post['order_voucher'];
		} elseif (isset($this->request->get['order_id'])) {
			$this->data['order_vouchers'] = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);
		} else {
			$this->data['order_vouchers'] = array();
		}

		$this->load->model('sale/voucher_theme');

		$this->data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();

        $total_order_amount_without_shipping = 0;

		if (isset($this->request->post['order_total'])) {
			$this->data['order_totals'] = $this->request->post['order_total'];
		} elseif (isset($this->request->get['order_id'])) {
			$this->data['order_totals'] = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

            foreach ($this->data['order_totals'] as $order_total_row) {
                if ($order_total_row['code'] == 'total') {
                    $total_order_amount_without_shipping += $order_total_row['value'];
                } elseif ($order_total_row['code'] == 'shipping') {
                    $total_order_amount_without_shipping -= abs($order_total_row['value']);
                }
            }

			foreach ($this->data['order_totals'] as $key => $totals) {
				if($totals['code'] == 'total')
					$this->data['order_totals'][$key]['value'] = (int) $this->model_sale_order->getOrderTotals($this->request->get['order_id'], true, false, true);
				elseif($totals['code'] == 'sub_total')
					$this->data['order_totals'][$key]['value'] = (int) $this->model_sale_order->getOrderTotals($this->request->get['order_id'], true, false);
				else
					$this->data['order_totals'][$key]['value'] = (int) $totals['value'];
			}
		} else {
			$this->data['order_totals'] = array();
		}

        $this->data['client_orders_count'] = '';
		// get client orders count
        if (isset($this->request->get['order_id'])) {
            $this->data['client_orders_count'] = $this->model_sale_order->getOrdersCountByCustomerId($order_info['customer_id']);
        }

        $this->data['total_order_amount_without_shipping'] = $total_order_amount_without_shipping;

		$this->template = 'sale/order_form.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	private function validateForm() {

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!empty($this->request->post["manager_id"])) {
			$this->load->model('sale/manager');
			$manager = $this->model_sale_manager->getManager($this->request->post["manager_id"]);

			if (!$manager) {
				$this->error['manager'] = $this->language->get('empty_manager');
			}
		}

		if (!empty($this->request->post['email']) &&
			((utf8_strlen($this->request->post['email']) > 96) ||
				(!preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $this->request->post['email'])))
		) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if (!empty($this->request->post['telephone']) &&
			(utf8_strlen($this->request->post['telephone']) < 3) ||
			(utf8_strlen($this->request->post['telephone']) > 32)
		) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		// Check if any products require shipping
		$shipping = false;

		if (isset($this->request->post['order_product'])) {
			$this->load->model('catalog/product');

			foreach ($this->request->post['order_product'] as $order_product) {
				$product_info = $this->model_catalog_product->getProduct($order_product['product_id']);

				if ($product_info && $product_info['shipping']) {
					$shipping = true;
				}
			}
		}

		if ($shipping) {

			if (!empty($this->request->post['shipping_city']) &&
				((utf8_strlen($this->request->post['shipping_city']) < 3) ||
					(utf8_strlen($this->request->post['shipping_city']) > 128))
			) {
				$this->error['shipping_city'] = $this->language->get('error_city');
			}

			$this->load->model('localisation/country');

			$country_info = $this->model_localisation_country->getCountry($this->request->post['shipping_country_id']);

			if ($country_info && $country_info['postcode_required'] &&
				(utf8_strlen($this->request->post['shipping_postcode']) < 2) ||
				(utf8_strlen($this->request->post['shipping_postcode']) > 10)
			) {
				$this->error['shipping_postcode'] = $this->language->get('error_postcode');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->error;
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	private function validateDelete() {
		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->setOutput(json_encode($json));
	}

    /**
     * Get Template Content
     * @param       $template_name
     * @param array $data
     * @return string
     * @internal param $name
     */
    protected function getTemplate($template_name, $data = array())
    {
        extract($data);
        ob_start();
        include(DIR_APPLICATION . 'view/template/email/' . $template_name . '.phtml');
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    public function orderSentNotification($order_id)
    {
        if (!isset($this->session->data['success'])) {
            $this->session->data['success'] = [];
        }
        if (!isset($this->session->data['warning'])) {
            $this->session->data['warning'] = [];
        }

        $domainName = 'https://kc-shoes.ru/';

        $this->load->model('sale/order');

        $order_info = $this->model_sale_order->getOrder($order_id);

        if ($order_info['order_status_id'] != 4) {
            return;
        }

        $order_info['page_links'] = [
            'about' => $domainName . $this->getPageUrl('information/about'),
            'manufacturer' => $domainName . $this->getPageUrl('product/manufacturer'),
            'new' => $domainName . $this->getPageUrl('product/category', 'collection=ss19'),
            'sale' => $domainName . $this->getPageUrl('product/category', 'collection=1'),
            'delivery' => $domainName . $this->getPageUrl('information/delivery'),
            'reviews' => $domainName . $this->getPageUrl('information/reviews'),
            'jobs' => $domainName . $this->getPageUrl('information/jobs'),
            'tracking' => $domainName . $this->getPageUrl('information/tracking'),
            'trend' => $domainName . $this->getPageUrl('information/trend'),
            'return' => $domainName . $this->getPageUrl('information/return'),
            'stock' => $domainName . $this->getPageUrl('information/stock'),
            'contacts' => $domainName . $this->getPageUrl('information/contacts'),
            'woman_shoes' => $domainName . $this->getPageUrl('product/category', 'category_id=61'),
            'man_shoes' => $domainName . $this->getPageUrl('product/category', 'category_id=60'),
            'bags' => $domainName . $this->getPageUrl('product/category', 'category_id=66'),
        ];

        if ($order_info['email']) {
            $subject = 'Ваш заказ отправлен';
            $message = $this->getTemplate('order_sent', $order_info);

            $mail = new Mail();
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');
            $mail->setTo($order_info['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_name'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();

            $this->session->data['success'][] = 'Отправлено уведомление на почтовый ящик ' . $order_info['email'];
        } else {
            $this->session->data['warning'][] = 'Не удалось отправить уведомление на email так как почтовый ящик не указан';
        }

        if ($order_info['telephone']) {
            $smsApi = new Transport();

            $phone = preg_replace(array('/\+/', '/\s/', '/\(/', '/\)/', '/\-/'), array('', '', '', '', ''), $order_info['telephone']);

            if ($phone) {
                $params = array(
                    "text" => 'Ваш заказ №' . $order_info['order_id'] . ' отправлен '
                        . date($this->language->get('date_format_short'), strtotime($order_info['date_sent']))
                        . ($order_info['post_no'] ? ', отправление №' . $order_info['post_no'] : '')
                );
                $phones = array($phone);
                $sms_response = $smsApi->send($params, $phones);

                if (isset($sms_response['code']) && $sms_response['code'] == 1) {
                    $this->session->data['success'][] = 'Отправлено sms-уведомление на номер ' . $phone;
                } else {
                    $this->session->data['warning'][] = 'Не удалось отправить sms-уведомление на номер ' . $phone;
                }
            }
        } else {
            $this->session->data['warning'][] = 'Не удалось отправить sms-уведомление так как номер телефона не указан';
        }
	}

    public function orderCompletedNotification($order_id)
    {
        if (!isset($this->session->data['success'])) {
            $this->session->data['success'] = [];
        }
        if (!isset($this->session->data['warning'])) {
            $this->session->data['warning'] = [];
        }

        $this->load->model('sale/order');

        $order_info = $this->model_sale_order->getOrder($order_id);

        if ($order_info['order_status_id'] != 3) {
            return;
        }

        $domainName = 'https://kc-shoes.ru/';

        // get sold order products
        $order_products = $this->model_sale_order->getOrderProducts($order_id, 0);

        if ($order_info['email'] && count($order_products)) {
            $this->load->model('catalog/product');
            $this->load->model('tool/image');

            $products_list = [];

            foreach ($order_products as $order_product) {
                $product = $this->model_catalog_product->getProductInfo($order_product['product_id']);

                if ($product['image']) {
                    // image settings
                    $ratio = '1.444';
                    $image = $this->model_tool_image->resize(
                            $product['image'],
                            138,
                            round(138 * $ratio)) . '?' . uniqid();
                } else {
                    $image = '';
                }

                $products_list[] = [
                    'product_id'      => $product['product_id'],
                    'image'           => $image,
                    'category'        => $product['category_name'],
                    'manufacturer'    => $product['manufacturer'],
                    'manufacturer_id' => $product['manufacturer_id'],
                    'name'            => $product['name'],
                    'href'            => $domainName . $this->getProductUrl($product),
                    'manufacturer_href' => $domainName . $this->getPageUrl('product/category', 'manufacturer=' . $product['manufacturer_id'])
                ];
            }
            $order_info['products'] = $products_list;

            $order_info['page_links'] = [
                'about' => $domainName . $this->getPageUrl('information/about'),
                'manufacturer' => $domainName . $this->getPageUrl('product/manufacturer'),
                'new' => $domainName . $this->getPageUrl('product/category', 'collection=ss19'),
                'sale' => $domainName . $this->getPageUrl('product/category', 'collection=1'),
                'delivery' => $domainName . $this->getPageUrl('information/delivery'),
                'reviews' => $domainName . $this->getPageUrl('information/reviews'),
                'jobs' => $domainName . $this->getPageUrl('information/jobs'),
                'tracking' => $domainName . $this->getPageUrl('information/tracking'),
                'trend' => $domainName . $this->getPageUrl('information/trend'),
                'return' => $domainName . $this->getPageUrl('information/return'),
                'stock' => $domainName . $this->getPageUrl('information/stock'),
                'contacts' => $domainName . $this->getPageUrl('information/contacts'),
                'woman_shoes' => $domainName . $this->getPageUrl('product/category', 'category_id=61'),
                'man_shoes' => $domainName . $this->getPageUrl('product/category', 'category_id=60'),
                'bags' => $domainName . $this->getPageUrl('product/category', 'category_id=66'),
            ];

            $subject = 'Благодарим за покупку!';
            $message = $this->getTemplate('order_completed', $order_info);

            $mail = new Mail();
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');
            $mail->setTo($order_info['email']);
            $mail->setFrom($this->config->get('config_email'));
            $mail->setSender($this->config->get('config_name'));
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
            $mail->send();

            $this->session->data['success'][] = 'Отправлено уведомление на почтовый ящик ' . $order_info['email'];
        } else {
            $this->session->data['warning'][] = 'Не удалось отправить уведомление на email так как почтовый ящик не указан';
        }
    }

	public function sendOrderInfo()
	{
		$data['status'] = 1;
		if (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');
			$this->load->model('sale/customer');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			$customer = $this->model_sale_customer->getCustomer($order_info['customer_id']);

			$order_info['total'] = (int) $this->model_sale_order->getOrderTotals($order_info['order_id'], true, false, true);
			if ($order_info
				&& ($order_info['order_status_id'] == 4 || $order_info['order_status_id'] == 3 || $order_info['order_status_id'] == 2)
				&& $order_info['date_sent'] != '0000-00-00 00:00:00'
			) {
				$text = 'Ваш заказ №' . $order_info['order_id'] . ' отправлен '
					. date($this->language->get('date_format_short'), strtotime($order_info['date_sent']))
					/*. ' к оплате ' . ((int) $order_info['total']) . ' руб.'*/
					. ($order_info['post_no']? ', отправление №' . $order_info['post_no'] . '' : '');

				if ($order_info['email']) {
					$subject = 'Ваш заказ отправлен';

					$message = '<table style="width: 550px;" align="center">
						<tr>
							<td valign="center">
								<a href="http://kc-shoes.ru/index.php?route=common/home">
									<img src="http://kc-shoes.ru/image/data/logo.gif" title="KC-Немецкая обувь" alt="KC-Немецкая обувь" width="117px" />
								</a>
							</td>
							<td valign="center" style="padding-top: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/call.jpg" alt="звонок бесплатный" width="38px" />
							</td>
							<td valign="center" style="font-family: Verdana; font-size: 14px; padding-top: 2px; color: #225887;">
								<a style="text-decoration: none; color: #225887;" href="http://kc-shoes.ru/"><font face="Verdana" color="#225887" size="1" style="font-size:14px">
									<strong>8(800)100-3752</strong></font><br />
								звонок бесплатный</a>
							</td>
							<td valign="center" style="padding-top: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/call.jpg" alt="звонок бесплатный" width="38px" />
							</td>
							<td valign="center" style="font-family: Verdana; font-size: 14px; padding-top: 2px; color: #225887;">
								<a style="text-decoration: none; color: #225887;" href="http://kc-shoes.ru/"><font face="Verdana" color="#225887" size="1" style="font-size:14px">
									<strong>8(3812)66-66-05</strong></font><br />
								звонок по Омску</a>
							</td>
							<td valign="center" style="padding-top: 6px; padding-left: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/cart.jpg" alt="Корзина" width="40px" />
							</td>
						</tr>
					</table>
					<table style="width: 550px; border-bottom: 1px solid #676767; font-family: Verdana; font-size: 12px; padding-top: 20px; padding-bottom: 10px;">
						<tr>
							<td>
								<font face="Verdana" color="#000000" size="1" style="font-size:12px">' . $text .'</font>
								<br />
								<br />
								<br />
								<font face="Verdana" color="#000000" size="1" style="font-size:12px">С уважением, КС-Немецкая обувь.</font>
							</td>
						</tr>
					</table>
					<table style="width: 550px;">
						<tr>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>Сервис</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#payment"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Способы оплаты</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#delivery"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Доставка товара</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#return"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Возврат товара</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#tracking"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Отслеживание заказа</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>Магазин</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=61"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Женская обувь</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=60"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Мужская обувь</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&new=1"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Новинки</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&sale=1"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Распродажа</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=66"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Сумки</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#stock_info"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Акции</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>KC-Shoes</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#about"><font face="Verdana" color="#676767" size="1" style="font-size:12px">О нас</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#news"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Новости</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#reviews"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Отзывы</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#contacts"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Контакты</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#stores"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Адреса магазинов</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#requisites"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Реквизиты</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<font face="Verdana" color="#676767" size="1" style="font-size:12px"><strong>Контакты</strong></font><br /><br />
								<font face="Verdana" color="#676767" size="1" style="font-size:12px">тел. (3812) 66-66-05</font><br/>
								<font face="Verdana" color="#676767" size="1" style="font-size:12px">скайп: kc-shoes.ru</font>
							</td>
						</tr>
					</table>';

					$mail = new Mail();
					$mail->protocol = $this->config->get('config_mail_protocol');
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->hostname = $this->config->get('config_smtp_host');
					$mail->username = $this->config->get('config_smtp_username');
					$mail->password = $this->config->get('config_smtp_password');
					$mail->port = $this->config->get('config_smtp_port');
					$mail->timeout = $this->config->get('config_smtp_timeout');
					$mail->setTo($order_info['email']);
					$mail->setFrom($this->config->get('config_email'));
					$mail->setSender($this->config->get('config_name'));
					$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
					$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
					$mail->send();
				}

				if ($order_info['telephone']) {
					$smsApi = new Transport();

					$phone = preg_replace(array('/\+/', '/\s/', '/\(/', '/\)/', '/\-/'), array('', '', '', '', ''), $order_info['telephone']);

					if ($phone) {
						$params = array(
							"text" => $text
						);
						$phones = array($phone);
						$sms_response = $smsApi->send($params,$phones);

						if (isset($sms_response['code']) && $sms_response['code'] != 1) {
							$data['status'] = 2;
							$data['text'] = 'Уведомление было отправленно только на электронный ящик. SMS сообщение не отправленно. '
								. $sms_response['descr'];
						}
					}
				} else {
					$data['status'] = 2;
					$data['text'] = 'Уведомление было отправленно только на электронный ящик. SMS сообщение не отправленно, т.к. номер телефона не был указан!';
				}


			} else if ($order_info['order_status_id'] != 4) {
				$status = $this->model_sale_order->getOrderStatus($order_info['order_status_id']);
				if ($status) {
					$data['status'] = 0;
					$data['text'] = 'Уведомление не отправленно! Статус заказа: ' . $status['name'];
				}
			} else if ($order_info['date_sent'] == '0000-00-00 00:00:00') {
				$data['status'] = 0;
				$data['text'] = 'Уведомление не отправленно! Неуказана дата отправки';
			} else if (!$order_info['email']) {
				$data['status'] = 0;
				$data['text'] = 'Уведомление не отправленно! Электронный адрес не указан';
			}
		}
		if ($data['status'] == 1) {
			$data['text'] = 'Уведомление успешно отправленно!';
		}
		$this->response->setOutput(json_encode($data));
	}

	public function info()
	{
		$this->load->model('sale/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$order_info = $this->model_sale_order->getOrder($order_id);

		if ($order_info) {
            if (isset($this->session->data['success'])) {
                $this->data['success'] = $this->session->data['success'];

                unset($this->session->data['success']);
            } else {
                $this->data['success'] = [];
            }
            if (isset($this->session->data['warning'])) {
                $this->data['warning'] = $this->session->data['warning'];

                unset($this->session->data['warning']);
            } else {
                $this->data['warning'] = [];
            }

			$lang = $this->load->language('sale/order');

			foreach ($lang as $key => $val) {
				$this->data[$key] = $val;
			}

			$date_added = date(
				$this->language->get('date_format_short') . ' ' . $this->language->get('time_format'),
				strtotime($order_info['date_added'])
			);
			$this->data['heading_title'] = sprintf($this->language->get('heading_order_title'), $order_id, $date_added);
			$this->document->setTitle($this->data['heading_title']);

			$this->data['token'] = $this->session->data['token'];

			$url = '';

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}

			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}

			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['filter_email'])) {
				$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));

			}

			$this->data['breadcrumbs'] = array();

			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false
			);

			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL'),
				'separator' => ' :: '
			);
			if ($order_info['ready_notify'] == 1) {
				$this->data['notification'] = $this->url->link('sale/order/sendOrderInfo', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');
			}
			$this->data['update'] = $this->url->link('sale/order/update', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');
			$this->data['invoice'] = $this->url->link('sale/order/invoice', 'token=' . $this->session->data['token'] . '&order_id=' . (int)$this->request->get['order_id'], 'SSL');
			$this->data['cancel'] = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url, 'SSL');

			$this->data['order_id'] = $this->request->get['order_id'];

			if ($order_info['invoice_no']) {
				$this->data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$this->data['invoice_no'] = '';
			}

			$this->data['store_name'] = $order_info['store_name'];
			$this->data['store_url'] = $order_info['store_url'];
			$this->data['fio'] = $order_info['fio'];
			$this->data['firstname'] = $order_info['firstname'];
			$this->data['lastname'] = $order_info['lastname'];

			if ($order_info['customer_id']) {
				$this->data['customer'] = $this->url->link('sale/customer/update', 'token=' . $this->session->data['token'] . '&customer_id=' . $order_info['customer_id'], 'SSL');
			} else {
				$this->data['customer'] = '';
			}

			$this->load->model('sale/customer_group');

			$customer_group_info = $this->model_sale_customer_group->getCustomerGroup($order_info['customer_group_id']);

			if ($customer_group_info) {
				$this->data['customer_group'] = $customer_group_info['name'];
			} else {
				$this->data['customer_group'] = '';
			}

            $this->load->model('sale/manager');

			$this->data['email'] = $order_info['email'];
			$this->data['manager_old'] = $order_info['manager'];
			$this->data['manager'] = $this->model_sale_manager->getManager($order_info['manager_id'])['name'];
			$this->data['packer'] = $this->model_sale_manager->getManager($order_info['packer_id'])['name'];
			$this->data['telephone'] = $order_info['telephone'];
			$this->data['fax'] = $order_info['fax'];
			$this->data['comment'] = nl2br($order_info['comment']);
			$this->data['shipping_method'] = $order_info['shipping_method'];
			$this->data['delivery'] = $order_info['delivery'];
			$this->data['payment_method'] = $order_info['payment_method'];
			$this->data['post_no'] = $order_info['post_no'];
			$this->data['shoes_price'] = (int)$order_info['shoes_price'];
			$this->data['shipment_summ'] = (int)$order_info['shipment_summ'];
			$this->data['shipment_rate'] = (float)$order_info['shipment_rate'];
			$this->data['shipment_agent_fee'] = (float)$order_info['shipment_agent_fee'];
			$this->data['return_rate'] = (float)$order_info['return_rate'];
			$this->data['trans_money'] = (int)$order_info['trans_money'];
			$this->data['total'] = (int) $order_info['total_to_products'];

			if ($order_info['total'] < 0) {
				$this->data['credit'] = (int) $order_info['total'];
			} else {
				$this->data['credit'] = 0;
			}

			$this->load->model('sale/customer');

			$this->data['credit_total'] = $this->model_sale_customer->getTotalTransactionsByOrderId($this->request->get['order_id']);

			$this->data['reward'] = $order_info['reward'];

			$this->data['reward_total'] = $this->model_sale_customer->getTotalCustomerRewardsByOrderId($this->request->get['order_id']);

			$this->data['affiliate_firstname'] = $order_info['affiliate_firstname'];
			$this->data['affiliate_lastname'] = $order_info['affiliate_lastname'];

			if ($order_info['affiliate_id']) {
				$this->data['affiliate'] = $this->url->link('sale/affiliate/update', 'token=' . $this->session->data['token'] . '&affiliate_id=' . $order_info['affiliate_id'], 'SSL');
			} else {
				$this->data['affiliate'] = '';
			}

			$this->data['commission'] = (int) $order_info['commission'];

			$this->load->model('sale/affiliate');

			$this->data['commission_total'] = $this->model_sale_affiliate->getTotalTransactionsByOrderId($this->request->get['order_id']);

			$this->load->model('localisation/order_status');

			$order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

			if ($order_status_info) {
				$this->data['order_status'] = $order_status_info['name'];
			} else {
				$this->data['order_status'] = '';
			}

			$this->data['ip'] = $order_info['ip'];
			$this->data['forwarded_ip'] = $order_info['forwarded_ip'];
			$this->data['user_agent'] = $order_info['user_agent'];
			$this->data['accept_language'] = $order_info['accept_language'];
			$this->data['date_take'] = ($order_info['date_take'] && $order_info['date_take'] != '0000-00-00 00:00:00')?
				date($this->language->get('date_format_short'), strtotime($order_info['date_take'])) : '';
			$this->data['date_return'] = ($order_info['date_return'] && $order_info['date_return'] != '0000-00-00 00:00:00')?
				date($this->language->get('date_format_short'), strtotime($order_info['date_return'])) : '';
			$this->data['date_barter'] = ($order_info['date_barter'] && $order_info['date_barter'] != '0000-00-00 00:00:00')?
				date($this->language->get('date_format_short'), strtotime($order_info['date_barter'])) : '';
			$this->data['date_trans'] = ($order_info['date_trans'] && $order_info['date_trans'] != '0000-00-00 00:00:00')?
				date($this->language->get('date_format_short'), strtotime($order_info['date_trans'])) : '';

			$this->data['date_sent'] = ($order_info['date_sent'] && $order_info['date_sent'] != '0000-00-00 00:00:00')?
				date($this->language->get('date_format_short'), strtotime($order_info['date_sent'])) : '';
			$this->data['date_added'] = ($order_info['date_added'] && $order_info['date_added'] != '0000-00-00 00:00:00')?
				date($this->language->get('date_format_short'), strtotime($order_info['date_added'])) : '';
			$this->data['date_modified'] = ($order_info['date_modified'] && $order_info['date_modified'] != '0000-00-00 00:00:00')?
				date($this->language->get('date_format_short'), strtotime($order_info['date_modified'])) : '';
			$this->data['payment_fio'] = $order_info['payment_fio'];
			$this->data['payment_firstname'] = $order_info['payment_firstname'];
			$this->data['payment_lastname'] = $order_info['payment_lastname'];
			$this->data['payment_company'] = $order_info['payment_company'];
			$this->data['payment_company_id'] = $order_info['payment_company_id'];
			$this->data['payment_tax_id'] = $order_info['payment_tax_id'];
			$this->data['payment_address_1'] = $order_info['payment_address_1'];
			$this->data['payment_address_2'] = $order_info['payment_address_2'];
			$this->data['payment_region'] = $order_info['payment_region'];
			$this->data['payment_city'] = $order_info['payment_city'];
			$this->data['payment_postcode'] = $order_info['payment_postcode'];
			$this->data['payment_zone'] = $order_info['payment_zone'];
			$this->data['payment_zone_code'] = $order_info['payment_zone_code'];
			$this->data['payment_country'] = $order_info['payment_country'];
			$this->data['shipping_fio'] = $order_info['shipping_fio'];
			$this->data['client_card_number'] = $order_info['client_card_number'];
			$this->data['shipping_firstname'] = $order_info['shipping_firstname'];
			$this->data['shipping_lastname'] = $order_info['shipping_lastname'];
			$this->data['shipping_company'] = $order_info['shipping_company'];
			$this->data['shipping_address_1'] = $order_info['shipping_address_1'];
			$this->data['shipping_address_2'] = $order_info['shipping_address_2'];
			$this->data['shipping_region'] = $order_info['shipping_region'];
			$this->data['prefix_region'] = $order_info['prefix_region'];
			$this->data['shipping_city'] = $order_info['shipping_city'];
			$this->data['prefix_city'] = $order_info['prefix_city'];
			$this->data['shipping_postcode'] = $order_info['shipping_postcode'];
			$this->data['shipping_street'] = $order_info['shipping_street'];
			$this->data['shipping_house'] = $order_info['shipping_house'];
			$this->data['shipping_flat'] = $order_info['shipping_flat'];
			$this->data['shipping_zone'] = $order_info['shipping_zone'];
			$this->data['shipping_zone_code'] = $order_info['shipping_zone_code'];
			$this->data['shipping_country'] = $order_info['shipping_country'];
			$this->data['notice'] = $order_info['notice'];
			$this->data['comment'] = $order_info['comment'];

            $delivery_info = [];

            if (!empty($order_info['courier_name']) && !empty($order_info['delivery_date']) && !empty($order_info['delivery_time'])) {
                $delivery_info = [
                    'courier_name'  => $order_info['courier_name'],
                    'delivery_date' => DateTime::createFromFormat('Y-m-d', $order_info['delivery_date'])->format('d.m.Y'),
                    'delivery_time' => $order_info['delivery_time'],
                ];
            }

            $this->data['delivery_info'] = $delivery_info;

			$this->data['products'] = array();

			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 0);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
							'type'  => $option['type'],
							'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&order_option_id=' . $option['order_option_id'], 'SSL')
						);
					}
				}
				$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                $transfer_store = explode(',', $product['transfer_store']);

                for ($i = 1; $i <= $product['quantity']; $i++) {
                    $this->data['products'][] = [
                        'order_product_id'          => $product['order_product_id'],
                        'product_id'                => $product['product_id'],
                        'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                        'op_name_1c'                => preg_replace(['/\//'], ['/ '], $product['op_name_1c']),
                        'name'                      => $product['name'],
                        'op_name'                   => $product['op_name'],
                        'discount'                  => $product['discount'],
                        'discount_price'            => $product['discount_price'],
                        'client_card_discount'      => $product['client_card_discount'],
                        'promo_code'                => $product['promo_code'],
                        'promo_code_discount'       => $product['promo_code_discount'],
                        'promo_code_discount_price' => $product['promo_code_discount_price'],
                        'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                        'size'                      => $product['size'],
                        'shopc'                     => $shopc_ids,
                        'transfer_store'            => array_key_exists($i - 1, $transfer_store) ? $transfer_store[$i - 1] : false,
                        'model'                     => $product['model'],
                        'option'                    => $option_data,
                        'order_original_price'      => (int)$product['original_price'],
                        'order_price'               => (int)$product['order_price'],
                        'op_order_price'            => (int)$product['op_order_price'],
                        'quantity'                  => 1,
                        'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                        'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price']: '',
                        'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                        'total'                     => (isset($product['total'])) ? (int)($product['total'] / $product['quantity']) : '',
                        'href'                      => $this->url->link('catalog/product/update',
                            'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                            'SSL'
                        ),
                    ];
                }
                /*$this->data['products'][] = [
                    'order_product_id'          => $product['order_product_id'],
                    'product_id'                => $product['product_id'],
                    'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                    'op_name_1c'                => preg_replace(['/\//'], ['/ '], $product['op_name_1c']),
                    'name'                      => $product['name'],
                    'op_name'                   => $product['op_name'],
                    'discount'                  => $product['discount'],
                    'discount_price'            => $product['discount_price'],
                    'client_card_discount'      => $product['client_card_discount'],
                    'promo_code'                => $product['promo_code'],
                    'promo_code_discount'       => $product['promo_code_discount'],
                    'promo_code_discount_price' => $product['promo_code_discount_price'],
                    'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                    'size'                      => $product['size'],
                    'shopc'                     => $shopc_ids,
                    'model'                     => $product['model'],
                    'option'                    => $option_data,
                    'order_original_price'      => (int)$product['original_price'],
                    'order_price'               => (int)$product['order_price'],
                    'op_order_price'            => (int)$product['op_order_price'],
                    'quantity'                  => $product['quantity'],
                    'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                    'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                    'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                    'total'                     => (isset($product['total'])) ? (int)$product['total'] : '',
                    'href'                      => $this->url->link('catalog/product/update',
                        'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                        'SSL'
                    ),
                ];*/
			}
			$products_r = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 1);

			foreach ($products_r as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
							'type'  => $option['type'],
							'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&order_option_id=' . $option['order_option_id'], 'SSL')
						);
					}
				}
				$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);
                for ($i = 1; $i <= $product['quantity']; $i++) {
                    $this->data['products_r'][] = [
                        'order_product_id'          => $product['order_product_id'],
                        'product_id'                => $product['product_id'],
                        'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                        'op_name_1c'                => preg_replace(['/\//'], ['/ '], $product['op_name_1c']),
                        'name'                      => $product['name'],
                        'op_name'                   => $product['op_name'],
                        'discount'                  => $product['discount'],
                        'discount_price'            => $product['discount_price'],
                        'client_card_discount'      => $product['client_card_discount'],
                        'promo_code'                => $product['promo_code'],
                        'promo_code_discount'       => $product['promo_code_discount'],
                        'promo_code_discount_price' => $product['promo_code_discount_price'],
                        'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                        'size'                      => $product['size'],
                        'shopc'                     => $shopc_ids,
                        'model'                     => $product['model'],
                        'option'                    => $option_data,
                        'order_original_price'      => (int)$product['original_price'],
                        'order_price'               => (int)$product['order_price'],
                        'op_order_price'            => (int)$product['op_order_price'],
                        'quantity'                  => 1,
                        'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                        'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price']: '',
                        'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                        'total'                     => (isset($product['total'])) ? (int)($product['total'] / $product['quantity']) : '',
                        'href'                      => $this->url->link('catalog/product/update',
                            'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                            'SSL'
                        ),
                    ];
                }
				/*$this->data['products_r'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name_1c'    	   => preg_replace(array('/\//'), array('/ '), $product['name_1c']),
					'name'    	 	   => $product['name'],
					'discount'    	   => $product['discount'],
					'discount_price'    	   => $product['discount_price'],
					'client_card_discount'         => $product['client_card_discount'],
					'promo_code'                => $product['promo_code'],
                    'promo_code_discount'       => $product['promo_code_discount'],
                    'promo_code_discount_price' => $product['promo_code_discount_price'],
                    'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
					'size'    	 	   => $product['size'],
					'shopc'    	 	   => $shopc_ids,
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'order_original_price' => (int)$product['original_price'],
					'order_price'      => (int)$product['order_price'],
					'quantity'		   => $product['quantity'],
					'original_price'   => (isset($product['original_price']))? (int) $product['original_price'] : '',
					'sale_price'       => (isset($product['sale_price']))? (int) $product['sale_price'] : '',
					'price'            => (isset($product['price']))? (int) $product['price'] : '',
					'total'            => (isset($product['total']))? (int) $product['total'] : '',
					'href' => $this->url->link('catalog/product/update',
						'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
						'SSL'
					)
				);*/
			}

			$products_o = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 2);

			foreach ($products_o as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
							'type'  => $option['type'],
							'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&order_option_id=' . $option['order_option_id'], 'SSL')
						);
					}
				}
				$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);
                for ($i = 1; $i <= $product['quantity']; $i++) {
                    $this->data['products_o'][] = [
                        'order_product_id'          => $product['order_product_id'],
                        'product_id'                => $product['product_id'],
                        'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                        'op_name_1c'                => preg_replace(['/\//'], ['/ '], $product['op_name_1c']),
                        'name'                      => $product['name'],
                        'op_name'                   => $product['op_name'],
                        'discount'                  => $product['discount'],
                        'discount_price'            => $product['discount_price'],
                        'client_card_discount'      => $product['client_card_discount'],
                        'promo_code'                => $product['promo_code'],
                        'promo_code_discount'       => $product['promo_code_discount'],
                        'promo_code_discount_price' => $product['promo_code_discount_price'],
                        'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                        'size'                      => $product['size'],
                        'shopc'                     => $shopc_ids,
                        'model'                     => $product['model'],
                        'option'                    => $option_data,
                        'order_original_price'      => (int)$product['original_price'],
                        'order_price'               => (int)$product['order_price'],
                        'op_order_price'            => (int)$product['op_order_price'],
                        'quantity'                  => 1,
                        'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                        'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price']: '',
                        'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                        'total'                     => (isset($product['total'])) ? (int)($product['total'] / $product['quantity']) : '',
                        'href'                      => $this->url->link('catalog/product/update',
                            'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                            'SSL'
                        ),
                    ];
                }
				/*$this->data['products_o'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name_1c'    	   => preg_replace(array('/\//'), array('/ '), $product['name_1c']),
					'name'    	 	   => $product['name'],
					'discount'    	   => $product['discount'],
					'discount_price'    	   => $product['discount_price'],
					'client_card_discount'         => $product['client_card_discount'],
					'promo_code'                => $product['promo_code'],
                    'promo_code_discount'       => $product['promo_code_discount'],
                    'promo_code_discount_price' => $product['promo_code_discount_price'],
                    'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
					'size'    	 	   => $product['size'],
					'shopc'    	 	   => $shopc_ids,
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'order_original_price' => (int)$product['original_price'],
					'order_price'      => (int)$product['order_price'],
					'quantity'		   => $product['quantity'],
					'original_price'   => (isset($product['original_price']))? (int) $product['original_price'] : '',
					'sale_price'       => (isset($product['sale_price']))? (int) $product['sale_price'] : '',
					'price'            => (isset($product['price']))? (int) $product['price'] : '',
					'total'            => (isset($product['total']))? (int) $product['total'] : '',
					'href' => $this->url->link('catalog/product/update',
						'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
						'SSL'
					)
				);*/
			}

			$products_v = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 3);

			foreach ($products_v as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
							'type'  => $option['type'],
							'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&order_option_id=' . $option['order_option_id'], 'SSL')
						);
					}
				}
				$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);
                for ($i = 1; $i <= $product['quantity']; $i++) {
                    $this->data['products_v'][] = [
                        'order_product_id'          => $product['order_product_id'],
                        'product_id'                => $product['product_id'],
                        'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                        'op_name_1c'                => preg_replace(['/\//'], ['/ '], $product['op_name_1c']),
                        'name'                      => $product['name'],
                        'op_name'                   => $product['op_name'],
                        'discount'                  => $product['discount'],
                        'discount_price'            => $product['discount_price'],
                        'client_card_discount'      => $product['client_card_discount'],
                        'promo_code'                => $product['promo_code'],
                        'promo_code_discount'       => $product['promo_code_discount'],
                        'promo_code_discount_price' => $product['promo_code_discount_price'],
                        'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                        'size'                      => $product['size'],
                        'shopc'                     => $shopc_ids,
                        'model'                     => $product['model'],
                        'option'                    => $option_data,
                        'order_original_price'      => (int)$product['original_price'],
                        'order_price'               => (int)$product['order_price'],
                        'op_order_price'            => (int)$product['op_order_price'],
                        'quantity'                  => 1,
                        'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                        'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price']: '',
                        'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                        'total'                     => (isset($product['total'])) ? (int)($product['total'] / $product['quantity']) : '',
                        'href'                      => $this->url->link('catalog/product/update',
                            'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                            'SSL'
                        ),
                    ];
                }
				/*$this->data['products_v'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name_1c'    	   => preg_replace(array('/\//'), array('/ '), $product['name_1c']),
					'name'    	 	   => $product['name'],
					'discount'    	   => $product['discount'],
					'discount_price'    	   => $product['discount_price'],
					'client_card_discount'         => $product['client_card_discount'],
					'promo_code'                => $product['promo_code'],
                    'promo_code_discount'       => $product['promo_code_discount'],
                    'promo_code_discount_price' => $product['promo_code_discount_price'],
                    'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
					'size'    	 	   => $product['size'],
					'shopc'    	 	   => $shopc_ids,
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'order_original_price' => (int)$product['original_price'],
					'order_price'      => (int)$product['order_price'],
					'quantity'		   => $product['quantity'],
					'original_price'   => (isset($product['original_price']))? (int) $product['original_price'] : '',
					'sale_price'       => (isset($product['sale_price']))? (int) $product['sale_price'] : '',
					'price'            => (isset($product['price']))? (int) $product['price'] : '',
					'total'            => (isset($product['total']))? (int) $product['total'] : '',
					'href' => $this->url->link('catalog/product/update',
						'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
						'SSL'
					)
				);*/
			}

			$this->data['vouchers'] = array();

			$vouchers = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

			foreach ($vouchers as $voucher) {
				$this->data['vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => (int) $voucher['amount'],
					'href'        => $this->url->link('sale/voucher/update', 'token=' . $this->session->data['token'] . '&voucher_id=' . $voucher['voucher_id'], 'SSL')
				);
			}

			$this->data['totals'] = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

            $total_order_amount_without_shipping = 0;
            foreach ($this->data['totals'] as $order_total_row) {
                if ($order_total_row['code'] == 'total') {
                    $total_order_amount_without_shipping += $order_total_row['value'];
                } elseif ($order_total_row['code'] == 'shipping') {
                    $total_order_amount_without_shipping -= abs($order_total_row['value']);
                }
            }

            $this->data['total_order_amount_without_shipping'] = $total_order_amount_without_shipping;

			foreach ($this->data['totals'] as $key => $total) {
				if($total['code'] == 'total')
					$this->data['totals'][$key]['value'] = (int) $this->model_sale_order->getOrderTotals($this->request->get['order_id'], true, false, true);
				elseif($total['code'] == 'sub_total')
					$this->data['totals'][$key]['value'] = (int) $this->model_sale_order->getOrderTotals($this->request->get['order_id'], true, false);
				else
					$this->data['totals'][$key]['value'] = (int) $total['value'];
			}

			$this->data['downloads'] = array();

			foreach ($products as $product) {
				$results = $this->model_sale_order->getOrderDownloads($this->request->get['order_id'], $product['order_product_id']);

				foreach ($results as $result) {
					$this->data['downloads'][] = array(
						'name'      => $result['name'],
						'filename'  => $result['mask'],
						'remaining' => $result['remaining']
					);
				}
			}

			$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

			$this->data['order_status_id'] = $order_info['order_status_id'];

			// Fraud
			$this->load->model('sale/fraud');

			$fraud_info = $this->model_sale_fraud->getFraud($order_info['order_id']);

			if ($fraud_info) {
				$this->data['country_match'] = $fraud_info['country_match'];

				if ($fraud_info['country_code']) {
					$this->data['country_code'] = $fraud_info['country_code'];
				} else {
					$this->data['country_code'] = '';
				}

				$this->data['high_risk_country'] = $fraud_info['high_risk_country'];
				$this->data['distance'] = $fraud_info['distance'];

				if ($fraud_info['ip_region']) {
					$this->data['ip_region'] = $fraud_info['ip_region'];
				} else {
					$this->data['ip_region'] = '';
				}

				if ($fraud_info['ip_city']) {
					$this->data['ip_city'] = $fraud_info['ip_city'];
				} else {
					$this->data['ip_city'] = '';
				}

				$this->data['ip_latitude'] = $fraud_info['ip_latitude'];
				$this->data['ip_longitude'] = $fraud_info['ip_longitude'];

				if ($fraud_info['ip_isp']) {
					$this->data['ip_isp'] = $fraud_info['ip_isp'];
				} else {
					$this->data['ip_isp'] = '';
				}

				if ($fraud_info['ip_org']) {
					$this->data['ip_org'] = $fraud_info['ip_org'];
				} else {
					$this->data['ip_org'] = '';
				}

				$this->data['ip_asnum'] = $fraud_info['ip_asnum'];

				if ($fraud_info['ip_user_type']) {
					$this->data['ip_user_type'] = $fraud_info['ip_user_type'];
				} else {
					$this->data['ip_user_type'] = '';
				}

				if ($fraud_info['ip_country_confidence']) {
					$this->data['ip_country_confidence'] = $fraud_info['ip_country_confidence'];
				} else {
					$this->data['ip_country_confidence'] = '';
				}

				if ($fraud_info['ip_region_confidence']) {
					$this->data['ip_region_confidence'] = $fraud_info['ip_region_confidence'];
				} else {
					$this->data['ip_region_confidence'] = '';
				}

				if ($fraud_info['ip_city_confidence']) {
					$this->data['ip_city_confidence'] = $fraud_info['ip_city_confidence'];
				} else {
					$this->data['ip_city_confidence'] = '';
				}

				if ($fraud_info['ip_postal_confidence']) {
					$this->data['ip_postal_confidence'] = $fraud_info['ip_postal_confidence'];
				} else {
					$this->data['ip_postal_confidence'] = '';
				}

				if ($fraud_info['ip_postal_code']) {
					$this->data['ip_postal_code'] = $fraud_info['ip_postal_code'];
				} else {
					$this->data['ip_postal_code'] = '';
				}

				$this->data['ip_accuracy_radius'] = $fraud_info['ip_accuracy_radius'];

				if ($fraud_info['ip_net_speed_cell']) {
					$this->data['ip_net_speed_cell'] = $fraud_info['ip_net_speed_cell'];
				} else {
					$this->data['ip_net_speed_cell'] = '';
				}

				$this->data['ip_metro_code'] = $fraud_info['ip_metro_code'];
				$this->data['ip_area_code'] = $fraud_info['ip_area_code'];

				if ($fraud_info['ip_time_zone']) {
					$this->data['ip_time_zone'] = $fraud_info['ip_time_zone'];
				} else {
					$this->data['ip_time_zone'] = '';
				}

				if ($fraud_info['ip_region_name']) {
					$this->data['ip_region_name'] = $fraud_info['ip_region_name'];
				} else {
					$this->data['ip_region_name'] = '';
				}

				if ($fraud_info['ip_domain']) {
					$this->data['ip_domain'] = $fraud_info['ip_domain'];
				} else {
					$this->data['ip_domain'] = '';
				}

				if ($fraud_info['ip_country_name']) {
					$this->data['ip_country_name'] = $fraud_info['ip_country_name'];
				} else {
					$this->data['ip_country_name'] = '';
				}

				if ($fraud_info['ip_continent_code']) {
					$this->data['ip_continent_code'] = $fraud_info['ip_continent_code'];
				} else {
					$this->data['ip_continent_code'] = '';
				}

				if ($fraud_info['ip_corporate_proxy']) {
					$this->data['ip_corporate_proxy'] = $fraud_info['ip_corporate_proxy'];
				} else {
					$this->data['ip_corporate_proxy'] = '';
				}

				$this->data['anonymous_proxy'] = $fraud_info['anonymous_proxy'];
				$this->data['proxy_score'] = $fraud_info['proxy_score'];

				if ($fraud_info['is_trans_proxy']) {
					$this->data['is_trans_proxy'] = $fraud_info['is_trans_proxy'];
				} else {
					$this->data['is_trans_proxy'] = '';
				}

				$this->data['free_mail'] = $fraud_info['free_mail'];
				$this->data['carder_email'] = $fraud_info['carder_email'];

				if ($fraud_info['high_risk_username']) {
					$this->data['high_risk_username'] = $fraud_info['high_risk_username'];
				} else {
					$this->data['high_risk_username'] = '';
				}

				if ($fraud_info['high_risk_password']) {
					$this->data['high_risk_password'] = $fraud_info['high_risk_password'];
				} else {
					$this->data['high_risk_password'] = '';
				}

				$this->data['bin_match'] = $fraud_info['bin_match'];

				if ($fraud_info['bin_country']) {
					$this->data['bin_country'] = $fraud_info['bin_country'];
				} else {
					$this->data['bin_country'] = '';
				}

				$this->data['bin_name_match'] = $fraud_info['bin_name_match'];

				if ($fraud_info['bin_name']) {
					$this->data['bin_name'] = $fraud_info['bin_name'];
				} else {
					$this->data['bin_name'] = '';
				}

				$this->data['bin_phone_match'] = $fraud_info['bin_phone_match'];

				if ($fraud_info['bin_phone']) {
					$this->data['bin_phone'] = $fraud_info['bin_phone'];
				} else {
					$this->data['bin_phone'] = '';
				}

				if ($fraud_info['customer_phone_in_billing_location']) {
					$this->data['customer_phone_in_billing_location'] = $fraud_info['customer_phone_in_billing_location'];
				} else {
					$this->data['customer_phone_in_billing_location'] = '';
				}

				$this->data['ship_forward'] = $fraud_info['ship_forward'];

				if ($fraud_info['city_postal_match']) {
					$this->data['city_postal_match'] = $fraud_info['city_postal_match'];
				} else {
					$this->data['city_postal_match'] = '';
				}

				if ($fraud_info['ship_city_postal_match']) {
					$this->data['ship_city_postal_match'] = $fraud_info['ship_city_postal_match'];
				} else {
					$this->data['ship_city_postal_match'] = '';
				}

				$this->data['score'] = $fraud_info['score'];
				$this->data['explanation'] = $fraud_info['explanation'];
				$this->data['risk_score'] = $fraud_info['risk_score'];
				$this->data['queries_remaining'] = $fraud_info['queries_remaining'];
				$this->data['maxmind_id'] = $fraud_info['maxmind_id'];
				$this->data['error'] = $fraud_info['error'];
			} else {
				$this->data['maxmind_id'] = '';
			}

			$this->template = 'sale/order_info.tpl';
			$this->children = array(
				'common/header',
				'common/footer'
			);

			$this->response->setOutput($this->render());
		} else {
			$this->load->language('error/not_found');

			$this->document->setTitle($this->language->get('heading_title'));

			$this->data['heading_title'] = $this->language->get('heading_title');

			$this->data['text_not_found'] = $this->language->get('text_not_found');

			$this->data['breadcrumbs'] = array();

			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false
			);

			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('error/not_found', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => ' :: '
			);

			$this->template = 'error/not_found.tpl';
			$this->children = array(
				'common/header',
				'common/footer'
			);

			$this->response->setOutput($this->render());
		}
	}

    public function prints_test()
    {
        $this->load->model('sale/order');

        if (isset($this->request->get['order_ids'])) {
            $order_ids = $this->request->get['order_ids'];
        } else {
            $order_ids = array();
        }

        if (is_array($order_ids)) {
            foreach ($order_ids as $order_id) {
                $order_info = $this->model_sale_order->getOrder($order_id);

                if ($order_info) {
                    $lang = $this->load->language('sale/order');

                    foreach ($lang as $key => $val) {
                        $this->data[$key] = $val;
                    }

                    $date_added = date(
                        $this->language->get('date_format_short') . ' ' . $this->language->get('time_format'),
                        strtotime($order_info['date_added'])
                    );
                    $this->data['orders'][$order_id]['heading_title'] = sprintf($this->language->get('heading_order_title'), $order_id, $date_added);
                    $this->document->setTitle($this->data['heading_title']);

                    $this->data['token'] = $this->session->data['token'];

                    $this->data['orders'][$order_id]['order_id'] = $order_id;

                    if ($order_info['invoice_no']) {
                        $this->data['orders'][$order_id]['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
                    } else {
                        $this->data['orders'][$order_id]['invoice_no'] = '';
                    }

                    $this->data['orders'][$order_id]['store_name'] = $order_info['store_name'];
                    $this->data['orders'][$order_id]['store_url'] = $order_info['store_url'];
                    $this->data['orders'][$order_id]['fio'] = $order_info['fio'];
                    $this->data['orders'][$order_id]['firstname'] = $order_info['firstname'];
                    $this->data['orders'][$order_id]['lastname'] = $order_info['lastname'];

                    if ($order_info['customer_id']) {
                        $this->data['orders'][$order_id]['customer'] = $this->url->link('sale/customer/update', 'token=' . $this->session->data['token'] . '&customer_id=' . $order_info['customer_id'], 'SSL');
                    } else {
                        $this->data['orders'][$order_id]['customer'] = '';
                    }

                    $this->load->model('sale/customer_group');

                    //$customer_group_info = $this->model_sale_customer_group->getCustomerGroup($order_info['customer_group_id']);

                    //if ($customer_group_info) {
                    //    $this->data['orders'][$order_id]['customer_group'] = $customer_group_info['name'];
                    //} else {
                        $this->data['orders'][$order_id]['customer_group'] = '';
                    //}

                    $this->load->model('sale/manager');

                    $this->data['orders'][$order_id]['email'] = $order_info['email'];
                    $this->data['orders'][$order_id]['telephone'] = $order_info['telephone'];
                    $this->data['orders'][$order_id]['fax'] = $order_info['fax'];
                    $this->data['orders'][$order_id]['comment'] = nl2br($order_info['comment']);
                    $this->data['orders'][$order_id]['shipping_method'] = $order_info['shipping_method'];
                    $this->data['orders'][$order_id]['delivery'] = $order_info['delivery'];
                    $this->data['orders'][$order_id]['payment_method'] = $order_info['payment_method'];
                    $this->data['orders'][$order_id]['post_no'] = $order_info['post_no'];
                    $this->data['orders'][$order_id]['shoes_price'] = (int)$order_info['shoes_price'];
                    $this->data['orders'][$order_id]['shipment_summ'] = (int)$order_info['shipment_summ'];
                    $this->data['orders'][$order_id]['shipment_rate'] = (float)$order_info['shipment_rate'];
                    $this->data['orders'][$order_id]['shipment_agent_fee'] = (float)$order_info['shipment_agent_fee'];
                    $this->data['orders'][$order_id]['return_rate'] = (float)$order_info['return_rate'];
                    $this->data['orders'][$order_id]['trans_money'] = (int)$order_info['trans_money'];
                    $this->data['orders'][$order_id]['total'] = (int)$order_info['total_to_products'];
                    $this->data['orders'][$order_id]['manager'] = $this->model_sale_manager->getManager($order_info['manager_id'])['name'];
                    $this->data['orders'][$order_id]['manager_old'] = $order_info['manager'];

                    if ($order_info['total'] < 0) {
                        $this->data['orders'][$order_id]['credit'] = (int)$order_info['total'];
                    } else {
                        $this->data['orders'][$order_id]['credit'] = 0;
                    }

                    $this->load->model('sale/customer');

                    $this->data['orders'][$order_id]['credit_total'] = 0;//$this->model_sale_customer->getTotalTransactionsByOrderId($order_id);

                    $this->data['orders'][$order_id]['reward'] = $order_info['reward'];

                    $this->data['orders'][$order_id]['reward_total'] = 0;//$this->model_sale_customer->getTotalCustomerRewardsByOrderId($order_id);

                    $this->data['orders'][$order_id]['affiliate_firstname'] = $order_info['affiliate_firstname'];
                    $this->data['orders'][$order_id]['affiliate_lastname'] = $order_info['affiliate_lastname'];

                    //if ($order_info['affiliate_id']) {
                    //    $this->data['orders'][$order_id]['affiliate'] = $this->url->link('sale/affiliate/update', 'token=' . $this->session->data['token'] . '&affiliate_id=' . $order_info['affiliate_id'], 'SSL');
                    //} else {
                        $this->data['orders'][$order_id]['affiliate'] = '';
                    //}

                    $this->data['orders'][$order_id]['commission'] = (int)$order_info['commission'];

                    $this->load->model('sale/affiliate');

                    $this->data['orders'][$order_id]['commission_total'] = 0;//$this->model_sale_affiliate->getTotalTransactionsByOrderId($order_id);

                    $this->load->model('localisation/order_status');

                    $order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

                    if ($order_status_info) {
                        $this->data['orders'][$order_id]['order_status'] = $order_status_info['name'];
                    } else {
                        $this->data['orders'][$order_id]['order_status'] = '';
                    }

                    $this->data['orders'][$order_id]['ip'] = $order_info['ip'];
                    $this->data['orders'][$order_id]['forwarded_ip'] = $order_info['forwarded_ip'];
                    $this->data['orders'][$order_id]['user_agent'] = $order_info['user_agent'];
                    $this->data['orders'][$order_id]['accept_language'] = $order_info['accept_language'];
                    $this->data['orders'][$order_id]['date_sent'] = ($order_info['date_sent'] && $order_info['date_sent'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_sent'])) : '';
                    $this->data['orders'][$order_id]['date_added'] = ($order_info['date_added'] && $order_info['date_added'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_added'])) : '';
                    $this->data['orders'][$order_id]['date_modified'] = ($order_info['date_modified'] && $order_info['date_modified'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_modified'])) : '';
                    $this->data['orders'][$order_id]['date_take'] = ($order_info['date_take'] && $order_info['date_take'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_take'])) : '';
                    $this->data['orders'][$order_id]['date_return'] = ($order_info['date_return'] && $order_info['date_return'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_return'])) : '';
                    $this->data['orders'][$order_id]['date_barter'] = ($order_info['date_barter'] && $order_info['date_barter'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_barter'])) : '';
                    $this->data['orders'][$order_id]['date_trans'] = ($order_info['date_trans'] && $order_info['date_trans'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_trans'])) : '';
                    //$this->data['orders'][$order_id]['manager'] = $order_info['manager'];
                    $this->data['orders'][$order_id]['client_card_number'] = $order_info['client_card_number'];
                    $this->data['orders'][$order_id]['payment_fio'] = $order_info['payment_fio'];
                    $this->data['orders'][$order_id]['payment_firstname'] = $order_info['payment_firstname'];
                    $this->data['orders'][$order_id]['payment_lastname'] = $order_info['payment_lastname'];
                    $this->data['orders'][$order_id]['payment_company'] = $order_info['payment_company'];
                    $this->data['orders'][$order_id]['payment_company_id'] = $order_info['payment_company_id'];
                    $this->data['orders'][$order_id]['payment_tax_id'] = $order_info['payment_tax_id'];
                    $this->data['orders'][$order_id]['payment_address_1'] = $order_info['payment_address_1'];
                    $this->data['orders'][$order_id]['payment_address_2'] = $order_info['payment_address_2'];
                    $this->data['orders'][$order_id]['payment_region'] = $order_info['payment_region'];
                    $this->data['orders'][$order_id]['payment_city'] = $order_info['payment_city'];
                    $this->data['orders'][$order_id]['payment_postcode'] = $order_info['payment_postcode'];
                    $this->data['orders'][$order_id]['payment_zone'] = $order_info['payment_zone'];
                    $this->data['orders'][$order_id]['payment_zone_code'] = $order_info['payment_zone_code'];
                    $this->data['orders'][$order_id]['payment_country'] = $order_info['payment_country'];
                    $this->data['orders'][$order_id]['shipping_fio'] = $order_info['shipping_fio'];
                    $this->data['orders'][$order_id]['shipping_firstname'] = $order_info['shipping_firstname'];
                    $this->data['orders'][$order_id]['shipping_lastname'] = $order_info['shipping_lastname'];
                    $this->data['orders'][$order_id]['shipping_company'] = $order_info['shipping_company'];
                    $this->data['orders'][$order_id]['shipping_address_1'] = $order_info['shipping_address_1'];
                    $this->data['orders'][$order_id]['shipping_address_2'] = $order_info['shipping_address_2'];
                    $this->data['orders'][$order_id]['shipping_region'] = $order_info['shipping_region'];
                    $this->data['orders'][$order_id]['prefix_region'] = $order_info['prefix_region'];
                    $this->data['orders'][$order_id]['shipping_city'] = $order_info['shipping_city'];
                    $this->data['orders'][$order_id]['prefix_city'] = $order_info['prefix_city'];
                    $this->data['orders'][$order_id]['shipping_postcode'] = $order_info['shipping_postcode'];
                    $this->data['orders'][$order_id]['shipping_street'] = $order_info['shipping_street'];
                    $this->data['orders'][$order_id]['shipping_house'] = $order_info['shipping_house'];
                    $this->data['orders'][$order_id]['shipping_flat'] = $order_info['shipping_flat'];
                    $this->data['orders'][$order_id]['shipping_zone'] = $order_info['shipping_zone'];
                    $this->data['orders'][$order_id]['shipping_zone_code'] = $order_info['shipping_zone_code'];
                    $this->data['orders'][$order_id]['shipping_country'] = $order_info['shipping_country'];
                    $this->data['orders'][$order_id]['notice'] = $order_info['notice'];
                    $this->data['orders'][$order_id]['comment'] = $order_info['comment'];

                    $this->data['orders'][$order_id]['products'] = [];

                    $order_products = $this->model_sale_order->getOrderProducts($order_id);

                    $shopc_ids_by_product = $this->model_sale_order->getShopcIdByProducts($order_products);

                    $products = [];
                    $products_o = [];
                    $products_r = [];
                    $products_v = [];

                    foreach ($order_products as $order_product) {
                        switch ($order_product['return']) {
                            case 0:
                                $products[] = $order_product;
                                break;
                            case 1:
                                $products_r[] = $order_product;
                                break;
                            case 2:
                                $products_o[] = $order_product;
                                break;
                            case 3:
                                $products_v[] = $order_product;
                                break;
                        }
                    }

                    //$products = $this->model_sale_order->getOrderProducts($order_id, 0);

                    foreach ($products as $product) {
                        $option_data = [];

                        //$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        $shopc_ids = $shopc_ids_by_product[$product['product_id']][$product['size']];

                        $transfer_store = explode(',', $product['transfer_store']);

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'transfer_store'            => array_key_exists($i - 1, $transfer_store) ? $transfer_store[$i - 1] : false,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                ),
                            ];
                        }
                    }
                    //$products_r = $this->model_sale_order->getOrderProducts($order_id, 1);

                    foreach ($products_r as $product) {
                        $option_data = [];

                        //$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        $shopc_ids = $shopc_ids_by_product[$product['product_id']][$product['size']];

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products_r'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                )
                            ];
                        }
                    }

                    //$products_o = $this->model_sale_order->getOrderProducts($order_id, 2);

                    foreach ($products_o as $product) {
                        $option_data = [];

                        //$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        $shopc_ids = $shopc_ids_by_product[$product['product_id']][$product['size']];

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products_o'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                )
                            ];
                        }
                    }

                    //$products_v = $this->model_sale_order->getOrderProducts($order_id, 3);

                    foreach ($products_v as $product) {
                        $option_data = [];

                        //$shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        $shopc_ids = $shopc_ids_by_product[$product['product_id']][$product['size']];

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products_v'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                )
                            ];
                        }
                    }

                    $this->data['orders'][$order_id]['vouchers'] = [];

                    $vouchers = $this->model_sale_order->getOrderVouchers($order_id);

                    foreach ($vouchers as $voucher) {
                        $this->data['orders'][$order_id]['vouchers'][] = [
                            'description' => $voucher['description'],
                            'amount'      => (int)$voucher['amount'],
                            'href'        => $this->url->link('sale/voucher/update', 'token=' . $this->session->data['token'] . '&voucher_id=' . $voucher['voucher_id'], 'SSL')
                        ];
                    }

                    $this->data['orders'][$order_id]['totals'] = $this->model_sale_order->getOrderTotals($order_id);

                    foreach ($this->data['orders'][$order_id]['totals'] as $key => $total) {
                        if ($total['code'] == 'total')
                            $this->data['orders'][$order_id]['totals'][$key]['value'] = (int)$this->model_sale_order->getOrderTotals($order_id, true, false, true);
                        elseif ($total['code'] == 'sub_total')
                            $this->data['orders'][$order_id]['totals'][$key]['value'] = (int)$this->model_sale_order->getOrderTotals($order_id, true, false);
                        else
                            $this->data['orders'][$order_id]['totals'][$key]['value'] = (int)$total['value'];
                    }

                    $this->data['orders'][$order_id]['downloads'] = [];

                    /*foreach ($products as $product) {
                        $results = $this->model_sale_order->getOrderDownloads($order_id, $product['order_product_id']);

                        foreach ($results as $result) {
                            $this->data['orders'][$order_id]['downloads'][] = [
                                'name'      => $result['name'],
                                'filename'  => $result['mask'],
                                'remaining' => $result['remaining']
                            ];
                        }
                    }*/

                    $this->data['orders'][$order_id]['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

                    $this->data['orders'][$order_id]['order_status_id'] = $order_info['order_status_id'];

                }
            }
        }

        $this->template = 'sale/order_print.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render());
    }

    public function prints()
    {
        $this->load->model('sale/order');

        if (isset($this->request->get['order_ids'])) {
            $order_ids = $this->request->get['order_ids'];
        } else {
            $order_ids = array();
        }

        if (is_array($order_ids)) {
            foreach ($order_ids as $order_id) {
                $order_info = $this->model_sale_order->getOrder($order_id);

                if ($order_info) {
                    $lang = $this->load->language('sale/order');

                    foreach ($lang as $key => $val) {
                        $this->data[$key] = $val;
                    }

                    $date_added = date(
                        $this->language->get('date_format_short') . ' ' . $this->language->get('time_format'),
                        strtotime($order_info['date_added'])
                    );
                    $this->data['orders'][$order_id]['heading_title'] = sprintf($this->language->get('heading_order_title'), $order_id, $date_added);
                    $this->document->setTitle($this->data['heading_title']);

                    $this->data['token'] = $this->session->data['token'];

                    $this->data['orders'][$order_id]['order_id'] = $order_id;

                    if ($order_info['invoice_no']) {
                        $this->data['orders'][$order_id]['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
                    } else {
                        $this->data['orders'][$order_id]['invoice_no'] = '';
                    }

                    $this->data['orders'][$order_id]['store_name'] = $order_info['store_name'];
                    $this->data['orders'][$order_id]['store_url'] = $order_info['store_url'];
                    $this->data['orders'][$order_id]['fio'] = $order_info['fio'];
                    $this->data['orders'][$order_id]['firstname'] = $order_info['firstname'];
                    $this->data['orders'][$order_id]['lastname'] = $order_info['lastname'];

                    if ($order_info['customer_id']) {
                        $this->data['orders'][$order_id]['customer'] = $this->url->link('sale/customer/update', 'token=' . $this->session->data['token'] . '&customer_id=' . $order_info['customer_id'], 'SSL');
                    } else {
                        $this->data['orders'][$order_id]['customer'] = '';
                    }

                    $this->load->model('sale/customer_group');

                    $customer_group_info = $this->model_sale_customer_group->getCustomerGroup($order_info['customer_group_id']);

                    if ($customer_group_info) {
                        $this->data['orders'][$order_id]['customer_group'] = $customer_group_info['name'];
                    } else {
                        $this->data['orders'][$order_id]['customer_group'] = '';
                    }

                    $this->load->model('sale/manager');

                    $this->data['orders'][$order_id]['email'] = $order_info['email'];
                    $this->data['orders'][$order_id]['telephone'] = $order_info['telephone'];
                    $this->data['orders'][$order_id]['fax'] = $order_info['fax'];
                    $this->data['orders'][$order_id]['comment'] = nl2br($order_info['comment']);
                    $this->data['orders'][$order_id]['shipping_method'] = $order_info['shipping_method'];
                    $this->data['orders'][$order_id]['delivery'] = $order_info['delivery'];
                    $this->data['orders'][$order_id]['payment_method'] = $order_info['payment_method'];
                    $this->data['orders'][$order_id]['post_no'] = $order_info['post_no'];
                    $this->data['orders'][$order_id]['shoes_price'] = (int)$order_info['shoes_price'];
                    $this->data['orders'][$order_id]['shipment_summ'] = (int)$order_info['shipment_summ'];
                    $this->data['orders'][$order_id]['shipment_rate'] = (float)$order_info['shipment_rate'];
                    $this->data['orders'][$order_id]['shipment_agent_fee'] = (float)$order_info['shipment_agent_fee'];
                    $this->data['orders'][$order_id]['return_rate'] = (float)$order_info['return_rate'];
                    $this->data['orders'][$order_id]['trans_money'] = (int)$order_info['trans_money'];
                    $this->data['orders'][$order_id]['total'] = (int)$order_info['total_to_products'];
                    $this->data['orders'][$order_id]['manager'] = $this->model_sale_manager->getManager($order_info['manager_id'])['name'];
                    $this->data['orders'][$order_id]['manager_old'] = $order_info['manager'];

                    if ($order_info['total'] < 0) {
                        $this->data['orders'][$order_id]['credit'] = (int)$order_info['total'];
                    } else {
                        $this->data['orders'][$order_id]['credit'] = 0;
                    }

                    $this->load->model('sale/customer');

                    $this->data['orders'][$order_id]['credit_total'] = $this->model_sale_customer->getTotalTransactionsByOrderId($order_id);

                    $this->data['orders'][$order_id]['reward'] = $order_info['reward'];

                    $this->data['orders'][$order_id]['reward_total'] = $this->model_sale_customer->getTotalCustomerRewardsByOrderId($order_id);

                    $this->data['orders'][$order_id]['affiliate_firstname'] = $order_info['affiliate_firstname'];
                    $this->data['orders'][$order_id]['affiliate_lastname'] = $order_info['affiliate_lastname'];

                    if ($order_info['affiliate_id']) {
                        $this->data['orders'][$order_id]['affiliate'] = $this->url->link('sale/affiliate/update', 'token=' . $this->session->data['token'] . '&affiliate_id=' . $order_info['affiliate_id'], 'SSL');
                    } else {
                        $this->data['orders'][$order_id]['affiliate'] = '';
                    }

                    $this->data['orders'][$order_id]['commission'] = (int)$order_info['commission'];

                    $this->load->model('sale/affiliate');

                    $this->data['orders'][$order_id]['commission_total'] = $this->model_sale_affiliate->getTotalTransactionsByOrderId($order_id);

                    $this->load->model('localisation/order_status');

                    $order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

                    if ($order_status_info) {
                        $this->data['orders'][$order_id]['order_status'] = $order_status_info['name'];
                    } else {
                        $this->data['orders'][$order_id]['order_status'] = '';
                    }

                    $this->data['orders'][$order_id]['ip'] = $order_info['ip'];
                    $this->data['orders'][$order_id]['forwarded_ip'] = $order_info['forwarded_ip'];
                    $this->data['orders'][$order_id]['user_agent'] = $order_info['user_agent'];
                    $this->data['orders'][$order_id]['accept_language'] = $order_info['accept_language'];
                    $this->data['orders'][$order_id]['date_sent'] = ($order_info['date_sent'] && $order_info['date_sent'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_sent'])) : '';
                    $this->data['orders'][$order_id]['date_added'] = ($order_info['date_added'] && $order_info['date_added'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_added'])) : '';
                    $this->data['orders'][$order_id]['date_modified'] = ($order_info['date_modified'] && $order_info['date_modified'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_modified'])) : '';
                    $this->data['orders'][$order_id]['date_take'] = ($order_info['date_take'] && $order_info['date_take'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_take'])) : '';
                    $this->data['orders'][$order_id]['date_return'] = ($order_info['date_return'] && $order_info['date_return'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_return'])) : '';
                    $this->data['orders'][$order_id]['date_barter'] = ($order_info['date_barter'] && $order_info['date_barter'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_barter'])) : '';
                    $this->data['orders'][$order_id]['date_trans'] = ($order_info['date_trans'] && $order_info['date_trans'] != '0000-00-00 00:00:00') ?
                        date($this->language->get('date_format_short'), strtotime($order_info['date_trans'])) : '';
                    //$this->data['orders'][$order_id]['manager'] = $order_info['manager'];
                    $this->data['orders'][$order_id]['client_card_number'] = $order_info['client_card_number'];
                    $this->data['orders'][$order_id]['payment_fio'] = $order_info['payment_fio'];
                    $this->data['orders'][$order_id]['payment_firstname'] = $order_info['payment_firstname'];
                    $this->data['orders'][$order_id]['payment_lastname'] = $order_info['payment_lastname'];
                    $this->data['orders'][$order_id]['payment_company'] = $order_info['payment_company'];
                    $this->data['orders'][$order_id]['payment_company_id'] = $order_info['payment_company_id'];
                    $this->data['orders'][$order_id]['payment_tax_id'] = $order_info['payment_tax_id'];
                    $this->data['orders'][$order_id]['payment_address_1'] = $order_info['payment_address_1'];
                    $this->data['orders'][$order_id]['payment_address_2'] = $order_info['payment_address_2'];
                    $this->data['orders'][$order_id]['payment_region'] = $order_info['payment_region'];
                    $this->data['orders'][$order_id]['payment_city'] = $order_info['payment_city'];
                    $this->data['orders'][$order_id]['payment_postcode'] = $order_info['payment_postcode'];
                    $this->data['orders'][$order_id]['payment_zone'] = $order_info['payment_zone'];
                    $this->data['orders'][$order_id]['payment_zone_code'] = $order_info['payment_zone_code'];
                    $this->data['orders'][$order_id]['payment_country'] = $order_info['payment_country'];
                    $this->data['orders'][$order_id]['shipping_fio'] = $order_info['shipping_fio'];
                    $this->data['orders'][$order_id]['shipping_firstname'] = $order_info['shipping_firstname'];
                    $this->data['orders'][$order_id]['shipping_lastname'] = $order_info['shipping_lastname'];
                    $this->data['orders'][$order_id]['shipping_company'] = $order_info['shipping_company'];
                    $this->data['orders'][$order_id]['shipping_address_1'] = $order_info['shipping_address_1'];
                    $this->data['orders'][$order_id]['shipping_address_2'] = $order_info['shipping_address_2'];
                    $this->data['orders'][$order_id]['shipping_region'] = $order_info['shipping_region'];
                    $this->data['orders'][$order_id]['prefix_region'] = $order_info['prefix_region'];
                    $this->data['orders'][$order_id]['shipping_city'] = $order_info['shipping_city'];
                    $this->data['orders'][$order_id]['prefix_city'] = $order_info['prefix_city'];
                    $this->data['orders'][$order_id]['shipping_postcode'] = $order_info['shipping_postcode'];
                    $this->data['orders'][$order_id]['shipping_street'] = $order_info['shipping_street'];
                    $this->data['orders'][$order_id]['shipping_house'] = $order_info['shipping_house'];
                    $this->data['orders'][$order_id]['shipping_flat'] = $order_info['shipping_flat'];
                    $this->data['orders'][$order_id]['shipping_zone'] = $order_info['shipping_zone'];
                    $this->data['orders'][$order_id]['shipping_zone_code'] = $order_info['shipping_zone_code'];
                    $this->data['orders'][$order_id]['shipping_country'] = $order_info['shipping_country'];
                    $this->data['orders'][$order_id]['notice'] = $order_info['notice'];
                    $this->data['orders'][$order_id]['comment'] = $order_info['comment'];

                    $this->data['orders'][$order_id]['products'] = [];

                    $products = $this->model_sale_order->getOrderProducts($order_id, 0);

                    foreach ($products as $product) {
                        $option_data = [];

                        $options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

                        foreach ($options as $option) {
                            if ($option['type'] != 'file') {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => $option['value'],
                                    'type'  => $option['type']
                                ];
                            } else {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
                                    'type'  => $option['type'],
                                    'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&order_option_id=' . $option['order_option_id'], 'SSL')
                                ];
                            }
                        }

                        $shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        $transfer_store = explode(',', $product['transfer_store']);

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'transfer_store'            => array_key_exists($i - 1, $transfer_store) ? $transfer_store[$i - 1] : false,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                ),
                            ];
                        }
                        /*$this->data['orders'][$order_id]['products'][] = [
                            'order_product_id'          => $product['order_product_id'],
                            'product_id'                => $product['product_id'],
                            'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                            'name'                      => $product['name'],
                            'discount'                  => $product['discount'],
                            'client_card_discount'      => $product['client_card_discount'],
                            'promo_code'                => $product['promo_code'],
                            'promo_code_discount'       => $product['promo_code_discount'],
                            'promo_code_discount_price' => $product['promo_code_discount_price'],
                            'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                            'size'                      => $product['size'],
                            'shopc'                     => $shopc_ids,
                            'model'                     => $product['model'],
                            'option'                    => $option_data,
                            'order_original_price'      => (int)$product['original_price'],
                            'order_price'               => (int)$product['order_price'],
                            'quantity'                  => $product['quantity'],
                            'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                            'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                            'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                            'total'                     => (isset($product['total'])) ? (int)$product['total'] : '',
                            'href'                      => $this->url->link('catalog/product/update',
                                'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                'SSL'
                            ),
                        ];*/
                    }
                    $products_r = $this->model_sale_order->getOrderProducts($order_id, 1);

                    foreach ($products_r as $product) {
                        $option_data = [];

                        $options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

                        foreach ($options as $option) {
                            if ($option['type'] != 'file') {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => $option['value'],
                                    'type'  => $option['type']
                                ];
                            } else {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
                                    'type'  => $option['type'],
                                    'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&order_option_id=' . $option['order_option_id'], 'SSL')
                                ];
                            }
                        }
                        $shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products_r'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                )
                            ];
                        }
                        /*$this->data['orders'][$order_id]['products_r'][] = [
                            'order_product_id'          => $product['order_product_id'],
                            'product_id'                => $product['product_id'],
                            'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                            'name'                      => $product['name'],
                            'discount'                  => $product['discount'],
                            'client_card_discount'      => $product['client_card_discount'],
                            'promo_code'                => $product['promo_code'],
                            'promo_code_discount'       => $product['promo_code_discount'],
                            'promo_code_discount_price' => $product['promo_code_discount_price'],
                            'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                            'size'                      => $product['size'],
                            'shopc'                     => $shopc_ids,
                            'model'                     => $product['model'],
                            'option'                    => $option_data,
                            'order_original_price'      => (int)$product['original_price'],
                            'order_price'               => (int)$product['order_price'],
                            'quantity'                  => $product['quantity'],
                            'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                            'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                            'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                            'total'                     => (isset($product['total'])) ? (int)$product['total'] : '',
                            'href'                      => $this->url->link('catalog/product/update',
                                'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                'SSL'
                            )
                        ];*/
                    }

                    $products_o = $this->model_sale_order->getOrderProducts($order_id, 2);

                    foreach ($products_o as $product) {
                        $option_data = [];

                        $options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

                        foreach ($options as $option) {
                            if ($option['type'] != 'file') {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => $option['value'],
                                    'type'  => $option['type']
                                ];
                            } else {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
                                    'type'  => $option['type'],
                                    'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $order_id . '&order_option_id=' . $option['order_option_id'], 'SSL')
                                ];
                            }
                        }
                        $shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products_o'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                )
                            ];
                        }
                        /*$this->data['orders'][$order_id]['products_o'][] = [
                            'order_product_id'          => $product['order_product_id'],
                            'product_id'                => $product['product_id'],
                            'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                            'name'                      => $product['name'],
                            'discount'                  => $product['discount'],
                            'client_card_discount'      => $product['client_card_discount'],
                            'promo_code'                => $product['promo_code'],
                            'promo_code_discount'       => $product['promo_code_discount'],
                            'promo_code_discount_price' => $product['promo_code_discount_price'],
                            'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                            'size'                      => $product['size'],
                            'shopc'                     => $shopc_ids,
                            'model'                     => $product['model'],
                            'option'                    => $option_data,
                            'order_original_price'      => (int)$product['original_price'],
                            'order_price'               => (int)$product['order_price'],
                            'quantity'                  => $product['quantity'],
                            'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                            'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                            'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                            'total'                     => (isset($product['total'])) ? (int)$product['total'] : '',
                            'href'                      => $this->url->link('catalog/product/update',
                                'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                'SSL'
                            )
                        ];*/
                    }

                    $products_v = $this->model_sale_order->getOrderProducts($order_id, 3);

                    foreach ($products_v as $product) {
                        $option_data = [];

                        $options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

                        foreach ($options as $option) {
                            if ($option['type'] != 'file') {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => $option['value'],
                                    'type'  => $option['type']
                                ];
                            } else {
                                $option_data[] = [
                                    'name'  => $option['name'],
                                    'value' => utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.')),
                                    'type'  => $option['type'],
                                    'href'  => $this->url->link('sale/order/download', 'token=' . $this->session->data['token'] . '&order_id=' . $order_id . '&order_option_id=' . $option['order_option_id'], 'SSL')
                                ];
                            }
                        }
                        $shopc_ids = $this->model_sale_order->getShopcIdByProduct($product['product_id'], $product['size']);

                        for ($i = 1; $i <= $product['quantity']; $i++) {
                            $this->data['orders'][$order_id]['products_v'][] = [
                                'order_product_id'          => $product['order_product_id'],
                                'product_id'                => $product['product_id'],
                                'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                                'name'                      => $product['name'],
                                'discount'                  => $product['discount'],
                                'client_card_discount'      => $product['client_card_discount'],
                                'promo_code'                => $product['promo_code'],
                                'promo_code_discount'       => $product['promo_code_discount'],
                                'promo_code_discount_price' => $product['promo_code_discount_price'],
                                'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                                'size'                      => $product['size'],
                                'shopc'                     => $shopc_ids,
                                'model'                     => $product['model'],
                                'option'                    => $option_data,
                                'order_original_price'      => (int)$product['original_price'],
                                'order_price'               => (int)$product['order_price'],
                                'quantity'                  => 1,
                                'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                                'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                                'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                                'total'                     => (isset($product['total'])) ? (int)$product['total'] / $product['quantity'] : '',
                                'href'                      => $this->url->link('catalog/product/update',
                                    'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                    'SSL'
                                )
                            ];
                        }
                        /*$this->data['orders'][$order_id]['products_v'][] = [
                            'order_product_id'          => $product['order_product_id'],
                            'product_id'                => $product['product_id'],
                            'name_1c'                   => preg_replace(['/\//'], ['/ '], $product['name_1c']),
                            'name'                      => $product['name'],
                            'discount'                  => $product['discount'],
                            'client_card_discount'      => $product['client_card_discount'],
                            'promo_code'                => $product['promo_code'],
                            'promo_code_discount'       => $product['promo_code_discount'],
                            'promo_code_discount_price' => $product['promo_code_discount_price'],
                            'price_final'               => (isset($product['price_final'])) ? (int)$product['price_final'] : '',
                            'size'                      => $product['size'],
                            'shopc'                     => $shopc_ids,
                            'model'                     => $product['model'],
                            'option'                    => $option_data,
                            'order_original_price'      => (int)$product['original_price'],
                            'order_price'               => (int)$product['order_price'],
                            'quantity'                  => $product['quantity'],
                            'original_price'            => (isset($product['original_price'])) ? (int)$product['original_price'] : '',
                            'sale_price'                => (isset($product['sale_price'])) ? (int)$product['sale_price'] : '',
                            'price'                     => (isset($product['price'])) ? (int)$product['price'] : '',
                            'total'                     => (isset($product['total'])) ? (int)$product['total'] : '',
                            'href'                      => $this->url->link('catalog/product/update',
                                'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'],
                                'SSL'
                            )
                        ];*/
                    }

                    $this->data['orders'][$order_id]['vouchers'] = [];

                    $vouchers = $this->model_sale_order->getOrderVouchers($order_id);

                    foreach ($vouchers as $voucher) {
                        $this->data['orders'][$order_id]['vouchers'][] = [
                            'description' => $voucher['description'],
                            'amount'      => (int)$voucher['amount'],
                            'href'        => $this->url->link('sale/voucher/update', 'token=' . $this->session->data['token'] . '&voucher_id=' . $voucher['voucher_id'], 'SSL')
                        ];
                    }

                    $this->data['orders'][$order_id]['totals'] = $this->model_sale_order->getOrderTotals($order_id);

                    foreach ($this->data['orders'][$order_id]['totals'] as $key => $total) {
                        if ($total['code'] == 'total')
                            $this->data['orders'][$order_id]['totals'][$key]['value'] = (int)$this->model_sale_order->getOrderTotals($order_id, true, false, true);
                        elseif ($total['code'] == 'sub_total')
                            $this->data['orders'][$order_id]['totals'][$key]['value'] = (int)$this->model_sale_order->getOrderTotals($order_id, true, false);
                        else
                            $this->data['orders'][$order_id]['totals'][$key]['value'] = (int)$total['value'];
                    }

                    $this->data['orders'][$order_id]['downloads'] = [];

                    foreach ($products as $product) {
                        $results = $this->model_sale_order->getOrderDownloads($order_id, $product['order_product_id']);

                        foreach ($results as $result) {
                            $this->data['orders'][$order_id]['downloads'][] = [
                                'name'      => $result['name'],
                                'filename'  => $result['mask'],
                                'remaining' => $result['remaining']
                            ];
                        }
                    }

                    $this->data['orders'][$order_id]['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

                    $this->data['orders'][$order_id]['order_status_id'] = $order_info['order_status_id'];

                }
            }
        }

        $this->template = 'sale/order_print.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render());
    }

	public function removeProductOrder()
	{
		$json = array('result' => false);
		if (!empty($this->request->post['order_product'])) {
			$order_product = array_values($this->request->post['order_product']);

			if (isset($order_product[0]['order_product_id'])) {
				$this->load->model('sale/order');
				$this->model_sale_order->deleteOrderProduct($order_product[0]['order_product_id']);

				$json = array(
					'result' => true
				);
			}
		}
		$this->response->setOutput(json_encode($json));
	}

	public function addProductOrder()
	{
		$json = array('result' => false);
		if (!empty($this->request->post['order_id'])) {
			$this->load->model('sale/order');

			$edit = $this->model_sale_order->editProductOrder($this->request->post['order_id'], array(
				'product_id' => $this->request->post['product_id'],
				'size' => str_replace(',', '.', $this->request->post['size']),
				'quantity' => $this->request->post['quantity'],
				'payment_id' => $this->request->post['payment_id'],
				'region' => $this->request->post['shipping_region'],
				'city' => $this->request->post['shipping_city'],
				'district' => $this->request->post['shipping_district']
			));
			if ($edit) {
				$this->load->model('catalog/product');
				$product = $this->model_catalog_product->getProduct($this->request->post['product_id']);
				$data = array(
					'order_status_id' => $this->request->post['order_status_id'],
					'comment' => 'Добавлен новый товар Id [' . $product['product_id'] . '], Артикул [' . $product['name'] . '] в количестве "' . $this->request->post['quantity'] . '" размер - ' . str_replace(',', '.', $this->request->post['size']),
					'notify' => false
				);
				$this->model_sale_order->addOrderHistory($this->request->post['order_id'], $data);
				$json = array('result' => true);
			}
		}
		echo(json_encode($json));
		die;
		$this->response->setOutput(json_encode($json));
	}

	public function createInvoiceNo() {
		$this->language->load('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$invoice_no = $this->model_sale_order->createInvoiceNo($this->request->get['order_id']);

			if ($invoice_no) {
				$json['invoice_no'] = $invoice_no;
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function addCredit() {
		$this->language->load('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			if ($order_info && $order_info['customer_id']) {
				$this->load->model('sale/customer');

				$credit_total = $this->model_sale_customer->getTotalTransactionsByOrderId($this->request->get['order_id']);

				if (!$credit_total) {
					$this->model_sale_customer->addTransaction($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $this->request->get['order_id'], (int) $order_info['total'], $this->request->get['order_id']);

					$json['success'] = $this->language->get('text_credit_added');
				} else {
					$json['error'] = $this->language->get('error_action');
				}
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function removeCredit() {
		$this->language->load('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			if ($order_info && $order_info['customer_id']) {
				$this->load->model('sale/customer');

				$this->model_sale_customer->deleteTransaction($this->request->get['order_id']);

				$json['success'] = $this->language->get('text_credit_removed');
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function addReward() {
		$this->language->load('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			if ($order_info && $order_info['customer_id']) {
				$this->load->model('sale/customer');

				$reward_total = $this->model_sale_customer->getTotalCustomerRewardsByOrderId($this->request->get['order_id']);

				if (!$reward_total) {
					$this->model_sale_customer->addReward($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $this->request->get['order_id'], $order_info['reward'], $this->request->get['order_id']);

					$json['success'] = $this->language->get('text_reward_added');
				} else {
					$json['error'] = $this->language->get('error_action');
				}
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function removeReward() {
		$this->language->load('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			if ($order_info && $order_info['customer_id']) {
				$this->load->model('sale/customer');

				$this->model_sale_customer->deleteReward($this->request->get['order_id']);

				$json['success'] = $this->language->get('text_reward_removed');
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function addCommission() {
		$this->language->load('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			if ($order_info && $order_info['affiliate_id']) {
				$this->load->model('sale/affiliate');

				$affiliate_total = $this->model_sale_affiliate->getTotalTransactionsByOrderId($this->request->get['order_id']);

				if (!$affiliate_total) {
					$this->model_sale_affiliate->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $this->request->get['order_id'], $order_info['commission'], $this->request->get['order_id']);

					$json['success'] = $this->language->get('text_commission_added');
				} else {
					$json['error'] = $this->language->get('error_action');
				}
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function removeCommission() {
		$this->language->load('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			if ($order_info && $order_info['affiliate_id']) {
				$this->load->model('sale/affiliate');

				$this->model_sale_affiliate->deleteTransaction($this->request->get['order_id']);

				$json['success'] = $this->language->get('text_commission_removed');
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->setOutput(json_encode($json));
	}
	public function returntoggle() {
		$product_checked = $this->request->post['product_checked'];
        //$from = $this->request->get['from'];
        $return = $this->request->get['return'];

        $grouped_product_checked = [];
        foreach ($product_checked as $product) {
            if (!array_key_exists($product, $grouped_product_checked)) {
                $grouped_product_checked[$product] = 0;
            }
            $grouped_product_checked[$product]++;
        }

		$this->load->model('sale/order');
		$json = array();
		foreach ($grouped_product_checked as $product_id => $quantity) {
			//$json[$p] = $this->model_sale_order->orderProductReturnTrigger($p);
			$json[$product_id] = $this->model_sale_order->orderProductReturnToggle($product_id, $quantity, $return);
		}
		$this->response->setOutput(json_encode($json));
	}
	public function history() {
		$this->language->load('sale/order');

		$this->data['error'] = '';
		$this->data['success'] = '';

		$this->load->model('sale/order');

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if (!$this->user->hasPermission('modify', 'sale/order')) {
				$this->data['error'] = $this->language->get('error_permission');
			}

			if (!$this->data['error']) {
				$this->model_sale_order->addOrderHistory($this->request->get['order_id'], $this->request->post);

				$this->data['success'] = $this->language->get('text_success');
			}
		}

		$this->data['text_no_results'] = $this->language->get('text_no_results');

		$this->data['column_date_added'] = $this->language->get('column_date_added');
		$this->data['column_status'] = $this->language->get('column_status');
		$this->data['column_notify'] = $this->language->get('column_notify');
		$this->data['column_comment'] = $this->language->get('column_comment');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$this->data['histories'] = array();

		$results = $this->model_sale_order->getOrderHistories($this->request->get['order_id'], ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$this->data['histories'][] = array(
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_sale_order->getTotalOrderHistories($this->request->get['order_id']);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('sale/order/history', 'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'] . '&page={page}', 'SSL');

		$this->data['pagination'] = $pagination->render();

		$this->template = 'sale/order_history.tpl';

		$this->response->setOutput($this->render());
	}

	public function download() {
		$this->load->model('sale/order');

		if (isset($this->request->get['order_option_id'])) {
			$order_option_id = $this->request->get['order_option_id'];
		} else {
			$order_option_id = 0;
		}

		$option_info = $this->model_sale_order->getOrderOption($this->request->get['order_id'], $order_option_id);

		if ($option_info && $option_info['type'] == 'file') {
			$file = DIR_DOWNLOAD . $option_info['value'];
			$mask = basename(utf8_substr($option_info['value'], 0, utf8_strrpos($option_info['value'], '.')));

			if (!headers_sent()) {
				if (file_exists($file)) {
					header('Content-Type: application/octet-stream');
					header('Content-Description: File Transfer');
					header('Content-Disposition: attachment; filename="' . ($mask ? $mask : basename($file)) . '"');
					header('Content-Transfer-Encoding: binary');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					header('Content-Length: ' . filesize($file));

					readfile($file, 'rb');
					exit;
				} else {
					exit('Error: Could not find file ' . $file . '!');
				}
			} else {
				exit('Error: Headers already sent out!');
			}
		} else {
			$this->load->language('error/not_found');

			$this->document->setTitle($this->language->get('heading_title'));

			$this->data['heading_title'] = $this->language->get('heading_title');

			$this->data['text_not_found'] = $this->language->get('text_not_found');

			$this->data['breadcrumbs'] = array();

			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false
			);

			$this->data['breadcrumbs'][] = array(
				'text'      => $this->language->get('heading_title'),
				'href'      => $this->url->link('error/not_found', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => ' :: '
			);

			$this->template = 'error/not_found.tpl';
			$this->children = array(
				'common/header',
				'common/footer'
			);

			$this->response->setOutput($this->render());
		}
	}

	public function upload() {
		$this->language->load('sale/order');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if (!empty($this->request->files['file']['name'])) {
				$filename = html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8');

				if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 128)) {
					$json['error'] = $this->language->get('error_filename');
				}

				$allowed = array();

				$filetypes = explode(',', $this->config->get('config_upload_allowed'));

				foreach ($filetypes as $filetype) {
					$allowed[] = trim($filetype);
				}

				if (!in_array(utf8_substr(strrchr($filename, '.'), 1), $allowed)) {
					$json['error'] = $this->language->get('error_filetype');
				}

				if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
					$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
				}
			} else {
				$json['error'] = $this->language->get('error_upload');
			}

			if (!isset($json['error'])) {
				if (is_uploaded_file($this->request->files['file']['tmp_name']) && file_exists($this->request->files['file']['tmp_name'])) {
					$file = basename($filename) . '.' . md5(mt_rand());

					$json['file'] = $file;

					move_uploaded_file($this->request->files['file']['tmp_name'], DIR_DOWNLOAD . $file);
				}

				$json['success'] = $this->language->get('text_upload');
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	public function invoice() {
		if (isset($this->request->get['order_id'])) {
			$this->load->language('sale/order');

			$this->data['title'] = $this->language->get('heading_title');

			if (isset($this->request->server['HTTPS']) &&
				(($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))
			) {
				$this->data['base'] = HTTPS_SERVER;
			} else {
				$this->data['base'] = HTTP_SERVER;
			}

			$this->data['direction'] = $this->language->get('direction');
			$this->data['language'] = $this->language->get('code');

			$this->data['text_invoice'] = $this->language->get('text_invoice');

			$this->data['text_order_id'] = $this->language->get('text_order_id');
			$this->data['text_invoice_no'] = $this->language->get('text_invoice_no');
			$this->data['text_invoice_date'] = $this->language->get('text_invoice_date');
			$this->data['text_date_added'] = $this->language->get('text_date_added');
			$this->data['text_telephone'] = $this->language->get('text_telephone');
			$this->data['text_fax'] = $this->language->get('text_fax');
			$this->data['text_to'] = $this->language->get('text_to');
			$this->data['text_company_id'] = $this->language->get('text_company_id');
			$this->data['text_tax_id'] = $this->language->get('text_tax_id');
			$this->data['text_ship_to'] = $this->language->get('text_ship_to');
			$this->data['text_payment_method'] = $this->language->get('text_payment_method');
			$this->data['text_shipping_method'] = $this->language->get('text_shipping_method');

			$this->data['column_name_1c'] = $this->language->get('column_name_1c');
			$this->data['column_product'] = $this->language->get('column_product');
			$this->data['column_model'] = $this->language->get('column_model');
			$this->data['column_quantity'] = $this->language->get('column_quantity');
			$this->data['column_price'] = $this->language->get('column_price');
			$this->data['column_total'] = $this->language->get('column_total');
			$this->data['column_comment'] = $this->language->get('column_comment');

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

			$this->data['order'] = array(
				'order_id'	         => $this->request->get['order_id'],
				'date_added'         => KcHelpers::rus_date($this->language->get('date_format_invoice'), strtotime($order_info['date_added'])),
				'store_name'         => $order_info['store_name'],
				'store_url'          => rtrim($order_info['store_url'], '/'),
				'fio'                => $order_info['fio'],
				'email'              => $order_info['email'],
				'telephone'          => $order_info['telephone'],
				'shipping_method'    => $order_info['shipping_method'],
				'payment_method'     => $order_info['payment_method'],
				'comment'            => nl2br($order_info['comment'])
			);

			$totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);
			$this->data['total'] = array();
			foreach ($totals as $key => $data) {
				$this->data['total'][$data['code']] = (int) $data['value'];
			}
			//$this->data['string_price'] = $this->model_sale_order->num2str((int) $order_info['total']);
			$this->data['string_price'] = $this->model_sale_order->num2str((int) $order_info['total_to_products']);
			$this->data['string_price'] = mb_strtoupper(mb_substr($this->data['string_price'], 0, 1, 'utf-8'), 'utf-8') .
				mb_substr($this->data['string_price'], 1, mb_strlen($this->data['string_price'], 'utf-8'), 'utf-8');
			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id'], 0);
            $formatted_products = [];
			$_total = 0;
			foreach ($products as $key => $product) {
			    for ($i = 1; $i <= $product['quantity']; $i++) {
                    $formatted_products[] = array_merge($product, [
                        'quantity' => 1,
                        'price' => (int) $product['price_final'],
                        'total' => (int) ($product['total'] / $product['quantity'])
                    ]);
                }
				//$products[$key]['price'] = (int) $product['price_final'];
				//$products[$key]['total'] = (int) $product['total'];
				$_total += (int) $product['total'];
			}
			$this->data['total']['total'] = $_total;
			$this->data['products'] = $formatted_products;//$products;

			$this->template = 'sale/order_invoice.tpl';
			$this->data['notification'] = '';

			if (!empty($this->request->post['invoice_email'])) {

				$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

				// set document information
				$pdf->SetCreator(PDF_CREATOR);
				// set default monospaced font
				$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
				// set auto page breaks
				$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

				// set image scale factor
				$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
				// set font
				$pdf->SetFont('dejavusans', '', 10);
				// add a page
				$pdf->AddPage();

				ob_start();
				extract($this->data);
				include(DIR_TEMPLATE . $this->template);
				$html = ob_get_clean();

				$pdf->writeHTML($html, true, false, true, false, '');
				$pdf->lastPage();

				$pdf->Output(DIR_TEMP . 'invoice_' . $this->request->get['order_id'] . '.pdf', 'F');
				$subject = 'Счет на оплату № ' . $this->request->get['order_id'] . ' от ' . $this->data['order']['date_added'];

				$message = '<table style="width: 550px;" align="center">
						<tr>
							<td valign="center">
								<a href="http://kc-shoes.ru/index.php?route=common/home">
									<img src="http://kc-shoes.ru/image/data/logo.gif" title="KC-Немецкая обувь" alt="KC-Немецкая обувь" width="117px" />
								</a>
							</td>
							<td valign="center" style="padding-top: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/call.jpg" alt="звонок бесплатный" width="38px" />
							</td>
							<td valign="center" style="font-family: Verdana; font-size: 14px; padding-top: 2px; color: #225887;">
								<a style="text-decoration: none; color: #225887;" href="http://kc-shoes.ru/"><font face="Verdana" color="#225887" size="1" style="font-size:14px">
									<strong>8(800)100-3752</strong></font><br />
								звонок бесплатный</a>
							</td>
							<td valign="center" style="padding-top: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/call.jpg" alt="звонок бесплатный" width="38px" />
							</td>
							<td valign="center" style="font-family: Verdana; font-size: 14px; padding-top: 2px; color: #225887;">
								<a style="text-decoration: none; color: #225887;" href="http://kc-shoes.ru/"><font face="Verdana" color="#225887" size="1" style="font-size:14px">
									<strong>8(3812)66-66-05</strong></font><br />
								звонок по Омску</a>
							</td>
							<td valign="center" style="padding-top: 6px; padding-left: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/cart.jpg" alt="Корзина" width="40px" />
							</td>
						</tr>
					</table>
					<table style="width: 550px; border-bottom: 1px solid #676767; font-family: Verdana; font-size: 12px; padding-top: 20px; padding-bottom: 10px;">
						<tr>
							<td>
								<font face="Verdana" color="#000000" size="1" style="font-size:12px">Счет на оплату № ' . $this->request->get['order_id'] . ' от ' . $this->data['order']['date_added'] .'</font>
								<br />
								<br />
								<br />
								<font face="Verdana" color="#000000" size="1" style="font-size:12px">С уважением, КС-Немецкая обувь.</font>
							</td>
						</tr>
					</table>
					<table style="width: 550px;">
						<tr>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>Сервис</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#payment"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Способы оплаты</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#delivery"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Доставка товара</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#return"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Возврат товара</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#tracking"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Отслеживание заказа</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>Магазин</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=61"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Женская обувь</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=60"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Мужская обувь</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&new=1"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Новинки</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&sale=1"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Распродажа</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=66"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Сумки</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#stock_info"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Акции</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>KC-Shoes</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#about"><font face="Verdana" color="#676767" size="1" style="font-size:12px">О нас</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#news"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Новости</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#reviews"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Отзывы</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#contacts"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Контакты</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#stores"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Адреса магазинов</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#requisites"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Реквизиты</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<font face="Verdana" color="#676767" size="1" style="font-size:12px"><strong>Контакты</strong></font><br /><br />
								<font face="Verdana" color="#676767" size="1" style="font-size:12px">тел. (3812) 66-66-05</font><br/>
								<font face="Verdana" color="#676767" size="1" style="font-size:12px">скайп: kc-shoes.ru</font>
							</td>
						</tr>
					</table>';

				$mail = new Mail();
				$mail->protocol = $this->config->get('config_mail_protocol');
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->hostname = $this->config->get('config_smtp_host');
				$mail->username = $this->config->get('config_smtp_username');
				$mail->password = $this->config->get('config_smtp_password');
				$mail->port = $this->config->get('config_smtp_port');
				$mail->timeout = $this->config->get('config_smtp_timeout');
				$mail->setTo($this->request->post['invoice_email']);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($this->config->get('config_name'));
				$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
				$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
				$mail->addAttachment(DIR_TEMP . 'invoice_' . $this->request->get['order_id'] . '.pdf');
				$mail->send();
				$this->data['notification'] = 'Сообщение отправленно!';
			}
			$this->children = array(
				'sale/order/invoice_form'
			);

			$this->response->setOutput($this->render());
		}
	}

	public function invoice_form() {
		if (isset($this->request->get['order_id'])) {
			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
			$this->data['order'] = array('email' => $order_info['email']);
			$this->data['order_url'] = $this->url->link(
				'sale/order/info',
				'token=' . $this->session->data['token'] . '&order_id=' . $this->request->get['order_id'], 'SSL');

			$this->template = 'sale/order_invoice_form.tpl';
			$this->render();
		}
	}

	public function delivery()
	{
		if (isset($this->request->get['city'], $this->request->get['region'], $this->request->get['district'],
		$this->request->get['payment_id'])
		) {
			$this->load->model('sale/order');

			$price = $this->model_sale_order->delivery(
				$this->request->get['city'],
				$this->request->get['region'],
				$this->request->get['payment_id'],
				$this->request->get['district']
			);

			$this->response->setOutput(json_encode(array('price' => $price)));
		}
	}

	public function paymentBox()
	{
		if (isset($this->request->get['shipping_id'])
		) {
			$this->load->model('page/payment');

			$this->data['payment'] = $this->model_page_payment->getPaymentByShippingId($this->request->get['shipping_id']);
			$this->data['payment_name'] = $this->request->get['payment_name'];

			$this->template = 'sale/payment_box.tpl';

			$this->response->setOutput($this->render());
		}
	}

	public function smsSend(){
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$res = array();
			$res = ($_POST);
			$errors = array();
			$sent = array();
			$orders_id=array();
			$orders_id = $res['selected'];

			$this->load->model('sale/order');

			$dataOrder = $this->model_sale_order->getOrders(array('filter_orders_id'=>$orders_id));

			foreach($dataOrder as $v){
				if (!empty($v['telephone'])) {
					$smsApi = new Transport();

					$phone = preg_replace(array('/\+/', '/\s/', '/\(/', '/\)/', '/\-/'), array('', '', '', '', ''), $v['telephone']);

					if ((int)$phone) {
						if(in_array($phone,$sent)){
							continue;
						}

						$params = array(
							"text" => $res['sms_text']
						);
						$phones = array($phone);
						$sms_response = $smsApi->send($params,$phones);

						if (isset($sms_response['code']) && $sms_response['code'] != 1) {
							$errors[] ='Для заказа №' .$v['order_id'].' сообщение НЕ ДОСТАВЛЕННО! ';
						} else {
							$sent[] = $phone;
						}
					} else {
						$errors[] ='В заказе №' .$v['order_id'].' некорректный номер! ';
					}
				} else {
					$errors[] ='В заказе №' .$v['order_id'].' некорректный номер! ';
				}

			}
				$return = array(
					'errors' => (!empty($errors))? implode('<br/>',$errors):''
				);


			echo json_encode($return); exit();
		}


	}

	public function emailSend(){
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$res = array();
			$res = ($_POST);
			$sent = array();
			$errors = array();
			$orders_id=array();
			$orders_id = $res['selected'];

			$this->load->model('sale/order');
			$dataOrder = $this->model_sale_order->getOrders(array('filter_orders_id'=>$orders_id));

			foreach($dataOrder as $v){
				if ($v['email'] && filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {

					if(in_array($v['email'],$sent)){
						continue;
					}
					$sent[] = $v['email'];

					$subject = $res['email_title'];
					$message = '<table style="width: 550px;" align="center">
						<tr>
							<td valign="center">
								<a href="http://kc-shoes.ru/index.php?route=common/home">
									<img src="http://kc-shoes.ru/image/data/logo.gif" title="KC-Немецкая обувь" alt="KC-Немецкая обувь" width="117px" />
								</a>
							</td>
							<td valign="center" style="padding-top: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/call.jpg" alt="звонок бесплатный" width="38px" />
							</td>
							<td valign="center" style="font-family: Verdana; font-size: 14px; padding-top: 2px; color: #225887;">
								<a style="text-decoration: none; color: #225887;" href="http://kc-shoes.ru/"><font face="Verdana" color="#225887" size="1" style="font-size:14px">
									<strong>8(800)100-3752</strong></font><br />
								звонок бесплатный</a>
							</td>
							<td valign="center" style="padding-top: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/call.jpg" alt="звонок бесплатный" width="38px" />
							</td>
							<td valign="center" style="font-family: Verdana; font-size: 14px; padding-top: 2px; color: #225887;">
								<a style="text-decoration: none; color: #225887;" href="http://kc-shoes.ru/"><font face="Verdana" color="#225887" size="1" style="font-size:14px">
									<strong>8(3812)66-66-05</strong></font><br />
								звонок по Омску</a>
							</td>
							<td valign="center" style="padding-top: 6px; padding-left: 8px;">
								<img src="http://kc-shoes.ru/image/email_template/cart.jpg" alt="Корзина" width="40px" />
							</td>
						</tr>
					</table>
					<table style="width: 550px; border-bottom: 1px solid #676767; font-family: Verdana; font-size: 12px; padding-top: 20px; padding-bottom: 10px;">
						<tr>
							<td>
								<font face="Verdana" color="#000000" size="1" style="font-size:12px">' . $res['email_text'] .'</font>
								<br />
								<br />
								<br />
								<font face="Verdana" color="#000000" size="1" style="font-size:12px">С уважением, КС-Немецкая обувь.</font>
							</td>
						</tr>
					</table>
					<table style="width: 550px;">
						<tr>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>Сервис</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#payment"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Способы оплаты</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#delivery"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Доставка товара</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#return"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Возврат товара</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#tracking"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Отслеживание заказа</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>Магазин</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=61"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Женская обувь</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=60"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Мужская обувь</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&new=1"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Новинки</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&sale=1"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Распродажа</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=product/category&path=66"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Сумки</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#stock_info"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Акции</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<strong>KC-Shoes</strong><br /><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#about"><font face="Verdana" color="#676767" size="1" style="font-size:12px">О нас</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#news"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Новости</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#reviews"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Отзывы</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#contacts"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Контакты</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#stores"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Адреса магазинов</font></a><br />
								<a style="text-decoration: none; color: #676767;" href="http://kc-shoes.ru/?route=information/about#requisites"><font face="Verdana" color="#676767" size="1" style="font-size:12px">Реквизиты</font></a>
							</td>
							<td valign="top" style="font-family: Verdana; font-size: 12px; color: #676767; padding-top: 5px;">
								<font face="Verdana" color="#676767" size="1" style="font-size:12px"><strong>Контакты</strong></font><br /><br />
								<font face="Verdana" color="#676767" size="1" style="font-size:12px">тел. (3812) 66-66-05</font><br/>
								<font face="Verdana" color="#676767" size="1" style="font-size:12px">скайп: kc-shoes.ru</font>
							</td>
						</tr>
					</table>';


					$mail = new Mail();
					$mail->protocol = $this->config->get('config_mail_protocol');
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->hostname = $this->config->get('config_smtp_host');
					$mail->username = $this->config->get('config_smtp_username');
					$mail->password = $this->config->get('config_smtp_password');
					$mail->port = $this->config->get('config_smtp_port');
					$mail->timeout = $this->config->get('config_smtp_timeout');
					$mail->setTo($v['email']);//емайлы из базы
					$mail->setFrom($this->config->get('config_email'));
					$mail->setSender($this->config->get('config_name'));
					$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
					$mail->setHtml(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
					/* $mail->addAttachment(DIR_TEMP . 'invoice_' . $this->request->get['order_id'] . '.pdf');*/
					$mail->send();
					$this->data['notification'] = 'Сообщение отправленно!';

				}
				else {
					$errors[] ='В заказе №' .$v['order_id'].' некорректный E-mail! ';
				}


			}
			$return = array(
				'errors' => (!empty($errors))? implode('<br/>',$errors):''
			);


			echo json_encode($return); exit();

		}

	}

    public function localization()
    {
        $this->data['orders'] = [];
        $this->data['token'] = $_GET['token'];

        if ($this->request->get['orders']) {
            $this->load->model('sale/order');
            $orders_ids = explode(',', $this->request->get['orders']);
            $this->data['orders_ids'] = $this->request->get['orders'];
            foreach ($orders_ids as $order_id) {
                $order = $this->model_sale_order->getOrder((int)trim($order_id));

                if (empty($order['shipping_city']) || empty($order['shipping_street']) || empty($order['shipping_house'])) {
                    continue;
                }

                $address_line = '';
                if (!empty($order['shipping_postcode'])) {
                    $address_line .= $order['shipping_postcode'] . ', ';
                }
                $address_line .= $order['shipping_city'] . ', ' . $order['shipping_street'] . ', ' . $order['shipping_house'];
                $formatted_address_line = preg_replace("/[^0-9а-яА-Я]+/u", "+", $address_line);

                $address_info = json_decode(file_get_contents('https://geocode-maps.yandex.ru/1.x/?geocode=' . $formatted_address_line . '&format=json&results=1'), true);

                if (isset($address_info['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'])
                    && $address_info['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0
                ) {
                    $position = explode(' ', $address_info['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']);

                    $name = $order['fio'] . ' (' . $order['telephone'];
                    if (!empty($order['email'])) {
                        $name .= ', ' . $order['email'];
                    }
                    $name .= ')';

                    $this->data['orders'][] = [
                        'coordX'   => $position[0],
                        'coordY'   => $position[1],
                        'address'  => $address_line,
                        'name'     => $name,
                        'order_id' => $order['order_id'],
                    ];
                }
            }
        }

        $this->template = 'sale/order_localization.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => 'Заказы',
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => 'Локализация',
            'href'      => $this->url->link('sale/order/localization', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->response->setOutput($this->render());
	}

    public function localizationMoskow()
    {
        set_time_limit(0);

        $this->data['orders'] = [];

        $order_data = file_get_contents(DIR_TEMP . 'courier_shipping_localization/moscow_delivery_data');

        if ($order_data) {
            $order_data_decoded = json_decode($order_data, true);

            if (count($order_data_decoded) > 0) {
                $this->data['orders'] = $order_data_decoded;
            }
        }

        if (empty($this->data['orders'])) {
            $this->load->model('sale/order');

            $start = 0;
            $limit = 1;

            do {
                $temp_orders = [];

                /*$orders = $this->model_sale_order->getOrders([
                    'filter_shipping_city' => 'Москва',
                    'filter_shipping_id'   => 2,
                    'filter_date_start'    => DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'))
                        ->sub(new DateInterval('P6M'))->format('Y-m-d H:i:s'),
                    'start'                => $start,
                    'limit'                => $limit,
                ]);*/
                $orders = $this->db->query('
                SELECT *
                FROM  `order`
                WHERE shipping_id = 2
                    AND shipping_city = "Москва"
                    AND date_added >= "2016-05-14 00:00:00"
                    AND date_added <= "2016-11-14 23:59:59"
                    AND order_status_id <> 6
                    AND shipping_city != ""
                    AND shipping_street != ""
                    AND shipping_house != ""
                LIMIT ' . $limit . ' OFFSET ' . $start . '
                ');

                foreach ($orders->rows as $order) {
                    if (empty($order['shipping_city']) || empty($order['shipping_street']) || empty($order['shipping_house'])) {
                        continue;
                    }

                    $address_line = '';
                    if (!empty($order['shipping_postcode'])) {
                        $address_line .= $order['shipping_postcode'] . ', ';
                    }
                    $address_line .= $order['shipping_city'] . ', ' . $order['shipping_street'] . ', ' . $order['shipping_house'];
                    $formatted_address_line = preg_replace("/[^0-9а-яА-Я]+/u", "+", $address_line);

                    $address_info = json_decode(file_get_contents('https://geocode-maps.yandex.ru/1.x/?geocode=' . $formatted_address_line . '&format=json&results=1'), true);

                    if (isset($address_info['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'])
                        && $address_info['response']['GeoObjectCollection']['metaDataProperty']['GeocoderResponseMetaData']['found'] > 0
                    ) {
                        $position = explode(' ', $address_info['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos']);

                        $name = $order['fio'] . ' (' . $order['telephone'];
                        if (!empty($order['email'])) {
                            $name .= ', ' . $order['email'];
                        }
                        $name .= ')';

                        $temp_orders[] = [
                            'coordX'  => $position[0],
                            'coordY'  => $position[1],
                            'address' => $address_line,
                            'name'    => $name,
                        ];
                    }
                }

                $stored_data = file_get_contents(DIR_TEMP . 'courier_shipping_localization/moscow_delivery_data');

                if ($stored_data) {
                    $stored_data_decoded = json_decode($stored_data, true);
                } else {
                    $stored_data_decoded = [];
                }

                $data_to_store = array_merge($stored_data_decoded, $temp_orders);

                file_put_contents(DIR_TEMP . 'courier_shipping_localization/moscow_delivery_data', json_encode($data_to_store));

                $start += $limit;
            } while ($orders->num_rows == $limit);

            $order_data = file_get_contents(DIR_TEMP . 'courier_shipping_localization/moscow_delivery_data');

            if ($order_data) {
                $order_data_decoded = json_decode($order_data, true);

                if (count($order_data_decoded) > 0) {
                    $this->data['orders'] = $order_data_decoded;
                }
            }
        }

        $this->template = 'sale/order_localization_moscow.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => 'Заказы',
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => 'Локализация',
            'href'      => $this->url->link('sale/order/localization', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->response->setOutput($this->render());
	}

    public function update_shipment_rate()
    {
        $order_id = $this->request->post['order_id'];

        $result = [
            'status' => 'failed',
        ];

        if ($order_id) {
            $sum = $this->get_shipment_rate($order_id);

            if ($sum !== false) {
                $result = [
                    'status'   => 'success',
                    'value'    => $sum['shipment_rate'],
                    'agentFee' => $sum['shipment_agent_fee'],
                ];

                if (array_key_exists('return_rate', $sum) && $sum['return_rate']) {
                    $result['returnRate'] = $sum['return_rate'];
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode($result);exit();
	}

    protected function get_shipment_rate($order_id)
    {
        if ($order_id) {
            $this->load->model('sale/order');

            $order_data = $this->model_sale_order->getOrder($order_id);

            if (!empty($order_data) && strlen($order_data['post_no']) == 10) {// cdek
                $secure = md5(date('Y-m-d') . '&' . 'a0f73843b7bc8555ddac34a67c964104');

                $strXml = '<?xml version="1.0" encoding="utf-8"?><InfoRequest></InfoRequest>';
                $xml = simplexml_load_string($strXml);
                $xml->addAttribute('Date', date('Y-m-d')); //, strtotime($this->request->post['DateOrder'])));
                $xml->addAttribute('Account', '5d38d9082427a655d0498ec2f79c7519');
                $xml->addAttribute('Secure', $secure);

                $order = $xml->addChild('Order');
                $order->addAttribute('DispatchNumber', $order_data['post_no']);
                //$order->addAttribute('Number', $order_id);
                //$order->addAttribute('Date', $order_data['date_send_cdek']);

                $xml_string = $xml->saveXML();

                $url = 'https://integration.cdek.ru/info_report.php';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, ['xml_request' => $xml_string]);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $data = curl_exec($curl);
                curl_close($curl);

                $response = new DOMDocument();
                $response->loadXML($data);
                $response->formatOutput = 1;

                if ($response->getElementsByTagName('Order')->length) {
                    $sum = $response->getElementsByTagName('Order')->item(0)->getAttribute('DeliverySum');
                    $agent_fee = 0;

                    $added_services = $response->getElementsByTagName('AddedService');

                    foreach ($added_services as $service) {
                        if ($service->getAttribute('ServiceCode') == 42) {
                            // agent fee
                            $agent_fee = $service->getAttribute('Sum');
                        } else {
                            $sum += $service->getAttribute('Sum');
                        }
                    }

                    $this->db->query('UPDATE `order` SET
                        `shipment_rate` = ' . $sum . ',
                        `shipment_agent_fee` = ' . $agent_fee . '
                        WHERE order_id = ' . (int)$order_data['order_id']);

                    $history_data = [
                        'order_status_id' => $order_data['order_status_id'],
                        'comment'         => 'Обновлён расход доставки',
                        'notify'          => false,
                    ];
                    $this->model_sale_order->addOrderHistory($order_id, $history_data);

                    return [
                        'shipment_rate'      => $sum,
                        'shipment_agent_fee' => $agent_fee,
                    ];
                }
            } elseif (!empty($order_data) && strlen($order_data['post_no']) == 7) {// KC courier
                $url = trim(KC_COURIER_URL, '/') . '/api/order/get';

                $requestData = [
                    'api_key'             => KC_COURIER_API_KEY,
                    'api_secret'          => KC_COURIER_API_SECRET,
                    'client_order_number' => $order_id,
                ];

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($requestData));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $data = curl_exec($curl);
                curl_close($curl);

                $result = [];

                if ($data) {
                    $data = json_decode($data, true);

                    if ($data['status'] == 1) {
                        foreach ($data['order']['services'] as $service) {
                            switch ($service['code']) {
                                case '1234': //courier
                                case '2541': // pickup
                                    $result['shipment_rate'] = array_key_exists('shipment_rate', $result)
                                        ? $result['shipment_rate'] + $service['amount'] : $service['amount'];
                                    break;
                                case '9584': // return
                                    $result['return_rate'] = $service['amount'];
                                    break;
                                case '1717': // agent revenue
                                    $result['shipment_agent_fee'] = $service['amount'];
                                    break;
                            }
                        }
                    }
                }

                if (!empty($result)) {
                    $this->db->query('UPDATE `order` SET
                        `shipment_rate` = "' . (@$result['shipment_rate'] ?: 0) . '",
                        `shipment_agent_fee` = "' . (@$result['shipment_agent_fee'] ?: 0) . '",
                        `return_rate` = "' . (@$result['return_rate'] ?: 0) . '"
                        WHERE order_id = ' . (int)$order_data['order_id']);

                    $history_data = [
                        'order_status_id' => $order_data['order_status_id'],
                        'comment'         => 'Обновлён расход доставки',
                        'notify'          => false,
                    ];
                    $this->model_sale_order->addOrderHistory($order_id, $history_data);
                }

                return $result;
            }
        }

        return false;
	}

    public function getPickupPoints($order_info = false)
    {
        if (!$order_info) {
            if (isset($_POST['shipping_city']) && isset($_POST['shipping_region']) && isset($_POST['shipping_id'])) {
                $order_info = [
                    'shipping_city'   => $_POST['shipping_city'],
                    'shipping_region' => $_POST['shipping_region'],
                    'shipping_id'     => $_POST['shipping_id'],
                ];
            }
        }

        $options = [];

        if ($order_info) {
            $city = preg_replace("/[^\w\-]+/u", " ", $order_info['shipping_city']);
            $region = preg_replace("/[^\w\-]+/u", " ", $order_info['shipping_region']);


            /* Фикс для москвы */
            if (strpos(mb_strtolower($region), 'москва') !== false) {
                $region = 'Моск';
            }
            /* Фикс для автономных округов */
            if (strlen($region) > 6) {
                $region = mb_substr($region, 0, 6);
            }
            /* Фикс для округов из нескольких слов или со спец. символами */
            if (mb_strpos($region, " ")) {
                $region = mb_substr($region, 0, mb_strpos($region, " ") + 1);
            }

            if ($order_info['shipping_id'] == 3) {
                // cdek

                $query = $this->db->query('SELECT * FROM cdk_city WHERE `Name` LIKE "' . $city . '"');


                if (count($query->rows) > 0) {
                	$cityResult = $query;

                    $query = $this->db->query('SELECT * FROM cdk_city WHERE `Name` LIKE "' . $city . '" AND `Region` LIKE "%' . $region . '%"');

                    if (empty($query->row)) {
                    	$query = $cityResult;
                    }
                }

                if (!empty($query->row)) {
                    $ID = $query->row["id"];

                    $query = $this->db->query('SELECT * FROM cdk_city_info WHERE id_city = ' . $ID);
                    if (empty($query->rows)) {
                        $url = 'https://integration.cdek.ru/pvzlist.php?cityid=' . $ID;
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        $data = curl_exec($curl);
                        curl_close($curl);
                        $city = new SimpleXMLElement($data);

                        foreach ($city as $c) {
                            $this->db->query('INSERT INTO cdk_city_info (id_city, City, Name, Code, Address, Phone, WorkTime, coordX, coordY, dateInsert)
                                VALUES (' . $ID . ', "' . $this->db->escape($c["City"]) . '", "' . $this->db->escape($c["Name"]) . '", "' . $this->db->escape($c["Code"]) . '", "' . $this->db->escape($c["Address"]) . '", "' . $this->db->escape($c["Phone"]) . '", "' . $this->db->escape($c["WorkTime"]) . '", ' . $this->db->escape($c["coordX"]) . ', ' . $this->db->escape($c["coordY"]) . ', "' . date("Y-m-d") . '")');
                        }
                    } else {
                        $city = $query->rows;
                    }

                    if (!empty($city)) {
                        if (!isset($city['ErrorCode'])) {

                            foreach ($city as $c) {
                                //$options[$c['Code']] = $c['Name'] . ' (' . $c['Address'] . ')';
                                $options[$c['Code']] = $c['Address'];
                            }
                        }
                    }
                }
            } elseif ($order_info['shipping_id'] == 5) {
                // kc courier
                $query = $this->db->query('SELECT * FROM kc_courier_city WHERE `name` LIKE "' . $city . '"');
                if (count($query->rows) > 1) {
                    $query = $this->db->query('SELECT * FROM kc_courier_city WHERE `name` LIKE "' . $city . '" AND `region` LIKE "%' . $_region . '%"');
                }

                if (!empty($query->row)) {
                    $ID = $query->row["id"];

                    $query = $this->db->query('SELECT * FROM kc_courier_pickup_point WHERE city_id = ' . $ID);
                    if (empty($query->rows)) {
                        $url = KC_COURIER_URL . '/api/city/' . $ID . '/pickup-points';
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($curl, CURLOPT_HEADER, false);
                        $data = curl_exec($curl);
                        curl_close($curl);

                        $response = json_decode($data, true);

                        if ($response['status'] == 1) {
                            $city = $response['pickup_points'];
                            foreach ($city as $pickupPoint) {
                                $this->db->query('INSERT INTO kc_courier_pickup_point (
                                    city_id, `name`, code, address, phone, work_time, lat, lng, created_at, updated_at
                                ) VALUES (
                                    ' . $ID . ',
                                    "' . $this->db->escape($pickupPoint['name']) . '",
                                    "' . $this->db->escape($pickupPoint['code']) . '",
                                    "' . $this->db->escape($pickupPoint['address']) . '",
                                    "' . $this->db->escape($pickupPoint['phone']) . '",
                                    "' . $this->db->escape($pickupPoint['work_time']) . '",
                                    "' . $this->db->escape($pickupPoint['lat']) . '",
                                    "' . $this->db->escape($pickupPoint['lng']) . '",
                                    "' . date('y-m-d H:i:s') . '",
                                    "' . date('y-m-d H:i:s') . '"
                                )');
                            }
                        }
                    } else {
                        $city = $query->rows;
                    }

                    if (!empty($city)) {
                        foreach ($city as $c) {
                            $options[$c['code']] = $c['name'] . ' (' . $c['address'] . ')';
                            //$options[$c['code']] = $c['address'];
                        }
                    }
                }
            }
        }

        if (isset($_POST['json']) && $_POST['json'] == 1) {
            echo json_encode($options);
            exit();
        }

        return $options;
	}
}
