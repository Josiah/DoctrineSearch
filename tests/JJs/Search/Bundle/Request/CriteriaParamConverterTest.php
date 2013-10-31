<?php

namespace JJs\Search\Bundle\Request;

use Doctrine\Common\Collections\Criteria;
use JJs\Search\CriteriaConverterInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Criteria Param Converter Test
 *
 * Tests that the request parameter converter operates as expected.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class CriteriaParamConverterTest extends TestCase
{
    /**
     * Tests that the criteria param converter correctly indicates support for
     * various configurations
     * 
     * @param ConfigurationInterface $config   Converter configuration
     * @param bool                   $expected Expected result
     *
     * @dataProvider dataSupports
     */
    public function testSupports(ConfigurationInterface $config, $expected)
    {
        $criteriaConverter = $this->getMock('JJs\Search\CriteriaConverterInterface');

        $paramConverter = new CriteriaParamConverter($criteriaConverter);

        if ($expected) {
            $this->assertTrue($expected, $paramConverter->supports($config), 'should be supported');
        } else {
            $this->assertFalse($expected, $paramConverter->supports($config), 'should not be supported');
        }
    }

    /**
     * @return array
     */
    public function dataSupports()
    {
        $mockCriteria = $this->getMock('Doctrine\Common\Collections\Criteria');

        return [
            [$this->getMock('Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface'), false],
            [new ParamConverter(['class' => get_class($mockCriteria)]), false],
            [new ParamConverter(['class' => 'Doctrine\Common\Collections\Criteria']), true],
        ];
    }

    /**
     * Tests that the criteria param converter is correctly applying the
     * parameter conversion to the request
     */
    public function testApply()
    {
        $criteria = new Criteria();
        $queryParams = ['test1' => 'abc', 'test2' => 'def'];

        $criteriaConverter = $this->getMock('JJs\Search\CriteriaConverterInterface');
        $criteriaConverter
            ->expects($this->once())
            ->method('convert')
            ->with($queryParams)
            ->will($this->returnValue($criteria));

        $request = new Request($queryParams);

        $config = new ParamConverter([
            'class' => 'Doctrine\Common\Collections\Criteria',
            'name' => 'criteria',
        ]);

        $paramConverter = new CriteriaParamConverter($criteriaConverter);
        $result = $paramConverter->apply($request, $config);

        $this->assertTrue($result, 'param converter should report that it has performed the conversion');

        $this->assertTrue($request->attributes->has('criteria'), "request should have a 'criteria' attribute");
        $this->assertSame($criteria, $request->attributes->get('criteria'), "request criteria should be same as returned by converter");
    }

    /**
     * Tests that the criteria param converter is failing when the configuration
     * is not supported.
     */
    public function testApplyFailure()
    {
        $criteriaConverter = $this->getMock('JJs\Search\CriteriaConverterInterface');
        $config = $this->getMock('Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface');

        $paramConverter = new CriteriaParamConverter($criteriaConverter);
        $result = $paramConverter->apply(new Request(), $config);

        $this->assertFalse($result, 'param converter should report that it failed to performed the conversion');
    }
}
