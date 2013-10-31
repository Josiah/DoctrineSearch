<?php

namespace JJs\Search\Bundle\Request;

use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use JJs\Search\CriteriaConverterInterface;

/**
 * Criteria Parameter Converter
 *
 * Converts the request query string into criteria using the criteria
 * converter.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class CriteriaParamConverter implements ParamConverterInterface
{
    /**
     * Criteria converter
     *
     * Preforms the actual conversion from parameters into criteria
     * 
     * @var CriteriaConverterInterface
     */
    protected $criteriaConverter;

    /**
     * Constructs the criteria param converter
     * 
     * @param CriteriaConverterInterface $criteriaConverter Criteria converter
     */
    public function __construct(CriteriaConverterInterface $criteriaConverter)
    {
        $this->criteriaConverter = $criteriaConverter;
    }

    /**
     * Returns the criteria converter
     * 
     * @return CriteriaConverterInterface
     */
    protected function getCriteriaConverter()
    {
        return $this->criteriaConverter;
    }

    /**
     * Indicates whether this criteria param converter supports conversion to
     * the specified configuration interface.
     * 
     * @param ConfigurationInterface $config Converter configuration
     * 
     * @return bool
     */
    public function supports(ConfigurationInterface $config)
    {
        // Ensure that the configuration is a param converter configuration
        if (!$config instanceof ParamConverter) {
            return false;
        }

        // Ensure that the parameter class is the 'criteria' class
        if ($config->getClass() !== 'Doctrine\Common\Collections\Criteria') {
            return false;
        }

        // All checks passed, conversion configuration is supported
        return true;
    }

    /**
     * Converts the request into a criteria object according to the 
     * configuration
     * 
     * @param Request                $request Http request
     * @param ConfigurationInterface $config  Param converter configuration
     * 
     * @return bool
     */
    public function apply(Request $request, ConfigurationInterface $config)
    {
        // Ensure that this configuration is supported
        if (!$this->supports($config)) {
            return false;
        }

        // Convert the request into criteria
        $criteria = $this->convertRequest($request, $config);

        // Add the criteria to the request attributes using the parameter name
        // from the configuration
        $request->attributes->set($config->getName(), $criteria);

        // Parameter was correctly assigned
        return true;
    }

    /**
     * Converts the request into a critera object
     * 
     * @param Request        $request Http request
     * @param ParamConverter $config  Conversion configuration
     * 
     * @return Criteria
     */
    protected function convertRequest(Request $request, ParamConverter $config)
    {
        $converter = $this->getCriteriaConverter();

        // Return the converted criteria
        return $converter->convert($request->query->all());
    }
}
