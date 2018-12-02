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
            'force',
            NULL,
            \Symfony\Component\Console\Input\InputOption::VALUE_NONE,
            'If set, all files will be overridden / regenerated without asking permission to override.'
        );
        $this->addOption(
            'outdated',
            NULL,
            \Symfony\Component\Console\Input\InputOption::VALUE_NONE,
            'Only outdated or non-existent resources will be (re)generated. Without asking for permission to override.'
        );

    }
    
    /**
     * @inheritdoc
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('outdated') && $input->getOption('force')) {
            $this->io->warning(
                'You\'ve activated options "force" and "outdated", which'
                . ' doesn\'t make sense as "outdated" will force-regenerate'
                . ' outdated resources anyway. Deactivating "force".'
            );
            $input->setOption('force', false);
        }
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
        
        $this->io->comment(sprintf('Generating %s static resources...', count($this->configuration['generatedResources'])));
        
        foreach ($this->configuration['generatedResources'] as $resourceTargetPath => $generatorConfiguration)
        {
            $this->io->comment(sprintf('Generating %s...', $resourceTargetPath));
            $sourcePath = './' . $generatorConfiguration['configuration']['source'];
            $fileExists = $this->fileSystem->exists($resourceTargetPath);
            $fileIsOutdated = true;

            if ($fileExists) {
                $fileIsOutdated = filemtime($resourceTargetPath) < filemtime($sourcePath);
            }

            if (!$fileIsOutdated && $input->getOption('outdated')) {
                $this->io->comment(
                    sprintf(
                        'Skipping, as the target file seems up-to-date (last updated on %s. Source File: %s).',
                        date('d.m.Y H:i', filemtime($resourceTargetPath)),
                        date('d.m.Y H:i', filemtime($sourcePath))
                    )
                );
                continue;
            }

            if ($fileExists && !$fileIsOutdated && !$input->getOption('force') && !$input->getOption('outdated')) {
                $overwriteCurrentFile = $this->io->confirm(
                    sprintf(
                        'Target resource %s exists. Overwrite?',
                        $resourceTargetPath
                    ),
                    false
                    );
                
                if (!$overwriteCurrentFile) {
                    continue;
                }
            }
            
            $targetDirectoryExists = $this->fileSystem->exists(dirname($resourceTargetPath));
            $createDirectory = $targetDirectoryExists || $input->getOption('force');
            if (!$targetDirectoryExists && !$createDirectory) {
                $createDirectory = $this->io->confirm(sprintf(
                    'Just to check: The target directory %s does not exist. Create and Continue?',
                    dirname($resourceTargetPath)
                    ), true);
            }
            
            if (!$createDirectory) {
                continue;
            }
            
            if (!$targetDirectoryExists) {
                $this->fileSystem->mkdir(dirname($resourceTargetPath));
            }
            
            if (!isset($generatorConfiguration['generator']) || empty($generatorConfiguration['generator'])) {
                $this->io->error('"generator" missing.');
                continue;
            }
            
            if (!isset($generatorConfiguration['configuration']) || empty($generatorConfiguration['configuration'])) {
                $this->io->error('Generator configuration missing.');
                continue;
            }
            
            $generatorMethodName = sprintf('generate%sResource', ucfirst(strtolower($generatorConfiguration['generator'])));
            
            if (!method_exists($this, $generatorMethodName)) {
                $this->io->error(sprintf('Invalid generator method "%s".', $generatorConfiguration['generator']));
                continue;
            }
            
            $errorMessage = '';
            $resourceHasBeenGenerated = $this->$generatorMethodName(
                $generatorConfiguration['configuration'],
                $resourceTargetPath,
                $errorMessage
            );
            
            if (!$resourceHasBeenGenerated) {
                $this->io->error($errorMessage);
                if (!$this->io->confirm('Continue?')) {
                    continue;
                }
            }
            
            $this->io->success(sprintf('Resource %s has been generated.', $resourceTargetPath));
            
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
            $this->io->comment($returnValue); // This should usually be empty.
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
            'constants' => $this->configuration['constants'],
            'applicationVersion' => \Glowpointzero\SiteOperator\ProjectInstance::getApplicationVersion(),
        ];
        $targetContents = \Glowpointzero\SiteOperator\Utility\StringUtility::replacePlaceholders(
            $sourceContents,
            $placeholderValues
        );
        
        $this->fileSystem->dumpFile($targetPath, $targetContents);
        return true;
    }
}
