<?php

namespace CoreShop\Bundle\ImportBundle\Loader;

use CoreShop\Bundle\ImportBundle\Import\DependentImporterInterface;
use CoreShop\Bundle\ImportBundle\Import\ImportInterface;
use CoreShop\Bundle\ImportBundle\Import\OrderedImporterInterface;
use Doctrine\Common\DataFixtures\Exception\CircularReferenceException;
use Psr\Container\ContainerInterface;

/**
 * This class is a copy from Doctrine\Common\DataImporters
 */
class Loader
{
    /**
     * Array of importer object instances to execute.
     *
     * @var array
     */
    private $importers = array();

    /**
     * Array of ordered importer object instances.
     *
     * @var array
     */
    private $orderedImporters = array();

    /**
     * Determines if we must order importers by number
     *
     * @var boolean
     */
    private $orderImportersByNumber = false;
    
    /**
     * Determines if we must order importers by its dependencies
     *
     * @var boolean
     */
    private $orderImportersByDependencies = false;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Has importer?
     *
     * @param ImportInterface $importer
     *
     * @return boolean
     */
    public function hasImporter($importer)
    {
        return isset($this->importers[get_class($importer)]);
    }

    /**
     * Get a specific importer instance
     *
     * @param string $className
     * @return ImportInterface
     */
    public function getImporter($className)
    {
        if (!isset($this->importers[$className])) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a registered importer',
                $className
            ));
        }

        return $this->importers[$className];
    }

    /**
     * Add a importer object instance to the loader.
     *
     * @param ImportInterface $importer
     */
    public function addImporter(ImportInterface $importer)
    {
        $importerClass = get_class($importer);

        if (!isset($this->importers[$importerClass])) {
            if ($importer instanceof OrderedImporterInterface && $importer instanceof DependentImporterInterface) {
                throw new \InvalidArgumentException(sprintf('Class "%s" can\'t implement "%s" and "%s" at the same time.', 
                    get_class($importer),
                    'OrderedImporterInterface',
                    'DependentImporterInterface'));
            }

            $this->importers[$importerClass] = $importer;

            if ($importer instanceof OrderedImporterInterface) {
                $this->orderImportersByNumber = true;
            } elseif ($importer instanceof DependentImporterInterface) {
                $this->orderImportersByDependencies = true;
                foreach($importer->getDependencies() as $class) {
                    if (class_exists($class)) {
                        $this->addImporter(new $class($this->container));
                    }
                }
            }
        }
    }

    /**
     * Returns the array of data importers to execute.
     *
     * @return array $importers
     */
    public function getImporters()
    {
        $this->orderedImporters = array();

        if ($this->orderImportersByNumber) {
            $this->orderImportersByNumber();
        }

        if ($this->orderImportersByDependencies) {
            $this->orderImportersByDependencies();
        }
        
        if (!$this->orderImportersByNumber && !$this->orderImportersByDependencies) {
            $this->orderedImporters = $this->importers;
        }

        return $this->orderedImporters;
    }

    /**
     * Check if a given importer is transient and should not be considered a data importers
     * class.
     *
     * @return boolean
     */
    public function isTransient($className)
    {
        $rc = new \ReflectionClass($className);
        if ($rc->isAbstract()) return true;

        $interfaces = class_implements($className);
        return in_array('CoreShop\Bundle\ImportBundle\Import\ImportInterface', $interfaces) ? false : true;
    }

    /**
     * Orders importers by number
     * 
     * @todo maybe there is a better way to handle reordering
     * @return void
     */
    private function orderImportersByNumber()
    {
        $this->orderedImporters = $this->importers;
        usort($this->orderedImporters, function($a, $b) {
            if ($a instanceof OrderedImporterInterface && $b instanceof OrderedImporterInterface) {
                if ($a->getOrder() === $b->getOrder()) {
                    return 0;
                }
                return $a->getOrder() < $b->getOrder() ? -1 : 1;
            } elseif ($a instanceof OrderedImporterInterface) {
                return $a->getOrder() === 0 ? 0 : 1;
            } elseif ($b instanceof OrderedImporterInterface) {
                return $b->getOrder() === 0 ? 0 : -1;
            }
            return 0;
        });
    }
    
    
    /**
     * Orders importers by dependencies
     * 
     * @return void
     */
    private function orderImportersByDependencies()
    {
        $sequenceForClasses = array();

        // If importers were already ordered by number then we need 
        // to remove classes which are not instances of OrderedImporterInterface
        // in case importers implementing DependentImporterInterface exist.
        // This is because, in that case, the method orderImportersByDependencies
        // will handle all importers which are not instances of 
        // OrderedImporterInterface
        if ($this->orderImportersByNumber) {
            $count = count($this->orderedImporters);

            for ($i = 0 ; $i < $count ; ++$i) {
                if (!($this->orderedImporters[$i] instanceof OrderedImporterInterface)) {
                    unset($this->orderedImporters[$i]);
                }
            }
        }

        // First we determine which classes has dependencies and which don't
        foreach ($this->importers as $importer) {
            $importerClass = get_class($importer);

            if ($importer instanceof OrderedImporterInterface) {
                continue;
            } elseif ($importer instanceof DependentImporterInterface) {
                $dependenciesClasses = $importer->getDependencies();
                
                $this->validateDependencies($dependenciesClasses);

                if (!is_array($dependenciesClasses) || empty($dependenciesClasses)) {
                    throw new \InvalidArgumentException(sprintf('Method "%s" in class "%s" must return an array of classes which are dependencies for the importer, and it must be NOT empty.', 'getDependencies', $importerClass));
                }

                if (in_array($importerClass, $dependenciesClasses)) {
                    throw new \InvalidArgumentException(sprintf('Class "%s" can\'t have itself as a dependency', $importerClass));
                }
                
                // We mark this class as unsequenced
                $sequenceForClasses[$importerClass] = -1;
            } else {
                // This class has no dependencies, so we assign 0
                $sequenceForClasses[$importerClass] = 0;
            }
        }

        // Now we order importers by sequence
        $sequence = 1;
        $lastCount = -1;
        
        while (($count = count($unsequencedClasses = $this->getUnsequencedClasses($sequenceForClasses))) > 0 && $count !== $lastCount) {
            foreach ($unsequencedClasses as $key => $class) {
                $importer = $this->importers[$class];
                $dependencies = $importer->getDependencies();
                $unsequencedDependencies = $this->getUnsequencedClasses($sequenceForClasses, $dependencies);

                if (count($unsequencedDependencies) === 0) {
                    $sequenceForClasses[$class] = $sequence++;
                }                
            }
            
            $lastCount = $count;
        }

        $orderedImporters = array();
        
        // If there're importers unsequenced left and they couldn't be sequenced, 
        // it means we have a circular reference
        if ($count > 0) {
            $msg = 'Classes "%s" have produced a CircularReferenceException. ';
            $msg .= 'An example of this problem would be the following: Class C has class B as its dependency. ';
            $msg .= 'Then, class B has class A has its dependency. Finally, class A has class C as its dependency. ';
            $msg .= 'This case would produce a CircularReferenceException.';
            
            throw new CircularReferenceException(sprintf($msg, implode(',', $unsequencedClasses)));
        } else {
            // We order the classes by sequence
            asort($sequenceForClasses);

            foreach ($sequenceForClasses as $class => $sequence) {
                // If importers were ordered 
                $orderedImporters[] = $this->importers[$class];
            }
        }

        $this->orderedImporters = array_merge($this->orderedImporters, $orderedImporters);
    }

    private function validateDependencies($dependenciesClasses)
    {
        $loadedImporterClasses = array_keys($this->importers);
        
        foreach ($dependenciesClasses as $class) {
            if (!in_array($class, $loadedImporterClasses)) {
                throw new \RuntimeException(sprintf('Importer "%s" was declared as a dependency, but it should be added in importer loader first.', $class));
            }
        }

        return true;
    }

    private function getUnsequencedClasses($sequences, $classes = null)
    {
        $unsequencedClasses = array();

        if (is_null($classes)) {
            $classes = array_keys($sequences);
        }

        foreach ($classes as $class) {
            if ($sequences[$class] === -1) {
                $unsequencedClasses[] = $class;
            }
        }

        return $unsequencedClasses;
    }
}
