<?php

use DpdConnect\Sdk\ClientBuilder;

/**
 * Class DPD_Connect_AjaxController
 */
class DPD_Connect_AjaxController extends Mage_Core_Controller_Front_Action {
    /**
     * Load indexpage of this controller.
     */
    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Return window for overlay mode of the shipping method.
     */
    public function windowindexAction(){
        $this->loadLayout();
        if($this->getRequest()->getParam('windowed')){
            $this->getLayout()->getBlock('dpd')->setIsAjax(true);
        }

        $this->renderLayout();
    }

    /**
     * Saves the parcel and returns the selected parcelshop template.
     */
    public function saveparcelAction(){
        $parcelData =  $this->getRequest()->getPost();

        $quote = Mage::getModel('checkout/cart')->getQuote();

        $quote
            ->setDpdSelected(1)
            ->setDpdParcelshopId($parcelData['parcelShopId'])
            ->setDpdCompany($parcelData['company'])
            ->setDpdStreet($parcelData['street'])
            ->setDpdHouseNumber($parcelData['houseNo'])
            ->setDpdZipcode($parcelData['zipCode'])
            ->setDpdCity($parcelData['city'])
            ->setDpdCountry($parcelData['isoAlpha2'])
            ->save();

        $quote->getShippingAddress()
            ->setShippingMethod('dpdparcelshops_dpdparcelshops')
            ->setCollectShippingRates(true)
            ->requestShippingRates();

        $quote->save();

        $this->loadLayout();

        $block = $this->getLayout()
            ->createBlock('dpd/carrier_parcelshop');

        $block->setParcelShop($parcelData);
        $block->setTemplate('dpd/parcelshopselected.phtml');

        $html = $block->toHtml();

        $this->getResponse()->setBody($html);
    }

    /**
     * Unsets selected parcelshop and returns select parcelshop template.
     */
    public function invalidateparcelAction(){
        Mage::getModel('checkout/cart')->getQuote()->setDpdSelected(0)->save();
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Get Google API key
     */
    public function jwtAction(){
        $token = Mage::getSingleton('dpd/webservice')->getToken()->getPublicJWTToken(
            Mage::getStoreConfig(DPD_Connect_Model_Webservice::XML_PATH_DPD_API_USERNAME),
            Mage::helper('core')->decrypt(Mage::getStoreConfig(DPD_Connect_Model_Webservice::XML_PATH_DPD_API_PASSWORD))
        );

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(['token' => $token])
        );
    }
}