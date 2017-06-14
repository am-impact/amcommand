<?php
namespace Craft;

class AmCommand_UsersService extends BaseApplicationComponent
{
    private $_currentUser;

    public function init()
    {
        $this->_currentUser = craft()->userSession->getUser();
    }

    /**
     * List of users you're able to edit.
     *
     * @return array
     */
    public function editUser()
    {
        // Gather commands
        $commands = array();

        // Find users
        $users = craft()->amCommand_elements->getElements(ElementType::User);
        if ($users) {
            foreach ($users as $user) {
                $commands[] = array(
                    'name' => $this->_getUserInfo($user),
                    'url'  => $this->_getCpEditUrl($user)
                );
            }
        }
        if (! count($commands)) {
            craft()->amCommand->setReturnMessage(Craft::t('Besides your account, no other account could be found.'));
        }

        return $commands;
    }

    /**
     * List of users you're able to delete.
     *
     * @return array
     */
    public function deleteUser()
    {
        // Gather commands
        $commands = array();

        // Find users
        $users = craft()->amCommand_elements->getElements(ElementType::User);
        foreach ($users as $user) {
            if ($this->_currentUser && $this->_currentUser->id != $user['id']) {
                $commands[] = array(
                    'name'    => $this->_getUserInfo($user),
                    'warn'    => true,
                    'call'    => 'deleteAnUser',
                    'service' => 'amCommand_users',
                    'vars'    => array(
                        'userId' => $user['id']
                    )
                );
            }
        }
        if (! count($commands)) {
            craft()->amCommand->setReturnMessage(Craft::t('Besides your account, no other account could be found.'));
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
    public function deleteAnUser($variables)
    {
        // Do we have the required information?
        if (! isset($variables['userId'])) {
            return false;
        }

        // Delete user!
        $user = craft()->users->getUserById($variables['userId']);
        $result = craft()->users->deleteUser($user);
        if ($result) {
            craft()->amCommand->deleteCurrentCommand();
            craft()->amCommand->setReturnMessage(Craft::t('User deleted.'));
        }
        else {
            craft()->amCommand->setReturnMessage(Craft::t('Couldn’t delete “{name}”.', array('name', $user->username)));
        }

        return $result;
    }

    /**
     * List of users you're able to login as.
     *
     * @return array
     */
    public function loginUser()
    {
        // Gather commands
        $commands = array();

        // Find users
        $users = craft()->amCommand_elements->getElements(ElementType::User);
        foreach ($users as $user) {
            if ($this->_currentUser && $this->_currentUser->id != $user['id']) {
                $commands[] = array(
                    'name'    => $this->_getUserInfo($user),
                    'warn'    => true,
                    'call'    => 'loginAsUser',
                    'service' => 'amCommand_users',
                    'vars'    => array(
                        'userId' => $user['id']
                    )
                );
            }
        }
        if (! count($commands)) {
            craft()->amCommand->setReturnMessage(Craft::t('Besides your account, no other account could be found.'));
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
    public function loginAsUser($variables)
    {
        // Do we have the required information?
        if (! isset($variables['userId'])) {
            return false;
        }

        // Login as user!
        if (craft()->userSession->loginByUserId($variables['userId'])) {
            craft()->userSession->setNotice(Craft::t('Logged in.'));
            craft()->amCommand->setReturnMessage(Craft::t('Login as user'));

            // Redirect
            if (craft()->userSession->getUser()->can('accessCp')) {
                craft()->amCommand->setReturnUrl(UrlHelper::getCpUrl('dashboard'));
            }
            else {
                craft()->amCommand->setReturnUrl(UrlHelper::getSiteUrl(''));
            }

            return true;
        }
        else {
            // We could not login..
            craft()->amCommand->setReturnMessage(Craft::t('There was a problem impersonating this user.'));
            Craft::log(craft()->userSession->getUser()->username . ' tried to log in using userId: '.$variables['userId'].' but something went wrong.', LogLevel::Error);

            return false;
        }
    }

    /**
     * Get user information.
     *
     * @param array $user
     *
     * @return array
     */
    private function _getUserInfo($user)
    {
        $userInfo = array();
        if ($user['firstName']) {
            $userInfo[] = $user['firstName'];
        }
        if ($user['lastName']) {
            $userInfo[] = $user['lastName'];
        }
        $userInfo[] = '(' . $user['email'] . ')';

        return implode(' ', $userInfo);
    }

    /**
     * Get CP edit URL.
     *
     * @return string|false
     */
    private function _getCpEditUrl($user)
    {
        if (! $this->_currentUser) {
            return false;
        }

        if ($this->_currentUser->id == $user['id']) {
            return UrlHelper::getCpUrl('myaccount');
        }
        else if (craft()->getEdition() == Craft::Client && ! $this->_currentUser->admin) {
            return UrlHelper::getCpUrl('clientaccount');
        }
        else if (craft()->getEdition() == Craft::Pro) {
            return UrlHelper::getCpUrl('users/'.$user['id']);
        }
        else {
            return false;
        }
    }
}
