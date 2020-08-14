<?php
class ControllerStartupSeoUrl extends Controller {
//    private $url_list = array (
//        'common/home'            => '',
//        'checkout/cart'          => 'basket',
//        'account/login'          => 'login',
//        'account/register'       => 'registry'
//    );
	public function index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}


        // Decode URL
        if (isset($this->request->get['_route_'])) {
            $parts = explode('/', $this->request->get['_route_']);
			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

				if ($query->num_rows) {
                    $url = explode('=', $query->row['query']);

                    if ($url[0] == 'product_id') {
                        $this->request->get['product_id'] = $url[1];
                    }

                    if ($url[0] == 'category_id') {
                        if (!isset($this->request->get['path'])) {
                            $this->request->get['path'] = $url[1];
                        } else {
                            $this->request->get['path'] .= '_' . $url[1];
                        }
                    }

                    if ($url[0] == 'manufacturer_id') {
                        $this->request->get['manufacturer_id'] = $url[1];
                    }

                    if ($url[0] == 'information_id') {
                        $this->request->get['information_id'] = $url[1];
                    }

                    if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
                        $this->request->get['route'] = $query->row['query'];
                    }
                } else {
                    $this->request->get['route'] = 'error/not_found';

                    break;
                }
            }
            /* Set route */
            if (in_array($this->request->get['_route_'], $this->getCustomRoutes())) {
                echo 'fffffff';
            }

            if (!isset($this->request->get['route'])) {
                if (isset($this->request->get['product_id'])) {
                    $this->request->get['route'] = 'product/product';
                } elseif (isset($this->request->get['path'])) {
                    $this->request->get['route'] = 'product/category';
                } elseif (isset($this->request->get['manufacturer_id'])) {
                    $this->request->get['route'] = 'product/manufacturer/info';
                } elseif (isset($this->request->get['information_id'])) {
                    $this->request->get['route'] = 'information/information';
                }
            }
        }

    }

	public function rewrite($link) {

        $url_info = parse_url(str_replace('&amp;', '&', $link));

        $url = '';

        $data = array();

        parse_str($url_info['query'], $data);

        foreach ($data as $key => $value) {
            if (isset($data['route'])) {
                if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
                    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

                    if ($query->num_rows && $query->row['keyword']) {
                        $url .= '/' . $query->row['keyword'];

                        unset($data[$key]);
                    }
                } elseif ($key == 'path') {
                    $categories = explode('_', $value);

                    foreach ($categories as $category) {
                        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

                        if ($query->num_rows && $query->row['keyword']) {
                            $url .= '/' . $query->row['keyword'];
                        } else {
                            $url = '';

                            break;
                        }
                    }

                    unset($data[$key]);
                }

                /* SEO Custom URL */
//                if( $keyword = $this->getKeyword($data['route']) ){
//                    $url .= $keyword;
//                    unset($data[$key]);
//                }
//                if ($this->checkJsonFileAndGetData()){
//                    $rows = $this->checkJsonFileAndGetData();
//                    if (array_key_exists($data['route'], $rows) && $rows[$data['route']]['status'] == 1){
//                        $url .= $data['route']['keyword'];
//                        unset($data[$key]);
//                    }
//                }
            }
		}

		if ($url) {
			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			return $link;
		}
	}
    /* Get keyword for building url */
//    public function getKeyword($route) {
//        if( count($this->url_list) > 0) {
//            foreach ($this->url_list as $key => $value) {
//                if($route == $key) {
//                    return '/'.$value;
//                }
//            }
//        }
//        return false;
//    }
    /* Check file snd set route */
//    public function setRoute($route) {
//        if( count($this->url_list) > 0 ){
//            foreach ($this->url_list as $key => $value) {
//                if($route == $value) {
//                    return $key;
//                }
//            }
//        }
//        return false;
//    }

    public function getCustomRoutes()
    {
        if ($this->checkJsonFileAndGetData()){
        $routes = array();
        $data = $this->checkJsonFileAndGetData();
            foreach ($data as $id => $arr) {
                echo $id;
                foreach ($arr as $key => $value) {
                    echo $key[1];

                }
                echo "</br>";
            }
        }
//        var_dump($routes);
    }
    public function getCustomKeywords()
    {
        if ($this->checkJsonFileAndGetData())
        $keywords = array();
        foreach ($this->checkJsonFileAndGetData() as $row){
            array_push($keywords, $row['keyword']);
        }
        var_dump($keywords);

        return $keywords;
    }

    public function checkJsonFileAndGetData()
    {
        if (is_file('system\library\rewrites_rules\rules.json')) {
            $json_file = file_get_contents('system\library\rewrites_rules\rules.json', true);
            $data = json_decode($json_file, true);
        } else {
            return false;
        }
        return $data;
    }
}
