<?php

namespace DpdConnect\Sdk\Objects\Response\Generic;

interface ItemStatusInterface extends ResponseStatusInterface
{
    /**
     * @return string
     */
    public function getIdentifier();
}
