<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Core\Model\CarrierInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Model\AbstractObject;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use CoreShop\Component\Shipping\Model\ShippingRuleGroupInterface;
use Pimcore\Logger;
use Pimcore\Model\Object\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Object\Service;
use Pimcore\Tool;

final class ProductImport extends AbstractPimcoreImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            AssetImport::class,
            ProductCategoryImport::class,
            ManufacturerImport::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        $ignore = [
            'metaTitle',
            'metaDescription',
            'metaKeywords',
            'friendlyUrl',
            'packagingTypes',
            'classificationStore',
            'specificPriceRules'
        ];

        foreach ($data as $id => $productData) {
            $product = $this->importObject($this->getPimcoreClass(), $productData, $idMap, $ignore);
            $idMap[$this->getType()][$id] = $product->getId();
            $idMap['object'][$id] = $product->getId();
        }

        return $idMap;
    }

    protected function getPimcoreClass()
    {
        return $this->container->getParameter('coreshop.model.product.class');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.category');
    }
}