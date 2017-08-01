<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Address\Model\ZoneInterface;

final class ZoneImport extends AbstractResourcesImport
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'zone';
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $zoneData) {
            /**
             * @var $zone ZoneInterface
             */
            $zone = $this->getFactory()->createNew();
            $zone->setName($zoneData['name']);
            $zone->setActive($zoneData['active'] === "1");

            $this->entityManager->persist($zone);
            $this->entityManager->flush();

            $idMap[$this->getType()][$zoneData['id']] = $zone->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.zone');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.zone');
    }
}