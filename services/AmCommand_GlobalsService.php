<?php
namespace Craft;

class AmCommand_GlobalsService extends BaseApplicationComponent
{
    /**
     * Get global sets that the current user is allowed to edit.
     */
    public function editGlobals()
    {
        // Gather commands
        $commands = array();

        // Find global sets
        $criteria = craft()->elements->getCriteria(ElementType::GlobalSet);
        $globalSets = $criteria->find();
        foreach ($globalSets as $globalSet) {
            if (craft()->userSession->checkPermission('editGlobalSet:' . $globalSet->id)) {
                $commands[] = array(
                    'name' => $globalSet->name,
                    'url'  => $globalSet->getCpEditUrl()
                );
            }
        }
        if (! count($commands)) {
            craft()->amCommand->setReturnMessage(Craft::t('No global sets exist yet.'));
        }

        return $commands;
    }
}
