/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
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
        >
          <template v-if="item.icon" #icon>
            <component :is="item.icon" v-if="item.icon" />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>

    <VScrollArea class="pro-settings__content">
      <div class="pro-settings__header">
        <h1 class="pro-settings__title">{{ contextTranslate("Pro version", context) }}</h1>
        <p class="pro-settings__description">
          {{ contextTranslate("Activate the paid modules with your license key.", context) }}
        </p>
        <p v-if="status && status.service_url" class="pro-settings__service-link">
          <a :href="status.service_url" target="_blank" rel="noopener noreferrer">
            {{ contextTranslate("Open the license service", context) }} ↗
          </a>
        </p>
      </div>

      <NcNoteCard
        v-if="status && status.activated && status.reachable === false"
        type="warning"
        class="pro-settings__notice"
      >
        {{ contextTranslate("Could not reach the license service. Update checks are unavailable right now; installed modules keep working.", context) }}
      </NcNoteCard>

      <NcNoteCard
        v-if="status && status.activated && !status.modules_installed"
        type="warning"
        class="pro-settings__notice"
      >
        {{ contextTranslate("The license is active, but the paid modules are not installed. Use Install to finish.", context) }}
      </NcNoteCard>

      <div class="pro-settings__section">
        <div class="pro-settings__status">
          <span v-if="status && status.activated" class="pro-settings__badge pro-settings__badge--active">
            {{ contextTranslate("Activated", context) }}
          </span>
          <span v-else class="pro-settings__badge">
            {{ contextTranslate("Not activated", context) }}
          </span>
          <span
            v-if="status && status.activated && status.installed_version"
            class="pro-settings__version"
          >
            v{{ status.installed_version }}
          </span>
        </div>

        <!-- License details -->
        <dl v-if="status && status.activated" class="pro-settings__info">
          <div v-if="status.license_status" class="pro-settings__info-row">
            <dt>{{ contextTranslate("License status", context) }}</dt>
            <dd>{{ contextTranslate(licenseStatusLabel(status.license_status), context) }}</dd>
          </div>
          <div v-if="status.plan" class="pro-settings__info-row">
            <dt>{{ contextTranslate("Plan", context) }}</dt>
            <dd>{{ contextTranslate(planLabel(status.plan), context) }}</dd>
          </div>
          <div v-if="status.key_masked" class="pro-settings__info-row">
            <dt>{{ contextTranslate("License key", context) }}</dt>
            <dd>{{ status.key_masked }}</dd>
          </div>
          <div v-if="status.updates_until" class="pro-settings__info-row">
            <dt>{{ contextTranslate("Updates until", context) }}</dt>
            <dd>{{ formatDate(status.updates_until) }}</dd>
          </div>
          <div class="pro-settings__info-row">
            <dt>{{ contextTranslate("Installed version", context) }}</dt>
            <dd>{{ status.installed_version ? "v" + status.installed_version : contextTranslate("Not installed", context) }}</dd>
          </div>
          <div class="pro-settings__info-row">
            <dt>{{ contextTranslate("Installed modules", context) }}</dt>
            <dd>
              <template v-if="status.installed_modules && status.installed_modules.length">
                <span v-for="m in status.installed_modules" :key="m" class="pro-settings__module-chip">{{ m }}</span>
              </template>
              <span v-else>{{ contextTranslate("None", context) }}</span>
            </dd>
          </div>
          <div v-if="status.activated_at" class="pro-settings__info-row">
            <dt>{{ contextTranslate("Activated at", context) }}</dt>
            <dd>{{ formatDate(status.activated_at) }}</dd>
          </div>
          <div v-if="status.last_heartbeat_at" class="pro-settings__info-row">
            <dt>{{ contextTranslate("Last checked", context) }}</dt>
            <dd>{{ formatDate(status.last_heartbeat_at) }}</dd>
          </div>
        </dl>

        <!-- First-time activation -->
        <div v-if="status && !status.activated" class="pro-settings__key-row">
          <NcTextField
            class="pro-settings__key-field"
            :value.sync="licenseKey"
            :label="contextTranslate('License key', context)"
            :disabled="busy"
          />
          <NcButton class="pro-settings__activate-btn" type="primary" :disabled="busy || !licenseKey" @click="activate">
            {{ contextTranslate("Activate", context) }}
          </NcButton>
        </div>

        <!-- Per-operation actions (activated) -->
        <div v-if="status && status.activated" class="pro-settings__actions">
          <NcButton
            v-if="!status.modules_installed"
            type="primary"
            :disabled="busy || !status.can_install"
            @click="install(false)"
          >
            {{ contextTranslate("Install modules", context) }}
          </NcButton>
          <NcButton
            v-else
            type="secondary"
            :disabled="busy || !status.can_install"
            @click="install(true)"
          >
            {{ contextTranslate("Reinstall modules", context) }}
          </NcButton>
          <NcButton type="secondary" :disabled="busy" @click="reactivate">
            {{ contextTranslate("Re-activate", context) }}
          </NcButton>
        </div>

        <div v-if="status && status.update" class="pro-settings__update">
          <p>
            {{ contextTranslate("Update available", context) }}: {{ status.update.version }}
          </p>
          <NcButton type="primary" :disabled="busy" @click="update">
            {{ contextTranslate("Update", context) }}
          </NcButton>
        </div>
      </div>
    </VScrollArea>
  </VPage>
</template>

<script>
import { NcBreadcrumbs, NcBreadcrumb, NcNoteCard } from "@nextcloud/vue";
import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
import NcTextField from "@nextcloud/vue/dist/Components/NcTextField.js";

