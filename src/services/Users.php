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
use craft\elements\User;
use craft\helpers\UrlHelper;

class Users extends Component
{
    /**
     * @var User
     */
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
    public function editUsers(): array
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
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'Besides your account, no other account could be found.'));
        }

        return $commands;
    }

    /**
     * List of users you're able to delete.
     *
     * @return array
     */
    public function deleteUsers(): array
    {
        // Gather commands
        $commands = [];

        // Find available users
        $users = User::find()
            ->limit(null)
            ->status(null)
            ->all();
        foreach ($users as $user) {
            if ($this->_currentUser && $this->_currentUser->id !== $user->id) {
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
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'Besides your account, no other account could be found.'));
        }

        return $commands;
    }

    /**
     * Delete a user.
     *
     * @param array $variables
     *
     * @return bool
     * @throws \Throwable
     */
    public function deleteUser(array $variables = []): bool
    {
        // Do we have the required information?
        if (! isset($variables['userId'])) {
            return false;
        }

        // Delete user!
        $success = Craft::$app->getElements()->deleteElementById($variables['userId']);
        if ($success) {
            CommandPalette::$plugin->general->deleteCurrentCommand();
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('app', 'User deleted.'));
        }
        else {
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('app', 'Couldn’t delete “{name}”.', ['name', $variables['userId']]));
        }

        return $success;
    }

    /**
     * List of users you're able to login as.
     *
     * @return array
     */
    public function loginUsers(): array
    {
        // Gather commands
        $commands = [];

        // Find available users
        $users = User::find()
            ->limit(null)
            ->status(null)
            ->all();
        foreach ($users as $user) {
            if ($this->_currentUser && $this->_currentUser->id !== $user->id) {
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
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('app', 'Besides your account, no other account could be found.'));
        }

        return $commands;
    }

    /**
     * Login as user.
     *
     * @param array $variables
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function loginByUser(array $variables = []): bool
    {
        // Do we have the required information?
        if (! isset($variables['userId'])) {
            return false;
        }

        // Login by user
        if (Craft::$app->getUser()->loginByUserId($variables['userId'])) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Logged in.'));
            CommandPalette::$plugin->general->setReturnMessage(Craft::t('command-palette', 'Login as user'));

            // Redirect
            if (Craft::$app->getUser()->can('accessCp')) {
                CommandPalette::$plugin->general->setReturnUrl(UrlHelper::cpUrl('dashboard'));
            }
            else {
                CommandPalette::$plugin->general->setReturnUrl(UrlHelper::siteUrl(''));
            }

            return true;
        }

        // Login failed
        CommandPalette::$plugin->general->setReturnMessage(Craft::t('app', 'There was a problem impersonating this user.'));
        Craft::info($this->_currentUser->username . ' tried to log in using userId: '.$variables['userId'].' but something went wrong.', __METHOD__);

        return false;
    }
}
