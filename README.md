# a&m command

_Command palette in Craft; Because you can_

## Functionality

If you have ever used Alfred, you know you'll be zipping through the control panel in no time!

You can open the command palette by using the keyboard combination: (command key for Apple users) CTRL + SHIFT + P, or you click on Command in the CP navigation.

Use the keyboard arrows (up and down) to navigate to your desired command.
When you hit the return key or click on a command, the command palette will navigate to the location and show what it's loading.
Use (command key for Apple users) CTRL + RETURN (or click) to fire the command in a new window.

![Palette](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amcommand/palette.gif "Palette")

## Current commands

### Default commands

| Command | Description |
| --------- | ----------- |
| Content: Delete all entries | Delete all entries in one of the available sections. |
| Content: Delete entries | Delete an entry in one of the available sections. |
| Content: Edit entries | Edit an entry in one of the available sections. |
| Content: New entry | Create a new entry in one of the available sections. |
| Dashboard | Redirect. |
| Globals: Edit | Edit one of your globals. |
| Search on Craft | Redirect - Search on Craft with given keywords. |
| Search on StackExchange | Redirect - Search on StackExchange with given keywords. |
| Settings: Assets | Redirect. |
| Settings: Categories | Redirect. |
| Settings: Fields | Redirect. |
| Settings: Fields - Duplicate | Duplicate a field. |
| Settings: Fields - Edit | Edit one of the fields. |
| Settings: Globals | Redirect. |
| Settings: Globals - Global Sets | Edit the settings for one of the globals. |
| Settings: New... | Add something new in the settings... |
| Settings: Plugins | Redirect. |
| Settings: Plugin settings | Edit the settings for one of the enabled plugins |
| Settings: Routes | Redirect. |
| Settings: Sections | Redirect. |
| Settings: Sections - Edit | Edit a section. |
| Settings: Sections - Edit entry type | Edit an entry type of a section. (Field Layout and such) |
| Settings: Users | Redirect. |
| Tasks | Manage Craft tasks. |
| Tools | Use one of the most used tools. |
| Users: Delete users | Delete a user other than your own. |
| Users: Edit users | Edit a user. |
| Users: Login as user | Log in as a different user, and navigate to their dashboard. |
| Users: New user | Create a user. |
| Sign out | End current session. |
| My Account | Redirect. |

### Special commands

| Command | Description |
| --------- | ----------- |
| Content: Compare entry version | Compare the current entry you are viewing in the CP with older versions. |
| Content: Duplicate entry | Duplicate the current entry you are viewing in the CP. |
| Simply type! | You will be able to search in elements directly when you haven't triggered a (deeper command, that returns a new list or such things) command yet. |

## Adding your own commands

If you'd like to add commands for a plugin you're developing, you can use the __addCommands__ hook!

### Example

Add this to your main plugin file:
```
public function addCommands() {
    $commands = array(
        array(
            'name' => 'Search on Google',
            'type' => 'Custom',
            'url'  => 'http://www.google.nl'
        ),
        array(
            'name' => 'My own plugin function in a service',
            'type' => 'Custom',
            'call' => 'yourPluginFunctionName',
            'service' => 'yourPluginServiceName'
        )
    );
    return $commands;
}
```

That's it! a&m Command Palette will add these two commands.

If you look at the second example, you see a call and service key. These can be used to load a new set of commands.

In your plugin's service __yourPluginServiceName__ (e.g.: amCommand or amCommand_command), you'll create a new function called __yourPluginFunctionName__. In here you could do the same thing as you see in the example, and just return the new set of commands.

## Contact

If you have any questions or suggestions, don't hesitate to contact us. We would like to add more commands to the palette, so if you have any ideas then please let us know!
