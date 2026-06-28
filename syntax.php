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
            $widthSpec = str_replace(array('<', '>', ';', '(', ')'), '', $match[2]);
            $tableAlign = $this->getTableAlign($match[1], $match[3]);

            return array($tableAlign . $widthSpec);
        }

        return false;
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
