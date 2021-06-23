<?php

defined('_JEXEC') or die;

class plgJshoppingSalesbeat extends JPlugin
{
    public function onBeforeDisplayProduct(&$product, &$view, &$product_images, &$product_videos, &$product_demofiles)
    {
        // Id свойств
        $widthId = $this->params->get('width_id', 0);
        $heightId = $this->params->get('height_id', 0);
        $depthId = $this->params->get('depth_id', 0);

        // Усредненные значения
        $averageWeight = $this->params->get('average_weight', 0);
        $averageWidth = $this->params->get('average_width', 0);
        $averageHeight = $this->params->get('average_height', 0);
        $averageDepth = $this->params->get('average_depth', 0);

        $extraFields = $product->getExtraFields(0);

        $arFields = [
            'weight' => $averageWeight,
            'width' => $averageWidth,
            'height' => $averageHeight,
            'depth' => $averageDepth
        ];

        $weight = round($product->product_weight, 0);
        if ($weight > 0) $arFields['weight'] = $weight;
        foreach ($extraFields as $extraField) {
            switch ($extraField['id']) {
                case $widthId:
                    $width = (float)str_replace(',', '.', $extraFields['value']);
                    if ($width > 0) $arFields['width'] = $width;
                    break;
                case $heightId:
                    $height = (float)str_replace(',', '.', $extraFields['value']);
                    if ($height > 0) $arFields['height'] = $height;
                    break;
                case $depthId:
                    $depth = (float)str_replace(',', '.', $extraFields['value']);
                    if ($depth > 0) $arFields['depth'] = $depth;
                    break;
            }
        }
        unset($extraFields, $extraField, $width, $height, $depth);

        // Определяем и нормализуем цену
        $price = $product->product_price ?
            round($product->product_price, 2) : 0;

        $arParamsInit = [
            'token' => $this->params->get('api_token'),
            'price_to_pay' => $price,
            'price_insurance' => $price,
            'weight' => $arFields['weight'] * 0.001,
            'x' => $arFields['width'] * 0.1,
            'y' => $arFields['height'] * 0.1,
            'z' => $arFields['depth'] * 0.1,
            'quantity' => round($product->product_quantity, 0),
            'city_by' => 'ip',
            'params_by' => 'params',
            'main_div_id' => 'salesbeat-deliveries-' . rand(1, 999)
        ];

        $jsonParamsInit = json_encode($arParamsInit);

        $link = htmlspecialchars(JUri::base(true) . '/plugins/jshopping/salesbeat/script.js');

        $product->salesbeat_widget_product .= <<<DESCRIPTION
<script src="https://app.salesbeat.pro/static/widget/js/widget.js" charset="UTF-8"></script>
<script src="{$link}"></script>
<div id="{$arParamsInit['main_div_id']}" class="salesbeat-deliveries"></div>

<script>
(function () {
    SalesbeatJoomShopProduct.init({$jsonParamsInit});
})();
</script>
DESCRIPTION;
    }

    public function onBeforeDisplayCheckoutStep3View(&$view)
    {
        $salesbeat = JFactory::getSession()->get('salesbeat');

        $arAvailablePayments = [];
        if (is_array($salesbeat['payments']))
            $arAvailablePayments = $salesbeat['payments'];

        if (!$arAvailablePayments)
            return;

        $paymentsCashId = $this->params->get('payments_cash_id');
        $paymentsCardId = $this->params->get('payments_card_id');
        $paymentsOnlineId = $this->params->get('payments_online_id');

        if (!$paymentsCashId && !$paymentsCardId && !$paymentsOnlineId)
            return;

        $arPayments = [];
        $checkPayment = false;
        foreach ($view->payment_methods as $paymentMethod) {
            $paymentCode = $paymentMethod->payment_id;
            if (in_array($paymentMethod->payment_id, $paymentsCashId))
                $paymentCode = 'cash';

            if (in_array($paymentMethod->payment_id, $paymentsCardId))
                $paymentCode = 'card';

            if (in_array($paymentMethod->payment_id, $paymentsOnlineId))
                $paymentCode = 'online';

            // Проверяем есть ли способ оплаты в справочнике досутпных
            if (in_array($paymentCode, $arAvailablePayments)) {
                $arPayments[] = $paymentMethod;

                // Проверяем является ли оплаты доступным способ оплаты
                if ($checkPayment == false && $paymentMethod->payment_id == $view->active_payment)
                    $checkPayment = true;
            }
        }
        $view->payment_methods = $arPayments; // Перезаписываем массив доступных способов оплат

        // Если нет выбранной доставки, то указываем самую первую
        if ($checkPayment == false) {
            $keyFirstPayment = key($view->payment_methods);
            $view->active_payment = $view->payment_methods[$keyFirstPayment]->payment_id;
        }
    }

