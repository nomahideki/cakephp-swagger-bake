<?php

namespace SwaggerBake\Test\TestCase\Lib\Operation;

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute;
use SwaggerBake\Lib\Attribute\OpenApiResponse;
use SwaggerBake\Lib\Configuration;
use SwaggerBake\Lib\Exception\SwaggerBakeRunTimeException;
use SwaggerBake\Lib\Factory\SwaggerFactory;
use SwaggerBake\Lib\OpenApi\Operation;
use SwaggerBake\Lib\OpenApi\Response;
use SwaggerBake\Lib\OpenApi\Schema;
use SwaggerBake\Lib\Operation\OperationResponse;
use SwaggerBake\Lib\Route\RouteScanner;

class OperationResponseTest extends TestCase
{
    /**
     * @var string[]
     */
    public $fixtures = [
        'plugin.SwaggerBake.Employees',
        'plugin.SwaggerBake.DepartmentEmployees',
    ];

    private Router $router;

    private Configuration $config;

    private array $routes;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $router = new Router();
        $router::scope('/', function (RouteBuilder $builder) {
            $builder->setExtensions(['json']);
            $builder->resources('Employees', [
                'only' => [
                    'index',
                    'create',
                    'delete',
                    'noResponsesDefined',
                    'textPlain',
                    'options'
                ],
                'map' => [
                    'noResponsesDefined'  => [
                        'method' => 'get',
                        'action' => 'noResponseDefined',
                        'path' => 'no-responses-defined'
                    ],
                    'textPlain'  => [
                        'method' => 'get',
                        'action' => 'textPlain',
                        'path' => 'text-plain'
                    ],
                    'options' => [
                        'method' => ['options'],
                        'action' => 'options',
                        'path' => 'options'
                    ]
                ]
            ]);
        });
        $this->router = $router;

        $this->config = new Configuration([
            'prefix' => '/',
            'yml' => '/config/swagger-bare-bones.yml',
            'json' => '/webroot/swagger.json',
            'webPath' => '/swagger.json',
            'hotReload' => false,
            'exceptionSchema' => 'Exception',
            'requestAccepts' => ['application/x-www-form-urlencoded'],
            'responseContentTypes' => ['application/json','application/xml'],
            'namespaces' => [
                'controllers' => ['\SwaggerBakeTest\App\\'],
                'entities' => ['\SwaggerBakeTest\App\\'],
                'tables' => ['\SwaggerBakeTest\App\\'],
            ]
        ], SWAGGER_BAKE_TEST_APP);

