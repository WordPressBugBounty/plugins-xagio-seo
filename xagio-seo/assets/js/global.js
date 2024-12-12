var textbox_ajax_timeout;
let filesToUpload = [];

function xagioConnectModal() {
    xagioModal("Account Required", "To use this feature, you need to have a Xagio account connected with this website. Getting a Xagio account is Free! Press continue to be redirected to Dashboard in order to Connect your Account!", function (result) {
        if (result) {
            document.location.href = xagio_data.wp_admin + 'admin.php?page=xagio-dashboard';
        }
    });
}

function xagioModal(title = "Confirm", message, callback = false) {
    let dialog = jQuery('<dialog class="xagio-modal">');


    dialog.html(`<div class="xagio-modal-header">
                    <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> ${title}</h3>
                    <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
                </div>
                <div class="xagio-modal-body">
                    <label class="modal-label">${message}</label>
                    <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium modal-button-holders">
                        
                        
                    </div>
                </div>`);

    if (callback === false) {

        let okBtn = jQuery('<button class="xagio-button xagio-button-outline" type="button"><i class="xagio-icon xagio-icon-check"></i> Ok</button>').appendTo(dialog.find('.modal-button-holders'));

        dialog.appendTo('body');
        dialog.get(0).showModal();

        okBtn.on('click', function () {
            dialog.get(0).close();
            dialog.remove();
        });

    } else {
        let cancelBtn = jQuery('<button class="xagio-button xagio-button-outline" type="button"><i class="xagio-icon xagio-icon-close"></i> Cancel</button>').appendTo(dialog.find('.modal-button-holders'));
        let confirmBtn = jQuery('<button type="button" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-check"></i> Continue</button>').appendTo(dialog.find('.modal-button-holders'));

        dialog.appendTo('body');
        dialog.get(0).showModal();

        confirmBtn.on('click', function () {
            dialog.get(0).close();
            callback(true);
            dialog.remove();
        });

        cancelBtn.on('click', function () {
            dialog.get(0).close();
            callback(false);
            dialog.remove();
        });

        dialog.find(".xagio-modal-close").on("click", function () {
            callback(false);
        })
    }

}

function xagioCustomModal(title = "Confirm", message, buttons = []) {
    let dialog = jQuery('<dialog class="xagio-modal">');

    dialog.html(`<div class="xagio-modal-header">
                    <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> ${title}</h3>
                    <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
                </div>
                <div class="xagio-modal-body">
                    <label class="modal-label">${message}</label>
                    <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium modal-button-holders">
                    </div>
                </div>`);

    const buttonHolder = dialog.find('.modal-button-holders');

    // Add cancel button by default
    let cancelBtn = jQuery('<button class="xagio-button xagio-button-outline" type="button"><i class="xagio-icon xagio-icon-close"></i> Cancel</button>')
        .appendTo(buttonHolder);

    // Add custom buttons
    buttons.forEach(button => {
        const { label, icon, callback, className = 'xagio-button-primary' } = button;
        const btnElement = jQuery(`<button type="button" class="xagio-button ${className}">
            ${icon ? `<i class="xagio-icon ${icon}"></i> ` : ''}${label}
        </button>`).appendTo(buttonHolder);

        btnElement.on('click', function() {
            if (typeof callback === 'function') {
                callback();
            }
            dialog.get(0).close();
            dialog.remove();
        });
    });

    // Handle cancel button click
    cancelBtn.on('click', function() {
        dialog.get(0).close();
        dialog.remove();
    });

    // Handle close button in header
    dialog.find('.xagio-modal-close').on('click', function() {
        dialog.get(0).close();
        dialog.remove();
    });

    dialog.appendTo('body');
    dialog.get(0).showModal();
}

function xagioPromptModal(title = "Confirm", message, callback) {
    let dialog = jQuery('<dialog class="xagio-modal">');


    dialog.html(`<div class="xagio-modal-header">
                    <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-info"></i> ${title}</h3>
                    <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
                </div>
                <div class="xagio-modal-body">
                    <label class="modal-label">${message}</label>
                    <input type="text" class="xagio-input-text-mini" id="xagio-prompt-input"/>
                    <div class="xagio-flex-right xagio-flex-gap-medium xagio-margin-top-medium modal-button-holders">
                        
                        
                    </div>
                </div>`);

    let cancelBtn = jQuery('<button class="xagio-button xagio-button-outline" type="button"><i class="xagio-icon xagio-icon-close"></i> Cancel</button>').appendTo(dialog.find('.modal-button-holders'));
    let confirmBtn = jQuery('<button type="button" class="xagio-button xagio-button-primary"><i class="xagio-icon xagio-icon-check"></i> Continue</button>').appendTo(dialog.find('.modal-button-holders'));

    dialog.appendTo('body');
    dialog.get(0).showModal();

    confirmBtn.on('click', function () {
        let input = dialog.find('#xagio-prompt-input').val();
        dialog.get(0).close();
        callback(input);
        dialog.remove();
    });

    cancelBtn.on('click', function () {
        dialog.get(0).close();
        callback(false);
        dialog.remove();
    });
}

