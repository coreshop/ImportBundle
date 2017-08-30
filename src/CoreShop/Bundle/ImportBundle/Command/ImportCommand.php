<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
*/

namespace CoreShop\Bundle\ImportBundle\Command;

use CoreShop\Bundle\ImportBundle\Import\ImportInterface;
use CoreShop\Bundle\ImportBundle\Loader\Loader;
use Pimcore\Model\Version;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ImportCommand extends ContainerAwareCommand
{
    /**
     * configure command.
     */
    protected function configure()
    {
        $this
            ->setName('coreshop:import')
            ->setDescription('Import File which has been exported from CoreShopExport Pimcore 4.* Plugin')
            ->addArgument('importFile')
            ->addOption('ignore-cache', 'c',  InputOption::VALUE_NONE);
    }

    /**
     * Execute command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Version::disable();

        $ignoreCache = $input->getOption('ignore-cache');
        $data = file_get_contents($input->getArgument('importFile'));
        $data = json_decode($data, true);

        $loader = new Loader($this->getContainer());
        $allServices = $this->getContainer()->get('coreshop.registry.import')->all();
        $idMap = [];

        /**
         * @var $service ImportInterface
         */
        foreach ($allServices as $type => $service) {
            $loader->addImporter($service);
        }

        $importerServices = $loader->getImporters();

        $progressBar = new ProgressBar($output, count($importerServices));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%/%estimated:-6s%) %message%');
        $progressBar->start();

        /**
         * @var $importer ImportInterface
         */
        foreach ($importerServices as $importer) {
            $progressBar->setMessage(sprintf('Run cleanup for %s', $importer->getType()));
            $progressBar->display();

            $idMapCacheFile = sprintf('%s/%s_id_map.serialized', PIMCORE_SYSTEM_TEMP_DIRECTORY, $importer->getType());

            if (!file_exists($idMapCacheFile) || $ignoreCache) {
                $importer->cleanup();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln("");

        $progressBar = new ProgressBar($output, count($importerServices));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%/%estimated:-6s%) %message%');
        $progressBar->start();

        /**
         * @var $importer ImportInterface
         */
        foreach ($importerServices as $importer) {
            $progressBar->setMessage(sprintf('Run persist for %s', $importer->getType()));
            $progressBar->display();

            $idMap[$importer->getType()] = [];

            $idMapCacheFile = sprintf('%s/%s_id_map.serialized', PIMCORE_SYSTEM_TEMP_DIRECTORY, $importer->getType());

            if (!file_exists($idMapCacheFile) || $ignoreCache) {
                $idMap = array_merge($idMap, $importer->persistData($data[$importer->getType()], $idMap));

                $idMapContent = serialize($idMap);
                file_put_contents($idMapCacheFile, $idMapContent);
            }
            else {
                $idMapContent = file_get_contents($idMapCacheFile);
                $idMap = array_merge($idMap, file_get_contents($idMapContent));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln("");

        $output->writeln("<info>Import finished!</info>");

        return 0;
    }
}
