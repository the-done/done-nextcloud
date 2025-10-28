<?php

namespace OCA\Done\Models;

use OCA\Done\Models\Dictionaries\Direction_Model;
use OCA\Done\Service\BaseService;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class UsersToDirections_Model.
 */
class UsersToDirections_Model extends Base_Model
{
    public string $table = 'done_userstodirections';
    public string $modelTitle = 'Users to directions';
    public string $modelName = 'usersToDirectionsModel';
    public string $dbTableComment = 'Links users to directions: defines which company directions employees belong to.';

    protected array $hashFields = [
        'user_id',
        'direction_id',
    ];

    public array $fields = [
        'id'           => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'ID',
            'db_comment' => 'Unique identifier for the user-to-direction link'
        ],
        'user_id'      => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'User',
            'link'       => User_Model::class,
            'required'   => true,
            'db_comment' => 'User ID. References oc_done_users_data.id'
        ],
        'direction_id' => [
            'type'       => IQueryBuilder::PARAM_STR,
            'title'      => 'Direction',
            'link'       => Direction_Model::class,
            'required'   => true,
            'db_comment' => 'Direction ID. References oc_done_directions.id'
        ],
        'created_at'   => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Created at',
            'required'   => false,
            'db_comment' => 'Record creation timestamp in UTC'
        ],
        'updated_at'   => [
            'type'       => IQueryBuilder::PARAM_DATETIME_IMMUTABLE,
            'title'      => 'Updated at',
            'required'   => false,
            'db_comment' => 'Record last update timestamp in UTC'
        ],
    ];

    /**
     * @override
     */
    public function validateData(array $data, bool $save = false, array $ignoreFields = []): array
    {
        [$data, $errors] = parent::validateData($data, $save, $ignoreFields);

        if (
            $save &&
            !empty(
            $this->getItemByFilter(
                [
                    'user_id'      => $data['user_id'],
                    'direction_id' => $data['direction_id'],
                ]
            )
            )
        ) {
            $errors[] = $this->translateService->getTranslate(
                'This employee is already assigned to the selected direction'
            );
        }

        if (
            !$save &&
            !empty(
            $this->getItemByFilter(
                [
                    'user_id'      => $data['user_id'],
                    'direction_id' => $data['direction_id'],
                    'id'           => ['!=', $data['id']],
                ]
            )
            )
        ) {
            $errors[] = $this->translateService->getTranslate(
                'This employee is already assigned to the selected direction'
            );
        }

        return [$data, $errors];
    }

    /**
     * Get user IDs from specified directions
     *
     * @param string[] $directions
     *
     * @return string[]
     */
    public function getEmployeesByDirections(array $directions = []): array
    {
        if (empty($directions)) {
            return [];
        }

        $list = $this->getListByFilter(
            ['direction_id' => ['IN', $directions, IQueryBuilder::PARAM_STR_ARRAY]]
        );

        return array_values(BaseService::getField($list, 'user_id', true));
    }
}