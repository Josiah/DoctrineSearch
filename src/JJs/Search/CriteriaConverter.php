<?php

namespace JJs\Search;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Comparison;

/**
 * Request Criteria Converter
 *
 * Converts a parameter bag into a set of collection criteria based on the
 * configuration of the class.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class CriteriaConverter implements CriteriaConverterInterface
{
    /**
     * Order parameter name
     * 
     * @var string
     */
    protected $orderParam = '-order';

    /**
     * First result parameter
     * 
     * @var string
     */
    protected $firstResultParam = '-first';

    /**
     * Last result parameter
     * 
     * @var string
     */
    protected $maxResultsParam = '-max';

    /**
     * Returns the order parameter
     * 
     * @return string
     */
    protected function getOrderParam()
    {
        return $this->orderParam;
    }

    /**
     * Returns the first result parameter
     * 
     * @return string
     */
    protected function getFirstResultParam()
    {
        return $this->firstResultParam;
    }

    /**
     * Returns the max results parameter
     * 
     * @return string
     */
    protected function getMaxResultsParam()
    {
        return $this->maxResultsParam;
    }

    /**
     * Returns the operator array map
     *
     * This array is used to map standard operators to specialized equivalents
     * for the comparison of arrays.
     * 
     * @return array
     */
    protected function getOperatorArrayMap()
    {
        return [
            Comparison::EQ => Comparison::IN,
            Comparison::NEQ => Comparison::NIN,
        ];
    }

    /**
     * Returns the operator modifier map
     * 
     * @return array
     */
    protected function getOperatorModifierMap()
    {
        return [
            '!' => Comparison::NEQ,
            '>' => Comparison::GTE,
            '<' => Comparison::LTE,
            '*' => Comparison::CONTAINS,
        ];
    }

    /**
     * Constructs a new set of criteria
     * 
     * @param Expression $expression  Criteria expression
     * @param array      $orderings   Orderings
     * @param int|null   $firstResult First result
     * @param int|null   $maxResults  Max results
     * 
     * @return Criteria
     */
    protected function createCriteria(Expression $expression = null, array $orderings = null, $firstResult = null, $maxResults = null)
    {
        return new Criteria($expression, $orderings, $firstResult, $maxResults);
    }

    /**
     * Converts an array of parameters into a set of criteria
     *
     * @param array $params Parameters to convert
     * 
     * @api
     * @return Criteria
     */
    public function convert(array $params)
    {
        // Generate the condition expression for the parameters
        $expression = $this->generateExpression($params);

        // Generate the orderings from the parameters
        $orderings = $this->generateOrder($params);

        // Determine the paging information from the parameters
        $firstResult = $this->determineFirstResult($params);
        $maxResults = $this->determineMaxResults($params);

        // Creates a set of criteria matching the parameters
        $criteria = $this->createCriteria($expression, $orderings, $firstResult, $maxResults);

        // Return the generated criteria
        return $criteria;
    }

    /**
     * Generates the condition expression based on the specified parameters
     * 
     * @param array $params Parameters
     * 
     * @return Expression|null
     */
    protected function generateExpression(array $params)
    {
        // Iterate over the parameters and generate each parameter expression
        $conditions = [];
        foreach ($params as $param => $value) {
            // Only generate conditions for condition parameters
            if (!$this->isConditionParameter($param)) {
                continue;
            }

            // Generate the condition expression
            $conditions[] = $this->generateCondition($param, $value);
        }

        // Return null when there are no conditions for the conjunctive
        // expression
        if (empty($conditions)) {
            return null;
        }

        // Return a conjunctive expression containing the conditions
        return new CompositeExpression(CompositeExpression::TYPE_AND, $conditions);
    }

    /**
     * Result order generator
     * 
     * Generates the result ordering information from the parameters
     *
     * @param array $params Criteria parameters
     * 
     * @return array|null
     */
    protected function generateOrder(array $params)
    {
        $orderKey = $this->getOrderParam();

        // When there is no ordering informaiton in the parameters null will be
        // returned.
        if (!array_key_exists($orderKey, $params)) {
            return null;
        }

        // The order parameter will be formatted as a associative array with the
        // key representing the field, and the value representing the order.
        $order = $params[$orderKey];

        // Orderings should be uppercase
        $order = array_map('strtoupper', $order);

        // Each value in the order array should be checked to see if it is an
        // an ascending or descending result ordering. Other values are
        // automatically disguarded.
        $order = array_filter($order, function ($direction) {
            return $direction === Criteria::ASC || $direction === Criteria::DESC;
        });

        // Resulting order array is returned
        return $order;
    }

    /**
     * First result determination
     * 
     * Determines which result should be returned first from a set of search
     * parameters.
     * 
     * @param array $params Search parameters
     * 
     * @return int|null
     */
    protected function determineFirstResult(array $params)
    {
        return $this->converterToInteger($params, $this->getFirstResultParam());
    }

    /**
     * Determines the maximum number of results which should be returned
     * according to the specified search parameters.
     * 
     * @param array $params Search parameters
     * 
     * @return int|null
     */
    protected function determineMaxResults(array $params)
    {
        return $this->converterToInteger($params, $this->getMaxResultsParam());
    }

    /**
     * Integer parameter conversion
     *
     * Converts a parameter to an integer or returns the default value. The
     * parameter is identified by the specified key.
     * 
     * @param array  $params  Parameters
     * @param string $key     Parameter key
     * @param mixed  $default Default value
     * 
     * @return int
     */
    protected function converterToInteger(array $params, $key, $default = null)
    {
        // Return the default if the parameters don't contain the key
        if (!array_key_exists($key, $params)) {
            return $default;
        }

        // Retrieve the value from the parameters
        $value = $params[$key];

        // Return the default if the parameter is an empty string
        if (strlen($value) === 0) {
            return $default;
        }

        // Return the default if the parameter is not only digits
        if (!ctype_digit($params[$key])) {
            return $default;
        }

        // Return the actual value as an integer
        return intval($value, 10);
    }

    /**
     * Generates an expression for a specific condition parameter
     * 
     * @param string $field Condition field
     * @param string $value Condition value
     * 
     * @return Expression 
     */
    protected function generateCondition($field, $value)
    {
        // Check the field suffix against the operator map to see if it is
        // suffixed with a recognised operator modifier.
        $operator = Comparison::EQ;
        foreach ($this->getOperatorModifierMap() as $mod => $op) {
            $pos = 0 - strlen($mod);

            // Do nothing unless the modifier was appended to the field
            if (substr($field, $pos) !== $mod) {
                continue;
            }

            // Set the operator accordingly where the modifier was appened to 
            // the field
            $field = substr($field, 0, $pos);
            $operator = $op;
        }

        // Special treatment is given to arrays
        if (is_array($value)) {
            $arrayMap = $this->getOperatorArrayMap();
            if (array_key_exists($operator, $arrayMap)) {
                $operator = $arrayMap[$operator];
            }
        }

        // Generate the expression
        return new Comparison($field, $operator, $value);
    }

    /**
     * Indicates whether the specified parameter is a condition
     * 
     * @param string $param Condition parameter
     * 
     * @return boolean - `true` if the parameter is a conditional parameter;
     *                   `false` otherwise.
     */
    protected function isConditionParameter($param)
    {
        // Ensure that the parameter isn't reserved for use as the ordering,
        // first result or last result parameter
        if ($this->isSpecialParameter($param)) {
            return false;
        }

        // Condition parameters must begin with a letter
        if (!ctype_alpha(substr($param, 0, 1))) {
            return false;
        }

        // It is most probably a condition parameter
        return true;
    }

    /**
     * Indicates whether a parameter name has special meaning
     *
     * Special meaning parameters include:
     *   - Ordering parameter
     *   - First result paramter
     *   - Last result parameter
     * 
     * @param string $param Parameter name
     * 
     * @return boolean
     */
    protected function isSpecialParameter($param)
    {
        // Order parameter is special
        if ($param === $this->getOrderParam()) {
            return true;
        }

        // First result parameter is special
        if ($param === $this->getFirstResultParam()) {
            return true;
        }

        // Max results parameter is special
        if ($param === $this->getMaxResultsParam()) {
            return true;
        }

        // Not a special parameter
        return false;
    }
}
