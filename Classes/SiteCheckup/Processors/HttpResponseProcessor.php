<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

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

            $this->io->comment(sprintf('Loading url "%s"... ', $url));
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
                $this->io->error(sprintf(
                    'Failed. Criteria "%s" (%s) was not fulfilled by the value checked (%s).',
                    $failedCriterionName,
                    $failedCriterionValue,
                    $failedCriterionComparedContent
                ));
                continue;
            }
        }
        return true;
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
                if (!$statusCodeMatches) {
                    $failedCriterionName = 'statusCode';
                    $failedCriterionValue =  $criterion['statusCode'];
                    $failedCriterionComparisonValue = $contents['statusCode'];
                }
            }

            $contentMatches = false;
            if (isset($criterion['content'])) {
                $contentMatches = StringUtility::stringsMatch($contents['content'], $criterion['content']);
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
