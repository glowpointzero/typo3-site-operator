<?php
namespace Glowpointzero\SiteOperator\SiteCheckup\Processors;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Registry;

class SchedulerProcessor extends AbstractSiteCheckupProcessor {

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $lastSeenMaximumMinutes = $this->getArgument('lastSeenMaximumMinutes') ?: 60;
        $lastSeenMinimumTimestamp = time()-60*$lastSeenMaximumMinutes;

        /** @var Registry $registry */
        $coreRegistry = GeneralUtility::makeInstance(Registry::class);
        $schedulerLastRun = $coreRegistry->get('tx_scheduler', 'lastRun', []);
        $endTime = isset($schedulerLastRun['end']) ? $schedulerLastRun['end'] : 0;
        if ($endTime < $lastSeenMinimumTimestamp) {
            $this->io->error(sprintf('It seems like no cron jobs ran for at least %s minutes.', $lastSeenMaximumMinutes));
            return false;
        }
        return true;
    }
}
