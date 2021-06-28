<?php

namespace App\Api\Fixtures;

use App\Api\Model\Playlist;

/**
 * Class PlaylistFixtures.
 *
 * Temporary class to provide testing data.
 */
class PlaylistFixtures
{
    private array $playlists = [];

    /**
     * MediaFixtures constructor.
     *
     * Initialize fixture data for testing.
     */
    public function __construct()
    {
        $this->playlists['29ff6eca-8778-6789-bfeb-53e4bf4a6457'] = [
            'id' => '29ff6eca-8778-6789-bfeb-53e4bf4a6457',
            'title' => 'The first playlist',
            'description' => 'This is an playlist with image and video',
            'tags' => [
                'itk',
                'video',
                'mock',
            ],
            'modified' => 1622557486,
            'created' => 1622557262,
            'modifiedBy' => 'Jens Jensen',
            'createdBy' => 'Ole Olesen',
            'slides' => [
                [
                    '@id' => '/v1/slide/497f6eca-4576-4883-cfeb-53cbffba6f08',
                    'weight' => 5,
                    'duration' => 10,
                ],
                [
                    '@id' => '/v1/slide/597f6eca-4576-1454-cf15-52cb3eba6b85',
                    'weight' => 10,
                    'duration' => 125,
                ],
            ],
            'published' => [
                'from' => 1622557262,
                'to' => 1622588254,
            ],
        ];
    }

    /**
     * Get playlist.
     *
     * @param $id
     *   ID of the playlist
     *
     * @return playlist|null The playlist if found else null
     */
    public function getPlaylist($id): ?Playlist
    {
        $data = array_key_exists($id, $this->playlists) ? $this->playlists[$id] : null;
        if (!is_null($data)) {
            $playlist = new Playlist();
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'slides':
                        foreach ($value as $slide) {
                            $playlist->addSlide($slide['@id'], $slide['weight'], $slide['duration']);
                        }
                        break;

                    case 'published':
                        $playlist->addPublished($value['from'], $value['to']);
                        break;

                    default:
                        $func = 'set'.ucfirst($key);
                        $playlist->$func($value);
                }
            }

            return $playlist;
        }

        return null;
    }

    /**
     * Get all playlists.
     */
    public function getPlaylists(): array
    {
        $data = [];

        foreach (array_keys($this->playlists) as $id) {
            $data[] = $this->getPlaylist($id);
        }

        return $data;
    }
}
