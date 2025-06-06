<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\minion;

use pocketmine\entity\Skin;

trait MinionSkinTrait {
    protected function loadCustomSkin(): void {
        $skinPath = $this->plugin->getDataFolder() . "resources/skins/" . $this->minionType . ".png";
        
        if (file_exists($skinPath)) {
            try {
                $skinData = $this->convertPngToSkinData($skinPath);
                if ($skinData !== null) {
                    $skin = new Skin($this->minionType . "_minion", $skinData);
                    $this->setSkin($skin);
                    return;
                }
            } catch (\Exception $e) {
                $this->plugin->getLogger()->warning("Failed to load minion skin: " . $e->getMessage());
            }
        }
        $this->setSkin($this->getDefaultSkin());
    }

    private function convertPngToSkinData(string $pngPath): ?string {
        if (!extension_loaded('gd')) {
            $this->plugin->getLogger()->warning("GD extension not loaded, cannot process PNG skins");
            return null;
        }

        $image = imagecreatefrompng($pngPath);
        if (!$image) {
            return null;
        }
        if (imagesx($image) !== 64 || imagesy($image) !== 64) {
            imagedestroy($image);
            return null;
        }

        $skinData = "";
        for ($y = 0; $y < 64; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                $a = (imagecolorsforindex($image, $color)['alpha'] ?? 0) === 127 ? 0 : 255;
                
                $skinData .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        imagedestroy($image);
        return $skinData;
    }

    protected function getDefaultSkin(): Skin {
        $color = $this->getMinionColor();
        $skinData = str_repeat($color, 64 * 64);
        
        return new Skin("default_" . $this->minionType, $skinData);
    }

    protected function getMinionColor(): string {
        if ($this->profession !== null) {
            switch ($this->profession->getColor()) {
                case "§a":
                    return "\x00\xFF\x00\xFF";
                case "§7":
                    return "\x7F\x7F\x7F\xFF";
                case "§6":
                    return "\xFF\xD7\x00\xFF";
                case "§b":
                    return "\x00\xFF\xFF\xFF";
                default:
                    return "\x8B\x69\x3D\xFF";
            }
        }
        return "\x8B\x69\x3D\xFF";
    }
}