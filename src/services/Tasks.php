<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette\services;

use amimpact\commandpalette\CommandPalette;

use Craft;
use craft\base\Component;
use craft\base\Task;

class Tasks extends Component
{
    /**
     * List task commands.
     *
     * @return array
     */
    public function getTaskCommands()
    {
        $commands = [
            [
                'name'    => Craft::t('command-palette', 'Delete a task'),
                'more'    => true,
                'call'    => 'listTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command-palette', 'Delete all tasks'),
                'warn'    => true,
                'call'    => 'deleteAllTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command-palette', 'Delete all failed tasks'),
                'warn'    => true,
                'call'    => 'deleteAllFailedTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command-palette', 'Delete all tasks by type'),
                'more'    => true,
                'call'    => 'listTaskTypes',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command-palette', 'Delete pending tasks'),
                'warn'    => true,
                'call'    => 'deletePendingTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command-palette', 'Delete running task'),
                'warn'    => true,
                'call'    => 'deleteRunningTask',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command-palette', 'Restart failed tasks'),
                'warn'    => true,
                'call'    => 'restartFailedTasks',
                'service' => 'tasks'
            ]
        ];
        return $commands;
    }

    /**
     * Get all tasks.
     *
     * @return array
     */
    public function listTasks()
    {
        // Gather commands
        $commands = [];

        // Find tasks
        $tasks = Craft::$app->getTasks()->getAllTasks();
        if (! $tasks) {
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'There are no tasks at the moment.'));
        }
        else {
            foreach ($tasks as $task) {
                $commands[] = [
                    'name'    => $task->getDescription(),
                    'warn'    => true,
                    'call'    => 'deleteTask',
                    'service' => 'tasks',
                    'vars'    => [
                        'taskId' => $task->id
                    ]
                ];
            }
        }

        return $commands;
    }

    /**
     * Get all task types.
     *
     * @return array
     */
    public function listTaskTypes()
    {
        // Gather commands
        $commands = [];

        // Find task types
        $taskTypes = [];
        $tasks = Craft::$app->getTasks()->getAllTasks();
        if (! $tasks) {
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'There are no tasks at the moment.'));
        }
        else {
            foreach ($tasks as $task) {
                if (! isset($taskTypes[ $task->type ])) {
                    $taskTypes[ $task->type ] = true;
                    $commands[] = [
                        'name'    => $task->type,
                        'warn'    => true,
                        'call'    => 'deleteAllTasksByType',
                        'service' => 'tasks',
                        'vars'    => [
                            'taskType' => $task->type
                        ]
                    ];
                }
            }
        }

        return $commands;
    }

    /**
     * Delete a task.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function deleteTask($variables)
    {
        // Do we have the required information?
        if (! isset($variables['taskId'])) {
            return false;
        }

        // Delete task!
        $result = Craft::$app->getTasks()->deleteTaskById($variables['taskId']);
        if ($result === true) {
            CommandPalette::$plugin->general->deleteCurrentCommand();
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'Task deleted.'));
        }
        else {
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'Couldn’t delete task.'));
        }

        return $result ? true : false;
    }

    /**
     * Delete all tasks.
     *
     * @return bool
     */
    public function deleteAllTasks()
    {
        $tasksService = Craft::$app->getTasks();
        $tasks = $tasksService->getAllTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                $tasksService->deleteTaskById($task->id);
            }
        }

        return true;
    }

    /**
     * Delete all failed tasks.
     *
     * @return bool
     */
    public function deleteAllFailedTasks()
    {
        $tasks = (new Query())
            ->select('*')
            ->from(['{{%tasks}}'])
            ->where(['level' => 0, 'status' => Task::STATUS_ERROR])
            ->all();

        if ($tasks) {
            $tasksService = Craft::$app->getTasks();
            foreach ($tasks as $task) {
                $tasksService->deleteTaskById($task['id']);
            }
        }

        return true;
    }

    /**
     * Delete all tasks by type.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function deleteAllTasksByType($variables)
    {
        // Do we have the required information?
        if (! isset($variables['taskType'])) {
            return false;
        }

        // Delete all tasks!
        $tasks = (new Query())
            ->select('*')
            ->from(['{{%tasks}}'])
            ->where(['type' => $variables['taskType']])
            ->all();

        if ($tasks) {
            $tasksService = Craft::$app->getTasks();
            foreach ($tasks as $task) {
                $tasksService->deleteTaskById($task['id']);
            }
        }

        return true;
    }

    /**
     * Delete pending tasks.
     *
     * @return bool
     */
    public function deletePendingTasks()
    {
        $tasksService = Craft::$app->getTasks();
        $tasks = $tasksService->getPendingTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                $tasksService->deleteTaskById($task->id);
            }
        }

        return true;
    }

    /**
     * Delete running task.
     *
     * @return bool
     */
    public function deleteRunningTask()
    {
        $task = Craft::$app->getTasks()->getRunningTask();
        if (! $task) {
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'There is no running task at the moment.'));
        }
        else {
            if (Craft::$app->getTasks()->deleteTaskById($task->id) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Restart failed tasks.
     *
     * @return bool
     */
    public function restartFailedTasks()
    {
        $tasksService = Craft::$app->getTasks();
        $tasks = $tasksService->getAllTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                if ($task->status == Task::STATUS_ERROR) {
                    $tasksService->rerunTaskById($task->id);
                }
            }
        }

        return true;
    }
}
