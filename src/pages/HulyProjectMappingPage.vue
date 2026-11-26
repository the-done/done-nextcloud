/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <div v-if="loaded" class="huly-mapping">
    <div class="huly-mapping__rows">
      <p v-if="hulyProjectsError" class="huly-mapping__hint">
        {{ contextTranslate("Could not load Huly projects. Check the connection settings and save.", context) }}
      </p>
      <div v-for="(row, index) in mapRows" :key="index" class="huly-mapping__row">
        <VDropdown
          class="huly-mapping__select"
          :value="hulySelected(row)"
          :options="hulyOptionsFor(row.identifier)"
          :placeholder="contextTranslate('Huly project', context)"
          :aria-label="contextTranslate('Huly project', context)"
          :disabled="busy"
          @input="(opt) => setHulyProject(row, opt)"
        />
        <VDropdown
          class="huly-mapping__select"
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

    <div class="huly-mapping__actions">
      <NcButton type="primary" :disabled="busy" @click="save">
        {{ contextTranslate("Save settings", context) }}
      </NcButton>
    </div>
  </div>
</template>

<script>
import { NcButton } from "@nextcloud/vue";

import { VDropdown } from "@/shared/components";

import { getHulyConfig, saveHulyConfig, getHulyProjects } from "@/entities/huly/api";
import { fetchProjects } from "@/entities/projects/api";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "HulyProjectMappingPage",
  components: {
    NcButton,
    VDropdown,
  },
  mixins: [contextualTranslationsMixin],
  data() {
    return {
      context: "settings",
      loaded: false,
      busy: false,
      hulyProjects: [],
      hulyProjectsError: false,
      doneProjects: [],
      mapRows: [],
    };
  },
  computed: {
    doneOptions() {
      return this.doneProjects.map((p) => ({ id: p.id, name: p.name || p.title }));
    },
  },
  async mounted() {
    await this.load();
  },
  methods: {
    applyProjectMap(projectMap) {
      this.mapRows = Object.entries(projectMap || {}).map(([identifier, projectId]) => ({
        identifier,
        // Done project ids are string hashes — keep them as strings.
        projectId: projectId == null ? null : String(projectId),
      }));
    },
    async load() {
      try {
        const { config } = await getHulyConfig();

        this.applyProjectMap(config?.project_map);
        this.loaded = true;
      } catch (e) {
        this.$notify({
          text: this.contextTranslate("Could not load settings", this.context),
          type: "error",
          duration: 5 * 1000,
        });

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
    buildProjectMap() {
      const projectMap = {};

      this.mapRows.forEach((row) => {
        if (row.identifier && row.projectId) {
          projectMap[row.identifier] = String(row.projectId);
        }
      });

      return projectMap;
    },
    async save() {
      this.busy = true;
      try {
        // Only the map is sent: the backend leaves the other settings as stored.
        const { config } = await saveHulyConfig({ project_map: this.buildProjectMap() });

        this.applyProjectMap(config?.project_map);
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
  },
};
</script>

<style scoped>
.huly-mapping {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 820px;
}

.huly-mapping__rows {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.huly-mapping__hint {
  color: var(--color-text-maxcontrast);
}

.huly-mapping__row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.huly-mapping__select {
  flex: 1 1 0;
  min-width: 260px;
}

.huly-mapping__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
</style>
