<?php
namespace Glowpointzero\SiteOperator\Command;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Glowpointzero\SiteOperator\Utility\FileSystemUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class InstallScheduledTasksCommand extends AbstractCommand
{
    const COMMAND_DESCRIPTION = 'Loads scheduled tasks files and adds them '
        . 'to the scheduler in the current TYPO3 installation.';
    
    /**
     * List of all properties used to identify an already registered
     * task per task type.
     */
    const TASK_IDENTIFIER_PROPERTIES = [
        \TYPO3\CMS\Scheduler\Task\IpAnonymizationTask::class => ['table'],
        \TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class => ['table', 'allTables']
    ];
    
    /**
     * Holds all currently registered tasks, will be populated on
     * first call to $this->getAllRegisteredTasks and extended when
     * adding/persisting tasks to the DB, that don't exist yet.
     *
     * @var array
     */
    protected $allRegisteredTasks = [];

    /**
     * @inheritdoc
     */
    protected function validateConfigurationForTheCurrentCommand()
    {
        $validationIssues = [];
        
        $tasksSources = @$this->configuration['scheduledTasksSourcePaths'];
        if (!is_array($tasksSources)) {
            $validationIssues[] = 'The "scheduledTasksSourcePaths" section could not be resolved to an array.';
        }

        if (count($validationIssues) > 0) {
            $this->issueConfigurationValidationWarning($validationIssues, $this->configurationFilePath);
            return false;
        }
        return true;
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return integer
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->typo3SchedulerIsLoaded()) {
            $this->io->error('The TYPO3 scheduler extension is not loaded. No scheduled tasks can be registered.');
            return;
        }

        $tasks = $this->loadConfiguredTasks();
        $this->registerScheduledTasks($tasks);
        return 0;
    }
    
    /**
     * Checks, whether the TYPO3 scheduler extension is
     * loaded at all. @todo - This usually needs some
     * TYPO3 / TYPO3 console bootstrapping!
     * 
     * @return bool
     */
    protected function typo3SchedulerIsLoaded()
    {
        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler');
    }
    
    /**
     * @return array
     */
    protected function loadConfiguredTasks()
    {
        $configuredTasks = [];
        
        foreach ($this->configuration['scheduledTasksSourcePaths'] as $taskSourceFilePath) {
            $taskSourceFileAbsolute = FileSystemUtility::resolvePath($taskSourceFilePath, $this->configurationFilePath);
            if (!$taskSourceFileAbsolute) {
                $this->io->error(sprintf('Scheduled tasks file %s does not exist.', $taskSourceFilePath));
                continue;
            }

            $newTasks = include $taskSourceFileAbsolute;
            if (!is_array($newTasks)) {
                $this->io->error(sprintf('Scheduled tasks file %s does not return an array.', $taskSourceFileAbsolute));
                continue;
            }
            $configuredTasks = array_merge($configuredTasks, $newTasks);
        }
        
        if (count($this->configuration['scheduledTasksSourcePaths']) > 0 && count($configuredTasks) === 0) {
            $this->io->caution(
                sprintf(
                    'No tasks found in any of the referenced files (%s).',
                    implode(', ', $this->configuration['scheduledTasksSourcePaths'])
                )
            );
            return;
        }
        
        return $configuredTasks;
    }
    
    /**
     * @param array $tasks
     */
    protected function registerScheduledTasks(array $tasks)
    {        
        $this->io->note(sprintf('Registering %s scheduled tasks...', count($tasks)));
                
        /** @var Scheduler $scheduler */
        $scheduler = $this->objectManager->get(Scheduler::class);
        
        /** @var AbstractTask $task */
        foreach ($tasks as $task) {
            
            if (!$task instanceof AbstractTask) {
                $taskErrorDescription = '(unknown)';
                if (is_object($task)) {
                    $taskErrorDescription = $task->getTaskClassName();
                }
                if (is_string($task)) {
                    $taskErrorDescription = $task;
                }
                $this->io->error('Task "%s" is not a Scheduler tasks instance.');
                sleep(0.5);
                continue;
            }

            $this->io->note('Registering task "' . $task->getTaskClassName() . '"');
            sleep(0.5);

            $similarTask = $this->getSameRegisteredTask($task);
            
            if ($similarTask instanceof AbstractTask) {
                $this->io->notice(
                    sprintf('A task like this is already registered (uid %s)', $similarTask->getTaskUid())
                );
                continue;
            }

            // Set default task group, if none is set
            if ($task->getTaskGroup() === null) {
                $task->setTaskGroup(0);
            }
            $scheduler->addTask($task);
            $this->allRegisteredTasks[] = $task;
            
            $this->io->success(sprintf('Task %s registered.', $task->getTaskClassName()));
        }

        sleep(0.5);
    }
    
    
    /**
     * Checks whether an instance of a given task is already registered. Compares
     * the class name (obviously) as well as the registered identifier properties
     * (self:TASK_IDENTIFIER_PROPERTIES) of the given task vs any persisted one.
     *
     * @param AbstractTask $task
     * @throws \TYPO3\CMS\Extbase\Exception
     * @return AbstractTask|bool
     */
    protected function getSameRegisteredTask(AbstractTask $task)
    {
        $allRegisteredTasks = $this->getAllRegisteredTasks();
        
        /** @var \TYPO3\CMS\Scheduler\Task\AbstractTask $registeredTask */
        foreach ($allRegisteredTasks as $registeredTask) {
            
            $taskClassName = $registeredTask->getTaskClassName();
            if ($taskClassName !== $task->getTaskClassName()) {
                continue;
            }
            $identifiesViaProperties = array_key_exists($taskClassName, self::TASK_IDENTIFIER_PROPERTIES);
            if (!$identifiesViaProperties) {
                return $registeredTask;
            }

            // Compare properties of the task objects as defined by self::TASK_IDENTIFIER_PROPERTIES
            $rawTaskProperties = get_object_vars($task);
            $rawRegisteredTaskProperties = get_object_vars($registeredTask);

            foreach (self::TASK_IDENTIFIER_PROPERTIES[$taskClassName] as $propertyName) {
                if ($rawTaskProperties[$propertyName] !== $rawRegisteredTaskProperties[$propertyName]) {
                    continue 2;
                }
            }
            
            return $registeredTask;
        }
        
        return false;
    }
    
    /**
     * @return array List of task objects
     */
    protected function getAllRegisteredTasks()
    {
        if (count($this->allRegisteredTasks)) {
            return $this->allRegisteredTasks;
        }
        $this->allRegisteredTasks = $this
            ->objectManager
            ->get(Scheduler::class)
            ->fetchTasksWithCondition('', true);

        return $this->allRegisteredTasks;
    }
}
