<?php

namespace CoreShop\Bundle\ImportBundle\Import;

/**
 * This interface is copied from Doctrine Fixtures
 */
interface OrderedImporterInterface
{   
    /**
     * Get the order of this fixture
     * 
     * @return integer
     */  
    public function getOrder();
}
