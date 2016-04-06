<?php

class shopLogsisPluginSettingsAction extends waViewAction {

    public function execute() {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get(shopLogsisPlugin::$plugin_id);
        $feature_model = new shopFeatureModel();
        $features = $feature_model->getByField('type', array('dimension.weight', '3d.dimension.length'), true);

        $this->view->assign('settings', $settings);
        $this->view->assign('features', $features);
    }

}
