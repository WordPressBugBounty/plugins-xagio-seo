var logTable;
var plugins,
    themes;
let log_info = [
    `<b><i class="xagio-icon xagio-icon-info"></i> What should you do here?</b> Here you can manage your website 301 redirects and 404 monitor.`,
    `<b><i class="xagio-icon xagio-icon-info"></i> What should you do here?</b> Here you can manage your website 301 redirects and 404 monitor.`,
    `<i class="xagio-icon xagio-icon-info"></i> <b>Set xagio the way you like it.</b> Configure miscellaneous settings that tell our plugin how to behave on 404s monitor section.`,
];
let selected_redirects = [];
let selected_logs = [];

(function ($) {
    'use strict';

    $(document).ready(function () {

        $(document).on('change.uk.tab', function (e, active, prev) {
            if (typeof active != 'undefined') {
                let currentTabIndex = active.index();
                let settingsInfo    = $('.log-info');
                settingsInfo.html(log_info[currentTabIndex]);
            }
        });

        link.loadRedirects();
        link.addNewRedirect();
        link.editNewRedirect();
        link.deleteRedirect();
        link.selectAllRedirects();
        link.uploadCSV();

        link.loadLog404();
        link.toggleIp();
        link.toggleReference();
        link.toggleAgent();
        link.selectAllLog404();
        link.deleteLog404();
        link.addNew404Redirect();
        link.export404Log();
        link.LogSettings();
        link.customRedirectSettings();

        $(document).on('click', '.remove-selected-ids', function () {
            let checkbox = $(this);
            if (checkbox.prop('checked')) {
                selected_redirects.push(checkbox.data('id'));
            } else {
                selected_redirects = $.grep(selected_redirects, (value) => value != checkbox.data('id'));
            }
            selected_redirects = $.unique(selected_redirects);

            if(selected_redirects.length > 0) {
                $('.selected-redirects').html(selected_redirects.length);
                $('.remove-selected-redirects').show();
            } else {
                $('.selected-redirects').html('');
                $('.remove-selected-redirects').hide();
            }
        });

        $(document).on('click', '.remove-selected-log-ids', function () {
            let checkbox = $(this);
            if (checkbox.prop('checked')) {
                selected_logs.push(checkbox.data('id'));
            } else {
                selected_logs = $.grep(selected_logs, (value) => value != checkbox.data('id'));
            }
            selected_logs = $.unique(selected_logs);

            if(selected_logs.length > 0) {
                $('.selected-logs-count').html(selected_logs.length);
                $('.remove-selected-log404').show();
            } else {
                $('.selected-logs-count').html('');
                $('.remove-selected-log404').hide();
            }
        });

    });

    var link = {

        loadRedirects         : function () {
            var messages = {
                empty  : '<tr><td colspan="5">Can\'t find any active redirects.</td></tr>',
                loading: '<tr><td colspan="5"><i class="xagio-icon xagio-icon-sync xagio-icon-spin"></i> Loading ...</td></tr>'
            };
            var table    = $('.table-redirects');
            var tbody    = table.find('tbody');

            tbody.empty().append(messages.loading);

            $.post(xagio_data.wp_post, 'action=xagio_get_redirects', function (d) {
                if (d.status == 'success') {

                    let redirects_total = d.data.length;
                    if (d.data.length == 0) {
                        tbody.empty().append(messages.empty);
                    } else {
                        tbody.empty();

                        $('.total-number-of-redirects').html(redirects_total);
                        for (var i = 0; i < d.data.length; i++) {
                            var data = d.data[i];
                            if (!data.new.match("^http")) {
                                data.new = '/' + data.new;
                            }

                            var qCheck;

                            if (data.qry_str_url == true) {
                                qCheck = 'checked="checked"';
                            } else {
                                qCheck = '';
                            }

                            let label = data.old;

                            if (data.old == '') {
                                label = 'Homepage'
                            } else {
                                label = '/' + data.old;
                            }

                            var html = '<tr>' +
                                '<td><input type="checkbox" data-id="' + data.id + '" class="xagio-input-checkbox remove-selected-ids"></td>' +
                                '<td><a target="_blank" href="/' + data.old + '">' + label + '</a></td>' +
                                '<td><a target="_blank" href="' + data.new + '">' + data.new + '</a></td>' +
                                '<td>' + data.date_created + '</td>' +
                                '<td>' +
                                '<div class="xagio-cell-actions-row xagio-flex-align-center"><button type="button" class="xagio-button xagio-button-primary xagio-button-mini edit-redirect" data-id="' + data.id + '" data-old-url="' + data.old + '" data-new-url="' + data.new + '" data-xagio-tooltip data-xagio-title="Edit this redirect"><i class="xagio-icon xagio-icon-edit"></i></button>' +
                                '<button type="button" class="xagio-button xagio-button-danger xagio-button-mini delete-redirect " data-id="' + data.id + '" data-xagio-tooltip data-xagio-title="Trash this redirect"><i class="xagio-icon xagio-icon-delete"></i></button>' +
                                `<div class="xagio-slider-container">
                                    <input type="hidden" name="toggle-redirect-${data.id}" id="toggle-redirect-${data.id}" value="${data.is_redirect_active}" />
                                    <div class="xagio-slider-frame">
                                        <span class="xagio-slider-button toggle-redirect ${(data.is_redirect_active === "1") ? 'on' : ''}" data-element="toggle-redirect-${data.id}" data-id="${data.id}"></span>
                                    </div>
                                </div>` +
                                       '</td>' +
                                       '</tr>';
                            tbody.append(html);
                        }
                    }

                } else {
                    xagioNotify("danger", "An unknown error has occurred.");
                }
            });

        },
        selectAllRedirects    : function () {
            $(document).on('click', '.select-all-redirects', function (e) {
                // e.preventDefault();
                $(".remove-selected-ids").each(function (i) {
                    var checked = $(this).prop("checked");
                    if (checked == true) {
                        $(this).prop("checked", false);
                        selected_redirects = $.grep(selected_redirects, (value) => value != $(this).data('id'));
                    } else {
                        selected_redirects.push($(this).data('id'));
                        $(this).prop("checked", true);
                    }
                });

                selected_redirects = $.unique(selected_redirects);
                if(selected_redirects.length > 0) {
                    $('.selected-redirects').html(selected_redirects.length);
                    $('.remove-selected-redirects').show();
                } else {
                    $('.selected-redirects').html('');
                    $('.remove-selected-redirects').hide();
                }
            })
        },
        deleteRedirect        : function () {
            $(document).on('click', '.delete-redirect', function (e) {
                e.preventDefault();
                var button = $(this);
                var id     = $(this).data('id');

                xagioModal("Are you sure?", "Are you sure that you want to delete this redirect?", function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_delete_redirect&id=' + id, function (d) {
                            button.disable();
                            link.loadRedirects();
                        });
                    }
                });
            });

            $(document).on('click', '.remove-selected-redirects', function (e) {
                e.preventDefault();
                var button = $(this);

                var ids = [];

                $('.remove-selected-ids').each(function () {
                    if (this.checked) {
                        ids.push($(this).data('id'));
                    }
                });

                xagioModal("Are you sure?", "Are you sure that you want to delete this redirect?", function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_delete_redirect&id=' + ids, function (d) {
                            button.disable();
                            link.loadRedirects();
                        });
                    }
                });
            });

            $(document).on('click', '.remove-all-redirects', function (e) {
                e.preventDefault();
                var button = $(this);
                xagioModal("Are you sure?", "Are you sure that you want to delete all redirects?", function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_delete_all_redirects', function (d) {
                            button.disable();
                            link.loadRedirects();
                        });
                    }
                });
            })
        },
        customRedirectSettings: function () {
            $(document).on('click', '.toggle-redirect', function (e) {
                var button  = $(this);
                var id      = $(this).data('id');
                let checked = $(this).prop('checked');
                let input = button.parents('.xagio-slider-container').find(`#toggle-redirect-${id}`).val();

                $.post(xagio_data.wp_post, 'action=xagio_toggle_redirect&id=' + id + '&value=' + input, function (d) {
                    xagioNotify("success", "Setting updated.");
                    link.loadRedirects();
                });

            });
        },
        addNewRedirect        : function () {
            $(document).on('click', '.add-new-redirect', function (e) {
                e.preventDefault();

                let button = $(this);

                xagioPromptModal("Confirm", "Old URL (use the /oldurl/ format):", function (result) {

                    if (result) {
                        let old_url = result;
                        xagioPromptModal("Confirm", "Redirect to URL (use the /newurl/ format) (DANGER: Creating invalid redirects may result in breaking of your website):", function (result) {
                            if (result) {
                                button.disable('Saving...');
                                $.post(xagio_data.wp_post, 'action=xagio_add_redirect&oldURL=' + encodeURIComponent(old_url) + '&newURL=' + encodeURIComponent(result), function (d) {
                                    button.disable();
                                    link.loadRedirects();

                                    xagioNotify("success", "New redirect created.");
                                });
                            }
                        });
                    }
                });

            });
        },
        editNewRedirect       : function () {
            $(document).on('click', '.edit-redirect', function (e) {
                e.preventDefault();

                var button = $(this);

                var coldURL = button.data('old-url');
                var cnewURL = button.data('new-url');

                var redirect_id = button.data('id');

                var oldURL = null;
                var newURL = null;

                xagioPromptModal("Confirm", `Editing Old URL: ${coldURL}`, function (result) {

                    if (result) {
                        let url = result;

                        if (url == '') {
                            oldURL = coldURL;
                        } else {
                            oldURL = url;
                        }

                        xagioPromptModal("Confirm", `Editing New URL: ${cnewURL}`, function (res) {
                            if (res) {
                                let url = res;

                                if (url == '') {
                                    newURL = cnewURL;
                                } else {
                                    newURL = url;
                                }

                                if (oldURL != null && newURL != null) {

                                    button.disable('Saving...');

                                    $.post(xagio_data.wp_post, 'action=xagio_edit_redirect&id=' + redirect_id + '&newURL=' + newURL + '&oldURL=' + oldURL, function (d) {
                                        button.disable();
                                        link.loadRedirects();

                                        xagioNotify("success", "Redirect updated.");
                                    });

                                }
                            }
                        });
                    }
                });
            });
        },
        uploadCSV             : function () {
            $(document).on('click', '#csv_file_modal', function () {
                $('#csv_modal')[0].showModal();
            });
            $(document).on('change', '#csv_file', function () {
                $('#confirmAddRedirects')[0].showModal();
            });

            $(document).on('click', '.confirm-add-redirects', function () {
                let btn = $(this);
                let modal = btn.parents('.xagio-modal');
                let modal_file = $('#csv_modal');
                let file = modal_file.find('#csv_file');
                file = file[0];

                if (file.files && file.files[0]) {

                    var extension = file.files[0].name.split('.').pop().toLowerCase();
                    if (extension != 'csv') {
                        xagioNotify("danger", "Please select a valid CSV file.");
                        return;
                    }

                    var myFile = file.files[0];
                    var reader = new FileReader();

                    reader.addEventListener('load', function (e) {
                        let csvdata = e.target.result;
                        csvdata     = csvdata.split("\n");
                        for (var i = 0; i < csvdata.length; i++) {
                            if (csvdata[i] != '') {
                                var line   = csvdata[i];
                                line       = line.split(",");
                                var oldURL = line[0];
                                var newURL = line[1];

                                $.postq("rqueue", xagio_data.wp_post, 'action=xagio_add_redirect&oldURL=' + oldURL + '&newURL=' + newURL, function (d) {
                                    xagioNotify("success", "Redirection added.");
                                });
                            }
                        }
                    });

                    reader.readAsBinaryString(myFile);

                    modal[0].close();
                    modal_file[0].close();
                }
            });
        },

        loadLog404       : function () {
            var linkIp   = "";
            var linkAgnt = "";
            logTable     = $('.logTable');
            logTable.show();
            logTable.dataTable({
                language        : {
                    search           : "_INPUT_",
                    searchPlaceholder: "Search 404 log...",
                    processing       : "Loading 404 log...",
                    emptyTable       : "Can\'t find any active logs."
                },
                "dom"           : '<"clear">rt<"xagio-table-bottom"lp><"clear">',
                "bDestroy"      : true,
                "bPaginate"     : true,
                "bAutoWidth"    : false,
                "bFilter"       : true,
                "bProcessing"   : true,
                "sServerMethod" : "POST",
                "bServerSide"   : true,
                "sAjaxSource"   : xagio_data.wp_post,
                "iDisplayLength": 10,
                "aLengthMenu"   : [
                    [
                        5,
                        10,
                        50,
                        100,
                        -1
                    ],
                    [
                        5,
                        10,
                        50,
                        100,
                        "All"
                    ]
                ],
                "aaSorting"     : [
                    [
                        3,
                        'desc'
                    ]
                ],
                "aoColumns"     : [
                    {
                        "sClass"     : "xagio-text-center",
                        "mData"      : "id",
                        "bSortable"  : false,
                        "bSearchable": false,
                        "mRender"    : function (data, type, row) {
                            return '<input type="checkbox" data-id="' + data + '" class="xagio-input-checkbox remove-selected-log-ids">';
                        }
                    },
                    {
                        "sClass"     : "column-hits xagio-text-center",
                        "bSortable"  : true,
                        "bSearchable": false,
                        "mData"      : "last_hit_counts",
                        "mRender"    : function (data, type, row) {
                            return data;
                        }
                    },
                    {
                        "sClass"     : "column-url",
                        "bSortable"  : true,
                        "bSearchable": true,
                        "mData"      : "url",
                        "mRender"    : function (data, type, row) {
                            return '<a href="'+data+'" target="_blank">' + data + '</a>';
                        }
                    },
                    {
                        "sClass"   : "column-last-hit xagio-text-center",
                        "bSortable": true,
                        "mData"    : "date_updated",
                        "mRender"  : function (data, type, row) {
                            return data;
                        }
                    },
                    {
                        "sClass"     : "column-ip xagio-text-center",
                        "bSortable"  : false,
                        "bSearchable": false,
                        "mData"      : "ip",
                        "mRender"    : function (data, type, row) {
                            var lengthIpCount = 0;
                            var ipAr          = jQuery.parseJSON(data);
                            linkIp            = " <span>" + ipAr.join("<br/>") + "</span>";
                            lengthIpCount     = ipAr.length;
                            var html          = lengthIpCount + ' <i title="View list of IPs" class="xagio-icon xagio-icon-list toggleIp tgl-btn-csr" ip-list="' + linkIp + '" log404s-toggle-id-ip="log404s-' + row.id + '-ip"></i>';

                            return html;
                        }
                    },
                    {
                        "sClass"     : "column-referers xagio-text-center",
                        "bSortable"  : true,
                        "bSearchable": false,
                        "mData"      : "reference",
                        "mRender"    : function (data, type, row) {
                            var linkRes        = '';
                            var lengthResCount = 0;
                            var referenceAr    = jQuery.parseJSON(data);
                            lengthResCount     = referenceAr.length;

                            $.each(referenceAr, function (index, value) {
                                linkRes += ' <a href="' + value + '" target="_blank" >' + value + '</a><br/>';
                            });

                            var html = lengthResCount + " <i title='View list of referring URLs' class='xagio-icon xagio-icon-list toggleRefer tgl-btn-csr' res-list=\'" + linkRes + "\' log404s-toggle-id-ref='log404s-" + row.id + "-referers'></i>";

                            return html;
                        }
                    },
                    {
                        "sClass"     : "column-agent xagio-text-center",
                        "bSortable"  : false,
                        "bSearchable": false,
                        "mData"      : "agent",
                        "mRender"    : function (data, type, row) {
                            var lengthAgntCount = 0;
                            var agentAr         = jQuery.parseJSON(data);
                            linkAgnt            = " <span>" + agentAr.join("<br/>") + "</span>";
                            lengthAgntCount     = agentAr.length;
                            var html            = lengthAgntCount + ' <i title="View list of user agents" class="xagio-icon xagio-icon-list toggleAgnt tgl-btn-csr" agnt-list="' + linkAgnt + '" log404s-toggle-id-agnt="log404s-' + row.id + '-agnts"></i>';

                            return html;
                        }
                    },
                    {
                        "sClass"     : "column-action xagio-text-center",
                        "bSortable"  : false,
                        "bSearchable": false,
                        "mData"      : "id",
                        "mRender"    : function (data, type, row) {
                            var html =
                                    '<div class="xagio-cell-actions-row xagio-flex-align-center"><a class="xagio-button xagio-button-primary xagio-button-mini add-new-404-redirect" data-current-url="' + row.slug + '" data-xagio-tooltip data-xagio-title="Add 301 Redirect"><i class="xagio-icon xagio-icon-plus"></i></a>' +
                                    '<a class="xagio-button xagio-button-primary xagio-button-mini open-404-redirect" target="_blank" href="' + row.url + '" data-xagio-tooltip data-xagio-title="Open URL in new window"><i class="xagio-icon xagio-icon-external-link"></i></a>' +
                                    '<button type="button" class="xagio-button xagio-button-danger xagio-button-mini delete-log404" data-id="' + row.id + '" data-xagio-tooltip data-xagio-title="Trash this log"><i class="xagio-icon xagio-icon-delete"></i></button></div>';

                            return html;
                        }
                    }
                ],
                "fnServerParams": function (aoData) {
                    aoData.push({
                        name : 'action',
                        value: 'xagio_get_log404s'
                    });
                },
                "fnCreatedRow"  : function (row, data, index) {
                },
                "fnInitComplete"  : function (settings, json) {
                    $('.total-number-of-logs').html(json.iTotalRecords);
                }
            });

        },
        toggleIp         : function () {
            /*$(document).on('click', '.toggleIp', function (e) {
                e.preventDefault();
                var toggleId = $(this).attr('log404s-toggle-id-ip');
                $('#'+toggleId).toggle(500);
            });*/

            $(document).on('click', '.toggleIp', function (e) {
                var tr       = $(this).closest('tr');
                var toggleId = $(this).attr('log404s-toggle-id-ip');
                var ipList   = $(this).attr('ip-list');
                var chkClsIp = $('.logTable tbody tr').hasClass('add-' + toggleId + '-list');
                let td = $(this).parents('td');
                if(td.hasClass('xagio-tr-opened')) {
                    td.removeClass('xagio-tr-opened');
                } else {
                    td.addClass('xagio-tr-opened');
                }
                if (chkClsIp === false) {

                    var html =
                            '<tr id="' + toggleId + '" class="add-' + toggleId + '-list"><td colspan="8">' +
                            '<div class="xagio-toogle-tr-row">' +
                            '<div><strong>IPs</strong></div>' +
                            '<ul><li>' + ipList + '</li></ul>' +
                            '</div>' +
                            '</td></tr>';

                    $(tr).after(html);

                } else {
                    $('#' + toggleId).toggle();
                }
            });

        },
        toggleReference  : function () {

            $(document).on('click', '.toggleRefer', function (e) {
                var tr        = $(this).closest('tr');
                var toggleId  = $(this).attr('log404s-toggle-id-ref');
                var resList   = $(this).attr('res-list');
                var chkClsRef = $('.logTable tbody tr').hasClass('add-' + toggleId + '-list');
                let td = $(this).parents('td');
                if(td.hasClass('xagio-tr-opened')) {
                    td.removeClass('xagio-tr-opened');
                } else {
                    td.addClass('xagio-tr-opened');
                }

                if (chkClsRef === false) {

                    var html =
                            '<tr id="' + toggleId + '" class="add-' + toggleId + '-list"><td colspan="8">' +
                            '<div class="xagio-toogle-tr-row">' +
                            '<div><strong>Referring URLs</strong></div>' +
                            '<ul><li>' + resList + '</li></ul>' +
                            '</div>' +
                            '</td></tr>';

                    $(tr).after(html);

                } else {
                    $('#' + toggleId).toggle();
                }
            });
        },
        toggleAgent      : function () {
            $(document).on('click', '.toggleAgnt', function (e) {
                var tr         = $(this).closest('tr');
                var toggleId   = $(this).attr('log404s-toggle-id-agnt');
                var agntList   = $(this).attr('agnt-list');
                var chkClsAgnt = $('.logTable tbody tr').hasClass('add-' + toggleId + '-list');
                let td = $(this).parents('td');
                if(td.hasClass('xagio-tr-opened')) {
                    td.removeClass('xagio-tr-opened');
                } else {
                    td.addClass('xagio-tr-opened');
                }

                if (chkClsAgnt === false) {

                    var html =
                            '<tr id="' + toggleId + '" class="add-' + toggleId + '-list"><td colspan="8">' +
                            '<div class="xagio-toogle-tr-row">' +
                            '<div><strong>User Agents</strong></div>' +
                            '<ul><li>' + agntList + '</li></ul>' +
                            '</div>' +
                            '</td></tr>';

                    $(tr).after(html);

                } else {
                    $('#' + toggleId).toggle();
                }
            });
        },
        selectAllLog404  : function () {
            $(document).on('click', '.select-all-log404', function (e) {
                $(".remove-selected-log-ids").each(function (i) {
                    var checked = $(this).prop("checked");
                    if (checked == true) {
                        $(this).prop("checked", false);
                        selected_logs = $.grep(selected_logs, (value) => value != $(this).data('id'));
                    } else {
                        selected_logs.push($(this).data('id'));
                        $(this).prop("checked", true);
                    }

                    selected_logs = $.unique(selected_logs);
                    if(selected_logs.length > 0) {
                        $('.selected-logs-count').html(selected_logs.length);
                        $('.remove-selected-log404').show();
                    } else {
                        $('.selected-logs-count').html('');
                        $('.remove-selected-log404').hide();
                    }
                });
            })
        },
        deleteLog404     : function () {
            $(document).on('click', '.delete-log404', function (e) {
                e.preventDefault();
                var button = $(this);
                var id     = $(this).data('id');

                xagioModal("Are you sure?", "Are you sure that you want to delete this log?", function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_delete_log404&id=' + id, function (d) {
                            button.disable();
                            link.loadLog404();

                            xagioNotify('success', 'Successfully deleted');
                        });
                    }
                });
            });

            $(document).on('click', '.remove-selected-log404', function (e) {
                e.preventDefault();
                var button = $(this);

                var ids = [];

                $('.remove-selected-log-ids').each(function () {
                    if (this.checked) {
                        ids.push($(this).data('id'));
                    }
                });

                xagioModal("Are you sure?", "Are you sure that you want to delete this log?", function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_delete_log404&id=' + ids, function (d) {
                            button.disable();
                            link.loadLog404();
                        });
                    }
                });
            });

            $(document).on('click', '.clear-log404', function (e) {
                e.preventDefault();
                var button = $(this);
                xagioModal("Are you sure?", "Are you sure that you want to clear logs?", function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_clear_log404', function (d) {
                            button.disable();
                            link.loadLog404();
                        });
                    }
                })
            })
        },
        addNew404Redirect: function () {
            $(document).on('click', '.add-new-404-redirect', function (e) {
                e.preventDefault();

                var button    = $(this);
                var old404URL = button.attr('data-current-url');

                xagioPromptModal("Redirect to URL", "Redirect to URL (use the /newurl/ format) (DANGER: Creating invalid redirects may result in breaking of your website):", function (result) {

                    if (result) {
                        let newURL = result;

                        button.disable();

                        $.post(xagio_data.wp_post, 'action=xagio_add_log404_redirect&old404URL=' + old404URL + '&newURL=' + newURL, function (d) {

                            button.disable();
                            link.loadRedirects();
                            if (d.status == 'success') {
                                xagioNotify("success", "Redirect successfully added in 301 redirects list.");
                            } else {
                                xagioNotify("danger", "404 URL not fetched! Please clear log or add this URL from 301 redirects.");
                            }
                        });
                    }
                });

            });
        },
        export404Log     : function () {
            $(document).on('click', '.export_404s_log', function () {
                window.location = xagio_data.wp_post + '?action=xagio_export_404s_log';
            })
        },
        LogSettings      : function () {
            $(document).on('click', '.xagio-slider-save-logs', function () {
                $('.frmLogSettings').submit();
            });


            $('.frmLogSettings').submit(function (e) {
                e.preventDefault();
                let logLmt = $('#XAGIO_MAX_LOG_LIMIT').val();

                if (!$.isNumeric(logLmt) || logLmt <= 0 || logLmt > 100000) {
                    xagioNotify("danger", "Please select correct max log limit.");
                    return;
                }

                var button = $(this).find('.btn-save-changes');
                button.disable('Loading ...');
                $.post(xagio_data.wp_post, $(this).serialize(), function (d) {
                    button.disable();

                    if (d.status === 'success') {
                        xagioNotify("success", "Operation completed.");
                    } else {
                        xagioNotify("danger", d.message);
                    }

                });
            });
        }

    };

})(jQuery);
