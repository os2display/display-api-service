<?php

namespace App\Api\Fixtures;

use App\Api\Model\Media;

/**
 * Class MediaFixtures
 *
 * Temporary class to provide testing data.
 */
class MediaFixtures {

    private array $media = [];

    /**
     * MediaFixtures constructor.
     *
     * Initialize fixture data for testing.
     */
    public function __construct()
    {
        $this->media['497f6eca-4576-4883-cfeb-53cbffba6f08'] = [
            'id' => '497f6eca-4576-4883-cfeb-53cbffba6f08',
            'title' => 'Sunset image',
            'description' => 'An image of a sunset',
            'tags' => [
                'itk',
                'example',
            ],
            'modified' => 1622556728,
            'created' => 1622550342,
            'modifiedBy' => 'Jens Jensen',
            'createdBy' => 'Ole Olesen',
            'assets' => [
              [
                'type' => 'image/png',
                'uri' => 'https://upload.wikimedia.org/wikipedia/commons/0/00/Sunset%2C_beach%2C_Northern_Territory_.png',
              ],
            ],
        ];

        $this->media['597f6eca-4576-1454-cf15-52cb3eba6b85'] = [
            'id' => '597f6eca-4576-1454-cf15-52cb3eba6b85',
            'title' => 'Video',
            'description' => 'The bunny example movie',
            'tags' => [
                'itk',
                'mock',
            ],
            'modified' => 1622554784,
            'created' => 1622556728,
            'modifiedBy' => 'Ole Olesen',
            'createdBy' => 'Jens Jensen',
            'assets' => [
                [
                    'type' => 'video/mp4',
                    'uri' => 'https://www.learningcontainer.com/wp-content/uploads/2020/05/sample-mp4-file.mp4',
                ],
            ],
        ];
    }

    public function getMedia($id) {
        $data = array_key_exists($id, $this->media) ? $this->media[$id] : null;
        if (!is_null($data)) {
            $media = new Media();
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'assets':
                        foreach ($value as $asset) {
                            $media->addAsset($asset['type'], $asset['uri']);
                        }
                        break;

                    default:
                        $func = 'set' . ucfirst($key);
                        $media->$func($value);
                }
            }
            return $media;
        }

        return null;
    }
}
