/**
 * @author    Pierre Allard <pierre.allard@akeneo.com>
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
'use strict';

define([
    'jquery',
    'pim/form/common/fields/select',
    'pim/fetcher-registry',
    'pim/user-context'
],
function (
    $,
    BaseSelect,
    FetcherRegistry,
    UserContext
) {
    return BaseSelect.extend({
        /**
         * {@inheritdoc}
         */
        configure: function () {
            return $.when(
                BaseSelect.prototype.configure.apply(this, arguments),
                FetcherRegistry.getFetcher('channel').fetchAll()
                    .then(function (scopes) {
                        this.config.choices = scopes;
                    }.bind(this))
            );
        },

        /**
         * @param {Array} scopes
         */
        formatChoices: function (scopes) {
            return scopes.reduce((result, channel) => {
                const label = channel.labels[UserContext.get('user_default_locale')];
                result[channel.code] = label !== undefined ? label : '[' + channel.code + ']';
                return result;
            }, {});
        }
    });
});
