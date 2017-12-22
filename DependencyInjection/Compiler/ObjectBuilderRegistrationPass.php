<?php

namespace Flying\ObjectBuilderBundle\DependencyInjection\Compiler;

use Flying\ObjectBuilder\Handler\HandlerInterface;
use Flying\ObjectBuilder\ObjectBuilder;
use Flying\ObjectBuilder\Registry\HandlersRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to register default implementation of object builder into container
 */
class ObjectBuilderRegistrationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \ReflectionException
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     * @throws ServiceNotFoundException
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(ObjectBuilder::class)) {
            return;
        }
        // Register object builder in container
        $builderDefinition = new Definition();
        $builderDefinition
            ->setClass(ObjectBuilder::class)
            ->setPublic(true);
        $container->setDefinition(ObjectBuilder::class, $builderDefinition);
        $container->setAlias('flying.object_builder', ObjectBuilder::class);

        // Create handlers registry and assign it with a builder
        if (!$container->hasDefinition(HandlersRegistry::class)) {
            $registryDefinition = new Definition();
            $registryDefinition
                ->setClass(HandlersRegistry::class)
                ->setPublic(true);
            $container->setDefinition(HandlersRegistry::class, $registryDefinition);
            $container->setAlias('flying.object_builder.registry', HandlersRegistry::class);
            $handlers = $container->findTaggedServiceIds('flying.object_builder.handler');
            $references = [];
            foreach ($handlers as $id => $handler) {
                $definition = $container->getDefinition($id);
                if (!class_implements($definition->getClass(), HandlerInterface::class)) {
                    throw new \RuntimeException(sprintf('Object builder handler "%s" should implement TargetProviderInterface', $definition->getClass()));
                }
                $references[] = new Reference($id);
            }
            $registryDefinition->setArgument('$handlers', $references);
        }

        // Assign handlers registry to the object builder
        $builderDefinition->setArgument('$handlers', new Reference(HandlersRegistry::class));
    }
}
