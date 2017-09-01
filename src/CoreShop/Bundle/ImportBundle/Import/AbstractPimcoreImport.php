<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Model\AbstractObject;
use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tool;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractPimcoreImport implements ImportInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        $class = $this->getPimcoreClass();

        foreach ($class::getList()->getObjects() as $resource) {
            $resource->delete();
        }
    }

    /**
     * @param $className
     * @param $arrayData
     * @param array $idMap
     * @param array $ignore
     * @return mixed
     * @throws \Exception
     */
    public function importObject($className, $arrayData, $idMap = [], $ignore = [])
    {
        $data = json_decode(json_encode($arrayData));
        $elements = $data->elements;

        if (Tool::classExists($className)) {
            $object = new $className();

            if ($object instanceof Concrete) {
                $class = $object->getClass();

                if (is_array($elements)) {
                    foreach ($elements as $element) {
                        if (in_array($element->name, $ignore)) {
                            continue;
                        }

                        $setter = "set" . ucfirst($element->name);

                        if ($element->type === 'coreShopStoreMultiselect') {
                            if (is_array($element->value)) {
                                foreach ($element->value as &$store) {
                                    $store = $idMap['store'][$store];
                                }
                            }
                        }

                        if ($element->type === 'multihref') {
                            $multihrefToUse = [];

                            if (is_array($element->value)) {
                                foreach ($element->value as &$multihref) {
                                    if ($multihref->type === 'asset') {
                                        if (array_key_exists($multihref->id, $idMap['asset'])) {
                                            $multihref->id = $idMap['asset'][$multihref->id];
                                            $multihrefToUse[] = $multihref;
                                        }
                                    } else if ($multihref->type === 'object') {
                                        if (array_key_exists($multihref->id, $idMap['object'])) {
                                            $multihref->id = $idMap['object'][$multihref->id];
                                            $multihrefToUse[] = $multihref;
                                        }
                                    }
                                }
                            }

                            $element->value = $multihrefToUse;
                        }

                        if ($element->type === 'objects') {
                            $relationsToUse = [];

                            if (is_array($element->value)) {
                                foreach ($element->value as &$objectRelation) {
                                    if (array_key_exists($objectRelation->id, $idMap['object'])) {
                                        $objectRelation->id = $idMap['object'][$objectRelation->id];
                                        $relationsToUse[] = $objectRelation;
                                    }
                                }
                            }

                            $element->value = $relationsToUse;
                        }

                        if ($element->type === "href") {
                            if ($element->value) {
                                if ($element->value->type === 'asset') {
                                    if (array_key_exists($element->value->id, $idMap['asset'])) {
                                        $element->value->id = $idMap['asset'][$element->value->id];
                                    }
                                    else {
                                        $element->value = null;
                                    }
                                } else if ($element->value->type === 'object') {
                                    if (array_key_exists($element->value->id, $idMap['object'])) {
                                        $element->value->id = $idMap['object'][$element->value->id];
                                    }
                                    else {
                                        $element->value = null;
                                    }
                                }
                            }
                        }

                        if ($element->type === "image") {
                            if ($element->value) {
                                if (array_key_exists($element->value->id, $idMap['asset'])) {
                                    $element->value = $idMap['asset'][$element->value];
                                }
                                else {
                                    $element->value = null;
                                }
                            }
                        }

                        if ($element->type === 'localizedfields') {
                            $values = $element->value;
                            $valuesToUse = [];

                            foreach ($values as $value) {
                                if (in_array($value->name, $ignore)) {
                                    continue;
                                }

                                $valuesToUse[] = $value;
                            }

                            $element->value = $valuesToUse;
                        }

                        if ($element->type === 'coreShopCurrency') {
                            if ($element->value) {
                                if (array_key_exists('currency', $idMap) && array_key_exists($element->value->id, $idMap['currency'])) {
                                    $element->value = $idMap['currency'][$element->value->id];
                                }
                            }
                        }

                        if ($element->type === 'coreShopTaxRuleGroup') {
                            if ($element->value) {
                                if (array_key_exists('tax_rule_group', $idMap) && array_key_exists($element->value->id, $idMap['tax_rule_group'])) {
                                    $element->value = $idMap['tax_rule_group'][$element->value->id];
                                }
                            }
                        }

                        if (method_exists($object, $setter)) {
                            $tag = $class->getFieldDefinition($element->name);
                            if ($tag) {
                                if ($element->value) {
                                    try {
                                        if ($class instanceof Fieldcollections) {
                                            $object->$setter($tag->getFromWebserviceImport($element->fieldcollection, $object, array()));
                                        } else {
                                            $object->$setter($tag->getFromWebserviceImport($element->value, $object, array()));
                                        }
                                    } catch (\Exception $ex) {
                                        Logger::err($ex);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            foreach (get_object_vars($data) as $key => $value) {
                if ($key === "elements" || $key === "classname" || $key === "id")
                    continue;

                $setter = "set" . ucfirst($key);

                if (method_exists($object, $setter)) {
                    $object->$setter($value);
                }
            }

            $parent = AbstractObject::getByPath($data->path);

            if ($parent instanceof AbstractObject) {
                $object->setParent($parent);
            } else {
                $object->setParent(Service::createFolderByPath($data->path));
            }

            $object->save();


            return $object;
        }

        throw new \Exception(sprintf("class with name %s not found", $className));
    }

    /**
     * @return string
     */
    protected abstract function getPimcoreClass();

    /**
     * @return FactoryInterface
     */
    protected abstract function getFactory();
}