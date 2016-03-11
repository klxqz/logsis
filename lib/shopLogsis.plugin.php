<?php

class shopLogsisPlugin extends shopPlugin {

    public static $plugin_id = array('shop', 'logsis');

    public function backendOrders() {
        if ($this->getSettings('status')) {
            $view = wa()->getView();
            $template_path = wa()->getAppPath('plugins/logsis/templates/BackendOrders.html', 'shop');
            $html = $view->fetch($template_path);
            return array('sidebar_section' => $html);
        }
    }

    public function backendOrder($order) {
        if ($this->getSettings('status')) {
            $settings = $this->getSettings();
            $view = wa()->getView();
            $view->assign('order', $order);
            if (!empty($order['logsis_is_send'])) {
                try {
                    $logsis_status = shopLogsis::getStatus($order['id']);
                    $logsis_status['status_text'] = shopLogsis::status($logsis_status['status']);
                } catch (Exception $ex) {
                    $logsis_status['error'] = $ex->getMessage();
                }
                $view->assign('logsis_status', $logsis_status);
            }
            $template_path = wa()->getAppPath('plugins/logsis/templates/BackendOrder.html', 'shop');
            $html = $view->fetch($template_path);
            return array('action_link' => $html);
        }
    }

}
