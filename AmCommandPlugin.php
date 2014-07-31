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
         return 'a&m impact command';
    }

    public function getVersion()
    {
        return '0.5.1';
    }

    public function getDeveloper()
    {
        return 'a&m impact';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    /**
     * Load Am Command palette.
     *
     * @return void
     */
    public function init()
    {
        // We only want to see the command palette in the backend
        // User has to be logged in (or it will also work on the login page)
        // Make sure we only run our code once on pages like Entries, by using: craft()->request->isAjaxRequest
        if (craft()->userSession->isLoggedIn() && craft()->request->isCpRequest() && ! craft()->request->isAjaxRequest()) {
            // Gather data
            $data = craft()->amCommand->getCommands();

            // Get the HTML
            $html = craft()->templates->render('amcommand/command', array(
                'data' => $data
            ));
            craft()->templates->includeFootHtml($html);

            // Load javascript
            $js = 'new Craft.AmCommand();';
            craft()->templates->includeJs($js);
            craft()->templates->includeJsResource('amcommand/js/AmCommand.js');
            craft()->templates->includeJsResource('amcommand/js/fuzzy-min.js');
            craft()->templates->includeCssResource('amcommand/css/AmCommand.css');

            craft()->templates->includeTranslations('Command');
        }
    }
}