<?php

namespace OCA\Done\Models\Table;

use OCA\Done\Models\Base_Model;

/**
 * Class TableSettings_Model.
 */
class TableSettings_Model extends Base_Model
{
    public function getData(string $userId, int $source): array
    {
        $result = [];

        $dataForUser = $this->getListByFilter(
            ['user_id' => $userId, 'source' => $source, 'for_all' => 0]
        );

        $dataForAll = $this->getListByFilter(
            ['source' => $source, 'for_all' => 1]
        );

        $data = [...$dataForUser, ...$dataForAll];

        foreach ($data as $item) {
            $column = $item['column'];
            $forAll = (int)$item['for_all'];

            $result[$column][$forAll] = $item;
        }

        return $result;
    }
}