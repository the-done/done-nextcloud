/**
 * Setting type constants
 * Correspond to constants in CustomSettings_Model
 */
export const SETTING_TYPES = {
  CHECKBOX: 1,
  STRING: 2,
  NUMBER: 3,
  SELECT: 4,
  TEXTAREA: 5,
};

/**
 * Setting ID constants
 * Correspond to constants in CustomSettings_Model
 */
export const SETTING_IDS = {
  CACHE_TIME_SETTING: 1,
  HIDE_EMPTY_FIELDS_IN_PREVIEW: 2,
  USER_LANGUAGE: 3,
};

/**
 * Get setting type name by ID
 */
export const getSettingTypeName = (typeId) => {
  switch (typeId) {
    case SETTING_TYPES.CHECKBOX:
      return 'checkbox';
    case SETTING_TYPES.STRING:
      return 'string';
    case SETTING_TYPES.NUMBER:
      return 'number';
    case SETTING_TYPES.SELECT:
      return 'select';
    case SETTING_TYPES.TEXTAREA:
      return 'textarea';
    default:
      return 'unknown';
  }
};

/**
 * Check setting type
 */
export const isSettingType = (typeId, expectedType) => {
  return typeId === expectedType;
}; 