<?php

/**
 * SPDX-FileCopyrightText: 2026 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Reports\Service;

use OCP\IDBConnection;
use OCP\Server;
use OCP\DB\QueryBuilder\IQueryBuilder;

class MatrixReportService
{
    /** null means unrestricted; an empty selection deliberately matches nothing. */
    public static function selection($value): ?array
    {
        if ($value === null) return null;
        if (is_string($value)) $value = $value === '' ? [] : explode(',', $value);
        if (!is_array($value) || !array_is_list($value) || count($value) > 1000) {
            throw new \InvalidArgumentException('Invalid filter selection');
        }
        foreach ($value as $id) {
            if (!is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
                throw new \InvalidArgumentException('Invalid filter ID');
            }
        }
        return array_values(array_unique($value));
    }

    private function filters($qb, ?array $projects, ?array $users): void
    {
        foreach (['project_id' => $projects, 'user_id' => $users] as $field => $ids) {
            if ($ids === null) continue;
            if ($ids === []) $qb->andWhere('1 = 0');
            else $qb->andWhere($qb->expr()->in('t.' . $field,
                $qb->createNamedParameter($ids, IQueryBuilder::PARAM_STR_ARRAY)));
        }
    }

    public function filterOptions(): array
    {
        $connection = Server::get(IDBConnection::class);
        $p = $connection->getQueryBuilder();
        $p->select('id', 'name')->from('done_projects')
            ->where($p->expr()->eq('deleted', $p->createNamedParameter(0)));
        $projects = $p->executeQuery()->fetchAll();
        usort($projects, static fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
        $u = $connection->getQueryBuilder();
        $u->select('id', 'name AS first_name', 'lastname AS last_name', 'user_display_name AS display_name')
            ->from('done_users_data')->where($u->expr()->eq('deleted', $u->createNamedParameter(0)));
        $users = $u->executeQuery()->fetchAll();
        foreach ($users as &$user) {
            $user['name'] = $this->name($user);
            $user['sort_name'] = trim($user['last_name'] . ' ' . $user['first_name']) ?: $user['name'];
        }
        unset($user);
        usort($users, static fn ($a, $b) => strnatcasecmp($a['sort_name'], $b['sort_name']));
        return ['project_ids' => $projects, 'user_ids' => array_map(
            static fn ($user) => ['id' => $user['id'], 'name' => $user['name']], $users)];
    }

    private function query()
    {
        $qb = Server::get(IDBConnection::class)->getQueryBuilder();
        $qb->from('done_times_data', 't')
            ->where($qb->expr()->orX(
                $qb->expr()->neq('t.status_id', $qb->createNamedParameter(5)),
                $qb->expr()->isNull('t.status_id')
            ));
        return $qb;
    }

    private function names($qb): void
    {
        $qb->leftJoin('t', 'done_users_data', 'u', 'u.id = t.user_id')
            ->leftJoin('t', 'done_projects', 'p', 'p.id = t.project_id');
    }

    private function range($qb, string $start, string $end): void
    {
        $qb->andWhere($qb->expr()->gte('t.date', $qb->createNamedParameter($start)))
            ->andWhere($qb->expr()->lte('t.date', $qb->createNamedParameter($end)));
    }

    private function name(array $row): string
    {
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $name ?: ($row['display_name'] ?? '') ?: 'Unknown employee';
    }

    private function profileSlugs(array &$row): void
    {
        $row['user_slug'] = empty($row['profile_user_id']) ? null
            : (($row['display_name'] ?? '') ?: $row['profile_user_id']);
        $row['project_slug'] = $row['profile_project_id'] ?? null;
        unset($row['profile_user_id'], $row['profile_project_id']);
    }

    public function matrix(string $date, string $period, bool $byDates, string $mode,
        ?array $projects = null, ?array $users = null): array
    {
        $calendar = MatrixReportBuilder::period($date, $period, $byDates);
        $qb = $this->query();
        $this->filters($qb, $projects, $users);
        $this->range($qb, $calendar['start'], $calendar['end']);
        $this->names($qb);
        $qb->select('t.date', 't.user_id', 't.project_id', 'u.name AS first_name',
            'u.lastname AS last_name', 'u.user_display_name AS display_name', 'p.name AS project_name',
            'u.id AS profile_user_id', 'p.id AS profile_project_id')
            ->selectAlias($qb->createFunction('SUM(t.minutes)'), 'minutes')
            ->groupBy('t.date', 't.user_id', 't.project_id', 'u.name', 'u.lastname', 'u.user_display_name',
                'p.name', 'u.id', 'p.id');
        $daily = $qb->executeQuery()->fetchAll();
        foreach ($daily as &$row) {
            $this->profileSlugs($row);
            $row['user_name'] = $this->name($row);
            $row['user_sort_name'] = trim(($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? ''))
                ?: $row['user_name'];
            $row['project_name'] = $row['project_name'] ?: 'Unknown project';
        }
        unset($row);
        $matrix = MatrixReportBuilder::build($calendar, $mode, $daily);
        foreach (['previous' => ['<', 'MAX', $calendar['start']], 'next' => ['>', 'MIN', $calendar['end']]] as $key => $spec) {
            $q = $this->query();
            $this->filters($q, $projects, $users);
            $q->selectAlias($q->createFunction($spec[1] . '(t.date)'), 'date');
            $q->andWhere($spec[0] === '<'
                ? $q->expr()->lt('t.date', $q->createNamedParameter($spec[2]))
                : $q->expr()->gt('t.date', $q->createNamedParameter($spec[2])));
            $matrix[$key] = $q->executeQuery()->fetchOne() ?: null;
        }
        return $matrix;
    }

    public function entries(string $start, string $end, string $user, string $project, int $offset,
        ?array $projects = null, ?array $users = null): array
    {
        $first = MatrixReportBuilder::date($start);
        $last = MatrixReportBuilder::date($end);
        if ($first > $last || $first->diff($last)->days > 366 || $offset < 0) {
            throw new \InvalidArgumentException('Invalid detail range');
        }
        foreach ([$user, $project] as $id) {
            if ($id !== '' && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $id)) {
                throw new \InvalidArgumentException('Invalid record ID');
            }
        }
        $qb = $this->query();
        $this->filters($qb, $projects, $users);
        $this->range($qb, $start, $end);
        foreach (['user_id' => $user, 'project_id' => $project] as $field => $value) {
            if ($value !== '') {
                $qb->andWhere($qb->expr()->eq('t.' . $field, $qb->createNamedParameter($value)));
            }
        }
        $totals = clone $qb;
        $totals->selectAlias($totals->createFunction('COUNT(*)'), 'count')
            ->selectAlias($totals->createFunction('SUM(t.minutes)'), 'minutes');
        $summary = $totals->executeQuery()->fetch();
        $this->names($qb);
        $qb->select('t.id', 't.date', 't.description', 't.comment', 't.task_link', 't.minutes', 't.is_downtime',
            'u.name AS first_name', 'u.lastname AS last_name', 'u.user_display_name AS display_name',
            'p.name AS project_name', 'u.id AS profile_user_id', 'p.id AS profile_project_id')
            ->orderBy('t.date', 'ASC')->addOrderBy('t.user_id', 'ASC')
            ->addOrderBy('t.sort', 'ASC')->addOrderBy('t.id', 'ASC')
            ->setFirstResult($offset)->setMaxResults(100);
        $entries = $qb->executeQuery()->fetchAll();
        foreach ($entries as &$row) {
            $this->profileSlugs($row);
            $row['user_name'] = $this->name($row);
            $row['minutes'] = (int)$row['minutes'];
            $row['project_name'] = $row['project_name'] ?: 'Unknown project';
            if (!preg_match('~^https?://~i', $row['task_link'] ?? '')) {
                $row['task_link'] = '';
            }
        }
        return ['entries' => $entries, 'count' => (int)$summary['count'], 'minutes' => (int)$summary['minutes']];
    }
}
