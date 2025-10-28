<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          :name="contextTranslate('User settings', context)"
          forceIconText
        >
          <template #icon>
            <Cog />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <div
          v-if="isLoading"
          class="size-full flex items-center justify-center"
        >
          <div class="flex flex-col items-center">
            <VLoader />
            <span>
              {{ contextTranslate("Loading settings...", context) }}
            </span>
          </div>
        </div>
        <template v-else>
          <div class="max-w-[600px] p-4 flex flex-col gap-6">
            <div
              v-for="(setting, settingId) in availableSettings"
              :key="settingId"
            >
              <!-- Checkbox settings -->
              <template
                v-if="isSettingType(setting.type, SETTING_TYPES.CHECKBOX)"
              >
                <label class="flex items-center cursor-pointer gap-2">
                  <input
                    v-model="settings[settingId]"
                    :name="`setting-${settingId}`"
                    type="checkbox"
                    class="h-auto! min-h-auto!"
                    @change="
                      handleSettingChange(settingId, settings[settingId])
                    "
                  />
                  <span class="font-medium cursor-pointer">
                    {{ contextTranslate(setting.title, context) }}
                  </span>
                </label>
                <div
                  v-if="setting.description"
                  class="text-(--color-text-maxcontrast)"
                >
                  {{ contextTranslate(setting.description, context) }}
                </div>
              </template>

              <!-- String settings -->
              <template
                v-else-if="isSettingType(setting.type, SETTING_TYPES.STRING)"
              >
                <label class="block font-medium text-(--color-text) mb-2">
                  {{ contextTranslate(setting.title, context) }}
                </label>
                <input
                  v-model="settings[settingId]"
                  :name="`setting-${settingId}`"
                  :placeholder="
                    contextTranslate(setting.placeholder, context) || ''
                  "
                  :class="inputClassNames"
                  type="text"
                />
                <div
                  v-if="setting.description"
                  class="text-(--color-text-maxcontrast)"
                >
                  {{ contextTranslate(setting.description, context) }}
                </div>
              </template>

              <!-- Number settings -->
              <template
                v-else-if="isSettingType(setting.type, SETTING_TYPES.NUMBER)"
              >
                <label class="block font-medium text-(--color-text) mb-2">
                  {{ contextTranslate(setting.title, context) }}
                </label>
                <input
                  v-model.number="settings[settingId]"
                  type="number"
                  :name="`setting-${settingId}`"
                  :class="inputClassNames"
                  :min="setting.min"
                  :max="setting.max"
                  :step="setting.step"
                />
                <div
                  v-if="setting.description"
                  class="text-(--color-text-maxcontrast) mt-2"
                >
                  {{ contextTranslate(setting.description, context) }}
                </div>
              </template>

              <!-- Select settings -->
              <template
                v-else-if="isSettingType(setting.type, SETTING_TYPES.SELECT)"
              >
                <VDropdown
                  :value="getSelectValue(settingId)"
                  :options="getSelectOptions(settingId)"
                  :label="contextTranslate(setting.title, context)"
                  :placeholder="setting.placeholder || ''"
                  @input="(value) => handleSettingChange(settingId, value)"
                />
                <div
                  v-if="setting.description"
                  class="text-(--color-text-maxcontrast) mt-2"
                >
                  {{ contextTranslate(setting.description, context) }}
                </div>
              </template>

              <!-- Textarea settings -->
              <template
                v-else-if="isSettingType(setting.type, SETTING_TYPES.TEXTAREA)"
              >
                <label class="block font-medium text-(--color-text) mb-2">
                  {{ contextTranslate(setting.title, context) }}
                </label>
                <textarea
                  v-model="settings[settingId]"
                  :name="`setting-${settingId}`"
                  :class="inputClassNames"
                  :rows="setting.rows || 3"
                  :placeholder="
                    contextTranslate(setting.placeholder, context) || ''
                  "
                />
                <div
                  v-if="setting.description"
                  class="text-(--color-text-maxcontrast) mt-2"
                >
                  {{ contextTranslate(setting.description, context) }}
                </div>
              </template>

              <!-- Unsupported types -->
              <template v-else>
                <div class="text-(--color-text-maxcontrast)">
                  {{
                    contextTranslate(
                      'Setting type "{type}" is not supported',
                      context,
                      { type: setting.type }
                    )
                  }}
                </div>
              </template>
            </div>
          </div>
          <div class="mt-8 p-4 border-t border-(--color-border)">
            <div class="max-w-[600px] flex justify-end">
              <NcButton
                type="primary"
                :disabled="isSaving"
                @click="saveSettings"
              >
                {{
                  isSaving
                    ? contextTranslate("Saving...", context)
                    : contextTranslate("Save", context)
                }}
              </NcButton>
            </div>
          </div>
        </template>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcButton } from "@nextcloud/vue";
