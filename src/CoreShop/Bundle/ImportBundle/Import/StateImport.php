<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Address\Model\StateInterface;
use CoreShop\Component\Core\Model\CountryInterface;

final class StateImport extends AbstractResourcesImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'state';
    }

    /**
     * {@inheritdoc}
     */
    function getDependencies()
    {
        return [
            CountryImport::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $stateData) {
            /**
             * @var $state StateInterface
             */
            $state = $this->getFactory()->createNew();
            $state->setName($stateData['name']);
            $state->setActive($stateData['active'] === "1");
            $state->setIsoCode($stateData['isoCode']);

            if ($stateData['country']) {
                if ($idMap['country'][$stateData['country']]) {
                    $country = $this->container->get('coreshop.repository.country')->find($idMap['country'][$stateData['country']]);

                    if ($country instanceof CountryInterface) {
                        $state->setCountry($country);
                    }
                }
            }


            $this->entityManager->persist($state);
            $this->entityManager->flush();

            $idMap[$this->getType()][$stateData['id']] = $state->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.state');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.state');
    }
}