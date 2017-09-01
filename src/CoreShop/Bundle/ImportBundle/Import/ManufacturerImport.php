<?php

namespace CoreShop\Bundle\ImportBundle\Import;

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