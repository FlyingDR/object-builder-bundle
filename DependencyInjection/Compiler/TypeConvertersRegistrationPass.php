<?php

namespace Flying\ObjectBuilderBundle\DependencyInjection\Compiler;

use Flying\ObjectBuilder\ObjectBuilder;
use Flying\ObjectBuilder\TypeConverter\TypeConverterInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to assign type converters to object builder
 */
class TypeConvertersRegistrationPass implements CompilerPassInterface
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
        $resolvers = $container->findTaggedServiceIds('flying.object_builder.type_converter');
        $references = [];
        foreach ($resolvers as $id => $resolver) {
            $definition = $container->getDefinition($id);
            if (!class_implements($definition->getClass(), TypeConverterInterface::class)) {
                throw new \RuntimeException(sprintf('Object builder type converter "%s" should implement TypeConverterInterface', $definition->getClass()));
            }
            $references[] = new Reference($id);
        }
        $container->getDefinition(ObjectBuilder::class)->setArgument('$converters', $references);
    }
}
