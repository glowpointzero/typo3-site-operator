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
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SitemapProcessor extends HttpResponseProcessor {

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        foreach ($this->generateUrlsBySitesAndLanguages($this->getArgument('location')) as $sitemapUrl) {

            if ($this->io->isVerbose()) {
                $this->io->startProcess(sprintf('Sitemap validation for "%s"', $sitemapUrl), 1);
            }

            $xml = $this->loadUrlAsXML($sitemapUrl);
            if (!$xml) {
                $this->messageCollector->addError('Could not load contents as xml.');
                continue;
            }

            $numberOrUrls = 0;
            $sitemapsXMLs = $this->resolveSitemapUrls($xml);
            foreach ($sitemapsXMLs as $sitemap) {
                foreach ($sitemap->url as $url) {
                    if (parse_url($url)) {
                        $numberOrUrls++;
                    }
                }
            }

            if ($numberOrUrls === 0) {
                $this->messageCollector->addError('No urls found.');
                if ($this->io->isVerbose()) {
                    $this->io->endProcess('', AbstractCommand::STATUS_ERROR);
                }
                continue;
            }
            if ($numberOrUrls < 5) {
                $this->messageCollector->addWarning(sprintf(
                    'Only %s urls found resolving "%s" (this might be ok, but usually, there are more than 5).',
                    $numberOrUrls,
                    $sitemapUrl
                ));
                if ($this->io->isVerbose()) {
                    $this->io->endProcess('', AbstractCommand::STATUS_WARNING);
                }
                continue;
            }

            if ($this->io->isVerbose()) {
                $this->io->endProcess(sprintf('%s URLs', $numberOrUrls), AbstractCommand::STATUS_SUCCESS);
            }
        }

        $endStatus = $this->messageCollector->getMostSevereMessageLevel() ?: AbstractCommand::STATUS_SUCCESS;
        return $endStatus;
    }

    /**
     * Loads given URL and attempts to parse its
     * returned content into a SimpleXMLElement.
     *
     * @param string $location
     * @return bool|\SimpleXMLElement
     */
    protected function loadUrlAsXML(string $location)
    {
        $content = GeneralUtility::getUrl($location);
        $xml = new \SimpleXMLElement($content);

        if (!$xml) {
            return false;
        }
        return $xml;
    }

    /**
     * Expects a sitemap xml of some kind and will
     * resolve contained <sitemap> elements pointing
     * to the content.
     *
     * @param \SimpleXMLElement $xml
     * @return array|bool
     */
    protected function resolveSitemapUrls(\SimpleXMLElement $xml)
    {
        $sitemapXMLs = [];
        $listOfSitemaps = $xml->sitemap;
        if (!$listOfSitemaps) {
            return [];
        }

        /** @var \SimpleXMLElement $sitemap */
        foreach ($listOfSitemaps as $sitemap) {
            $sitemapLocation = $sitemap->loc;
            $loadedSitemap = $this->loadUrlAsXML($sitemapLocation);
            if (!$loadedSitemap) {
                $this->io->error(sprintf('Loading referenced sitemap "%s" failed.', $sitemapLocation));
                return false;
            }
            $sitemapXMLs[] = $loadedSitemap;
        }

        return $sitemapXMLs;
    }
}
