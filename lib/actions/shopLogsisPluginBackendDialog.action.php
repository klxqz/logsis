<?php

class shopLogsisPluginBackendDialogAction extends waViewAction {

    public function execute() {
        if ($order_id = waRequest::get('order_id', 0, waRequest::TYPE_INT)) {
            $order_model = new shopOrderModel();
            $order = $order_model->getOrder($order_id);
            if (empty($order['logsis'])) {
                $order['logsis'] = array();
            } else {
                $order['logsis'] = json_decode($order['logsis'], true);
            }

            $this->prepareOrder($order);
            $this->view->assign('order', $order);
            if (waRequest::get('resend')) {
                $this->view->assign('resend', 1);
            }
        } else {
            throw new waException('Не определен номер заказа');
        }
    }

    private function prepareOrder(&$order) {
        $logsis = &$order['logsis'];

        //mo_punkt_id
        if (empty($logsis['addr']['mo_punkt_id']) && !empty($order['params']['shipping_address.city'])) {
            $city = mb_strtoupper($order['params']['shipping_address.city']);
            $kladr = include wa()->getAppPath('plugins/logsis/lib/config/kladr.php', 'shop');
            if ($kladr_id = array_search($city, $kladr)) {
                $logsis['addr']['mo_punkt_id'] = $kladr_id;
            }
        }

        //order_weight
        if (empty($logsis['order_weight'])) {
            $logsis['order_weight'] = shopLogsis::getOrderWeight($order);
        }

        //order_volume
        if (empty($logsis['dimension_side1']) && empty($logsis['dimension_side2']) && empty($logsis['dimension_side3'])) {
            list($logsis['dimension_side1'], $logsis['dimension_side2'], $logsis['dimension_side3']) = shopLogsis::getOrderDimensionSide($order);
        }

        //phone
        if (empty($logsis['target_contacts']) && !empty($order['contact']['phone'])) {
            $logsis['target_contacts'] = preg_replace('/[^0-9]/', '', $order['contact']['phone']);
        }
    }

}
