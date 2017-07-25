<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\UrlHelper;

class Users extends Component
{
    private $_currentUser;

    public function init()
    {
        $this->_currentUser = Craft::$app->getUser()->getIdentity();
    }

    /**
     * List of users you're able to edit.
     *
     * @return array
     */
    public function editUsers()
    {
        // Gather commands
        $commands = [];

        // Find available users
        $users = User::find()
            ->limit(null)
            ->status(null)
            ->all();
        if ($users) {
            foreach ($users as $user) {
                $commands[] = [
                    'name' => trim($user->getFullName() . ' (' . $user->email . ')'),
                    'url'  => $user->getCpEditUrl(),
                ];
            }
        }
        if (! count($commands)) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'Besides your account, no other account could be found.'));
        }

        return $commands;
    }

    /**
     * List of users you're able to delete.
     *
     * @return array
     */
    public function deleteUsers()
    {
        // Gather commands
        $commands = [];

        // Find available users
        $users = User::find()
            ->limit(null)
            ->status(null)
            ->all();
        foreach ($users as $user) {
            if ($this->_currentUser && $this->_currentUser->id != $user->id) {
                $commands[] = [
                    'name'    => trim($user->getFullName() . ' (' . $user->email . ')'),
                    'warn'    => true,
                    'call'    => 'deleteUser',
                    'service' => 'users',
                    'vars'    => [
                        'userId' => $user->id
                    ]
                ];
            }
        }
        if (! count($commands)) {
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'Besides your account, no other account could be found.'));
        }

        return $commands;
    }

    /**
     * Delete a user.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function deleteUser($variables)
    {
        // Do we have the required information?
        if (! isset($variables['userId'])) {
            return false;
        }

        // Delete user!
        $success = Craft::$app->elements->deleteElementById($variables['userId']);
        if ($success) {
            Command::$plugin->general->deleteCurrentCommand();
            Command::$plugin->general->setReturnMessage(Craft::t('app', 'User deleted.'));
        }
        else {
            Command::$plugin->general->setReturnMessage(Craft::t('app', 'Couldn’t delete “{name}”.', ['name', $user->username]));
        }

        return $success;
    }

    /**
     * List of users you're able to login as.
     *
     * @return array
     */
    public function loginUsers()
    {
        // Gather commands
        $commands = [];

        // Find available users
        $users = User::find()
            ->limit(null)
            ->status(null)
            ->all();
        foreach ($users as $user) {
            if ($this->_currentUser && $this->_currentUser->id != $user->id) {
                $commands[] = [
                    'name'    => trim($user->getFullName() . ' (' . $user->email . ')'),
                    'warn'    => true,
                    'call'    => 'loginByUser',
                    'service' => 'users',
                    'vars'    => [
                        'userId' => $user->id
                    ]
                ];
            }
        }
        if (! count($commands)) {
            Command::$plugin->general->setReturnMessage(Craft::t('app', 'Besides your account, no other account could be found.'));
        }

        return $commands;
    }

    /**
     * Login as user.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function loginByUser($variables)
    {
        // Do we have the required information?
        if (! isset($variables['userId'])) {
            return false;
        }

        // Login by user
        if (Craft::$app->getUser()->loginByUserId($variables['userId'])) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Logged in.'));
            Command::$plugin->general->setReturnMessage(Craft::t('command', 'Login as user'));

            // Redirect
            if (Craft::$app->getUser()->can('accessCp')) {
                Command::$plugin->general->setReturnUrl(UrlHelper::cpUrl('dashboard'));
            }
            else {
                Command::$plugin->general->setReturnUrl(UrlHelper::siteUrl(''));
            }

            return true;
        }

        // Login failed
        Command::$plugin->general->setReturnMessage(Craft::t('app', 'There was a problem impersonating this user.'));
        Craft::info($this->_currentUser->username . ' tried to log in using userId: '.$variables['userId'].' but something went wrong.', __METHOD__);

        return false;
    }
}
