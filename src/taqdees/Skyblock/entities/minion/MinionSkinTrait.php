<?php

declare(strict_types=1);

namespace taqdees\Skyblock\entities\minion;

use pocketmine\entity\Skin;
use taqdees\Skyblock\entities\MinionTypes\mining\CobblestoneMinion;
use taqdees\Skyblock\entities\MinionTypes\farming\WheatMinion;
use taqdees\Skyblock\entities\MinionTypes\foraging\OakMinion;
use taqdees\Skyblock\minions\MinionRegistry;

trait MinionSkinTrait {

    private function getMinionSkinCategory(): string {
        $class = MinionRegistry::getClass($this->minionType);
        if ($class === null) return 'mining';

        return match(true) {
            is_a($class, CobblestoneMinion::class, true) => 'mining',
            is_a($class, WheatMinion::class, true)       => 'farming',
            is_a($class, OakMinion::class, true)         => 'foraging',
            default                                       => 'mining',
        };
    }

    protected function loadCustomSkin(): void {
        $category = $this->getMinionSkinCategory();
        $skinPath = $this->plugin->getDataFolder() . "skins/minions/{$category}/{$this->minionType}.png";

        if (!file_exists($skinPath)) {
            $this->setSkin($this->getDefaultSkin());
            return;
        }

        if (!extension_loaded('gd')) {
            $this->plugin->getLogger()->warning("GD extension not loaded, cannot process PNG skins");
            $this->setSkin($this->getDefaultSkin());
            return;
        }

        try {
            $image = imagecreatefrompng($skinPath);
            if ($image === false) {
                throw new \RuntimeException("imagecreatefrompng failed");
            }

            if (!imageistruecolor($image)) {
                $trueColor = imagecreatetruecolor(imagesx($image), imagesy($image));
                imagealphablending($trueColor, false);
                imagesavealpha($trueColor, true);
                $transparent = imagecolorallocatealpha($trueColor, 0, 0, 0, 127);
                imagefill($trueColor, 0, 0, $transparent);
                imagecopy($trueColor, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                imagedestroy($image);
                $image = $trueColor;
            }

            imagesavealpha($image, true);
            imagealphablending($image, false);

            $width  = imagesx($image);
            $height = imagesy($image);

            if ($width !== 64 || !in_array($height, [64, 128], true)) {
                imagedestroy($image);
                throw new \RuntimeException("Invalid skin size {$width}x{$height}, must be 64x64 or 64x128");
            }

            $skinData = "";
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $rgba = imagecolorat($image, $x, $y);
                    $r = ($rgba >> 16) & 0xFF;
                    $g = ($rgba >> 8)  & 0xFF;
                    $b = $rgba         & 0xFF;
                    $a = (int) round((127 - (($rgba >> 24) & 0x7F)) / 127 * 255);
                    $skinData .= chr($r) . chr($g) . chr($b) . chr($a);
                }
            }

            imagedestroy($image);
            $this->setSkin(new Skin("minion_{$this->minionType}", $skinData));

        } catch (\Exception $e) {
            $this->plugin->getLogger()->warning("Failed to load skin for minion '{$this->minionType}': " . $e->getMessage());
            $this->setSkin($this->getDefaultSkin());
        }
    }

    protected function getDefaultSkin(): Skin {
        $skinData = str_repeat($this->getMinionColor(), 64 * 64);
        return new Skin("default_" . $this->minionType, $skinData);
    }

    protected function getMinionColor(): string {
        if ($this->profession !== null) {
            return match($this->profession->getColor()) {
                "§a"    => "\x00\xFF\x00\xFF",
                "§7"    => "\x7F\x7F\x7F\xFF",
                "§6"    => "\xFF\xD7\x00\xFF",
                "§b"    => "\x00\xFF\xFF\xFF",
                default => "\x8B\x69\x3D\xFF",
            };
        }
        return "\x8B\x69\x3D\xFF";
    }
}