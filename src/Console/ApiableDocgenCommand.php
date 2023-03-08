<?php

namespace OpenSoutheners\LaravelApiable\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSoutheners\LaravelApiable\Attributes\AppendsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\DocumentedEndpointSection;
use OpenSoutheners\LaravelApiable\Attributes\FieldsQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\FilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\IncludeQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\QueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchFilterQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SearchQueryParam;
use OpenSoutheners\LaravelApiable\Attributes\SortQueryParam;
use OpenSoutheners\LaravelApiable\Http\AllowedFilter;
use OpenSoutheners\LaravelApiable\Http\AllowedSort;
use OpenSoutheners\LaravelApiable\Http\JsonApiResponse;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class ApiableDocgenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apiable:docgen
                            {--exclude= : Exclude routes containing the following list on their URI paths (comma separated)}
                            {--only= : Generate documentation only for routes containing the following on their URI paths}
                            {--markdown : Generate documentation in raw and reusable Markdown}
                            {--postman : Generate documentation as Postman collection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation based on JSON:API endpoints.';

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct(
        protected Router $router,
        protected array $resources = [],
        protected array $endpoints = []
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $appRoutes = $this->router->getRoutes();

        $exportFormat = $this->askForExportFormat();

        $this->getEndpointsToDocument(
            $this->filterRoutesToDocument($appRoutes)
        );

        // TODO: Auth event with Sanctum or Passport?
        match ($exportFormat) {
            'postman' => $this->exportEndpointsToPostman(),
            'markdown' => $this->exportEndpointsToMarkdown(),
            default => null
        };

        $this->info("Export successfully to {$exportFormat}");

        return 0;
    }

    public function exportEndpointsToMarkdown()
    {
        Collection::make($this->endpoints)
            ->groupBy('resource')
            ->each(function (Collection $item, string $resource) {
                $markdownContent = '';
                $resourceFancyName = $this->resources[$resource]['title'] ?? Str::title($resource);
                $markdownContent .= "\n# {$resourceFancyName}\n";
                $resourceDescription = $this->resources[$resource]['description'] ?? "{$resourceFancyName} resource endpoints documentation.";
                $markdownContent .= "\n{$resourceDescription}\n";

                // TODO: Extract model properties into <Properties> React component

                $item->each(function (array $value) use (&$markdownContent) {
                    $markdownContent .= "\n---\n";
                    $markdownContent .= "\n## {$value['name']} {{ tag: '{$value['method']}', label: '{$value['path']}' }}\n";

                    $queryParamsRequiredProperties = '';

                    if (! empty($value['requiredPayload'])) {
                        $queryParamsRequiredProperties .= "### Required attributes\n";
                        $queryParamsRequiredProperties .= "<Properties>\n";

                        foreach ($value['requiredPayload'] as $payloadParam => $payloadValueType) {
                            $queryParamsRequiredProperties .= "<Property name=\"{$payloadParam}\" type=\"{$payloadValueType}\">\n";

                            // TODO: Query parameter description...

                            $queryParamsRequiredProperties .= "</Property>\n";
                        }

                        $queryParamsRequiredProperties .= "</Properties>\n";
                    }

                    $queryParamsProperties = "<Properties>\n";

                    foreach ($value['query'] as $queryParam => $queryParamValue) {
                        $queryParamsProperties .= "<Property name=\"{$queryParam}\" type=\"string\">\n";

                        // TODO: Query parameter description...
                        // $queryParamsProperties .= 'Possible values: '.implode(', ', array_map(fn ($sortValue) => "`{$sortValue}`", array_values($value['query']['sorts'])))."\n";
                        $queryParamsProperties .= "{$queryParamValue['description']}\n";

                        $queryParamsProperties .= "</Property>\n";
                    }

                    foreach ($value['payload'] as $payloadParam => $payloadValueType) {
                        $queryParamsProperties .= "<Property name=\"{$payloadParam}\" type=\"{$payloadValueType}\">\n";

                        // TODO: Query parameter description...

                        $queryParamsProperties .= "</Property>\n";
                    }

                    $queryParamsProperties .= "</Properties>\n";

                    $markdownContent .= <<<EOT
                    <Row>
                        <Col>
                            {$value['description']}

                            {$queryParamsRequiredProperties}

                            ### Optional attributes
                            {$queryParamsProperties}
                        </Col>
                        <Col sticky>
                            <CodeGroup title="Request" tag="{$value['method']}" label="{$value['path']}">

                            ```bash {{ title: 'cURL' }}
                            curl -G {$value['fullPath']} \
                                -H "Authorization: Bearer {token}"
                            ```
                            
                            </CodeGroup>

                            ```json {{ title: 'Response' }}
                            {
                                "data": [
                                    {
                                        "id": "1",
                                        "type": "{$value['name']}"
                                    },
                                    // ...
                                ],
                                "meta": {},
                                "links": {}
                            }
                            ```
                        </Col>
                    </Row>
                    EOT;
                });

                Storage::disk('local')->put("exports/markdown/{$resource}.mdx", $markdownContent);
            });
    }

    // TODO: Update with new array data structure from fetchRoutes
    protected function exportEndpointsToPostman()
    {
        $postmanCollection = [
            'info' => [
                'name' => config('app.name'),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        Collection::make($this->endpoints)
            ->groupBy('resource')
            ->each(function (Collection $item, string $resource) use (&$postmanCollection) {
                $postmanItem = [
                    'name' => $resource,
                    'description' => $this->resources[$resource]['description'] ?? '',
                    'item' => [],
                ];

                $item->each(function (array $value) use (&$postmanItem) {
                    $postmanItemValue = [];

                    $postmanItemValue['name'] = $value['name'];
                    $postmanItemValue['request']['description'] = $value['description'];

                    $host = '{{base_url}}';
                    $path = preg_replace('/{(.+)}/m', '{{$1}}', $value['path']);
                    $postmanItemValue['request']['url']['raw'] = "{$host}{$path}";
                    $postmanItemValue['request']['url']['host'] = $host;
                    $postmanItemValue['request']['url']['path'] = explode('/', $path);
                    $postmanItemValue['request']['method'] = $value['method'];
                    $postmanItemValue['request']['header'][] = [
                        'key' => 'Accept',
                        'type' => 'text',
                        'value' => 'application/json',
                    ];

                    foreach ($value['query'] as $paramKey => $paramDetails) {
                        $filterItem = [];

                        $filterItem['key'] = $paramKey;
                        $filterItem['value'] = '';
                        $values = implode(', ', (array) $paramDetails['values']);
                        $filterItem['description'] = $paramDetails['description'] ? "{$paramDetails['description']}. " : '';
                        $filterItem['description'] .= "Allowed values: {$values}";
                        $filterItem['disabled'] = true;

                        $postmanItemValue['request']['url']['query'][] = $filterItem;
                    }

                    if (! empty($value['requiredPayload']) || ! empty($value['payload'])) {
                        $postmanItemValue['request']['body'] = [
                            'mode' => 'urlencoded',
                            'urlencoded' => [],
                        ];
                    }

                    foreach ($value['requiredPayload'] as $key => $type) {
                        $bodyItem = [];

                        $bodyItem['key'] = $key;
                        $bodyItem['value'] = '';
                        $bodyItem['description'] = "Required value of type {$type}";
                        $bodyItem['disabled'] = false;

                        $postmanItemValue['request']['body'][$postmanItemValue['request']['body']['mode']][] = $bodyItem;
                    }

                    foreach ($value['payload'] as $paramKey => $paramDetails) {
                        $bodyItem = [];

                        $bodyItem['key'] = $key;
                        $bodyItem['value'] = '';
                        $bodyItem['description'] = "Optional value of type {$type}";
                        $bodyItem['disabled'] = true;

                        $postmanItemValue['request']['body'][$postmanItemValue['request']['body']['mode']][] = $bodyItem;
                    }

                    // if ($value['query']['search'] ?? false) {
                    //     $postmanItemValue['request']['url']['query'][] = [
                    //         'key' => 'q',
                    //         'value' => '',
                    //         'description' => "Search {$postmanItemValue['name']} performing a fulltext search",
                    //         'disabled' => true,
                    //     ];

                    //     foreach ($value['query']['searchFilters'] as $attribute => $filterValues) {
                    //         $searchFilterItem = [];

                    //         $searchFilterItem['key'] = "search[filter][{$attribute}]";
                    //         $searchFilterItem['value'] = '';
                    //         $searchFilterItem['description'] = 'Allowed values: '.implode(',', (array) $filterValues);
                    //         $searchFilterItem['disabled'] = true;

                    //         $postmanItemValue['request']['url']['query'][] = $searchFilterItem;
                    //     }
                    // }

                    $postmanItem['item'][] = $postmanItemValue;
                });

                $postmanCollection['item'][] = $postmanItem;
            });

        Storage::disk('local')->put(
            'exports/documentation.postman_collection.json',
            json_encode($postmanCollection)
        );
    }

    protected function filterRoutesToDocument(RouteCollection $routes)
    {
        $filterOnlyBy = $this->option('only');

        return array_filter(iterator_to_array($routes), function (Route $route) use ($filterOnlyBy) {
            $hasBeenExcluded = Str::is(array_merge(explode(',', $this->option('exclude')), [
                '_debugbar/*', '_ignition/*', 'nova-api/*', 'nova/*', 'nova',
            ]), $route->uri());

            if ($hasBeenExcluded) {
                return false;
            }

            if ($filterOnlyBy) {
                return Str::is($filterOnlyBy, $route->uri());
            }

            return true;
        });
    }

    protected function getEndpointsToDocument(array $routes)
    {
        /** @var \Illuminate\Routing\Route $route */
        foreach ($routes as $route) {
            $documentedRoute = [];

            $routeMethods = array_filter($route->methods(), fn ($value) => $value !== 'HEAD');

            $routeAction = $route->getActionName();

            if ($routeAction === 'Closure') {
                continue;
            }

            if (! Str::contains($routeAction, '@')) {
                $routeAction = "{$routeAction}@__invoke";
            }

            [$controller, $method] = explode('@', $routeAction);

            if (! class_exists($controller) || ! method_exists($controller, $method)) {
                continue;
            }

            $classReflector = new ReflectionClass($controller);
            $methodReflector = new ReflectionMethod($controller, $method);

            $hasJsonApiResponse = ! empty(array_filter(
                $methodReflector->getParameters(),
                fn (ReflectionParameter $reflectorParam) => $reflectorParam->getType()?->getName() === JsonApiResponse::class
            ));

            $attributes = $hasJsonApiResponse
                ? array_filter(
                    array_merge(
                        $classReflector->getAttributes(),
                        $methodReflector->getAttributes()
                    ),
                    function (ReflectionAttribute $attribute) {
                        return is_subclass_of($attribute->getName(), QueryParam::class);
                    }
                ) : [];

            $routeName = $route->getName();

            if ($routeName && str_contains($routeName, '.')) {
                $endpointResource = Str::of($controller)->afterLast('\\')->before('Controller')->lower()->toString();
                $endpointResourcePlural = Str::plural($endpointResource);

                [$endpointAction, $endpointDescription] = match ($method) {
                    'index' => ["List {$endpointResourcePlural}", "This endpoint allows you to retrieve a paginated list of all your {$endpointResourcePlural}."],
                    'store' => ["Create new {$endpointResource}", "This endpoint allows you to add a new {$endpointResource}."],
                    'show' => ["Get one {$endpointResource}", "This endpoint allows you to retrieve a {$endpointResource}."],
                    'update' => ["Modify {$endpointResource}", "This endpoint allows you to perform an update on a {$endpointResource}."],
                    'destroy' => ["Remove {$endpointResource}", "This endpoint allows you to delete a {$endpointResource}."],
                    default => ['']
                };

                $documentedRoute['resource'] = $endpointResourcePlural;
                $documentedRoute['name'] = $endpointAction;
                $documentedRoute['description'] = $endpointDescription;
            }

            $documentedEndpointMethodAttribute = array_filter($methodReflector->getAttributes(DocumentedEndpointSection::class));

            if (! empty($documentedEndpointMethodAttribute)) {
                $documentedEndpointMethodAttribute = reset($documentedEndpointMethodAttribute);
                $documentedEndpointMethodAttribute = $documentedEndpointMethodAttribute->newInstance();

                $documentedRoute['name'] = $documentedEndpointMethodAttribute?->title ?? $documentedRoute['name'] ?? '';
                $documentedRoute['description'] = $documentedEndpointMethodAttribute?->description ?? $documentedRoute['description'] ?? '';
            }

            $this->resources[$documentedRoute['resource']] = $this->resources[$documentedRoute['resource']] ?? [];

            if (empty($this->resources[$documentedRoute['resource']])) {
                $documentedEndpointClassAttribute = $classReflector->getAttributes(DocumentedEndpointSection::class);
                $documentedEndpointClassAttribute = reset($documentedEndpointClassAttribute);

                if ($documentedEndpointClassAttribute) {
                    $documentedEndpointClassAttribute = $documentedEndpointClassAttribute->newInstance();
                    $this->resources[$documentedRoute['resource']]['title'] = $documentedEndpointClassAttribute?->title;
                    $this->resources[$documentedRoute['resource']]['description'] = $documentedEndpointClassAttribute?->description;
                }
            }

            $documentedRoute['method'] = head($routeMethods);
            $documentedRoute['path'] = Str::start($route->uri(), '/');
            $documentedRoute['fullPath'] = url($route->uri());

            //
            // Get payload params from receiving data routes with validation.
            //

            $documentedRoute['requiredPayload'] = [];
            $documentedRoute['payload'] = [];

            $formRequestParam = array_filter(
                $methodReflector->getParameters(),
                fn (ReflectionParameter $reflectorParam) => is_subclass_of($reflectorParam->getType()?->getName(), FormRequest::class)
            );

            if (! empty($formRequestParam)) {
                $formRequestParam = reset($formRequestParam)->getType()?->getName();

                // TODO: Too much guessing... maybe use rules in attributes?
                $formRequestParamRules = (new $formRequestParam())->rules();

                foreach ($formRequestParamRules as $attribute => $values) {
                    if (is_object($values)) {
                        // TODO:
                        $ruleValues = 'string';
                    } else {
                        $ruleValues = is_string($values) ? explode('|', $values) : $values;
                    }

                    $ruleValueType = array_filter(
                        $ruleValues,
                        fn ($rule) => in_array($rule, ['numeric', 'string', 'date', 'boolean', 'array'])
                    );

                    $isRequired = in_array('required', $ruleValues);

                    $documentedRoute[$isRequired ? 'requiredPayload' : 'payload'][$attribute] = reset($ruleValueType);
                }
            }

            //
            // Get query params from class or method attributes
            //

            $documentedRoute['query'] = [];

            foreach ($attributes as $attribute) {
                $attributeInstance = $attribute->newInstance();

                $matchFn = match ($attribute->getName()) {
                    DocumentedEndpointSection::class => function () use (&$documentedRoute, $attributeInstance) {
                        $documentedRoute['name'] = $attributeInstance->title;
                        $documentedRoute['description'] = $attributeInstance->description;
                    },
                    FilterQueryParam::class => function () use (&$documentedRoute, $attributeInstance) {
                        $filterTypes = array_map(fn ($operator) => match ($operator) {
                            AllowedFilter::EXACT => 'equal',
                            AllowedFilter::SCOPE => 'scope',
                            AllowedFilter::SIMILAR => 'like',
                            AllowedFilter::LOWER_THAN => 'lt',
                            AllowedFilter::GREATER_THAN => 'gt',
                            AllowedFilter::LOWER_OR_EQUAL_THAN => 'lte',
                            AllowedFilter::GREATER_OR_EQUAL_THAN => 'gte',
                            default => 'like',
                        }, (array) $attributeInstance->type);
                        $filterParamKey = "filter[{$attributeInstance->attribute}]";

                        foreach ($filterTypes as $type) {
                            $documentedRoute['query']["{$filterParamKey}[{$type}]"] = [
                                'values' => $attributeInstance->values,
                                'description' => $attributeInstance->description,
                            ];
                        }

                        if (count($filterTypes) <= 1) {
                            $documentedRoute['query'][$filterParamKey] = [
                                'values' => $attributeInstance->values,
                                'description' => $attributeInstance->description,
                            ];
                        }
                    },
                    FieldsQueryParam::class => function () use (&$documentedRoute, $attributeInstance) {
                        $documentedRoute['query']["fields[{$attributeInstance->type}]"] = [
                            'values' => $attributeInstance->fields,
                            'description' => $attributeInstance->description,
                        ];
                    },
                    AppendsQueryParam::class => function () use (&$documentedRoute, $attributeInstance) {
                        $documentedRoute['query']["appends[{$attributeInstance->type}]"] = [
                            'values' => $attributeInstance->attributes,
                            'description' => $attributeInstance->description,
                        ];
                    },
                    SortQueryParam::class => function () use (&$documentedRoute, $attributeInstance) {
                        if (! isset($documentedRoute['query']['sorts'])) {
                            $documentedRoute['query']['sorts'] = [
                                'values' => [],
                                'description' => $attributeInstance->description,
                            ];
                        }

                        $documentedRoute['query']['sorts']['values'] = array_merge(
                            $documentedRoute['query']['sorts']['values'],
                            match ($attributeInstance->direction) {
                                AllowedSort::BOTH => [$attributeInstance->attribute, "-$attributeInstance->attribute"],
                                AllowedSort::DESCENDANT => ["-$attributeInstance->attribute"],
                                AllowedSort::ASCENDANT => [$attributeInstance->attribute],
                            },
                        );
                    },
                    SearchFilterQueryParam::class => function () use (&$documentedRoute, $attributeInstance) {
                        $documentedRoute['query']['searchFilters'][$attributeInstance->attribute] = [
                            'values' => $attributeInstance->values,
                            'description' => $attributeInstance->description,
                        ];
                    },
                    SearchQueryParam::class => function () use (&$documentedRoute, $attributeInstance) {
                        $documentedRoute['query']['search'] = [
                            'values' => true,
                            'description' => $attributeInstance->description,
                        ];
                    },
                    IncludeQueryParam::class => function () use (&$documentedRoute, $attributeInstance) {
                        $documentedRoute['query']['includes'] = [
                            'values' => $attributeInstance->relationships,
                            'description' => $attributeInstance->description,
                        ];
                    },
                };

                $matchFn();
            }

            $this->endpoints[] = $documentedRoute;
        }
    }

    protected function askForExportFormat()
    {
        $postman = $this->option('postman');
        $markdown = $this->option('markdown');

        $formatOptions = compact('postman', 'markdown');

        if (empty($formatOptions)) {
            $option = $this->askWithCompletion('Export API documentation using format', array_keys($formatOptions));
        }

        return head(array_keys(array_filter($formatOptions)));
    }
}
