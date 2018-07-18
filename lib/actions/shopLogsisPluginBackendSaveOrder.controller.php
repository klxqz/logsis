<?php

class shopLogsisPluginBackendSaveOrderController extends waJsonController {

    public function execute() {
        try {
            if ($order_id = waRequest::post('order_id', 0, waRequest::TYPE_INT)) {
                $logsis_params = waRequest::post('logsis', array());
                $order_model = new shopOrderModel();
                $order_model->updateById($order_id, array('logsis' => json_encode($logsis_params)));
                if (waRequest::post('resend')) {
                    $order_model->updateById($order_id, array('logsis_is_send' => 0));
                }
                if (waRequest::post('send')) {
                    $response = shopLogsis::createOrder($order_id);
                    if (!empty($response['order_id'])) {
                        $order_model->updateById($order_id, array('logsis_is_send' => 1, 'logsis_response' => json_encode($response)));
                    }
                }
            } else {
                throw new waException('Не определен номер заказа');
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
