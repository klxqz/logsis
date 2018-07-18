<?php

$model = new waModel();


try {
    $sql = 'SELECT `logsis_response` FROM `shop_order` WHERE 0';
    $model->query($sql);
} catch (waDbException $ex) {
    $sql = 'ALTER TABLE `shop_order` ADD `logsis_response` TEXT NULL';
    $model->query($sql);
}