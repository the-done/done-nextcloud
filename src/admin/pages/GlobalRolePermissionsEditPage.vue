/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

<template>
  <VPage data-component-id="GlobalRolePermissionsEditPage">
    <VToolbar>
      <NcBreadcrumbs>
        <NcBreadcrumb
          v-for="(item, index) in breadcrumbs"
          :key="index"
          :name="contextTranslate(item.title, context)"
          :to="item.path"
          :forceIconText="index === 0"
        >
          <template v-if="item.icon" #icon>
            <component v-if="item.icon" :is="item.icon" />
            <BookOpenVariant v-else />
          </template>
        </NcBreadcrumb>
      </NcBreadcrumbs>
    </VToolbar>
    <VPageLayout>
      <VPageContent>
        <template v-if="entity">
          <table class="w-full my-2 border-collapse">
            <tbody>
              <template v-for="(field, fieldId) in entity.fields">
                <tr>
                  <td
                    :colspan="
                      field.expanded ? 1 : Object.keys(field.roles).length + 1
                    "
                    class="px-4 py-2 font-medium"
                  >
                    <div class="flex gap-2 items-center">
                      <VExpandIconButton
                        :expanded="field.expanded"
                        @on-click="() => handleExpandRow(field)"
                      />
                      <span class="text-(--color-primary)">
                        {{ contextTranslate(field.field_title, context) }}
                      </span>
                    </div>
                  </td>
                  <template v-if="field.expanded">
                    <td
                      v-for="actionId in actionsList"
                      :key="`${fieldId}_${actionId}`"
                      class="px-4 py-2 font-medium text-center"
                    >
                      {{ contextTranslate(getActionTitle(actionId), context) }}
                    </td>
                  </template>
                </tr>
                <template v-if="field.expanded">
                  <tr v-for="(role, roleId) in field.roles" :key="roleId">
                    <td class="px-4 py-2">
                      {{ contextTranslate(role.role_title, context) }}
                    </td>
                    <td
                      v-for="actionId in actionsList"
                      :key="`${fieldId}_${actionId}`"
                      class="px-4 py-2"
                    >
                      <div class="flex justify-center">
                        <NcCheckboxRadioSwitch
                          :model-value="
                            getCheckboxFieldValue({
                              fieldId,
                              roleId,
                              actionId,
                            })
                          "
                          :loading="
                            getFieldLoading({
                              fieldId,
                              roleId,
                              actionId,
                            })
                          "
                          :disabled="
                            getCommonPermission('canEditRightsMatrix') === false
                          "
                          value="true"
                          @update:modelValue="
                            () =>
                              handleUpdateValue({
                                fieldId,
                                role,
                                roleId,
                                actionId,
                              })
                          "
                        />
                      </div>
                    </td>
                  </tr>
                </template>
              </template>
            </tbody>
          </table>
        </template>
      </VPageContent>
    </VPageLayout>
  </VPage>
</template>

<script>
import {
  NcBreadcrumbs,
  NcBreadcrumb,
  NcCheckboxRadioSwitch,
} from "@nextcloud/vue";
import { mapState } from "pinia";

import ShieldAccount from "vue-material-design-icons/ShieldAccount.vue";

import { usePermissionStore } from "@/admin/app/store/permission";

import {
  fetchGlobalRolesPermissions,
  saveGlobalRolesPermissions,
  editGlobalRolesPermissions,
} from "@/admin/entities/permissions/api";

import {
  VPage,
  VPageContent,
  VPageLayout,
  VPagePadding,
} from "@/common/widgets/VPage";

import VToolbar from "@/common/shared/components/VToolbar/VToolbar.vue";
import VExpandIconButton from "@/common/shared/components/VExpandIconButton/VExpandIconButton.vue";

import { handleRestErrors } from "@/common/shared/lib/helpers";

import { MAP_ACTION_TITLE } from "@/admin/entities/permissions/constants";
import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/common/shared/mixins/contextualTranslationsMixin";

