<?php
/**
 * Logos personnalisables pour les canevas (page de garde + RECAP TECHN)
 */

class CanevasLogoManager
{
    public const LOGO_DIR = __DIR__ . '/../uploads/canevas_logos';

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

    public const MAX_SIZE = 2 * 1024 * 1024;

    /** @var array<string, array{label: string, hint: string}> */
    public const SLOTS = [
        'logo_gauche' => [
            'label' => 'Logo gauche',
            'hint' => 'En-tête haut gauche (page de garde)',
        ],
        'logo_droit' => [
            'label' => 'Logo droit',
            'hint' => 'En-tête haut droit + feuille RECAP TECHN (gauche)',
        ],
        'logo_centre' => [
            'label' => 'Logo central',
            'hint' => 'Logo principal au centre de la page de garde',
        ],
        'logo_recap' => [
            'label' => 'Logo RECAP',
            'hint' => 'Feuille RECAP TECHN (droite)',
        ],
    ];

    public function __construct()
    {
        if (!is_dir(self::LOGO_DIR)) {
            mkdir(self::LOGO_DIR, 0755, true);
        }
    }

    public function listLogos()
    {
        $logos = [];
        foreach (self::SLOTS as $slot => $meta) {
            $path = $this->findSlotPath($slot);
            $logos[$slot] = [
                'slot' => $slot,
                'label' => $meta['label'],
                'hint' => $meta['hint'],
                'uploaded' => $path !== null,
                'filename' => $path ? basename($path) : null,
                'updated_at' => $path ? date('c', filemtime($path)) : null,
            ];
        }
        return $logos;
    }

    public function getSlotPath($slot)
    {
        if (!isset(self::SLOTS[$slot])) {
            return null;
        }
        return $this->findSlotPath($slot);
    }

    public function upload($slot, array $file)
    {
        if (!isset(self::SLOTS[$slot])) {
            throw new Exception('Emplacement de logo invalide.');
        }
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors du téléversement du fichier.');
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw new Exception('Fichier trop volumineux (max 2 Mo).');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Format non autorisé. Utilisez JPG, PNG ou GIF.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        if (!in_array($mime, $allowedMimes, true)) {
            throw new Exception('Le fichier doit être une image.');
        }

        $this->removeSlotFiles($slot);
        $dest = self::LOGO_DIR . DIRECTORY_SEPARATOR . $slot . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception('Impossible d\'enregistrer le logo.');
        }

        return [
            'slot' => $slot,
            'filename' => basename($dest),
        ];
    }

    public function delete($slot)
    {
        if (!isset(self::SLOTS[$slot])) {
            throw new Exception('Emplacement de logo invalide.');
        }
        $this->removeSlotFiles($slot);
    }

    /**
     * Remplace les images du modèle Excel par les logos uploadés.
     */
    public function applyToSpreadsheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet)
    {
        $paths = $this->getAllPaths();
        if (empty($paths)) {
            return;
        }

        $coordinateMap = [
            'page de garde' => [
                'A1' => ['slot' => 'logo_gauche', 'width' => 91, 'height' => 57],
                'H3' => ['slot' => 'logo_droit', 'width' => 129, 'height' => 104],
                'C6' => ['slot' => 'logo_centre', 'width' => 298, 'height' => 320],
            ],
            'RECAP TECHN' => [
                'B1' => ['slot' => 'logo_droit', 'width' => 128, 'height' => 100],
                'E1' => ['slot' => 'logo_recap', 'width' => 169, 'height' => 198],
            ],
        ];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $title = $sheet->getTitle();
            if (!isset($coordinateMap[$title])) {
                continue;
            }
            $map = $coordinateMap[$title];
            foreach ($sheet->getDrawingCollection() as $drawing) {
                $coord = $drawing->getCoordinates();
                if (!isset($map[$coord])) {
                    continue;
                }
                $slot = $map[$coord]['slot'];
                if (empty($paths[$slot])) {
                    continue;
                }
                $drawing->setPath($paths[$slot]);
                $drawing->setWidth($map[$coord]['width']);
                $drawing->setHeight($map[$coord]['height']);
            }
        }
    }

    private function getAllPaths()
    {
        $paths = [];
        foreach (array_keys(self::SLOTS) as $slot) {
            $path = $this->findSlotPath($slot);
            if ($path) {
                $paths[$slot] = $path;
            }
        }
        return $paths;
    }

    private function findSlotPath($slot)
    {
        foreach (self::ALLOWED_EXTENSIONS as $ext) {
            $path = self::LOGO_DIR . DIRECTORY_SEPARATOR . $slot . '.' . $ext;
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function removeSlotFiles($slot)
    {
        foreach (self::ALLOWED_EXTENSIONS as $ext) {
            $path = self::LOGO_DIR . DIRECTORY_SEPARATOR . $slot . '.' . $ext;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
