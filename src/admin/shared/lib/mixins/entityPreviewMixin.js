/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { saveEntityImage, saveEntityColor } from "@/admin/entities/common/api";

export const entityPreviewMixin = {
  methods: {
    async handleFileUpload({ fieldName, image }) {
      try {
        await saveEntityImage({
          source: this.source,
          slug: this.slug,
          fieldName,
          image,
        });

        this.handleFetchData();
      } catch (error) {
        console.error("Error uploading file:", error);
      }
    },
    async handleColorSubmit(color) {
      try {
        await saveEntityColor({
          source: this.source,
          slug: this.slug,
          color,
        });

        this.handleFetchData();
      } catch (error) {
        console.error("Error uploading file:", error);
      }
    },
  },
};
