var rTable;
var jsonLoaded    = false;
var pTable;
var selectedPosts = [];

Array.prototype.remove = function (data) {
    const dataIdx = this.indexOf(data);
    if (dataIdx >= 0) {
        this.splice(dataIdx, 1);
    }
    return this.length;
};

(function ($) {

    'use strict';

    $(document).ready(function () {
        render.init();
        colors.init();
        display.init();
        handlers.init();
        load();
        save();
        approve();
        unapprove();
        remove();
        edit_review();
        add_review();
        clone();
        postsTable();
        postsCloneTable();
        handlers.placeholder_change();
        movePostToPage();

    });

    var add_review = function () {
        $(document).on('click', '.add_review', function (e) {
            e.preventDefault();
            let modal = $('#edit_review');
            var form = $('.edit_review_submit');
            modal.find('.xagio-modal-title').html('<i class="xagio-icon xagio-icon-plus"></i> Add New Review')
            form[0].reset();
            $('.review-id').val(0);
            $('#review-date').val(new Date().toDateString());

            modal[0].showModal();
        });
    };

    var edit_review = function () {
        $(document).on('click', '.edit_review', function (e) {
            e.preventDefault();
            let id = $(this).parents('tr').find('.select-review').val();
            let modal = $('#edit_review');

            modal.find('.xagio-modal-title').html('<i class="xagio-icon xagio-icon-edit"></i> Edit Review');

            $.post(xagio_data.wp_post, 'action=xagio_getReview&id=' + id, function (d) {

                for (let key in d) {
                    let value = d[key];
                    if (value == 0) value = '';
                    $('#review-' + key).val(value);
                }

                modal[0].showModal();
            });
        });
        $(document).on('submit', '.edit_review_submit', function (e) {
            e.preventDefault();

            $.post(xagio_data.wp_post, $(this).serialize(), function (d) {

                if (d.status === 'error') {
                    xagioNotify("danger", d.message);
                } else {
                    xagioNotify("success", d.message);
                    $('#edit_review')[0].close();
                    rTable.fnDraw();
                }

            });

        });
    };

    var gravatar_get = function (email, size) {

        // MD5 (Message-Digest Algorithm) by WebToolkit
        //

        var MD5 = function (s) {
            function L(k, d) {
                return (k << d) | (k >>> (32 - d))
            }

            function K(G, k) {
                var I,
                    d,
                    F,
                    H,
                    x;
                F = (G & 2147483648);
                H = (k & 2147483648);
                I = (G & 1073741824);
                d = (k & 1073741824);
                x = (G & 1073741823) + (k & 1073741823);
                if (I & d) {
                    return (x ^ 2147483648 ^ F ^ H)
                }
                if (I | d) {
                    if (x & 1073741824) {
                        return (x ^ 3221225472 ^ F ^ H)
                    } else {
                        return (x ^ 1073741824 ^ F ^ H)
                    }
                } else {
                    return (x ^ F ^ H)
                }
            }

            function r(d, F, k) {
                return (d & F) | ((~d) & k)
            }

            function q(d, F, k) {
                return (d & k) | (F & (~k))
            }

            function p(d, F, k) {
                return (d ^ F ^ k)
            }

            function n(d, F, k) {
                return (F ^ (d | (~k)))
            }

            function u(G, F, aa, Z, k, H, I) {
                G = K(G, K(K(r(F, aa, Z), k), I));
                return K(L(G, H), F)
            }

            function f(G, F, aa, Z, k, H, I) {
                G = K(G, K(K(q(F, aa, Z), k), I));
                return K(L(G, H), F)
            }

            function D(G, F, aa, Z, k, H, I) {
                G = K(G, K(K(p(F, aa, Z), k), I));
                return K(L(G, H), F)
            }

            function t(G, F, aa, Z, k, H, I) {
                G = K(G, K(K(n(F, aa, Z), k), I));
                return K(L(G, H), F)
            }

            function e(G) {
                var Z;
                var F  = G.length;
                var x  = F + 8;
                var k  = (x - (x % 64)) / 64;
                var I  = (k + 1) * 16;
                var aa = Array(I - 1);
                var d  = 0;
                var H  = 0;
                while (H < F) {
                    Z     = (H - (H % 4)) / 4;
                    d     = (H % 4) * 8;
                    aa[Z] = (aa[Z] | (G.charCodeAt(H) << d));
                    H++
                }
                Z         = (H - (H % 4)) / 4;
                d         = (H % 4) * 8;
                aa[Z]     = aa[Z] | (128 << d);
                aa[I - 2] = F << 3;
                aa[I - 1] = F >>> 29;
                return aa
            }

            function B(x) {
                var k = "",
                    F = "",
                    G,
                    d;
                for (d = 0; d <= 3; d++) {
                    G = (x >>> (d * 8)) & 255;
                    F = "0" + G.toString(16);
                    k = k + F.substr(F.length - 2, 2)
                }
                return k
            }

            function J(k) {
                k     = k.replace(/rn/g, "n");
                var d = "";
                for (var F = 0; F < k.length; F++) {
                    var x = k.charCodeAt(F);
                    if (x < 128) {
                        d += String.fromCharCode(x)
                    } else {
                        if ((x > 127) && (x < 2048)) {
                            d += String.fromCharCode((x >> 6) | 192);
                            d += String.fromCharCode((x & 63) | 128)
                        } else {
                            d += String.fromCharCode((x >> 12) | 224);
                            d += String.fromCharCode(((x >> 6) & 63) | 128);
                            d += String.fromCharCode((x & 63) | 128)
                        }
                    }
                }
                return d
            }

            var C = Array();
            var P,
                h,
                E,
                v,
                g,
                Y,
                X,
                W,
                V;
            var S = 7,
                Q = 12,
                N = 17,
                M = 22;
            var A = 5,
                z = 9,
                y = 14,
                w = 20;
            var o = 4,
                m = 11,
                l = 16,
                j = 23;
            var U = 6,
                T = 10,
                R = 15,
                O = 21;
            s = J(s);
            C = e(s);
            Y = 1732584193;
            X = 4023233417;
            W = 2562383102;
            V = 271733878;
            for (P = 0; P < C.length; P += 16) {
                h = Y;
                E = X;
                v = W;
                g = V;
                Y = u(Y, X, W, V, C[P + 0], S, 3614090360);
                V = u(V, Y, X, W, C[P + 1], Q, 3905402710);
                W = u(W, V, Y, X, C[P + 2], N, 606105819);
                X = u(X, W, V, Y, C[P + 3], M, 3250441966);
                Y = u(Y, X, W, V, C[P + 4], S, 4118548399);
                V = u(V, Y, X, W, C[P + 5], Q, 1200080426);
                W = u(W, V, Y, X, C[P + 6], N, 2821735955);
                X = u(X, W, V, Y, C[P + 7], M, 4249261313);
                Y = u(Y, X, W, V, C[P + 8], S, 1770035416);
                V = u(V, Y, X, W, C[P + 9], Q, 2336552879);
                W = u(W, V, Y, X, C[P + 10], N, 4294925233);
                X = u(X, W, V, Y, C[P + 11], M, 2304563134);
                Y = u(Y, X, W, V, C[P + 12], S, 1804603682);
                V = u(V, Y, X, W, C[P + 13], Q, 4254626195);
                W = u(W, V, Y, X, C[P + 14], N, 2792965006);
                X = u(X, W, V, Y, C[P + 15], M, 1236535329);
                Y = f(Y, X, W, V, C[P + 1], A, 4129170786);
                V = f(V, Y, X, W, C[P + 6], z, 3225465664);
                W = f(W, V, Y, X, C[P + 11], y, 643717713);
                X = f(X, W, V, Y, C[P + 0], w, 3921069994);
                Y = f(Y, X, W, V, C[P + 5], A, 3593408605);
                V = f(V, Y, X, W, C[P + 10], z, 38016083);
                W = f(W, V, Y, X, C[P + 15], y, 3634488961);
                X = f(X, W, V, Y, C[P + 4], w, 3889429448);
                Y = f(Y, X, W, V, C[P + 9], A, 568446438);
                V = f(V, Y, X, W, C[P + 14], z, 3275163606);
                W = f(W, V, Y, X, C[P + 3], y, 4107603335);
                X = f(X, W, V, Y, C[P + 8], w, 1163531501);
                Y = f(Y, X, W, V, C[P + 13], A, 2850285829);
                V = f(V, Y, X, W, C[P + 2], z, 4243563512);
                W = f(W, V, Y, X, C[P + 7], y, 1735328473);
                X = f(X, W, V, Y, C[P + 12], w, 2368359562);
                Y = D(Y, X, W, V, C[P + 5], o, 4294588738);
                V = D(V, Y, X, W, C[P + 8], m, 2272392833);
                W = D(W, V, Y, X, C[P + 11], l, 1839030562);
                X = D(X, W, V, Y, C[P + 14], j, 4259657740);
                Y = D(Y, X, W, V, C[P + 1], o, 2763975236);
                V = D(V, Y, X, W, C[P + 4], m, 1272893353);
                W = D(W, V, Y, X, C[P + 7], l, 4139469664);
                X = D(X, W, V, Y, C[P + 10], j, 3200236656);
                Y = D(Y, X, W, V, C[P + 13], o, 681279174);
                V = D(V, Y, X, W, C[P + 0], m, 3936430074);
                W = D(W, V, Y, X, C[P + 3], l, 3572445317);
                X = D(X, W, V, Y, C[P + 6], j, 76029189);
                Y = D(Y, X, W, V, C[P + 9], o, 3654602809);
                V = D(V, Y, X, W, C[P + 12], m, 3873151461);
                W = D(W, V, Y, X, C[P + 15], l, 530742520);
                X = D(X, W, V, Y, C[P + 2], j, 3299628645);
                Y = t(Y, X, W, V, C[P + 0], U, 4096336452);
                V = t(V, Y, X, W, C[P + 7], T, 1126891415);
                W = t(W, V, Y, X, C[P + 14], R, 2878612391);
                X = t(X, W, V, Y, C[P + 5], O, 4237533241);
                Y = t(Y, X, W, V, C[P + 12], U, 1700485571);
                V = t(V, Y, X, W, C[P + 3], T, 2399980690);
                W = t(W, V, Y, X, C[P + 10], R, 4293915773);
                X = t(X, W, V, Y, C[P + 1], O, 2240044497);
                Y = t(Y, X, W, V, C[P + 8], U, 1873313359);
                V = t(V, Y, X, W, C[P + 15], T, 4264355552);
                W = t(W, V, Y, X, C[P + 6], R, 2734768916);
                X = t(X, W, V, Y, C[P + 13], O, 1309151649);
                Y = t(Y, X, W, V, C[P + 4], U, 4149444226);
                V = t(V, Y, X, W, C[P + 11], T, 3174756917);
                W = t(W, V, Y, X, C[P + 2], R, 718787259);
                X = t(X, W, V, Y, C[P + 9], O, 3951481745);
                Y = K(Y, h);
                X = K(X, E);
                W = K(W, v);
                V = K(V, g)
            }
            var i = B(Y) + B(X) + B(W) + B(V);
            return i.toLowerCase()
        };

        var size = size || 32;

        return '//www.gravatar.com/avatar/' + MD5(email) + '.jpg?s=' + size + '&d=identicon';
    };

    var save = function () {
        $('.save-review-widget').submit(function (e) {
            e.preventDefault();
            var btn  = $('.uk-button-save-review-design');
            var data = $(this).serialize();
            btn.disable('Saving ...');
            $.post(xagio_data.wp_post, data, function (d) {
                btn.disable();
                if (d.status === 'error') {
                    xagioNotify("danger", d.message);
                } else {
                    xagioNotify("success", d.message);
                }
            });
        });
    };

    var movePostToPage = function () {
        $(document).on('click', '.select_post', function () {

            var review_ids = $('#selectedReviews').val();
            var post_id    = $(this).data('post-id');


            if (review_ids === '') {
                xagioNotify("danger", 'Please select reviews that you want to move!');
            }

            if (post_id === '') {
                xagioNotify("danger", 'Please select page!');
            }

            var data = {
                action : 'xagio_bulkReviews',
                type   : 'move',
                ids    : review_ids,
                post_id: post_id
            };

            $.post(xagio_data.wp_post, data, function (d) {
                xagioNotify("success", "Action successfully performed!");
                $('#availablePagesModal')[0].close();
                load();

            });
        });

        $('#availablePagesModal')[0].addEventListener("close", (event) => {
            $(this).find('#selectedReviews').val('');
            $('#ReviewBulkActions').val('');
        });

        $('#availablePagesCloneModal')[0].addEventListener("close", (event) => {
            selectedPosts = [];
            $('#selectedReviewId').val('');
        });


        $(document).on('change', '#PostsType2', function (e) {
            pTable.fnDraw();
        });
        $(document).on('change', '#PostsTypeClone', function (e) {
            postsCloneTable.fnDraw();
        });
    };

    var postsTable = function () {
        $(document).on('change', '.select-posts-all', function () {

            let checked = $(this).is(':checked');

            $('.postsCloneTable').find('.select-post').each(function () {
                $(this).prop('checked', checked);
                $(this).trigger('change');
            });

        });

        $(document).on('change', '.select-post', function () {
            let checked = $(this).is(':checked');
            if (checked) {
                selectedPosts.push($(this).val());
            } else {
                selectedPosts.remove($(this).val());
            }
        });
        pTable = $('.postsTable2').dataTable({
            language        : {
                search           : "_INPUT_",
                searchPlaceholder: "Search posts...",
                processing       : "Loading Posts...",
                emptyTable       : "No posts found on this website.",
                info             : "_START_ to _END_ of _TOTAL_ results",
                infoEmpty        : "0 to 0 of 0 results",
                infoFiltered     : "(from _MAX_ total results)",
            },
            "dom"           : '<"posts-actions2"<"uk-float-right"fl>>rt<"posts-actions-bottom2"ip<"uk-clearfix">>',
            "bDestroy"      : true,
            "searchDelay"   : 350,
            "bPaginate"     : true,
            "bAutoWidth"    : false,
            "bFilter"       : true,
            "bProcessing"   : true,
            "sServerMethod" : "POST",
            "bServerSide"   : true,
            "sAjaxSource"   : xagio_data.wp_post,
            "iDisplayLength": 50,
            "aLengthMenu"   : [
                [
                    5,
                    10,
                    50,
                    100
                ],
                [
                    5,
                    10,
                    50,
                    100
                ]
            ],
            "aaSorting"     : [
                [
                    1,
                    'desc'
                ]
            ],
            "aoColumns"     : [
                {
                    "bSortable": true,
                    "mRender"  : function (data, type, row) {
                        return '<button type="button" data-post-id="' + row.ID + '" class="uk-button uk-button-primary uk-button-mini select_post"><i class="xagio-icon xagio-icon-check"></i> Select</button>';
                    },
                    "asSorting": [
                        "desc",
                        "asc"
                    ]
                },
                {
                    "sClass"   : "text-left",
                    "bSortable": true,
                    "mData"    : 'post_title',
                    "mRender"  : function (data, type, row) {
                        return "<b class='post-title'>" + data + "</b>"
                               + "<div class='row-actions'>"

                               + "<a href='" + xagio_data.wp_admin + 'post.php?post=' + row.ID + '&action=edit' + "' target='_blank' class='edit'>Edit</a>"

                               + " <span>|</span> "

                               + "<a href='" + row.guid + "' target='_blank' class='view'>View</a>"
                               + "</div>";
                    },
                    "asSorting": [
                        "desc",
                        "asc"
                    ]
                },
                {
                    "bSortable": true,
                    "mData"    : 'post_date',
                    "mRender"  : function (data, type, row) {
                        return '<b>' + row.post_status.charAt(0).toUpperCase() + row.post_status.slice(1) + 'ed</b>'
                               + '<br>'
                               + '<abbr title="' + data + '">' + new Date(data).toUTCString().split(' ').splice(0, 4).join(' ') + '</abbr>';
                    },
                    "asSorting": [
                        "desc",
                        "asc"
                    ]
                }
            ],
            "fnServerParams": function (aoData) {

                aoData.push({
                    name : 'action',
                    value: 'xagio_get_posts'
                });

                if ($('#PostsType2').length > 0) {
                    aoData.push({
                        name : 'PostsType',
                        value: $('#PostsType2').val()
                    });
                }
            },

            fnInitComplete: function () {
                $('.posts-actions2').append(
                    '<div class="uk-float-left">' +
                    '<select class=" xagio-input-select xagio-input-select-gray" id="PostsType2">' +
                    '<option value="">Post Type</option>' +
                    '<option value="post">Posts</option>' +
                    '<option value="page">Pages</option>' +
                    '</select>' +
                    '</div>' +
                    '<div class="uk-clearfix"></div>'
                );
            }

        });
    };

    var postsCloneTable = function () {
        $('.postsCloneTable').dataTable({
            language        : {
                search           : "_INPUT_",
                searchPlaceholder: "Search posts...",
                processing       : "Loading Posts...",
                emptyTable       : "No posts found on this website.",
                info             : "_START_ to _END_ of _TOTAL_ results",
                infoEmpty        : "0 to 0 of 0 results",
                infoFiltered     : "(from _MAX_ total results)",
            },
            "dom"           : '<"posts-actions-clone"<"uk-float-right"fl>>rt<"posts-actions-bottom2"ip<"uk-clearfix">>',
            "bDestroy"      : true,
            "searchDelay"   : 350,
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
                    100
                ],
                [
                    5,
                    10,
                    50,
                    100
                ]
            ],
            "aaSorting"     : [
                [
                    1,
                    'desc'
                ]
            ],
            "aoColumns"     : [
                {
                    "sClass"     : "text-left",
                    "bSortable"  : false,
                    "bSearchable": false,
                    "mRender"    : function (data, type, row) {
                        let checked = '';

                        if ($.inArray(row.ID, selectedPosts) !== -1) {
                            checked = 'checked';
                        }

                        return '<input ' + checked + ' class="select-post" type="checkbox" value="' + row.ID + '">';
                    }
                },
                {
                    "sClass"   : "text-left",
                    "bSortable": true,
                    "mData"    : 'post_title',
                    "mRender"  : function (data, type, row) {
                        return "<b class='post-title'>" + data + "</b>"
                               + "<div class='row-actions'>"

                               + "<a href='" + xagio_data.wp_admin + 'post.php?post=' + row.ID + '&action=edit' + "' target='_blank' class='edit'>Edit</a>"

                               + " <span>|</span> "

                               + "<a href='" + row.guid + "' target='_blank' class='view'>View</a>"
                               + "</div>";
                    },
                    "asSorting": [
                        "desc",
                        "asc"
                    ]
                },
                {
                    "bSortable": true,
                    "mData"    : 'post_date',
                    "mRender"  : function (data, type, row) {
                        return '<b>' + row.post_status.charAt(0).toUpperCase() + row.post_status.slice(1) + 'ed</b>'
                               + '<br>'
                               + '<abbr title="' + data + '">' + new Date(data).toUTCString().split(' ').splice(0, 4).join(' ') + '</abbr>';
                    },
                    "asSorting": [
                        "desc",
                        "asc"
                    ]
                },
            ],
            "fnServerParams": function (aoData) {

                aoData.push({
                    name : 'action',
                    value: 'xagio_get_posts'
                });

                if ($('#PostsTypeClone').length > 0) {

                    aoData.push({
                        name : 'PostsType',
                        value: $('#PostsTypeClone').val()
                    });

                }
            },

            fnInitComplete: function () {

                $('.posts-actions-clone').append(
                    '<div class="uk-float-left">'

                    + '<select class=" xagio-input-select xagio-input-select-gray" id="PostsTypeClone">'
                    + '<option value="">Post Type</option>'
                    + '<option value="post">Posts</option>'
                    + '<option value="page">Pages</option>'
                    + '</select>'

                    + '</div>'

                    + '<div class="uk-clearfix"></div>'
                );
            }

        });
    };

    var load = function () {
        rTable = $('.rTable');
        rTable.parents('.reviews-table-holder').find('.rTable-no-reviews').hide();
        rTable.show();
        rTable.dataTable({
            language        : {
                search           : "_INPUT_",
                searchPlaceholder: "Search reviews...",
                processing       : "Loading Reviews...",
                emptyTable       : "No reviews found. To collect reviews, use the <kbd>Reviews Widget</kbd> in Appearance > Widgets."
            },
            "dom"           : 'rt<"xagio-table-bottom"lp>',
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
                    1,
                    'desc'
                ]
            ],
            "aoColumns"     : [
                {
                    "sClass"     : "column-id",
                    "mData"      : "id",
                    "bSortable"  : false,
                    "bSearchable": false,
                    "mRender"    : function (data, type, row) {
                        return '<input class="xagio-input-checkbox select-review" type="checkbox" value="' + data + '">';
                    }
                },
                {
                    "sClass"     : "author column-author",
                    "bSortable"  : true,
                    "bSearchable": true,
                    "mData"      : "name",
                    "mRender"    : function (data, type, row) {

                        if (isBlank(data)) {
                            data = '<i>Unknown</i>';
                        }

                        if (isBlank(row.email)) {
                            row.email = '';
                        }

                        var html = '<div class="review-author-holder"><img src="' + gravatar_get(row.email) + '" class="avatar avatar-32 photo" height="32" width="32"> ' + data + '</strong></div>';
                        return html;
                    }
                },
                {
                    "sClass"   : "column-comment",
                    "bSortable": true,
                    "mData"    : "rating",
                    "mRender"  : function (data, type, row) {

                        var html        = '<span style="display: none">' + row.rating + '</span>';
                        var full_stars  = row.rating;
                        var empty_stars = 5 - row.rating;
                        html += '<div class="stars-container">';
                        for (var i = 0; i < full_stars; i++) {
                            html += '<i class="xagio-icon xagio-icon-star"></i> ';
                        }
                        for (var i = 0; i < empty_stars; i++) {
                            html += '<i class="xagio-icon xagio-icon-star-o"></i> ';
                        }
                        html += '</div>';

                        // if (!isBlank(row.title)) {
                        //     html += '<h4>' + row.title + '</h4>';
                        // }

                        if (!isBlank(row.review)) {
                            html += '<p>' + row.review + '</p>';
                        } else {
                            html += '<i> Stars only review. </i>';
                        }

                        // html += '<div class="row-actions">'
                        //         + '<span class="approve"><a href="#" class="approve_review vim-a" title="Approve this comment">Approve</a></span>'
                        //         + '<span class="unapprove"><a href="#" class="unapprove_review vim-u" title="Unapprove this comment">Unapprove</a></span>'
                        //         + '<span class="edit"> | <a href="#" class="edit_review vim-e" title="Edit this comment">Edit</a></span>'
                        //         + '<span class="trash"> | <a href="#" class="remove_review vim-d vim-destructive" title="Move this comment to the Trash">Trash</a></span>'
                        //         + '<span class="clone"> | <a href="#" class="clone_review vim-d" title="Clone this comment on multiple pages">Clone</a></span>'
                        //         + '</div>';
                        return html;
                    }
                },
                {
                    "sClass"   : "column-post",
                    "bSortable": true,
                    "mData"    : "page_id",
                    "mRender"  : function (data, type, row) {
                        return "<div class='response-links'>"
                               // + "<a target='_blank' href='" + row.page_edit + "' class='comments-edit-item-link'>" + row.page_title + "</a>"
                               + "<a target='_blank' href='" + row.page_url + "' class='comments-view-item-link'>View Post</a>"
                               + "</div>";
                    }
                },
                {
                    "sClass"     : "column-date",
                    "bSortable"  : true,
                    "bSearchable": true,
                    "mData"      : "date",
                    "mRender"    : function (data, type, row) {
                        return new Date(data).toLocaleDateString("en-US", { weekday: 'short', year: 'numeric', month: 'numeric', day: 'numeric' });
                    }
                },
                {
                    "sClass"     : "column-action",
                    "bSortable"  : true,
                    "bSearchable": true,
                    "mData"      : "date",
                    "mRender"    : function (data, type, row) {
                        return `<div class="xagio-table-buttons-flex"><a href="#" class="xagio-button xagio-button-primary xagio-button-mini approve_review vim-a" title="Approve this comment"><i class="xagio-icon xagio-icon-thumbs-up"></i></a>
                                <a href="#" class="xagio-button xagio-button-primary xagio-button-mini unapprove_review vim-u" data-xagio-tooltip data-xagio-title="Unapprove this comment"><i class="xagio-icon xagio-icon-thumbs-down"></i></a>
                                <a href="#" class="xagio-button xagio-button-primary xagio-button-mini edit_review vim-e" data-xagio-tooltip data-xagio-title="Edit this comment"><i class="xagio-icon xagio-icon-edit"></i></a>
                                <a href="#" class="xagio-button xagio-button-danger xagio-button-mini remove_review vim-d vim-destructive" data-xagio-tooltip data-xagio-title="Move this comment to the Trash"><i class="xagio-icon xagio-icon-delete"></i></a>
                                <a href="#" class="xagio-button xagio-button-primary xagio-button-mini clone_review vim-d" data-xagio-tooltip data-xagio-title="Clone this comment on multiple pages"><i class="xagio-icon xagio-icon-copy"></i></a></div>`;
                    }
                }
            ],
            "fnServerParams": function (aoData) {
                aoData.push({
                    name : 'action',
                    value: 'xagio_getReviews'
                }, {
                    name : 'ReviewState',
                    value: $('#ReviewState').val()
                });
            },
            "fnCreatedRow"  : function (row, data, index) {
                if (data.approved == 0) {
                    $(row).addClass('unapproved');
                }
            },
            fnInitComplete  : function (settings, json) {
                $('.total-number-of-reviews').html(json.iTotalRecords);

                $('.left.filters').append(
                    + '<div style="display: inline">'
                    + '<select class="uk-form-small" id="ReviewBulkActions">'
                    + '<option value="">–– Bulk Actions ––</option>'
                    + '<option value="approve">Approve</option>'
                    + '<option value="unapprove">Unapprove</option>'
                    + '<option value="delete">Trash</option>'
                    + '<option value="move">Move</option>'
                    + '</select>'
                    + '</div>'
                );

                $('.select-review-all').attr('checked', false);
            }
        });

        $(document).on('change', '#ReviewBulkActions', function (e) {
            var ids   = [];
            var value = $(this).val();
            if (!isBlank(value)) {

                $('.select-review').each(function () {
                    if ($(this).is(':checked')) {
                        ids.push($(this).val());
                    }
                });

                if (ids.length > 0) {
                    if (value === 'move') {
                        $('#selectedReviews').val(ids.join());
                        $('#availablePagesModal')[0].showModal();
                        return false;
                    }

                    xagioModal("Are you sure?", "You are about to <b>" + value + "</b> all selected reviews. Proceed?", function (yes) {
                        if (yes) {
                            var data = {
                                action: 'xagio_bulkReviews',
                                type  : value,
                                ids   : ids
                            };

                            $.post(xagio_data.wp_post, data, function (d) {

                                xagioNotify("success", "Action successfully performed!");
                                rTable.fnDraw();

                            });
                        }
                    })
                } else {
                    xagioNotify("danger", "You must select some reviews first!");
                }
                $(this).val('');
            }

        });

        $(document).on('change', '#ReviewState', function (e) {
            rTable.fnDraw();
        });

        $(document).on('click', '.select-review-all', function (e) {
            e.stopPropagation();

            var checked = $(this).is(':checked');
            $('.select-review').each(function () {
                $(this).prop('checked', checked);
            })

        });

    };

    var clone     = function () {

        $(document).on('click', '#cloneReview', function () {
            var btn       = $(this);
            var review_id = btn.parents('.uk-modal-dialog').find('#selectedReviewId').val();

            if (selectedPosts.length < 1) {
                xagioNotify("danger", "Please select some posts!");
                return false;
            }
            btn.disable();

            $.post(xagio_data.wp_post, 'action=xagio_cloneReview&post_ids=' + selectedPosts.join() + '&review_id=' + review_id).done(function (d) {
                xagioNotify("success", "Successfully cloned review to selected posts.");
                $('#availablePagesCloneModal')[0].close();
                load();
                btn.disable();
            });


        });

        $(document).on('click', '.clone_review', function (e) {
            e.preventDefault();
            var id = $(this).parents('tr').find('.select-review').val();
            postsCloneTable();
            var modal = $('#availablePagesCloneModal');
            modal.find('#selectedReviewId').val(id);
            $(modal)[0].showModal();
        });
    };
    var unapprove = function () {
        $(document).on('click', '.unapprove_review', function (e) {
            e.preventDefault();
            var button = $(this);
            var id     = $(this).parents('tr').find('.select-review').val();
            $.post(xagio_data.wp_post, 'action=xagio_unapproveReview&id=' + id).done(function (d) {
                rTable.fnDraw();
                xagioNotify("success", "Review has been unapproved.");
            });
        });
    };

    var approve = function () {
        $(document).on('click', '.approve_review', function (e) {
            e.preventDefault();
            var button = $(this);
            var id     = $(this).parents('tr').find('.select-review').val();
            $.post(xagio_data.wp_post, 'action=xagio_approveReview&id=' + id).done(function (d) {
                rTable.fnDraw();
                xagioNotify("success", "Review has been approved.");
            });
        });
    };

    var remove = function () {
        $(document).on('click', '.remove_review', function (e) {
            e.preventDefault();
            var id = $(this).parents('tr').find('.select-review').val();
            $.post(xagio_data.wp_post, 'action=xagio_removeReview&id=' + id).done(function (d) {
                xagioNotify("success", "Review has been removed.");
                rTable.fnDraw();
            });
        });
    };

    var render = {
        fields         : {
            name     : {
                type          : "text",
                label         : "Name:",
                placeholder   : "eg. John",
                altplaceholder: "Your Name",
                visible       : true,
                required      : false
            },
            review   : {
                type          : "textarea",
                label         : "Review:",
                placeholder   : "eg. This is really a cool website!",
                altplaceholder: "Your Review",
                visible       : true,
                required      : false
            },
            rating   : {
                type          : "stars",
                label         : "Rating:",
                placeholder   : "",
                altplaceholder: "",
                visible       : true,
                required      : true
            },
            email    : {
                type          : "email",
                label         : "E-Mail Address:",
                placeholder   : "eg. your@email.com",
                altplaceholder: "E-Mail Address",
                visible       : false,
                required      : false
            },
            website  : {
                type          : "url",
                label         : "Website:",
                placeholder   : "eg. http://www.website.com",
                altplaceholder: "Your Website",
                visible       : false,
                required      : false
            },
            title    : {
                type          : "text",
                label         : "Title:",
                placeholder   : "eg. I like this product",
                altplaceholder: "Your Title",
                visible       : false,
                required      : false
            },
            telephone: {
                type          : "text",
                label         : "Telephone:",
                placeholder   : "eg. 1-800-500-6000",
                altplaceholder: "Your Phone Number",
                visible       : false,
                required      : false
            },
            location : {
                type          : "text",
                label         : "Location:",
                placeholder   : "eg. Los Angeles",
                altplaceholder: "Your Location",
                visible       : false,
                required      : false
            },
            age      : {
                type          : "number",
                label         : "Age:",
                placeholder   : "eg. 35",
                altplaceholder: "Your Age",
                visible       : false,
                required      : false
            }
        },
        init           : function () {
            render.firstStart();
            render.doRender();
            render.moveFields();
            render.showHideFields();
            render.requireFields();
        },
        firstStart     : function () {
            var json = $('[name="XAGIO_REVIEW[fields]"]').val();
            if (json == '') return;
            json = JSON.parse(atob(json));

            jsonLoaded = true;

            var fields_temp = render.fields;
            render.fields   = render.fixPlaceholders($.extend(true, {}, json));

            for (var key in fields_temp) {
                if (!render.fields.hasOwnProperty(key)) {
                    render.fields[key] = fields_temp[key];
                    $('.ps_review_placeholders [data-name="' + key + '"]').val(render.fields[key].placeholder);
                } else {
                    $('.ps_review_placeholders [data-name="' + key + '"]').val(render.fields[key].placeholder);
                }
            }
        },
        fixPlaceholders: function (fields) {
            for (let name in fields) {
                let old_field = render.fields[name];
                let new_field = fields[name];

                if (!new_field.hasOwnProperty('altplaceholder')) {
                    new_field.altplaceholder = old_field.altplaceholder;
                }
            }
            return fields;
        },
        showHideFields : function () {
            $(document).on('click', '.uk-button-switch', function () {
                var i            = $(this).find('i');
                var n            = $(this).parents('.xagio-sortable-row').data('name');
                var btn_required = $(this).parents('.xagio-sortable-row').find('.uk-button-required');
                var newFields    = render.fields;
                if (i.hasClass('xagio-icon-plus')) {
                    // ADD
                    i.removeClass('xagio-icon-plus').addClass('xagio-icon-delete');
                    btn_required.show();
                    newFields[n].visible = true;
                } else {
                    // REMOVE
                    i.removeClass('xagio-icon-delete').addClass('xagio-icon-plus');
                    btn_required.hide();
                    newFields[n].visible = false;
                }
                render.fields = $.extend(true, {}, newFields);
                render.doRender();
            });
        },
        requireFields  : function () {
            $(document).on('click', '.uk-button-required', function () {
                var i         = $(this).find('i');
                var n         = $(this).parents('.xagio-sortable-row').data('name');
                var newFields = render.fields;
                if (i.hasClass('xagio-icon-ban')) {
                    // ADD
                    i.removeClass('xagio-icon-ban').addClass('xagio-icon-check');
                    newFields[n].required = true;
                } else {
                    // REMOVE
                    i.removeClass('xagio-icon-check').addClass('xagio-icon-ban');
                    newFields[n].required = false;
                }
                render.fields = $.extend(true, {}, newFields);
                render.doRender();
            });
        },
        moveFields     : function () {
            $('.uk-sortable').on('stop.uk.sortable', function (e) {
                var newFields = [];
                $('.fields').find('.xagio-sortable-row').each(function () {
                    var key        = $(this).data('name');
                    newFields[key] = render.fields[key];
                });
                render.fields = $.extend(true, {}, newFields);
                render.doRender();
            });
        },
        saveJson       : function () {
            // This cannot be here because on FIRST LOAD of plugin option does not exist and filed will never be loaded into XAGIO_REVIEW[fields]
            // if (!jsonLoaded) return;
            $('[name="XAGIO_REVIEW[fields]"]').val(btoa(JSON.stringify(render.fields)));
        },
        doRender       : function () {
            render.saveJson();
            var cnt = $('.review-widget-block-container');
            cnt.empty();
            var i          = 0;
            var allHtml    = '';
            var stars_only = $('[name="XAGIO_REVIEW[settings][stars_only]"]').val();
            for (var name in render.fields) {
                i++;
                var field = render.fields[name];
                if (field.visible == false || (stars_only == 1 && name !== 'rating')) continue;
                $('[data-name="' + name + '"]').find('button.uk-button-switch').find('i').removeClass('xagio-icon-plus').addClass('xagio-icon-delete');
                if (field.required) {
                    $('[data-name="' + name + '"]').find('button.uk-button-required').show().find('i').removeClass('xagio-icon-ban').addClass('xagio-icon-check');
                } else {
                    $('[data-name="' + name + '"]').find('button.uk-button-required').show().find('i').removeClass('xagio-icon-check').addClass('xagio-icon-ban');
                }
                var html = '';
                if (stars_only != 1) {
                    html += '<label class="review-widget-label" for="i' + i + '">' + field.label + '</label>';
                }
                switch (field.type) {
                    case "text":
                        html += '<input name="' + name + '" type="text" class="review-widget-input" id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '"/>';
                        break;
                    case "textarea":
                        html += '<textarea name="' + name + '" rows="5" class="review-widget-input" id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '"></textarea>';
                        break;
                    case "stars":
                        html += '<input type="hidden" value="5" name="' + name + '" id="ps-rating"/>';
                        html += '<div class="review-widget-stars">';
                        html += '<i class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i class="xagio-icon xagio-icon-star-o"></i>';
                        html += '<i class="xagio-icon xagio-icon-star-o"></i>';
                        html += '</div>';
                        break;
                    case "email":
                        html += '<input name="' + name + '" type="email" class="review-widget-input" id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '"/>';
                        break;
                    case "url":
                        html += '<input name="' + name + '" type="url" class="review-widget-input" id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '"/>';
                        break;
                    case "number":
                        html += '<input name="' + name + '" type="number" class="review-widget-input" id="i' + i + '" placeholder="' + field.placeholder + '" data-placeholder="' + field.placeholder + '" data-alt-placeholder="' + field.altplaceholder + '"/>';
                        break;

                }
                html += '<div class="cx"></div>';
                html = '<div class="review-widget-block">' + html + '</div>';
                allHtml += html;
            }
            cnt.append(allHtml);
            var hnd = $('[name="XAGIO_REVIEW[settings][form_labels]"]');
            hnd.trigger('change');
            colors.refresh_colors();
        }
    };

    var handlers = {
        t1                          : null,
        t2                          : null,
        t3                          : null,
        t4                          : null,
        t5                          : null,
        t6                          : null,
        init                        : function () {
            handlers.title_changing();
            handlers.text_changing();
            handlers.rating_text_changing();
            handlers.rating_info_changing();
            handlers.display_widget_text_changing();
            handlers.button_title_changing();
            handlers.form_label_changing();
            handlers.widget_theme_changing();
            handlers.widget_width_changing();
            handlers.alignment_changing();
            handlers.font_size_changing();
            handlers.padding_changing();
            handlers.alpha_widget();
            handlers.stars_only();
        },
        title_changing              : function () {
            var hnd = $('[name="XAGIO_REVIEW[details][title]"]');
            var def = 'Leave a Review';
            hnd.keyup(function () {
                var d = $(this);
                clearTimeout(handlers.t1);
                handlers.t1 = setTimeout(function () {
                    var val = d.val();
                    var ele = $('.review-widget-title > h2');
                    if (val == '') {
                        ele.html(def);
                    } else {
                        ele.html(val);
                    }
                }, 200);
            });
            hnd.trigger('keyup');
        },
        text_changing               : function () {
            var hnd = $('[name="XAGIO_REVIEW[details][text]"]');
            var def = 'Please be kind and leave us a review!';
            hnd.keyup(function () {
                var d = $(this);
                clearTimeout(handlers.t2);
                handlers.t2 = setTimeout(function () {
                    var val = d.val();
                    var ele = $('.review-widget-text');
                    if (val == '') {
                        ele.html(def);
                    } else {
                        ele.html(val);
                    }
                }, 200);
            });
            hnd.trigger('keyup');
        },
        rating_info_changing        : function () {
            var hnd = $('[name="XAGIO_REVIEW[details][rating_info]"]');
            var def = 'Click a star to add your rating';
            hnd.keyup(function () {
                var d = $(this);
                clearTimeout(handlers.t5);
                handlers.t5 = setTimeout(function () {
                    var val = d.val();
                    var ele = $('.review-widget-stars-ratings-info');
                    if (val == '') {
                        ele.html(def);
                    } else {
                        ele.html(val);
                    }
                }, 200);
            });
            hnd.trigger('keyup');
        },
        rating_text_changing        : function () {
            var hnd = $('[name="XAGIO_REVIEW[details][rating_text]"]');
            var def = '';
            hnd.keyup(function () {
                var d = $(this);
                clearTimeout(handlers.t6);
                handlers.t6 = setTimeout(function () {
                    var val = d.val();
                    var ele = $('.review-widget-stars-ratings-sum');
                    if (val == '') {
                        ele.html(def);
                    } else {

                        ele.html(val.replace('{num}', '<b>100%</b>').replace('{sum}', '<b>12</b>').replace('{calc}', '<b>5.0</b>'));
                    }
                }, 200);
            });
            hnd.trigger('keyup');

            $('[name="XAGIO_REVIEW[details][rating_heading_size]"]').change(function () {
                $('.review-widget-stars-ratings-sum').css('font-size', $(this).val() + 'px');
            }).trigger('change');

            $('[name="XAGIO_REVIEW[details][rating_instruction_size]"]').change(function () {
                $('.review-widget-stars-ratings-info').css('font-size', $(this).val() + 'px');
            }).trigger('change');
        },
        display_widget_text_changing: function () {
            var hnd = $('[name="XAGIO_REVIEW[details][display_reviews_text]"]');
            var def = '{calc} Rating From {sum} Reviews.';
            hnd.keyup(function () {
                var d = $(this);
                clearTimeout(handlers.t4);
                handlers.t4 = setTimeout(function () {
                    var val = d.val();
                    var ele = $('.prs-review-container-aggregate');
                    if (val == '') {
                        ele.html(def.replace('{calc}', '<b>5</b>').replace('{sum}', '<b>13</b>'));
                    } else {
                        ele.html(val.replace('{calc}', '<b>5</b>').replace('{sum}', '<b>13</b>'));
                    }
                }, 200);
            });
            hnd.trigger('keyup');
        },
        button_title_changing       : function () {
            var hnd = $('[name="XAGIO_REVIEW[details][button_title]"]');
            var def = 'Submit Review';
            hnd.keyup(function () {
                var d = $(this);
                clearTimeout(handlers.t3);
                handlers.t3 = setTimeout(function () {
                    var val = d.val();
                    var ele = $('.review-widget-button');
                    if (val == '') {
                        ele.text(def);
                    } else {
                        ele.text(val);
                    }
                }, 200);
            });
            hnd.trigger('keyup');
        },
        widget_width_changing       : function () {
            var hnd = $('[name="XAGIO_REVIEW[settings][widget_width]"]');
            var wid = $('.review-widget');
            hnd.change(function () {
                var val = $(this).val();
                if (val == 0) {
                    wid.removeClass('review-widget-auto-width');
                } else if (val == 1) {
                    wid.addClass('review-widget-auto-width');
                }
            });
            hnd.trigger('change');
        },
        widget_theme_changing       : function () {
            var hnd = $('[name="XAGIO_REVIEW[settings][widget_theme]"]');
            var wid = $('.review-widget');
            hnd.change(function () {
                var val = $(this).val();
                if (val == 0) {
                    wid.removeClass('review-widget-flat');
                    wid.removeClass('review-widget-minimal');
                } else if (val == 1) {
                    wid.addClass('review-widget-flat');
                    wid.removeClass('review-widget-minimal');
                } else if (val == 2) {
                    wid.removeClass('review-widget-flat');
                    wid.addClass('review-widget-minimal');
                }
            });
            hnd.trigger('change');
        },
        alpha_widget                : function () {
            var hnd = $('[name="XAGIO_REVIEW[settings][alpha_bg]"]');
            var wid = $('.review-widget');
            var par = $('#preview-area');
            hnd.change(function () {
                var val = $(this).val();
                if (val == 0) {
                    wid.removeClass('review-widget-alpha');
                    par.removeClass('alpha-mode');
                } else if (val == 1) {
                    wid.addClass('review-widget-alpha');
                    par.addClass('alpha-mode');
                }
            });
            hnd.trigger('change');
        },
        stars_only                  : function () {
            var hnd = $('[name="XAGIO_REVIEW[settings][stars_only]"]');
            var wid = $('.review-widget');
            hnd.change(function () {
                var val = $(this).val();
                if (val == 0) {
                    wid.removeClass('review-widget-stars-only');
                } else if (val == 1) {
                    wid.addClass('review-widget-stars-only');
                }
                render.doRender();
            });
            hnd.trigger('change');
        },
        form_label_changing         : function () {
            var hnd = $('[name="XAGIO_REVIEW[settings][form_labels]"]');
            var wid = $('.review-widget');
            hnd.change(function () {
                var val = $(this).val();
                if (val == 0) {
                    wid.removeClass('review-widget-labels');
                    wid.removeClass('review-widget-placeholders');
                    $('.review-widget-input').each(function () {
                        $(this).attr('placeholder', $(this).attr('data-placeholder'));
                    });
                } else if (val == 1) {
                    wid.addClass('review-widget-labels');
                    wid.removeClass('review-widget-placeholders');
                    $('.review-widget-input').each(function () {
                        $(this).attr('placeholder', $(this).attr('data-placeholder'));
                    });
                } else if (val == 2) {
                    wid.addClass('review-widget-placeholders');
                    wid.removeClass('review-widget-labels');
                    $('.review-widget-input').each(function () {
                        $(this).attr('placeholder', $(this).attr('data-alt-placeholder'));
                    });
                }
            });
            hnd.trigger('change');
        },
        alignment_changing          : function () {
            var hnd = $('[name="XAGIO_REVIEW[settings][alignment]"]');
            var wid = $('.review-widget');
            hnd.change(function () {
                var val = $(this).val();
                wid.removeClass('review-widget-left');
                wid.removeClass('review-widget-center');
                wid.removeClass('review-widget-right');
                wid.addClass('review-widget-' + val);
            });
            hnd.trigger('change');
        },
        placeholder_change          : function () {
            $(document).on('change keyup', '.ps_review_placeholders input[type="text"]', function () {
                var input  = $(this);
                var target = input.data('name');
                $('#preview-area [name="' + target + '"]').attr('placeholder', input.val());

                var newFields                    = render.fields;
                newFields[target].placeholder    = input.val();
                newFields[target].altplaceholder = input.val();
                render.fields                    = $.extend(true, {}, newFields);
                render.doRender();
            });
        },
        font_size_changing          : function () {

            $('[name="XAGIO_REVIEW[font_size][heading]"]').change(function () {
                $('.review-widget-title').find('h2').css('font-size', $(this).val() + 'px');
            }).trigger('change');

            $('[name="XAGIO_REVIEW[font_size][subheading]"]').change(function () {
                $('.review-widget-text').css('font-size', $(this).val() + 'px');
            }).trigger('change');

            $('[name="XAGIO_REVIEW[font_size][label]"]').change(function () {
                $('.review-widget-label').css('font-size', $(this).val() + 'px');
            }).trigger('change');

            $('[name="XAGIO_REVIEW[font_size][input]"]').change(function () {
                $('.review-widget-input').css('font-size', $(this).val() + 'px');
            }).trigger('change');

        },
        padding_changing            : function () {

            $('[name="XAGIO_REVIEW[padding][widget]"]').change(function () {
                $('.review-widget').css('padding', $(this).val() + 'px');
            }).trigger('change');

            $('[name="XAGIO_REVIEW[padding][input]"]').change(function () {
                $('.review-widget-input').css('padding', $(this).val() + 'px');
            }).trigger('change');

        }
    };

    var colors = {
        init             : function () {
            colors.background();
            colors.border();
            colors.button_background();
            colors.button_text();
            colors.text();
            colors.input_background();
            colors.input_text();
            colors.stars();
            colors.rating_heading();
            colors.rating_info();
            colors.stars_size();
        },
        rating_heading   : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][rating_heading]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget-stars-ratings-sum').css('color', color);
            });
            hnd.trigger('change');
        },
        rating_info      : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][rating_info]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget-stars-ratings-info').css('color', color);
            });
            hnd.trigger('change');
        },
        background       : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][background]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget').css('background', color);
            });
            hnd.trigger('change');
        },
        border           : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][border]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget').css('box-shadow', '2px 3px 3px 0 ' + color);
                $('.review-widget').css('border-color', color);
            });
            hnd.trigger('change');
        },
        button_background: function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][button_background]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget-button').css('background', color);
            });
            hnd.trigger('change');
        },
        button_text      : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][button_text]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget-button').css('color', color);
            });
            hnd.trigger('change');
        },
        text             : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][text]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget').css('color', color);
                $('.review-widget-title > h2').css('color', color);
            });
            hnd.trigger('change');
        },
        input_background : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][input_background]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget-input').css('background', color);
            });
            hnd.trigger('change');
        },
        input_text       : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][input_text]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget-input').css('color', color);
            });
            hnd.trigger('change');
        },
        stars            : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][stars]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.review-widget-stars i').css('color', color);
            });
            hnd.trigger('change');
        },
        stars_size       : function () {
            var hnd = $('[name="XAGIO_REVIEW[font_size][stars]"]');
            hnd.change(function () {
                $('.review-widget-stars i').css('font-size', $(this).val() + 'px');
            });
            hnd.trigger('change');
        },
        refresh_colors   : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors][background]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[colors][border]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[colors][button_background]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[colors][button_text]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[colors][text]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[colors][input_background]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[colors][input_text]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[colors][stars]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[font_size][stars]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[font_size][label]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[font_size][input]"]');
            hnd.trigger('change');
            hnd = $('[name="XAGIO_REVIEW[padding][input]');
            hnd.trigger('change');
        }
    };

    var display = {
        init              : function () {
            $(document).on('click', '.show_widget li', function (e) {
                let li = $(this);
                let show_widget = li.data('widget');

                if (show_widget === 'reviews') {
                    $('.review-widget').hide();
                    $('.review-display').show();
                } else {
                    $('.review-widget').show();
                    $('.review-display').hide();
                }

                var hnd = $('[name="XAGIO_REVIEW[settings][stars_only]"]');

                if (show_widget === 'ratings') {
                    $('.review-widget').addClass('review-widget-stars-only');
                    $('.review_widget_rating_mode_alert').show();

                    let oldVal = hnd.val();
                    hnd.val(1);
                    render.doRender();
                    hnd.val(oldVal);
                } else {
                    $('.review-widget').removeClass('review-widget-stars-only');
                    $('.review_widget_rating_mode_alert').hide();
                    let oldVal = hnd.val();
                    hnd.val(0);
                    render.doRender();
                    hnd.val(oldVal);
                }

            });

            display.heading();
            display.background();
            display.border();
            display.stars();
            display.display_stars_size();
            display.text();
        },
        heading           : function () {
            var hnd = $('[name="XAGIO_REVIEW[details][display_reviews_heading]"]');
            hnd.keyup(function () {
                var text = $(this).val();
                $('.prs-review-display-heading').find('h2').html(text);
            });
            hnd.trigger('keyup');

            $('[name="XAGIO_REVIEW[details][heading_size]"]').change(function () {
                $('.prs-review-display-heading').find('h2').css('font-size', $(this).val() + 'px');
            }).trigger('change');

            $('[name="XAGIO_REVIEW[details][subheading_size]"]').change(function () {
                $('.prs-review-container-aggregate').css('font-size', $(this).val() + 'px');
            }).trigger('change');
        },
        background        : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors_display][background]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.prs-review-container-aggregate').css('background', color);
                $('.prs-review-container').css('background', color);
            });
            hnd.trigger('change');
        },
        border            : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors_display][border]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.prs-review-container-aggregate').css('border-color', color);
                $('.prs-review-container').css('border-color', color);
            });
            hnd.trigger('change');
        },
        stars             : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors_display][stars]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.prs-review-stars i').css('color', color);
            });
            hnd.trigger('change');
        },
        display_stars_size: function () {
            var hnd = $('[name="XAGIO_REVIEW[details][display_star_size]"]');
            hnd.change(function () {
                $('.prs-review-stars i').css('font-size', $(this).val() + 'px');
            });
            hnd.trigger('change');
        },
        text              : function () {
            var hnd = $('[name="XAGIO_REVIEW[colors_display][text]"]');
            hnd.change(function () {
                var color = $(this).val();
                $('.prs-review-container-aggregate').css('color', color);
                $('.prs-review-container').css('color', color);
                $('.prs-review-display-heading').find('h2').css('color', color);
            });
            hnd.trigger('change');
        }
    }

})(jQuery);
