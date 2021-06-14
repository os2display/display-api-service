<?php

namespace App\Api\Fixtures;

use App\Api\Model\Template;

/**
 * Class TemplateFixtures
 *
 * Temporary class to provide testing data.
 */
class TemplateFixtures {

    private array $templates = [];

    /**
     * MediaFixtures constructor.
     *
     * Initialize fixture data for testing.
     */
    public function __construct()
    {
        $this->templates['457d6ecb-6378-4299-bfcb-53cbaaaa6f65'] = [
            'id' => '457d6ecb-6378-4299-bfcb-53cbaaaa6f65',
            'title' => "Template 1",
            "description" => "This is a image example template",
            "tags" => [
                "itk",
                "mock"
            ],
            "modified" => 1622555659,
            "created" => 1622552649,
            "modifiedBy" => "Jens Jensen",
            "createdBy" => "Ole Olensen",
            "icon" => "http://templates.itkdev.dk/image/icon.png",
            "resources" => [
                "component" => "http://templates.itkdev.dk/image/image.js",
                "assets" => [
                    [
                        "type" => "css",
                        "url" => "http://templates.itkdev.dk/image/image.css"
                    ]
                ],
                "options" => [
                    "fade" => true
                ],
                "content" => [
                    "text" => "This is a template placeholder text"
                ]
            ]
        ];
    }

    /**
     * Get template
     *
     * @param $id
     *   Media ID to fetch.
     *
     * @return Template|null
     *   Media object or null if not found.
     */
    public function getTemplate($id) : ?Template
    {
        $data = array_key_exists($id, $this->templates) ? $this->templates[$id] : null;
        if (!is_null($data)) {
            $template = new Template();
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'resources':
                        $template->addResource($value['component'], $value['assets'], $value['options'], $value['content']);
                        break;

                    default:
                        $func = 'set' . ucfirst($key);
                        $template->$func($value);
                }
            }
            return $template;
        }

        return null;
    }
}
