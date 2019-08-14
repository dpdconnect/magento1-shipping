<?php


/**
 * Class DPD_Connect_Model_Adminhtml_DpdGrid
 */
class DPD_Connect_Model_Adminhtml_Dpdgrid extends Mage_Core_Model_Abstract
{

    /**
     * Generates and completes an order, reference: generateAndCompleteAction.
     *
     * @param $orderId
     * @return $this
     */
    public function generateAndCompleteOrder($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        $shipmentCollection = $order->getShipmentsCollection();
        if ($shipmentCollection->count() >= 1) {
            $dpdused = false;
            foreach ($shipmentCollection as $shipment) {
                foreach ($shipment->getAllTracks() as $tracker) {
                    if (strpos($tracker->getCarrierCode(), 'dpd') !== false) {
                        $labelName = $this->_generateLabelAndReturnLabel($order, $shipment);
                        if (!$labelName) {
                            $message = Mage::helper('dpd')->__("Something went wrong while processing order %s, please check your error logs.", $order->getIncrementId());
                            Mage::getSingleton('core/session')->addError($message);
                            continue;
                        } else {
                            $dpdused = true;
                            $locale = Mage::app()->getStore($order->getStoreId())->getConfig('general/locale/code');
                            $localeCode = explode('_', $locale);
                            $labelNameCode = explode('-', $labelName);
                            $shipment->setDpdLabelPath($labelNameCode[1] . ".pdf");
                            if(substr($labelNameCode[1], 0, 3) == "MPS" || substr($labelNameCode[1], 0, 3) == "B2C"){
	                        	
		                        $shipment->setDpdTrackingUrl('<a target="_blank" href="' . "https://tracking.dpd.de/status/".$locale."/shipment/" . $labelNameCode[1]. '">' . Mage::helper('dpd')->__('Track this shipment') . '</a>');

	                        }else{

		                        $shipment->setDpdTrackingUrl('<a target="_blank" href="' . "https://tracking.dpd.de/status/".$locale."/parcel/" . $labelNameCode[1]. '">' . Mage::helper('dpd')->__('Track this shipment') . '</a>');
	                        }
                            $tracker->setData('number', $labelName);
                            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($shipment)
                                ->addObject($tracker)
                                ->save();
                        }
                    }
                }
            }
            if ($dpdused) {
                $order->addStatusHistoryComment(Mage::helper('dpd')->__('Shipped with DPD generateLabelAndComplete'), true);
                $order->setDpdLabelExists(1);
                $order->save();
                return true;
            } else {
                $message = Mage::helper('dpd')->__("The order with id %s has only none DPD shipments.", $order->getIncrementId());
                Mage::getSingleton('core/session')->addNotice($message);
                return false;
            }
        } elseif (!$order->getDpdLabelExists()) {
            $shipment = $order->prepareShipment();
            $shipment->register();
            $weight = Mage::helper('dpd')->calculateTotalShippingWeight($shipment);
            $shipment->setTotalWeight($weight);
            $label = $this->_generateLabelAndReturnLabel($order, $shipment);
            if (!$label) {
                $message = Mage::helper('dpd')->__("Something went wrong while processing order %s, please check your error logs.", $order->getIncrementId());
                Mage::getSingleton('core/session')->addError($message);
                return false;
            } else {


                $explodeForCarrier = explode('_', $order->getShippingMethod(), 3);
                $locale = Mage::app()->getStore($order->getStoreId())->getConfig('general/locale/code');
                $shipment->setDpdLabelPath($label['identifier'] . ".pdf");

                $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($shipment)
                                ->addObject($shipment->getOrder());


                foreach ($label['trackingNumbers'] AS $trackingNumber) {
                    if (substr($trackingNumber, 0, 3) == "MPS" || substr($trackingNumber, 0, 3) == "B2C") {

                        $shipment->setDpdTrackingUrl('<a target="_blank" href="' . "https://tracking.dpd.de/status/" . $locale . "/shipment/" . $trackingNumber . '">' . Mage::helper('dpd')->__('Track this shipment') . '</a>');

                    } else {

                        $shipment->setDpdTrackingUrl('<a target="_blank" href="' . "https://tracking.dpd.de/status/" . $locale . "/parcel/" . $trackingNumber . '">' . Mage::helper('dpd')->__('Track this shipment') . '</a>');
                    }

                    $order->setIsInProcess(true);
                    $order->addStatusHistoryComment(Mage::helper('dpd')->__('Shipped with DPD generateLabelAndComplete'), true);
                    $order->setDpdLabelExists(1);
                    $tracker = Mage::getModel('sales/order_shipment_track')
                        ->setShipment($shipment)
                        ->setData('title', 'DPD')
                        ->setData('number', $trackingNumber)
                        ->setData('carrier_code', $explodeForCarrier[0])
                        ->setData('order_id', $shipment->getData('order_id'));

                    $transactionSave->addObject($tracker);
                }

                $transactionSave
                    ->addObject($shipment)
                    ->save();

                return true;
            }
        } else {
            $message = Mage::helper('dpd')->__("The order with id %s is not ready to be shipped or has already been shipped.", $order->getIncrementId());
            Mage::getSingleton('core/session')->addNotice($message);
            return false;
        }
        return $this;
    }

