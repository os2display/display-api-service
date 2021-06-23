<?php

namespace App\Api\Fixtures;

use App\Api\Model\Slide;

/**
 * Class SlideFixtures
 *
 * Temporary class to provide testing data.
 */
class SlideFixtures {

    private array $slides = [];

    /**
     * MediaFixtures constructor.
     *
     * Initialize fixture data for testing.
     */
    public function __construct()
    {
        $this->slides['a97f6ec4-5278-4993-bfeb-53cdedea6d10'] = [
            'id' => 'a97f6ec4-5278-4993-bfeb-53cdedea6d10',
            'title' => 'Image Slide',
            'description' => 'This is the first slide named one',
            'tags' => [
                'itk',
                'mock',
                'example'
            ],
            'modified' => 1622555123,
            'created' => 1622554031,
            'modifiedBy' => 'Ole Olesen',
            'createdBy' => 'Jens Jensen',
            'template' => [
                '@id' => '/v1/template/457d6ecb-6378-4299-bfcb-53cbaaaa6f65',
                'options' => [
                    [
                        'fade' => false
                    ],
                    [
                        'icon' => true,
                        'url' => 'http=>//test.local.itkdev.dk/icon.png'
                    ]
                ]
            ],
            'duration' => 45,
            'content' => [
                'text' => 'This is slide content',
                'media' => '497f6eca-4576-4883-cfeb-53cbffba6f08'
            ],
            'published' => 1622555123
        ];

        $this->slides['787f6ece-6276-5982-beeb-53cba4f36f12'] = [
            'id' => '787f6ece-6276-5982-beeb-53cba4f36f12',
            'title' => 'Video Slide',
            'description' => 'This is the next slide named two',
            'tags' => [
                'itk',
                'mock',
                'example'
            ],
            'modified' => 1622555475,
            'created' => 1622553404,
            'modifiedBy' => 'Jens Jensen',
            'createdBy' => 'Ole Olesen',
            'template' => [
                '@id' => '/v1/template/457d6ecb-6378-4299-bfcb-53cbaaaa6f65',
                'options' => [
                    [
                        'fade' => true
                    ]
                ]
            ],
            'duration' => 90,
            'content' => [
                'media' => '/v1/media/597f6eca-4576-1454-cf15-52cb3eba6b85'
            ],
            'published' => 1622555475
        ];
    }

    /**
     * Get slide
     *
     * @param $id
     *   Slide ID to fetch.
     *
     * @return Slide|null
     *   Media object or null if not found.
     */
    public function getSlide($id) : ?Slide
    {
        $data = array_key_exists($id, $this->slides) ? $this->slides[$id] : null;
        if (!is_null($data)) {
            $slide = new Slide();
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'template':
                        $slide->addTemplate($value['@id'], $value['options']);
                        break;

                    default:
                        $func = 'set' . ucfirst($key);
                        $slide->$func($value);
                }
            }
            return $slide;
        }

        return null;
    }

    /**
     * Get all slides
     *
     * @return array
     */
    public function getSlides(): array
    {
        $data = [];

        foreach (array_keys($this->slides) as $id) {
            $data[] = $this->getSlide($id);
        }

        return $data;
    }
}
