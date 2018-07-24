<?php

$model = new waModel();

try {
    $model->query("SELECT `logsis` FROM `shop_order` WHERE 0");
    $model->exec("ALTER TABLE `shop_order` DROP `logsis`");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `logsis_is_send` FROM `shop_order` WHERE 0");
    $model->exec("ALTER TABLE `shop_order` DROP `logsis_is_send`");
} catch (waDbException $e) {
    
}

try {
    $model->query("SELECT `logsis_response` FROM `shop_order` WHERE 0");
    $model->exec("ALTER TABLE `shop_order` DROP `logsis_response`");
} catch (waDbException $e) {
    
}
