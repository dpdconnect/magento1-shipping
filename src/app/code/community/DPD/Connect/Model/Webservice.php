<?php


use DpdConnect\Sdk\ClientBuilder;

/**
 * Class DPD_Connect_Model_Webservice
 */
class DPD_Connect_Model_Webservice extends Mage_Core_Model_Abstract
{
    /**
     * Product type for shipmentservice, should be always 'CL' as instructed by DPD.
     */
    CONST SHIPMENTSERVICE_PRODUCT = 'CL';

    /**
     * Ordertype for shipmentservice, should be always 'consigment' as instructed by DPD.
     */
    CONST SHIPMENTSERVICE_ORDERTYPE = 'consignment';

    /**
     * Paperformat for Return labels, should always be 'A6' as instructed by DPD.
     */
    CONST SHIPMENTSERVICE_RETURN_PAPERFORMAT = 'A6';

    /**
     * Product attribute HS code
     */
    CONST XML_PATH_DPD_PRODUCT_ATTR_HS_CODE = 'shipping/dpd_product_attributes/hs_code';

    /**
     * XML path to configuration setting for sending_depot.
     */
    CONST XML_PATH_DPD_SENDING_DEPOT = 'shipping/dpd/sending_depot';

    /**
     * XML path to configuration setting for hostname.
     */
    CONST XML_PATH_DPD_API_HOSTNAME = 'shipping/dpd/hostname';

    /**
     * XML path to configuration setting for username.
     */
    CONST XML_PATH_DPD_API_USERNAME = 'shipping/dpd/username';

    /**
     * XML path to configuration setting for password.
     */
    CONST XML_PATH_DPD_API_PASSWORD = 'shipping/dpd/password';

    /**
     * XML path to configuration setting for sender name.
     */
    CONST XML_PATH_DPD_SENDER_NAME = 'shipping/dpdclassic/sender_name';

    /**
     * XML path to configuration setting for sender street.
     */
    CONST XML_PATH_DPD_SENDER_STREET = 'shipping/dpdclassic/sender_street';

    /**
     * XML path to configuration setting for sender streetnumber.
     */
    CONST XML_PATH_DPD_SENDER_STREETNUMBER = 'shipping/dpdclassic/sender_streetnumber';

    /**
     * XML path to configuration setting for sender country.
     */
    CONST XML_PATH_DPD_SENDER_COUNTRY = 'shipping/dpdclassic/sender_country';

    /**
     * XML path to configuration setting for sender zipcode.
     */
    CONST XML_PATH_DPD_SENDER_ZIPCODE = 'shipping/dpdclassic/sender_zipcode';

    /**
     * XML path to configuration setting for sender city.
     */
    CONST XML_PATH_DPD_SENDER_CITY = 'shipping/dpdclassic/sender_city';

    /**
     * XML path to configuration setting for email.
     */
    CONST XML_PATH_DPD_EMAIL = 'shipping/dpdclassic/sender_email';

    /**
     * XML path to configuration setting for phone number.
     */
    CONST XML_PATH_DPD_PHONE = 'shipping/dpdclassic/sender_phonenumber';

    /**
     * XML path to configuration setting for vat.
     */
    CONST XML_PATH_DPD_VAT_NUMBER = 'shipping/dpdclassic/sender_vat';

    /**
     * XML path to configuration setting for sender eori.
     */
    CONST XML_PATH_DPD_EORI_NUMBER = 'shipping/dpdclassic/sender_eori';

    /**
     * XML path to configuration setting for paperformat of shipping labels.
     */
    CONST XML_PATH_DPD_PAPERFORMAT = 'shipping/dpdclassic/paperformat';

    /**
     * XML path to configuration setting for weight unit to send to webservice;
     */
    CONST XML_PATH_DPD_WEIGHTUNIT = 'shipping/dpdclassic/weight_unit';

    /**
     * XML path to configuration setting for the maximum number of parcelshops that should be returned by the webservice.
     */
    CONST XML_PATH_PARCELSHOP_MAXPOINTERS = 'carriers/dpdparcelshops/google_maps_maxpointers';

    /**
     * Get depot from store config
     *
     * @return mixed
     */
    protected function _getSendingDepot()
    {
        return Mage::getStoreConfig(self::XML_PATH_DPD_SENDING_DEPOT);
    }

    /**
     * Get depot from store config
     *
     * @return mixed
     */
    protected function _getHsCodeDefault()
    {
        return Mage::getStoreConfig(self::XML_PATH_DPD_PRODUCT_ATTR_HS_CODE);
    }

