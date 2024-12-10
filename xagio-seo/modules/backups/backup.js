(function ($) {
    'use strict';

    let dropArea = $('.restore-area');
    let backup_progress = $('.backup-progress');
    let restore_progress = $('.restore-progress');

    let location = $('[name="location"]');
    let frequency = $('[name="frequency"]');
    let copies = $('[name="copies"]');
    let view_remote_backups = $('.view-remote-backups');

    $(document).ready(function () {
        backup.backup();

        if (xagio_backup.backup_speed.grade < 8) {
            xagioModal("Warning", "Your hosting is not powerful enough to handle backups without issues, it received performance grade of " + xagio_backup.backup_speed.grade + " / 10. Please contact your hosting provider to upgrade your plan or get a <b>FREE</b> migration of this website by switching to our <a href='https://care.xagio.com' target='_blank'>Xagio Care Hosting</a>.");
        }

        if (xagio_backup.backup_size > 1000) {
            xagioModal("Warning", "Your website is large, backup size could reach up to " + xagio_backup.backup_size + " Mb in file size. This might create issues during upload with script timeouts, especially if you're using CloudFlare.");
        }
    });

    let backup = {

        backup: function () {
            backup.setupFileSelect();
            backup.setupDragAndDrop();
            backup.restoreBackupFromUrl();
            backup.createBackup();
            backup.downloadBackup();
            backup.removeBackup();
            backup.saveSettings();
            backup.viewRemoteBackups();
            backup.refreshSpeeds();
        },

        refreshSpeeds: function(){
            $(document).on('click', '.check-backup-speed', function(e){
                e.preventDefault();
                $(this).disable();
                $.post(xagio_data.wp_post, 'action=xagio_check_backup_speed', function(d){
                    xagioModal("Success", "Your Backup Grade has been refreshed. Reloading this page...");
                    setTimeout(function(){
                        document.location.reload();
                    }, 2000);
                });
            });
            $(document).on('click', '.check-backup-size', function(e){
                e.preventDefault();
                $(this).disable();
                $.post(xagio_data.wp_post, 'action=xagio_check_backup_size', function(d){
                    xagioModal("Success", "Your Backup Estimated Size has been refreshed. Reloading this page...");
                    setTimeout(function(){
                        document.location.reload();
                    }, 2000);
                });
            });
        },

        viewRemoteBackups: function () {
            let container = $('.remote-backups');
            view_remote_backups.on('change', function (e) {

                let storage = $(this).val();

                container.html('');
                container.addClass('loading');

                $.post(xagio_data.wp_post, 'action=xagio_get_backups&storage=' + storage, function (d) {

                    container.removeClass('loading');

                    // if d contains files key
                    if (d.files) {

                        $.each(d.files, function (i, file) {
                            let template = $('.backup-template.xagio-hidden').clone(true);
                            template.removeClass('xagio-hidden');

                            // determine backup type
                            let fileType, fileClass;
                            if (file.file.includes('full')) {
                                fileType = 'Full';
                                fileClass = 'full';
                            } else if (file.file.includes('files')) {
                                fileType = 'Files';
                                fileClass = 'files';
                            } else if (file.file.includes('mysql')) {
                                fileType = 'Database';
                                fileClass = 'mysql';
                            }

                            // convert date to this format: March 28 2024 13:33:08
                            let date = new Date(file.date);
                            file.date = date.toLocaleString('en-US', {
                                month : 'long',
                                day   : 'numeric',
                                year  : 'numeric',
                                hour  : 'numeric',
                                minute: 'numeric',
                                second: 'numeric'
                            });

                            template.find('.backup-type').addClass(fileClass).text(fileType);
                            template.find('.backup-name').text(file.date);

                            template.find('.download-remote-backup').attr('data-id', file.id);
                            template.find('.download-remote-backup').attr('data-backup', file.file);
                            template.find('.download-remote-backup').attr('data-storage', storage);

                            template.find('.remove-remote-backup').attr('data-id', file.id);
                            template.find('.remove-remote-backup').attr('data-backup', file.file);
                            template.find('.remove-remote-backup').attr('data-storage', storage);

                            container.append(template);
                        });

                    }
                });
            });

            $(document).on('click', '.download-remote-backup', function (e) {
                e.preventDefault();

                let id = $(this).data('id');
                let backup = $(this).data('backup');
                let storage = $(this).data('storage');

                let button = $(this);
                button.disable();

                $.post(xagio_data.wp_post, 'action=xagio_download_backup&id=' + id + "&backup=" + backup + "&storage=" + storage, function (d) {
                    button.disable();

                    // if d.status == redirect, open the download link in a new tab, else show a notification
                    if (d.status == 'redirect' || d.status == 'success') {
                        window.open(d.data, '_blank');
                    } else {
                        xagioNotify(d.status == 'success' ? 'success' : 'danger', d.message);
                    }
                });
            });

            $(document).on('click', '.remove-remote-backup', function (e) {
                e.preventDefault();

                let id = $(this).data('id');
                let backup = $(this).data('backup');
                let storage = $(this).data('storage');

                let button = $(this);
                button.disable();

                $.post(xagio_data.wp_post, 'action=xagio_delete_backup&id=' + id + "&backup=" + backup + "&storage=" + storage, function (d) {
                    button.disable();

                    xagioNotify(d.status == 'success' ? 'success' : 'danger', d.message)

                    if (d.status == 'success') {
                        button.parents('.backup-template').remove();
                    }
                });
            });

            setTimeout(function () {
                view_remote_backups.eq(0).trigger('change');
            }, 1000);
        },

        saveSettings: function () {
            $(document).on('submit', '.save-settings', function (e) {
                e.preventDefault();

                let $this = $(this);
                let button = $this.find('button');

                button.disable('Saving...');

                $.post(xagio_data.wp_post, $this.serialize(), function (d) {
                    button.disable();
                    xagioNotify(d.status == 'success' ? 'success' : 'danger', d.message)
                });
            });

            location.val(location.data('selected'));
            frequency.val(frequency.data('selected'));
            copies.val(copies.data('selected'));
            view_remote_backups.val(view_remote_backups.data('selected'));

        },

        setupFileSelect: function () {
            let fileInput = $('#fileInput');

            // Handle file selection through the link
            $('.restore-area a').on('click', function (e) {
                e.preventDefault();
                fileInput.click();
            });

            // Handle the file input change event
            fileInput.on('change', function () {
                if (this.files.length) {
                    backup.determineRestoreType(this.files[0]);
                    // Clear the input after handling to ensure a file can be reselected
                    $(this).val('');
                }
            });
        },

        setupDragAndDrop: function () {
            dropArea.on('dragover', function (e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            dropArea.on('dragleave', function (e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
            });

            dropArea.on('drop', function (e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                $(this).addClass('drag-inside');

                let files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    backup.determineRestoreType(files[0]);
                }
            });
        },

        determineRestoreType: function (file) {
            let fileName = file.name;
            let fileType = fileName.split('.').pop().toLowerCase();

            if (fileType === 'zip') {
                // Check if the ZIP file is a full backup or a file backup
                if (fileName.includes('full')) {
                    // Assume 'full' in filename indicates a full backup
                    backup.restoreFullBackup(file);
                } else if (fileName.includes('files')) {
                    backup.restoreFileBackup(file);
                } else if (fileName.includes('mysql')) {
                    backup.restoreMySQLBackup(file);
                } else {
                    backup.restoreFileBackup(file);
                }
            } else {
                xagioNotify('danger', 'Unsupported file type. Please upload a Xagio backup file.');
            }
        },


        restoreBackupFromUrl: function () {
            $(document).on('click', '.restore-backup', function (e) {
                e.preventDefault();

                let button = $(this);
                let url = button.data('url');

                xagioModal("Are you sure?", "This will restore the selected backup. Continue?", function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_restore_backup&url=' + url, function (d) {
                            button.disable();
                            xagioNotify(d.status == 'success' ? 'success' : 'danger', d.message)
                        });
                    }
                })
            });
        },

        restoreFileBackup: function (file) {
            this.uploadBackup(file, xagio_data.wp_get + '?action=xagio_restore_file_backup');
        },

        restoreMySQLBackup: function (file) {
            this.uploadBackup(file, xagio_data.wp_get + '?action=xagio_restore_mysql_backup');
        },

        restoreFullBackup: function (file) {
            this.uploadBackup(file, xagio_data.wp_get + '?action=xagio_restore_full_backup');
        },

        uploadBackup: function (file, actionUrl) {

            dropArea.fadeOut('fast', function () {
                restore_progress.fadeIn();
            })

            let formData = new FormData();
            formData.append('file', file);
            formData.append('_xagio_nonce', xagio_data.nonce);

            // Optional: Validate file type here if necessary

            let progress_bar = restore_progress.find('.xagio-progress');
            let bar = restore_progress.find('.xagio-progress-bar');
            let status = $('.restore-status');

            $.ajax({
                       url        : actionUrl,
                       type       : 'POST',
                       data       : formData,
                       contentType: false,
                       processData: false,
                       xhr        : function () {
                           let xhr = new window.XMLHttpRequest();
                           xhr.upload.addEventListener('progress', function (e) {
                               if (e.lengthComputable) {
                                   let percent = Math.ceil((e.loaded / e.total) * 100);
                                   bar.css('width', percent + '%').text(percent + '%');
                                   if (percent == 100) {
                                       bar.text(' ');
                                       status.html('<i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Restoring...');
                                       progress_bar.addClass('xagio-progress-bar-infinite');
                                   }
                               }
                           }, false);
                           return xhr;
                       },
                       beforeSend : function () {
                           bar.css('width', '0%');
                       },
                       success    : function (response) {
                           if (response.status == 'error') {
                               status.html('<i class="xagio-icon xagio-icon-close"></i> ' + response.message);
                           } else {
                               status.html('<i class="xagio-icon xagio-icon-check"></i> Restore completed!');
                           }
                           progress_bar.removeClass('xagio-progress-bar-infinite');
                           bar.css('width', '100%');

                           setTimeout(function () {
                               restore_progress.fadeOut('fast', function () {
                                   dropArea.fadeIn();
                               });
                           }, 5000);

                       },
                       error      : function () {
                           xagioNotify('danger', 'An error occurred during the restore process.');
                       }
                   });
        },

        removeBackup: function () {
            $(document).on('click', '.remove-backup', function (e) {
                e.preventDefault();
                let name = $(this).data('name');
                let div = $(this).parents('.backup-template');

                xagioModal("Are you sure?", "This will remove this backup. Continue?", function (yes) {
                    if (yes) {
                        $.post(xagio_data.wp_post, 'action=xagio_remove_backup&name=' + name, function (d) {
                            div.fadeOut();
                            xagioNotify('success', 'Backup has been successfully removed!');
                        })
                    }
                })
            });
        },

        downloadBackup: function () {
            $(document).on('click', '.download-backup', function (e) {
                e.preventDefault();
                let url = $(this).data('url');
                $('body').append('<iframe class="xagio-hidden" src="' + url + '"></iframe>');
            });
        },

        createBackup: function () {
            $(document).on('submit', '.create-backup', function (e) {
                e.preventDefault();

                let $this = $(this);
                let button = $this.find('button');

                xagioModal("Are you sure?", "This will create a new backup. Continue?", function (yes) {
                    if (yes) {
                        let destination = $this.find('select[name="destination"]').val();
                        button.disable('Creating...');
                        backup_progress.fadeIn();

                        $.post(xagio_data.wp_post, $this.serialize(), function (d) {
                            button.disable();
                            backup_progress.fadeOut();

                            xagioNotify(d.status == 'success' ? 'success' : 'danger', d.message);

                            if (d.status == 'success' && destination == 'local') {
                                // show a download dialog
                                $('body').append('<iframe class="xagio-hidden" src="' + d.data + '"></iframe>');
                            }

                            if (d.status == 'success') {
                                //document.location.reload();
                            }
                        });
                    }
                })

            });
        },

    };

})(jQuery);


