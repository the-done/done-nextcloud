import { t } from '@nextcloud/l10n';

// Custom translations by contexts/components
const customTranslations = {
  // Translations for TimeTrackingEditForm
  'TimeTrackingEditForm': {
    //'Record date': 'Statistics recording date'
  },
  
  // Translations for other components can be added here
  'ProjectForm': {
    'Project name': 'Project name'
  }
};

/**
 * Get custom translation for specific context
 * @param {string} context - Context (e.g., component name)
 * @param {string} key - Translation key
 * @returns {string} - Translated text
 */
export const getCustomTranslation = (context, key) => {
  // Check if there is a custom translation for this context and key
  if (customTranslations[context] && customTranslations[context][key]) {
    return customTranslations[context][key];
  }
  
  // If no custom translation exists, use standard one
  return t('done', key);
};

/**
 * Get all custom translations for context
 * @param {string} context - Context
 * @returns {Object} - Object with translations
 */
export const getCustomTranslationsForContext = (context) => {
  return customTranslations[context] || {};
};

/**
 * Add custom translation
 * @param {string} context - Context
 * @param {string} key - Translation key
 * @param {string} value - Translation value
 */
export const addCustomTranslation = (context, key, value) => {
  if (!customTranslations[context]) {
    customTranslations[context] = {};
  }
  customTranslations[context][key] = value;
}; 