<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Core\Model\CarrierInterface;
use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use CoreShop\Component\Shipping\Model\ShippingRuleGroupInterface;

final class CarrierImport extends AbstractResourcesImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'carrier';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ShippingRuleImport::class,
            //TaxRuleGroup:class
            StoreImport::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $carrierData) {
            /**
             * @var $carrier CarrierInterface
             */
            $carrier = $this->getFactory()->createNew();
            $carrier->setName($carrierData['name']);
            $carrier->setLabel($carrierData['label']);
            $carrier->setTrackingUrl($carrierData['trackingUrl']);
            //TODO: $carrier->setTaxRule()
            $carrier->setRangeBehaviour($carrierData['rangeBehaviour']);

            //TODO:
            //foreach ($carrierData['shops'] as $shop) {
            //    $carrier->addStore($this->container->get('coreshop.repository.store')->find($idMap['store'][$shop]));
            //}

            foreach ($carrierData['shippingRuleGroups'] as $shippingRuleGroupData) {
                /**
                 * @var $shippingRuleGroup ShippingRuleGroupInterface
                 */
                $shippingRuleGroup = $this->container->get('coreshop.factory.shipping_rule_group')->createNew();
                $shippingRuleGroup->setPriority($shippingRuleGroupData['priority']);
                $shippingRuleGroup->setShippingRule($this->container->get('coreshop.repository.shipping_rule')->find($idMap['shipping_rule'][$shippingRuleGroupData['shippingRule']]));

                $carrier->addShippingRule($shippingRuleGroup);
            }

            $this->entityManager->persist($carrier);
            $this->entityManager->flush();

            $idMap[$this->getType()][$carrierData['id']] = $carrier->getId();
        }

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.carrier');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.carrier');
    }
}