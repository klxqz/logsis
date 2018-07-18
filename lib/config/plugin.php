<?php

return array(
    'name' => 'Курьерская доставка Logsis',
    'description' => '',
    'vendor' => 985310,
    'version' => '2.0.0',
    'img' => 'img/logsis.png',
    'shop_settings' => true,
    'frontend' => false,
    'handlers' => array(
        'backend_orders' => 'backendOrders',
        'backend_order' => 'backendOrder',
    ),
);
//EOF
