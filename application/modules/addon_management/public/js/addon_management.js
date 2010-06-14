/**
 * JS enhancements for addon management
 */
BYOB_Repacks_Edit_AddonManagement = (function () {

    var $this = {

        // Indexed map of selections in sidebar to choices in UI.
        _selections_map: [],

        /**
         * Initialization
         */
        init: function () {
            $(document).ready($this.onready);
            $(document).unload($this.onunload);
            return $this;
        },

        /**
         * Do some cleanup on page unload.
         */
        onunload: function () {
            $this._selections_map = null;
        },

        /**
         * Document ready handler.
         */
        onready: function () {

            $this.wireUpSelectionsPane();
            $this.updateSelectionsPane();
            $this.setupPrettyUploads();

            // When the "No Persona" choice is selected, clear the persona URL
            // field for good measure.
            $('.personas li input').click(function () {
                $('.persona_url').val('');
            });
        },

        /**
         * Make the file upload fields pretty.
         */
        setupPrettyUploads: function () {

            // Make file upload fields styleable through magic and trickery.
            $('.pretty_upload').each(function () {

                var root_el = $(this),
                    input_el = root_el.find('input[type=file]');

                // Cram some styleable markup into the page as a fake file
                // upload widget.
                input_el.after($(
                    '<span class="fake_upload">' +
                        '<input class="text" type="text" />' +
                        '<button class="browse button grey">Browse</button>' +
                    '</span>'
                ));

                var text_el = root_el.find('.fake_upload input[type=text]')
                    browse_el = root_el.find('.fake_upload .browse');

                // Hide the file upload element, but make the fake text field
                // track any value changes in the file upload.
                input_el
                    .addClass('hidden')
                    .change(function (ev) {
                        text_el.val(input_el.val());
                    });

                // Whenever the fake button is clicked, proxy to the real file
                // upload field.
                browse_el
                    .click(function (ev) {
                        input_el.click();
                        return false;
                    });

            });

        },

        /**
         * Wire up the interaction handlers for the selections pane.
         */
        wireUpSelectionsPane: function () {

            // Any click on an input element under .choices (eg. checkboxen,
            // radio buttons) should trigger a sidebar update.
            $('.choices input')
                .click($this.updateSelectionsPane);

            $('.selections')
                // Delegated click on the remove link should trigger removal.
                .bind('click', function (ev) {
                    var target = $(ev.target);
                    if (target.parent().hasClass('remove_link'))
                        target = target.parent();
                    if (target.hasClass('remove_link')) {
                        var item = target.parent();
                        $this.removeSelection(item);
                    }
                });

        },

        /**
         * Remove the given selection by reversing the choice in the UI.
         */
        removeSelection: function (item) {
            var idx = item.attr('data-selection-index'),
                choice = $this._selections_map[idx];

            if (item.hasClass('theme')) {
                $('#theme_id_none').click();
            } else if (item.hasClass('persona')) {
                $('#persona_id_none').click();
            } else if (item.hasClass('extensionUpload')) {
            } else if (item.hasClass('searchpluginUpload')) {
            } else {
                choice.find('input:checkbox')
                    .attr('checked', false);
            }

            $this.updateSelectionsPane();
        },

        /**
         * Update the contents of the selection pane from the choices made in
         * the UI.
         */
        updateSelectionsPane: function () {

            var list = $('.selections .addon-selections'),
                tmpl = list.find('.template');

            $this._selections_map = [];
            list.find('li:not(.template)').remove();

            $('#tab-extensions-upload').contents().find('.uploads li')
                .each(function () {
                    var item = $(this);
                    $this._selections_map.push(item);
                    tmpl.cloneTemplate({
                        '@class': 'extensionUpload',
                        '@data-selection-index': $this._selections_map.length - 1,
                        '.name': item.text()
                    }).appendTo(list);
                });

            $('.extensions li input:checked')
                .each(function () {
                    var item = $(this).parent();
                    $this._selections_map.push(item);
                    tmpl.cloneTemplate({
                        '@class': 'extension',
                        '@data-selection-index': $this._selections_map.length - 1,
                        '.name': item.find('.name').text()
                    }).appendTo(list);
                });

            $('#tab-searchplugins-upload').contents().find('.uploads li')
                .each(function () {
                    var item = $(this);
                    $this._selections_map.push(item);
                    tmpl.cloneTemplate({
                        '@class': 'searchpluginUpload',
                        '@data-selection-index': $this._selections_map.length - 1,
                        '.name': item.text()
                    }).appendTo(list);
                });

            $('.searchplugins li input:checked')
                .each(function () {
                    var item = $(this).parent();
                    $this._selections_map.push(item);
                    tmpl.cloneTemplate({
                        '@class': 'searchplugin',
                        '@data-selection-index': $this._selections_map.length - 1,
                        '.name': item.find('.name').text()
                    }).appendTo(list);
                });

            $('.personas li input:checked')
                .each(function () {
                    var item = $(this).parent();
                    if (item.hasClass('none')) return;
                    $this._selections_map.push(item);
                    tmpl.cloneTemplate({
                        '@class': 'persona',
                        '@data-selection-index': $this._selections_map.length - 1,
                        '.name': item.find('.name').text()
                    }).appendTo(list);
                });

            $('.themes li input:checked')
                .each(function () {
                    var item = $(this).parent();
                    if (item.hasClass('none')) return;
                    $this._selections_map.push(item);
                    tmpl.cloneTemplate({
                        '@class': 'theme',
                        '@data-selection-index': $this._selections_map.length - 1,
                        '.name': item.find('.name').text()
                    }).appendTo(list);
                });

            // HACK: Sweep through the added list items, look for class name
            // transitions and tag items with divider class
            var last_cls = '';
            list.find('li:not(.template)').each(function () {
                var item = $(this),
                    curr_cls = item.attr('class');
                if ((last_cls != '') && (curr_cls != last_cls)) {
                    item.addClass('divider');
                }
                last_cls = curr_cls;
            });

        },

        EOF: null
    };

    return $this.init();

})();
