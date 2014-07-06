(function($) {

Craft.AmCommand = Garnish.Base.extend(
{
    $searchField:     $('.amcommand__search > input'),
    $container:       $('.amcommand'),
    $itemsContainer:  $('.amcommand__items'),
    $items:           null,
    fuse:             null,
    fuseOptions: {
        location: 0,
        threshold: 0.4
    },
    ignoreSearchKeys: [Garnish.UP_KEY, Garnish.DOWN_KEY, Garnish.RETURN_KEY, Garnish.ESC_KEY],
    items:            [],
    isOpen:           false,
    limit:            10,
    P_KEY:            80,

    init: function(params) {
        var self = this;
        // Items
        self.$items = self.$itemsContainer.find('li');
        self.$items.hide().filter(':lt(' + self.limit + ')').show();
        var items = self.$itemsContainer.find('li > a');
        items.each(function(index, item) {
            self.items.push(item.text);
        });
        // Create Fuse object
        self.fuse = new Fuse(self.items, self.fuseOptions);
        // Listeners
        self.addListener(self.$searchField, 'keyup', 'search');
        self.addListener(window, 'keydown', function(ev) {
            if ((ev.metaKey || ev.ctrlKey) && ev.shiftKey && ev.keyCode == self.P_KEY) {
                self.openCommand(self, ev);
            }
            else if (ev.keyCode == Garnish.UP_KEY) {
                self.moveItemFocus(self, ev, 'up');
            }
            else if (ev.keyCode == Garnish.DOWN_KEY) {
                self.moveItemFocus(self, ev, 'down');
            }
            else if (ev.keyCode == Garnish.RETURN_KEY) {
                self.triggerItem(self, ev);
            }
            else if (ev.keyCode == Garnish.ESC_KEY) {
                self.closeCommand(self, ev);
            }
        });
    },

    /**
     * Open the command palette.
     *
     * @param object self AmCommand object.
     * @param object ev   The triggered event.
     */
    openCommand: function(self, ev) {
        if (! self.isOpen) {
            self.$container.fadeIn(400, function() {
                self.isOpen = true;
                self.$searchField.focus();
            });
        }
        ev.preventDefault();
    },

    /**
     * Close the command palette.
     *
     * @param object self AmCommand object.
     * @param object ev   The triggered event.
     */
    closeCommand: function(self, ev) {
        if (self.isOpen) {
            self.$container.fadeOut(400, function() {
                self.isOpen = false;
            });
        }
        ev.preventDefault();
    },

    /**
     * Search the available items.
     *
     * @param object ev The triggered event.
     */
    search: function(ev) {
        if (this.isOpen) {
            // Make sure we don't trigger ignored keys
            if (this.ignoreSearchKeys.indexOf(ev.keyCode) < 0) {
                var self = this,
                    searchValue = this.$searchField.val(),
                    results = this.fuse.search(searchValue),
                    totalResults = results.length;
                // Hide all
                self.$items.hide();
                // Find matches
                if (totalResults) {
                    var $matches = self.$items.filter(function(index) {
                        return jQuery.inArray($(this).data('id'), results) > -1;
                    });
                    $matches.filter(':lt(' + self.limit + ')').show();
                }
                else if (searchValue === '') {
                    self.$items.filter(':lt(' + self.limit + ')').show();
                }
                // Reset focus and select first item
                self.moveItemFocus(self, ev, 'reset');
            }
        }
    },

    /**
     * Open the command palette.
     *
     * @param object self      AmCommand object.
     * @param object ev        The triggered event.
     * @param string direction In which direction the focus should go to.
     */
    moveItemFocus: function(self, ev, direction) {
        if (self.isOpen) {
            var $items = self.$items.filter(':visible');
            switch (direction) {
                case 'up':
                    var $current = self.$itemsContainer.find('li.focus');
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
                    var $current = self.$itemsContainer.find('li.focus');
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
        }
        ev.preventDefault();
    },

    /**
     * Navigate to the current focused item.
     *
     * @param object self AmCommand object.
     * @param object ev   The triggered event.
     */
    triggerItem: function(self, ev) {
        if (self.isOpen) {
            var $current = self.$itemsContainer.find('li.focus > a');
            if ($current.length) {
                $current[0].click();
            }
            ev.preventDefault();
        }
    }
});

})(jQuery);