        $cakeRoute = new RouteScanner($this->router, $this->config);
        $this->routes = $cakeRoute->getRoutes();
    }

    public function test_get_operation_with_attribute_response(): void
    {
        $route = $this->routes['employees:index'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([
                    new ReflectionAttribute(OpenApiResponse::class, [
                        'statusCode' => '200',
                    ]),
                ])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('hello', 'get'),
            $route,
            null,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();

        $this->assertInstanceOf(Response::class, $operation->getResponseByCode('200'));
    }

    public function test_get_operation_with_schema_response(): void
    {
        $route = $this->routes['employees:add'];

        $schema = (new Schema())->setName('Employee')->setType('object');

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('employees:add', 'post'),
            $route,
            $schema,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();

        $this->assertInstanceOf(Response::class, $operation->getResponseByCode('200'));
    }

    public function test_add_operation_with_open_api_response_of_201(): void
    {
        $route = $this->routes['employees:add'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([
                    new ReflectionAttribute(OpenApiResponse::class, [
                        'statusCode' => '201',
                    ]),
                ])
            );

        $schema = (new Schema())->setName('Employee')->setType('object');

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('employees:add', 'post'),
            $route,
            $schema,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();
        $response = $operation->getResponseByCode('201');
        $this->assertNotEmpty($response);

        $content = $response->getContentByMimeType('application/json');

        $this->assertNotEmpty($content);
        $this->assertNotEmpty($content->getSchema());
    }

    public function test_add_operation_with_no_response_defined(): void
    {
        $route = $this->routes['employees:add'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('employees:add', 'post'),
            $route,
            null,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();
        $response = $operation->getResponseByCode('200');
        $this->assertNotEmpty($response);

        $content = $response->getContentByMimeType('application/json');

        $this->assertNotEmpty($content);
        $this->assertNotEmpty($content->getSchema());
    }

    public function test_delete_action_response_with_http_204(): void
    {
        $route = $this->routes['employees:delete'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('employees:delete', 'delete'),
            $route,
            null,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();
        $this->assertNotEmpty($operation->getResponseByCode('204'));
    }

    public function test_no_response_defined(): void
    {
        $route = $this->routes['employees:noresponsedefined'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('employees:noresponsedefined', 'get'),
            $route,
            null,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();
        $response = $operation->getResponseByCode('200');
        $this->assertNotEmpty($response);

        $content = $response->getContentByMimeType('application/json');
        $this->assertNotEmpty($content);
        $this->assertNotEmpty($content->getSchema());
    }

    public function test_get_operation_with_swag_response_schema_ref_entity(): void
    {
        $route = $this->routes['employees:index'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([
                    new ReflectionAttribute(OpenApiResponse::class, [
                        'schemaType' => 'array',
                        'ref' => '#/components/schema/Employee',
                    ]),
                ])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('employees:index', 'get'),
            $route,
            null,
            $mockReflectionMethod
        );

        $content = $operationResponse
            ->getOperationWithResponses()
            ->getResponseByCode('200')
            ->getContentByMimeType('application/json');

        $this->assertEquals('#/components/schema/Employee', $content->getSchema()->getItems()['$ref']);
    }

    public function test_get_operation_with_swag_response_schema_text_plain(): void
    {
        $route = $this->routes['employees:textplain'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([
                    new ReflectionAttribute(OpenApiResponse::class, [
                        'mimeTypes' => ['text/plain'],
                        'schemaFormat' => 'date-time',
                    ]),
                ])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('employees:textplain', 'get'),
            $route,
            null,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();

        $content = $operation->getResponseByCode('200')->getContentByMimeType('text/plain');

        $this->assertEquals('string', $content->getSchema()->getType());
        $this->assertEquals('date-time', $content->getSchema()->getFormat());
    }

    /**
     * @link https://github.com/cnizzardini/cakephp-swagger-bake/issues/363
     */
    public function test_associations(): void
    {
        $route = $this->routes['employees:index'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([
                    new ReflectionAttribute(OpenApiResponse::class, [
                        'associations' => ['whiteList' => ['DepartmentEmployees']]
                    ]),
                ])
            );

        $swagger = (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create();

        $operationResponse = new OperationResponse(
            $swagger,
            $this->config,
            new Operation('hello', 'get'),
            $route,
            null,
            $mockReflectionMethod
        );

        $operation = $operationResponse->getOperationWithResponses();
        $content = $operation->getResponseByCode('200')->getContentByMimeType('application/json');
        $this->assertArrayHasKey('department_employees', $content->getSchema()->getProperties());

        // issue #363 association schema should only modify the operations' response schema and not the main schema.
        $schema = $swagger->getSchemaByName('Employee');
        $this->assertArrayNotHasKey('department_employees', $schema->getProperties());
    }

    public function test_text_plain_mime_type(): void
    {
        $route = $this->routes['employees:index'];

        $mockReflectionMethod = $this->createPartialMock(\ReflectionMethod::class, ['getAttributes']);
        $mockReflectionMethod->expects($this->once())
            ->method(
                'getAttributes'
            )
            ->with(OpenApiResponse::class)
            ->will(
                $this->returnValue([
                    new ReflectionAttribute(OpenApiResponse::class, [
                        'mimeTypes' => ['text/plain']
                    ]),
                ])
            );

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('hello', 'get'),
            $route,
            null,
            $mockReflectionMethod
        );

        $content = $operationResponse
            ->getOperationWithResponses()
            ->getResponseByCode('200')
            ->getContentByMimeType('text/plain');

        $this->assertEquals('text/plain', $content->getMimeType());
    }

    public function test_http_options(): void
    {
        $route = $this->routes['employees:options'];

        $operationResponse = new OperationResponse(
            (new SwaggerFactory($this->config, new RouteScanner($this->router, $this->config)))->create(),
            $this->config,
            new Operation('hello', 'options'),
            $route,
            null,
            null
        );

        $this->assertNotEmpty($operationResponse->getOperationWithResponses()->getResponseByCode('200'));
    }
}