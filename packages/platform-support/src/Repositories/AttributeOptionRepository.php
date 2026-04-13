<?php

namespace Platform\PlatformSupport\Repositories;

use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Platform\PlatformSupport\Services\SquareCanvasImageService;
use Webkul\Attribute\Repositories\AttributeOptionRepository as BaseAttributeOptionRepository;

class AttributeOptionRepository extends BaseAttributeOptionRepository
{
    public function __construct(
        Container $app,
        protected SquareCanvasImageService $squareCanvasImageService
    ) {
        parent::__construct($app);
    }

    public function create(array $data)
    {
        $option = parent::create($data);

        return $option->fresh();
    }

    public function update(array $data, $id)
    {
        $option = parent::update($data, $id);

        return $option->fresh();
    }

    public function uploadSwatchImage($data, $optionId)
    {
        if (empty($data['swatch_value']) || ! $data['swatch_value'] instanceof UploadedFile) {
            return;
        }

        $relativePath = $this->squareCanvasImageService->fromUploadedFile(
            $data['swatch_value'],
            'attribute_option'
        );

        parent::update([
            'swatch_value' => $relativePath,
        ], $optionId);
    }
}
