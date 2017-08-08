<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette\controllers;

use amimpact\commandpalette\CommandPalette;

use Craft;
use craft\web\Controller;
use craft\web\View;

class CommandsController extends Controller
{
    /**
     * Trigger a command.
     */
    public function actionTriggerCommand()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        // Get services
        $viewService = Craft::$app->getView();
        $requestService = Craft::$app->getRequest();

        // Get POST data and trigger the command
        $command  = $requestService->getBodyParam('command', false);
        $plugin   = $requestService->getBodyParam('plugin', false);
        $service  = $requestService->getBodyParam('service', false);
        $vars     = $requestService->getBodyParam('vars', false);
        $result   = CommandPalette::$plugin->general->triggerCommand($command, $plugin, $service, $vars);
        $title    = CommandPalette::$plugin->general->getReturnTitle();
        $message  = CommandPalette::$plugin->general->getReturnMessage();
        $redirect = CommandPalette::$plugin->general->getReturnUrl();
        $action   = CommandPalette::$plugin->general->getReturnAction();
        $commands = CommandPalette::$plugin->general->getReturnCommands();
        $delete   = CommandPalette::$plugin->general->getDeleteStatus();

        // Overwrite result with overwritten commands?
        if ($commands) {
            $result = $commands;
        }

        // Return the result
        if ($result === false) {
            return $this->asJson([
                'success' => false,
                'message' => $message ? $message : Craft::t('command-palette', 'Couldn’t trigger the command.')
            ]);
        }
        else {
            return $this->asJson([
                'success'       => true,
                'title'         => $title,
                'message'       => $message,
                'result'        => $result,
                'redirect'      => $redirect,
                'isNewSet'      => !is_bool($result),
                'isAction'      => $action,
                'isHtml'        => CommandPalette::$plugin->general->getReturnHtml(),
                'headHtml'      => $viewService->getHeadHtml(),
                'footHtml'      => $viewService->getBodyHtml(),
                'deleteCommand' => $delete
            ]);
        }
    }
}
