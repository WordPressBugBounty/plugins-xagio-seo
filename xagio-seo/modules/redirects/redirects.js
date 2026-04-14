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
let selected_refs = 0;

(function ($) {
    'use strict';

    $(document).ready(function () {

        $(document).on('change.uk.tab', function (e, active, prev) {
            if (typeof active != 'undefined') {
                let currentTabIndex = active.index();
                let settingsInfo = $('.log-info');
                settingsInfo.html(log_info[currentTabIndex]);
            }
        });

        link.refreshXags();
        link.loadRedirects();
        link.addNewRedirect();
        link.editRedirect();
        link.redirectNextStep();
        link.editRedirectNextStep();
        link.submitRedirect();
        link.submitEditUrl();
        link.redirectSelect();
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
        link.retrieveMetrics();

        $("#redirect_select").select2({
                                          placeholder   : "Select page/post",
                                          width         : '100%',
                                          dropdownParent: $('#addRedirectModal')
                                      });

        $("#edit_redirect_select").select2({
                                               placeholder   : "Select page/post",
                                               width         : '100%',
                                               dropdownParent: $('#editRedirectModal')
                                           });

        $("#editing-new-url").select2({
                                          placeholder   : "Select page/post",
                                          width         : '100%',
                                          dropdownParent: $('#redirectToModal')
                                      });

        $("#redirect-to-select").select2({
                                             placeholder   : "Select page/post",
                                             width         : '100%',
                                             dropdownParent: $('#addRedirectToModal')
                                         });

        $('#addRedirectModal').on('close', function () {
            $('.add-new-redirect').disable();
        })

        $(document).on('click', '.remove-selected-ids', function () {
            let checkbox = $(this);
            if (checkbox.prop('checked')) {
                selected_redirects.push(checkbox.data('id'));
            } else {
                selected_redirects = $.grep(selected_redirects, (value) => value != checkbox.data('id'));
            }
            selected_redirects = $.unique(selected_redirects);

            if (selected_redirects.length > 0) {
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
                selected_refs += checkbox.data('ref');
            } else {
                selected_logs = $.grep(selected_logs, (value) => value != checkbox.data('id'));
                selected_refs -= checkbox.data('ref');
            }
            selected_logs = $.unique(selected_logs);

            if (selected_logs.length > 0) {
                $('.selected-logs-count').html(selected_logs.length);
                $('.remove-selected-log404').show();
            } else {
                $('.selected-logs-count').html('');
                $('.remove-selected-log404').hide();
            }
            if (selected_refs > 0) {
                $('.selected-refs-count').html(selected_refs);
                $('.retrieve-metrics').show();
            } else {
                $('.selected-refs-count').html('');
                $('.retrieve-metrics').hide();
            }
        });

    });

    var link = {
        allowances                               : {
            cost          : []
        },

        loadRedirects: function () {
            let table = $('.table-redirects');
            table.show();

            table.dataTable({
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search redirects...",
                    processing: "Loading redirects...",
                    emptyTable: "Can't find any active redirects."
                },
                dom: '<"clear">rt<"xagio-table-bottom"lp><"clear">',
                bDestroy: true,
                bPaginate: true,
                bAutoWidth: false,
                bFilter: true,
                bProcessing: true,
                sServerMethod: "POST",
                bServerSide: true,
                sAjaxSource: xagio_data.wp_post,
                iDisplayLength: 10,
                aLengthMenu: [
                    [5, 10, 50, 100, -1],
                    [5, 10, 50, 100, "All"]
                ],
                aaSorting: [[3, 'desc']],
                aoColumns: [
                    {
                        sClass: "xagio-text-center",
                        mData: "id",
                        bSortable: false,
                        bSearchable: false,
                        mRender: function (data, type, row) {
                            return '<input type="checkbox" data-id="' + data +
                                '" class="xagio-input-checkbox remove-selected-ids">';
                        }
                    },
                    {
                        sClass: "column-old-url",
                        mData: "old",
                        bSortable: false,
                        bSearchable: true,
                        mRender: function (data, type, row) {
                            let label = row.old ? '/' + row.old : 'Homepage';
                            return '<a target="_blank" href="/' + row.old + '">' + label + '</a>';
                        }
                    },
                    {
                        sClass: "column-new-url",
                        mData: "new",
                        bSortable: false,
                        bSearchable: true,
                        mRender: function (data, type, row) {
                            let url = row.new.match(/^http/) ? row.new : '/' + row.new;
                            return '<a target="_blank" href="' + url + '">' + url + '</a>';
                        }
                    },
                    {
                        sClass: "column-date-created xagio-text-center",
                        mData: "date_created",
                        bSortable: true,
                        mRender: function (data) {
                            return data;
                        }
                    },
                    {
                        sClass: "column-action xagio-text-center",
                        mData: "id",
                        bSortable: false,
                        bSearchable: false,
                        mRender: function (data, type, row) {
                            return '<div class="xagio-cell-actions-row xagio-flex-align-center">' +
                                '<button type="button" class="xagio-button xagio-button-primary xagio-button-mini edit-redirect" data-id="' +
                                row.id + '" data-old-url="' + row.old + '" data-new-url="' + row.new +
                                '" data-xagio-tooltip data-xagio-title="Edit this redirect"><i class="xagio-icon xagio-icon-edit"></i></button>' +
                                '<button type="button" class="xagio-button xagio-button-danger xagio-button-mini delete-redirect" data-id="' +
                                row.id + '" data-xagio-tooltip data-xagio-title="Trash this redirect"><i class="xagio-icon xagio-icon-delete"></i></button>' +
                                `<div class="xagio-slider-container">
                            <input type="hidden" name="toggle-redirect-${row.id}" id="toggle-redirect-${row.id}" value="${row.is_redirect_active}" />
                            <div class="xagio-slider-frame">
                                <span class="xagio-slider-button toggle-redirect ${(row.is_redirect_active === "1") ? 'on' : ''}" data-element="toggle-redirect-${row.id}" data-id="${row.id}"></span>
                            </div>
                        </div>` +
                                '</div>';
                        }
                    }
                ],
                fnServerParams: function (aoData) {
                    aoData.push({
                        name: 'action',
                        value: 'xagio_get_redirects'
                    });
                },
                fnInitComplete: function (settings, json) {
                    $('.total-number-of-redirects').html(json.iTotalRecords);
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
                if (selected_redirects.length > 0) {
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
                var id = $(this).data('id');

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
                            button.disable().hide();
                            link.loadRedirects();

                            // clear selected redirects array
                            selected_redirects = [];

                            xagioNotify(d.status, d.message);
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

                            xagioNotify(d.status, d.message);
                        });
                    }
                });
            })
        },
        customRedirectSettings: function () {
            $(document).on('click', '.toggle-redirect', function (e) {
                var button = $(this);
                var id = $(this).data('id');
                let checked = $(this).prop('checked');
                let input = button.parents('.xagio-slider-container').find(`#toggle-redirect-${id}`).val();

                $.post(xagio_data.wp_post, 'action=xagio_toggle_redirect&id=' + id + '&value=' + input, function (d) {
                    xagioNotify("success", "Setting updated.");
                });

            });
        },
        addNewRedirect        : function () {
            $(document).on('click', '.add-new-redirect', function (e) {
                e.preventDefault();

                let button = $(this);
                button.disable('Saving...');
                $("#addRedirectModal")[0].showModal();
            });
        },
        editRedirect          : function () {
            $(document).on('click', '.edit-redirect', function (e) {
                e.preventDefault();

                let button = $(this);

                let coldURL = button.data('old-url');
                let cnewURL = button.data('new-url');
                let redirect_id = button.data('id');

                let modal = $("#editRedirectModal");

                modal.find('.xagio-modal-title span').text(coldURL);
                modal.find('#edit_redirect_select').val(coldURL).trigger('change');
                modal.find('#edit_old_url').val(coldURL);

                modal[0].showModal();

                $('.edit-redirect-next-step').attr('data-old-url', coldURL).attr('data-new-url', cnewURL).attr('data-id', redirect_id);
            });
        },
        redirectSelect        : function () {
            $(document).on('change', '#redirect_select', function (e) {
                let value = $(this).val();

                $("#addRedirectModal").find('#old_url').val(`/${value}/`);
            })

            $(document).on('change', '#edit_redirect_select', function (e) {
                let value = $(this).val();

                $("#editRedirectModal").find('#edit_old_url').val(value);
            })

            $(document).on('change', '#editing-new-url', function (e) {
                let value = $(this).val();

                $("#redirectToModal").find('#edit_new_url').val(value);
            })

            $(document).on('change', '#redirect-to-select', function (e) {
                let value = $(this).val();

                $("#addRedirectToModal").find('#redirect-to-input').val(`/${value}/`);
            })
        },
        redirectNextStep      : function () {
            $(document).on('click', '.redirect-next-step', function () {
                let select = $('#redirect_select');
                let modal = $("#addRedirectModal");
                let old_url = modal.find('#old_url').val();

                if (old_url === '') {
                    xagioNotify('danger', 'Input field cannot be empty. Please select a page/post or enter a URL manually!');
                    return;
                }

                modal[0].close();
                select.val(null).trigger('change');
                $('#old_url').val('');

                let secondModal = $("#addRedirectToModal");
                secondModal.find('.submit-redirect').attr('data-old-url', old_url);

                //open second modal
                secondModal[0].showModal();
            });
        },

        submitRedirect: function () {
            $(document).on("click", ".submit-redirect", function () {
                let button = $(this);
                let select = $('#redirect-to-select');
                let modal = $("#addRedirectToModal");
                let input = modal.find('#redirect-to-input').val();
                let oldURL = button.attr('data-old-url');

                if (input === '') {
                    xagioNotify('danger', 'Input field cannot be empty. Please select a page/post or enter a URL manually!');
                    return;
                }

                button.disable("Saving...");

                $.post(xagio_data.wp_post, 'action=xagio_add_redirect&oldURL=' + encodeURIComponent(oldURL) +
                                           '&newURL=' + encodeURIComponent(input), function (d) {
                    link.loadRedirects();
                    button.disable();

                    select.val(null).trigger('change');
                    modal.find('#redirect-to-input').val('');

                    modal[0].close();
                    xagioNotify("success", "Redirect updated.");
                });
            });
        },

        editRedirectNextStep: function () {
            $(document).on('click', '.edit-redirect-next-step', function (e) {
                e.preventDefault();

                let modal = $("#editRedirectModal");
                //first modal input
                let input = modal.find('#edit_old_url').val();

                if (input === '') {
                    xagioNotify('danger', 'Input field cannot be empty. Please select a page/post or enter a URL manually!');
                    return;
                }

                let button = $(this);
                let cnewURL = button.attr('data-new-url').replace(/^\/|\/$/g, "");
                let redirect_id = button.data('id');

                modal[0].close();

                let secondModal = $("#redirectToModal");
                secondModal.find('.xagio-modal-title span').text(cnewURL);
                secondModal.find('#editing-new-url').val(cnewURL).trigger('change');
                secondModal.find('#edit_new_url').val(cnewURL);
                secondModal.find('.submit-edit-url').attr('data-old-url', input).attr('data-id', redirect_id);

                //open second modal
                secondModal[0].showModal();
            });
        },

        submitEditUrl: function () {
            $(document).on('click', '.submit-edit-url', function () {
                let button = $(this);
                let modal = $("#redirectToModal");
                let newURL = modal.find('#edit_new_url').val();
                let redirect_id = button.attr('data-id');
                let oldURL = button.attr('data-old-url');

                if (newURL === '') {
                    xagioNotify('danger', 'Input field cannot be empty. Please select a page/post or enter a URL manually!');
                    return;
                }

                button.disable("Saving...");

                $.post(xagio_data.wp_post, 'action=xagio_edit_redirect&id=' + redirect_id + '&newURL=' + newURL +
                                           '&oldURL=' + oldURL, function () {
                    link.loadRedirects();
                    button.disable();

                    modal[0].close();
                    xagioNotify("success", "Redirect updated.");
                });
            });
        },
        uploadCSV    : function () {
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
                        csvdata = csvdata.split("\n");
                        for (var i = 0; i < csvdata.length; i++) {
                            if (csvdata[i] != '') {
                                var line = csvdata[i];
                                line = line.split(",");
                                var oldURL = line[0];
                                var newURL = line[1];

                                $.postq("rqueue", xagio_data.wp_post, 'action=xagio_add_redirect&oldURL=' + oldURL +
                                                                      '&newURL=' + newURL, function (d) {
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
            var linkIp = "";
            var linkAgnt = "";
            logTable = $('.logTable');
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
                                            let reference = row.reference;
                                            let refCount = 0;
                                            let referenceAr = jQuery.parseJSON(reference);
                                            refCount = referenceAr.length;
                                               return '<input type="checkbox" data-id="' + data +
                                                      '" data-ref="'+ refCount +'" class="xagio-input-checkbox remove-selected-log-ids">';
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
                                           "mData"      : null,
                                           "render"     : function (data, type, row) {
                                               // row.url_href & row.url_text come from the escaped PHP above
                                               return '<a href="' + row.url_href + '" target="_blank" rel="noopener">' +
                                                      row.url_text +
                                                      '</a>';
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
                                               var ipAr = jQuery.parseJSON(data);
                                               linkIp = " <span>" + ipAr.join("<br/>") + "</span>";
                                               lengthIpCount = ipAr.length;
                                               var html = lengthIpCount +
                                                          ' <i title="View list of IPs" class="xagio-icon xagio-icon-list toggleIp tgl-btn-csr" ip-list="' +
                                                          linkIp + '" log404s-toggle-id-ip="log404s-' + row.id +
                                                          '-ip"></i>';

                                               return html;
                                           }
                                       },
                                       {
                                           "sClass"     : "column-referers xagio-text-center",
                                           "bSortable"  : true,
                                           "bSearchable": false,
                                           "mData"      : "reference",
                                           "mRender"    : function (data, type, row) {
                                               var lengthResCount = 0;
                                               var referenceAr = jQuery.parseJSON(data);
                                               lengthResCount = referenceAr.length;
                                               return lengthResCount +
                                                          " <i title='View list of referring URLs' class='xagio-icon xagio-icon-list toggleRefer tgl-btn-csr' res-list=\'" +
                                                          btoa(data) + "\' log404s-toggle-id-ref='log404s-" +
                                                          row.id + "-referers'></i>";
                                           }
                                       },
                                       {
                                           "sClass"     : "column-agent xagio-text-center",
                                           "bSortable"  : false,
                                           "bSearchable": false,
                                           "mData"      : "agent",
                                           "mRender"    : function (data, type, row) {
                                               var lengthAgntCount = 0;
                                               var agentAr = jQuery.parseJSON(data);
                                               linkAgnt = " <span>" + agentAr.join("<br/>") + "</span>";
                                               lengthAgntCount = agentAr.length;
                                               var html = lengthAgntCount +
                                                          ' <i title="View list of user agents" class="xagio-icon xagio-icon-list toggleAgnt tgl-btn-csr" agnt-list="' +
                                                          linkAgnt + '" log404s-toggle-id-agnt="log404s-' + row.id +
                                                          '-agnts"></i>';

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
                                                       '<div class="xagio-cell-actions-row xagio-flex-align-center"><a class="xagio-button xagio-button-primary xagio-button-mini add-new-404-redirect" data-current-url="' +
                                                       row.slug +
                                                       '" data-xagio-tooltip data-xagio-title="Add 301 Redirect"><i class="xagio-icon xagio-icon-plus"></i></a>' +
                                                       '<a class="xagio-button xagio-button-primary xagio-button-mini open-404-redirect" target="_blank" href="' +
                                                       row.url +
                                                       '" data-xagio-tooltip data-xagio-title="Open URL in new window"><i class="xagio-icon xagio-icon-external-link"></i></a>' +
                                                       '<button type="button" class="xagio-button xagio-button-danger xagio-button-mini delete-log404" data-id="' +
                                                       row.id +
                                                       '" data-xagio-tooltip data-xagio-title="Trash this log"><i class="xagio-icon xagio-icon-delete"></i></button></div>';

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
                                   "fnInitComplete": function (settings, json) {
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
                var tr = $(this).closest('tr');
                var toggleId = $(this).attr('log404s-toggle-id-ip');
                var ipList = $(this).attr('ip-list');
                var chkClsIp = $('.logTable tbody tr').hasClass('add-' + toggleId + '-list');
                let td = $(this).parents('td');
                if (td.hasClass('xagio-tr-opened')) {
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
                var tr = $(this).closest('tr');
                var toggleId = $(this).attr('log404s-toggle-id-ref');
                var resList = atob($(this).attr('res-list'));
                var refList = jQuery.parseJSON(resList);
                var chkClsRef = $('.logTable tbody tr').hasClass('add-' + toggleId + '-list');
                let td = $(this).parents('td');
                if (td.hasClass('xagio-tr-opened')) {
                    td.removeClass('xagio-tr-opened');
                } else {
                    td.addClass('xagio-tr-opened');
                }

                let list = '';
                $.each(refList, function (index, value) {
                    let dr = value.DR == null ? '' : value.DR;
                    let ur = value.UR == null ? '': value.UR;
                    list += '<tr><td>' + $('<a>', { target: '_blank', rel: 'noopener', href: value.reference }).text(value.reference)[0].outerHTML + '</td>' + '<td>' + dr + '</td>' + '<td>' + ur + '</td></tr>';
                });
                
                if (chkClsRef === false) {

                    var html =
                            '<tr id="' + toggleId + '" class="add-' + toggleId + '-list"><td colspan="8">' +
                            '<div class="xagio-toogle-tr-row">' +
                            '<div><strong>Referring URLs</strong></div>' +
                            '<table class="xagio-toggle-ref-table"><tr><td>URL</td><td>DR</td><td>UR</td></tr>' + list +'</table>'
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
                var tr = $(this).closest('tr');
                var toggleId = $(this).attr('log404s-toggle-id-agnt');
                var agntList = $(this).attr('agnt-list');
                var chkClsAgnt = $('.logTable tbody tr').hasClass('add-' + toggleId + '-list');
                let td = $(this).parents('td');
                if (td.hasClass('xagio-tr-opened')) {
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
                        selected_refs -= $(this).data('ref');
                    } else {
                        selected_logs.push($(this).data('id'));
                        selected_refs += $(this).data('ref');
                        $(this).prop("checked", true);
                    }

                    selected_logs = $.unique(selected_logs);
                    if (selected_logs.length > 0) {
                        $('.selected-logs-count').html(selected_logs.length);
                        $('.remove-selected-log404').show();
                    } else {
                        $('.selected-logs-count').html('');
                        $('.remove-selected-log404').hide();
                    }

                    if (selected_refs > 0) {
                        $('.selected-refs-count').html(selected_refs);
                        $('.retrieve-metrics').show();
                    } else {
                        $('.selected-refs-count').html('');
                        $('.retrieve-metrics').hide();
                    }
                    
                });
            })
        },
        deleteLog404     : function () {
            $(document).on('click', '.delete-log404', function (e) {
                e.preventDefault();
                var button = $(this);
                var id = $(this).data('id');

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

                var button = $(this);
                var old404URL = button.attr('data-current-url');

                xagioPromptModal("Redirect to URL", "Redirect to URL (use the /newurl/ format) (DANGER: Creating invalid redirects may result in breaking of your website):", function (result) {

                    if (result) {
                        let newURL = result;

                        button.disable();

                        $.post(xagio_data.wp_post, 'action=xagio_add_log404_redirect&old404URL=' + old404URL +
                                                   '&newURL=' + newURL, function (d) {

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
                let exportUrl = xagio_data.wp_post + '?action=xagio_export_404s_log' + '&_xagio_nonce=' + xagio_data.nonce;
                window.location = exportUrl;
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
                        link.loadLog404();
                        xagioNotify("success", "Operation completed.");
                    } else {
                        xagioNotify("danger", d.message);
                    }

                });
            });
        },

        refreshXags: function () {
            $.post(xagio_data.wp_post, 'action=xagio_refreshXags', function (d) {
                if (d.status == 'success') {
                    link.allowances.cost = d.data.xags_cost;
                    link.allowances.xags_total = d.data.xags_total;
                    link.allowances.xags_sum = d.data.xags + d.data.xags_allowance;
                }
            });
        },

        retrieveMetrics   : function () {
            $(document).on('click', '.retrieve-metrics', function(e) {
                e.preventDefault();
                var button = $(this);

                var ids = [];

                $('.remove-selected-log-ids').each(function () {
                    if (this.checked) {
                        ids.push($(this).data('id'));
                    }
                });

                let balance = link.allowances.xags_sum;
                let xag_price = link.allowances.cost.metrics * selected_refs * 2;
                
                if(xag_price > balance) {
                    xagioNotify("warning", "You do not have enough XAGS to perform this operation!");
                    return;
                }
                
                xagioModal("Are you sure?", `This action will consume ${xag_price} XAGS`, function (yes) {
                    if (yes) {
                        button.disable();
                        $.post(xagio_data.wp_post, 'action=xagio_retrieve_metrics&ids=' + ids, function (d) {
                            button.disable();
                            link.loadLog404();
                            selected_logs = [];
                            selected_refs = 0;
                            $('.retrieve-metrics').hide();
                            $('.remove-selected-log404').hide();
                        });
                    }
                });
            })

        }

    };

})(jQuery);
