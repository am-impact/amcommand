<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command\controllers;

use amimpact\command\Command;

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
        $result   = Command::$plugin->general->triggerCommand($command, $plugin, $service, $vars);
        $title    = Command::$plugin->general->getReturnTitle();
        $message  = Command::$plugin->general->getReturnMessage();
        $redirect = Command::$plugin->general->getReturnUrl();
        $action   = Command::$plugin->general->getReturnAction();
        $commands = Command::$plugin->general->getReturnCommands();
        $delete   = Command::$plugin->general->getDeleteStatus();

        // Overwrite result with overwritten commands?
        if ($commands) {
            $result = $commands;
        }

        // Return the result
        if ($result === false) {
            return $this->asJson([
                'success' => false,
                'message' => $message ? $message : Craft::t('command', 'Couldn’t trigger the command.')
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
                'isHtml'        => Command::$plugin->general->getReturnHtml(),
                'headHtml'      => $viewService->getHeadHtml(),
                'footHtml'      => $viewService->getBodyHtml(),
                'deleteCommand' => $delete
            ]);
        }
    }
}
