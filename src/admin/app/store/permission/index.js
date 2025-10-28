import { defineStore } from "pinia";

import { state } from "./state";
import { getters } from "./getters";

export const usePermissionStore = defineStore("permission", {
  state,
  getters,
});
