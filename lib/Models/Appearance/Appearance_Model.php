<?php

namespace OCA\Done\Models\Appearance;

use OCA\Done\Models\Base_Model;

/**
 * Abstract class Appearance_Model.
 */
abstract class Appearance_Model extends Base_Model
{
    // Table fields that contain appearance data
    public array $appearanceFields = [];

    // Appearance fields for which image file is loaded
    public array $appearanceFieldsWithFile = [];
}