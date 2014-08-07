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
                self.triggerCommand(ev, (ev.metaKey || ev.ctrlKey));
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
                // If we have any new commands, reset back to first set of commands
                if (self.loadedNewCommands) {
                    self.loadedNewCommands = false;
                    self.resetPalette(true);
                }
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
                        self.keepCommandVisible($prev);
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
                        self.keepCommandVisible($next);
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
     * Scroll to make the current focused item visible when necessary.
     *
     * @param object current Current focused item.
     */
    keepCommandVisible: function(current) {
        var self = this,
            currentTop = current.offset().top,
            currentHeight = current.outerHeight(),
            containerTop = self.$commandsContainer.offset().top,
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
     * Navigate to the current focused command.
     *
     * @param object ev          The triggered event.
     * @param bool   ctrlPressed Whether the CTRL or Command key was pressed.
     */
    triggerCommand: function(ev, ctrlPressed) {
        var self = this;

        if (self.isOpen && ! self.loading) {
            if (ev.type == 'click') {
                if (ev.ctrlKey || ev.metaKey) {
                    ctrlPressed = true;
                }
                var $current = $(ev.currentTarget).children('.amcommand__commands--name');
                $current.addClass('focus');
            } else {
                var $current = self.$commands.filter('.focus').children('.amcommand__commands--name');
            }
            if ($current.length) {
                var confirmed = true,
                    warn = $current.data('warn'),
                    url = $current.data('url'),
                    callback = $current.data('callback'),
                    callbackService = $current.data('callback-service'),
                    callbackData = $current.data('callback-data');
                // Do we have to show a warning?
                if (warn !== undefined && warn == '1') {
                    var confirmation = confirm(Craft.t('Are you sure you want to execute this command?'));
                    if (! confirmation) {
                        confirmed = false;
                    }
                }
                // Can we execute the command?
                if (confirmed) {
                    self.loading = true;
                    if (callback !== undefined) {
                        if (callback === undefined) {
                            callbackService = false;
                        }
                        self.triggerCallback(callback, callbackService, callbackData);
                    }
                    else if (url !== undefined) {
                        // Open the URL in a new window if the CTRL or Command key was pressed
                        if (ctrlPressed) {
                            window.open(url);
                        } else {
                            window.location = url;
                        }
                        Craft.cp.displayNotice('<span class="amcommand__notice">' + Craft.t('Command executed') + ' &raquo;</span>' + $current.text());
                        self.closePalette(ev);
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
     */
    triggerCallback: function(name, service, data) {
        var self = this,
            $current = self.$commands.filter('.focus').children('.amcommand__commands--name');

        // Hide current commands and display a loader
        self.$commands.hide();
        self.$loader.removeClass('hidden');

        Craft.postActionRequest('amCommand/commands/triggerCommand', {command: name, service: service, data: data}, $.proxy(function(response, textStatus)
        {
            if (textStatus == 'success') {
                self.loading = false;
                self.$loader.addClass('hidden');
                if (response.success)
                {
                    if (response.isNewSet) {
                        // Remember current commands
                        self.loadedNewCommands = true;
                        // Display new commands
                        self.$commandsContainer.html(response.result);
                        self.resetPalette();
                    } else {
                        // Show executed message and close palette
                        Craft.cp.displayNotice('<span class="amcommand__notice">' + Craft.t('Command executed') + ' &raquo;</span>' + $current.text());
                        self.closePalette();
                    }
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