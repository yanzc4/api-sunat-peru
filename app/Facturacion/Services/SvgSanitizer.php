<?php

declare(strict_types=1);

namespace App\Facturacion\Services;

use App\Facturacion\Exceptions\FacturacionException;
use DOMDocument;
use DOMElement;
use DOMNode;

final class SvgSanitizer
{
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'lineargradient', 'radialgradient', 'stop',
        'clippath', 'mask', 'pattern', 'image',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'xmlns', 'xmlns:xlink', 'id', 'class', 'viewbox', 'preserveaspectratio',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'width', 'height', 'd', 'points', 'transform',
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width',
        'stroke-opacity', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray',
        'opacity', 'color', 'offset', 'stop-color', 'stop-opacity',
        'gradientunits', 'gradienttransform', 'spreadmethod',
        'clip-path', 'clip-rule', 'mask', 'filter',
        'font-family', 'font-size', 'font-style', 'font-weight',
        'text-anchor', 'dominant-baseline', 'letter-spacing',
        'href', 'xlink:href',
    ];

    public function sanitizeFile(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new FacturacionException('No se pudo leer el archivo SVG');
        }

        return $this->sanitize($contents);
    }

    public function sanitize(string $svg): string
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
            throw new FacturacionException('El SVG contiene declaraciones no permitidas');
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $loaded = $document->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$document->documentElement ||
            strtolower($document->documentElement->localName) !== 'svg') {
            throw new FacturacionException('El archivo SVG no es válido');
        }

        $this->sanitizeChildren($document->documentElement);
        $this->sanitizeAttributes($document->documentElement);

        $result = $document->saveXML($document->documentElement);
        if ($result === false || trim($result) === '') {
            throw new FacturacionException('No se pudo sanear el archivo SVG');
        }

        return $result;
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                if (!in_array(strtolower($child->localName), self::ALLOWED_ELEMENTS, true)) {
                    $parent->removeChild($child);
                    continue;
                }

                $this->sanitizeAttributes($child);
                $this->sanitizeChildren($child);
                continue;
            }

            if (!in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE], true)) {
                $parent->removeChild($child);
            }
        }
    }

    private function sanitizeAttributes(DOMElement $element): void
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute;
        }

        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');

            if (str_starts_with($name, 'on') ||
                !in_array($name, self::ALLOWED_ATTRIBUTES, true) ||
                $this->isUnsafeValue($name, $value)) {
                $element->removeAttributeNode($attribute);
            }
        }
    }

    private function isUnsafeValue(string $name, string $value): bool
    {
        if (preg_match('/javascript\s*:|vbscript\s*:|data\s*:\s*text\/html/i', $value)) {
            return true;
        }

        if (in_array($name, ['href', 'xlink:href'], true)) {
            return !str_starts_with($value, '#') &&
                !preg_match('/^data:image\/(png|jpe?g|gif);base64,[a-z0-9+\/=\s]+$/i', $value);
        }

        if (preg_match('/url\s*\((.*?)\)/i', $value, $matches)) {
            $reference = trim($matches[1], " \t\n\r\0\x0B\"'");
            return !str_starts_with($reference, '#');
        }

        return false;
    }
}
