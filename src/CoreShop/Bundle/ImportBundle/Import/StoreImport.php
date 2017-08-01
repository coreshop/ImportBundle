<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Core\Model\StoreInterface;

final class StoreImport extends AbstractResourcesImport
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'store';
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $storeData) {
            /**
             * @var $store StoreInterface
             */
            $store = $this->getFactory()->createNew();
            $store->setName($storeData['name']);
            $store->setTemplate($storeData['template']);
            $store->setIsDefault($storeData['isDefault'] === "1");

            $this->entityManager->persist($store);
            $this->entityManager->flush();

            $idMap[$this->getType()][$storeData['id']] = $store->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.store');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.store');
    }
}