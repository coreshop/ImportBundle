<?php

namespace CoreShop\Bundle\ImportBundle\Import;

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