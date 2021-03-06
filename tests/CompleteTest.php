<?php

declare(strict_types=1);

namespace TomasVotruba\SymfonyLegacyControllerAutowire\Tests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use TomasVotruba\SymfonyLegacyControllerAutowire\Controller\ControllerTrait;
use TomasVotruba\SymfonyLegacyControllerAutowire\HttpKernel\Controller\ControllerResolver;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\CompleteTestSource\Controller\ControllerWithParameter;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\CompleteTestSource\DoNotScan\SomeRegisteredController;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\CompleteTestSource\Scan\ContainerAwareController;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\CompleteTestSource\Scan\TraitAwareController;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\HttpKernel\Controller\ControllerFinderSource\SomeController;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\HttpKernel\Controller\ControllerFinderSource\SomeService;

final class CompleteTest extends TestCase
{
    /**
     * @var ControllerResolver
     */
    private $controllerResolver;

    protected function setUp(): void
    {
        $kernel = new AppKernel();
        $kernel->boot();

        $container = $kernel->getContainer();
        $this->controllerResolver = $container->get('symplify.controller_resolver');
    }

    public function testMissingControllerParameter(): void
    {
        $request = new Request();
        /** @var bool $controller */
        $controller = $this->controllerResolver->getController($request);
        $this->assertFalse($controller);
    }

    public function testGetAutowiredController(): void
    {
        $request = $this->createRequestWithControllerAttribute(SomeController::class . '::someAction');

        /** @var SomeController $controller */
        $controller = $this->controllerResolver->getController($request)[0];

        $this->assertInstanceOf(SomeController::class, $controller);
        $this->assertInstanceOf(SomeService::class, $controller->getSomeService());
    }

    public function testGetContainerAwareController(): void
    {
        $request = $this->createRequestWithControllerAttribute(
            ContainerAwareController::class . '::someAction'
        );

        /** @var ContainerAwareController $controller */
        $controller = $this->controllerResolver->getController($request)[0];

        $this->assertInstanceOf(ContainerAwareController::class, $controller);
        $this->assertInstanceOf(ContainerInterface::class, $controller->getContainer());
    }

    public function testGetAutowiredControllerWithParameter(): void
    {
        $request = $this->createRequestWithControllerAttribute('some.controller.with_parameter:someAction');

        /** @var ControllerWithParameter $controller */
        $controller = $this->controllerResolver->getController($request)[0];

        $this->assertInstanceOf(ControllerWithParameter::class, $controller);
        $this->assertSame(__DIR__, $controller->getKernelRootDir());
    }

    public function testGetControllerWithTrait(): void
    {
        $request = $this->createRequestWithControllerAttribute(
            'tomasvotruba.symfonylegacycontrollerautowire.tests.completetestsource.scan.traitawarecontroller:someAction'
        );

        /** @var TraitAwareController&ControllerTrait $controller */
        $controller = $this->controllerResolver->getController($request)[0];

        $this->assertInstanceOf(TraitAwareController::class, $controller);

        $httpKernel = Assert::getObjectAttribute($controller, 'httpKernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $httpKernel);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function testGetControllerServiceMissing(): void
    {
        $request = $this->createRequestWithControllerAttribute(
            'some.missing.controller.service:someAction'
        );

        $this->controllerResolver->getController($request);
    }

    public function testGetControllerServiceRegisteredInConfig(): void
    {
        $request = $this->createRequestWithControllerAttribute('some.controller.service:someAction');

        $controller = $this->controllerResolver->getController($request)[0];
        $this->assertInstanceOf(SomeRegisteredController::class, $controller);
    }

    private function createRequestWithControllerAttribute(string $controllerAttribute): Request
    {
        $attributes = [
            '_controller' => $controllerAttribute,
        ];

        return new Request([], [], $attributes);
    }
}
