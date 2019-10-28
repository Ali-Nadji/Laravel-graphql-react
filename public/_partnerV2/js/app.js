/*
 Template Name: Admiry - Bootstrap 4 Admin Dashboard
 Author: Themesdesign
 Website: www.themesdesign.in
 File: Main js
 */

!function ($) {
    "use strict";

    var MainApp = function () {
        this.$body = $("body"),
            this.$wrapper = $("#wrapper"),
            this.$btnFullScreen = $("#btn-fullscreen"),
            this.$leftMenuButton = $('.button-menu-mobile'),
            this.$menuItem = $('.has_sub > a')
    };
    //scroll
    MainApp.prototype.initSlimscroll = function () {
        $('.slimscrollleft').slimscroll({
            height: 'auto',
            position: 'right',
            size: "10px",
            color: '#9ea5ab'
        });
    },
        //left menu
        MainApp.prototype.initLeftMenuCollapse = function () {
            var $this = this;
            this.$leftMenuButton.on('click', function (event) {
                event.preventDefault();
                $this.$body.toggleClass("fixed-left-void");
                $this.$wrapper.toggleClass("enlarged");
            });
        },
        //left menu
        MainApp.prototype.initComponents = function () {
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        },
        //full screen
        MainApp.prototype.initFullScreen = function () {
            var $this = this;
            $this.$btnFullScreen.on("click", function (e) {
                e.preventDefault();

                if (!document.fullscreenElement && /* alternative standard method */ !document.mozFullScreenElement && !document.webkitFullscreenElement) {  // current working methods
                    if (document.documentElement.requestFullscreen) {
                        document.documentElement.requestFullscreen();
                    } else if (document.documentElement.mozRequestFullScreen) {
                        document.documentElement.mozRequestFullScreen();
                    } else if (document.documentElement.webkitRequestFullscreen) {
                        document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                    }
                } else {
                    if (document.cancelFullScreen) {
                        document.cancelFullScreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.webkitCancelFullScreen) {
                        document.webkitCancelFullScreen();
                    }
                }
            });
        },
        //full screen
        MainApp.prototype.initMenu = function () {
            var $this = this;
            $this.$menuItem.on('click', function () {
                var parent = $(this).parent();
                var sub = parent.find('> ul');

                if (!$this.$body.hasClass('sidebar-collapsed')) {
                    if (sub.is(':visible')) {
                        sub.slideUp(300, function () {
                            parent.removeClass('nav-active');
                            $('.body-content').css({height: ''});
                            adjustMainContentHeight();
                        });
                    } else {
                        visibleSubMenuClose();
                        parent.addClass('nav-active');
                        sub.slideDown(300, function () {
                            adjustMainContentHeight();
                        });
                    }
                }
                return false;
            });

            //inner functions
            function visibleSubMenuClose() {
                $('.has_sub').each(function () {
                    var t = $(this);
                    if (t.hasClass('nav-active')) {
                        t.find('> ul').slideUp(300, function () {
                            t.removeClass('nav-active');
                        });
                    }
                });
            }

            function adjustMainContentHeight() {
                // Adjust main content height
                var docHeight = $(document).height();
                if (docHeight > $('.body-content').height())
                    $('.body-content').height(docHeight);
            }
        },
        MainApp.prototype.activateMenuItem = function () {
            // === following js will activate the menu in left side bar based on url ====
            $("#sidebar-menu a").each(function () {
                if (this.href == window.location.href) {
                    $(this).addClass("active");
                    $(this).parent().addClass("active"); // add active to li of the current link
                    $(this).parent().parent().prev().addClass("active"); // add active class to an anchor
                    $(this).parent().parent().parent().addClass("active"); // add active class to an anchor
                    $(this).parent().parent().prev().click(); // click the item to make it drop
                }
            });
        },
        MainApp.prototype.Preloader = function () {
            $(window).on('load', function() {
                $('#status').fadeOut();
                $('#preloader').delay(350).fadeOut('slow');
                $('body').delay(350).css({
                    'overflow': 'visible'
                });
            });
        },
        MainApp.prototype.init = function () {
            this.initSlimscroll();
            this.initLeftMenuCollapse();
            this.initComponents();
            this.initFullScreen();
            this.initMenu();
            this.activateMenuItem();
            this.Preloader();
        },
        //init
        $.MainApp = new MainApp, $.MainApp.Constructor = MainApp
}(window.jQuery),