    /**
     * Generates a shipment label and saves it on the harddisk.
     *
     * @param $order
     * @param $shipment
     * @return mixed
     */
    protected function _generateLabelAndReturnLabel($order, $shipment)
    {
        $parcelshop = false;
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if (strpos($order->getShippingMethod(), 'parcelshop') !== false) {
            $parcelshop = true;
        }
        if ($parcelshop) {
            $recipient = array(
                'name1'             => $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
                'name2'             => $billingAddress->getCompany(),
                'street'            => $billingAddress->getStreet(1),
                'housenumber'       => $billingAddress->getStreet(2),
                'postalcode'        => $billingAddress->getPostcode(),
                'city'              => $billingAddress->getCity(),
                'country'           => $billingAddress->getCountry(),
                'commercialAddress' => (!$billingAddress->getCompany() ? false : true),
            );
        }
        else{

            $recipient = array(
                'name1'             => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
                'name2'             => $shippingAddress->getCompany(),
                'street'            => $shippingAddress->getStreet(1),
                'housenumber'       => $shippingAddress->getStreet(2),
                'postalcode'        => $shippingAddress->getPostcode(),
                'city'              => $shippingAddress->getCity(),
                'country'           => $shippingAddress->getCountry(),
                'commercialAddress' => (!$shippingAddress->getCompany() ? false : true),
            );
        }
        $labelWebserviceCallback = Mage::getSingleton('dpd/webservice')->getShippingLabel($recipient, $order, $shipment, $parcelshop);

        if ($labelWebserviceCallback) {

            try {


                $labels = $labelWebserviceCallback->getContent()['labelResponses'];
                $labelContent = [];
                $labelTrackingNumbers = [];
                $labelIdentifier = null;
                foreach ($labels as $label) {

                    if (!empty($label['label'])) {

                        $labelContent[] = base64_decode($label['label']);
                    }
                    $labelTrackingNumbers[] = implode('', $label['parcelNumbers']);

                    if (is_null($labelIdentifier)) {
                        $labelIdentifier = $label['shipmentIdentifier'];
                    }
                }

                if (count($labelContent) > 0 && count($labelTrackingNumbers) > 0) {
                    $parcelPdf = $this->combineLabelsPdf($labelContent);
                    $generatedPdf = Mage::helper('dpd')->generatePdfAndSave($parcelPdf->render(), 'orderlabels', $labelIdentifier);

                    return [
                        'identifier' => $labelIdentifier,
                        'trackingNumbers' => $labelTrackingNumbers,
                        'shipmentPdf' => $parcelPdf,
                        'pdfLabels' => $labelContent,
                        'pdfLabelsMerged' => $parcelPdf,
                        'pdfUrl' => $generatedPdf
                    ];
                }

            } catch (\Exception $exception) {
                Mage::helper('dpd')->log($exception->getMessage(), Zend_Log::ERR);
                Mage::getSingleton('adminhtml/session')->addError('Something went wrong with the webservice, please check the log files.');
                return false;
            }

        }

        return false;
    }

