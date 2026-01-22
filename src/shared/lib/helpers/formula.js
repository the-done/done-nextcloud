/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

export const transformFormulaToString = (value) => {
  if (!value?.length || Array.isArray(value) === false) {
    return "";
  }

  return value.reduce((accum, item) => {
    switch (item.type) {
      case "field":
        return accum + `{contract.${item.value}}`;
      case "parameter":
        return accum + `{param.${item.name}}`;
      case "number":
        return accum + item.value;
      case "operator":
        return accum + ` ${item.value} `;
      default:
        return accum + "[ERROR]";
    }
  }, "");
};

// May be helpfull if new formula editor with textarea will be created
// const transformFormulaStringToArray = (value) => {
//   const tokenPattern = /\{([^}]+)\}|(\d+(?:\.\d+)?)|([+\-*/()])|\s+/g;
//   const tokens = [];
//
//   let match;
//
//   while ((match = tokenPattern.exec(this.value)) !== null) {
//     if (match[1]) {
//       // Это поле или параметр в фигурных скобках
//       const value = match[1];
//       const type = value.startsWith("param.") ? "parameter" : "field";
//       tokens.push({ type, value });
//     } else if (match[2]) {
//       // Это число
//       tokens.push({ type: "number", value: parseFloat(match[2]) });
//     } else if (match[3]) {
//       // Это оператор
//       tokens.push({ type: "operator", value: match[3] });
//     }
//     // Пробелы игнорируем
//   }
//
//   return tokens;
// };
