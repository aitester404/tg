<?php

namespace Skyblock;

use ZipArchive;

final class WorldCloner {

    public static function unzipMcWorld(string $zipFile, string $destDir) : bool {
        $zip = new ZipArchive();
        if($zip->open($zipFile) !== true){
            return false;
        }
        $ok = $zip->extractTo($destDir);
        $zip->close();
        return $ok;
    }

    public static function detectWorldRoot(string $extractedDir) : ?string {
        if(is_file($extractedDir . "/level.dat")){
            return $extractedDir;
        }
        $entries = array_diff(scandir($extractedDir), ['.', '..']);
        foreach($entries as $entry){
            $path = $extractedDir . "/" . $entry;
            if(is_dir($path) && is_file($path . "/level.dat")){
                return $path;
            }
        }
        return null;
    }

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

    public static function deleteDirectory(string $dir) : void {
        if(!is_dir($dir)) return;
        foreach(scandir($dir) as $item){
            if($item === '.' ||
