<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

use Glowpointzero\SiteOperator\Utility\ArrayUtility;
use Glowpointzero\SiteOperator\Utility\StringUtility;

class VariableProcessor extends AbstractSiteCheckupProcessor implements CriteriaMatcherInterface {

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $criteriaMatches = $this->matchCriteria(
            $this->getArgument('successCriteria'),
            [
                'GLOBALS' => $GLOBALS,
                '_SERVER' => $_SERVER,
                '_ENV' => $_ENV
            ],
            $failedCriterionName,
            $failedCriterionValue,
            $failedCriterionComparedContent
        );
        if (!$criteriaMatches) {
            $this->io->error(sprintf(
                'Failed. Criteria "%s" (%s) was not fulfilled by the value checked (%s).',
                $failedCriterionName,
                $failedCriterionValue,
                $failedCriterionComparedContent
            ));
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    function matchCriteria(
        array $successCriteria,
        array $contents,
        string &$failedCriterionName = null,
        string &$failedCriterionValue = null,
        string &$failedCriterionComparisonValue = null
    ) {
        foreach ($successCriteria as $criteriaGroup)
        {
            $criteriaGroupMatches = false;
            foreach ($criteriaGroup as $variablePath => $expectedVariableValue) {
                $explodedVariablePath = explode('|', $variablePath);
                $baseVariableName = array_shift($explodedVariablePath);

                $baseVariable = $$baseVariableName;
                if (isset($contents[$baseVariableName])) {
                    $baseVariable = $contents[$baseVariableName];
                }

                $nestedValue = ArrayUtility::getNestedArrayValue($baseVariable, $explodedVariablePath);
                $nestedValue = strval($nestedValue);

                $variableValueMatches = StringUtility::stringsMatch($nestedValue, $expectedVariableValue);
                if (!$variableValueMatches) {
                    $failedCriterionName = $variablePath;
                    $failedCriterionValue =  $expectedVariableValue;
                    $failedCriterionComparisonValue = $nestedValue;
                    continue;
                }

                $failedCriterionName = '';
                $failedCriterionValue = '';
                $failedCriterionComparisonValue = '';
                $criteriaGroupMatches = true;
                break;
            }

            if (!$criteriaGroupMatches) {
                return false;
            }
        }

        return true;
    }
}
