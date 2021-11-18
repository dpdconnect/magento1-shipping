<?php


use DpdConnect\Sdk\Client;
use DpdConnect\Sdk\ClientBuilder;
use DpdConnect\Sdk\Common\ResourceClient;
use DpdConnect\Sdk\Exceptions\DpdException;
use DpdConnect\Sdk\Objects\MetaData;
use DpdConnect\Sdk\Objects\ObjectFactory;
use DpdConnect\Sdk\Resources\Token;
use DpdConnect\Shipping\Helper\Constants;
use DpdConnect\Shipping\Helper\DpdSettings;

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
     * XML path to configuration setting for customs terms.
     */
    CONST XML_PATH_DPD_CUSTOMS_TERMS = 'shipping/dpdclassic/customs_terms';

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
     * @return Client
     */
    protected function _apiBuilder()
    {
        $username = Mage::getStoreConfig(self::XML_PATH_DPD_API_USERNAME);
        $password = Mage::helper('core')->decrypt(Mage::getStoreConfig(self::XML_PATH_DPD_API_PASSWORD));
        $hostname = $this->getClientUrl();
        $cache = Mage::app()->getCache();

        try {
            $clientBuilder = new ClientBuilder($hostname, ObjectFactory::create(MetaData::class, [
                'webshopType'       => 'Magento',
                'webshopVersion'    => Mage::getVersion(),
                'pluginVersion'     => '2.0'
            ]));

            $client = $clientBuilder->buildAuthenticatedByPassword(
                $username, $password
            );

            $client->getAuthentication()->setJwtToken(
                $cache->load('dpdconnect_jwt_token') ?: null
            );

            $client->getAuthentication()->setTokenUpdateCallback(function ($jwtToken) use ($cache, $client) {
                $cache->save($jwtToken, 'dpdconnect_jwt_token');
                $client->getAuthentication()->setJwtToken($jwtToken);
            });

            return $client;
        } catch(\Exception $exception) {
            Mage::helper('dpd')->log($exception->getMessage(), Zend_Log::ERR);
            Mage::getSingleton('adminhtml/session')->addError('Something went wrong with the webservice, please check the log files.');
            return false;
        }
    }

    /**
     * @return Token
     */
    public function getToken() {
        $client = $this->_apiBuilder();
        return $client->getToken();
    }

    /**
     * Get parcelshops from webservice findParcelShopsByGeoData.
     *
     * @param $longitude
     * @param $latitude
     * @param $country
     * @return mixed
     */
    public function getParcelShops($longitude, $latitude, $country = 'nl')
    {
        $apiBuilder = $this->_apiBuilder();

        $parameters = array(
            'longitude'                 => $longitude,
            'latitude'                  => $latitude,
            'limit'                     => 10,
            'countryIso'                => $country,
            'consigneePickupAllowed'    => 'true'
        );

        $result = $apiBuilder->getParcelshop()->getList($parameters);

        return $result;
    }

    /**
     * @return array
     * @throws DpdException
     */
    public function getAvailableProducts()
    {
        $apiBuilder = $this->_apiBuilder();

        return $apiBuilder->getProduct()->getList();
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
                    'returns'            => true
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

        } catch(Exception $exception) {
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
     * @param Mage_Sales_Model_Order $order
     * @param $shipment
     * @param false $parcelshop
     * @return ResourceClient|false|int
     */
    public function getShippingLabel($recipient, Mage_Sales_Model_Order $order, $shipment, $parcelshop = false)
    {
        $apiBuilder = $this->_apiBuilder();
        $sender = $this->_getSenderInformation();
        $recipient['postalcode'] = str_replace(' ', '', $recipient['postalcode']);
        $shippingMethod = $order->getShippingMethod();

        $i = 0;
        $lines = [];
        $orderitems = $order->getAllVisibleItems();
        foreach ($orderitems as $item) {
            $product = $item->getProduct();
            echo $product->getId();
            $i++;
            $lines[] = [
                'description'           => substr($product->getName(), 0, 35),
                'harmonizedSystemCode'  => (!empty($product->getHsCode()) ? substr($product->getHsCode(), 0, 8) : (!empty($this->_getHsCodeDefault()) ? substr($this->_getHsCodeDefault(), 0, 8)     : "")),
                'originCountry'         => (isset($item['item_origin_country']) ? $item['item_origin_country'] : "NL"),
                'quantity'              => (int) $item->getQtyOrdered(),
                'grossWeight'           => (int) round($item->getWeight()) * 100,
                'totalAmount'           => (float) ($item->getPriceInclTax()),
                'customsLineNumber'     => $i,
            ];
        }
        
        $request = array(
            'printOptions'  => $this->_printOptions(),
            'createLabel'   => true,
            'shipments'     => []
        );

        $productCode = $this->getProductCode($order, $shipment);

        $homeDelivery = true;
        if(($parcelshop || $productCode !== 'CL') && !$this->isFreshOrFreeze($shipment)) {
            $homeDelivery = false;
        }

        $shipmentArray = [
            'orderId'       => $order->getIncrementId(),
            'sendingDepot'  => $this->_getSendingDepot(),
            'sender'        => $sender,
            'receiver'      => $recipient,
            'product'       => array(
                'productCode' => $productCode,
                'saturdayDelivery' => $this->getSaturdayDelivery($shippingMethod),
                'homeDelivery' => $homeDelivery
            ),
            "parcels" => [
                $this->addParcel($order, $shipment)
            ],
            'customs' => [
                'terms'               => Mage::getStoreConfig(self::XML_PATH_DPD_CUSTOMS_TERMS),
                'totalAmount'         => (float) $order->getBaseGrandTotal(),
                'totalCurrency'       => $order->getOrderCurrency()->getCurrencyCode(),
                'consignee'           => $sender,
                'consignor'           => $recipient,
                'customsLines'        => $lines
            ]
        ];

        if ($parcelshop && !$this->isFreshOrFreeze($shipment)) {
            $parcelShopId = $order->getDpdParcelshopId();
            $shipmentArray['product']['parcelshopId'] = $parcelShopId;
            $shipmentArray['notifications'][] = [
                'subject' => 'parcelshop',
                'channel' => 'EMAIL',
                'value' => $order->getCustomerEmail(),
            ];
        } else {
            $shipmentArray['notifications'][] = [
                'subject' => 'predict',
                'channel' => 'EMAIL',
                'value' => $order->getCustomerEmail(),
            ];
        }

        $request['shipments'] = [$shipmentArray];

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

    /**
     * @param Mage_Sales_Model_Order $order
     * @param null $shipment
     * @return array
     */
    public function addParcel(Mage_Sales_Model_Order $order, $shipment = null)
    {
            $parcel = [
                'customerReferences' => array(
                    $order->getIncrementId(),
                    !$this->isFreshOrFreeze($shipment) && $order->getDpdParcelshopId() ? $order->getDpdParcelshopId(): '',
                    $shipment->getEntityId() ? $shipment->getEntityId() : ''
                ),
                'weight' =>  (int) round($shipment->getTotalWeight(),0) * 100
            ];

            if (null !== $shipment && $shipment->hasData(Mage::helper('dpd/constants')->SHIPMENT_EXTRA_DATA)) {
                $extradata = $shipment->getData(Mage::helper('dpd/constants')->SHIPMENT_EXTRA_DATA);
                if (isset($extradata['expirationDate']) && isset($extradata['description'])) {
                    $parcel['goodsExpirationDate'] = intval(str_replace('-', '', $extradata['expirationDate']));
                    $parcel['goodsDescription'] = $extradata['description'];
                }
            }

            return $parcel;
    }

    private function getProductCode($order, $shipment, $return = false)
    {
        if ($return === true || $return === 1 || $return === '1') {
            return 'RETURN';
        }

        // Fetch the code from the shipment, if any, else default to the order code
        if ($shipment && $shipment->hasData(Mage::helper('dpd/constants')->SHIPMENT_EXTRA_DATA)) {
            if($code = $shipment->getData(Mage::helper('dpd/constants')->SHIPMENT_EXTRA_DATA)['code']) {
                return $code;
            }
        }

        if ($order->getShippingMethod() === 'dpd_e10') {
            return 'E10';
        }

        if ($order->getShippingMethod() === 'dpd_e12') {
            return 'E12';
        }

        if ($order->getShippingMethod() === 'dpd_e18') {
            return 'E18';
        }

        return 'CL';
    }

    private function isFreshOrFreeze($shipment) {
        // Fetch the code from the shipment, if any, else default to the order code
        // Because only Fresh/Freeze is accepted in Magento1 we do not have to check the type
        if ($shipment && $shipment->hasData(Mage::helper('dpd/constants')->SHIPMENT_EXTRA_DATA)) {
            if($shipment->getData(Mage::helper('dpd/constants')->SHIPMENT_EXTRA_DATA)['code']) {
                return true;
            }
        }

        return false;
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

    public function getClientUrl() {
        return  Mage::getStoreConfig(self::XML_PATH_DPD_API_HOSTNAME) ? Mage::getStoreConfig(self::XML_PATH_DPD_API_HOSTNAME) : Client::ENDPOINT;
    }
}
