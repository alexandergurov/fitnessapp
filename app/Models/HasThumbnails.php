<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait HasThumbnails
{
    public function generateThumbnails($field, $regenerate = FALSE) {
        $stored = json_decode($this->thumbnails, TRUE);
        if (!isset($stored[$field]) || $regenerate) {
            if ($originalUrl = $this->{$field}) {
                $filename = basename($originalUrl);
                $images = ['original' => 'storage/' . $this->{$field}];
                foreach ($this->thumbnailsSizes as $resolutionName => $resolution) {
                    $folderName = 'storage/thumbnails/' . $resolutionName;
                    $thumbnailUrl = $folderName . '/' . $filename;
                    if (!file_exists($folderName)) {
                        mkdir($folderName, 666, TRUE);
                    }
                    if (file_exists('storage/' . $originalUrl)) {
                        if (!Storage::disk('public')->exists($thumbnailUrl)) {
                            $img = Image::make('storage/' . $originalUrl)
                                        ->fit($resolution[0], $resolution[1], function ($constraint) {
                                            $constraint->upsize();
                                        });
                            $img->save($thumbnailUrl);
                        }
                    }
                    $images[$resolutionName] = $thumbnailUrl;
                }
                $stored[$field] = $images;
                $this->update(['thumbnails' => json_encode($stored)], ['thumbnails_updated' => TRUE]);
            }
        }
        else {
            $this->{$field} = $stored[$field];
        }
    }

    public function save(array $options = []) {
        if (!isset($options['thumbnails_updated'])) {
            $this->generateThumbnails('icon', TRUE);
            $this->generateThumbnails('photo', TRUE);
            $this->generateThumbnails('image', TRUE);
            $this->generateThumbnails('avatar', TRUE);
            $this->generateThumbnails('class_image', TRUE);
        }
        if (!isset($options['thumbnails_only'])) {
            parent::save($options);
        }
    }
}
