<?php
/**
 * Command plugin for Craft CMS 3.x
 *
 * Command palette in Craft; Because you can
 *
 * @link      http://www.am-impact.nl
 * @copyright Copyright (c) 2017 a&m impact
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

        // Get POST data and trigger the command
        $command  = Craft::$app->request->getBodyParam('command', false);
        $plugin   = Craft::$app->request->getBodyParam('plugin', false);
        $service  = Craft::$app->request->getBodyParam('service', false);
        $vars     = Craft::$app->request->getBodyParam('vars', false);
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
        } else {
            return $this->asJson([
                'success'       => true,
                'title'         => $title,
                'message'       => $message,
                'result'        => $result,
                'redirect'      => $redirect,
                'isNewSet'      => !is_bool($result),
                'isAction'      => $action,
                'isHtml'        => Command::$plugin->general->getReturnHtml(),
                'headHtml'      => Craft::$app->view->getHeadHtml(),
                'footHtml'      => Craft::$app->view->getFootHtml(),
                'deleteCommand' => $delete
            ]);
        }
    }
}
