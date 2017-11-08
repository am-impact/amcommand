(function($) {

Craft.AmCommand = Garnish.Base.extend(
{
    // DOM elements
    $searchField:       $('.amcommand__search input[type=text]'),
    $container:         $('.amcommand'),
    $tabsContainer:     $('.amcommand__tabs'),
    $searchContainer:   $('.amcommand__search'),
    $commandsContainer: $('.amcommand__commands ul'),
    $loader:            $('.amcommand__loader'),
    $commands:          $('.amcommand__commands li'),
    $button:            $('#nav-amcommand'),

    // Keys
    P_KEY:            80,
    NUM_KEYS:         [49, 50, 51, 52, 53, 54, 55, 56, 57],
    ignoreSearchKeys: [Garnish.UP_KEY, Garnish.DOWN_KEY, Garnish.LEFT_KEY, Garnish.RIGHT_KEY, Garnish.RETURN_KEY, Garnish.ESC_KEY, Garnish.CMD_KEY, Garnish.CTRL_KEY],

    // Palette options & commands
    commandsArray: [],
    elementCommandsArray: [],
    combinedCommandsArray: [],
    searchedCommandsArray: [],
    rememberPalette: {
        currentSet: 0,
        paletteStyle: [],
        commandNames: [],
        commandsArray: [],
        searchKeywords: [],
        actions: []
    },

    // Palette status
    isOpen:             false,
    isHtml:             false,
    isAction:           false,
    isActionAsync:      true,
    isActionRealtime:   false,
    actionData:         [],
    actionTimer:        false,
    loadingCommand:     '',
    loadingRequest:     false,
    loadingElements:    false,
    allowElementSearch: true,
    elementSearchTimer: false,

    /**
     * Initiate command palette.
     *
     * @param json Commands.
     */
    init: function(commands) {
        var self = this;

        // Set commands for fuzzy search
        self.commandsArray = commands;
        self.combinedCommandsArray = commands;

        // Add event listeners
        self.bindEvents();
    },

    /**
     * Bind events for the command palette.
     */
    bindEvents: function() {
        var self = this;

        self.addListener(self.$button, 'click', 'openPalette');

        self.addListener(self.$searchField, 'keyup', function(ev) {
            // Make sure we don't trigger ignored keys
            if (self.ignoreSearchKeys.indexOf(ev.keyCode) < 0) {
                self.search(ev, false, false);

                // Allow element searches when we no commands triggered
                if (self.isOpen && self.allowElementSearch && self.$searchField.val().length) {
                    if (self.elementSearchTimer) {
                        clearTimeout(self.elementSearchTimer);
                    }
                    self.elementSearchTimer = setTimeout($.proxy(function() {
                        // Trigger element search
                        var variables = {
                            'option': 'DirectElements',
                            'searchText': self.$searchField.val()
                        };
                        self.triggerCallback('searchOn', 'amCommand_search', variables, true);
                    }, this), 600);
                }
            }
        });

        self.addListener(window, 'keydown', function(ev) {
            if ((ev.metaKey || ev.ctrlKey) && ev.shiftKey && ev.keyCode == self.P_KEY) {
                if (! self.isOpen) {
                    self.openPalette(ev);
                }
                else {
                    self.closePalette(ev);
                }
            }
            else if ((ev.metaKey || ev.ctrlKey) && self.NUM_KEYS.indexOf(ev.keyCode) > -1) {
                self.triggerCommand(ev, false);
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
                }
                else {
                    self.closePalette(ev);
                }
            }
        });

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
                self.$commandsContainer.addClass('hidden');
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
                self.search(ev, false, false);
                self.isOpen = false;
                if (self.loadingRequest) {
                    self.loadingRequest.abort();
                    self.$loader.addClass('hidden');
                }
                self.loadingRequest = false;
                self.loadingElements = false;
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
     * @param object ev              The triggered event.
     * @param bool   isRealtime      Whether the search was triggered by a realtime action.
     * @param bool   isElementSearch Whether the search was triggered by an element search.
     */
    search: function(ev, isRealtime, isElementSearch) {
        var self = this;

        if ((! self.isAction || isRealtime) && (! self.loadingRequest || self.loadingElements)) {
            var commandsArray = self.allowElementSearch ? self.combinedCommandsArray : self.commandsArray,
                searchValue = isRealtime ? '' : self.$searchField.val(),
                filtered = fuzzysort.go(searchValue, commandsArray, {
                    keys: ['name', 'info'],
                    scoreFn: function(a) {
                        return Math.max(a[0] ? a[0].score : -1000, a[1] ? a[1].score-100 : -1000)
                    }
                }),
                totalResults = filtered.length,
                performUpdate = true;

            // Reset searched commands
            self.searchedCommandsArray = [];

            // Render commands?
            if (! self.allowElementSearch || (searchValue.length && self.allowElementSearch)) {
                // Find matches
                var results = self.renderCommands(filtered);
                if (! results.length && searchValue == '' && ! self.allowElementSearch) {
                    // When a command was executed that displays a new list of commands, make sure they are shown
                    results = self.renderCommands(commandsArray);
                }

                // Element search deliver anything new?
                if (isElementSearch) {
                    var currentMatches = self.$commands.length;
                    if (currentMatches == results.length) {
                        performUpdate = false;
                    }
                }

                // Update palettte
                if (performUpdate) {
                    self.$commandsContainer.removeClass('hidden');
                    self.$commandsContainer.html(results.join(''));
                    if (! results.length) {
                        self.$commandsContainer.addClass('hidden');
                    }
                    self.resetPalette();
                }
            }
            else {
                // Hide commands
                self.$commandsContainer.html('');
                self.$commandsContainer.addClass('hidden');
            }
        }
        else if (self.isAction && self.isActionRealtime) {
            if (self.actionTimer) {
                clearTimeout(self.actionTimer);
            }
            self.actionTimer = setTimeout($.proxy(function() {
                self.triggerRealtimeAction();
            }, this), 600);
        }
    },

    /**
     * Render the commands that will be showed.
     *
     * @param array commands
     */
    renderCommands: function(commands) {
        var self = this;

        var counter = -1,
            results = commands.map(function(el) {
            // Where is our command located?
            var object = el;
            var name = object.name;
            var info = object.info;
            if ('obj' in el) {
                object = el.obj;
                name = object.name;
                info = object.info;

                // Highlight our best result
                if (el[0] !== null) {
                    if (el[0].target == info) {
                        info = fuzzysort.highlight(el[0]);
                    }
                    else {
                        name = fuzzysort.highlight(el[0]);
                    }
                }
                else if (el[1] !== null) {
                    if (el[1].target == info) {
                        info = fuzzysort.highlight(el[1]);
                    }
                    else {
                        name = fuzzysort.highlight(el[1]);
                    }
                }
                else {
                    return '';
                }
            }

            // Add the object to our current searched commands
            self.searchedCommandsArray.push(object);

            // Render command
            counter ++;
            var icon = ('icon' in object) ? '<span class="amcommand__icon"' + (object.icon.type == 'font' ? ' data-icon="' + object.icon.content + '"' : '') + '>' + (object.icon.type != 'font' ? object.icon.content : '') + '</span>' : '';
            var shortcut = (counter < 9) ? '<span class="amcommand__shortcut">&#8984;' + (counter + 1) + '</span>' : '';
            name = '<span class="amcommand__name' + ('more' in object && object.more ? ' go' : '') + '">' + name + '</span>';
            info = (info.length) ? '<span class="amcommand__info">' + info + '</span>' : '';

            return '<li data-id="' + counter + '">' + icon + '<div class="amcommand__content">' + name + info + '</div>' + shortcut + '</li>';
        });

        return results;
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

        if ((currentTop + currentHeight) > (containerHeight + containerTop)) {
            // Down
            self.$commandsContainer.scrollTop((currentTop - containerTop - containerHeight) + currentHeight + containerScroll);
        }
        else if (currentTop < containerTop) {
            // Up
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
            }
            else {
                Craft.cp.displayNotice('<span class="amcommand__notice">' + Craft.t('Command executed') + ' &raquo;</span>' + executedCommand);
            }
        }
        else {
            Craft.cp.displayError(customMessage);
        }
    },

    /**
     * Remember current commands.
     */
    rememberCommands: function() {
        var self = this,
            currentAction = {
                isAction: self.isAction,
                isActionAsync: self.isActionAsync,
                isActionRealtime: self.isActionRealtime,
                actionData: self.actionData
            };

        // Store current commands
        self.rememberPalette.currentSet++;
        self.rememberPalette.paletteStyle[ self.rememberPalette.currentSet ] = {
            width: self.$container.width()
        };
        self.rememberPalette.commandNames[ self.rememberPalette.currentSet ] = self.loadingCommand;
        self.rememberPalette.commandsArray[ self.rememberPalette.currentSet ] = self.commandsArray;
        self.rememberPalette.searchKeywords[ self.rememberPalette.currentSet ] = self.$searchField.val();
        self.rememberPalette.actions[ self.rememberPalette.currentSet ] = currentAction;

        // Reset action if set
        if (self.isAction && ! self.isActionRealtime) {
            self.isAction = false;
            self.isActionAsync = true;
            self.actionData = [];
        }

        // Diable element search?
        if (self.rememberPalette.currentSet > 0) {
            self.allowElementSearch = false;
        }
    },

    /**
     * Restore the previous set of commands.
     */
    restoreCommands: function() {
        var self = this,
            restoreAction = self.rememberPalette.actions[ self.rememberPalette.currentSet ];

        // Adjust palette?
        if (self.isHtml) {
            self.isHtml = false;
            self.$searchContainer.removeClass('hidden');
            self.$container.velocity(self.rememberPalette.paletteStyle[ self.rememberPalette.currentSet ], 400);
        }

        // Reset action if set
        if (self.isAction) {
            self.isAction = false;
            self.isActionAsync = true;
            self.isActionRealtime = false;
            self.actionData = [];
        }

        // Stop request if set
        if (self.loadingRequest) {
            self.loadingRequest.abort();
            self.$loader.addClass('hidden');
        }
        self.loadingRequest = false;
        self.loadingElements = false;

        // Restore commands
        self.commandsArray = self.rememberPalette.commandsArray[ self.rememberPalette.currentSet ];

        // Reset focus and search keywords
        self.$searchField.val(self.rememberPalette.searchKeywords[ self.rememberPalette.currentSet ]);
        self.$searchField.focus();

        // Lower current set
        self.rememberPalette.currentSet--;

        // Enable element search?
        if (self.rememberPalette.currentSet == 0) {
            self.allowElementSearch = true;
            self.combinedCommandsArray = self.commandsArray.concat(self.elementCommandsArray);
        }

        // Display the commands
        self.search(undefined, false, false);

        // Reset executed command
        if (self.rememberPalette.currentSet > 0) {
            self.$tabsContainer.text(self.rememberPalette.commandNames[ self.rememberPalette.currentSet ]);
        }
        else {
            self.$tabsContainer.addClass('hidden');
        }

        // Restore action
        if (restoreAction.isAction) {
            self.isAction = true;
            self.isActionAsync = restoreAction.isActionAsync;
            self.isActionRealtime = restoreAction.isActionRealtime;
            self.actionData = restoreAction.actionData;
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
        self.triggerCallback(self.actionData.call, self.actionData.service, variables, false);
    },

    /**
     * Navigate to the current focused command.
     *
     * @param object ev          The triggered event.
     * @param bool   ctrlPressed Whether the CTRL or Command key was pressed.
     */
    triggerCommand: function(ev, ctrlPressed) {
        var self = this;

        if (self.isOpen) {
            if (self.isAction && ! self.isActionRealtime) {
                var variables = self.actionData.vars;
                variables['searchText'] = self.$searchField.val();

                // Trigger action
                self.triggerCallback(self.actionData.call, self.actionData.service, variables, false);
            }
            else {
                if (ev.type == 'click') {
                    if (ev.ctrlKey || ev.metaKey) {
                        ctrlPressed = true;
                    }
                    var $current = $(ev.currentTarget);

                    // Remove focus from all, and focus the clicked command
                    self.$commands.removeClass('focus');
                    $current.addClass('focus');
                }
                else if (ev.type == 'keydown' && self.NUM_KEYS.indexOf(ev.keyCode) > -1) {
                    var commandNumber = self.NUM_KEYS.indexOf(ev.keyCode);
                    var $current = self.$commands.filter(':eq(' + commandNumber + ')');
                }
                else {
                    var $current = self.$commands.filter('.focus');
                }
                if ($current.length) {
                    var commandId = $current.data('id');

                    if (commandId in self.searchedCommandsArray) {
                        var confirmed       = true,
                            commandData     = self.searchedCommandsArray[commandId],
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
                            if (callback) {
                                self.triggerCallback(callback, callbackService, callbackVars, false);
                            }
                            else if (url) {
                                // Open the URL in a new window if the CTRL or Command key was pressed
                                if (ctrlPressed) {
                                    window.open(url);
                                }
                                else {
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
     * @param string name            Callback function.
     * @param string service         Which service should be triggered.
     * @param string vars            JSON string with optional variables.
     * @param bool   isElementSearch Whether this is a element search call.
     */
    triggerCallback: function(name, service, vars, isElementSearch) {
        var self = this,
            allowRequest = false,
            displayDefaultMessage = false,
            $current = self.$commands.filter('.focus');

        if (! self.loadingRequest) {
            allowRequest = true;
        }
        else if (self.loadingElements) {
            allowRequest = true;

            if (self.loadingRequest) {
                self.loadingElements = false;
                self.loadingRequest.abort();
            }
        }

        if (allowRequest) {
            // Hide current commands and display a loader
            if (! isElementSearch) {
                self.$commands.hide();
            }
            self.$loader.removeClass('hidden');

            // Are we going to load elements?
            if (isElementSearch) {
                self.loadingElements = true;
            }

            self.loadingRequest = Craft.postActionRequest('amCommand/commands/triggerCommand', { command: name, service: service, vars: vars }, $.proxy(function (response, textStatus) {
                self.loadingRequest = false;
                if (textStatus == 'success') {
                    self.$loader.addClass('hidden');

                    if (response.success) {
                        // Was there a custom title set?
                        if (response.title) {
                            self.loadingCommand = response.title;
                        }

                        // What result do we have? HTML, an action, new command set or just a result message?
                        if (response.isHtml) {
                            // It is a command that shows HTML, but did we get any?
                            if (response.result == '') {
                                self.$commands.show(); // Show current commands again
                            }
                            else {
                                // Remember current commands
                                self.rememberCommands();
                                self.isHtml = true;

                                // Display executed command above search field
                                self.$tabsContainer.text(self.loadingCommand);
                                self.$tabsContainer.removeClass('hidden');

                                // Adjust palette
                                self.$searchContainer.addClass('hidden');
                                self.$container.velocity({ width: '80%' }, 400);

                                // Show HTML
                                self.$commandsContainer.html(response.result);
                                Craft.appendHeadHtml(response.headHtml);
                                Craft.appendFootHtml(response.footHtml);
                                Craft.initUiElements(self.$commandsContainer);
                            }
                        }
                        else if (isElementSearch && response.isNewSet) {
                            // Display combined commands
                            self.loadingElements = false;
                            self.elementCommandsArray = response.result;
                            self.combinedCommandsArray = self.commandsArray.concat(self.elementCommandsArray);

                            // Only sort our commands, when we received new ones
                            if (response.result.length > 0) {
                                self.search(undefined, false, true);
                            }
                        }
                        else if (self.isAction && self.isActionRealtime && response.isNewSet) {
                            // Display new commands
                            self.commandsArray = response.result;
                            self.search(undefined, true, false);
                        }
                        else if (response.isAction) {
                            // Executed function
                            self.loadingCommand = response.isAction.tabs;

                            // Remember current commands and action information
                            self.rememberCommands();
                            self.isAction = true;
                            self.isActionAsync = response.isAction.async;
                            self.isActionRealtime = response.isAction.realtime;
                            self.actionData = response.isAction;

                            // Display text
                            self.$tabsContainer.text(response.isAction.tabs);
                            self.$tabsContainer.removeClass('hidden');

                            // Reset palette
                            self.commandsArray = [];
                            self.$commandsContainer.html('');
                            self.$commandsContainer.addClass('hidden');

                            // Display text in search field
                            self.$searchField.val(response.isAction.searchText);
                            self.$searchField.focus();
                        }
                        else if (response.isNewSet) {
                            // It is a command that loads a new set of commands, but did we get any?
                            if (response.result == '') {
                                self.$commands.show(); // Show current commands again
                            }
                            else {
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
                                self.search(undefined, false, false);
                            }
                        }
                        else if (response.deleteCommand) {
                            // Reset focus
                            self.$searchField.val('');
                            self.$searchField.focus();
                            self.$commands.removeClass('focus');

                            // We delete the current command, and keep the command palette open
                            self.deleteCommand($current.data('id'));
                            self.search(undefined, true, false);
                            self.$commands.show();

                            // Close the command palette if all commands are hidden
                            if (self.commandsArray.length <= 0) {
                                self.displayMessage(false, Craft.t('There are no more commands available.'), false);
                                self.closePalette();
                            }
                        }
                        else {
                            // Command was executed, nothing special happened afterwards
                            displayDefaultMessage = true;
                            self.closePalette();
                        }

                        // Show message
                        if (response.message) {
                            self.displayMessage((response.result != ''), response.message, false);
                        }
                        else if (displayDefaultMessage) {
                            self.displayMessage(true, false, self.loadingCommand);
                        }

                        // Redirect?
                        if (response.redirect) {
                            if (response.redirect.newWindow) {
                                window.open(response.redirect.url);
                            }
                            else {
                                window.location = response.redirect.url;
                            }
                        }
                    }
                    else {
                        // Delete current commands if realtime action
                        if (self.isAction && self.isActionRealtime) {
                            self.commandsArray = [];
                            self.$commandsContainer.html('');
                            self.$commandsContainer.addClass('hidden');
                        }

                        // Show current commands again and display a message
                        self.$commands.show();
                        self.$searchField.focus();
                        if (! isElementSearch) {
                            self.displayMessage(false, response.message, false);
                        }
                    }
                }
            }, self), {
                async: self.isActionAsync,
                complete: function(jqXHR, textStatus) {
                    // Overwrite complete in craft.js
                }
            });
        }
    },

    /**
     * Delete a command from the available commands array.
     *
     * @param int index Command index.
     */
    deleteCommand: function(index) {
        var self = this,
            len = self.commandsArray.length;

        if (! len) {
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
