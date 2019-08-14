<?php

namespace DpdConnect\Sdk\Test;

use DpdConnect\Sdk\Objects\ObjectFactory;
use DpdConnect\Sdk\Objects\Parcelshop;
use DpdConnect\Sdk\Objects\ShipmentLabel;
use PHPUnit\Framework\TestCase;

class ParcelshopTest extends TestCase
{
    /**
     * @return Parcelshop
     */
    private function getObject()
    {
        return ObjectFactory::create(Parcelshop::class, [
            'company'   => 'dpd',
            'street'    => 'dpd_street'
        ]);
    }

    /**
     * @return ShipmentLabel
     */
    public function testGenerateParcelshop()
    {
        $label = $this->getObject();

        $this->assertNotNull($label);
        return $label;
    }

    /**
     * @depends testGenerateParcelshop
     * @param Parcelshop $parcelshop
     */
    public function testGetters($parcelshop)
    {
        $this->assertEquals($parcelshop->getCompany(), 'dpd');
        $this->assertEquals($parcelshop->getStreet(), 'dpd_street');
    }
}
