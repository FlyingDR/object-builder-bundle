<?php

namespace Flying\ObjectBuilderBundle\DependencyInjection\Compiler;

use Flying\ObjectBuilder\ObjectBuilder;
use Flying\ObjectBuilder\TargetProvider\TargetProviderInterface;
use Flying\ObjectBuilder\TypeConverter\TypeConverterInterface;
use Flying\ObjectBuilder\ValueAssigner\ValueAssignerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * Compiler pass to put all default object builder handlers implementations into container
 */
class DefaultHandlersRegistrationPass implements CompilerPassInterface
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
        // Register object builder in container
        $builderDefinition = new Definition();
        $builderDefinition
            ->setClass(ObjectBuilder::class)
            ->setPublic(true);
        $container->setDefinition(ObjectBuilder::class, $builderDefinition);
        $container->setAlias('flying.object_builder', ObjectBuilder::class);

        // Try to find all default handlers for object builder and register them too
        $finder = new Finder();
        $finder
            ->files()
            ->in(\dirname((new \ReflectionClass($builderDefinition->getClass()))->getFileName()))
            ->name('*.php');
        $tagsMap = [
            TargetProviderInterface::class => 'flying.object_builder.target_provider',
            TypeConverterInterface::class  => 'flying.object_builder.type_converter',
            ValueAssignerInterface::class  => 'flying.object_builder.value_assigner',
        ];
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $fqcn = $this->getFqcn($file->getPathname());
            $reflection = new \ReflectionClass($fqcn);
            if (!$reflection->isInstantiable()) {
                continue;
            }
            $tags = [];
            foreach ($tagsMap as $interface => $tag) {
                if ($reflection->implementsInterface($interface)) {
                    $tags[$tag] = [];
                }
            }
            if (!empty($tags)) {
                $definition = new Definition($reflection->getName());
                $definition->setPublic(false);
                $definition->setTags($tags);
                $container->setDefinition($reflection->getName(), $definition);
            }
        }
    }

    /**
     * Get FQCN by given file path
     *
     * @see    https://stackoverflow.com/a/7153391/2633956
     * @author https://stackoverflow.com/users/492901/netcoder
     *
     * @param string $path
     * @return string
     */
    protected function getFqcn(string $path): string
    {
        $fp = fopen($path, 'rb');
        $class = $namespace = $buffer = '';
        $i = 0;
        while (!$class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 512);
            $level = error_reporting(0);
            $tokens = token_get_all($buffer);
            error_reporting($level);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            $count = \count($tokens);
            for (; $i < $count; $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\' . $tokens[$j][1];
                        } else {
                            if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                break;
                            }
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS || $tokens[$i][0] === T_INTERFACE) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }
        return ltrim($namespace, '\\') . '\\' . $class;
    }
}
