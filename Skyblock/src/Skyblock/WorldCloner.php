<?php

namespace Skyblock;

use ZipArchive;

final class WorldCloner {

    public static function unzipMcWorld(string $zipFile, string $destDir) : bool {
        $zip = new ZipArchive();
        $res = $zip->open($zipFile);
        if($res !== true){
            return false;
        }
        $ok = $zip->extractTo($destDir);
        $zip->close();
        return $ok;
    }

    /**
     * Detects the root world directory (must contain level.dat) inside extracted folder.
     */
    public static function detectWorldRoot(string $extractedDir) : ?string {
        // Case 1: level.dat directly in extractedDir
        if(is_file($extractedDir . "/level.dat")){
            return $extractedDir;
        }
        // Case 2: inside a single subfolder
        $entries = array_values(array_filter(scandir($extractedDir), function($f){
            return $f !== '.' && $f !== '..';
        }));
        foreach($entries as $entry){
            $path = $extractedDir . "/" . $entry;
            if(is_dir($path) && is_file($path . "/level.dat")){
                return $path;
            }
        }
        return null;
    }

    public static function copyDirectory(string $src, string $dst) : bool {
        if(!is_dir($src)){
            return false;
        }
        @mkdir($dst, 0777, true);
        $items = scandir($src);
        foreach($items as $item){
            if($item === '.' || $item === '..') continue;
            $srcPath = $src . "/" . $item;
            $dstPath = $dst . "/" . $item;
            if(is_dir($srcPath)){
                if(!self::copyDirectory($srcPath, $dstPath)){
                    return false;
                }
            }else{
                if(!@copy($srcPath, $dstPath)){
                    return false;
                }
            }
        }
        return true;
    }

    public static function deleteDirectory(string $dir) : void {
        if(!is_dir($dir)) return;
        $items = scandir($dir);
        foreach($items as $item){
            if($item === '.' || $item === '..') continue;
            $path = $dir . "/" . $item;
            if(is_dir($path)){
                self::deleteDirectory($path);
            }else{
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
