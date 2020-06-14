<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Glowpointzero\SiteOperator\Command\AbstractCommand;
use Glowpointzero\SiteOperator\Utility\ArrayUtility;
use Glowpointzero\SiteOperator\Utility\StringUtility;

class VariableProcessor extends AbstractSiteCheckupProcessor implements CriteriaMatcherInterface {

    /**
     * {@inheritdoc}
     */
    protected $requiresSitesAndLanguages = false;

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
            $this->messageCollector->addError(sprintf(
                'The value (%s) gotten from the variable "%s" did not match the defined criteria ("%s").',
                $failedCriterionValue,
                $failedCriterionName,
                $failedCriterionComparedContent
            ));
            return AbstractCommand::STATUS_ERROR;
        }

        return AbstractCommand::STATUS_SUCCESS;
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
        foreach ($successCriteria as $criteriaGroupIndex => $criteriaGroup)
        {
            if ($this->io->isVerbose()) {
                $this->io->startProcess(sprintf('Checking variables in group %s', $criteriaGroupIndex), 1);
            }

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

            if ($this->io->isVerbose()) {
                $messageSeverity = $criteriaGroupMatches ? AbstractCommand::STATUS_SUCCESS : AbstractCommand::STATUS_ERROR;
                $message = $criteriaGroupMatches ? '' : sprintf('%s failed', $failedCriterionName);
                $this->io->endProcess($message, $messageSeverity);
            }

            if (!$criteriaGroupMatches) {
                return false;
            }
        }

        return true;
    }
}
