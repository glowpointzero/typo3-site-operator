<?php
namespace Glowpointzero\SiteOperator\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateStaticResourcesCommand extends AbstractCommand
{
    const COMMAND_DESCRIPTION = 'Generates static resources as defined'
        . ' by the configuration (p.e. images).';
    
    const TYPO3_CORE_REQUIRED = false;
    
    /**
     * @var array
     */
    protected $resourcesGeneratorConfiguration = [];
    
    /**
     * @inheritdoc
     */
    public function validateConfigurationForTheCurrentCommand()
    {
        $validationIssues = [];

        $resources = @$this->configuration['generatedResources'];
        if (!is_array($resources)) {
            $validationIssues[] = 'The "generatedResources" section could not be resolved to an array.';
        }

        if (count($validationIssues) > 0) {
            $this->issueConfigurationValidationWarning($validationIssues, $this->configurationFilePath);
            return false;
        }
        return true;
    }
    
    /**
     * Configure this command (add options).
     */
    public function configure()
    {
        parent::configure();
        $this->addOption(
            'all',
            NULL,
            \Symfony\Component\Console\Input\InputOption::VALUE_NONE,
            'If set, all files will be regenerated, even the ones that seem up to date.'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (count($this->configuration['generatedResources']) === 0) {
            $this->io->warning('No resources configured to generate.');
            return;
        }
        
        $this->io->note(sprintf('Generating %s static resources...', count($this->configuration['generatedResources'])));
        
        foreach ($this->configuration['generatedResources'] as $resourceTargetPath => $generatorConfiguration)
        {
            $resourceTargetPathExcerpt = substr($resourceTargetPath, -40);
            if (strlen($resourceTargetPath) > strlen($resourceTargetPathExcerpt)) {
                $resourceTargetPathExcerpt = '(...)' . $resourceTargetPathExcerpt;
            }
            $this->io->startProcess(sprintf('Generating "%s"', $resourceTargetPathExcerpt));
            $sourcePath = './' . $generatorConfiguration['configuration']['source'];
            $fileExists = $this->fileSystem->exists($resourceTargetPath);
            $fileIsOutdated = true;

            if ($fileExists) {
                $fileIsOutdated = filemtime($resourceTargetPath) < filemtime($sourcePath);
            }

            if ($fileExists && !$fileIsOutdated && !$input->getOption('all')) {
                $this->io->endProcess('skipped', AbstractCommand::STATUS_INFO);
                if ($this->io->isVerbose()) {
                    $this->io->info(
                        sprintf(
                            'Skipping, as the target file seems up-to-date (last updated on %s. Source File: %s).',
                            date('d.m.Y H:i', filemtime($resourceTargetPath)),
                            date('d.m.Y H:i', filemtime($sourcePath))
                        )
                    );
                }
                continue;
            }
            
            $targetDirectoryExists = $this->fileSystem->exists(dirname($resourceTargetPath));

            if (!$targetDirectoryExists) {
                $this->fileSystem->mkdir(dirname($resourceTargetPath));
            }
            
            if (!isset($generatorConfiguration['generator']) || empty($generatorConfiguration['generator'])) {
                $this->io->endProcess('"generator" configuration node is missing.', AbstractCommand::STATUS_ERROR);
                continue;
            }
            
            if (!isset($generatorConfiguration['configuration']) || empty($generatorConfiguration['configuration'])) {
                $this->io->endProcess('Generator configuration missing.', AbstractCommand::STATUS_ERROR);
                continue;
            }
            
            $generatorMethodName = sprintf('generate%sResource', ucfirst(strtolower($generatorConfiguration['generator'])));

            if (!method_exists($this, $generatorMethodName)) {
                $this->io->endProcess(sprintf('Invalid generator method "%s".', $generatorConfiguration['generator']), AbstractCommand::STATUS_ERROR);
                continue;
            }
            
            $errorMessage = '';
            $resourceHasBeenGenerated = $this->$generatorMethodName(
                $generatorConfiguration['configuration'],
                $resourceTargetPath,
                $errorMessage
            );
            
            if (!$resourceHasBeenGenerated) {
                $this->io->endProcess($errorMessage, AbstractCommand::STATUS_ERROR);
                continue;
            }
            
            $this->io->endProcess();
        }
    }
    
    /**
     * @param array $configuration
     * @param string $targetPath
     * @param string $errorMessage
     * @return bool
     */
    protected function generateImageResource(array $configuration, $targetPath, &$errorMessage)
    {
        $executableFinder = new \Symfony\Component\Process\ExecutableFinder();
        $detectedMagickPath = $executableFinder->find('magick');
        
        if (!$detectedMagickPath
            && !$this->io->confirm('Could not detect "magick" (ImageMagick library). Try anyway?', false)) {
            $errorMessage = 'ImageMagick library not found.';
            return false;
        }
                
        $command = 'magick convert';
        foreach ($configuration['parameters'] as $parameterName => $parameterValue) {
            
            $command .= ' -' . $parameterName;
            if (!empty($parameterValue)) {
                $command .= ' "' . $parameterValue . '"';
            }
        }
        
        $targetFileExtension = substr($targetPath, strrpos($targetPath, '.')+1);
        $tempTargetFile = $targetPath . '.tmp.' . date('Y-m-d__H_i_s') . '.' . $targetFileExtension;
        $command .= ' "./' . $configuration['source'] . '" "./' . $tempTargetFile . '"';
        $returnValue = exec($command);
        
        if (!empty($returnValue)) {
            $this->io->notice($returnValue); // This should usually be empty.
        }
        
        if (!$this->fileSystem->exists($tempTargetFile) || filesize($tempTargetFile) === 0) {
            $errorMessage = 'The generated file seems to be empty. The final file will not be written.';
            $this->fileSystem->remove($tempTargetFile);
            return false;
        }
        
        $this->fileSystem->rename($tempTargetFile, $targetPath, true);
        return true;
    }
    
    /**
     * Generates any type of text file, reading an existing one
     * and replacing its placeholders.
     *
     * @param array $configuration
     * @param string $targetPath
     * @param string $errorMessage
     * @return bool
     */
    protected function generateTextResource(array $configuration, $targetPath, &$errorMessage)
    {
        $sourceContents = @file_get_contents($configuration['source']);
        if (!$sourceContents) {
            $errorMessage = sprintf('Could not read source file (%s).', $configuration['source']);
            return false;
        }
        
        $placeholderValues = [
            'siteConfiguration' => $this->siteConfigurations,
            'constants' => $this->configuration['constants']
        ];
        $targetContents = \Glowpointzero\SiteOperator\Utility\StringUtility::replacePlaceholders(
            $sourceContents,
            $placeholderValues
        );
        
        $this->fileSystem->dumpFile($targetPath, $targetContents);
        return true;
    }
}
