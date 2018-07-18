<?php

$plugin_id = array('shop', 'logsis');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');
$app_settings_model->set($plugin_id, 'api_key', '');
$app_settings_model->set($plugin_id, 'weight_feature', 'weight');
$app_settings_model->set($plugin_id, 'add_weight', '0');
$app_settings_model->set($plugin_id, 'volume_feature', '');


$model = new waModel();
try {
    $sql = 'SELECT `logsis` FROM `shop_order` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE `shop_order` ADD `logsis` TEXT NULL';
    $model->query($sql);
}

try {
    $sql = 'SELECT `logsis_response` FROM `shop_order` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE `shop_order` ADD `logsis_response` TEXT NULL';
    $model->query($sql);
}

try {
    $sql = 'SELECT `logsis_is_send` FROM `shop_order` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = "ALTER TABLE `shop_order` ADD `logsis_is_send` TINYINT( 1 ) NOT NULL DEFAULT '0'";
    $model->query($sql);
}

