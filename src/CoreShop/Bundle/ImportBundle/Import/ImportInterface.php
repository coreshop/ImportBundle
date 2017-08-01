<?php

namespace CoreShop\Bundle\ImportBundle\Import;

use CoreShop\Component\Resource\Model\ResourceInterface;

interface ImportInterface
{
    /**
     * @return string
     */
    public function getType();

    /**
     * @param ResourceInterface[] $data
     * @param array $idMap
     * @return boolean
     */
    public function persistData($data, $idMap);

    /**
     * Cleanup Database -> means erasing existing data
     * @return boolean
     */
    public function cleanup();
}