    /**
     * Returns the sender information filled in the backend, is used for the generation of labels.
     *
     * @return array
     */
    protected function _getSenderInformation()
    {
        return [
            'name1'             => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_NAME),
            'street'            => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_STREET),
            'housenumber'       => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_STREETNUMBER),
            'postalcode'        => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_ZIPCODE),
            'city'              => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_CITY),
            'country'           => Mage::getStoreConfig(self::XML_PATH_DPD_SENDER_COUNTRY),
            'email'             => Mage::getStoreConfig(self::XML_PATH_DPD_EMAIL),
            'phoneNumber'       => Mage::getStoreConfig(self::XML_PATH_DPD_PHONE),
            'vatnumber'         => Mage::getStoreConfig(self::XML_PATH_DPD_VAT_NUMBER),
            'eorinumber'        => Mage::getStoreConfig(self::XML_PATH_DPD_EORI_NUMBER),
            'commercialAddress' => true,
        ];
    }

    /**
     * @return bool|\DpdConnect\Sdk\ClientInterface
     */
    protected function _apiBuilder()
    {
        $username = Mage::getStoreConfig(self::XML_PATH_DPD_API_USERNAME);
        $password = Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_DPD_API_PASSWORD));
        $hostname = Mage::getStoreConfig(self::XML_PATH_DPD_API_HOSTNAME);

        try {
            $clientBuiler = new ClientBuilder($hostname, [
                'webshopType'       => 'Magento',
                'webshopVersion'    => '1.9.4.2',
                'pluginVersion'     => '2.0'
            ]);

            $client = $clientBuiler->buildAuthenticatedByPassword(
                $username, $password
            );

            return $client;
        } catch(\Exception $exception) {
            Mage::helper('dpd')->log($exception->getMessage(), Zend_Log::ERR);
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong with the webservice, please check the log files.');
            return false;
        }
    }

    /**
     * Get parcelshops from webservice findParcelShopsByGeoData.
     *
     * @param $longitude
     * @param $latitude
     * @return mixed
     */
    public function getParcelShops($longitude, $latitude)
    {
        $apiBuilder = $this->_apiBuilder();

        $parameters = array(
            'longitude'                 => $longitude,
            'latitude'                  => $latitude,
            'limit'                     => 10,
            'countryIso'                => 'nl',
            'consigneePickupAllowed'    => 'true'
        );

        $result = $apiBuilder->getParcelshop()->getList($parameters);

        return $result;
    }

    /**
     * Get a returnlabel from webservice storeOrders.
     * $recipient = array('name1' => '', 'street' => '', 'country' => '', 'zipCode' => '', 'city' => '');
     *
     * @param $recipient
     * @param null $orderId
     * @return mixed
     */
    public function getReturnLabel($recipient, $orderId = null)
    {
        $apiBuilder = $this->_apiBuilder();
        if (!$apiBuilder) {
            die('no client');
        }

        $sender = $this->_getSenderInformation();
        $recipient['postalcode'] = str_replace(' ', '', $recipient['postalcode']);

        $request = array(
            'printOptions'  => $this->_printOptions(),
            'createLabel'   => true,
            'shipments'     => [[
                'orderId'       => $orderId,
                'sendingDepot'  => $this->_getSendingDepot(),
                'sender'        => $sender,
                'receiver'      => $recipient,
                'product'       => array(
                    'productCode' => 'RETURN',
                ),
                "parcels" => [[
                    'customerReferences' => array($orderId),
                    'volume' => '000010000',
                    'weight' => 100
                ]],
            ]]
        );

        try {
            $result = $apiBuilder->getShipment()->create($request);

            if ($result->getStatus() >= 300) {
                $error = $result->getContent()['message'];
                Notice::add($error, NoticeType::ERROR);
                throw new Exception($error);
            }

            return $result;

        } catch(\Exception $exception) {
            Mage::helper('dpd')->log($exception->getMessage(), Zend_Log::ERR);
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong with the webservice, please check the log files.');
            return false;
        }
    }

    /**
     * Get a shippinglabel from webservice storeOrders.
     * $recipient = array('name1' => '', 'street' => '', 'country' => '', 'zipCode' => '', 'city' => '');
     *
     * @param $recipient
     * @param $order
     * @param $shipment
     * @param bool $parcelshop
     * @return mixed
     */
    public function getShippingLabel($recipient, Mage_Sales_Model_Order $order, $shipment, $parcelshop = false)
    {
        $apiBuilder = $this->_apiBuilder();
        $sender = $this->_getSenderInformation();
        $recipient['postalcode'] = str_replace(' ', '', $recipient['postalcode']);
        $language = Mage::helper('dpd')->getLanguageFromStore($order->getStoreId());
        $shippingMethod = $order->getShippingMethod();

        if (Mage::getStoreConfig(self::XML_PATH_DPD_WEIGHTUNIT) == "") {
            $weight = $shipment->getTotalWeight() * 100;
        } else {
            $weight = $shipment->getTotalWeight() * Mage::getStoreConfig(self::XML_PATH_DPD_WEIGHTUNIT);
        }

        $i = 0;
        $lines = [];
        $orderitems = $order->getAllVisibleItems();
        foreach ($orderitems as $item) {
            $product = $item->getProduct();
            echo $product->getId();
            $i++;
            $lines[] = [
                'description'           => $product->getName(),
                'harmonizedSystemCode'  => (!empty($product->getHsCode()) ? substr($product->getHsCode(), 0, 8) : (!empty($this->_getHsCodeDefault()) ? substr($this->_getHsCodeDefault(), 0, 8)     : "")),
                'originCountry'         => (isset($item['item_origin_country']) ? $item['item_origin_country'] : "NL"),
                'quantity'              => (int) $item->getQtyOrdered(),
                'grossWeight'           => (int) round($item->getWeight()),
                'totalAmount'           => (float) 10.00,
                'customsLineNumber'     => $i,
            ];
        }
        
        $request = array(
            'printOptions'  => $this->_printOptions(),
            'createLabel'   => true,
            'shipments'     => []
        );

        $shipment = [
            'orderId'       => $order->getIncrementId(),
            'sendingDepot'  => $this->_getSendingDepot(),
            'sender'        => $sender,
            'receiver'      => $recipient,
            'product'       => array(
                'productCode' => $this->getProductCode($shippingMethod),
                'saturdayDelivery' => $this->getSaturdayDelivery($shippingMethod),
            ),
            "parcels" => [[
                'customerReferences' => array($order->getIncrementId()),
                'volume'             => '000010000',
                'weight'             =>  (int) round($shipment->getTotalWeight(),0)
            ]],
            'customs' => [
                'description'         => "verkoop product",
                'terms'               => 'DAP',
                'reasonForExport'     => 'SALE',
                'totalAmount'         => 10.00,
                'totalCurrency'       => $order->getOrderCurrency()->getCurrencyCode(),
                'consignee'           => $sender,
                'consignor'           => $recipient,
                'customsLines'        => $lines
            ]
        ];

        if ($parcelshop) {
            $parcelShopId = $order->getDpdParcelshopId();
            $shipment['product']['parcelshopId'] = $parcelShopId;
            $shipment['notifications'][] = [
                'subject' => 'parcelshop',
                'channel' => 'EMAIL',
                'value' => $order->getCustomerEmail(),
            ];
        }

        $request['shipments'] = [$shipment];

        $result = null;
        try {
            $result = $apiBuilder->getShipment()->create($request);

            return $result;
        } catch (\Exception $exception) {
            Mage::helper('dpd')->log($exception->getMessage(), Zend_Log::ERR);
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong with the webservice, please check the log files.');
            return false;
        }
    }

    private function getProductCode($shippingMethod, $return = false)
    {
        if ($return === true || $return === 1 || $return === '1') {
            return 'RETURN';
        }

        if ($shippingMethod === 'dpd_e10') {
            return 'E10';
        }

        if ($shippingMethod === 'dpd_e12') {
            return 'E12';
        }

        if ($shippingMethod === 'dpd_e18') {
            return 'E18';
        }

        return 'CL';
    }

    private function getSaturdayDelivery($shippingMethod, $returnLabel = false)
    {
        if ($returnLabel) {
            return false;
        }

        if ($shippingMethod === 'dpd_saturday') {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    protected function _printOptions()
    {
        $paperFormatSource = Mage::getModel('dpd/system_config_source_paperformat')->toArray();
        $paperFormat = $paperFormatSource[Mage::getStoreConfig(self::XML_PATH_DPD_PAPERFORMAT)];

        return [
            'printerLanguage'   => 'PDF',
            'paperFormat'       => $paperFormat,
            'verticalOffset'    => 0,
            'horizontalOffset'  => 0,
        ];
    }
}