import { t } from "@nextcloud/l10n";

import Cog from "vue-material-design-icons/Cog.vue";
import ContentSave from "vue-material-design-icons/ContentSave.vue";

import { VPage, VPageLayout, VPageContent } from "@/common/widgets/VPage";

import VLoader from "@/common/shared/components/VLoader/VLoader.vue";
import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VDropdown from "@/common/shared/components/VDropdown/VDropdown.vue";

import {
  fetchCustomSettingsList,
  fetchUserCustomSettings,
  saveUserSettings,
  fetchAvailableLanguages,
} from "@/common/entities/settings/api";

import { timeTrackingFormMixin } from "@/common/shared/mixins/timeTrackingFormMixin";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import {
  SETTING_TYPES,
  isSettingType as isSettingTypeFn,
  SETTING_IDS,
} from "@/common/entities/settings/constants";

export default {
  created() {
    // Preload translations for context synchronously
    this.preloadTranslationsForContexts([this.context]);
  },
  name: "UserSettingsPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    Cog,
    ContentSave,
    VPage,
    VPageLayout,
    VPageContent,
    VLoader,
    VToolbar,
    VDropdown,
  },
  mixins: [timeTrackingFormMixin, contextualTranslationsMixin],
  computed: {
    SETTING_TYPES() {
      return SETTING_TYPES;
    },
    SETTING_IDS() {
      return SETTING_IDS;
    },

    getSelectValue() {
      return (settingId) => {
        // Convert settingId to int
        settingId = parseInt(settingId);
        const value = this.settings[settingId];

        if (settingId === SETTING_IDS.USER_LANGUAGE) {
          // For language find object with correct value
          return (
            this.languageOptions.find((option) => option.value === value) ||
            null
          );
        }

        return value;
      };
    },
  },
  data() {
    return {
      context: "admin/users",
      settings: {},
      availableSettings: {},
      isSaving: false,
      isLoading: false,
      existingSettings: {},
      languageOptions: [], // now empty array, will be filled dynamically
      inputClassNames:
        "w-full px-3 py-2 rounded-(--border-radius) bg-(--color-background) text-(--color-text) font-base border border-(--color-border)",
    };
  },
  methods: {
    t,
    getSelectOptions(settingId) {
      settingId = parseInt(settingId);

      if (settingId === SETTING_IDS.USER_LANGUAGE) {
        return this.languageOptions;
      }

      return this.availableSettings[settingId]?.options || [];
    },

    isSettingType(type, expectedType) {
      return isSettingTypeFn(type, expectedType);
    },

    handleSettingChange(settingId, value) {
      // Convert settingId to int
      settingId = parseInt(settingId);

      // Update value in settings
      let settingValue = value;

      // If it's an object (e.g., from VDropdown), extract value
      if (
        typeof value === "object" &&
        value !== null &&
        value.value !== undefined
      ) {
        settingValue = value.value;
      }

      this.$set(this.settings, settingId, settingValue);

      // If time caching setting changed, update cache immediately
      if (settingId === SETTING_IDS.CACHE_TIME_SETTING) {
        this.updateTimeCachingSetting(settingValue);
      }
      // If language changed, apply it in Nextcloud
      if (settingId === SETTING_IDS.USER_LANGUAGE) {
        this.changeUserLanguage(settingValue);
      }
    },
    async loadLanguages() {
      // Get languages list through API
      try {
        const response = await fetchAvailableLanguages();
        // Convert options format for VDropdown (label -> name)
        this.languageOptions = response.data.map((lang) => ({
          value: lang.value,
          name: lang.label,
        }));
      } catch (e) {
        console.error("Language loading error:", e);
        // fallback: English only
        this.languageOptions = [{ value: "en", name: "English" }];
      }
    },

    async changeUserLanguage(lang) {
      // TODO: Use api folder
      try {
        // Use our API for language change
        const response = await fetch(
          "/index.php/apps/done/ajax/changeUserLanguage",
          {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `language=${encodeURIComponent(lang)}`,
          }
        );

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
          this.$notify({
            type: "success",
            title: this.contextTranslate("Language changed", this.context),
            text: this.contextTranslate(
              "The interface language will be changed after refreshing the page.",
              this.context
            ),
          });
          setTimeout(() => window.location.reload(), 1000); // auto-refresh after 1 sec
        } else {
          throw new Error(
            result.error || this.contextTranslate("Unknown error", this.context)
          );
        }
      } catch (e) {
        console.error("Language change error:", e);
        this.$notify({
          type: "error",
          title: this.contextTranslate("Error", this.context),
          text: "Failed to change interface language:" + e.message,
        });
      }
    },

    async saveSettings() {
      this.isSaving = true;

      try {
        // Prepare settings array for sending
        const settingsToSave = Object.keys(this.settings).map((settingId) => {
          const setting = this.availableSettings[settingId];
          let value;

          // Convert value to correct format depending on setting type
          if (isSettingTypeFn(setting.type, SETTING_TYPES.CHECKBOX)) {
            value = this.settings[settingId] ? "1" : "0";
          } else if (isSettingTypeFn(setting.type, SETTING_TYPES.NUMBER)) {
            value = String(this.settings[settingId] || 0);
          } else {
            value = String(this.settings[settingId] || "");
          }

          const slug = this.existingSettings[settingId]?.slug || null;

          return {
            setting_id: settingId,
            value: value,
            type_id: setting.type,
            slug: slug,
          };
        });

        // Send all settings with one request
        const response = await saveUserSettings(settingsToSave);

        // Process results and update existingSettings
        let successCount = 0;
        let errorCount = 0;
        const errors = [];

        if (response.data && response.data.results) {
          response.data.results.forEach((result) => {
            if (result.success && result.slug) {
              successCount++;
              const settingId = result.setting_id;
              if (!this.existingSettings[settingId]) {
                this.$set(this.existingSettings, settingId, {});
              }
              this.$set(this.existingSettings[settingId], "slug", result.slug);

              // Update value in existingSettings
              const setting = this.availableSettings[settingId];
              if (isSettingTypeFn(setting.type, SETTING_TYPES.CHECKBOX)) {
                this.$set(
                  this.existingSettings[settingId],
                  "value",
                  this.settings[settingId] ? "1" : "0"
                );
              } else {
                this.$set(
                  this.existingSettings[settingId],
                  "value",
                  String(this.settings[settingId] || "")
                );
              }

              // If time caching setting changed, update cache
              if (settingId === SETTING_IDS.CACHE_TIME_SETTING) {
                this.updateTimeCachingSetting(this.settings[settingId]);
              }
            } else {
              errorCount++;
              const settingTitle =
                this.availableSettings[result.setting_id]?.title ||
                result.setting_id;
              errors.push(`${settingTitle}: ${result.message}`);
            }
          });
        }

        // Show notification depending on results
        if (errorCount === 0) {
          this.$notify({
            type: "success",
            title: this.contextTranslate("Settings saved", this.context),
            text: this.contextTranslate(
              "All settings have been saved successfully.",
              this.context
            ),
          });
        } else if (successCount === 0) {
          this.$notify({
            type: "error",
            title: this.contextTranslate("Saving error", this.context),
            text: this.contextTranslate(
              "Failed to save any settings. Errors: {errors}",
              false,
              [errors.join(", ")]
            ),
          });
        } else {
          this.$notify({
            type: "warning",
            title: this.contextTranslate("Partially saved", this.context),
            text: `Saved ${successCount} out of ${
              successCount + errorCount
            } settings. Errors: ${errors.join(", ")}`,
          });
        }
      } catch (error) {
        console.error("Settings saving error:", error);
        this.$notify({
          type: "error",
          title: this.contextTranslate("Error", this.context),
          text: this.contextTranslate(
            "Failed to save settings. Try again.",
            this.context
          ),
        });
      } finally {
        this.isSaving = false;
      }
    },

    async loadAvailableSettings() {
      try {
        const { data } = await fetchCustomSettingsList();
        this.availableSettings = data;

        // Initialize settings object for all available settings
        Object.keys(data).forEach((settingId) => {
          this.$set(this.settings, settingId, false);
        });
      } catch (error) {
        console.error("Error loading settings list:", error);
        this.$notify({
          type: "error",
          title: this.contextTranslate("Error", this.context),
          text: this.contextTranslate(
            "Failed to load settings list.",
            this.context
          ),
        });
      }
    },

    async loadUserSettings() {
      try {
        const { data } = await fetchUserCustomSettings({});

        // Convert settings array to object for convenience
        const settingsMap = {};
        data.forEach((setting) => {
          settingsMap[setting.setting_id] = {
            slug: setting.slug || setting.id, // Use slug if available, otherwise id
            value: setting.value,
          };
        });

        this.existingSettings = settingsMap;

        // Set settings values based on loaded data
        Object.keys(this.availableSettings).forEach((settingId) => {
          if (settingsMap[settingId]) {
            const setting = this.availableSettings[settingId];
            let savedValue = settingsMap[settingId].value;

            // Convert value to correct type depending on setting type
            if (isSettingTypeFn(setting.type, SETTING_TYPES.CHECKBOX)) {
              // Parse JSON value if it's in that format
              if (
                typeof savedValue === "string" &&
                savedValue.startsWith('"') &&
                savedValue.endsWith('"')
              ) {
                try {
                  savedValue = JSON.parse(savedValue);
                } catch (e) {
                  console.warn(
                    "JSON parsing error for setting:",
                    settingId,
                    savedValue
                  );
                }
              }

              const booleanValue =
                savedValue === "1" || savedValue === 1 || savedValue === true;
              this.$set(this.settings, settingId, booleanValue);
            } else if (isSettingTypeFn(setting.type, SETTING_TYPES.NUMBER)) {
              // For numeric values
              let numericValue = savedValue;
              if (
                typeof savedValue === "string" &&
                savedValue.startsWith('"') &&
                savedValue.endsWith('"')
              ) {
                try {
                  numericValue = JSON.parse(savedValue);
                } catch (e) {
                  console.warn(
                    "JSON parsing error for setting:",
                    settingId,
                    savedValue
                  );
                }
              }
              this.$set(this.settings, settingId, Number(numericValue) || 0);
            } else {
              // For string and other types
              let stringValue = savedValue;
              if (
                typeof savedValue === "string" &&
                savedValue.startsWith('"') &&
                savedValue.endsWith('"')
              ) {
                try {
                  stringValue = JSON.parse(savedValue);
                } catch (e) {
                  console.warn(
                    "JSON parsing error for setting:",
                    settingId,
                    savedValue
                  );
                }
              }
              this.$set(this.settings, settingId, stringValue || "");
            }
          }
        });
      } catch (error) {
        console.error("Error loading user settings:", error);
        this.$notify({
          type: "error",
          title: "Error",
          text: "Failed to load your settings.",
        });
      }
    },

    async loadSettings() {
      this.isLoading = true;

      try {
        // First load available settings list
        await this.loadAvailableSettings();

        // Then load saved user settings
        await this.loadUserSettings();
      } catch (error) {
        console.error("Error loading settings:", error);
      } finally {
        this.isLoading = false;
      }
    },
  },
  async mounted() {
    this.loadSettings();
    await this.loadLanguages();
  },
};
</script>
