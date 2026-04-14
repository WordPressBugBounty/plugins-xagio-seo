(function ($) {
    'use strict';

    $(document).ready(function () {
        clone.clone();
    });

    let clone  = {

        prefix      : null,
        backup_path : null,
        backup_name : null,
        api_key     : null,
        admin_post  : null,
        current_step: 0,
        steps       : [
            // Obtain API key
            function () {

                clone.change_status(25, 'Obtaining Keys for Remote Website', false);

                clone.append_output('info', `Obtaining an API Key & API Url for inter-plugin communication...`);

                $.post(xagio_data.wp_post, 'action=xagio_obtain_api_key&url=' + clone.admin_post, function (d) {

                    if (d.status == 'error') {

                        clone.change_status(100, 'Failed to obtain Keys!', true);
                        xagioNotify("error", d.message);
                        clone.append_output('error', `Failed to obtain an API Key and API URL! Reason: ${d.message}`);

                    } else {

                        clone.api_key    = d.data.key;
                        clone.admin_post = d.data.admin_post;
                        clone.current_step++;

                        clone.append_output('success', `API Key obtained: <b>${clone.api_key}</b>`);
                        clone.append_output('success', `API URL obtained: <a href="${clone.admin_post}" target="_blank">${clone.admin_post}</a>`);

                        clone.steps[clone.current_step]();

                    }

                });

            },
            // Create copy of Remote Website
            function () {
                let status = $('.creating-copy');

                clone.change_status(45, 'Creating Remote Backup', false);

                clone.append_output('info', `Creating a backup of the remote website...`);

                $.post(xagio_data.wp_post, 'action=xagio_create_clone_backup&url=' + clone.admin_post + '&key=' + clone.api_key, function (d) {

                    if (!d.hasOwnProperty('status')) {

                        clone.change_status(100, 'Failed to Create Remote Backup', true);
                        xagioNotify("error", d.message);
                        clone.append_output('error', `Failed to create a backup of the remote website! Reason: A timeout has occurred.`);

                    } else if (d.status == 'error') {

                        clone.change_status(100, 'Failed to Create Remote Backup', true);
                        xagioNotify("error", d.message);
                        clone.append_output('error', `Failed to create a backup of the remote website! Reason: ${d.message}`);

                    } else {

                        clone.backup_name = d.data;
                        clone.change_status(status, 'success', false);
                        clone.current_step++;

                        clone.append_output('success', `Successfully created a backup of the remote website: <a href="${clone.backup_name}" target="_blank">${clone.backup_name}</a>`);

                        clone.steps[clone.current_step]();

                    }

                });

            },
            // Download copy of Remote Website
            function () {
                let status = $('.downloading-copy');

                clone.change_status(60, 'Downloading Backup', false);

                clone.append_output('info', `Downloading and unpacking remote website backup...`);

                $.post(xagio_data.wp_post, 'action=xagio_download_clone_backup&backup=' + clone.backup_name, function (d) {

                    if (d.status == 'error') {

                        clone.change_status(100, 'Failed to Download Backup', true);
                        xagioNotify("error", d.message);
                        clone.append_output('error', `Failed to download a backup from the remote website! Reason: ${d.message}`);

                    } else {

                        $.post(xagio_data.wp_post, 'action=xagio_remove_clone_backup&url=' + clone.admin_post + '&key=' + clone.api_key + '&backup=' + clone.backup_name, function (d) {

                            clone.append_output('success', `Cleared out the unnecessary files...`);

                        });

                        clone.backup_path = d.data.extDir;
                        clone.prefix      = d.data.prefix;
                        clone.change_status(status, 'success', false);
                        clone.current_step++;

                        clone.append_output('success', `Backup successfully downloaded to: <b>${clone.backup_path}</b>`);

                        clone.steps[clone.current_step]();

                    }

                });

            },
            // Extract Files & Merge Database
            function () {

                let status = $('.extracting-files-merging-databases');

                clone.change_status(80, 'Cloning from Backup', false);

                clone.append_output('info', `Migrating database data and merging files from remote website backup...`);

                $.post(xagio_data.wp_post, 'action=xagio_extract_merge_clone&backup_path=' + clone.backup_path + '&url=' + clone.admin_post + '&prefix=' + clone.prefix, function (d) {

                    if (d.status == 'error') {

                        clone.change_status(100, 'Failed to Clone', true);
                        xagioNotify("error", d.message);
                        clone.append_output('error', `Failed to complete database import and file merge! Reason: ${d.message}`);

                    } else {

                        clone.append_output('success', `Database has been successfully migrated and files have been merged in the root directory.`);

                        clone.change_status(status, 'success', false);
                        clone.current_step++;
                        clone.steps[clone.current_step]();

                    }

                });

            },
            // Finish Cloning
            function () {
                let status = $('.finishing-cloning');

                clone.change_status(100, 'Completed', false);

                clone.append_output('success', `Clone has finished successfully.`);

                setTimeout(function () {

                    clone.change_status(status, 'success', false);
                    xagioNotify("success", "Cloning is completed. Refresh this page in order to see your cloned website. <br> Please use credentials(username and password) from cloned website in order to login to WordPress.");
                }, 4000);

            }
        ],

        append_output: function (type, message, clear) {
            let o = $('.output-window');
            if (typeof clear !== 'undefined') {
                o.empty();
            }
            let icon = '';
            if (type == 'info') {
                icon = '<i class="xagio-icon xagio-icon-info"></i>';
            } else if (type == 'error') {
                icon = '<i class="xagio-icon xagio-icon-close"></i>';
            } else if (type == 'success') {
                icon = '<i class="xagio-icon xagio-icon-info"></i>';
            }
            o.append(`<p class="output-status ${type}">${icon}${message}</p>`);
            o.scrollTop(o.prop("scrollHeight"));
        },

        change_status: function (size, words, triggerError=false) {
            let progress = $('.xagio-progress');
            progress.find('.xagio-progress-bar').html(words);
            progress.find('.xagio-progress-bar').css('width', size + '%');
            if (triggerError) {
                progress.removeClass('xagio-progress-green').addClass('xagio-progress-red');
            }
        },

        clone: function () {

            $(document).on('click', '.clone-button', function (e) {
                e.preventDefault();

                let cbtn = $('.clone-button');
                let btn  = $('.verify-button');
                cbtn.disable('Cloning...');
                btn.attr('disabled', 'disabled');


                xagioModal("Are you sure?", "This will completely override this website with the new data from the selected website!", function (result) {
                    if (result) {
                        $('.xagio-progress').fadeIn('fast',function(){
                            clone.steps[clone.current_step]();
                        });
                    } else {
                        cbtn.disable();
                        btn.removeAttr('disabled');
                    }
                });
            });

            $(document).on('submit', '.verify', function (e) {
                e.preventDefault();

                if (!xagio_data.connected) {
                    xagioConnectModal();
                    return;
                }

                let btn  = $('.verify-button');
                let cbtn = $('.clone-button');
                let url  = $('.clone-url');

                cbtn.attr('disabled', 'disabled');

                if (clone.admin_post != null) {
                    url.removeAttr('disabled');
                    btn.removeClass('uk-button-danger').addClass('uk-button-success');
                    btn.html('<i class="xagio-icon xagio-icon-plug"></i> Verify Connection');
                    clone.admin_post = null;
                    clone.append_output('info', 'Waiting for action...', true);
                    return;
                }

                btn.disable('Verifying...');

                clone.append_output('info', `Validating connection to <a href="${url.val()}" target="_blank">${url.val()}</a>`, true);

                $.post(xagio_data.wp_post, $(this).serialize(), function (d) {

                    btn.disable();

                    if (d.status == 'success') {

                        $('#cloneNotice')[0].showModal();

                        cbtn.removeAttr('disabled');
                        clone.admin_post = d.data;
                        btn.removeClass('uk-button-success').addClass('uk-button-danger');
                        btn.html('<i class="xagio-icon xagio-icon-close"></i> Cancel');
                        url.attr('disabled', 'disabled');
                        clone.append_output('success', `Successfully connected to <a href="${url.val()}" target="_blank">${url.val()}</a>`);

                    } else {
                        url.addClass('uk-form-danger');
                        xagioNotify("error", d.message);
                        clone.append_output('error', `Failed to connect to <a href="${url.val()}" target="_blank">${url.val()}</a>`);
                    }

                });

            });

        },

    };

})(jQuery);


