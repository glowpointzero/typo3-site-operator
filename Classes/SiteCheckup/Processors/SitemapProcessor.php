<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class SitemapProcessor extends HttpResponseProcessor {

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        foreach ($this->generateUrlsBySitesAndLanguages($this->getArgument('location')) as $url) {
            $this->io->comment(sprintf('Check sitemap located under %s.', $url));

            $xml = $this->loadUrlAsXML($url);
            if (!$xml) {
                $this->io->error('Could not load contents as xml.');
                continue;
            }

            $sitemapsXMLs = $this->resolveSitemapUrls($xml);
            foreach ($sitemapsXMLs as $sitemap) {
                $numberOrUrls = 0;
                foreach ($sitemap->url as $url) {
                    if (parse_url($url)) {
                        $numberOrUrls++;
                    }
                }
            }

            if ($numberOrUrls === 0) {
                $this->io->error('No urls found.');
                continue;
            }
            $this->io->success(sprintf('%s urls detected.', $numberOrUrls));
        }
        return true;
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
