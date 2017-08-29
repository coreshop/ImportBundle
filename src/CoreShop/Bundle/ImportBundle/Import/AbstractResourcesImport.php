<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Resource\Factory\FactoryInterface;
use CoreShop\Component\Resource\Repository\RepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractResourcesImport implements ImportInterface
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
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup() {
        foreach($this->getRepository()->findAll() as $resource) {
            $this->entityManager->remove($resource);
        }

        $this->entityManager->flush();
    }

    /**
     * @return RepositoryInterface
     */
    protected abstract function getRepository();

    /**
     * @return FactoryInterface
     */
    protected abstract function getFactory();
}