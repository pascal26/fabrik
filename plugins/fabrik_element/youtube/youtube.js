/**
 * YouTube Element
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

var FbYouTube;

requirejs(['jquery'], function (jQuery) {
    FbYouTube = new Class({
        Extends   : FbElement,
        initialize: function (element, options) {
            this.setPlugin('fabrikyoutube');
            this.parent(element, options);
        }
    });
});