    /**
     * Combine array of labels as instance PDF
     *
     * @param array $labelsContent
     * @return \Zend_Pdf
     */
    public function combineLabelsPdf(array $labelsContent)
    {
        $outputPdf = new \Zend_Pdf();
        foreach ($labelsContent as $content) {
            if (stripos($content, '%PDF-') !== false) {
                $pdfLabel = \Zend_Pdf::parse($content);
                foreach ($pdfLabel->pages as $page) {
                    $outputPdf->pages[] = clone $page;
                }
            } else {
                $page = $this->createPdfPageFromImageString($content);
                if ($page) {
                    $outputPdf->pages[] = $page;
                }
            }
        }
        return $outputPdf;
    }

    /**
     * Create \Zend_Pdf_Page instance with image from $imageString. Supports JPEG, PNG, GIF, WBMP, and GD2 formats.
     *
     * @param string $imageString
     * @return \Zend_Pdf_Page|false
     */
    public function createPdfPageFromImageString($imageString)
    {
        $image = @imagecreatefromstring($imageString);
        if (!$image) {
            return false;
        }

        $xSize = imagesx($image);
        $ySize = imagesy($image);
        $page = new \Zend_Pdf_Page($xSize, $ySize);

        imageinterlace($image, 0);
        $tmpFileName = tempnam("/tmp", "FOO");
        imagepng($image, $tmpFileName);
        $pdfImage = \Zend_Pdf_Image::imageWithPath($tmpFileName);
        $page->drawImage($pdfImage, 0, 0, $xSize, $ySize);
        unlink($tmpFileName);
        if (is_resource($image)) {
            imagedestroy($image);
        }
        return $page;
    }

    /**
     * Processes the undownloadable labels. (set mark and zip)
     *
     * @param $orderIds
     * @return bool|string
     */
    public function processUndownloadedLabels($orderIds)
    {
        $labelPdfArray = array();
        $i = 0;
        $err = false;
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            $exported = false;
            if (!$order->getDpdLabelExported()) {
                $shippingCollection = Mage::getResourceModel('sales/order_shipment_collection')
                    ->setOrderFilter($order)
                    ->load();
                if (count($shippingCollection)) {
                    foreach ($shippingCollection as $shipment) {
                        if ($shipment->getDpdLabelPath() != "" && file_exists(Mage::getBaseDir('media') . "/dpd/orderlabels/" . $shipment->getDpdLabelPath()) && $shipment->getDpdLabelPath() != ".pdf") {
                            $labelPdfArray[] = Mage::getBaseDir('media') . "/dpd/orderlabels/" . $shipment->getDpdLabelPath();
                            $exported = true;
                        }
                    }
                    if ($exported) {
                        $order->setDpdLabelExported(1)->save();
                    }
                }
            } else {
                $i++;
            }
        }
        if (!count($labelPdfArray)) {
            return false;
        }
        if ($i > 0) {
            $message = Mage::helper('dpd')->__('%s orders already had downloaded labels.', $i);
            Mage::getSingleton('core/session')->addNotice($message);
        } else {
            $message = Mage::helper('dpd')->__('All labels have been downloaded.');
            Mage::getSingleton('core/session')->addSuccess($message);
        }
        return $this->_zipLabelPdfArray($labelPdfArray, Mage::getBaseDir('media') . "/dpd/orderlabels/undownloaded.zip", true);
    }

    /**
     * Zips the labels.
     *
     * @param array $files
     * @param string $destination
     * @param bool $overwrite
     * @return bool|string
     */
    protected function _zipLabelPdfArray($files = array(), $destination = '', $overwrite = false)
    {
        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        $valid_files = array();
        if (is_array($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        if (count($valid_files)) {
            $zip = new ZipArchive();
            if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE | ZIPARCHIVE::CREATE : ZIPARCHIVE::CREATE) !== true) {
                return false;
            }
            foreach ($valid_files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            return $destination;
        } else {
            return false;
        }
    }

}
