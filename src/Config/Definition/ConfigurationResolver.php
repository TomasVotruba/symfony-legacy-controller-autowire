<?php

declare(strict_types=1);

namespace TomasVotruba\SymfonyLegacyControllerAutowire\Config\Definition;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TomasVotruba\SymfonyLegacyControllerAutowire\SymplifyControllerAutowireBundle;

final class ConfigurationResolver
{
    /**
     * @var string[]
     */
    private $resolvedConfiguration;

    /**
     * @return mixed[]
     */
    public function resolveFromContainerBuilder(ContainerBuilder $containerBuilder): array
    {
        if (! $this->resolvedConfiguration) {
            $processor = new Processor();
            $configs = $containerBuilder->getExtensionConfig(SymplifyControllerAutowireBundle::ALIAS);
            $configs = $processor->processConfiguration(new Configuration(), $configs);

            $this->resolvedConfiguration = $containerBuilder->getParameterBag()->resolveValue($configs);
        }

        return $this->resolvedConfiguration;
    }
}
