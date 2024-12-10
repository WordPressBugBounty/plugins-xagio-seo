(function () {

    tinymce.PluginManager.add('xagio_shortcodes', function (editor, url) {

        var groups    = [];
        var shortcode = {};

        if (xagio_tinymce_data.shortcodes.length < 1) {
            groups.push({
                text   : 'No Shortcodes',
                value  : 'There are no available affiliate shortcodes! Please create some!',
                onclick: function () {
                    return false;
                }
            });
        } else {
            // Create Groups
            jQuery.each(xagio_tinymce_data.shortcodes, function (index, array) {
                var found     = false;
                var groupName = array.group;
                for (var i = 0; i < groups.length; i++) {
                    var g = groups[i];
                    if (g.text == groupName) {
                        found = true;
                    }
                }
                if (!found) {
                    groups.push({
                        text: groupName,
                        menu: []
                    });
                }
            });

            // Add Shortcodes to Groups
            jQuery.each(xagio_tinymce_data.shortcodes, function (index, array) {
                shortcode = {
                    text   : array.shortcode,
                    value  : '[' + array.shortcode + ']',
                    onclick: function () {

                        editor.windowManager.open({
                            title   : 'Keyword - ' + array.shortcode,
                            width   : 400,
                            height  : 100,
                            body    : [
                                {
                                    type : 'textbox',
                                    name : 'title',
                                    label: 'Anchor Text / Title:',
                                    value: array.title
                                }
                            ],
                            onsubmit: function (e) {
                                editor.insertContent('[' + array.shortcode + ' title="' + e.data.title + '"]');
                            }
                        });

                    }
                };

                for (var i = 0; i < groups.length; i++) {
                    if (groups[i].text == array.group) {
                        groups[i].menu.push(shortcode);
                        break;
                    }
                }

            });
        }

        editor.addButton('xagio_shortcodes', {
            title: 'Xagio Shortcodes',
            type : 'menubutton',
            image: xagio_data.plugins_url + 'assets/img/tinymce/shortcodes.webp',
            menu : groups
        });
    });
})();
