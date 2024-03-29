<?php


/**
 * Class DPD_Connect_Model_Observer
 */
class DPD_Connect_Model_Observer
{
    /**
     * Observes html load, this will add html to the Parcelshop shipping method and set default shipping method.
     *
     * @param $observer
     */
    public function core_block_abstract_to_html_after($observer)
    {
        if ($observer->getBlock() instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available) {
            //get HTML
            $html = $observer->getTransport()->getHtml();

            //set default if in config
            $html = Mage::helper('dpd')->checkShippingDefault($html);

            //replace label by html
            $html = Mage::helper('dpd')->addHTML($html);

            //set HTML
            $observer->getTransport()->setHtml($html);
        }
    }

    /**
     * Triggered when saving a shipping method, this saves dpd data to the customer address without saving it on customer.
     *
     * @param $observer
     */
    public function checkout_controller_onepage_save_shipping_method($observer)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $address = $quote->getShippingAddress();
        if ($address->getShippingMethod() == "dpdparcelshops_dpdparcelshops") {
            $address->unsetAddressId()
                ->unsetTelephone()
                ->setSaveInAddressBook(0)
                ->setFirstname('DPD ParcelShop: ')
                ->setLastname($quote->getDpdCompany())
                ->setStreet($quote->getDpdStreet()  .' ' .$quote->getDpdHouseNumber())
                ->setCity($quote->getDpdCity())
                ->setPostcode($quote->getDpdZipcode())
                ->setCountryId($quote->getDpdCountry())
                ->save();
        }
    }

    /**
     * Sets generate return label button on order detail view in the admin.
     * Sets download dpdlabel button on shipment order detail.
     *
     * @param $observer
     */
    public function core_block_abstract_to_html_before($observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View && $block->getRequest()->getControllerName() == 'sales_order') {
            $orderId = Mage::app()->getRequest()->getParam('order_id');
            $block->addButton('print_retour_label', array(
                'label' => Mage::helper('dpd')->__('DPD Return Label'),
                'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/dpdorder/generateRetourLabel/order_id/' . $orderId) . '\')',
                'class' => 'go'
            ));

        }
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View && $block->getRequest()->getControllerName() == "sales_order_shipment") {
            $shipment = Mage::registry('current_shipment');
            $shipmentId = $shipment->getId();
            $order = Mage::getModel('sales/order')->load($shipment->getOrderId());
            if (strpos($order->getShippingMethod(), 'dpd') !== false) {
                $block->addButton('download_dpd_label', array(
                    'label' => Mage::helper('dpd')->__('Download DPD Label'),
                    'onclick' => 'setLocation(\'' . Mage::helper("adminhtml")->getUrl('adminhtml/dpdorder/downloadDpdLabel/shipment_id/' . $shipmentId) . '\')',
                    'class' => 'scalable save'
                ));
            }
        }
    }

    /**
     * @param $observer
     * @throws Exception
     */
    public function sales_order_shipment_save_before($observer)
    {
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $url = Mage::getSingleton('core/url')->parseUrl($currentUrl);
        $path = $url->getPath();

        if (false !== stripos($path, 'admin/dpdorder')) {
            return;
        }

        if(Mage::helper('dpd')->hasDpdFreshProducts($observer->getEvent()->getShipment()->getOrder())) {
            Mage::getSingleton('core/session')->addError(Mage::helper('dpd')->__('This order has DPD Fresh/Freeze products, shipments can only be made through the Sales/DPD Orders screen'));
            throw new Exception(Mage::helper('dpd')->__('This order has DPD Fresh/Freeze products, shipments can only be made through the Sales/DPD Orders screen'));
        }

        $shipment = $observer->getEvent()->getShipment();
        if (!$shipment->hasId() && !$shipment->getTotalWeight()) {
            $weight = Mage::helper('dpd')->calculateTotalShippingWeight($shipment);
            $shipment->setTotalWeight($weight);
        }
    }

    /**
     * If the checkout is a Onestepcheckout and dpdselected is true, we need to copy the address on submitting
     *
     * @param $observer
     */
    public function checkout_submit_all_after($observer)
    {
        if (Mage::helper('dpd')->getIsOnestepCheckout()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $address = $quote->getShippingAddress();
            if ($address->getShippingMethod() == "dpdparcelshops_dpdparcelshops" && (bool)$quote->getDpdSelected()) {
                $address->unsetAddressId()
                    ->unsetTelephone()
                    ->setSaveInAddressBook(0)
                    ->setFirstname('DPD ParcelShop: ')
                    ->setLastname($quote->getDpdCompany())
                    ->setStreet($quote->getDpdStreet()  .' ' .$quote->getDpdHouseNumber())
                    ->setCity($quote->getDpdCity())
                    ->setPostcode($quote->getDpdZipcode())
                    ->setCountryId($quote->getDpdCountry())
                    ->save();
            }
            $quote->setDpdSelected(0);
        }
    }

    /**
     * If Billing/Shipping address was changed, reset the DPD shipping Method.
     *
     * @param $observer
     */
    public function controller_action_predispatch_onestepcheckout_ajax_save_billing($observer)
    {
        if (Mage::helper('dpd')->getIsOnestepCheckout()) {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            if ($quote->getDpdSelected()) {
                $quote->setDpdSelected(0);
            }
        }
    }
}