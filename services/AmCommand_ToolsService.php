<?php
namespace Craft;

class AmCommand_ToolsService extends BaseApplicationComponent
{
    /**
     * Get a list of tools.
     *
     * @return array
     */
    public function listTools()
    {
        $commands = array(
            array(
                'name'    => Craft::t('Clear Caches'),
                'warn'    => true,
                'call'    => 'initTool',
                'service' => 'amCommand_tools',
                'vars'    => array(
                    'tool' => 'ClearCaches'
                )
            ),
            array(
                'name'    => Craft::t('Rebuild Search Indexes'),
                'warn'    => true,
                'call'    => 'initTool',
                'service' => 'amCommand_tools',
                'vars'    => array(
                    'tool' => 'SearchIndex'
                )
            ),
            array(
                'name'    => Craft::t('Update Asset Indexes'),
                'warn'    => true,
                'call'    => 'initTool',
                'service' => 'amCommand_tools',
                'vars'    => array(
                    'tool' => 'AssetIndex'
                )
            )

        );
        return $commands;
    }

    /**
     * Initiate tool.
     *
     * @param array $variables
     *
     * @return bool
     */
    public function initTool($variables)
    {
        if (! isset($variables['tool'])) {
            return false;
        }
        switch ($variables['tool']) {
            case 'ClearCaches':
                // Clear all caches
                $params = array('caches' => '*');
                break;
            case 'SearchIndex':
                // Get batches
                $params = array('start' => 'true');
                break;
            case 'AssetIndex':
                // Get batches
                $params = array('start' => 'true', 'sources' => '');
                break;
            default:
                $params = array();
                break;
        }

        craft()->config->maxPowerCaptain();

        $tool = craft()->components->getComponentByTypeAndClass(ComponentType::Tool, $variables['tool']);

        $response = $tool->performAction($params);

        if (is_array($response) && isset($response['batches'])) {
            for ($i = 0; $i < count($response['batches']); $i++) {
                foreach ($response['batches'][$i] as $batch) {
                    $tool->performAction($batch['params']);
                }
            }
        }
        return true;
    }
}