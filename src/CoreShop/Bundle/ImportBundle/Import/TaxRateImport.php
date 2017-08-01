<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Taxation\Model\TaxRateInterface;
use Pimcore\Tool;

final class TaxRateImport extends AbstractResourcesImport
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'tax_rate';
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $taxRateData) {
            /**
             * @var $taxRate TaxRateInterface
             */
            $taxRate = $this->getFactory()->createNew();
            $taxRate->setRate($taxRateData['rate']);
            $taxRate->setActive($taxRateData['active'] === "1");

            foreach (Tool::getValidLanguages() as $lang) {
                if (array_key_exists($lang, $taxRateData['name'])) {
                    $taxRate->setName($taxRateData['name'][$lang], $lang);
                }
            }

            $this->entityManager->persist($taxRate);
            $this->entityManager->flush();

            $idMap[$this->getType()][$taxRateData['id']] = $taxRate->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.tax_rate');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.tax_rate');
    }
}