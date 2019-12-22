<?php
namespace Glowpointzero\SiteOperator\Command;

use Glowpointzero\SiteOperator\Console\MessageCollector;
use Glowpointzero\SiteOperator\SiteCheckup\Processors\SiteCheckupProcessorInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;

class SiteCheckupCommand extends AbstractCommand
{
    const COMMAND_DESCRIPTION = 'Runs all the defined tests on the current instance.';

    /** @var MessageCollector */
    protected $messageCollector;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->notice(sprintf('Running in application context "%s".', Environment::getContext()->__toString()));
        $this->messageCollector = new MessageCollector();

        $checkups = $this->configuration['siteCheckup'];
        if (count($checkups) < 1) {
            $this->io->error('No site checkups configured.');
            return;
        }

        foreach ($checkups as $checkupIdentifier => $checkupConfiguration) {

            $this->io->startProcess(sprintf('Site checkup: %s', $checkupIdentifier));

            $checkupStatus = $this->processCheckup(
                $checkupIdentifier,
                $checkupConfiguration,
                $input
            );

            $this->io->endProcess('', $checkupStatus);
            // If the 'verbose' option is active, we expect subprocesses to have
            // output various messages, so we force a new line at the end of a checkup.
            if ($this->io->isVerbose()) {
                $this->io->newLine();
            }
        }

        $this->io->newLine();
        if (!count($this->messageCollector->getMessages())) {
            return;
        }

        // Output any collected, more detailed messages
        $this->io->notice('Notice these messages generated during the site checkup:');
        foreach ($this->messageCollector->getMessages() as $message) {
            $this->io->say(
                $this->messageCollector->renderMessage($message),
                $message['severity']
            );
        }
    }

    /**
     * Processes one single entry in the site checkup list.
     *
     * @param string $identifier
     * @param array $configuration
     * @param InputInterface $input
     * @return int
     */
    protected function processCheckup(string $identifier, array $configuration, InputInterface $input)
    {
        if (!class_exists($configuration['processor'])) {
            $this->io->error(sprintf('Processor class "%s" could not be found / has not been autoloaded.', $configuration['processor']));
            return false;
        }

        /** @var SiteCheckupProcessorInterface $checkup */
        $checkup = new $configuration['processor']();
        $checkup->setInputInterface($input);
        $checkup->setSymfonyStyle($this->io);
        $checkup->setConfiguration($configuration);
        $checkup->setIdentifier($identifier);

        $processorMessageCollector = new MessageCollector();
        $checkup->setMessageCollector($processorMessageCollector);

        $status = $checkup->run();

        if (count($processorMessageCollector->getMessages())) {
            $this->messageCollector->addMessage(
                sprintf('Messages for checkup "%s":', $identifier),
                $processorMessageCollector->getMostSevereMessageLevel()
            );
            $this->messageCollector->mergeMessagesFrom($processorMessageCollector, 1);
        }

        return $status;

    }
}
