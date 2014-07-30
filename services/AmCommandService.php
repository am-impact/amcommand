<?php
namespace Craft;

class AmCommandService extends BaseApplicationComponent
{
    /**
     * Get all available commands.
     *
     * @return array
     */
    public function getCommands()
    {
        $commands = array();
        // Add Content commands
        if (craft()->sections->getTotalEditableSections() > 0) {
            $commands[] = array(
                'name' => Craft::t('New Entry'),
                'type' => Craft::t('Content'),
                'url'  => '',
                'info' => Craft::t('Create a new entry in one of the available sections.'),
                'call' => '_newEntry'
            );
        }
        // Add other plugin's commands
        $pluginCommands = craft()->plugins->call('addCommands');
        foreach ($pluginCommands as $pluginCommand) {
            foreach ($pluginCommand as $command) {
                $commands[] = $command;
            }
        }
        // Add settings commands
        $commands = $this->_getSettings($commands);
        return $commands;
    }

    /**
     * Trigger a command that was called through ajax.
     *
     * @param string $command Command as function name.
     * @param string $service Which service should be called instead.
     *
     * @return mixed False on error otherwise an array with commands.
     */
    public function triggerCommand($command, $service)
    {
        if ($service !== false) {
            if (! method_exists(craft()->$service, $command)) {
                return false;
            }
            $newCommands = craft()->$service->$command();
        } else {
            if (! method_exists($this, $command)) {
                return false;
            }
            $newCommands = $this->$command();
        }
        return craft()->templates->render('amcommand/triggerCommand', array(
            'data' => $newCommands
        ));
    }

    /**
     * Get useful settings.
     *
     * @return array
     */
    private function _getSettings($currentCommands)
    {
        if (! craft()->userSession->isAdmin()) {
            return array();
        }
        $currentCommands[] = array(
            'name' => Craft::t('New') . '..',
            'type' => Craft::t('Settings'),
            'url'  => '',
            'info' => Craft::t('Add something new in the settings..'),
            'call' => '_newSettings'
        );
        $currentCommands[] = array(
            'name' => Craft::t('Fields'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/fields')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Sections'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/sections')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Globals'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/globals')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Users'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/users')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Routes'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/routes')
        );
        $currentCommands[] = array(
            'name' => Craft::t('Categories'),
            'type' => Craft::t('Settings'),
            'url'  => UrlHelper::getUrl('settings/categories')
        );
        return $currentCommands;
    }

    /**
     * Get all available sections to add a new entry to.
     *
     * @return array
     */
    private function _newEntry()
    {
        $commands = array();
        $availableSections = craft()->sections->getEditableSections();
        foreach ($availableSections as $section) {
            if ($section->type != SectionType::Single) {
                $commands[] = array(
                    'name' => $section->name,
                    'type' => Craft::t('New Entry'),
                    'url'  => UrlHelper::getUrl('entries/' . $section->handle . '/new')
                );
            }
        }
        return $commands;
    }

    /**
     * Get settings that you're able to add new things to.
     *
     * @return array
     */
    private function _newSettings()
    {
        $commands = array(
            array(
                'name' => Craft::t('New Field'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/fields/new')
            ),
            array(
                'name' => Craft::t('New Section'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/sections/new')
            ),
            array(
                'name' => Craft::t('New Global Set'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/globals/new')
            ),
            array(
                'name' => Craft::t('New Group'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/users/groups/new')
            ),
            array(
                'name' => Craft::t('New Category Group'),
                'type' => Craft::t('Settings'),
                'url'  => UrlHelper::getUrl('settings/categories/new')
            )
        );
        return $commands;
    }
}