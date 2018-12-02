<?php
namespace Glowpointzero\SiteOperator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Core\Environment;

class AbstractCommand extends Command
{
    const COMMAND_DESCRIPTION = '';
    
    const CONFIGURATION_REQUIRED = true;
    const TYPO3_CORE_REQUIRED = true;
    
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    
    /**
     *
     * @var Environment
     */
    protected $environment;

    /**
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    protected $io;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fileSystem;

    /**
     * @var string
     */
    protected $configurationFilePath = '';
    
    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var array
     */
    protected $siteConfigurations = [];
    

    /**
     * {@inheritdoc}
     */
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
        $this->io->note('Running ' . $this->getName() . ' ...');
        $this->fileSystem = new \Symfony\Component\Filesystem\Filesystem();
        
        if ($this::TYPO3_CORE_REQUIRED && !$this->typo3CoreIsAvailable()) {
            $this->io->error('TYPO3 doesn\'t seem to be installed yet.');
            exit();
        }

        if ($this::CONFIGURATION_REQUIRED) {
            $this->loadSiteConfigurations();
        }

        if ($this::CONFIGURATION_REQUIRED && !$this->loadConfiguration()) {
            exit();
        }
        
        if ($this::CONFIGURATION_REQUIRED && !$this->validateConfigurationForTheCurrentCommand()) {
            exit();
        }
    }

    /**
     * Configure the current command => set up
     * some local properties.
     */
    protected function configure()
    {
        $this->fileSystem = new \Symfony\Component\Filesystem\Filesystem();
        $this->setDescription($this::COMMAND_DESCRIPTION);
    }

    /**
     * Loads all site configurations into the
     * ->siteConfigurations property (by site identifier).
     */
    protected function loadSiteConfigurations()
    {
        $siteFinder = new \TYPO3\CMS\Core\Site\SiteFinder();
        $allSites = $siteFinder->getAllSites();
        /** @var \TYPO3\CMS\Core\Site\Entity\Site $site */
        foreach ($allSites as $site) {
            $this->siteConfigurations[$site->getIdentifier()] = $site->getConfiguration();
        }
    }

    /**
     * Loads typo3-site-tools configuration into memory.
     *
     * @return boolean
     */
    protected function loadConfiguration()
    {
        $possibleConfigurationPaths = [
            Environment::getConfigPath() . '/typo3-site-operator.json',
            './typo3-site-operator.json',
            Environment::getLegacyConfigPath() . '/typo3-site-operator.json'
        ];
        $configurationFile = '';
        
        foreach ($possibleConfigurationPaths as $configurationPath) {
            if ($this->fileSystem->exists($configurationPath)) {
                $configurationFile = $configurationPath;
                break;
            }
        }
        if (empty($configurationFile)) {
            $this->io->error(
                sprintf(
                    'No configuration file found under any of the paths (%s).',
                    implode(', ', $possibleConfigurationPaths)
                )
            );
            return false;
        }
        
        $configuration = json_decode(file_get_contents($configurationFile), true);
        if (!is_array($configuration)) {
            $this->io->error(
                sprintf(
                    'Configuration (%s) could not be loaded. Malformatted json?',
                    $configurationFile
                )
            );
            return false;
        }
        
        if (!isset($configuration['constants']) || !is_array($configuration['constants']) || empty($configuration['constants'])) {
            $this->io->warning(
                sprintf(
                    'The configuration (%s) doesn\'t contain a "constants" section.'
                    . ' This will most probably at some point. Fix it - or don\'t.',
                    $configurationFile
                )
            );
        }
        
        $this->configurationFilePath;
        $this->configuration = array_merge_recursive($this->configuration, $configuration);
        
        return true;
    }
    
    /**
     * Validates the current configuration array ($this->configuration)
     * making sure we have everything to execute the current command.
     */
    protected function validateConfigurationForTheCurrentCommand()
    {
        // This should be implemented in the concreate (non-abstract) command classes
        
        return true;
    }
    
    /**
     * Issues an error/warning message for invalid configuration and references
     * the source file name.
     *
     * @param array $validationIssues
     * @param type $sourceFileName
     */
    public function issueConfigurationValidationWarning(array $validationIssues, $sourceFileName)
    {
        array_unshift(
            $validationIssues,
            sprintf('The configuration loaded from %s does not seem to validate!', $sourceFileName)
        );
        $this->io->warning($validationIssues);
    }
    
    /**
     * Checks, whether TYPO3 has been installed / is available yet.
     * 
     * @todo Make this a bit more sophisticated, if possible. :)
     * @return bool
     */
    public function typo3CoreIsAvailable()
    {
        try {
            $confPath = Environment::getLegacyConfigPath();
        } catch (Exception $exception) {
            return false;
        }
        
        if (!$this->fileSystem->exists($confPath . '/LocalConfiguration.php')) {
            return false;
        }
        if (!$this->fileSystem->exists($confPath . '/PackageStates.php')) {
            return false;
        }
        return true;
    }
}
