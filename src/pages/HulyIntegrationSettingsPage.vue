/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage>
    <VToolbar>
      <NcBreadcrumbs v-if="breadcrumbs?.length > 0">
        <NcBreadcrumb
          v-for="(item, index) in breadcrumbs"
          :key="index"
          :name="contextTranslate(item.title, context)"
          :to="item.path"
          :forceIconText="index === 0"
        />
      </NcBreadcrumbs>
    </VToolbar>

    <VScrollArea class="huly-settings__content">
      <div class="huly-settings__header">
        <h1 class="huly-settings__title">{{ contextTranslate("Huly integration", context) }}</h1>
        <p class="huly-settings__description">
          {{ contextTranslate("Suggest Huly tasks assigned to an employee when they log time.", context) }}
        </p>
      </div>

      <div v-if="loaded" class="huly-settings__section">
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

        <div class="huly-settings__field">
          <VDropdown
            v-model="selectedEmailField"
            :options="emailFieldOptions"
            :label="contextTranslate('Employee email field', context)"
            :placeholder="contextTranslate('Not selected', context)"
            :disabled="busy"
          />
        </div>

        <div class="huly-settings__field">
          <h2 class="huly-settings__subtitle">{{ contextTranslate("Project mapping", context) }}</h2>
          <p v-if="hulyProjectsError" class="huly-settings__hint">
            {{ contextTranslate("Could not load Huly projects. Check the connection settings and save.", context) }}
          </p>
          <div v-for="(row, index) in mapRows" :key="index" class="huly-settings__map-row">
            <VDropdown
              class="huly-settings__map-select"
              :value="hulySelected(row)"
              :options="hulyOptionsFor(row.identifier)"
              :placeholder="contextTranslate('Huly project', context)"
              :aria-label="contextTranslate('Huly project', context)"
              :disabled="busy"
              @input="(opt) => setHulyProject(row, opt)"
            />
            <VDropdown
              class="huly-settings__map-select"
              :value="doneSelected(row)"
              :options="doneOptions"
              :placeholder="contextTranslate('Done project', context)"
              :aria-label="contextTranslate('Done project', context)"
              :disabled="busy"
              @input="(opt) => setDoneProject(row, opt)"
            />
            <NcButton type="tertiary" :disabled="busy" @click="removeRow(index)">
              {{ contextTranslate("Remove mapping", context) }}
            </NcButton>
          </div>
          <NcButton type="secondary" :disabled="busy" @click="addRow">
            {{ contextTranslate("Add mapping", context) }}
          </NcButton>
        </div>

        <div class="huly-settings__actions">
          <NcButton type="primary" :disabled="busy" @click="save">
            {{ contextTranslate("Save settings", context) }}
          </NcButton>
          <NcButton type="secondary" :disabled="busy" @click="testConnection">
            {{ contextTranslate("Test connection", context) }}
          </NcButton>
        </div>

        <NcNoteCard v-if="testResult" :type="testResult.ok ? 'success' : 'error'">
          {{ testResult.message || (testResult.ok ? contextTranslate("Connection successful", context) : contextTranslate("Connection failed", context)) }}
        </NcNoteCard>
      </div>
    </VScrollArea>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcNoteCard, NcButton, NcTextField, NcCheckboxRadioSwitch } from "@nextcloud/vue";

import { VPage } from "@/widgets";
import { VToolbar, VScrollArea, VDropdown } from "@/shared/components";

