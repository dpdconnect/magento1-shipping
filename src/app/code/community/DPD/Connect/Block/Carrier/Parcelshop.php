<?php

use DpdConnect\Shipping\Helper\DpdSettings;

/**
 * Class DPD_Connect_Block_Carrier_Parcelshop
 */
class DPD_Connect_Block_Carrier_Parcelshop extends Mage_Core_Block_Template
{
    /**
     * Used to check if the url has to be shown or not. (click here to select..)
     *
     * @var
     */
    private $_showurl;
    /**
     * Array of all configdata to pass to javascript.
     *
     * @var array
     */
    private $_configArray = array();

    /** @var object */
    private $parcelshop;

    public function setParcelShop($parcelShop) {
        $this->parcelshop = $parcelShop;
    }

    /**
     * Check if the url has to be shown or not. (click here to select..)
     *
     * @param $bool
     */
    public function setShowUrl($bool)
    {
        if ($bool && !Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_display'))
            $this->_showurl = $bool;
        return;
    }

    /**
     * Returns showurl variable.
     *
     * @return mixed
     */
    public function getShowUrl()
    {
        return $this->_showurl;
    }

    /**
     * Gets all parcelshops from dpd webservice based on shipping address.
     *
     * @return mixed
     */
    public function getParcelShops()
    {
        $coordinates = Mage::Helper('dpd')->getGoogleMapsCenter();

        $parcelshops = Mage::getSingleton('dpd/webservice')->getParcelShops(
            $coordinates['longitude'],
            $coordinates['latitude'],
            $coordinates['country']
        );

        return $parcelshops;
    }

    /**
     * Get all config data to pass to javascript (array) and jsonencode.
     *
     * @return mixed
     */
    public function getConfig()
    {
        $this->_configArray["saveParcelUrl"] = $this->getUrl('dpd/ajax/saveparcel', array('_secure' => true));
        $this->_configArray["jwtUrl"] = $this->getUrl('dpd/ajax/jwt', array('_secure' => true));
        $this->_configArray["invalidateParcelUrl"] = $this->getUrl('dpd/ajax/invalidateparcel', array('_secure' => true));
        $this->_configArray["windowParcelUrl"] = $this->getUrl('dpd/ajax/windowindex', array('_secure' => true));
        $this->_configArray["ParcelUrl"] = $this->getUrl('dpd/ajax/index', array('_secure' => true));
        $this->_configArray["gmapsDpdParcelShopUseDpdKey"] = (bool)Mage::getStoreConfig('carriers/dpdparcelshops/use_dpd_maps_key');

        $this->_configArray["gmapsDpdParcelShopGoogleKey"] = Mage::helper('core')->decrypt(Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_api'));
        $shippingAddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
        $this->_configArray["shippingaddress"] =
            $shippingAddress->getPostcode()
            .' ' .$shippingAddress->getCity()
            .' ' .$shippingAddress->getCountryId();

        $this->_configArray["loadingmessage"] = '<span class="message">'.$this->__('Loading DPD parcelshop map based on your address...').'</span>';
        $this->_configArray["gmapsHeight"] = Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_height') . 'px';
        $this->_configArray["gmapsWidth"] = Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_width') . 'px';
        $this->_configArray["gmapsDisplay"] = (bool)Mage::getStoreConfig('carriers/dpdparcelshops/google_maps_display');
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $this->_configArray["dpdSelected"] = $quote->getDpdSelected();
        $this->_configArray["localCode"] = substr(Mage::getStoreConfig('general/locale/code'),0,2);

        return Mage::helper('core')->jsonEncode($this->_configArray);
    }

    /**
     * Render the html for openinghours. (to keep template files clean from to much functional php)
     *
     * @return string
     */
    public function getOpeningHoursHtml()
    {
        $html = "";

        foreach($this->parcelshop['openingHours'] as $openinghours) {
            $openingHoursMorning = $openinghours['openMorning'] . ' - ' . $openinghours['closeMorning'];
            $openingHoursAfternoon = $openinghours['openAfternoon'] . ' - ' . $openinghours['closeAfternoon'];

            if ($openingHoursMorning == '00:00 - 00:00') {
                $openingHoursMorning = Mage::helper('dpd')->__('closed');
            }

            if ($openingHoursAfternoon == '00:00 - 00:00') {
                $openingHoursAfternoon = Mage::helper('dpd')->__('closed');
            }

            $html .= '<ul class="daywrapper left">';
            $html .= '<li class="day">' . Mage::helper('dpd')->__($openinghours['weekday']) . '</li>';
            $html .= '<li class="hour">' . $openingHoursMorning . '</li>';
            $html .= '<li class="hour">' . $openingHoursAfternoon . '</li>';
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Returns quote object.
     *
     * @return mixed
     */
    public function getQuote()
    {
        return Mage::getModel('checkout/cart')->getQuote();
    }

    /**
     * Returns shipping cost.
     *
     * @return string
     */
    public function getShippingAmount() {
        $cost = $this->getQuote()->getShippingAddress()->getShippingAmount();

        return number_format((float)$cost, 2, '.', '');
    }
}