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
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->limit = null;
        $users = $criteria->find();
        foreach ($users as $user) {
            $commands[] = array(
                'name' => $user->username,
                'type' => Craft::t('Edit'),
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
        $criteria = craft()->elements->getCriteria(ElementType::User);
        $criteria->limit = null;
        $users = $criteria->find();
        foreach ($users as $user) {
            if (! $user->isCurrent()) {
                $commands[] = array(
                    'name'    => $user->username,
                    'type'    => Craft::t('Delete'),
                    'call'    => 'deleteAnUser',
                    'service' => 'amCommand_users',
                    'data'    => array(
                        'userId' => $user->id
                    )
                );
            }
        }
        return $commands;
    }

    /**
     * Delete an user.
     *
     * @param array $data
     *
     * @return type
     */
    public function deleteAnUser($data)
    {
        if (! isset($data['userId'])) {
            return false;
        }
        $user = craft()->users->getUserById($data['userId']);
        return craft()->users->deleteUser($user);
    }
}