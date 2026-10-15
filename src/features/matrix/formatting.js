import { t } from "@nextcloud/l10n";
const parse = (value) => new Date(`${value}T12:00:00`);

export default {
  data: () => ({ locale: document.documentElement.lang || "en" }),
  methods: {
    profileRoute(type, record) {
      const slug = record[`${type}_slug`];
      return slug ? { name: type === "user" ? "staff-preview" : "project-preview", params: { slug } } : null;
    },
    rowProfile(row) {
      if (row.id === "company") return null;
      const type = (this.mode === "employees") !== (row.depth === 1) ? "user" : "project";
      return this.profileRoute(type, row);
    },
    tr(text) { return t("done", text); },
    hours(minutes) { return (minutes / 60).toLocaleString(this.locale, { maximumFractionDigits: 2 }); },
    localizedDate(date, options) {
      const text = parse(date).toLocaleDateString(this.locale, options);
      return /^ru(?:-|$)/i.test(this.locale) ? text.replace(/\s*г\.$/u, "") : text;
    },
    formatDate(date) {
      return this.localizedDate(date, { day: "numeric", month: "short", year: "numeric" });
    },
    columnSubtitle(column) {
      const date = parse(column.start);
      if (this.period === "month" && !this.byDates) {
        return new Intl.DateTimeFormat(this.locale, { day: "numeric", month: "long" })
          .formatToParts(date).find((part) => part.type === "month").value;
      }
      if (this.period === "week" || this.period === "month" && this.byDates) {
        return date.toLocaleDateString(this.locale, { weekday: "long" });
      }
      return "";
    },
    columnTitle(column) {
      const first = parse(column.start);
      if (this.period === "year") return first.toLocaleDateString(this.locale, { month: "short" });
      if (this.period === "month" && !this.byDates) {
        return column.start === column.end ? String(first.getDate())
          : `${first.getDate()}–${parse(column.end).getDate()}`;
      }
      return first.toLocaleDateString(this.locale, { day: "numeric", month: "short" });
    },
    isWeekend(column) {
      return column.start === column.end && [0, 6].includes(parse(column.start).getDay());
    },
    cellLabel(row, column, minutes) {
      return `${row.name}, ${column.start} – ${column.end}, ${this.hours(minutes)} ${this.tr("hours")}`;
    },
  },
};