export default {
  name: "GlobalRolePermissionsEditPage",
  mixins: [contextualTranslationsMixin],
  components: {
    NcBreadcrumbs,
    NcBreadcrumb,
    NcCheckboxRadioSwitch,
    ShieldAccount,
    VPage,
    VPageLayout,
    VPageContent,
    VPagePadding,
    VToolbar,
    VExpandIconButton,
  },
  props: {
    additionalProps: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      isLoading: false,
      entity: null,
      cachedFields: {},
    };
  },
  computed: {
    ...mapState(usePermissionStore, ["getCommonPermission"]),
    source() {
      return this.additionalProps.source;
    },
    breadcrumbs() {
      return this.additionalProps.breadcrumbs;
    },
    rolesList() {
      if (!this.entity) {
        return [];
      }

      const { fields } = this.entity;

      if (!fields) {
        return [];
      }

      const fieldKeys = Object.keys(fields);

      if (fieldKeys.length === 0) {
        return [];
      }

      const firstFieldKey = fieldKeys[0];
      const firstField = fields[firstFieldKey];
      const { roles } = firstField;

      if (!roles) {
        return [];
      }

      const roleKeys = Object.keys(roles);

      return roleKeys.reduce((accum, id) => {
        const role = roles[id];

        return [
          ...accum,
          {
            id,
            slug: role.slug,
            title: role.role_title,
            actions: role.actions,
          },
        ];
      }, []);
    },
    actionsList() {
      if (!this.rolesList || this.rolesList.length === 0) {
        return [];
      }

      const firstRole = this.rolesList[0];
      const actionKeys = Object.keys(firstRole.actions);

      if (!actionKeys) {
        return [];
      }

      return actionKeys;
    },
  },
  methods: {
    t,
    handleExpandRow(row) {
      row.expanded = !row.expanded;
    },
    getActionTitle(value) {
      return (
        this.contextTranslate(MAP_ACTION_TITLE[value], this.context) || value
      );
    },
    getCachedKey({ fieldId, roleId, actionId }) {
      return `${fieldId}_${roleId}_${actionId}`;
    },
    getField({ fieldId, roleId, actionId }) {
      const key = this.getCachedKey({ fieldId, roleId, actionId });

      return this.cachedFields[key];
    },
    getFieldValue({ fieldId, roleId, actionId }) {
      const field = this.getField({ fieldId, roleId, actionId });

      return field.value;
    },
    getFieldLoading({ fieldId, roleId, actionId }) {
      const field = this.getField({ fieldId, roleId, actionId });

      return field.isLoading;
    },
    getCheckboxFieldValue({ fieldId, roleId, actionId }) {
      const value = this.getFieldValue({ fieldId, roleId, actionId });

      return String(value);
    },
    async handleUpdateValue({ fieldId, role, roleId, actionId }) {
      try {
        const actions = Object.keys(MAP_ACTION_TITLE).reduce((accum, key) => {
          return {
            ...accum,
            [key]: this.getField({
              fieldId,
              roleId,
              actionId: key,
            }),
          };
        }, {});

        const currentField = actions[actionId];
        const { value: currentValue } = currentField;
        const nextValue = !currentValue;

        currentField.isLoading = true;

        const { slug } = role;

        if (slug) {
          await editGlobalRolesPermissions({
            slug,
            [actionId]: nextValue,
          });
        } else {
          const {
            data: { slug: newSlug },
          } = await saveGlobalRolesPermissions({
            role: roleId,
            entity: this.source,
            field: fieldId,
            [actionId]: nextValue,
          });

          role.slug = newSlug;
        }

        currentField.value = nextValue;
        currentField.isLoading = false;

        if (nextValue === false) {
          return;
        }

        if (actionId === "can_read") {
          actions["can_view"].value = true;
        }

        if (actionId === "can_write") {
          actions["can_view"].value = true;
          actions["can_read"].value = true;
        }

        if (actionId === "can_delete") {
          actions["can_view"].value = true;
          actions["can_read"].value = true;
          actions["can_write"].value = true;
        }
      } catch (e) {
        handleRestErrors(e);
      }
    },
    getCachedFormValues(entity) {
      const { fields } = entity;
      const result = {};

      Object.keys(fields).forEach((fieldId) => {
        const field = fields[fieldId];
        const { roles } = field;

        Object.keys(roles).forEach((roleId) => {
          const role = roles[roleId];
          const { actions } = role;

          Object.keys(actions).forEach((actionId) => {
            const value = actions[actionId];
            const key = this.getCachedKey({
              fieldId,
              roleId,
              actionId,
            });

            result[key] = {
              isLoading: false,
              value,
            };
          });
        });
      });

      return result;
    },
    transformDataForFront(entity) {
      const addExpandedProperty = (obj) => {
        return Object.keys(obj).reduce((accum, key) => {
          const value = obj[key];

          return {
            ...accum,
            [key]: {
              ...value,
              expanded: false,
            },
          };
        }, {});
      };

      return {
        ...entity,
        fields: addExpandedProperty(entity.fields),
      };
    },
    async handleFetchData() {
      this.isLoading = true;

      try {
        const {
          data: { permissions },
        } = await fetchGlobalRolesPermissions({ source: Number(this.source) });

        const entity = permissions[this.source];

        if (!entity) {
          return;
        }

        const result = this.transformDataForFront(entity);
        const cached = this.getCachedFormValues(result);

        this.entity = result;
        this.cachedFields = cached;
      } catch (e) {
        console.log(e);
      } finally {
        this.isLoading = false;
      }
    },
    init() {
      this.handleFetchData();
    },
  },
  mounted() {
    this.init();
  },
  watch: {
    $route() {
      this.init();
    },
  },
};
</script>
