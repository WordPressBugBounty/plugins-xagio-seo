(function () {

    tinymce.PluginManager.add('xag_keywords', function (editor, url) {

        var groups = [];
        var group  = {};

        if (xagio_tinymce_data.keywords.length < 1) {
            group = {
                text   : 'No Keywords',
                value  : 'There are no available keywords for this post/page!',
                onclick: function () {
                    editor.insertContent(this.value());
                }
            };
            groups.push(group);
        } else {
            jQuery.each(xagio_tinymce_data.keywords, function (index, value) {

                group = {
                    text: (index !== '') ? index : '--- Unnamed ---',
                    menu: []
                };

                jQuery.each(value, function (i, v) {

                    var body = [
                        {
                            type       : 'textbox',
                            name       : 'url',
                            label      : 'Anchor URL:',
                            value      : (v.url === "") ? '' : document.location.protocol + '//' + document.location.host + '/' + v.url + '/',
                            placeholder: 'eg. http://mywebsite.com/url'
                        },
                        {
                            name : 'capitalize',
                            type : 'checkbox',
                            label: 'Capitalize first word:'
                        },
                        {
                            name : 'target',
                            type : 'checkbox',
                            label: 'Open in new window:'
                        }
                    ];

                    var keyword = {
                        text   : v.keyword,
                        value  : '[xagio_project_keyword]',
                        onclick: function () {
                            editor.windowManager.open({
                                title   : 'Keyword - ' + v.keyword,
                                width   : 800,
                                height  : 150,
                                body    : body,
                                onsubmit: function (e) {
                                    editor.insertContent('[xagio_project_keyword keyword="' + v.keyword + '" url="' + e.data.url + '" capitalize=' + e.data.capitalize + ' target=' + e.data.target + ']');
                                }
                            });
                        }
                    };

                    group.menu.push(keyword);
                });


                groups.push(group);
            });
        }

        editor.addButton('xag_keywords', {
            title: 'Project Keywords',
            type : 'menubutton',
            image: xagio_data.plugins_url + 'assets/img/tinymce/keywords.webp',
            menu : groups
        });
    });
})();
