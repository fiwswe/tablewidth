<?php

/**
 * Plugin TableWidth
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Mykola Ostrovskyy <dwpforge@gmail.com>
 */

class action_plugin_tablewidth extends DokuWiki_Action_Plugin {

    /**
     * Register callbacks
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'replaceComments');
    }

    /**
     * Replace table-width comments by HTML
     */
    public function replaceComments(&$event, $param) {
        if ($event->data[0] == 'xhtml') {
            $pattern = '/(<!-- table-width [^\n]+? -->\n)([^\n]*<table.*?>)(\s*<t)/';
            $flags = PREG_SET_ORDER | PREG_OFFSET_CAPTURE;

            if (preg_match_all($pattern, $event->data[1], $match, $flags) > 0) {
                $start = 0;
                $html = '';

                foreach ($match as $data) {
                    $html .= substr($event->data[1], $start, $data[0][1] - $start);
                    $html .= $this->processTable($data);
                    $start = $data[0][1] + strlen($data[0][0]);
                }

                $event->data[1] = $html . substr($event->data[1], $start);
            }
        }
    }

    /**
     * Convert table-width comments and table mark-up into final HTML
     */
    private function processTable($data) {
        preg_match('/<!-- table-width ([^\n]+?) -->/', $data[1][0], $match);

        $width = preg_split('/\s+/', $match[1]);
        $tableAlign = preg_match('/[<>]+/', $width[0]) == 1 ? array_shift($width) : '-';
        $tableWidth = array_shift($width);

        if ($tableWidth != '-' || $tableAlign != '-') {
            $table = $this->styleTable($data[2][0], $tableWidth, $tableAlign);
        }
        else {
            $table = $data[2][0];
        }

        return $table . $this->renderColumns($width) . $data[3][0];
    }

    /**
     * Add width and align styles to the table
     */
    private function styleTable($html, $width, $align) {
        preg_match('/^([^\n]*<table)(.*?)(>)$/', $html, $match);

        $entry = $match[1];
        $attributes = $match[2];
        $exit = $match[3];

        $widthStyle = $this->getTableWidthStyle($width);
        $alignStyle = $this->getTableAlignStyle($align);
        $tableStyle = implode(' ', array_filter([$widthStyle, $alignStyle]));

        if (preg_match('/(.*?style\s*=\s*(["\']).*?)(\2.*)/', $attributes, $match) == 1) {
            $attributes = $match[1] . '; ' . $tableStyle . $match[3];
        }
        else {
            $attributes .= ' style="' . $tableStyle . '"';
        }

        return $entry . $attributes . $exit;
    }

    /**
     * Return table width style
     */
    private function getTableWidthStyle($width) {
        if ($width != '-') {
            return 'min-width: 0; width: ' . $width . ';';
        }

        return '';
    }

    /**
     * Return table align style
     */
    private function getTableAlignStyle($align) {
        switch ($align) {
            case '><':
                return 'margin-left: auto; margin-right: auto;';
            case '>':
                return 'margin-left: auto; margin-right: 0;';
            case '<':
                return 'margin-left: 0; margin-right: auto;';
        }

        return '';
    }

    /**
     * Render column tags
     */
    private function renderColumns($width) {
        $html = DOKU_LF;

        if (!empty($width)) {
            $html .= '<colgroup>';
            foreach ($width as $w) {
                if ($w != '-') {
                    $html .= '<col style="width: ' . $w . '" />';
                }
                else {
                    $html .= '<col />';
                }
            }
            $html .= '</colgroup>';
        }

        return $html;
    }
}
