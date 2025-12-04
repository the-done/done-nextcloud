/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { getCustomTranslation } from '@/common/shared/lib/customTranslations';

export const customTranslationsMixin = {
  methods: {
    /**
     * Get custom translation for current component
     * @param {string} key - Translation key
     * @returns {string} - Translated text
     */
    ct(key) {
      return getCustomTranslation(this.$options.name, key);
    },
    
    /**
     * Get custom translation for specified context
     * @param {string} context - Context
     * @param {string} key - Translation key
     * @returns {string} - Translated text
     */
    ctContext(context, key) {
      return getCustomTranslation(context, key);
    }
  }
}; 