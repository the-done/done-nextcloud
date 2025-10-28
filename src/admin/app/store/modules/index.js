import { defineStore } from "pinia";

import { state } from "./state";
import { getters } from "./getters";

export const useModulesStore = defineStore("modules", {
  state,
  getters,
});
