/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const clickOutside = {
  bind(el, binding, vnode) {
    el.clickOutsideEvent = function (event) {
      // Check that click was outside element and its children
      if (!(el === event.target || el.contains(event.target))) {
        // Call the passed method
        vnode.context[binding.expression](event);
      }
    };
    document.body.addEventListener("click", el.clickOutsideEvent);
  },
  unbind(el) {
    document.body.removeEventListener("click", el.clickOutsideEvent);
  },
};
