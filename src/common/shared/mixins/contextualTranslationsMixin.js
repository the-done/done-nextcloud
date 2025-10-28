import { getContextualTranslation, getContextualTranslationWithParams, preloadContextualTranslations } from '@/common/shared/lib/contextualTranslations';
import { t } from '@nextcloud/l10n';

export const contextualTranslationsMixin = {
    methods: {
        /**
         * Synchronous contextual translation for use in template
         * @param {string} key - Translation key
         * @param {string} context - Context (optional)
         * @param {Object} parameters - Parameters for substitution (optional)
         * @returns {string} - Translated text
         */
        contextTranslate(key, context = '', parameters = {}) {
            // If context is not specified, use standard Nextcloud translation
            if (!context) {
                return t('done', key);
            }
            
            // If there are parameters, use function with parameters
            if (parameters && Object.keys(parameters).length > 0) {
                return getContextualTranslationWithParams(key, context, parameters, 'done');
            }
            
            // Use regular contextual translation
            return getContextualTranslation(key, context, 'done');
        },

        /**
         * Preload translations for context
         * @param {string} context - Context
         * @returns {boolean} - true if translations loaded successfully
         */
        preloadTranslations(context) {
            return preloadContextualTranslations(context);
        },

        /**
         * Preload translations for multiple contexts
         * @param {Array} contexts - Array of contexts
         */
        preloadTranslationsForContexts(contexts) {
            contexts.forEach(context => {
                preloadContextualTranslations(context);
            });
        }
    },
    data: () => ({
          context: '',
    }),
};