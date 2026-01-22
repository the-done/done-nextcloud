/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const getters = {
  getPermission: (state) => (path) => {
    const { list } = state;

    if (!list) {
      return undefined;
    }

    const pathArray = path.split(".");

    return pathArray.reduce((result, key) => {
      if (result === undefined) {
        return undefined;
      }

      const child = result[key];

      if (child === undefined) {
        return undefined;
      }

      return child;
    }, list);
  },
  getCommonPermission() {
    return (key) => {
      return this.getPermission(`common.${key}`);
    };
  },
  getFieldPermission() {
    return ({ form, field, action }) => {
      const value = this.getPermission(`fields.${form}.${field}.${action}`);

      if (value === undefined) {
        return true;
      }

      return value;
    };
  },
  canViewField() {
    return ({ form, field }) => {
      return this.getFieldPermission({ form, field, action: "can_view" });
    };
  },
  canReadField() {
    return ({ form, field }) => {
      return this.getFieldPermission({ form, field, action: "can_read" });
    };
  },
  canWriteField() {
    return ({ form, field }) => {
      return this.getFieldPermission({ form, field, action: "can_write" });
    };
  },
  canDeleteField() {
    return ({ form, field }) => {
      return this.getFieldPermission({ form, field, action: "can_delete" });
    };
  },
  canViewFieldAddInfo() {
    return ({ form, field }) => {
      return this.getFieldPermission({
        form,
        field,
        action: "can_view_add_info",
      });
    };
  },
};
