import { fetchUserProjectsForReport } from "@/common/entities/users/api";
import { fetchUserCustomSettings } from "@/common/entities/settings/api";
import { minutesToHours } from "@/common/shared/lib/helpers";
import { clearTimeCache } from "@/common/shared/lib/timeCacheUtils";
import {
  LOCALSTORAGE_LAST_REPORT_PROJECT_ID,
  LOCALSTORAGE_LAST_REPORT_MINUTES,
} from "@/common/shared/lib/constants";
import { SETTING_IDS } from "@/common/entities/settings/constants";

const DATE_FIELDS = ["date"];

export const timeTrackingFormMixin = {
  data: () => ({
    projectOptions: [],
    formValues: {
      date: new Date(), // Report date *
      project_id: null, // Project *
      task_link: "", // Task link *
      description: "", // Description *
      comment: "", // Comment *
      hours: "", // Number of hours (will be transformed to minutes sum)
      minutes: "", // Number of minutes (will be transformed to minutes sum) *
      is_downtime: false, // Downtime (Disables description and task link)
    },
    userSettings: {}, // User settings cache
  }),
  methods: {
    /**
     * Saves last form values to localStorage with time caching setting check
     * @param {Object} payload - Object with data to save
     * @param {string} payload.project_id - Project ID
     * @param {string|number} payload.minutes - Number of minutes
     */
    async saveLastFormValues({ project_id, minutes }) {
      try {
        // Check time caching setting
        const shouldCacheTime = await this.checkTimeCachingSetting();
        
        if (!shouldCacheTime) {
          // If setting is disabled, clear saved values
          clearTimeCache();
          return;
        }
        
        // Save values only if setting is enabled
        localStorage.setItem(LOCALSTORAGE_LAST_REPORT_PROJECT_ID, project_id);
        localStorage.setItem(LOCALSTORAGE_LAST_REPORT_MINUTES, minutes);
      } catch (e) {
        console.log('Error saving last form values:', e);
      }
    },
    
    /**
     * Checks user's time caching setting
     * @returns {Promise<boolean>} true if caching is enabled, false if disabled
     */
    async checkTimeCachingSetting() {
      try {
        // Check settings cache
        if (this.userSettings[SETTING_IDS.CACHE_TIME_SETTING] !== undefined) {
          return this.userSettings[SETTING_IDS.CACHE_TIME_SETTING];
        }
        
        // Load user settings
        const { data } = await fetchUserCustomSettings({});
        
        // Find time caching setting
        const cacheTimeSetting = data.find(setting => 
          setting.setting_id === SETTING_IDS.CACHE_TIME_SETTING
        );
        
        // Parse setting value
        let settingValue = false;
        if (cacheTimeSetting) {
          let savedValue = cacheTimeSetting.value;
          
          // Parse JSON value if it's in that format
          if (typeof savedValue === 'string' && savedValue.startsWith('"') && savedValue.endsWith('"')) {
            try {
              savedValue = JSON.parse(savedValue);
            } catch (e) {
              console.warn('JSON parsing error for time caching setting:', savedValue);
            }
          }
          
          settingValue = savedValue === '1' || savedValue === 1 || savedValue === true;
        }
        
        // Cache result
        this.$set(this.userSettings, SETTING_IDS.CACHE_TIME_SETTING, settingValue);
        
        return settingValue;
      } catch (error) {
        console.error('Error checking time caching setting:', error);
        // In case of error, don't cache time
        return false;
      }
    },
    
    /**
     * Loads cached time and project values with setting check
     */
    async loadCachedValues() {
      try {
        // Check time caching setting
        const shouldCacheTime = await this.checkTimeCachingSetting();
        
        if (!shouldCacheTime) {
          // If setting is disabled, don't load cached values
          return;
        }
        
        const localMinutes = localStorage.getItem(LOCALSTORAGE_LAST_REPORT_MINUTES);
        const localProjectId = localStorage.getItem(LOCALSTORAGE_LAST_REPORT_PROJECT_ID);
        
        if (localMinutes) {
          this.setFormValues({ minutes: localMinutes });
        }
        
        if (localProjectId) {
          this.setFormValues({ project_id: localProjectId });
        }
      } catch (e) {
        console.log('Error loading cached values:', e);
      }
    },
    
    /**
     * Clears time cache and resets settings cache
     */
    clearTimeCache() {
      try {
        clearTimeCache();
        // Clear settings cache
        this.$set(this.userSettings, SETTING_IDS.CACHE_TIME_SETTING, undefined);
      } catch (e) {
        console.log('Error clearing time cache:', e);
      }
    },
    
    /**
     * Updates time caching setting and clears cache if necessary
     * @param {boolean} newValue - New setting value
     */
    async updateTimeCachingSetting(newValue) {
      try {
        // Update settings cache
        this.$set(this.userSettings, SETTING_IDS.CACHE_TIME_SETTING, newValue);
        
        // If setting is disabled, clear cache
        if (!newValue) {
          this.clearTimeCache();
        }
      } catch (e) {
        console.log('Error updating time caching setting:', e);
      }
    },
    setFormValues(payload) {
      const payloadKeys = Object.keys(payload);
      const availableKeys = Object.keys(this.formValues);

      const data = payloadKeys.reduce((accum, key) => {
        const value = payload[key];

        if (availableKeys.includes(key) === false) {
          return accum;
        }

        if (DATE_FIELDS.includes(key) === true) {
          return {
            ...accum,
            [key]: value ? new Date(value) : new Date(),
          };
        }

        if (key === "project_id") {
          const objectValue = this.projectOptions.find(
            (item) => String(item.id) === String(value)
          );

          return {
            ...accum,
            [key]: objectValue || null,
          };
        }

        if (key === "minutes") {
          const { minutes, hours } = minutesToHours(value);

          return {
            ...accum,
            hours,
            minutes,
          };
        }

        return {
          ...accum,
          [key]: value,
        };
      }, {});

      this.formValues = {
        ...this.formValues,
        ...data,
      };
    },
    async handleFetchDictionaries() {
      const promises = [fetchUserProjectsForReport()];

      const [{ data: projectOptions }] = await Promise.all(promises);

      this.projectOptions = projectOptions;
    },
  },
};
