<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

use Glowpointzero\SiteOperator\Console\MessageCollector;
use Glowpointzero\SiteOperator\Console\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractSiteCheckupProcessor implements SiteCheckupProcessorInterface {

    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var bool
     */
    protected $requiresSitesAndLanguages = true;

    /**
     * @var InputInterface
     */
    protected $inputInterface;

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var MessageCollector
     */
    protected $messageCollector;

    /**
     * @var array
     */
    protected $affectedSites = [];

    /**
     * @var array
     */
    protected $affectedLanguagesBySite = [];

    /**
     * {@inheritdoc}
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
        $this->detectAndSetAffectedSitesAndLanguages();
    }

    /**
     * {@inheritdoc}
     */
    public function setInputInterface(InputInterface $input)
    {
        $this->inputInterface = $input;
    }

    /**
     * {@inheritdoc}
     */
    public function setSymfonyStyle(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * @param MessageCollector $messageCollector
     */
    public function setMessageCollector(MessageCollector &$messageCollector)
    {
        $this->messageCollector = $messageCollector;
    }

    /**
     * Resolves a list of sites that will be affected/checked
     * by the current processor. May or may not be used,
     * depending on the processor. Interprets the 'sites'
     * array in the siteCheckup entry.
     */
    public function detectAndSetAffectedSitesAndLanguages()
    {
        if (!$this->requiresSitesAndLanguages) {
            return;
        }
        /** @var SiteFinder $siteFinder */
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $this->affectedSites = $siteFinder->getAllSites();

        if (isset($this->configuration['sitesAndLanguages'])) {
            $this->affectedSites = [];
            foreach ($this->configuration['sitesAndLanguages'] as $siteIdentifier => $languageUids) {
                $site = $siteFinder->getSiteByIdentifier($siteIdentifier);
                $this->affectedSites[$siteIdentifier] = $site;

                $affectedLanguages = [];
                foreach ($site->getAllLanguages() as $language) {
                    if (in_array($language->getLanguageId(), $languageUids) || count($languageUids) === 0) {
                        $affectedLanguages[] = $language;
                        $affectedLanguagesStrings[] = sprintf('%s (%s)', $language->getTitle(), $language->getLanguageId());
                    }
                }

                $this->affectedLanguagesBySite[$siteIdentifier] = $affectedLanguages;
            }
        }

        foreach ($this->affectedSites as $siteIdentifier => $site) {
            if (!isset($this->affectedLanguagesBySite[$siteIdentifier])) {
                $this->affectedLanguagesBySite[$siteIdentifier] = [];
            }
            if (count($this->affectedLanguagesBySite[$siteIdentifier]) === 0) {
                $this->affectedLanguagesBySite[$siteIdentifier] = $site->getAllLanguages();
            }

            if ($this->io->isVerbose()) {
                $this->io->abortCurrentProcess();
                $this->io->say(sprintf('Checkup will run for the site "%s" in these languages:', $siteIdentifier), null, 1);
                /** @var SiteLanguage $language */
                foreach ($this->affectedLanguagesBySite[$siteIdentifier] as $language) {
                    $this->io->say(sprintf('%s (%s)', $language->getTitle(), $language->getLanguageId()), null, 2);
                }
            }
        }
    }

    /**
     * @param string $argumentName
     * @return mixed
     */
    protected function getArgument(string $argumentName)
    {
        if (!isset($this->configuration['arguments'])) {
            return null;
        }
        if (!isset($this->configuration['arguments'][$argumentName])) {
            return null;
        }
        return $this->configuration['arguments'][$argumentName];
    }

     /**
     * {@inheritdoc}
     */
    public function run()
    {
        $this->io->error('Replace this default "run" method implementation.');
    }
}
