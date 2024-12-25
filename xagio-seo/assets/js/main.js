(function ($) {
    'use strict';

    /**
     *  Global doc.ready function
     */
    $(document).ready(function () {

        // Init Sliders
        $(document).on('click', '.slider-button', function (e) {
            e.preventDefault();

            let attr = $(this).attr('data-element');

            if ($(this).hasClass('on')) {
                $(this).removeClass('on').html('Off');
                $('#' + attr).val(0).trigger('change');
            } else {
                $(this).addClass('on').html('On');
                $('#' + attr).val(1).trigger('change');
            }

        });

        $('.prs-settings-form').submit(function (e) {
            e.preventDefault();

            $.post(xagio_data.wp_post, $(this).serialize(), function (d) {
                xagioNotify("success", "Your settings have been saved.");
            });
        });

        // Set the selected option of selects
        $('select').each(function () {
            var attr = $(this).attr('data-value');
            if (typeof attr !== typeof undefined && attr !== false && attr !== '') {
                $(this).val(attr);
            }
        });

    });


})(jQuery);

function isBlank(value) {
    return typeof value === 'string' && !value.trim() || typeof value === 'undefined' || value === null || value === 0;
}

String.prototype.containsText = function (it) {
    return this.indexOf(it) != -1;
};
String.prototype.isJSON       = function () {
    var json;
    try {
        json = JSON.parse(this);
    } catch (e) {
        return this;
    }
    return json;
};

jQuery.extend({
    highlight: function (node, re, nodeName, className) {
        if (node.nodeType === 3) {
            var match = node.data.match(re);
            if (match) {
                var highlight       = document.createElement(nodeName || 'span');
                highlight.className = className || 'highlight';
                var wordNode        = node.splitText(match.index);
                wordNode.splitText(match[0].length);
                var wordClone = wordNode.cloneNode(true);
                highlight.appendChild(wordClone);
                wordNode.parentNode.replaceChild(highlight, wordNode);
                return 1; //skip added node in parent
            }
        } else if ((node.nodeType === 1 && node.childNodes) && // only element nodes that have children
            !/(script|style)/i.test(node.tagName) && // ignore script and style nodes
            !(node.tagName === nodeName.toUpperCase() && node.className === className)) { // skip if already highlighted
            for (var i = 0; i < node.childNodes.length; i++) {
                i += jQuery.highlight(node.childNodes[i], re, nodeName, className);
            }
        }
        return 0;
    }
});


jQuery.fn.unhighlight = function (options) {
    var settings = {
        className: 'highlightCloud',
        element:   'span'
    };
    jQuery.extend(settings, options);

    return this.find(settings.element + "." + settings.className).each(function () {
        var parent = this.parentNode;
        parent.replaceChild(this.firstChild, this);
        parent.normalize();
    }).end();
};

jQuery.fn.highlight = function (words, options) {
    var settings = {
        className:     'highlightCloud',
        element:       'span',
        caseSensitive: true,
        wordsOnly:     true
    };
    jQuery.extend(settings, options);

    if (words.constructor === String) {
        words = [words];
    }
    words = jQuery.grep(words, function (word, i) {
        return word != '';
    });
    words = jQuery.map(words, function (word, i) {
        return word.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    });
    if (words.length == 0) {
        return this;
    }

    var flag    = settings.caseSensitive ? "" : "i";
    var pattern = "(" + words.join("|") + ")";
    if (settings.wordsOnly) {
        pattern = "\\b" + pattern + "\\b";
    }
    var re = new RegExp(pattern, flag);

    return this.each(function () {
        jQuery.highlight(this, re, settings.element, settings.className);
    });
};

// Disable Element
jQuery.fn.extend({
    uploader: function (data, allowedExtension, callback) {
        return this.each(function () {
            var modal       = jQuery(this);
            var progressBar = modal.find(".uk-progress");
            var bar         = progressBar.find('.uk-progress-bar');
            var settings    = {

                action:      xagio_data.wp_post + '?' + data,
                allow:       '*.(' + allowedExtension + ')',
                param:       'file-import',
                filelimit:   1,
                before: function () {
                    if(data === 'action=xagio_importLinks') {
                        // this.action += '&domain=' + jQuery('#linkInsertDomain').val();
                    }

                },
                loadstart:   function () {
                    bar.css("width", "0%").text("0%");
                    progressBar.removeClass("xagio-hidden");
                },
                progress:    function (percent) {
                    percent = Math.ceil(percent);
                    bar.css("width", percent + "%").text(percent + "%");
                    modal[0].close();
                    if (percent == 100) {
                        xagioModal(`<span id="csvImporting">Importing ${allowedExtension.toUpperCase()} ...</span>`, `<div class="uk-progress uk-progress-striped uk-active"><div class="uk-progress-bar" style="width: 100%;"></div></div>`);
                    }
                },
                allcomplete: function (response) {

                    bar.css("width", "100%").text("100%");
                    if(data === 'action=xagio_importLinks') {
                        xagioNotify("success", response);
                    } else {
                        xagioNotify("success", `Successfully imported ${allowedExtension.toUpperCase()}.`);
                    }
                    progressBar.addClass("xagio-hidden");
                    jQuery("#csvImporting").closest("dialog.xagio-modal").remove();

                    progressBar.addClass("uk-hidden");
                    callback();

                }
            };
        });
    },
    disable:  function (message) {
        return this.each(function () {
            var i = jQuery(this).find('i');
            if (typeof jQuery(this).attr('disabled') == 'undefined') {
                if (i.length > 0) {
                    i.attr('class-backup', i.attr('class'));
                    i.attr('class', 'xagio-icon xagio-icon-sync xagio-icon-spin');
                }
                if (typeof message != 'undefined') {
                    jQuery(this).attr('text-backup', jQuery(this).text());
                    jQuery(this).text(' ' + message);
                    jQuery(this).prepend(i);
                }
                jQuery(this).attr('disabled', 'disabled');
            } else {
                jQuery(this).removeAttr('disabled');
                if (i.length > 0) i.attr('class', i.attr('class-backup'));
                if (typeof jQuery(this).attr('text-backup') != 'undefined') {
                    jQuery(this).text(' ' + jQuery(this).attr('text-backup'));
                    jQuery(this).prepend(i);
                }
            }
        });
    }
});

jQuery.fn.extend({
    blockUI: function () {
        return this.each(function () {
            let $this = jQuery(this);
            if ($this.find('.block-element').length != 0) {
                $this.removeClass('block-element-parent');
                $this.find('.block-element').remove();
            } else {
                $this.addClass('block-element-parent');
                $this.append('<div class="block-element"></div>');
            }
        });
    }
});

function copyTextToClipboard(text) {
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).then(function () {
        console.log('Async: Copying to clipboard was successful!');
    }, function (err) {
        console.error('Async: Could not copy text: ', err);
    });
}

function fallbackCopyTextToClipboard(text) {
    var textArea   = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top      = "0";
    textArea.style.left     = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        var msg        = successful ? 'successful' : 'unsuccessful';
        console.log('Fallback: Copying text command was ' + msg);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    document.body.removeChild(textArea);
}

function xagio_notify(status, message) {
    xagioNotify(status == 'success' ? "success" : "error", message);
}
