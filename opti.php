<?php 
public function updateLoadProduct()
    {
        $this->load->model('extension/module/import');
        $this->load->model('catalog/manufacturer');
        
        $res = $this->model_extension_module_import->getSettings();
        $settings = array();
        foreach ($res->rows as $key => $value) {
            $settings[$value['name']] = $value['value'];
        }
        unset($res);
        $settings['api-list-category-id_update'] = explode(",", $settings['api-list-category-id_update']);

        $price_settings = array();
        
        $price_settings["manufacturer_overprice"] = $this->getManufacturerOverprice();
        
        $price_settings["category_overprice"] = $this->getCategoryOverprice();
        
        $price_settings["default_overprice"] = doubleval(str_replace(",", ".", $settings["api-general-overprice"]));


        $price_settings["usd_curs"] = $this->getUsdCurs();

        $price_settings["shop_cat"] = $this->getProductByCategory();
        $price_settings["skus_product"] = $this->getProductSkus();
        $cnt_qu = 25; // длина очереди

        $config = new NetLabConfig();
        $httpClient = new HttpClient();
        $urlBuilder = new UrlBuilder($config);
        $service = new Service($config, $urlBuilder, $httpClient);

        $token = $service->getToken($config->USERNAME, $config->PASSWORD);

        $catalogs = $service->getCatalogsAction($token);
        $catalogs = str_replace("{} &&", "", $catalogs);
        $catalogs = json_decode($catalogs, 1);
        // получение каталогов
        $catalogs = $catalogs['catalogsResponse']['data']['catalogs'];

        foreach ($catalogs as $key => $value) {
            // обрабатываем только прайс-лист
            if($value['name'] == 'Прайс-лист') {
                foreach ($settings['api-list-category-id_update'] as $category_id) {
                    $products = $service->getCategoryAction($token, $value['name'], (int)$category_id);
                    $products = str_replace("{} &&", "", $products);
                    $products = json_decode($products, 1);
                    $products = $products['categoryResponse']['data']['goods'];
                    $cnt_products = count($products)-1; // чтобы не вычислять в цикле ниже
                    $i=0;
                    $pattern_sql = "UPDATE `".DB_PREFIX."product` SET `price` = ( CASE %rr_price% END), `quantity` = ( CASE %rr_quantity% END) WHERE sku IN ( %rr_sku% )";
                    $rr_price = "";
                    $rr_quantity = "";
                    $rr_sku = array();

                    foreach ($products as $num => $product) {
                       // формируем sql запрос
                       // $product["id"]; - sku в таблице товаров используем в предикате 
                       // $products[$num]['properties']['цена по категории F']; // цена для обновления используем в секции SET
                       // $products[$num]['properties']['количество на Лобненской']; - кол-во для обновление используем в секции SET
                        $cur_price = $this->preparePrice($products[$num]['properties']['цена по категории F'], $price_settings, $price_settings["skus_product"][$product["id"]]["product_id"], $price_settings["skus_product"][$product["id"]]['manufacturer_id']);

                        if(!$cur_price) $cur_price = " `price` "; // оставляем старую цену

                        $rr_price .= "WHEN `sku` = ".$product["id"]." THEN ".$cur_price." ";
                        $rr_quantity .= "WHEN `sku` = ".$product["id"]." THEN ".$products[$num]['properties']['количество на Лобненской']." ";
                        $rr_sku[] = $product["id"];
                        // каждые cnt_qu товаров обновляем за 1-запрос-т.к возможно есть ограничение по длине запроса - это позволит ускорить работу с базой данных в cnt_qu раз

                        if($rr_sku && (($i%$cnt_qu == 0 && $i != 0) || $i == $cnt_products)){
                            $query = str_replace("%rr_price%",  $rr_price, $pattern_sql);
                            $query = str_replace("%rr_quantity%"  , $rr_quantity, $query);
                            $query = str_replace("%rr_sku%"  ,  implode(",", $rr_sku), $query);
                            // var_dump($i);
                            // выполнение запроса
                            $this->model_extension_module_import->jobQuery($query);
                            // end выполнение запроса
                            $query = "";
                            $rr_price = "";
                            $rr_quantity = "";
                            $rr_sku = array();
                        }
                        $i++;
                        // exit;                    
                    }
                }
            }
        }
    }