/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { contractFieldOptions } from "@/shared/lib/constants/contractFormula";

export const getFieldName = (value) => {
  const exist = contractFieldOptions.find((item) => item.value === value);

  if (exist) {
    return exist.label;
  }

  return value;
};

export const getParameterName = (id, parameterOptions) => {
  const exist = parameterOptions.find((item) => item.slug === id);

  if (exist) {
    return exist.contract_parameter_name;
  }

  return `${id.slice(0, 5)}...`;
};

export const transformFormulaToString = (value, parameterOptions) => {
  if (!value?.length || Array.isArray(value) === false) {
    return "";
  }

  return value.reduce((accum, item) => {
    switch (item.type) {
      case "field":
        return accum + getFieldName(item.value);
      case "parameter":
        return accum + getParameterName(item.id, parameterOptions);
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
//       // A field or parameter in curly braces
//       const value = match[1];
//       const type = value.startsWith("param.") ? "parameter" : "field";
//       tokens.push({ type, value });
//     } else if (match[2]) {
//       // A number
//       tokens.push({ type: "number", value: parseFloat(match[2]) });
//     } else if (match[3]) {
//       // An operator
//       tokens.push({ type: "operator", value: match[3] });
//     }
//     // Ignore whitespace
//   }
//
//   return tokens;
// };
