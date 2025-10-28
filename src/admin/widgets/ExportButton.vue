<template>
  <NcActions>
    <NcActionButton :disabled="isLoading" @click="handleExport('excel')">
      <template #icon>
        <Download v-if="!isLoading" />
        <Loading v-else />
      </template>
      {{
        isLoading
          ? contextTranslate("Exporting...", contextName)
          : contextTranslate("Export to Excel", contextName)
      }}
    </NcActionButton>

    <NcActionButton :disabled="isLoading" @click="handleExport('csv')">
      <template #icon>
        <Download v-if="!isLoading" />
        <Loading v-else />
      </template>
      {{
        isLoading
          ? contextTranslate("Exporting...", contextName)
          : contextTranslate("Export to CSV", contextName)
      }}
    </NcActionButton>
  </NcActions>
</template>

<script>
import { NcActions, NcActionButton } from "@nextcloud/vue";

import Download from "vue-material-design-icons/Download.vue";
import Loading from "vue-material-design-icons/Loading.vue";

import { exportToExcel } from "@/admin/entities/common/api";

import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

import { getFileNameFromResponse } from "@/admin/shared/lib/helpers";

export default {
  name: "ExportButton",
  mixins: [contextualTranslationsMixin],
  components: {
    NcActions,
    NcActionButton,
    Download,
    Loading,
  },
  props: {
    source: {
      type: Number,
      required: true,
    },
    contextName: {
      type: String,
      default: "admin",
    },
  },
  data() {
    return {
      isLoading: false,
    };
  },
  methods: {
    getSuccessMessage(format) {
      const formatName = format === "excel" ? "Excel" : "CSV";

      return this.contextTranslate(
        `Export to ${formatName} completed successfully`,
        this.contextName
      );
    },
    getErrorText(e) {
      const { message } = e;

      if (message.includes("rate limit")) {
        return this.contextTranslate(
          "Export rate limit exceeded. Please wait before trying again.",
          this.contextName
        );
      }

      if (
        message.includes("permission") ||
        message.includes("You do not have permission")
      ) {
        return this.contextTranslate(
          "You do not have permission to export data.",
          this.contextName
        );
      }

      if (message.includes("Invalid entity source")) {
        return this.contextTranslate("Invalid entity source", this.contextName);
      }

      if (message.includes("Too many rows")) {
        return this.contextTranslate(
          "Too many rows to export. Maximum allowed: 10000",
          this.contextName
        );
      }

      if (message.includes("Export failed")) {
        return this.contextTranslate(
          "Export failed. Please try again.",
          this.contextName
        );
      }

      return message;
    },
    async handleExport(format) {
      this.isLoading = true;

      try {
        const response = await exportToExcel({
          source: this.source,
          options: { format },
        });

        const blob = new Blob([response.data], {
          type: response.headers["content-type"] || "application/octet-stream",
        });

        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = url;
        link.download = getFileNameFromResponse(response) || "export.xlsx";

        document.body.appendChild(link);

        link.click();

        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        const text = this.getSuccessMessage(format);

        this.$notify({
          text,
          type: "success",
          duration: 5 * 1000,
        });
      } catch (e) {
        const text = this.getErrorText(e);

        this.$notify({
          text,
          type: "error",
          duration: 5 * 1000,
        });
      } finally {
        this.isLoading = false;
      }
    },
  },
};
</script>
