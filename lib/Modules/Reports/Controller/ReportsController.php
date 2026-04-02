<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Modules\Reports\Controller;

use OCA\Done\Attribute\RequireRole;
use OCA\Done\Models\Dictionaries\GlobalRolesModel;
use OCA\Done\Models\UserModel;
use OCA\Done\Modules\BaseModuleController;
use OCA\Done\Modules\Projects\Models\ProjectModel;
use OCA\Done\Modules\Reports\Service\ReportsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IRequest;

class ReportsController extends BaseModuleController
{
    /** @var ReportsService */
    private ReportsService $reportsService;

    public string $moduleName = 'reports';

    public function __construct($appName, IRequest $request)
    {
        parent::__construct($appName, $request);

        $this->reportsService = ReportsService::getInstance();
        $this->setAllowedRoles([GlobalRolesModel::OFFICER, GlobalRolesModel::HEAD]);
    }

    /**
     * Get common statistics
     *
     * @param IRequest $request
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/reports/getCommonStatistics')]
    public function getCommonStatistics(IRequest $request): JSONResponse
    {
        try {
            $dateFrom = $request->getParam('date_from');
            $dateTo = $request->getParam('date_to');
            $userSlug = $request->getParam('user_slug');
            $projects = $request->getParam('projects');

            if (empty($dateFrom) || empty($dateTo)) {
                return $this->formatModuleResponse([
                    'message' => $this->translateService->getTranslate('Select interval'),
                ], 400);
            }

            $userId = !empty($userSlug) ? (new UserModel())->getItemIdBySlug($userSlug) : '';

            $filter = [
                'date' => [
                    'BETWEEN',
                    [
                        (new \DateTimeImmutable($dateFrom))->format('Y-m-d'),
                        (new \DateTimeImmutable($dateTo))->format('Y-m-d'),
                    ],
                ],
            ];

            if (!empty($projects)) {
                $filter['project_id'] = ['IN', $projects, IQueryBuilder::PARAM_STR_ARRAY];
            }

            if (!empty($userId)) {
                $filter['user_id'] = $userId;
            }

            $result = $this->reportsService->getCommonReportData($filter);

            return $this->formatModuleResponse($result);
        } catch (\Exception $e) {
            return $this->handleModuleError($e);
        }
    }

    /**
     * Get project statistics
     *
     * @param IRequest $request
     *
     * @return JSONResponse
     *
     * @throws \Exception
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER, GlobalRolesModel::HEAD])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/reports/getProjectsStatistics')]
    public function getProjectsStatistics(IRequest $request): JSONResponse
    {
        $dateFrom = $request->getParam('date_from');
        $dateTo = $request->getParam('date_to');
        $projectSlug = $request->getParam('slug');
        $userSlug = $request->getParam('user_slug');

        $projectModel = new ProjectModel();
        $projectId = !empty($projectSlug) ? $projectModel->getItemIdBySlug($projectSlug) : null;
        $userId = !empty($userSlug) ? (new UserModel())->getItemIdBySlug($userSlug) : null;

        if (empty($dateFrom) || empty($dateTo)) {
            return new JSONResponse(
                ['message' => $this->translateService->getTranslate('Select interval')],
                Http::STATUS_BAD_REQUEST
            );
        }

        $isHeadInfo = $this->userService->isHead();
        $systemFilter = [];

        if (
            $this->userService->canDoAction(GlobalRolesModel::CAN_VIEW_PROJECTS_LIST_RELATED)
            && !$this->userService->canDoAction(GlobalRolesModel::CAN_READ_PROJECTS_LIST)
            && $isHeadInfo['isHead']
        ) {
            $systemFilter['project_id'] = ['IN', $isHeadInfo['projectsIds'], IQueryBuilder::PARAM_STR_ARRAY];
        }

        return new JSONResponse(
            $this->reportsService->getProjectsStatistics(
                new \DateTimeImmutable($dateFrom),
                new \DateTimeImmutable($dateTo),
                $projectId,
                $userId,
                $systemFilter
            ),
            Http::STATUS_OK
        );
    }

    /**
     * Get common user statistics
     *
     * @param IRequest $request
     *
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[RequireRole([GlobalRolesModel::OFFICER])]
    #[FrontpageRoute(verb: 'POST', url: '/ajax/reports/getUsersCommonStatistics')]
    public function getUsersCommonStatistics(IRequest $request): JSONResponse
    {
        $dateFrom = $request->getParam('date_from');
        $dateTo = $request->getParam('date_to');
        $projects = $request->getParam('projects');
        $teams = $request->getParam('teams');
        $contractTypes = $request->getParam('contract_types');
        $directions = $request->getParam('directions');

        if (empty($dateFrom) || empty($dateTo)) {
            return new JSONResponse(
                ['message' => $this->translateService->getTranslate('Select interval')],
                Http::STATUS_BAD_REQUEST
            );
        }

        $filter = ReportsService::prepareFilterForUsersCommonStatistics(
            $dateFrom,
            $dateTo,
            !empty($projects) ? (\is_array($projects) ? $projects : [$projects]) : [],
            !empty($teams) ? (\is_array($teams) ? $teams : [$teams]) : [],
            !empty($contractTypes) ? (\is_array($contractTypes) ? $contractTypes : [$contractTypes]) : [],
            !empty($directions) ? (\is_array($directions) ? $directions : [$directions]) : [],
        );

        return new JSONResponse(
            $this->reportsService->getUsersCommonReportData($filter),
            Http::STATUS_OK
        );
    }
}
