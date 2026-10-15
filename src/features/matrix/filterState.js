/** Pure filter state shared by the drawer and its regression tests. */
export function visibleOptions(options, selected, search, locale) {
  const query = search.trim().toLocaleLowerCase(locale);
  return options.filter((option) => !(selected || []).includes(option.id)
    && option.name.toLocaleLowerCase(locale).includes(query));
}

export function normalizeSelection(ids, options) {
  if (ids === null || ids.length === 0) return null;
  const unique = [...new Set(ids)];
  return options.length && options.every((option) => unique.includes(option.id)) ? null : unique;
}

// The first outside click dismisses only the nested picker. A second can dismiss the drawer.
export function outsideClickAction({ pickerOpen, insidePicker, closeButton, insideDrawer, switchesDrawer }) {
  if (closeButton) return null; // The explicit close control handles its own click.
  if (pickerOpen && !insidePicker) return "picker";
  if (insideDrawer || switchesDrawer) return null;
  return "drawer";
}
