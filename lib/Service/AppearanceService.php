<?php

declare(strict_types=1);

namespace OCA\Done\Service;

use OCA\Done\Models\PermissionsEntities_Model;
use OCA\Done\Modules\Projects\Models\ProjectAppearance_Model;
use OCA\Done\Service\FileService;
use OCA\Done\Service\TranslateService;
use OCP\Server;

/**
 * Service for working with entity appearance (ProjectAppearance, UserAppearance, Tea)
 */
class AppearanceService
{
    private static AppearanceService $instance;

    /** @var FileService */
    private FileService $fileService;

    /** @var TranslateService */
    private TranslateService $translateService;

    public function __construct(
        FileService $fileService,
        TranslateService $translateService
    ) {
        $this->fileService      = $fileService;
        $this->translateService = $translateService;
        $this->appearanceModel  = new ProjectAppearance_Model();
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = Server::get(AppearanceService::class);
        }

        return self::$instance;
    }

    /**
     * Save project color
     *
     * @param string $entityId
     * @param string $color Color in hex format (#RRGGBB or #RGB)
     * @param int    $source
     * @return array Operation result
     */
    public function saveEntityColor(string $entityId, string $color, int $source): array
    {
        $sourceData = PermissionsEntities_Model::getPermissionsEntities($source);
        $model = new $sourceData[$source]['model']();
        $entityKey =$sourceData[$source]['foreign_key'];
        $appearanceModel = new $model->appearanceModel();

        if (!$this->validateColor($color)) {
            return [
                'success' => false,
                'message' => 'Invalid color format',
            ];
        }

        $data = [
            $entityKey => $entityId,
            'color'      => $color,
        ];

        try {
            $appearanceModel->upsertByFilter($data, [$entityKey => $entityId]);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update database',
            ];
        }

        return [
            'success' => true,
            'color'   => $color,
            'message' => 'Color saved successfully',
        ];
    }

    /**
     * Method for saving project images
     *
     * @param string $projectId Project hash
     * @param array $uploadedFile Uploaded file
     * @param string $imageField File field in DB table // 'avatar', 'bg_image', 'symbol'
     *
     * @return array Operation result
     */
    public function saveEntityImage(string $slug, array $uploadedFile, string $imageField, int $source): array
    {
        $sourceData = PermissionsEntities_Model::getPermissionsEntities($source);
        $model = new $sourceData[$source]['model']();
        $entityKey =$sourceData[$source]['foreign_key'];
        $entityId = $model->getItemIdBySlug($slug);
        $appearanceModel = new $model->appearanceModel();

        // 1. File validation (only images)
        if (!$this->fileService->validateFile($uploadedFile)) {
            return [
                'success' => false,
                'message' => 'Invalid file type or file too large',
            ];
        }

        // 2. Get existing record
        $existingRecord = $this->getExistingRecord($entityId, $entityKey);
        $oldFileName    = null;

        // Remember old file name for subsequent deletion
        if (!empty($existingRecord) && !empty($existingRecord[$imageField])) {
            $oldFileName = $existingRecord[$imageField];
        }

        // 3. Save new file to AppData
        $fileName = $this->fileService->saveFileToAppData($entityId, $uploadedFile, $imageField);

        // Check that file was actually saved
        if (empty($fileName)) {
            return [
                'success' => false,
                'message' => 'Failed to save file',
            ];
        }

        // 4. Upsert record in DB (create or update)
        $data = [
            $entityKey => $entityId,
            $imageField  => $fileName,
        ];

        try {
            $appearanceModel->upsertByFilter($data, [$entityKey => $entityId]);
        } catch (\Exception $e) {
            // If failed to update DB, delete uploaded file
            $this->fileService->deleteFileFromAppData($entityId, $imageField, $fileName);

            return [
                'success' => false,
                'message' => 'Failed to update database',
            ];
        }

        // 5. Delete old file only after successful new file save
        if ($oldFileName !== null && $oldFileName !== $fileName) {
            $this->fileService->deleteFileFromAppData($entityId, $imageField, $oldFileName);
        }

        return [
            'success'  => true,
            'fileName' => $fileName,
            'message'  => 'File saved successfully',
        ];
    }

    /**
     * Get existing project record
     *
     * @param string $projectId Project ID
     * @param string $entityKey Entity key
     *
     * @return array|null Existing record or null
     */
    private function getExistingRecord(string $projectId, string $entityKey): ?array
    {
        return $this->appearanceModel->getItemByFilter([$entityKey => $projectId]);
    }

    /**
     * Color validation (hex format)
     *
     * @param string $color Color to validate
     *
     * @return bool Validation result
     */
    private function validateColor(string $color): bool
    {
        // Check hex format (#RRGGBB or #RGB)
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }

    /**
     * Get project file
     *
     * @param string $entityId Entity ID
     * @param string $fileType File type // 'avatar', 'bg_image', 'symbol'
     * @param string $fileName File name
     *
     * @return array|null File data or null
     */
    public function getEntityFile(string $entityId, string $fileType, string $fileName): ?array
    {
        return $this->fileService->getFile($entityId, $fileType, $fileName);
    }
}
