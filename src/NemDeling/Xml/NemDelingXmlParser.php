<?php

declare(strict_types=1);

namespace App\NemDeling\Xml;

/**
 * Converts NemDeling XML payloads to the array shape expected by the data mappers.
 *
 * The structure mirrors express-xml-bodyparser used in integration-source.
 */
final class NemDelingXmlParser
{
    public function parse(string $xml): array
    {
        $document = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if (false === $document) {
            throw new \InvalidArgumentException('Invalid NemDeling XML payload.');
        }

        $rootName = $document->getName();
        $parsed = $this->convertNode($document);

        return [$rootName => $parsed];
    }

  /**
   * @return mixed
   */
    private function convertNode(\SimpleXMLElement $node)
    {
        $attributes = $node->attributes();
        $isArray = null !== $attributes && isset($attributes['is_array']) && 'true' === (string) $attributes['is_array'];

        $children = $node->children();
        if (null === $children || 0 === count($children)) {
            $value = trim((string) $node);

            $nodeAttributes = $node->attributes();
            if (null !== $nodeAttributes && count($nodeAttributes) > 0) {
                $attributeMap = ['$' => $this->convertAttributes($node)];
                if ('' !== $value) {
                    $attributeMap['_'] = $value;
                }

                return [$attributeMap];
            }

            return $value;
        }

        $grouped = [];
        foreach ($children as $child) {
            $name = $child->getName();
            $converted = $this->convertNode($child);

            if (!array_key_exists($name, $grouped)) {
                $grouped[$name] = $converted;
                continue;
            }

            if (!is_array($grouped[$name]) || !array_is_list($grouped[$name])) {
                $grouped[$name] = [$grouped[$name]];
            }

            $grouped[$name][] = $converted;
        }

        if ($isArray) {
            if (!array_key_exists('item', $grouped)) {
                return [[]];
            }

            $items = $grouped['item'];
            if (!is_array($items) || !array_is_list($items)) {
                $items = [$items];
            }

            return ['item' => $items];
        }

        return $grouped;
    }

    /**
     * @return array<string, string>
     */
    private function convertAttributes(\SimpleXMLElement $node): array
    {
        $attributes = [];
        $nodeAttributes = $node->attributes();
        if (null === $nodeAttributes) {
            return $attributes;
        }

        foreach ($nodeAttributes as $name => $value) {
            if ('is_array' === $name) {
                continue;
            }
            $attributes[$name] = (string) $value;
        }

        return $attributes;
    }
}
