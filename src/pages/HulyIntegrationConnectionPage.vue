/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div v-if="loaded" class="huly-connection">
    <NcCheckboxRadioSwitch :checked.sync="form.enabled" type="switch" :disabled="busy">
      {{ contextTranslate("Enable Huly integration", context) }}
    </NcCheckboxRadioSwitch>

    <NcTextField
      :value.sync="form.base_url"
      :label="contextTranslate('Huly base URL', context)"
      :disabled="busy"
    />
    <NcTextField
      :value.sync="form.token"
      type="password"
      autocomplete="new-password"
      :label="contextTranslate('Huly service token', context)"
      :placeholder="hasToken ? secretMask : ''"
      :disabled="busy"
    />
    <NcTextField
      :value.sync="form.workspace"
      :label="contextTranslate('Huly workspace', context)"
      :disabled="busy"
    />

    <div class="huly-connection__field">
      <VDropdown
        v-model="selectedEmailField"
        :options="emailFieldOptions"
        :label="contextTranslate('Employee email field', context)"
        :placeholder="contextTranslate('Not selected', context)"
        :disabled="busy"
      />
    </div>

    <div class="huly-connection__field">
      <NcCheckboxRadioSwitch :checked.sync="form.auto_sync" type="switch" :disabled="busy">
        {{ contextTranslate("Automatic sync of Huly time reports", context) }}
      </NcCheckboxRadioSwitch>
      <VDatePicker
        :value="autoSyncFromDate"
        :label="contextTranslate('Sync reports dated from', context)"
        :clearable="false"
        :disabled="busy || !form.auto_sync"
        @input="(d) => (form.auto_sync_from = toIsoDate(d))"
      />
      <p class="huly-connection__status">{{ autoSyncStatus }}</p>
    </div>

    <div class="huly-connection__actions">
      <NcButton type="primary" :disabled="busy" @click="save">
        {{ contextTranslate("Save settings", context) }}
      </NcButton>
      <NcButton type="secondary" :disabled="busy" @click="testConnection">
        {{ contextTranslate("Test connection", context) }}
      </NcButton>
    </div>

    <NcNoteCard v-if="testResult" :type="testResult.ok ? 'success' : 'error'">
      {{ testResultMessage }}
    </NcNoteCard>
  </div>
</template>

<script>
import { NcNoteCard, NcButton, NcTextField, NcCheckboxRadioSwitch } from "@nextcloud/vue";

import { VDropdown, VDatePicker } from "@/shared/components";

import { getHulyConfig, saveHulyConfig, testHulyConnection } from "@/entities/huly/api";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";
import { toIsoDate, fromIsoDate } from "@/shared/lib/isoDate";