import { VPage } from "@/widgets";
import { VToolbar, VScrollArea } from "@/shared/components";

import { getProStatus, activatePro, installPro, updatePro } from "@/entities/pro/api";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export default {
  name: "ProVersionSettingsPage",
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcButton,
    NcTextField,
    NcNoteCard,
    VPage,
    VToolbar,
    VScrollArea,
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
      licenseKey: "",
      status: null,
      busy: false,
    };
  },
  async mounted() {
    await this.loadStatus();
  },
  methods: {
    async loadStatus() {
      try {
        this.status = await getProStatus();
      } catch (e) {
        this.status = null;
      }
    },
    licenseStatusLabel(value) {
      // Distinct source strings (not the bare "Active") so these license states
      // never collide with the same words translated for other screens.
      const map = {
        active: "License is active",
        expired: "License expired",
        revoked: "License revoked",
        suspended: "License suspended",
      };

      return map[value] || value;
    },
    planLabel(value) {
      const map = { year: "Yearly", month: "Monthly" };

      return map[value] || value;
    },
    formatDate(value) {
      if (!value) {
        return "";
      }
      const d = new Date(value);

      return Number.isNaN(d.getTime()) ? value : d.toLocaleString();
    },
    async activate() {
      this.busy = true;
      try {
        this.status = await activatePro(this.licenseKey);
        this.licenseKey = "";
        this.$notify({ text: this.contextTranslate("License activated. Use Install to set up the modules.", this.context), type: "success", duration: 4 * 1000 });
      } catch (e) {
        this.$notify({ text: e?.response?.data?.message || this.contextTranslate("Activation failed", this.context), type: "error", duration: 5 * 1000 });
      } finally {
        this.busy = false;
      }
    },
    async reactivate() {
      this.busy = true;
      try {
        this.status = await activatePro("");
        this.$notify({ text: this.contextTranslate("License re-activated.", this.context), type: "success", duration: 3 * 1000 });
      } catch (e) {
        this.$notify({ text: e?.response?.data?.message || this.contextTranslate("Activation failed", this.context), type: "error", duration: 5 * 1000 });
      } finally {
        this.busy = false;
      }
    },
    async install(force) {
      this.busy = true;
      try {
        this.status = await installPro(force);
        this.$notify({ text: this.contextTranslate("Modules installed. Reload the page to see them.", this.context), type: "success", duration: 3 * 1000 });
      } catch (e) {
        this.$notify({ text: e?.response?.data?.message || this.contextTranslate("Installation failed", this.context), type: "error", duration: 5 * 1000 });
      } finally {
        this.busy = false;
      }
    },
    async update() {
      this.busy = true;
      try {
        this.status = await updatePro();
        this.$notify({ text: this.contextTranslate("Update installed. Reload the page.", this.context), type: "success", duration: 3 * 1000 });
      } catch (e) {
        this.$notify({ text: e?.response?.data?.message || this.contextTranslate("Update failed", this.context), type: "error", duration: 5 * 1000 });
      } finally {
        this.busy = false;
      }
    },
  },
};
</script>

<style scoped>
.pro-settings__content {
  padding: 24px;
}

.pro-settings__header {
  margin-bottom: 24px;
}

.pro-settings__title {
  font-size: 24px;
  font-weight: 600;
}

.pro-settings__description {
  color: var(--color-text-maxcontrast);
}

.pro-settings__service-link {
  margin-top: 8px;
}

.pro-settings__notice {
  max-width: 640px;
  margin-bottom: 16px;
}

.pro-settings__section {
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-width: 640px;
}

.pro-settings__badge {
  display: inline-flex;
  align-items: center;
  height: 28px;
  padding: 0 14px;
  border-radius: 14px;
  /* Not-activated state: explicit bright amber + dark text — theme
   * --color-warning renders too pale and the white label blended into it. */
  background-color: #f5a623;
  color: #3a2a00;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.pro-settings__badge--active {
  /* Explicit saturated green + white text: theme --color-success renders too
   * pale here and the white label blended into it. */
  background-color: #2fa84f;
  color: #ffffff;
}

.pro-settings__status {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Installed version, as a neutral chip to the right of the status badge. */
.pro-settings__version {
  display: inline-flex;
  align-items: center;
  height: 28px;
  padding: 0 12px;
  border-radius: 14px;
  background-color: var(--color-background-dark);
  color: var(--color-main-text);
  font-size: 13px;
  font-weight: 600;
}

/* License details as a two-column key/value list. */
.pro-settings__info {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin: 4px 0 8px;
  padding: 16px;
  border-radius: var(--border-radius-large);
  background-color: var(--color-background-hover);
}

.pro-settings__info-row {
  display: flex;
  gap: 12px;
}

.pro-settings__info-row dt {
  flex: 0 0 160px;
  color: var(--color-text-maxcontrast);
}

.pro-settings__info-row dd {
  margin: 0;
  font-weight: 500;
}

.pro-settings__module-chip {
  display: inline-block;
  margin: 0 6px 4px 0;
  padding: 2px 10px;
  border-radius: 12px;
  background-color: var(--color-background-dark);
  font-size: 12px;
  text-transform: capitalize;
}

.pro-settings__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.pro-settings__key-row {
  display: flex;
  align-items: flex-end;
  gap: 12px;
}

/* Let the field take the remaining width; keep the button at its natural
 * width so its label is never truncated. */
.pro-settings__key-field {
  flex: 1 1 auto;
  min-width: 0;
}

.pro-settings__activate-btn {
  flex: 0 0 auto;
  white-space: nowrap;
}

.pro-settings__update {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: var(--border-radius-large);
  background-color: var(--color-background-hover);
}
</style>
