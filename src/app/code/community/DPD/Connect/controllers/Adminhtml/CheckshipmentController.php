<?php

/**
* Class DPD_Connect_Adminhtml_DpdconfigController
*/
class DPD_Connect_Adminhtml_CheckshipmentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Load indexpage of this controller.
     */
    public function indexAction()
    {
        $orderIds = $this->getRequest()->getParam('entity_id');

        if(!is_array($orderIds)) {
            $orderIds = [$orderIds];
        }

        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('entity_id', array('in'=> $orderIds));

        $maxOrderCount = 100;
        if(count($orderIds) > $maxOrderCount){
            $message = Mage::helper('dpd')->__("The maximum number of orders to process is %s. You selected %s. Please deselect some orders and try again.",$maxOrderCount, count($orderIds));
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('adminhtml/dpdorder/index');
            return $this;
        }

        try {
            $nonExistingDpdLabels = false;
            $orders = [];
            /** @var Mage_Sales_Model_Order $order */
            foreach ($orderCollection as $order) {
                if (!Mage::helper('dpd')->isDPDOrder($order)) {
                    Mage::getSingleton('core/session')
                        ->addError(sprintf(__('Order %s is not a DPD order.'), $order->getIncrementId()));

                    continue;
                }

                if(!$order->getShipmentsCollection()->count()) {
                    $nonExistingDpdLabels = true;
                }

                foreach ($order->getShipmentsCollection as $shipment) {
                    if(!$shipment->getDpdLabelPath()) {
                        $nonExistingDpdLabels = true;
                    }
                }

                $orders[] = $order;
            }

            if (0 === count($orders)) {
                Mage::getSingleton('core/session')
                    ->addError('DPD - None of the selected orders are eligible for generating DPD shipment labels this way.');

                return $this->_redirect('adminhtml/dpdorder/index');
            }

            if(!$nonExistingDpdLabels) {
                $message = Mage::helper('dpd')->__("Some of the selected orders are not ready to be shipped or have already been shipped, operation canceled.");
                Mage::getSingleton('core/session')->addError($message);

                return $this->_redirect('adminhtml/dpdorder/index');
            }

            Mage::register('dpd_orders', $orders);
        } catch (\Exception $e) {
            $message = Mage::helper('dpd')->__("An error has occured while generating the labels. Check the logs");
            Mage::getSingleton('core/session')->addError($message);
            return $this->_redirect('adminhtml/dpdorder/index');
        }

        $this->loadLayout();
        $this->_title($this->__('dpd'))->_title($this->__('DPD Shipping'));

        $this->renderLayout();
    }
}