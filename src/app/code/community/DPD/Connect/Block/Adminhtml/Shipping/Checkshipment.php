<?php

class DPD_Connect_Block_Adminhtml_Shipping_Checkshipment extends Mage_Core_Block_Template
{
    public function getOrders()
    {
        return Mage::registry('dpd_orders');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function hasFreshFreezeProducts($order) {
        return Mage::helper('dpd')->hasDpdFreshProducts($order);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     */
    public function isParcelshopOrder($order)
    {
        return 'dpdparcelshops_dpdparcelshops' === $order->getShippingMethod();
    }

    /**
     * @return string
     */
    public function getPostUrl() {
        return $this->getUrl('adminhtml/dpdorder/generateAndComplete');
    }

    /**
     * @return string
     */
    public function getOrderOverviewUrl() {
        return $this->getUrl('adminhtml/dpdorder/index');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getRows($order)
    {
        $rows = [];
        $defaultProducts = [];

        $orderItems = $order->getAllVisibleItems();
        foreach($orderItems as $orderItem) {
            $productType = $orderItem->getProduct()->getData('dpd_shipping_type') ?: 'default';

            switch($productType) {
                case 'fresh':
                    $rows[] = [
                        'productType' => 'fresh',
                        'code' => 'FRESH',
                        'expirationDate' => date('Y-m-d', strtotime('+5 weekdays')),
                        'description' => $orderItem->getProduct()->getData('dpd_fresh_description'),
                        'products' => [$orderItem->getProduct()],
                    ];
                    break;

                case 'freeze':
                    $rows[] = [
                        'productType' => 'freeze',
                        'code' => 'FREEZE',
                        'expirationDate' => date('Y-m-d', strtotime('+5 weekdays')),
                        'description' => $orderItem->getProduct()->getData('dpd_fresh_description'),
                        'products' => [$orderItem->getProduct()],
                    ];
                    break;

                case 'default':
                default:
                    $defaultProducts[] = $orderItem->getProduct();
                    break;
            }
        }

        if (0 < count($defaultProducts)) {
            $rows[] = [
                'productType' => 'default',
                'code' => $order->getDpdShippingProduct(),
                'products' => $defaultProducts,
            ];
        }

        return $rows;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    public function getLabelTypeOptions($order)
    {
        $availableProducts = [];
        $shippingProducts =  Mage::getSingleton('dpd/webservice')->getAvailableProducts();
        foreach($shippingProducts as $shippingProduct) {
            if ('fresh' === $shippingProduct['type']
                || ('parcelshop' === $shippingProduct['type'] && !$this->isParcelshopOrder($order))
            ) {
                continue;
            }

            $availableProducts[] = $shippingProduct;
        }

        return $availableProducts;
    }
}