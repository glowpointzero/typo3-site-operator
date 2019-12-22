<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

use Glowpointzero\SiteOperator\Command\AbstractCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Registry;

class SchedulerProcessor extends AbstractSiteCheckupProcessor {

    /**
     * {@inheritdoc}
     */
    protected $requiresSitesAndLanguages = false;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        if ($this->io->isVerbose()) {
            $this->io->startProcess(sprintf('Retrieving last scheduler run'), 1);
        }
        $lastSeenMaximumMinutes = $this->getArgument('lastSeenMaximumMinutes') ?: 60;
        $lastSeenMinimumTimestamp = time()-60*$lastSeenMaximumMinutes;

        /** @var Registry $registry */
        $coreRegistry = GeneralUtility::makeInstance(Registry::class);
        $schedulerLastRun = $coreRegistry->get('tx_scheduler', 'lastRun', []);
        $endTime = isset($schedulerLastRun['end']) ? $schedulerLastRun['end'] : 0;

        $lastSeen = $endTime ? date('Y-m-d H:i:s', $endTime) : '(never)';
        $lastSeenMessage = sprintf('last run ended %s', $lastSeen);

        $checkupSucceeded = $endTime >= $lastSeenMinimumTimestamp;
        $checkupStatus = $checkupSucceeded ? AbstractCommand::STATUS_SUCCESS : AbstractCommand::STATUS_ERROR;

        if ($this->io->isVerbose()) {
            $this->io->endProcess($lastSeenMessage, $checkupStatus, 1);
        }

        if (!$checkupSucceeded) {
            $this->messageCollector->addError(sprintf('It seems like no cron jobs ran for at least %s minutes.', $lastSeenMaximumMinutes));
        }

        return $checkupStatus;
    }
}
