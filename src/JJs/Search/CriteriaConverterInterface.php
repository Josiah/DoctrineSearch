<?php

namespace JJs\Search;

use Doctrine\Common\Collections\Criteria;

/**
 * Criteria Converter
 *
 * Converts an array of parameters into a set of criteria.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
interface CriteriaConverterInterface
{
    /**
     * Converts an array of parameters into a set of criteria
     * 
     * @param array $parameters Parameters to convert
     * 
     * @api
     * @return Criteria
     */
    public function convert(array $parameters);
}