    public function onBeforeDisplayCheckoutStep4View($view)
    {
        $shippingId = $this->params->get('shipping_id');
        foreach ($view->shipping_methods as $shippingMethod) {
            if ($shippingMethod->shipping_id != $shippingId) continue;

            $apiToken = $this->params->get('api_token');
            $strError = '';
            if (!$apiToken)
                $strError .= '<span style="color:red">Ошибка загрузки доставки, не указан api-token</span><br>';

            if (mb_strlen($strError) > 0) {
                $shippingMethod->description .= $strError;
                continue;
            }

            $cart = JSFactory::getModel('cart', 'jshop');
            $cart->load();

            // Id свойств
            $widthId = $this->params->get('width_id', 0);
            $heightId = $this->params->get('height_id', 0);
            $depthId = $this->params->get('depth_id', 0);

            // Усредненные значения
            $averageWeight = $this->params->get('average_weight', 0);
            $averageWidth = $this->params->get('average_width', 0);
            $averageHeight = $this->params->get('average_height', 0);
            $averageDepth = $this->params->get('average_depth', 0);

            $arProducts = [];
            foreach ($cart->products as $key => $product) {
                $tProduct = JTable::getInstance('Product', 'jshop');
                $tProduct->load($product['product_id']);
                $extraFields = $tProduct->getExtraFields(0);
                unset($tProduct);

                $arFields = [
                    'weight' => $averageWeight,
                    'width' => $averageWidth,
                    'height' => $averageHeight,
                    'depth' => $averageDepth
                ];

                if ($product['weight'] > 0) $arFields['weight'] = $product['weight'];
                foreach ($extraFields as $extraField) {
                    switch ($extraField['id']) {
                        case $widthId:
                            $width = (float)str_replace(',', '.', $extraFields['value']);
                            if ($width > 0) $arFields['width'] = $width;
                            break;
                        case $heightId:
                            $height = (float)str_replace(',', '.', $extraFields['value']);
                            if ($height > 0) $arFields['height'] = $height;
                            break;
                        case $depthId:
                            $depth = (float)str_replace(',', '.', $extraFields['value']);
                            if ($depth > 0) $arFields['depth'] = $depth;
                            break;
                    }
                }
                unset($extraFields, $extraField, $width, $height, $depth);

                $arProducts[] = [
                    'price_to_pay' => $product['price'],
                    'price_insurance' => $product['price'],
                    'weight' => $arFields['weight'] * 0.001,
                    'x' => $arFields['width'] * 0.1,
                    'y' => $arFields['height'] * 0.1,
                    'z' => $arFields['depth'] * 0.1,
                    'quantity' => $product['quantity'],
                ];
            }

            $shippingMethodId = $shippingMethod->sh_pr_method_id;

            $arParamsInit = [
                'token' => $apiToken,
                'city_code' => '',
                'products' => json_encode($arProducts),
                'shipping_id' => $shippingMethodId,
            ];
            $jsonParamsInit = json_encode($arParamsInit);

            $linkCss = htmlspecialchars(JUri::base(true) . '/plugins/jshopping/salesbeat/style.css');
            $linkJs = htmlspecialchars(JUri::base(true) . '/plugins/jshopping/salesbeat/script.js');

            $shippingMethod->description .= <<<DESCRIPTION
<input type="hidden" name="params[{$shippingId}][shipping_sum]" data-salesbeat-sum />
<input type="hidden" name="params[{$shippingId}][shipping_info]" data-salesbeat-info />

<link href="{$linkCss}" rel="stylesheet" type="text/css" />
<script src="//app.salesbeat.pro/static/widget/js/widget.js" type="text/javascript"></script>
<script src="//app.salesbeat.pro/static/widget/js/cart_widget.js" type="text/javascript"></script>
<script src="{$linkJs}" type="text/javascript"></script>
<div id="sb-cart-widget"></div>
<div id="sb-cart-widget-result"></div>

<script>
(function () {
    setTimeout(function() {
        SalesbeatJoomShopDelivery.init({$jsonParamsInit});
    }, 100);
})();
</script>
DESCRIPTION;
        }
    }

    function onAfterSaveCheckoutStep4(&$adv_user, &$sh_method, &$shipping_method_price, &$cart)
    {
        $deliveryId = $this->params->get('shipping_id');
        if ($sh_method->shipping_id != $deliveryId) return;

        $params = $cart->getShippingParams();
        if ($params['shipping_sum'])
            $cart->setShippingPrice($params['shipping_sum']);
    }

    function onBeforeCreateOrder($order, $cart, $model)
    {
        $deliveryId = $this->params->get('shipping_id');
        if ($order->shipping_method_id != $deliveryId) return;

        $params = unserialize($order->shipping_params_data);
        if ($params['shipping_info'])
            $order->shipping_params = 'Детальная информация:<br>' . $params['shipping_info'] . '<br>';

        // Удаляем не нужную сессию
        JFactory::getSession()->clear('salesbeat');
    }
}