let xagioNotifications = jQuery(`<div class="xagio-notifications">`);
xagioNotifications.appendTo("body");
notificationCount = 0;

function xagioNotify(status, message, instantCloseDialogNotification = false, seconds = 5) {
    notificationCount++;
    if (status === "error") status = "danger";

    let notify = jQuery(`<div class="xagio-notify xagio-${status}">`);
    let icon = "";

    if (status === "success") {
        icon = "xagio-icon xagio-icon-check";
    } else if (status === "danger") {
        icon = "xagio-icon xagio-icon-close";
    } else if (status === "warning") {
        icon = "xagio-icon xagio-icon-warning";
    }

    notify.html(`<div class="xagio-notify-wrapper ${status}">
       <div class="xagio-notify-text">
            <div class="xagio-notify-icon ${status}">
                <i class="${icon}"></i>
            </div>
           <p>${message}</p>
       </div>
   </div>    
   `);

    notify.appendTo(xagioNotifications);

    if (notificationCount >= 1) {
        let modal = jQuery('.xagio-modal[open]');
        if (modal.length && !instantCloseDialogNotification) {
            xagioNotifications.appendTo(modal);
        } else {
            xagioNotifications.appendTo("body");
        }
    }

    let timeoutId = setTimeout(function () {
        notify.addClass("hide");
        setTimeout(function () {
            notify.remove();
            notificationCount--;
        }, 300);
    }, seconds * 1000);

    notify.on('mouseenter', function () {
        clearTimeout(timeoutId);
    });

    notify.on('mouseleave', function () {
        timeoutId = setTimeout(function () {
            notify.addClass("hide");
            setTimeout(function () {
                notify.remove();
                notificationCount--;
            }, 300);
        }, seconds / 2 * 1000);
    });
}

