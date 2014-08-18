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
