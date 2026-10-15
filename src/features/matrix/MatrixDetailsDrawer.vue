<template>
  <div class="matrix-drawer-content">
        <header>
          <div><span class="matrix-eyebrow">{{ tr('Time entries') }}</span>
            <h2>
              <router-link v-if="selected.profile" class="matrix-profile-link" :to="selected.profile">
                {{ selected.name }}&nbsp;<span class="matrix-link-arrow" aria-hidden="true">↗</span>
              </router-link>
              <template v-else>{{ selected.name }}</template>
            </h2>
          </div>
          <button ref="closeButton" class="matrix-close" :aria-label="tr('Close details')" @click="$emit('close')">
            <Close :size="20" />
          </button>
        </header>
        <div class="matrix-details-summary">
          <span>{{ formatDate(selected.start) }}<template v-if="selected.start !== selected.end">
            – {{ formatDate(selected.end) }}</template></span>
          <strong>{{ hours(selected.minutes) }} {{ tr('hours') }}</strong>
          <small v-if="detailCount !== null">{{ detailCount }} {{ tr('entries') }}</small>
        </div>
        <div ref="detailList" class="matrix-detail-list" tabindex="0" :aria-label="tr('Time entries')"
          @scroll.passive="onDetailsScroll">
          <p v-if="detailError" role="alert">{{ detailError }}
            <button @click="loadDetails">{{ tr('Retry') }}</button></p>
          <article v-for="entry in entries" :key="entry.id" class="matrix-entry">
            <div class="matrix-entry-top"><time>{{ formatDate(entry.date) }}</time>
              <strong>{{ hours(entry.minutes) }} {{ tr('h') }}</strong></div>
            <div class="matrix-entry-entities">
              <component :is="index === 0 ? 'h3' : 'div'" v-for="(entity, index) in entryEntities(entry)"
                :key="entity.type" :class="{ 'matrix-entry-secondary': index === 1 }">
                <router-link v-if="entity.to" class="matrix-profile-link" :to="entity.to">
                  {{ entity.name }}&nbsp;<span class="matrix-link-arrow" aria-hidden="true">↗</span>
                </router-link>
                <span v-else>{{ entity.name }}</span>
              </component>
            </div>
            <p>{{ entry.description }}</p>
            <p v-if="entry.comment" class="matrix-entry-comment">{{ entry.comment }}</p>
            <small v-if="Number(entry.is_downtime)">{{ tr('Downtime') }}</small>
            <a v-if="entry.task_link" class="matrix-entry-task" :href="entry.task_link"
              target="_blank" rel="noopener noreferrer">
              {{ tr('Open task') }}&nbsp;<span class="matrix-link-arrow" aria-hidden="true">↗</span>
            </a>
          </article>
          <p v-if="detailsLoading && !entries.length" role="status"><VLoaderDots /> {{ tr('Loading entries…') }}</p>
          <button v-if="entries.length < detailCount" class="matrix-load-more" :disabled="detailsLoading"
            @click="loadDetails">
            {{ tr(detailsLoading ? 'Loading entries…' : 'Load more') }} ({{ entries.length }} / {{ detailCount }})
          </button>
          <p v-else-if="detailCount === 0">{{ tr('No time entries in this period') }}</p>
        </div>
  </div>
</template>
<script>
import Close from "vue-material-design-icons/Close.vue";
import { VLoaderDots } from "@/shared/components";
import { fetchMatrixEntries } from "@/entities/reports/api";
import formatting from "./formatting";
export default {
  name: "MatrixDetailsDrawer",
  components: { Close, VLoaderDots },
  mixins: [formatting],
  props: { selected: { type: Object, required: true }, filterParams: { type: Object, required: true } },
  data: () => ({ entries: [], detailCount: null, detailsLoading: false, detailError: "",
    detailSequence: 0, detailController: null }),
  mounted() { this.loadDetails(); this.$refs.closeButton.focus(); },
  beforeDestroy() { this.detailSequence++; this.detailController?.abort(); },
  methods: {
    entryEntities(entry) {
      const order = this.selected.employeeView ? ["project", "user"] : ["user", "project"];
      return order.map((type) => ({ type, name: entry[`${type}_name`], to: this.profileRoute(type, entry) }));
    },
    onDetailsScroll(event) {
      const list = event.currentTarget;
      if (this.detailsLoading || this.detailError || !this.selected || this.entries.length >= this.detailCount) return;
      if (list.scrollTop > 0 && list.scrollHeight - list.scrollTop - list.clientHeight < 240) this.loadDetails();
    },
    async loadDetails() {
      if (!this.selected || this.detailsLoading) return;
      const sequence = ++this.detailSequence;
      this.detailController?.abort();
      this.detailController = new AbortController();
      this.detailsLoading = true;
      this.detailError = "";
      try {
        const { data } = await fetchMatrixEntries(
          { ...this.selected, ...this.filterParams, offset: this.entries.length }, this.detailController.signal);
        if (sequence !== this.detailSequence) return;
        if (!Array.isArray(data.entries)) throw new Error("Invalid detail response");
        this.entries = [...this.entries, ...data.entries];
        this.detailCount = data.count;
      } catch (error) {
        if (sequence === this.detailSequence) this.detailError = this.tr("Could not load time entries.");
      } finally { if (sequence === this.detailSequence) this.detailsLoading = false; }
    },
  },
};
</script>