//initializing
    function ($) {
        "use strict";
        $.MainApp.init();
    }(window.jQuery);


jQuery.fn.extend({


    /**
     * "prepend event" functionality as a jQuery plugin
     * @link http://stackoverflow.com/questions/10169685/prepend-an-onclick-action-on-a-button-with-jquery
     *
     * @param event
     * @param handler
     * @returns {*}
     */
    prependEvent: function (event, handler) {
        return this.each(function () {
            var events = $(this).data("events"),
                currentHandler;

            if (events && events[event].length > 0) {
                currentHandler = events[event][0].handler;
                events[event][0].handler = function () {
                    handler.apply(this, arguments);
                    currentHandler.apply(this, arguments);
                }
            }
        });
    },

    // INITAILISATION
    initialize: function (callback) {
        // MODAL
        jQuery(this).find('.modal-remote').each(function () {

            jQuery(this).click(function (e) {
                e.preventDefault();
                var target = jQuery(this).data('target');
                var size = jQuery(this).data('size');
                $url = jQuery(this).attr('href');

                $data = {};
                if (jQuery(this).data('method')) {
                    $data = {_method: jQuery(this).data('method')}
                }

                jQuery(target)
                    .find('.modal-content')
                    .empty()
                    .load($url, $data, function () {
                        jQuery(this).initialize();
                        jQuery(this).parent().removeClass('modal-lg modal-sm').addClass(size);
                        jQuery(target).modal('show');
                        jQuery(target).on('hidden.bs.modal', function () {
                            jQuery(this).find('.modal-content').html('');
                        });
                    });
                e.stopImmediatePropagation();
            });
        });

        // FORM
        if (jQuery.fn.ajaxForm != undefined) {
            // FORM REMOTE
            jQuery(this).find('.form-remote').ajaxForm({

                beforeSubmit: function (a, f) {
                    jQuery(f).find("input[type='submit']")
                        .attr("disabled", "disabled")
                        .attr("value", "En cours ...");
                },

                success: function (html) {
                    console.log(html)
                    jQuery('.modal-content')
                        .empty()
                        .html(html)
                        .initialize();
                }
            });

            // FORM CALLBACK
            jQuery(this).find('.form-callback').ajaxForm({

                beforeSubmit: function (a, f) {
                    jQuery(f).find("input[type='submit']")
                        .attr("disabled", "disabled")
                        .attr("value", "En cours ...");
                },

                success: function (js) {
                    eval(js);
                }
            });
        }

        // ajout du compteur sur le text area
        $('textarea.ff-text-count[maxlength]').each(function () {

            var _this = $(this);
            var _parent = _this.parent();

            // ajout du compteur if nécessary
            if(! ($(this).next().hasClass("pull-right") && ($(this).next().children(":first").hasClass("txt-current")))) {

                $('<div class="pull-right"><span class="txt-current"></span><span class="txt-max"></span></div>')
                    .insertAfter(_this);

                _parent.find('span.txt-max').html(" / " + _this.prop('maxlength'));

                _this.on("click mousedown mouseup focus blur keydown change", function () {
                    _parent.find('span.txt-current').text($(this).val().length);
                });

                _this.change();
            }

        });

        // CALLBACK
        jQuery(this).find('.callback-remote').each(function () {
            jQuery(this).click(function (e) {

                if (jQuery(this).data('method')) {

                    $data = {};
                    if (jQuery(this).data('method')) {
                        $data = {_method: jQuery(this).data('method')}
                    }

                    jQuery.post(
                        jQuery(this).attr('href'),
                        $data,
                        function (a) {
                            eval(a);
                        }
                    );
                } else {
                    jQuery.getScript(jQuery(this).attr('href'));
                }

                e.preventDefault();
                e.stopImmediatePropagation();
            });
        });

        // INPUT CALLBACK
        jQuery(this).find('.input-callback').each(function () {
            jQuery(this).change(function (e) {

                if (jQuery(this).data('method')) {
                    jQuery.post(
                        jQuery(this).data('action'),
                        {_method: jQuery(this).data('method'), value: jQuery(this).val()},
                        function (a) {
                            eval(a);
                        }
                    );
                } else {
                    jQuery.getScript(jQuery(this).attr('href'));
                }

                e.preventDefault();
                e.stopImmediatePropagation();
            });
        });

        // SELECT 2
        if (jQuery.fn.select2 != undefined) {

            jQuery(this).find('select.select2').each(function () {
                jQuery(this).select2();
            });

            jQuery(this).find('.select2-remote').each(function () {

                jQuery(this).select2({
                    minimumInputLength: jQuery(this).data('length'),
                    ajax: {
                        url: jQuery(this).data('remote'),
                        dataType: 'json',
                        delay: 250,
                        data: function (term) {
                            return {
                                q: term // search term
                            };
                        },
                        results: function (d) {
                            return d;
                        }
                    },

                    containerCssClass: jQuery(this).data('css'),
                    formatResult: function (i) {
                        return i.text;
                    },
                    formatSelection: function (i) {
                        return i.text;
                    },

                    escapeMarkup: function (markup) {
                        return markup;
                    },

                    initSelection: function (element, callback) {
                        $.ajax(jQuery(element).data('remote') + '?id=' + jQuery(element).val(), {dataType: "json"})
                            .done(function (data) {
                                if (data.results[0]) {
                                    callback(data.results[0]);
                                }
                            });
                    },
                });
            });
        }


        // LIAISON SELECT
        jQuery('.select-remote').each(function () {
            var $that = $(this);
            selector = jQuery(this).data('parent-selector');
            jQuery(selector).change(function (e) {
                url = $that.data('parent-url') + '?value=' + jQuery(this).val();
                jQuery.getJSON(url, function (a) {
                    populate = $that.data('populate');
                    $that.empty();
                    jQuery.each(a, function (i, v) {
                        selected = '';
                        if (populate == i) {
                            selected = 'selected'
                        }
                        $that.append(jQuery("<option " + selected + "/>").val(i).text(v));
                    });
                });
            }).change();
        });

        // UNIFORM
        if (jQuery.fn.uniform != undefined) {
            jQuery(this).find("input[type=checkbox]:not(.toggle, .make-switch), input[type=radio]:not(.toggle, .star, .make-switch)").each(function () {
                jQuery(this).uniform();
            });
        }

        // DATEPICKER
        if (jQuery.fn.datepicker != undefined) {
            jQuery(this).find('.date-picker').datepicker({
                autoclose: true
            });
        }

        // TIMEPICKER
        if (jQuery.fn.timepicker != undefined) {
            jQuery(this).find(".timepicker-24").timepicker({
                autoclose: true,
                minuteStep: 1,
                showSeconds: true,
                showMeridian: false
            });
        }

        // TOOLTIP
        if (jQuery.fn.tooltip != undefined) {

            jQuery('[data-toggle="tooltip"]').tooltip({container: 'body'});

            jQuery(this).find('.ff-tooltip-left').tooltip({
                container: 'body',
                placement: 'left'
            });

            jQuery(this).find('.ff-tooltip-bottom').tooltip({
                container: 'body',
                placement: 'left'
            });
        }

        // SWITCH
        if (jQuery.fn.bootstrapSwitch !== undefined) {

            /**
             * Table Remote Boolean
             */
            jQuery(this).find('input[type=checkbox].ff-remote-boolean').bootstrapSwitch({
                onSwitchChange: function (event, state) {
                    event.preventDefault();
                    jQuery.post(jQuery(this).closest('.datatable-remote').DataTable().ajax.url(), {
                        id: jQuery(this).data('id'),
                        column: jQuery(this).data('column')
                    }, function (e, f) {
                        eval(e)
                    });
                }
            });

            jQuery(this).find('input[type=checkbox].make-switch:not(.ff-remote-boolean)').bootstrapSwitch();
        }


        /**
         * Table remote text
         */
        jQuery(this).find('div.ff-remote-text')
            .dblclick(function (e) {

                // if element is active, we disable action
                if (jQuery(this).hasClass('ff-remote-active')) {
                    return jQuery(this);
                }

                // mark element as actiove
                jQuery(this).addClass('ff-remote-active');

                // hide span
                var _that = jQuery(this).find('span').hide();

                // input setting
                jQuery(this).find('input')
                    .on('ff.remote.text', function () {
                        // disable input
                        jQuery(this)
                            .off('focusout')
                            .off('ff.remote.text')
                            .off('ff.remote.process');
                        jQuery(this).hide().prev('span').show();
                        jQuery(this).parent('.ff-remote-active').removeClass('ff-remote-active');
                    })
                    .on('ff.remote.process', function () {
                        // process modifiction
                        e.preventDefault();
                        if (_that.html() != jQuery(this).val()) {
                            _that.html(jQuery(this).val());
                            jQuery.post(jQuery(this).closest('.datatable-remote').DataTable().ajax.url(), {
                                id: jQuery(this).data('id'),
                                column: jQuery(this).data('column'),
                                value: jQuery(this).val()
                            }, function (e, f) {
                                eval(e)
                            });
                        }
                        jQuery(this).trigger('ff.remote.text');
                        return false;
                    })
                    .val(_that.html()) // set value
                    .show()
                    .focus()
                    .focusout(function () {
                        jQuery(this).trigger('ff.remote.text');
                    }).keypress(function (e) {
                    // enable process on enter key
                    if (e.which == 13) {
                        jQuery(this).trigger('ff.remote.process');
                    }
                });
            });

        // TABLE REMOTE SELECT
        jQuery(this).find('select.ff-remote-select').change(
            function (e) {

                e.preventDefault();
                e.stopImmediatePropagation();

                // if element is active, we disable action
                if (jQuery(this).hasClass('ff-remote-active')) {
                    return jQuery(this);
                }

                // mark element as actiove
                jQuery(this).addClass('ff-remote-active');

                jQuery.post(jQuery(this).closest('.datatable-remote').DataTable().ajax.url(), {
                    id: jQuery(this).data('id'),
                    column: jQuery(this).data('column'),
                    value: jQuery(this).val()
                }, function (e, f) {
                    eval(e)
                });
            }
        );

        //DATATABLE DECORATION
        jQuery(this).find('table.table > thead > tr:last-child').children().css('border-bottom', '1px solid #ddd');


        // Edition des datatatable
        jQuery('.ff-edit[data-edit-id]').contextmenu(function () {

            // reécupération des informations
            var $target = jQuery(this).data('target');
            var $url = jQuery(this).data('url');

            $data = {_method: 'get', 'id': jQuery(this).data('edit-id')};

            jQuery($target)
                .find('.modal-content')
                .empty()
                .load($url, $data, function(){
                    jQuery(this).initialize();
                    jQuery($target).modal('show');
                    jQuery($target).on('hidden.bs.modal', function() {
                        jQuery(this).find('.modal-content').html('');
                    });
                });

            return false;
        });

        // SimpleMDE
        if (window.SimpleMDE != undefined) {
            jQuery('textarea.ff-markdown').each(function() {
                new SimpleMDE({ element: document.getElementById($(this).attr('id')), forceSync: true});
            });
        }

        // HIGHLIGHT.JS
        if (window.hljs != undefined) {
            jQuery('code.ff-highlight').each(function(i, block) {
                hljs.highlightBlock(block);
            });
        }

        // KNOB
        if (jQuery.fn.knob != undefined) {
            jQuery(this).find('.ff-knob').knob();
        }

        // CALLBACK
        if (typeof callback === 'function') {
            callback(jQuery(this));
        }

    },

    /*
    // Populate a form in javascript
    populate: function (data) {

        // get the form
        var $form = $(this);

        // for each index we assigen value
        $.each(data, function (i, v) {
            e = $form.find('#' + i);

            // if only one item
            if (e.length == 1) {
                // case switch (boolean)
                if (e.hasClass('make-switch')) {
                    e.bootstrapSwitch('state', v);
                } else {
                    e.val(v);
                }
            }
        });
    },

    // add disabled class to elements
    disable: function (state) {
        return this.each(function () {
            $(this).addClass('disabled');
        });
    },

    // remove class disabled to element
    enable: function (state) {
        return this.each(function () {
            $(this).removeClass('disabled');
        });
    }
    */

});


