(function($) {

Craft.AmCommand = Garnish.Base.extend(
{
    $searchField:        $('.amcommand__search input[type=text]'),
    $container:          $('.amcommand'),
    $tabsContainer:      $('.amcommand__tabs'),
    $searchContainer:    $('.amcommand__search'),
    $commandsContainer:  $('.amcommand__commands ul'),
    $loader:             $('.amcommand__loader'),
    $commands:           $('.amcommand__commands li'),
    $button:             $('#nav-amcommand'),
    $buttonExecute:      $('.amcommand__search input[type=button]'),
    ignoreSearchKeys:    [Garnish.UP_KEY, Garnish.DOWN_KEY, Garnish.LEFT_KEY, Garnish.RIGHT_KEY, Garnish.RETURN_KEY, Garnish.ESC_KEY],
    fuzzyOptions:        {
        pre: "<strong>",
        post: "</strong>",
        extract: function(element) {
            return element.name;
        }
    },
    commandsArray:       [],
    rememberPalette:     {
        currentSet: 0,
        commandNames: [],
        commandsArray: [],
        searchKeywords: []
    },
    isOpen:              false,
    isAction:            false,
    isActionAsync:       true,
    isActionRealtime:    false,
    actionData:          [],
    actionTimer:         false,
    loading:             false,
    loadingCommand:      '',
    P_KEY:               80,

    /**
     * Initiate command palette.
     *
     * @param json Commands.
     */
    init: function(commands) {
        var self = this;

        // Set commands for fuzzy search
        self.commandsArray = commands;

        // Display commands
        self.search(undefined, true); // Override event

        // Add event listeners
        self.bindEvents();
    },

    /**
     * Bind events for the command palette.
     */
    bindEvents: function() {
        var self = this;

        self.addListener(self.$button, 'click', function(ev) {
            ev.preventDefault();
            self.openPalette();
        });

        self.addListener(self.$searchField, 'keyup', 'search');

        self.addListener(window, 'keydown', function(ev) {
            if ((ev.metaKey || ev.ctrlKey) && ev.shiftKey && ev.keyCode == self.P_KEY) {
                self.openPalette(ev);
            }
            else if (ev.keyCode == Garnish.UP_KEY) {
                self.moveCommandFocus(ev, 'up');
            }
            else if (ev.keyCode == Garnish.DOWN_KEY) {
                self.moveCommandFocus(ev, 'down');
            }
            else if (ev.keyCode == Garnish.RETURN_KEY) {
                self.triggerCommand(ev, (ev.metaKey || ev.ctrlKey));
            }
            else if (ev.keyCode == Garnish.ESC_KEY) {
                if (self.rememberPalette.currentSet > 0) {
                    self.restoreCommands();
                } else {
                    self.closePalette(ev);
                }
            }
        });

        self.addListener(self.$buttonExecute, 'click', 'triggerCommand');

        self.addListener(document.body, 'click', 'closePalette');

        // Don't close the palette when we click inside it
        self.addListener(self.$container, 'click', function(ev) {
            ev.stopPropagation();
        });
    },

    /**
     * Open the command palette.
     *
     * @param object ev The triggered event.
     */
    openPalette: function(ev) {
        var self = this;

        if (! self.isOpen) {
            self.$container.fadeIn(1, function() {
                self.isOpen = true;
                self.$searchField.focus();
            });
            ev.preventDefault();
        }
    },

    /**
     * Close the command palette.
     *
     * @param object ev The triggered event.
     */
    closePalette: function(ev) {
        var self = this;

        if (self.isOpen) {
            self.$container.fadeOut(1, function() {
                // Hide execute button
                self.$buttonExecute.addClass('hidden');
                self.$searchContainer.removeClass('amcommand__search--hasButton');
                // Reset search keywords and executed command
                self.$searchField.val('');
                self.$tabsContainer.addClass('hidden');
                // If we have any new commands, reset back to first set of commands
                if (self.rememberPalette.currentSet > 0) {
                    self.isAction = false;
                    self.isActionAsync = true;
                    self.isActionRealtime = false;
                    self.actionData = [];
                    self.rememberPalette.currentSet = 0;
                    self.commandsArray = self.rememberPalette.commandsArray[1];
                }
                self.search(undefined, true);
                self.isOpen = false;
                self.loading = false; // Reset loading if the user cancels the page request
            });
            if (ev !== undefined) {
                ev.preventDefault();
            }
        }
    },

    /**
     * Reset the command palette.
     */
    resetPalette: function() {
        var self = this;

        // Reset clicking event
        self.$commands = $('.amcommand__commands li');
        self.addListener(self.$commands, 'click', 'triggerCommand');

        // Focus first
        self.$commands.first().addClass('focus');
    },

    /**
     * Search the available commands.
     *
     * @param object ev          The triggered event.
     * @param bool   allowSearch Override for command palette init.
     * @param bool   realtime    Whether the search was triggered by a realtime action.
     */
    search: function(ev, allowSearch, realtime) {
        var self = this;

        if (! allowSearch && self.isOpen && ! self.loading) {
            // Make sure we don't trigger ignored keys
            if (self.ignoreSearchKeys.indexOf(ev.keyCode) < 0) {
                allowSearch = true;
            }
        }
        if (allowSearch) {
            if (! self.isAction || realtime) {
                var searchValue = realtime ? '' : self.$searchField.val(),
                    filtered = fuzzy.filter(searchValue, self.commandsArray, self.fuzzyOptions),
                    totalResults = filtered.length;

                // Find matches
                var results = filtered.map(function(el) {
                    var name = '<span class="amcommand__commands--name' + ('more' in el.original && el.original.more ? ' go' : '') + '">' + el.string + '</span>';
                    var info = ('info' in el.original) ? '<span class="amcommand__commands--info">' + el.original.info + '</span>' : '';
                    return '<li data-id="' + el.index + '">' + name + info + '</li>';
                });
                self.$commandsContainer.html(results.join(''));
                self.resetPalette();
            }
            else if (self.isAction && self.isActionRealtime) {
                if (self.actionTimer) {
                    clearTimeout(self.actionTimer);
                }
                self.actionTimer = setTimeout($.proxy(function() {
                    self.triggerRealtimeAction();
                }, this), 600);
            }
        }
    },

    /**
     * Move the focus to a different command.
     *
     * @param object ev        The triggered event.
     * @param string direction In which direction the focus should go to.
     */
    moveCommandFocus: function(ev, direction) {
        var self = this;

        if (self.isOpen) {
            var $current = self.$commands.filter('.focus');

            switch (direction) {
                case 'up':
                    var $prev = $current.prev();
                    if ($prev.length) {
                        $prev.addClass('focus');
                        $current.removeClass('focus');
                        self.keepCommandVisible($prev);
                    }
                    break;
                case 'down':
                    var $next = $current.next();
                    if ($next.length) {
                        $next.addClass('focus');
                        $current.removeClass('focus');
                        self.keepCommandVisible($next);
                    }
                    break;
            }
            ev.preventDefault();
        }
    },

    /**
     * Scroll to make the current focused item visible when necessary.
     *
     * @param object current Current focused item.
     */
    keepCommandVisible: function(current) {
        var self = this,
            currentTop      = current.offset().top,
            currentHeight   = current.outerHeight(),
            containerTop    = self.$commandsContainer.offset().top,
            containerScroll = self.$commandsContainer.scrollTop(),
            containerHeight = self.$commandsContainer.height();

        // Down
        if ((currentTop + currentHeight) > (containerHeight + containerTop)) {
            self.$commandsContainer.scrollTop((currentTop - containerTop - containerHeight) + currentHeight + containerScroll);
        }
        // Up
        else if (currentTop < containerTop) {
            self.$commandsContainer.scrollTop((containerScroll - containerTop) + currentTop);
        }
    },

    /**
     * Display a notification message.
     *
     * @param bool  success         Whether the command was succesful.
     * @param mixed customMessage   Whether the message was manually set.
     * @param mixed executedCommand Which command was executed.
     */
    displayMessage: function(success, customMessage, executedCommand) {
        if (success) {
            if (customMessage !== false) {
                Craft.cp.displayNotice(customMessage);
            } else {
                Craft.cp.displayNotice('<span class="amcommand__notice">' + Craft.t('Command executed') + ' &raquo;</span>' + executedCommand);
            }
        } else {
            Craft.cp.displayError(customMessage);
        }
    },

    /**
     * Remember current commands.
     */
    rememberCommands: function() {
        var self = this;

        self.rememberPalette.currentSet++;
        self.rememberPalette.commandNames[ self.rememberPalette.currentSet ] = self.loadingCommand;
        self.rememberPalette.commandsArray[ self.rememberPalette.currentSet ] = self.commandsArray;
        self.rememberPalette.searchKeywords[ self.rememberPalette.currentSet ] = self.$searchField.val();
    },

    /**
     * Restore the previous set of commands.
     */
    restoreCommands: function() {
        var self = this;

        // Reset action if set
        if (self.isAction) {
            self.isAction = false;
            self.isActionAsync = true;
            self.isActionRealtime = false;
            self.actionData = [];
            self.$buttonExecute.addClass('hidden');
            self.$searchContainer.removeClass('amcommand__search--hasButton');
        }
        // Restore commands
        self.commandsArray = self.rememberPalette.commandsArray[ self.rememberPalette.currentSet ];
        // Reset focus and search keywords
        self.$searchField.val(self.rememberPalette.searchKeywords[ self.rememberPalette.currentSet ]);
        self.$searchField.focus();
        // Display the commands
        self.search(undefined, true);
        // Lower current set
        self.rememberPalette.currentSet--;
        // Reset executed command
        if (self.rememberPalette.currentSet > 0) {
            self.$tabsContainer.text(self.rememberPalette.commandNames[ self.rememberPalette.currentSet ]);
        } else {
            self.$tabsContainer.addClass('hidden');
        }
    },

    /**
     * Submit current criteria in the textfield to current action.
     */
    triggerRealtimeAction: function()
    {
        var self = this;

        // Disable timer if set
        if (self.actionTimer) {
            clearTimeout(self.actionTimer);
            self.actionTimer = false;
        }

        // Set action data
        var variables = self.actionData.vars;
        variables['searchText'] = self.$searchField.val();
        // Trigger action
        self.loading = true;
        self.triggerCallback(self.actionData.call, self.actionData.service, variables);
    },

    /**
     * Navigate to the current focused command.
     *
     * @param object ev          The triggered event.
     * @param bool   ctrlPressed Whether the CTRL or Command key was pressed.
     */
    triggerCommand: function(ev, ctrlPressed) {
        var self = this;

        if (self.isOpen && ! self.loading) {
            if (self.isAction && ! self.isActionRealtime) {
                var variables = self.actionData.vars;
                variables['searchText'] = self.$searchField.val();
                // Trigger action
                self.loading = true;
                self.triggerCallback(self.actionData.call, self.actionData.service, variables);
            } else {
                if (ev.type == 'click') {
                    if (ev.ctrlKey || ev.metaKey) {
                        ctrlPressed = true;
                    }
                    var $current = $(ev.currentTarget);
                    // Remove focus from all, and focus the clicked command
                    self.$commands.removeClass('focus');
                    $current.addClass('focus');
                } else {
                    var $current = self.$commands.filter('.focus');
                }
                if ($current.length) {
                    var commandId = $current.data('id');

                    if(commandId in self.commandsArray) {
                        var confirmed       = true,
                            commandData     = self.commandsArray[commandId],
                            warn            = ('warn' in commandData) ? commandData.warn : false,
                            url             = ('url' in commandData) ? commandData.url : false,
                            callback        = ('call' in commandData) ? commandData.call : false,
                            callbackService = ('service' in commandData) ? commandData.service : false,
                            callbackVars    = ('vars' in commandData) ? commandData.vars : false;

                        // Remember command for when a new set is loaded
                        self.loadingCommand = commandData.name;
                        // Do we have to show a warning?
                        if (warn) {
                            var confirmation = confirm(Craft.t('Are you sure you want to execute this command?'));
                            if (! confirmation) {
                                confirmed = false;
                            }
                        }
                        // Can we execute the command?
                        if (confirmed) {
                            self.loading = true;
                            if (callback) {
                                self.triggerCallback(callback, callbackService, callbackVars);
                            }
                            else if (url) {
                                // Open the URL in a new window if the CTRL or Command key was pressed
                                if (ctrlPressed) {
                                    window.open(url);
                                } else {
                                    window.location = url;
                                }
                                self.displayMessage(true, false, commandData.name);
                                self.closePalette(ev);
                            }
                        }
                    }
                }
            }
            ev.preventDefault();
        }
    },

    /**
     * Trigger a command callback function rather than navigating to it.
     *
     * @param string name    Callback function.
     * @param string service Which service should be triggered.
     * @param string vars    JSON string with optional variables.
     */
    triggerCallback: function(name, service, vars) {
        var self = this,
            displayDefaultMessage = false,
            $current = self.$commands.filter('.focus');

        // Hide current commands and display a loader
        self.$commands.hide();
        self.$loader.removeClass('hidden');

        Craft.postActionRequest('amCommand/commands/triggerCommand', {command: name, service: service, vars: vars}, $.proxy(function(response, textStatus) {
            if (textStatus == 'success') {
                self.loading = false;
                self.$loader.addClass('hidden');
                if (response.success) {
                    // Reset action if set
                    if (self.isAction && ! self.isActionRealtime) {
                        self.isAction = false;
                        self.isActionAsync = true;
                        self.actionData = [];
                        self.$buttonExecute.addClass('hidden');
                        self.$searchContainer.removeClass('amcommand__search--hasButton');
                    }

                    // What result do we have? An action, new command set or just a result message?
                    if (self.isAction && self.isActionRealtime && response.isNewSet) {
                        self.commandsArray = response.result;
                        self.search(undefined, true, true);
                    }
                    else if (response.isAction) {
                        // Remember current commands and action information
                        self.rememberCommands();
                        self.isAction = true;
                        self.isActionAsync = response.isAction.async;
                        self.isActionRealtime = response.isAction.realtime;
                        self.actionData = response.isAction;
                        // Display text
                        self.$tabsContainer.text(response.isAction.tabs);
                        self.$tabsContainer.removeClass('hidden');
                        // Only display the execute button next to search field when it's not realtime
                        if (! response.isAction.realtime) {
                            self.$buttonExecute.removeClass('hidden');
                            self.$searchContainer.addClass('amcommand__search--hasButton');
                        }
                        // Reset palette
                        self.commandsArray = [];
                        self.$commandsContainer.html('');
                        // Display text in search field
                        self.$searchField.val(response.isAction.searchText);
                        self.$searchField.focus();
                    }
                    else if (response.isNewSet) {
                        // It is a command that loads a new set of commands, but did we get any?
                        if (response.result == '') {
                            self.$commands.show(); // Show current commands again
                        } else {
                            // Remember current commands
                            self.rememberCommands();
                            // Display executed command above search field
                            self.$tabsContainer.text(self.loadingCommand);
                            self.$tabsContainer.removeClass('hidden');
                            // Reset focus
                            self.$searchField.val('');
                            self.$searchField.focus();
                            // Display new commands
                            self.commandsArray = response.result;
                            self.search(undefined, true);
                        }
                    }
                    else if (response.deleteCommand)
                    {
                        // Reset focus
                        self.$searchField.val('');
                        self.$searchField.focus();
                        self.$commands.removeClass('focus');
                        // We delete the current command, and keep the command palette open
                        self.deleteCommand($current.data('id'));
                        self.search(undefined, true);
                        self.$commands.show();
                        // Close the command palette if all commands are hidden
                        if (self.commandsArray.length <= 0) {
                            self.displayMessage(false, Craft.t('There are no more commands available.'), false);
                            self.closePalette();
                        }
                    } else {
                        // Command was executed, nothing special happened afterwards
                        displayDefaultMessage = true;
                        self.closePalette();
                    }
                    // Show message
                    if (response.message) {
                        self.displayMessage((response.result != ''), response.message, false);
                    }
                    else if (displayDefaultMessage) {
                        self.displayMessage(true, false, $current.children('.amcommand__commands--name').text());
                    }
                    // Redirect?
                    if (response.redirect) {
                        if (response.redirect.newWindow) {
                            window.open(response.redirect.url);
                        } else {
                            window.location = response.redirect.url;
                        }
                    }
                } else {
                    // Delete current commands if realtime action
                    if (self.isAction && self.isActionRealtime) {
                        self.commandsArray = [];
                        self.$commandsContainer.html('');
                    }
                    // Show current commands again and display a message
                    self.$commands.show();
                    self.$searchField.focus();
                    self.displayMessage(false, response.message, false);
                }
            }
        }, self), {async: self.isActionAsync});
    },

    /**
     * Delete a command from the available commands array.
     *
     * @param int index Command index.
     */
    deleteCommand: function(index) {
        var self = this,
            len = self.commandsArray.length;

        if (!len) {
            return;
        }
        while (index < len) {
            self.commandsArray[index] = self.commandsArray[index + 1];
            index++;
        }
        self.commandsArray.length--;
    }
});

})(jQuery);
