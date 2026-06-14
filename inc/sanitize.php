<?php
if (!defined('APP')) exit;

/**
 * HTML-fertőtlenítő (allow-list) a WYSIWYG szerkesztőből érkező tartalomhoz.
 * Csak biztonságos elemek/attribútumok maradnak meg; script, on* eseménykezelők,
 * javascript:/data: URL-ek és minden ismeretlen elem eltávolításra kerül.
 * Védelem a tárolt XSS ellen (akkor is, ha az admin fiók kompromittálódik).
 */

function sanitize_html($html) {
    $html = trim((string)$html);
    if ($html === '') return '';

    $allowed = array(
        'p' => array(), 'br' => array(),
        'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'u' => array(),
        's' => array(), 'sub' => array(), 'sup' => array(),
        'h2' => array(), 'h3' => array(), 'h4' => array(),
        'blockquote' => array(), 'pre' => array(), 'code' => array(),
        'ul' => array(), 'ol' => array(), 'li' => array(),
        'a' => array('href', 'title', 'target', 'rel'),
        'img' => array('src', 'alt', 'width', 'height'),
        'figure' => array(), 'figcaption' => array(),
        'span' => array(), 'hr' => array(),
    );

    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    // A <?xml encoding> előtag az UTF-8 helyes kezeléséhez kell.
    $wrapped = '<?xml encoding="UTF-8"><div>' . $html . '</div>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // A becsomagoló div az első elem-csomópont (getElementById nem megbízható itt).
    $root = null;
    foreach ($dom->childNodes as $n) {
        if ($n->nodeType === XML_ELEMENT_NODE) { $root = $n; break; }
    }
    if (!$root) return '';

    _sanitize_node($root, $allowed);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    return trim($out);
}

function _sanitize_node(DOMNode $node, array $allowed) {
    $blocked = array('script','style','iframe','object','embed','form','input',
        'button','textarea','select','option','link','meta','base','svg','math');

    $children = iterator_to_array($node->childNodes);
    foreach ($children as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower($child->nodeName);

            if (in_array($tag, $blocked, true)) {
                $child->parentNode->removeChild($child);
                continue;
            }

            if (!array_key_exists($tag, $allowed)) {
                // ismeretlen, de nem veszélyes elem: kibontjuk (tartalmat megtartjuk)
                _sanitize_node($child, $allowed);
                while ($child->firstChild) {
                    $child->parentNode->insertBefore($child->firstChild, $child);
                }
                $child->parentNode->removeChild($child);
                continue;
            }

            // engedélyezett elem: attribútumok szűrése
            if ($child->hasAttributes()) {
                $attrs = iterator_to_array($child->attributes);
                foreach ($attrs as $attr) {
                    $an = strtolower($attr->nodeName);
                    if (!in_array($an, $allowed[$tag], true)) {
                        $child->removeAttribute($attr->nodeName);
                        continue;
                    }
                    $av = trim($attr->nodeValue);
                    if (($an === 'href' || $an === 'src') && !_safe_url($av)) {
                        $child->removeAttribute($attr->nodeName);
                    }
                }
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener noreferrer');
            }

            _sanitize_node($child, $allowed);
        } elseif ($child->nodeType === XML_COMMENT_NODE) {
            $child->parentNode->removeChild($child);
        }
        // szöveg-csomópontokat meghagyjuk
    }
}

/** Engedélyezett URL-séma? (javascript:, data: stb. tiltva.) */
function _safe_url($url) {
    if ($url === '') return false;
    if (preg_match('#^(https?:)?//#i', $url)) return true;          // http(s) vagy protokoll-relatív
    if (preg_match('#^(mailto:|tel:)#i', $url)) return true;        // mailto / tel
    if ($url[0] === '/' || $url[0] === '.' || $url[0] === '#') return true; // relatív
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) return false;  // bármilyen más séma -> tilt
    return true;                                                    // egyszerű relatív útvonal
}
