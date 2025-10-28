import Vue from "vue";
import { ru } from "date-fns/locale";
import { t } from "@nextcloud/l10n";

export const getJoinString = (array = [], separator = " ") => {
  const result = array.filter((item) => Boolean(item));

  return result.join(separator);
};

export const minutesToHours = (totalMinutes) => {
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;
  const stringHours = hours < 10 ? `0${hours}` : hours;
  const stringMinutes = minutes < 10 ? `0${minutes}` : minutes;

  const resultHours = hours > 0 ? `${hours} ${t("done", "h")}` : "";
  const resultMinutes = minutes > 0 ? `${minutes} ${t("done", "min")}` : "";

  if (resultHours && resultMinutes) {
    return {
      hours,
      minutes,
      stringHours,
      stringMinutes,
      stringTime: `${resultHours} ${resultMinutes}`,
    };
  }

  return {
    hours,
    minutes,
    stringHours,
    stringMinutes,
    stringTime: resultHours || resultMinutes || "",
  };
};

export const declOfNum = (number, titles) => {
  const cases = [2, 0, 1, 1, 1, 2];

  return titles[
    number % 100 > 4 && number % 100 < 20
      ? 2
      : cases[number % 10 < 5 ? number % 10 : 5]
  ];
};

export const handleRestErrors = (e) => {
  const { response } = e;

  console.log(e);

  if (
    response?.data.error_type === "validation" &&
    Array.isArray(response.data.message) &&
    response.data.message.length > 0
  ) {
    response.data.message.forEach((text) => {
      Vue.notify({
        text,
        type: "error",
        duration: 5 * 1000,
      });
    });

    return;
  }

  if (e.message) {
    Vue.notify({
      text: e.message,
      type: "error",
      duration: 5 * 1000,
    });

    return;
  }

  Vue.notify({
    text: "An unexpected error occurred",
    type: "error",
    duration: 5 * 1000,
  });
};

export const localizeDateFns = (func, ...props) => {
  return func(...props, { locale: ru });
};

export const transformDotsToDashDate = (value) => {
  const array = value.split(".");

  return getJoinString(array.reverse(), "-");
};

export const transformDashToDotsDate = (value) => {
  const array = value.split("-");

  return getJoinString(array, ".");
};

export const isEmptyValue = (value) => {
  return (
    ["", null, undefined, "{}"].includes(value) === true ||
    (Array.isArray(value) === true && value.length === 0) ||
    (typeof value === "object" &&
      !Array.isArray(value) &&
      Object.keys(value).length === 0)
  );
};

export const sortAlphabeticallyByKey = (array, key) => {
  return array.sort((a, b) => {
    const { [key]: aKey } = a;
    const { [key]: bKey } = b;

    if (aKey < bKey) {
      return -1;
    }
    if (aKey > bKey) {
      return 1;
    }

    return 0;
  });
};

export const findPathToNode = (tree, propName, value) => {
  const search = (node, currentPath) => {
    const newPath = [...currentPath, node];

    if (node[propName] && node[propName] === value) {
      return newPath;
    }

    if (node.children?.length) {
      for (const child of node.children) {
        const result = search(child, newPath);

        if (result) {
          return result;
        }
      }
    }

    return null;
  };

  for (const node of tree) {
    const path = search(node, []);

    if (path) {
      return path;
    }
  }

  return [];
};
