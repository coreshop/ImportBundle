<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Core\Model\CarrierInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Model\AbstractObject;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use CoreShop\Component\Shipping\Model\ShippingRuleGroupInterface;
use Pimcore\Model\Object\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Object\Service;
use Pimcore\Tool;

final class ManufacturerImport extends AbstractPimcoreImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'manufacturer';
    }

    /**
     * {@inheritdoc}
     */
    function getDependencies()
    {
        return [
            AssetImport::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $id => $manufacturerData) {
            $manufacturer = $this->importObject($this->getPimcoreClass(), $manufacturerData, $idMap);

            $idMap[$this->getType()][$id] = $manufacturer->getId();
            $idMap['object'][$id] = $manufacturer->getId();
        }

        return $idMap;
    }

    protected function getPimcoreClass()
    {
        return $this->container->getParameter('coreshop.model.manufacturer.class');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.manufacturer');
    }
}