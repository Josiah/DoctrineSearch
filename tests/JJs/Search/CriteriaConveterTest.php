<?php

namespace JJs\Search;

use PHPUnit_Framework_TestCase as TestCase;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

/**
 * Criteria Conveter Test
 *
 * Tests the functionality of the criteria converter to ensure that it is
 * behaving as expected.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class CriteriaConverterTest extends TestCase
{
    /**
     * Tests the criteria converters ability to convert a set of search
     * parameters into search criteria.
     */
    public function testConvert()
    {
        $converter = new CriteriaConverter();
        $criteria = $converter->convert([
            'testa'  => 'abc',
            'testb!' => 'def',
            'testc<' => '10',
            'testd>' => '0',
            'teste*' => 'something',
            'testf'  => ['arr1', 'arr2'],
            'testg!' => ['arr3', 'arr4'],

            '-test' => 'invalid param',
            '-order' => [
                'abc' => 'asc',
                'foo' => 'bar',
            ],
            '-first' => '1',
            '-max'  => '100'
        ]);

        // The criteria should be a criteria object
        $this->assertInstanceOf('Doctrine\Common\Collections\Criteria', $criteria, 'generated criteria');

        $where = $criteria->getWhereExpression();

        // Where expression should be a composite expression
        $this->assertInstanceOf('Doctrine\Common\Collections\Expr\CompositeExpression', $where, 'where expression');

        $ex = $where->getExpressionList();

        $this->assertEquals(CompositeExpression::TYPE_AND, $where->getType());
        $this->assertCount(7, $ex, 'composite expression should contain 7 comparisons');

        $this->assertEquals('testa', $ex[0]->getField(), 'expression #1 field');
        $this->assertEquals(Comparison::EQ, $ex[0]->getOperator(), 'expression #1 operator');
        $this->assertEquals('abc', $ex[0]->getValue()->getValue(), 'expression #1 value');

        $this->assertEquals('testb', $ex[1]->getField(), 'expression #2 field');
        $this->assertEquals(Comparison::NEQ, $ex[1]->getOperator(), 'expression #2 operator');
        $this->assertEquals('def', $ex[1]->getValue()->getValue(), 'expression #2 value');

        $this->assertEquals('testc', $ex[2]->getField(), 'expression #3 field');
        $this->assertEquals(Comparison::LTE, $ex[2]->getOperator(), 'expression #3 operator');
        $this->assertEquals('10', $ex[2]->getValue()->getValue(), 'expression #3 value');

        $this->assertEquals('testd', $ex[3]->getField(), 'expression #4 field');
        $this->assertEquals(Comparison::GTE, $ex[3]->getOperator(), 'expression #4 operator');
        $this->assertEquals('0', $ex[3]->getValue()->getValue(), 'expression #4 value');

        $this->assertEquals('teste', $ex[4]->getField(), 'expression #5 field');
        $this->assertEquals(Comparison::CONTAINS, $ex[4]->getOperator(), 'expression #5 operator');
        $this->assertEquals('something', $ex[4]->getValue()->getValue(), 'expression #5 value');

        $this->assertEquals('testf', $ex[5]->getField(), 'expression #6 field');
        $this->assertEquals(Comparison::IN, $ex[5]->getOperator(), 'expression #6 operator');
        $this->assertEquals(['arr1', 'arr2'], $ex[5]->getValue()->getValue(), 'expression #6 value');

        $this->assertEquals('testg', $ex[6]->getField(), 'expression #7 field');
        $this->assertEquals(Comparison::NIN, $ex[6]->getOperator(), 'expression #7 operator');
        $this->assertEquals(['arr3', 'arr4'], $ex[6]->getValue()->getValue(), 'expression #7 value');

        $this->assertEquals(['abc' => 'ASC'], $criteria->getOrderings(), 'order by');

        $this->assertSame(1, $criteria->getFirstResult(), 'first result param');
        $this->assertSame(100, $criteria->getMaxResults(), 'max results param');
    }

    /**
     * Tests the conversion with no first result
     */
    public function testConvertEmpty()
    {
        $converter = new CriteriaConverter();
        $criteria = $converter->convert(['-max' => '']);

        $this->assertNull($criteria->getWhereExpression(), 'where expression');
        $this->assertNull($criteria->getOrderings(), 'orderings');
        $this->assertNull($criteria->getFirstResult(), 'first result');
        $this->assertNull($criteria->getMaxResults(), 'max results');
    }

    /**
     * Tests the conversion with non-integer paging
     */
    public function testConvertNonIntegerPaging()
    {
        $converter = new CriteriaConverter();
        $criteria = $converter->convert(['-first' => '0.1', '-max' => 'nine']);

        $this->assertNull($criteria->getFirstResult(), 'first result');
        $this->assertNull($criteria->getMaxResults(), 'max results');
    }
}
