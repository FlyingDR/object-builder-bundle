<?php

namespace Flying\ObjectBuilderBundle\DependencyInjection\Compiler;

use Flying\ObjectBuilder\TargetProvider\TargetProviderInterface;
use Flying\ObjectBuilder\ObjectBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to assign target providers to object builder
 */
class TargetProvidersRegistrationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws ServiceNotFoundException
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ObjectBuilder::class)) {
            return;
        }
        $providers = $container->findTaggedServiceIds('flying.object_builder.target_provider');
        $references = [];
        foreach ($providers as $id => $provider) {
            $definition = $container->getDefinition($id);
            if (!class_implements($definition->getClass(), TargetProviderInterface::class)) {
                throw new \RuntimeException(sprintf('Object builder target provider "%s" should implement TargetProviderInterface', $definition->getClass()));
            }
            $references[] = new Reference($id);
        }
        $container->getDefinition(ObjectBuilder::class)->setArgument('$providers', $references);
    }
}
