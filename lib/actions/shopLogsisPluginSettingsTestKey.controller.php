<?php

class shopLogsisPluginSettingsTestKeyController extends waJsonController {

    public function execute() {
        try {
            $response = shopLogsis::testKey();
            $this->response = 'Ключ корректный. Идентификатор клиента ' . $response['client_id'] . ' (' . $response['client_name'] . ')';
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
