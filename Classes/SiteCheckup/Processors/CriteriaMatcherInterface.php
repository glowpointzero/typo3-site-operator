<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

interface CriteriaMatcherInterface {

    /**
     * @param array $successCriteria
     * @param array $contents
     * @param string|null $failedCriterionName
     * @param string|null $failedCriterionValue
     * @param string|null $failedCriterionComparisonValue
     * @return mixed
     */
    function matchCriteria(
        array $successCriteria,
        array $contents,
        string &$failedCriterionName = null,
        string &$failedCriterionValue = null,
        string &$failedCriterionComparisonValue = null
    );
}
