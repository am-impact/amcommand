<?php
namespace Craft;

class AmCommand_TasksService extends BaseApplicationComponent
{
    /**
     * List task commands.
     *
     * @return array
     */
    public function taskCommands()
    {
        $commands = array(
            array(
                'name'    => Craft::t('Delete a task'),
                'more'    => true,
                'call'    => 'listTasks',
                'service' => 'amCommand_tasks'
            ),
            array(
                'name'    => Craft::t('Delete all tasks'),
                'warn'    => true,
                'call'    => 'deleteAllTasks',
                'service' => 'amCommand_tasks'
            ),
            array(
                'name'    => Craft::t('Delete all failed tasks'),
                'warn'    => true,
                'call'    => 'deleteAllFailedTasks',
                'service' => 'amCommand_tasks'
            ),
            array(
                'name'    => Craft::t('Delete all tasks by type'),
                'more'    => true,
                'call'    => 'listTaskTypes',
                'service' => 'amCommand_tasks'
            ),
            array(
                'name'    => Craft::t('Delete pending tasks'),
                'warn'    => true,
                'call'    => 'deletePendingTasks',
                'service' => 'amCommand_tasks'
            ),
            array(
                'name'    => Craft::t('Delete running task'),
                'warn'    => true,
                'call'    => 'deleteRunningTask',
                'service' => 'amCommand_tasks'
            ),
            array(
                'name'    => Craft::t('Restart failed tasks'),
                'warn'    => true,
                'call'    => 'restartFailedTasks',
                'service' => 'amCommand_tasks'
            )
        );
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
        $commands = array();

        // Find tasks
        $tasks = craft()->tasks->getAllTasks();
        if (! $tasks) {
            craft()->amCommand->setReturnMessage(Craft::t('There are no tasks at the moment.'));
        }
        else {
            foreach ($tasks as $task) {
                $commands[] = array(
                    'name'    => $task->getDescription(),
                    'warn'    => true,
                    'call'    => 'deleteTask',
                    'service' => 'amCommand_tasks',
                    'vars'    => array(
                        'taskId' => $task->id
                    )
                );
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
        $commands = array();

        // Find task types
        $taskTypes = array();
        $tasks = craft()->tasks->getAllTasks();
        if (! $tasks) {
            craft()->amCommand->setReturnMessage(Craft::t('There are no tasks at the moment.'));
        }
        else {
            foreach ($tasks as $task) {
                if (! isset($taskTypes[ $task->type ])) {
                    $taskTypes[ $task->type ] = true;
                    $commands[] = array(
                        'name'    => $task->type,
                        'warn'    => true,
                        'call'    => 'deleteAllTasksByType',
                        'service' => 'amCommand_tasks',
                        'vars'    => array(
                            'taskType' => $task->type
                        )
                    );
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
        $result = craft()->tasks->deleteTaskById($variables['taskId']);
        if ($result === true) {
            craft()->amCommand->deleteCurrentCommand();
            craft()->amCommand->setReturnMessage(Craft::t('Task deleted.'));
        }
        else {
            craft()->amCommand->setReturnMessage(Craft::t('Couldn’t delete task.'));
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
        $tasks = craft()->tasks->getAllTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                craft()->tasks->deleteTaskById($task->id);
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
            ->where(array('and', 'level = 0', 'status = :status'), array(':status' => TaskStatus::Error))
            ->queryAll();

        if ($tasks) {
            foreach ($tasks as $task) {
                craft()->tasks->deleteTaskById($task['id']);
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
        $tasks = craft()->db->createCommand()
            ->select('*')
            ->from('tasks')
            ->where('type = :type', array(':type' => $variables['taskType']))
            ->queryAll();

        if ($tasks) {
            foreach ($tasks as $task) {
                craft()->tasks->deleteTaskById($task['id']);
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
        $tasks = craft()->tasks->getPendingTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                craft()->tasks->deleteTaskById($task->id);
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
        $task = craft()->tasks->getRunningTask();
        if (! $task) {
            craft()->amCommand->setReturnMessage(Craft::t('There is no running task at the moment.'));
        }
        else {
            if (craft()->tasks->deleteTaskById($task->id) === true) {
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
        $tasks = craft()->tasks->getAllTasks();
        if ($tasks) {
            foreach ($tasks as $task) {
                if ($task->status == TaskStatus::Error) {
                    craft()->tasks->rerunTaskById($task->id);
                }
            }
        }

        return true;
    }
}
