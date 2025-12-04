<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\Base_Model;
use OCA\Done\Models\Dictionaries\Contracts_Model;
use OCA\Done\Models\Dictionaries\Customers_Model;
use OCA\Done\Models\Dictionaries\Direction_Model;
use OCA\Done\Models\Dictionaries\GlobalRoles_Model;
use OCA\Done\Models\Dictionaries\Positions_Model;
use OCA\Done\Models\Dictionaries\Roles_Model;
use OCA\Done\Models\Dictionaries\Stages_Model;
use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Modules\Teams\Models\RolesInTeam_Model;

class DictionariesService
{
    public Positions_Model $positionsDictionary;
    public Contracts_Model $contractsDictionary;
    public Roles_Model $rolesDictionary;
    public GlobalRoles_Model $globalRolesDictionary;
    public Direction_Model $directionDictionary;
    public Stages_Model $stagesDictionary;
    public Customers_Model $customersDictionary;
    public ?RolesInTeam_Model $rolesInTeamDictionary;
    protected TranslateService $translateService;

    public function __construct(
        Positions_Model $positionsDictionary,
        Contracts_Model $contractsDictionary,
        Roles_Model $rolesDictionary,
        GlobalRoles_Model $globalRolesDictionary,
        Direction_Model $directionDictionary,
        Stages_Model $stagesDictionary,
        Customers_Model $customersDictionary,
        TranslateService $translateService,
    ) {
        $this->positionsDictionary = $positionsDictionary;
        $this->contractsDictionary = $contractsDictionary;
        $this->rolesDictionary = $rolesDictionary;
        $this->globalRolesDictionary = $globalRolesDictionary;
        $this->directionDictionary = $directionDictionary;
        $this->stagesDictionary = $stagesDictionary;
        $this->customersDictionary = $customersDictionary;
        $this->translateService = $translateService;

        if (BaseModuleService::moduleExists('teams')) {
            $this->rolesInTeamDictionary = RolesInTeam_Model::getInstance();
        }
    }

    /**
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getDictionary(string $dictTitle = ''): array
    {
        $data = [];
        $dictionaryModel = $this->getDictionaryModel($dictTitle);

        if (empty($dictionaryModel)) {
            return $data;
        }

        $data['body'] = $this->getDictionaryBodyData($dictionaryModel);
        $data['header'] = $this->getDictionaryHeaderData($dictionaryModel->fields);

        return $data;
    }

    private function getDictionaryBodyData(Base_Model $dictionaryModel): array
    {
        return $dictionaryModel->getListByFilter([], ['*'], ['sort', 'ASC'], ['name', 'ASC']);
    }

    private function getDictionaryHeaderData(array $fields): array
    {
        $header = [];

        foreach ($fields as $name => $fieldParams) {
            $header[$name] = $this->translateService->getTranslate($fieldParams['title']);
        }

        return $header;
    }

    public function getDictionariesModels(): array
    {
        $models = [
            $this->positionsDictionary->modelName   => $this->positionsDictionary,
            $this->contractsDictionary->modelName   => $this->contractsDictionary,
            $this->rolesDictionary->modelName       => $this->rolesDictionary,
            $this->globalRolesDictionary->modelName => $this->globalRolesDictionary,
            $this->directionDictionary->modelName   => $this->directionDictionary,
            $this->stagesDictionary->modelName      => $this->stagesDictionary,
            $this->customersDictionary->modelName   => $this->customersDictionary,
        ];

        if (BaseModuleService::moduleExists('teams')) {
            $models[$this->rolesInTeamDictionary->modelName] = $this->rolesInTeamDictionary;
        }

        return $models;
    }

    /**
     * @NoAdminRequired
     *
     * @NoCSRFRequired
     */
    public function getDictionaryItem(
        string $dictTitle,
        int | string | null $slug,
        ?int $slugType
    ): array {
        $data = [];
        $dictionaryModel = $this->getDictionaryModel($dictTitle);

        if (empty($dictionaryModel)) {
            return $data;
        }

        $itemId = $dictionaryModel->getItemIdBySlug($slug);

        return $dictionaryModel->getItem($itemId);
    }

    public function getDictionaryModel(string $dictTitle): ?Base_Model
    {
        return $this->getDictionariesModels()[$dictTitle] ?? null;
    }
}
