<?php

namespace Skyblock;

use ZipArchive;

final class WorldCloner {

    /**
     * Bir .mcworld dosyasını (zip formatında) verilen klasöre açar.
     */
    public static function unzipMcWorld(string $zipFile, string $destDir) : bool {
        $zip = new ZipArchive();
        if($zip->open($zipFile) !== true){
            return false;
        }
        $ok = $zip->extractTo($destDir);
        $zip->close();
        return $ok;
    }

    /**
     * Açılmış klasör içinde level.dat dosyasını arar ve dünya kök klasörünü döner.
     */
    public static function detectWorldRoot(string $extractedDir) : ?string {
        // Case 1: level.dat direkt extractedDir içinde
        if(is_file($extractedDir . "/level.dat")){
            return $extractedDir;
        }
        // Case 2: alt klasörlerde arama
        $entries = array_diff(scandir($extractedDir), ['.', '..']);
        foreach($entries as $entry){
            $path = $extractedDir . "/" . $entry;
            if(is_dir($path) && is_file($path . "/level.dat")){
                return $path;
            }
        }
        return null;
    }

    /**
     * Bir klasörü (src) hedef klasöre (dst) kopyalar.
     */
    public static function copyDirectory(string $src, string $dst) : void {
        @mkdir($dst, 0777, true);
        foreach(scandir($src) as $item){
            if($item === '.' || $item === '..') continue;
            $srcPath = $src . "/" . $item;
            $dstPath = $dst . "/" . $item;
            if(is_dir($srcPath)){
                self::copyDirectory($srcPath, $dstPath);
            } else {
                @copy($srcPath, $dstPath);
            }
        }
    }

    /**
     * Bir klasörü ve içindekileri tamamen siler.
     */
    public static function deleteDirectory(string $dir) : void {
        if(!is_dir($dir)) return;
        foreach(scandir($dir) as $item){
            if($item === '.' || $item === '..') continue;
            $path = $dir . "/" . $item;
            if(is_dir($path)){
                self::deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

