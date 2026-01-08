<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\BaseModel;
use OCA\Done\Models\Dictionaries\ContractsModel;
use OCA\Done\Models\Dictionaries\CustomersModel;
use OCA\Done\Models\Dictionaries\DirectionModel;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\Dictionaries\PositionsModel;
use OCA\Done\Models\Dictionaries\RolesModel;
use OCA\Done\Models\Dictionaries\StagesModel;
use OCA\Done\Modules\BaseModuleService;
use OCA\Done\Modules\Teams\Models\RolesInTeamModel;

class DictionariesService
{
    public PositionsModel $positionsDictionary;
    public ContractsModel $contractsDictionary;
    public RolesModel $rolesDictionary;
    public GlobalRolesModel $globalRolesDictionary;
    public DirectionModel $directionDictionary;
    public StagesModel $stagesDictionary;
    public CustomersModel $customersDictionary;
    public ?RolesInTeamModel $rolesInTeamDictionary;
    protected TranslateService $translateService;

    public function __construct(
        PositionsModel $positionsDictionary,
        ContractsModel $contractsDictionary,
        RolesModel $rolesDictionary,
        GlobalRolesModel $globalRolesDictionary,
        DirectionModel $directionDictionary,
        StagesModel $stagesDictionary,
        CustomersModel $customersDictionary,
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
            $this->rolesInTeamDictionary = RolesInTeamModel::getInstance();
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

    private function getDictionaryBodyData(BaseModel $dictionaryModel): array
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

    public function getDictionaryModel(string $dictTitle): ?BaseModel
    {
        return $this->getDictionariesModels()[$dictTitle] ?? null;
    }
}
