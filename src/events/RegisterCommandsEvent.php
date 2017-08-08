<?php
/**
 * Command palette for Craft.
 *
 * @author    a&m impact
 * @copyright Copyright (c) 2017 a&m impact
 * @link      http://www.am-impact.nl
 */

namespace amimpact\commandpalette\events;

use yii\base\Event;

class RegisterCommandsEvent extends Event
{
    /**
     * @var array The registered commands.
     */
    public $commands = [];
}
