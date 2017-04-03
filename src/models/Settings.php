<?php
/**
 * Command plugin for Craft CMS 3.x
 *
 * Command palette in Craft; Because you can
 *
 * @link      http://www.am-impact.nl
 * @copyright Copyright (c) 2017 a&m impact
 */

namespace amimpact\command\models;

use amimpact\command\Command;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    /**
     * Define settings.
     */
    public $newEntry = true;
    public $editEntries = true;
    public $deleteEntries = true;
    public $deleteAllEntries = true;
    public $editGlobals = true;
    public $userCommands = true;
    public $searchCommands = true;
    public $settings = true;
    public $tasks = true;
    public $utilities = true;

    /**
     * Returns the validation rules for attributes.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['newEntry', 'bool'],
            ['editEntries', 'bool'],
            ['deleteEntries', 'bool'],
            ['deleteAllEntries', 'bool'],
            ['editGlobals', 'bool'],
            ['userCommands', 'bool'],
            ['searchCommands', 'bool'],
            ['settings', 'bool'],
            ['tasks', 'bool'],
            ['utilities', 'bool'],
        ];
    }
}
