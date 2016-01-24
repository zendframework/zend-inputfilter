<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\InputFilter;

use Interop\Container\ContainerInterface;
use Zend\Filter\FilterPluginManager;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Validator\ValidatorPluginManager;

class InputFilterAbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @param ServiceLocatorInterface $inputFilters
     * @param string                  $cName
     * @param string                  $rName
     * @return bool
     */
    public function canCreate(ContainerInterface $services, $rName)
    {
        if (! $services->has('Config')) {
            return false;
        }

        $config = $services->get('Config');
        if (!isset($config['input_filter_specs'][$rName])
            || !is_array($config['input_filter_specs'][$rName])
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param ServiceLocatorInterface $inputFilters
     * @param string                  $cName
     * @param string                  $rName
     * @return InputFilterInterface
     */
    public function __invoke(ContainerInterface $services, $rName, array  $options = null)
    {
        $allConfig = $services->get('Config');
        $config    = $allConfig['input_filter_specs'][$rName];

        $factory   = $this->getInputFilterFactory($services);

        return $factory->createInputFilter($config);
    }

    /**
     * @param ServiceLocatorInterface $inputFilters
     * @param string                  $cName
     * @param string                  $rName
     * @return InputFilterInterface
     */
    public function createServiceWithName(ServiceLocatorInterface $inputFilters, $cName, $rName)
    {
        return $this($inputFilters, $rName);
    }

    /**
     * @param ServiceLocatorInterface $services
     * @return Factory
     */
    protected function getInputFilterFactory(ServiceLocatorInterface $services)
    {
        if ($this->factory instanceof Factory) {
            return $this->factory;
        }

        $this->factory = new Factory();
        $this->factory
            ->getDefaultFilterChain()
            ->setPluginManager($this->getFilterPluginManager($services));
        $this->factory
            ->getDefaultValidatorChain()
            ->setPluginManager($this->getValidatorPluginManager($services));

        return $this->factory;
    }

    /**
     * @param ServiceLocatorInterface $services
     * @return FilterPluginManager
     */
    protected function getFilterPluginManager(ServiceLocatorInterface $services)
    {
        if ($services->has('FilterManager')) {
            return $services->get('FilterManager');
        }

        return new FilterPluginManager(new ServiceManager());
    }

    /**
     * @param ServiceLocatorInterface $services
     * @return ValidatorPluginManager
     */
    protected function getValidatorPluginManager(ServiceLocatorInterface $services)
    {
        if ($services->has('ValidatorManager')) {
            return $services->get('ValidatorManager');
        }

        return new ValidatorPluginManager(new ServiceManager());
    }
}
