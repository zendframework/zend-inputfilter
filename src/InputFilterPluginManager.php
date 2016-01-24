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
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\InitializableInterface;
use Zend\ServiceManager\Factory\InvokableFactory;

/**
 * Plugin manager implementation for input filters.
 *
 * @method InputFilterInterface|InputInterface get($name)
 */
class InputFilterPluginManager extends AbstractPluginManager
{
    /**
     * Default alias of plugins
     *
     * @var string[]
     */
    protected $aliases = [
        'inputfilter' => InputFilter::class,
        'collection'  => CollectionInputFilter::class,
    ];

    /**
     * Default set of plugins
     *
     * @var string[]
     */
    protected $factories = [
        InputFilter::class  => InvokableFactory::class,
        CollectionInputFilter::class  => InvokableFactory::class,
    ];

    /**
     * Whether or not to share by default
     *
     * @var bool
     */
    protected $sharedByDefault = false;

    /**
     * @param ContainerInterface $parentLocator
     * @param array $config
     */
    public function __construct(ContainerInterface $parentLocator, array $config = [])
    {
        parent::__construct($parentLocator, $config);
        $this->addInitializer([$this, 'populateFactory']);
    }

    /**
     * Inject this and populate the factory with filter chain and validator chain
     *
     * @param mixed $first
     * @param mixed $second
     */
    public function populateFactory($first, $second)
    {
        if ($first instanceof ContainerInterface) {
            $container = $first;
            $inputFilter = $second;
        } else {
            $container = $second;
            $inputFilter = $first;
        }
        if ($inputFilter instanceof InputFilter) {
            $factory = $inputFilter->getFactory();

            $factory->setInputFilterManager($this);

            if ($container instanceof ContainerInterface) {
                $factory->getDefaultFilterChain()->setPluginManager($container->get('FilterManager'));
                $factory->getDefaultValidatorChain()->setPluginManager($container->get('ValidatorManager'));
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate($plugin)
    {
        if ($plugin instanceof InputFilterInterface || $plugin instanceof InputInterface) {
            // Hook to perform various initialization, when the inputFilter is not created through the factory
            if ($plugin instanceof InitializableInterface) {
                $plugin->init();
            }

            // we're okay
            return;
        }

        throw new Exception\RuntimeException(sprintf(
            'Plugin of type %s is invalid; must implement %s or %s',
            (is_object($plugin) ? get_class($plugin) : gettype($plugin)),
            InputFilterInterface::class,
            InputInterface::class
        ));
    }

    public function shareByDefault()
    {
        return $this->shareByDefault;
    }
}
