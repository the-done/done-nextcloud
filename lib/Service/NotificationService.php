<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

namespace OCA\Done\Service;

use OCA\Done\AppInfo\Application;
use OCP\Notification\IManager;
use OCP\Server;

/**
 * Generic notification helper for the Done app.
 *
 * Wraps Nextcloud's notification framework so that modules don't have to talk
 * to IManager directly. Resolution of subjectId into a user-facing string
 * happens in {@see \OCA\Done\Notification\Notifier}.
 */
class NotificationService
{
    private IManager $manager;
    private static ?NotificationService $instance = null;

    public function __construct(IManager $manager)
    {
        $this->manager = $manager;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(Server::get(IManager::class));
        }

        return self::$instance;
    }

    /**
     * Send a notification to a single user.
     *
     * @param string      $userId            Recipient user ID
     * @param string      $subjectId         Subject identifier handled by {@see Notifier}
     * @param array       $subjectParameters Parameters passed to the Notifier
     * @param string      $objectType        Used to group/dedupe notifications (e.g. 'agreement_request')
     * @param string      $objectId          Object ID (e.g. request ID)
     * @param null|string $link              Optional absolute or relative URL to open on click
     */
    public function send(
        string $userId,
        string $subjectId,
        array $subjectParameters = [],
        string $objectType = '',
        string $objectId = '',
        ?string $link = null
    ): void {
        if ($userId === '') {
            return;
        }

        $notification = $this->manager->createNotification();

        $notification
            ->setApp(Application::APP_ID)
            ->setUser($userId)
            ->setDateTime(new \DateTime())
            ->setSubject($subjectId, $subjectParameters);

        if ($objectType !== '' && $objectId !== '') {
            $notification->setObject($objectType, $objectId);
        } else {
            // setObject is required by Nextcloud; fall back to subjectId so each notification is still unique.
            $notification->setObject($subjectId, $objectId !== '' ? $objectId : uniqid('', true));
        }

        if ($link !== null && $link !== '') {
            $notification->setLink($link);
        }

        $this->manager->notify($notification);
    }

    /**
     * Send the same notification to a list of users. Duplicate and empty entries are skipped.
     *
     * @param string[] $userIds
     */
    public function sendMany(
        array $userIds,
        string $subjectId,
        array $subjectParameters = [],
        string $objectType = '',
        string $objectId = '',
        ?string $link = null
    ): void {
        $seen = [];

        foreach ($userIds as $userId) {
            if (!\is_string($userId) || $userId === '' || isset($seen[$userId])) {
                continue;
            }
            $seen[$userId] = true;

            $this->send($userId, $subjectId, $subjectParameters, $objectType, $objectId, $link);
        }
    }

    /**
     * Mark all notifications matching ($objectType, $objectId) as resolved for every user.
     * Useful when a request reaches a terminal state and pending alerts become stale.
     */
    public function clearForObject(string $objectType, string $objectId): void
    {
        if ($objectType === '' || $objectId === '') {
            return;
        }

        $notification = $this->manager->createNotification();
        $notification
            ->setApp(Application::APP_ID)
            ->setObject($objectType, $objectId);

        $this->manager->markProcessed($notification);
    }
}