export default {
  name: "HulyIntegrationConnectionPage",
  components: {
    NcButton,
    NcTextField,
    NcNoteCard,
    NcCheckboxRadioSwitch,
    VDropdown,
    VDatePicker,
  },
  mixins: [contextualTranslationsMixin],
  data() {
    return {
      context: "settings",
      loaded: false,
      busy: false,
      secretMask: "••••••",
      hasToken: false,
      emailFields: [],
      testResult: null,
      autoSyncState: {
        last_run: null,
        last_created: 0,
        last_skipped: 0,
        last_error: "",
      },
      form: {
        enabled: false,
        base_url: "",
        token: "",
        workspace: "",
        email_field_id: "",
        auto_sync: false,
        auto_sync_from: "",
      },
    };
  },
  computed: {
    autoSyncFromDate() {
      return fromIsoDate(this.form.auto_sync_from);
    },
    autoSyncStatus() {
      const {
        last_run: lastRun,
        last_created: lastCreated,
        last_skipped: lastSkipped,
        last_error: lastError,
      } = this.autoSyncState;

      if (!lastRun) {
        return this.contextTranslate("The sync has not run yet.", this.context);
      }

      const date = new Date(lastRun).toLocaleString();

      if (lastError) {
        return this.contextTranslate("Last run {date} failed: {error}", this.context, { date, error: lastError });
      }

      return this.contextTranslate("Last run {date}, records created: {count}, skipped: {skipped}", this.context, {
        date,
        count: String(lastCreated || 0),
        skipped: String(lastSkipped || 0),
      });
    },
    testResultMessage() {
      if (this.testResult?.message) {
        return this.testResult.message;
      }

      return this.testResult?.ok
        ? this.contextTranslate("Connection successful", this.context)
        : this.contextTranslate("Connection failed", this.context);
    },
    emailFieldOptions() {
      return this.emailFields.map((f) => ({ id: String(f.id), name: f.title }));
    },
    selectedEmailField: {
      get() {
        return (
          this.emailFieldOptions.find(
            (o) => String(o.id) === String(this.form.email_field_id)
          ) || null
        );
      },
      set(option) {
        this.form.email_field_id = option ? String(option.id) : "";
      },
    },
  },
  async mounted() {
    await this.load();
  },
  methods: {
    toIsoDate,
    applyConfig(config) {
      this.form.enabled = config.enabled === true;
      this.form.base_url = config.base_url || "";
      this.form.workspace = config.workspace || "";
      this.form.email_field_id = config.email_field_id ? String(config.email_field_id) : "";
      // Secrets are never returned; leave the inputs empty (empty = keep stored value).
      this.form.token = "";
      this.hasToken = config.has_token === true;
      this.form.auto_sync = config.auto_sync === true;
      this.form.auto_sync_from = config.auto_sync_from || "";
      this.autoSyncState = {
        last_run: config.auto_sync_last_run || null,
        last_created: Number(config.auto_sync_last_created) || 0,
        last_skipped: Number(config.auto_sync_last_skipped) || 0,
        last_error: config.auto_sync_last_error || "",
      };
    },
    async load() {
      try {
        const { config, email_fields: emailFields } = await getHulyConfig();

        this.emailFields = emailFields || [];
        this.applyConfig(config || {});
        this.loaded = true;
      } catch (e) {
        this.$notify({
          text: this.contextTranslate("Could not load settings", this.context),
          type: "error",
          duration: 5 * 1000,
        });
      }
    },
    buildPayload() {
      // The project map is saved on the "Project mapping" tab and is left out here,
      // so the backend keeps the stored map (it writes only the keys it receives).
      const payload = {
        enabled: this.form.enabled,
        base_url: this.form.base_url,
        workspace: this.form.workspace,
        email_field_id: this.form.email_field_id,
        auto_sync: this.form.auto_sync,
      };

      // An empty start date is left out; the backend defaults it to today on enable.
      if (this.form.auto_sync_from) {
        payload.auto_sync_from = this.form.auto_sync_from;
      }

      // The token is sent only when the admin typed a new value; omitting it
      // keeps the stored one (the backend overwrites only on a non-empty value).
      if (this.form.token) {
        payload.token = this.form.token;
      }

      return payload;
    },
    async save() {
      this.busy = true;
      try {
        const { config } = await saveHulyConfig(this.buildPayload());

        this.applyConfig(config || {});
        this.$notify({
          text: this.contextTranslate("Settings saved", this.context),
          type: "success",
          duration: 3 * 1000,
        });
      } catch (e) {
        this.$notify({
          text: e?.response?.data?.message || this.contextTranslate("Could not save settings", this.context),
          type: "error",
          duration: 5 * 1000,
        });
      } finally {
        this.busy = false;
      }
    },
    async testConnection() {
      this.busy = true;
      this.testResult = null;
      try {
        const config = { baseUrl: this.form.base_url, workspace: this.form.workspace };

        if (this.form.token) {
          config.token = this.form.token;
        }
        this.testResult = await testHulyConnection(config);
      } catch (e) {
        this.testResult = { ok: false, message: e?.response?.data?.message || "" };
      } finally {
        this.busy = false;
      }
    },
  },
};
</script>

<style scoped>
.huly-connection {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 820px;
}

.huly-connection__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.huly-connection__status {
  color: var(--color-text-maxcontrast);
}

.huly-connection__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
</style>
