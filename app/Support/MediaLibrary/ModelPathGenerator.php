<?php

namespace App\Support\MediaLibrary;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class ModelPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getModelDirectory($media)
            . $media->getKey()
            . '/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getModelDirectory($media)
            . $media->getKey()
            . '/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getModelDirectory($media)
            . $media->getKey()
            . '/responsive-images/';
    }

    private function getModelDirectory(Media $media): string
    {
        $modelName = class_basename($media->model_type);

        return Str::pluralStudly($modelName) . '/';
    }
}
