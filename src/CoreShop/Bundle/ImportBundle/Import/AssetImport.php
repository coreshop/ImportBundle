<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Tool;

final class AssetImport implements ImportInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'asset';
    }

    /**
     * {@inheritdoc}
     */
    public function persistData($data, $idMap)
    {
        foreach ($data as $assetData) {

            if ($assetData['type'] === 'folder') {
                continue;
            }

            $assetType = ucfirst($assetData['type']);
            $assetClass = Asset::class . "\\" . $assetType;

            if (Tool::classExists($assetClass)) {
                /**
                 * @var $asset Asset
                 */
                $asset = new $assetClass();
                $asset->setParent(Asset\Service::createFolderByPath($assetData['path']));
                $asset->setFilename($assetData['filename']);

                $cache = md5($assetData['url']);
                $cacheFile = sprintf('%s/%s', PIMCORE_SYSTEM_TEMP_DIRECTORY, $cache);

                if (file_exists($cacheFile)) {
                    Logger::debug('using asset from cache');

                    $asset->setData(file_get_contents($cacheFile));
                } else {
                    $url = parse_url($assetData['url']);

                    if ($url) {
                        $path = implode('/', array_map('rawurlencode', explode('/', $url['path'])));

                        $fileData = file_get_contents(sprintf("%s://%s/%s", $url['scheme'], $url['host'], $path));

                        file_put_contents($cacheFile, $fileData);

                        $asset->setData($fileData);
                    }
                }

                $asset->save();

                $idMap[$this->getType()][$assetData['id']] = $asset->getId();
            }
        }

        return $idMap;
    }

    public function cleanup()
    {
        $list = Asset::getList(['condition' => 'type != \'folder\'']);
        $list->load();

        foreach ($list as $asset) {
            if ($asset->getFullPath() === '/') {
                continue;
            }

            $asset->delete();
        }
    }
}