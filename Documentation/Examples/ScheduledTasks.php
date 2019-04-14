<?php
use TYPO3\CMS\Core\Utility\GeneralUtility;

$tasks = [];

/***************************************************************
 *    Privacy-related tasks
 *    (anonymize IPs, garbage collector)
 ***************************************************************/
/** @var \TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask $sysLogGarbageCollectionTask */
$sysLogGarbageCollectionTask = GeneralUtility::makeInstance(\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class);
$sysLogGarbageCollectionTask->registerRecurringExecution(
    time(), // start timestamp
    0, // interval (seconds)
    0, // end timestamp
    false, // multiple executions allowed in parallel
    '0 1 * * 0' // minute (0 - 59)  hour (0 - 23)  day (1 - 31)  month (1 - 12)  weekday (0-6 = sunday-saturday)
);
$sysLogGarbageCollectionTask->table = 'sys_log';
$sysLogGarbageCollectionTask->numberOfDays = 365;

$tasks[] = $sysLogGarbageCollectionTask;

/** @var \TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask $sysHistoryGarbageCollectionTask */
$sysHistoryGarbageCollectionTask = GeneralUtility::makeInstance(\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class);
$sysHistoryGarbageCollectionTask->registerRecurringExecution(
    time(), // start timestamp
    0, // interval (seconds)
    0, // end timestamp
    false, // multiple executions allowed in parallel
    '0 1 * * 0' // minute (0 - 59)  hour (0 - 23)  day (1 - 31)  month (1 - 12)  weekday (0-6 = sunday-saturday)
);
$sysHistoryGarbageCollectionTask->table = 'sys_history';
$sysHistoryGarbageCollectionTask->numberOfDays = 365;

$tasks[] = $sysHistoryGarbageCollectionTask;

/** @var \TYPO3\CMS\Scheduler\Task\IpAnonymizationTask $anonymizeIpsTask */
$anonymizeIpsTask = GeneralUtility::makeInstance(\TYPO3\CMS\Scheduler\Task\IpAnonymizationTask::class);
$anonymizeIpsTask->registerRecurringExecution(
    time(), // start timestamp
    0, // interval (seconds)
    0, // end timestamp
    false, // multiple executions allowed in parallel
    '0 1 * * 0' // minute (0 - 59)  hour (0 - 23)  day (1 - 31)  month (1 - 12)  weekday (0-6 = sunday-saturday)
);
$anonymizeIpsTask->table = 'sys_log';

$tasks[] = $anonymizeIpsTask;

/***************************************************************
 *    Return all tasks to the 'ScheduledTasksCommandController'
 ***************************************************************/
return $tasks;
