<?php

class shopLogsis {

    public static function status($status_id) {
        if (waConfig::get('is_template')) {
            return;
        }
        $statuses = array(
            1 => 'Новая',
            2 => 'Принята',
            3 => 'На складе',
            4 => 'На доставке',
            5 => 'Доставлена',
            6 => 'Частичный отказ',
            7 => 'Отказ',
            8 => 'Отмена',
        );
        if (isset($statuses[$status_id])) {
            return $statuses[$status_id];
        } else {
            return 'Не определен';
        }
    }

    public static function getStatus($order_id) {
        if (waConfig::get('is_template')) {
            return;
        }
        $app_settings_model = new waAppSettingsModel();
        $data = array(
            'key' => $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'api_key'),
            'inner_n' => $order_id
        );
        return self::request('getstatus', $data);
    }

    public static function createOrder($order_id) {
        if (waConfig::get('is_template')) {
            return;
        }
        $app_settings_model = new waAppSettingsModel();
        $order_model = new shopOrderModel();
        $order = $order_model->getOrder($order_id);
        if (!$order) {
            throw new waException('Не возможно получить данные о заказе');
        }

        if (!empty($order['logsis_is_send'])) {
            throw new waException('Заявка уже отправлена');
        }

        if (empty($order['logsis'])) {
            throw new waException('Не заполнена информация доставки для заказа ' . shopHelper::encodeOrderId($order['id']));
        }
        $logsis = json_decode($order['logsis'], true);

        $total = shop_currency($order['total'], $order['currency'], wa('shop')->getConfig()->getCurrency(), false);

        $data = array(
            'key' => $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'api_key'),
            'inner_n' => $order['id'],
            'delivery_date' => $logsis['delivery_date'],
            'delivery_time1' => $logsis['delivery_time1'],
            'delivery_time2' => $logsis['delivery_time2'],
            'target_name' => $logsis['target_name'],
            'target_contacts' => $logsis['target_contacts'],
            'target_notes' => $logsis['target_notes'],
            'os' => $total,
            'np' => $logsis['np'],
            'price_client' => $total,
            'price_client_delivery' => $order['shipping'],
            'price_client_delivery_nds' => $logsis['price_client_delivery_nds'],
            'order_weight' => $logsis['order_weight'],
            'places_count' => $logsis['places_count'],
            'dimension_side1' => $logsis['dimension_side1'],
            'dimension_side2' => $logsis['dimension_side2'],
            'dimension_side3' => $logsis['dimension_side3'],
            'city' => $logsis['city'],
            'mo_punkt_id' => $logsis['mo_punkt_id'],
            'addr' => $logsis['addr'],
            'sms' => $logsis['sms'],
            'open_option' => $logsis['open_option'],
            'call_option' => $logsis['call_option'],
            'docs_option' => $logsis['docs_option'],
            'partial_option' => $logsis['partial_option'],
            'dress_fitting_option' => $logsis['dress_fitting_option'],
            'lifting_option' => $logsis['lifting_option'],
            'cargo_lift' => $logsis['cargo_lift'],
            'floor' => $logsis['floor'],
            'change_option' => $logsis['change_option'],
        );


        $goods = array();
        foreach ($order['items'] as $item) {
            $weight = self::getItemWeight($item) > 0 ? self::getItemWeight($item) : 0.1;
            $goods[] = array(
                'articul' => $item['sku_id'],
                'artname' => $item['name'],
                'count' => $item['quantity'],
                'weight' => $weight,
                'price' => shop_currency($item['price'], $order['currency'], wa('shop')->getConfig()->getCurrency(), false),
                'nds' => 2,
            );
        }
        $data['goods'] = $goods;

        return self::request('createorder', $data, 'POST');
    }

    public static function confirmOrder($order_id) {
        if (waConfig::get('is_template')) {
            return;
        }
        $app_settings_model = new waAppSettingsModel();
        $data = array(
            'key' => $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'api_key'),
            'inner_n' => $order_id
        );
        return self::request('confirmorder', $data, 'POST');
    }

    public static function testKey() {
        if (waConfig::get('is_template')) {
            return;
        }
        $app_settings_model = new waAppSettingsModel();
        $data = array(
            'key' => $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'api_key'),
        );
        return self::request('testkey', $data);
    }

    private static function request($action, $data, $method = 'GET') {

        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new waException('PHP расширение cURL не доступно');
        }

        if (!($ch = curl_init())) {
            throw new waException('curl init error');
        }

        if (curl_errno($ch) != 0) {
            throw new waException('Ошибка инициализации curl: ' . curl_errno($ch));
        }

        $url = 'http://cab.logsis.ru/apiv2/' . $action;

        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: text/html'
        ));
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = @curl_exec($ch);
        $app_error = null;
        if (curl_errno($ch) != 0) {
            $app_error = 'Ошибка curl: ' . curl_error($ch);
        }
        curl_close($ch);
        if ($app_error) {
            throw new waException($app_error);
        }

        if (empty($response)) {
            throw new waException('Пустой ответ от сервера');
        }

        $response = json_decode($response, true);

        if (!empty($response['response']['Error'])) {
            throw new waException($response['response']['Error']);
        }

        if ($response['status'] == 200) {
            return $response['response'];
        } else {
            throw new waException('Неизвестная ошибка');
        }
    }

    public static function getItemWeight($item) {
        if (waConfig::get('is_template')) {
            return;
        }
        $app_settings_model = new waAppSettingsModel();
        $product_features_model = new shopProductFeaturesModel();

        $weight_feature = $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'weight_feature');
        $sku_features = $product_features_model->getValues($item['product_id'], $item['sku_id']);

        if (!empty($sku_features[$weight_feature]) && $sku_features[$weight_feature] instanceof shopDimensionValue) {
            $weight = $sku_features[$weight_feature];
            return $weight->convert('kg');
        }
        return 0;
    }

    public static function getOrderWeight($order) {
        if (waConfig::get('is_template')) {
            return;
        }
        $app_settings_model = new waAppSettingsModel();
        $weight = 0;
        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                $weight += self::getItemWeight($item);
            }
            $weight += $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'add_weight');
        }
        return $weight;
    }

    public static function getOrderDimensionSide($order) {
        if (waConfig::get('is_template')) {
            return;
        }
        $app_settings_model = new waAppSettingsModel();
        $product_features_model = new shopProductFeaturesModel();
        $volume_feature = $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'volume_feature');

        $sides = array();
        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                $sku_features = $product_features_model->getValues($item['product_id'], $item['sku_id']);
                if (!empty($sku_features[$volume_feature]) && $sku_features[$volume_feature] instanceof shopCompositeValue) {
                    $volume = $sku_features[$volume_feature];
                    if (!empty($volume[0]) && $volume[0] instanceof shopDimensionValue) {
                        $sides[] = array(
                            $volume[0]->convert('cm'),
                            $volume[1]->convert('cm'),
                            $volume[2]->convert('cm'),
                        );
                    }
                }
            }
        }
        return self::calculateVolume($sides);
    }

    private static function calculateVolume($sides) {
        if (count($sides) == 1) {
            return reset($sides);
        } else {
            return array(0, 0, 0);
        }
    }

}
