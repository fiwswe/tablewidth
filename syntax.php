<?php

/**
 * Plugin TableWidth
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <dwpforge@gmail.com>
 */

class syntax_plugin_tablewidth extends DokuWiki_Syntax_Plugin {

    private $mode;

    public function __construct() {
        $this->mode = substr(get_class($this), 7);
    }

    public function getType() {
        return 'container';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 5;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('[\t ]*\n\|<[^\n]+?>\|(?=\s*?\n[|^])', $mode, $this->mode);
    }

    public function handle($match, $state, $pos, Doku_Handler $handler) {
        if ($state == DOKU_LEXER_SPECIAL) {
            if (preg_match('/\|<(\s*)(.+?)(\s*)>\|/', $match, $match) != 1) {
                return false;
            }

            // Sanitize the width spec to avoid injection of HTML tags and extra CSS declarations
            // and coalesce white space runs to single spaces
            $wspec = explode(' ', preg_replace('/\s+/u', ' ', $match[2]));
            $widthSpec = implode(' ', array_map(fn($w): string => $this->cleanCSSwidth($w), $wspec));
            $tableAlign = $this->getTableAlign($match[1], $match[3]);

            return array($tableAlign . $widthSpec);
        }

        return false;
    }

    /**
     * Return the cleaned CSS width value
     */
    private function cleanCSSwidth($width): string {
        // Do we need to remove < and > characters? Probably not because
        // in the context of a CSS width property value, they don't make sense.
        // However we need to prevent breaking out of the property value. So
        // prevent extra CSS properties (seperated by ;) and prevent ending the
        // style attribute value using a ".
        return trim(explode(';', explode('"', $width)[0])[0]);
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode == 'xhtml') {
            $renderer->doc .= '<!-- table-width ' . $data[0] . ' -->' . DOKU_LF;

            return true;
        }

        return false;
    }

    private function getTableAlign($paddingLeft, $paddingRight) {
        if (strlen($paddingLeft) > 1) {
            if (strlen($paddingRight) > 1) {
                return '>< ';
            }
            else {
                return '> ';
            }
        }
        else {
            if (strlen($paddingRight) > 1) {
                return '< ';
            }
        }

        return '';
    }
}
