<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Notification;

use OCA\Done\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier
{
    public const SUBJECT_AGREEMENT_PENDING = 'agreement_request_pending';

    private IFactory $l10nFactory;
    private IURLGenerator $urlGenerator;

    public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator)
    {
        $this->l10nFactory = $l10nFactory;
        $this->urlGenerator = $urlGenerator;
    }

    public function getID(): string
    {
        return Application::APP_ID;
    }

    public function getName(): string
    {
        return 'Done';
    }

    public function prepare(INotification $notification, string $languageCode): INotification
    {
        if ($notification->getApp() !== Application::APP_ID) {
            throw new \InvalidArgumentException();
        }

        $l = $this->l10nFactory->get(Application::APP_ID, $languageCode);
        $params = $notification->getSubjectParameters();

        switch ($notification->getSubject()) {
            case self::SUBJECT_AGREEMENT_PENDING:
                $title = $params['title'] ?? '';
                $author = $params['author'] ?? '';
                $vacationId = $params['vacation_id'] ?? '';

                $notification
                    ->setIcon($this->urlGenerator->getAbsoluteURL(
                        $this->urlGenerator->imagePath(Application::APP_ID, 'done-dark.svg')
                    ))
                    ->setParsedSubject(
                        $author !== ''
                            ? $l->t('Approval request from %s', [$author])
                            : $l->t('Approval request')
                    )
                    ->setParsedMessage(
                        $title !== ''
                            ? $l->t('A request is waiting for your approval: %s', [$title])
                            : $l->t('A request is waiting for your approval')
                    );

                if ($vacationId !== '') {
                    $base = rtrim($this->urlGenerator->linkToRoute('done.common.index'), '/');
                    $notification->setLink($this->urlGenerator->getAbsoluteURL(
                        $base . '/vacations/requests/' . $vacationId . '/'
                    ));
                }

                return $notification;

            default:
                throw new \InvalidArgumentException();
        }
    }
}
