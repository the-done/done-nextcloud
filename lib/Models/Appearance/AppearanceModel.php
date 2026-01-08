<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Models\Appearance;

use OCA\Done\Models\BaseModel;

/**
 * Abstract class AppearanceModel.
 */
abstract class AppearanceModel extends BaseModel
{
    // Table fields that contain appearance data
    public array $appearanceFields = [];

    // Appearance fields for which image file is loaded
    public array $appearanceFieldsWithFile = [];
}
