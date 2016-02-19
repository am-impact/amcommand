<?php
/**
 * Command palette for Craft.
 *
 * @package   Am Command
 * @author    Hubert Prein
 */
namespace Craft;

class AmCommandPlugin extends BasePlugin
{
    public function getName()
    {
         return 'Command';
    }

    public function getVersion()
    {
        return '1.1.4';
    }

    public function getSchemaVersion()
    {
        return '1.1.4';
    }

    public function getDeveloper()
    {
        return 'a&m impact';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    public function hasCpSection()
    {
        return true;
    }

    /**
     * Display command palette settings.
     */
    public function getSettingsHtml()
    {
        $commands = array(
            'newEntry' => array(
                'name' => Craft::t('New Entry'),
                'info' => Craft::t('Create a new entry in one of the available sections.')
            ),
            'editEntries' => array(
                'name' => Craft::t('Edit entries'),
                'info' => Craft::t('Edit an entry in one of the available sections.')
            ),
            'deleteEntries' => array(
                'name' => Craft::t('Delete entries'),
                'info' => Craft::t('Delete an entry in one of the available sections.')
            ),
            'deleteAllEntries' => array(
                'name' => Craft::t('Delete all entries'),
                'info' => Craft::t('Delete all entries in one of the available sections.') . ' (' . Craft::t('This action may only be performed by admins.') . ')'
            ),
            'duplicateEntry' => array(
                'name' => Craft::t('Duplicate entry'),
                'info' => Craft::t('Duplicate the current entry.')
            ),
            'editGlobals' => array(
                'name' => Craft::t('Globals'),
                'info' => Craft::t('Edit')
            ),
            'userCommands' => array(
                'name' => Craft::t('Administrate users'),
                'info' => Craft::t('Create, edit or delete a user.')
            ),
            'searchCommands' => array(
                'name' => Craft::t('Search'),
                'info' => Craft::t('You’re able to search within a site that offers help.')
            ),
            'settings' => array(
                'name' => Craft::t('Settings'),
                'info' => Craft::t('This action may only be performed by admins.')
            ),
            'tools' => array(
                'name' => Craft::t('Tools'),
                'info' => Craft::t('This action may only be performed by admins.')
            )
        );
        return craft()->templates->render('amcommand/settings', array(
            'commands' => $commands,
            'settings' => $this->getSettings()
        ));
    }

    /**
     * Load command palette.
     *
     * @return void
     */
    public function init()
    {
        // We only want to see the command palette in the backend
        // User has to be logged in (or it will also work on the login page)
        // Make sure we only run our code once on pages like Entries, by using: craft()->request->isAjaxRequest
        if (craft()->request->isCpRequest() && craft()->userSession->isLoggedIn() && ! craft()->request->isAjaxRequest()) {
            // Gather commands
            $commands = craft()->amCommand->getCommands($this->getSettings());

            // Get the HTML
            $html = craft()->templates->render('amcommand/command');
            craft()->templates->includeFootHtml($html);

            // Load javascript
            $js = sprintf('new Craft.AmCommand(%s);', $commands);
            craft()->templates->includeJs($js);
            craft()->templates->includeJsResource('amcommand/js/AmCommand.js');
            craft()->templates->includeJsResource('amcommand/js/fuzzy-min.js');
            craft()->templates->includeCssResource('amcommand/css/AmCommand.css');
            craft()->templates->includeTranslations('Command executed', 'Are you sure you want to execute this command?', 'There are no more commands available.');
        }
    }

    /**
     * Plugin settings.
     *
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'duplicateEntry'   => array(AttributeType::Bool, 'default' => true),
            'newEntry'         => array(AttributeType::Bool, 'default' => true),
            'editEntries'      => array(AttributeType::Bool, 'default' => true),
            'deleteEntries'    => array(AttributeType::Bool, 'default' => true),
            'deleteAllEntries' => array(AttributeType::Bool, 'default' => true),
            'editGlobals'      => array(AttributeType::Bool, 'default' => true),
            'userCommands'     => array(AttributeType::Bool, 'default' => true),
            'searchCommands'   => array(AttributeType::Bool, 'default' => true),
            'settings'         => array(AttributeType::Bool, 'default' => true),
            'tools'            => array(AttributeType::Bool, 'default' => true)
        );
    }
}
