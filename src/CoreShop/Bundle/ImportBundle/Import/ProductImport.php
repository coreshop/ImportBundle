<?php

namespace CoreShop\Bundle\ImportBundle\Import;


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