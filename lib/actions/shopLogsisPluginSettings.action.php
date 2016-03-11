<?php

class shopLogsisPluginSettingsAction extends waViewAction {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopLogsisPlugin::$plugin_id);
        $feature_model = new shopFeatureModel();
        $features = $feature_model->getFeatures(true, null, 'id');
        
        $this->view->assign('settings', $settings);
        $this->view->assign('features', $features);
    }

}
