<?php
namespace Craft;

class AmCommand_UsersService extends BaseApplicationComponent
{
    /**
     * List of user commands.
     *
     * @return array
     */
    public function userCommands()
    {
        // We don't need to check for permissions anymore, the command calling this already did
        $commands = array(
            array(
                'name'    => Craft::t('New User'),
                'info'    => Craft::t('Create a user.'),
                'url'     => UrlHelper::getUrl('users/new')
            ),
            array(
                'name'    => Craft::t('Edit users'),
                'info'    => Craft::t('Edit a user.'),
                'more'    => true,
                'call'    => 'editUser',
                'service' => 'amCommand_users'
            ),
            array(
                'name'    => Craft::t('Delete users'),
                'info'    => Craft::t('Delete a user other than your own.'),
                'more'    => true,
                'call'    => 'deleteUser',
                'service' => 'amCommand_users'
            )
        );
        return $commands;
    }
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
     * @return type
     */
    public function deleteAnUser($variables)
    {
        if (! isset($variables['userId'])) {
            return false;
        }
        $user   = craft()->users->getUserById($variables['userId']);
        $result = craft()->users->deleteUser($user);
        if ($result) {
            craft()->amCommand->setReturnMessage(Craft::t('User deleted.'));
        } else {
            craft()->amCommand->setReturnMessage(Craft::t('Couldn’t delete “{name}”.', array('name', $user->username)));
        }
        return $result;
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