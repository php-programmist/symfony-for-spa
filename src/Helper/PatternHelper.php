<?php /** @noinspection HtmlUnknownAttribute */


namespace App\Helper;


class PatternHelper
{
    //TODO убрать, как unione пофиксит баг с переносом строки в ссылках
    private const EMAIL_LINKS_HREF_TRANSFER_PATTERN = '/<a([^>]+)(?=href)([^>]+)\s*>/m';
    private const EMAIL_LINKS_HREF_TRANSFER_REPLACEMENT = '<a \\2 \\1>';

    /**
     * @param string $basePattern
     * @return string
     */
    public static function getHTMLPattern(string $basePattern): string
    {
        return substr($basePattern, 1, -1);
    }

    /**
     * @param string $body
     * @return string
     */
    public static function linksHrefPositionChange(string $body): string
    {
        return preg_replace(
            self::EMAIL_LINKS_HREF_TRANSFER_PATTERN,
            self::EMAIL_LINKS_HREF_TRANSFER_REPLACEMENT,
            $body
        );
    }
}
