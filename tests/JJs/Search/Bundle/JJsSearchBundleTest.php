<?php

namespace JJs\Search\Bundle;

use JJs\Search\CriteriaConverterInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Search Bundle Test
 *
 * Tests that the search bundle is correctly registering itself and the
 * container extensions.
 */
class JJsSearchBundleTest extends TestCase
{
    /**
     * Tests the bundle registration within a symfony kernel
     *
     * @return ContainerInterface
     */
    public function testBundleRegistration()
    {
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', true]);
        $kernel
            ->expects($this->once())
            ->method('registerBundles')
            ->will($this->returnValue([new JJsSearchBundle()]));
        $kernel->boot();

        $container = $kernel->getContainer();

        return $container;
    }

    /**
     * Tests that the criteria converter service is correctly registered in the 
     * dependency injection container.
     *
     * @param ContainerInterface $container Container interface
     * 
     * @depends testBundleRegistration
     * 
     * @return CriteriaConverterInterface
     */
    public function testCriteriaConverterService(ContainerInterface $container)
    {
        $criteriaConverter = $container->get('search.criteria.converter');

        $this->assertInstanceOf('JJs\Search\CriteriaConverterInterface', $criteriaConverter, 'service `search.criteria.converter` must implement the correct interface');

        return $criteriaConverter;
    }

    /**
     * Tests that the criteria param converter is correctly registered in the
     * dependency injection container.
     * 
     * @param ContainerInterface $container Container interface
     * 
     * @depends testBundleRegistration
     * 
     * @return ParamConverterInterface
     */
    public function testCriteriaParamConverterService(ContainerInterface $container)
    {
        $paramConverter = $container->get('search.criteria.param_converter');

        $this->assertInstanceOf('Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface', $paramConverter, 'service `search.criteria.param_converter` must implement the correct interface');

        return $paramConverter;
    }
}
