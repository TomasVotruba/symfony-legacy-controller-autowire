<?php

declare(strict_types=1);

namespace TomasVotruba\SymfonyLegacyControllerAutowire\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TomasVotruba\SymfonyLegacyControllerAutowire\DependencyInjection\Compiler\RegisterControllersPass;
use TomasVotruba\SymfonyLegacyControllerAutowire\DependencyInjection\ControllerClassMap;
use TomasVotruba\SymfonyLegacyControllerAutowire\HttpKernel\Controller\ControllerFinder;
use TomasVotruba\SymfonyLegacyControllerAutowire\SymplifyControllerAutowireBundle;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\CompleteTestSource\Scan\AlreadyRegisteredController;
use TomasVotruba\SymfonyLegacyControllerAutowire\Tests\DependencyInjection\Compiler\RegisterControllersPassSource\SomeController;

final class RegisterControllersPassTest extends TestCase
{
    /**
     * @var RegisterControllersPass
     */
    private $registerControllersPass;

    protected function setUp(): void
    {
        $controllerClassMap = new ControllerClassMap();
        $controllerClassMap->addController('somecontroller', 'SomeController');

        $controllerFinder = new ControllerFinder();
        $this->registerControllersPass = new RegisterControllersPass($controllerClassMap, $controllerFinder);
    }

    public function testProcess(): void
    {
        $containerBuilder = new ContainerBuilder();
        $this->assertCount(0, $containerBuilder->getDefinitions());

        $containerBuilder->prependExtensionConfig(SymplifyControllerAutowireBundle::ALIAS, [
            'controller_dirs' => [
                __DIR__ . '/RegisterControllersPassSource',
            ],
        ]);
        $this->registerControllersPass->process($containerBuilder);

        $definitions = $containerBuilder->getDefinitions();
        $this->assertCount(1, $definitions);

        /** @var Definition $controllerDefinition */
        $controllerDefinition = array_pop($definitions);
        $this->assertInstanceOf(Definition::class, $controllerDefinition);

        $this->assertSame(SomeController::class, $controllerDefinition->getClass());
        $this->assertTrue($controllerDefinition->isAutowired());
    }

    public function testServiceDefinitionExists(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->prependExtensionConfig(SymplifyControllerAutowireBundle::ALIAS, [
            'controller_dirs' => [
                __DIR__ . '/RegisterControllersPassSource',
            ],
        ]);

        $controllerDefinition = new Definition(SomeController::class);
        $containerBuilder->setDefinition(
            'tomasvotruba.symfonylegacycontrollerautowire.tests.dependencyinjection.'
                . 'compiler.registercontrollerspasssource.somecontroller',
            $controllerDefinition
        );
        $this->assertCount(1, $containerBuilder->getDefinitions());

        $this->registerControllersPass->process($containerBuilder);
        $this->assertCount(1, $containerBuilder->getDefinitions());

        $this->assertTrue($controllerDefinition->isAutowired());
    }

    /**
     * Issue https://github.com/Symplify/Symplify/issues/103.
     */
    public function testPreventDuplicatedControllerRegistration(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->prependExtensionConfig(SymplifyControllerAutowireBundle::ALIAS, [
            'controller_dirs' => [
                __DIR__ . '/../../CompleteTestSource/Scan',
            ],
        ]);

        $controllerDefinition = new Definition(AlreadyRegisteredController::class);

        $containerBuilder->setDefinition('already_registered_controller', $controllerDefinition);
        $this->assertCount(1, $containerBuilder->getDefinitions());
        $this->assertFalse($controllerDefinition->isAutowired());

        $this->registerControllersPass->process($containerBuilder);

        $this->assertCount(3, $containerBuilder->getDefinitions());
        $this->assertTrue($controllerDefinition->isAutowired());
    }
}
