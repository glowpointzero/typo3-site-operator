<?php
namespace Glowpointzero\SiteOperator\Command;

use Glowpointzero\SiteOperator\SiteCheckup\Processors\SiteCheckupProcessorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SiteCheckupCommand extends AbstractCommand
{
    const COMMAND_DESCRIPTION = 'Runs all the defined tests on the current instance.';

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $checkups = $this->configuration['siteCheckup'];
        if (count($checkups) < 1) {
            $this->io->warning('No site checkups configured.');
            return;
        }
        foreach ($checkups as $checkupIdentifier => $checkupConfiguration) {
            $checkSuccessful = $this->processCheckup(
                $checkupIdentifier,
                $checkupConfiguration,
                $input
            );
            if ($checkSuccessful) {
                $this->io->success('This check succeeded.');
                continue;
            }
            $this->io->error('This check failed.');
        }
    }

    /**
     * Processes one single entry in the site checkup list.
     *
     * @param string $identifier
     * @param array $configuration
     * @param InputInterface $input
     * @return bool
     */
    protected function processCheckup(string $identifier, array $configuration, InputInterface $input)
    {
        $this->io->section(sprintf('Running checkup "%s"', $identifier));
        if (!class_exists($configuration['processor'])) {
            $this->io->error(sprintf('Processor class "%s" could not be found / has not been autoloaded.', $configuration['processor']));
            return false;
        }

        /** @var SiteCheckupProcessorInterface $checkup */
        $checkup = new $configuration['processor']();
        $checkup->setConfiguration($configuration);
        $checkup->setIdentifier($identifier);
        $checkup->setInputInterface($input);
        $checkup->setSymfonyStyle($this->io);

        return $checkup->run();

    }
}
