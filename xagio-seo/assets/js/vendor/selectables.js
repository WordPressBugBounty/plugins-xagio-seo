/*
 *   Selectables  
 *   
 *   v1.4.0
 *       
 *   https://github.com/p34eu/Selectables.git
 */

function Selectables(opts) {
    'use strict';
    var defaults             = {
        zone         : "#wrapper", // ID of the element whith selectables.
        elements     : "a", //  items to be selectable .list-group, #id > .class,'htmlelement' - valid querySelectorAll
        selectedClass: 'selected', // class name to apply to seleted items
        key          : false, //'altKey,ctrlKey,metaKey,false  // activate using optional key
        moreUsing    : 'shiftKey', //altKey,ctrlKey,metaKey   // add more to selection
        enabled      : true, //false to .enable() at later time
        start        : null, //  event on selection start
        stop         : null, // event on selection end
        onSelect     : null, // event fired on every item when selected.
        onDeselect   : null         // event fired on every item when selected.
    };
    var extend               = function extend(a, b) {
        for (var prop in b) {
            a[prop] = b[prop];
        }
        return a;
    };
    this.foreach             = function (items, callback, scope) {
        if (Object.prototype.toString.call(items) === '[object Object]') {
            for (var prop in items) {
                if (Object.prototype.hasOwnProperty.call(items, prop)) {
                    callback.call(scope, items[prop], prop, items);
                }
            }
        } else {
            for (var i = 0, len = items.length; i < len; i++) {
                callback.call(scope, items[i], i, items);
            }
        }
    }
    this.options             = extend(defaults, opts || {});
    this.x                   = false;
    this.y                   = false;
    this.on                  = false;
    var self                 = this;
    this.enable              = function () {
        if (this.on) {
            throw new Error(this.constructor.name + " :: is alredy enabled");
            return;
        }
        this.zone = this.options.zone;
        if (!this.zone) {
            throw new Error(this.constructor.name + " :: no zone defined in options. Please use element with ID");
        }
        this.items = this.options.elements;
        this.disable();
        this.zone.on('mousedown', self.rectOpen);
        this.on = true;
        return this;
    };
    this.disable             = function () {
        this.zone.off('mousedown', self.rectOpen);
        this.on = false;
        return this;
    };
    var offset               = function (el) {
        var r = el.getBoundingClientRect();
        return {
            top : r.top + document.body.scrollTop,
            left: r.left + document.body.scrollLeft
        }
    };
    this.suspend             = function (e) {
        //e.preventDefault();
        //e.stopPropagation();
        return false;
    }
    this.rectOpen            = function (e) {
        self.options.start && self.options.start(e);
        if (self.options.key && !e[self.options.key]) {
            return;
        }
        document.body.classList.add('s-noselect');
        self.items.each(function () {
            let el = jQuery(this);
            el.on('click', self.suspend); //skip any clicks
            if (!e[self.options.moreUsing]) {
                el.removeClass(self.options.selectedClass);
            }
        });

        self.ipos = [
            e.pageX,
            e.pageY
        ];
        if (!rb()) {
            var gh        = document.createElement('div');
            gh.id         = 's-rectBox';
            gh.style.left = e.pageX + 'px';
            gh.style.top  = e.pageY + 'px';
            document.body.appendChild(gh);
        }
        document.body.addEventListener('mousemove', self.rectDraw);
        window.addEventListener('mouseup', self.select);
    };
    var rb                   = function () {
        return document.getElementById('s-rectBox');
    };
    var cross                = function (a, b, r) {
        var aTop  = offset(a).top / r;
        var aLeft = offset(a).left / r;

        var bTop  = offset(b).top / r;
        var bLeft = offset(b).left / r;

        return !(((aTop + (a.offsetHeight / r)) < (bTop)) || (aTop > (bTop + b.offsetHeight)) || ((aLeft + (a.offsetWidth / r)) < bLeft) || (aLeft > (bLeft + b.offsetWidth)));
    };
    this.select              = function (e) {
        var a = rb();
        if (!a) {
            return;
        }
        delete (self.ipos);
        document.body.classList.remove('s-noselect');
        document.body.removeEventListener('mousemove', self.rectDraw);
        window.removeEventListener('mouseup', self.select);
        var s      = self.options.selectedClass;
        var r      = jQuery('.silo:visible').flowchart('getPositionRatio');
        self.items = jQuery('.silo:visible div.flowchart-operator');
        self.items.each(function () {
            let el  = jQuery(this);
            let res = cross(a, el[0], r);
            if (res === true) {
                if (el.hasClass(s)) {
                    el.removeClass(s);
                    self.options.onDeselect && self.options.onDeselect(el);
                } else {
                    el.addClass(s);
                    self.options.onSelect && self.options.onSelect(el);
                }
            }
            setTimeout(function () {
                el.off('click', self.suspend, true);
            }, 100);
        });
        a.parentNode.removeChild(a);
        self.options.stop && self.options.stop(e);
    }
    this.rectDraw            = function (e) {
        var g = rb();
        if (!self.ipos || g === null) {
            return;
        }
        var tmp,
            x1 = self.ipos[0],
            y1 = self.ipos[1],
            x2 = e.pageX,
            y2 = e.pageY;
        if (x1 > x2) {
            tmp = x2, x2 = x1, x1 = tmp;
        }
        if (y1 > y2) {
            tmp = y2, y2 = y1, y1 = tmp;
        }
        g.style.left = x1 + 'px', g.style.top = y1 + 'px', g.style.width = (x2 - x1) + 'px', g.style.height = (y2 - y1) + 'px';
    }
    this.options.selectables = this;
    if (this.options.enabled) {
        return this.enable();
    }
    return this;
}
