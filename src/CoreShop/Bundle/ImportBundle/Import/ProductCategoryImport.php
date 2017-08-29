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

final class ProductCategoryImport extends AbstractPimcoreImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'category';
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
        $ignore = [
            'metaTitle',
            'metaDescription',
            'metaKeywords',
            'friendlyUrl'
        ];

        foreach ($data as $id => $categoryData) {
            $category = $this->importObject($this->getPimcoreClass(), $categoryData, $idMap, $ignore);

            $idMap[$this->getType()][$id] = $category->getId();
            $idMap['object'][$id] = $category->getId();
        }

        return $idMap;
    }

    protected function getPimcoreClass()
    {
        return $this->container->getParameter('coreshop.model.category.class');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.category');
    }
}