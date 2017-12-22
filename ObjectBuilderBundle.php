<?php

namespace Flying\ObjectBuilderBundle;

use Flying\ObjectBuilderBundle\DependencyInjection\Compiler\DefaultHandlersRegistrationPass;
use Flying\ObjectBuilderBundle\DependencyInjection\Compiler\TargetProvidersRegistrationPass;
use Flying\ObjectBuilderBundle\DependencyInjection\Compiler\TypeConvertersRegistrationPass;
use Flying\ObjectBuilderBundle\DependencyInjection\Compiler\ValueAssignersRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ObjectBuilderBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DefaultHandlersRegistrationPass());
        $container->addCompilerPass(new TargetProvidersRegistrationPass());
        $container->addCompilerPass(new TypeConvertersRegistrationPass());
        $container->addCompilerPass(new ValueAssignersRegistrationPass());
    }
}
