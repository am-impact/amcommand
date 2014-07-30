<?php
namespace Craft;

class AmCommand_CommandsController extends BaseController
{
    public function actionTriggerCommand()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        // Get POST data and trigger the command
        $command = craft()->request->getPost('command', false);
        $service = craft()->request->getPost('service', false);
        $result = craft()->amCommand->triggerCommand($command, $service);

        // Return the result
        if ($result === false) {
            $this->returnJson(array(
                'success' => false,
                'message' => Craft::t('Couldn\'t trigger the command.')
            ));
        } else {
            $this->returnJson(array(
                'success' => true,
                'commands' => $result
            ));
        }
    }
}