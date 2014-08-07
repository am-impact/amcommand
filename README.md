# a&m command

_Command palette in Craft; Because you can_

## Functionality

When you install this plugin, you'll have the ability to show a command palette when you navigate through the backend in Craft.

You can open the command palette by using the keyboard combination: (command key for Apple users) CTRL + SHIFT + P, or you can use the lightning button that'll be added to the header actions.

![Header actions](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amcommand/header-actions.jpg "Header Actions")

The command palette will show admin functions if you are logged in as an admin, and all available sections (non Single) that the user has access to.

![Command palette](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amcommand/command.jpg "Command Palette")

It's equipped with fuzzy search!

![Fuzzy search](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amcommand/fuzzy-search.jpg "Fuzzy Search")

Use the keyboard arrows (up and down) to navigate to your desired command.

![Focus](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amcommand/focus.jpg "Focus")

When you hit the return key or click on a command, the command palette will navigate to the location and show what it's loading.

![Loading](https://raw.githubusercontent.com/am-impact/am-impact.github.io/master/img/readme/amcommand/loading.jpg "Loading")

You can close the command by either clicking anywhere on the page (besides the command palette) or by pressing the ESC key.

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
            'url'  => '',
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

## Changelog

### v0.6.1

- If a scrollbar is shown in the list of commands, keep the current focused item visible by auto scroll while navigating with the arrow keys.

### v0.6

- Ability to execute commands that just perform an action.
- Ability to add data to a command that'll be send with the triggerCommand function.
- Ability to show a confirmation box per command, before executing it.
- New command: Delete entries. Quickly delete all entries from a section of choice (with confirmation).
- New command: Edit entries. Quickly edit an entry from a section of choice.
- When a new command set has been loaded, they are not nicely sorted on type and name as well.
- An url option for a command is no longer required.
- Palette opens fasters.
- Fixed a bug that wouldn't allow you to trigger a command anymore, if you tried to trigger a command when none were available.

### v0.5.1

- Commands are now nicely sorted on type and name.
- Fixed a bug that could load the same command multiple time by bashing the return key.
- Fixed a bug that would make the left and right arrow keys start searching.

### v0.5

- Ability to create your own commands in a plugin.
- Ability to create commands that can load up a new set of commands.
- If a new set of commands was loaded, and the palette was closed, you'll see the regular commands return when reopening the palette.
- If a new set of commands can't be loaded, you'll see a notification and the regular set of commands return.
- Clear notification of what command is being executed.
- Added a few more commands (more are coming).
- Added a loader that'll be shown when loading a new set of commands.
- Styling edited.
- Commands are no longer hyperlinks.
- Alot of the old code was changed.

### v0.2

- Initial release.

## Contact

If you have any questions or suggestions, don't hesitate to contact us. We would like to add more commands to the palette, so if you have any ideas then please let us know!
