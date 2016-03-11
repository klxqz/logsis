<?php

class shopLogsisPluginBackendSendOrdersController extends waJsonController {

    public function execute() {
        try {
            $order_ids = waRequest::post('order_ids', array(), waRequest::TYPE_ARRAY_INT);
            if (empty($order_ids)) {
                throw new waException('Нет выбранных заказов для отправки');
            }
            $order_model = new shopOrderModel();
            foreach ($order_ids as $order_id) {
                $response = shopLogsis::newOrder($order_id);
                if (!empty($response['order_id'])) {
                    $order_model->updateById($order_id, array('logsis_is_send' => 1));
                }
            }
            $this->response['message'] = 'Успешно';
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
