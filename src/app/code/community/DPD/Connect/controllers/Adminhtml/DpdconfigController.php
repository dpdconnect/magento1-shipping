<?php

use DpdConnect\Sdk\ClientBuilder;

/**
 * Class DPD_Connect_Adminhtml_DpdconfigController
 */
class DPD_Connect_Adminhtml_DpdconfigController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Export shipping table rates in csv format
     *
     */
    public function exportDpdClassicTableratesAction()
    {
        $fileName = 'dpdclassic_tablerates.csv';
        /** @var $gridBlock Mage_Adminhtml_Block_Connect_Carrier_Tablerate_Grid */
        $gridBlock = $this->getLayout()->createBlock('dpd/adminhtml_shipping_carrier_dpdclassic_tablerate_grid');
        $website = Mage::app()->getWebsite($this->getRequest()->getParam('website'));
        if ($this->getRequest()->getParam('conditionName')) {
            $conditionName = $this->getRequest()->getParam('conditionName');
        } else {
            $conditionName = $website->getConfig('carriers/dpdclassic/condition_name');
        }
        $gridBlock->setWebsiteId($website->getId())->setConditionName($conditionName);
        $content = $gridBlock->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    /**
     * Export shipping table rates in csv format
     *
     */
    public function exportDpdParcelshopsTableratesAction()
    {
        $fileName = 'dpdparcelshops_tablerates.csv';
        /** @var $gridBlock Mage_Adminhtml_Block_Connect_Carrier_Tablerate_Grid */
        $gridBlock = $this->getLayout()->createBlock('dpd/adminhtml_shipping_carrier_dpdparcelshops_tablerate_grid');
        $website = Mage::app()->getWebsite($this->getRequest()->getParam('website'));
        if ($this->getRequest()->getParam('conditionName')) {
            $conditionName = $this->getRequest()->getParam('conditionName');
        } else {
            $conditionName = $website->getConfig('carriers/dpdparcelshops/condition_name');
        }
        $gridBlock->setWebsiteId($website->getId())->setConditionName($conditionName);
        $content = $gridBlock->getCsvFile();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed( 'sales/dpd' );
    }
}
