let rescue_info = [
    `<i class="xagio-icon xagio-icon-info"></i> <b>Read before usage!</b><br>  If you just want to quickly overwrite all of the core WordPress files while removing all files that are not recognized as WordPress core files, go with the Easy Mode. However, if you would like to select custom lists of file to overwrite / remove, select the Advanced Mode.`,
    `<i class="xagio-icon xagio-icon-info"></i> <b>Read before usage!</b><br>  Unlike WordPress rescue, you will not have two modes here. Only easy mode will be available, meaning that all plugin files will automatically be replaced/added/deleted based on the provided version of the plugin. This feature is this way to maximize security of your plugins.`,
    `<i class="xagio-icon xagio-icon-info"></i> <b>Read before usage!</b><br> Using Uploads rescue will scan your WordPress uploads directory for any files that are executable or potentially dangerous. You may choose which to keep and which to remove once the scan is completed.`,
];

(function ($) {
    'use strict';

    $(document).ready(function () {

        $(document).on('change.uk.tab', function (e, active, prev) {
            if (typeof active != 'undefined') {
                let currentTabIndex = active.index();
                let settingsInfo = $('.rescue-info');
                settingsInfo.html(rescue_info[currentTabIndex]);
            }
        });

        coreRescue.closeRescue();
        coreRescue.beginRescue();
        coreRescue.downloadCoreFiles();
        coreRescue.previewCoreFiles();
        coreRescue.startCoreRescue();
        coreRescue.removeOldFiles();

        pluginsThemesRescue.scanForPluginsThemes();
        pluginsThemesRescue.uninstallPluginTheme();
        pluginsThemesRescue.normalRescuePluginTheme();
        pluginsThemesRescue.uploadRescuePluginTheme();

        uploadsRescue.scanUploads();
        uploadsRescue.removeSingle();
        uploadsRescue.removeSelected();

    });


    let uploadsRescue = {
        removeSelected         : function () {
            $(document).on('click', '.rescue-uploads-remove-selected', function (e) {
                e.preventDefault();

                let files = [];
                let lis = [];
                $('.rescue-uploads-select:checked').each(function () {

                    let li = $(this).parents('.rescue-upload-template');
                    let path = li.data('path');

                    files.push(path);
                    lis.push(li);
                });

                if (files.length < 1) {
                    xagioNotify("danger", "You must select some files first!");
                    return;
                }


                xagioModal("Are you sure?", "Are you sure that you want to remove <b>all selected files</b>?", function (yes) {
                    if (yes) {

                        let data = {
                            action: 'xagio_remove_uploads',
                            files : files
                        };

                        $.post(xagio_data.wp_post, data, function (d) {

                            xagioNotify("success", "Files successfully removed.");

                            for (let i = 0; i < lis.length; i++) {
                                let li = lis[i];
                                li.remove();
                            }

                            uploadsRescue.checkIfRemainingUploads();

                        });
                    }
                })
            });
        },
        removeSingle           : function () {
            $(document).on('click', '.rescue-uploads-remove', function (e) {
                e.preventDefault();

                let li = $(this).parents('.rescue-upload-template');
                let path = li.data('path');

                xagioModal("Are you sure?", "Are you sure that you want to remove file <br> <b>" + path + "</b> ?", function (yes) {
                    if (yes) {

                        let data = {
                            action: 'xagio_remove_uploads',
                            files : [path]
                        };

                        $.post(xagio_data.wp_post, data, function (d) {

                            xagioNotify("success", "File successfully removed.");
                            li.remove();

                            uploadsRescue.checkIfRemainingUploads();

                        });
                    }
                })

            });
        },
        checkIfRemainingUploads: function () {

            let count = $('.rescue-uploads-files > .rescue-upload-template').length;

            if (count < 1) {

                actions.hide('.rescue-uploads-files');
                actions.hide('.rescue-uploads-remove-selected');

                let alert = $('.rescue-uploads-alert');

                alert.removeClass('xagio-alert-danger').addClass('xagio-alert-primary');
                alert.find('p').html("Good job! No more suspicious files remaining. Your uploads directory is now safe.");

            }

        },
        scanUploads            : function () {

            let data = {
                action: 'xagio_scan_uploads'
            };

            $.post(xagio_data.wp_post, data, function (d) {

                $('.rescue-loading-skeleton.second').remove();

                let alert = $('.rescue-uploads-alert');
                alert.removeClass('xagio-hidden');

                if (Object.keys(d.data).length < 1) {

                    alert.addClass('xagio-alert-primary');
                    alert.find('h2').html('Good news!');
                    alert.find('p').html('We didn\'t find any suspicious files or folders inside of your Uploads directory. To manage your existing uploads, head over to <a href="/wp-admin/upload.php">Media Library</a>.');

                } else {

                    actions.show('.rescue-uploads-remove-selected');

                    alert.addClass('xagio-alert-danger');
                    alert.find('h2').remove();
                    alert.find('p').html('<i class="xagio-icon xagio-icon-info"></i> <b>We found some suspicious files!</b> Check the list of files below this notification. These files are potentially suspicious and should be further examined / removed.');

                    let container = $('.rescue-uploads-files');
                    container.empty();

                    container.removeClass('xagio-hidden');

                    let very_dangerous = [];
                    let dangerous = [];
                    let suspicious = [];
                    let template = $('.rescue-upload-template');

                    for (let file in d.data) {
                        let data = d.data[file];
                        let path = data.path;
                        let ext = file.split('.')[1];
                        let id = actions.generateID();

                        let severityClass = 'suspicious';
                        let severity = 'Suspicious';

                        let row = template.clone();
                        row.removeClass('xagio-hidden');

                        row.attr('data-path', path);
                        row.attr('data-id', id);
                        row.find('.rescue-name').html(file);
                        row.find('.rescue-location').html("<i class='xagio-icon xagio-icon-folder'></i> " + path);

                        if (ext === 'php' || ext === 'vba' || ext === 'vbs') {
                            severityClass = 'very-dangerous';
                            severity = 'Critical';

                            row.find('.rescue-type').addClass(severityClass).html(severity);
                            very_dangerous.push(row);

                        } else if (ext === 'exe' || ext === 'dmg' || ext === 'js') {
                            severityClass = 'dangerous';
                            severity = 'Dangerous';

                            row.find('.rescue-type').addClass(severityClass).html(severity);
                            very_dangerous.push(row);

                        } else {

                            severityClass = 'suspicious';
                            severity = 'Suspicious';

                            row.find('.rescue-type').addClass(severityClass).html(severity);
                            very_dangerous.push(row);

                        }
                    }

                    container.append(very_dangerous);
                    container.append(dangerous);
                    container.append(suspicious);

                }

            });

        }
    };

    let pluginsThemesRescue = {
        uploadRescuePluginTheme: function () {
            $(document).on('change', '.plugin-theme-upload', function (e) {

                let c = $(this).parents('.rescue-plugin-theme-template');
                let name = c.find('.rescue-name').text();
                let type = c.data('type');
                let slug = c.data('slug');
                let $this = this;

                xagioModal("Are you sure?", "Are you sure that you want to rescue " + type + " " + name + "?", function (yes) {
                    if (yes) {
                        let file = $this.files[0];
                        let upload = new Upload(file);

                        let fileType = upload.getType();

                        // if file type does not contain zip
                        if (fileType.indexOf('zip') === -1) {
                            xagioNotify("danger", "Invalid file type supplied! You are allowed to upload only ZIP files.");
                            return;
                        }

                        // execute upload
                        upload.doUpload(type, slug, c);
                    }
                })

            });
        },
        normalRescuePluginTheme: function () {
            $(document).on('click', '.begin-plugin-theme-rescue', function (e) {
                e.preventDefault();

                let btn = $(this);
                let c = btn.parents('.rescue-plugin-theme-template');
                let name = c.find('.rescue-name').text();
                let type = c.data('type');
                let slug = c.data('slug');
                let download = c.data('download');

                xagioModal("Are you sure?", "Are you sure that you want to rescue " + type + " " + name + "?", function (yes) {
                    if (yes) {
                        c.find('.rescue-plugin-theme-progress').removeClass('xagio-hidden');

                        let data = {
                            action  : 'xagio_normal_rescue_plugin_theme',
                            type    : type,
                            slug    : slug,
                            download: download
                        };

                        btn.disable('Rescuing...');

                        $.post(xagio_data.wp_post, data, function (d) {

                            btn.disable();

                            c.find('.rescue-plugin-theme-buttons').addClass('xagio-hidden');
                            c.find('.rescue-plugin-theme-alert').removeClass('xagio-hidden');

                            let alert = c.find('.rescue-plugin-theme-alert');

                            if (d.status === 'success') {
                                alert.html('<i class="xagio-icon xagio-icon-check"></i> ' + d.message);
                            } else {
                                alert.html('<i class="xagio-icon xagio-icon-close"></i> ' + d.message);
                            }

                        });
                    }
                });
            });
        },
        scanForPluginsThemes   : function () {
            $.post(xagio_data.wp_post, 'action=xagio_scan_plugins_themes', function (d) {

                $('.rescue-loading-skeleton.first').remove();

                if (d.status !== 'success') {
                    xagioNotify("danger", "Failed to retrieve plugins and themes. Please try again later.");
                } else {

                    actions.hide('.rescue-scan-plugins-themes');
                    actions.show('.rescue-plugins-themes-list');

                    let rowTemplate = $('.rescue-plugin-theme-template.xagio-hidden');

                    let foundPlugins = d.data.plugins.found;
                    let foundPluginsContainer = $('.rescue-found-plugins');

                    let foundThemes = d.data.themes.found;
                    let foundThemesContainer = $('.rescue-found-themes');

                    let missingPlugins = d.data.plugins.missing;
                    let missingPluginsContainer = $('.rescue-missing-plugins');

                    let missingThemes = d.data.themes.missing;
                    let missingThemesContainer = $('.rescue-missing-themes');

                    foundPluginsContainer.empty();
                    if (Object.keys(foundPlugins).length > 0) {
                        for (let slug in foundPlugins) {
                            let plugin = foundPlugins[slug];
                            let template = rowTemplate.clone();

                            template.removeClass('xagio-hidden');

                            template.find('.rescue-name').html(plugin.Title);
                            template.find('.rescue-version').html('v' + plugin.Version);
                            template.find('.rescue-type').html('Plugin').addClass('plugin');

                            template.attr('data-type', 'plugin');
                            template.attr('data-slug', slug);
                            template.attr('data-download', plugin.DownloadUrl);

                            template.find('.begin-plugin-theme-rescue').removeClass('xagio-hidden');

                            template.find('.plugin-theme-upload').remove();

                            foundPluginsContainer.append(template);
                        }
                    }

                    foundThemesContainer.empty();
                    if (Object.keys(foundThemes).length > 0) {
                        for (let slug in foundThemes) {
                            let theme = foundThemes[slug];
                            let template = rowTemplate.clone();

                            template.removeClass('xagio-hidden');

                            template.find('.rescue-name').html(theme.Name);
                            template.find('.rescue-version').html('v' + theme.Version);
                            template.find('.rescue-type').html('Theme').addClass('theme');

                            template.attr('data-type', 'theme');
                            template.attr('data-slug', slug);
                            template.attr('data-download', theme.DownloadUrl);

                            template.find('.begin-plugin-theme-rescue').removeClass('xagio-hidden');

                            template.find('.plugin-theme-upload').remove();

                            foundThemesContainer.append(template);
                        }
                    }

                    missingPluginsContainer.empty();
                    if (Object.keys(missingPlugins).length > 0) {
                        for (let slug in missingPlugins) {
                            let plugin = missingPlugins[slug];
                            let template = rowTemplate.clone();

                            template.removeClass('xagio-hidden');

                            template.find('.rescue-name').html(plugin.Title);
                            template.find('.rescue-version').html('v' + plugin.Version);
                            template.find('.rescue-type').html('Plugin').addClass('plugin');

                            template.attr('data-type', 'plugin');
                            template.attr('data-slug', slug);

                            let randomID = actions.generateID();

                            template.find('.upload-plugin-theme-rescue').removeClass('xagio-hidden');
                            template.find('.upload-plugin-theme-rescue').attr('for', randomID);
                            template.find('.plugin-theme-upload').attr('id', randomID);

                            missingPluginsContainer.append(template);
                        }
                    }

                    missingThemesContainer.empty();
                    if (Object.keys(missingThemes).length > 0) {
                        for (let slug in missingThemes) {
                            let theme = missingThemes[slug];
                            let template = rowTemplate.clone();

                            template.removeClass('xagio-hidden');

                            template.find('.rescue-name').html(theme.Name);
                            template.find('.rescue-version').html('v' + theme.Version);
                            template.find('.rescue-type').html('Theme').addClass('theme');

                            template.attr('data-type', 'theme');
                            template.attr('data-slug', slug);

                            let randomID = actions.generateID();

                            template.find('.upload-plugin-theme-rescue').removeClass('xagio-hidden');
                            template.find('.upload-plugin-theme-rescue').attr('for', randomID);
                            template.find('.plugin-theme-upload').attr('id', randomID);

                            missingThemesContainer.append(template);
                        }
                    }

                }

            });
        },
        uninstallPluginTheme   : function () {
            $(document).on('click', '.remove-plugin-theme-rescue', function (e) {
                e.preventDefault();

                let c = $(this).parents('.rescue-plugin-theme-template');
                let name = c.find('.rescue-name').text();
                let type = c.data('type');
                let slug = c.data('slug');

                xagioModal("Are you sure?", "Are you sure that you want to uninstall " + name + "?", function (yes) {
                    if (yes) {
                        let data = {
                            action: 'xagio_uninstall_plugin_theme',
                            type  : type,
                            slug  : slug
                        };

                        $.post(xagio_data.wp_post, data, function (d) {

                            xagioNotify("success", `${name} successfully uninstalled.`);
                            c.fadeOut();

                        });
                    }
                })

            });
        },
    };

    let coreRescue = {
        removeOldFiles   : function () {
            $(document).on('click', '.remove-old-core-files', function (e) {
                e.preventDefault();

                $.post(xagio_data.wp_post, 'action=xagio_remove_old_core', function (d) {

                    xagioNotify("danger", "Old WordPress core files successfully removed.");
                    $('.old-core-files-message').fadeOut();

                });

            });
        },
        startCoreRescue  : function () {

            $(document).on('click', '.start-core-rescue', function (e) {
                e.preventDefault();

                let button = $(this);

                let rescueType = $('.rescue-core-type').text().toLowerCase();
                let coreList = $('#rescue-core-files-list');
                let filesData = {filesToAdd: [], filesToDelete: [], filesToOverwrite: []};

                if (rescueType === 'easy') {

                    coreList.find('[aria-selected="true"]').each(function () {

                        let item = $(this);
                        if (item.data('action') === 'delete') {
                            filesData.filesToDelete.push(item.data('path'));
                        } else if (item.data('action') === 'add') {
                            filesData.filesToAdd.push([
                                                          item.data('newpath'),
                                                          item.data('path')
                                                      ]);
                        } else {
                            filesData.filesToOverwrite.push([
                                                                item.data('newpath'),
                                                                item.data('path')
                                                            ]);
                        }

                    });

                } else if (rescueType === 'advanced') {

                    let items = coreList.jstree("get_selected", true);

                    for (let i = 0; i < items.length; i++) {
                        let item = items[i];

                        if (item.data.action === 'delete') {
                            filesData.filesToDelete.push(item.data.path);
                        } else if (item.data.action === 'add') {
                            filesData.filesToAdd.push([
                                                          item.data.newpath,
                                                          item.data.path
                                                      ]);
                        } else {
                            filesData.filesToOverwrite.push([
                                                                item.data.newpath,
                                                                item.data.path
                                                            ]);
                        }

                    }

                }

                if (
                    filesData.filesToDelete.length === 0 &&
                    filesData.filesToOverwrite.length === 0 &&
                    filesData.filesToAdd.length === 0
                ) {
                    xagioNotify("danger", "You must at least have one file selected to begin rescuing process.")
                    return;
                }

                let formData = new FormData();
                formData.append('action', 'xagio_start_core_rescue');

                function appendFilesArray(formData, key, array) {
                    for (let i = 0; i < array.length; i++) {
                        let item = array[i];
                        if (Array.isArray(item)) {
                            for (let j = 0; j < item.length; j++) {
                                formData.append(`${key}[${i}][]`, item[j]);
                            }
                        } else {
                            formData.append(`${key}[]`, item);
                        }
                    }
                }

                appendFilesArray(formData, 'filesToAdd', filesData.filesToAdd);
                appendFilesArray(formData, 'filesToDelete', filesData.filesToDelete);
                appendFilesArray(formData, 'filesToOverwrite', filesData.filesToOverwrite);

                button.disable('Rescuing...');

                $.ajax({
                           url: xagio_data.wp_post,
                           type: 'POST',
                           data: formData,
                           processData: false, // Prevent jQuery from processing the data
                           contentType: false, // Prevent jQuery from setting the content type
                           success: function (d) {
                               button.disable();

                               actions.transition('.rescue-core-files', '.rescue-core-operation');

                               let message = $('.rescue-core-message');

                               message.find('h2').html(d.status);
                               message.find('p').html(d.message);
                           }
                       });

            });

        },
        previewCoreFiles : function () {
            $(document).on('click', '.preview-core-files', function (e) {
                e.preventDefault();

                let btn = $(this);
                btn.disable('Loading...');

                let rescue_type = $('.rescue-core-type').text().toLowerCase();

                $.post(xagio_data.wp_post, 'action=xagio_files_core&type=' + rescue_type, function (d) {

                    btn.disable();

                    let files = $('#rescue-core-files-list');
                    files.jstree('destroy');
                    files.removeClass();
                    files.empty();
                    files.append('<ul></ul>');
                    let coreList = files.find('ul');

                    actions.transition('.rescue-core-download', '.rescue-core-files');

                    if (d.status === 'success') {

                        if (rescue_type === 'advanced') {

                            let jsTree = actions.buildJsTree(d.data);
                            coreList.append(jsTree);

                            files.jstree({
                                             "checkbox": {
                                                 "keep_selected_style": false
                                             },
                                             "types"   : {
                                                 "folder"    : {
                                                     "icon": "xagio-icon xagio-icon-folder"
                                                 },
                                                 "file"      : {
                                                     "icon": "xagio-icon xagio-icon-code"
                                                 },
                                                 "image"     : {
                                                     "icon": "xagio-icon xagio-icon-image"
                                                 },
                                                 "archive"   : {
                                                     "icon": "xagio-icon xagio-icon-zip"
                                                 },
                                                 "executable": {
                                                     "icon": "xagio-icon xagio-icon-warning"
                                                 }
                                             },
                                             "plugins" : [
                                                 "checkbox",
                                                 "types"
                                             ]
                                         });

                        } else if (rescue_type === 'easy') {

                            let tree = actions.buildTree(d.data);
                            if (tree.length !== 0) {
                                files.addClass('easy-mode');
                                coreList.append(tree);
                            } else {
                                coreList.append('<li>There are no local WordPress core files that are different from the remote ones.</li>');
                            }

                        }


                    }


                });

            });
        },
        downloadCoreFiles: function () {
            $(document).on('click', '.select-core-version', function (e) {
                e.preventDefault();

                let version = $('#rescue-core-version-value').val();

                if (version === '') {
                    xagioNotify("danger", "You must select a version to download first.");
                    return;
                }

                let button = $(this);
                button.disable('Downloading...');

                $.post(xagio_data.wp_post, 'action=xagio_download_core&version=' + version, function (d) {

                    actions.transition('.rescue-core-version', '.rescue-core-download');

                    button.disable();

                    let message = $('.download-core-message');

                    message.find('h2').html(d.status.charAt(0).toUpperCase() + d.status.slice(1));
                    message.find('p').html(d.message);

                    if (d.status === 'success') {
                        actions.show('.preview-core-files');
                    } else {
                        actions.show('.download-core-close');
                    }

                });

            });
        },
        closeRescue      : function () {
            $(document).on('click', '.rescue-core-close', function (e) {
                e.preventDefault();

                actions.transition('.rescue-container', '.rescue-select-mode');

            });
        },
        beginRescue      : function () {
            $(document).on('click', '.begin-core-rescue', function (e) {
                e.preventDefault();

                // Insert the current rescue type
                $('.rescue-core-type').html($(this).data('type').charAt(0).toUpperCase() + $(this).data('type').slice(1));

                actions.transition('.rescue-select-mode', '.rescue-container');

            });
        }

    };

    let actions = {

        transition: function (from, to) {
            $(from).fadeOut('fast', function () {
                $(to).fadeIn('fast');
            });
        },
        hide      : function (what) {
            what = $(what);
            what.addClass('xagio-hidden');
            return what;
        },
        show      : function (what) {
            what = $(what);
            what.removeClass('xagio-hidden');
            return what;
        },

        buildTree: function (children) {

            let files = [];

            for (let file in children) {
                let child = children[file];
                let new_path = '';
                if (child.action !== 'delete') {
                    new_path = "data-newpath='" + child.new_path + "'"
                }
                files.push('<li data-action=\'' + child.action + '\' aria-selected="true" ' + new_path + ' data-path=\'' + child.path + '\'>' + file + '</li>');

            }

            files.sort();

            return files;
        },

        buildJsTree: function (children) {

            let folders = [];
            let files = [];

            for (let file in children) {

                let child = children[file];
                if (child.hasOwnProperty("action")) {

                    let type = 'file';
                    let extension = file.split('.')[1];

                    if ($.inArray(extension, [
                        'png',
                        'jpeg',
                        'jpg',
                        'gif'
                    ]) !== -1) {
                        type = 'image';
                    }

                    if ($.inArray(extension, [
                        'zip',
                        'rar',
                        'tar',
                        'tar.gz'
                    ]) !== -1) {
                        type = 'archive';
                    }

                    if ($.inArray(extension, [
                        'exe',
                        'sh',
                        'com',
                        'vba'
                    ]) !== -1) {
                        type = 'executable';
                    }

                    let selected = '';
                    if (child.action !== 'overwrite') {
                        selected = ', "selected" : true';
                    }

                    let new_path = '';
                    if (child.action !== 'delete') {
                        new_path = "data-newpath='" + child.new_path + "'"
                    }

                    files.push('<li data-action=\'' + child.action + '\' ' + new_path + ' data-path=\'' + child.path + '\' data-jstree=\'{ "type" : "' + type + '" ' + selected + ' }\'>' + file + '</li>');

                } else {
                    folders.push('<li data-jstree=\'{ "type" : "folder" }\'><span class="rescue-folder">' + file + '</span> <ul>' + actions.buildJsTree(child) + '</ul></li>');
                }

            }

            folders.sort();
            files.sort();

            return folders + files;
        },

        generateID: function () {
            let text = "";
            let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

            for (let i = 0; i < 5; i++)
                text += possible.charAt(Math.floor(Math.random() * possible.length));

            return text;
        }

    };

    let Upload = function (file) {
        this.file = file;
    };

    Upload.prototype.getType = function () {
        return this.file.type;
    };
    Upload.prototype.getName = function () {
        return this.file.name;
    };
    Upload.prototype.doUpload = function (type, slug, parent) {
        let formData = new FormData();

        formData.append("action", "xagio_upload_rescue_plugin_theme");
        formData.append("file", this.file, this.getName());
        formData.append("type", type);
        formData.append("slug", slug);

        let buttons = parent.find('.rescue-plugin-theme-buttons');
        let progress = parent.find('.rescue-plugin-theme-progress');
        let alert = parent.find('.rescue-plugin-theme-alert');
        buttons.addClass('xagio-hidden');
        progress.removeClass('xagio-hidden');


        $.ajax({
                   type       : "POST",
                   url        : xagio_data.wp_post,
                   xhr        : function () {
                       let myXhr = $.ajaxSettings.xhr();
                       if (myXhr.upload) {
                           myXhr.upload.addEventListener('progress', function (event) {

                               let percent = 0;
                               let position = event.loaded || event.position;
                               let total = event.total;
                               if (event.lengthComputable) {
                                   percent = Math.ceil(position / total * 100);
                               }

                               progress.find(".uk-progress-bar").css("width", +percent + "%");

                           }, false);
                       }
                       return myXhr;
                   },
                   success    : function (data) {
                       progress.addClass('xagio-hidden');
                       alert.removeClass('xagio-hidden');
                       alert.html("Rescue operation completed successfully.");
                   },
                   error      : function (error) {
                       progress.addClass('xagio-hidden');
                       alert.removeClass('xagio-hidden');
                       alert.html("Failed to perform rescue operation, please try again later.");
                   },
                   async      : true,
                   data       : formData,
                   cache      : false,
                   contentType: false,
                   processData: false,
                   timeout    : 60000
               });
    };

})(jQuery);