(function ($) {
    'use strict';

    let originalDeleteUrl;
    let currentPlugin;

    $(document).on('click', '.plugins [data-slug="xagio-seo"] .deactivate a', function (e) {
        e.preventDefault();

        originalDeleteUrl = $(this).attr('href');
        currentPlugin = $(this).closest('tr').attr('data-plugin');
        $('#xagio-deactivate-plugin')[0].showModal();
    });

    $(document).on('submit', '#xagio-deactivate', function (e) {
        e.preventDefault();
        let $this = $(this);
        $.ajax({
                   url    : xagio_data.wp_post,
                   type   : 'POST',
                   data   : $this.serialize(),
                   success: function (response) {
                       if (response.status == 'success') {
                           window.location.href = originalDeleteUrl;
                       } else {
                           alert(response.message);
                       }
                   },
                   error  : function (xhr, status, error) {
                       alert('Error: ' + error);
                   }
               });
    });

    // Prepend _ajax_nonce to every AJAX request
    $(document).ajaxSend(function (event, jqxhr, settings) {
        // Function to check if action contains 'xag'
        function actionContainsXag(data) {
            if (typeof data === 'string') {
                // Check if the action parameter contains 'xag'
                return data.includes('action=xag');
            } else if (data instanceof FormData) {
                // Check if the FormData has 'action' containing 'xag'
                for (var pair of data.entries()) {
                    if (pair[0] === 'action' && pair[1].includes('xag')) {
                        return true;
                    }
                }
            }
            return false;
        }

        // If the action contains 'xag', append the nonce
        if (actionContainsXag(settings.data)) {
            if (settings.data instanceof FormData) {
                // Append the nonce directly to the FormData object
                settings.data.append('_xagio_nonce', xagio_data.nonce);
            } else {
                // If it's a URL-encoded string, append the nonce
                settings.data += '&_xagio_nonce=' + xagio_data.nonce;
            }
        }
    });

    $.fn.xagio_uploader = function (action, callback) {
        if (this.length === 0) return this;

        return this.each(function () {
            let $this = $(this);
            let filesToUpload = [];

            // Original HTML to reset the form
            const originalHTML = $this.html();

            // Drag and drop events
            $this.find(".xagio-upload-drop").on('dragenter', function (e) {
                e.stopPropagation();
                e.preventDefault();
                $(this).addClass('xagio-drag-enter');
            });

            $this.find(".xagio-upload-drop").on('dragleave', function (e) {
                e.stopPropagation();
                e.preventDefault();
                $(this).removeClass('xagio-drag-enter');
            });

            $this.find(".xagio-upload-drop").on('drop', function (e) {
                e.preventDefault();
                $(this).removeClass('xagio-drag-enter');
                let files = e.originalEvent.dataTransfer.files;
                handleFileSelection(files);
            });

            $(document).on('dragenter dragover drop', function (e) {
                e.stopPropagation();
                e.preventDefault();
            });

            $this.find(".xagio-upload-drop").on('click', function () {
                $this.find(".file-upload").click();
            });

            $this.find(".file-upload").on('change', function (e) {
                let files = e.target.files;
                handleFileSelection(files);
            });

            function handleFileSelection(files) {
                // Validate file type
                let validFiles = [];
                for (let i = 0; i < files.length; i++) {
                    if (files[i].type === 'text/csv' || files[i].name.endsWith('.csv')) {
                        validFiles.push(files[i]);
                    } else {
                        xagioNotify("danger", "Invalid file type: " + files[i].name + ". Only CSV files are allowed.");
                    }
                }

                if (validFiles.length > 0) {
                    filesToUpload = validFiles; // Store the selected valid files
                    displayFileNames(validFiles);
                    $this.find(".xagio-upload-drop").removeClass('xagio-drag-enter');

                    $this.find(".xagio-file-upload-button").off('click').on('click', function () {
                        handleFileUpload(filesToUpload, $this, action, callback);
                    });
                }
            }

            function displayFileNames(files) {
                let fileNamesContainer = $this.find(".xagio-file-names");
                fileNamesContainer.empty();
                for (let i = 0; i < files.length; i++) {
                    fileNamesContainer.append("<div><i class='xagio-icon xagio-icon-file'></i>" + files[i].name +
                                              "</div>");
                }
            }

            function handleFileUpload(files, obj, action, callback) {
                // Extract action and additional parameters
                let [actionName, ...paramsArray] = action.split('&');
                let params = new URLSearchParams(paramsArray.join('&'));

                for (let i = 0; i < files.length; i++) {
                    let fd = new FormData();
                    fd.append('action', actionName);
                    fd.append('file-import', files[i]);
                    for (const [key, value] of params) {
                        fd.append(key, value);
                    }
                    let status = new CreateStatusbar(obj);
                    status.setFileNameSize(files[i].name, files[i].size);
                    sendFileToServer(fd, status, callback);
                }
            }

            function CreateStatusbar(obj) {
                this.statusbar = $("<div class='statusbar '></div>");
                this.filename = $("<div class='xagio-upload-filename'></div>").appendTo(this.statusbar);
                this.size = $("<div class='xagio-upload-filesize'></div>").appendTo(this.filename);
                this.progressHolder = $("<div class='xagio-progress-holder'></div>").appendTo(this.statusbar);
                this.progressBar = $("<div class='progressBar'><div></div></div>").appendTo(this.progressHolder);
                this.abort = $("<div class='xagio-button xagio-button-danger xagio-button-mini abort'><i class='xagio-icon xagio-icon-close'></i></div>").appendTo(this.progressHolder);
                obj.find('.xagio-file-names').html(this.statusbar);

                this.setFileNameSize = function (name, size) {
                    let sizeStr = "";
                    let sizeKB = size / 1024;
                    if (parseInt(sizeKB) > 1024) {
                        let sizeMB = sizeKB / 1024;
                        sizeStr = sizeMB.toFixed(2) + " MB";
                    } else {
                        sizeStr = sizeKB.toFixed(2) + " KB";
                    }
                    this.filename.html(name + ' <div class="xagio-upload-filesize">' + sizeStr + '</div>');
                };

                this.setProgress = function (progress) {
                    let progressBarWidth = progress * this.progressBar.width() / 100;
                    this.progressBar.find('div').animate({width: progressBarWidth}, 10).html(progress + "% ");
                    if (parseInt(progress) >= 100) {
                        this.abort.hide();
                    }
                };

                this.setAbort = function (jqxhr) {
                    let sb = this.statusbar;
                    this.abort.off('click').on('click', function () {
                        jqxhr.abort();
                        sb.hide();
                    });
                };
            }

            function sendFileToServer(formData, status, callback) {
                let uploadURL = xagio_data.wp_post; // Adjust if needed
                let jqXHR = $.ajax({
                                       xhr        : function () {
                                           let xhrobj = $.ajaxSettings.xhr();
                                           if (xhrobj.upload) {
                                               xhrobj.upload.addEventListener('progress', function (event) {
                                                   let percent = 0;
                                                   let position = event.loaded || event.position;
                                                   let total = event.total;
                                                   if (event.lengthComputable) {
                                                       percent = Math.ceil(position / total * 100);
                                                   }
                                                   status.setProgress(percent);
                                               }, false);
                                           }
                                           return xhrobj;
                                       },
                                       url        : uploadURL,
                                       type       : "POST",
                                       contentType: false,
                                       processData: false,
                                       cache      : false,
                                       data       : formData,
                                       success    : function (data) {
                                           status.setProgress(100);
                                           if (typeof callback === 'function') {
                                               callback(data); // Call the callback function
                                           }
                                           resetUploadFormAndCloseModal($this);
                                       }
                                   });
                status.setAbort(jqXHR);
            }

            function resetUploadFormAndCloseModal($element) {
                // Reset the form to its original HTML
                $element.html(originalHTML);

                // Re-initialize the uploader to restore functionality
                $element.xagio_uploader(action, callback);

                // Close the modal if it's a dialog element
                if ($element[0].nodeName === 'DIALOG') {
                    $element[0].close();
                }
            }
        });
    };

    $(document).ready(function () {
        actions.initAccordion();
        actions.initSliders();
        actions.initTabs();
        actions.initCheckboxes();
        actions.initActionButtons();
        actions.saveTroubleshootingSliders();
        actions.saveSettingsSlider();
        actions.saveSettingsTextbox();
        actions.initXagioTags();
        actions.dismissMigrationNotice();
        actions.closeXagioNotify();

        $.post(xagio_data.wp_post, 'action=xagio_get_links', function (d) {
            if (d !== false) {
                let dashboard_btn = $('.xagio-button-premium-button');
                if (dashboard_btn.length > 0) {
                    dashboard_btn.text(d.dashboard.text);
                    dashboard_btn.attr('href', d.dashboard.url);
                }
            }
        });

        $(document).mouseup(function (e) {
            let container = $(".xagio-dropdown .xagio-button-dropdown");
            if (!container.is(e.target) && container.has(e.target).length === 0 &&
                !$('.xagio-dropdown > button').is(e.target)) {
                container.prev('button.xagio-on').removeClass('xagio-on');
                container.hide();
            }

            if ($(e.target).is('[data-xagio-dropdown-close]')) {
                container.prev('button.xagio-on').removeClass('xagio-on');
                container.hide();
            }

            container = $(".xagio-dropdown-simple .xagio-button-dropdown");
            if (!container.is(e.target) && container.has(e.target).length === 0 &&
                !$('.xagio-dropdown > button').is(e.target)) {
                container.prev('button.xagio-dropdown-show').removeClass('xagio-dropdown-show');
                container.hide();
            }

            if ($(e.target).is('[data-xagio-dropdown-close]')) {
                container.prev('button.xagio-dropdown-show').removeClass('xagio-dropdown-show');
                container.hide();
            }
        });


        $(document).on('click', '.xagio-dropdown > button', function (e) {
            let btn = $(this);
            let dropdown = btn.next('.xagio-button-dropdown');

            if (btn.hasClass('xagio-on')) {
                btn.removeClass('xagio-on')
                dropdown.hide();
            } else {
                btn.addClass('xagio-on');
                dropdown.show();
            }
        });

        $(document).on('click', '.xagio-dropdown-simple > button', function (e) {
            let btn = $(this);
            let dropdown = btn.next('.xagio-button-dropdown');

            if (btn.hasClass('xagio-dropdown-show')) {
                btn.removeClass('xagio-dropdown-show')
                setTimeout(function () {
                    dropdown.hide();
                }, 300); // Match this timeout with the CSS transition duration
            } else {
                btn.addClass('xagio-dropdown-show');
                dropdown.show();
            }
        });

        $(document).on('click', '[data-xagio-modal]', function (e) {
            e.preventDefault();
            let btn = $(this);
            let modal_id = btn.attr('data-xagio-modal');
            let modal = document.getElementById(modal_id);

            if (modal) {
                modal.showModal();
            }
        });

        $(document).on('click', '.xagio-modal-close, [data-xagio-close-modal]', function (e) {
            e.preventDefault();
            let btn = $(this);
            let modal = btn.parents('.xagio-modal');
            modal[0].close();
        });

        $(document).on('mouseenter', '[data-xagio-tooltip]', function (e) {
            e.preventDefault();
            let el = $(this);
            let title = el.attr('data-xagio-title');

            if (title.length < 1) return;

            let position = $(this).data('xagio-tooltip-position') || 'top';
            let tooltip = $(`<div class="xagio-tooltip ${position}"><div class="xagio-tooltip-body">${title}</div><div class="xagio-tooltip-arrow"></div></div>`);

            let dialogContainer = el.closest('.xagio-modal');

            let tooltipTop, tooltipLeft;

            if (dialogContainer.length) {
                tooltip.appendTo(dialogContainer);

                let elementPosition = el.offset();
                let dialogPosition = dialogContainer.offset(); // Get dialog's position relative to the document

                tooltipLeft = elementPosition.left - dialogPosition.left + el.outerWidth() / 2 - tooltip.outerWidth() /
                              2;

                if (position === 'bottom') {
                    tooltipTop = elementPosition.top - dialogPosition.top + el.outerHeight() + 10; // Position below the element
                } else {
                    tooltipTop = elementPosition.top - dialogPosition.top - tooltip.outerHeight() - 10; // Default to position above the element
                }

                let dialogWidth = dialogContainer.outerWidth(); // Get dialog's outer width

                if (tooltipLeft < 0) {
                    tooltipLeft = 0;
                } else if (tooltipLeft + tooltip.outerWidth() > dialogWidth) {
                    tooltipLeft = dialogWidth - tooltip.outerWidth();
                }

                if (tooltipTop < 0) {
                    tooltipTop = 0;
                }

                let arrowLeft = elementPosition.left - dialogPosition.left + el.outerWidth() / 2 - tooltipLeft;
                tooltip.find('.xagio-tooltip-arrow').css('left', arrowLeft);
            } else {
                tooltip.appendTo('body');

                let elementPosition = el.offset();
                tooltipLeft = elementPosition.left + el.outerWidth() / 2 - tooltip.outerWidth() / 2;

                if (position === 'bottom') {
                    tooltipTop = elementPosition.top + el.outerHeight() + 10; // Position below the element
                } else {
                    tooltipTop = elementPosition.top - tooltip.outerHeight() - 10; // Default to position above the element
                }

                let windowWidth = $(window).width();

                if (tooltipLeft < 0) {
                    tooltipLeft = 0;
                } else if (tooltipLeft + tooltip.outerWidth() > windowWidth) {
                    tooltipLeft = windowWidth - tooltip.outerWidth();
                }

                if (tooltipTop < 0) {
                    tooltipTop = 0;
                }

                let arrowLeft = elementPosition.left + el.outerWidth() / 2 - tooltipLeft;
                tooltip.find('.xagio-tooltip-arrow').css('left', arrowLeft);
            }

            tooltip.css({
                            left: tooltipLeft,
                            top : tooltipTop
                        });
        });

        $(document).on('mouseleave', '[data-xagio-tooltip]', function () {
            $('.xagio-tooltip').remove();
        });


        const xagioRange = $('.xagio-range');

        xagioRange.each(function () {
            let value = $(this).val();
            let min = $(this).attr('min');
            let max = $(this).attr('max');
            const clamp = clampToPercentage(value, min, max);
            $(this).css('background', `linear-gradient(to right, #1a4573 0%, #1a4573 ${clamp}%, #a9bacb ${clamp}%, #a9bacb 100%)`);

            $(this).on('input, change', function () {
                let value = $(this).val();
                let min = $(this).attr('min');
                let max = $(this).attr('max');
                const clamp = clampToPercentage(value, min, max);

                $(this).next('.current-value').text(value);
                $(this).css('background', `linear-gradient(to right, #1a4573 0%, #1a4573 ${clamp}%, #a9bacb ${clamp}%, #a9bacb 100%)`);
            });
        });
    });

    function clampToPercentage(value, min, max) {
        value = Math.max(Math.min(value, max), min);
        const normalizedValue = (value - min) / (max - min);
        const percentage = normalizedValue * 100;
        return percentage.toFixed(2);
    }

    let actions = {
        dismissMigrationNotice    : function () {
            $(document).on('click', '.migration-no-thanks', function (e) {
                e.preventDefault();
                let btn = $(this);


                btn.disable();
                $.post(xagio_data.wp_post, 'action=xagio_dismiss_migration_notice', function (d) {
                    btn.disable();
                    $('.xagio-migraion-notice').slideUp();
                });


            });
        },
        initXagioTags             : function () {
            var blocks = [];
            if (typeof xagio_replaces != 'undefined') {
                blocks = xagio_replaces;
            }

            let XagioReplaceHtmlEntites = (function () {
                let translate_re = /&(nbsp|amp|quot|lt|gt);/g;
                let translate = {
                    "nbsp": "",
                    "amp" : "&",
                    "quot": "\"",
                    "lt"  : "<",
                    "gt"  : ">"
                };
                return function (s) {
                    return (s.replace(translate_re, function (match, entity) {
                        return translate[entity];
                    }));
                }
            })();

            function replaceVars(e) {
                // check if space bar was hit
                if (e.keyCode === 32) {

                    for (let block in blocks) {
                        let replacement = blocks[block]['name'];

                        block = '{' + block + '}';

                        // Get selection and range based on position of caret
                        // (we assume nothing is selected, and range points to the position of the caret)
                        var sel = window.getSelection();
                        var range = sel.getRangeAt(0);

                        // check that we have at least incorrectTxt.length characters in our container
                        if (range.startOffset - block.length >= 0) {

                            // clone the range, so we can alter the start and end
                            var clone = range.cloneRange();

                            // alter start and end of cloned ranged, so it selects incorrectTxt.length characters
                            clone.setStart(range.startContainer, range.startOffset - block.length);
                            clone.setEnd(range.startContainer, range.startOffset);

                            // get contents of cloned range
                            var contents = clone.toString();

                            // check if the contents of the cloned range is equal to our incorrectTxt string
                            if (contents === block) {

                                clone.deleteContents();

                                var blockTag = document.createElement('div');
                                blockTag.innerHTML = '<span class="block-name">' + replacement + '</span>' +
                                                     '<i class="xagio-icon xagio-icon-arrow-down"></i>';
                                blockTag.className = 'seo-block';
                                blockTag.setAttribute('data-block', block);
                                blockTag.setAttribute('contenteditable', false);
                                range.insertNode(blockTag);

                                // set the start of the range after the inserted node, so we have the caret after the inserted text
                                range.setStartAfter(blockTag);

                                // Chrome fix
                                sel.removeAllRanges();
                                sel.addRange(range);

                            }
                        }

                    }

                }
            }

            function openDropdown(element, existing) {

                // toggle caret icon
                element.find('i').toggleClass('xagio-icon-arrow-down xagio-icon-arrow-up');

                if (element.find('.xagio-tags-dropdown').length > 0) {
                    $('.xagio-tags-dropdown').each(function () {
                        $(this).prev('i').toggleClass('xagio-icon-arrow-down xagio-icon-arrow-up');
                        $(this).remove();
                    });
                    return;
                }

                $('.xagio-tags-dropdown').remove();
                let dropdown = $('<div class="xagio-tags-dropdown"></div>');
                dropdown.css('top', element.css('height'));

                let search = $('<div class="xagio-tags-search"></div>');
                search.append('<input placeholder="Search blocks..." type="search"/>');
                if (existing) {
                    search.append('<i class="xagio-icon xagio-icon-delete remove-block"></i>');
                }
                dropdown.append(search);

                let list = $('<ul></ul>');
                for (let block in blocks) {
                    let item = $('<li></li>');
                    item.attr('data-block', '{' + block + '}');
                    item.append('<div class="icon"><i class="xagio-icon xagio-icon-plus"></i></div>');
                    item.append('<div class="text"><span class="name">' + blocks[block]['name'] +
                                '</span><span class="desc">' + blocks[block]['desc'] + '</span></div>');
                    item.append('<div class="clearfix"></div>')
                    list.append(item);
                }

                dropdown.append(list);

                element.append(dropdown);

            }

            $(document).on('mouseover', '.xagio-tags-container', function (e) {
                e.preventDefault();
                $(this).find('.xagio-tags-preview').toggleClass('active');
            });

            $(document).on('mouseout', '.xagio-tags-container', function (e) {
                e.preventDefault();
                $(this).find('.xagio-tags-preview').removeClass('active');
            });

            $(document).on('click', '.remove-block', function (e) {
                e.preventDefault();
                let tags_values = $(this).parents('.xagio-tags-values');
                $(this).parents('.seo-block').remove();
                tags_values.trigger('input');
            });

            /** initiate xagio tags */
            $('.xagio-tags').each(function () {

                // if this is input field change type to hidden, if not, create hidden input field
                if ($(this).is('input')) {
                    $(this).attr('type', 'hidden');
                } else {
                    $('<input type="hidden" name="' + $(this).attr('name') + '" value="' + $(this).html() +
                      '">').insertAfter($(this));
                    $(this).attr('name', '');
                    $(this).hide();
                }
                $('<div class="xagio-tags-container">' +
                  '<div class="xagio-tags-preview">' + $(this).val() + '</div>' +
                  '<div class="xagio-tags-values" contenteditable="true">' + $(this).val() + '</div>' +
                  '<div class="xagio-tags-list">Blocks <i class="xagio-icon xagio-icon-arrow-down"></i></div>' +
                  '</div>').insertAfter($(this));


                renderBlocks($(this).next().find('.xagio-tags-preview'));
                $(this).next().find('.xagio-tags-values').trigger('keydown');
            });

            $(document).click(function (e) {

                $('.xagio-tags-dropdown').each(function () {
                    $(this).prev('i').toggleClass('xagio-icon-arrow-down xagio-icon-arrow-up');
                    $(this).remove();
                });

            });

            $(document).on('click', '.xagio-tags-dropdown', function (e) {
                e.preventDefault();
                e.stopPropagation();
            });

            $(document).on('input', '.xagio-tags-values', function (e) {
                e.preventDefault();

                let input = $(this).parents('.xagio-tags-container').prev();
                let preview = $(this).parents('.xagio-tags-container').find('.xagio-tags-preview');
                let value = $(this).html();

                // remove all block tags
                value = XagioReplaceHtmlEntites(value.replace(/<div class="seo-block" data-block="(.*?)"[^>]*>.*?<\/div>/g, ' $1 ')).trim();

                input.val(value);
                preview.html(value);

                renderBlocks(preview);
            });

            function renderBlocks(preview) {
                $.post(xagio_data.wp_post, 'action=xagio_render_blocks&html=' + encodeURI(preview.html()) + '&page=' +
                                           xagio_post_id, function (d) {
                    preview.html(d.data);
                });
            }

            $(document).on('click', '.xagio-tags-dropdown li', function (e) {
                e.preventDefault();

                let $this = $(this);
                let container = $this.parents('.xagio-tags-container');
                let values = container.find('.xagio-tags-values');
                let block = $this.attr('data-block').replace('{', '').replace('}', '');

                if ($this.parents('.xagio-tags-list').length > 0) {
                    let name = blocks[block]['name'];
                    let shortcode = '{' + block + '}';
                    values.append('<div class="seo-block" data-block="' + shortcode +
                                  '" contenteditable="false"><span class="block-name">' + name +
                                  '</span><i class="xagio-icon xagio-icon-arrow-down"></i></div>');
                    values.trigger('input');
                } else {
                    $this.parents('.seo-block').attr('data-block', $this.attr('data-block')).find('span.block-name').html(blocks[block]['name']);
                }
            });

            $(document).on('keydown', '.xagio-tags-values', function (e) {
                replaceVars(e);
                $(this).trigger('input');
            });

            $(document).on('paste', '.xagio-tags-values', function (e) {
                e.preventDefault();
                var text = '';
                if (e.clipboardData || e.originalEvent.clipboardData) {
                    text = (e.originalEvent || e).clipboardData.getData('text/plain');
                } else if (window.clipboardData) {
                    text = window.clipboardData.getData('Text');
                }
                if (document.queryCommandSupported('insertText')) {
                    document.execCommand('insertText', false, text);
                } else {
                    document.execCommand('paste', false, text);
                }
                replaceVars(e);
                $(this).trigger('input');
            });

            $(document).on('click', '.xagio-tags-list', function (e) {

                e.preventDefault();
                e.stopPropagation();

                openDropdown($(this), false);

            });

            $(document).on('click', '.seo-block', function (e) {

                e.preventDefault();
                e.stopPropagation();

                openDropdown($(this), true);
            });
        },
        initCheckboxes            : function () {
            $(document).on('click', '.xagio-input-checkbox', function () {
                let checkbox = $(this);

                if (checkbox.attr('data-value') === undefined) {
                    checkbox.val(checkbox.prop('checked') ? 1 : 0);
                } else {
                    checkbox.val(checkbox.prop('checked') ? checkbox.data('value') : '');
                }
            });
        },
        initTabs                  : function () {
            $('.xagio-tab').each(function (index) {
                let tabs = $(this);
                let tabContentHolder = tabs.next('.xagio-tab-content-holder');
                let activeTab = tabs.find('li.xagio-tab-active').index();
                let tabContent = tabContentHolder.find(`> .xagio-tab-content:nth-child(${activeTab + 1})`);
                if (tabContent.length > 0) tabContent.show();
            });

            $(document).on('click', '.xagio-tab > li a', function (e) {
                e.preventDefault();
            });

            $(document).on('click', '.xagio-tab > li:not(".xagio-tab-active")', function (e) {
                e.preventDefault();
                let tab = $(this);

                tab.parents('.xagio-tab').find('> li').removeClass('xagio-tab-active');
                tab.addClass('xagio-tab-active');

                let tabContentHolder = tab.parents('.xagio-tab').next('.xagio-tab-content-holder');
                let activeTab = tab.index();

                tabContentHolder.find(`> .xagio-tab-content`).hide();
                let tabContent = tabContentHolder.find(`> .xagio-tab-content:nth-child(${activeTab + 1})`);
                if (tabContent.length > 0) tabContent.fadeIn(function () {
                    tabContent.trigger('loaded');
                });
            });
        },
        initSliders               : function () {
            // Init Sliders
            $(document).on('click', '.xagio-slider-button', function (e) {
                e.preventDefault();

                let attr = $(this).attr('data-element');
                let page = $(this).attr('data-page');
                let tooltip_new = $(this).attr('data-tooltip-change');
                let tooltip_old = $(this).attr('data-tooltip-original');

                if ($(this).hasClass('on')) {
                    $(this).removeClass('on');

                    if (typeof tooltip_new !== undefined) {
                        $(this).parents('.xagio-slider-container').attr('data-xagio-title', tooltip_new);
                        $('.xagio-tooltip .xagio-tooltip-body').text(tooltip_new);
                    }

                    if (page === 'edit') {
                        $(this).parents('.xagio-slider-container').find(`input[name="${attr}"]`).val(0).trigger('change');
                    } else {
                        $('input#' + attr).val(0).trigger('change');
                    }
                } else {
                    $(this).addClass('on');

                    if (typeof tooltip_new !== undefined) {
                        $(this).parents('.xagio-slider-container').attr('data-xagio-title', tooltip_old);
                        $('.xagio-tooltip .xagio-tooltip-body').text(tooltip_old);
                    }

                    if (page === 'edit') {
                        $(this).parents('.xagio-slider-container').find(`input[name="${attr}"]`).val(1).trigger('change');
                    } else {
                        $('input#' + attr).val(1).trigger('change');
                    }
                }
            });

            $(document).on('change', '.edit-page-seo-enable', function (e) {
                e.preventDefault();
                let input = $(this);
                let page_id = input.parents('.xagio-slider-container').find('input[name="post_id"]').val();

                let data = [
                    {
                        name : 'action',
                        value: 'xagio_save_seo_search'
                    },
                    {
                        name : 'status',
                        value: $(this).val()
                    },
                    {
                        name : 'post_id',
                        value: page_id
                    }
                ];
                $.post(xagio_data.wp_post, data, function (d) {
                    xagioNotify("success", "Setting updated.");
                });

            });

        },
        initAccordion             : function () {
            $(document).on('click', '.xagio-accordion-title', function (e) {
                // if event target is not the title, return
                if ($(e.target).is('button') || $(e.target).parents('button').length) {
                    return;
                }

                let parent = $(this).parents('.xagio-accordion');

                if (parent.hasClass('xagio-accordion-opened')) {
                    parent.removeClass('xagio-accordion-opened');
                } else {
                    parent.addClass('xagio-accordion-opened');
                }


                if (typeof scripts !== 'undefined' && typeof scripts.refreshEditors === 'function') {
                    scripts.refreshEditors();
                }
            });
        },
        initActionButtons         : function () {
            $(document).on('click', '.action-button', function (e) {
                e.preventDefault();
                let button = $(this);
                let target = button.data('target');
                button.disable();
                $.post(xagio_data.wp_post, 'action=' + target, function (d) {
                    button.disable();
                    if (d.hasOwnProperty('message')) {
                        xagioNotify(d.status, d.message);
                    } else {
                        xagioNotify(d.status, "Operation finished successfully.");
                    }
                });
            });
        },
        saveTroubleshootingSliders: function () {
            $(document).on('click', '.xagio-slider-button.xagio-slider-button-troubleshooting', function (e) {
                e.preventDefault();

                let attr = $(this).attr('data-element');
                actions.updateTroubleshooting(attr, $('#' + attr).val());
            });
        },
        saveSettingsSlider        : function () {
            $(document).on('click', '.slider-button.slider-button-settings, .xagio-slider-button.xagio-slider-button-settings', function (e) {
                e.preventDefault();

                let attr = $(this).attr('data-element');

                setTimeout(function () {
                    actions.updateSettings(attr, $('#' + attr).val());
                }, 500);
            });
        },
        saveSettingsTextbox       : function () {
            $(document).on('keyup paste', '.text-settings', function (e) {
                let $this = $(this);
                let name = $this.attr('name');

                clearTimeout(textbox_ajax_timeout);
                textbox_ajax_timeout = setTimeout(function () {
                    actions.updateSettings(name, $this.val());
                }, 500);
            });
        },
        updateSettings            : function (option_name, option_value) {
            let data = [
                {
                    name : 'action',
                    value: 'xagio_save_settings'
                },
                {
                    name : option_name,
                    value: option_value
                }
            ];
            $.post(xagio_data.wp_post, data, function (d) {
                xagioNotify("success", "Setting updated.")
            });
        },
        updateTroubleshooting     : function (option_name, option_value) {
            let data = [
                {
                    name : 'action',
                    value: 'xagio_save_troubleshooting'
                },
                {
                    name : option_name,
                    value: option_value
                }
            ];
            $.post(xagio_data.wp_post, data, function (d) {
                xagioNotify("success", "Setting updated.");
            });
        },

        closeXagioNotify: function () {
            $(document).on('click', ".xagio-notify", function () {
                let notify = $(this);
                notify.addClass('hide');
                setTimeout(function () {
                    notify.remove();
                    notificationCount--;
                }, 300);
            });
        },
    };


})(jQuery);


String.prototype.containsText = function (it) {
    return this.indexOf(it) != -1;
};