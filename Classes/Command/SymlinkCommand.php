<?php
namespace Glowpointzero\SiteOperator\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SymlinkCommand extends AbstractCommand
{
    const COMMAND_DESCRIPTION = 'Suggests and creates preconfigured symlinks.';
    const TYPO3_CORE_REQUIRED = false;

    /**
     * Add custom options to the command.
     */
    public function configure()
    {
        parent::configure();

        $this->addOption(
            'override-existing',
            'o',
            InputOption::VALUE_NONE,
            'If set, existing shortcuts will be overridden.'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuredSymlinks = $this->configuration['symlinks'];
        if (count($configuredSymlinks) < 1) {
            $this->io->warning('No symlinks configured.');
            return;
        }
        foreach ($configuredSymlinks as $symlinkTarget => $symlinkConfiguration) {
            $this->processSymlink(
                $symlinkTarget,
                $symlinkConfiguration,
                $input->getOption('override-existing')
            );
        }
    }

    /**
     * Process one single symlink.
     *
     * @param string $symlinkTarget
     * @param array $symlinkConfiguration
     * @param bool $overrideExisting
     * @return bool
     */
    protected function processSymlink(string $symlinkTarget, array $symlinkConfiguration, bool $overrideExisting)
    {
        $this->io->info(sprintf('Setting up symlink %s', $symlinkTarget));

        $targetExists = $this->fileSystem->exists($symlinkTarget);

        if ($targetExists && !is_link($symlinkTarget)) {
            $this->io->error(sprintf(
                'The target "%s" exists, but is not a symlink and will therefore not be touched.',
                $symlinkTarget
            ));
            return false;
        }

        if ($targetExists && !$overrideExisting) {
            $override = $this->io->confirm(sprintf('Target "%s" exists. Continue?', $symlinkTarget), false);
            if (!$override) {
                return false;
            }
        }

        $sources = $this->validateSourcesAndTarget($symlinkConfiguration, $symlinkTarget);
        if (!$sources) {
            return false;
        }

        $sourcePath = $this->chooseSource($sources, $symlinkTarget);
        if (!$sourcePath) {
            return false;
        }

        if ($targetExists) {
            $this->fileSystem->remove($symlinkTarget);
        }

        try {
            symlink(realpath($sourcePath), $symlinkTarget);
            $this->io->success(sprintf('Symlink "%s" created.', $symlinkTarget));
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Validates configured symlink source(s) and target paths.
     *
     * @param $symlinkConfiguration
     * @param $symlinkTarget
     * @return array|bool
     */
    protected function validateSourcesAndTarget($symlinkConfiguration, $symlinkTarget)
    {
        $symlinkTargetDirectory = dirname($symlinkTarget);

        if (!$this->fileSystem->exists($symlinkTargetDirectory)) {
            $this->io->error(
                sprintf('Target symlink parent directory "%s" doesn\'t exist.', $symlinkTargetDirectory)
            );
            return false;
        }

        $sources = [];
        if (isset($symlinkConfiguration['source'])) {
            $sources[] = $symlinkConfiguration['source'];
        }

        if (isset($symlinkConfiguration['sources']) && is_array($symlinkConfiguration['sources'])) {
            $sources = array_merge($sources, $symlinkConfiguration['sources']);
        }
        $sources = array_unique($sources);

        if (count($sources) < 1) {
            $this->io->error('No valid source paths found.', $symlinkTargetDirectory);
            return false;
        }

        return $sources;
    }

    /**
     * Detects / asks for the source file to be linked.
     *
     * @param array $sources
     * @param $symlinkTarget
     * @return bool|string
     */
    protected function chooseSource(array $sources, $symlinkTarget)
    {
        $sourcePath = $sources[0];

        if (count($sources) > 1) {
            $sourcePath = $this->io->choice(
                sprintf('Choose the file/directory path that should be linked to "%s".', $symlinkTarget),
                $sources
            );
        }

        if (!$this->fileSystem->exists($sourcePath)) {
            $this->io->error(sprintf('The source "%s" doesn\t exist.', $sourcePath));
            return false;
        }

        return $sourcePath;
    }
}
