<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Core\Model\CurrencyInterface;

final class CurrencyImport extends AbstractResourcesImport
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'currency';
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $currencyData) {
            /**
             * @var $currency CurrencyInterface
             */
            $currency = $this->getFactory()->createNew();
            $currency->setName($currencyData['name']);
            $currency->setIsoCode($currencyData['isoCode']);
            $currency->setNumericIsoCode($currencyData['numericIsoCode']);
            $currency->setSymbol($currencyData['symbol']);

            $this->entityManager->persist($currency);
            $this->entityManager->flush();

            $idMap[$this->getType()][$currencyData['id']] = $currency->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.currency');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.currency');
    }
}