<?php
namespace Craft;

class AmCommand_UsersService extends BaseApplicationComponent
{
    /**
     * List of users you're able to edit.
     *
     * @return array
     */
    public function editUser()
    {
        $commands = array();
        $users = $this->_getUsers();
        foreach ($users as $user) {
            $userInfo = $this->_getUserInfo($user);
            $commands[] = array(
                'name' => $user->username,
                'info' => implode(' - ', $userInfo),
                'url'  => $user->getCpEditUrl()
            );
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
        $commands = array();
        $users = $this->_getUsers();
        foreach ($users as $user) {
            $userInfo = $this->_getUserInfo($user);
            if (! $user->isCurrent()) {
                $commands[] = array(
                    'name'    => $user->username,
                    'info'    => implode(' - ', $userInfo),
                    'warn'    => true,
                    'call'    => 'deleteAnUser',
                    'service' => 'amCommand_users',
                    'vars'    => array(
                        'userId' => $user->id
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
        if (! isset($variables['userId'])) {
            return false;
        }
        $user   = craft()->users->getUserById($variables['userId']);
        $result = craft()->users->deleteUser($user);
        if ($result) {
            craft()->amCommand->deleteCurrentCommand();
            craft()->amCommand->setReturnMessage(Craft::t('User deleted.'));
        } else {
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
        $commands = array();
        $users = $this->_getUsers();
        foreach ($users as $user) {
            $userInfo = $this->_getUserInfo($user);
            if (! $user->isCurrent()) {
                $commands[] = array(
                    'name'    => $user->username,
                    'info'    => implode(' - ', $userInfo),
                    'warn'    => true,
                    'call'    => 'loginAsUser',
                    'service' => 'amCommand_users',
                    'vars'    => array(
                        'userId' => $user->id
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
        if (! isset($variables['userId'])) {
            return false;
        }
        if (craft()->userSession->loginByUserId($variables['userId'])) {
            craft()->userSession->setNotice(Craft::t('Logged in.'));
            craft()->amCommand->setReturnMessage(Craft::t('Login as user'));

            if (craft()->userSession->getUser()->can('accessCp')) {
                craft()->amCommand->setReturnUrl(UrlHelper::getCpUrl('dashboard'));
            } else {
                craft()->amCommand->setReturnUrl(UrlHelper::getSiteUrl(''));
            }
            return true;
        } else {
            craft()->amCommand->setReturnMessage(Craft::t('There was a problem impersonating this user.'));
            Craft::log(craft()->userSession->getUser()->username . ' tried to log in using userId: '.$variables['userId'].' but something went wrong.', LogLevel::Error);
            return false;
        }
    }

    /**
     * Get users from any status.
     *
     * @return array
     */
    private function _getUsers()
    {
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->status = null;
        $criteria->limit = null;
        return $criteria->find();
    }

    /**
     * Get user information.
     *
     * @param UserModel $user
     *
     * @return array
     */
    private function _getUserInfo(UserModel $user)
    {
        $userInfo = array();
        if ($user->firstName) {
            $userInfo[] = $user->firstName;
        }
        if ($user->lastName) {
            $userInfo[] = $user->lastName;
        }
        $userInfo[] = $user->email;
        return $userInfo;
    }
}