<?php

declare(strict_types=1);

namespace OCA\Done\Service;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;

/**
 * Universal service for working with files in Done application
 */
class FileService
{
    private static FileService $instance;

    /** @var IAppData */
    private IAppData $appData;

    // Allowed extensions for all files (lowercase only)
    private array $enabledExt = [
        'bmp', 'csv', 'doc', 'docx', 'gif', 'ico', 'jpg', 'jpeg', 'odg', 'odp', 'ods', 'odt',
        'pdf', 'png', 'ppt', 'swf', 'txt', 'xcf', 'xls', 'xlsx', 'zip'
    ];

    // Allowed MIME types for all files
    private array $enabledMimeTypes = [
        'image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'application/x-shockwave-flash',
        'application/msword', 'application/excel', 'application/pdf', 'application/powerpoint',
        'text/plain', 'application/x-zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/svg+xml', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ];

    // Maximum file size (10MB)
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function __construct(
        IAppData $appData
    ) {
        $this->appData = $appData;
    }

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            $container = \OC::$server->get(\OCP\IServerContainer::class);
            self::$instance = $container->get(\OCA\Done\Service\FileService::class);
        }
        return self::$instance;
    }

    /**
     * Validate any file
     */
    public function validateFile($file): bool
    {
        if (empty($file) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        // File size check
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return false;
        }

        // Extension check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->enabledExt)) {
            return false;
        }

        // MIME type check
        if (!in_array($file['type'], $this->enabledMimeTypes)) {
            return false;
        }

        return true;
    }

    /**
     * Save file to AppData
     */
    public function saveFileToAppData(string $folderPath, $file, string $subFolder = ''): string
    {
        try {
            // Create main folder
            $mainFolder = $this->appData->newFolder($folderPath);
            
            // Create subfolder if specified
            $targetFolder = $mainFolder;
            if (!empty($subFolder)) {
                try {
                    $targetFolder = $mainFolder->getFolder($subFolder);
                } catch (NotFoundException $e) {
                    $targetFolder = $mainFolder->newFolder($subFolder);
                }
            }

            // Clean and create unique file name with timestamp
            $originalName = $file['name'];
            $cleanName = $this->cleanFileName($originalName);
            $uniqueName = $this->createUniqueFileName($cleanName);

            // Save file
            $fileNode = $targetFolder->newFile($uniqueName);
            $fileNode->putContent(file_get_contents($file['tmp_name']));

            return $uniqueName;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Delete file from AppData
     */
    public function deleteFileFromAppData(string $folderPath, string $subFolder, string $fileName): bool
    {
        try {
            $mainFolder = $this->appData->getFolder($folderPath);
            $targetFolder = $mainFolder->getFolder($subFolder);
            $fileNode = $targetFolder->getFile($fileName);
            $fileNode->delete();
            return true;
        } catch (NotFoundException $e) {
            // File no longer exists, this is normal
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get file from AppData
     */
    public function getFile(string $folderPath, string $subFolder, string $fileName): ?array
    {
        try {
            $mainFolder = $this->appData->getFolder($folderPath);
            $targetFolder = $mainFolder->getFolder($subFolder);
            $fileNode = $targetFolder->getFile($fileName);

            return [
                'content' => $fileNode->getContent(),
                'mimeType' => $this->getMimeTypeByExtension(pathinfo($fileName, PATHINFO_EXTENSION)),
                'size' => $fileNode->getSize(),
                'name' => $fileName
            ];
        } catch (NotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clean file name
     */
    public function cleanFileName(string $fileName): string
    {
        $fileName = str_replace(' ', '-', $fileName);
        $fileName = $this->transliterate($fileName, true);
        return (string)preg_replace('/[^a-zA-Z0-9._\-]/ui', '', $fileName);
    }

    /**
     * Create unique file name with timestamp
     */
    public function createUniqueFileName(string $fileName): string
    {
        $pathInfo = pathinfo($fileName);
        $name = $pathInfo['filename'];
        $ext = $pathInfo['extension'] ?? '';
        $ext = $ext ? '.' . $ext : '';

        // Create timestamp with microseconds for uniqueness
        // Use sprintf to avoid scientific notation
        $timestamp = sprintf('%.0f', microtime(true) * 1000000);
        
        return $name . '_' . $timestamp . $ext;
    }



    /**
     * Determine MIME type by extension
     */
    public function getMimeTypeByExtension(string $extension): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        $ext = strtolower($extension);
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }

    /**
     * Transliterate string to Latin
     */
    public function transliterate($string, $forFiles = false): string
    {
        $glyph_array = [
            'a'    => 'à,á,â,ã,ä,å,ā,ă,ą,ḁ,α,ά,а',
            'ae'   => 'æ',
            'b'    => 'β,б',
            'c'    => 'ç,ć,ĉ,ċ,č,ћ,ц',
            'ch'   => 'ч',
            'd'    => 'ď,đ,Ð,д,ђ,δ,ð',
            'dz'   => 'џ',
            'e'    => 'è,é,ê,ë,ē,ĕ,ė,ę,ě,э,ε,έ,е,є',
            'f'    => 'ƒ,ф',
            'g'    => 'ğ,ĝ,ğ,ġ,ģ,г,γ,ґ',
            'h'    => 'ĥ,ħ,Ħ,х',
            'i'    => 'ì,í,î,ï,ı,ĩ,ī,ĭ,į,и,й,ъ,ы,ь,η,ή,і',
            'ij'   => 'ĳ',
            'j'    => 'ĵ',
            'ja'   => 'я',
            'ju'   => 'яю',
            'k'    => 'ķ,ĸ,κ,к',
            'l'    => 'ĺ,ļ,ľ,ŀ,ł,л,λ',
            'lj'   => 'љ',
            'm'    => 'μ,м',
            'n'    => 'ñ,ņ,ň,ŉ,ŋ,н,ν',
            'nj'   => 'њ',
            'o'    => 'ö,ò,ó,ô,õ,ø,ō,ŏ,ő,ο,ό,ω,ώ,о',
            'oe'   => 'œ',
            'p'    => 'п,π',
            'ph'   => 'φ',
            'ps'   => 'ψ',
            'r'    => 'ŕ,ŗ,ř,р,ρ,σ,ς',
            's'    => 'ş,ś,ŝ,ş,š,с',
            'ss'   => 'ß,ſ',
            'sh'   => 'ш',
            'shch' => 'щ',
            't'    => 'ţ,ť,ŧ,τ,т',
            'th'   => 'θ',
            'u'    => 'ù,ú,û,ü,ũ,ū,ŭ,ů,ű,ų,у',
            'v'    => 'в',
            'w'    => 'ŵ',
            'x'    => 'χ,ξ',
            'y'    => 'ý,þ,ÿ,ŷ',
            'z'    => 'ź,ż,ž,з,ж,ζ',
            'jo'   => 'ё',
            'zh'   => 'ж',
            'ji'   => 'ї',
            'kh'   => 'х',
            'ts'   => 'ц',
            'yu'   => 'ю',
            'ya'   => 'я',
            ''     => 'ъ,ь',
        ];

        if ($forFiles) {
            $glyph_array['_'] = '№,–,—';
        }

        $str = mb_strtolower($string);

        foreach ($glyph_array as $letter => $glyphs) {
            $glyphs = explode(',', $glyphs);
            $str = str_replace($glyphs, $letter, $str);
        }

        return $str;
    }
}
