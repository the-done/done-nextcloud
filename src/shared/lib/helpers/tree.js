/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

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
