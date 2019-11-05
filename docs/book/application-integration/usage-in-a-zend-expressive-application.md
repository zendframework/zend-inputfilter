# Usage in a zend-expressive Application

The following example shows _one_ usage of zend-inputfilter in a zend-expressive
based application. The example uses a module, config provider configuration,
zend-servicemanager as dependency injection container, the zend-inputfilter
plugin manager and a request handler.

Before starting, make sure zend-inputfilter is installed and configured.

## Create Input Filter

Create an input filter as separate class, e.g.
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

## Using Input Filter

### Create Handler

Using the input filter in a request handler, e.g.
`src/Album/Handler/ListHandler.php`:

```php
namespace Album\Handler;

use Album\InputFilter\QueryInputFilter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class ListHandler implements RequestHandlerInterface
{
    /** @var InputFilterInterface */
    private $inputFilter;

    /** @var TemplateRendererInterface */
    private $renderer;
    
    public function __construct(
        InputFilterInterface $inputFilter,
        TemplateRendererInterface $renderer
    ) {
        $this->inputFilter = $inputFilter;
        $this->renderer    = $renderer;
    }
    
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->inputFilter->setData($request->getQueryParams());
        $this->inputFilter->isValid();
        $filteredParams = $this->inputFilter->getValues();
        
        // …

        return new HtmlResponse($this->renderer->render(
            'album::list',
            []
        ));
    }
}
```

### Create Factory for Handler

Fetch the `QueryInputFilter` from the input filter plugin manager in a factory,
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

        return new ListHandler(
            $inputFilter,
            $container->get(TemplateRendererInterface::class)
        );
    }
}
```

> ### Instantiating the InputFilter
>
> The `InputFilterPluginManager` is used instead of directly instantiating the
> input filter to ensure to get the filter and validator plugin managers
> injected. This allows usage of any filters and validators registered with
> their respective plugin managers.
>
> Additionally the `InputFilterPluginManager` calls the `init` method _after_
> instantiating the input filter, ensuring all dependencies are fully injected
> first.

## Register Input Filter and Handler

Extend the configuration provider of the module to register the input filter and
the request handler, e.g. `src/Album/ConfigProvider.php`:

```php
namespace Album;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies'  => $this->getDependencies(),
            'input_filters' => $this->getInputFilters(), // <-- Add this line
        ];
    }
    
    public function getDependencies() : array
    {
        return [
            'factories' => [
                Handler\ListHandler::class => Handler\ListHandlerFactory::class, // <-- Add this line
                // …
            ],
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
