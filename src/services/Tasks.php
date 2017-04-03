<?php
/**
 * Command plugin for Craft CMS 3.x
 *
 * Command palette in Craft; Because you can
 *
 * @link      http://www.am-impact.nl
 * @copyright Copyright (c) 2017 a&m impact
 */

namespace amimpact\command\services;

use amimpact\command\Command;

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
                'name'    => Craft::t('command', 'Delete a task'),
                'more'    => true,
                'call'    => 'listTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command', 'Delete all tasks'),
                'warn'    => true,
                'call'    => 'deleteAllTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command', 'Delete all failed tasks'),
                'warn'    => true,
                'call'    => 'deleteAllFailedTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command', 'Delete all tasks by type'),
                'more'    => true,
                'call'    => 'listTaskTypes',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command', 'Delete pending tasks'),
                'warn'    => true,
                'call'    => 'deletePendingTasks',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command', 'Delete running task'),
                'warn'    => true,
                'call'    => 'deleteRunningTask',
                'service' => 'tasks'
            ],
            [
                'name'    => Craft::t('command', 'Restart failed tasks'),
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
        $commands = [];

        $tasks = Craft::$app->tasks->getAllTasks();
        if (! $tasks) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'There are no tasks at the moment.'));
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
        $commands = [];
        $taskTypes = [];

        $tasks = Craft::$app->tasks->getAllTasks();
        if (! $tasks) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'There are no tasks at the moment.'));
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
        if (! isset($variables['taskId'])) {
            return false;
        }
        $result = Craft::$app->tasks->deleteTaskById($variables['taskId']);
        if ($result === true) {
            Command::$plugin->general->deleteCurrentCommand();
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'Task deleted.'));
        } else {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'Couldn’t delete task.'));
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
        $tasks = Craft::$app->tasks->getAllTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                Craft::$app->tasks->deleteTaskById($task->id);
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
        $tasks = craft()->db->createCommand()
            ->select('*')
            ->from('tasks')
            ->where(['and', 'level = 0', 'status = :status'], [':status' => Task::STATUS_ERROR])
            ->queryAll();

        if ($tasks) {
            foreach ($tasks as $task) {
                Craft::$app->tasks->deleteTaskById($task['id']);
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
        if (! isset($variables['taskType'])) {
            return false;
        }

        $tasks = craft()->db->createCommand()
            ->select('*')
            ->from('tasks')
            ->where('type = :type', [':type' => $variables['taskType']])
            ->queryAll();

        if ($tasks) {
            foreach ($tasks as $task) {
                Craft::$app->tasks->deleteTaskById($task['id']);
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
        $tasks = Craft::$app->tasks->getPendingTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                Craft::$app->tasks->deleteTaskById($task->id);
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
        $task = Craft::$app->tasks->getRunningTask();
        if (! $task) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'There is no running task at the moment.'));
        }
        else {
            if (Craft::$app->tasks->deleteTaskById($task->id) === true) {
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
        $tasks = Craft::$app->tasks->getAllTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                if ($task->status == Task::STATUS_ERROR) {
                    Craft::$app->tasks->rerunTaskById($task->id);
                }
            }
        }

        return true;
    }
}
