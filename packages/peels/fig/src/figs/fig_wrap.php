<?php

function fig_wrap(string $tag, array $attr = [], string $content = '', bool $escape = true)
{
    $selfClosing = ['area', 'base', 'br', 'embed', 'hr', 'iframe', 'img', 'input', 'link', 'meta', 'param', 'source'];

    $html = '<' . $tag . ' ' . str_replace("=", '="', http_build_query($attr, '', '" ', PHP_QUERY_RFC3986)) . '">';

    if (!empty($content)) {
        $html .= ($escape) ? htmlentities($content) : $content;
    }

    if (!in_array($tag, $selfClosing)) {
        $html .= '</' . $tag . '>';
    }

    return $html;
}
