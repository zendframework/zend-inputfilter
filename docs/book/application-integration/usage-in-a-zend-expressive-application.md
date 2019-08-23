# Usage in a zend-expressive Application

The following example shows _one_ usage of zend-inputfilter in a zend-expressive
based application. The example uses a module, config provider configuration,
zend-servicemanager as dependency injection container, the zend-inputfilter
plugin manager and a request handler.

Before starting, make sure zend-inputfilter is installed and configured.

## Create Input-Filter

Create an input-filter as separate class, e.g.
`src/Album/InputFilter/QueryInputFilter.php`:

```php
namespace Album\InputFilter;

use Zend\Filter\ToInt;
use Zend\I18n\Validator\IsInt;
use Zend\InputFilter\InputFilter;

class QueryInputFilter extends InputFilter
{
    public function init()
    {
        // Page
        $this->add(
            [
                'name'              => 'page',
                'allow_empty'       => true,
                'validators'        => [
                    [
                        'name' => IsInt::class,                        
                    ],                    
                ],
                'filters'           => [
                    [
                        'name' => ToInt::class,
                    ],
                ],
                'fallback_value'    => 1,
            ]
        );
    
        // …
    }
}
```

## Register Input-Filter

Extend the configuration provider of the module to register the input-filter,
e.g. `src/Album/ConfigProvider.php`:

```php
namespace Album;

use Zend\ServiceManager\Factory\InvokableFactory;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies'  => $this->getDependencies(),
            'input_filters' => $this->getInputFilters(), // <-- Add this line
        ];
    }
    
    // Add the following method
    public function getInputFilters() : array
    {
        return [
            'factories' => [
                InputFilter\QueryInputFilter::class => InvokableFactory::class,
            ],
        ];
    }
    
    // …
}
```

## Using Input-Filter

### Create Handler

Using the input-filter in a request handler, e.g.
`src/Album/Handler/ListHandler.php`:

```php
namespace Album\Handler;

use Album\InputFilter\QueryInputFilter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\InputFilter\InputFilterInterface;

class ListHandler implements RequestHandlerInterface
{
    /** @var InputFilterInterface */
    private $inputFilter;
    
    public function __construct(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;        
    }
    
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->inputFilter->setData($request->getQueryParams());
        $this->inputFilter->isValid();
        $filteredParams = $this->inputFilter->getValues();
        
        // …
    }
}
```

### Create Factory for Handler

Fetch the `QueryInputFilter` from the input-filter plugin manager in a factory,
e.g. `src/Album/Handler/ListHandlerFactory.php`:

```php
namespace Album\Handler;

use Album\InputFilter\QueryInputFilter;
use Psr\Container\ContainerInterface;
use Zend\InputFilter\InputFilterPluginManager;

class ListHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        /** @var InputFilterPluginManager $pluginManager */
        $pluginManager = $container->get(InputFilterPluginManager::class);
        $inputFilter   = $pluginManager->get(QueryInputFilter::class);

        return new ListHandler($inputFilter);
    }
}
```

> ### Instantiating the Input-Filter
>
> The `InputFilterPluginManager` calls the `init` method _after_ instantiating
the input-filter and injecting it with a factory composing all the various 
plugin-manager services.

### Register Handler

Extend the configuration provider of the module to register the input-filter,
e.g. `src/Album/ConfigProvider.php`:

```php
namespace Album;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies'  => $this->getDependencies(),
            'input_filters' => $this->getInputFilters(),
        ];
    }
    
    public function getDependencies() : array
    {
        return [
            'factories' => [
                Handler\ListHandler::class => Handler\ListHandlerFactory::class, // <-- Add this line
            ],
        ];
    }
    
    // …
}
```
