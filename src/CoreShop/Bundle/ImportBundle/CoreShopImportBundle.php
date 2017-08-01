<?php

namespace CoreShop\Bundle\ImportBundle;

use CoreShop\Bundle\ImportBundle\DependencyInjection\CompilerPass\RegisterImporterPass;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CoreShopImportBundle extends AbstractPimcoreBundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterImporterPass());
    }
}
