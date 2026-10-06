<?php

/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Reports\Service;

/** Calendar and integer-minute aggregation shared by the screen and every export. */
class MatrixReportBuilder
{
    public static function date(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException('Invalid date; expected YYYY-MM-DD');
        }
        return $date;
    }

    public static function period(string $date, string $period, bool $byDates): array
    {
        $day = self::date($date);
        switch ($period) {
            case 'year':
                $start = $day->modify('first day of January this year');
                $end = $start->modify('+1 year -1 day');
                break;
            case 'month':
                $start = $day->modify('first day of this month');
                $end = $day->modify('last day of this month');
                break;
            case 'week':
                $start = $day->modify('-' . ((int)$day->format('N') - 1) . ' days');
                $end = $start->modify('+6 days');
                break;
            default:
                throw new \InvalidArgumentException('Invalid period');
        }
        $columns = [];
        for ($cursor = $start; $cursor <= $end; $cursor = $last->modify('+1 day')) {
            if ($period === 'year') {
                $last = $cursor->modify('last day of this month');
            } elseif ($period === 'month' && !$byDates) {
                $last = min($end, $cursor->modify('+' . (7 - (int)$cursor->format('N')) . ' days'));
            } else {
                $last = $cursor;
            }
            $columns[] = ['start' => $cursor->format('Y-m-d'), 'end' => $last->format('Y-m-d')];
        }
        return ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d'), 'columns' => $columns];
    }

    public static function build(array $calendar, string $mode, array $daily): array
    {
        if (!in_array($mode, ['employees', 'projects'], true)) {
            throw new \InvalidArgumentException('Invalid grouping');
        }
        $zeros = array_fill(0, count($calendar['columns']), 0);
        $total = ['id' => 'company', 'name' => 'Company total', 'cells' => $zeros, 'total' => 0];
        $rows = [];
        $dateColumns = [];
        foreach ($calendar['columns'] as $index => $column) {
            for ($day = self::date($column['start']); $day <= self::date($column['end']); $day = $day->modify('+1 day')) {
                $dateColumns[$day->format('Y-m-d')] = $index;
            }
        }
        foreach ($daily as $item) {
            $index = $dateColumns[$item['date']] ?? null;
            if ($index === null) {
                continue;
            }
            $parentKey = $mode === 'employees' ? 'user' : 'project';
            $childKey = $mode === 'employees' ? 'project' : 'user';
            $parent = $parentKey . ':' . $item[$parentKey . '_id'];
            $child = $childKey . ':' . $item[$childKey . '_id'];
            $rows[$parent] ??= [
                'id' => $parent, 'name' => $item[$parentKey . '_name'],
                'sort_name' => $item[$parentKey . '_sort_name'] ?? $item[$parentKey . '_name'],
                $parentKey . '_id' => $item[$parentKey . '_id'],
                $parentKey . '_slug' => $item[$parentKey . '_slug'] ?? null,
                'cells' => $zeros, 'total' => 0, 'children' => [],
            ];
            $rows[$parent]['children'][$child] ??= [
                'id' => $parent . '/' . $child, 'name' => $item[$childKey . '_name'],
                'sort_name' => $item[$childKey . '_sort_name'] ?? $item[$childKey . '_name'],
                'user_id' => $item['user_id'], 'project_id' => $item['project_id'],
                'user_slug' => $item['user_slug'] ?? null, 'project_slug' => $item['project_slug'] ?? null,
                'cells' => $zeros, 'total' => 0,
            ];
            $minutes = (int)$item['minutes'];
            $rows[$parent]['cells'][$index] += $minutes;
            $rows[$parent]['total'] += $minutes;
            $rows[$parent]['children'][$child]['cells'][$index] += $minutes;
            $rows[$parent]['children'][$child]['total'] += $minutes;
            $total['cells'][$index] += $minutes;
            $total['total'] += $minutes;
        }
        $sort = static function (array $a, array $b): int {
            return strnatcasecmp($a['sort_name'] ?? $a['name'], $b['sort_name'] ?? $b['name']) ?: strcmp($a['id'], $b['id']);
        };
        foreach ($rows as &$row) {
            $row['children'] = array_values($row['children']);
            usort($row['children'], $sort);
        }
        unset($row);
        $rows = array_values($rows);
        usort($rows, $sort);
        return $calendar + ['mode' => $mode, 'rows' => $rows, 'company' => $total];
    }

    /** Sort siblings only, keeping the company total and each hierarchy intact. */
    public static function sorted(array $matrix, string $key = 'name', string $direction = 'asc'): array
    {
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException('Invalid sort direction');
        }
        $column = null;
        if ($key !== 'name' && $key !== 'total') {
            if (!preg_match('/^column:(0|[1-9][0-9]*)$/', $key, $match)
                || !isset($matrix['columns'][(int)$match[1]])) {
                throw new \InvalidArgumentException('Invalid sort column');
            }
            $column = (int)$match[1];
        }
        $compare = static function (array $a, array $b) use ($key, $column, $direction): int {
            $nameOrder = strnatcasecmp($a['sort_name'] ?? $a['name'], $b['sort_name'] ?? $b['name']) ?: strcmp($a['id'], $b['id']);
            $order = $key === 'name' ? $nameOrder
                : (($key === 'total' ? $a['total'] : $a['cells'][$column])
                    <=> ($key === 'total' ? $b['total'] : $b['cells'][$column]));
            return ($direction === 'desc' ? -$order : $order) ?: $nameOrder;
        };
        usort($matrix['rows'], $compare);
        foreach ($matrix['rows'] as &$row) {
            usort($row['children'], $compare);
        }
        unset($row);
        $matrix['sort'] = ['key' => $key, 'direction' => $direction];
        return $matrix;
    }

    public static function visibleRows(array $matrix, array $expanded): array
    {
        $result = [$matrix['company'] + ['depth' => 0]];
        foreach ($matrix['rows'] as $row) {
            $result[] = $row + ['depth' => 0];
            if (in_array($row['id'], $expanded, true)) {
                foreach ($row['children'] as $child) {
                    $result[] = $child + ['depth' => 1, 'parent_name' => $row['name']];
                }
            }
        }
        return $result;
    }
}
