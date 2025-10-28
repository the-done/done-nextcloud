/**
 * Utility for working with contextual translations in Vue components
 * Loads translations from l10n/contexts/ files
 */

import { t } from '@nextcloud/l10n'

/**
 * Context hierarchy for fallback
 */
const CONTEXT_HIERARCHY = {
  'admin/projects': ['admin/projects', 'admin', 'global'],
  'admin/users': ['admin/users', 'admin', 'global'],
  'admin/finances': ['admin/finances', 'admin', 'global'],
  'user/time-tracking': ['user/time-tracking', 'user', 'global'],
}

/**
 * Paths to contextual translation files for each context
 */
const CONTEXTUAL_TRANSLATION_FILES = {
  'admin/projects': '/l10n/contexts/admin/projects',
  'admin/users': '/l10n/contexts/admin/users',
  'admin/finances': '/l10n/contexts/admin/finances',
  'user/time-tracking': '/l10n/contexts/user/time-tracking',
}

/**
 * Global cache for loaded translations
 */
let globalTranslationsCache = {}

/**
 * Gets current user language
 * @returns {string} - Language code (e.g., 'ru', 'en', 'de')
 */
function getCurrentLanguage() {
  return OC.getLanguage() || 'en'
}

/**
 * Synchronous loading of translations from file
 * @param {string} context - Context
 * @param {string} language - Language code
 * @returns {Object} - Object with translations or empty object
 */
function loadTranslationsFromFileSync(context, language) {
  const contextPath = CONTEXTUAL_TRANSLATION_FILES[context]
  if (!contextPath) {
    return {}
  }

  // Form file path
  const appPath = OC.appswebroots.done || '/apps/done/'
  const filePath = `${appPath}${contextPath}/${language}.json`

  try {
    // Use XMLHttpRequest for synchronous loading
    const xhr = new XMLHttpRequest()
    xhr.open('GET', filePath, false) // false = synchronous request
    xhr.send()
    
    if (xhr.status === 200) {
      const data = JSON.parse(xhr.responseText)
      return data.translations || {}
    }
  } catch (error) {
    console.warn(`Failed to load contextual translations from ${filePath}:`, error)
  }
  
  return {}
}

/**
 * Gets translations for context with hierarchy consideration (synchronously)
 * @param {string} context - Context
 * @param {string} language - Language code
 * @returns {Object} - Object with translations
 */
function getTranslationsForContextSync(context, language) {
  const cacheKey = `${context}_${language}`
  
  // Check cache
  if (globalTranslationsCache[cacheKey]) {
    return globalTranslationsCache[cacheKey]
  }

  const hierarchy = CONTEXT_HIERARCHY[context] || [context, 'global']
  let allTranslations = {}

  // Load translations by hierarchy
  for (const ctx of hierarchy) {
    const translations = loadTranslationsFromFileSync(ctx, language)
    allTranslations = { ...allTranslations, ...translations }
  }

  // Cache result
  globalTranslationsCache[cacheKey] = allTranslations
  
  return allTranslations
}

/**
 * Gets contextual translation (synchronously)
 * @param {string} key - Translation key
 * @param {string} context - Context (e.g., 'admin/projects')
 * @param {string} appName - Application name (default 'done')
 * @returns {string} - Translated text
 */
export function getContextualTranslation(key, context, appName = 'done') {
  const language = getCurrentLanguage()
  const translations = getTranslationsForContextSync(context, language)
  
  if (translations[key]) {
    return translations[key]
  }
  
  // Fallback to standard Nextcloud translation
  return t(appName, key)
}

/**
 * Preloads translations for context (synchronously)
 * @param {string} context - Context
 * @returns {boolean} - true if translations loaded successfully
 */
export function preloadContextualTranslations(context) {
  const language = getCurrentLanguage()
  const cacheKey = `${context}_${language}`
  
  // If already loaded, return true
  if (globalTranslationsCache[cacheKey]) {
    return true
  }
  
  // Load translations
  getTranslationsForContextSync(context, language)
  return !!globalTranslationsCache[cacheKey]
}

/**
 * Gets contextual translation with parameters (synchronously)
 * @param {string} key - Translation key
 * @param {string} context - Context
 * @param {Object} parameters - Parameters for substitution
 * @param {string} appName - Application name
 * @returns {string} - Translated text with substituted parameters
 */
export function getContextualTranslationWithParams(key, context, parameters = {}, appName = 'done') {
  let translation = getContextualTranslation(key, context, appName)
  
  // Substitute parameters
  Object.keys(parameters).forEach(param => {
    translation = translation.replace(new RegExp(`{${param}}`, 'g'), parameters[param])
  })
  
  return translation
}

/**
 * Checks if contextual translation exists for key (synchronously)
 * @param {string} key - Translation key
 * @param {string} context - Context
 * @returns {boolean} - true if contextual translation exists
 */
export function hasContextualTranslation(key, context) {
  const language = getCurrentLanguage()
  const translations = getTranslationsForContextSync(context, language)
  return !!(translations && translations[key])
}

/**
 * Gets all available contextual translations for context (synchronously)
 * @param {string} context - Context
 * @returns {Object} - Object with translations
 */
export function getContextualTranslationsForContext(context) {
  const language = getCurrentLanguage()
  return getTranslationsForContextSync(context, language)
}

/**
 * Gets list of all available contexts
 * @returns {Array} - Array of contexts
 */
export function getAvailableContexts() {
  return Object.keys(CONTEXTUAL_TRANSLATION_FILES)
}

/**
 * Clears translations cache
 */
export function clearTranslationsCache() {
  globalTranslationsCache = {}
}

/**
 * Gets information about loaded translations
 * @returns {Object} - Cache information
 */
export function getCacheInfo() {
  return {
    cacheSize: Object.keys(globalTranslationsCache).length,
    cachedContexts: Object.keys(globalTranslationsCache),
    availableContexts: getAvailableContexts(),
    currentLanguage: getCurrentLanguage(),
  }
}

/**
 * Vue plugin for integration with Vue components
 */
export const ContextualTranslationsPlugin = {
  install(Vue) {
    Vue.prototype.$getContextualTranslation = getContextualTranslation
    Vue.prototype.$getContextualTranslationWithParams = getContextualTranslationWithParams
    Vue.prototype.$hasContextualTranslation = hasContextualTranslation
    Vue.prototype.$preloadContextualTranslations = preloadContextualTranslations
    Vue.prototype.$clearTranslationsCache = clearTranslationsCache
    Vue.prototype.$getCacheInfo = getCacheInfo
  }
}

export default {
  getContextualTranslation,
  getContextualTranslationWithParams,
  hasContextualTranslation,
  getContextualTranslationsForContext,
  getAvailableContexts,
  preloadContextualTranslations,
  clearTranslationsCache,
  getCacheInfo,
  ContextualTranslationsPlugin
} 