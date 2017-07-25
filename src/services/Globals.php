<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\command\services;

use amimpact\command\Command;

use Craft;
use craft\base\Component;
use craft\elements\GlobalSet;

class Globals extends Component
{
    /**
     * Get global sets that the current user is allowed to edit.
     */
    public function editGlobals()
    {
        // Gather commands
        $commands = [];

        // Find available global sets
        $globalSets = GlobalSet::find()->all();
        foreach ($globalSets as $globalSet) {
            if (Craft::$app->getUser()->checkPermission('editGlobalSet:' . $globalSet->id)) {
                $commands[] = [
                    'name' => $globalSet->name,
                    'url'  => $globalSet->getCpEditUrl()
                ];
            }
        }
        if (! count($commands)) {
            Command::$plugin->general->setReturnMessage(Craft::t('app', 'No global sets exist yet.'));
        }

        return $commands;
    }
}
