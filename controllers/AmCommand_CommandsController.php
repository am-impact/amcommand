<?php
namespace Craft;

class AmCommand_CommandsController extends BaseController
{
    /**
     * Trigger a command.
     */
    public function actionTriggerCommand()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        // Get POST data and trigger the command
        $command  = craft()->request->getPost('command', false);
        $service  = craft()->request->getPost('service', false);
        $vars     = craft()->request->getPost('vars', false);
        $result   = craft()->amCommand->triggerCommand($command, $service, $vars);
        $title    = craft()->amCommand->getReturnTitle();
        $message  = craft()->amCommand->getReturnMessage();
        $redirect = craft()->amCommand->getReturnUrl();
        $action   = craft()->amCommand->getReturnAction();
        $commands = craft()->amCommand->getReturnCommands();
        $delete   = craft()->amCommand->getDeleteStatus();

        // Overwrite result with overwritten commands?
        if ($commands) {
            $result = $commands;
        }

        // Return the result
        if ($result === false) {
            $this->returnJson(array(
                'success' => false,
                'message' => $message ? $message : Craft::t('Couldn’t trigger the command.')
            ));
        }
        else {
            $this->returnJson(array(
                'success'       => true,
                'title'         => $title,
                'message'       => $message,
                'result'        => $result,
                'redirect'      => $redirect,
                'isNewSet'      => !is_bool($result),
                'isAction'      => $action,
                'isHtml'        => craft()->amCommand->getReturnHtml(),
                'headHtml'      => craft()->templates->getHeadHtml(),
                'footHtml'      => craft()->templates->getFootHtml(),
                'deleteCommand' => $delete
            ));
        }
    }
}
