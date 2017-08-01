<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Address\Model\StateInterface;
use CoreShop\Component\Core\Model\CountryInterface;
use CoreShop\Component\Core\Model\TaxRuleGroupInterface;
use CoreShop\Component\Core\Model\TaxRuleInterface;
use CoreShop\Component\Taxation\Model\TaxRateInterface;

final class TaxRuleGroupImport extends AbstractResourcesImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'tax_rule_group';
    }

    /**
     * {@inheritdoc}
     */
    function getDependencies()
    {
        return [
            CountryImport::class,
            StateImport::class,
            TaxRateImport::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $taxRuleGroupData) {
            /**
             * @var $taxRuleGroup TaxRuleGroupInterface
             */
            $taxRuleGroup = $this->getFactory()->createNew();
            $taxRuleGroup->setName($taxRuleGroupData['name']);

            foreach ($taxRuleGroupData['rules'] as $taxRuleData) {
                /**
                 * @var $taxRule TaxRuleInterface
                 */
                $taxRule = $this->container->get('coreshop.factory.tax_rule')->createNew();
                $taxRule->setBehavior($taxRuleData['behaviour']);

                if ($taxRuleData['countryId']) {
                    if ($idMap['country'][$taxRuleData['countryId']]) {
                        $country = $this->container->get('coreshop.repository.country')->find($idMap['country'][$taxRuleData['countryId']]);

                        if ($country instanceof CountryInterface) {
                            $taxRule->setCountry($country);
                        }
                    }
                }

                if ($taxRuleData['stateId']) {
                    if ($idMap['state'][$taxRuleData['stateId']]) {
                        $state = $this->container->get('coreshop.repository.state')->find($idMap['state'][$taxRuleData['stateId']]);

                        if ($state instanceof StateInterface) {
                            $taxRule->setState($state);
                        }
                    }
                }

                if ($taxRuleData['taxRateId']) {
                    if ($idMap['tax_rate'][$taxRuleData['taxRateId']]) {
                        $taxRate = $this->container->get('coreshop.repository.tax_rate')->find($idMap['tax_rate'][$taxRuleData['taxRateId']]);

                        if ($taxRate instanceof TaxRateInterface) {
                            $taxRule->setTaxRate($taxRate);
                        }
                    }
                }

                $taxRuleGroup->addTaxRule($taxRule);
            }


            $this->entityManager->persist($taxRuleGroup);
            $this->entityManager->flush();

            $idMap[$this->getType()][$taxRuleGroupData['id']] = $taxRuleGroup->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.tax_rule_group');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.tax_rule_group');
    }
}