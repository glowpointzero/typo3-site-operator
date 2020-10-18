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
use Glowpointzero\SiteOperator\Utility\StringUtility;
use GuzzleHttp\Psr7\Response;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HttpResponseProcessor extends AbstractSiteCheckupProcessor implements CriteriaMatcherInterface {

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        /**
         * @var string $siteIdentifier
         * @var Site $site
         */
        foreach ($this->generateUrlsBySitesAndLanguages($this->getArgument('location')) as $url) {

            $this->io->sayVerbosely(sprintf('Response validation for url "%s"', $url), null, 1);

            $report = [];

            $content = GeneralUtility::getUrl($url, 1, null, $report);
            $statusCode = null;

            if (isset($report['http_code'])) {
                $statusCode = $report['http_code'];
            }

            if (isset($report['exception'])) {
                /** @var Response $response */
                $response = $report['exception']->getResponse();
                $content = $response->getBody();
                $statusCode = $response->getStatusCode();
            }

            $failedCriterionName = '';
            $failedCriterionValue = '';
            $failedCriterionComparedContent = '';

            $criteriaMatches = $this->matchCriteria(
                $this->getArgument('successCriteria'),
                [
                    'statusCode' => $statusCode,
                    'content' => $content
                ],
                $failedCriterionName,
                $failedCriterionValue,
                $failedCriterionComparedContent
            );

            if (!$criteriaMatches) {
                $this->messageCollector->addError(
                    sprintf(
                        'The value checked (%s) did not match the "%s" criterion (which is set to "%s").',
                        $failedCriterionValue,
                        $failedCriterionName,
                        $failedCriterionComparedContent
                    )
                );
                continue;
            }
        }

        $severity = $this->messageCollector->getMostSevereMessageLevel() ?: AbstractCommand::STATUS_SUCCESS;
        return $severity;
    }

    /**
     * @param string $relativeLocation
     * @return array
     */
    protected function generateUrlsBySitesAndLanguages(string $relativeLocation)
    {
        $urls = [];
        foreach ($this->affectedSites as $siteIdentifier => $site) {
            /** @var SiteLanguage $language */
            foreach ($this->affectedLanguagesBySite[$siteIdentifier] as $language) {
                $urls[] = $language->getBase() . $relativeLocation;
            }
        }
        return $urls;
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
        foreach ($successCriteria as $criterion)
        {
            $statusCodeMatches = false;
            if (isset($criterion['statusCode'])) {

                $statusCodeMatches = StringUtility::stringsMatch($contents['statusCode'], $criterion['statusCode']);

                if ($this->io->isVerbose()) {
                    $this->io->startProcess(sprintf('Status code (expected "%s")', $criterion['statusCode']), 2);
                    $processEndStatus = $statusCodeMatches ? AbstractCommand::STATUS_SUCCESS : AbstractCommand::STATUS_ERROR;
                    $this->io->endProcess('', $processEndStatus);
                }

                if (!$statusCodeMatches) {
                    $failedCriterionName = 'statusCode';
                    $failedCriterionValue =  $criterion['statusCode'];
                    $failedCriterionComparisonValue = $contents['statusCode'];

                }
            }

            $contentMatches = false;
            if (isset($criterion['content'])) {
                $contentMatches = StringUtility::stringsMatch($contents['content'], $criterion['content']);

                if ($this->io->isVerbose()) {
                    $this->io->startProcess(
                        sprintf(
                            'Content (expected "%s")',
                            StringUtility::createExcerpt($criterion['content'], 30)
                        ),
                        2
                    );
                    $processEndStatus = $contentMatches ? AbstractCommand::STATUS_SUCCESS : AbstractCommand::STATUS_ERROR;
                    $this->io->endProcess('', $processEndStatus);
                }

                if (!$contentMatches) {
                    $failedCriterionName = 'content';
                    $failedCriterionValue =  $criterion['content'];
                    $failedCriterionComparisonValue = StringUtility::createExcerpt($contents['content'], 20);
                }
            }

            // Note that both content and status code must fail to match
            // as criteria in the same group are combined with 'OR'.
            if (!$contentMatches && !$statusCodeMatches) {
                return false;
            }
            $failedCriterionName = '';
            $failedCriterionValue = '';
            $failedCriterionComparisonValue = '';
        }
        return true;
    }

}
