<?php

namespace App\Api\Fixtures;

use App\Api\Model\Screen;

/**
 * Class ScreenFixtures
 *
 * Temporary class to provide testing data.
 */
class ScreenFixtures {

    private array $screen = [];

    /**
     * MediaFixtures constructor.
     *
     * Initialize fixture data for testing.
     */
    public function __construct()
    {
        $this->screen['497f6eca-6276-1596-bfeb-53ceb43a6f54'] = [
            'id' => '497f6eca-6276-1596-bfeb-53ceb43a6f54',
            'title' => 'Test screen 1',
            'description' => 'This is an test screen 1 description',
            'regions' => [
                [
                    'name' => 'center',
                    'playlists' => [
                        '/v1/playlist/29ff6eca-8778-6789-bfeb-53e4bf4a6457'
                    ]
                ]
            ],
            'tags' => [
                'itk',
                'screens',
                'test'
            ],
            'modified' => 1622535248,
            'created' => 1622524267,
            'modifiedBy' => 'Jens Jensen',
            'createdBy' => 'Ole Olesen'
        ];

        $this->screen['854f6ecb-6276-6854-bfeb-53cffffa6d1e'] = [
            'id' => '854f6ecb-6276-6854-bfeb-53cffffa6d1e',
            'title' => 'Test screen 2',
            'description' => 'This is an test screen 2 description',
            'regions' => [
                [
                    'name' => 'top',
                    'playlists' => [
                        '/v1/playlist/29ff6eca-8778-6789-bfeb-53e4bf4a6457'
                    ]
                ],
                [
                    'name' => 'buttom',
                    'playlists' => [
                        '/v1/playlist/29ff6eca-8778-6789-bfeb-53e4bf4a6457'
                    ]
                ]
            ],
            'tags' => [
                'itk',
                'screens',
                'test'
            ],
            'modified' => 1622535248,
            'created' => 1622524267,
            'modifiedBy' => 'Jens Jensen',
            'createdBy' => 'Ole Olesen'
        ];
    }

    /**
     * Get screen
     *
     * @param $id
     *   Screen ID to fetch.
     *
     * @return Screen|null
     *   Screen object or null if not found.
     */
    public function getScreen($id) : ?Screen
    {
        $data = array_key_exists($id, $this->screen) ? $this->screen[$id] : null;
        if (!is_null($data)) {
            $screen = new Screen();
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'regions':
                        foreach ($value as $region) {
                            $screen->addRegion($region['name'], $region['playlists']);
                        }
                        break;

                    default:
                        $func = 'set' . ucfirst($key);
                        $screen->$func($value);
                }
            }
            return $screen;
        }

        return null;
    }

    /**
     * Get all screens
     *
     * @return array
     */
    public function getScreens(): array
    {
        $data = [];

        foreach (array_keys($this->screen) as $id) {
            $data[] = $this->getScreen($id);
        }

        return $data;
    }
}
