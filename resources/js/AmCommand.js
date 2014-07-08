(function($) {

Craft.AmCommand = Garnish.Base.extend(
{
    $searchField:     $('.amcommand__search > input'),
    $container:       $('.amcommand'),
    $items:           $('.amcommand__items li'),
    ignoreSearchKeys: [Garnish.UP_KEY, Garnish.DOWN_KEY, Garnish.RETURN_KEY, Garnish.ESC_KEY],
    fuzzyOptions:     {
        pre: "<b>",
        post: "</b>"
    },
    itemsArray:       [],
    isOpen:           false,
    P_KEY:            80,

    init: function() {
        var self = this;

        // Get items for fuzzy search
        self.$items.each(function(index, item) {
            self.itemsArray.push($(item).text().trim());
        });

        // Focus first
        self.$items.first().addClass('focus');

        self.bindEvents();
    },

    /**
     * Bind events for the command palette.
     */
    bindEvents: function() {
        var self = this;

        self.addListener(self.$searchField, 'keyup', 'search');

        self.addListener(window, 'keydown', function(ev) {
            if ((ev.metaKey || ev.ctrlKey) && ev.shiftKey && ev.keyCode == self.P_KEY) {
                self.openCommand(ev);
            }
            else if (ev.keyCode == Garnish.UP_KEY) {
                self.moveItemFocus(ev, 'up');
            }
            else if (ev.keyCode == Garnish.DOWN_KEY) {
                self.moveItemFocus(ev, 'down');
            }
            else if (ev.keyCode == Garnish.RETURN_KEY) {
                self.triggerItem(ev);
            }
            else if (ev.keyCode == Garnish.ESC_KEY) {
                self.closeCommand(ev);
            }
        });
    },

    /**
     * Open the command palette.
     *
     * @param object ev The triggered event.
     */
    openCommand: function(ev) {
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
    closeCommand: function(ev) {
        var self = this;

        if (self.isOpen) {
            self.$container.fadeOut(400, function() {
                self.isOpen = false;
            });
            ev.preventDefault();
        }
    },

    /**
     * Search the available items.
     *
     * @param object ev The triggered event.
     */
    search: function(ev) {
        var self = this;

        if (self.isOpen) {
            // Make sure we don't trigger ignored keys
            if (self.ignoreSearchKeys.indexOf(ev.keyCode) < 0) {
                var searchValue = self.$searchField.val(),
                    results = fuzzy.filter(searchValue, self.itemsArray, self.fuzzyOptions),
                    totalResults = results.length;

                // Hide all
                self.$items.hide();

                // Find matches
                if (totalResults) {
                    results.map(function(el) {
                        self.$items
                            .filter('[data-id=' + el.index + ']')
                            .show()
                            .children('a')
                                .html(el.string);
                    });
                }
                // Reset focus and select first item
                self.moveItemFocus(ev, 'reset');
            }
        }
    },

    /**
     * Open the command palette.
     *
     * @param object ev        The triggered event.
     * @param string direction In which direction the focus should go to.
     */
    moveItemFocus: function(ev, direction) {
        var self = this;

        if (self.isOpen) {
            var $items = self.$items.filter(':visible'),
                $current = self.$items.filter('.focus');

            switch (direction) {
                case 'up':
                    if (! $current.length) {
                        $prev = $items.first();
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
                        $next = $items.first();
                    } else {
                        $next = $current.nextAll(':visible').first();
                    }
                    if ($next.length) {
                        $current.removeClass('focus');
                        $next.addClass('focus');
                    }
                    break;
                case 'reset':
                    self.$items.removeClass('focus');
                    $items.first().addClass('focus');
                    break;
            }
            ev.preventDefault();
        }
    },

    /**
     * Navigate to the current focused item.
     *
     * @param object ev   The triggered event.
     */
    triggerItem: function(ev) {
        var self = this;

        if (self.isOpen) {
            var $current = self.$items.filter('.focus').children('a');
            if ($current.length) {
                $current[0].click();
                Craft.cp.displayNotice(Craft.t('Loading') + ' - ' + $current.text());
                self.closeCommand(ev);
            }
            ev.preventDefault();
        }
    }
});

})(jQuery);