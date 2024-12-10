(function () {
    tinymce.PluginManager.add('xagio_pixabay', function (editor, url) {
        editor.addButton('xagio_pixabay', {
            title  : 'Pixabay Image Search',
            image  : xagio_data.plugins_url + 'assets/img/tinymce/pixabay.webp',
            onclick: function () {
                modal = document.getElementById("pixabayModal");
                modal.showModal();
            }
        });
    });
})();