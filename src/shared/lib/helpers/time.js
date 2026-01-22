/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */
import { t } from "@nextcloud/l10n";

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
