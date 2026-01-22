/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import { defaultLayoutRoutes } from "./defaultLayoutRoutes";
import { settingsLayoutRoutes } from "./settingsLayoutRoutes";

export const routes = [...defaultLayoutRoutes, ...settingsLayoutRoutes];
