<?php

/**
 * Class DPD_Connect_Model_Returnlabels
 */
class DPD_Connect_Model_Returnlabels extends Mage_Core_Model_Abstract
{
    /**
     * Initialise the model.
     */
    protected function _construct()
    {
        $this->_init("dpd/returnlabels");
    }

    /**
     * Gets label from webservice, saves it and returns the saved id.
     *
     * @param $orderId
     * @return int
     */
    public function generateLabelAndSave($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        if (strpos($order->getShippingMethod(), 'parcelshop') !== false) {
            $parcelshop = true;
        }
        if ($parcelshop) {
            $billingAddress = $order->getBillingAddress();
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
        } else {
            $shippingAddress = $order->getShippingAddress();
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

        $returnlabel = Mage::getSingleton('dpd/webservice')->getReturnLabel($recipient, $order->getIncrementId());

        if (!$returnlabel) {
            return false;
        }

        $labelResponses = $returnlabel->getContent()['labelResponses'];
        $firstLabel = null;
        foreach ($labelResponses as $labelResponse) {
            $parcelNumbers = implode(',', $labelResponse['parcelNumbers']);
            $parcelNumber = $parcelNumbers;

            Mage::helper('dpd')->generatePdfAndSave(base64_decode($labelResponse['label']), 'returnlabel', $labelResponse['shipmentIdentifier'] . "-" . $parcelNumber);

            $returnLabelObject = new DPD_Connect_Model_Returnlabels;
            $returnLabelObject
                ->setLabelNumber($parcelNumber)
                ->setLabelPdfUrl($labelResponse['shipmentIdentifier'] . "-" . $parcelNumber . ".pdf")
                ->setLabelInstructionsUrl($labelResponse['shipmentIdentifier'] . "-" . $parcelNumber .  "-instructions.pdf")
                ->setOrderId($orderId)
                ->save();

            if (is_null($firstLabel)) {
                $firstLabel = $returnLabelObject;
            }
        }

        if (!is_null($firstLabel)) {
            return $firstLabel->getId();
        }

        return false;
    }

    /**
     * Sends email with custom dpd template, attaches both instruction and label pdf.
     *
     * @param $order
     * @param $returnId
     * @return $this
     */
    public function sendEmail($order, $returnId)
    {
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $returnLabel = Mage::getModel('dpd/returnlabels')->load($returnId);
        $billingAddress = $order->getBillingAddress();
        $attachments = array($returnLabel->getLabelPdfUrl(), $returnLabel->getLabelInstructionsUrl());
        $templateVars = array('returnlabel' => $returnLabel, 'order' => $order, 'store' => Mage::app()->getStore($order->getStoreId()));
        $transactionalEmail = Mage::getModel('core/email_template')
            ->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStoreId()));
        foreach ($attachments as $pdf_attachment) {
            if (!empty($pdf_attachment) && file_exists(Mage::getBaseDir('media') . "/dpd/returnlabel/" . $pdf_attachment)) {
                $transactionalEmail
                    ->getMail()
                    ->createAttachment(
                        file_get_contents(Mage::getBaseDir('media') . "/dpd/returnlabel/" . $pdf_attachment),
                        Zend_Mime::TYPE_OCTETSTREAM,
                        Zend_Mime::DISPOSITION_ATTACHMENT,
                        Zend_Mime::ENCODING_BASE64,
                        basename($pdf_attachment)
                    );
            }
        }
        $transactionalEmail
            ->sendTransactional('dpd_returnlabel_email_template',
                array('name' => Mage::getStoreConfig('trans_email/ident_support/name'),
                    'email' => Mage::getStoreConfig('trans_email/ident_support/email')),
                $billingAddress->getEmail(),
                $billingAddress->getFirstname() . " " . $billingAddress->getLastname(),
                $templateVars);
        $translate->setTranslateInline(true);
        return $this;
    }

    /**
     * Deletes attachments and pdf when returnlabel call failed, this is to avoid empty/corrupt data.
     *
     * @param $returnId
     */
    public function deleteEntryAndAttachments($returnId)
    {
        $returnLabel = Mage::getModel('dpd/returnlabels')->load($returnId);
        $attachments = array($returnLabel->getLabelPdfUrl(), $returnLabel->getLabelInstructionsUrl());
        foreach ($attachments as $pdf_attachment) {
            $file = Mage::getBaseDir('media') . "/dpd/returnlabels/" . $pdf_attachment;
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $returnLabel->delete();
    }

    /**
     * Generates and saves the instructions pdf with shoplogo and returnid.
     *
     * @param $orderId
     * @param $returnId
     * @return string
     */
    public function generateInstructionsPdf($orderId, $returnId)
    {
        $returnlabel = Mage::getModel('dpd/returnlabels')->load($returnId);
        $pdf = Zend_Pdf::load(Mage::getBaseDir('skin') . DS . 'adminhtml' . DS . 'default' . DS . 'default' . DS . 'dpd' . DS . 'returnlabel' . DS . 'instructions.pdf');
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $page = $pdf->pages[0];
        $page->setFont($font, 15);
        if (Mage::getStoreConfig('design/email/logo') && strpos(Mage::getStoreConfig('design/email/logo'), '.gif') === false && Mage::getVersion() >= "1.7") {
            $uploadDir = Mage_Adminhtml_Model_System_Config_Backend_Email_Logo::UPLOAD_DIR;
            $fullFileName = Mage::getBaseDir('media') . DS . $uploadDir . DS . Mage::getStoreConfig('design/email/logo');
            $image = Zend_Pdf_Image::imageWithPath($fullFileName);
            $imgWidthPts = $image->getPixelWidth() * 72 / 96;
            $imgHeightPts = $image->getPixelHeight() * 72 / 96;
            $x1 = 50;
            $y1 = 687;
            $page->drawImage($image, $x1, $y1, $x1 + $imgWidthPts, $y1 + $imgHeightPts);
        } elseif (Mage::getVersion() < "1.7") {
            try {
                $fullFileName = Mage::getBaseDir('skin') . DS . 'frontend' . DS . 'default' . DS . 'default' . DS . Mage::getStoreConfig('design/header/logo_src');
                $image = Zend_Pdf_Image::imageWithPath($fullFileName);
                $imgWidthPts = $image->getPixelWidth() * 72 / 96;
                $imgHeightPts = $image->getPixelHeight() * 72 / 96;
                $x1 = 50;
                $y1 = 687;
                $page->drawImage($image, $x1, $y1, $x1 + $imgWidthPts, $y1 + $imgHeightPts);
            } catch (Exception $e) {
                Mage::helper('dpd')->log('Instructions PDF: No logo found or incorrect file format', Zend_Log::INFO);
            }
        } else {
            Mage::helper('dpd')->log('Instructions PDF: No logo found or incorrect file format', Zend_Log::INFO);
        }
        $page->drawText(implode(' ', str_split($returnlabel->getLabelNumber(), 4)), '321', '215');
        $order = Mage::getResourceModel('sales/order_collection')->addAttributeToSelect('increment_id')->addAttributeToFilter('entity_id', array('eq' => $orderId))->getFirstItem();
        Mage::helper('dpd')->generatePdfAndSave($pdf->render(), 'returnlabel', $order->getIncrementId() . '-' . $returnlabel->getLabelNumber() . "-instructions");
    }
}
