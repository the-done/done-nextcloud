import { defineStore } from "pinia";

import { state } from "./state";
import { getters } from "./getters";

export const useDynamicTableStore = defineStore("dynamicTable", {
  state,
  getters,
});
