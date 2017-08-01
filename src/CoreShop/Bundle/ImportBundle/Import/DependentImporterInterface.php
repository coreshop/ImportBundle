<?php

namespace CoreShop\Bundle\ImportBundle\Import;

/**
 * This interface is copied from Doctrine Fixtures
 */
interface DependentImporterInterface
{   
    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    function getDependencies();
}
