(function($) {

Craft.AmCommand = Garnish.Base.extend(
{
    $searchField:        $('.amcommand__search > input'),
    $container:          $('.amcommand'),
    $commandsContainer:  $('.amcommand__commands'),
    $loader:             $('.amcommand__loader'),
    $commands:           $('.amcommand__commands li'),
    $button:             $('<li><span class="customicon customicon__lightning" title="Command palette"></span></li>').prependTo('#header-actions'),
    ignoreSearchKeys:    [Garnish.UP_KEY, Garnish.DOWN_KEY, Garnish.LEFT_KEY, Garnish.RIGHT_KEY, Garnish.RETURN_KEY, Garnish.ESC_KEY],
    fuzzyOptions:        {
        pre: "<strong>",
        post: "</strong>"
    },
    commandsArray:       [],
    rememberPalette:     {
        commandsArray: null,
        commandsContainer: null
    },
    isOpen:              false,
    loading:             false,
    loadedNewCommands:   false,
    P_KEY:               80,

    init: function() {
        var self = this;

        // Get commands for fuzzy search
        self.$commands.each(function(index, command) {
            self.commandsArray.push($(command).children('.amcommand__commands--name').text().trim());
        });

        // Focus first
        self.$commands.first().addClass('focus');

        // Remember current commands for reset
        self.rememberPalette.commandsArray = self.commandsArray;
        self.rememberPalette.commandsContainer = self.$commandsContainer.html();

        self.bindEvents();
    },

    /**
     * Bind events for the command palette.
     */
    bindEvents: function() {
        var self = this;

        self.addListener(self.$button, 'click', 'openPalette');

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
                self.triggerCommand(ev);
            }
            else if (ev.keyCode == Garnish.ESC_KEY) {
                self.closePalette(ev);
            }
        });

        self.addListener(self.$commands, 'click', 'triggerCommand');

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
            self.$container.fadeIn(400, function() {
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
            self.$container.fadeOut(400, function() {
                // If we have any new commands, reset back to first set of commands
                if (self.loadedNewCommands) {
                    self.loadedNewCommands = false;
                    self.resetPalette(true);
                }
                self.isOpen = false;
                self.loading = false; // Reset loading if the user cancels the page request
            });
            ev.preventDefault();
        }
    },

    /**
     * Reset the command palette.
     *
     * @param bool resetToStart Whether to reset the command palette to it's first set of commands.
     */
    resetPalette: function(resetToStart) {
        var self = this;

        self.$searchField.val('');
        self.$searchField.focus();

        if(resetToStart) {
            // Reset to first set of commands
            self.commandsArray = self.rememberPalette.commandsArray;
            self.$commandsContainer.html(self.rememberPalette.commandsContainer);
            self.$commands = $('.amcommand__commands li');
            self.$commands.show();
        } else {
            // Reset variables
            self.$commands = $('.amcommand__commands li');
            self.commandsArray = [];

            // Get commands for fuzzy search
            self.$commands.each(function(index, command) {
                self.commandsArray.push($(command).children('.amcommand__commands--name').text().trim());
            });
        }

        // Reset clicking event
        self.addListener(self.$commands, 'click', 'triggerCommand');

        // Focus first
        self.$commands.first().addClass('focus');
    },

    /**
     * Search the available commands.
     *
     * @param object ev The triggered event.
     */
    search: function(ev) {
        var self = this;

        if (self.isOpen) {
            // Make sure we don't trigger ignored keys
            if (self.ignoreSearchKeys.indexOf(ev.keyCode) < 0) {
                var searchValue = self.$searchField.val(),
                    results = fuzzy.filter(searchValue, self.commandsArray, self.fuzzyOptions),
                    totalResults = results.length;

                // Hide all
                self.$commands.hide();

                // Find matches
                if (totalResults) {
                    results.map(function(el) {
                        self.$commands
                            .filter('[data-id=' + el.index + ']')
                            .show()
                            .children('.amcommand__commands--name')
                                .html(el.string);
                    });
                }
                // Reset focus and select first command
                self.moveCommandFocus(ev, 'reset');
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
            var $commands = self.$commands.filter(':visible'),
                $current = self.$commands.filter('.focus');

            switch (direction) {
                case 'up':
                    if (! $current.length) {
                        $prev = $commands.first();
                    } else {
                        $prev = $current.prevAll(':visible').first();
                    }
                    if ($prev.length) {
                        $current.removeClass('focus');
                        $prev.addClass('focus');
                    }
                    break;
                case 'down':
                    if (! $current.length) {
                        $next = $commands.first();
                    } else {
                        $next = $current.nextAll(':visible').first();
                    }
                    if ($next.length) {
                        $current.removeClass('focus');
                        $next.addClass('focus');
                    }
                    break;
                case 'reset':
                    self.$commands.removeClass('focus');
                    $commands.first().addClass('focus');
                    break;
            }
            ev.preventDefault();
        }
    },

    /**
     * Navigate to the current focused command.
     *
     * @param object ev The triggered event.
     */
    triggerCommand: function(ev) {
        var self = this;

        if (self.isOpen && ! self.loading) {
            self.loading = true;
            if (ev.type == 'click') {
                var $current = $(ev.currentTarget).children('.amcommand__commands--name');
            } else {
                var $current = self.$commands.filter('.focus').children('.amcommand__commands--name');
            }
            if ($current.length) {
                var callback = $current.data('callback'),
                    callbackService = $current.data('callback-service');
                if (callback !== undefined) {
                    if (callback === undefined) {
                        callbackService = false;
                    }
                    self.triggerCallback(callback, callbackService);
                } else {
                    window.location = $current.data('url');
                    Craft.cp.displayNotice('<span class="amcommand__notice">' + Craft.t('Command') + ' &raquo;</span>' + $current.text());
                    self.closePalette(ev);
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
     */
    triggerCallback: function(name, service) {
        var self = this;

        // Hide current commands and display a loader
        self.$commands.hide();
        self.$loader.removeClass('hidden');

        Craft.postActionRequest('amCommand/commands/triggerCommand', {command: name, service: service}, $.proxy(function(response, textStatus)
        {
            if (textStatus == 'success') {
                self.loading = false;
                self.$loader.addClass('hidden');
                if (response.success)
                {
                    // Remember current commands
                    self.loadedNewCommands = true;
                    // Display new commands
                    self.$commandsContainer.html(response.commands);
                    self.resetPalette();
                }
                else
                {
                    // Show current commands again and display a message
                    self.$commands.show();
                    Craft.cp.displayError(response.message);
                }
            }
        }, self));
    }
});

})(jQuery);