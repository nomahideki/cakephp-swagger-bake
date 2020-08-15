<?php

namespace SwaggerBake\Test\TestCase\Lib\Extension\CakeSearch;

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use SwaggerBake\Lib\AnnotationLoader;
use SwaggerBake\Lib\EntityScanner;
use SwaggerBake\Lib\RouteScanner;
use SwaggerBake\Lib\Configuration;
use SwaggerBake\Lib\Extension\CakeSearch\Annotation\SwagSearch;
use SwaggerBake\Lib\ExtensionLoader;
use SwaggerBake\Lib\Swagger;

class ExtensionTest extends TestCase
{
    /** @var string[] */
    public $fixtures = [
        'plugin.SwaggerBake.Employees',
    ];

    /** @var array */
    private $config;

    /** @var Router  */
    private $router;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $router = new Router();
        $router::scope('/api', function (RouteBuilder $builder) {
            $builder->setExtensions(['json']);
            $builder->resources('Employees', [
                'only' => ['swagSearch'],
                'map' => [
                    'swagSearch' => [
                        'action' => 'swagSearch',
                        'method' => 'GET',
                        'path' => 'swag-search'
                    ]
                ]
            ]);
        });
        $this->router = $router;

        $this->config = [
            'prefix' => '/api',
            'yml' => '/config/swagger-bare-bones.yml',
            'json' => '/webroot/swagger.json',
            'webPath' => '/swagger.json',
            'hotReload' => false,
            'exceptionSchema' => 'Exception',
            'requestAccepts' => ['application/x-www-form-urlencoded'],
            'responseContentTypes' => ['application/json'],
            'namespaces' => [
                'controllers' => ['\SwaggerBakeTest\App\\'],
                'entities' => ['\SwaggerBakeTest\App\\'],
                'tables' => ['\SwaggerBakeTest\App\\'],
            ]
        ];
        $this->assertTrue(class_exists(SwagSearch::class));
        $this->loadPlugins(['Search']);
        AnnotationLoader::load();
        ExtensionLoader::load();
    }

    public function testGetOperation()
    {
        $configuration = new Configuration($this->config, SWAGGER_BAKE_TEST_APP);

        $cakeRoute = new RouteScanner($this->router, $configuration);
        $swagger = new Swagger(new EntityScanner($cakeRoute, $configuration));

        $arr = json_decode($swagger->toString(), true);

        $this->assertArrayHasKey('get', $arr['paths']['/employees/swag-search']);
        $swagSearch = $arr['paths']['/employees/swag-search']['get'];

        $this->assertEquals('first_name', $swagSearch['parameters'][0]['name']);
    }
}