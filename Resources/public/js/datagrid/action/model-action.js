/*global define*/
define(['underscore', './abstract-action'
    ], function (_, AbstractAction) {
    'use strict';

    /**
     * Basic model action class.
     *
     * @export  oro/datagrid/action/model-action
     * @class   oro.datagrid.action.ModelAction
     * @extends oro.datagrid.action.AbstractAction
     */
    return AbstractAction.extend({
        /** @property {Backbone.Model} */
        model: null,

        /** @property {String} */
        link: undefined,

        /** @property {Boolean} */
        backUrl: false,

        /** @property {String} */
        backUrlParameter: 'back',

        /**
         * Initialize view
         *
         * @param {Object} options
         * @param {Backbone.Model} options.model Optional parameter
         * @throws {TypeError} If model is undefined
         */
        initialize: function (options) {
            var opts = options || {};

            if (!opts.model) {
                throw new TypeError("'model' is required");
            }
            this.model = opts.model;

            if (_.has(opts, 'backUrl')) {
                this.backUrl = opts.backUrl;
            }

            if (_.has(opts, 'backUrlParameter')) {
                this.backUrlParameter = opts.backUrlParameter;
            }

            AbstractAction.prototype.initialize.apply(this, arguments);
        },

        /**
         * Get action link
         *
         * @return {String}
         * @throws {TypeError} If route is undefined
         */
        getLink: function () {
            var result, backUrl;
            if (!this.link) {
                throw new TypeError("'link' is required");
            }

            if (this.model.has(this.link)) {
                result = this.model.get(this.link);
            } else {
                result = this.link;
            }

            if (this.backUrl) {
                backUrl = _.isBoolean(this.backUrl) ? window.location.href : this.backUrl;
                backUrl = encodeURIComponent(backUrl);
                result = this.addUrlParameter(result, this.backUrlParameter, backUrl);
            }

            return result;
        },

        /**
         * Add parameter to URL
         *
         * @param {String} url
         * @param {String} parameterName
         * @param {String} parameterValue
         * @return {String}
         * @protected
         */
        addUrlParameter: function (url, parameterName, parameterValue) {
            var urlHash, sourceUrl, replaceDuplicates = true;
            if (url.indexOf('#') > 0) {
                var cl = url.indexOf('#');
                urlHash = url.substring(url.indexOf('#'), url.length);
            } else {
                urlHash = '';
                cl = url.length;
            }
            sourceUrl = url.substring(0,cl);

            var urlParts = sourceUrl.split("?");
            var newQueryString = "";

            if (urlParts.length > 1) {
                var parameters = urlParts[1].split("&");
                for (var i=0; (i < parameters.length); i++)
                {
                    var parameterParts = parameters[i].split("=");
                    if (!(replaceDuplicates && parameterParts[0] == parameterName))
                    {
                        if (newQueryString == "")
                            newQueryString = "?";
                        else
                            newQueryString += "&";
                        newQueryString += parameterParts[0] + "=" + (parameterParts[1] ? parameterParts[1] : '');
                    }
                }
            }
            if (newQueryString == "") {
                newQueryString = "?";
            }
            if (newQueryString !== "" && newQueryString != '?') {
                newQueryString += "&";
            }
            newQueryString += parameterName + "=" + (parameterValue ? parameterValue : '');
            return urlParts[0] + newQueryString + urlHash;
        }
    });
});
