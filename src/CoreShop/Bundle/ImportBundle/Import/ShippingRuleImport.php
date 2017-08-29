<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Rule\Model\Action;
use CoreShop\Component\Rule\Model\Condition;
use CoreShop\Component\Shipping\Model\ShippingRuleInterface;
use Symfony\Component\Intl\Exception\NotImplementedException;

final class ShippingRuleImport extends AbstractResourcesImport implements DependentImporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'shipping_rule';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            ProductImport::class,
            ProductCategoryImport::class,
            CountryImport::class,
            CurrencyImport::class,
            ZoneImport::class,
            CountryImport::class,
            //CustomerGroupImport::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        $objectMap = [];

        foreach ($data as $ruleData) {
            /**
             * @var $rule ShippingRuleInterface
             */
            $rule = $this->getFactory()->createNew();

            $rule->setName($ruleData['name']);

            $this->entityManager->persist($rule);
            $this->entityManager->flush();

            $idMap['shipping_rule'][$ruleData['id']] = $rule->getId();
            $objectMap[$ruleData['id']] = $rule;
        }

        foreach ($data as $ruleData) {
            /**
             * @var $rule ShippingRuleInterface
             */
            $rule = $objectMap[$ruleData['id']];

            foreach ($ruleData['actions'] as $actionData) {
                $action = new Action();

                switch ($actionData['type']) {
                    case 'fixedPrice':
                        $action->setType('price');
                        $action->setConfiguration(['price' => $actionData['configuration']['fixedPrice']]);
                        break;

                    case 'shippingRule':
                        $action->setType('shippingRule');
                        $action->setConfiguration(['shippingRule' => $idMap['shipping_rule'][$actionData['configuration']['shippingRule']]]);
                        break;

                    default:
                        $action->setType($actionData['type']);
                        $action->setConfiguration($actionData['configuration']);
                        break;
                }

                $rule->addAction($action);
            }

            foreach ($ruleData['conditions'] as $conditionData) {
                $condition = new Condition();

                switch ($conditionData['type']) {
                    case 'categories':
                        $condition->setType('categories');
                        $categories = [];

                        foreach ($conditionData['configuration']['categories'] as $category) {
                            $categories[] = $idMap['category'][$category];
                        }

                        $condition->setConfiguration(['categories' => $categories]);
                        break;

                    case 'countries':
                        $condition->setType('countries');
                        $countries = [];

                        foreach ($conditionData['configuration']['countries'] as $country) {
                            $countries[] = $idMap['country'][$country];
                        }

                        $condition->setConfiguration(['countries' => $countries]);
                        break;

                    case 'zones':
                        $condition->setType('zones');
                        $zones = [];

                        foreach ($conditionData['configuration']['zones'] as $zone) {
                            $zones[] = $idMap['country'][$zone];
                        }

                        $condition->setConfiguration(['zones' => $zones]);
                        break;

                    case 'currencies':
                        $condition->setType('currencies');
                        $currencies = [];

                        foreach ($conditionData['configuration']['currencies'] as $currency) {
                            $currencies[] = $idMap['currency'][$currency];
                        }

                        $condition->setConfiguration(['currencies' => $currencies]);
                        break;

                    case 'customerGroups':
                        $condition->setType('customerGroups');
                        $customerGroups = [];

                        foreach ($conditionData['configuration']['customerGroups'] as $customerGroup) {
                            $customerGroups[] = $idMap['customer_group'][$customerGroup];
                        }

                        $condition->setConfiguration(['customerGroups' => $customerGroups]);
                        break;

                    case 'products':
                        $condition->setType('products');
                        $products = [];

                        foreach ($conditionData['configuration']['products'] as $product) {
                            $products[] = $idMap['product'][$product];
                        }

                        $condition->setConfiguration(['products' => $products]);
                        break;

                    case 'shippingRule':
                        $condition->setType('shippingRule');
                        $condition->setConfiguration(['shippingRule' => $idMap['shipping_rule'][$conditionData['configuration']['shippingRule']]]);
                        break;

                    case 'conditions':
                        throw new NotImplementedException('Nested Conditions are currently not supported');
                        break;

                    default:
                        $condition->setType($conditionData['type']);
                        $condition->setConfiguration($conditionData['configuration']);
                        break;
                }

                $rule->addCondition($condition);
            }
        }

        $this->entityManager->flush();

        return $idMap;
    }

    protected function getRepository()
    {
        return $this->container->get('coreshop.repository.shipping_rule');
    }

    protected function getFactory()
    {
        return $this->container->get('coreshop.factory.shipping_rule');
    }
}