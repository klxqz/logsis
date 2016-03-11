<?php

class shopLogsis {

    public static function status($status_id) {
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
        $app_settings_model = new waAppSettingsModel();
        $data = array(
            'key' => $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'api_key'),
            'action' => 'getstatus',
            'order_id' => $order_id
        );
        return self::request($data);
    }

    public static function newOrder($order_id) {
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

        $sdata = array(
            'type' => $logsis['type'],
            'inner_n' => $order['id'],
            'delivery_date' => $logsis['delivery_date'],
            'delivery_time1' => $logsis['delivery_time1'],
            'delivery_time2' => $logsis['delivery_time2'],
            'target_name' => $logsis['target_name'],
            'target_contacts' => $logsis['target_contacts'],
            'target_notes' => $logsis['target_notes'],
            'addr' => Array(
                'mo_punkt_id' => $logsis['addr']['mo_punkt_id'],
                'street' => $logsis['addr']['street'],
                'building' => $logsis['addr']['building'],
                'corpus' => $logsis['addr']['corpus'],
                'office' => $logsis['addr']['office']
            ),
            'os' => $total,
            'price_client' => $total,
            'price_client_delivery' => $order['shipping'],
            'dimension_side1' => $logsis['dimension_side1'],
            'dimension_side2' => $logsis['dimension_side2'],
            'dimension_side3' => $logsis['dimension_side3'],
            'order_weight' => $logsis['order_weight'],
            'is_complect' => 0,
            'np' => $logsis['np'],
            'sms' => $logsis['sms'],
            'is_packed' => 0,
        );

        $goods = array();
        foreach ($order['items'] as $item) {
            $goods[] = array(
                'artname' => $item['name'],
                'price' => shop_currency($item['price'], $order['currency'], wa('shop')->getConfig()->getCurrency(), false),
                'weight' => self::getItemWeight($item),
                'count' => $item['quantity'],
            );
        }
        $sdata['goods'] = $goods;

        $data = array(
            'key' => $app_settings_model->get(shopLogsisPlugin::$plugin_id, 'api_key'),
            'action' => 'neworder',
            'order' => urlencode(json_encode($sdata))
        );

        return self::request($data);
    }

    private static function request($data) {
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new waException('PHP расширение cURL не доступно');
        }

        if (!($ch = curl_init())) {
            throw new waException('curl init error');
        }

        if (curl_errno($ch) != 0) {
            throw new waException('Ошибка инициализации curl: ' . curl_errno($ch));
        }

        @curl_setopt($ch, CURLOPT_URL, 'http://cab.logsis.ru/apiv1/index');
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
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

        if ($response['status'] == 200 && !empty($response['response']['order_id'])) {
            return $response['response'];
        } else {
            throw new waException('Неизвестная ошибка');
        }
    }

    public static function getItemWeight($item) {
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