import { getHulyConfig, saveHulyConfig, testHulyConnection, getHulyProjects } from "@/entities/huly/api";
import { fetchProjects } from "@/entities/projects/api";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "HulyIntegrationSettingsPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    NcTextField,
    NcNoteCard,
    NcCheckboxRadioSwitch,
    VPage,
    VToolbar,
    VScrollArea,
    VDropdown,
  },
  mixins: [contextualTranslationsMixin],
  props: {
    breadcrumbs: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      context: "settings",
      loaded: false,
      busy: false,
      secretMask: "••••••",
      hasToken: false,
      emailFields: [],
      hulyProjects: [],
      hulyProjectsError: false,
      doneProjects: [],
      mapRows: [],
      testResult: null,
      form: {
        enabled: false,
        base_url: "",
        token: "",
        workspace: "",
        email_field_id: "",
      },
    };
  },
  computed: {
    emailFieldOptions() {
      return this.emailFields.map((f) => ({ id: String(f.id), name: f.title }));
    },
    doneOptions() {
      return this.doneProjects.map((p) => ({ id: p.id, name: p.name || p.title }));
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
    applyConfig(config) {
      this.form.enabled = config.enabled === true;
      this.form.base_url = config.base_url || "";
      this.form.workspace = config.workspace || "";
      this.form.email_field_id = config.email_field_id ? String(config.email_field_id) : "";
      // Secrets are never returned; leave the inputs empty (empty = keep stored value).
      this.form.token = "";
      this.hasToken = config.has_token === true;
      this.mapRows = Object.entries(config.project_map || {}).map(([identifier, projectId]) => ({
        identifier,
        // Done project ids are string hashes — keep them as strings.
        projectId: projectId == null ? null : String(projectId),
      }));
    },
    async load() {
      try {
        const { config, email_fields: emailFields } = await getHulyConfig();

        this.emailFields = emailFields || [];
        this.applyConfig(config || {});
        this.loaded = true;
      } catch (e) {
        this.$notify({ text: this.contextTranslate("Could not load settings", this.context), type: "error", duration: 5 * 1000 });

        return;
      }

      try {
        const { data } = await fetchProjects();

        this.doneProjects = Array.isArray(data) ? data.filter((p) => p.id !== "separator") : [];
      } catch (e) {
        this.doneProjects = [];
      }

      await this.loadHulyProjects();
    },
    async loadHulyProjects() {
      try {
        const data = await getHulyProjects();

        this.hulyProjects = data?.projects || [];
        this.hulyProjectsError = Boolean(data?.error);
      } catch (e) {
        this.hulyProjects = [];
        this.hulyProjectsError = true;
      }
    },
    hulyProjectOptions(selected) {
      // Keep an already-mapped identifier selectable even if Huly did not return it.
      if (selected && !this.hulyProjects.some((p) => p.identifier === selected)) {
        return [...this.hulyProjects, { identifier: selected, name: selected }];
      }

      return this.hulyProjects;
    },
    hulyOptionsFor(identifier) {
      return this.hulyProjectOptions(identifier).map((p) => ({
        id: p.identifier,
        name: `${p.name} (${p.identifier})`,
      }));
    },
    hulySelected(row) {
      return (
        this.hulyOptionsFor(row.identifier).find(
          (o) => String(o.id) === String(row.identifier)
        ) || null
      );
    },
    setHulyProject(row, opt) {
      row.identifier = opt ? String(opt.id) : "";
    },
    doneSelected(row) {
      return (
        this.doneOptions.find((o) => String(o.id) === String(row.projectId)) || null
      );
    },
    setDoneProject(row, opt) {
      row.projectId = opt ? opt.id : null;
    },
    addRow() {
      this.mapRows.push({ identifier: "", projectId: null });
    },
    removeRow(index) {
      this.mapRows.splice(index, 1);
    },
    buildPayload() {
      const projectMap = {};

      this.mapRows.forEach((row) => {
        if (row.identifier && row.projectId) {
          projectMap[row.identifier] = String(row.projectId);
        }
      });

      const payload = {
        enabled: this.form.enabled,
        base_url: this.form.base_url,
        workspace: this.form.workspace,
        email_field_id: this.form.email_field_id,
        project_map: projectMap,
      };

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
        this.$notify({ text: this.contextTranslate("Settings saved", this.context), type: "success", duration: 3 * 1000 });
        await this.loadHulyProjects();
      } catch (e) {
        this.$notify({ text: e?.response?.data?.message || this.contextTranslate("Could not save settings", this.context), type: "error", duration: 5 * 1000 });
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
.huly-settings__content {
  padding: 24px;
}

.huly-settings__header {
  margin-bottom: 24px;
}

.huly-settings__title {
  font-size: 24px;
  font-weight: 600;
}

.huly-settings__description,
.huly-settings__hint {
  color: var(--color-text-maxcontrast);
}

.huly-settings__subtitle {
  font-size: 16px;
  font-weight: 600;
}

.huly-settings__section {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 820px;
}

.huly-settings__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.huly-settings__map-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.huly-settings__map-select {
  flex: 1 1 0;
  min-width: 260px;
}

.huly-settings__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
</style>
