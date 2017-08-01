<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Address\Model\ZoneInterface;
use CoreShop\Component\Core\Model\CountryInterface;
use CoreShop\Component\Core\Model\CurrencyInterface;

final class CountryImport extends AbstractResourcesImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'country';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ZoneImport::class,
            //TaxRuleGroup:class
            StoreImport::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $countryData) {
            /**
             * @var $country CountryInterface
             */
            $country = $this->getFactory()->createNew();
            $country->setName($countryData['name']);
            $country->setIsoCode($countryData['isoCode']);
            $country->setActive($countryData['active'] === "1");
            $country->setAddressFormat($countryData['addressFormat']);

            foreach ($countryData['shops'] as $shop) {
                $country->addStore($this->container->get('coreshop.repository.store')->find($idMap['store'][$shop]));
            }

            if ($countryData['currency']) {
                if ($idMap['currency'][$countryData['currency']]) {
                    $currency = $this->container->get('coreshop.repository.currency')->find($idMap['currency'][$countryData['currency']]);

                    if ($currency instanceof CurrencyInterface) {
                        $country->setCurrency($currency);
                    }
                }
            }

            if ($countryData['zone']) {
                if ($idMap['zone'][$countryData['zone']]) {
                    $zone = $this->container->get('coreshop.repository.zone')->find($idMap['zone'][$countryData['zone']]);

                    if ($zone instanceof ZoneInterface) {
                        $country->setZone($zone);
                    }
                }
            }

            $this->entityManager->persist($country);
            $this->entityManager->flush();

            $idMap[$this->getType()][$countryData['id']] = $country->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.country');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.country');
    }
}