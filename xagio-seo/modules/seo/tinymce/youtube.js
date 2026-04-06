(function () {
    tinymce.PluginManager.add('xagio_youtube', function (editor, url) {
        editor.addButton('xagio_youtube', {
            title  : 'YouTube Search',
            image  : xagio_data.plugins_url + 'assets/img/tinymce/youtube.webp',
            onclick: function () {
                modal = document.getElementById("youtubeModal");
                modal.showModal();
            }
        });
    });
})();
